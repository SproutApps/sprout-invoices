<?php

/**
 * WPInvoice Importer
 *
 * @package Sprout_Invoice
 * @subpackage Importers
 */
class SI_WPInvoice_Import extends SI_Importer {
	const SETTINGS_PAGE = 'import';
	const PROCESS_ACTION = 'start_import';
	const DELETE_WPINVOICE_DATA = 'import_archived';
	const PAYMENT_METHOD = 'WPInvoice Imported';
	const PROGRESS_OPTION = 'current_import_progress_wpinvoice';

	// Meta
	const WPINVOICE_ID = '_wpinvoice_id';

	private static $wpinvoice_delete;

	public static function init() {
		// Settings
		self::$wpinvoice_delete = get_option( self::DELETE_WPINVOICE_DATA, '' );
		self::register_payment_settings();
		self::save_options();

		// Maybe process import
		self::maybe_process_import();
	}

	public static function register() {
		self::add_importer( __CLASS__, self::__( 'WP-Invoice' ) );
	}


	/**
	 * Register the payment settings
	 * @return  
	 */
	public static function register_payment_settings() {
		// Settings
		$settings = array(
			'si_wpinvoice_importer_settings' => array(
				'title' => 'WPInvoice Import Settings',
				'weight' => 0,
				'tab' => self::get_settings_page( FALSE ),
				'settings' => array(
					self::DELETE_WPINVOICE_DATA => array(
						'label' => self::__( 'Delete WP-Invoices' ),
						'option' => array(
							'type' => 'checkbox',
							'value' => 'remove',
							'label' => 'Cleanup some WP-Invoice during the import.',
							'description' => self::__( 'You must really love us to delete those WP-Invoices, since you can\'t go back. Settings and the log table (sigh) will be kept.' )
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
		// no options to save
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
	public static function delete_wpinvoice_data() {
		self::$wpinvoice_delete = ( isset( $_POST[self::DELETE_WPINVOICE_DATA] ) && $_POST[self::DELETE_WPINVOICE_DATA] == 'remove' ) ? TRUE : FALSE ;
		return self::$wpinvoice_delete;
	}

	/**
	 * Start the import process
	 */
	public static function init_import_show_progress() {
		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, array() );
		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		// run script forever
		set_time_limit( 0 );

		self::update_progress_info( 'com', 0, 0, 10, self::__('Looking for any WP-Invoices...') );

		$args = array(
				'post_type' => 'wpi_object', // why object? I don't get it either.
				'post_status' => 'active',
				'posts_per_page' => -1,
				'fields' => 'ids'
			);

		$wp_invoice_ids = get_posts( $args );

		if ( empty( $wp_invoice_ids ) ) {
			self::update_progress_info( 'com', 0, 0, 100, self::__('We couldn\'t fine any WP-Invoices to import.') );
			self::update_progress_info( 'clients', 0, 0, 100, self::__('Skipped.') );
			self::update_progress_info( 'contacts', 0, 0, 100, self::__('Skipped.') );
			self::update_progress_info( 'estimates', 0, 0, 100, self::__('Skipped.') );
			self::update_progress_info( 'invoices', 0, 0, 100, self::__('Skipped.') );
			self::update_progress_info( 'payments', 0, 0, 100, self::__('Skipped.') );
			echo '<script language="javascript">document.getElementById("complete_import").className="";</script>';
		}

		if ( !class_exists( 'WPI_Invoice' ) ) {
			self::update_progress_info( 'com', 0, 0, 100, self::__('WP-Invoices needs to be activated before proceeding.') );
			self::update_progress_info( 'clients', 0, 0, 100, self::__('Incomplete.') );
			self::update_progress_info( 'contacts', 0, 0, 100, self::__('Incomplete.') );
			self::update_progress_info( 'estimates', 0, 0, 100, self::__('Incomplete.') );
			self::update_progress_info( 'invoices', 0, 0, 100, self::__('Incomplete.') );
			self::update_progress_info( 'payments', 0, 0, 100, self::__('Incomplete.') );
		}


		$clients_tally = 0;
		$contacts_tally = 0;
		$invoices_tally = 0;
		$payments_tally = 0;
		$invoices_total = count( $wp_invoice_ids );

		self::update_progress_info( 'clients', $clients_tally, $invoices_total );
		self::update_progress_info( 'contacts', $contacts_tally, $invoices_total );
		self::update_progress_info( 'invoices', $invoices_tally, $invoices_total );
		self::update_progress_info( 'estimates', 0, 0, 100, self::__('WP-Invoices doesn\'t support estimates.') );
		self::update_progress_info( 'payments', $payments_tally, $invoices_total, 0, sprintf( self::__('Created %o payments from %o invoices.') , $payments_tally, $invoices_total ) );

		foreach ( $wp_invoice_ids as $wp_invoice_id ) {
			$wp_invoice = new WPI_Invoice();
			$wp_invoice = $wp_invoice->load_invoice( array( 'id' => $wp_invoice_id, 'return' => TRUE ) );
			if ( $wp_invoice['type'] != 'invoice' ) {
				continue;
			}
			// prp($wp_invoice);
			// continue;
			/////////////
			// Clients //
			/////////////
			usleep(10000);
			$clients_tally++;
			self::update_progress_info( 'com', 0, 0, 20, self::__('Attempting to import new clients from what WP-Invoice stores...') );
			$new_client_id = self::create_client( $wp_invoice );
			self::update_progress_info( 'clients', $clients_tally, $invoices_total );

			//////////////
			// Contacts //
			//////////////
			usleep(10000);
			// Just in case the role wasn't already added
			add_role( SI_Client::USER_ROLE, self::__('Client'), array( 'read' => true, 'level_0' => true ) );
			$contacts_tally++;
			self::update_progress_info( 'com', 0, 0, 40, self::__('Attempting to convert wp-invoice users to clients...') );
			self::create_contact( $wp_invoice, $new_client_id );
			self::update_progress_info( 'contacts', $contacts_tally, $invoices_total );

			//////////////
			// Invoices //
			//////////////
			usleep(10000);
			$invoices_tally++;
			self::update_progress_info( 'com', 0, 0, 60, self::__('Attempting to import invoices...') );
			$new_invoice = self::create_invoice( $wp_invoice, $new_client_id );
			self::update_progress_info( 'invoices', $invoices_tally, $invoices_total );

			//////////////
			// Payments //
			//////////////
			usleep(10000);
			self::update_progress_info( 'com', 0, 0, 80, self::__('Attempting to import payments...') );
			if ( !empty( $wp_invoice['log'] ) ) {
				foreach ( $wp_invoice['log'] as $key => $event ) {
					if ( $event['attribute'] == 'balance' ) {
						$payments_tally++;
						self::create_invoice_payment( $event, $new_invoice );
						self::update_progress_info( 'payments', $payments_tally, $invoices_total, 100, sprintf( self::__('Created %o payment(s) from %o invoices.') , $payments_tally, $invoices_total ) );
					}
				}
			}
			else {
				self::update_progress_info( 'com', 0, 0, 80, self::__('No payments were found.') );
			}
			
			usleep(10000);
			if ( self::delete_wpinvoice_data() ) {
				printf( 'Deleting WP-Invoice: %s', esc_attr($wp_invoice['post_title']) );
				//wp_delete_post( $invoice_id, TRUE );
			}

		}

		//////////////
		// All done //
		//////////////
		self::update_progress_info( 'com', 0, 0, 100, self::__('Finished importing...') );
		echo '<script language="javascript">document.getElementById("complete_import").className="";</script>';

	}

	public static function create_client( $wp_invoice = array() ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::WPINVOICE_ID => $wp_invoice['ID'] ) );
		// Don't create a duplicate if this was already imported.
		if ( !empty( $possible_dups ) ) {
			do_action( 'si_error', 'Client imported already', $wp_invoice['ID'] );
			return;
		}
		$wp_invoice_user_data = $wp_invoice['user_data'];
		// args to create new client
		$address = array(
			'street' => isset( $wp_invoice_user_data['streetaddress'] ) ? self::esc__( $wp_invoice_user_data['streetaddress']) : '',
			'city' => isset( $wp_invoice_user_data['city'] ) ? self::esc__($wp_invoice_user_data['city']) : '',
			'zone' => isset( $wp_invoice_user_data['state'] ) ? self::esc__($wp_invoice_user_data['state']) : '',
			'postal_code' => isset( $wp_invoice_user_data['zip'] ) ? self::esc__($wp_invoice_user_data['zip']) : '',
			'country' => isset( $wp_invoice_user_data['country'] ) ? self::esc__($wp_invoice_user_data['country']) : 'US',
		);
		$args = array(
			'address' => $address,
			'company_name' => ( isset( $wp_invoice_user_data['company_name'] ) ) ? $wp_invoice_user_data['company_name'] : '',
			'website' => ( isset( $wp_invoice_user_data['user_url'] ) ) ? $wp_invoice_user_data['user_url'] : '',
			'currency' => ( isset( $wp_invoice_user_data['default_currency_code'] ) ) ? $wp_invoice_user_data['default_currency_code'] : '',
		);
		if ( $args['company_name'] == '' ) {
			if ( is_array( $wp_invoice_user_data['first_name'] ) || is_array( $wp_invoice_user_data['last_name'] ) ) {
				do_action( 'si_error', 'Client creation error', $wp_invoice );
				return;
			}
			$args['company_name'] = $wp_invoice_user_data['first_name'] . ' ' . $wp_invoice_user_data['last_name'];
		}
		$client_id = SI_Client::new_client( $args );
		// create import record
		update_post_meta( $client_id, self::WPINVOICE_ID, $wp_invoice['ID'] );
		return $client_id;
	}

	public static function create_contact( $wp_invoice = array(), $client_id = 0 ) {
		$wp_invoice_user_data = $wp_invoice['user_data'];
		$user_id = $wp_invoice_user_data['ID'];
		if ( $user_id ) {
			// Attempt to convert the wp-invoice user to a client if currently a subscriber.
			if ( !user_can( $user_id, 'edit_posts' ) ) {
				wp_update_user( array( 'ID' => $user_id, 'role' => SI_Client::USER_ROLE ) );
			}
			// Get client and confirm it's validity
			$client = SI_Client::get_instance( $client_id );
			if ( !is_a( $client, 'SI_Client' ) ) {
				return;
			}
			// Assign user to new client.
			$client->add_associated_user( $user_id );
		}
		return $user_id;
	}

	public static function create_estimate( $estimate = array() ) {
		return;
	}

	public static function create_invoice( $wp_invoice = array(), $client_id = 0 ) {
		// Don't create a duplicate if this was already imported.
		$possible_dups = SI_Post_Type::find_by_meta( SI_Invoice::POST_TYPE, array( self::WPINVOICE_ID => $wp_invoice['ID'] ) );
		if ( !empty( $possible_dups ) ) {
			do_action( 'si_error', 'Invoice imported already', $wp_invoice['ID'] );
			return;
		}
		// Get client
		if ( !$client_id ) {
			$clients = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::WPINVOICE_ID => $wp_invoice['client_id'] ) );
			// Get client and confirm it's validity
			$client = SI_Client::get_instance( $clients[0] );
			$client_id = ( is_a( $client, 'SI_Client' ) ) ? $client->get_id() : 0 ;
		}

		$args = array(
			'subject' => ( $wp_invoice['post_title'] ) ? $wp_invoice['post_title'] : 'WPInvoice Import #' . $wp_invoice['ID']
		);
		$new_invoice_id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_TEMP );
		update_post_meta( $new_invoice_id, self::WPINVOICE_ID, $wp_invoice['invoice_id'] );

		$invoice = SI_Invoice::get_instance( $new_invoice_id );
		$invoice->set_client_id( $client_id );
		if ( isset( $wp_invoice['invoice_id'] ) ) {
			$invoice->set_invoice_id( $wp_invoice['invoice_id'] );
		}
		if ( isset( $wp_invoice['subtotal'] ) ) {
			$invoice->set_total( $wp_invoice['subtotal'] );
		}
		if ( isset( $wp_invoice['deposit_amount'] ) ) {
			$invoice->set_deposit( $wp_invoice['deposit_amount'] );
		}
		if ( isset( $wp_invoice['default_currency_code'] ) ) {
			$invoice->set_currency( $wp_invoice['default_currency_code'] );
		}
		if ( isset( $wp_invoice['custom_id'] ) ) {
			$invoice->set_po_number( $wp_invoice['custom_id'] );
		}
		if ( isset( $wp_invoice['total_discount'] ) ) {
			$invoice->set_discount( $wp_invoice['total_discount'] );
		}
		if ( isset( $wp_invoice['post_content'] ) ) {
			$invoice->set_notes( $wp_invoice['post_content'] );
		}
		$invoice->set_issue_date( strtotime( $wp_invoice['due_date_day'].'-'.$wp_invoice['due_date_month'].'-'.$wp_invoice['due_date_year'] ) );
		// post date
		$invoice->set_post_date( date( 'Y-m-d H:i:s', strtotime( $wp_invoice['post_date'] ) ) );

		// line items
		$line_items = array();
		if ( isset( $wp_invoice['itemized_list'] ) && !empty( $wp_invoice['itemized_list'] ) ) {
			foreach ( $wp_invoice['itemized_list'] as $key => $item ) {
				$line_items[] = array( 
					'rate' => ( isset( $item['price'] ) ) ? $item['price'] : '',
					'qty' => ( isset( $item['quantity'] ) ) ? $item['quantity'] : '',
					'desc' => ( $item['description'] == '' ) ? $item['name'] : '<strong>'.$item['name'].'</strong><br/>'.$item['description'],
					'type' => '',
					'total' => ( isset( $item['line_total_after_tax'] ) ) ? $item['line_total_after_tax'] : '',
					'tax' => ( isset( $item['tax_rate'] ) ) ? $item['tax_rate'] : '',
					);
			}
		}
		// I don't know what itemized charges could possibly be used for but they can be items.
		if ( isset( $wp_invoice['itemized_charges'] ) && !empty( $wp_invoice['itemized_charges'] ) ) {
			foreach ( $wp_invoice['itemized_charges'] as $key => $item ) {
				$line_items[] = array( 
					'rate' => ( isset( $item['amount'] ) ) ? $item['amount'] : '',
					'qty' => 1,
					'desc' => ( isset( $item['name'] ) ) ? $item['name'] : '',
					'type' => '',
					'total' => ( isset( $item['after_tax'] ) ) ? $item['after_tax'] : '',
					'tax' => ( isset( $item['tax'] ) ) ? $item['tax'] : '',
					);
			}
		}
		$invoice->set_line_items( $line_items );
		// Records
		if ( !empty( $wp_invoice['log'] ) ) {
			foreach ( $wp_invoice['log'] as $key => $event ) {
				if ( $event['attribute'] == 'notification' ) { // payments are added separately
					do_action( 'si_new_record', 
							self::__('Notification content was not stored by WP-Invoice.'), // content
							SI_Notifications::RECORD, // type slug
							$new_invoice_id, // post id
							$event['text'], // title
							0, // user id
							FALSE // don't encode
							);
				}
			}
		}
		do_action( 'si_new_record', 
			$wp_invoice, // content
			self::RECORD, // type slug
			$new_invoice_id, // post id
			self::__('Invoice Imported'), // title
			0 // user id
			);
		return $invoice;
	}

	public static function create_invoice_payment( $payment = array(), SI_Invoice $invoice ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Payment::POST_TYPE, array( self::WPINVOICE_ID => $payment['ID'] ) );
		// Don't create a duplicate if this was already imported.
		if ( !empty( $possible_dups ) ) {
			do_action( 'si_error', 'Invoice imported already', $payment['ID'] );
			return;
		}

		$payment_id = SI_Payment::new_payment( array(
				'payment_method' => ( isset( $payment['action'] ) ) ? self::PAYMENT_METHOD . ' :: ' . $payment['action'] : self::PAYMENT_METHOD,
				'invoice' => $invoice->get_id(),
				'amount' => $payment['value'],
				'transaction_id' => ( isset( $payment['ID'] ) ) ? $payment['object_id'] . '::' . $payment['ID'] : '',
				'data' => array(
					'api_response' => $payment
				),
			) );
		$new_payment = SI_Payment::get_instance( $payment_id );
		$new_payment->set_post_date( date( 'Y-m-d H:i:s', $payment['time'] ) );
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
		echo '<span id="progress_js"><script language="javascript">
			document.getElementById("'.$context.'_import_progress").innerHTML="<div style=\"width:'.$percent.'%;background-color:#ddd;\">&nbsp;</div>";
			document.getElementById("'.$context.'_import_information").innerHTML="'.$messaging.'";
			document.getElementById("progress_js").remove();
			</script></span>';
		flush();
	}

}
SI_WPInvoice_Import::register();
