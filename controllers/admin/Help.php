<?php 


/**
 * Admin help controller.
 *
 * @package Sprout_Invoice
 * @subpackage Help
 */
class SI_Help extends SI_Controller {
	const NONCE = 'si_pointer_nonce';
	protected static $pointer_key = 'si_pointer_hook';

	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_pointer_scripts' ) );

			add_filter( 'posts_where_request', array( __CLASS__, 'filter_admin_search' ) );
		}
		add_filter( 'admin_footer_text', array( __CLASS__, 'please_rate_si' ), 1, 2 );
	}

	public static function please_rate_si( $footer_text ) {
		if ( self::is_si_admin() ) {
			$footer_text = sprintf( __( 'Please support the future of <strong>Sprout Invoices</strong> by rating the free version <a href="%1$s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%1$s" target="_blank">WordPress.org</a>. Have an awesome %2$s!', 'sprout-invoices' ), 'http://wordpress.org/support/view/plugin-reviews/sprout-invoices?filter=5', date_i18n('l') );
		}
		return $footer_text;
	}

	public static function enqueue_pointer_scripts( $hook_suffix ) {
		// Add pointers script and style to queue
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );

		$defaults = array(
			'post-new.php' => 'new_doc',
			'post.php' => 'si_help_tab_post',
			'edit.php' => 'si_help_tab_edit',
			'edit-tags.php' => 'si_help_tab_edit',
			'sa_invoice_page_sprout-apps/invoice_payments' => 'si_help_tab_settings',
			'sprout-apps_page_sprout-apps/settings' => 'si_help_tab_settings',
			// Pointers for all admin pages should be added to an array
			'all_admin_pages' => array()

		);

		$registered_pointers = apply_filters( 'si_pointers', $defaults );

		// Check for pointers that show throughout the admin
		foreach ( $registered_pointers as $hook => $pt ) {
			if ( is_array($pt) && $hook === 'all_admin_pages' ) {
				foreach ( $pt as $point ) {
					add_action( 'admin_print_footer_scripts', array( get_class(), 'pointer_' . $point ) );
				}
			}
		}

		// Check if screen related pointer is registered
		if ( empty( $registered_pointers[ $hook_suffix ] ) )
			return;

		$pointer = $registered_pointers[ $hook_suffix ];

		// FUTURE, if necessary
		$caps_required = array();
		if ( isset( $caps_required[ $pointer ] ) ) {
			foreach ( $caps_required[ $pointer ] as $cap ) {
				if ( ! current_user_can( $cap ) )
					return;
			}
		}
		// Bind pointer print function
		add_action( 'admin_print_footer_scripts', array( get_class(), 'pointer_' . $pointer ) );
	}

	/**
	 * Print the pointer javascript data.
	 *
	 * @param string  $pointer_id The pointer ID.
	 * @param string  $selector   The HTML elements, on which the pointer should be attached.
	 * @param array   $args       Arguments to be passed to the pointer JS (see wp-pointer.dev.js).
	 */
	private static function print_js( $pointer_id, $selector, $args, $close = null ) {
		if ( empty( $pointer_id ) || empty( $selector ) || empty( $args ) || empty( $args['content'] ) )
			return;

		// Get dismissed pointers
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

		// Pointer has been dismissed
		if ( in_array( $pointer_id, $dismissed ) )
			return;


		?>
		<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
			var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
			var options = <?php echo wp_json_encode( $args ); ?>;

			if ( ! options )
				return;

			options = $.extend( options, {
				close: function() {
					$.post( ajaxurl, {
						pointer: '<?php echo esc_js( $pointer_id ); ?>',
						action: 'dismiss-wp-pointer'
					});
					<?php echo esc_js( $close ); ?>
				}
			});

			$('<?php echo esc_js( $selector ); ?>').pointer( options ).pointer('open');
		});
		//]]>
		</script>
		<?php
	}

	public static function pointer_new_doc() {

		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : false;
		if ( $post_id ) {
			$post_type = get_post_type( $post_id );
		} else {
			$post_type = ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) ? $_REQUEST['post_type'] : null ;
		}

		///////////////
		// Estimates //
		///////////////

		if ( $post_type == SI_Estimate::POST_TYPE ) {
			
			$content  = '<h3>' . esc_js( __( 'Nested Line Items', 'sprout-invoices' ) ). '</h3>';
			$content .= '<p>' . esc_js( __( 'Line items can be nested. Use this handle and drag the line item around to re-order or make it a sub-item.', 'sprout-invoices' ) ) . '</p>';
			$content .= '<p style="height:215px"><img src="https://sproutapps.co/wp-content/uploads/2014/08/line-items-mgmt.gif" alt="Nested line Items" style="width:auto;height:215px;"></p>';
			self::print_js(
				'si_nested_line_items',
				'.dd-handle',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'si_pointer',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) )
			);
		}
		//////////////
		// Invoices //
		//////////////
		elseif ( $post_type == SI_Invoice::POST_TYPE ) {

			$content  = '<h3>' . esc_js( __( 'Nested Line Items', 'sprout-invoices' ) ). '</h3>';
			$content .= '<p>' . esc_js( __( 'Line items can be nested. Use this handle and drag the line item around to re-order or make it a sub-item.', 'sprout-invoices' ) ) . '</p>';
			$content .= '<p style="height:215px"><img src="https://sproutapps.co/wp-content/uploads/2014/08/line-items-mgmt.gif" alt="Nested line Items" style="width:auto;height:215px;"></p>';
			self::print_js(
				'si_nested_line_items',
				'.dd-handle',
				array(
					'content'  => $content,
					'pointerWidth' => 300,
					'pointerClass' => 'si_pointer',
					'position' => array( 'edge' => 'left', 'align' => 'left' ) )
			);
		}
		/////////////
		// Clients //
		/////////////
		elseif ( $post_type == SI_Client::POST_TYPE ) {

			
		}
	}


	public static function pointer_si_help_tab_post() {
		if ( self::is_relevant_admin_page() ) {
			self::pointer_si_help_tab( '_post' );
		}
	}

	public static function pointer_si_help_tab_edit() {
		if ( self::is_relevant_admin_page() ) {
			self::pointer_si_help_tab( '_edit' );
		}
	}

	public static function is_relevant_admin_page() {
		return self::is_si_admin();
	}

	public static function pointer_si_help_tab_settings() {
		self::pointer_si_help_tab( '_settings' );
	}

	/**
	 * Help tab function used for posts, edit screens and option pages.
	 */
	public static function pointer_si_help_tab( $context = null, $class = null, $close_callback = null ) {

		$content  = '<h3>' . esc_js( __( 'Need Help?', 'sprout-invoices' ) ). '</h3>';
		$content .= '<p>' . esc_js( __( 'This help tab has a lot of great information for you to learn all about the options and settings on this page.', 'sprout-invoices' ) ) . '</p>';

		self::print_js(
			'si_help_tab'.$context,
			'#contextual-help-link',
			array(
				'content'  => $content,
				'pointerWidth' => 250,
				'pointerClass' => 'si_pointer si_pointer_help_tab '.$class,
				'position' => array( 'edge' => 'top', 'align' => 'right' ) ),
			$close_callback
		);
	}

	//////////////////
	// Admin Search //
	//////////////////

	public static function filter_admin_search( $where = '' ) {
		if ( ! is_admin() || ! is_search() ) {
			return $where;
		}

		global $wpdb, $wp;
		if ( !isset( $_REQUEST['s'] ) || $wp->query_vars['s'] !== $_REQUEST['s'] ) {
			return $where;
		}

		$custom_fields = apply_filters( 'si_admin_meta_search', array(), $wp->query_vars['post_type'] );
		if ( empty( $custom_fields ) ) {
			return $where;
		}

		foreach( $custom_fields as $cf ) {
			// append meta search after title search
			$where = preg_replace(
				"/({$wpdb->posts}.post_title LIKE '%{$wp->query_vars['s']}%')/i",
				"$0 OR ({$wpdb->postmeta}.meta_key = '{$cf}' AND {$wpdb->postmeta}.meta_value LIKE '%{$wp->query_vars['s']}%')",
				$where
			);
		}
		// join meta and make distinct
		add_filter( 'posts_join_request', array( __CLASS__, 'filter_admin_search_join' ) );
		add_filter( 'posts_distinct_request', array( __CLASS__, 'filter_admin_search_distinct' ) );
		return $where;
	}

	public static function filter_admin_search_join( $join ) {
		global $wpdb;
		$join .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
		return $join;
	}

	public static function filter_admin_search_distinct( $distinct ) {
		$distinct = 'DISTINCT';
		return $distinct;
	}


}