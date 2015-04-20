<?php


/**
 * Updates class
 *
 * @package Sprout_Invoice
 * @subpackage Updates
 */
class SI_Free_License extends SI_Controller {
	const LICENSE_KEY_OPTION = 'si_license_key';
	const LICENSE_UID_OPTION = 'si_uid';
	const API_CB = 'https://sproutapps.co/';
	protected static $license_key;
	protected static $uid;
	
	public static function init() {
		self::$license_key = trim( get_option( self::LICENSE_KEY_OPTION, '' ) );
		self::$uid = trim( get_option( self::LICENSE_UID_OPTION, 0 ) );

		if ( is_admin() ) {
			// AJAX
			add_action( 'wp_ajax_si_get_license',  array( __CLASS__, 'maybe_get_free_license' ), 10, 0 );
		}

		add_filter( 'si_get_purchase_link', array( __CLASS__, 'add_uid_to_url' ) );
		add_filter( 'si_get_sa_link', array( __CLASS__, 'add_uid_to_url' ) );

		// Messaging
		add_action( 'si_settings_page',  array( __CLASS__, 'thank_for_registering' ), 10, 0 );
	}

	public static function license_key(){
		return self::$license_key;
	}

	public static function uid(){
		return self::$uid;
	}

	public static function license_status(){
		return ( self::$license_key ) ? TRUE : FALSE;
	}

	///////////
	// AJAX //
	///////////

	public static function maybe_get_free_license() {
		if ( !isset( $_REQUEST['security'] ) )
			self::ajax_fail( 'Forget something?' );

		$nonce = $_REQUEST['security'];
		if ( !wp_verify_nonce( $nonce, self::NONCE ) )
			self::ajax_fail( 'Not going to fall for it!' );

		if ( !current_user_can( 'activate_plugins' ) )
			return;
		
		if ( !isset( $_REQUEST['license'] ) ) {
			self::ajax_fail( 'No email submitted' );
		}

		if ( !is_email( $_REQUEST['license'] ) ) {
			self::ajax_fail( 'No Email Submitted' );
		}

		$license_response = self::get_free_license( $_REQUEST['license'] );
		if ( is_object( $license_response ) ) {
			$message = self::__('Thank you for registering Sprout Invoices with Sprout Apps.');
			$response = array(
					'license' => $license_response->license_key,
					'uid' => $license_response->uid,
					'response' => $message,
					'error' => !isset( $license_response->license_key )
				);
			
			update_option( self::LICENSE_KEY_OPTION, $license_response->license_key );
			update_option( self::LICENSE_UID_OPTION, $license_response->uid );
		}
		else {
			$message = self::__('License not created.') ;
			$response = array(
					'response' => $message,
					'error' => 1
				);
		}

		header( 'Content-type: application/json' );
		echo json_encode( $response );
		exit();
	}

	public static function thank_for_registering() {
		if ( !self::$uid ) {
			return;
		}
	}


	//////////////
	// Utility //
	//////////////


	public static function get_free_license( $license = '' ) {
		$first_name = '';
		$last_name = '';
		$user = get_user_by( 'email', $license );
		if ( is_a( $user, 'WP_User' ) ) {
			$first_name = $user->first_name;
			$last_name = $user->last_name;
		}

		// data to send in our API request
		$api_params = array( 
			'action' => 'sgmnt_free_license',
			'item_name' => urlencode( self::PLUGIN_NAME ),
			'url' => home_url(),
			'uid' => $license,
			'first_name'=> $first_name,
			'last_name' => $last_name,
		);

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, self::API_CB . 'wp-admin/admin-ajax.php' ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_response = json_decode( wp_remote_retrieve_body( $response ) );

		return $license_response;
	}

	public static function add_uid_to_url( $url = '' ) {
		if ( !self::$uid ) {
			return esc_url( $url );
		}
		return esc_url( add_query_arg( array( 'uid' => self::$uid ), $url ) );
	}
	
}