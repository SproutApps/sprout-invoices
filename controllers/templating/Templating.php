<?php


/**
 * Templating API
 * shortcodes, page creation, etc.
 *
 * @package Sprout_Invoice
 * @subpackage TEmplating
*/
class SI_Templating_API extends SI_Controller {

	private static $pages = array();
	private static $shortcodes = array();
	
	public static function get_template_pages() {
		return self::$pages;
	}

	public static function get_shortcodes() {
		return self::$shortcodes;
	}

	public static function init() {
		// Register Shortcodes
		add_action( 'sprout_shortcode', array( __CLASS__, 'register_shortcode' ), 0, 3 );
		// Add shortcodes
		add_action( 'init', array( __CLASS__, 'add_shortcodes' ) );
	}

	/**
	 * Wrapper for the add_shorcode function WP provides
	 * @param string the shortcode
	 * @param array $callback
	 * @param array $args FUTURE
	 */
	public static function register_shortcode( $tag = '', $callback = array(), $args = array() ) {
		// FUTURE $args
		self::$shortcodes[$tag] = $callback;
	}

	/**
	 * Loop through registered shortcodes and use the WP function.
	 * @return  
	 */
	public static function add_shortcodes(){
		foreach ( self::$shortcodes as $tag => $callback ) {
			add_shortcode( $tag, $callback );
		}
	}

}