<?php

/**
 * l18n
 *
 * @package Sprout_Invoice
 * @subpackage l18n
 */
class SI_l10n extends SI_Controller {
	public static $language_loaded;

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
			// Look in global /wp-content/languages/plugins/sprout-invoices folder
			load_textdomain( self::TEXT_DOMAIN, $mofile_plugins_global );
		} elseif ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/sprout-invoices folder
			load_textdomain( self::TEXT_DOMAIN, $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/sprout-invoices/languages/ folder
			load_textdomain( self::TEXT_DOMAIN, $mofile_local );
		} else {
			// Load the default language files
			$loaded = load_plugin_textdomain( self::TEXT_DOMAIN, false, $sa_lang_dir );
			// if a language was not loaded than ask for help
			if ( ! $loaded ) {
				self::load_translation_help_messaging();
			}
		}
	}

	public static function load_translation_help_messaging() {
		add_action( 'admin_notices', array( __CLASS__, 'language_detector_admin_notices' ) );
		add_action( 'wp_ajax_si_language_nag_dismiss', array( __CLASS__, 'language_detector_dismiss_callback' ) );
		add_action( 'admin_footer', array( __CLASS__, 'language_detector_admin_footer' ) );
	}

	public static function language_detector_admin_notices() {

		// Check if the nag screen has been disabled for this language
		if ( self::has_language_detector_dismissed() ) {
			return;
		}

		// Get the current language locale
		$language = get_locale();

		require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
		$translations = wp_get_available_translations();

		if ( ! isset( $translations[ $language ] ) ) {
			return;
		}

		printf(
			'<div id="si_language_detect_nag_dismiss" class="notice notice-info is-dismissible"><p>%s</p></div>',
			sprintf(
				__( '<span class="dashicons dashicons-megaphone"></span> <b>%1$s</b> does not have a translation for <b>%2$s</b>, please help the community translate at <a href="%3$s" target="_blank">%3$s</a>. Supporting translators receive major discounts on <a href="%4$s" target="_blank">pro licenses</a> and <a href="%5$s" target="_blank">add-ons</a>.', 'sprout-invoices' ),
				esc_html( self::PLUGIN_NAME ),
				esc_html( $translations[ $language ]['native_name'] ),
				esc_url( 'https://translate.wordpress.org/projects/wp-plugins/sprout-invoices/' ),
				si_get_sa_link( 'https://sproutapps.co/sprout-invoices/purchase/', 'translation' ),
				si_get_sa_link( 'https://sproutapps.co/marketplace/', 'translation' )
			)
		);
	}


	public static function language_detector_admin_footer() {
		$language = get_locale();
		// We only add our JavaScript if the nag notice is being displayed any way
		if ( self::has_language_detector_dismissed() ) {
			return;
		} ?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$( "#si_language_detect_nag_dismiss" ).on( "click", ".notice-dismiss", function() {
					var data = {
						action : 'si_language_nag_dismiss',
						language : '<?php echo esc_js( $language ) ?>'
					};
					$.post(
						ajaxurl,
						data
					);
				});
			});
		</script>
		<?php
	}

	public static function language_detector_dismiss_callback() {
		$language = '';
		if ( isset( $_REQUEST['language'] ) && '' !== $_REQUEST['language'] ) {
			$language = $_REQUEST['language'];
		}
		self::dismiss_language_detector( $language );
		wp_die();
	}

	public static function has_language_detector_dismissed() {
		$language = get_locale();
		$option = get_option( self::TEXT_DOMAIN . '_language_help_message_' . $language );
		return $option;
	}

	public static function dismiss_language_detector( $language = '' ) {
		if ( '' === $language ) {
			$language = get_locale();
		}
		update_option( self::TEXT_DOMAIN . '_language_help_message_' . $language, time() );
		return true;
	}
}
