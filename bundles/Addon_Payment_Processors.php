<?php

/**
* Addons: Admin purchasing, check for updates, etc.
*
*/
class SA_Init_Addon_Processors extends SI_Controller {

	public static function init() {
		self::load_bundled_payment_processor();
	}

	public static function load_bundled_payment_processor() {
		if ( SI_FREE_TEST ) {
			return;
		}

		// basic list for now with something more elegant later.
		if ( file_exists( SI_PATH.'/bundles/sprout-invoices-addon-woocommerce/inc/Woo_Payment_Processor.php' ) ) {
			require_once SI_PATH.'/bundles/sprout-invoices-addon-woocommerce/inc/Woo_Payment_Processor.php';
		}

		if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-stripe/SA_Stripe.php' ) ) {
			if ( ! defined( 'SA_ADDON_STRIPE_URL' ) ) {
				define( 'SA_ADDON_STRIPE_URL', plugins_url( '/sprout-invoices-payments-stripe', __FILE__ ) );
			}
			require_once SI_PATH.'/bundles/sprout-invoices-payments-stripe/SA_Stripe.php';
		}

		if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-offsite-url/inc/SA_Offsite_URL.php' ) ) {
			if ( ! defined( 'SA_ADDON_PAYMENTREDIRECT_URL' ) ) {
				define( 'SA_ADDON_PAYMENTREDIRECT_URL', plugins_url( '/sprout-invoices-payments-offsite-url', __FILE__ ) );
			}
			require_once SI_PATH.'/bundles/sprout-invoices-payments-offsite-url/inc/SA_Offsite_URL.php';
		}

		if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-squareup/inc/Square_Up.php' ) ) {
			if ( ! defined( 'SA_ADDON_SQUARE_URL' ) ) {
				define( 'SA_ADDON_SQUARE_URL', plugins_url( '/sprout-invoices-payments-squareup', __FILE__ ) );
			}
			require_once SI_PATH.'/bundles/sprout-invoices-payments-squareup/inc/Square_Up.php';
		}
	}
}
