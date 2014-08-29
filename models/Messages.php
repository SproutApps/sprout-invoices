<?php

/**
 * Messages Model
 * Used for CRM messages, Estimate comments and Invoice comments.
 * 
 * FUTURE
 *
 * 
 * 
 *
 * @package Sprout_Invoices
 * @subpackage Messages
 */
class SI_Messages extends SI_Post_Type {
	
	const POST_TYPE = 'sa_messages';
	private static $instances = array();

	private static $meta_keys = array(
		
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.


	public static function init() {
		// register Messages post type
		$post_type_args = array(
			'public' => FALSE,
			'has_archive' => FALSE,
			'show_ui' => FALSE,
			'show_in_menu' => 'sprout-invoice',
			'supports' => array( 'title', 'editor', 'revisions' )
		);
		self::register_post_type( self::POST_TYPE, 'Messages', 'Messages', $post_type_args );
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Sprout_Invoices_Messages
	 */
	public static function get_instance( $id = 0 ) {
		if ( !$id )
			return NULL;
		
		if ( !isset( self::$instances[$id] ) || !self::$instances[$id] instanceof self )
			self::$instances[$id] = new self( $id );

		if ( !isset( self::$instances[$id]->post->post_type ) )
			return NULL;
		
		if ( self::$instances[$id]->post->post_type != self::POST_TYPE )
			return NULL;
		
		return self::$instances[$id];
	}

	// A pretty basic post type. Not much else to do here.
}
