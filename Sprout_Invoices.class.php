<?php


/**
 * A fundamental class from which all other classes in the plugin should be derived.
 * The purpose of this class is to hold data useful to all classes.
 * @package SI
 */

if ( !defined( 'SI_FREE_TEST' ) )
	define( 'SI_FREE_TEST', FALSE );

if ( !defined( 'SI_DEV' ) )
	define( 'SI_DEV', FALSE );

abstract class Sprout_Invoices {

	/**
	 * Application text-domain
	 */
	const TEXT_DOMAIN = 'sprout-apps';
	/**
	 * Application text-domain
	 */
	const PLUGIN_URL = 'https://sproutapps.co';
	/**
	 * Current version. Should match sprout-invoices.php plugin version.
	 */
	const SI_VERSION = '1.0.7';
	/**
	 * DB Version
	 */
	const DB_VERSION = 1;
	/**
	 * Application Name
	 */
	const PLUGIN_NAME = 'Sprout Invoices';
	const PLUGIN_FILE = SI_PLUGIN_FILE;
	/**
	 * SI_DEV constant within the wp-config to turn on SI debugging
	 * <code>
	 * define( 'SI_DEV', TRUE/FALSE )
	 * </code>
	 */
	const DEBUG = SI_DEV;

	/**
	 * A wrapper around WP's __() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return string|void
	 */
	public static function __( $string ) {
		return __( apply_filters( 'si_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * A wrapper around WP's _e() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return void
	 */
	public static function _e( $string ) {
		return _e( apply_filters( 'si_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * Wrapper around esc_attr__
	 * @param  string $string 
	 * @return          
	 */
	public static function esc__( $string ) {
		return esc_attr__( $string, self::TEXT_DOMAIN );
	}

	/**
	 * Wrapper around esc_attr__
	 * @param  string $string 
	 * @return          
	 */
	public static function esc_e( $string ) {
		return esc_attr_e( $string, self::TEXT_DOMAIN );
	}
}