<?php


/**
 * Invoices Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Invoices
 */
class SI_Invoices_Records extends SI_Invoices {

	public static function init() {
		// Status updates
		add_action( 'si_invoice_status_updated',  array( __CLASS__, 'maybe_create_status_update_record' ), 10, 3 );
	}

	/////////////////////
	// Record Keeping //
	/////////////////////

	/**
	 * Maybe create a status update record
	 * @param  SI_Estimate $estimate
	 * @param  string      $status
	 * @param  string      $original_status
	 * @return null
	 */
	public static function maybe_create_status_update_record( SI_Invoice $invoice, $status = '', $original_status = '' ) {
		do_action( 'si_new_record',
			sprintf( __( 'Status changed: %s to <b>%s</b>.', 'sprout-invoices' ), $invoice->get_status_label( $original_status ), $invoice->get_status_label( $status ) ),
			self::HISTORY_STATUS_UPDATE,
			$invoice->get_id(),
			sprintf( __( 'Status update for %s.', 'sprout-invoices' ), $invoice->get_id() ),
			0,
		false );
	}
}
