<?php


/**
 * Pro Version class
 *
 * @package Sprout_Invoice
 * @subpackage Pro
 */
class SI_Pro extends SI_Controller {
	const SI_FREE_PLUGIN_SLUG = 'sprout-invoices';
	const SI_FREE_PLUGIN_PATH = 'sprout-invoices/sprout-invoices.php';

	public static function init() {

		if ( ! is_multisite() && is_admin() ) {
			add_action( 'shutdown',  array( __CLASS__, 'maybe_install_free_version' ), 10, 0 );
			add_action( 'shutdown',  array( __CLASS__, 'maybe_activate_free_version' ), 10, 0 );
		}

	}

	public static function is_free_version_installed() {
		if ( ! function_exists( 'validate_plugin' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$installed = true; // default to true for sanity

		$valid = validate_plugin( self::SI_FREE_PLUGIN_PATH );
		if ( is_wp_error( $valid ) ) {
			if ( 'plugin_not_found' === $valid->get_error_code() ) {
				$installed = false;
			}
		}
		return $installed;
	}

	public static function is_free_version_activated() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$activated = false;

		// check for plugin using plugin name
		if ( is_plugin_active( self::SI_FREE_PLUGIN_PATH ) ) {
			$activated = true;
		}

		return $activated;
	}

	public static function maybe_install_free_version() {
		if ( ! self::is_free_version_installed() ) {
			self::install_free_version( true );
		}
	}

	public static function install_free_version( $activate = false ) {

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$api = plugins_api( 'plugin_information', array(
			'slug' => self::SI_FREE_PLUGIN_SLUG,
			'fields' => array(
				'short_description' => false,
				'sections' => false,
				'requires' => false,
				'rating' => false,
				'ratings' => false,
				'downloaded' => false,
				'last_updated' => false,
				'added' => false,
				'tags' => false,
				'compatibility' => false,
				'homepage' => false,
				'donate_link' => false,
			),
		) );

		ob_start();
		$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );
		$upgrader->install( $api->download_link );
		$ob = ob_get_clean();

		if ( $activate ) {
			self::maybe_activate_free_version();
		}

	}

	public static function maybe_activate_free_version() {
		if ( ! self::is_free_version_activated() ) {
			activate_plugin( self::SI_FREE_PLUGIN_PATH );
		}
	}
}
