<?php 

if ( !function_exists('si_get_client_address') ) :
/**
 * Get the client address
 * @param  integer $id 
 * @return string      
 */
function si_get_client_address( $id = 0 ) {
	if ( !$id ) {
		global $post;
		$id = $post->ID;
	}
	$client = SI_Client::get_instance( $id );
	return apply_filters( 'si_get_client_address', $client->get_address(), $client );
}
endif;

if ( !function_exists('si_client_address') ) :
/**
 * Echo the client address
 * @param  integer $id 
 * @return string      
 */
function si_client_address( $id = 0 ) {
	if ( !$id ) {
		global $post;
		$id = $post->ID;
	}
	echo apply_filters( 'si_client_address', si_address( si_get_client_address( $id ) ), $id );
}
endif;