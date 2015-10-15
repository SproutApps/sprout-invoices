<?php


function si_head() {
	do_action( 'si_head' );
}


function si_footer() {
	do_action( 'si_footer' );
}

/**
 * Wrapper functions for many estimate and invoice template-tags
 * so they can be called ambiguously.
 *
 */

if ( ! function_exists( 'si_get_doc_object' ) ) :
	/**
 * Get the doc object
 * @param  integer $id
 * @return SI_Estimate/SI_Invoice
 */
	function si_get_doc_object( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		switch ( get_post_type( $id ) ) {
			case SI_Estimate::POST_TYPE:
				$doc = SI_Estimate::get_instance( $id );
				break;
			case SI_Invoice::POST_TYPE:
				$doc = SI_Invoice::get_instance( $id );
				break;

			default:
				$doc = '';
				break;
		}
		return apply_filters( 'si_get_doc_object', $doc );
	}
endif;

if ( ! function_exists( 'si_get_doc_context' ) ) :
	/**
 * Get the doc object
 * @param  integer $id
 * @return SI_Estimate/SI_Invoice
 */
	function si_get_doc_context( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		switch ( get_post_type( $id ) ) {
			case SI_Estimate::POST_TYPE:
				$context = 'estimate';
				break;
			case SI_Invoice::POST_TYPE:
				$context = 'invoice';
				break;

			default:
				$context = '';
				break;
		}
		return apply_filters( 'si_get_doc_context', $context );
	}
endif;


if ( ! function_exists( 'si_get_doc_line_items' ) ) :
	/**
 * Get the invoice line items
 * @param  integer $id
 * @return array
 */
	function si_get_doc_line_items( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		if ( si_get_doc_context( $id ) == 'estimate' ) {
			return si_get_estimate_line_items( $id );
		}
		return si_get_invoice_line_items( $id );
	}
endif;