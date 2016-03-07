<?php


/**
 * Invoices Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Invoices
 */
class SI_Invoices_Scheduled extends SI_Invoices {

	public static function init() {
		add_action( 'future_to_publish', array( __CLASS__, 'scheduled_post_transition' ) );
	}

	public static function scheduled_post_transition( $post ) {
		if ( SI_Invoice::POST_TYPE !== $post->post_type ) {
			return;
		}

		$invoice = SI_Invoice::get_instance( $post->ID );

		$client = $invoice->get_client();
		if ( ! is_a( $client, 'SI_Client' ) ) {
			return;
		}

		$recipients = $client->get_associated_users();
		if ( empty( $recipients ) ) {
			return;
		}

		do_action( 'send_invoice', $invoice, $recipients );
	}
}
