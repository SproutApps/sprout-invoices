<?php

/**
 *
 *
 * @package SI_Killing_Machine
 * @subpackage SI_Controller
 */
class SI_Killing_Machine extends SI_Controller {
	const SEND_ACTION = 'si_destroy_everything';
	const NONCE = 'si_destroy_everything';

	public static function init() {
		// register settings
		add_action( 'init', array( __CLASS__, 'register_settings' ) );

		// AJAX action to handle test request
		add_action( 'wp_ajax_' . self::SEND_ACTION, array( __CLASS__, 'maybe_destroy_everything' ) );
	}


	////////////
	// admin //
	////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			'destroy_everything' => array(
				'weight' => PHP_INT_MAX,
				'tab' => 'settings',
				'settings' => array(
					'destroy_everything' => array(
						'label' => __( 'Destroyer of Worlds', 'sprout-invoices' ),
						'description' => __( 'This will delete all posts that are attributed to Sprout Invoices and their meta data.', 'sprout-invoices' ),
						'option' => array(
							'type' => 'bypass',
							'output' => self::destroy_option(),
							),
						),
					),
				),
			);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );
	}

	public static function destroy_option() {
		return self::load_view_to_string( 'admin/options/destroy-everything.php', array(
				'nonce' => wp_create_nonce( self::NONCE ),
				'action' => self::SEND_ACTION,
		), false );
	}

	public static function maybe_destroy_everything() {
		if ( ! current_user_can( 'manage_sprout_invoices_options' ) ) {
			return;
		}

		$nonce = $_REQUEST['nonce'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_send_json_error( array( 'message' => 'Not gonna happen!' ) );
		}
		self::destroy_everything();

	}

	public static function destroy_everything() {
		$cpts = array( SI_Invoice::POST_TYPE, SI_Estimate::POST_TYPE, SI_Client::POST_TYPE, SI_Project::POST_TYPE, SI_Record::POST_TYPE, 'sa_expense', 'sa_credit_type', 'sa_item', 'sa_time', 'sa_notification' ); // notifications will be recreated

		$delete_post_ids = array();
		$records_buffer = 50;
		foreach ( $cpts as $post_type ) {
			if ( $records_buffer > 0 ) {
				$pt_post_ids = self::get_post_ids( $post_type, $records_buffer );
				$delete_post_ids = array_merge( $pt_post_ids, $delete_post_ids );
				$records_buffer = $records_buffer - count( $pt_post_ids );
			}
		}

		$deleted = 0;
		foreach ( $delete_post_ids as $post_id ) {
			$del = wp_delete_post( $post_id );
			if ( false !== $del ) {
				++$deleted;
			}
		}

		$runagain = false;
		foreach ( $cpts as $post_type ) {
			$db_post_ids = self::get_post_ids( $post_type, $records_buffer );
			if ( ! empty( $db_post_ids ) ) {
				$runagain = true;
			}
		}

		$response = array(
				'message' => ( $runagain ) ? sprintf( __( '<p class="ajax_message">Deleted %s records.</p>', 'sprout-invoices' ), $deleted ) : sprintf( __( '<p class="ajax_message">Shredder won! %s records destroyed.</p>', 'sprout-invoices' ), $deleted ),
				'runagain' => $runagain,
				'deleted' => $deleted,
			);
		wp_send_json_success( $response );
	}

	/**
	 * Returns post ids from a post type.
	 *
	 * @since 1.0
	 * @param string  $post_type Post type
	 * @param integer $limit     Limit how many ids are returned.
	 * @return array Array with post ids
	 */
	private static function get_post_ids( $post_type, $limit = 0 ) {
		global $wpdb;
		$limit = $limit ? " LIMIT {$limit}" : '';
		$query = "SELECT p.ID FROM $wpdb->posts AS p WHERE p.post_type IN (%s){$limit}";
		return $wpdb->get_col( $wpdb->prepare( $query, $post_type ) );
	}
}
