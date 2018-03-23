<?php


/**
 * Invoices Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Invoices
 */
class SI_Invoices extends SI_Controller {
	const HISTORY_UPDATE = 'si_history_update';
	const HISTORY_STATUS_UPDATE = 'si_history_status_update';
	const VIEWED_STATUS_UPDATE = 'si_viewed_status_update';

	public static function init() {

		// Unique urls
		add_filter( 'wp_unique_post_slug', array( __CLASS__, 'post_slug' ), 10, 4 );

		// Create invoice when estimate is approved.
		add_action( 'doc_status_changed',  array( __CLASS__, 'create_invoice_on_est_acceptance' ), 0 ); // fire before any others
		add_action( 'doc_status_changed',  array( __CLASS__, 'create_payment_when_invoice_marked_as_paid' ) );

		// reset invoice object caches
		add_action( 'si_new_payment',  array( __CLASS__, 'reset_invoice_totals_cache' ), -100 );
		add_action( 'si_payment_status_updated',  array( __CLASS__, 'reset_invoice_totals_cache' ), -100 );

		// Mark paid or partial after payment
		add_action( 'si_new_payment',  array( __CLASS__, 'change_status_after_payment_status_update' ) );
		add_action( 'si_payment_status_updated',  array( __CLASS__, 'change_status_after_payment_status_update' ) );

		// Cloning from estimates
		add_action( 'si_cloned_post',  array( __CLASS__, 'associate_invoice_after_clone' ), 10, 3 );

		// Adjust invoice id and status after clone
		add_action( 'si_cloned_post',  array( __CLASS__, 'adjust_cloned_invoice' ), 10, 3 );

		// Invoice Payment Remove deposit
		add_filter( 'processed_payment', array( __CLASS__, 'maybe_remove_deposit' ), 10, 2 );

		// Notifications
		add_filter( 'wp_ajax_sa_send_est_notification', array( __CLASS__, 'maybe_send_notification' ) );

	}

	///////////////
	// Re-writes //
	///////////////

	/**
	 * Filter the unique post slug.
	 *
	 * @param string $slug          The post slug.
	 * @param int    $post_ID       Post ID.
	 * @param string $post_status   The post status.
	 * @param string $post_type     Post type.
	 * @param int    $post_parent   Post parent ID
	 * @param string $original_slug The original post slug.
	 */
	public static function post_slug( $slug, $post_ID, $post_status, $post_type ) {
		if ( $post_type == SI_Invoice::POST_TYPE ) {
			return $post_ID;
		}
		return $slug;
	}

	/////////////////////
	// AJAX Callbacks //
	/////////////////////

	public static function maybe_send_notification() {
		// form maybe be serialized
		if ( isset( $_REQUEST['serialized_fields'] ) ) {
			foreach ( $_REQUEST['serialized_fields'] as $key => $data ) {
				if ( strpos( $data['name'], '[]' ) !== false ) {
					$_REQUEST[ str_replace( '[]', '', $data['name'] ) ][] = $data['value'];
				} else {
					$_REQUEST[ $data['name'] ] = $data['value'];
				}
			}
		}
		if ( ! isset( $_REQUEST['sa_send_metabox_notification_nonce'] ) ) {
			self::ajax_fail( 'Forget something (nonce)?' );
		}

		$nonce = $_REQUEST['sa_send_metabox_notification_nonce'];
		if ( ! wp_verify_nonce( $nonce, SI_Controller::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' );
		}

		if ( ! isset( $_REQUEST['sa_send_metabox_doc_id'] ) ) {
			self::ajax_fail( 'Forget something (id)?' );
		}

		if ( get_post_type( $_REQUEST['sa_send_metabox_doc_id'] ) !== SI_Invoice::POST_TYPE ) {
			return;
		}

		$recipients = ( isset( $_REQUEST['sa_metabox_recipients'] ) ) ? $_REQUEST['sa_metabox_recipients'] : array();

		if ( isset( $_REQUEST['sa_metabox_custom_recipient'] ) && '' !== trim( $_REQUEST['sa_metabox_custom_recipient'] ) ) {
			$submitted_recipients = explode( ',', trim( $_REQUEST['sa_metabox_custom_recipient'] ) );
			foreach ( $submitted_recipients as $key => $email ) {
				$email = trim( $email );
				if ( is_email( $email ) ) {
					$recipients[] = $email;
				}
			}
		}

		if ( empty( $recipients ) ) {
			self::ajax_fail( 'A recipient is required.' );
		}

		$invoice = SI_Invoice::get_instance( $_REQUEST['sa_send_metabox_doc_id'] );
		$invoice->set_sender_note( $_REQUEST['sa_send_metabox_sender_note'] );

		$from_email = null;
		$from_name = null;
		if ( isset( $_REQUEST['sa_send_metabox_send_as'] ) ) {
			$name_and_email = SI_Notifications_Control::email_split( $_REQUEST['sa_send_metabox_send_as'] );
			if ( is_email( $name_and_email['email'] ) ) {
				$from_name = $name_and_email['name'];
				$from_email = $name_and_email['email'];
			}
		}

		do_action( 'send_invoice', $invoice, $recipients, $from_email, $from_name );

		// If status is temp than change to pending.
		if ( ! in_array( $invoice->get_status(), array( SI_Invoice::STATUS_PENDING, SI_Invoice::STATUS_PARTIAL, SI_Invoice::STATUS_PAID ) ) ) {
			$invoice->set_pending();
		}

		header( 'Content-type: application/json' );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode( array( 'response' => __( 'Notification Queued', 'sprout-invoices' ) ) );
		exit();
	}

	////////////
	// Misc. //
	////////////

	public static function reset_invoice_totals_cache( SI_Payment $payment ) {

		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance( $invoice_id );
		if ( ! is_a( $invoice, 'SI_Invoice' ) ) {
			return;
		}

		// reset the totals since payment totals are new.
		$invoice->reset_totals( true );

	}

	public static function change_status_after_payment_status_update( SI_Payment $payment ) {

		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance( $invoice_id );
		if ( ! is_a( $invoice, 'SI_Invoice' ) ) {
			return;
		}

		switch ( $payment->get_status() ) {

			case SI_Payment::STATUS_PENDING:
			case SI_Payment::STATUS_AUTHORIZED:

				// payments are not retroactivly set to pending or authorized, so don't downgrade the status.
				if ( SI_Invoice::STATUS_TEMP === $invoice->get_status() ) {
					$invoice->set_pending();
				}

				break;

			case SI_Payment::STATUS_COMPLETE:

				if ( $invoice->get_balance() >= 0.01 ) {
					$invoice->set_as_partial();
				} else { // else there's no balance
					$invoice->set_as_paid();
				}

			case SI_Payment::STATUS_VOID:
			case SI_Payment::STATUS_REFUND:

				if ( $invoice->get_balance() >= 0.01 ) {
					if ( $invoice->get_payments_total( false ) >= 0.01 ) {
						$invoice->set_as_partial();
					} else {
						$invoice->set_pending();
					}
				} else { // else there's no balance
					$invoice->set_as_paid();
				}

				break;

			case SI_Payment::STATUS_RECURRING:
			case SI_Payment::STATUS_CANCELLED:
			default:

				// no nothing at this time.

				break;
		}

	}

	/**
	 * Create invoice when estimate is accepted.
	 * @param  object $doc estimate or invoice object
	 * @return int cloned invoice id.
	 */
	public static function create_payment_when_invoice_marked_as_paid( $doc ) {
		if ( ! is_a( $doc, 'SI_Invoice' ) ) {
			return;
		}
		// Check if status changed was to approved.
		if ( SI_Invoice::STATUS_PAID !== $doc->get_status() ) {
			return;
		}
		$balance = $doc->get_balance();
		if ( $balance < 0.01 ) {
			return;
		}
		SI_Admin_Payment::create_admin_payment( $doc->get_id(), $balance, '', 'Now', __( 'This payment was automatically added to settle the balance after it was marked as "Paid".', 'sprout-invoices' ) );
	}

	/**
	 * Create invoice when estimate is accepted.
	 * @param  object $doc estimate or invoice object
	 * @return int cloned invoice id.
	 */
	public static function create_invoice_on_est_acceptance( $doc ) {
		if ( ! is_a( $doc, 'SI_Estimate' ) ) {
			return;
		}
		// Check if status changed was to approved.
		if ( SI_Estimate::STATUS_APPROVED !== $doc->get_status() ) {
			return;
		}
		if ( apply_filters( 'si_disable_create_invoice_on_est_acceptance', false, $doc ) ) {
			return;
		}
		$invoice_post_id = self::clone_post( $doc->get_id(), SI_Invoice::STATUS_PENDING, SI_Invoice::POST_TYPE );
		$invoice = SI_Invoice::get_instance( $invoice_post_id );

		// blank
		$invoice->set_sender_note();
		// transfer over since meta_key is different
		$estimate_notes = $doc->get_notes();
		$invoice->set_notes( $estimate_notes );

		do_action( 'si_create_invoice_on_est_acceptance', $invoice, $doc );
		return $invoice_post_id;
	}

	/**
	 * Associate a newly cloned invoice with the estimate cloned from
	 * @param  integer $new_post_id
	 * @param  integer $cloned_post_id
	 * @param  string  $new_post_type
	 * @return
	 */
	public static function associate_invoice_after_clone( $new_post_id = 0, $cloned_post_id = 0, $new_post_type = '' ) {
		if ( SI_Estimate::POST_TYPE === get_post_type( $cloned_post_id ) ) {
			if ( SI_Invoice::POST_TYPE === $new_post_type ) {
				$invoice = SI_Invoice::get_instance( $new_post_id );
				$invoice->set_estimate_id( $cloned_post_id );
				$invoice->set_as_temp();
			}
		}
	}

	/**
	 * Adjust the invoice id
	 * @param  integer $new_post_id
	 * @param  integer $cloned_post_id
	 * @param  string  $new_post_type
	 * @return
	 */
	public static function adjust_cloned_invoice( $new_post_id = 0, $cloned_post_id = 0, $new_post_type = '' ) {
		if ( get_post_type( $cloned_post_id ) === SI_Estimate::POST_TYPE ) {
			$estimate = SI_Estimate::get_instance( $cloned_post_id );
			$est_id = $estimate->get_estimate_id();
			$invoice = SI_Invoice::get_instance( $new_post_id );
			if ( ! is_a( $invoice, 'SI_Invoice' ) ) {
				return;
			}
			// Adjust invoice id
			$new_id = apply_filters( 'si_adjust_cloned_invoice_id', $est_id . '-' . $new_post_id, $new_post_id, $cloned_post_id );
			$invoice->set_invoice_id( $new_id );

			// Adjust status
			$invoice->set_as_temp();
		}
	}

	public static function maybe_remove_deposit( SI_Payment $payment, SI_Checkouts $checkout ) {
		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance( $invoice_id );
		$payment_amount = $payment->get_amount();
		$invoice_deposit = $invoice->get_deposit();
		if ( $payment_amount >= $invoice_deposit ) {
			// Reset the deposit since the payment made covers it.
			$invoice->set_deposit( '' );
		}
	}

	//////////////
	// Utility //
	//////////////


	/**
	 * Used to add the invoice post type to some taxonomy registrations.
	 * @param array $post_types
	 */
	public static function add_invoice_post_type_to_taxonomy( $post_types ) {
		$post_types[] = SI_Invoice::POST_TYPE;
		return $post_types;
	}

	public static function is_edit_screen() {
		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( SI_Invoice::POST_TYPE === $screen_post_type ) {
			return true;
		}
		return false;
	}
}
