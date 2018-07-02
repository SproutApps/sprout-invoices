<?php

/**
 *
 *
 * @package Sprout_Invoice
 * @subpackage SI_Notifications_Test
 */
class SI_Notifications_Test extends SI_Notifications {
	const SEND_ACTION = 'si_send_test_estimate';

	public static function init() {
		// register settings
		add_filter( 'si_notification_settings', array( __CLASS__, 'register_settings' ) );

		// enqueue javascript
		if ( is_admin() ) {
			// Enqueue
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ), 20 );
		}

		// AJAX action to handle test request
		add_action( 'wp_ajax_' . self::SEND_ACTION, array( __CLASS__, 'maybe_send_test' ) );
	}


	////////////
	// admin //
	////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings( $settings = array() ) {
		// Settings
		$settings['html_notifications'] = array(
				'title' => __( 'Test Notifications', 'sprout-invoices' ),
				'weight' => 30.2,
				'tab' => 'settings',
				'settings' => array(
					'test_notifications' => array(
						'label' => __( 'Test Notification', 'sprout-invoices' ),
						'option' => array(
							'type' => 'bypass',
							'output' => self::test_send_selection(),
							),
						),
					),
				);
		return $settings;
	}

	//////////////
	// Enqueue //
	//////////////

	public static function register_resources() {
		// admin js
		wp_register_script( 'si_test_notifications', SI_URL . '/resources/admin/js/notification-tests.js', array( 'jquery' ), self::SI_VERSION );
	}

	public static function admin_enqueue() {
		wp_localize_script( 'si_test_notifications', 'test_notification',
			array(
				'action' => self::SEND_ACTION,
				'sent_button_text' => __( 'Send Another', 'sprout-invoices' ),
				'warning' => __( 'Are you sure? This will delete any customized notifications and replace them with the default HTML templates.', 'sprout-invoices' ),
				)
		);
		wp_enqueue_script( 'si_test_notifications' );
	}


	//////////
	// Test //
	//////////

	public static function notifications_to_test() {
		$notifications = array();
		$notifications['send_invoice'] = array(
				'test_name' => __( 'Invoice Available', 'sprout-invoices' ),
				'record_types' => array( 'invoices' ), // an array but needs to be a single record type ATM
				'action_name' => 'send_invoice', // if different than the key
				'description' => __( 'Select a recent invoice and send a test invoice notification to yourself.', 'sprout-invoices' ),
			);
		$notifications['send_estimate'] = array(
				'test_name' => __( 'Estimate Available', 'sprout-invoices' ),
				'record_types' => array( 'estimates' ), // an array but needs to be a single record type ATM
				'action_name' => 'send_estimate', // if different than the key
				'description' => __( 'Select a recent estimate and send a test estimate notification to yourself.', 'sprout-invoices' ),
			);
		$notifications['payment_complete'] = array(
				'test_name' => __( 'Payment Received', 'sprout-invoices' ),
				'record_types' => array( 'payments' ), // an array but needs to be a single record type ATM
				'action_name' => 'payment_complete', // if different than the key
				'description' => __( 'Select a recent payment and send a test payment notification to yourself.', 'sprout-invoices' ),
			);
		$notifications['doc_status_changed'] = array(
				'test_name' => __( 'Estimate Accepted/Declined (to admin)', 'sprout-invoices' ),
				'record_types' => array( 'estimates' ), // an array but needs to be a single record type ATM
				'action_name' => 'doc_status_changed', // if different than the key
				'description' => __( 'Select a recent estimate and send a test admin notification to show that the estimate was declined or accepted. Note: estimate selected must have the status of accepted or declined.', 'sprout-invoices' ),
			);
		$notifications['si_new_payment'] = array(
				'test_name' => __( 'New Payment (to admin)', 'sprout-invoices' ),
				'record_types' => array( 'payments' ), // an array but needs to be a single record type ATM
				'action_name' => 'si_new_payment', // if different than the key
				'description' => __( 'Select a recent payment and send a test admin payment notification to yourself.', 'sprout-invoices' ),
			);
		return apply_filters( 'notifications_to_test', $notifications );
	}

	public static function test_send_selection() {

		$args = array(
			'post_type' => SI_Client::POST_TYPE,
			'post_status' => 'publish',
			'posts_per_page' => 5,
			'fields' => 'ids',
		);
		$recent_clients = get_posts( apply_filters( 'si_get_recent_clients_for_selection', $args ) );

		return self::load_view_to_string( 'admin/options/test-send-selection.php', array(
				'notification_types' => self::notifications_to_test(),
				'recent_invoices' => self::get_recent_records( SI_Invoice::POST_TYPE ),
				'recent_estimates' => self::get_recent_records( SI_Estimate::POST_TYPE ),
				'recent_payments' => self::get_recent_records( SI_Payment::POST_TYPE ),
				'recent_clients' => self::get_recent_records( SI_Client::POST_TYPE ),
		), false );
	}

	public static function get_recent_records( $post_type = '', $count = 3 ) {
		$args = array(
			'post_type' => $post_type,
			'post_status' => 'publish',
			'posts_per_page' => $count,
			'fields' => 'ids',
		);
		$recent_records = get_posts( apply_filters( 'si_get_recent_records_for_selection', $args, $post_type ) );
		return $recent_records;
	}

	//////////////////
	// Send Control //
	//////////////////

	public static function maybe_send_test() {
		if ( ! current_user_can( 'manage_sprout_invoices_options' ) ) {
			return;
		}

		$data = $_POST;
		if ( ! isset( $data['notification'] ) ) {
			wp_send_json_error( array( 'message' => 'Doh. Error: TS65' ) );
		}
		self::test_send( $data['notification'], $data['record_selected'] );

	}

	public static function test_send( $action_name = '', $associated_record = 0 ) {

		$test_notifications = self::notifications_to_test();
		if ( ! isset( $test_notifications[ $action_name ] ) ) {
			wp_send_json_error( array( 'message' => 'Doh. Error: TS66' ) );
		}

		add_filter( 'si_is_test_notification', '__return_true', 2005 );

		$test = $test_notifications[ $action_name ];
		$recipients = array( 0 ); // no recipents need to be set since it's being sent to the current user

		switch ( $test['action_name'] ) {
			case 'send_invoice':
				$invoice = SI_Invoice::get_instance( $associated_record );
				do_action( 'send_invoice', $invoice, $recipients );
				break;
			case 'send_estimate':
				$estimate = SI_Estimate::get_instance( $associated_record );
				do_action( 'send_estimate', $estimate, $recipients );
				break;
			case 'payment_complete':

				// can't use the action since there's a balance requirement
				$payment = SI_Payment::get_instance( $associated_record );
				$invoice_id = $payment->get_invoice_id();
				$invoice = SI_Invoice::get_instance( $invoice_id );
				$client = $invoice->get_client();
				$data = array(
					'payment' => $payment,
					'invoice' => $invoice,
					'client' => ( is_a( $client, 'SI_Client' ) ) ? $client : 0,
				);
				self::test_send_notification( 'final_payment', $data );
				break;

			// admin

			case 'doc_status_changed':
				$estimate = SI_Estimate::get_instance( $associated_record );

				if ( $estimate->get_status() !== ( SI_Estimate::STATUS_DECLINED || SI_Estimate::STATUS_APPROVED ) ) {
					wp_send_json_error( array( 'message' => __( 'Estimate status is neither Approved or Declined.', 'sprout-invoices' ) ) );
				}

				do_action( 'doc_status_changed', $estimate, $recipients );
				break;
			case 'si_new_payment':
				$payment = SI_Payment::get_instance( $associated_record );
				do_action( 'si_new_payment', $payment, $recipients );
				break;

			default:
				wp_send_json_error( array( 'message' => 'Doh. Error: TS67' ) );
				break;
		}

		remove_filter( 'si_is_test_notification', '__return_true', 2005 );

		$response = array(
				'message' => sprintf( __( '<p class="ajax_message">Test notification sent. Review all <a href="%s">notification records</a>.</p>', 'sprout-invoices' ), admin_url( 'tools.php?page=sprout-apps%2Fsi_records' ) ),
			);

		wp_send_json_success( $response );

	}
}
