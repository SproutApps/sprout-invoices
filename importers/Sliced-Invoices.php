<?php

/**
 * Sliced Invoices Importer
 *
 * @package Sprout_Invoice
 * @subpackage Importers
 */
class SI_Sliced_Invoices_Import extends SI_Importer {
	const SETTINGS_PAGE = 'import';
	const PROCESS_ACTION = 'start_import';
	const DELETE_SLICEDINVOICES_DATA = 'import_archived';
	const PAYMENT_METHOD = 'Sliced Invoices Imported';
	const PROGRESS_OPTION = 'current_import_progress_slicedinvoices_v1';

	// Meta
	const SLICEDINVOICES_ID = '_slicedinvoices_id';

	private static $slicedinvoices_delete;

	public static function init() {
		// Settings
		self::$slicedinvoices_delete = get_option( self::DELETE_SLICEDINVOICES_DATA, '' );
		self::register_payment_settings();
		self::save_options();

		// Maybe process import
		self::maybe_process_import();
	}

	public static function register() {
		self::add_importer( __CLASS__, __( 'Sliced Invoices', 'sprout-invoices' ) );
	}


	/**
	 * Register the payment settings
	 * @return
	 */
	public static function register_payment_settings() {
		// Settings
		$settings = array(
			'si_slicedinvoices_importer_settings' => array(
				'title' => 'Sliced Invoices Import Settings',
				'weight' => 0,
				'tab' => self::get_settings_page( false ),
				'settings' => array(
					self::DELETE_SLICEDINVOICES_DATA => array(
						'label' => __( 'Delete Sliced Invoices', 'sprout-invoices' ),
						'option' => array(
							'type' => 'checkbox',
							'value' => 'remove',
							'label' => 'Cleanup Sliced Invoices during the import.',
							'description' => __( 'You must really love us to delete those Sliced Invoices, since you can\'t go back. Settings and the log table (sigh) will be kept.', 'sprout-invoices' ),
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
		// For testing
		delete_option( self::PROGRESS_OPTION );
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
	public static function delete_slicedinvoices_data() {
		self::$slicedinvoices_delete = ( isset( $_POST[ self::DELETE_SLICEDINVOICES_DATA ] ) && $_POST[ self::DELETE_SLICEDINVOICES_DATA ] == 'remove' ) ? true : false ;
		return self::$slicedinvoices_delete;
	}

	/**
	 * First step in the import progress
	 * @return
	 */
	public static function import_authentication() {

		if ( ! class_exists( 'Sliced_Invoices' ) ) {
			self::return_error( __( 'Sliced Invoices needs to be activated before proceeding.', 'sprout-invoices' ) );
		}

		$args = array(
				'post_type' => 'sliced_invoice',
				'post_status' => 'any',
				'posts_per_page' => -1,
				'fields' => 'ids',
			);

		$sliced_invoices_ids = get_posts( $args );

		if ( empty( $sliced_invoices_ids ) ) {
			self::return_error( __( 'We couldn\'t fine any Sliced Invoices to import.', 'sprout-invoices' ) );
		}

		$progress_tally = array();
		$progress_tally['clients_tally'] = 0;
		$progress_tally['contacts_tally'] = 0;
		$progress_tally['invoices_tally'] = 0;
		$progress_tally['payments_tally'] = 0;
		$progress_tally['invoices_total'] = count( $sliced_invoices_ids );
		$progress_tally['total_records'] = count( $sliced_invoices_ids );
		update_option( self::PROGRESS_OPTION.'tally', $progress_tally );

		$total_records = count( $sliced_invoices_ids );
		self::return_progress( array(
			'authentication' => array(
			'message' => sprintf( __( 'Preparing to import from %s invoices...', 'sprout-invoices' ), $total_records ),
			'progress' => 90,
			),
			'clients' => array(
			'message' => sprintf( __( 'Importing clients from %s Sliced Invoices records...', 'sprout-invoices' ), $total_records ),
			'progress' => 10,
			),
			'contacts' => array(
			'message' => sprintf( __( 'Importing contacts from %s Sliced Invoices records...', 'sprout-invoices' ), $total_records ),
			'progress' => 10,
			),
			'estimates' => array(
			'message' => sprintf( __( 'No estimates will be imported, unfortunately...', 'sprout-invoices' ), $total_records ),
			'progress' => 0,
			),
			'invoices' => array(
			'message' => sprintf( __( 'Importing invoices from %s Sliced Invoices records...', 'sprout-invoices' ), $total_records ),
			'progress' => 10,
			),
			'payments' => array(
			'message' => sprintf( __( 'Importing payments from %s Sliced Invoices records...', 'sprout-invoices' ), $total_records ),
			'progress' => 10,
			'next_step' => 'invoices',
			),
		) );

	}

	public static function import_invoices() {
		// Store the import progress
		$progress = get_option( self::PROGRESS_OPTION, 1 );
		$progress_tally = get_option( self::PROGRESS_OPTION.'tally', array() );

		// Suppress notifications
		add_filter( 'suppress_notifications', '__return_true' );

		// run script forever
		set_time_limit( 0 );

		$args = array(
				'post_type' => 'sliced_invoice', // why object? I don't get it either.
				'post_status' => 'any',
				'posts_per_page' => 25,
				'offset' => ( $progress * 25 ) - 25,
				'fields' => 'ids',
			);

		$sliced_invoices_ids = get_posts( $args );

		if ( empty( $progress_tally ) ) {
			$progress_tally['clients_tally'] = 0;
			$progress_tally['contacts_tally'] = 0;
			$progress_tally['invoices_tally'] = 0;
			$progress_tally['payments_tally'] = 0;
			$progress_tally['invoices_total'] = count( $sliced_invoices_ids );
			$progress_tally['total_records'] = count( $sliced_invoices_ids );
		}

		if ( empty( $sliced_invoices_ids ) ) {

			//////////////
			// All done //
			//////////////

			self::return_progress( array(
				'authentication' => array(
				'message' => sprintf( __( 'Imported %s invoices!', 'sprout-invoices' ), $progress_tally['total_records'] ),
				'progress' => 100,
				),
				'clients' => array(
				'message' => sprintf( __( 'Importing %s clients from %s Sliced Invoices records...', 'sprout-invoices' ), $progress_tally['clients_tally'], $progress_tally['total_records'] ),
				'progress' => 100,
				),
				'contacts' => array(
				'message' => sprintf( __( 'Importing %s contacts from %s Sliced Invoices records...', 'sprout-invoices' ), $progress_tally['contacts_tally'], $progress_tally['total_records'] ),
				'progress' => 100,
				),
				'estimates' => array(
				'message' => __( 'No estimates were imported', 'sprout-invoices' ),
				'progress' => 100,
				),
				'invoices' => array(
				'message' => sprintf( __( 'Importing %s invoices from %s Sliced Invoices records...', 'sprout-invoices' ), $progress_tally['invoices_tally'], $progress_tally['total_records'] ),
				'progress' => 100,
				),
				'payments' => array(
				'message' => sprintf( __( 'Importing %s payments from %s Sliced Invoices records...', 'sprout-invoices' ), $progress_tally['payments_tally'], $progress_tally['total_records'] ),
				'progress' => 100,
				'next_step' => 'complete',
				),
			) );
		}

		foreach ( $sliced_invoices_ids as $sliced_invoice_id ) {

			$id = Sliced_Shared::get_item_id( $sliced_invoice_id );

			// prp($sliced_invoices);
			// continue;
			/////////////
			// Clients //
			/////////////
			$progress_tally['clients_tally']++;
			$new_client_id = self::create_client( $id );

			//////////////
			// Contacts //
			//////////////
			// Just in case the role wasn't already added
			SI_Client::client_role();
			$progress_tally['contacts_tally']++;
			self::create_contact( $sliced_invoice_id, $new_client_id );

			//////////////
			// Invoices //
			//////////////
			$progress_tally['invoices_tally']++;
			$new_invoice = self::create_invoice( $sliced_invoice_id, $new_client_id );

			//////////////
			// Payments //
			//////////////
			$payments = Sliced_Shared::get_payments( $sliced_invoice_id );
			if ( ! empty( $payments ) ) {
				foreach ( $payments[0] as $payment ) {
					$progress_tally['payments_tally']++;
					self::create_invoice_payment( $payment, $new_invoice );
				}
			}

			if ( self::delete_slicedinvoices_data() ) {
				// printf( 'Deleting Sliced Invoices: %s', esc_attr( $sliced_invoices['post_title'] ) );
				// wp_delete_post( $sliced_invoice_id, true );
			}
		}

		update_option( self::PROGRESS_OPTION, $progress + 1 );
		update_option( self::PROGRESS_OPTION.'tally', $progress_tally );

		self::return_progress( array(
			'authentication' => array(
			'message' => sprintf( __( 'Preparing to import from %s invoices...', 'sprout-invoices' ), $progress_tally['total_records'] ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			),
			'clients' => array(
			'message' => sprintf( __( 'Importing %s clients from %s Sliced Invoices records...', 'sprout-invoices' ), $progress_tally['clients_tally'], $progress_tally['total_records'] ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			),
			'contacts' => array(
			'message' => sprintf( __( 'Importing %s contacts from %s Sliced Invoices records...', 'sprout-invoices' ), $progress_tally['contacts_tally'], $progress_tally['total_records'] ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			),
			'estimates' => array(
			'message' => __( 'No estimates were imported', 'sprout-invoices' ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			),
			'invoices' => array(
			'message' => sprintf( __( 'Importing %s invoices from %s Sliced Invoices records...', 'sprout-invoices' ), $progress_tally['invoices_tally'], $progress_tally['total_records'] ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			),
			'payments' => array(
			'message' => sprintf( __( 'Importing %s payments from %s Sliced Invoices records...', 'sprout-invoices' ), $progress_tally['payments_tally'], $progress_tally['total_records'] ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			'next_step' => 'invoices',
			),
		) );

	}

	public static function create_client( $sliced_invoice_id = 0 ) {

		$client = Sliced_Shared::get_client_details( $sliced_invoice_id );

		$client_id = $client['id'];

		$possible_dups = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::SLICEDINVOICES_ID => $client_id ) );
		// Don't create a duplicate if this was already imported.
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Client imported already', $client_id );
			return $possible_dups[0];
		}

		// args to create new client
		$address = array(
			'street' => isset( $client['address'] ) ? esc_html( $client['address'] ) : '',
			'city' => isset( $client['city'] ) ? esc_html( $client['city'] ) : '',
			'zone' => isset( $client['state'] ) ? esc_html( $client['state'] ) : '',
			'postal_code' => isset( $client['zip'] ) ? esc_html( $client['zip'] ) : '',
			'country' => isset( $client['country'] ) ? esc_html( $client['country'] ) : apply_filters( 'si_default_country_code', 'US' ),
		);
		$args = array(
			'company_name' => ( isset( $client['business'] ) ) ? $client['business'] : '',
			'website' => ( isset( $client['website'] ) ) ? $client['website'] : '',
			'address' => $address,
			'currency' => sliced_get_invoice_currency( $sliced_invoice_id ),
		);
		if ( $args['company_name'] == '' ) {
			if ( is_array( $client['first_name'] ) || is_array( $client['last_name'] ) ) {
				do_action( 'si_error', 'Client creation error', $sliced_invoices );
				return;
			}
			$args['company_name'] = $client['first_name'] . ' ' . $client['last_name'];
		}

		$si_client_id = SI_Client::new_client( $args );

		$record_id = SI_Internal_Records::new_record( $client['extra_info'], SI_Controller::PRIVATE_NOTES_TYPE, $si_client_id, '', 0, false );

		// create import record
		update_post_meta( $client_id, self::SLICEDINVOICES_ID, $client_id );
		return $client_id;
	}

	public static function create_contact( $sliced_invoice_id = 0, $si_client_id = 0 ) {
		// modify the current role
		$client = Sliced_Shared::get_client_details( $sliced_invoice_id );
		$user_id = wp_update_user( array( 'ID' => $client['id'], 'role' => SI_Client::USER_ROLE ) );
		return $user_id;
	}

	public static function create_estimate( $estimate = array() ) {
		return;
	}

	public static function create_invoice( $sliced_invoice_id = 0, $si_client_id = 0 ) {
		// Don't create a duplicate if this was already imported.
		$possible_dups = SI_Post_Type::find_by_meta( SI_Invoice::POST_TYPE, array( self::SLICEDINVOICES_ID => $sliced_invoice_id ) );
		if ( ! empty( $possible_dups ) ) {
			$invoice = SI_Invoice::get_instance( $possible_dups[0] );
			do_action( 'si_error', 'Invoice imported already', $sliced_invoice_id );
			return $invoice;
		}

		// Get client
		if ( ! $si_client_id ) {
			$clients = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::SLICEDINVOICES_ID => $sliced_invoice_id ) );
			// Get client and confirm it's validity
			$client = SI_Client::get_instance( $clients[0] );
			$client_id = ( is_a( $client, 'SI_Client' ) ) ? $client->get_id() : 0 ;
		}

		$args = array(
			'subject' => ( '' !== get_the_title( $sliced_invoice_id ) ) ? get_the_title( $sliced_invoice_id ) : 'Sliced Invoices Import #' . $sliced_invoice_id,
		);
		$new_invoice_id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_PENDING );
		update_post_meta( $new_invoice_id, self::SLICEDINVOICES_ID, $sliced_invoice_id );

		$invoice = SI_Invoice::get_instance( $new_invoice_id );
		$invoice->set_client_id( $si_client_id );
		$invoice->set_invoice_id( sliced_get_invoice_label( $sliced_invoice_id ) . ' ' . sliced_get_invoice_prefix( $sliced_invoice_id ) . sliced_get_invoice_number( $sliced_invoice_id ) . sliced_get_invoice_suffix( $sliced_invoice_id ) );
		$invoice->set_po_number( sliced_get_invoice_order_number( $sliced_invoice_id ) );
		$invoice->set_due_date( sliced_get_invoice_due( $sliced_invoice_id ) );
		$invoice->set_post_date( date( 'Y-m-d H:i:s', sliced_get_invoice_created( $sliced_invoice_id ) ) );

		switch ( sliced_get_invoice_status( $sliced_invoice_id ) ) {
			case 'success':
				$invoice->set_as_paid();
				break;
			case 'pending':
				$invoice->set_pending();
				break;
			case 'failed':
			case 'refunded':
			case 'cancelled':
				$invoice->set_as_written_off();
				break;
			default:
				$invoice->set_as_temp();
				break;
		}

		$invoice->set_deposit( sliced_get_invoice_deposit( $sliced_invoice_id ) );
		$invoice->set_total( sliced_get_invoice_total( $sliced_invoice_id ) );
		$invoice->set_notes( sliced_get_invoice_description( $sliced_invoice_id ) );
		$invoice->set_terms( sliced_get_invoice_terms( $sliced_invoice_id ) );

		error_log( 'line items: ' . print_r( sliced_get_invoice_line_items( $sliced_invoice_id ), true ) );

		$output = Sliced_Shared::get_totals( $id );

		// line items
		$line_items = array();
		foreach ( sliced_get_invoice_line_items( $sliced_invoice_id ) as $array_key => $item ) {
			$subtotal = $item['amount'] * $item['qty'];
			$line_items[] = array(
				'rate' => ( isset( $item['amount'] ) ) ? $item['amount'] : '',
				'qty' => ( isset( $item['qty'] ) ) ? $item['qty'] : '',
				'desc' => ( '' !== $item['description'] ) ? $item['description'] : '',
				'tax' => ( 'on' === $item['taxable'] ) ? $output['tax'] : '',
				'total' => ( $subtotal ) + ( ($output['tax'] / 100) * $subtotal ),
			);
		}

		$invoice->set_line_items( $line_items );

		do_action( 'si_new_record',
			$sliced_invoice_id, // content
			self::RECORD, // type slug
			$new_invoice_id, // post id
			__( 'Invoice Imported', 'sprout-invoices' ), // title
			0 // user id
		);
		return $invoice;
	}

	public static function create_invoice_payment( $payment = array(), SI_Invoice $invoice ) {

		$payment_id = SI_Payment::new_payment( array(
			'payment_method' => ( isset( $payment['gateway'] ) ) ? $payment['gateway'] : '',
			'invoice' => $invoice->get_id(),
			'amount' => $payment['amount'],
			'data' => array(
			'memo' => $payment['memo'],
			'payment_id' => $payment['payment_id'],
			),
		) );
		$new_payment = SI_Payment::get_instance( $payment_id );
		$new_payment->set_post_date( date( 'Y-m-d H:i:s', $payment['date'] ) );
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
}
//SI_Sliced_Invoices_Import::register();
