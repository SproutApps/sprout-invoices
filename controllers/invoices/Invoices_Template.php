<?php


/**
 * Invoices Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Invoices
 */
class SI_Invoices_Template extends SI_Controller {

	public static function init() {

		add_action( 'si_invoice_payment_button', array( __CLASS__, 'show_payment_options' ), 100, 2 );

		add_filter( 'si_line_item_content', array( __CLASS__, 'line_item_content_filter' ) );

		// Templating
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'remove_scripts_and_styles' ), PHP_INT_MAX );
		add_action( 'wp_print_scripts', array( __CLASS__, 'remove_scripts_and_styles_from_stupid_themes_and_plugins' ), -PHP_INT_MAX ); // can't rely on themes to abide by enqueing correctly
	}

	/////////////
	// Content //
	/////////////

	public static function line_item_content_filter( $description = '' ) {
		if ( apply_filters( 'si_the_content_filter_line_item_descriptions', true ) ) {
			$content = apply_filters( 'the_content', $description );
		}
		else {
			$content = wpautop( $description );
		}
		return $content;
	}


	/////////////////
	// Templating //
	/////////////////


	/**
	 * Set a purchase action since it's a bit convoluted.
	 * @param  integer $invoice_id
	 * @return string
	 */
	public static function show_payment_options( $invoice_id = 0, $payment_string = '' ) {
		if ( ! $invoice_id ) {
			$invoice_id = get_the_id();
		}
		if ( '' === $payment_string ) {
			$payment_string = ( si_has_invoice_deposit( $invoice_id ) ) ? si__( 'Pay Deposit' ) : si__( 'Pay Invoice' );
		}
		self::load_view( 'templates/invoice/payment-options', array(
				'id' => $invoice_id,
				'payment_options' => si_payment_options(),
				'payment_string' => $payment_string,
			), false );
	}

	/**
	 * Remove all actions to wp_print_scripts since stupid themes (and plugins) want to use it as a
	 * hook to enqueue scripts and plugins. Ideally we would live in a world where this wasn't necessary
	 * but it is.
	 * @return
	 */
	public static function remove_scripts_and_styles_from_stupid_themes_and_plugins() {
		if ( SI_Invoice::is_invoice_query() && is_single() ) {
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
		if ( SI_Invoice::is_invoice_query() && is_single() ) {
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
				$allowed_styles = apply_filters( 'si_allowed_doc_styles', array( 'sprout_doc_style', 'qtip', 'dropdown' ) );
				$allowed_admin_styles = apply_filters( 'si_allowed_admin_doc_styles', array_merge( array( 'admin-bar' ), $allowed_styles ) );
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