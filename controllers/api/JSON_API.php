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

	public static function init() {
		self::register_query_var( self::API_QUERY_VAR, array( __CLASS__, 'api_callback' ) );
	}

	/**
	 * Technically not an endpoint until we start using permalinks but it will work.
	 *
	 * @param  string $endpoint
	 * @return
	 */
	public static function get_url( $endpoint = 'payments' ) {
		return esc_url_raw( add_query_arg( array( self::API_QUERY_VAR => $endpoint ), home_url() ) );
	}

	/**
	 * Set callback to endpoint
	 *
	 * @return
	 */
	public static function api_callback() {
		if ( isset( $_REQUEST[ self::API_QUERY_VAR ] ) && '' !== $_REQUEST[ self::API_QUERY_VAR ] ) {

			$data = $_REQUEST[ self::API_QUERY_VAR ];

			if ( strpos( $data, 'create-' ) !== false ) {
				self::authenticate_request();
			}

			$data = json_decode( file_get_contents( 'php://input' ) );

			switch ( $data ) {

				case 'get_token':
					self::api_get_token();
					break;
				case 'ping':
					$response = self::ping( $data );
					break;
				case 'invoice':
					$response = self::invoice( $data );
					break;
				case 'estimate':
					$response = self::estimate( $data );
					break;
				case 'payment':
					$response = self::payment( $data );
					break;
				case 'client':
					$response = self::client( $data );
					break;
				case 'create_invoice':
					$response = self::create_invoice( $data );
					break;
				case 'create_estimate':
					$response = self::create_estimate( $data );
					break;
				case 'create_payment':
					$response = self::create_payment( $data );
					break;
				case 'create_client':
					$response = self::create_client( $data );
					break;

				default:
					self::fail( 'Not a valid endpoint.' );
					break;

			}

			@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
			@header( 'Expires: '. gmdate( 'D, d M Y H:i:s', mktime( date( 'H' ) + 2, date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) ) .' GMT' );
			@header( 'Last-Modified: '. gmdate( 'D, d M Y H:i:s' ) .' GMT' );
			@header( 'Cache-Control: no-cache, must-revalidate' );
			@header( 'Pragma: no-cache' );

			wp_send_json_success( $data );
		}
	}

	///////////////
	// Endpoints //
	///////////////

	/**
	 * Ping
	 *
	 */
	private static function ping() {
		return array(
			'status' => 'verified'
		);
	}

	public static function create_invoice( $data = array() ) {
		$invoice_id = SI_Invoice::create_invoice( $data );
		return self::invoice_data( $invoice_id );
	}

	public static function create_estimate( $data = array() ) {
		$estimate_id = SI_Estimate::create_estimate( $data );
		return self::estimate_data( $estimate_id );
	}

	public static function create_payment( $data = array() ) {
		$payment_id = SI_Payment::new_payment( $data );
		return self::payment_data( $payment_id );
	}

	public static function create_client( $data = array() ) {
		$client_id = SI_Client::new_client( $data );
		return self::client_data( $client_id );
	}

	public static function invoice( $data = array() ) {
		if ( ! isset( $data['id'] ) ) {
			$data['id'] = $_GET['id'];
		}
		$invoice = SI_Invoice::get_instance( $data['id'] );
		if ( ! is_a( $invoice, 'SI_Invoice' ) ) {
			return;
		}
		return self::invoice_data( $invoice );
	}

	public static function estimate( $data = array() ) {
		if ( ! isset( $data['id'] ) ) {
			$data['id'] = $_GET['id'];
		}
		$estimate = SI_Estimate::get_instance( $data['id'] );
		if ( ! is_a( $estimate, 'SI_Estimate' ) ) {
			return;
		}
		return self::estimate_data( $estimate );
	}

	public static function payment( $data = array() ) {
		if ( ! isset( $data['id'] ) ) {
			$data['id'] = $_GET['id'];
		}
		$payment = SI_Payment::get_instance( $data['id'] );
		if ( ! is_a( $payment, 'SI_Payment' ) ) {
			return;
		}
		return self::payment_data( $payment );
	}

	public static function client( $data = array() ) {
		if ( ! isset( $data['id'] ) ) {
			$data['id'] = $_GET['id'];
		}
		$client = SI_Client::get_instance( $data['id'] );
		if ( ! is_a( $client, 'SI_Client' ) ) {
			return;
		}
		return self::client_data( $client );
	}

	///////////
	// Data //
	///////////

	public static function estimate_data( SI_Estimate $estimate ) {
		$estimate_data = array(
			'title' => $estimate->get_title(),
			'id' => $estimate->get_id(),
			'estimate_id' => $estimate->get_estimate_id(),
			'invoice_id' => $estimate->get_invoice_id(),
			'client_id' => $estimate->get_client_id(),
			'client_data' => array(),
			'status' => $estimate->get_status(),
			'issue_date' => $estimate->get_issue_date(),
			'expiration_date' => $estimate->get_expiration_date(),
			'po_number' => $estimate->get_po_number(),
			'discount' => $estimate->get_discount(),
			'tax' => $estimate->get_tax(),
			'tax2' => $estimate->get_tax2(),
			'currency' => $estimate->get_currency(),
			'total' => $estimate->get_total(),
			'subtotal' => $estimate->get_subtotal(),
			'calculated_total' => $estimate->get_calculated_total(),
			'project_id' => $estimate->get_project_id(),
			'terms' => $estimate->get_terms(),
			'notes' => $estimate->get_notes(),
			'line_items' => $estimate->get_line_items(),
			'user_id' => $estimate->get_user_id(),
			);
		if ( $estimate->get_client_id() ) {
			$client = SI_Client::get_instance( $estimate->get_client_id() );
			if ( is_a( $client, 'SI_Client' ) ) {
				$estimate_data['client_data'] = self::client_data( $client );
			}
		}
		return $estimate_data;
	}

	public static function invoice_data( SI_Invoice $invoice ) {
		$invoice_data = array(
			'title' => $invoice->get_title(),
			'id' => $invoice->get_id(),
			'invoice_id' => $invoice->get_invoice_id(),
			'status' => $invoice->get_status(),
			'balance' => $invoice->get_balance(),
			'deposit' => $invoice->get_deposit(),
			'issue_date' => $invoice->get_issue_date(),
			'estimate_id' => $invoice->get_estimate_id(),
			'due_date' => $invoice->get_due_date(),
			'expiration_date' => $invoice->get_expiration_date(),
			'client_id' => $invoice->get_client_id(),
			'client_data' => array(),
			'po_number' => $invoice->get_po_number(),
			'discount' => $invoice->get_discount(),
			'tax' => $invoice->get_tax(),
			'tax2' => $invoice->get_tax2(),
			'currency' => $invoice->get_currency(),
			'subtotal' => $invoice->get_subtotal(),
			'calculated_total' => $invoice->get_calculated_total(),
			'project_id' => $invoice->get_project_id(),
			'terms' => $invoice->get_terms(),
			'notes' => $invoice->get_notes(),
			'line_items' => $invoice->get_line_items(),
			'user_id' => $invoice->get_user_id(),
			'payment_ids' => $invoice->get_payments(),
			);
		if ( $invoice->get_client_id() ) {
			$client = SI_Client::get_instance( $invoice->get_client_id() );
			if ( is_a( $client, 'SI_Client' ) ) {
				$invoice_data['client_data'] = self::client_data( $client );
			}
		}
		return $invoice_data;
	}

	public static function payment_data( SI_Payment $payment ) {
		$payment_data = array(
			'title' => $payment->get_title(),
			'id' => $payment->get_id(),
			'status' => $payment->get_status(),
			'payment_method' => $payment->get_payment_method(),
			'amount' => $payment->get_amount(),
			'invoice_id' => $payment->get_invoice_id(),
			'data' => $payment->get_data(),
			);
		$invoice = SI_Invoice::get_instance( $payment->get_invoice_id() );
		if ( is_a( $invoice, 'SI_Invoice' ) ) {
			$payment_data['invoice_data'] = self::invoice_data( $invoice );
		}
		return $payment_data;
	}

	public static function client_data( SI_Client $client ) {
		$emails = array();
		$associated_users = $client->get_associated_users();
		if ( ! empty( $associated_users ) ) {
			foreach ( $associated_users as $user_id ) {
				$user = get_userdata( $user_id );
				if ( $user ) {
					$emails[] = $user->user_email;
				}
			}
		}
		$client_data = array(
			'company_name' => $client->get_title(),
			'address' => $client->get_address(),
			'user_ids' => $associated_users,
			'user_emails' => $emails,
			'phone' => $client->get_phone(),
			'website' => $client->get_website(),
			'estimate_ids' => $client->get_invoices(),
			'invoice_ids' => $client->get_estimates(),
			'payment_ids' => $client->get_payments(),
			);
		return $client_data;
	}

	public static function project_data( SI_Project $project ) {
		$project_data = array(

			);
		return $project_data;
	}



	////////////////////////////
	// Authentication Methods //
	////////////////////////////

	/**
	 * Login the user and return a authentication token.
	 *
	 * @return string/error
	 */
	public static function api_get_token() {
		if ( ! isset( $_REQUEST['user'] ) || ! isset( $_REQUEST['pwd'] ) ) {
			status_header( 401 );
			exit();
		}
		$user = wp_signon( array(
				'user_login' => $_REQUEST['user'],
				'user_password' => $_REQUEST['pwd'],
				'remember' => false,
			) );
		if ( ! $user || is_wp_error( $user ) ) {
			status_header( 401 );
			exit();
		}
		$token = self::get_user_token( $user );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode( $token );
		exit();
	}

	/**
	 * Verify that the current request is valid and authenticated.
	 * Check to see if the auth-nonce is being used before falling back
	 * to the more advanced token based authentication.
	 *
	 * @param bool    $die If true, execution will stop on failure
	 * @return int|bool The authenticated user's ID, or false on failure
	 */
	protected static function authenticate_request( $die = true ) {
		if ( isset( $_REQUEST[ self::AUTH_NONCE ] ) ) {
			check_ajax_referer( $_REQUEST[ self::AUTH_NONCE ], self::AUTH_NONCE );
			return true;
		}

		$user = '';
		$user_id = false;
		if ( ! empty( $_REQUEST['user'] ) && ! empty( $_REQUEST['signature'] ) && ! empty( $_REQUEST['timestamp'] ) ) {
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
				if ( $hash === $_REQUEST['signature'] ) {
					$user_id = $user->ID;
				}
			}
		}
		$user_id = apply_filters( 'si_api_authenticate_request_user_id', $user_id, $_REQUEST, $user );
		if ( $die && ! $user_id ) {
			status_header( 401 );
			if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
			die( -1 );
		}
		return $user_id;
	}

	/**
	 * Get (and create if necessary) an API token for the user
	 *
	 * @param WP_User|int $user
	 * @return bool/string
	 */
	private static function get_user_token( $user = 0 ) {
		$user = $user ? $user : wp_get_current_user();
		if ( ! is_object( $user ) ) {
			$user = new WP_User( $user );
		}
		if ( ! $user->ID ) {
			return false;
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
		if ( ! isset( $_REQUEST['user'] ) ) {
			return; }

		return get_user_by( 'login', $_REQUEST['user'] );
	}

	public function get_user_id() {
		$user = self::get_user();
		return $user->ID;
	}

	/////////////
	// Utility //
	/////////////

	/**
	 * Failed
	 * @param  string $message
	 * @return json
	 */
	public static function fail( $message = '' ) {
		if ( $message == '' ) {
			$message = self::__( 'Something failed.' );
		}
		wp_send_json_error( $message );
	}
}