<?php


/**
 * Estimates Controller
 *
 *
 * @package Sprout_Estimate
 * @subpackage Estimates
 */
class SI_Estimates_Template extends SI_Estimates {

	public static function init() {
		// Templating
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'remove_scripts_and_styles' ), PHP_INT_MAX );
		add_action( 'wp_print_scripts', array( __CLASS__, 'remove_scripts_and_styles_from_stupid_themes_and_plugins' ), -PHP_INT_MAX ); // can't rely on themes to abide by enqueing correctly
	}

	/////////////////
	// Templating //
	/////////////////

	/**
	 * Remove all actions to wp_print_scripts since stupid themes (and plugins) want to use it as a
	 * hook to enqueue scripts and plugins. Ideally we would live in a world where this wasn't necessary
	 * but it is.
	 * @return
	 */
	public static function remove_scripts_and_styles_from_stupid_themes_and_plugins() {
		if ( SI_Estimate::is_estimate_query() && is_single() ) {
			if ( apply_filters( 'si_remove_scripts_styles_on_doc_pages', '__return_true' ) ) {
				remove_all_actions( 'wp_print_scripts' );
			}
		}
	}

	/**
	 * Remove all scripts and styles from the estimate view and then add those specific to si.
	 * @return
	 */
	public static function remove_scripts_and_styles() {
		if ( SI_Estimate::is_estimate_query() && is_single() ) {
			if ( apply_filters( 'si_remove_scripts_styles_on_doc_pages', '__return_true' ) ) {
				global $wp_scripts, $wp_styles;
				$allowed_scripts = apply_filters( 'si_allowed_doc_scripts', array( 'sprout_doc_scripts', 'qtip', 'dropdown' ) );
				$allowed_admin_scripts = apply_filters( 'si_allowed_admin_doc_scripts', array_merge( array( 'admin-bar' ), $allowed_scripts ) );
				if ( current_user_can( 'edit_sprout_invoices' ) ) {
					$wp_scripts->queue = $allowed_admin_scripts;
				}
				else {
					$wp_scripts->queue = $allowed_scripts;
				}
				$allowed_styles = apply_filters( 'si_allowed_admin_doc_scripts', array( 'sprout_doc_style', 'qtip', 'dropdown' ) );
				$allowed_admin_styles = apply_filters( 'si_allowed_admin_doc_scripts', array_merge( array( 'admin-bar' ), $allowed_styles ) );
				if ( current_user_can( 'edit_sprout_invoices' ) ) {
					$wp_styles->queue = $allowed_admin_styles;
				}
				else {
					$wp_styles->queue = $allowed_styles;
				}
				do_action( 'si_doc_enqueue_filtered' );
			}
			else {
				// scripts
				wp_enqueue_script( 'sprout_doc_scripts' );
				wp_enqueue_script( 'dropdown' );
				wp_enqueue_script( 'qtip' );
				// Styles
				wp_enqueue_style( 'sprout_doc_style' );
				wp_enqueue_style( 'dropdown' );
				wp_enqueue_style( 'qtip' );
			}
		}
	}

}