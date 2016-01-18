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
	const PROGRESS_OPTION = 'current_import_progress_wpinvoice_v3';

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
		self::add_importer( __CLASS__, __( 'WP-Invoice', 'sprout-invoices' ) );
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
				'tab' => self::get_settings_page( false ),
				'settings' => array(
					self::DELETE_WPINVOICE_DATA => array(
						'label' => __( 'Delete WP-Invoices', 'sprout-invoices' ),
						'option' => array(
							'type' => 'checkbox',
							'value' => 'remove',
							'label' => 'Cleanup some WP-Invoice during the import.',
							'description' => __( 'You must really love us to delete those WP-Invoices, since you can\'t go back. Settings and the log table (sigh) will be kept.', 'sprout-invoices' ),
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
		// no options to save
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
	public static function delete_wpinvoice_data() {
		self::$wpinvoice_delete = ( isset( $_POST[ self::DELETE_WPINVOICE_DATA ] ) && $_POST[ self::DELETE_WPINVOICE_DATA ] == 'remove' ) ? true : false ;
		return self::$wpinvoice_delete;
	}

	/**
	 * First step in the import progress
	 * @return
	 */
	public static function import_authentication() {

		if ( ! class_exists( 'WPI_Invoice' ) ) {
			self::return_error( __( 'WP-Invoices needs to be activated before proceeding.', 'sprout-invoices' ) );
		}

		$args = array(
				'post_type' => 'wpi_object', // why object? I don't get it either.
				'post_status' => 'any',
				'posts_per_page' => -1,
				'fields' => 'ids',
			);

		$wp_invoice_ids = get_posts( $args );

		if ( empty( $wp_invoice_ids ) ) {
			self::return_error( __( 'We couldn\'t fine any WP-Invoices to import.', 'sprout-invoices' ) );
		}

		$progress_tally = array();
		$progress_tally['clients_tally'] = 0;
		$progress_tally['contacts_tally'] = 0;
		$progress_tally['invoices_tally'] = 0;
		$progress_tally['payments_tally'] = 0;
		$progress_tally['invoices_total'] = count( $wp_invoice_ids );
		$progress_tally['total_records'] = count( $wp_invoice_ids );
		update_option( self::PROGRESS_OPTION.'tally', $progress_tally );

		$total_records = count( $wp_invoice_ids );
		self::return_progress( array(
			'authentication' => array(
			'message' => sprintf( __( 'Preparing to import from %s invoices...', 'sprout-invoices' ), $total_records ),
			'progress' => 90,
			),
			'clients' => array(
			'message' => sprintf( __( 'Importing clients from %s WP-Invoice records...', 'sprout-invoices' ), $total_records ),
			'progress' => 10,
			),
			'contacts' => array(
			'message' => sprintf( __( 'Importing contacts from %s WP-Invoice records...', 'sprout-invoices' ), $total_records ),
			'progress' => 10,
			),
			'estimates' => array(
			'message' => sprintf( __( 'No estimates will be imported, unfortunately...', 'sprout-invoices' ), $total_records ),
			'progress' => 0,
			),
			'invoices' => array(
			'message' => sprintf( __( 'Importing invoices from %s WP-Invoice records...', 'sprout-invoices' ), $total_records ),
			'progress' => 10,
			),
			'payments' => array(
			'message' => sprintf( __( 'Importing payments from %s WP-Invoice records...', 'sprout-invoices' ), $total_records ),
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
				'post_type' => 'wpi_object', // why object? I don't get it either.
				'post_status' => 'any',
				'posts_per_page' => 25,
				'offset' => ( $progress * 25 ) - 25,
				'fields' => 'ids',
			);

		$wp_invoice_ids = get_posts( $args );

		if ( empty( $progress_tally ) ) {
			$progress_tally['clients_tally'] = 0;
			$progress_tally['contacts_tally'] = 0;
			$progress_tally['invoices_tally'] = 0;
			$progress_tally['payments_tally'] = 0;
			$progress_tally['invoices_total'] = count( $wp_invoice_ids );
			$progress_tally['total_records'] = count( $wp_invoice_ids );
		}

		if ( empty( $wp_invoice_ids ) ) {

			//////////////
			// All done //
			//////////////

			self::return_progress( array(
				'authentication' => array(
				'message' => sprintf( __( 'Imported %s invoices!', 'sprout-invoices' ), $progress_tally['total_records'] ),
				'progress' => 100,
				),
				'clients' => array(
				'message' => sprintf( __( 'Importing %s clients from %s WP-Invoice records...', 'sprout-invoices' ), $progress_tally['clients_tally'], $progress_tally['total_records'] ),
				'progress' => 100,
				),
				'contacts' => array(
				'message' => sprintf( __( 'Importing %s contacts from %s WP-Invoice records...', 'sprout-invoices' ), $progress_tally['contacts_tally'], $progress_tally['total_records'] ),
				'progress' => 100,
				),
				'estimates' => array(
				'message' => __( 'No estimates were imported', 'sprout-invoices' ),
				'progress' => 100,
				),
				'invoices' => array(
				'message' => sprintf( __( 'Importing %s invoices from %s WP-Invoice records...', 'sprout-invoices' ), $progress_tally['invoices_tally'], $progress_tally['total_records'] ),
				'progress' => 100,
				),
				'payments' => array(
				'message' => sprintf( __( 'Importing %s payments from %s WP-Invoice records...', 'sprout-invoices' ), $progress_tally['payments_tally'], $progress_tally['total_records'] ),
				'progress' => 100,
				'next_step' => 'complete',
				),
			) );
		}

		foreach ( $wp_invoice_ids as $wp_invoice_id ) {
			$wp_invoice = new WPI_Invoice();
			$wp_invoice = $wp_invoice->load_invoice( array( 'id' => $wp_invoice_id, 'return' => true ) );

			if ( $wp_invoice['type'] != 'invoice' && $wp_invoice['type'] != 'recurring' ) {
				continue;
			}

			// prp($wp_invoice);
			// continue;
			/////////////
			// Clients //
			/////////////
			$progress_tally['clients_tally']++;
			$new_client_id = self::create_client( $wp_invoice );

			//////////////
			// Contacts //
			//////////////
			// Just in case the role wasn't already added
			add_role( SI_Client::USER_ROLE, __( 'Client', 'sprout-invoices' ), array( 'read' => true, 'level_0' => true ) );
			$progress_tally['contacts_tally']++;
			self::create_contact( $wp_invoice, $new_client_id );

			//////////////
			// Invoices //
			//////////////
			$progress_tally['invoices_tally']++;
			$new_invoice = self::create_invoice( $wp_invoice, $new_client_id );

			//////////////
			// Payments //
			//////////////
			if ( ! empty( $wp_invoice['log'] ) ) {
				foreach ( $wp_invoice['log'] as $key => $event ) {
					if ( 'balance' === $event['attribute'] ) {
						$progress_tally['payments_tally']++;
						self::create_invoice_payment( $event, $new_invoice );
					}
				}
			}

			if ( self::delete_wpinvoice_data() ) {
				// printf( 'Deleting WP-Invoice: %s', esc_attr( $wp_invoice['post_title'] ) );
				// wp_delete_post( $wp_invoice_id, true );
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
			'message' => sprintf( __( 'Importing %s clients from %s WP-Invoice records...', 'sprout-invoices' ), $progress_tally['clients_tally'], $progress_tally['total_records'] ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			),
			'contacts' => array(
			'message' => sprintf( __( 'Importing %s contacts from %s WP-Invoice records...', 'sprout-invoices' ), $progress_tally['contacts_tally'], $progress_tally['total_records'] ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			),
			'estimates' => array(
			'message' => __( 'No estimates were imported', 'sprout-invoices' ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			),
			'invoices' => array(
			'message' => sprintf( __( 'Importing %s invoices from %s WP-Invoice records...', 'sprout-invoices' ), $progress_tally['invoices_tally'], $progress_tally['total_records'] ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			),
			'payments' => array(
			'message' => sprintf( __( 'Importing %s payments from %s WP-Invoice records...', 'sprout-invoices' ), $progress_tally['payments_tally'], $progress_tally['total_records'] ),
			'progress' => intval( ( $progress_tally['invoices_tally'] / $progress_tally['total_records'] ) * 100 ),
			'next_step' => 'invoices',
			),
		) );

	}

	public static function create_client( $wp_invoice = array() ) {
		$wp_invoice_user_data = $wp_invoice['user_data'];

		if ( isset( $wp_invoice_user_data['ID'] ) ) {
			$possible_dups = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::WPINVOICE_ID => $wp_invoice_user_data['ID'] ) );
			// Don't create a duplicate if this was already imported.
			if ( ! empty( $possible_dups ) ) {
				do_action( 'si_error', 'Client imported already', $wp_invoice_user_data['ID'] );
				return $possible_dups[0];
			}
		}

		// args to create new client
		$address = array(
			'street' => isset( $wp_invoice_user_data['streetaddress'] ) ? esc_html( $wp_invoice_user_data['streetaddress'] ) : '',
			'city' => isset( $wp_invoice_user_data['city'] ) ? esc_html( $wp_invoice_user_data['city'] ) : '',
			'zone' => isset( $wp_invoice_user_data['state'] ) ? esc_html( $wp_invoice_user_data['state'] ) : '',
			'postal_code' => isset( $wp_invoice_user_data['zip'] ) ? esc_html( $wp_invoice_user_data['zip'] ) : '',
			'country' => isset( $wp_invoice_user_data['country'] ) ? esc_html( $wp_invoice_user_data['country'] ) : apply_filters( 'si_default_country_code', 'US' ),
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

		// Attempt to find matching client
		if ( isset( $args['company_name'] ) ) {
			global $wpdb;
			$client_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s", esc_sql( $args['company_name'] ), SI_Client::POST_TYPE ) );
			if ( ! empty( $client_ids ) ) {
				do_action( 'si_error', 'Client imported already (name match)', $wp_invoice['ID'] );
				return $client_ids[0];
			}
		}

		$client_id = SI_Client::new_client( $args );
		// create import record
		update_post_meta( $client_id, self::WPINVOICE_ID, $wp_invoice_user_data['ID'] );
		return $client_id;
	}

	public static function create_contact( $wp_invoice = array(), $client_id = 0 ) {
		$wp_invoice_user_data = $wp_invoice['user_data'];
		$user_id = $wp_invoice_user_data['ID'];
		if ( $user_id ) {
			// Attempt to convert the wp-invoice user to a client if currently a subscriber.
			if ( ! user_can( $user_id, 'edit_posts' ) ) {
				wp_update_user( array( 'ID' => $user_id, 'role' => SI_Client::USER_ROLE ) );
			}
			// Get client and confirm it's validity
			$client = SI_Client::get_instance( $client_id );
			if ( ! is_a( $client, 'SI_Client' ) ) {
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
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Invoice imported already', $wp_invoice['ID'] );
			return;
		}
		// Get client
		if ( ! $client_id ) {
			$clients = SI_Post_Type::find_by_meta( SI_Client::POST_TYPE, array( self::WPINVOICE_ID => $wp_invoice['ID'] ) );
			// Get client and confirm it's validity
			$client = SI_Client::get_instance( $clients[0] );
			$client_id = ( is_a( $client, 'SI_Client' ) ) ? $client->get_id() : 0 ;
		}

		$args = array(
			'subject' => ( $wp_invoice['post_title'] ) ? $wp_invoice['post_title'] : 'WPInvoice Import #' . $wp_invoice['ID'],
		);
		$new_invoice_id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_PENDING );
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
		if ( isset( $wp_invoice['post_status'] ) ) {
			switch ( $wp_invoice['post_status'] ) {
				case 'paid':
					$invoice->set_as_paid();
					break;
				case 'active':
				case 'pending':
					$invoice->set_pending();
					break;
				case 'refund':
					$invoice->set_as_written_off();
					break;
				default:
					$invoice->set_as_temp();
					break;
			}
		}
		$invoice->set_issue_date( strtotime( $wp_invoice['due_date_day'].'-'.$wp_invoice['due_date_month'].'-'.$wp_invoice['due_date_year'] ) );
		// post date
		$invoice->set_post_date( date( 'Y-m-d H:i:s', strtotime( $wp_invoice['post_date'] ) ) );

		// line items
		$line_items = array();
		if ( isset( $wp_invoice['itemized_list'] ) && ! empty( $wp_invoice['itemized_list'] ) ) {
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
		if ( isset( $wp_invoice['itemized_charges'] ) && ! empty( $wp_invoice['itemized_charges'] ) ) {
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
		if ( ! empty( $wp_invoice['log'] ) ) {
			foreach ( $wp_invoice['log'] as $key => $event ) {
				if ( $event['attribute'] == 'notification' ) { // payments are added separately
					do_action( 'si_new_record',
						__( 'Notification content was not stored by WP-Invoice.', 'sprout-invoices' ), // content
						SI_Notifications::RECORD, // type slug
						$new_invoice_id, // post id
						$event['text'], // title
						0, // user id
						false // don't encode
					);
				}
			}
		}
		do_action( 'si_new_record',
			$wp_invoice, // content
			self::RECORD, // type slug
			$new_invoice_id, // post id
			__( 'Invoice Imported', 'sprout-invoices' ), // title
			0 // user id
		);
		return $invoice;
	}

	public static function create_invoice_payment( $payment = array(), SI_Invoice $invoice ) {
		$possible_dups = SI_Post_Type::find_by_meta( SI_Payment::POST_TYPE, array( self::WPINVOICE_ID => $payment['ID'] ) );
		// Don't create a duplicate if this was already imported.
		if ( ! empty( $possible_dups ) ) {
			do_action( 'si_error', 'Invoice imported already', $payment['ID'] );
			return;
		}
		$payment_id = SI_Payment::new_payment( array(
			'payment_method' => ( isset( $payment['action'] ) ) ? self::PAYMENT_METHOD . ' :: ' . $payment['action'] : self::PAYMENT_METHOD,
			'invoice' => $invoice->get_id(),
			'amount' => $payment['value'],
			'transaction_id' => ( isset( $payment['ID'] ) ) ? $payment['object_id'] . '::' . $payment['ID'] : '',
			'data' => array(
			'api_response' => $payment,
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
SI_WPInvoice_Import::register();
