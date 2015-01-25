<?php 

/**
 * l18n
 *
 * @package Sprout_Invoice
 * @subpackage l18n
 */
class SI_i18n extends SI_Controller {

	public static function init() {
		self::load_textdomain();
	}

	/**
	 * Loads the plugin language files
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		// Set filter for plugin's languages directory
		$sa_lang_dir = dirname( plugin_basename( self::PLUGIN_FILE ) ) . '/languages/';
		$sa_lang_dir = apply_filters( 'sa_languages_directory', $sa_lang_dir );

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), self::TEXT_DOMAIN );
		$mofile        = sprintf( '%1$s-%2$s.mo', self::TEXT_DOMAIN, $locale );

		// Setup paths to current locale file
		$mofile_local  = $sa_lang_dir . $mofile;
		$mofile_plugins_global = WP_LANG_DIR . '/plugins/' . self::TEXT_DOMAIN . '/' . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . self::TEXT_DOMAIN . '/' . $mofile;

		if ( file_exists( $mofile_plugins_global ) ) {
			// Look in global /wp-content/languages/plugins/sprout-apps folder
			load_textdomain( self::TEXT_DOMAIN, $mofile_plugins_global );
		} elseif ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/sprout-apps folder
			load_textdomain( self::TEXT_DOMAIN, $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/sprout-invoices{-pro}/languages/ folder
			load_textdomain( self::TEXT_DOMAIN, $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( self::TEXT_DOMAIN, false, $sa_lang_dir );
		}
	}
}