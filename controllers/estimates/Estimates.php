<?php

/**
 * Estimates Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Estimates
 */
class SI_Estimates extends SI_Controller {
	const HISTORY_UPDATE = 'si_history_update';
	const HISTORY_STATUS_UPDATE = 'si_history_status_update';
	const HISTORY_INVOICE_CREATED = 'si_invoice_created';
	const VIEWED_STATUS_UPDATE = 'si_viewed_status_update';

	public static function init() {

		// Unique urls
		add_filter( 'wp_unique_post_slug', array( __CLASS__, 'post_slug' ), 10, 4 );

		// Adjust estimate id and status after clone
		add_action( 'si_cloned_post',  array( __CLASS__, 'adjust_cloned_estimate' ), 10, 3 );

		// reset cached totals
		add_action( 'save_post', array( __CLASS__, 'reset_totals_cache' ) );

		// Notifications
		add_filter( 'wp_ajax_sa_send_est_notification', array( __CLASS__, 'maybe_send_notification' ) );

	}

	///////////////
	// Re-writes //
	///////////////

	/**
	 * Filter the unique post slug.
	 *
	 * @param string $slug          The post slug.
	 * @param int    $post_ID       Post ID.
	 * @param string $post_status   The post status.
	 * @param string $post_type     Post type.
	 * @param int    $post_parent   Post parent ID
	 * @param string $original_slug The original post slug.
	 */
	public static function post_slug( $slug, $post_ID, $post_status, $post_type ) {
		if ( $post_type == SI_Estimate::POST_TYPE ) {
			return $post_ID;
		}
		return $slug;
	}


	/////////////////////
	// AJAX Callbacks //
	/////////////////////

	public static function maybe_send_notification() {
		// form maybe be serialized
		if ( isset( $_REQUEST['serialized_fields'] ) ) {
			foreach ( $_REQUEST['serialized_fields'] as $key => $data ) {
				if ( strpos( $data['name'], '[]' ) !== false ) {
					$_REQUEST[ str_replace( '[]', '', $data['name'] ) ][] = $data['value'];
				} else {
					$_REQUEST[ $data['name'] ] = $data['value'];
				}
			}
		}
		if ( ! isset( $_REQUEST['sa_send_metabox_notification_nonce'] ) ) {
			self::ajax_fail( 'Forget something (nonce)?' ); }

		$nonce = $_REQUEST['sa_send_metabox_notification_nonce'];
		if ( ! wp_verify_nonce( $nonce, SI_Controller::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! isset( $_REQUEST['sa_send_metabox_doc_id'] ) ) {
			self::ajax_fail( 'Forget something (id)?' ); }

		if ( get_post_type( $_REQUEST['sa_send_metabox_doc_id'] ) !== SI_Estimate::POST_TYPE ) {
			return;
		}

		$recipients = ( isset( $_REQUEST['sa_metabox_recipients'] ) ) ? $_REQUEST['sa_metabox_recipients'] : array();

		if ( isset( $_REQUEST['sa_metabox_custom_recipient'] ) && '' !== trim( $_REQUEST['sa_metabox_custom_recipient'] ) ) {
			$submitted_recipients = explode( ',', trim( $_REQUEST['sa_metabox_custom_recipient'] ) );
			foreach ( $submitted_recipients as $key => $email ) {
				$email = trim( $email );
				if ( is_email( $email ) ) {
					$recipients[] = $email;
				}
			}
		}

		if ( empty( $recipients ) ) {
			self::ajax_fail( 'A recipient is required.' );
		}

		$estimate = SI_Estimate::get_instance( $_REQUEST['sa_send_metabox_doc_id'] );
		$estimate->set_sender_note( $_REQUEST['sa_send_metabox_sender_note'] );

		$from_email = null;
		$from_name = null;
		if ( isset( $_REQUEST['sa_send_metabox_send_as'] ) ) {
			$name_and_email = SI_Notifications_Control::email_split( $_REQUEST['sa_send_metabox_send_as'] );
			if ( is_email( $name_and_email['email'] ) ) {
				$from_name = $name_and_email['name'];
				$from_email = $name_and_email['email'];
			}
		}

		do_action( 'send_estimate', $estimate, $recipients, $from_email, $from_name );

		// If status is temp than change to pending.
		if ( ! in_array( $estimate->get_status(), array( SI_Estimate::STATUS_APPROVED, SI_Estimate::STATUS_PENDING ) ) ) {
			$estimate->set_pending();
		}

		header( 'Content-type: application/json' );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode( array( 'response' => __( 'Notification Queued', 'sprout-invoices' ) ) );
		exit();
	}

	////////////
	// Misc. //
	////////////

	public static function reset_totals_cache( $estimate_id = 0 ) {
		$estimate = SI_Estimate::get_instance( $estimate_id );
		if ( ! is_a( $estimate, 'SI_Estimate' ) ) {
			return;
		}

		// reset the totals since payment totals are new.
		$estimate->reset_totals( true );
	}

	/**
	 * Adjust the estimate id
	 * @param  integer $new_post_id
	 * @param  integer $cloned_post_id
	 * @param  string  $new_post_type
	 * @return
	 */
	public static function adjust_cloned_estimate( $new_post_id = 0, $cloned_post_id = 0, $new_post_type = '' ) {
		if ( get_post_type( $new_post_id ) == SI_Estimate::POST_TYPE ) {
			$og_estimate = SI_Estimate::get_instance( $cloned_post_id );
			$og_id = $og_estimate->get_estimate_id();
			$estimate = SI_Estimate::get_instance( $new_post_id );

			// Adjust estimate id
			$new_id = apply_filters( 'si_adjust_cloned_estimate_id', $og_id . '-' . $new_post_id, $new_post_id, $cloned_post_id );
			$estimate->set_estimate_id( $new_id );

			// Adjust status
			$estimate->set_pending();
		}
	}

	/////////////
	// utility //
	/////////////

	public static function is_edit_screen() {
		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( $screen_post_type == SI_Estimate::POST_TYPE ) {
			return true;
		}
		return false;
	}
}
