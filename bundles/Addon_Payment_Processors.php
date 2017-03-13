<?php

/**
* Addons: Admin purchasing, check for updates, etc.
*
*/
class SA_Init_Addon_Processors extends SI_Controller {

	public static function init() {
		self::load_addons();
	}

	public static function load_addons() {
		if ( SI_FREE_TEST ) {
			return;
		}

		// basic list for now with something more elegant later.
		if ( file_exists( SI_PATH.'/bundles/sprout-invoices-addon-woocommerce/inc/Woo_Payment_Processor.php' ) ) {
			require_once SI_PATH.'/bundles/sprout-invoices-addon-woocommerce/inc/Woo_Payment_Processor.php';
		}
	}
}
