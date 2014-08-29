<?php

/**
 * Offsite payments controller, extends all payment processors
 *
 * @package Sprout_Invoice
 * @subpackage Payments
 */
abstract class SI_Offsite_Processors extends SI_Payment_Processors {
	// payment handler
	const PAYMENT_HANDLER_QUERY_ARG = 'payment_handler';
	private static $payment_handler_path = 'payment_handler';
	private static $payment_handler = NULL;


	protected function __construct() {
		parent::__construct();
		self::register_query_var( self::PAYMENT_HANDLER_QUERY_ARG, array( __CLASS__, 'handle_action' ) );

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
		return FALSE;
	}

	/**
	 * Handler action
	 * @return  string
	 */
	public static function handler_url() {
		return home_url( add_query_arg( self::PAYMENT_HANDLER_QUERY_ARG, 1 ) );		
	}

	/**
	 * Callback from payment handler query variable.
	 * @return  
	 */
	private function handle_action() {
		self::do_not_cache();
		if ( isset( $_GET[self::PAYMENT_HANDLER_QUERY_ARG] ) ) {
			do_action( 'si_payment_handler_'.strtolower( $_GET[self::PAYMENT_HANDLER_QUERY_ARG] ), $this );
		}
		do_action( 'si_payment_handler', $_POST, $this );
	}
}
