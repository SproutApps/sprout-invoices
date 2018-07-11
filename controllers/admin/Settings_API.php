<?php

/**
 * Admin settings pages and meta controller.
 *
 * Add APIs for easily adding admin menus and meta boxes.
 *
 * @package Sprout_Invoice
 * @subpackage Settings
 */
class SI_Settings_API extends SI_Controller {
	protected static $settings_page;
	private static $settings = array();

	public static function init() {

		// messages
		add_filter( 'si_admin_notices', array( __CLASS__, 'admin_messages' ) );

		// tabs for settings pages
		add_action( 'sprout_settings_header', array( __CLASS__, 'sprout_settings_header' ) );

		add_action( 'sprout_settings_messages', array( __CLASS__, 'sprout_admin_messages' ) );
		add_action( 'sprout_settings_progress', array( __CLASS__, 'sprout_progress_window' ) );
		add_action( 'wp_ajax_si_progress_view',  array( __CLASS__, 'ajax_view_sprout_progress_window' ), 10, 0 );

		// Admin pages
		add_action( 'admin_menu', array( __CLASS__, 'add_sub_admin_pages' ), 20, 0 );

		// scripts
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'script_localization' ) );

		// Rest API
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_route' ) );

		add_action( 'si_display_settings', array( __CLASS__, 'display_settings' ), 10, 2 );

		add_filter( 'si_settings_sanitize_pre_save', array( __CLASS__, 'maybe_sanitize_value' ), 10, 2 );

	}

	public static function admin_messages( $messages = array() ) {
		/*/
		$messages[] = array(
			'type' => 'info',
			'content' => sprintf( __( 'Hello,<br/>Since you\'ve been using <strong>Sprout Invoices</strong> for a while I\'d love it if you\'d consider rating the free version of SI <a href="%1$s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%1$s" target="_blank">WordPress.org</a> (if you haven\'t already). Beleive it or not those ratings really help the future of Sprout Invoices. <br/>Thank you!<br/>Dan Cameron', 'sprout-invoices' ), 'http://wordpress.org/support/view/plugin-reviews/sprout-invoices?filter=5' ),
		);
		/**/

		/*/
		$messages[] = array(
			'type' => 'info',
			'content' => sprintf( __( '<b>Summer Update Brings All New Sprout Invoices Admin</b>! Learn more about the release <a href="%1$s" target="_blank">at sproutapps.co</a>.', 'sprout-invoices' ), si_get_sa_link('https://sproutapps.co/news/summer-update-brings-all-new-sprout-invoices-admin/') ),
		);
		/**/

		return $messages;
	}

	/**
	 * Filtered settings
	 * @return array
	 */
	public static function get_si_settings() {
		if ( ! empty( self::$settings ) ) {
			return self::$settings;
		}
		$settings = apply_filters( 'si_settings', array() );
		uasort( $settings, array( __CLASS__, 'sort_by_weight' ) );
		self::$settings = $settings;
		return $settings;
	}

	public static function get_general_settings_tabs() {
		$tabs = array(
			'start' => __( 'Getting Started', 'sprout-invoices' ),
			'general' => __( 'General', 'sprout-invoices' ),
			'addons' => __( 'Add-ons', 'sprout-invoices' ),
			'advanced' => __( 'Advanced', 'sprout-invoices' ),
		);
		$tabs = apply_filters( 'si_general_settings_tabs', $tabs );
		return $tabs;
	}

	////////////////////
	// Register Admin //
	////////////////////


	public static function add_sub_admin_pages() {
		$defaults = array(
			'parent' => '',
			'slug' => 'undefined_slug',
			'title' => 'Undefined Title',
			'menu_title' => 'Undefined Menu Title',
			'tab_title' => false,
			'weight' => 10,
			'reset' => false,
			'section' => 'theme',
			'show_tabs' => true,
			'tab_only' => false,
			'callback' => array( __CLASS__, 'si_settings_render_settings_page' ),
			'ajax' => false,
			'ajax_full_page' => false,
			'add_new' => '',
			'add_new_post_type' => '',
			'capability' => 'manage_sprout_invoices_options',
		);

		$sub_pages = apply_filters( 'si_sub_admin_pages', array() );
		if ( empty( $sub_pages ) ) {
			do_action( 'si_error', 'No Subpages', $sub_pages );
			return;
		}
		uasort( $sub_pages, array( __CLASS__, 'sort_by_weight' ) );
		foreach ( $sub_pages as $menu_slug => $args ) {
			do_action( 'si_adding_sub_admin_page', $menu_slug, $args );

			$parsed_args = wp_parse_args( $args, $defaults );
			add_submenu_page( self::TEXT_DOMAIN, $parsed_args['title'], $parsed_args['menu_title'], $parsed_args['capability'], self::TEXT_DOMAIN . '-' . $menu_slug, $parsed_args['callback'] );

			do_action( 'si_added_sub_admin_page', $menu_slug, $args );
		}
	}

	//////////
	// View //
	//////////

	public static function si_settings_render_settings_page() {
		$current_page = ( isset( $_GET['page'] ) ) ? str_replace( 'sprout-invoices-', '', $_GET['page'] ) : '' ;
		$args = array(
			'allsettings' => self::get_si_settings(),
			'tabs' => self::get_general_settings_tabs(),
			'current_page' => $current_page,
		);
		self::load_view( 'admin/settings.php', $args );
	}

	/**
	 * Build the tabs for all the admin settings
	 * @param  string $plugin_page slug for settings page
	 * @return string              html
	 */
	public static function sprout_settings_header() {
		$sub_pages = apply_filters( 'si_sub_admin_pages', array() );
		uasort( $sub_pages, array( __CLASS__, 'sort_by_weight' ) );
		$args = array(
			'sub_pages' => $sub_pages,
		);
		self::load_view( 'admin/settings-nav.php', $args );
	}

	public static function sprout_admin_messages() {
		$args = array(
			'messages' => apply_filters( 'si_admin_notices', array() ),
		);
		self::load_view( 'admin/messages.php', $args );
	}

	public static function sprout_progress_window() {
		$args = array(
			'progress' => self::progress_track(),
		);
		self::load_view( 'admin/settings-progress.php', $args );
	}

	public static function ajax_view_sprout_progress_window() {
		self::sprout_progress_window();
		exit();
	}

	public static function progress_track() {

		$num_est = wp_count_posts( SI_Estimate::POST_TYPE );
		$num_est->{'auto-draft'} = 0; // remove auto-drafts
		$total_estimates = array_sum( (array) $num_est );
		$num_inv = wp_count_posts( SI_Estimate::POST_TYPE );
		$num_inv->{'auto-draft'} = 0; // remove auto-drafts
		$total_invoices = array_sum( (array) $num_inv );

		$license_active = false;
		if ( class_exists( 'SI_Updates' ) ) {
			$license_active = ( SI_Updates::license_status() != false && SI_Updates::license_status() == 'valid' );
		} else {
			$license_active = SI_Free_License::license_status();
		}

		$address = get_option( SI_Admin_Settings::ADDRESS_OPTION, array() );
		$enabled_pps = get_option( SI_Payment_Processors::ENABLED_PROCESSORS_OPTION, array() );
		$progress = array(
				array(
					'label' => __( 'Activate License', 'sprout-invoices' ),
					'aria-label' => __( 'Activate your license to get updates', 'sprout-invoices' ),
					'link' => admin_url( 'admin.php?page=sprout-invoices-settings' ),
					'status' => ( $license_active ) ? true : false,
				),
				array(
					'label' => __( 'Update Company Info', 'sprout-invoices' ),
					'aria-label' =>
				__( 'Update your business information for display on your invoices and estimates.', 'sprout-invoices' ),
					'link' => admin_url( 'admin.php?page=sprout-invoices-settings' ),
					'status' => ( empty( $address ) ) ? false : true,
				),
				array(
					'label' => __( 'Create an Estimate', 'sprout-invoices' ),
					'aria-label' =>
				__( 'Create your first estimate' ),
					'link' => admin_url( 'post-new.php?post_type=sa_estimate' ),
					'status' => ( $total_estimates > 0 ) ? true : false,
				),
				array(
					'label' => __( 'Create an Invoice', 'sprout-invoices' ),
					'aria-label' =>
				__( 'Create an invoice, or accept the estimate to create one automatically' ),
					'link' => admin_url( 'post-new.php?post_type=sa_invoice' ),
					'status' => ( $total_invoices > 0 ) ? true : false,
				),
				array(
					'label' => __( 'Customize with Your Logo & Colors', 'sprout-invoices' ),
					'aria-label' =>
				__( 'Use the customizer to add a custom logo to your invoices and estimates, and alter the the colors to match.', 'sprout-invoices' ),
					'link' => admin_url( 'admin.php?page=sprout-invoices-settings' ),
					'status' => ( get_theme_mod( 'si_logo', false ) ) ? true : false,
				),
				array(
					'label' => __( 'Activate Additional Features', 'sprout-invoices' ),
					'aria-label' =>
				__( 'Manage add-ons, including Client Dashboards and Recurring Payments.', 'sprout-invoices' ),
					'link' => admin_url( 'admin.php?page=sprout-invoices-addons' ),
					'status' => ( '' !== get_option( SA_Addons::PROGRESS_TRACKER, '' ) ) ? true : false,
				),
				array(
					'label' => __( 'Setup Notifications', 'sprout-invoices' ),
					'aria-label' =>
				__( 'Personalize your notifications, and maybe make them pretty with some HTML.', 'sprout-invoices' ),
					'link' => admin_url( 'admin.php?page=sprout-invoices-notifications' ),
					'status' => ( '' !== get_option( SI_Notifications_Control::EMAIL_FROM_EMAIL, '' ) ) ? true : false,
				),
				array(
					'label' => __( 'Setup Payments', 'sprout-invoices' ),
					'aria-label' =>
				__( 'Enable ways for you to get paid!', 'sprout-invoices' ),
					'link' => admin_url( 'admin.php?page=sprout-invoices-payments' ),
					'status' => ( ! empty( $enabled_pps ) ) ? true : false,
					),
					array(
					'label' => __( 'Integrate with a Form Builder', 'sprout-invoices' ),
					'aria-label' =>
					__( 'Mark this complete by installing one of our free integration add-ons from the WordPress.org repo.', 'sprout-invoices' ),
					'link' => admin_url( 'admin.php?page=sprout-invoices-settings' ),
					'status' => ( class_exists( 'NF_SproutInvoices' ) || class_exists( 'SI_GF_Integration_Addon_Bootstrap' ) || class_exists( 'SI_Formidable' ) || class_exists( 'SI_WPForms' ) ) ? true : false,
					),
					array(
					'label' => __( 'Review Import Methods', 'sprout-invoices' ),
					'aria-label' =>
					__( 'If not starting fresh you can import from another source.', 'sprout-invoices' ),
					'link' => admin_url( 'admin.php?page=sprout-invoices-import' ),
					'status' => ( '' !== get_option( SI_Importer::PROGRESS_TRACKER, '' ) ) ? true : false,
					),
				);
				return apply_filters( 'si_setup_tracker', $progress );
	}

	/////////////
	// Scripts //
	/////////////

	public static function register_scripts( $page ) {
		// Check if we are currently viewing our setting page
		if ( strpos( $page, self::TEXT_DOMAIN ) !== false ) {

			// Vue.js
			wp_enqueue_script( 'sprout-invoices-vue', SI_URL . '/resources/admin/js/vue.js', array(), self::SI_VERSION, false );

			// SI plugin settings
			wp_enqueue_script( 'sprout-invoices-settings', SI_URL . '/resources/admin/js/settings.js', array( 'sprout-invoices-vue', 'jquery', 'si_admin' ), self::SI_VERSION, true );

			wp_enqueue_style( 'sprout-invoices-settings', SI_URL . '/resources/admin/css/settings.css', array( 'sprout_invoice_admin_css' ), self::SI_VERSION );
		}
	}

	public static function script_localization() {
		// Sending data to our plugin settings JS file
		wp_localize_script( 'sprout-invoices-settings', 'SI_Settings', array(
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'siteUrl' => get_site_url(),
			'options' => apply_filters( 'si_settings_options', self::add_settings_options() ),
		) );
	}

	public static function add_settings_options( $options = array() ) {
		$settings = self::get_si_settings();
		$san_settings = SI_Settings_API::_build_settings_array( $settings );
		return array_merge( $san_settings, $options );
	}

	public static function _build_settings_array( $settings = array() ) {
		$opts = array();
		foreach ( $settings as $section => $setting_section ) {
			if ( isset( $setting_section['settings'] ) ) {
				$options = self::_sanitize_input_array_for_vue( $setting_section['settings'] );
				$opts = array_merge( $options, $opts );
			} else {
				if ( ! is_array( $setting_section ) ) {
					continue;
				}
				foreach ( $setting_section as $key => $set_sec ) {
					if ( isset( $set_sec['settings'] ) ) {
						$options = self::_sanitize_input_array_for_vue( $set_sec['settings'] );
						$opts = array_merge( $options, $opts );
					}
				}
			}
		}
		return $opts;
	}

	public static function _sanitize_input_array_for_vue( $settings = array() ) {
		foreach ( $settings as $key => $data ) {
			$default = ( isset( $data['option']['default'] ) ) ? $data['option']['default'] : '' ;
			$options[ SI_Settings_API::_sanitize_input_for_vue( $key ) ] = $default;
		}
		return $options;
	}

	public static function _sanitize_input_for_vue( $key = '' ) {
		return str_replace( '-', '', $key );
	}

	/**
	 * TODO: Optimize
	 * @param  string $value
	 * @param  string $key
	 * @return sanitized value
	 */
	public static function maybe_sanitize_value( $value = '', $key = '' ) {
		$settings = self::get_si_settings();
		foreach ( $settings as $k => $section ) {
			if ( isset( $section['settings'] ) && ! empty( $section['settings'] ) ) {
				foreach ( $section['settings'] as $kee => $field ) {
					if ( $key !== $kee ) {
						continue;
					}
					if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
						$value = call_user_func( $field['sanitize_callback'], $value );
					}
				}
			}
		}
		return $value;
	}

	/////////////
	// Utility //
	/////////////

	public static function register_rest_route() {

		if ( ! current_user_can( 'manage_sprout_invoices_options' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you cannot view/user this resource without Sprout Invoices admin access.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		register_rest_route( 'si-settings/v1', '/save', array(
			'methods' => 'POST',
			'callback' => function() {
				foreach ( $_POST as $option_key => $value ) {

					if ( substr( $option_key, 0, strlen( 'si_' ) ) === 'si_' ) {

						$value = apply_filters( 'si_settings_sanitize_pre_save', $value, $option_key );

						update_option( $option_key, $value );
					}
				}

				// TODO REMOVE - don't flush the rewrite rules every time settings are saved...this will help those that have already installed though.
				flush_rewrite_rules();

				do_action( 'si_settings_saved' );

				die( '1' );
			},
		) );

		register_rest_route( 'si-settings/v1', '/manage-addon', array(
			'methods' => 'POST',
			'callback' => function() {

				if ( isset( $_POST['activate'] ) ) {
					SA_Addons::activate_addon( $_POST['activate'] );
				}

				if ( isset( $_POST['deactivate'] ) ) {
					SA_Addons::deactivate_addon( $_POST['deactivate'] );
				}

				do_action( 'si_addons_managed' );

				die( '1' );
			},
		) );

		register_rest_route( 'si-settings/v1', '/manage-pp', array(
			'methods' => 'POST',
			'callback' => function() {

				$update_cc = ( isset( $_POST['update_cc'] ) && $_POST['update_cc'] ) ? true : false ;
				if ( isset( $_POST['activate'] ) ) {
					$active_pp = SI_Payment_Processors::activate_pp( $_POST['activate'], $update_cc );
				}

				if ( isset( $_POST['deactivate'] ) ) {
					$active_pp = SI_Payment_Processors::deactivate_pp( $_POST['deactivate'] );
				}

				do_action( 'si_pps_managed' );

				wp_send_json_success( $active_pp );
			},
		) );
	}

	public static function display_settings( $settings = array(), $vue = false ) {
		foreach ( $settings as $key => $field ) {
			if ( isset( $field['option'] ) && is_callable( $field['option'] ) ) {
				call_user_func_array( $field['option'], $field );
			} else {
				printf( '<div class="si_field_wrap">%s</div>', self::get_display_field( $key, $field, $vue ) );
			}
		}
	}

	public static function get_display_field( $key = '', $field = array(), $vue = false ) {

		// all inputs need attributes
		if ( ! isset( $field['option']['attributes'] ) || ! is_array( $field['option']['attributes'] ) ) {
			$field['option']['attributes'] = array();
		}

		switch ( $field['option']['type'] ) {
			case 'text':
			case 'input':
				$fld = self::input_field_wrap( self::get_input_field( $key, $field ), $field );
				break;
			case 'textarea':
				$fld = self::input_field_wrap( self::get_textarea_field( $key, $field ), $field );
				break;
			case 'wysiwyg':
				$field['option']['attributes'] = array_merge( array( 'class' => 'si_wysiwyg' ), $field['option']['attributes'] );
				$field['option']['cols'] = 100;
				$fld = self::input_field_wrap( self::get_textarea_field( $key, $field ), $field );
				break;
			case 'small-input':
				$field['option']['attributes'] = array_merge( array( 'class' => 'small-input' ), $field['option']['attributes'] );
				$fld = self::input_field_wrap( self::get_input_field( $key, $field ), $field );
				break;
			case 'checkbox':
				$fld = self::input_field_wrap( self::get_checkbox_field( $key, $field ), $field );
				break;
			case 'radio':
			case 'radios':
				$fld = self::input_field_wrap( self::get_radio_field( $key, $field ), $field );
				break;
			case 'select':
				$fld = self::input_field_wrap( self::get_select_field( $key, $field ), $field );
				break;
			case 'group-select':
			case 'select-state':
				$fld = self::input_field_wrap( self::get_group_select_field( $key, $field ), $field );
				break;
			case 'file':
				$fld = self::input_field_wrap( self::get_file_input_field( $key, $field ), $field );
				break;
			case 'hidden':
				$fld = self::input_field_wrap( self::get_hidden_input_field( $key, $field ), $field );
				break;
			case 'bypass':
				// don't add view since it's bypassed
				return self::input_field_wrap( $field['option']['output'], $field );
				break;
			default:
				$fld = self::input_field_wrap( self::get_input_field( $key, $field ), $field );
				break;
		}
		if ( $vue ) {
			$fld = str_replace( 'type=', sprintf( 'v-model="vm.%s" type=', self::_sanitize_input_for_vue( $key ) ), $fld );
		}
		return $fld;
	}

	public static function input_field_wrap( $input, $field ) {
		$description = ( isset( $field['option']['description'] ) && '' !== $field['option']['description'] ) ? $field['option']['description'] : false ;
		if ( $description ) {
			$description = sprintf( '<span class="input_desc help_block">%s</span>', $description );
		}
		return sprintf( '<div class="si_input_field_wrap si_field_wrap_input_%s">%s%s</div>', $field['option']['type'], $input, $description );
	}

	public static function get_input_field( $key, $field = array() ) {
		$default = ( isset( $field['option']['default'] ) ) ? $field['option']['default'] : '' ;
		$attributes = '';
		// sigh
		if ( isset( $field['option']['attributes']['class'] ) ) {
			$field['option']['attributes']['class'] = $field['option']['attributes']['class'] . ' si_input';
		} else {
			$field['option']['attributes']['class'] = 'si_input';
		}

		foreach ( $field['option']['attributes'] as $attr => $attr_value ) {
			$attributes .= esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" ';
		}
		ob_start(); ?>
			<label for="<?php echo esc_attr( $key ); ?>" class="si_input_label"><?php echo $field['label'] ?></label>
			<input 
			type="<?php echo esc_attr( $field['option']['type'] ); ?>" 
			name="<?php echo esc_attr( $key ); ?>" 
			id="<?php echo esc_attr( $key ); ?>" 
			value="<?php echo $default ?>" 
			placeholder="<?php echo isset( $field['option']['placeholder'] )?$field['option']['placeholder']:''; ?>" 
			size="<?php echo isset( $field['option']['size'] )?$field['option']['size']:40; ?>" <?php echo $attributes ?> 
			<?php if ( isset( $field['option']['required'] ) && $field['option']['required'] ) { echo 'required'; } ?> />
		<?php
		return apply_filters( 'si_admin_settings_input_field', ob_get_clean(), $field );
	}

	public static function get_textarea_field( $key, $field = array() ) {
		$default = ( isset( $field['option']['default'] ) ) ? $field['option']['default'] : '' ;
		$attributes = '';
		foreach ( $field['option']['attributes'] as $attr => $attr_value ) {
			$attributes .= esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" ';
		}
		ob_start(); ?>
			<label for="<?php echo esc_attr( $key ); ?>" class="si_input_label"><?php echo $field['label'] ?></label>
			<textarea type="textarea" name="<?php echo esc_attr( $key ); ?>" 
			id="<?php echo esc_attr( $key ); ?>" rows="<?php echo isset( $field['option']['rows'] )?$field['option']['rows']:4; ?>" cols="<?php echo isset( $field['option']['cols'] )?$field['option']['cols']:40; ?>" <?php echo $attributes ?>><?php echo esc_textarea( $default ); ?></textarea>
		<?php
		return apply_filters( 'si_admin_settings_textarea_field', ob_get_clean(), $field );
	}

	public static function get_select_field( $key, $field = array() ) {
		$default = ( isset( $field['option']['default'] ) ) ? $field['option']['default'] : '' ;
		$attributes = '';
		foreach ( $field['option']['attributes'] as $attr => $attr_value ) {
			$attributes .= esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" ';
		}
		ob_start(); ?>
			<label for="<?php echo esc_attr( $key ); ?>" class="si_input_label"><?php echo $field['label'] ?></label>
			<select type="select" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" <?php echo $attributes ?> <?php if ( isset( $field['option']['required'] ) && $field['option']['required'] ) { echo 'required'; } ?>>
				<?php foreach ( $field['option']['options'] as $option_key => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $field['option']['default'] ) ?>><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php
		return apply_filters( 'si_admin_settings_input_field', ob_get_clean(), $field );
	}

	public static function get_group_select_field( $key, $field = array() ) {
		$default = ( isset( $field['option']['default'] ) ) ? $field['option']['default'] : '' ;
		$attributes = '';
		foreach ( $field['option']['attributes'] as $attr => $attr_value ) {
			$attributes .= esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" ';
		}
		ob_start(); ?>
			<label for="<?php echo esc_attr( $key ); ?>" class="si_input_label"><?php echo $field['label'] ?></label>
			<select type="select" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" <?php echo $attributes ?> <?php if ( isset( $field['option']['required'] ) && $field['option']['required'] ) { echo 'required'; } ?>>
				<?php foreach ( $field['options'] as $group => $opts ) : ?>
					<optgroup label="<?php echo esc_attr( $group ); ?>">
						<?php foreach ( $opts as $option_key => $option_label ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $field['option']['default'] ) ?>><?php echo esc_html( $option_label ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			</select>
		<?php
		return apply_filters( 'si_admin_settings_input_field', ob_get_clean(), $field );
	}

	public static function get_radio_field( $key, $field = array() ) {
		$default = ( isset( $field['option']['default'] ) ) ? $field['option']['default'] : '' ;
		$attributes = '';
		foreach ( $field['option']['attributes'] as $attr => $attr_value ) {
			$attributes .= esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" ';
		}
		ob_start(); ?>
			<label for="<?php echo esc_attr( $key ); ?>" class="si_input_label"><?php echo $field['label'] ?></label>
			<?php foreach ( $field['option']['options'] as $option_key => $option_label ) : ?>
				<label for="<?php echo esc_attr( $key ); ?>_<?php esc_attr_e( $option_key ); ?>" class="si_radio_label">
					<input type="radio" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>_<?php esc_attr_e( $option_key ); ?>" value="<?php esc_attr_e( $option_key ); ?>" <?php checked( $option_key, $default ) ?> <?php echo $attributes ?>/>&nbsp;<?php echo esc_html( $option_label ); ?>
				</label>
				
			<?php endforeach; ?>
		<?php
		return apply_filters( 'si_admin_settings_input_field', ob_get_clean(), $field );
	}

	public static function get_file_input_field( $key, $field = array() ) {
		$default = ( isset( $field['option']['default'] ) ) ? $field['option']['default'] : '' ;
		$attributes = '';
		foreach ( $field['option']['attributes'] as $attr => $attr_value ) {
			$attributes .= esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" ';
		}
		ob_start(); ?>
			<label for="<?php echo esc_attr( $key ); ?>" class="si_input_label"><?php echo $field['label'] ?></label>
			<input type="file" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="si_input_file" />
			<label for="<?php echo esc_attr( $key ); ?>"><span><strong><span class="dashicons dashicons-paperclip"></span> <?php _e( 'Choose a file&hellip;', 'sprout-invoices' ) ?></strong></span></label>
		<?php
		return apply_filters( 'si_admin_settings_file_input_field', ob_get_clean(), $field );
	}

	public static function get_multi_file_input_field( $key, $field = array() ) {
		$default = ( isset( $field['option']['default'] ) ) ? $field['option']['default'] : '' ;
		$attributes = '';
		foreach ( $field['option']['attributes'] as $attr => $attr_value ) {
			$attributes .= esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" ';
		}
		ob_start(); ?>
			<input type="file" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="si_input_file" data-multiple-caption="<?php _e( '{count} files selected', 'sprout-invoices' ) ?>" multiple />
			<label for="<?php echo esc_attr( $key ); ?>"><span><strong><span class="dashicons dashicons-paperclip"></span> <?php _e( 'Choose a file&hellip;', 'sprout-invoices' ) ?></strong></span></label>
		<?php
		return apply_filters( 'si_admin_settings_file_input_field', ob_get_clean(), $field );
	}

	public static function get_hidden_input_field( $key, $field = array() ) {
		$attributes = '';
		foreach ( $field['option']['attributes'] as $attr => $attr_value ) {
			$attributes .= esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" ';
		}
		ob_start(); ?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $field['option']['value'] ); ?>" <?php echo $attributes ?> <?php if ( isset( $field['option']['required'] ) && $field['option']['required'] ) { echo 'required'; } ?> class="si-hidden-input" />
		<?php
		return apply_filters( 'si_admin_settings_input_field', ob_get_clean(), $field );
	}

	public static function get_checkbox_field( $key, $field = array() ) {
		$default = ( isset( $field['option']['default'] ) ) ? $field['option']['default'] : '' ;
		$attributes = '';
		foreach ( $field['option']['attributes'] as $attr => $attr_value ) {
			$attributes .= esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" ';
		}
		ob_start(); ?>
			<label for="<?php echo esc_attr( $key ); ?>" class="si_input_label si_checkbox_label">
				<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="si-checkbox" <?php checked( $field['option']['value'], $default ); ?> value="<?php echo isset( $field['option']['value'] )?$field['option']['value']:'On'; ?>" <?php echo $attributes ?> <?php if ( isset( $field['option']['required'] ) && $field['option']['required'] ) { echo 'required'; } ?>/>&nbsp;<?php echo $field['label'] ?>
			</label>
		<?php
		return apply_filters( 'si_admin_settings_checkbox_field', ob_get_clean(), $field );
	}
}
