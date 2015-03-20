<?php

/**
 * @package Sprout_Invoices
 * @version 5.4.1
 */

/*
 * Plugin Name: Sprout Invoices
 * Plugin URI: https://sproutapps.co/sprout-invoices/
 * Description: App allows for any WordPress site to accept estimates, create invoices and receive invoice payments. Learn more at <a href="https://sproutapps.co">Sprout Apps</a>.
 * Author: Sprout Apps
 * Version: 5.4.1
 * Author URI: https://sproutapps.co
 * Text Domain: sprout-apps
 * Domain Path: languages
*/


/**
 * SI directory
 */
define( 'SI_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
/**
 * Plugin File
 */
define( 'SI_PLUGIN_FILE', __FILE__ );

/**
 * SI URL
 */
define( 'SI_URL', plugins_url( '', __FILE__ ) );
/**
 * URL to resources directory
 */
define( 'SI_RESOURCES', plugins_url( 'resources/', __FILE__ ) );


/**
 * Load plugin
 */
require_once SI_PATH . '/load.php';

/**
 * do_action when plugin is activated.
 * @package Sprout_Invoices
 * @ignore
 */
register_activation_hook( __FILE__, 'si_plugin_activated' );
function si_plugin_activated() {
	do_action( 'si_plugin_activation_hook' );
}
/**
 * do_action when plugin is deactivated.
 * @package Sprout_Invoices
 * @ignore
 */
register_deactivation_hook( __FILE__, 'si_plugin_deactivated' );
function si_plugin_deactivated() {
	do_action( 'si_plugin_deactivation_hook' );
}

function si_deactivate_plugin() {
	if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {
		require_once ABSPATH.'/wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
	}
}