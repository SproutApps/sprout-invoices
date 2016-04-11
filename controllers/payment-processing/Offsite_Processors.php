<?php

/**
 * Offsite payments controller, extends all payment processors
 *
 * @package Sprout_Invoice
 * @subpackage Payments
 */
abstract class SI_Offsite_Processors extends SI_Payment_Processors {

	protected function __construct() {
		parent::__construct();

		add_action( 'si_checkout_action_'.SI_Checkouts::PAYMENT_PAGE, array( $this, 'processed_payment_page' ), 20, 1 );
	}

	/**
	 * Add action when the payment page is complete and before the review page.
	 * @param  SI_Checkouts $checkout
	 * @return
	 */
	public function processed_payment_page( SI_Checkouts $checkout ) {
		if ( $checkout->is_page_complete( SI_Checkouts::PAYMENT_PAGE ) ) { // Make sure to send offsite when it's okay to do so.
			do_action( 'si_send_offsite_for_payment', $checkout );
		}
	}

	/**
	 * Add additional action for offsite payments on review page.
	 * @param  SI_Checkouts $checkout
	 * @return
	 */
	public function process_review_page( SI_Checkouts $checkout ) {
		do_action( 'si_send_offsite_for_payment_after_review', $checkout );
	}

	/**
	 * Subclasses should override this to identify if they've returned from
	 * offsite processing
	 *
	 * @static
	 * @return bool
	 */
	public static function returned_from_offsite() {
		return false;
	}
}
