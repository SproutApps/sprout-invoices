<?php


/**
 * Estimates Controller
 *
 *
 * @package Sprout_Estimate
 * @subpackage Estimates
 */
class SI_Estimates_Scheduled extends SI_Estimates {

	public static function init() {
		add_action( 'future_to_publish', array( __CLASS__, 'scheduled_post_transition' ) );
	}

	public static function scheduled_post_transition( $post ) {
		if ( SI_Estimate::POST_TYPE !== $post->post_type ) {
			return;
		}

		$estimate = SI_Estimate::get_instance( $post->ID );

		$client = $estimate->get_client();
		if ( ! is_a( $client, 'SI_Client' ) ) {
			return;
		}

		$recipients = $client->get_associated_users();
		if ( empty( $recipients ) ) {
			return;
		}

		do_action( 'send_estimate', $estimate, $recipients );
	}
}
