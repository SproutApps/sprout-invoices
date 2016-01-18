<?php

/**
 * Harvest Importer
 *
 * @package Sprout_Invoice
 * @subpackage Importers
 */
class SI_Harvest_Import extends SI_Importer {
	const SETTINGS_PAGE = 'import';
	const PROCESS_ACTION = 'start_import';
	const HARVEST_USER_OPTION = 'si_harvest_user_option';
	const HARVEST_PASS_OPTION = 'si_harvest_pass_option';
	const HARVEST_ACCOUNT_OPTION = 'si_harvest_account_option';
	const PROCESS_ARCHIVED = 'si_harvest_import_archived';
	const PAYMENT_METHOD = 'Harvest Imported';
	const PROGRESS_OPTION = 'current_import_progress_harvest';
	const DELETE_PROGRESS = 'remove_progress_option';

	// Meta
	const HARVEST_ID = '_harvest_id';

	private static $harvest_user;
	private static $harvest_pass;
	private static $harvest_account;
	private static $importing_archived;
	private static $start_progress_over;

	public static function init() {
		// Settings
		self::$harvest_user = get_option( self::HARVEST_USER_OPTION, '' );
		self::$harvest_pass = get_option( self::HARVEST_PASS_OPTION, '' );
		self::$harvest_account = self::sanitize_subdomain( get_option( self::HARVEST_ACCOUNT_OPTION, '' ) );
		self::register_payment_settings();
		self::save_options();

		// Maybe process import
		self::maybe_process_import();
	}

	public static function register() {
		self::add_importer( __CLASS__, __( 'Harvest', 'sprout-invoices' ) );
	}


	/**
	 * Register the payment settings
	 * @return
	 */
	public static function register_payment_settings() {
		// Settings
		$settings = array(
			'si_harvest_importer_settings' => array(
				'title' => 'Harvest Import Settings',
				'weight' => 0,
				'tab' => self::get_settings_page( false ),
				'settings' => array(
					self::HARVEST_USER_OPTION => array(
						'label' => __( 'User', 'sprout-invoices' ),
						'option' => array(
							'type' => 'text',
							'default' => self::$harvest_user,
							'attributes' => array(
		'placeholder' => __(
		'user@gmail.com', 'sprout-invoices' ),
							),
							'description' => '',
						),
					),
					self::HARVEST_PASS_OPTION => array(
						'label' => __( 'Password', 'sprout-invoices' ),
						'option' => array(
							'type' => 'password',
							'default' => self::$harvest_pass,
							'attributes' => array(
					'placeholder' => __(
					'password', 'sprout-invoices' ),
							),
							'description' => '',
						),
					),
					self::HARVEST_ACCOUNT_OPTION => array(
						'label' => __( 'Account/Sub-domain', 'sprout-invoices' ),
						'option' => array(
							'type' => 'text',
							'default' => self::$harvest_account,
							'attributes' => array(
					'placeholder' => __(
					'your-subdomain', 'sprout-invoices' ),
							),
							'description' => __( 'https://[subdomain].harvest.com', 'sprout-invoices' ),
							'sanitize_callback' => array( __CLASS__, 'sanitize_subdomain' ),
						),
					),
					self::PROCESS_ARCHIVED => array(
						'label' => __( 'Import Archived', 'sprout-invoices' ),
						'option' => array(
							'type' => 'checkbox',
							'value' => 'archived',
							'label' => __( 'Import inactive clients.', 'sprout-invoices' ),
							'description' => '',
						),
					),
					self::DELETE_PROGRESS => array(
						'label' => __( 'Clear Progress', 'sprout-invoices' ),
						'option' => array(
							'type' => 'checkbox',
							'value' => 'restart',
							'label' => 'Re-start the Import Process',
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
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );
	}

	public static function save_options() {
		if ( ! current_user_can( 'manage_sprout_invoices_importer' ) ) {
			return;
		}

		if ( isset( $_POST[ self::HARVEST_USER_OPTION ] ) && $_POST[ self::HARVEST_USER_OPTION ] != '' ) {
			self::$harvest_user = $_POST[ self::HARVEST_USER_OPTION ];
			update_option( self::HARVEST_USER_OPTION, $_POST[ self::HARVEST_USER_OPTION ] );
		}
		if ( isset( $_POST[ self::HARVEST_PASS_OPTION ] ) && $_POST[ self::HARVEST_PASS_OPTION ] != '' ) {
			self::$harvest_pass = $_POST[ self::HARVEST_PASS_OPTION ];
			update_option( self::HARVEST_PASS_OPTION, $_POST[ self::HARVEST_PASS_OPTION ] );
		}
		if ( isset( $_POST[ self::HARVEST_ACCOUNT_OPTION ] ) && $_POST[ self::HARVEST_ACCOUNT_OPTION ] != '' ) {
			self::$harvest_account = $_POST[ self::HARVEST_ACCOUNT_OPTION ];
			update_option( self::HARVEST_ACCOUNT_OPTION, $_POST[ self::HARVEST_ACCOUNT_OPTION ] );
		}

		// Clear out progress
		if ( isset( $_POST[ self::DELETE_PROGRESS ] ) && $_POST[ self::DELETE_PROGRESS ] == 'restart' ) {
			delete_option( self::PROGRESS_OPTION );
		}
	}

	/**
	 * Check to see if it's time to start the import process.
	 * @return
	 */
	public static function maybe_process_import() {
		if ( isset( $_POST[ self::PROCESS_ACTION ] ) && wp_verify_nonce( $_POST[ self::PROCESS_ACTION ], self::PROCESS_ACTION ) ) {
			add_filter( 'si_show_importer_settings', '__return_false' );
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
		require_once SI_PATH . '/importers/lib/harvest/HarvestAPI.php';
		spl_autoload_register( array( 'HarvestAPI', 'autoload' ) );
		$api = new HarvestAPI();
		$api->setUser( self::$harvest_user );
		$api->setPassword( self::$harvest_pass );
		$api->setAccount( self::$harvest_account );
		$api->setRetryMode( HarvestAPI::RETRY );
		$api->setSSL( true );

		// get clients, though we're just confirming credentials
		$result = $api->getClients();
		if ( ! $result->isSuccess() ) {
			self::return_error( __( 'Authentication error.', 'sprout-invoices' ) );
		}
		self::return_progress( array(
			'authentication' => array(
			'message' => __( 'Communicating with the Harvest API...', 'sprout-invoices' ),
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

			require_once SI_PATH . '/importers/lib/harvest/HarvestAPI.php';
			spl_autoload_register( array( 'HarvestAPI', 'autoload' ) );
			$api = new HarvestAPI();
			$api->setUser( self::$harvest_user );
			$api->setPassword( self::$harvest_pass );
			$api->setAccount( self::$harvest_account );
			$api->setRetryMode( HarvestAPI::RETRY );
			$api->setSSL( true );

			$progress_key = 'clients_import_progress';
			if ( ! isset( $progress[ $progress_key ] ) ) {
				$progress[ $progress_key ] = 0;
				update_option( self::PROGRESS_OPTION, $progress );
			}

			$result = $api->getClients();

			if ( ! $result->isSuccess() ) {
				self::return_error( __( 'Client import error.', 'sprout-invoices' ) );
			}

			// Start importing the clients 20 at a time
			$total_records = count( $result->data );
			// Break the array up into pages
			$paged_data = array_chunk( $result->data, 20 );
			$pages = count( $paged_data );
			$total_imported = intval( ($total_records / $pages) * $progress[ $progress_key ] );

			if ( $progress[ $progress_key ] <= $pages ) {

				$current_page = $paged_data[ $progress[ $progress_key ] ];
				foreach ( $current_page as $client_id => $client ) {
					self::create_client( $client );
				}

				$progress[ $progress_key ]++;
				update_option( self::PROGRESS_OPTION, $progress );

				// Return the progress
				self::return_progress( array(
					'authentication' => array(
					'message' => sprintf( __( 'Attempting to import %s clients...', 'sprout-invoices' ), $total_records ),
					'progress' => 10 + $progress[ $progress_key ],
					),
					'clients' => array(
					'message' => sprintf( __( 'Imported about %s clients so far.', 'sprout-invoices' ), $total_imported ),
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
				'message' => sprintf( __( 'Successfully imported %s clients...', 'sprout-invoices' ), $total_records ),
				'progress' => 50,
				),
				'clients' => array(
				'message' => sprintf( __( 'Imported %s clients!', 'sprout-invoices' ), $total_records ),
				'progress' => 100,
				'next_step' => 'contacts',
				),
			) );
		}

		// Completed previously
		self::return_progress( array(
			'authentication' => array(
			'message' => sprintf( __( 'Successfully imported %s clients already, moving on...', 'sprout-invoices' ), $total_records ),
			'progress' => 50,
			),
			'clients' => array(
			'progress' => 100,
			'message' => sprintf( __( 'Successfully imported %s clients already.', 'sprout-invoices' ), $total_records ),
			'next_step' => 'contacts',
			),
		) );

		// If this is needed something went wrong since json should have been printed and exited.
		return;

	}

	/**
	 * Third step is to import contacts
	 * @return
	 */
	public static function import_contacts() {

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		$total_records = 0;
		if ( ! isset( $progress['contacts_complete'] ) ) {

			require_once SI_PATH . '/importers/lib/harvest/HarvestAPI.php';
			spl_autoload_register( array( 'HarvestAPI', 'autoload' ) );
			$api = new HarvestAPI();
			$api->setUser( self::$harvest_user );
			$api->setPassword( self::$harvest_pass );
			$api->setAccount( self::$harvest_account );
			$api->setRetryMode( HarvestAPI::RETRY );
			$api->setSSL( true );

			$progress_key = 'contacts_import_progress';
			if ( ! isset( $progress[ $progress_key ] ) ) {
				$progress[ $progress_key ] = 0;
				update_option( self::PROGRESS_OPTION, $progress );
			}

			$result = $api->getContacts();

			if ( ! $result->isSuccess() ) {
				self::return_error( __( 'Contact import error.', 'sprout-invoices' ) );
			}

			// Start importing the contacts 20 at a time
			$total_records = count( $result->data );
			// Break the array up into pages
			$paged_data = array_chunk( $result->data, 20 );
			$pages = count( $paged_data ) -1;
			$total_imported = intval( ($total_records / $pages) * $progress[ $progress_key ] );

			if ( $progress[ $progress_key ] <= $pages ) {

				$current_page = $paged_data[ $progress[ $progress_key ] ];
				foreach ( $current_page as $contact_id => $contact ) {
					self::create_contact( $contact );
				}

				$progress[ $progress_key ]++;
				update_option( self::PROGRESS_OPTION, $progress );

				// Return the progress
				self::return_progress( array(
					'authentication' => array(
					'message' => sprintf( __( 'Attempting to import %s contacts...', 'sprout-invoices' ), $total_records ),
					'progress' => 25 + $progress[ $progress_key ],
					),
					'contacts' => array(
					'message' => sprintf( __( 'Imported about %s contacts so far.', 'sprout-invoices' ), $total_imported ),
					'progress' => intval( ($progress[ $progress_key ] / $pages) * 100 ),
					'next_step' => 'contacts',
					),
				) );
			}

			// Mark as complete
			$progress['contacts_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );

			// Complete
			self::return_progress( array(
				'authentication' => array(
				'message' => sprintf( __( 'Successfully imported %s contacts...', 'sprout-invoices' ), $total_records ),
				'progress' => 50,
				),
				'contacts' => array(
				'message' => sprintf( __( 'Imported %s contacts!', 'sprout-invoices' ), $total_records ),
				'progress' => 100,
				'next_step' => 'estimates',
				),
			) );
		}

		// Completed previously
		self::return_progress( array(
			'authentication' => array(
			'message' => sprintf( __( 'Successfully imported %s contacts already, moving on...', 'sprout-invoices' ), $total_records ),
			'progress' => 50,
			),
			'contacts' => array(
			'progress' => 100,
			'message' => sprintf( __( 'Successfully imported %s contacts already.', 'sprout-invoices' ), $total_records ),
			'next_step' => 'estimates',
			),
		) );

		// If this is needed something went wrong since json should have been printed and exited.
		return;

	}

	/**
	 * Fourth step is to import estimates
	 * @return
	 */
	public static function import_estimates() {

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );

		// Mark as complete
		$progress['estimates_complete'] = 1;
		update_option( self::PROGRESS_OPTION, $progress );

		// Completed previously
		self::return_progress( array(
			'authentication' => array(
			'message' => __( 'Attempting to get your Harvest estimates...', 'sprout-invoices' ),
			'progress' => 50,
			),
			'estimates' => array(
			'progress' => 100,
			'message' => __( 'The Harvest API does not permit access to your estimates.', 'sprout-invoices' ),
			'next_step' => 'invoices',
			),
		) );

		// If this is needed something went wrong since json should have been printed and exited.
		return;

	}

	/**
	 * Final step is to import invoices and payments
	 * @return
	 */
	public static function import_invoices() {

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		if ( ! isset( $progress['invoices_complete'] ) ) {

			set_time_limit( 0 ); // run script forever

			$progress_key = 'invoices_import_progress';
			if ( ! isset( $progress[ $progress_key ] ) ) {
				$progress[ $progress_key ] = 0;
				update_option( self::PROGRESS_OPTION, $progress );
			}
			// Check which chunk we're at
			// Since increment of 50 invoices will tend to bring a server to it's knees
			// break up the returned results down.
			$progress_pagechunk_key = 'invoices_import_progress_pagechunk';
			if ( ! isset( $progress[ $progress_pagechunk_key ] ) ) {
				$progress[ $progress_pagechunk_key ] = 0;
				update_option( self::PROGRESS_OPTION, $progress );
			}

			// If we're just starting out than provide messaging
			if ( $progress[ $progress_key ] == 0 ) {
				$progress[ $progress_key ]++;
				update_option( self::PROGRESS_OPTION, $progress );

				// Return the progress
				self::return_progress( array(
					'authentication' => array(
					'message' => __( 'Attempting to import your invoices and their payments...', 'sprout-invoices' ),
					'progress' => 60 + $progress[ $progress_key ],
					),
					'invoices' => array(
					'message' => sprintf( __( 'Currently importing invoices and their payments in increments of %s. Thank you for your patience, this is a very slow process.', 'sprout-invoices' ), 50 ),
					'progress' => 15 + ($progress[ $progress_key ] * 5),
					),
					'payments' => array(
					'message' => __( 'Payments will be imported with new invoices', 'sprout-invoices' ),
					'progress' => 15 + ($progress[ $progress_key ] * 5),
					'next_step' => 'invoices',
					),
				) );
			}

			require_once SI_PATH . '/importers/lib/harvest/HarvestAPI.php';
			spl_autoload_register( array( 'HarvestAPI', 'autoload' ) );
			$api = new HarvestAPI();
			$api->setUser( self::$harvest_user );
			$api->setPassword( self::$harvest_pass );
			$api->setAccount( self::$harvest_account );
			$api->setRetryMode( HarvestAPI::RETRY );
			$api->setSSL( true );

			$filter = new Harvest_Invoice_Filter();
			$filter->set( 'page', $progress[ $progress_key ] );
			$result = $api->getInvoices( $filter );

			if ( ! $result->isSuccess() ) {
				self::return_error( __( 'Invoice import error.', 'sprout-invoices' ) );
			}

			if ( $result->isSuccess() ) {

				$payments_imported = 0;
				$invoices_imported = 0;

				// Break the array up into pages of 10 items
				$paged_data = array_chunk( $result->data, apply_filters( 'si_harvest_import_increments_for_invoices', 10 ) );

				do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' page: ', $progress[ $progress_key ], false );
				do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' chunk count: ', count( $paged_data ), false );
				do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' chunk progress: ', $progress[ $progress_pagechunk_key ], false );

				if ( isset( $paged_data[ $progress[ $progress_pagechunk_key ] ] ) ) {

					$current_chunk = $paged_data[ $progress[ $progress_pagechunk_key ] ];
					foreach ( $current_chunk as $key => $invoice ) {
						$invoices_imported++;

						$invoice_id = $invoice->id;
						$new_invoice = self::create_invoice( $invoice );

						if ( is_a( $new_invoice, 'SI_Invoice' ) ) {

							////////////////
							// Line Items //
							////////////////
							$result = $api->getInvoice( $invoice_id );
							if ( $result->isSuccess() ) {
								self::add_invoice_line_items( $result->data, $new_invoice );
							}

							//////////////
							// Payments //
							//////////////
							$result = $api->getInvoicePayments( $invoice_id );
							if ( $result->isSuccess() ) {
								foreach ( $result->data as $payment_id => $payment ) {
									$payments_imported++;
									self::create_invoice_payment( $payment, $new_invoice );
								}
							}
						}
					}
					// Update the page chunk currently processed
					$progress[ $progress_pagechunk_key ]++;
					// If the last chunk was just processed than
					// start the progress over
					if ( $progress[ $progress_pagechunk_key ] == count( $paged_data ) ) {
						unset( $progress[ $progress_pagechunk_key ] );

						// Total progress for paged HAPI filter
						$progress[ $progress_key ]++;
						update_option( self::PROGRESS_OPTION, $progress );
					}
					update_option( self::PROGRESS_OPTION, $progress );

					// Return the progress
					self::return_progress( array(
						'authentication' => array(
						'message' => sprintf( __( 'Attempting to import %s new invoices and their payments...', 'sprout-invoices' ), $invoices_imported ),
						'progress' => 60 + $progress[ $progress_key ],
						),
						'payments' => array(
						'message' => sprintf( __( 'Just imported %s more payments.', 'sprout-invoices' ), $payments_imported ),
						'progress' => 15 + ($progress[ $progress_key ] * 2),
						),
						'invoices' => array(
						'message' => sprintf( __( 'Importing invoices in increments of %s. Thank you for your patience, this is a very slow process.', 'sprout-invoices' ), apply_filters( 'si_harvest_import_increments_for_invoices', 10 ) ),
						'progress' => 15 + ($progress[ $progress_key ] * 2),
						'next_step' => 'invoices',
						),
					) );
				}
			}

			// Mark as complete
			$progress['invoices_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );

			// Complete
			self::return_progress( array(
				'authentication' => array(
				'message' => __( 'Successfully imported a bunch of invoices...', 'sprout-invoices' ),
				'progress' => 100,
				),
				'payments' => array(
				'message' => __( 'Successfully imported a bunch of payments.', 'sprout-invoices' ),
				'progress' => 100,
				),
				'invoices' => array(
				'message' => __( 'Finished importing your invoices!', 'sprout-invoices' ),
				'progress' => 100,
				'next_step' => 'complete',
				),
			) );
		}

		// Completed previously
		self::return_progress( array(
			'authentication' => array(
			'message' => __( 'Successfully imported invoices already, moving on...', 'sprout-invoices' ),
			'progress' => 50,
			),
			'payments' => array(
			'message' => __( 'Successfully imported a bunch of payments already.', 'sprout-invoices' ),
			'progress' => 100,
			),
			'invoices' => array(
			'progress' => 100,
			'message' => __( 'Imported all the invoices already!', 'sprout-invoices' ),
			'next_step' => 'complete',
			),
		) );

		// If this is needed something went wrong since json should have been printed and exited.
		return;

	}

	//////////////
	// utility //
	//////////////

	public static function create_client( Harvest_Client $client ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::HARVEST_ID => $client->id ) );
		// Don't create a duplicate if this was already imported.
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Client imported already', $client->id );
			return;
		}
		if ( ! self::import_archived_data() && $client->active == 'false' ) {
			return;
		}
		$args = array(
			'company_name' => $client->name,
			'currency' => substr( $client->currency, -3 ),
			'address' => array( 'street' => $client->details ),
		);
		$client_id = SI_Client::new_client( $args );
		update_post_meta( $client_id, self::HARVEST_ID, $client->id );

	}

	public static function create_contact( Harvest_Contact $contact ) {
		if ( $user = get_user_by( 'email', $contact->email ) ) {
			do_action( 'si_error', 'Contact imported already', $contact->id );
			return $user->ID;
		}
		$clients = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::HARVEST_ID => $contact->client_id ) );
		// Only create a contact if a client was already created.
		if ( empty( $clients ) ) {
			return;
		}
		// Get client and confirm it's validity
		$client = SI_Client::get_instance( $clients[0] );
		if ( ! is_a( $client, 'SI_Client' ) ) {
			return;
		}
		$args = array(
			'user_login' => $contact->email,
			'display_name' => $client->get_title(),
			'user_email' => $contact->email,
			'first_name' => $contact->first_name,
			'last_name' => $contact->last_name,
		);
		$user_id = SI_Clients::create_user( $args );
		update_user_meta( $user_id, self::HARVEST_ID, $contact->id );
		update_user_meta( $user_id, self::USER_META_TITLE, $contact->title );
		update_user_meta( $user_id, self::USER_META_PHONE, $contact->phone_mobile );
		update_user_meta( $user_id, self::USER_META_OFFICE_PHONE, $contact->phone_office );

		// Assign new user to client.
		$client->add_associated_user( $user_id );
		return $user_id;
	}

	public static function create_estimate( $estimate ) {
		return;
	}

	public static function create_invoice( Harvest_Invoice $invoice ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Invoice::POST_TYPE, array( self::HARVEST_ID => $invoice->id ) );
		// Don't create a duplicate if this was already imported.
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Invoice imported already', $invoice->id );
			return;
		}
		$clients = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::HARVEST_ID => $invoice->client_id ) );
		// Get client and confirm it's validity
		$client = SI_Client::get_instance( $clients[0] );
		$client_id = ( is_a( $client, 'SI_Client' ) ) ? $client->get_id() : 0 ;

		$args = array(
			'subject' => ( $invoice->subject ) ? $invoice->subject : 'Harvest Import #' . $invoice->id,
		);
		$inv_id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_PENDING );
		update_post_meta( $inv_id, self::HARVEST_ID, $invoice->id );

		$inv = SI_Invoice::get_instance( $inv_id );
		$inv->set_client_id( $client_id );
		$inv->set_invoice_id( $invoice->number );
		$inv->set_total( $invoice->amount );
		$inv->set_tax( $invoice->tax );
		$inv->set_discount( $invoice->discount );
		$inv->set_notes( $invoice->notes );
		$inv->set_due_date( strtotime( $invoice->due_at ) );
		$inv->set_issue_date( strtotime( $invoice->created_at ) );
		// post date
		$inv->set_post_date( date( 'Y-m-d H:i:s', strtotime( $invoice->created_at ) ) );

		// Record
		do_action( 'si_new_record',
			$invoice, // content
			self::RECORD, // type slug
			$inv_id, // post id
			__( 'Invoice Imported', 'sprout-invoices' ), // title
			0 // user id
		);

		return $inv;
	}

	public static function add_invoice_line_items( Harvest_Invoice $invoice, SI_Invoice $new_invoice ) {
		// Format the csv string to compensate for bad formatting and missing values.
		$csv_string = $invoice->csv_line_items;
		// Create rows for each line item. Making sure to not break with wrapped line items.
		// https://bugs.php.net/bug.php?id=55763
		$lines = preg_split( '/[\r\n]{1,2}(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/', $csv_string );

		// Build array for each line item
		$row_arrays = array_map( 'str_getcsv', $lines );
		// Header row
		$header = array_shift( $row_arrays );
		// Rename the key names to match what SI uses
		$header = str_replace(
			array( 'unit_price', 'quantity', 'description', 'kind', 'amount' ),
			array( 'rate', 'qty', 'desc', 'type', 'total' ),
		$header );
		// Build a line item associated array with header names
		$line_items = array();
		foreach ( $row_arrays as $row ) {
			if ( strlen( implode( '', $row ) ) > 0 ) { // Don't add empty rows
				$line_items[] = array_combine( $header, $row );
			}
		}
		// Set the line items meta
		$new_invoice->set_line_items( $line_items );
	}

	public static function create_invoice_payment( Harvest_Payment $payment, SI_Invoice $invoice ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Payment::POST_TYPE, array( self::HARVEST_ID => $payment->id ) );
		// Don't create a duplicate if this was already imported.
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Invoice imported already', $payment->id );
			return;
		}
		$payment_id = SI_Payment::new_payment( array(
			'payment_method' => ( isset( $payment->recorded_by ) ) ? $payment->recorded_by : self::PAYMENT_METHOD,
			'invoice' => $invoice->get_ID(),
			'amount' => $payment->amount,
			'transaction_id' => ( isset( $payment->pay_pal_transaction_id ) ) ? $payment->pay_pal_transaction_id : '',
			'data' => array(
			'api_response' => array(
			'amount' => $payment->amount,
			'authorization' => $payment->authorization,
			'created_at' => $payment->created_at,
			'id' => $payment->id,
			'invoice_id' => $payment->invoice_id,
			'paid_at' => $payment->paid_at,
			'paypal_transaction_id' => $payment->pay_pal_transaction_id,
			'payment_gateway_id' => $payment->payment_gateway_id,
			'recorded_by' => $payment->recorded_by,
			'recorded_by_email' => $payment->recorded_by_email,
			'updated_at' => $payment->updated_at,
			),
			),
		) );
		$new_payment = SI_Payment::get_instance( $payment_id );
		$new_payment->set_post_date( date( 'Y-m-d H:i:s', strtotime( $payment->created_at ) ) );
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
SI_Harvest_Import::register();
