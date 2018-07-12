<?php


/**
 * A fundamental class from which all other classes in the plugin should be derived.
 * The purpose of this class is to hold data useful to all classes.
 * @package SI
 */

if ( ! defined( 'SI_FREE_TEST' ) ) {
	define( 'SI_FREE_TEST', false ); }

if ( ! defined( 'SI_DEV' ) ) {
	define( 'SI_DEV', false );
}

abstract class Sprout_Invoices {

	/**
	 * Application app-domain
	 */
	const APP_DOMAIN = 'sprout-apps';

	/**
	 * Application text-domain
	 */
	const TEXT_DOMAIN = 'sprout-invoices';
	/**
	 * Application text-domain
	 */
	const PLUGIN_URL = 'https://sproutapps.co/sprout-invoices/';
	/**
	 * Current version. Should match sprout-invoices.php plugin version.
	 */
	const SI_VERSION = '18.0.6';
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
	 * define( 'SI_DEV', true/false )
	 * </code>
	 */
	const DEBUG = SI_DEV;

}
