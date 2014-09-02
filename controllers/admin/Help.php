<?php 


/**
 * Admin help controller.
 *
 * @package Sprout_Invoice
 * @subpackage Help
 */
class SI_Help extends SI_Controller {
	const NONCE = 'si_pointer_nonce';
	protected static $pointer_key = 'si_pointer_hook';

	public static function init() {

		if ( is_admin() ) {

		}

	}

}