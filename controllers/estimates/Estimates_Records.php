<?php


/**
 * Estimates Controller
 *
 *
 * @package Sprout_Estimate
 * @subpackage Estimates
 */
class SI_Estimates_Records extends SI_Estimates {

	public static function init() {

		// Status updates
		add_action( 'si_estimate_status_updated',  array( __CLASS__, 'maybe_create_status_update_record' ), 10, 3 );

		// Record when invoice is created
		add_action( 'si_cloned_post',  array( __CLASS__, 'create_record_of_cloned_invoice' ), 10, 3 );

		// Mark estimate viewed
		add_action( 'estimate_viewed',  array( __CLASS__, 'maybe_log_estimate_view' ) );
	}

	/**
	 * Maybe create a status update record
	 * @param  SI_Estimate $estimate
	 * @param  string      $status
	 * @param  string      $original_status
	 * @return null
	 */
	public static function maybe_create_status_update_record( SI_Estimate $estimate, $status = '', $original_status = '' ) {
		do_action( 'si_new_record',
			sprintf( __( 'Status changed: %s to <b>%s</b>.', 'sprout-invoices' ), $estimate->get_status_label( $original_status ), $estimate->get_status_label( $status ) ),
			self::HISTORY_STATUS_UPDATE,
			$estimate->get_id(),
			sprintf( __( 'Status update for %s.', 'sprout-invoices' ), $estimate->get_id() ),
			0,
		false );
	}

	/**
	 * Create a record of the new invoice created.
	 * @param  integer $new_post_id
	 * @param  integer $cloned_post_id
	 * @param  string  $new_post_type
	 * @return
	 */
	public static function create_record_of_cloned_invoice( $new_post_id = 0, $cloned_post_id = 0, $new_post_type = '' ) {
		if ( get_post_type( $cloned_post_id ) === SI_Estimate::POST_TYPE ) {
			if ( SI_Invoice::POST_TYPE === $new_post_type ) {
				do_action( 'si_new_record',
					sprintf( __( 'Invoice Created: <a href="%s">%s</a>.', 'sprout-invoices' ), get_edit_post_link( $new_post_id ), get_the_title( $new_post_id ) ),
					self::HISTORY_INVOICE_CREATED,
					$cloned_post_id,
					sprintf( __( 'Invoice Created: %s.', 'sprout-invoices' ), get_the_title( $new_post_id ) ),
					0,
				false );
			}
		}
	}

	public static function maybe_log_estimate_view() {
		global $post;

		if ( ! is_single() ) {
			return; }

		// Make sure this is an estimate we're viewing
		if ( $post->post_type !== SI_Estimate::POST_TYPE ) {
			return; }

		// Don't log the authors views
		if ( $post->post_author === get_current_user_id() ) {
			return; }

		if ( is_user_logged_in() ) {
			$user = get_userdata( get_current_user_id() );
			$name = $user->first_name . ' ' . $user->last_name;
			$whom = $name . ' (' . $user->user_login. ')';
		} else {
			$whom = self::get_user_ip();
		}
		if ( in_array( $whom, array( '127.0.0.1', '::1' ) ) ) {
			return;
		}

		$estimate = SI_Estimate::get_instance( $post->ID );
		$title = sprintf( __( 'Estimate viewed by %s for the first time.', 'sprout-invoices' ), esc_html( $whom ) );

		$found = false;
		$view_logs = SI_Record::get_records_by_type_and_association( $estimate->get_id(), self::VIEWED_STATUS_UPDATE );
		foreach ( $view_logs as $record_id ) {
			if ( ! $found && $title === get_the_title( $record_id ) ) {
				$found = true;
			}
		}
		// Record exists
		if ( $found ) {
			return;
		}

		do_action( 'si_new_record',
			$_SERVER,
			self::VIEWED_STATUS_UPDATE,
			$estimate->get_id(),
		$title );
	}
}
