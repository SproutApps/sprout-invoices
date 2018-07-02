<?php

/**
 * Freshbooks Importer
 *
 * @package Sprout_Invoice
 * @subpackage Importers
 */
class SI_Freshbooks_Import extends SI_Importer {
	const SETTINGS_PAGE = 'import';
	const IMPORTER_ID = 'freshbooks';
	const FRESHBOOKS_TOKEN_OPTION = 'si_freshbooks_token_option';
	const FRESHBOOKS_ACCOUNT_OPTION = 'si_freshbooks_domain_option';
	const PROCESS_ARCHIVED = 'import_archived';
	const PAYMENT_METHOD = 'Freshbooks Imported';
	const DELETE_PROGRESS = 'remove_progress_option';
	const PROGRESS_OPTION = 'current_import_progress_freshbooks';

	// Meta
	const FRESHBOOKS_ID = '_freshbooks_id';

	private static $freshbooks_token;
	private static $freshbooks_account;
	private static $importing_archived;
	private static $start_progress_over;

	public static function init() {
		// Settings
		self::$freshbooks_token = get_option( self::FRESHBOOKS_TOKEN_OPTION, '' );
		self::$freshbooks_account = self::sanitize_subdomain( get_option( self::FRESHBOOKS_ACCOUNT_OPTION, '' ) );

		self::save_options();
	}

	public static function register() {
		self::add_importer( __CLASS__, __( 'Freshbooks', 'sprout-invoices' ) );
	}

	public static function get_id() {
		return self::IMPORTER_ID;
	}


	/**
	 * Register the payment settings
	 * @return
	 */
	public static function get_options( $settings = array() ) {
		// Settings
		$settings = array(
			'si_freshbooks_importer_settings' => array(
				'title' => __( 'Freshbooks Import Settings', 'sprout-invoices' ),
				'description' => __( 'Use your Freshbooks API credentials to import your records for Sprout Invoices.', 'sprout-invoices' ),
				'weight' => 0,
				'settings' => array(
					self::FRESHBOOKS_TOKEN_OPTION => array(
						'label' => __( 'Token', 'sprout-invoices' ),
						'option' => array(
							'type' => 'text',
							'default' => get_option( self::FRESHBOOKS_TOKEN_OPTION, '' ),
							'attributes' => array(
		'placeholder' => __(
		'6c4384e426e4b560d1227f4ad0f88b2c', 'sprout-invoices' ),
							),
							'description' => __( 'Get your token form My Account > Freshbooks API ', 'sprout-invoices' ),
						),
					),
					self::FRESHBOOKS_ACCOUNT_OPTION => array(
						'label' => __( 'Account/Sub-domain', 'sprout-invoices' ),
						'option' => array(
							'type' => 'text',
							'default' => self::sanitize_subdomain( get_option( self::FRESHBOOKS_ACCOUNT_OPTION, '' ) ),
							'attributes' => array(
					'placeholder' => __(
					'your-subdomain', 'sprout-invoices' ),
							),
							'description' => __( 'https://[subdomain].freshbooks.com', 'sprout-invoices' ),
							'sanitize_callback' => array( __CLASS__, 'sanitize_subdomain' ),
						),
					),
					self::PROCESS_ARCHIVED => array(
						'label' => __( 'Import Archived', 'sprout-invoices' ),
						'option' => array(
							'type' => 'checkbox',
							'value' => 'archived',
							'label' => __( 'Import archived clients.', 'sprout-invoices' ),
							'description' => '',
						),
					),
					self::DELETE_PROGRESS => array(
						'label' => __( 'Clear Progress', 'sprout-invoices' ),
						'option' => array(
							'type' => 'checkbox',
							'value' => 'restart',
							'label' => __( 'Re-start the Import Process', 'sprout-invoices' ),
							'description' => __( 'This will start the import process from the start. Any records already imported will not be duplicated but any new records will.', 'sprout-invoices' ),
						),
					),
					self::PROCESS_ACTION => array(
						'option' => array(
							'type' => 'hidden',
							'value' => wp_create_nonce( self::PROCESS_ACTION ),
						),
					),
				),
			),
		);
		return $settings;
	}

	public static function save_options() {
		if ( ! current_user_can( 'manage_sprout_invoices_importer' ) ) {
			error_log( 'failed user can' . print_r( false, true ) );
			return;
		}

		if ( isset( $_POST[ self::FRESHBOOKS_TOKEN_OPTION ] ) && $_POST[ self::FRESHBOOKS_TOKEN_OPTION ] != '' ) {
			self::$freshbooks_token = $_POST[ self::FRESHBOOKS_TOKEN_OPTION ];
			update_option( self::FRESHBOOKS_TOKEN_OPTION, $_POST[ self::FRESHBOOKS_TOKEN_OPTION ] );
		}
		if ( isset( $_POST[ self::FRESHBOOKS_ACCOUNT_OPTION ] ) && $_POST[ self::FRESHBOOKS_ACCOUNT_OPTION ] != '' ) {
			self::$freshbooks_account = $_POST[ self::FRESHBOOKS_ACCOUNT_OPTION ];
			update_option( self::FRESHBOOKS_ACCOUNT_OPTION, $_POST[ self::FRESHBOOKS_ACCOUNT_OPTION ] );
		}

		// Clear out progress
		if ( isset( $_POST[ self::DELETE_PROGRESS ] ) && $_POST[ self::DELETE_PROGRESS ] == 'restart' ) {
			delete_option( self::PROGRESS_OPTION );
		}
	}

	/**
	 * Import archived data
	 * @return bool
	 */
	public static function import_archived_data() {
		self::$importing_archived = ( isset( $_POST[ self::PROCESS_ARCHIVED ] ) && $_POST[ self::PROCESS_ARCHIVED ] == 'archived' ) ? true : false ;
		return self::$importing_archived;
	}

	/**
	 * Utility to return a JSON error
	 * @param  string $message
	 * @return json
	 */
	public static function return_error( $message ) {
		header( 'Content-type: application/json' );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode(
			array( 'error' => true, 'message' => $message )
		);
		exit();
	}

	/**
	 * Return the progress array
	 * @param  array  $array associated array with method and status message
	 * @return json
	 */
	public static function return_progress( $array = array() ) {
		header( 'Content-type: application/json' );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode( $array );
		exit();
	}

	/**
	 * First step in the import progress
	 * @return
	 */
	public static function import_authentication() {
		require_once SI_PATH . '/importers/lib/freshbooks/FreshBooksRequest.php';
		FreshBooksRequest::init( self::$freshbooks_account, self::$freshbooks_token );

		// Initial API callback to get the client list
		$fb = new FreshBooksRequest( 'client.list' );
		$fb->post( array( 'per_page' => 5 ) );
		$fb->request();
		if ( ! $fb->success() ) {
			$error = ( $fb->getError() == 'System does not exist.' ) ? __( 'Authentication error.', 'sprout-invoices' ) : $fb->getError();
			self::return_error( $error );
		}
		self::return_progress( array(
			'authentication' => array(
			'message' => __( 'Communicating with the Freshbooks API...', 'sprout-invoices' ),
			'progress' => 99.9,
			'next_step' => 'clients',
			),
		) );
	}

	/**
	 * Second step is to import clients and contacts
	 * @return
	 */
	public static function import_clients() {

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		$total_records = 0;
		if ( ! isset( $progress['clients_complete'] ) ) {

			require_once SI_PATH . '/importers/lib/freshbooks/FreshBooksRequest.php';
			FreshBooksRequest::init( self::$freshbooks_account, self::$freshbooks_token );

			$progress_key = 'client_import_progress';
			if ( ! isset( $progress[ $progress_key ] ) ) {
				$progress[ $progress_key ] = 1;
				update_option( self::PROGRESS_OPTION, $progress );
			}

			// Start importing the clients 10 at a time
			$fb = new FreshBooksRequest( 'client.list' );
			$fb->post( array( 'page' => $progress[ $progress_key ], 'per_page' => 20 ) );
			$fb->request();

			if ( ! $fb->success() ) {
				$error = ( $fb->getError() == 'System does not exist.' ) ? __( 'Authentication error.', 'sprout-invoices' ) : $fb->getError();
				self::return_error( $error );
			}

			$response = $fb->getResponse();
			$pages = $response['clients']['@attributes']['pages'];
			$total_records = $response['clients']['@attributes']['total'];
			$total_imported = ($total_records / $pages) * $progress[ $progress_key ];

			if ( $progress[ $progress_key ] <= $pages ) {
				// If there's a single contact than it's not an array of clients...lame.
				if ( ! isset( $response['clients']['client'][0] ) ) {
					$client = $response['clients']['client'];
					$new_client_id = self::create_client( $client );
					self::create_contacts( $client, $new_client_id );
				} else {
					foreach ( $response['clients']['client'] as $key => $client ) {
						$new_client_id = self::create_client( $client );
						self::create_contacts( $client, $new_client_id );
					}
				}

				$progress[ $progress_key ]++;
				update_option( self::PROGRESS_OPTION, $progress );

				// Return the progress
				self::return_progress( array(
					'authentication' => array(
					'message' => sprintf( __( 'Attempting to import %s contacts and their clients...', 'sprout-invoices' ), $total_records ),
					'progress' => 20 + $progress[ $progress_key ],
					),
					'clients' => array(
					'message' => sprintf( __( 'Imported about %s clients so far.', 'sprout-invoices' ), $total_imported ),
					'progress' => intval( ($progress[ $progress_key ] / $pages) * 100 ),
					),
					'contacts' => array(
					'message' => sprintf( __( 'Imported more than %s contacts from imported clients.', 'sprout-invoices' ), $total_imported ),
					'progress' => intval( ($progress[ $progress_key ] / $pages) * 100 ),
					'next_step' => 'clients',
					),
				) );
			}

			// Mark as complete
			$progress['clients_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );

			// Complete
			self::return_progress( array(
				'authentication' => array(
				'message' => sprintf( __( 'Successfully imported %s contacts and their clients...', 'sprout-invoices' ), $total_records ),
				'progress' => 25,
				),
				'clients' => array(
				'message' => sprintf( __( 'Imported more than %s clients!', 'sprout-invoices' ), $total_imported ),
				'progress' => 100,
				),
				'contacts' => array(
				'message' => sprintf( __( 'More than %s contacts were added and assigned to their clients!', 'sprout-invoices' ), $total_imported ),
				'progress' => 100,
				'next_step' => 'estimates',
				),
			) );
		}

		// Completed previously
		self::return_progress( array(
			'authentication' => array(
			'message' => sprintf( __( 'Successfully imported %s contacts and their clients already, moving on...', 'sprout-invoices' ), $total_records ),
			'progress' => 25,
			),
			'clients' => array(
			'message' => sprintf( __( 'Successfully imported %s clients already.', 'sprout-invoices' ), $total_records ),
			'progress' => 100,
			),
			'contacts' => array(
			'message' => sprintf( __( 'Successfully imported more than %s contacts from their clients already.', 'sprout-invoices' ), $total_records ),
			'progress' => 100,
			'next_step' => 'estimates',
			),
		) );

		// If this is needed something went wrong since json should have been printed and exited.
		return;

	}

	/**
	 * Third step is to import estimates
	 * @return
	 */
	public static function import_estimates() {

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		$total_records = 0;
		if ( ! isset( $progress['estimates_complete'] ) ) {

			require_once SI_PATH . '/importers/lib/freshbooks/FreshBooksRequest.php';
			FreshBooksRequest::init( self::$freshbooks_account, self::$freshbooks_token );

			$progress_key = 'estimate_import_progress';
			if ( ! isset( $progress[ $progress_key ] ) ) {
				$progress[ $progress_key ] = 1;
				update_option( self::PROGRESS_OPTION, $progress );
			}

			// Start importing the clients 10 at a time
			$fb = new FreshBooksRequest( 'estimate.list' );
			$fb->post( array( 'page' => $progress[ $progress_key ], 'per_page' => 10 ) );
			$fb->request();

			if ( ! $fb->success() ) {
				$error = ( $fb->getError() == 'System does not exist.' ) ? __( 'Authentication error.', 'sprout-invoices' ) : $fb->getError();
				self::return_error( $error );
			}

			$response = $fb->getResponse();

			$pages = $response['estimates']['@attributes']['pages'];
			$total_records = $response['estimates']['@attributes']['total'];
			$total_imported = $pages * $progress[ $progress_key ];

			if ( $progress[ $progress_key ] <= $pages ) {

				if ( isset( $response['estimates']['estimate'][0] ) ) {
					foreach ( $response['estimates']['estimate'] as $key => $estimate ) {
						self::create_estimate( $estimate );
					}
				} else {
					self::create_estimate( $response['estimates']['estimate'] );
				}

				$progress[ $progress_key ]++;
				update_option( self::PROGRESS_OPTION, $progress );

				// Return the progress
				self::return_progress( array(
					'authentication' => array(
					'message' => sprintf( __( 'Attempting to import %s estimates...', 'sprout-invoices' ), $total_records ),
					'progress' => 25 + $progress[ $progress_key ],
					),
					'estimates' => array(
					'message' => sprintf( __( 'Imported about %s estimates so far.', 'sprout-invoices' ), $total_imported ),
					'progress' => intval( ($progress[ $progress_key ] / $pages) * 100 ),
					'next_step' => 'estimates',
					),
				) );
			}

			// Mark as complete
			$progress['estimates_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );

			// Complete
			self::return_progress( array(
				'authentication' => array(
				'message' => sprintf( __( 'Successfully imported %s estimates...', 'sprout-invoices' ), $total_records ),
				'progress' => 50,
				),
				'estimates' => array(
				'message' => sprintf( __( 'Imported %s estimates!', 'sprout-invoices' ), $total_records ),
				'progress' => 100,
				'next_step' => 'invoices',
				),
			) );
		}

		// Completed previously
		self::return_progress( array(
			'authentication' => array(
			'message' => sprintf( __( 'Successfully imported %s estimates already, moving on...', 'sprout-invoices' ), $total_records ),
			'progress' => 50,
			),
			'estimates' => array(
			'progress' => 100,
			'message' => sprintf( __( 'Successfully imported %s estimates already.', 'sprout-invoices' ), $total_records ),
			'next_step' => 'invoices',
			),
		) );

		// If this is needed something went wrong since json should have been printed and exited.
		return;

	}

	/**
	 * Fourth step is to import invoices
	 * @return
	 */
	public static function import_invoices() {

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		$total_records = 0;
		if ( ! isset( $progress['invoices_complete'] ) ) {

			require_once SI_PATH . '/importers/lib/freshbooks/FreshBooksRequest.php';
			FreshBooksRequest::init( self::$freshbooks_account, self::$freshbooks_token );

			$progress_key = 'invoices_import_progress';
			if ( ! isset( $progress[ $progress_key ] ) ) {
				$progress[ $progress_key ] = 1;
				update_option( self::PROGRESS_OPTION, $progress );
			}

			// Start importing the clients 10 at a time
			$fb = new FreshBooksRequest( 'invoice.list' );
			$fb->post( array( 'page' => $progress[ $progress_key ], 'per_page' => 10 ) );
			$fb->request();

			if ( ! $fb->success() ) {
				$error = ( $fb->getError() == 'System does not exist.' ) ? __( 'Authentication error.', 'sprout-invoices' ) : $fb->getError();
				self::return_error( $error );
			}

			$response = $fb->getResponse();

			$pages = $response['invoices']['@attributes']['pages'];
			$total_records = $response['invoices']['@attributes']['total'];
			$total_imported = $pages * $progress[ $progress_key ];

			if ( $progress[ $progress_key ] <= $pages ) {

				if ( isset( $response['invoices']['invoice'][0] ) ) {
					foreach ( $response['invoices']['invoice'] as $key => $invoice ) {
						self::create_invoice( $invoice );
					}
				} else {
					self::create_invoice( $response['invoices']['invoice'] );
				}

				$progress[ $progress_key ]++;
				update_option( self::PROGRESS_OPTION, $progress );

				// Return the progress
				self::return_progress( array(
					'authentication' => array(
					'message' => sprintf( __( 'Attempting to import %s invoices...', 'sprout-invoices' ), $total_records ),
					'progress' => 50 + $progress[ $progress_key ],
					),
					'invoices' => array(
					'message' => sprintf( __( 'Imported about %s invoices so far.', 'sprout-invoices' ), $total_imported ),
					'progress' => intval( ($progress[ $progress_key ] / $pages) * 100 ),
					'next_step' => 'invoices',
					),
				) );
			}

			// Mark as complete
			$progress['invoices_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );

			// Complete
			self::return_progress( array(
				'authentication' => array(
				'message' => sprintf( __( 'Successfully imported %s invoices...', 'sprout-invoices' ), $total_records ),
				'progress' => 75,
				),
				'invoices' => array(
				'message' => sprintf( __( 'Imported %s invoices!', 'sprout-invoices' ), $total_records ),
				'progress' => 100,
				'next_step' => 'payments',
				),
			) );
		}

		// Completed previously
		self::return_progress( array(
			'authentication' => array(
			'message' => sprintf( __( 'Successfully imported %s invoices already, moving on...', 'sprout-invoices' ), $total_records ),
			'progress' => 75,
			),
			'invoices' => array(
			'message' => sprintf( __( 'Successfully imported %s invoices already.', 'sprout-invoices' ), $total_records ),
			'progress' => 100,
			'next_step' => 'payments',
			),
		) );

		// If this is needed something went wrong since json should have been printed and exited.
		return;

	}

	/**
	 * Final step is to import payments
	 * @return
	 */
	public static function import_payments() {

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		$total_records = 0;
		if ( ! isset( $progress['payments_complete'] ) ) {

			require_once SI_PATH . '/importers/lib/freshbooks/FreshBooksRequest.php';
			FreshBooksRequest::init( self::$freshbooks_account, self::$freshbooks_token );

			$progress_key = 'payments_import_progress';
			if ( ! isset( $progress[ $progress_key ] ) ) {
				$progress[ $progress_key ] = 1;
				update_option( self::PROGRESS_OPTION, $progress );
			}

			// Start importing the clients 10 at a time
			$fb = new FreshBooksRequest( 'payment.list' );
			$fb->post( array( 'page' => $progress[ $progress_key ], 'per_page' => 10 ) );
			$fb->request();

			if ( ! $fb->success() ) {
				$error = ( $fb->getError() == 'System does not exist.' ) ? __( 'Authentication error.', 'sprout-invoices' ) : $fb->getError();
				self::return_error( $error );
			}

			$response = $fb->getResponse();

			$pages = $response['payments']['@attributes']['pages'];
			$total_records = $response['payments']['@attributes']['total'];
			$total_imported = $pages * $progress[ $progress_key ];

			if ( $progress[ $progress_key ] <= $pages ) {

				if ( isset( $response['payments']['payment'][0] ) ) {
					foreach ( $response['payments']['payment'] as $key => $payment ) {
						self::create_payment( $payment );
					}
				} else {
					self::create_payment( $response['payments']['payment'] );
				}

				$progress[ $progress_key ]++;
				update_option( self::PROGRESS_OPTION, $progress );

				// Return the progress
				self::return_progress( array(
					'authentication' => array(
					'message' => sprintf( __( 'Attempting to import %s payments...', 'sprout-invoices' ), $total_records ),
					'progress' => 75 + $progress[ $progress_key ],
					),
					'payments' => array(
					'message' => sprintf( __( 'Imported about %s payments so far.', 'sprout-invoices' ), $total_imported ),
					'progress' => intval( ($progress[ $progress_key ] / $pages) * 100 ),
					'next_step' => 'payments',
					),
				) );
			}

			// Mark as complete
			$progress['payments_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );

			// Complete
			self::return_progress( array(
				'authentication' => array(
				'message' => sprintf( __( 'Successfully imported %s payments...', 'sprout-invoices' ), $total_records ),
				'progress' => 100,
				),
				'payments' => array(
				'message' => sprintf( __( 'Imported %s payments!', 'sprout-invoices' ), $total_records ),
				'progress' => 100,
				'next_step' => 'complete',
				),
			) );
		}

		// Completed previously
		self::return_progress( array(
			'authentication' => array(
			'message' => sprintf( __( 'Successfully imported %s estimates already, moving on...', 'sprout-invoices' ), $total_records ),
			'progress' => 100,
			),
			'payments' => array(
			'message' => sprintf( __( 'Successfully imported %s payments already.', 'sprout-invoices' ), $total_records ),
			'progress' => 100,
			'next_step' => 'complete',
			),
		) );

		// If this is needed something went wrong since json should have been printed and exited.
		return;

	}

	//////////////
	// Utility //
	//////////////

	public static function create_client( $client = array() ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::FRESHBOOKS_ID => $client['client_id'] ) );
		// Don't create a duplicate if this was already imported.
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Client imported already', $client['client_id'] );
			return;
		}
		if ( ! self::import_archived_data() && $client['folder'] != 'active' ) {
			return;
		}
		// args to create new client
		$address = array(
			'street' => ( isset( $client['p_street1'] ) && ! is_array( $client['p_street1'] ) ) ? esc_html( $client['p_street1'] ) : '',
			'city' => ( isset( $client['p_city'] ) && ! is_array( $client['p_city'] ) ) ? esc_html( $client['p_city'] ) : '',
			'zone' => ( isset( $client['p_state'] ) && ! is_array( $client['p_state'] ) ) ? esc_html( $client['p_state'] ) : '',
			'postal_code' => ( isset( $client['p_code'] ) && ! is_array( $client['p_code'] ) ) ? esc_html( $client['p_code'] ) : '',
			'country' => ( isset( $client['p_country'] ) && ! is_array( $client['p_country'] ) ) ? esc_html( $client['p_country'] ) : '',
		);
		if ( isset( $client['p_street2'] ) && ! is_array( $client['p_street2'] ) ) {
			$address['street'] .= '/n' . esc_html( $client['p_street2'] );
		}
		$args = array(
			'address' => $address,
			'company_name' => ( isset( $client['organization'] ) && ! is_array( $client['organization'] ) ) ? $client['organization'] : '',
			'website' => ( isset( $client['website'] ) && ! is_array( $client['website'] ) ) ? $client['website'] : '',
			'currency' => ( isset( $client['currency_code'] ) && ! is_array( $client['currency_code'] ) ) ? $client['currency_code'] : '',
		);
		if ( '' === $args['company_name'] ) {
			if ( is_array( $client['first_name'] ) || is_array( $client['last_name'] ) ) {
				do_action( 'si_error', 'Client creation error', $client['client_id'] );
				return;
			}
			$args['company_name'] = $client['first_name'] . ' ' . $client['last_name'];
		}
		$client_id = SI_Client::new_client( $args );
		// notes
		if ( isset( $client['notes'] ) && ! is_array( $client['notes'] ) ) {
			SI_Internal_Records::new_record( $client['notes'], SI_Controller::PRIVATE_NOTES_TYPE, $client_id, '', 0 );
		}
		// create import record
		update_post_meta( $client_id, self::FRESHBOOKS_ID, $client['client_id'] );
		return $client_id;
	}

	public static function create_contacts( $client = array(), $client_id = 0 ) {
		$contacts_created = array();
		// The first contact is part of the master client.
		$contact_by_client = array(
			'contact_id' => ( isset( $client['contact_id'] ) && ! is_array( $client['contact_id'] ) ) ? $client['contact_id'] : '',
			'username' => ( isset( $client['username'] ) && ! is_array( $client['username'] ) ) ? $client['username'] : '',
			'email' => ( isset( $client['email'] ) && ! is_array( $client['email'] ) ) ? $client['email'] : '',
			'first_name' => ( isset( $client['first_name'] ) && ! is_array( $client['first_name'] ) ) ? $client['first_name'] : '',
			'last_name' => ( isset( $client['last_name'] ) && ! is_array( $client['last_name'] ) ) ? $client['last_name'] : '',
		);
		$contacts_created[] = self::create_contact( $contact_by_client, $client_id );

		// Any additional contacts will be part of an array.
		if ( is_array( $client['contacts']['contact'] ) ) {
			// for some reason FB API does this shit
			if ( isset( $client['contacts']['contact'][0] ) ) { // array of contacts
				foreach ( $client['contacts']['contact'] as $key => $contact ) {
					$contacts_created[] = self::create_contact( $contact, $client_id );
				}
			} else {
				$contacts_created[] = self::create_contact( $client['contacts']['contact'], $client_id );
			}
		}
		return $contacts_created;
	}

	public static function create_contact( $contact = array(), $client_id = 0 ) {
		if ( $user = get_user_by( 'email', $contact['email'] ) ) {
			do_action( 'si_error', 'Contact imported already', $contact['contact_id'] );
			return $user->ID;
		}
		// Get client and confirm it's validity
		$client = SI_Client::get_instance( $client_id );
		if ( ! is_a( $client, 'SI_Client' ) ) {
			return;
		}
		$args = array(
			'user_login' => ( ! is_array( $contact['email'] ) ) ? $contact['email'] : $contact['email'],
			'user_email' => $contact['email'],
			'first_name' => ( ! is_array( $contact['first_name'] ) ) ? $contact['first_name'] : '',
			'last_name' => ( ! is_array( $contact['last_name'] ) ) ? $contact['last_name'] : '',
		);
		$user_id = SI_Clients::create_user( $args );
		update_user_meta( $user_id, self::FRESHBOOKS_ID, $contact['contact_id'] );
		if ( isset( $contact['phone1'] ) && ! is_array( $contact['phone1'] ) ) { update_user_meta( $user_id, self::USER_META_PHONE, $contact['phone1'] ); }
		if ( isset( $contact['phone2'] ) && ! is_array( $contact['phone2'] ) ) { update_user_meta( $user_id, self::USER_META_OFFICE_PHONE, $contact['phone2'] ); }

		// Assign new user to client.
		$client->add_associated_user( $user_id );
		return $user_id;
	}

	public static function create_estimate( $estimate = array() ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Estimate::POST_TYPE, array( self::FRESHBOOKS_ID => $estimate['estimate_id'] ) );
		// Don't create a duplicate if this was already imported.
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Estimate imported already', $estimate['estimate_id'] );
			return;
		}
		$clients = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::FRESHBOOKS_ID => $estimate['client_id'] ) );
		// Get client and confirm it's validity
		$client_id = 0;
		if ( is_array( $clients ) ) {
			$client = SI_Client::get_instance( $clients[0] );
			$client_id = ( is_a( $client, 'SI_Client' ) ) ? $client->get_id() : 0 ;
		}

		$args = array(
			'subject' => ( $estimate['description'] ) ? $estimate['description'] : 'Freshbooks Import #' . $estimate['estimate_id'],
		);
		$new_estimate_id = SI_Estimate::create_estimate( $args, SI_Estimate::STATUS_PENDING );
		update_post_meta( $new_estimate_id, self::FRESHBOOKS_ID, $estimate['estimate_id'] );

		$est = SI_Estimate::get_instance( $new_estimate_id );
		$est->set_client_id( $client_id );
		if ( ! is_array( $estimate['number'] ) ) {
			$est->set_estimate_id( $estimate['number'] );
		}
		if ( ! is_array( $estimate['amount'] ) ) {
			$est->set_total( $estimate['amount'] );
		}
		if ( ! is_array( $estimate['currency_code'] ) ) {
			$est->set_currency( $estimate['currency_code'] );
		}
		if ( ! is_array( $estimate['po_number'] ) ) {
			$est->set_po_number( $estimate['po_number'] );
		}
		if ( ! is_array( $estimate['discount'] ) ) {
			$est->set_discount( $estimate['discount'] );
		}
		if ( ! is_array( $estimate['notes'] ) ) {
			$est->set_notes( $estimate['notes'] );
		}
		if ( ! is_array( $estimate['terms'] ) ) {
			$est->set_terms( $estimate['terms'] );
		}
		$est->set_issue_date( strtotime( $estimate['date'] ) );
		// post date
		$est->set_post_date( date( 'Y-m-d H:i:s', strtotime( $estimate['date'] ) ) );
		// line items
		$line_items = array();
		if ( isset( $estimate['lines']['line'] ) && ! empty( $estimate['lines']['line'] ) ) {
			// for some reason FB
			if ( isset( $estimate['lines']['line'][0] ) ) {
				foreach ( $estimate['lines']['line'] as $key => $item ) {
					$line_items[] = array(
						'rate' => ( ! is_array( $item['unit_cost'] ) ) ? $item['unit_cost'] : '',
						'qty' => ( ! is_array( $item['quantity'] ) ) ? $item['quantity'] : '',
						'desc' => ( ! is_array( $item['description'] ) ) ? $item['description'] : '',
						'type' => ( ! is_array( $item['type'] ) ) ? $item['type'] : '',
						'total' => ( ! is_array( $item['amount'] ) ) ? $item['amount'] : '',
						'tax' => ( ! is_array( $item['tax1_percent'] ) ) ? $item['tax1_percent'] : '',
						);
				}
			} else {
				$line_items[] = array(
					'rate' => ( ! is_array( $estimate['lines']['line']['unit_cost'] ) ) ? $estimate['lines']['line']['unit_cost'] : '',
					'qty' => ( ! is_array( $estimate['lines']['line']['quantity'] ) ) ? $estimate['lines']['line']['quantity'] : '',
					'desc' => ( ! is_array( $estimate['lines']['line']['description'] ) ) ? $estimate['lines']['line']['description'] : '',
					'type' => ( ! is_array( $estimate['lines']['line']['type'] ) ) ? $estimate['lines']['line']['type'] : '',
					'total' => ( ! is_array( $estimate['lines']['line']['amount'] ) ) ? $estimate['lines']['line']['amount'] : '',
					'tax' => ( ! is_array( $estimate['lines']['line']['tax1_percent'] ) ) ? $estimate['lines']['line']['tax1_percent'] : '',
					);
			}
		}
		$est->set_line_items( $line_items );

		// Record
		do_action( 'si_new_record',
			$estimate, // content
			self::RECORD, // type slug
			$new_estimate_id, // post id
			__( 'Estimate Imported', 'sprout-invoices' ), // title
			0 // user id
		);
		return $est;
	}

	public static function create_invoice( $invoice = array() ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Invoice::POST_TYPE, array( self::FRESHBOOKS_ID => $invoice['invoice_id'] ) );
		// Don't create a duplicate if this was already imported.
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Invoice imported already', $invoice['invoice_id'] );
			return;
		}
		$clients = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::FRESHBOOKS_ID => $invoice['client_id'] ) );
		// Get client and confirm it's validity
		$client = SI_Client::get_instance( $clients[0] );
		$client_id = ( is_a( $client, 'SI_Client' ) ) ? $client->get_id() : 0 ;

		$args = array(
			'subject' => ( isset( $invoice['description'] ) ) ? $invoice['description'] : 'Freshbooks Import #' . $invoice['invoice_id'],
		);
		$new_invoice_id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_PENDING );
		update_post_meta( $new_invoice_id, self::FRESHBOOKS_ID, $invoice['invoice_id'] );

		$inv = SI_Invoice::get_instance( $new_invoice_id );
		$inv->set_client_id( $client_id );
		if ( ! is_array( $invoice['number'] ) ) {
			$inv->set_invoice_id( $invoice['number'] );
		}
		if ( ! is_array( $invoice['amount'] ) ) {
			$inv->set_total( $invoice['amount'] );
		}
		if ( ! is_array( $invoice['currency_code'] ) ) {
			$inv->set_currency( $invoice['currency_code'] );
		}
		if ( ! is_array( $invoice['po_number'] ) ) {
			$inv->set_po_number( $invoice['po_number'] );
		}
		if ( ! is_array( $invoice['discount'] ) ) {
			$inv->set_discount( $invoice['discount'] );
		}
		if ( ! is_array( $invoice['notes'] ) ) {
			$inv->set_notes( $invoice['notes'] );
		}
		if ( ! is_array( $invoice['terms'] ) ) {
			$inv->set_terms( $invoice['terms'] );
		}
		$inv->set_issue_date( strtotime( $invoice['date'] ) );
		// post date
		$inv->set_post_date( date( 'Y-m-d H:i:s', strtotime( $invoice['date'] ) ) );
		// line items
		$line_items = array();
		if ( isset( $invoice['lines']['line'] ) && ! empty( $invoice['lines']['line'] ) ) {
			// for some reason FB
			if ( isset( $invoice['lines']['line'][0] ) ) {
				foreach ( $invoice['lines']['line'] as $key => $item ) {
					$line_items[] = array(
						'rate' => ( ! is_array( $item['unit_cost'] ) ) ? $item['unit_cost'] : '',
						'qty' => ( ! is_array( $item['quantity'] ) ) ? $item['quantity'] : '',
						'desc' => ( ! is_array( $item['description'] ) ) ? $item['description'] : '',
						'type' => ( ! is_array( $item['type'] ) ) ? $item['type'] : '',
						'total' => ( ! is_array( $item['amount'] ) ) ? $item['amount'] : '',
						'tax' => ( ! is_array( $item['tax1_percent'] ) ) ? $item['tax1_percent'] : '',
						);
				}
			} else {
				$line_items[] = array(
					'rate' => ( ! is_array( $invoice['lines']['line']['unit_cost'] ) ) ? $invoice['lines']['line']['unit_cost'] : '',
					'qty' => ( ! is_array( $invoice['lines']['line']['quantity'] ) ) ? $invoice['lines']['line']['quantity'] : '',
					'desc' => ( ! is_array( $invoice['lines']['line']['description'] ) ) ? $invoice['lines']['line']['description'] : '',
					'type' => ( ! is_array( $invoice['lines']['line']['type'] ) ) ? $invoice['lines']['line']['type'] : '',
					'total' => ( ! is_array( $invoice['lines']['line']['amount'] ) ) ? $invoice['lines']['line']['amount'] : '',
					'tax' => ( ! is_array( $invoice['lines']['line']['tax1_percent'] ) ) ? $invoice['lines']['line']['tax1_percent'] : '',
					);
			}
		}
		$inv->set_line_items( $line_items );

		// Record
		do_action( 'si_new_record',
			$invoice, // content
			self::RECORD, // type slug
			$new_invoice_id, // post id
			__( 'Invoice Imported', 'sprout-invoices' ), // title
			0 // user id
		);
		return $inv;
	}

	public static function create_payment( $payment ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Payment::POST_TYPE, array( self::FRESHBOOKS_ID => $payment['payment_id'] ) );
		// Don't create a duplicate if this was already imported.
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Invoice imported already', $payment['payment_id'] );
			return;
		}

		// Find the associated invoice
		$invoices = SI_Post_Type::find_by_meta( SI_Invoice::POST_TYPE, array( self::FRESHBOOKS_ID => $payment['invoice_id'] ) );
		$invoice = SI_Invoice::get_instance( $invoices[0] );
		$invoice_id = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_id() : 0 ;

		// Can't assign a payment without an invoice
		if ( ! $invoice_id ) {
			do_action( 'si_error', 'No invoice found for this payment', $payment['payment_id'] );
			return;
		}

		$payment_id = SI_Payment::new_payment( array(
			'payment_method' => ( isset( $payment['type'] ) && ! is_array( $payment['type'] ) ) ? $payment['type'] : self::PAYMENT_METHOD,
			'invoice' => $invoice_id,
			'amount' => round( $payment['amount'], 2 ),
			'transaction_id' => ( isset( $payment['payment_id'] ) ) ? $payment['payment_id'] : '',
			'data' => array(
			'api_response' => $payment,
			),
		) );
		$new_payment = SI_Payment::get_instance( $payment_id );
		$new_payment->set_post_date( date( 'Y-m-d H:i:s', strtotime( $payment['date'] ) ) );
		return $new_payment;
	}


	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	protected function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}

	protected function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}

	protected function __construct() {
		//
	}

	protected static function csv_to_array( $csv, $delimiter = ',', $enclosure = '', $escape = '\\', $terminator = "\n" ) {
		$r = array();
		$rows = explode( $terminator,trim( $csv ) );
		$names = array_shift( $rows );
		$names = str_getcsv( $names,$delimiter,$enclosure,$escape );
		$nc = count( $names );
		foreach ( $rows as $row ) {
			if ( trim( $row ) ) {
				$values = str_getcsv( $row,$delimiter,$enclosure,$escape );
				if ( ! $values ) { $values = array_fill( 0,$nc,null ); }
				$r[] = array_combine( $names,$values );
			}
		}
		return $r;
	}
}
SI_Freshbooks_Import::register();
