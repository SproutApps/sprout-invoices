<?php

/**
 * SI Post types. A model that all post types derive from.
 *
 * @package Sprout_Invoices
 * @subpackage Model
 */
abstract class SI_Post_Type extends Sprout_Invoices {
	private static $post_types_to_register = array();
	private static $taxonomies_to_register = array();

	protected $ID;
	protected $post;
	protected $post_meta = array();



	/* =============================================================
	 * Class methods
	 * ============================================================= */

	/**
	 * Tracks all the post types registered by sub-classes, and hooks into WP to register them
	 *
	 * @static
	 * @param string  $post_type
	 * @param string  $singular
	 * @param string  $plural
	 * @param array   $args
	 * @return void
	 */
	protected static function register_post_type( $post_type, $singular = '', $plural = '', $args = array() ) {
		self::add_register_post_types_hooks();

		if ( ! $singular ) {
			$singular = $post_type;
		}
		if ( ! $plural ) {
			$plural = $singular.'s';
		}
		$defaults = array(
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'public' => true,
			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions' ),
			'label' => __( $plural, 'sprout-invoices' ),
			'labels' => self::post_type_labels( $singular, $plural ),
		);
		$args = wp_parse_args( $args, $defaults );
		if ( isset( self::$post_types_to_register[ $post_type ] ) ) {
			do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' - Attempting to re-register post type', $post_type );
			return;
		}
		self::$post_types_to_register[ $post_type ] = $args;
	}

	/**
	 * Generate a set of labels for a post type
	 *
	 * @static
	 * @param string  $singular
	 * @param string  $plural
	 * @return array All the labels for the post type
	 */
	private static function post_type_labels( $singular, $plural ) {
		return array(
			'name' => __( $plural, 'sprout-invoices' ),
			'singular_name' => __( $singular, 'sprout-invoices' ),
			'add_new' => __( 'Add ' . $singular, 'sprout-invoices' ),
			'add_new_item' => __( 'Add New ' . $singular, 'sprout-invoices' ),
			'edit_item' => __( 'Edit ' . $singular, 'sprout-invoices' ),
			'new_item' => __( 'New ' . $singular, 'sprout-invoices' ),
			'all_items' => __( $plural, 'sprout-invoices' ),
			'view_item' => __( 'View ' . $singular, 'sprout-invoices' ),
			'search_items' => __( 'Search ' . $plural, 'sprout-invoices' ),
			'not_found' => __( 'No ' . $plural . ' found', 'sprout-invoices' ),
			'not_found_in_trash' => __( 'No ' . $plural . ' found in Trash', 'sprout-invoices' ),
			'menu_name' => __( $plural, 'sprout-invoices' ),
		);
	}

	/**
	 * Add the hooks necessary to register post types at the right time
	 *
	 * @return void
	 */
	private static function add_register_post_types_hooks() {
		static $registered = false; // only do it once
		if ( ! $registered ) {
			$registered = true;
			add_action( 'init', array( __CLASS__, 'register_post_types' ) );
			add_action( 'template_redirect', array( __CLASS__, 'context_fixer' ) );
			add_filter( 'body_class', array( __CLASS__, 'body_classes' ) );
			add_filter( 'post_updated_messages', array( __CLASS__, 'update_messages' ) );
		}
	}

	/**
	 * Register each queued up post type
	 *
	 * @static
	 * @var array $args Filter: Post type args filter (by taxonomy) with si_register_post_type_args-[taxonomy]
	 * @var array $args Filter: Post type args filter with si_register_post_type_args
	 * @return void
	 */
	public static function register_post_types() {
		foreach ( self::$post_types_to_register as $post_type => $args ) {
			$args = apply_filters( 'si_register_post_type_args-'.$post_type, $args );
			$args = apply_filters( 'si_register_post_type_args', $args, $post_type );
			register_post_type( $post_type, $args );
		}
	}

	/**
	 * is_home should be false if on a managed post type
	 *
	 * @return void
	 */
	public static function context_fixer() {
		if ( in_array( get_query_var( 'post_type' ), array_keys( self::$post_types_to_register ) ) ) {
			global $wp_query;
			$wp_query->is_home = false;
		}
	}

	/**
	 * If a managed post type is queried, add the post type to body classes
	 *
	 * @static
	 * @param array   $c classes
	 * @return array
	 */
	public static function body_classes( $c ) {
		$query_post_type = get_query_var( 'post_type' );
		if ( in_array( $query_post_type, array_keys( self::$post_types_to_register ) ) ) {
			$c[] = $query_post_type;
			$c[] = 'type-' . $query_post_type;
		}
		return $c;
	}

	public static function update_messages( $messages ) {
		foreach ( self::$post_types_to_register as $post_type => $args ) {
			$messages[ $post_type ] = self::post_type_messages( $post_type, $args );
		}

		return $messages;
	}

	/**
	 * Generate a set of messages for a post type
	 *
	 * @static
	 * @param string  $name
	 * @return array All the update messages for the post type
	 */
	private static function post_type_messages( $name, $args ) {
		global $post, $post_id;
		switch ( $name ) {
			default:
				$name = str_replace( 'sa_', '', $name );
				break;
		}
		if ( $args['public'] ) {
			$messages = array(
				0 => '', // Unused. Messages start at index 1.
				1 => sprintf( __( '%s updated. <a href="%s">View %s</a>', 'sprout-invoices' ), ucfirst( $name ), esc_url( get_permalink( $post_id ) ), $name ),
				2 => __( 'Custom field updated.', 'sprout-invoices' ),
				3 => __( 'Custom field deleted.', 'sprout-invoices' ),
				4 => sprintf( __( '%s updated.', 'sprout-invoices' ), ucfirst( $name ) ),
				/* translators: %s: date and time of the revision */
				5 => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s', 'sprout-invoices' ), ucfirst( $name ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __( '%s published. <a href="%s">View %s</a>', 'sprout-invoices' ), ucfirst( $name ), esc_url( get_permalink( $post_id ) ), $name ),
				7 => sprintf( __( '%s saved.', 'sprout-invoices' ), ucfirst( $name ) ),
				8 => sprintf( __( '%s submitted. <a target="_blank" href="%s">Preview %s</a>', 'sprout-invoices' ), ucfirst( $name ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ), $name ),
				9 => sprintf( __( '%s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview %s</a>', 'sprout-invoices' ), $name, date_i18n( __( 'M j, Y @ G:i', 'sprout-invoices' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_id ) ), $name ),
				10 => sprintf( __( '%s draft updated. <a target="_blank" href="%s">Preview %s</a>', 'sprout-invoices' ), ucfirst( $name ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ), $name ),
			);
		} else { // post types that are not public should not have links to a post
			$messages = array(
				0 => '', // Unused. Messages start at index 1.
				1 => sprintf( __( '%s updated.', 'sprout-invoices' ), ucfirst( $name ) ),
				2 => __( 'Custom field updated.', 'sprout-invoices' ),
				3 => __( 'Custom field deleted.', 'sprout-invoices' ),
				4 => sprintf( __( '%s updated.', 'sprout-invoices' ), ucfirst( $name ) ),
				/* translators: %s: date and time of the revision */
				5 => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s', 'sprout-invoices' ), ucfirst( $name ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __( '%s published.', 'sprout-invoices' ), ucfirst( $name ) ),
				7 => sprintf( __( '%s saved.', 'sprout-invoices' ), ucfirst( $name ) ),
				8 => sprintf( __( '%s submitted.', 'sprout-invoices' ), ucfirst( $name ) ),
				9 => sprintf( __( '%s scheduled for: <strong>%1$s</strong>.', 'sprout-invoices' ), ucfirst( $name ), date_i18n( __( 'M j, Y @ G:i', 'sprout-invoices' ), strtotime( $post->post_date ) ) ),
				10 => sprintf( __( '%s draft updated.', 'sprout-invoices' ), ucfirst( $name ) ),
			);
		}

		return $messages;
	}

	/**
	 * Tracks all the taxonomies registered by sub-classes, and hooks into WP to register them
	 *
	 * @static
	 * @param string  $taxonomy   taxonomy slug
	 * @param array   $post_types array of posts types to associate taxonomy with
	 * @param string  $singular   singular name for labels
	 * @param string  $plural     plural name for labels
	 * @param array   $args       taxonomy args
	 * @return void
	 */
	protected static function register_taxonomy( $taxonomy, $post_types, $singular = '', $plural = '', $args = array() ) {
		self::add_register_taxonomies_hooks();

		if ( ! $singular ) {
			$singular = $taxonomy;
		}
		if ( ! $plural ) {
			$plural = $singular.'s';
		}
		$defaults = array(
			'hierarchical' => true,
			'labels' => self::taxonomy_labels( $singular, $plural ),
			'show_ui' => true,
			'query_var' => true,
			'show_in_nav_menus' => false,
		);
		$args = wp_parse_args( $args, $defaults );
		if ( isset( self::$taxonomies_to_register[ $taxonomy ] ) ) {
			do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' - Attempting to re-register taxonomy', $taxonomy );
			return;
		}
		self::$taxonomies_to_register[ $taxonomy ] = array(
			'post_types' => $post_types,
			'args' => $args,
		);
	}

	private static function taxonomy_labels( $singular, $plural ) {
		return array(
			'name' => __( $plural, 'sprout-invoices' ),
			'singular_name' => __( $singular, 'sprout-invoices' ),
			'search_items' => __( 'Search '.$plural, 'sprout-invoices' ),
			'popular_items' => __( 'Popular '.$plural, 'sprout-invoices' ),
			'all_items' => __( 'All '.$plural, 'sprout-invoices' ),
			'parent_item' => __( 'Parent '.$singular, 'sprout-invoices' ),
			'parent_item_colon' => __( 'Parent '.$singular.':', 'sprout-invoices' ),
			'edit_item' => __( 'Edit '.$singular, 'sprout-invoices' ),
			'update_item' => __( 'Update '.$singular, 'sprout-invoices' ),
			'add_new_item' => __( 'Add New '.$singular, 'sprout-invoices' ),
			'new_item_name' => __( 'New '.$singular.' Name', 'sprout-invoices' ),
			'menu_name' => __( $plural, 'sprout-invoices' ),
		);
	}

	/**
	 * Add the hooks necessary to register post types at the right time
	 *
	 * @return void
	 */
	private static function add_register_taxonomies_hooks() {
		static $registered = false; // only do it once
		if ( ! $registered ) {
			$registered = true;
			add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
		}
	}

	/**
	 * Register each queued up taxonomy.
	 *
	 * @var array $post_types Filter: Taxonomy registered post_types filter si_register_taxonomy_post_types-[taxonomy]
	 * @var array $args Filter: Taxonomy args filter (by taxonomy) with si_register_taxonomy_args-[taxonomy]
	 * @var array $args Filter: Taxonomy args filter with si_register_taxonomy_args
	 *
	 * @static
	 * @return void
	 */
	public static function register_taxonomies() {
		foreach ( self::$taxonomies_to_register as $taxonomy => $data ) {
			$post_types = apply_filters( 'si_register_taxonomy_post_types-'.$taxonomy, $data['post_types'], $data['args'], $data );
			$args = apply_filters( 'si_register_taxonomy_args-'.$taxonomy, $data['args'], $data['post_types'], $data );
			$args = apply_filters( 'si_register_taxonomy_args', $args, $taxonomy, $data['post_types'], $data );
			register_taxonomy( $taxonomy, $post_types, $args );
		}
	}


	/* =============================================================
	 * Instance methods
	 * ============================================================= */
	/*
	 * Multiton Design Pattern
	 * ------------------------------------------------------------- */
	final protected function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}

	final protected function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}

	/**
	 *
	 *
	 * @param int     $id The ID of the post
	 */
	protected function __construct( $id ) {
		$this->ID = $id;
		$this->refresh();
		$this->register_update_hooks();
	}

	public function get_id() {
		return $this->ID;
	}

	public function __destruct() {
		$this->unregister_update_hooks();
	}

	/**
	 * Update with fresh data from the database
	 *
	 * @return void
	 */
	protected function refresh() {
		$this->load_post();
	}

	/**
	 * Update the post
	 *
	 * @return void
	 */
	protected function load_post() {
		$this->post = get_post( $this->ID );
	}

	protected function save_post() {
		wp_update_post( $this->post );
	}

	/**
	 * Watch for updates to the post or its meta
	 *
	 * @return void
	 */
	protected function register_update_hooks() {
		add_action( 'save_post', array( $this, 'post_updated' ), 1000, 2 );
	}

	/**
	 * I'm dying, don't talk to me.
	 *
	 * @return void
	 */
	protected function unregister_update_hooks() {
		remove_action( 'save_post', array( $this, 'post_updated' ), 1000, 2 );
	}

	/**
	 * A post was updated. Refresh if necessary.
	 *
	 * @param int     $post_id The ID of the post that was updated
	 * @param object  $post
	 * @return void
	 */
	public function post_updated( $post_id, $post ) {
		if ( $post_id == $this->ID ) {
			$this->refresh();
		}
	}

	/**
	 * A post's meta was updated. Refresh if necessary.
	 *
	 * @param int     $meta_id
	 * @param int     $post_id
	 * @param string  $meta_key
	 * @param mixed   $meta_value
	 * @return void
	 */
	public function post_meta_updated( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( $post_id == $this->ID ) {
			$this->refresh();
		}
	}

	/**
	 * Get the post object
	 *
	 * @return object
	 */
	public function get_post() {
		return $this->post;
	}


	/**
	 * Set the title of the post and save
	 *
	 * @param string  $title
	 * @return void
	 */
	public function set_title( $title ) {
		$this->post->post_title = $title;
		$this->save_post();
	}


	/**
	 * Get the title of the post
	 *
	 * @return string
	 */
	public function get_title() {
		return str_replace( __( 'Auto Draft' ), '', $this->post->post_title );
	}


	/**
	 * Set the content of the post and save
	 *
	 * @param string  $content
	 * @return void
	 */
	public function set_content( $content ) {
		$this->post->post_content = $content;
		$this->save_post();
	}


	/**
	 * Get the content of the post
	 *
	 * @return string
	 */
	public function get_content() {
		return $this->post->post_content;
	}


	/**
	 * Set the excerpt of the post and save
	 *
	 * @param string  $excerpt
	 * @return void
	 */
	public function set_excerpt( $excerpt ) {
		$this->post->post_excerpt = $excerpt;
		$this->save_post();
	}


	/**
	 * Get the excerpt of the post
	 *
	 * @return string
	 */
	public function get_excerpt() {
		return $this->post->post_excerpt;
	}


	/**
	 * Get the post_date of the post
	 *
	 * @return string
	 */
	public function get_post_date() {
		return $this->post->post_date;
	}


	/**
	 * Set the post_date of the post and save
	 *
	 * @param string  $post_date
	 * @return void
	 */
	public function set_post_date( $post_date ) {
		$this->post->post_date = $post_date;
		$this->post->post_date_gmt = get_gmt_from_date( $post_date );
		$this->save_post();
	}

	/**
	 * Saves the given meta key/value pairs to the post.
	 *
	 * By default, keys will be unique per post. Override in a child class to change this.
	 *
	 * @param array   $meta An associative array of meta keys and their values to save
	 * @return void
	 */
	public function save_post_meta( $meta = array(), $trim = true ) {
		foreach ( $meta as $key => $value ) {
			if ( $trim ) {
				$value = self::trim_input( $value );
			}
			update_post_meta( $this->ID, $key, $value );
		}
	}

	public function add_post_meta( $meta = array(), $unique = false ) {
		foreach ( $meta as $key => $value ) {
			add_post_meta( $this->ID, $key, $value, $unique );
		}
	}

	public function delete_post_meta( $meta ) {
		foreach ( $meta as $key => $value ) {
			delete_post_meta( $this->ID, $key, $value );
		}
	}

	/**
	 * Returns post meta about the post
	 *
	 * @param string|null $meta_key A string indicating which meta key to retrieve, or null to return all keys
	 * @param bool    $single   true to return the first value, false to return an array of values
	 * @return string|array
	 */
	public function get_post_meta( $meta_key = null, $single = true ) {
		if ( $meta_key !== null ) { // get a single field
			return get_post_meta( $this->ID, $meta_key, $single );
		} else {
			return get_post_custom( $this->ID );
		}
	}

	/**
	 * Add a file as a post attachment.
	 * @param array $files
	 * @param string $key
	 */
	public function set_attachement( $files, $key = '' ) {
		if ( function_exists( 'ga_load_wp_media' ) ) { // Allow for easy overrides.
			ga_load_wp_media();
		} elseif ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin' . '/includes/image.php';
			require_once ABSPATH . 'wp-admin' . '/includes/file.php';
			require_once ABSPATH . 'wp-admin' . '/includes/media.php';
		}
		$attach_ids = array();
		foreach ( $files as $file => $array ) {
			if ( $files[ $file ]['error'] !== UPLOAD_ERR_OK ) {
				// show error?
			}
			if ( $key !== '' ) {
				if ( $key == $file  ) {
					$attach_ids[] = media_handle_upload( $file, $this->ID );
				}
			} else {
				$attach_ids[] = media_handle_upload( $file, $this->ID );
			}
		}
		return $attach_ids;
	}

	/**
	 * Trim inputs and arrays
	 * @param  string/array $value value/s to trim
	 * @return
	 */
	public static function trim_input( $value ) {
		if ( is_object( $value ) ) {
			return $value; }

		if ( is_array( $value ) ) {
			$return = array();
			foreach ( $value as $k => $v ) {
				if ( is_object( $v ) ) {
					$return[ $k ] = $v;
					continue;
				}
				$return[ $k ] = is_array( $v ) ? self::trim_input( $v ) : trim( $v );
			}
			return $return;
		}
		return trim( $value );
	}

	/**
	 * Find all posts in the given post type with matching meta
	 *
	 * @static
	 * @param string  $post_type
	 * @param array   $meta
	 * @return array
	 */
	public static function find_by_meta( $post_type, $meta = array(), $serialized_value = false ) {
		$cache = array();
		$cache_index = 0;
		$cache_key = 0;
		// see if we've cached the result
		if ( count( $meta ) == 1 ) {
			$array_keys = array_keys( $meta );
			$array_values = array_values( $meta );
			$cache_key = 'si_find_by_meta_'.$post_type.'_'.reset( $array_keys );
			$cache_index = reset( $array_values );
			if ( $cache_index ) {
				$cache = wp_cache_get( $cache_key, 'si' );
				if ( is_array( $cache ) && isset( $cache[ $cache_index ] ) ) {
					return $cache[ $cache_index ];
				}
			}
		}

		// Optionally bypass the standard db call
		$result = apply_filters( 'si_find_by_meta', null, $post_type, $meta );

		if ( ! is_array( $result ) ) {
			$args = array(
				'post_type' => $post_type,
				'post_status' => 'any',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'si_bypass_filter' => true,
			);

			if ( ! $serialized_value ) {
				if ( ! empty( $meta ) ) {
					foreach ( $meta as $key => $value ) {
						$args['meta_query'][] = array(
							'key' => $key,
							'value' => $value,
						);
					}
				}
			} else {
				if ( ! empty( $meta ) ) {
					foreach ( $meta as $key => $value ) {
						$args['meta_query'][] = array(
							'key' => $key,
							'value' => sprintf( ':"%s";', $value ),
							'compare' => 'LIKE',
						);
					}
				}
			}
			$result = get_posts( $args );
		}

		if ( count( $meta ) == 1 && $cache_index ) {
			$cache[ $cache_index ] = $result;
			wp_cache_set( $cache_key, $cache, 'si' );
		}

		return $result;
	}

	/**
	 * Flush cache when post is updated.
	 * @return null
	 */
	public static function init() {
		add_action( 'added_post_meta', array( __CLASS__, 'flush_cache_on_meta_update' ), 10, 3 );
		add_action( 'updated_post_meta', array( __CLASS__, 'flush_cache_on_meta_update' ), 10, 3 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'flush_cache_on_meta_update' ), 10, 3 );
	}

	public static function flush_cache_on_meta_update( $meta_id, $object_id, $meta_key ) {
		self::flush_find_by_meta_cache( $meta_key, get_post_type( $object_id ) );
	}

	private static function flush_find_by_meta_cache( $meta_key, $post_type ) {
		wp_cache_delete( 'si_find_by_meta_'.$post_type.'_'.$meta_key, 'si' );
	}
}
