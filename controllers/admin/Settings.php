<?php

/**
 * Admin settings controller.
 *
 * @package Sprout_Invoice
 * @subpackage Settings
 */
class SI_Admin_Settings extends SI_Controller {
	const WELCOME_SETTINGS_SLUG = 'welcome';
	const ADDRESS_OPTION = 'si_address';
	const COUNTRIES_OPTION = 'si_countries_filter';
	const STATES_OPTION = 'si_states_filter';
	const MENU_ID = 'si_menu';
	protected static $address;
	protected static $option_countries;
	protected static $option_states;

	public static function init() {
		// Store options
		self::$address = get_option( self::ADDRESS_OPTION, FALSE );
		self::$option_countries = get_option( self::COUNTRIES_OPTION, FALSE );
		self::$option_states = get_option( self::STATES_OPTION, FALSE );
		
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
			'tab_title' => 'General Settings',
			'weight' => 10,
			'reset' => FALSE, 
			'section' => 'settings'
			);
		do_action( 'sprout_settings_page', $args );

		// Dashboard
		$args = array(
			'slug' => 'dashboard',
			'title' => 'Sprout Invoices Dashboard',
			'menu_title' => 'Getting Started',
			'weight' => 1,
			'reset' => FALSE, 
			'tab_only' => TRUE,
			'section' => 'settings',
			'callback' => array( __CLASS__, 'welcome_page' )
			);
		do_action( 'sprout_settings_page', $args );

		// Settings
		$settings = array(
			'si_site_settings' => array(
				'title' => 'Company Info',
				'weight' => 200,
				'tab' => 'settings',
				'callback' => array( __CLASS__, 'display_general_section' ),
				'settings' => array(
					self::ADDRESS_OPTION => array(
						'label' => NULL,
						'option' => array( __CLASS__, 'display_address_fields' ),
						'sanitize_callback' => array( __CLASS__, 'save_address' )
					),
				)
			),
			/*/
			'si_form_settings' => array(
				'title' => 'Form Settings',
				'weight' => 500,
				'callback' => array( __CLASS__, 'display_internationalization_section' ),
				'settings' => array(
					self::STATES_OPTION => array(
						'label' => self::__( 'States' ),
						'option' => array( __CLASS__, 'display_option_states' ),
						'sanitize_callback' => array( __CLASS__, 'save_states' )
					),
					self::COUNTRIES_OPTION => array(
						'label' => self::__( 'Countries' ),
						'description' => 'test',
						'option' => array( __CLASS__, 'display_option_countries' ),
						'sanitize_callback' => array( __CLASS__, 'save_countries' )
					)
				)
			)
			/**/
		);
		do_action( 'sprout_settings', $settings );
	}

	/**
	 * Check if the plugin has been activated, redirect if true and delete the option to prevent a loop.
	 * @package Sprout_Invoices
	 * @subpackage Base
	 * @ignore
	 */
	public static function redirect_on_activation() {
		if ( get_option( 'si_do_activation_redirect', FALSE ) ) {
			// Flush the rewrite rules after SI is activated.
			flush_rewrite_rules();
			delete_option( 'si_do_activation_redirect' );
			wp_redirect( admin_url( 'admin.php?page=' . self::TEXT_DOMAIN . '/settings&tab=dashboard' ) );
		}
	}

	/**
	 * Check if SSL is being used
	 * @param  WP     $wp 
	 * @return bool     
	 */
	public static function ssl_check( WP $wp ) {
		if ( apply_filters( 'si_require_ssl', FALSE, $wp ) ) {
			self::ssl_required();
		} else {
			self::no_ssl();
		}
	}

	protected static function ssl_required() {
		if ( !is_ssl() ) {
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
		if ( is_ssl() && strpos( self::si_get_home_url_option(), 'https' ) === FALSE && apply_filters( 'si_no_ssl_redirect', FALSE ) ) {
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
		$premium = ( !SI_FREE_TEST && file_exists( SI_PATH.'/controllers/updates/Updates.php' ) ) ? '-premium' : '' ;
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
		echo '<p>'.self::_e( 'The company name and address will be shown on the estimates and invoices.' ).'</p>';
	}

	public static function display_address_fields() {
		echo '<div id="client_fields" class="admin_fields clearfix">';
		sa_admin_fields( self::address_form_fields( FALSE ) );
		echo '</div>';
	}

	public static function address_form_fields( $required = TRUE ) {

		$fields['name'] = array(
			'weight' => 1,
			'label' => self::__( 'Company Name' ),
			'type' => 'text',
			'required' => $required,
			'default' => ( isset(self::$address['name']) ) ? self::$address['name'] : get_bloginfo( 'name' )
		);
		$fields['email'] = array(
			'weight' => 2,
			'label' => self::__( 'Contact Email' ),
			'type' => 'text',
			'required' => $required,
			'default' => self::$address['email']
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
		$address = array(
			'name' => isset( $_POST['sa_metabox_name'] ) ? $_POST['sa_metabox_name'] : '',
			'first_name' => isset( $_POST['sa_metabox_first_name'] ) ? $_POST['sa_metabox_first_name'] : '',
			'last_name' => isset( $_POST['sa_metabox_last_name'] ) ? $_POST['sa_metabox_last_name'] : '',
			'email' => isset( $_POST['sa_metabox_email'] ) ? $_POST['sa_metabox_email'] : '',
			'street' => isset( $_POST['sa_metabox_street'] ) ? $_POST['sa_metabox_street'] : '',
			'city' => isset( $_POST['sa_metabox_city'] ) ? $_POST['sa_metabox_city'] : '',
			'zone' => isset( $_POST['sa_metabox_zone'] ) ? $_POST['sa_metabox_zone'] : '',
			'postal_code' => isset( $_POST['sa_metabox_postal_code'] ) ? $_POST['sa_metabox_postal_code'] : '',
			'country' => isset( $_POST['sa_metabox_country'] ) ? $_POST['sa_metabox_country'] : '',
		);
		return $address;
	}

	public static function get_site_address() {
		return self::$address;
	}


	////////////////////////////////
	// State and Country Settings //
	////////////////////////////////

	public static function display_internationalization_section() {
		echo '<p>'.self::_e( 'Select the states and countries/provinces for all forms, e.g. purchase, estimates and registration.' ).'</p>';

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
			foreach ($states as $key => $name) {
				$selected = ( empty( self::$option_states ) || ( isset( self::$option_states[$group] ) && in_array( $name, self::$option_states[$group] ) ) ) ? 'selected="selected"' : null ;
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
	public static function display_option_countries() { ?>
		<div class="sprout_country_options">
			<select name="<?php echo self::COUNTRIES_OPTION ?>[]" multiple="multiple" class="select2" style="min-width:50%;">
				<?php foreach ( parent::$countries as $key => $name ): ?>
					<?php $selected = ( empty( self::$option_countries ) || in_array( $name, self::$option_countries ) ) ? 'selected="selected"' : null ;  ?>
					<option value="<?php echo $name ?>" <?php echo $selected ?>><?php echo $name ?></option>
				<?php endforeach ?>
			</select>
		</div> <?php
	}

	/**
	 * Save callback for saving states
	 * @param  array  $options 
	 * @return $options          
	 */
	public static function save_states( $selected = array() ) {
		$sanitized_options = array();
		if ( is_array( $selected ) ) {
			foreach ( self::$grouped_states as $group => $states ) {
				$sanitized_options[$group] = array();
				foreach ($states as $key => $name) {
					if ( in_array( $key, $selected ) ) {
						$sanitized_options[$group][$key] = $name;
					}
				}
				// Unset the empty groups
				if ( empty( $sanitized_options[$group] ) ) {
					unset( $sanitized_options[$group] );
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
					$sanitized_options[$key] = $name;
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

		if ( !current_user_can( 'manage_options' ) )
			return;

		$menu_items = apply_filters( 'si_admin_bar', array() );
		$sub_menu_items = apply_filters( 'si_admin_bar_sub_items', array() );

		$wp_admin_bar->add_node( array(
				'id' => self::MENU_ID,
				'parent' => false,
				'title' => '<span class="icon-sproutapps-flat">'.self::__('Sprout Invoices').'</span>',
				'href' => admin_url( 'admin.php?page=sprout-apps/settings&tab=reporting' )
			) );

		uasort( $menu_items, array( get_class(), 'sort_by_weight' ) );
		foreach ( $menu_items as $item ) {
			$wp_admin_bar->add_node( array(
					'parent' => self::MENU_ID,
					'id' => $item['id'],
					'title' => self::__($item['title']),
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
					'title' => self::__($item['title']),
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
		if ( !isset( $_GET['tab'] ) ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
					'id' => 'general-about',
					'title' => self::__( 'License' ),
					'content' => sprintf( '<p>%s</p>', self::__('Activate your license if you have not done so already.') )
				) );

			$screen->add_help_tab( array(
					'id' => 'general-leads',
					'title' => self::__( 'Credit Card Processing' ),
					'content' => sprintf( '<p>%s</p><p>%s</p>', self::__('To get you started, Sprout Invoices provides a fully customizable form for estimate submissions. Add the shortcode below to a page to use this default form: <code>[estimate_submission]Thank you![/estimate_submission]</code>'), self::__('Additional documentation is available to customize the default estimate form and using the integration add-on.') )
				) );

			$screen->add_help_tab( array(
					'id' => 'general-estimate',
					'title' => self::__( 'Estimate/Invoice Settings' ),
					'content' => sprintf( '<p>%s</p>', self::__('The Default Terms and Default Notes will be added to each estimate unless an estimate has customized Terms and/or Notes.') )
				) );

			$screen->add_help_tab( array(
					'id' => 'general-notification',
					'title' => self::__( 'Notification Settings' ),
					'content' => sprintf( '<p>%s</p><p>%s</p>', self::__('The from name and from e-mail is used for all Sprout Invoice notifications. Example, “Joc Pederson” future@dodgers.com.'), self::__('Changing the email format to “HTML” will make the default notifications unformatted and look like garbage; if you want to create some pretty HTML notifications make sure to modify all notification formatting.') )
				) );

			$screen->add_help_tab( array(
					'id' => 'general-company',
					'title' => self::__( 'Company Info' ),
					'content' => sprintf( '<p>%s</p>', self::__('This information is used on all estimates and invoices. You’ll want to make sure to set this information before sending out any invoices/estimates.') )
				) );

			$screen->add_help_tab( array(
					'id' => 'general-advanced',
					'title' => self::__( 'Advanced' ),
					'content' => sprintf( '<p>%s</p>', self::__('The option to Save Logs is for debugging purposes and not recommended, unless advised. It’s important to note that turning enabling this option on a live site may cause private transaction data to be saved in the DB unencrypted, i.e. CC data.') )
				) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', self::__('For more information:') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/sprout-invoices-getting-started/', self::__('Documentation') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', self::__('Support') )
			);
		}
	}

}