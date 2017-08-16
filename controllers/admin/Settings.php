<?php

if ( class_exists( 'SA_Settings_API' ) ) {
	// Another Sprout App is active
	return;
}

/**
 * Admin settings controller.
 *
 * @package Sprout_Invoice
 * @subpackage Settings
 */
class SI_Admin_Settings extends SI_Controller {
	const WELCOME_SETTINGS_SLUG = 'welcome';
	const ADDRESS_OPTION = 'si_address';
	const CURRENCY_FORMAT_OPTION = 'si_localeconv_setting';
	const COUNTRIES_OPTION = 'si_countries_filter';
	const STATES_OPTION = 'si_states_filter';
	const MENU_ID = 'si_menu';
	protected static $address;
	protected static $option_countries;
	protected static $option_states;
	protected static $localeconv_options;

	public static function init() {
		// Store options
		self::$address = get_option( self::ADDRESS_OPTION, false );
		self::$option_countries = get_option( self::COUNTRIES_OPTION, false );
		self::$option_states = get_option( self::STATES_OPTION, false );
		self::$localeconv_options = get_option( self::CURRENCY_FORMAT_OPTION, array() );

		// Register Settings
		self::register_settings();

		// Help Sections
		add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

		// Redirect after activation
		add_action( 'admin_init', array( __CLASS__, 'redirect_on_activation' ), 20, 0 );

		// Check if site is using ssl.
		// add_action( 'parse_request', array( __CLASS__, 'ssl_check' ), 0, 1 );

		// Admin bar
		add_action( 'admin_bar_menu', array( get_class(), 'sa_admin_bar' ), 62 );

		add_filter( 'si_localeconv', array( __CLASS__, 'localeconv_options' ), 0 );
	}

	public static function localeconv_options() {
		$localeconv = self::$localeconv_options;
		if ( empty( $localeconv ) || $localeconv['int_curr_symbol'] == '' ) {
			$localeconv = array(
				'decimal_point' => '.',
				'thousands_sep' => ',',
				'int_curr_symbol' => 'USD',
				'currency_symbol' => '$',
				'mon_decimal_point' => '.',
				'mon_thousands_sep' => ',',
				'positive_sign' => '',
				'negative_sign' => '-',
				'int_frac_digits' => 2,
				'frac_digits' => 2,
				'p_cs_precedes' => 1,
				'p_sep_by_space' => 0,
				'n_cs_precedes' => 1,
				'n_sep_by_space' => 0,
				'p_sign_posn' => 1,
				'n_sign_posn' => 1,
				'grouping' => array(),
				'mon_grouping' => array( 3, 3 ),
			);
		}
		return $localeconv;
	}


	//////////////
	// Settings //
	//////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Option page
		$args = array(
			'slug' => self::SETTINGS_PAGE,
			'title' => 'Sprout Invoices Settings',
			'menu_title' => 'Sprout Invoices',
			'tab_title' => __( 'General Settings', 'sprout-invoices' ),
			'weight' => 10,
			'reset' => false,
			'section' => 'settings',
			);
		do_action( 'sprout_settings_page', $args );

		// Dashboard
		$args = array(
			'slug' => 'dashboard',
			'title' => 'Sprout Invoices Dashboard',
			'menu_title' => __( 'Getting Started', 'sprout-invoices' ),
			'weight' => 1,
			'reset' => false,
			'tab_only' => true,
			'section' => 'settings',
			'callback' => array( __CLASS__, 'welcome_page' ),
			);
		do_action( 'sprout_settings_page', $args );

		// Settings
		$settings = array(
			'si_site_settings' => array(
				'title' => __( 'Company Info', 'sprout-invoices' ),
				'weight' => 200,
				'tab' => 'settings',
				'callback' => array( __CLASS__, 'display_general_section' ),
				'settings' => array(
					self::ADDRESS_OPTION => array(
						'label' => null,
						'option' => array( __CLASS__, 'display_address_fields' ),
						'sanitize_callback' => array( __CLASS__, 'save_address' ),
					),
				),
			),
			'si_currency_settings' => array(
				'title' => __( 'Currency Formatting', 'sprout-invoices' ),
				'weight' => 250,
				'tab' => 'settings',
				'callback' => array( __CLASS__, 'display_currency_section' ),
				'settings' => array(
					self::CURRENCY_FORMAT_OPTION => array(
						'label' => null,
						'option' => array( __CLASS__, 'display_currency_locale_fields' ),
						'sanitize_callback' => array( __CLASS__, 'save_currency_locale' ),
					),
				),
			),
			/*/
			'si_form_settings' => array(
				'title' => 'Form Settings',
				'weight' => 500,
				'callback' => array( __CLASS__, 'display_internationalization_section' ),
				'settings' => array(
					self::STATES_OPTION => array(
						'label' => __( 'States', 'sprout-invoices' ),
						'option' => array( __CLASS__, 'display_option_states' ),
						'sanitize_callback' => array( __CLASS__, 'save_states' )
					),
					self::COUNTRIES_OPTION => array(
						'label' => __( 'Countries', 'sprout-invoices' ),
						'description' => 'test',
						'option' => array( __CLASS__, 'display_option_countries' ),
						'sanitize_callback' => array( __CLASS__, 'save_countries' )
					)
				)
			)
			/**/
		);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );
	}

	/**
	 * Check if the plugin has been activated, redirect if true and delete the option to prevent a loop.
	 * @package Sprout_Invoices
	 * @subpackage Base
	 * @ignore
	 */
	public static function redirect_on_activation() {
		if ( get_option( 'si_do_activation_redirect', false ) ) {
			// Flush the rewrite rules after SI is activated.
			flush_rewrite_rules();
			delete_option( 'si_do_activation_redirect' );
			wp_redirect( admin_url( 'admin.php?page=' . self::APP_DOMAIN . '/settings&tab=dashboard' ) );
		}
	}

	/**
	 * Check if SSL is being used
	 * @param  WP     $wp
	 * @return bool
	 */
	public static function ssl_check( WP $wp ) {
		if ( apply_filters( 'si_require_ssl', false, $wp ) ) {
			self::ssl_required();
		} else {
			self::no_ssl();
		}
	}

	protected static function ssl_required() {
		if ( ! is_ssl() ) {
			if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
				wp_redirect( preg_replace( '|^http://|', 'https://', $_SERVER['REQUEST_URI'] ) );
				exit();
			} else {
				wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
				exit();
			}
		}
	}

	protected static function no_ssl() {
		if ( is_ssl() && strpos( self::si_get_home_url_option(), 'https' ) === false && apply_filters( 'si_no_ssl_redirect', false ) ) {
			if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'https' ) ) {
				wp_redirect( preg_replace( '|^https://|', 'http://', $_SERVER['REQUEST_URI'] ) );
				exit();
			} else {
				wp_redirect( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
				exit();
			}
		}
	}

	///////////////////
	// Welcome Page //
	///////////////////

	/**
	 * Dashboard
	 * @return string
	 */
	public static function welcome_page() {
		// TODO REMOVE - don't flush the rewrite rules every time this page is loaded,
		// this will help those that have already installed though.
		flush_rewrite_rules();

		// Determine if this is a premium install.
		// TODO abstract this and use a filter in another file.
		$premium = ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/updates/Updates.php' ) ) ? '-premium' : '' ;
		if ( isset( $_GET['whats-new'] ) ) {
			self::load_view( 'admin/whats-new/'.$_GET['whats-new'].$premium.'.php', array() );
			return;
		}
		self::load_view( 'admin/sprout-invoices-dashboard'.$premium.'.php', array() );
	}

	//////////////////////
	// General Settings //
	//////////////////////

	public static function display_general_section() {
		echo '<p>'._e( 'The company name and address will be shown on the estimates and invoices.', 'sprout-invoices' ).'</p>';
	}

	public static function display_address_fields() {
		echo '<div id="client_fields" class="split admin_fields clearfix">';
		sa_admin_fields( self::address_form_fields( false ) );
		echo '</div>';
	}

	public static function address_form_fields( $required = true ) {

		$fields['name'] = array(
			'weight' => 1,
			'label' => __( 'Company Name', 'sprout-invoices' ),
			'type' => 'text',
			'required' => $required,
			'default' => ( isset( self::$address['name'] ) ) ? self::$address['name'] : get_bloginfo( 'name' ),
		);
		$fields['email'] = array(
			'weight' => 2,
			'label' => __( 'Contact Email', 'sprout-invoices' ),
			'type' => 'text',
			'required' => $required,
			'default' => self::$address['email'],
		);

		$fields = array_merge( $fields, self::get_standard_address_fields( $required ) );

		// Default
		$fields['first_name']['default'] = self::$address['first_name'];
		$fields['last_name']['default'] = self::$address['last_name'];
		$fields['street']['default'] = self::$address['street'];
		$fields['city']['default'] = self::$address['city'];
		$fields['zone']['default'] = self::$address['zone'];
		$fields['postal_code']['default'] = self::$address['postal_code'];
		$fields['country']['default'] = self::$address['country'];

		$fields = apply_filters( 'si_site_address_form_fields', $fields );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	public static function save_address( $address = array() ) {
		$fields = self::address_form_fields( false );

		$address = array();
		foreach ( $fields as $key => $value ) {
			$address[ $key ] = isset( $_POST[ 'sa_metabox_' . $key ] ) ? $_POST[ 'sa_metabox_' . $key ] : '';
		}
		return stripslashes_deep( $address );
	}

	public static function get_site_address() {
		return self::$address;
	}


	///////////////////////
	// Currency options //
	///////////////////////

	public static function display_currency_section() {
		printf( __( '<p>Manually set your currency formatting. More information about these settings and using a filter can be found in the <a href="%s">documentation</a>.</p>', 'sprout-invoices' ), 'https://sproutapps.co/support/knowledgebase/sprout-invoices/troubleshooting/troubleshooting-moneycurrency-issues/' );
	}

	public static function display_currency_locale_fields() {
		$localeconv = self::localeconv_options();

		$fields['int_curr_symbol'] = array(
			'weight' => 1,
			'label' => __( 'International Currency Symbol', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( isset( $localeconv['int_curr_symbol'] ) ) ? $localeconv['int_curr_symbol'] : '',
			'description' => __( 'U.S. default is <code>USD</code>', 'sprout-invoices' ),
		);
		$fields['currency_symbol'] = array(
			'weight' => 1,
			'label' => __( 'Currency Symbol', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( isset( $localeconv['currency_symbol'] ) ) ? $localeconv['currency_symbol'] : '',
			'description' => __( 'U.S. default is <code>$</code>', 'sprout-invoices' ),
		);
		$fields['mon_decimal_point'] = array(
			'weight' => 5,
			'label' => __( 'Decimal Point', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( isset( $localeconv['mon_decimal_point'] ) ) ? $localeconv['mon_decimal_point'] : '.',
			'description' => __( 'U.S. default is <code>.</code>', 'sprout-invoices' ),
		);
		$fields['mon_thousands_sep'] = array(
			'weight' => 10,
			'label' => __( 'Thousands Separator', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( isset( $localeconv['mon_thousands_sep'] ) ) ? $localeconv['mon_thousands_sep'] : ',',
			'description' => __( 'U.S. default is <code>,</code>', 'sprout-invoices' ),
		);
		$fields['positive_sign'] = array(
			'weight' => 15,
			'label' => __( 'Positive Sign', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( isset( $localeconv['positive_sign'] ) ) ? $localeconv['positive_sign'] : '',
			'description' => __( 'U.S. default is blank', 'sprout-invoices' ),
		);
		$fields['negative_sign'] = array(
			'weight' => 1,
			'label' => __( 'Negative Sign', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( isset( $localeconv['negative_sign'] ) ) ? $localeconv['negative_sign'] : '',
			'description' => __( 'U.S. default is <code>-</code>', 'sprout-invoices' ),
		);
		$fields['int_frac_digits'] = array(
			'weight' => 1,
			'label' => __( 'Fraction Digits', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( isset( $localeconv['int_frac_digits'] ) ) ? $localeconv['int_frac_digits'] : '',
			'description' => __( 'U.S. default is <code>2</code>', 'sprout-invoices' ),
		);
		$fields['mon_grouping'] = array(
			'weight' => 1,
			'label' => __( 'Money Grouping', 'sprout-invoices' ),
			'type' => 'checkbox',
			'type' => 'text',
			'default' => ( ! empty( $localeconv['mon_grouping'] ) ) ? implode( ',', $localeconv['mon_grouping'] ) : '3, 3',
			'description' => __( 'U.S. default is <code>3, 3</code>', 'sprout-invoices' ),
		);
		$fields['p_cs_precedes'] = array(
			'weight' => 1,
			'label' => __( 'Currency Symbol Precedes (Positive)', 'sprout-invoices' ),
			'type' => 'checkbox',
			'value' => '1',
			'default' => ( isset( $localeconv['p_cs_precedes'] ) ) ? $localeconv['p_cs_precedes'] : 1,
			'description' => __( 'U.S. default is checked.', 'sprout-invoices' ),
		);
		$fields['p_sep_by_space'] = array(
			'weight' => 1,
			'label' => __( 'Space Separation (Positive)', 'sprout-invoices' ),
			'type' => 'checkbox',
			'value' => '1',
			'default' => ( isset( $localeconv['p_sep_by_space'] ) ) ? $localeconv['p_sep_by_space'] : 0,
			'description' => __( 'U.S. default is unchecked.', 'sprout-invoices' ),
		);
		$fields['p_sign_posn'] = array(
			'weight' => 1,
			'label' => __( 'Positive Position', 'sprout-invoices' ),
			'type' => 'checkbox',
			'value' => '1',
			'default' => ( isset( $localeconv['p_sign_posn'] ) ) ? $localeconv['p_sign_posn'] : 1,
			'description' => __( 'U.S. default is checked.', 'sprout-invoices' ),
		);
		$fields['n_cs_precedes'] = array(
			'weight' => 1,
			'label' => __( 'Currency Symbol Precedes (Negative)', 'sprout-invoices' ),
			'type' => 'checkbox',
			'value' => '1',
			'default' => ( isset( $localeconv['n_cs_precedes'] ) ) ? $localeconv['n_cs_precedes'] : 1,
			'description' => __( 'U.S. default is checked.', 'sprout-invoices' ),
		);
		$fields['n_sep_by_space'] = array(
			'weight' => 1,
			'label' => __( 'Space Separation (Negative)', 'sprout-invoices' ),
			'type' => 'checkbox',
			'value' => '1',
			'default' => ( isset( $localeconv['n_sep_by_space'] ) ) ? $localeconv['n_sep_by_space'] : 0,
			'description' => __( 'U.S. default is unchecked.', 'sprout-invoices' ),
		);
		$fields['n_sign_posn'] = array(
			'weight' => 1,
			'label' => __( 'Positive Position', 'sprout-invoices' ),
			'type' => 'checkbox',
			'value' => '1',
			'default' => ( isset( $localeconv['n_sign_posn'] ) ) ? $localeconv['n_sign_posn'] : 1,
			'description' => __( 'U.S. default is checked.', 'sprout-invoices' ),
		);
		echo '<div id="currency_fields" class="split admin_fields clearfix">';
		sa_admin_fields( $fields );
		echo '</div>';
	}

	public static function save_currency_locale( $locale = array() ) {
	 	$localeconv = array();
	 	$lc_options = array(
			   'decimal_point' => '.',
			   'thousands_sep' => ',',
			   'int_curr_symbol' => 'USD',
			   'currency_symbol' => '$',
			   'mon_decimal_point' => '.',
			   'mon_thousands_sep' => ',',
			   'positive_sign' => '',
			   'negative_sign' => '-',
			   'int_frac_digits' => 2,
			   'frac_digits' => 2,
			   'p_cs_precedes' => 1,
			   'p_sep_by_space' => 0,
			   'n_cs_precedes' => 1,
			   'n_sep_by_space' => 0,
			   'p_sign_posn' => 1,
			   'n_sign_posn' => 1,
			   'grouping' => array(),
			   'mon_grouping' => array( 3, 3 ),
		   );
	 	foreach ( $lc_options as $key => $default ) {
	 		$localeconv[ $key ] = isset( $_POST[ 'sa_metabox_'.$key ] ) ? $_POST[ 'sa_metabox_'.$key ] : '';
	 	}
	 	if ( isset( $_POST['sa_metabox_mon_grouping'] ) ) {
	 		$mon_grouping = explode( ',', $_POST['sa_metabox_mon_grouping'] );
	 		if ( is_array( $mon_grouping ) ) {
		 		$localeconv['mon_grouping'] = array_map( 'trim', $mon_grouping );
	 		}
	 	}
		return $localeconv;
	}

		////////////////////////////////
		// State and Country Settings //
		////////////////////////////////

	public static function display_internationalization_section() {
		echo '<p>'._e( 'Select the states and countries/provinces for all forms, e.g. purchase, estimates and registration.', 'sprout-invoices' ).'</p>';

	}

		/**
	 * Display for countries option
	 * @return string
	 */
	public static function display_option_states() {
		echo '<div class="sprout_state_options">';
		echo '<select name="'.self::STATES_OPTION.'[]" multiple="multiple" class="select2" style="min-width:50%;">';
		foreach ( parent::$grouped_states as $group => $states ) {
			echo '<optgroup label="'.$group.'">';
			foreach ( $states as $key => $name ) {
				$selected = ( empty( self::$option_states ) || ( isset( self::$option_states[ $group ] ) && in_array( $name, self::$option_states[ $group ] ) ) ) ? 'selected="selected"' : null ;
				echo '<option value="'.$key.'" '.$selected.'>'.$name.'</option>';
			}
			echo '</optgroup>';
		}
		echo '</select>';
		echo '</div>';
	}

		/**
	 * Display for countries option
	 * @return string
	 */
	public static function display_option_countries() {
	?>
			<div class="sprout_country_options">
				<select name="<?php echo self::COUNTRIES_OPTION ?>[]" multiple="multiple" class="select2" style="min-width:50%;">
					<?php foreach ( parent::$countries as $key => $name ) : ?>
					<?php $selected = ( empty( self::$option_countries ) || in_array( $name, self::$option_countries ) ) ? true : false ;  ?>
					<option value="<?php echo esc_attr( $name ) ?>" <?php selected( $selected, true, true ); ?>><?php echo esc_html( $name ) ?></option>
				<?php endforeach ?>
				</select>
			</div> <?php
	}

		/**
	 * Save callback for saving states
	 * @param  array  $selected
	 * @return $selected
	 */
	public static function save_states( $selected = array() ) {
		$sanitized_options = array();
		if ( is_array( $selected ) ) {
			foreach ( self::$grouped_states as $group => $states ) {
				$sanitized_options[ $group ] = array();
				foreach ( $states as $key => $name ) {
					if ( in_array( $key, $selected ) ) {
						$sanitized_options[ $group ][ $key ] = $name;
					}
				}
				// Unset the empty groups
				if ( empty( $sanitized_options[ $group ] ) ) {
					unset( $sanitized_options[ $group ] );
				}
			}
		}
		return $sanitized_options;
	}

		/**
	 * Save callback for saving countries
	 * @param  array  $options
	 * @return $options
	 */
	public static function save_countries( $options = array() ) {
		$sanitized_options = array();
		if ( is_array( $options ) ) {
			foreach ( self::$countries  as $key => $name ) {
				if ( in_array( $name, $options ) ) {
					$sanitized_options[ $key ] = $name;
				}
			}
		}
		return $sanitized_options;
	}

		////////////
		// Misc. //
		////////////



		/**
	 * Creates an Admin Bar Option Offer and submenu for any registered sub-menus ( admin submenu )
	 *
	 * @static
	 * @return void
	 */
	public static function sa_admin_bar( WP_Admin_Bar $wp_admin_bar ) {

		if ( ! current_user_can( 'manage_sprout_invoices_options' ) ) {
			return; }

		$menu_items = apply_filters( 'si_admin_bar', array() );
		$sub_menu_items = apply_filters( 'si_admin_bar_sub_items', array() );

		$wp_admin_bar->add_node( array(
			'id' => self::MENU_ID,
			'parent' => false,
			'title' => '<span class="icon-sproutapps-flat ab-icon"></span>'.__( 'Sprout Invoices', 'sprout-invoices' ),
			'href' => admin_url( 'admin.php?page=sprout-apps/settings&tab=reporting' ),
		) );

		uasort( $menu_items, array( get_class(), 'sort_by_weight' ) );
		foreach ( $menu_items as $item ) {
			$wp_admin_bar->add_node( array(
				'parent' => self::MENU_ID,
				'id' => $item['id'],
				'title' => __( $item['title'], 'sprout-invoices' ),
				'href' => $item['href'],
			) );
		}

		$wp_admin_bar->add_group( array(
			'parent' => self::MENU_ID,
			'id'     => self::MENU_ID.'_options',
			'meta'   => array( 'class' => 'ab-sub-secondary' ),
		) );

		uasort( $sub_menu_items, array( get_class(), 'sort_by_weight' ) );
		foreach ( $sub_menu_items as $item ) {
			$wp_admin_bar->add_node( array(
				'parent' => self::MENU_ID.'_options',
				'id' => $item['id'],
				'title' => __( $item['title'], 'sprout-invoices' ),
				'href' => $item['href'],
			) );
		}
	}



		////////////////
		// Admin Help //
		////////////////

	public static function help_sections() {
		add_action( 'load-sprout-apps_page_sprout-apps/settings', array( __CLASS__, 'help_tabs' ) );
	}

	public static function help_tabs() {
		if ( ! isset( $_GET['tab'] ) ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
				'id' => 'general-about',
				'title' => __( 'License', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'Activate your license if you have not done so already.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'general-leads',
				'title' => __( 'Credit Card Processing', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p>', __( 'To get you started, Sprout Invoices provides a fully customizable form for estimate submissions. Add the shortcode below to a page to use this default form: <code>[estimate_submission]Thank you![/estimate_submission]</code>', 'sprout-invoices' ), __( 'Additional documentation is available to customize the default estimate form and using the integration add-on.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'general-estimate',
				'title' => __( 'Estimate/Invoice Settings', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'The Default Terms and Default Notes will be added to each estimate unless an estimate has customized Terms and/or Notes.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'general-notification',
				'title' => __( 'Notification Settings', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p>', __( 'The from name and from e-mail is used for all Sprout Invoice notifications. Example, “Joc Pederson” future@dodgers.com.', 'sprout-invoices' ), __( 'Changing the email format to “HTML” will make the default notifications unformatted and look like garbage; if you want to create some pretty HTML notifications make sure to modify all notification formatting.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'general-company',
				'title' => __( 'Company Info', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'This information is used on all estimates and invoices. You’ll want to make sure to set this information before sending out any invoices/estimates.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'general-advanced',
				'title' => __( 'Advanced', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'The option to Save Logs is for debugging purposes and not recommended, unless advised. It’s important to note that turning enabling this option on a live site may cause private transaction data to be saved in the DB unencrypted, i.e. CC data.', 'sprout-invoices' ) ),
			) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', __( 'For more information:', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/sprout-invoices-getting-started/', __( 'Documentation', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', si_get_sa_link( 'https://sproutapps.co/support/' ), __( 'Support', 'sprout-invoices' ) )
			);
		}
	}
}
