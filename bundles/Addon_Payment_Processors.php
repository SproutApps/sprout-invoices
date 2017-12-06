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

		if ( ! class_exists( 'SA_Stripe' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-stripe/SA_Stripe.php' ) ) {
				if ( ! function_exists( 'sa_load_auto_billing_addon' ) ) {
					if ( ! defined( 'SA_ADDON_STRIPE_URL' ) ) {
						define( 'SA_ADDON_STRIPE_URL', plugins_url( '/sprout-invoices-payments-stripe', __FILE__ ) );
					}
					require_once SI_PATH.'/bundles/sprout-invoices-payments-stripe/SA_Stripe.php';
				}
			}
		}

		if ( ! class_exists( 'SA_Payment_Redirect' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-offsite-url/inc/SA_Offsite_URL.php' ) ) {
				if ( ! defined( 'SA_ADDON_PAYMENTREDIRECT_URL' ) ) {
					define( 'SA_ADDON_PAYMENTREDIRECT_URL', plugins_url( '/sprout-invoices-payments-offsite-url', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-offsite-url/inc/SA_Offsite_URL.php';
			}
		}

		if ( ! class_exists( 'SA_Square' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-squareup/inc/Square_Up.php' ) ) {
				if ( ! defined( 'SA_ADDON_SQUARE_URL' ) ) {
					define( 'SA_ADDON_SQUARE_URL', plugins_url( '/sprout-invoices-payments-squareup', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-squareup/inc/Square_Up.php';
			}
		}

		if ( ! class_exists( 'SA_2Checkout' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-2checkout/inc/SA_2CO.php' ) ) {
				if ( ! defined( 'SA_ADDON_2CO_URL' ) ) {
					define( 'SA_ADDON_2CO_URL', plugins_url( '/sprout-invoices-payments-2checkout', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-2checkout/inc/SA_2CO.php';
			}
		}

		if ( ! class_exists( 'SA_AuthorizeNet' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-authorize-net/SA_AuthorizeNet.php' ) ) {
				if ( ! function_exists( 'sa_load_auto_billing_addon' ) ) {
					if ( ! defined( 'SA_ADDON_AUTHORIZENET_URL' ) ) {
						define( 'SA_ADDON_AUTHORIZENET_URL', plugins_url( '/sprout-invoices-payments-authorize-net', __FILE__ ) );
					}
					require_once SI_PATH.'/bundles/sprout-invoices-payments-authorize-net/SA_AuthorizeNet.php';
				}
			}
		}

		if ( ! class_exists( 'SA_BeanStream' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-beanstream/inc/SA_Beanstream.php' ) ) {
				if ( ! defined( 'SA_ADDON_BEANSTREAM_URL' ) ) {
					define( 'SA_ADDON_BEANSTREAM_URL', plugins_url( '/sprout-invoices-payments-beanstream', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-beanstream/inc/SA_Beanstream.php';
			}
		}

		if ( ! class_exists( 'SA_BluePay' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-bluepay/SA_BluePay.php' ) ) {
				if ( ! defined( 'SA_ADDON_BLUEPAY_URL' ) ) {
					define( 'SA_ADDON_BLUEPAY_URL', plugins_url( '/sprout-invoices-payments-bluepay', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-bluepay/SA_BluePay.php';
			}
		}

		if ( ! class_exists( 'SA_Braintree' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-braintree/inc/SA_Braintree.php' ) ) {
				if ( ! defined( 'SA_ADDON_BRAINTREE_PATH' ) ) {
					define( 'SA_ADDON_BRAINTREE_PATH', dirname( __FILE__ ) . '/sprout-invoices-payments-braintree/' );
				}
				if ( ! defined( 'SA_ADDON_BRAINTREE_URL' ) ) {
					define( 'SA_ADDON_BRAINTREE_URL', plugins_url( '/sprout-invoices-payments-braintree', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-braintree/inc/SA_Braintree.php';
			}
		}

		if ( ! class_exists( 'SA_eWAY' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-eway/sprout-invoices-eway.php' ) ) {
				if ( ! defined( 'SA_ADDON_EWAY_URL' ) ) {
					define( 'SA_ADDON_EWAY_URL', plugins_url( '/sprout-invoices-payments-eway', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-eway/inc/SA_eWay.php';
			}
		}

		if ( ! class_exists( 'SA_PaymentExpressCC' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-express-payments-cc/SA_PaymentExpress_CC.php' ) ) {
				if ( ! defined( 'SA_ADDON_PX_POST_URL' ) ) {
					define( 'SA_ADDON_PX_POST_URL', plugins_url( '/sprout-invoices-payments-express-payments-cc', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-express-payments-cc/SA_PaymentExpress_CC.php';
			}
		}

		if ( ! class_exists( 'SI_Mercadopago' ) ) {

			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-mercadopago/SI_Mercadopago.php' ) ) {
				if ( ! defined( 'SA_ADDON_MERCADOPAGO_URL' ) ) {
					define( 'SA_ADDON_MERCADOPAGO_URL', plugins_url( '/sprout-invoices-payments-mercadopago', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-mercadopago/SI_Mercadopago.php';
			}
		}

		if ( ! class_exists( 'SA_NMI' ) ) {
			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-nmi/SA_NMI.php' ) ) {
				if ( ! defined( 'SA_ADDON_NMI_URL' ) ) {
					define( 'SA_ADDON_NMI_URL', plugins_url( '/sprout-invoices-payments-nmi', __FILE__ ) );
				}
				if ( ! defined( 'SA_ADDON_NMI_PATH' ) ) {
					define( 'SA_ADDON_NMI_PATH', dirname( __FILE__ ) . '/sprout-invoices-payments-nmi' );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-nmi/SA_NMI.php';
			}
		}

		if ( ! class_exists( 'SA_PagSeguro' ) ) {
			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-pagseguro/PagSeguro.php' ) ) {
				if ( ! defined( 'SA_ADDON_PAGSEGURO_URL' ) ) {
					define( 'SA_ADDON_PAGSEGURO_URL', plugins_url( '/sprout-invoices-payments-pagseguro', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-pagseguro/PagSeguro.php';
			}
		}

		if ( ! class_exists( 'SA_Square_Cash' ) ) {
			if ( file_exists( SI_PATH.'/bundles/sprout-invoices-payments-square-cash/inc/SA_Square_Cash.php' ) ) {
				if ( ! defined( 'SA_ADDON_SQUARECASH_URL' ) ) {
					define( 'SA_ADDON_SQUARECASH_URL', plugins_url( '/sprout-invoices-payments-square-cash', __FILE__ ) );
				}
				require_once SI_PATH.'/bundles/sprout-invoices-payments-square-cash/inc/SA_Square_Cash.php';
			}
		}
	}
}
