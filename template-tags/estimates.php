<?php


/**
	 * Sprout Apps Estimate Template Functions
 *
	 * @package Sprout_Invoice
	 * @subpackage Utility
	 * @category Template Tags
	 */


if ( ! function_exists( 'si_get_estimate_line_items' ) ) :
	/**
	 * Get the estimate line items
	 * @param  integer $id
	 * @return array
	 */
	function si_get_estimate_line_items( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_line_items', $estimate->get_line_items(), $estimate );
	}
endif;

if ( ! function_exists( 'si_get_estimate_history' ) ) :
	/**
	 * Get the estimate history
	 * @param  integer $id
	 * @return array
	 */
	function si_get_estimate_history( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_history', $estimate->get_history(), $estimate );
	}
endif;


if ( ! function_exists( 'si_get_estimate_status' ) ) :
	/**
	 * Get the estimate status
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_status( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		switch ( $estimate->get_status() ) {
			case 'draft' :
				$status = SI_Estimate::STATUS_REQUEST;
				break;
			case SI_Estimate::STATUS_PENDING:
				$status = 'pending';
				break;
			case SI_Estimate::STATUS_APPROVED:
				$status = 'approved';
				break;

			default:
				$status = $estimate->get_status();
				break;
		}
		return apply_filters( 'si_get_estimate_status', $status, $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_status' ) ) :
	/**
	 * Echo the estimate status
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_status( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_status', si_get_estimate_status( $id ), $id );
	}
endif;

if ( ! function_exists( 'si_is_estimate_approved' ) ) :
	/**
	 * Is the estimate approved
	 * @param  integer $id
	 * @return bool
	 */
	function si_is_estimate_approved( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$bool = ( si_get_estimate_status( $id ) == 'approved' );
		return apply_filters( 'si_is_estimate_approved', $bool, $id );
	}
endif;

if ( ! function_exists( 'si_is_estimate_declined' ) ) :
	/**
	 * Is the estimate approved
	 * @param  integer $id
	 * @return bool
	 */
	function si_is_estimate_declined( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$bool = ( si_get_estimate_status( $id ) == 'declined' );
		return apply_filters( 'si_is_estimate_declined', $bool, $id );
	}
endif;


if ( ! function_exists( 'si_get_estimatestatus_label' ) ) :
	/**
	 * Get the estimate status_label
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_status_label( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_status_label', $estimate->get_status_label(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_status_label' ) ) :
	/**
	 * Echo the estimate status_label
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_status_label( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_status_label', si_get_estimate_status_label( $id ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimatesubmission_fields' ) ) :
	/**
	 * Get the estimate submission_fields
	 * @param  integer $id
	 * @return array
	 */
	function si_get_estimate_submission_fields( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_submission_fields', $estimate->get_submission_fields(), $estimate );
	}
endif;

if ( ! function_exists( 'si_is_estimate_submission' ) ) :
	/**
	 * Is the estimate a submission
	 * @param  integer $id
	 * @return array
	 */
	function si_is_estimate_submission( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		$submission_fields = $estimate->get_submission_fields();
		return apply_filters( 'si_is_estimate_submission', ! empty( $submission_fields ), $estimate );
	}
endif;


if ( ! function_exists( 'si_get_estimate_issue_date' ) ) :
	/**
	 * Get the estimate issue_date
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_issue_date( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_issue_date', $estimate->get_issue_date(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_issue_date' ) ) :
	/**
	 * Echo the estimate issue_date
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_issue_date( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_issue_date', date_i18n( get_option( 'date_format' ), si_get_estimate_issue_date( $id ) ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_expiration_date' ) ) :
	/**
	 * Get the estimate expiration_date
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_expiration_date( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_expiration_date', $estimate->get_expiration_date(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_expiration_date' ) ) :
	/**
	 * Echo the estimate expiration_date
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_expiration_date( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_expiration_date', date_i18n( get_option( 'date_format' ), si_get_estimate_expiration_date( $id ) ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_id' ) ) :
	/**
	 * Get the estimate id
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_id( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		if ( $estimate->get_estimate_id() ) {
			$id = $estimate->get_estimate_id();
		}
		return apply_filters( 'si_get_estimate_id', $id, $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_id' ) ) :
	/**
	 * Echo the estimate id
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_id( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_id', si_get_estimate_id( $id ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_po_number' ) ) :
	/**
	 * Get the estimate po_number
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_po_number( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_po_number', $estimate->get_po_number(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_po_number' ) ) :
	/**
	 * Echo the estimate po_number
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_po_number( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_po_number', si_get_estimate_po_number( $id ), $id );
	}
endif;

if ( ! function_exists( 'si_get_estimate_client_id' ) ) :
	/**
	 * Get the estimate client_id
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_client_id( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_client_id', $estimate->get_client_id(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_client_id' ) ) :
	/**
	 * Echo the estimate client_id
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_client_id( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_client_id', si_get_estimate_client_id( $id ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_client' ) ) :
	/**
	 * Get the estimate client
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_client( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return $estimate->get_client();
	}
endif;

if ( ! function_exists( 'si_get_estimate_invoice_id' ) ) :
	/**
	 * Get the estimate invoice_id
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_invoice_id( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_invoice_id', $estimate->get_invoice_id(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_invoice_id' ) ) :
	/**
	 * Echo the estimate invoice_id
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_invoice_id( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_invoice_id', si_get_estimate_invoice_id( $id ), $id );
	}
endif;

if ( ! function_exists( 'si_get_estimate_invoice' ) ) :
	/**
	 * Get the estimate invoice
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_invoice( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$invoice = SI_Invoice::get_instance( $id );
		return $invoice->get_invoice();
	}
endif;

if ( ! function_exists( 'si_get_estimate_discount' ) ) :
	/**
	 * Get the estimate discount
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_discount( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_discount', $estimate->get_discount(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_discount' ) ) :
	/**
	 * Echo the estimate discount
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_discount( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_discount', si_get_estimate_discount( $id ), $id );
	}
endif;

if ( ! function_exists( 'si_get_estimate_discount_total' ) ) :
	/**
	 * Get the invoice tax
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_discount_total( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_discount_total', $estimate->get_discount_total(), $estimate );
	}
endif;


if ( ! function_exists( 'si_get_estimate_tax' ) ) :
	/**
	 * Get the estimate tax
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_tax( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_tax', $estimate->get_tax(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_tax' ) ) :
	/**
	 * Echo the estimate tax
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_tax( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_tax', si_get_estimate_tax( $id ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_tax2' ) ) :
	/**
	 * Get the estimate tax
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_tax2( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_tax2', $estimate->get_tax2(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_tax2' ) ) :
	/**
	 * Echo the estimate tax
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_tax2( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_tax2', si_get_estimate_tax2( $id ), $id );
	}
endif;

if ( ! function_exists( 'si_get_estimate_taxes_total' ) ) :
	/**
	 * Get the invoice tax
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_taxes_total( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_taxes_total', $estimate->get_tax_total() + $estimate->get_tax2_total(), $estimate );
	}
endif;

if ( ! function_exists( 'si_get_estimate_total' ) ) :
	/**
	 * Get the estimate total
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_total( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_total', $estimate->get_total(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_total' ) ) :
	/**
	 * Echo the estimate total
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_total( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_total', sa_get_formatted_money( si_get_estimate_total( $id ), $id ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_subtotal' ) ) :
	/**
	 * Get the estimate subtotal
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_subtotal( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_subtotal', $estimate->get_subtotal(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_subtotal' ) ) :
	/**
	 * Echo the estimate subtotal
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_subtotal( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_subtotal', sa_get_formatted_money( si_get_estimate_subtotal( $id ), $id ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_terms' ) ) :
	/**
	 * Get the estimate terms
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_terms( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_terms', apply_filters( 'the_content', $estimate->get_terms() ), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_terms' ) ) :
	/**
	 * Echo the estimate terms
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_terms( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_terms', si_get_estimate_terms( $id ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_sender_note' ) ) :
	/**
	 * Get the estimate sender_note
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_sender_note( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_sender_note', $estimate->get_sender_note(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_sender_note' ) ) :
	/**
	 * Echo the estimate sender_note
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_sender_note( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_sender_note', si_get_estimate_sender_note( $id ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_notes' ) ) :
	/**
	 * Get the estimate notes
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_notes( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_notes', apply_filters( 'the_content', $estimate->get_notes() ), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_notes' ) ) :
	/**
	 * Echo the estimate notes
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_notes( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_notes', si_get_estimate_notes( $id ), $id );
	}
endif;


if ( ! function_exists( 'si_get_estimate_currency' ) ) :
	/**
	 * Get the estimate currency
	 * @param  integer $id
	 * @return string
	 */
	function si_get_estimate_currency( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );
		return apply_filters( 'si_get_estimate_currency', $estimate->get_currency(), $estimate );
	}
endif;

if ( ! function_exists( 'si_estimate_currency' ) ) :
	/**
	 * Echo the estimate currency
	 * @param  integer $id
	 * @return string
	 */
	function si_estimate_currency( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_estimate_currency', si_get_estimate_currency( $id ), $id );
	}
endif;
