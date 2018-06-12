<?php

/**
 * A base class from which all other controllers should be derived
 *
 * @package Sprout_Invoices
 * @subpackage Controller
 */
abstract class SI_Controller extends Sprout_Invoices {
	const SHORTCODE = 'si_blank';
	const MESSAGE_STATUS_INFO = 'info';
	const MESSAGE_STATUS_ERROR = 'error';
	const MESSAGE_META_KEY = 'sa_messages';
	const PRIVATE_NOTES_TYPE = 'sa_private_notes';
	const CRON_HOOK = 'si_cron';
	const DAILY_CRON_HOOK = 'si_daily_cron';
	const DEFAULT_TEMPLATE_DIRECTORY = 'sa_templates';
	const SETTINGS_PAGE = 'settings';
	const NONCE = 'sprout_invoices_controller_nonce';

	private static $messages = array();
	private static $query_vars = array();
	private static $template_path = self::DEFAULT_TEMPLATE_DIRECTORY;

	public static function init() {
		if ( is_admin() ) {

			// On Activation
			add_action( 'si_plugin_activation_hook', array( __CLASS__, 'sprout_invoices_activated' ) );

			// clone notification
			add_action( 'admin_init', array( get_class(), 'maybe_clone_and_redirect' ) );
		}

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_enqueue' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ), 20 );

		// Cron
		add_filter( 'cron_schedules', array( __CLASS__, 'si_cron_schedule' ) );
		add_action( 'init', array( __CLASS__, 'set_schedule' ), 10, 0 );

		// Messages
		add_action( 'init', array( __CLASS__, 'load_messages' ), 0, 0 );

		// AJAX
		add_action( 'wp_ajax_si_display_messages', array( __CLASS__, 'display_messages' ) );
		add_action( 'wp_ajax_nopriv_si_display_messages', array( __CLASS__, 'display_messages' ) );

		add_action( 'wp_ajax_si_number_formatter', array( __CLASS__, 'ajax_number_formatter' ) );

		add_action( 'wp_ajax_sa_create_private_note',  array( get_class(), 'maybe_create_private_note' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_sa_create_private_note',  array( get_class(), 'maybe_create_private_note' ), 10, 0 );
		add_action( 'wp_ajax_si_change_doc_status',  array( get_class(), 'maybe_change_status' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_si_change_doc_status',  array( get_class(), 'maybe_change_status' ), 10, 0 );

		// No index
		add_action( 'pre_si_invoice_view', array( __CLASS__, 'add_x_robots_header' ) );
		add_action( 'pre_si_estimate_view', array( __CLASS__, 'add_x_robots_header' ) );

		// archive page
		add_filter( 'pre_get_posts', array( __CLASS__, 'filter_post_type_query' ) );

	}

	/**
	 * Prevent the archive page of site.com?post_type=sa_invoice/estimate
	 * @param  object $query
	 * @return object $query
	 * @since 8.0
	 */
	public static function filter_post_type_query( $query ) {
		if ( is_admin() ) {
			return $query;
		}
		if ( $query->is_single() ) {
			return $query;
		}
		if ( $query->is_main_query() ) {
			$type = $query->get( 'post_type' );
			if ( in_array( $type, array( SI_Invoice::POST_TYPE, SI_Estimate::POST_TYPE ) ) ) {
				$query->set( 'post_type', 'post' );
			}
		}
		return $query;
	}

	/**
	 * Template path for templates/views, default to 'invoices'.
	 *
	 * @return string self::$template_path the folder
	 */
	public static function get_template_path() {
		return apply_filters( 'si_template_path', self::$template_path );
	}

	/**
	 * Fire actions based on plugin being updated.
	 * @return
	 */
	public static function sprout_invoices_activated() {
		add_option( 'si_do_activation_redirect', true );
		// Get the previous version number
		$si_version = get_option( 'si_current_version', self::SI_VERSION );
		if ( version_compare( $si_version, self::SI_VERSION, '<' ) ) { // If an upgrade create some hooks
			do_action( 'si_version_upgrade', $si_version );
			do_action( 'si_version_upgrade_'.$si_version );
		}
		// Set the new version number
		update_option( 'si_current_version', self::SI_VERSION );
	}



	public static function register_resources() {
		// admin js
		wp_register_script( 'si_admin', SI_URL . '/resources/admin/js/sprout_invoice.js', array( 'jquery', 'qtip' ), self::SI_VERSION );

		// Item management
		wp_register_script( 'nestable', SI_URL . '/resources/admin/js/nestable.js', array( 'jquery' ), self::SI_VERSION );
		wp_register_script( 'sticky', SI_URL . '/resources/admin/js/sticky.js', array( 'jquery' ), self::SI_VERSION );
		wp_register_script( 'si_admin_est_and_invoices', SI_URL . '/resources/admin/js/est_and_invoices.js', array( 'jquery', 'nestable', 'sticky' ), self::SI_VERSION );
		wp_register_style( 'sprout_invoice_admin_css', SI_URL . '/resources/admin/css/sprout-invoice.css', array(), self::SI_VERSION );

		// Redactor
		if ( ! SI_FREE_TEST && file_exists( SI_PATH . '/resources/admin/plugins/redactor/redactor.min.js' ) ) {
			wp_register_script( 'redactor', SI_URL . '/resources/admin/plugins/redactor/redactor.min.js', array( 'jquery' ), self::SI_VERSION );
			wp_register_style( 'redactor', SI_URL . '/resources/admin/plugins/redactor/redactor.min.css', array(), self::SI_VERSION );
		}

		// Select2
		wp_register_script( 'select2_4.0', SI_URL . '/resources/admin/plugins/select2/js/select2.min.js', array( 'jquery' ), self::SI_VERSION, false );
		wp_register_style( 'select2_4.0_css', SI_URL . '/resources/admin/plugins/select2/css/select2.min.css', null, self::SI_VERSION, false );

		// qtip plugin
		wp_register_style( 'qtip', SI_URL . '/resources/admin/plugins/qtip/jquery.qtip.min.css', null, self::SI_VERSION, false );
		wp_register_script( 'qtip', SI_URL . '/resources/admin/plugins/qtip/jquery.qtip.min.js', array( 'jquery' ), self::SI_VERSION, true );

		// dropdown plugin
		wp_register_style( 'dropdown', SI_URL . '/resources/admin/plugins/dropdown/jquery.dropdown.css', null, self::SI_VERSION, false );
		wp_register_script( 'dropdown', SI_URL . '/resources/admin/plugins/dropdown/jquery.dropdown.min.js', array( 'jquery' ), self::SI_VERSION, true );

		// Templates
		wp_register_script( 'sprout_doc_scripts', SI_URL . '/resources/front-end/js/sprout-invoices.js', array( 'jquery', 'qtip' ), self::SI_VERSION );
		wp_register_style( 'sprout_doc_style', SI_URL . '/resources/front-end/css/sprout-invoices.style.css', array( 'open-sans', 'dashicons', 'qtip' ), self::SI_VERSION );

	}

	public static function frontend_enqueue() {
		wp_localize_script( 'sprout_doc_scripts', 'si_js_object', self::get_localized_js() );
	}

	public static function get_localized_js() {
		// Localization
		$si_js_object = array(
			'ajax_url' => get_admin_url().'admin-ajax.php',
			'plugin_url' => SI_URL,
			'thank_you_string' => __( 'Thank you!', 'sprout-invoices' ),
			'updating_string' => __( 'Updating...', 'sprout-invoices' ),
			'sorry_string' => __( 'Maybe next time?', 'sprout-invoices' ),
			'security' => wp_create_nonce( self::NONCE ),
			'locale' => get_locale(),
			'locale_standard' => str_replace( '_', '-', get_locale() ),
			'inline_spinner' => '<span class="spinner si_inline_spinner" style="visibility:visible;display:inline-block;"></span>',
		);
		if ( is_single() && ( get_post_type( get_the_ID() ) === SI_Invoice::POST_TYPE ) ) {
			$si_js_object += array(
				'invoice_id' => get_the_ID(),
				'invoice_amount' => si_get_invoice_calculated_total(),
				'invoice_balance' => si_get_invoice_balance(),
			);
		}
		if ( is_single() && ( get_post_type( get_the_ID() ) === SI_Estimate::POST_TYPE ) ) {
			$si_js_object += array(
				'estimate_id' => get_the_ID(),
				'estimate_total' => si_get_estimate_total(),
			);
		}
		return apply_filters( 'si_sprout_doc_scripts_localization', $si_js_object );
	}

	public static function admin_enqueue() {
		$add_to_js_object = array();
		// doc admin templates
		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( in_array( $screen_post_type, array( SI_Estimate::POST_TYPE, SI_Invoice::POST_TYPE ) ) ) {

			if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/resources/admin/plugins/redactor/redactor.min.js' ) ) {

				$add_to_js_object['redactor'] = true;

				wp_enqueue_script( 'redactor' );
				wp_enqueue_style( 'redactor' );
			}

			wp_enqueue_script( 'nestable' );
			wp_enqueue_script( 'sticky' );
			wp_enqueue_script( 'si_admin_est_and_invoices' );

			// add doc info
			$add_to_js_object += array(
				'doc_status' => get_post_status( get_the_id() ),
			);

			self::enqueue_general_scripts_styles();
		}

		if ( SI_Client::POST_TYPE === $screen_post_type ) {

			wp_enqueue_script( 'si_admin_est_and_invoices' );

			self::enqueue_general_scripts_styles();
		}

		if ( SI_Project::POST_TYPE === $screen_post_type ) {

			wp_enqueue_script( 'si_admin_est_and_invoices' );

			self::enqueue_general_scripts_styles();
		}

		if ( self::is_si_admin() ) {
			self::enqueue_general_scripts_styles();
		}

		wp_enqueue_script( 'si_admin' );
		wp_enqueue_style( 'sprout_invoice_admin_css' );

		$si_js_object = self::get_localized_js();

		// Localization
		$si_js_object += array(
			'premium' => ( ! SI_FREE_TEST && file_exists( SI_PATH . '/controllers/updates/Updates.php' ) ) ? true : false,
			'redactor' => false,
		);

		$js_object = array_merge( $si_js_object, $add_to_js_object );
		wp_localize_script( 'si_admin', 'si_js_object', apply_filters( 'si_admin_scripts_localization', $js_object ) );

	}

	public static function enqueue_general_scripts_styles( $scripts = array() ) {

		// Defaults
		if ( empty( $scripts ) ) {
			$scripts = array( 'dropdown', 'select2', 'qtip' );
		}

		$scripts = apply_filters( 'si_enqueued_admin_scripts', $scripts );

		if ( in_array( 'dropdown', $scripts ) ) {
			// dropdowns
			wp_enqueue_style( 'dropdown' );
			wp_enqueue_script( 'dropdown' );
		}

		if ( in_array( 'select2', $scripts ) ) {
			// selects
			wp_enqueue_script( 'select2_4.0' );
			wp_enqueue_style( 'select2_4.0_css' );
		}

		if ( in_array( 'qtip', $scripts ) ) {
			// qtips
			wp_enqueue_script( 'qtip' );
			wp_enqueue_style( 'qtip' );
		}

	}

	/**
	 * Filter WP Cron schedules
	 * @param  array $schedules
	 * @return array
	 */
	public static function si_cron_schedule( $schedules ) {
		$schedules['minute'] = array(
			'interval' => 60,
			'display' => __( 'Once a Minute' ),
		);
		$schedules['quarterhour'] = array(
			'interval' => 900,
			'display' => __( '15 Minutes' ),
		);
		$schedules['halfhour'] = array(
			'interval' => 1800,
			'display' => __( 'Twice Hourly' ),
		);
		return $schedules;
	}

	/**
	 * schedule wp events for wpcron.
	 */
	public static function set_schedule() {
		if ( self::DEBUG ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$interval = apply_filters( 'si_set_schedule', 'quarterhour' );
			wp_schedule_event( time(), $interval, self::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( self::DAILY_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::DAILY_CRON_HOOK );
		}
	}

	public static function add_x_robots_header() {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}

	/**
	 * Display the template for the given view
	 *
	 * @static
	 * @param string  $view
	 * @param array   $args
	 * @param bool    $allow_theme_override
	 * @return void
	 */
	public static function load_view( $view, $args, $allow_theme_override = true ) {
		// whether or not .php was added
		if ( substr( $view, -4 ) != '.php' ) {
			$view .= '.php';
		}

		// in case locate template was used
		$view = str_replace( SI_PATH.'/views/', '', $view );

		// In case locate template was used and a theme file was returned.
		if ( file_exists( $view ) ) {
			$file = $view;
			$view = substr( strrchr( $view, '/' ), 1 );
		} else {
			$path = apply_filters( 'si_views_path', SI_PATH.'/views/' );

			$file = $path.$view;
			if ( $allow_theme_override && defined( 'TEMPLATEPATH' ) ) {
				$file = self::locate_template( array( $view ), $file );
			}
		}

		$file = apply_filters( 'sprout_invoice_template_'.$view, $file );

		$args = apply_filters( 'load_view_args_'.$view, $args, $allow_theme_override );
		if ( ! empty( $args ) ) { extract( $args ); }
		include $file;
	}

	/**
	 * Return a template as a string
	 *
	 * @static
	 * @param string  $view
	 * @param array   $args
	 * @param bool    $allow_theme_override
	 * @return string
	 */
	protected static function load_view_to_string( $view, $args, $allow_theme_override = true ) {
		ob_start();
		self::load_view( $view, $args, $allow_theme_override );
		return ob_get_clean();
	}

	/**
	 * Locate the template file, either in the current theme or the public views directory
	 *
	 * @static
	 * @param array   $possibilities
	 * @param string  $default
	 * @return string
	 */
	protected static function locate_template( $possibilities, $default = '' ) {
		$possibilities = apply_filters( 'sprout_invoice_template_possibilities', $possibilities );
		$possibilities = array_filter( $possibilities );
		// check if the theme has an override for the template
		$theme_overrides = array();
		foreach ( $possibilities as $p ) {
			$theme_overrides[] = self::get_template_path().'/'.$p;
		}
		if ( $found = locate_template( $theme_overrides, false ) ) {
			return $found;
		}

		// check for it in the templates directory
		foreach ( $possibilities as $p ) {
			if ( file_exists( SI_PATH.'/views/templates/'.$p ) ) {
				return SI_PATH.'/views/templates/'.$p;
			}
		}

		// we don't have it
		return $default;
	}

	///////////////////////////////
	// Query vars and callbacks //
	///////////////////////////////

	/**
	 * Register a query var and a callback method
	 * @param  string $var      query variable
	 * @param  string $callback callback for query variable
	 * @return null
	 */
	protected static function register_query_var( $var, $callback = '' ) {
		self::add_register_query_var_hooks();
		self::$query_vars[ $var ] = $callback;
	}

	/**
	 * Register query var hooks with WordPress.
	 */
	private static function add_register_query_var_hooks() {
		static $registered = false; // only do this once
		if ( ! $registered ) {
			add_filter( 'query_vars', array( __CLASS__, 'filter_query_vars' ) );
			add_action( 'parse_request', array( __CLASS__, 'handle_callbacks' ), 10, 1 );
			$registered = true;
		}
	}

	/**
	 * Add query vars into the filtered query_vars filter
	 * @param  array  $vars
	 * @return array  $vars
	 */
	public static function filter_query_vars( array $vars ) {
		$vars = array_merge( $vars, array_keys( self::$query_vars ) );
		return $vars;
	}

	/**
	 * Handle callbacks for registered query vars
	 * @param  WP     $wp
	 * @return null
	 */
	public static function handle_callbacks( WP $wp ) {
		foreach ( self::$query_vars as $var => $callback ) {
			if ( isset( $wp->query_vars[ $var ] ) && $wp->query_vars[ $var ] && $callback && is_callable( $callback ) ) {
				call_user_func( $callback, $wp );
			}
		}
	}

	///////////////
	// Messages //
	///////////////

	public static function has_messages() {
		$msgs = self::get_messages();
		return ! empty( $msgs );
	}

	public static function set_message( $message, $status = self::MESSAGE_STATUS_INFO, $save = true ) {
		if ( ! isset( self::$messages ) ) {
			self::load_messages();
		}
		$message = __( $message, 'sprout-invoices' );
		if ( ! isset( self::$messages[ $status ] ) ) {
			self::$messages[ $status ] = array();
		}
		self::$messages[ $status ][] = $message;
		if ( $save ) {
			self::save_messages();
		}
	}

	public static function clear_messages() {
		self::$messages = array();
		self::save_messages();
	}

	private static function save_messages() {
		global $blog_id;
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			if ( '' !== self::get_user_ip() ) {
				set_transient( 'si_messaging_for_'.self::get_user_ip(), self::$messages, 300 );
			}
		}
		update_user_meta( $user_id, $blog_id.'_'.self::MESSAGE_META_KEY, self::$messages );
	}

	public static function get_messages( $type = null ) {
		if ( ! isset( self::$messages ) ) {
			self::load_messages();
		}
		return self::$messages;
	}

	public static function load_messages() {
		$user_id = get_current_user_id();
		$messages = false;
		if ( ! $user_id ) {
			if ( '' !== self::get_user_ip() ) {
				$messages = get_transient( 'si_messaging_for_'.self::get_user_ip() );
			}
		} else {
			global $blog_id;
			$messages = get_user_meta( $user_id, $blog_id.'_'.self::MESSAGE_META_KEY, true );
		}
		if ( $messages ) {
			self::$messages = $messages;
		} else {
			self::$messages = array();
		}
	}

	public static function display_messages( $type = null ) {
		$type = ( isset( $_REQUEST['si_message_type'] ) ) ? $_REQUEST['si_message_type'] : $type ;
		$statuses = array();
		if ( $type == null ) {
			if ( isset( self::$messages[ self::MESSAGE_STATUS_INFO ] ) ) {
				$statuses[] = self::MESSAGE_STATUS_INFO;
			}
			if ( isset( self::$messages[ self::MESSAGE_STATUS_ERROR ] ) ) {
				$statuses[] = self::MESSAGE_STATUS_ERROR;
			}
		} elseif ( isset( self::$messages[ $type ] ) ) {
			$statuses = array( $type );
		}

		if ( ! isset( self::$messages ) ) {
			self::load_messages();
		}
		foreach ( $statuses as $status ) {
			foreach ( self::$messages[ $status ] as $message ) {
				self::load_view( 'templates/messages', array(
						'status' => $status,
						'message' => $message,
				), true );
			}
			self::$messages[ $status ] = array();
		}
		self::save_messages();
		if ( defined( 'DOING_AJAX' ) ) {
			exit();
		}
	}

	public static function login_required( $redirect = '' ) {
		if ( ! get_current_user_id() && apply_filters( 'si_login_required', true ) ) {
			if ( ! $redirect && self::using_permalinks() ) {
				$schema = is_ssl() ? 'https://' : 'http://';
				$redirect = $schema.$_SERVER['SERVER_NAME'].htmlspecialchars( $_SERVER['REQUEST_URI'] );
				if ( isset( $_REQUEST ) ) {
					$redirect = urlencode( add_query_arg( $_REQUEST, $redirect ) );
				}
			}
			wp_redirect( wp_login_url( $redirect ) );
			exit();
		}
		return true; // explicit return value, for the benefit of the router plugin
	}

	/**
	 * Get the home_url option directly since home_url injects a scheme based on current page.
	 */
	public static function si_get_home_url_option() {
		global $blog_id;

		if ( empty( $blog_id ) || ! is_multisite() ) {
			$url = get_option( 'home' ); } else {
			$url = get_blog_option( $blog_id, 'home' ); }

			return apply_filters( 'si_get_home_url_option', esc_url( $url ) );
	}

	/**
	 * Comparison function
	 */
	public static function sort_by_weight( $a, $b ) {
		if ( ! isset( $a['weight'] ) || ! isset( $b['weight'] ) ) {
			return 0; }

		if ( $a['weight'] == $b['weight'] ) {
			return 0;
		}
		return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
	}

	/**
	 * Comparison function
	 */
	public static function sort_by_date( $a, $b ) {
		if ( ! isset( $a['date'] ) || ! isset( $b['date'] ) ) {
			return 0; }

		if ( $a['date'] == $b['date'] ) {
			return 0;
		}
		return ( $a['date'] < $b['date'] ) ? -1 : 1;
	}

	/**
	 * Is current site using permalinks
	 * @return bool
	 */
	public static function using_permalinks() {
		return get_option( 'permalink_structure' ) != '';
	}

	/**
	 * Tell caching plugins not to cache the current page load
	 */
	public static function do_not_cache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}

	/**
	 * Tell caching plugins to clear their caches related to a post
	 *
	 * @static
	 * @param int $post_id
	 */
	public static function clear_post_cache( $post_id ) {
		if ( function_exists( 'wp_cache_post_change' ) ) {
			// WP Super Cache

			$GLOBALS['super_cache_enabled'] = 1;
			wp_cache_post_change( $post_id );

		} elseif ( function_exists( 'w3tc_pgcache_flush_post' ) ) {
			// W3 Total Cache

			w3tc_pgcache_flush_post( $post_id );

		}
	}

	/**
	 * Function to duplicate the post
	 * @param  int $post_id
	 * @param  string $new_post_status
	 * @return
	 */
	protected static function clone_post( $post_id, $new_post_status = 'draft', $new_post_type = '' ) {
		$post = get_post( $post_id );
		$new_post_id = 0;
		if ( isset( $post ) && $post != null ) {

			if ( $new_post_type == '' ) {
				$new_post_type = $post->post_type; }

			/*
			 * new post data array
			 */
			$args = array(
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $post->post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => $new_post_status,
				'post_title'     => $post->post_title,
				'post_type'      => $new_post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order,
			);

			// clone the post
			$new_post_id = wp_insert_post( $args );

			// get current terms and add them to the new post
			$taxonomies = get_object_taxonomies( $post->post_type );
			if ( is_array( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					$post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'orderby' => 'term_order' ) );
					$terms = array();
					for ( $i = 0; $i < count( $post_terms ); $i++ ) {
						$terms[] = $post_terms[ $i ]->slug;
					}
					wp_set_object_terms( $new_post_id, $terms, $taxonomy );
				}
			}

			// Duplicate all post_meta
			$meta_keys = get_post_custom_keys( $post_id );
			if ( $meta_keys ) {
				foreach ( $meta_keys as $meta_key ) {
					$meta_values = get_post_custom_values( $meta_key, $post_id );
					foreach ( $meta_values as $meta_value ) {
						$meta_value = maybe_unserialize( $meta_value );
						add_post_meta( $new_post_id, $meta_key, $meta_value );
					}
				}
			}
		}
		// end
		do_action( 'si_cloned_post', $new_post_id, $post_id, $new_post_type, $new_post_status );
		return $new_post_id;
	}

	///////////////////////////////
	// Notification duplication //
	///////////////////////////////

	public static function maybe_clone_and_redirect() {
		if ( isset( $_GET['clone_si_post'] ) && isset( $_GET['post'] ) && $_GET['clone_si_post'] ) {
			$post_id = (int) $_GET['post'];
			if ( check_admin_referer( 'clone-si_post_'.$post_id, 'clone_si_post' ) ) {
				$new_post_type = ( isset( $_GET['post_type'] ) ) ? $_GET['post_type'] : '' ;
				$cloned_post_id = self::clone_post( $post_id, 'publish', $new_post_type );

				do_action( 'si_cloned_post_before_redirect', $cloned_post_id );

				wp_redirect( add_query_arg( array( 'post' => $cloned_post_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) );
				exit();
			}
		}

	}

	public static function get_clone_post_url( $post_id = 0, $new_post_type = '' ) {
		$url = wp_nonce_url( get_edit_post_link( $post_id ), 'clone-si_post_'.$post_id, 'clone_si_post' );
		if ( $new_post_type != '' ) {
			$url = add_query_arg( array( 'post_type' => $new_post_type ), esc_url_raw( $url ) );
		}
		return apply_filters( 'si_get_clone_post_url', esc_url_raw( $url ), $post_id, $new_post_type );
	}


	/**
	 * Standard Address Fields.
	 * Params are used for filter only.
	 * @param  integer $user_id
	 * @param  boolean $shipping
	 * @return array
	 */
	public static function get_standard_address_fields( $required = true, $user_id = 0 ) {
		$client = 0;
		$user = 0;
		$address = array();
		if ( $user_id ) {
			$client_ids = SI_Client::get_clients_by_user( $user_id );
			$client = ( ! empty( $client_ids ) && isset( $client_ids[0] ) ) ? SI_Client::get_instance( $client_ids[0] ) : 0 ;
			if ( is_a( $client, 'SI_Client' ) ) {
				$address = $client->get_address();
			}
			$user = get_userdata( $user_id );
		}
		$fields = array();
		$fields['first_name'] = array(
			'weight' => 50,
			'label' => __( 'First Name', 'sprout-invoices' ),
			'placeholder' => __( 'First Name', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( is_a( $user, 'WP_User' ) ) ? $user->first_name : '',
			'required' => $required,
		);
		$fields['last_name'] = array(
			'weight' => 51,
			'label' => __( 'Last Name', 'sprout-invoices' ),
			'placeholder' => __( 'Last Name', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( is_a( $user, 'WP_User' ) ) ? $user->last_name : '',
			'required' => $required,
		);
		$fields['street'] = array(
			'weight' => 60,
			'label' => __( 'Street Address', 'sprout-invoices' ),
			'placeholder' => __( 'Street Address', 'sprout-invoices' ),
			'type' => 'textarea',
			'rows' => 2,
			'default' => ( isset( $address['street'] ) ) ? $address['street'] : '',
			'required' => $required,
		);
		$fields['city'] = array(
			'weight' => 65,
			'label' => __( 'City', 'sprout-invoices' ),
			'placeholder' => __( 'City', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( isset( $address['city'] ) ) ? $address['city'] : '',
			'required' => $required,
		);

		$fields['postal_code'] = array(
			'weight' => 70,
			'label' => __( 'ZIP Code', 'sprout-invoices' ),
			'placeholder' => __( 'ZIP Code', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( isset( $address['postal_code'] ) ) ? $address['postal_code'] : '',
			'required' => $required,
		);

		$fields['zone'] = array(
			'weight' => 75,
			'label' => __( 'State', 'sprout-invoices' ),
			'type' => 'select-state',
			'options' => SI_Countries_States::get_state_options( array( 'include_option_none' => ' -- '.__( 'State', 'sprout-invoices' ).' -- ' ) ),
			'attributes' => array( 'class' => 'select2' ),
			'required' => $required,
			'default' => ( isset( $address['zone'] ) ) ? $address['zone'] : '',
		); // FUTURE: Add some JavaScript to switch between select box/text-field depending on country

		$fields['country'] = array(
			'weight' => 80,
			'label' => __( 'Country', 'sprout-invoices' ),
			'type' => 'select',
			'required' => $required,
			'options' => SI_Countries_States::get_country_options( array( 'include_option_none' => ' -- '.__( 'Country', 'sprout-invoices' ).' -- ' ) ),
			'default' => ( isset( $address['country'] ) ) ? $address['country'] : '',
			'attributes' => array( 'class' => 'select2' ),
		);
		$billing_fields = apply_filters( 'si_get_standard_address_fields', $fields, $required, $user_id );
		uasort( $billing_fields, array( __CLASS__, 'sort_by_weight' ) );
		return $billing_fields;
	}

	////////////////////
	// AJAX Callback //
	////////////////////

	public static function ajax_number_formatter() {

		if ( ! isset( $_REQUEST['number'] ) ) {
			self::ajax_fail( 'Forget something?' );
		}

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' );
		}

		$number = $_REQUEST['number'];
		$currency = array(
			'money' => sa_get_formatted_money( $number ),
			'unformatted_money' => sa_get_unformatted_money( $number ),
			'float' => si_get_number_format( $number ),
			'int' => (int) si_get_number_format( $number ),
			);
		header( 'Content-type: application/json' );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode( $currency );
		exit();

	}

	public static function maybe_create_private_note() {

		if ( ! isset( $_REQUEST['private_note_nonce'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['private_note_nonce'];
		if ( ! wp_verify_nonce( $nonce, SI_Internal_Records::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! current_user_can( 'edit_sprout_invoices' ) ) {
			return; }

		$record_id = SI_Internal_Records::new_record( $_REQUEST['notes'], SI_Controller::PRIVATE_NOTES_TYPE, $_REQUEST['associated_id'], '', 0, false );
		$error = ( $record_id ) ? '' : __( 'Private note failed to save, try again.', 'sprout-invoices' );
		$data = array(
			'id' => $record_id,
			'content' => $_REQUEST['notes'],
			'type' => __( 'Private Note', 'sprout-invoices' ),
			'post_date' => __( 'Just now', 'sprout-invoices' ),
			'error' => $error,
		);

		header( 'Content-type: application/json' );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode( $data );
		exit();

	}

	public static function maybe_change_status() {
		if ( ! isset( $_REQUEST['change_status_nonce'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = esc_attr( $_REQUEST['change_status_nonce'] );
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! isset( $_REQUEST['id'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		if ( ! isset( $_REQUEST['status'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$view = '';
		$doc_id = esc_attr( $_REQUEST['id'] );
		$new_status = esc_attr( $_REQUEST['status'] );
		switch ( get_post_type( $doc_id ) ) {
			case SI_Invoice::POST_TYPE:
				$doc = SI_Invoice::get_instance( $doc_id );
				$doc->set_status( $new_status );
				$view = self::load_view_to_string( 'admin/sections/invoice-status-change-drop', array(
						'id' => $doc_id,
						'status' => $doc->get_status(),
				), false );
				break;
			case SI_Estimate::POST_TYPE:
				switch ( $new_status ) {
					case 'accept':
						$new_status = SI_Estimate::STATUS_APPROVED;
						break;
					case 'decline':
						$new_status = SI_Estimate::STATUS_DECLINED;
						break;

					default:
						break;
				}
				$doc = SI_Estimate::get_instance( $doc_id );
				$doc->set_status( $new_status );
				$view = self::load_view_to_string( 'admin/sections/estimate-status-change-drop', array(
						'id' => $doc_id,
						'status' => $doc->get_status(),
				), false );
				break;

			default:
				self::ajax_fail( 'Not an estimate or invoice.' );
				return;
				break;
		}

		// action
		do_action( 'doc_status_changed', $doc, $_REQUEST );

		header( 'Content-type: application/json' );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode( array( 'new_button' => $view ) );
		exit();

	}


	//////////////
	// Utility //
	//////////////

	public static function is_si_admin() {
		$bool = false;
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return $bool;
		}

		// Options
		if ( isset( $_GET['page'] ) && strpos( $_GET['page'] , self::APP_DOMAIN ) !== false ) {
			$bool = true;
		}

		global $current_screen;
		if ( isset( $current_screen->id ) && strpos( $current_screen->id, self::APP_DOMAIN ) !== false ) {
			$bool = true;
		}

		if ( ! $bool ) { // check if admin for SI post types.
			$post_type = false;

			if ( isset( $current_screen->post_type ) ) {
				$post_type = $current_screen->post_type;
			} else {
				// Trying hard to figure out the post type if not yet set.
				$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : false;
				if ( $post_id ) {
					$post_type = get_post_type( $post_id );
				} else {
					$post_type = ( isset( $_REQUEST['post_type'] ) ) ? $_REQUEST['post_type'] : false ;
				}
			}
			if ( $post_type ) {
				if ( in_array( $post_type, array( SI_Invoice::POST_TYPE, SI_Estimate::POST_TYPE, SI_Client::POST_TYPE, SI_Project::POST_TYPE ) ) ) {
					return true;
				}
			}
		}
		return apply_filters( 'is_si_admin', $bool );
	}

	public static function is_estimate_or_invoice() {
		$type = false;
		if ( is_single() && ( get_post_type( get_the_ID() ) === SI_Invoice::POST_TYPE ) ) {
			$type = SI_Invoice::POST_TYPE;
		} elseif ( is_single() && ( get_post_type( get_the_ID() ) === SI_Estimate::POST_TYPE ) ) {
			$type = SI_Estimate::POST_TYPE;
		}
		return $type;
	}

	public static function ajax_fail( $message = '', $json = true ) {
		if ( $message == '' ) {
			$message = __( 'Something failed.', 'sprout-invoices' );
		}
		if ( $json ) { header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) ); }
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		if ( $json ) {
			echo wp_json_encode( array( 'error' => 1, 'response' => esc_html( $message ) ) );
		} else {
			echo $message;
		}
		exit();
	}

	public static function get_user_ip() {
		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];

		$ip = '';
		if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
			$ip = $client;
		} elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
			$ip = $forward;
		} elseif ( filter_var( $remote, FILTER_VALIDATE_IP ) ) {
			$ip = $remote;
		}
		return $ip;
	}

	/**
	 * Number with ordinal suffix
	 */
	public static function number_ordinal_suffix( $number = 0 ) {
		if ( ! is_numeric( $number ) ) {
			return $number;
		}
		if ( ! $number ) {
			return 'zero';
		}
		$ends = array( 'th','st','nd','rd','th','th','th','th','th','th' );
		if ( ($number % 100) >= 11 && ($number % 100) <= 13 ) {
			$abbreviation = $number. 'th';
		} else {
			$abbreviation = $number. $ends[ $number % 10 ];
		}
		return $abbreviation;

	}

	public static function _save_null() {
		__return_null();
	}
}
