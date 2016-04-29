<?php

if ( ! function_exists( 'si_get_client_address' ) ) :
	/**
 * Get the client address
 * @param  integer $id
 * @return string
 */
	function si_get_client_address( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$client = SI_Client::get_instance( $id );
		return apply_filters( 'si_get_client_address', $client->get_address(), $client );
	}
endif;

if ( ! function_exists( 'si_client_address' ) ) :
	/**
 * Echo the client address
 * @param  integer $id
 * @return string
 */
	function si_client_address( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_client_address', si_address( si_get_client_address( $id ) ), $id );
	}
endif;

if ( ! function_exists( 'si_who_is_paying' ) ) :
	/**
	 * Return the user object for the person responsible paying at the time of purchase.
	 * @param  SI_Invoice $invoice
	 * @return object/false
	 */
	function si_who_is_paying( SI_Invoice $invoice ) {
		$user = false;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$user = get_userdata( $user_id );
		} elseif ( ! is_wp_error( $invoice ) ) {
			$client = $invoice->get_client();
			if ( ! is_wp_error( $client ) ) {
				$client_users = $client->get_associated_users();
				$client_user_id = array_shift( $client_users );
				$user = get_userdata( $client_user_id );
			}
		}
		// Default to the admin if no other user is associated
		if ( ! $user ) {
			$user = get_user_by( 'email', get_option( 'admin_email' ) );
		}
		return apply_filters( 'si_who_is_paying', $user, $invoice );
	}
endif;

if ( ! function_exists( 'si_default_client_user' ) ) :
	/**
	 * Return the user object for the person responsible paying at the time of purchase.
	 * @param  SI_Invoice $invoice
	 * @return object/false
	 */
	function si_default_client_user( $client_id = 0 ) {
		if ( ! $client_id ) {
			$client_id = get_the_id();
		}
		$user = false;
		$client = SI_Client::get_instance( $client_id );
		if ( ! is_wp_error( $client ) ) {
			$client_users = $client->get_associated_users();
			$client_user_id = array_shift( $client_users );
			if ( $client_user_id ) {
				$user = get_userdata( $client_user_id );
			}
		}
		return apply_filters( 'si_default_client_user', $user, $invoice );
	}
endif;

if ( ! function_exists( 'si_whos_user_id_is_paying' ) ) :
	/**
	 * Return the user object for the person responsible paying at the time of purchase.
	 * @param  SI_Invoice $invoice
	 * @return object/false
	 */
	function si_whos_user_id_is_paying( SI_Invoice $invoice ) {
		$user = si_who_is_paying( $invoice );
		if ( ! is_a( $user, 'WP_User' ) ) {
			return $user;
		}
		return apply_filters( 'si_whos_user_id_is_paying', $user->ID, $invoice );
	}
endif;

