<?php


/**
 * Invoices Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Invoices
 */
class SI_Invoices_Template extends SI_Controller {

	public static function init() {
		add_filter( 'the_title', array( __CLASS__, 'prevent_auto_draft_title' ), 10, 2 );

		add_action( 'si_invoice_payment_button', array( __CLASS__, 'show_payment_options' ), 100, 2 );

		add_filter( 'si_line_item_content', array( __CLASS__, 'line_item_content_filter' ) );
	}

	/////////////
	// Content //
	/////////////

	public static function prevent_auto_draft_title( $title = '', $post_id = 0 ) {
		if ( __( 'Auto Draft' ) !== $title ) {
			return $title;
		}
		if ( SI_Invoice::POST_TYPE !== get_post_type( $post_id ) ) {
			return $title;
		}
		$invoice = SI_Invoice::get_instance( $post_id );
		return apply_filters( 'si_default_invoice_title', sprintf( '#%s', $invoice->get_invoice_id() ), $invoice );

	}

	public static function line_item_content_filter( $description = '' ) {
		if ( apply_filters( 'si_the_content_filter_line_item_descriptions', true ) ) {
			$content = apply_filters( 'the_content', $description );
		} else {
			$content = wpautop( $description );
		}
		return $content;
	}


	/////////////////
	// Templating //
	/////////////////


	/**
	 * Set a purchase action since it's a bit convoluted.
	 * @param  integer $invoice_id
	 * @return string
	 */
	public static function show_payment_options( $invoice_id = 0, $payment_string = '' ) {
		if ( ! $invoice_id ) {
			$invoice_id = get_the_id();
		}
		if ( '' === $payment_string ) {
			$payment_string = ( si_has_invoice_deposit( $invoice_id ) ) ? __( 'Pay Deposit', 'sprout-invoices' ) : __( 'Pay Invoice', 'sprout-invoices' );
		}

		self::load_view( 'templates/invoice/payment-options', array(
				'id' => $invoice_id,
				'payment_options' => si_payment_options(),
				'payment_string' => $payment_string,
		), false );
	}
}
