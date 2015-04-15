<?php

/**
 * CSV Importer
 *
 * @package Sprout_Invoice
 * @subpackage Importers
 */
class SI_CSV_Import extends SI_Importer {
	const SETTINGS_PAGE = 'import';
	const PROCESS_ACTION = 'start_import';
	const CLIENT_FILE_OPTION = 'si_client_csv_upload';
	const INVOICE_FILE_OPTION = 'si_invoice_csv_upload';
	const ESTIMATE_FILE_OPTION = 'si_estimate_csv_upload';
	const PAYMENT_FILE_OPTION = 'si_payment_csv_upload';
	const PAYMENT_METHOD = 'CSV Imported';
	const DELETE_PROGRESS = 'remove_progress_option';
	const PROGRESS_OPTION = 'current_import_progress_csv';

	// Meta
	const CSV_ID = '_csv_id';

	private static $start_progress_over;

	public static function init() {
		self::register_settings();
		self::save_options();

		// Maybe process import
		self::maybe_process_import();
	}

	public static function register() {
		self::add_importer( __CLASS__, self::__( 'CSV' ) );
	}


	/**
	 * Register the payment settings
	 * @return  
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			'si_csv_importer_settings' => array(
				'title' => 'CSV Import Settings',
				'weight' => 0,
				'tab' => self::get_settings_page( FALSE ),
				'settings' => array(
					self::CLIENT_FILE_OPTION => array(
						'label' => self::__( 'Clients' ),
						'option' => array(
							'type' => 'file',
							'description' => sprintf( self::__( 'Example CSV <a href="%s" target="_blank">here</a>. To be safe import no more than 100 clients at a time and import all of your clients before importing invoices or payments.' ), SI_URL . '/importers/csv-examples/clients.csv' ),
						)
					),
					self::ESTIMATE_FILE_OPTION => array(
						'label' => self::__( 'Estimates' ),
						'option' => array(
							'type' => 'file',
							'description' => sprintf( self::__( 'Example CSV <a href="%s" target="_blank">here</a>. To be safe import no more than 250 estimates at a time and import all of your clients.' ), SI_URL . '/importers/csv-examples/estimates.csv' ),
						)
					),
					self::INVOICE_FILE_OPTION => array(
						'label' => self::__( 'Invoices' ),
						'option' => array(
							'type' => 'file',
							'description' => sprintf( self::__( 'Example CSV <a href="%s" target="_blank">here</a>. To be safe import no more than 250 invoices at a time, import all of your clients, and import before payments.' ), SI_URL . '/importers/csv-examples/invoices.csv' ),
						)
					),
					self::PAYMENT_FILE_OPTION => array(
						'label' => self::__( 'Payments' ),
						'option' => array(
							'type' => 'file',
							'description' => sprintf( self::__( 'Example CSV <a href="%s" target="_blank">here</a>. To be safe import no more than 100 payments at a time and make sure all your invoices are imported first.' ), SI_URL . '/importers/csv-examples/payments.csv' ),
						)
					),
					self::DELETE_PROGRESS => array(
						'label' => self::__( 'Clear Progress' ),
						'option' => array(
							'type' => 'checkbox',
							'value' => 'restart',
							'label' => self::__('Re-start the Import Process'),
							'description' => self::__( 'This will start the import process from the start. Any records already imported will not be duplicated but any new records will.' )
						)
					),
					self::PROCESS_ACTION => array(
						'option' => array(
							'type' => 'hidden',
							'value' => wp_create_nonce( self::PROCESS_ACTION ),
						)
					)
				)
			)
		);
		do_action( 'sprout_settings', $settings );
	}

	public static function save_options() {
		// Clear out progress
		if ( isset( $_POST[self::DELETE_PROGRESS] ) && $_POST[self::DELETE_PROGRESS] == 'restart' ) {
			delete_option( self::PROGRESS_OPTION );
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$upload_overrides = array( 'test_form' => false, 'mimes' => array( 'csv' => 'text/csv' ) );
		if ( isset( $_FILES[self::CLIENT_FILE_OPTION] ) ) {
			$client_csv_file = $_FILES[self::CLIENT_FILE_OPTION];
			$client_csv = wp_handle_upload( $client_csv_file, $upload_overrides );
			if ( isset( $client_csv['file'] ) && $client_csv['file'] != '' ) {
				update_option( self::CLIENT_FILE_OPTION, $client_csv['file'] );
			}
		}
		if ( isset( $_FILES[self::INVOICE_FILE_OPTION] ) ) {
			$invoice_csv_file = $_FILES[self::INVOICE_FILE_OPTION];
			$invoice_csv = wp_handle_upload( $invoice_csv_file, $upload_overrides );
			if ( isset( $invoice_csv['file'] ) && $invoice_csv['file'] != '' ) {
				update_option( self::INVOICE_FILE_OPTION, $invoice_csv['file'] );
			}
		}
		if ( isset( $_FILES[self::ESTIMATE_FILE_OPTION] ) ) {
			$estimate_csv_file = $_FILES[self::ESTIMATE_FILE_OPTION];
			$estimate_csv = wp_handle_upload( $estimate_csv_file, $upload_overrides );
			if ( isset( $estimate_csv['file'] ) && $estimate_csv['file'] != '' ) {
				update_option( self::ESTIMATE_FILE_OPTION, $estimate_csv['file'] );
			}
		}
		if ( isset( $_FILES[self::PAYMENT_FILE_OPTION] ) ) {
			$payment_csv_file = $_FILES[self::PAYMENT_FILE_OPTION];
			$payment_csv = wp_handle_upload( $payment_csv_file, $upload_overrides );
			if ( isset( $payment_csv['file'] ) && $payment_csv['file'] != '' ) {
				update_option( self::PAYMENT_FILE_OPTION, $payment_csv['file'] );
			}
		}

	}

	/**
	 * Check to see if it's time to start the import process.
	 * @return  
	 */
	public static function maybe_process_import() {
		if ( isset( $_POST[self::PROCESS_ACTION] ) && wp_verify_nonce( $_POST[self::PROCESS_ACTION], self::PROCESS_ACTION ) ) {
			add_filter( 'si_show_importer_settings', '__return_false' );
		}
	}

	/**
	 * Utility to return a JSON error
	 * @param  string $message 
	 * @return json          
	 */
	public static function return_error( $message ) {
		header( 'Content-type: application/json' );
		if ( self::DEBUG ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( 
				array( 'error' => TRUE, 'message' => $message )
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
		if ( self::DEBUG ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $array );
		exit();
	}

	/**
	 * First step in the import progress
	 * @return 
	 */
	public static function import_authentication() {
		self::return_progress( array( 
					'authentication' => array( 
						'message' => self::__('Uploaded CSV files being processed...Hold on to your butts...'), 
						'progress' => 10,
					),
					'clients' => array( 
						'message' => self::__('Preparing...'), 
						'progress' => 80,
						),
					'contacts' => array( 
						'message' => self::__('Preparing...'), 
						'progress' => 80,
						'next_step' => 'clients'
						),
				) );
	}

	/**
	 * Second step is to import clients and contacts
	 * @return 
	 */
	public static function import_clients() {
		// run script forever
		set_time_limit( 0 );

		$csv_file = get_option( self::CLIENT_FILE_OPTION );

		if ( !$csv_file ) {
			// Completed previously
			self::return_progress( array( 
						'authentication' => array( 
							'message' => self::__('Skipping clients without a CSV to process...'), 
							'progress' => 25
							),
						'clients' => array( 
							'message' => self::__('Skipped...nothing to import.'), 
							'progress' => 100,
							),
						'contacts' => array( 
							'message' => self::__('Skipped...nothing to import.'), 
							'progress' => 100,
							),
						'estimates' => array( 
							'progress' => 80,
							'message' => self::__('Preparing...'), 
							'next_step' => 'estimates'
							),
						) );
			return;
		}

		$clients = self::csv_to_array( $csv_file );
		$total_records = count( $clients );

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		if ( !isset( $progress['clients_complete'] ) ) {

			foreach ( $clients as $key => $client ) {
				$new_client_id = self::create_client( $client );
				self::create_contact( $client, $new_client_id );
			}

			// Mark as complete
			$progress['clients_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );
			delete_option( self::CLIENT_FILE_OPTION );
		}

		// Completed previously
		self::return_progress( array( 
				'authentication' => array( 
					'message' => sprintf( self::__('Successfully imported %s contacts and their clients already, moving on...'), $total_records ), 
					'progress' => 25
					),
				'clients' => array( 
					'message' => sprintf( self::__('Successfully imported %s clients.'), $total_records ), 
					'progress' => 100,
					),
				'contacts' => array( 
					'message' => sprintf( self::__('Successfully imported more than %s contacts from their clients.'), $total_records ), 
					'progress' => 100,
					),
				'estimates' => array( 
					'progress' => 80,
					'message' => self::__('Preparing...'), 
					'next_step' => 'estimates'
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
		// run script forever
		set_time_limit( 0 );

		$csv_file = get_option( self::ESTIMATE_FILE_OPTION );

		if ( !$csv_file ) {
			// Completed previously
			self::return_progress( array( 
						'authentication' => array( 
							'message' => self::__('Skipping estimate importing without a CSV to process...'), 
							'progress' => 80
							),
						'estimates' => array( 
							'message' => self::__('Skipped...nothing to import.'), 
							'progress' => 100,
							),
						'invoices' => array( 
							'progress' => 80,
							'message' => self::__('Preparing...'), 
							'next_step' => 'invoices'
							),
						) );
			return;
		}

		$invoices = self::csv_to_array( $csv_file );
		$total_records = count( $invoices );

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		if ( !isset( $progress['estimates_complete'] ) ) {

			foreach ( $estimates as $key => $estimate ) {
				self::create_estimate( $estimate );
			}

			// Mark as complete
			$progress['estimates_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );
			delete_option( self::ESTIMATE_FILE_OPTION );

			// Complete
			self::return_progress( array( 
						'authentication' => array( 
							'message' => sprintf( self::__('Successfully imported %s estimates...'), $total_records ), 
							'progress' => 75
							),
						'estimates' => array(
							'message' => sprintf( self::__('Imported %s estimates!'), $total_records ),  
							'progress' => 100,
							),
						'invoices' => array( 
							'progress' => 80,
							'message' => self::__('Preparing...'), 
							'next_step' => 'invoices'
							),
						) );
		}

		// Completed previously
		self::return_progress( array( 
						'authentication' => array( 
							'message' => sprintf( self::__('Successfully imported %s estimates already, moving on...'), $total_records ), 
							'progress' => 75
							),
						'estimates' => array( 
							'message' => sprintf( self::__('Successfully imported %s estimates already.'), $total_records ), 
							'progress' => 100,
							),
						'invoices' => array( 
							'progress' => 80,
							'message' => self::__('Preparing...'), 
							'next_step' => 'invoices'
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
		// run script forever
		set_time_limit( 0 );

		$csv_file = get_option( self::INVOICE_FILE_OPTION );

		if ( !$csv_file ) {
			// Completed previously
			self::return_progress( array( 
						'authentication' => array( 
							'message' => self::__('Skipping invoice importing without a CSV to process...'), 
							'progress' => 80
							),
						'invoices' => array( 
							'message' => self::__('Skipped...nothing to import.'), 
							'progress' => 100,
							),
						'payments' => array( 
							'progress' => 80,
							'message' => self::__('Preparing...'), 
							'next_step' => 'payments'
							),
						) );
			return;
		}

		$invoices = self::csv_to_array( $csv_file );
		$total_records = count( $invoices );

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		if ( !isset( $progress['invoices_complete'] ) ) {

			foreach ( $invoices as $key => $invoice ) {
				self::create_invoice( $invoice );
			}

			// Mark as complete
			$progress['invoices_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );
			delete_option( self::INVOICE_FILE_OPTION );

			// Complete
			self::return_progress( array( 
						'authentication' => array( 
							'message' => sprintf( self::__('Successfully imported %s invoices...'), $total_records ), 
							'progress' => 75
							),
						'invoices' => array(
							'message' => sprintf( self::__('Imported %s invoices!'), $total_records ),  
							'progress' => 100,
							),
						'payments' => array( 
							'progress' => 80,
							'message' => self::__('Preparing...'), 
							'next_step' => 'payments'
							),
						) );
		}

		// Completed previously
		self::return_progress( array( 
						'authentication' => array( 
							'message' => sprintf( self::__('Successfully imported %s invoices already, moving on...'), $total_records ), 
							'progress' => 75
							),
						'invoices' => array( 
							'message' => sprintf( self::__('Successfully imported %s invoices already.'), $total_records ), 
							'progress' => 100,
							),
						'payments' => array( 
							'progress' => 80,
							'message' => self::__('Preparing...'), 
							'next_step' => 'payments'
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
		// run script forever
		set_time_limit( 0 );

		$csv_file = get_option( self::PAYMENT_FILE_OPTION );

		if ( !$csv_file ) {
			// Completed previously
			self::return_progress( array( 
						'authentication' => array( 
							'message' => self::__('Skipping payment importing without a CSV to process...'), 
							'progress' => 100
							),
						'payments' => array( 
							'message' => self::__('Skipped...nothing to import.'), 
							'progress' => 100,
							'next_step' => 'complete'
							),
						) );
			return;
		}

		$payments = self::csv_to_array( $csv_file );
		$total_records = count( $payments );

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		if ( !isset( $progress['payments_complete'] ) ) {

			foreach ( $payments as $key => $payment ) {
				self::create_payment( $payment );
			}

			// Mark as complete
			$progress['payments_complete'] = 1;
			update_option( self::PROGRESS_OPTION, $progress );
			delete_option( self::PAYMENT_FILE_OPTION );

			// Complete
			self::return_progress( array( 
						'authentication' => array( 
							'message' => sprintf( self::__('Successfully imported %s payments...'), $total_records ), 
							'progress' => 100
							),
						'payments' => array(
							'message' => sprintf( self::__('Imported %s payments!'), $total_records ),  
							'progress' => 100,
							'next_step' => 'complete'
							),
						) );
		}

		// Completed previously
		self::return_progress( array( 
						'authentication' => array( 
							'message' => sprintf( self::__('Successfully imported %s estimates already, moving on...'), $total_records ), 
							'progress' => 100
							),
						'payments' => array( 
							'message' => sprintf( self::__('Successfully imported %s payments already.'), $total_records ), 
							'progress' => 100,
							'next_step' => 'complete'
							),
						) );
		
		// If this is needed something went wrong since json should have been printed and exited.
		return;

	}

	//////////////
	// Utility //
	//////////////

	public static function create_client( $client = array() ) {
		// args to create new client
		$address = array(
			'street' => $client['Address'] . ' ' . $client['Address 2'],
			'city' => $client['City'],
			'zone' => $client['State'],
			'postal_code' => $client['Zip'],
			'country' => $client['Country'],
		);
		$args = array(
			'address' => $address,
			'company_name' => ( isset( $client['Company'] ) ) ? $client['Company'] : $client['First Name'] . ' ' . $client['Last Name'],
			'company_name' => ( isset( $client['Company'] ) ) ? $client['Company'] : '',
			'website' => ( isset( $client['Web Address'] ) ) ? $client['Web Address'] : '',
			'phone' =>  ( isset( $client['Telephone'] ) ) ? $client['Telephone'] : '',
		);

		$client_id = SI_Client::new_client( $args );
		// notes
		if ( isset( $client['Notes'] ) && $client['Notes'] != '' ) {
			SI_Internal_Records::new_record( $client['Notes'], SI_Controller::PRIVATE_NOTES_TYPE, $client_id, '', 0 );
		}
		return $client_id;
	}

	public static function create_contact( $client = array(), $client_id = 0 ) {
		$contact = array(
			'username' =>  ( isset( $client['Email Address'] ) ) ? $client['Email Address'] : '',
			'email' =>  ( isset( $client['Email Address'] ) ) ? $client['Email Address'] : '',
			'first_name' =>  ( isset( $client['First Name'] ) ) ? $client['First Name'] : '',
			'last_name' =>  ( isset( $client['Last Name'] ) ) ? $client['Last Name'] : '',
		);

		if ( $user = get_user_by( 'email', $contact['email'] ) ) {
			do_action( 'si_error', 'Contact/user imported already', $contact );
			return $user->ID;
		}
		// Get client and confirm it's validity
		$client = SI_Client::get_instance( $client_id );
		if ( !is_a( $client, 'SI_Client' ) ) {
			return;
		}
		$args = array(
			'user_login' => ( $contact['username'] ) ? $contact['username'] : $contact['email'],
			'display_name' => $client->get_title(),
			'user_email' => $contact['email'],
			'first_name' => ( $contact['first_name'] ) ? $contact['first_name'] : '',
			'last_name' => ( $contact['last_name'] ) ? $contact['last_name'] : '',
		);
		$user_id = SI_Clients::create_user( $args );

		// Assign new user to client.
		$client->add_associated_user( $user_id );
		return $user_id;
	}

	public static function create_estimate( $estimate = array() ) {
		if ( isset( $invoice['Description'] ) && $invoice['Description'] != '' ) {
			$subject = $invoice['Description'];
		}
		elseif ( isset( $invoice['Client'] ) && $invoice['Client'] != '' ) {
			$subject = $invoice['Client'] . ' #' . $invoice['Estimate ID'];
		}
		else {
			$subject = '#' . $invoice['Estimate ID'];
		}
		$args = array(
			'subject' => $subject,
		);
		$new_estimate_id = SI_Estimate::create_estimate( $args, SI_Estimate::STATUS_TEMP );
		update_post_meta( $new_estimate_id, self::CSV_ID, $estimate['Estimate ID'] );

		$est = SI_Estimate::get_instance( $new_estimate_id );

		// Attempt to find matching client
		if ( isset( $estimate['Company'] ) ) {
			global $wpdb;
			$client_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s", esc_sql( $estimate['Company'] ), SI_Client::POST_TYPE ) );
			// Get client and confirm it's validity
			if ( is_array( $client_ids ) ) {
				$client = SI_Client::get_instance( $client_ids[0] );
				$inv->set_client_id( $client->get_id() );
			}
		}

		if ( isset( $estimate['Estimate ID'] ) ) {
			$est->set_estimate_id( $estimate['Estimate ID'] );
		}
		if ( isset( $estimate['Total'] ) ) {
			$est->set_total( $estimate['Total'] );
		}
		if ( isset( $estimate['Currency Code'] ) ) {
			$est->set_currency( $estimate['Currency Code'] );
		}
		if ( isset( $estimate['PO Number'] ) ) {
			$est->set_po_number( $estimate['PO Number'] );
		}
		if ( isset( $estimate['Discount %'] ) ) {
			$est->set_discount( $estimate['Discount %'] );
		}
		if ( isset( $estimate['Tax 1 %'] ) ) {
			$est->set_tax( $estimate['Tax 1 %'] );
		}
		if ( isset( $estimate['Tax 2 %'] ) ) {
			$est->set_tax2( $estimate['Tax 2 %'] );
		}
		if ( isset( $estimate['Notes'] ) ) {
			$est->set_notes( $estimate['Notes'] );
		}
		if ( isset( $estimate['Terms'] ) ) {
			$est->set_terms( $estimate['Terms'] );
		}
		if ( isset( $estimate['Estimate Date'] ) ) {
			$est->set_issue_date( strtotime( $estimate['Estimate Date'] ) );
		}

		$line_items = self::build_line_items( $estimate );
		$est->set_line_items( $line_items );

		
		// post date
		$est->set_post_date( date( 'Y-m-d H:i:s', strtotime( $estimate['Estimate Date'] ) ) );

		return $est;
	}


	public static function create_invoice( $invoice = array() ) {
		if ( isset( $invoice['Description'] ) && $invoice['Description'] != '' ) {
			$subject = $invoice['Description'];
		}
		elseif ( isset( $invoice['Client'] ) && $invoice['Client'] != '' ) {
			$subject = $invoice['Client'] . ' #' . $invoice['Invoice ID'];
		}
		else {
			$subject = '#' . $invoice['Invoice ID'];
		}
		$args = array(
			'subject' => $subject,
		);
		$new_invoice_id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_TEMP );
		update_post_meta( $new_invoice_id, self::CSV_ID, $invoice['Invoice ID'] );

		$inv = SI_Invoice::get_instance( $new_invoice_id );

		// Attempt to find matching client
		if ( isset( $invoice['Company'] ) ) {
			global $wpdb;
			$client_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s", esc_sql( $invoice['Company'] ), SI_Client::POST_TYPE ) );
			// Get client and confirm it's validity
			error_log( 'clients: ' . print_r( $client_ids, TRUE ) );
			if ( is_array( $client_ids ) && !empty( $client_ids ) ) {
				$client = SI_Client::get_instance( $client_ids[0] );
				$inv->set_client_id( $client->get_id() );
			}
		}

		if ( isset( $invoice['Invoice ID'] ) ) {
			$inv->set_invoice_id( $invoice['Invoice ID'] );
		}
		if ( isset( $invoice['Total'] ) ) {
			$inv->set_total( $invoice['Total'] );
		}
		if ( isset( $invoice['Currency Code'] ) ) {
			$inv->set_currency( $invoice['Currency Code'] );
		}
		if ( isset( $invoice['PO Number'] ) ) {
			$inv->set_po_number( $invoice['PO Number'] );
		}
		if ( isset( $invoice['Discount %'] ) ) {
			$inv->set_discount( $invoice['Discount %'] );
		}
		if ( isset( $invoice['Tax 1 %'] ) ) {
			$inv->set_tax( $invoice['Tax 1 %'] );
		}
		if ( isset( $invoice['Tax 2 %'] ) ) {
			$inv->set_tax2( $invoice['Tax 2 %'] );
		}
		if ( isset( $invoice['Notes'] ) ) {
			$inv->set_notes( $invoice['Notes'] );
		}
		if ( isset( $invoice['Terms'] ) ) {
			$inv->set_terms( $invoice['Terms'] );
		}
		if ( isset( $invoice['Invoice Date'] ) ) {
			$inv->set_issue_date( strtotime( $invoice['Invoice Date'] ) );
		}
		if ( isset( $invoice['Due Date'] ) ) {
			$inv->set_due_date( strtotime( $invoice['Due Date'] ) );
		}
		
		$line_items = self::build_line_items( $invoice );
		$inv->set_line_items( $line_items );
		error_log( 'invoice +++++++++++++++++++ ' . print_r( $inv->get_title(), TRUE ) );

		// post date
		$inv->set_post_date( date( 'Y-m-d H:i:s', strtotime( $invoice['Invoice Date'] ) ) );

		return $inv;
	}

	public static function create_payment( $payment = array() ) {
		if ( !isset( $payment['Invoice ID'] ) ) {
			do_action( 'si_error', 'No Invoice ID given within payment import', $payment );
			return;
		}
		// Find the associated invoice
		$invoices = SI_Post_Type::find_by_meta( SI_Invoice::POST_TYPE, array( self::CSV_ID => $payment['Invoice ID'] ) );

		// Can't assign a payment without an invoice
		if ( empty( $invoices ) ) {
			do_action( 'si_error', 'No invoice found for this payment', $payment['Payment ID'] );
			return;
		}
		$invoice = SI_Invoice::get_instance( $invoices[0] );
		if ( !is_a( $invoice, 'SI_Invoice' ) ) {
			return;
		}

		$payment_id = SI_Payment::new_payment( array(
				'payment_method' => ( isset( $payment['Payment Method'] ) ) ? $payment['Payment Method'] : self::PAYMENT_METHOD,
				'invoice' => $invoice->get_id(),
				'amount' => round( $payment['Amount'], 2),
				'transaction_id' => ( isset( $payment['Payment ID'] ) ) ? $payment['Payment ID'] : '',
				'data' => array(
					'api_response' => $payment
				),
			) );
		$new_payment = SI_Payment::get_instance( $payment_id );
		$new_payment->set_post_date( date( 'Y-m-d H:i:s', strtotime( $payment['Date'] ) ) );
		return $new_payment;
	}

	public static function build_line_items( $data = array() ) {
		if ( ! isset( $data['Line Item Desc'] ) ) {
			return array();
		}
		$line_items = array();
		$line_items_desc = explode( ',', $data['Line Item Desc'] );
		$line_items_rate = explode( ',', $data['Line Item Rate'] );
		$line_items_qty = explode( ',', $data['Line Item Quantity'] );
		$line_items_percentage = explode( ',', $data['Line Item Percentage'] );
		$line_items_total = explode( ',', $data['Line Item Total'] );
		foreach ( $line_items_desc as $key => $value ) {
			$line_items[] = array( 
				'rate' => ( isset( $line_items_rate[$key] ) ) ? $line_items_rate[$key] : 0,
				'qty' => ( isset( $line_items_qty[$key] ) ) ? $line_items_qty[$key] : 0,
				'desc' => $value,
				'total' => ( isset( $line_items_total[$key] ) ) ? $line_items_total[$key] : 0,
				'tax' => ( isset( $line_items_percentage[$key] ) ) ? $line_items_percentage[$key] : '',
				);
		}
		error_log( 'line items: ' . print_r( $line_items, TRUE ) );
		return $line_items;
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

	protected static function csv_to_array( $filename = '', $delimiter = ',', $fieldnames = '' ) {
		if( !file_exists( $filename ) || !is_readable( $filename ) ){
			return FALSE;
		}
		if( strlen( $fieldnames ) > 0 ) {
			$header = explode( ",", $fieldnames );
		} else {
			$header = NULL;
		}
		$data = array();
		if ( ( $handle = fopen( $filename, 'r' ) ) !== FALSE ) {
			while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
				if(!$header)
					$header = $row;
				else
					$data[] = array_combine( $header, $row );
			}
			fclose( $handle );
		}
		return $data;
	}

}
SI_CSV_Import::register();
