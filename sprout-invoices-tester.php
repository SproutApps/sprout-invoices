<?php

/*
 * Plugin Name: Sprout Invoices TEST
 * Plugin URI: https://sproutapps.co/sprout-invoices/
 * Description: A support plugin that provides some necessary information
 * Author: Sprout Apps
 * Version: 0.1
 * Author URI: https://sproutapps.co
 * Text Domain: sprout-invoices
 * Domain Path: languages
*/


class SI_TEST {

	public static function init() {
		add_action( 'show_user_profile', array( __CLASS__, 'user_profile_fields' ) );

		add_action( 'edit_user_profile', array( __CLASS__, 'user_profile_fields' ) );
		add_action( 'si_document_footer', array( __CLASS__, 'show_whos_paying' ) );
	}

	public static function user_profile_fields( $user ) {
		$user_id = $user->ID;
		$customer_id = get_user_meta( $user_id, 'si_stripe_customer_id_v1', true );
		echo 'Customer Id: ';
		echo $customer_id;
	}

	public static function show_whos_paying() {
		printf( '<!-- Whos Paying ID: %s -->', si_whos_user_id_is_paying( si_get_doc_object() ) );
		$invoice = si_get_doc_object();
		$client = $invoice->get_client();
		$client_users = $client->get_associated_users();
		prp( $client_users );
		$client_user_id = array_shift( $client_users );
		prp( $client_user_id );
	}
}
SI_TEST::init();
