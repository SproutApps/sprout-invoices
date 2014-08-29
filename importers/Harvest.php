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

	// Meta
	const HARVEST_ID = '_harvest_id';

	private static $harvest_user;
	private static $harvest_pass;
	private static $harvest_account;
	private static $importing_archived;

	public static function init() {
		// Settings
		self::$harvest_user = get_option( self::HARVEST_USER_OPTION, '' );
		self::$harvest_pass = get_option( self::HARVEST_PASS_OPTION, '' );
		self::$harvest_account = get_option( self::HARVEST_ACCOUNT_OPTION, '' );
		self::register_payment_settings();
		self::save_options();

		// Maybe process import
		self::maybe_process_import();
	}

	public static function register() {
		self::add_importer( __CLASS__, self::__( 'Harvest' ) );
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
				'tab' => self::get_settings_page( FALSE ),
				'settings' => array(
					self::HARVEST_USER_OPTION => array(
						'label' => self::__( 'User' ),
						'option' => array(
							'type' => 'text',
							'default' => self::$harvest_user,
							'attributes' => array( 'placeholder' => self::__(
								'user@gmail.com') ),
							'description' => self::__( '' ),
						)
					),
					self::HARVEST_PASS_OPTION => array(
						'label' => self::__( 'Password' ),
						'option' => array(
							'type' => 'password',
							'default' => self::$harvest_pass,
							'attributes' => array( 'placeholder' => self::__(
								'password') ),
							'description' => self::__( '' )
						)
					),
					self::HARVEST_ACCOUNT_OPTION => array(
						'label' => self::__( 'Domain' ),
						'option' => array(
							'type' => 'text',
							'default' => self::$harvest_account,
							'attributes' => array( 'placeholder' => self::__(
								'domain/account') ),
							'description' => self::__( '' )
						)
					),
					self::PROCESS_ARCHIVED => array(
						'label' => self::__( 'Import Archived' ),
						'option' => array(
							'type' => 'checkbox',
							'value' => 'archived',
							'label' => '',
							'description' => self::__( '' )
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
		if ( isset( $_POST[self::HARVEST_USER_OPTION] ) && $_POST[self::HARVEST_USER_OPTION] != '') {
			self::$harvest_user = $_POST[self::HARVEST_USER_OPTION];
			update_option( self::HARVEST_USER_OPTION, $_POST[self::HARVEST_USER_OPTION] );
		}
		if ( isset( $_POST[self::HARVEST_PASS_OPTION] ) && $_POST[self::HARVEST_PASS_OPTION] != '') {
			self::$harvest_pass = $_POST[self::HARVEST_PASS_OPTION];
			update_option( self::HARVEST_PASS_OPTION, $_POST[self::HARVEST_PASS_OPTION] );
		}
		if ( isset( $_POST[self::HARVEST_ACCOUNT_OPTION] ) && $_POST[self::HARVEST_ACCOUNT_OPTION] != '') {
			self::$harvest_account = $_POST[self::HARVEST_ACCOUNT_OPTION];
			update_option( self::HARVEST_ACCOUNT_OPTION, $_POST[self::HARVEST_ACCOUNT_OPTION] );
		}
	}

	/**
	 * Check to see if it's time to start the import process.
	 * @return  
	 */
	public static function maybe_process_import() {
		if ( isset( $_POST[self::PROCESS_ACTION] ) && wp_verify_nonce( $_POST[self::PROCESS_ACTION], self::PROCESS_ACTION ) ) {
			add_filter( 'si_show_importer_settings', '__return_false' );
			add_action( 'si_import_progress', array( __CLASS__, 'init_import_show_progress' ) );
		}
	}

	/**
	 * Import archived data
	 * @return bool
	 */
	public static function import_archived_data() {
		self::$importing_archived = ( isset( $_POST[self::PROCESS_ARCHIVED] ) && $_POST[self::PROCESS_ARCHIVED] == 'archived' ) ? TRUE : FALSE ;
		return self::$importing_archived;
	}

	/**
	 * Start the import process
	 */
	public static function init_import_show_progress() {
		$auth_error = FALSE;
		require_once SI_PATH . '/importers/lib/harvest/HarvestAPI.php';
		spl_autoload_register( array( 'HarvestAPI', 'autoload' ) );
		$api = new HarvestAPI();
		$api->setUser( self::$harvest_user );
		$api->setPassword( self::$harvest_pass );
		$api->setAccount( self::$harvest_account );
		$api->setRetryMode( HarvestAPI::RETRY );
		$api->setSSL( true );

		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );

		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		set_time_limit( 0 ); // run script forever

		echo '<script language="javascript">document.getElementById("patience").className="updated";</script>';

		self::update_progress_info( 'com', 0, 0, 10, self::__('Attempting to authentic API connection...') );

		///////////////
		// Clients //
		///////////////
		if ( !in_array( 'clients', $progress ) ) {

			self::update_progress_info( 'com', 0, 0, 20, self::__('Attempting to get your Harvest clients...') );

			$result = $api->getClients();
			if( $result->isSuccess() ) {
				$i = 0;
				$total = count( $result->data );
				if ( $total ) {
					foreach ( $result->data as $client_id => $client ) {
						$i++;
						self::update_progress_info( 'clients', $i, $total );
						self::create_client( $client );
					}
				}
				else {
					$auth_error = TRUE;
					self::update_progress_info( 'clients', 0, 0, 100, self::__('No clients imported.') );
				}
			}
		}
		else {
			self::update_progress_info( 'clients', 0, 0, 100, self::__('Clients already imported.') );
		}
		$progress[] = 'clients';
		update_option( self::PROGRESS_OPTION, $progress );

		//////////////
		// Contacts //
		//////////////
		if ( !in_array( 'contacts', $progress ) ) {
			// Just in case the role wasn't already added
			add_role( SI_Client::USER_ROLE, self::__('Client'), array( 'read' => true, 'level_0' => true ) );

			self::update_progress_info( 'com', 0, 0, 45, self::__('Attempting to get your Harvest contacts...') );

			// Contacts
			$result = $api->getContacts();
			if( $result->isSuccess() ) {
				$i = 0;
				$total = count( $result->data );
				if ( $total ) {
					foreach ( $result->data as $contact_id => $contact ) {
						$i++;
						self::update_progress_info( 'contacts', $i, $total );
						self::create_contact( $contact );
					}
				}
				else {
					$auth_error = TRUE;
					self::update_progress_info( 'contacts', 0, 0, 100, self::__('No contacts imported.') );
				}
			}
		}
		else {
			self::update_progress_info( 'contacts', 0, 0, 100, self::__('Contacts already imported.') );
		}
		$progress[] = 'contacts';
		update_option( self::PROGRESS_OPTION, $progress );

		///////////////
		// Estimates //
		///////////////
		self::update_progress_info( 'com', 0, 0, 60, self::__('Attempting to get your Harvest estimates...') );
		self::update_progress_info( 'estimates', 0, 0, 100, self::__('Harvest API does not permit access to your estimates.') );

		$progress[] = 'estimates';
		update_option( self::PROGRESS_OPTION, $progress );

		//////////////
		// Invoices //
		//////////////
		
		self::update_progress_info( 'com', 0, 0, 65, self::__('Attempting to get your Harvest invoices...') );

		// total invoice count, updated ASAP
		$total = 0;
		// total invoices processed 
		$i = 0;
		// Current page on invoices imported
		$progress_count = array_count_values( $progress );
		$api_page = ( isset( $progress_count['invoices_page'] ) ) ? $progress_count['invoices_page'] : 1 ;

		if ( $api_page > 1 ) {
			// Progress message
			self::update_progress_info( 'invoices', 0, 0, 80, self::__('Some invoices were imported already and the importer will try to start where it left off.') );
			sleep(1);
		}

		for ( $api_page; $api_page < 100; $api_page++ ) {
			$filter = new Harvest_Invoice_Filter();
     		$filter->set( 'page', $api_page );
			$result = $api->getInvoices( $filter );
			if( $result->isSuccess() ) {
				// count of total payments
				$payments_total = 0;
				// update the total invoices
				$total += count( $result->data );

				if ( count( $result->data ) == 0 ) {
					if ( $api_page == 1 ) { // first attempt
						$auth_error = TRUE;
						self::update_progress_info( 'invoices', 0, 0, 100, self::__('No invoices imported.') );
					}
					else {
						self::update_progress_info( 'invoices', 0, 0, 100, self::__('No new invoices imported.') );
					}
					$api_page = 101;
					break;
				}

				foreach ( $result->data as $invoice_id => $invoice ) {
					$i++;
					self::update_progress_info( 'invoices', $i, $total );
					$new_invoice = self::create_invoice( $invoice );

					if ( is_a( $new_invoice, 'SI_Invoice' ) ) {

						////////////////
						// Line Items //
						////////////////
						$result = $api->getInvoice( $invoice_id );
						if( $result->isSuccess() ) {
							self::add_invoice_line_items( $result->data, $new_invoice );
						}
						
						
						//////////////
						// Payments //
						//////////////
						$result = $api->getInvoicePayments( $invoice_id );
						if( $result->isSuccess() ) {
							$p = 0;
							$ptotal = count( $result->data );
							foreach ( $result->data as $payment_id => $payment ) {
								$p++;
								$payments_total++;
								$ppercent = intval($p/$ptotal * 100);
								$message = sprintf( self::__('%o payments of %o imported for %s.'), $p, $ptotal, $new_invoice->get_title() );
								self::update_progress_info( 'payments', $p, $payments_total, $ppercent, $message );

								self::create_invoice_payment( $payment, $new_invoice );
							}
						}
					}	
				}
			}
			$progress[] = 'invoices_page';
			update_option( self::PROGRESS_OPTION, $progress );
		}

		////////////////////////
		// Payments messaging //
		////////////////////////
		
		$message = sprintf( self::__('%s Payments imported.' ), $payments_total );
		self::update_progress_info( 'payments', $p, $payments_total, 100, $message );

		//////////////
		// All done //
		//////////////
		self::update_progress_info( 'com', 0, 0, 100, self::__('API connection closed.') );
		echo '<script language="javascript">document.getElementById("complete_import").className="";</script>';

		if ( $auth_error ) {
			echo '<script language="javascript">document.getElementById("auth_patience").className="error";</script>';
		}

	}

	public static function create_client( Harvest_Client $client ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::HARVEST_ID => $client->id ) );
		// Don't create a duplicate if this was already imported.
		if ( !empty( $possible_dups ) ) {
			do_action( 'si_error', 'Client imported already', $client->id );
			return;
		}
		if ( !self::$importing_archived && $client->active == 'false' ) {
			return;
		}
		$args = array(
			'company_name' => $client->name,
			'currency' => substr( $client->currency, -3 ),
			'address' => array( 'street' => $client->details )
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
		if ( !is_a( $client, 'SI_Client' ) ) {
			return;
		}
		$args = array(
			'user_login' => $contact->email,
			'display_name' => $client->get_title(),
			'user_email' => $contact->email,
			'first_name' => $contact->first_name,
			'last_name' => $contact->last_name
		);
		$user_id = SI_Clients::create_user( $args );
		update_usermeta( $user_id, self::HARVEST_ID, $contact->id );
		update_usermeta( $user_id, self::USER_META_TITLE, $contact->title );
		update_usermeta( $user_id, self::USER_META_PHONE, $contact->phone_mobile );
		update_usermeta( $user_id, self::USER_META_OFFICE_PHONE, $contact->phone_office );

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
		if ( !empty( $possible_dups ) ) {
			do_action( 'si_error', 'Invoice imported already', $invoice->id );
			return;
		}
		$clients = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::HARVEST_ID => $invoice->client_id ) );
		// Get client and confirm it's validity
		$client = SI_Client::get_instance( $clients[0] );
		$client_id = ( is_a( $client, 'SI_Client' ) ) ? $client->get_id() : 0 ;

		$args = array(
			'subject' => ( $invoice->subject ) ? $invoice->subject : 'Harvest Import #' . $invoice->id
		);
		$inv_id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_TEMP );
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
			self::__('Invoice Imported'), // title
			0 // user id
			);

		return $inv;
	}

	public static function add_invoice_line_items( Harvest_Invoice $invoice, SI_Invoice $new_invoice ) {
		// Format the csv string to compensate for bad formatting and missing values.
		$csv_string = $invoice->csv_line_items;
		// Create rows for each line item. Making sure to not break with wrapped line items.
		// https://bugs.php.net/bug.php?id=55763
		$lines = preg_split('/[\r\n]{1,2}(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/', $csv_string );

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
		if ( !empty( $possible_dups ) ) {
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
						)
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

	public static function update_progress_info( $context = 'contacts', $i = 0, $total_records = 0, $percent = 0, $messaging = '' ) {
		if ( !$percent ) {
			$percent = intval($i/$total_records * 100);
		}
		if ( $messaging == '' ) {
			$messaging = sprintf( self::__('%o %s of %o imported.'), $i, $context, $total_records );
		}
		echo '<script language="javascript">document.getElementById("progress_js").remove();</script><span id="progress_js"><script language="javascript">
			document.getElementById("'.$context.'_import_progress").innerHTML="<div style=\"width:'.$percent.'%;background-color:#ddd;\">&nbsp;</div>";
			document.getElementById("'.$context.'_import_information").innerHTML="'.$messaging.'";
			</script></span>';
		flush();
	}

	protected static function csv_to_array( $csv, $delimiter = ',', $enclosure = '', $escape = '\\', $terminator = "\n") { 
		$r = array(); 
		$rows = explode($terminator,trim($csv)); 
		$names = array_shift($rows); 
		$names = str_getcsv($names,$delimiter,$enclosure,$escape); 
		$nc = count($names); 
		foreach ($rows as $row) { 
			if (trim($row)) { 
				$values = str_getcsv($row,$delimiter,$enclosure,$escape); 
				if (!$values) $values = array_fill(0,$nc,null); 
				$r[] = array_combine($names,$values); 
			} 
		} 
		return $r; 
	} 

}
SI_Harvest_Import::register();
