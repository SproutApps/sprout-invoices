<?php

/**
 * Payment processor controller
 *
 * @package Sprout_Invoice
 * @subpackage Payments
 */
abstract class SI_Payment_Processors extends SI_Controller {
	const SETTINGS_PAGE = 'payments';
	const ENABLED_PROCESSORS_OPTION = 'si_payment_processor';
	const CURRENCY_SYMBOL_OPTION = 'si_currency_symbol1';
	const MONEY_FORMAT_OPTION = 'si_money_format';
	const AJAX_NONCE = 'si_payment_processors_nonce';
	private static $active_payment_processors;
	private static $potential_processors = array(); // added by each payment processor
	private static $currency_symbol;
	private static $money_format;

	final public static function init() {
		// init payment processors
		self::get_payment_processor();

		// always load all enabled processors on admin pages
		if ( is_admin() ) {
			self::load_enabled_processors();
			// store option after locale can be loaded.
			add_filter( 'shutdown',  array( __CLASS__, 'store_format_option' ) );
		}

		// Settings
		self::$currency_symbol = get_option( self::CURRENCY_SYMBOL_OPTION, '$' );
		self::$money_format = get_option( self::MONEY_FORMAT_OPTION, '%0.2f' );

		self::register_payment_settings();

		// Help Sections
		add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

		// AJAX utility
		add_action( 'wp_ajax_si_manually_capture_payment',  array( __CLASS__, 'manually_capture_payment' ), 10, 0 );
		add_action( 'wp_ajax_si_mark_payment_complete',  array( __CLASS__, 'manually_mark_complete' ), 10, 0 );

		// Main section
		add_action( 'si_payments_pane', array( __CLASS__, 'show_payments_pane' ), 100 );

		// js
		add_filter( 'si_admin_scripts_localization',  array( __CLASS__, 'add_currency_options' ) );
	}

	public static function store_format_option() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'sprout-apps/settings' ) {
			update_option( self::MONEY_FORMAT_OPTION, sa_get_formatted_money( rand( 11000, 9999999 ) ) );
		}
	}

	/**
	 * Get enabled processors
	 * @return  array
	 */
	public static function enabled_processors() {
		$enabled = get_option( self::ENABLED_PROCESSORS_OPTION, array() );
		if ( ! is_array( $enabled ) ) {
			$enabled = array();
		}
		return apply_filters( 'si_enabled_processors', array_filter( $enabled ) );
	}

	public static function doc_enabled_processors( $doc_id = 0 ) {
		$enabled = get_option( self::ENABLED_PROCESSORS_OPTION, array() );
		if ( ! is_array( $enabled ) ) {
			$enabled = array();
		}
		return apply_filters( 'si_doc_enabled_processors', array_filter( $enabled ), $doc_id );
	}

	public static function get_payment_classname( $payment_id = 0 ) {
		$payment_gateways = SI_Payment_Processors::enabled_processors();
		if ( 1 < count( $payment_gateways ) ) {
			return '';
		}

		$gw_methods = array();
		foreach ( $payment_gateways as $class_name ) {
			if ( method_exists( $class_name, 'get_instance' ) ) {
				$payment_processor = call_user_func( array( $class_name, 'get_instance' ) );
				$gw_methods[ $payment_processor->get_payment_method() ] = $class_name;
			}
		}

		$payment = SI_Payment::get_instance( $payment_id );
		if ( ! is_a( $payment, 'SI_Payment' ) ) {
			return '';
		}
		$payment_method = $payment->get_payment_method();
		if ( ! isset( $gw_methods[ $payment_method ] ) ) {
			return '';
		}
		return $gw_methods[ $payment_method ];
	}

	public static function is_active() {
		$enabled = SI_Payment_Processors::enabled_processors();
		return in_array( get_called_class(), $enabled );
	}

	/**
	 * Get an instance of the active payment processor
	 * Used during checkout where the payment processor is active/selected and defaults to the option
	 *
	 * @static
	 * @return SI_Payment_Processors|null
	 */
	public static function get_payment_processor() {
		if ( isset( $_REQUEST[ SI_Checkouts::CHECKOUT_QUERY_VAR ] ) && $_REQUEST[ SI_Checkouts::CHECKOUT_QUERY_VAR ] != '' ) {
			// Get the option specifying which payment processor to use
			self::$active_payment_processors = self::enabled_processors();
			foreach ( self::$active_payment_processors as $class ) {
				$payment_processor = self::load_processor( $class );
				if ( $_REQUEST[ SI_Checkouts::CHECKOUT_QUERY_VAR ] === $payment_processor->get_slug() ) {
					return $payment_processor;
				}
			}
		} else {
			// load up and get enabled
			self::load_enabled_processors();
			self::$active_payment_processors = self::enabled_processors();

			if ( empty( self::$active_payment_processors ) ) {
				return;
			}

			// get class and load
			$class = ( isset( self::$active_payment_processors[0] ) ) ? self::$active_payment_processors[0] : array() ;

			if ( ! class_exists( $class ) ) {
				return;
			}

			$default = self::load_processor( $class );

			return apply_filters( 'si_default_get_payment_processor', $default, self::$active_payment_processors );
		}
	}

	/**
	 * Load any enabled processors
	 *
	 * @return void
	 */
	public static function load_enabled_processors() {
		$current = self::enabled_processors();
		foreach ( $current as $class ) {
			self::load_processor( $class );
		}
	}

	/**
	 * Get the active credit card processor
	 * @return  mixed
	 */
	public static function get_active_credit_card_processor() {
		$processor = '';
		self::$active_payment_processors = self::enabled_processors();
		foreach ( self::$active_payment_processors as $class ) {
			if ( self::is_cc_processor( $class ) ) {
				$processor = self::load_processor( $class );
			}
		}
		return $processor;
	}

	/**
	 * @param string $class
	 * @return SI_Payment_Processors|null
	 */
	private static function load_processor( $class ) {
		if ( is_string( $class ) && class_exists( $class ) ) {
			$processor = call_user_func( array( $class, 'get_instance' ) );
			return $processor;
		}
		return null;
	}

	/**
	 * Is the processor enabled
	 * @param  string  $class Processor Class Name
	 * @return boolean
	 */
	public static function is_processor_enabled( $class ) {
		if ( ! is_array( self::$active_payment_processors ) ) {
			self::$active_payment_processors = self::enabled_processors();
		}

		$enabled = false;
		if ( in_array( $class, self::$active_payment_processors ) ) {
			$enabled = true;
		}

		return $enabled;
	}

	/**
	 * Register the payment settings
	 * @return
	 */
	public static function register_payment_settings() {

		// Addon page
		$args = array(
			'slug' => self::get_settings_page( false ),
			'title' => __( 'Sprout Invoices Payment Settings', 'sprout-invoices' ),
			'menu_title' => __( 'Payment Settings', 'sprout-invoices' ),
			'weight' => 15,
			'reset' => false,
			'section' => 'settings',
			'tab_only' => true,
			'ajax' => true,
			'ajax_full_page' => true,
			);
		do_action( 'sprout_settings_page', $args );

		// Settings
		$settings = array(
			'si_general_settings' => array(
				'title' => '',
				'weight' => 0,
				'tab' => self::get_settings_page( false ),
				'callback' => array( __CLASS__, 'settings_description' ),
				'settings' => array(
					self::ENABLED_PROCESSORS_OPTION => array(
						'label' => __( 'Payment Processors', 'sprout-invoices' ),
						'option' => array( __CLASS__, 'display_payment_methods_field' ),
						),
					self::MONEY_FORMAT_OPTION => array(
						'label' => __( 'Money Format', 'sprout-invoices' ),
						'option' => array(
							'type' => 'bypass',
							'output' => get_option( self::MONEY_FORMAT_OPTION ),
							'description' => sprintf( __( 'Money formatting is based on the local (%s) this WordPress install was configured with during installation. Please review the Sprout Invoices knowledgebase if this needs to be changed.', 'sprout-invoices' ), '<code>'.get_locale().'</code>' ),
						),
					),
				),
			),
		);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );
	}

	public static function settings_description() {
		$processors = self::get_registered_processors( 'offsite' );
		if ( ! in_array( 'SI_Paypal_EC', array_keys( $processors ) ) ) {
			printf( '<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span>%s</p></div>', sprintf( __( '<strong>Missing Paypal Express Checkout?</strong> The add-on is available for <b>free</b> on the <a href="%s">Sprout Apps marketplace</a>.', 'sprout-invoices' ), si_get_sa_link( 'https://sproutapps.co/marketplace/paypal-payments-express-checkout/' ) ) );
		} else {
			printf( '<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span><strong>%s</strong> %s</p></div>', __( 'More Payment Gateways Available:', 'sprout-invoices' ), sprintf( __( 'Checkout the Sprout Apps <a href="%s">marketplace</a>.', 'sprout-invoices' ), si_get_sa_link( 'https://sproutapps.co/marketplace/' ) ) );
		}
	}

	/**
	 * Show the payment options
	 * @return null
	 */
	public static function display_payment_methods_field() {
		$offsite = self::get_registered_processors( 'offsite' );
		$credit = self::get_registered_processors( 'credit' );
		$enabled = self::enabled_processors();

		if ( $offsite ) {
			printf( '<p><strong>%s</strong></p>', __( 'Offsite Processors', 'sprout-invoices' ) );
			foreach ( $offsite as $class => $label ) {
				printf( '<p><label><input type="checkbox" name="%s[]" value="%s" %s /> %s</label></p>', self::ENABLED_PROCESSORS_OPTION, esc_attr( $class ), checked( true, in_array( $class, $enabled ), false ), esc_html( $label ) );
			}
		}
		if ( $credit ) {
			printf( '<br/><p><strong>%s</strong></p>', __( 'Credit Card Processors', 'sprout-invoices' ) );
			printf( '<p><select name="%s[]" class="select2">', self::ENABLED_PROCESSORS_OPTION );
			printf( '<option value="">%s</option>', __( '-- None --', 'sprout-invoices' ) );
			foreach ( $credit as $class => $label ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $class ), selected( true, in_array( $class, $enabled ), false ), esc_html( $label ) );
			}
			echo '</select>';
		}
	}

	/**
	 * Settings page
	 * @param  boolean $prefixed
	 * @return string
	 */
	public static function get_settings_page( $prefixed = true ) {
		return ( $prefixed ) ? self::APP_DOMAIN . '/' . self::SETTINGS_PAGE : self::SETTINGS_PAGE ;
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	final protected function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}

	final protected function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}

	protected function __construct() {
		//
	}

	/**
	 * Show a pane based on the page within checkout. If not on the checkout
	 * show all invoice panes and the active cc processors payments pane.
	 *
	 * @param  string $current
	 * @return string
	 */
	public static function show_payments_pane( $current = '' ) {
		$processor = '';
		$checkout = SI_Checkouts::get_instance();
		if ( SI_Checkouts::is_checkout_page() ) {
			$current = $checkout->get_current_page();
			do_action( 'payments_pane_action_'.strtolower( $current ), $checkout );
			do_action( 'payments_pane_action', $current, $checkout );
			$processor = $checkout->get_processor();
		}
		$pane = '';
		switch ( $current ) {
			case SI_Checkouts::CONFIRMATION_PAGE:
				if ( method_exists( $processor, 'confirmation_pane' ) ) {
					$pane = $processor->confirmation_pane( $checkout );
				}
				break;
			case SI_Checkouts::REVIEW_PAGE:
				if ( method_exists( $processor, 'review_pane' ) ) {
					$pane = $processor->review_pane( $checkout );
				}
				break;
			case SI_Checkouts::PAYMENT_PAGE:
				if ( method_exists( $processor, 'payments_pane' ) ) {
					$pane = $processor->payments_pane( $checkout );
				}
				break;

			default:
				// Load up all invoice level panes
				self::$active_payment_processors = self::enabled_processors();
				foreach ( self::$active_payment_processors as $class ) {
					$processor = self::load_processor( $class );
					if ( method_exists( $processor, 'invoice_pane' ) ) {
						$pane .= $processor->invoice_pane( $checkout );
					}
					if ( self::is_cc_processor( $class ) && method_exists( $processor, 'payments_pane' ) ) {
						$pane .= $processor->payments_pane( $checkout );
					}
				}
				break;
		}
		return $pane;
	}

	/**
	 * Process a payment
	 *
	 * @abstract
	 * @param SI_Checkouts $checkout
	 * @param SI_Invoice $invoice
	 * @return SI_Payment|bool false if the payment failed, otherwise a Payment object
	 */
	public abstract function process_payment( SI_Checkouts $checkout, SI_Invoice $invoice );

	/**
	 * Subclasses have to register to be listed as payment options
	 *
	 * @abstract
	 * @return void
	 */
	// public static abstract function register();


	/**
	 * Remove the payments page from the list of completed checkout pages
	 *
	 * @param SI_Checkouts $checkout
	 * @return void
	 */
	protected function invalidate_checkout( SI_Checkouts $checkout ) {
		$checkout->mark_page_incomplete( SI_Checkouts::PAYMENT_PAGE );
	}

	/**
	 * Get all registered processors, activated or not.
	 * @param  string $filter filter by type
	 * @return array
	 */
	public static function get_registered_processors( $filter = '' ) {
		$processors = self::$potential_processors;
		switch ( $filter ) {
			case 'offsite':
				foreach ( $processors as $class => $label ) {
					if ( ! self::is_offsite_processor( $class ) ) {
						unset( $processors[ $class ] );
					}
				}
				break;
			case 'credit':
				foreach ( $processors as $class => $label ) {
					if ( ! self::is_cc_processor( $class ) ) {
						unset( $processors[ $class ] );
					}
				}
				break;
			default:
				break; // do not filter
		}
		return $processors;
	}

	/**
	 * Is this a CC processor
	 * @param  class  $class
	 * @return boolean
	 */
	public static function is_cc_processor( $class ) {
		return is_subclass_of( $class, 'SI_Credit_Card_Processors' );
	}

	/**
	 * Is this a Offsite processor
	 * @param  class  $class
	 * @return boolean
	 */
	public static function is_offsite_processor( $class ) {
		return is_subclass_of( $class, 'SI_Offsite_Processors' );
	}

	/**
	 * Payment processors register by adding themselves.
	 * @param class $class
	 * @param string $label Name of processor
	 */
	final protected static function add_payment_processor( $class, $label ) {
		self::$potential_processors[ $class ] = $label;
	}

	/**
	 * Currency option
	 * @return string
	 */
	public static function get_currency_symbol() {
		$localeconv = si_localeconv();
		return $localeconv['currency_symbol'];
	}

	/**
	 *
	 *
	 * @abstract
	 * @return string
	 */
	public abstract function get_payment_method();

	/**
	 * Check if a recurring payment is still active with the payment processor
	 *
	 * @param SI_Payment $payment
	 * @return void
	 */
	public function verify_recurring_payment( SI_Payment $payment ) {
		// default implementation does nothing
		// it's up to the individual payment processor to verify
	}

	/**
	 * Cancel a recurring payment
	 *
	 * @param SI_Payment $payment
	 * @return void
	 */
	public function cancel_recurring_payment( SI_Invoice $invoice ) {
		$payment = self::get_recurring_payment( $invoice );
		if ( ! $payment ) {
			return;
		}
		$payment->set_status( SI_Payment::STATUS_CANCELLED );
		// it's up to the individual payment processor to handle any other details
	}

	public static function get_recurring_payment( SI_Invoice $invoice ) {
		$payment_ids = $invoice->get_payments();
		if ( empty( $payment_ids ) ) {
			return 0;
		}
		$r_payment_id = 0;
		foreach ( $payment_ids as $pid ) {
			if ( in_array( get_post_status( $pid ), array( SI_Payment::STATUS_RECURRING, SI_Payment::STATUS_CANCELLED ) ) ) {
				$r_payment_id = $pid;
			}
		}
		if ( ! $r_payment_id ) {
			return false;
		}
		$payment = SI_Payment::get_instance( $r_payment_id );
		return $payment;
	}


	public static function add_currency_options( $js_object = array() ) {
		$js_object['currency_symbol'] = sa_get_currency_symbol();
		$js_object['localeconv'] = si_localeconv();
		return $js_object;
	}

	/**
	 * Function to be called via AJAX to manually capture a payment.
	 * Processor must support the si_manually_capture_purchase action.
	 *
	 * @return
	 */
	public static function manually_capture_payment() {
		if ( ! isset( $_REQUEST['capture_payment_nonce'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['capture_payment_nonce'];
		if ( ! wp_verify_nonce( $nonce, self::AJAX_NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! current_user_can( 'manage_sprout_invoices_payments' ) ) {
			return;
		}

		$payment_id = $_REQUEST['payment_id'];
		$payment = SI_Payment::get_instance( $payment_id );
		$status = $payment->get_status();

		if ( ! is_a( $payment, 'SI_Payment' ) ) {
			self::ajax_fail( 'Payment ID Error.' ); }

		// Payment processors need to allow for this functionality.
		do_action( 'si_manually_capture_purchase', $payment );

		if ( $payment->get_status() != $status ) {
			header( 'Content-type: application/json' );
			echo wp_json_encode( array( 'response' => __( 'Payment status updated.', 'sprout-invoices' ) ) );
			exit();
		} else {
			self::ajax_fail( 'Failed payment capture.' );
		}
	}

	/**
	 * Mark payment complete
	 *
	 * @return
	 */
	public static function manually_mark_complete() {
		if ( ! isset( $_REQUEST['complete_payment_nonce'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['complete_payment_nonce'];
		if ( ! wp_verify_nonce( $nonce, self::AJAX_NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! current_user_can( 'manage_sprout_invoices_payments' ) ) {
			return;
		}

		$payment_id = $_REQUEST['payment_id'];
		$payment = SI_Payment::get_instance( $payment_id );
		$status = $payment->get_status();
		if ( ! is_a( $payment, 'SI_Payment' ) ) {
			self::ajax_fail( 'Payment ID Error.' ); }

		$payment->set_status( SI_Payment::STATUS_COMPLETE );
		do_action( 'payment_complete', $payment );

		if ( $payment->get_status() != $status ) {
			header( 'Content-type: application/json' );
			echo wp_json_encode( array( 'response' => __( 'Payment status updated.', 'sprout-invoices' ) ) );
			exit();
		} else {
			self::ajax_fail( 'Failed payment capture.' );
		}

	}

	/**
	 * Generate a list of months
	 *
	 * @static
	 * @return array
	 */
	public static function get_month_options() {
		$months = array(
			1 => __( '01 - January', 'sprout-invoices' ),
			2 => __( '02 - February', 'sprout-invoices' ),
			3 => __( '03 - March', 'sprout-invoices' ),
			4 => __( '04 - April', 'sprout-invoices' ),
			5 => __( '05 - May', 'sprout-invoices' ),
			6 => __( '06 - June', 'sprout-invoices' ),
			7 => __( '07 - July', 'sprout-invoices' ),
			8 => __( '08 - August', 'sprout-invoices' ),
			9 => __( '09 - September', 'sprout-invoices' ),
			10 => __( '10 - October', 'sprout-invoices' ),
			11 => __( '11 - November', 'sprout-invoices' ),
			12 => __( '12 - December', 'sprout-invoices' ),
		);
		return apply_filters( 'si_payment_month_options', $months );
	}

	/**
	 * Generate an array of years, starting with the current year, with keys matching values
	 *
	 * @static
	 * @param int     $number The number of values in the list
	 * @return array
	 */
	public static function get_year_options( $number = 10 ) {
		$this_year = (int) date( 'Y' );
		$years = array();
		for ( $i = 0 ; $i < $number ; $i++ ) {
			$years[ $this_year + $i ] = $this_year + $i;
		}
		return apply_filters( 'si_payment_year_options', $years );
	}

	////////////////
	// Admin Help //
	////////////////

	public static function help_sections() {
		add_action( 'load-sprout-apps_page_sprout-apps/settings', array( __CLASS__, 'help_tabs' ) );
	}

	public static function help_tabs() {
		if ( isset( $_GET['tab'] ) && $_GET['tab'] == self::SETTINGS_PAGE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
				'id' => 'payment-about',
				'title' => __( 'About Payment Processing', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p>', __( 'By default no payment processors are active. After selecting the Payment Settings tab you will find that there are two types of Payment Processors: Offsite Processors and Credit Card Processors.', 'sprout-invoices' ), __( 'After selecting the processors you want to accept for invoice payments and saving the processor options will be shown. Each payment process has its own settings, review and complete each option before saving again', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'payment-cc',
				'title' => __( 'Credit Card Processing', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p>', __( 'These are the credit card payment processors. You’ll notice that only one credit card processor can be activated at a time, this is by design since there’s no viable reason to accept CCs from multiple processors.', 'sprout-invoices' ), __( 'If you ware accepting credit card information on your site you will want to use SSL for your site to keep your client’s data secure. Having SSL on your site is highly recommended for more reasons than accepting CC information, since every WordPress site has at least a login form.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'payment-offsite',
				'title' => __( 'Offsite Processing', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'Essentially a payment processed outside of your site. These payments can include external payment providers like Paypal and Check or P.O. payments. Virtually an unlimited amount of offsite processors can be activated.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'payment-currency',
				'title' => __( 'Currency Symbol', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'If your currency is formatted wight the symbol after the amount, place a % before your currency symbol. For example, %£.', 'sprout-invoices' ) ),
			) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', __( 'For more information:', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/payment-settings/', __( 'Documentation', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', si_get_sa_link( 'https://sproutapps.co/support/' ), __( 'Support', 'sprout-invoices' ) )
			);
		}
	}
}
