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
		add_filter( 'the_title', array( __CLASS__, 'prevent_auto_draft_title' ), 10, 2 );

		// Templating
		add_action( 'wp_print_scripts', array( __CLASS__, 'remove_scripts_and_styles_from_stupid_themes_and_plugins' ), -PHP_INT_MAX ); // can't rely on themes to abide by enqueing correctly
	}

	/////////////
	// Content //
	/////////////

	public static function prevent_auto_draft_title( $title = '', $post_id = 0 ) {
		if ( __( 'Auto Draft' ) !== $title ) {
			return $title;
		}
		if ( SI_Estimate::POST_TYPE !== get_post_type( $post_id ) ) {
			return $title;
		}
		$estimate = SI_Estimate::get_instance( $post_id );
		return apply_filters( 'si_default_estimate_title', sprintf( '#%s', $estimate->get_estimate_id() ), $estimate );

	}

	/**
	 * Unused
	 * @see  /controllers/invoices/Invoices_Template.php
	 */
	public static function line_item_content_filter( $description = '' ) {
		if ( apply_filters( 'si_the_content_filter_line_item_descriptions', true ) ) {
			$content = apply_filters( 'the_content', $description );
		} else {
			$content = wpautop( $description );
		}
		return $content;
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
}
