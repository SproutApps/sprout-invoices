<?php


/**
 * Invoices Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Invoices
 */
class SI_Invoices_Deposit extends SI_Invoices {

	public static function init() {
		add_action( 'si_line_item_totals_section', array( __CLASS__, 'add_deposit_option' ) );
	}

	public static function add_deposit_option( $doc_id ) {
		$context = si_get_doc_context( $doc_id );

		if ( 'invoice' !== $context ) {
			return;
		}

		$invoice = SI_Invoice::get_instance( $doc_id );
		$total = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_total() : '0.00' ;
		$total_payments = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_payments_total() : '0.00' ;
		$deposit = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_deposit() : '0.00' ;
		$status = ( is_a( $invoice, 'SI_Invoice' ) && $invoice->get_status() !== 'auto-draft' ) ? $invoice->get_status() : SI_Invoice::STATUS_TEMP ;

		if ( apply_filters( 'show_upgrade_messaging', true, 'deposit-line-items' ) ) {
			?>
			<div id="deposit">
				<b title="<?php _e( 'Upgrade Sprout Invoices to enable deposits.', 'sprout-invoices' ) ?>" class="helptip"><?php _e( 'Deposit Due', 'sprout-invoices' ) ?></b>
				<input type="number" name="deposit" min="0" max="0" step="any" disabled="disabled">
			</div>
			<?php
		} elseif ( floatval( $total - $total_payments ) > 0.00 || 'auto-draft' === $status || 'temp' === $status  ) {
			?>
			<div id="deposit">
				<b title="<?php _e( 'Set the amount due for the next payment&mdash;amount due will be used 0', 'sprout-invoices' ) ?>" class="helptip"><?php _e( 'Deposit Due', 'sprout-invoices' ) ?></b>
				<input type="number" name="deposit" value="<?php echo (float) $deposit ?>" min="0" max="<?php echo floatval( $total - $total_payments ) ?>"  step="any">
			</div>
			<?php
		}
	}

}