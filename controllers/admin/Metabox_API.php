<?php

/**
 * Metabox meta controller.
 *
 * Add APIs for easily adding admin menus and meta boxes.
 *
 * @package Sprout_Invoice
 * @subpackage Settings
 */
class SI_Metabox_API extends SI_Controller {
	private static $meta_boxes = array();

	public static function init() {

		// Register meta box
		add_action( 'sprout_meta_box', array( __CLASS__, 'register_meta_box' ), 10, 2 );

		// add meta boxes
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );

		// save meta boxes
		add_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
	}


	/**
	 * Registered meta boxes for all post types, including the si_deal post type.
	 *
	 * @param  array  $registered_boxes array of registered metaboxes
	 * @param  string/array $post_types             post type(s)
	 * @return null 		                  modifies class variable for all pt metaboxes
	 */
	public static function register_meta_box( $registered_boxes = array(), $post_types = array() ) {
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types ); // convert a string into an array.
		}
		foreach ( $post_types as $post_type ) {
			$defaults = array(
					'title' => 'Settings',
					'callback' => array( __CLASS__, 'show_meta_box' ),
					'screen' => $post_type,
					'context' => 'normal',
					'priority' => 'high',
					'callback_args' => array(),
					'weight' => 10,
					'save_priority' => 10,
				);

			if ( ! isset( self::$meta_boxes[ $post_type ] ) ) {
				self::$meta_boxes[ $post_type ] = array();
			}
			foreach ( $registered_boxes as $box_name => $args ) {
				$registered_boxes[ $box_name ] = wp_parse_args( $args, $defaults );
			}
			self::$meta_boxes[ $post_type ] = wp_parse_args( self::$meta_boxes[ $post_type ], $registered_boxes );
		}
	}

	/**
	 * loop through registered meta boxes and use the add_meta_box WP function.
	 *
	 */
	public static function add_meta_boxes() {
		// Loop through all registered meta boxes
		foreach ( self::$meta_boxes as $post_type => $meta_boxes ) {
			// Sort boxes based on weight before priority
			uasort( $meta_boxes, array( __CLASS__, 'sort_by_weight' ) );
			// Loop through each meta box registered under this type.
			foreach ( $meta_boxes as $metabox_name => $args ) {
				$args = apply_filters( $metabox_name . '_meta_box_args', $args );
				extract( $args );
				add_meta_box( $metabox_name, __( $title, 'sprout-invoices' ), $callback, $screen, $context, $priority, $args );
			}
		}
	}

	/**
	 * Show the meta box using the registered callback.
	 *
	 * @param  object $post
	 * @param  array $meta_box
	 */
	public static function show_meta_box( $post, $meta_box ) {
		if ( $is_callable = is_callable( $meta_box['args']['show_callback'] ) ) {
			do_action( implode( '::', $meta_box['args']['show_callback'] ), $post, $meta_box );
			call_user_func_array( $meta_box['args']['show_callback'], array( $post, $meta_box ) );
			do_action( implode( '::', $meta_box['args']['show_callback'] ), $post, $meta_box );
		} else {
			if ( method_exists( $meta_box['args']['show_callback'][0], $meta_box['args']['show_callback'][1] ) ) {
				do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' - callback may be private.', $meta_box );
			}
		}
	}

	/**
	 * Attempt to save all registered meta boxes.
	 *
	 * @param  int $post_id
	 * @param  object $post
	 * @return
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		// Don't save meta boxes when the importer is used.
		if ( isset( $_GET['import'] ) && $_GET['import'] == 'wordpress' ) {
			return;
		}

		if ( ! isset( $_POST['action'] ) || 'editpost' !== $_POST['action'] ) {
			return;
		}

		// don't do anything on autosave, auto-draft, bulk edit, or quick edit
		if ( wp_is_post_autosave( $post_id ) || $post->post_status == 'auto-draft' || defined( 'DOING_AJAX' ) || isset( $_GET['bulk_edit'] ) ) {
			return;
		}

		foreach ( self::$meta_boxes as $post_type => $post_meta_boxes ) {
			// Only save the meta boxes that count
			if ( $post->post_type == $post_type ) {
				// Sort by saved weight
				uasort( $post_meta_boxes, array( __CLASS__, 'sort_by_save_weight' ) );
				// Loop through each meta box registered under this type.
				foreach ( $post_meta_boxes as $box_name => $args ) {
					if ( isset( $args['save_callback'] ) && is_array( $args['save_callback'] ) ) {
						if ( is_callable( $args['save_callback'] ) ) {
							$callback_args = ( ! isset( $args['save_callback_args'] ) ) ? array() : $args['save_callback_args'] ;

							$action_name = implode( '::', $args['save_callback'] );
							if ( did_action( $action_name ) >= 1 ) {
								return;
							}
							// execute
							call_user_func_array( $args['save_callback'], array( $post_id, $post, $callback_args ) );
							// action
							do_action( $action_name, $post_id, $post, $callback_args );
						} elseif ( method_exists( $args['save_callback'][0], $args['save_callback'][1] ) ) {
							do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' - callback may be private.', $args );
						}
					}
				}
			}
		}
	}

	/**
	 * Comparison function
	 */
	public static function sort_by_save_weight( $a, $b ) {
		if ( ! isset( $a['save_priority'] ) || ! isset( $b['save_priority'] ) ) {
			return 0; }

		if ( $a['save_priority'] == $b['save_priority'] ) {
			return 0;
		}
		return ( $a['save_priority'] < $b['save_priority'] ) ? -1 : 1;
	}
}

