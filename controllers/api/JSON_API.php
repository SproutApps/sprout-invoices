<?php

/**
* Basic JSON implementation at the moment for reporting.
 *
 * @package Sprout_Invoice
 * @subpackage API
 */
class SI_JSON_API extends SI_Controller {
	const API_QUERY_VAR = 'si_json_api';
	const AUTH_NONCE = 'auth';
	const CACHE_KEY_PREFIX = 'si_report_cache_';
	const CACHE_TIMEOUT = 172800; // 48 hours
	const TIMEOUT = 120;
	const MAX_ITEMS_IN_FEED = 50;

	public static function init() {
		// FUTURE Rewrite rules each endpoint. WP-Router?
		self::register_query_var( self::API_QUERY_VAR, array( __CLASS__, 'api_endpoints' ) );

		// FUTURE AJAX callback to reset cache and return fresh data.
	}

	/**
	 * Technically not an endpoint until we start using permalinks but it will work.
	 * 
	 * @param  string $endpoint 
	 * @return            
	 */
	public static function get_url( $endpoint = 'payments' ) {
		return add_query_arg( array( self::API_QUERY_VAR => $endpoint ), home_url() );
	}

	/**
	 * Set callback to endpoint
	 * 
	 * @return 
	 */
	public static function api_endpoints() {
		if ( isset( $_REQUEST[self::API_QUERY_VAR] ) && $_REQUEST[self::API_QUERY_VAR] != '' ) {
			switch ( $_REQUEST[self::API_QUERY_VAR] ) {
				case 'payments':
				case 'payment':
					self::payment_endpoint();
					break;
				
				default:
					self::ajax_fail( 'Not a valid endpoint.' );
					break;
			}
		}
	}

	/**
	 * Return a data set based on a set of paramaters.
	 *
	 * start
	 * end
	 * per_month
	 * per_week
	 * per_day
	 * payment_method		
	 * 	
	 * @return string json array.
	 */
	public static function payment_endpoint() {
		$data = array(
				rand( 2, 55 ), 
				rand( 2, 55 ),
				rand( 2, 55 ),
				rand( 2, 55 ),
				rand( 2, 55 ),
				rand( 2, 55 )
			);

		header( 'Content-type: application/json' );
		echo json_encode( $data );
		exit();
	}



	////////////////////////////
	// Authentication Methods //
	////////////////////////////

	/**
	 * Basic authentication since SI will only support json requests from
	 * users that are already logged in.
	 *
	 * If the user isn't logged in than a nonce isn't returned and the authenticate_request will 
	 * fail, unless it's using the more advanced token based authentication.
	 * 
	 * @return string
	 */
	public static function basic_authentication_nonce() {
		$ajax_nonce = '';
		if ( is_user_logged_in() ) {
			$ajax_nonce = wp_create_nonce( get_current_user_id() );	
		}
		return $ajax_nonce;
	}

	/**
	 * Login the user and return a authentication token.
	 * 
	 * @return string/error 
	 */
	public static function api_get_token() {
		if ( !isset( $_REQUEST['user'] ) || !isset( $_REQUEST['pwd'] ) ) {
			status_header( 401 );
			exit();
		}
		$user = wp_signon( array(
				'user_login' => $_REQUEST['user'],
				'user_password' => $_REQUEST['pwd'],
				'remember' => FALSE,
			) );
		if ( !$user || is_wp_error( $user ) ) {
			status_header( 401 );
			exit();
		}
		$token = self::get_user_token( $user );
		if ( self::DEBUG ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $token );
		exit();
	}

	/**
	 * Verify that the current request is valid and authenticated.
	 * Check to see if the auth-nonce is being used before falling back
	 * to the more advanced token based authentication.
	 *
	 * @param bool    $die If TRUE, execution will stop on failure
	 * @return int|bool The authenticated user's ID, or FALSE on failure
	 */
	protected static function authenticate_request( $die = TRUE ) {
		if ( isset( $_REQUEST[self::AUTH_NONCE] ) ) {
			check_ajax_referer( $_REQUEST[self::AUTH_NONCE], self::AUTH_NONCE );
			return TRUE;
		}

		$user_id = FALSE;
		if ( !empty( $_REQUEST['user'] ) && !empty( $_REQUEST['signature'] ) && !empty( $_REQUEST['timestamp'] ) ) {
			$user = self::get_user();
			if ( ( time() - $_REQUEST['timestamp'] < self::TIMEOUT ) && $user ) {
				$token = self::get_user_token( $user );

				$hash = $_SERVER['REQUEST_URI'];
				$request = $_REQUEST;
				unset( $request['signature'] );
				ksort( $request );
				if ( $request ) {
					$hash .= '?'.http_build_query( $request, '', '&' );
				}
				$hash .= $token;
				$hash .= self::$private_key;
				$hash = hash( 'sha256', $hash );
				if ( $hash == $_REQUEST['signature'] ) {
					$user_id = $user->ID;
				}
			}
		}
		$user_id = apply_filters( 'si_api_authenticate_request_user_id', $user_id, $_REQUEST, $user );
		if ( $die && !$user_id ) {
			status_header( 401 );
			if ( self::DEBUG ) header( 'Access-Control-Allow-Origin: *' );
			die( -1 );
		}
		return $user_id;
	}

	/**
	 * Get (and create if necessary) an API token for the user
	 *
	 * @param WP_User|int $user
	 * @return string
	 */
	private static function get_user_token( $user = 0 ) {
		$user = $user ? $user : wp_get_current_user();
		if ( !is_object( $user ) ) {
			$user = new WP_User( $user );
		}
		if ( !$user->ID ) {
			return FALSE;
		}
		$stored = get_user_option( 'si_api_token', $user->ID );
		if ( $stored ) {
			return $stored;
		}

		$now = time();
		$token = md5( serialize( $user ).$now );
		update_user_option( $user->ID, 'si_api_token', $token );
		update_user_option( $user->ID, 'si_api_token_timestamp', $now );
		return $token;
	}

	/**
	 * Delete a user's stored token
	 *
	 * @param int     $user_id
	 */
	private static function revoke_user_token( $user_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		delete_user_option( $user_id, 'si_api_token' );
	}

	public function get_user() {
		if ( !isset( $_REQUEST['user'] ) )
			return;

		return get_user_by( 'login', $_REQUEST['user'] );
	}

	public function get_user_id() {
		$user = self::get_user();
		return $user->ID;
	}

	//////////////////////
	// Testing Methods //
	//////////////////////

}