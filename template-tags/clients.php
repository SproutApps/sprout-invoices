<?php

function si_get_client_id( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
	}
	switch ( get_post_type( $id ) ) {
		case SI_Estimate::POST_TYPE:
			$doc = SI_Estimate::get_instance( $id );
			$client_id = $doc->get_client_id();
			break;
		case SI_Invoice::POST_TYPE:
			$doc = SI_Invoice::get_instance( $id );
			$client_id = $doc->get_client_id();
			break;

		default:
			$client_id = false;
			break;
	}
	return apply_filters( 'si_get_client_id', $client_id );
}

if ( ! function_exists( 'si_get_client_address' ) ) :
	/**
 * Get the client address
 * @param  integer $id
 * @return string
 */
	function si_get_client_address( $id = 0 ) {
		if ( ! $id ) {
			$id = si_get_client_id();
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
			$id = si_get_client_id();
		}
		echo apply_filters( 'si_client_address', si_address( si_get_client_address( $id ) ), $id );
	}
endif;

if ( ! function_exists( 'si_get_client_phone' ) ) :
	/**
 * Get the client phone
 * @param  integer $id
 * @return string
 */
	function si_get_client_phone( $id = 0 ) {
		if ( ! $id ) {
			$id = si_get_client_id();
		}
		$client = SI_Client::get_instance( $id );
		return apply_filters( 'si_get_client_phone', $client->get_phone(), $client );
	}
endif;

if ( ! function_exists( 'si_client_phone' ) ) :
	/**
 * Echo the client phone
 * @param  integer $id
 * @return string
 */
	function si_client_phone( $id = 0 ) {
		if ( ! $id ) {
			$id = si_get_client_id();
		}
		echo apply_filters( 'si_client_phone', si_get_client_phone( $id ), $id );
	}
endif;

if ( ! function_exists( 'si_get_client_website' ) ) :
	/**
 * Get the client website
 * @param  integer $id
 * @return string
 */
	function si_get_client_website( $id = 0 ) {
		if ( ! $id ) {
			$id = si_get_client_id();
		}
		$client = SI_Client::get_instance( $id );
		return apply_filters( 'si_get_client_website', $client->get_website(), $client );
	}
endif;

if ( ! function_exists( 'si_client_website' ) ) :
	/**
 * Echo the client website
 * @param  integer $id
 * @return string
 */
	function si_client_website( $id = 0 ) {
		if ( ! $id ) {
			$id = si_get_client_id();
		}
		echo apply_filters( 'si_client_website', si_get_client_website( $id ), $id );
	}
endif;

if ( ! function_exists( 'si_get_client_fax' ) ) :
	/**
 * Get the client fax
 * @param  integer $id
 * @return string
 */
	function si_get_client_fax( $id = 0 ) {
		if ( ! $id ) {
			$id = si_get_client_id();
		}
		$client = SI_Client::get_instance( $id );
		return apply_filters( 'si_get_client_fax', $client->get_fax(), $client );
	}
endif;

if ( ! function_exists( 'si_client_fax' ) ) :
	/**
 * Echo the client fax
 * @param  integer $id
 * @return string
 */
	function si_client_fax( $id = 0 ) {
		if ( ! $id ) {
			$id = si_get_client_id();
		}
		echo apply_filters( 'si_client_fax', si_get_client_fax( $id ), $id );
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
				foreach ( $client_users as $client_user_id ) {
					$temp_user = get_userdata( $client_user_id );
					if ( is_a( $temp_user, 'WP_User' ) ) {
						$user = $temp_user;
						break;
					}
				}
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

