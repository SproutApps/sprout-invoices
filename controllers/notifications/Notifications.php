<?php

/**
 * Hooks for registered shortcodes and shortcode callbacks
 *
 * @package Sprout_Invoice
 * @subpackage Notification
 */
class SI_Notifications extends SI_Notifications_Control {

	public static function init() {
		// register notifications
		add_filter( 'sprout_notifications', array( __CLASS__, 'register_notifications' ) );
		// Shortcodes
		add_filter( 'sprout_notification_shortcodes', array( __CLASS__, 'register_shortcodes' ) );

		// Hook actions that would send a notification
		self::notification_hooks();
	}

	/**
	 * Hooks for all notifications
	 * @return
	 */
	private static function notification_hooks() {
		// Notifications can be suppressed
		if ( apply_filters( 'suppress_notifications', FALSE ) ) {
			return;
		}

		// estimates
		add_action( 'send_estimate', array( __CLASS__, 'estimate_notification' ), 10, 2 );
		// invoices
		add_action( 'send_invoice', array( __CLASS__, 'invoice_notification' ), 10, 2 );
		add_action( 'si_new_payment', array( __CLASS__, 'paid_notification' ), 10, 2 );
		
		// Admin
		add_action( 'doc_status_changed', array( __CLASS__, 'admin_estimate_accepted' ), 10, 2 );
		add_action( 'doc_status_changed', array( __CLASS__, 'admin_estimate_declined' ), 10, 2 );
		add_action( 'si_new_payment', array( __CLASS__, 'admin_payment_notification' ), 10, 2 );
	}

	public static function register_notifications( $notifications = array() ) {
		$default_notifications = array(
				// Lead Generation
				'estimate_received' => array(
					'name' => self::__( 'Lead Received' ),
					'description' => self::__( 'Customize the email that is sent to a prospective client after a lead is submitted.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'lead_entries', 'estimate_id', 'estimate_edit_url', 'client_name', 'client_edit_url', 'estimate_total', 'estimate_subtotal' ),
					'default_title' => sprintf( self::__( '%s: Estimate Request Received' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/request-received', NULL ),
					'always_disabled' => TRUE
				),
				// Estimates
				'send_estimate' => array(
					'name' => self::__( 'Estimate Available' ),
					'description' => self::__( 'Customize the estimate email that is sent to selected recipients.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'admin_note', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'estimate_subject', 'estimate_id', 'estimate_edit_url', 'estimate_url', 'estimate_issue_date', 'estimate_po_number', 'estimate_total', 'estimate_subtotal', 'client_name', 'client_edit_url'  ),
					'default_title' => sprintf( self::__( '%s: Your Estimate is Available' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/estimate', NULL )
				),
				// Invoices
				'send_invoice' => array(
					'name' => self::__( 'Invoice Available' ),
					'description' => self::__( 'Customize the invoice email that is sent to selected recipients.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'admin_note', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_total', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_deposit_amount', 'invoice_total_payments', 'client_name', 'client_edit_url'  ),
					'default_title' => sprintf( self::__( '%s: Your Invoice is Available' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/invoice', NULL )
				),
				// Payments
				'deposit_payment' => array(
					'name' => self::__( 'Deposit Payment Received' ),
					'description' => self::__( 'Customize the payment email that is sent to the client recipients when a deposit is made.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'payment_total', 'payment_id', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_total', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_total_due', 'invoice_deposit_amount', 'invoice_total_payments', 'client_name', 'client_edit_url'  ),
					'default_title' => sprintf( self::__( '%s: Deposit Received' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/payment-deposit', NULL ),
					'always_disabled' => TRUE
				),
				'final_payment' => array(
					'name' => self::__( 'Invoice Paid' ),
					'description' => self::__( 'Customize the email sent to the client recipients when the final payment for an invoice is made.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'payment_total', 'payment_id', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_total', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_total_due', 'invoice_deposit_amount', 'invoice_total_payments', 'client_name', 'client_edit_url'  ),
					'default_title' => sprintf( self::__( '%s: Thank You' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/payment-final', NULL )
				),
				'reminder_payment' => array(
					'name' => self::__( 'Payment Reminder' ),
					'description' => self::__( 'Customize the email that is sent to the client recipients in order to remind them that their payment is overdue.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'payment_total', 'payment_id', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_total', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_total_due', 'invoice_deposit_amount', 'invoice_total_payments', 'client_name', 'client_edit_url'  ),
					'default_title' => sprintf( self::__( '%s: Invoice Payment Overdue' ),  get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/payment-reminder', NULL ),
					'always_disabled' => TRUE
				),
				// Admin Notifications
				'estimate_submitted' => array(
					'name' => self::__( 'Lead Submitted' ),
					'description' => self::__( 'Customize the email that is sent to the site admin after an lead is submitted.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'estimate_subject', 'estimate_id', 'estimate_edit_url', 'estimate_url', 'estimate_issue_date', 'estimate_po_number', 'estimate_total', 'estimate_subtotal', 'client_name', 'client_edit_url'  ),
					'default_title' => sprintf( self::__( '%s: Estimate Request Received' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/admin-request-submitted', NULL ),
					'always_disabled' => TRUE
				),
				'accepted_estimate' => array(
					'name' => self::__( 'Estimate Accepted' ),
					'description' => self::__( 'Customize the email sent to the admin after an estimate is accepted.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'estimate_subject', 'estimate_id', 'estimate_edit_url', 'estimate_url', 'estimate_issue_date', 'estimate_po_number', 'estimate_total', 'estimate_subtotal', 'client_name', 'client_edit_url'  ),
					'default_title' => sprintf( self::__( '%s: Estimate Accepted' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/admin-estimate-accepted', NULL )
				),
				'declined_estimate' => array(
					'name' => self::__( 'Estimate Declined' ),
					'description' => self::__( 'Customize the email sent to the admin after an estimate is accepted.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'estimate_subject', 'estimate_id', 'estimate_edit_url', 'estimate_url', 'estimate_issue_date', 'estimate_po_number', 'estimate_total', 'estimate_subtotal', 'client_name', 'client_edit_url'  ),
					'default_title' => sprintf( self::__( '%s: Estimate Declined' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/admin-estimate-declined', NULL )
				),
				'payment_notification' => array(
					'name' => self::__( 'Payment Received' ),
					'description' => self::__( 'Customize the email sent to an admin when any payment is received.' ),
					'shortcodes' => array( 'date', 'name', 'username', 'payment_total', 'payment_id', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_total', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_total_due', 'invoice_deposit_amount', 'invoice_total_payments', 'client_name', 'client_edit_url'  ),
					'default_title' => sprintf( self::__( '%s: Payment Received' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/admin-payment', NULL )
				),
			);
		return array_merge( $notifications, $default_notifications );
	}

	public static function register_shortcodes( $shortcodes = array() ) {
		// Notification shortcodes include the code, a description, and a callback
		// Most shortcodes should be defined by a different controller using the 'sprout_notification_shortcodes' filter
		$default_shortcodes = array(
				'date' => array(
					'description' => self::__( 'Used to display the date.' ),
					'callback' => array( 'SI_Notifications', 'shortcode_date' )
					),
				'name' => array(
					'description' => self::__( 'Used to display the user&rsquo;s name.' ),
					'callback' => array( 'SI_Notifications', 'shortcode_sender_name' )
					),
				'username' => array(
					'description' => self::__( 'Used to display the user&rsquo;s name.' ),
					'callback' => array( 'SI_Notifications', 'shortcode_username' )
					),
				'admin_note' => array(
					'description' => self::__( 'Used to display the note created before sending.' ),
					'callback' => array( 'SI_Notifications', 'shortcode_admin_note' )
					),
				'payment_total' => array(
						'description' => self::__( 'Used to display the payment total.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_payment_total' )
					),
				'payment_id' => array(
						'description' => self::__( 'Used to display the .' ),
						'callback' => array( 'SI_Notifications', 'shortcode_payment_id' )
					),
				'line_item_table' => array(
						'description' => self::__( 'Used to display the line items for an estimate or invoice in a table format (HTML).' ),
						'callback' => array( 'SI_Notifications', 'shortcode_line_item_table' )
					),
				'line_item_list' => array(
						'description' => self::__( 'Used to display the line items for an estimate or invoice in a list format (HTML).' ),
						'callback' => array( 'SI_Notifications', 'shortcode_line_item_list' )
					),
				'line_item_plain_list' => array(
						'description' => self::__( 'Used to display the line items for an estimate or invoice in a plain text list.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_line_item_plain_list' )
					),
				'invoice_subject' => array(
						'description' => self::__( 'Used to display the invoice subject.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_subject' )
					),
				'invoice_id' => array(
						'description' => self::__( 'Used to display the invoice id.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_id' )
					),
				'invoice_edit_url' => array(
						'description' => self::__( 'Used to display the invoice edit url.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_edit_url' )
					),
				'invoice_url' => array(
						'description' => self::__( 'Used to display the invoice url.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_url' )
					),
				'invoice_issue_date' => array(
						'description' => self::__( 'Used to display the invoice issue date.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_issue_date' )
					),
				'invoice_due_date' => array(
						'description' => self::__( 'Used to display the due date.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_due_date' )
					),
				'invoice_past_due_date' => array(
						'description' => self::__( 'Used to display how many days the invoice is past due.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_past_due_date' )
					),
				'invoice_po_number' => array(
						'description' => self::__( 'Used to display the invoice po number.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_po_number' )
					),
				'invoice_total' => array(
						'description' => self::__( 'Used to display the invoice total.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_total' )
					),
				'invoice_calculated_total' => array(
						'description' => self::__( 'Used to display the invoice calculated total.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_calculated_total' )
					),
				'invoice_subtotal' => array(
						'description' => self::__( 'Used to display the invoice sub total.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_subtotal' )
					),
				'invoice_total_due' => array(
						'description' => self::__( 'Used to display the total amount due.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_total_due' )
					),
				'invoice_deposit_amount' => array(
						'description' => self::__( 'Used to display the deposit amount due.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_deposit_amount' )
					),
				'invoice_total_payments' => array(
						'description' => self::__( 'Used to display the total of all payments.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_total_payments' )
					),
				'client_name' => array(
						'description' => self::__( 'Used to display the client name.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_client_name' )
					),
				'client_edit_url' => array(
						'description' => self::__( 'Used to display the client edit url.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_client_edit_url' )
					),
				'estimate_subject' => array(
						'description' => self::__( 'Used to display the estimate subject.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_subject' )
					),
				'estimate_id' => array(
						'description' => self::__( 'Used to display the estimate id.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_id' )
					),
				'estimate_edit_url' => array(
						'description' => self::__( 'Used to display the edit url.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_edit_url' )
					),
				'estimate_url' => array(
						'description' => self::__( 'Used to display the estimate url.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_url' )
					),
				'estimate_issue_date' => array(
						'description' => self::__( 'Used to display the estimate issue date.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_issue_date' )
					),
				'estimate_po_number' => array(
						'description' => self::__( 'Used to display the estimate po number.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_po_number' )
					),
				'estimate_total' => array(
						'description' => self::__( 'Used to display the estimate total.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_total' )
					),
				'estimate_subtotal' => array(
						'description' => self::__( 'Used to display the estimate total.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_subtotal' )
					),
				'lead_entries' => array(
						'description' => self::__( 'Used to display the lead entries in HTML.' ),
						'callback' => array( 'SI_Notifications', 'shortcode_lead_entries' )
					),

			);
		return array_merge( $shortcodes, $default_shortcodes );
	}

	/////////////////////////////
	// notification callbacks //
	/////////////////////////////

	public static function estimate_notification( SI_Estimate $estimate, $recipients = array() ) {
		$invoice = '';
		if ( $invoice_id = $estimate->get_invoice_id() ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
		}
		foreach ( array_unique( $recipients ) as $user_id ) {
			$to = self::get_user_email( $user_id );
			$data = array(
				'user_id' => $user_id,
				'estimate' => $estimate,
				'invoice' => $invoice,
				'to' => $to
			);
			self::send_notification( 'send_estimate', $data, $to );
		}
	}

	public static function invoice_notification( SI_Invoice $invoice, $recipients = array() ) {
		$estimate = '';
		if ( $estimate_id = $invoice->get_estimate_id() ) {
			$estimate = SI_Estimate::get_instance( $estimate_id );
		}
		foreach ( array_unique( $recipients ) as $user_id ) {
			$to = self::get_user_email( $user_id );
			$data = array(
				'user_id' => $user_id,
				'invoice' => $invoice,
				'estimate' => $estimate,
				'to' => $to
			);
			self::send_notification( 'send_invoice', $data, $to );
		}
	}

	public static function paid_notification( SI_Payment $payment, $args = array() ) {
		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance( $invoice_id );
		if ( is_a( $invoice, 'SI_Invoice' ) && $invoice->get_balance() < 0.01 ) { // leave a bit of room for floating point arithmetic
			$client = $invoice->get_client();
			// get the user ids associated with this doc.
			if ( !is_wp_error( $client ) ) {
				$client_users = $client->get_associated_users();
			}
			else { // no client associated
				$user_id = $invoice->get_user_id(); // check to see if a user id is associated
				if ( !$user_id ) {
					return;
				}
				$client = 0;
				$client_users = array( $user_id );
			}
			foreach ( array_unique( $client_users ) as $user_id ) {
				if ( ! is_wp_error( $user_id ) ) {
					$to = self::get_user_email( $user_id );
					$data = array(
						'payment' => $payment,
						'invoice' => $invoice,
						'client' => $client,
						'to' => $to
					);
					self::send_notification( 'final_payment', $data, $to );
				}
			}
		}
	}

	/**
	 * Estimate accepted notification
	 * @param  object $doc  SI_Invoice or SI_Estimate
	 * @param  array  $args 
	 * @return        
	 */
	public static function admin_estimate_accepted( $doc, $args = array() ) {
		// The $doc doesn't have to be an estimate
		if ( !is_a( $doc, 'SI_Estimate' ) ) {
			return;
		}
		// Check if status changed was to approved.
		if ( $doc->get_status() != SI_Estimate::STATUS_APPROVED ) {
			return;
		}
		$estimate = $doc;
		$invoice = '';
		$invoice_id = $estimate->get_invoice_id();
		if ( $invoice_id ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
		}
		// Admin email 
		$admin_to = get_option( 'admin_email' );
		$data = array(
			'user_id' => $estimate->get_user_id(),
			'estimate' => $estimate,
			'invoice' => $invoice,
			'client' => $estimate->get_client(),
			'to' => $admin_to
		);
		self::send_notification( 'accepted_estimate', $data, $admin_to );
	}

	/**
	 * Estimate accepted notification
	 * @param  object $doc  SI_Invoice or SI_Estimate
	 * @param  array  $args 
	 * @return        
	 */
	public static function admin_estimate_declined( $doc, $args = array() ) {
		// The $doc doesn't have to be an estimate
		if ( !is_a( $doc, 'SI_Estimate' ) ) {
			return;
		}
		// Check if status changed was to approved.
		if ( $doc->get_status() != SI_Estimate::STATUS_DECLINED ) {
			return;
		}
		$estimate = $doc;
		$invoice = '';
		$invoice_id = $estimate->get_invoice_id();
		if ( $invoice_id ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
		}
		// Admin email 
		$admin_to = get_option( 'admin_email' );
		$data = array(
			'user_id' => $estimate->get_user_id(),
			'estimate' => $estimate,
			'invoice' => $invoice,
			'client' => $estimate->get_client(),
			'to' => $admin_to
		);
		self::send_notification( 'declined_estimate', $data, $admin_to );
	}

	/**
	 * Send the admin a notification when a payment is received.
	 * @param  SI_Payment $payment 
	 * @param  array      $args    
	 * @return               
	 */
	public static function admin_payment_notification( SI_Payment $payment, $args = array() ) {
		$payment_method = $payment->get_payment_method();
		// A notification shouldn't be sent to the admin when s/he created it
		if ( $payment_method == SI_Admin_Payment::PAYMENT_METHOD ) {
			return;
		}

		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance( $invoice_id );
		$client = $invoice->get_client();

		// Admin email 
		$admin_to = get_option( 'admin_email' );
		$data = array(
			'payment' => $payment,
			'invoice' => $invoice,
			'client' => $client,
			'to' => $admin_to
		);
		self::send_notification( 'payment_notification', $data, $admin_to );
	}


	/////////////////
	// Shortcodes //
	/////////////////


	/**
	 * Get current date
	 *
	 * Currently undocumented, but a "format" attribute can be used to customize the date format
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_date( $atts, $content, $code, $data ) {
		$atts = shortcode_atts( array( 'format' => get_option( 'date_format' ) ), $atts );
		return date( $atts['format'], current_time( 'timestamp' ) );
	}

	/**
	 * Return the name of the user receiving this email.
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_sender_name( $atts, $content, $code, $data ) {
		$name = self::__('Client');
		$to = ( isset( $data['to'] ) ) ? $data['to'] : 0 ;
		$user_id = self::get_notification_instance_user_id( $to, $data );
		if ( is_numeric( $user_id ) && $user_id ) {
			// Fallback to Username
			$user = get_userdata( $user_id );
			if ( $user->display_name == $user->user_nicename ) { // display name is still set to userlogin
				// Build off first and last name.
				$name = $user->first_name . ' ' . $user->last_name;
				// no first and last name?
				if ( $name == ' ' ) {
					$name = $display_name;
				}
			}
		}
		// If no user can be found attempt to use the client.
		else {
			if ( isset( $data['client'] ) && is_a( $data['client'], 'SI_Client' ) ) {
				$client_id = $data['client']->get_id();
				$name = get_the_title( $client_id );
			}
		}
		return $name;
	}

	/**
	 * Return the 
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_username( $atts, $content, $code, $data ) {
		$to = ( isset( $data['to'] ) ) ? $data['to'] : 0 ;
		$user_id = self::get_notification_instance_user_id( $to, $data );
		if ( is_numeric( $user_id ) ) {
			$user = get_userdata( $user_id );
			return $user->user_login;
		}
		return self::__( 'Client' );
	}

	/**
	 * Return the admin sender note attached to a sent estimate/invoice.
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_admin_note( $atts, $content, $code, $data ) {
		$sender_note = '';
		if ( $data['invoice'] && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$sender_note = $data['invoice']->get_sender_note();
		}
		if ( $sender_note == '' ) {
			if ( $data['estimate'] && is_a( $data['estimate'], 'SI_Estimate' ) ) {
				$sender_note = $data['estimate']->get_sender_note();
			}
		}
		return $sender_note;
	}

	/**
	 * Return the payment total
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_payment_total( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money(0);
		if ( isset( $data['payment'] ) ) {
			$amount = sa_get_formatted_money( $data['payment']->get_amount() );
		}
		return $amount;
	}

	/**
	 * Return the payment id
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_payment_id( $atts, $content, $code, $data ) {
		if ( isset( $data['payment'] ) ) {
			return $data['payment']->get_id();
		}
		return 0;
	}

	/**
	 * Return the line items within an html table.
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_line_item_table( $atts, $content, $code, $data ) {
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$line_items = $data['invoice']->get_line_items();
		}
		else {
			if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
				$line_items = $data['estimate']->get_line_items();
			}
		}
		if ( empty( $line_items ) ) {
			return '';
		}
		ob_start(); ?>
			<table>
				<thead>
					<tr>
						<th><?php si_e('#') ?></th>
						<th><?php si_e('Description') ?></th>
						<th><?php si_e('Rate') ?></th>
						<th><?php si_e('Quantity') ?></th>
						<th><?php si_e('% Adjustment') ?></th>
						<th><?php si_e('Total') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $line_items as $position => $data ): ?>
						<tr class="item" data-id="<?php echo $position ?>">
							<td><?php esc_attr_e( $position+1 ) ?></td>
							<td><?php echo apply_filters( 'the_content', $data['desc'] ) ?></td>
							<td><?php esc_attr_e( $data['rate'] ) ?></td>
							<td><?php esc_attr_e( $data['qty'] ) ?></td>
							<td><?php esc_attr_e( $data['tax'] ) ?>%</td>
							<td><?php sa_formatted_money( $data['total'] ) ?></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		<?php
		$table = ob_get_clean();
		return $table;
	}

	/**
	 * Return the line items within an plain text list.
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_line_item_plain_list( $atts, $content, $code, $data ) {
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$line_items = $data['invoice']->get_line_items();
		}
		else {
			if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
				$line_items = $data['estimate']->get_line_items();
			}
		}
		if ( empty( $line_items ) ) {
			return '';
		}
		ob_start(); ?>
			<?php foreach ( $line_items as $position => $data ): ?>
				<?php if ( is_int( $position ) ): // is not a child ?>
					<?php
						// get the children of this top level item
						$children = si_line_item_get_children( $position, $line_items ); ?>
					<?php 
						// build single item
						echo "\n\n* " . si_line_item_build_plain( $position, $line_items, $children ) ?>

					<?php if ( !empty( $children ) ): // if has children, loop and show ?>
						<?php foreach ( $children as $child_position ): ?>
							<?php echo "\n** " . si_line_item_build_plain( $child_position, $line_items ) ?>
						<?php endforeach ?>
					<?php endif ?>
				<?php endif ?>
			<?php endforeach ?>
		<?php
		$table = ob_get_clean();
		return $table;
	}

	/**
	 * Return the line items within an html list.
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_line_item_list( $atts, $content, $code, $data ) {
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$line_items = $data['invoice']->get_line_items();
		}
		else {
			if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
				$line_items = $data['estimate']->get_line_items();
			}
		}
		if ( empty( $line_items ) ) {
			return '';
		}
		ob_start(); ?>
			<ol>
				<?php foreach ( $line_items as $position => $data ): ?>
					<?php if ( is_int( $position ) ): // is not a child ?>
						<li class="item" data-id="<?php echo $position ?>">
							<?php
								// get the children of this top level item
								$children = si_line_item_get_children( $position, $line_items ); ?>

							<?php 
								// build single item
								echo si_line_item_build( $position, $line_items, $children ) ?>

							<?php if ( !empty( $children ) ): // if has children, loop and show  ?>
								<ol class="items_list">
									<?php foreach ( $children as $child_position ): ?>
										<li class="item" data-id="<?php echo $child_position ?>"><?php echo si_line_item_build( $child_position, $line_items ) ?></li>
									<?php endforeach ?>
								</ol>
							<?php endif ?>
						</li>
					<?php endif ?>
				<?php endforeach ?>
			</ol>
			
		<?php
		$table = ob_get_clean();
		return $table;
	}

	/**
	 * Return the invoice subject
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_subject( $atts, $content, $code, $data ) {
		$invoice_id = 0;
		if ( !isset( $data['invoice'] ) && $data['estimate'] ) {
			$invoice_id = $data['estimate']->get_invoice_id();
		}
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$invoice_id = $data['invoice']->get_id();
			
		}
		return ( $invoice_id ) ? get_the_title( $invoice_id ) : '' ;
	}

	/**
	 * Return the invoice id
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_id( $atts, $content, $code, $data ) {
		$invoice_id = 0;
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$invoice_id = $data['invoice']->get_invoice_id();
		}
		return $invoice_id;
	}

	/**
	 * Return the invoice edit url
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_edit_url( $atts, $content, $code, $data ) {
		$url = '';
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$invoice_id = $data['invoice']->get_id();
			$url = get_edit_post_link( $invoice_id );
		}
		return $url;
	}

	/**
	 * Return the invoice url
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_url( $atts, $content, $code, $data ) {
		$url = '';
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$invoice_id = $data['invoice']->get_id();
			$url = get_permalink( $invoice_id );
		}
		return $url;
	}

	/**
	 * Return the invoice issue date
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_issue_date( $atts, $content, $code, $data ) {
		$date = '';
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$timestamp = $data['invoice']->get_issue_date();
			$date = date( get_option('date_format'), $timestamp );
		}
		return $date;
	}

	/**
	 * Return the invoice due date
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_due_date( $atts, $content, $code, $data ) {
		$date = '';
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$timestamp = $data['invoice']->get_due_date();
			$date = date( get_option('date_format'), $timestamp );
		}
		return $date;
	}

	/**
	 * Return the invoice due date
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_past_due_date( $atts, $content, $code, $data ) {
		$days = 0;
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$duedate = $data['invoice']->get_due_date();
			$pastdue = current_time( 'timestamp' )-$due_date;
			$date = floor($pastdue/(60*60*24));
		}
		return $days;
	}

	/**
	 * Return the invoice po number
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_po_number( $atts, $content, $code, $data ) {
		$po_number = '';
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$po_number = $data['invoice']->get_po_number();
		}
		return $po_number;
	}

	/**
	 * Return the invoice total
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_total( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money(0);
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_total() );
		}
		return $amount;
	}

	/**
	 * Return the invoice get_calculated_total
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_calculated_total( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money(0);
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_calculated_total() );
		}
		return $amount;
	}

	/**
	 * Return the invoice get_subtotal
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_subtotal( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money(0);
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_subtotal() );
		}
		return $amount;
	}

	/**
	 * Return the invoice get_balance
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_total_due( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money(0);
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_balance() );
		}
		return $amount;
	}

	/**
	 * Return the invoice get_deposit
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_deposit_amount( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money(0);
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_deposit() );
		}
		return $amount;
	}

	/**
	 * Return the invoice get_payments_total
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_invoice_total_payments( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money(0);
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_payments_total() );
		}
		return $amount;
	}

	/**
	 * Return the 
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_client_name( $atts, $content, $code, $data ) {
		$client_id = 0;
		if ( isset( $data['client'] ) && is_a( $data['client'], 'SI_Client' ) ) {
			$client_id = $data['client']->get_id();			
		}
		if ( !$client_id && isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$client_id = $data['invoice']->get_client_id();
		}
		if ( !$client_id && isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$client_id = $data['estimate']->get_client_id();
		}
		$name = ( $client_id ) ? get_the_title( $client_id ) : '' ;
		return $name;
	}

	/**
	 * Return the client edit url
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_client_edit_url( $atts, $content, $code, $data ) {
		$url = '';
		if ( isset( $data['client'] ) && is_a( $data['client'], 'SI_Client' ) ) {
			$client_id = $data['client']->get_id();
			$url = get_edit_post_link( $client_id );
		}
		return $url;
	}



	/**
	 * Return the estimate subject
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_estimate_subject( $atts, $content, $code, $data ) {
		$title = '';
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$estimate_id = $data['estimate']->get_id();
			$title = get_the_title( $estimate_id );
		}
		return $title;
	}

	/**
	 * Return the estimate id
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_estimate_id( $atts, $content, $code, $data ) {
		$estimate_id = 0;
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$estimate_id = $data['estimate']->get_estimate_id();
		}
		return $estimate_id;
	}

	/**
	 * Return the estimate edit url
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_estimate_edit_url( $atts, $content, $code, $data ) {
		$url = '';
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$estimate_id = $data['estimate']->get_id();
			$url = get_edit_post_link( $estimate_id );
		}
		return $url;
	}

	/**
	 * Return the estimate url
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_estimate_url( $atts, $content, $code, $data ) {
		$url = '';
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$estimate_id = $data['estimate']->get_id();
			$url = get_permalink( $estimate_id );
		}
		return $url;
	}

	/**
	 * Return the estimate issue date
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_estimate_issue_date( $atts, $content, $code, $data ) {
		$date = '';
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$timestamp = $data['estimate']->get_issue_date();
			$date = date( get_option('date_format'), $timestamp );
		}
		return $date;
	}

	/**
	 * Return the estimate po number
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_estimate_po_number( $atts, $content, $code, $data ) {
		$po_number = '';
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$po_number = $data['estimate']->get_po_number();
		}
		return $po_number;
	}

	/**
	 * Return the estimate total
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_estimate_total( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money(0);
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$amount = sa_get_formatted_money( $data['estimate']->get_total() );
		}
		return $amount;
	}

	/**
	 * Return the estimate get_subtotal
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_estimate_subtotal( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money(0);
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$amount = sa_get_formatted_money( $data['estimate']->get_subtotal() );
		}
		return $amount;
	}

	/**
	 * Return the lead entries
	 * 
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered
	 */
	public static function shortcode_lead_entries( $atts, $content, $code, $data ) {
		$entries = '';
		if ( isset( $data['submission_fields']['fields'] ) ) {
			if ( !empty( $data['submission_fields']['fields'] ) ) {
				ob_start(); ?>
					<?php foreach ( $data['submission_fields']['fields'] as $key => $value ): ?>
						<?php if ( isset( $value['data']['label'] ) && isset( $value['data']['type'] ) && $value['data']['type'] != 'hidden' ): ?>
							<dt><?php echo $value['data']['label'] ?></dt>
							<?php if ( is_numeric( $value['value'] ) && strpos( $value['data']['label'], self::__('Type') ) !== FALSE ): ?>
								<dd><p><?php 
										$term = get_term_by( 'id', $value['value'], SI_Estimate::PROJECT_TAXONOMY );
										if ( !is_wp_error( $term ) ) {
											self::_e( $term->name );
										}
									 ?></p></dd>
							<?php else: ?>
								<dd><?php echo apply_filters( 'the_content', $value['value'] ) ?></dd>
							<?php endif ?>
						<?php endif ?>
					<?php endforeach;

				$entries = ob_get_clean();
			}
		}
		return $entries;
	}

}