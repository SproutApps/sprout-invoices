<?php


/**
 * Updates class
 *
 * @package Sprout_Invoice
 * @subpackage Updates
 */
class SI_Updates extends SI_Controller {
	const LICENSE_KEY_OPTION = 'si_license_key';
	const LICENSE_STATUS = 'si_license_status';
	protected static $license_key;
	protected static $license_status;
	
	public static function init() {
		self::$license_key = trim( get_option( self::LICENSE_KEY_OPTION, '' ) );
		self::$license_status = get_option( self::LICENSE_STATUS, FALSE );
		self::register_settings();

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'init_edd_udpater' ) );

			// AJAX
			add_action( 'wp_ajax_si_activate_license',  array( __CLASS__, 'maybe_activate_license' ), 10, 0 );
			add_action( 'wp_ajax_si_deactivate_license',  array( __CLASS__, 'maybe_deactivate_license' ), 10, 0 );
			add_action( 'wp_ajax_si_check_license',  array( __CLASS__, 'maybe_check_license' ), 10, 0 );
		}
	}

	public static function init_edd_udpater() {

		// setup the updater
		$edd_updater = new EDD_SL_Plugin_Updater( self::PLUGIN_URL, self::PLUGIN_FILE, array( 
				'version' 	=> self::SI_VERSION,			// current version number
				'license' 	=> self::$license_key,	 		// license key (used get_option above to retrieve from DB)
				'item_name' => self::PLUGIN_NAME, 			// name of this plugin
				'author' 	=> 'Sprout Apps' 				// author of this plugin
			)
		);

		// $edd_updater->api_request( 'plugin_latest_version', array( 'slug' => basename( self::PLUGIN_FILE, '.php') ) );

		// uncomment this line for testing
		// set_site_transient( 'update_plugins', null );
	}

	public static function license_key(){
		return self::$license_key;
	}

	public static function license_status(){
		return self::$license_status;
	}

	///////////////
	// Settings //
	///////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			'so_activation' => array(
				'title' => self::__('Sprout Invoices Activation'),
				'weight' => 0,
				'tab' => 'settings',
				'callback' => array( __CLASS__, 'update_setting_description' ),
				'settings' => array(
					self::LICENSE_KEY_OPTION => array(
						'label' => self::__( 'License Key' ),
						'option' => array(
							'type' => 'bypass',
							'output' => self::license_key_option(),
							'description' => sprintf( self::__('Enter your license key to enable automatic plugin updates. Find your license key in your Sprout Apps Dashboard under the <a href="%s" target="_blank">Downloads</a> section.'), self::PLUGIN_URL.'/account/' )
							)
						),
					)
				)
			);
		do_action( 'sprout_settings', $settings );

	}

	public static function license_key_option() {
		ob_start(); ?>
			<input type="text" name="<?php echo self::LICENSE_KEY_OPTION ?>" id="<?php echo self::LICENSE_KEY_OPTION ?>" value="<?php echo self::$license_key ?>" class="<?php echo 'license_'.self::$license_status ?>" size="40" class="text-input">
			<?php if ( self::$license_status != FALSE && self::$license_status == 'valid' ): ?>
				<button id="activate_license" class="button" disabled="disabled"><?php self::_e('Activate License') ?></button> 
				<button id="deactivate_license" class="button"><?php self::_e('Deactivate License') ?></button>
			<?php else: ?>
				<button id="activate_license" class="button button-primary"><?php self::_e('Activate License') ?></button>
			<?php endif ?>
			<div id="license_message" class="clearfix"></div>
		<?php
		$view = ob_get_clean();
		return $view;
	}

	public static function update_setting_description() {
		// self::_e('TODO Describe the license key and how to purchase.');
	}


	///////////////////
	// API Controls //
	///////////////////

	public static function activate_license() {
		$license_data = self::api( 'activate_license' );
		update_option( self::LICENSE_STATUS, $license_data->license );
		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'valid' ) {
			return TRUE;
		}
		return FALSE;
	}

	public static function deactivate_license() {
		$license_data = self::api( 'deactivate_license' );
		
		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' ) {
			delete_option( self::LICENSE_STATUS );
			return TRUE;
		}
		return FALSE;
	}

	public static function check_license() {
		$license_data = self::api( 'check_license' );
		return ( $license_data->license == 'valid' );
	}

	///////////
	// AJAX //
	///////////

	public static function maybe_activate_license() {
		if ( !isset( $_REQUEST['security'] ) )
			self::ajax_fail( 'Forget something?' );

		$nonce = $_REQUEST['security'];
		if ( !wp_verify_nonce( $nonce, self::NONCE ) )
			self::ajax_fail( 'Not going to fall for it!' );

		if ( !current_user_can( 'activate_plugins' ) )
			return;
		
		if ( !isset( $_REQUEST['license'] ) ) {
			self::ajax_fail( 'No license key submitted' );
		}

		update_option( self::LICENSE_KEY_OPTION, $_REQUEST['license'] );
		self::$license_key = $_REQUEST['license'];

		$activated = self::activate_license();
		$message = ( $activated ) ? self::__('Thank you for supporting the future of Sprout Invoices.') : self::__('License is not active.') ;
		$response = array(
				'activated' => $activated,
				'response' => $message,
				'error' => !$activated
			);

		header( 'Content-type: application/json' );
		if ( SA_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $response );
		exit();
	}

	public static function maybe_deactivate_license() {
		if ( !isset( $_REQUEST['security'] ) )
			self::ajax_fail( 'Forget something?' );

		$nonce = $_REQUEST['security'];
		if ( !wp_verify_nonce( $nonce, self::NONCE ) )
			self::ajax_fail( 'Not going to fall for it!' );

		if ( !current_user_can( 'activate_plugins' ) )
			return;
		
		$deactivated = self::deactivate_license();
		$message = ( $deactivated ) ? self::__('License is deactivated.') : self::__('Something went wrong. Contact support for help.') ;
		$response = array(
				'valid' => $deactivated,
				'response' => $message,
				'error' => !$deactivated
			);

		header( 'Content-type: application/json' );
		if ( SA_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $response );
		exit();
	}

	public static function maybe_check_license() {
		if ( !isset( $_REQUEST['security'] ) )
			self::ajax_fail( 'Forget something?' );

		$nonce = $_REQUEST['security'];
		if ( !wp_verify_nonce( $nonce, self::NONCE ) )
			self::ajax_fail( 'Not going to fall for it!' );

		if ( !current_user_can( 'activate_plugins' ) )
			return;
		
		$is_valid = self::check_license();
		$message = ( $is_valid ) ? self::__('Thank you for supporting the future of Sprout Invoices.') : self::__('License is not valid.') ;
		$response = array(
				'valid' => $is_valid,
				'response' => $message,
			);

		header( 'Content-type: application/json' );
		if ( SA_DEV ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $response );
		exit();
	}



	//////////////
	// Utility //
	//////////////


	public static function api( $action = 'activate_license', $api_args = array() ) {
		// data to send in our API request
		$api_params_defaults = array( 
			'edd_action' => $action, 
			'license' => self::$license_key, 
			'item_name' => urlencode( self::PLUGIN_NAME ),
			'url'       => home_url()
		);
		$api_params = wp_parse_args( $api_args, $api_params_defaults );

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, self::PLUGIN_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		return $license_data;
	}
	
}

if ( !class_exists('EDD_SL_Plugin_Updater') ) :
/**
 * Allows plugins to use their own update API.
 *
 * @author Pippin Williamson
 * @version 1.2
 */
class EDD_SL_Plugin_Updater {
	private $api_url  = '';
	private $api_data = array();
	private $name     = '';
	private $slug     = '';
	private $do_check = false;

	/**
	 * Class constructor.
	 *
	 * @uses plugin_basename()
	 * @uses hook()
	 *
	 * @param string $_api_url The URL pointing to the custom API endpoint.
	 * @param string $_plugin_file Path to the plugin file.
	 * @param array $_api_data Optional data to send with API calls.
	 * @return void
	 */
	function __construct( $_api_url, $_plugin_file, $_api_data = null ) {
		$this->api_url  = trailingslashit( $_api_url );
		$this->api_data = urlencode_deep( $_api_data );
		$this->name     = plugin_basename( $_plugin_file );
		$this->slug     = basename( $_plugin_file, '.php');
		$this->version  = $_api_data['version'];

		// Set up hooks.
		$this->hook();
	}

	/**
	 * Set up WordPress filters to hook into WP's update process.
	 *
	 * @uses add_filter()
	 *
	 * @return void
	 */
	private function hook() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_site_transient_update_plugins_filter' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
		add_filter( 'http_request_args', array( $this, 'http_request_args' ), 10, 2 );
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @uses api_request()
	 *
	 * @param array $_transient_data Update array build by WordPress.
	 * @return array Modified update array with custom plugin data.
	 */
	function pre_set_site_transient_update_plugins_filter( $_transient_data ) {

		if( empty( $_transient_data ) || ! $this->do_check ) {

			// This ensures that the custom API request only runs on the second time that WP fires the update check
			$this->do_check = true;

			return $_transient_data;
		}

		$to_send = array( 'slug' => $this->slug );

		$api_response = $this->api_request( 'plugin_latest_version', $to_send );

		if( false !== $api_response && is_object( $api_response ) && isset( $api_response->new_version ) ) {

			if( version_compare( $this->version, $api_response->new_version, '<' ) ) {
				$_transient_data->response[$this->name] = $api_response;
			}
		}
		return $_transient_data;
	}


	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @uses api_request()
	 *
	 * @param mixed $_data
	 * @param string $_action
	 * @param object $_args
	 * @return object $_data
	 */
	function plugins_api_filter( $_data, $_action = '', $_args = null ) {
		if ( ( $_action != 'plugin_information' ) || !isset( $_args->slug ) || ( $_args->slug != $this->slug ) ) return $_data;

		$to_send = array( 'slug' => $this->slug );

		$api_response = $this->api_request( 'plugin_information', $to_send );
		if ( false !== $api_response ) $_data = $api_response;

		return $_data;
	}


	/**
	 * Disable SSL verification in order to prevent download update failures
	 *
	 * @param array $args
	 * @param string $url
	 * @return object $array
	 */
	function http_request_args( $args, $url ) {
		// If it is an https request and we are performing a package download, disable ssl verification
		if( strpos( $url, 'https://' ) !== false && strpos( $url, 'edd_action=package_download' ) ) {
			$args['sslverify'] = false;
		}
		return $args;
	}

	/**
	 * Calls the API and, if successfull, returns the object delivered by the API.
	 *
	 * @uses get_bloginfo()
	 * @uses wp_remote_post()
	 * @uses is_wp_error()
	 *
	 * @param string $_action The requested action.
	 * @param array $_data Parameters for the API action.
	 * @return false||object
	 */
	public function api_request( $_action, $_data ) {
		global $wp_version;

		$data = array_merge( $this->api_data, $_data );

		if( $data['slug'] != $this->slug )
			return;

		if( empty( $data['license'] ) )
			return;

		$api_params = array(
			'edd_action' => 'get_version',
			'license'    => $data['license'],
			'name'       => $data['item_name'],
			'slug'       => $this->slug,
			'author'     => $data['author'],
			'url'        => home_url()
		);

		$request = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		
		if ( ! is_wp_error( $request ) ):
			$request = json_decode( wp_remote_retrieve_body( $request ) );
			if( $request && isset( $request->sections ) )
				$request->sections = maybe_unserialize( $request->sections );
			return $request;
		else:
			return false;
		endif;
	}
}
endif;