<?php

/**
 * SI Record Model
 *
 * @package Sprout_Invoices
 * @subpackage Record
 */
class SI_Record extends SI_Post_Type {

	const POST_TYPE = 'sa_record';
	const TAXONOMY = 'sa_record_type';
	const DEFAULT_TYPE = 'no_type_set';

	private static $instances = array();

	public static function init() {
		$post_type_args = array(
			'public' => false,
			'has_archive' => false,
			'show_in_menu' => false,
			'rewrite' => false,
			'supports' => array(),
		);
		self::register_post_type( self::POST_TYPE, 'Record', 'Records', $post_type_args );

		// register Locations taxonomy
		$singular = 'Record Type';
		$plural = 'Record Types';
		$taxonomy_args = array(
			'hierarchical' => true,
			'public' => false,
			'show_ui' => false,
		);
		self::register_taxonomy( self::TAXONOMY, array( self::POST_TYPE ), $singular, $plural, $taxonomy_args );

		self::flush_cache_hooks();
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return SI_Record
	 */
	public static function get_instance( $id = 0 ) {
		if ( ! $id ) {
			return null;
		}

		if ( ! isset( self::$instances[ $id ] ) || ! self::$instances[ $id ] instanceof self ) {
			self::$instances[ $id ] = new self( $id );
		}

		if ( ! isset( self::$instances[ $id ]->post->post_type ) ) {
			return null;
		}

		if ( self::$instances[ $id ]->post->post_type !== self::POST_TYPE ) {
			return null;
		}

		return self::$instances[ $id ];
	}

	/**
	 * Executed after all the records data has been completed.
	 */
	public function activate() {
		//$this->post->post_status = 'publish';
		//$this->save_post();
		do_action( 'record_activated', $this );
	}

	/**
	 *
	 *
	 * @return int The ID of the content associated with this record
	 */
	public function get_associate_id() {
		$associate_id = $this->post->post_parent;
		return $associate_id;
	}

	/**
	 * Associate this record with content
	 *
	 * @param int     $id The new value
	 * @return int The ID of the content associated with this record
	 */
	public function set_associate_id( $associate_id ) {
		$this->post->post_parent = $associate_id;
		$this->save_post();
		return $associate_id;
	}

	/**
	 *
	 *
	 * @return array The data
	 */
	public function get_data() {
		$content = json_decode( $this->post->post_content );
		if ( $content === null ) { // isn't json
			return $this->post->post_content;
		}
		return (array) $content;
	}

	/**
	 * Set data
	 *
	 * @param array   The data
	 * @return array The data
	 */
	public function set_data( $data, $encode = true ) {
		// __sleep preventing will prevent some objects from serializing
		$this->post->post_content = ( $encode ) ? wp_json_encode( $data ) : $data ;
		$this->save_post();
		return $data;
	}


	/**
	 *
	 *
	 * @return array The type
	 */
	public function get_type() {
		$terms = wp_get_object_terms( $this->ID, self::TAXONOMY );
		if ( empty( $terms ) ) {
			return $this->set_type( self::DEFAULT_TYPE );
		}
		$type_term = array_pop( $terms );
		return $type_term->slug;
	}

	/**
	 * Set type
	 *
	 * @param array   The type
	 * @return array The type
	 */
	public function set_type( $type ) {
		$slug = self::maybe_add_type( $type );
		wp_set_object_terms( $this->ID, $slug, self::TAXONOMY );
		return $slug;
	}

	/**
	 * Check if type exists as a term, if not create one.
	 *
	 * @param string  $type
	 * @return
	 */
	public static function maybe_add_type( $type = '', $name = '' ) {
		$type = ( '' === $type  ) ? self::DEFAULT_TYPE : $type ;
		$term = get_term_by( 'slug', $type, self::TAXONOMY );
		if ( empty( $term ) ) {
			$name = ( '' !== $name ) ? $name : $type;
			$new_term = wp_insert_term(
				$name, // the term name
				self::TAXONOMY, // the taxonomy
				array( 'slug' => $type )
			);
			if ( is_array( $new_term ) && isset( $new_term['term_id'] ) ) {
				$term = get_term_by( 'id', $new_term['term_id'], self::TAXONOMY );
			}
		}
		return $term->slug;
	}

	public static function register_type( $type, $name = '' ) {
		$type_slug = self::maybe_add_type( $type, $name );
		return $type_slug;
	}

	/**
	 *
	 *
	 * @param int     $type the associate content id
	 * @return array List of IDs for records of this type
	 */
	public static function get_records_by_type_and_association( $associate_id, $type ) {
		// see if we've cached the result
		$cache_key = 'si_find_records_by_type_and_assoc_id';
		$cache_index = $type.$associate_id;
		$cache = wp_cache_get( $cache_key, 'si' );
		if ( is_array( $cache ) && isset( $cache[ $cache_index ] ) ) {
			return $cache[ $cache_index ];
		}

		$args = array(
			'post_type' => self::POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'post_parent' => $associate_id,
			'fields' => 'ids',
			'si_bypass_filter' => true,
			self::TAXONOMY => $type,
		);
		$result = get_posts( $args );
		// Set cache
		$cache[ $cache_index ] = $result;
		wp_cache_set( $cache_key, $cache, 'si' );

		return $result;
	}


	/**
	 *
	 *
	 * @param int     $type the associate content id
	 * @return array List of IDs for records of this type
	 */
	public static function get_records_by_type( $type ) {
		// see if we've cached the result
		$cache_key = 'si_find_records_by_type';
		$cache_index = $type;
		$cache = wp_cache_get( $cache_key, 'si' );
		if ( is_array( $cache ) && isset( $cache[ $cache_index ] ) ) {
			return $cache[ $cache_index ];
		}

		$args = array(
			'post_type' => self::POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'si_bypass_filter' => true,
			self::TAXONOMY => $type,
		);

		$result = get_posts( $args );

		// Set cache
		$cache[ $cache_index ] = $result;
		wp_cache_set( $cache_key, $cache, 'si' );

		return $result;
	}

	/**
	 *
	 *
	 * @param int     $associate_id the associate content id
	 * @return array List of IDs for records with this association
	 */
	public static function get_records_by_association( $associate_id ) {
		// see if we've cached the result
		$cache_key = 'si_find_records_by_assoc_id';
		$cache_index = $associate_id;
		$cache = wp_cache_get( $cache_key, 'si' );
		if ( is_array( $cache ) && isset( $cache[ $cache_index ] ) ) {
			return $cache[ $cache_index ];
		}

		$args = array(
			'post_type' => self::POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'post_parent' => $associate_id,
			'fields' => 'ids',
			'si_bypass_filter' => true,
		);

		$result = get_posts( $args );

		// Set cache
		$cache[ $cache_index ] = $result;
		wp_cache_set( $cache_key, $cache, 'si' );

		return $result;
	}

	/**
	 * Flush cache when a new record is created.
	 * @return null
	 */
	public static function flush_cache_hooks() {
		add_action( 'wp_insert_post', array( __CLASS__, 'flush_cache_on_meta_update' ), 10, 3 );
	}

	/**
	 * Clear the cache since a new record is created.
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated or not.
	 */
	public static function flush_cache_on_meta_update( $post_ID, $post, $update ) {
		if ( $update ) { // Don't flush when a post is just updated.
			return;
		}
		if ( $post->post_type !== self::POST_TYPE ) { // Don't flush cache unless the post being created is a record.
			return;
		}
		wp_cache_delete( 'si_find_records_by_type_and_assoc_id', 'si' );
		wp_cache_delete( 'si_find_records_by_type', 'si' );
		wp_cache_delete( 'si_find_records_by_assoc_id', 'si' );
	}
}
