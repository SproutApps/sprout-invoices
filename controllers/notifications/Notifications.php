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

		add_filter( 'user_can_richedit', array( __CLASS__, 'disabled_wysiwyg_editor' ) );

		// Hook actions that would send a notification
		self::notification_hooks();
	}

	public static function disabled_wysiwyg_editor( $default ) {
		if ( SI_Notification::POST_TYPE == get_post_type() ) {
			return false;
		}
		return $default;
	}

	/**
	 * Hooks for all notifications
	 * @return
	 */
	private static function notification_hooks() {
		// Notifications can be suppressed
		if ( apply_filters( 'suppress_notifications', false ) ) {
			return;
		}

		// estimates
		add_action( 'send_estimate', array( __CLASS__, 'estimate_notification' ), 10, 4 );
		// invoices
		add_action( 'send_invoice', array( __CLASS__, 'invoice_notification' ), 10, 4 );
		add_action( 'payment_complete', array( __CLASS__, 'paid_notification' ) );

		// Admin
		add_action( 'doc_status_changed', array( __CLASS__, 'admin_estimate_accepted' ), 10, 2 );
		add_action( 'doc_status_changed', array( __CLASS__, 'admin_estimate_declined' ), 10, 2 );
		add_action( 'si_new_payment', array( __CLASS__, 'admin_payment_notification' ), 10, 2 );
	}

	public static function register_notifications( $notifications = array() ) {
		$default_notifications = array(
				// Lead Generation
				'estimate_received' => array(
					'name' => __( 'Lead Received', 'sprout-invoices' ),
					'description' => __( 'Customize the email that is sent to a prospective client after a lead is submitted.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'lead_entries', 'estimate_id', 'estimate_edit_url', 'client_name', 'client_address', 'client_company_website', 'client_edit_url', 'estimate_total', 'estimate_terms', 'estimate_notes', 'estimate_subtotal' ),
					'default_title' => sprintf( __( '%s: Estimate Request Received', 'sprout-invoices' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/request-received', null, false ),
					'always_disabled' => true,
				),
				// Estimates
				'send_estimate' => array(
					'name' => __( 'Estimate Available', 'sprout-invoices' ),
					'description' => __( 'Customize the estimate email that is sent to selected recipients.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'admin_note', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'estimate_subject', 'estimate_id', 'estimate_edit_url', 'estimate_url', 'estimate_issue_date', 'estimate_po_number', 'estimate_tax_total', 'estimate_tax', 'estimate_tax2', 'estimate_terms', 'estimate_notes', 'estimate_total', 'estimate_subtotal', 'client_name', 'client_address', 'client_company_website' ),
					'default_title' => sprintf( __( '%s: Your Estimate is Available', 'sprout-invoices' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/estimate', null, false ),
				),
				// Invoices
				'send_invoice' => array(
					'name' => __( 'Invoice Available', 'sprout-invoices' ),
					'description' => __( 'Customize the invoice email that is sent to selected recipients.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'admin_note', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_tax_total', 'invoice_tax', 'invoice_tax2', 'invoice_terms', 'invoice_notes', 'invoice_total', 'invoice_payments_list', 'invoice_payments_list_html', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_deposit_amount', 'invoice_total_due', 'invoice_total_payments', 'client_name', 'client_address', 'client_company_website' ),
					'default_title' => sprintf( __( '%s: Your Invoice is Available', 'sprout-invoices' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/invoice', null ),
				),
				// Payments
				'deposit_payment' => array(
					'name' => __( 'Deposit Payment Received', 'sprout-invoices' ),
					'description' => __( 'Customize the payment email that is sent to the client recipients when a deposit is made.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'payment_total', 'payment_id', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_tax_total', 'invoice_tax', 'invoice_tax2', 'invoice_terms', 'invoice_notes', 'invoice_total', 'invoice_payments_list', 'invoice_payments_list_html', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_total_due', 'invoice_deposit_amount', 'invoice_total_payments', 'client_name',  'client_address', 'client_company_website' ),
					'default_title' => sprintf( __( '%s: Deposit Received', 'sprout-invoices' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/payment-deposit', null, false ),
					'always_disabled' => true,
				),
				'final_payment' => array(
					'name' => __( 'Invoice Paid', 'sprout-invoices' ),
					'description' => __( 'Customize the email sent to the client recipients when the final payment for an invoice is made.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'payment_total', 'payment_id', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_tax_total', 'invoice_tax', 'invoice_tax2', 'invoice_terms', 'invoice_notes', 'invoice_total', 'invoice_payments_list', 'invoice_payments_list_html', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_total_due', 'invoice_deposit_amount', 'invoice_total_payments', 'client_name',  'client_address', 'client_company_website' ),
					'default_title' => sprintf( __( '%s: Thank You', 'sprout-invoices' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/payment-final', null, false ),
				),
				'reminder_payment' => array(
					'name' => __( 'Payment Reminder', 'sprout-invoices' ),
					'description' => __( 'Customize the email that is sent to the client recipients in order to remind them that their payment is overdue.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'payment_total', 'payment_id', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_tax_total', 'invoice_tax', 'invoice_tax2', 'invoice_terms', 'invoice_notes', 'invoice_total', 'invoice_payments_list', 'invoice_payments_list_html', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_total_due', 'invoice_deposit_amount', 'invoice_total_payments', 'client_name', 'client_address', 'client_company_website' ),
					'default_title' => sprintf( __( '%s: Invoice Payment Overdue', 'sprout-invoices' ),  get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/payment-reminder', null, false ),
					'always_disabled' => true,
				),
				// Admin Notifications
				'estimate_submitted' => array(
					'name' => __( 'Lead Submitted', 'sprout-invoices' ),
					'description' => __( 'Customize the email that is sent to the site admin after an lead is submitted.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'estimate_subject', 'estimate_id', 'estimate_edit_url', 'estimate_url', 'estimate_issue_date', 'estimate_po_number', 'estimate_total', 'estimate_subtotal', 'client_name', 'client_edit_url', 'client_address', 'client_company_website' ),
					'default_title' => sprintf( __( '%s: Estimate Request Received', 'sprout-invoices' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/admin-request-submitted', null, false ),
					'always_disabled' => true,
				),
				'accepted_estimate' => array(
					'name' => __( 'Estimate Accepted', 'sprout-invoices' ),
					'description' => __( 'Customize the email sent to the admin after an estimate is accepted.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'estimate_subject', 'estimate_id', 'estimate_edit_url', 'estimate_url', 'estimate_issue_date', 'estimate_po_number', 'estimate_terms', 'estimate_notes', 'estimate_total', 'estimate_subtotal', 'client_name', 'client_edit_url', 'client_address', 'client_company_website' ),
					'default_title' => sprintf( __( '%s: Estimate Accepted', 'sprout-invoices' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/admin-estimate-accepted', null, false ),
				),
				'declined_estimate' => array(
					'name' => __( 'Estimate Declined', 'sprout-invoices' ),
					'description' => __( 'Customize the email sent to the admin after an estimate is declined.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'estimate_subject', 'estimate_id', 'estimate_edit_url', 'estimate_url', 'estimate_issue_date', 'estimate_po_number', 'estimate_terms', 'estimate_notes', 'estimate_total', 'estimate_subtotal', 'client_name', 'client_edit_url', 'client_address', 'client_company_website' ),
					'default_title' => sprintf( __( '%s: Estimate Declined', 'sprout-invoices' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/admin-estimate-declined', null, false ),
				),
				'payment_notification' => array(
					'name' => __( 'Payment Received', 'sprout-invoices' ),
					'description' => __( 'Customize the email sent to an admin when any payment is received.', 'sprout-invoices' ),
					'shortcodes' => array( 'date', 'name', 'username', 'payment_total', 'payment_id', 'line_item_table', 'line_item_list', 'line_item_plain_list', 'invoice_subject', 'invoice_id', 'invoice_edit_url', 'invoice_url', 'invoice_issue_date', 'invoice_due_date', 'invoice_past_due_date', 'invoice_po_number', 'invoice_terms', 'invoice_notes', 'invoice_total', 'invoice_payments_list', 'invoice_payments_list_html', 'invoice_subtotal', 'invoice_calculated_total', 'invoice_total_due', 'invoice_deposit_amount', 'invoice_total_payments', 'client_name', 'client_edit_url', 'client_address', 'client_company_website' ),
					'default_title' => sprintf( __( '%s: Payment Received', 'sprout-invoices' ), get_bloginfo( 'name' ) ),
					'default_content' => self::load_view_to_string( 'notifications/admin-payment', null, false ),
				),
			);
		return array_merge( $notifications, $default_notifications );
	}

	public static function register_shortcodes( $shortcodes = array() ) {
		// Notification shortcodes include the code, a description, and a callback
		// Most shortcodes should be defined by a different controller using the 'sprout_notification_shortcodes' filter
		$default_shortcodes = array(
				'date' => array(
					'description' => __( 'Used to display the date.', 'sprout-invoices' ),
					'callback' => array( 'SI_Notifications', 'shortcode_date' ),
					),
				'name' => array(
					'description' => __( 'Used to display the user&rsquo;s name.', 'sprout-invoices' ),
					'callback' => array( 'SI_Notifications', 'shortcode_sender_name' ),
					),
				'username' => array(
					'description' => __( 'Used to display the user&rsquo;s login.', 'sprout-invoices' ),
					'callback' => array( 'SI_Notifications', 'shortcode_username' ),
					),
				'admin_note' => array(
					'description' => __( 'Used to display the note created before sending.', 'sprout-invoices' ),
					'callback' => array( 'SI_Notifications', 'shortcode_admin_note' ),
					),
				'payment_total' => array(
						'description' => __( 'Used to display the payment total.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_payment_total' ),
					),
				'payment_id' => array(
						'description' => __( 'Used to display the payment id (post_id).', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_payment_id' ),
					),
				'line_item_table' => array(
						'description' => __( 'Used to display the line items for an estimate or invoice in a table format (HTML).', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_line_item_table' ),
					),
				'line_item_list' => array(
						'description' => __( 'Used to display the line items for an estimate or invoice in a list format (HTML).', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_line_item_list' ),
					),
				'line_item_plain_list' => array(
						'description' => __( 'Used to display the line items for an estimate or invoice in a plain text list.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_line_item_plain_list' ),
					),
				'invoice_subject' => array(
						'description' => __( 'Used to display the invoice subject.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_subject' ),
					),
				'invoice_id' => array(
						'description' => __( 'Used to display the invoice id.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_id' ),
					),
				'invoice_edit_url' => array(
						'description' => __( 'Used to display the invoice edit url.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_edit_url' ),
					),
				'invoice_url' => array(
						'description' => __( 'Used to display the invoice url.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_url' ),
					),
				'invoice_issue_date' => array(
						'description' => __( 'Used to display the invoice issue date.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_issue_date' ),
					),
				'invoice_due_date' => array(
						'description' => __( 'Used to display the due date.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_due_date' ),
					),
				'invoice_past_due_date' => array(
						'description' => __( 'Used to display how many days the invoice is past due.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_past_due_date' ),
					),
				'invoice_po_number' => array(
						'description' => __( 'Used to display the invoice po number.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_po_number' ),
					),
				'invoice_tax_total' => array(
						'description' => __( 'Used to display the invoice tax total.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_tax_total' ),
					),
				'invoice_tax' => array(
						'description' => __( 'Used to display the invoice tax.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_tax' ),
					),
				'invoice_tax2' => array(
						'description' => __( 'Used to display the invoice tax (2).', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_tax2' ),
					),
				'invoice_notes' => array(
						'description' => __( 'Used to display the invoice notes.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_notes' ),
					),
				'invoice_terms' => array(
						'description' => __( 'Used to display the invoice terms.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_terms' ),
					),
				'invoice_total' => array(
						'description' => __( 'Used to display the invoice total.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_total' ),
					),
				'invoice_payments_list' => array(
						'description' => __( 'Used to display the invoice payments.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_payments_list' ),
					),
				'invoice_payments_list_html' => array(
						'description' => __( 'Used to display the invoice payments (in html).', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_payments_list_html' ),
					),
				'invoice_calculated_total' => array(
						'description' => __( 'Used to display the invoice calculated total.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_calculated_total' ),
					),
				'invoice_subtotal' => array(
						'description' => __( 'Used to display the invoice sub total.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_subtotal' ),
					),
				'invoice_total_due' => array(
						'description' => __( 'Used to display the total amount due, or deposit if set.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_total_due' ),
					),
				'invoice_deposit_amount' => array(
						'description' => __( 'Used to display the deposit amount due.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_deposit_amount' ),
					),
				'invoice_total_payments' => array(
						'description' => __( 'Used to display the total of all payments.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_invoice_total_payments' ),
					),
				'client_name' => array(
						'description' => __( 'Used to display the client name.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_client_name' ),
					),
				'client_edit_url' => array(
						'description' => __( 'Used to display the client edit url.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_client_edit_url' ),
					),
				'client_company_website' => array(
					'description' => __( 'Used to display the client&rsquo;s company website address.', 'sprout-invoices' ),
					'callback' => array( 'SI_Notifications', 'shortcode_client_website' ),
					),
				'client_address' => array(
					'description' => __( 'Used to display the client&rsquo;s address (HTML).', 'sprout-invoices' ),
					'callback' => array( 'SI_Notifications', 'shortcode_client_address' ),
					),
				'estimate_subject' => array(
						'description' => __( 'Used to display the estimate subject.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_subject' ),
					),
				'estimate_id' => array(
						'description' => __( 'Used to display the estimate id.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_id' ),
					),
				'estimate_edit_url' => array(
						'description' => __( 'Used to display the edit url.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_edit_url' ),
					),
				'estimate_url' => array(
						'description' => __( 'Used to display the estimate url.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_url' ),
					),
				'estimate_issue_date' => array(
						'description' => __( 'Used to display the estimate issue date.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_issue_date' ),
					),
				'estimate_po_number' => array(
						'description' => __( 'Used to display the estimate po number.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_po_number' ),
					),
				'estimate_total' => array(
						'description' => __( 'Used to display the estimate total.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_total' ),
					),
				'estimate_subtotal' => array(
						'description' => __( 'Used to display the estimate total.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_subtotal' ),
					),
				'estimate_tax_total' => array(
						'description' => __( 'Used to display the estimate tax total.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_tax_total' ),
					),
				'estimate_tax' => array(
						'description' => __( 'Used to display the estimate tax.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_tax' ),
					),
				'estimate_tax2' => array(
						'description' => __( 'Used to display the estimate tax (2).', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_tax2' ),
					),
				'estimate_notes' => array(
						'description' => __( 'Used to display the estimate notes.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_notes' ),
					),
				'estimate_terms' => array(
						'description' => __( 'Used to display the estimate terms.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_estimate_terms' ),
					),
				'lead_entries' => array(
						'description' => __( 'Used to display the lead entries in HTML.', 'sprout-invoices' ),
						'callback' => array( 'SI_Notifications', 'shortcode_lead_entries' ),
					),

			);
		return array_merge( $shortcodes, $default_shortcodes );
	}

	/////////////////////////////
	// notification callbacks //
	/////////////////////////////

	public static function estimate_notification( SI_Estimate $estimate, $recipients = array(), $from_email = null, $from_name = null ) {
		$invoice = '';
		if ( $invoice_id = $estimate->get_invoice_id() ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
		}

		// If status is temp than change to pending.
		if ( SI_Estimate::STATUS_TEMP === $estimate->get_status() ) {
			$estimate->set_pending();
		}

		foreach ( array_unique( $recipients ) as $user_id ) {
			/**
			 * sometimes the recipients list will pass an email instead of an id
			 * attempt to find a user first.
			 */
			if ( is_email( $user_id ) ) {
				if ( $user = get_user_by( 'email', $user_id ) ) {
					$user_id = $user->ID;
					$to = self::get_user_email( $user_id );
				} else { // no user found
					$to = $user_id;
				}
			} else {
				$to = self::get_user_email( $user_id );
			}
			$data = array(
				'user_id' => ( is_numeric( $user_id ) ) ? $user_id : '',
				'estimate' => $estimate,
				'invoice' => $invoice,
				'to' => $to,
			);

			self::send_notification( 'send_estimate', $data, $to, $from_email, $from_name );
		}
	}

	public static function invoice_notification( SI_Invoice $invoice, $recipients = array(), $from_email = null, $from_name = null ) {
		$estimate = '';
		if ( $estimate_id = $invoice->get_estimate_id() ) {
			$estimate = SI_Estimate::get_instance( $estimate_id );
		}

		// If status is temp than change to pending.
		if ( SI_Invoice::STATUS_TEMP === $invoice->get_status() ) {
			$invoice->set_pending();
		}

		foreach ( array_unique( $recipients ) as $user_id ) {

			/**
			 * sometimes the recipients list will pass an email instead of an id
			 * attempt to find a user first.
			 */
			if ( is_email( $user_id ) ) {
				if ( $user = get_user_by( 'email', $user_id ) ) {
					$user_id = $user->ID;
					$to = self::get_user_email( $user_id );
				} else { // no user found
					$to = $user_id;
				}
			} else {
				$to = self::get_user_email( $user_id );
			}

			$data = array(
				'user_id' => ( is_numeric( $user_id ) ) ? $user_id : '',
				'invoice' => $invoice,
				'estimate' => $estimate,
				'to' => $to,
			);

			self::send_notification( 'send_invoice', $data, $to, $from_email, $from_name );
		}
	}

	public static function paid_notification( SI_Payment $payment ) {
		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance( $invoice_id );
		if ( is_a( $invoice, 'SI_Invoice' ) && $invoice->get_balance() < 0.01 ) { // leave a bit of room for floating point arithmetic
			$client = $invoice->get_client();
			$client_users = self::get_document_recipients( $invoice );
			foreach ( array_unique( $client_users ) as $user_id ) {
				if ( ! is_wp_error( $user_id ) ) {
					$to = self::get_user_email( $user_id );
					$data = array(
						'payment' => $payment,
						'invoice' => $invoice,
						'client' => ( is_a( $client, 'SI_Client' ) ) ? $client : 0,
						'user_id' => $user_id,
						'to' => $to,
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
		if ( ! is_a( $doc, 'SI_Estimate' ) ) {
			return;
		}
		// Check if status changed was to approved.
		if ( $doc->get_status() !== SI_Estimate::STATUS_APPROVED ) {
			return;
		}
		$estimate = $doc;
		$invoice = '';
		$invoice_id = $estimate->get_invoice_id();
		if ( $invoice_id ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
		}
		// Admin email
		$data = array(
			'user_id' => $estimate->get_user_id(),
			'estimate' => $estimate,
			'invoice' => $invoice,
			'client' => $estimate->get_client(),
		);
		$admin_to = self::admin_email( $data );
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
		if ( ! is_a( $doc, 'SI_Estimate' ) ) {
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
		$data = array(
			'user_id' => $estimate->get_user_id(),
			'estimate' => $estimate,
			'invoice' => $invoice,
			'client' => $estimate->get_client(),
		);
		$admin_to = self::admin_email( $data );
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
		if ( apply_filters( 'si_disable_payment_notification_by_payment_method', false, $payment_method ) ) {
			return;
		}

		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance( $invoice_id );
		if ( ! is_a( $invoice, 'SI_Invoice' ) ) {
			do_action( 'si_error', 'Admin Payment Notification Not Sent to Client; No Invoice Found: ' . $invoice_id, $payment->get_id() );
			return;
		}
		$client = $invoice->get_client();

		// Admin email
		$data = array(
			'payment' => $payment,
			'invoice' => $invoice,
			'client' => $client,
		);
		$admin_to = self::admin_email( $data );
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
		$date = date_i18n( $atts['format'], current_time( 'timestamp' ) );
		return apply_filters( 'shortcode_date', $date, $data );
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
		$name = __( 'Client', 'sprout-invoices' );
		$to = ( isset( $data['to'] ) ) ? $data['to'] : 0 ;
		$user_id = self::get_notification_instance_user_id( $to, $data );
		if ( is_numeric( $user_id ) && $user_id ) {
			// Fallback to Username
			$user = get_userdata( $user_id );
			// Build off first and last name.
			$name = $user->first_name . ' ' . $user->last_name;
			// no first and last name?
			if ( $name == ' ' ) {
				$name = $user->display_name;
			}
		}
		// If no user can be found attempt to use the client.
		if ( $name == __( 'Client', 'sprout-invoices' ) ) {
			if ( isset( $data['client'] ) && is_a( $data['client'], 'SI_Client' ) ) {
				$client_id = $data['client']->get_id();
				$name = get_the_title( $client_id );
			}
		}
		return apply_filters( 'shortcode_sender_name', $name, $data );
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
		return __( 'Client', 'sprout-invoices' );
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
		} else {
			if ( $data['estimate'] && is_a( $data['estimate'], 'SI_Estimate' ) ) {
				$sender_note = $data['estimate']->get_sender_note();
			}
		}
		return apply_filters( 'shortcode_admin_note', $sender_note, $data );
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
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['payment'] ) ) {
			$amount = sa_get_formatted_money( $data['payment']->get_amount(), $data['invoice']->get_id() );
		}
		return apply_filters( 'shortcode_payment_total', $amount, $data );
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
		$doc_id = 0;
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$line_items = $data['invoice']->get_line_items();
			$doc_id = $data['invoice']->get_id();
		} else {
			if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
				$line_items = $data['estimate']->get_line_items();
				$doc_id = $data['estimate']->get_id();
			}
		}
		if ( $doc_id ) {
			// Set the global post to pass the doc id around town
			global $post;
			if ( ! is_a( $post, 'Post' ) ) {
				$post = new stdClass;
				$post->ID = $doc_id;
				$post = new WP_Post( (object) $post );
			}
		}
		if ( empty( $line_items ) ) {
			return '';
		}
		ob_start(); ?>
			<table>
				<thead>
					<tr>
						<th><?php _e( '#', 'sprout-invoices' ) ?></th>
						<th><?php _e( 'Description', 'sprout-invoices' ) ?></th>
						<th><?php _e( 'Rate', 'sprout-invoices' ) ?></th>
						<th><?php _e( 'Quantity', 'sprout-invoices' ) ?></th>
						<th><?php _e( '% Adjustment', 'sprout-invoices' ) ?></th>
						<th><?php _e( 'Total', 'sprout-invoices' ) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $line_items as $position => $ldata ) : ?>
						<tr class="item" data-id="<?php echo (float) $position ?>">
							<td><?php esc_attr_e( $position + 1 ) ?></td>
							<td><?php echo apply_filters( 'the_content', $ldata['desc'] ) ?></td>
							<td><?php esc_attr_e( $ldata['rate'] ) ?></td>
							<td><?php esc_attr_e( $ldata['qty'] ) ?></td>
							<td><?php esc_attr_e( $ldata['tax'] ) ?>%</td>
							<td><?php sa_formatted_money( $ldata['total'], $doc_id ) ?></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		<?php
		$table = ob_get_clean();
		return apply_filters( 'shortcode_line_item_table', $table, $line_items, $data );
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
		$doc_id = 0;
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$line_items = $data['invoice']->get_line_items();
			$doc_id = $data['invoice']->get_id();
		} else {
			if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
				$line_items = $data['estimate']->get_line_items();
				$doc_id = $data['estimate']->get_id();
			}
		}
		if ( $doc_id ) {
			// Set the global post to pass the doc id around town
			global $post;
			if ( ! is_a( $post, 'Post' ) ) {
				$post = new stdClass;
				$post->ID = $doc_id;
				$post = new WP_Post( (object) $post );
			}
		}
		if ( empty( $line_items ) ) {
			return '';
		}

		$view = '';
		$prev_type = '';
		foreach ( $line_items as $position => $item_data ) {
			if ( is_int( $position ) ) {

				$children = si_line_item_get_children( $position, $line_items );
				$has_children = ( ! empty( $children ) ) ? true : false ;

				$line_item = si_get_plain_text_line_item( $item_data, $position, $prev_type, $has_children );

				$view .= sprintf( "\n\n* %s", $line_item );

				if ( $has_children ) {
					foreach ( $children as $child_position => $item_data ) {
						$line_item = si_get_plain_text_line_item( $line_items[ $child_position ], $child_position, $item_data['type'], false );
						$view .= sprintf( "\n\t %s", $line_item );
					}
				}
				$prev_type = $item_data['type'];
			}
		}
		return apply_filters( 'shortcode_line_item_plain_list', wp_strip_all_tags( $view ), $line_items, $data );
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
		} else {
			if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
				$line_items = $data['estimate']->get_line_items();
			}
		}
		if ( $doc_id ) {
			// Set the global post to pass the doc id around town
			global $post;
			if ( ! is_a( $post, 'Post' ) ) {
				$post = new stdClass;
				$post->ID = $doc_id;
				$post = new WP_Post( (object) $post );
			}
		}
		if ( empty( $line_items ) ) {
			return '';
		}
		ob_start(); ?>
			<ol>
				<?php foreach ( $line_items as $position => $data ) : ?>
					<?php if ( is_int( $position ) ) : // is not a child ?>
						<li class="item" data-id="<?php echo (float) $position ?>">
							<?php
								// get the children of this top level item
								$children = si_line_item_get_children( $position, $line_items ); ?>

							<?php
								// build single item
								echo si_line_item_build( $position, $line_items, $children ) ?>

							<?php if ( ! empty( $children ) ) : // if has children, loop and show  ?>
								<ol class="items_list">
									<?php foreach ( $children as $child_position ) : ?>
										<li class="item" data-id="<?php echo (float) $child_position ?>"><?php echo si_line_item_build( $child_position, $line_items ) ?></li>
									<?php endforeach ?>
								</ol>
							<?php endif ?>
						</li>
					<?php endif ?>
				<?php endforeach ?>
			</ol>
			
		<?php
		$table = ob_get_clean();
		return apply_filters( 'shortcode_line_item_list', $table, $line_items, $data );
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
		if ( ! isset( $data['invoice'] ) && $data['estimate'] ) {
			$invoice_id = $data['estimate']->get_invoice_id();
		}
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$invoice_id = $data['invoice']->get_id();

		}
		$subject = ( $invoice_id ) ? html_entity_decode( get_the_title( $invoice_id ) ) : '' ;
		return apply_filters( 'shortcode_invoice_subject', $subject, $invoice_id, $data );
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
		return apply_filters( 'shortcode_invoice_id', $invoice_id, $data );
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
			// $url = get_edit_post_link( $invoice_id, '' ); // Doesn't work so it needs to be built manually.
			$post_type_object = get_post_type_object( SI_Invoice::POST_TYPE );
			$url = apply_filters( 'get_edit_post_link', admin_url( sprintf( $post_type_object->_edit_link . '&action=edit', $invoice_id ) ), $invoice_id, '' );
		}
		return apply_filters( 'shortcode_invoice_edit_url', esc_url_raw( $url ), $data );
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
		return apply_filters( 'shortcode_invoice_url', esc_url_raw( $url ), $data );
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
			$date = date_i18n( get_option( 'date_format' ), $timestamp );
		}
		return apply_filters( 'shortcode_invoice_issue_date', $date, $data );
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
			$date = date_i18n( get_option( 'date_format' ), $timestamp );
		}
		return apply_filters( 'shortcode_invoice_due_date', $date, $data );
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
			$due_date = $data['invoice']->get_due_date();
			$pastdue = current_time( 'timestamp' ) -$due_date;
			$days = floor( $pastdue / (DAY_IN_SECONDS) );
		}
		return apply_filters( 'shortcode_invoice_past_due_date', $days, $data );
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
		return apply_filters( 'shortcode_invoice_po_number', $po_number, $data );
	}

	/**
	 * Return the invoice tax total
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_invoice_tax_total( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$tax_total = $data['invoice']->get_tax_total() + $data['invoice']->get_tax2_total();
			$amount = sa_get_formatted_money( $tax_total, $data['invoice']->get_id() );
		}
		return apply_filters( 'shortcode_invoice_tax_total', $amount, $data );
	}


	/**
	 * Return the invoice tax
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_invoice_tax( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_tax_total(), $data['invoice']->get_id() );
		}
		return apply_filters( 'shortcode_invoice_tax', $amount, $data );
	}


	/**
	 * Return the invoice tax 2
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_invoice_tax2( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_tax2_total(), $data['invoice']->get_id() );
		}
		return apply_filters( 'shortcode_invoice_tax', $amount, $data );
	}


	/**
	 * Return the invoice terms
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_invoice_terms( $atts, $content, $code, $data ) {
		$terms = '';
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$terms = $data['invoice']->get_terms();
		}
		return apply_filters( 'shortcode_invoice_terms', $terms, $data );
	}


	/**
	 * Return the invoice notes
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_invoice_notes( $atts, $content, $code, $data ) {
		$notes = '';
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$notes = $data['invoice']->get_notes();
		}
		return apply_filters( 'shortcode_invoice_notes', $notes, $data );
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
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_total(), $data['invoice']->get_ID() );
		}
		return apply_filters( 'shortcode_invoice_total', $amount, $data );
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
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_calculated_total(), $data['invoice']->get_id() );
		}
		return apply_filters( 'shortcode_invoice_calculated_total', $amount, $data );
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
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_subtotal(), $data['invoice']->get_id() );
		}
		return apply_filters( 'shortcode_invoice_subtotal', $amount, $data );
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
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			if ( $data['invoice']->get_deposit() > 0.01 ) {
				$amount = sa_get_formatted_money( $data['invoice']->get_deposit(), $data['invoice']->get_id() );
			} else {
				$amount = sa_get_formatted_money( $data['invoice']->get_balance(), $data['invoice']->get_id() );
			}
		}
		return apply_filters( 'shortcode_invoice_total_due', $amount, $data );
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
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_deposit(), $data['invoice']->get_id() );
		}
		return apply_filters( 'shortcode_invoice_deposit_amount', $amount, $data );
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
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$amount = sa_get_formatted_money( $data['invoice']->get_payments_total(), $data['invoice']->get_id() );
		}
		return apply_filters( 'shortcode_invoice_total_payments', $amount, $data );
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
	public static function shortcode_invoice_payments_list( $atts, $content, $code, $data ) {
		if ( ! isset( $data['invoice'] ) || ! is_a( $data['invoice'], 'SI_Invoice' ) ) {
			return;
		}
		$invoice_id = $data['invoice']->get_id();
		$payments = $data['invoice']->get_payments();
		if ( empty( $payments ) ) {
			return;
		}

		$payments_list = '';

		foreach ( $payments as $payment_id ) {
			$payment = SI_Payment::get_instance( $payment_id );
			$method = ( strpos( strtolower( $payment->get_payment_method() ), 'credit' ) !== false && $payment->get_payment_method() !== 'Credit' ) ? __( 'Credit Card', 'sprout-invoices' ) : $payment->get_payment_method();
			$method_name = apply_filters( 'si_display_payment_name', $method, $payment );

			$payments_list = sprintf( __( '%1$s: %2$s on %3$s\\n', 'sprout-invoices' ), $method_name, sa_get_formatted_money( $payment->get_amount(), $invoice_id ), date( get_option( 'date_format' ), strtotime( $payment->get_post_date() ) ) );
		}

		return apply_filters( 'shortcode_invoice_payments_list', $payments_list, $data );
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
	public static function shortcode_invoice_payments_list_html( $atts, $content, $code, $data ) {
		if ( ! isset( $data['invoice'] ) || ! is_a( $data['invoice'], 'SI_Invoice' ) ) {
			return;
		}
		$invoice_id = $data['invoice']->get_id();
		$payments = $data['invoice']->get_payments();
		if ( empty( $payments ) ) {
			return;
		}

		$payments_list = '';

		foreach ( $payments as $payment_id ) {
			$payment = SI_Payment::get_instance( $payment_id );
			$method = ( strpos( strtolower( $payment->get_payment_method() ), 'credit' ) !== false && $payment->get_payment_method() !== 'Credit' ) ? __( 'Credit Card', 'sprout-invoices' ) : $payment->get_payment_method();
			$method_name = apply_filters( 'si_display_payment_name', $method, $payment );

			$payments_list = sprintf( __( '<strong>%1$s</strong>: %2$s on %3$s<br/>', 'sprout-invoices' ), $method_name, sa_get_formatted_money( $payment->get_amount(), $invoice_id ), date( get_option( 'date_format' ), strtotime( $payment->get_post_date() ) ) );
		}

		return apply_filters( 'shortcode_invoice_payments_list_html', $payments_list, $data );
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
		if ( ! $client_id && isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$client_id = $data['invoice']->get_client_id();
		}
		if ( ! $client_id && isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$client_id = $data['estimate']->get_client_id();
		}
		$name = ( $client_id ) ? get_the_title( $client_id ) : '' ;
		return apply_filters( 'shortcode_client_name', $name, $data );
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
	public static function shortcode_client_website( $atts, $content, $code, $data ) {
		$client_id = 0;
		if ( isset( $data['client'] ) && is_a( $data['client'], 'SI_Client' ) ) {
			$client_id = $data['client']->get_id();
		}
		if ( ! $client_id && isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$client_id = $data['invoice']->get_client_id();
		}
		if ( ! $client_id && isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$client_id = $data['estimate']->get_client_id();
		}
		$client = SI_Client::get_instance( $client_id );
		$website = ( is_a( $client, 'SI_Client' ) ) ? $client->get_website() : '' ;
		return apply_filters( 'shortcode_client_website', $website, $data );
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
	public static function shortcode_client_address( $atts, $content, $code, $data ) {
		$client_id = 0;
		if ( isset( $data['client'] ) && is_a( $data['client'], 'SI_Client' ) ) {
			$client_id = $data['client']->get_id();
		}
		if ( ! $client_id && isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$client_id = $data['invoice']->get_client_id();
		}
		if ( ! $client_id && isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$client_id = $data['estimate']->get_client_id();
		}
		$client = SI_Client::get_instance( $client_id );
		$address = ( is_a( $client, 'SI_Client' ) ) ? si_format_address( $client->get_address(), 'string', '<br/>' ) : '' ;
		return apply_filters( 'shortcode_client_address', $address, $data );
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
		return apply_filters( 'shortcode_client_edit_url', esc_url_raw( $url ), $data );
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
		return apply_filters( 'shortcode_estimate_subject', $title, $data );
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
		return apply_filters( 'shortcode_estimate_id', $estimate_id, $data );
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
		return apply_filters( 'shortcode_estimate_edit_url', esc_url_raw( $url ), $data );
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
		return apply_filters( 'shortcode_estimate_url', esc_url_raw( $url ), $data );
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
			$date = date_i18n( get_option( 'date_format' ), $timestamp );
		}
		return apply_filters( 'shortcode_estimate_issue_date', $date, $data );
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
		return apply_filters( 'shortcode_estimate_po_number', $po_number, $data );
	}

	/**
	 * Return the estimate tax total
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_estimate_tax_total( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$tax_total = $data['estimate']->get_tax_total() + $data['estimate']->get_tax2_total();
			$amount = sa_get_formatted_money( $tax_total, $data['estimate']->get_id() );
		}
		return apply_filters( 'shortcode_estimate_tax_total', $amount, $data );
	}


	/**
	 * Return the estimate tax
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_estimate_tax( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$amount = sa_get_formatted_money( $data['estimate']->get_tax_total(), $data['estimate']->get_id() );
		}
		return apply_filters( 'shortcode_estimate_tax', $amount, $data );
	}


	/**
	 * Return the estimate tax 2
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_estimate_tax2( $atts, $content, $code, $data ) {
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$amount = sa_get_formatted_money( $data['estimate']->get_tax2_total(), $data['estimate']->get_id() );
		}
		return apply_filters( 'shortcode_estimate_tax', $amount, $data );
	}


	/**
	 * Return the estimate terms
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_estimate_terms( $atts, $content, $code, $data ) {
		$terms = '';
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$terms = $data['estimate']->get_terms();
		}
		return apply_filters( 'shortcode_estimate_terms', $terms, $data );
	}


	/**
	 * Return the estimate notes
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @param  string $code
	 * @param  array $data
	 * @return string          filtered
	 */
	public static function shortcode_estimate_notes( $atts, $content, $code, $data ) {
		$notes = '';
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$notes = $data['estimate']->get_notes();
		}
		return apply_filters( 'shortcode_estimate_notes', $notes, $data );
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
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$amount = sa_get_formatted_money( $data['estimate']->get_total(), $data['estimate']->get_id() );
		}
		return apply_filters( 'shortcode_estimate_total', $amount, $data );
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
		$amount = sa_get_formatted_money( 0 );
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$amount = sa_get_formatted_money( $data['estimate']->get_subtotal(), $data['estimate']->get_id() );
		}
		return apply_filters( 'shortcode_estimate_subtotal', $amount, $data );
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
			if ( ! empty( $data['submission_fields']['fields'] ) ) {
				ob_start(); ?>
					<?php foreach ( $data['submission_fields']['fields'] as $key => $value ) : ?>
						<?php if ( isset( $value['data']['label'] ) && isset( $value['data']['type'] ) && $value['data']['type'] != 'hidden' ) : ?>
							<dt><?php echo esc_html( $value['data']['label'] ); ?></dt>
							<?php if ( is_numeric( $value['value'] ) && strpos( $value['data']['label'], __( 'Type', 'sprout-invoices' ) ) !== false ) : ?>
								<dd><p><?php
										$term = get_term_by( 'id', $value['value'], SI_Estimate::PROJECT_TAXONOMY );
								if ( ! is_wp_error( $term ) ) {
									_e( $term->name, 'sprout-invoices' );
								}
										?></p></dd>
							<?php else : ?>
								<dd><?php echo apply_filters( 'the_content', $value['value'] ) ?></dd>
							<?php endif ?>
						<?php endif ?>
					<?php endforeach;

				$entries = ob_get_clean();
			}
		}
		return apply_filters( 'shortcode_lead_entries', $entries, $data );
	}
}
