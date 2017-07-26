<?php


function si_head( $v2_theme = false ) {
	do_action( 'si_head', $v2_theme );
}


function si_footer( $v2_theme = false ) {
	do_action( 'si_footer', $v2_theme );
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

if ( ! function_exists( 'si_doc_notification_sent' ) ) :

	function si_doc_notification_sent( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}

		$sent = false;

		$doc = si_get_doc_object( $id );
		$history = $doc->get_history();
		foreach ( $history as $item_id ) {
			$record = SI_Record::get_instance( $item_id );
			if ( SI_Notifications::RECORD === $record->get_type() ) {
				// TODO learn regex
				$email = str_replace( __( 'Notification sent to ', 'sprout-invoices' ), '', $record->get_title() );
				$email = substr( $email, 0, -1 );
				if ( $email !== SI_Notifications::get_user_email() ) {
					$sent = true;
				}
			}
		}

		return apply_filters( 'si_doc_notification_sent', $sent, $id );
	}
endif;

if ( ! function_exists( 'si_was_doc_viewed' ) ) :

	function si_was_doc_viewed( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}

		$viewed = false;

		$doc = si_get_doc_object( $id );
		$history = $doc->get_history();
		foreach ( $history as $item_id ) {
			$record = SI_Record::get_instance( $item_id );
			if ( 'si_viewed_status_update' === $record->get_type() ) {
				if ( 'Estimate viewed by ::1.' !== $record->get_title() ) {
					$viewed = true;
				}
			}
		}

		// If there was a payment than it should have been viewed.
		if ( ! $viewed && in_array( $doc->get_status(), array( SI_Invoice::STATUS_PAID, SI_Invoice::STATUS_PARTIAL, SI_Estimate::STATUS_APPROVED ) ) ) {
			$viewed = true;
		}

		return apply_filters( 'si_was_doc_viewed', $viewed, $id );
	}
endif;

