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
	}

	/**
	 * Get enabled processors
	 * @return  array
	 */
	public static function enabled_processors() {
		$enabled = get_option(self::ENABLED_PROCESSORS_OPTION, array());
		if ( !is_array($enabled) ) { $enabled = array(); }
		return array_filter( $enabled );
	}

	/**
	 * Get an instance of the active payment processor
	 * Used during checkout where the payment processor is active/selected and defaults to the option
	 *
	 * @static
	 * @return SI_Payment_Processors|NULL
	 */
	public static function get_payment_processor() {
		if ( isset( $_REQUEST[SI_Checkouts::CHECKOUT_QUERY_VAR] ) && $_REQUEST[SI_Checkouts::CHECKOUT_QUERY_VAR] != '' ) {
			// Get the option specifying which payment processor to use
			self::$active_payment_processors = self::enabled_processors();
			foreach ( self::$active_payment_processors as $class ) {
				$payment_processor = self::load_processor($class);
				if ( $_REQUEST[SI_Checkouts::CHECKOUT_QUERY_VAR] === $payment_processor->get_slug() ) {
					return $payment_processor;
				}
			}
		}
		else {
			self::$active_payment_processors = self::enabled_processors();
			foreach ( self::$active_payment_processors as $class ) {
				return self::load_processor( $class );
			}
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
	 * @return  
	 */
	public static function get_active_credit_card_processor() {
		self::$active_payment_processors = self::enabled_processors();
		foreach ( self::$active_payment_processors as $class ) {
			if ( self::is_cc_processor($class) ) {
				$processor = self::load_processor($class);
			}
		}
		return $processor;
	}

	/**
	 * @param string $class
	 * @return SI_Payment_Processors|NULL
	 */
	private static function load_processor( $class ) {
		if ( class_exists( $class ) ) {
			$processor = call_user_func(array($class, 'get_instance'));
			return $processor;
		}
		return NULL;
	}

	/**
	 * Register the payment settings
	 * @return  
	 */
	public static function register_payment_settings() {

		// Addon page
		$args = array(
			'slug' => self::get_settings_page( FALSE ),
			'title' => self::__( 'Sprout Invoices Payment Settings' ),
			'menu_title' => self::__( 'Payment Settings' ),
			'weight' => 15,
			'reset' => FALSE, 
			'section' => 'settings',
			'tab_only' => TRUE,
			'ajax' => TRUE,
			'ajax_full_page' => TRUE,
			);
		do_action( 'sprout_settings_page', $args );


		// Settings
		$settings = array(
			'si_general_settings' => array(
				'title' => '',
				'weight' => 0,
				'tab' => self::get_settings_page( FALSE ),
				'callback' => array( __CLASS__, 'settings_description' ),
				'settings' => array(
					self::ENABLED_PROCESSORS_OPTION => array(
						'label' => self::__( 'Payment Processors' ),
						'option' => array( __CLASS__, 'display_payment_methods_field' )
						),
					self::CURRENCY_SYMBOL_OPTION => array(
						'label' => self::__( 'Currency Symbol' ),
						'option' => array(
							'type' => 'text',
							'label' => '',
							'default' => self::$currency_symbol,
							'attributes' => array( 'class' => 'small-text' ),
						'description' => self::__( 'If your currency has the symbol after the amount place a % before your currency symbol. Example, %&pound; ' )
						)
					)
				)
			)
		);
		do_action( 'sprout_settings', $settings );
	}

	public static function settings_description() {
		$credit = self::get_registered_processors('credit');
		if ( empty( $credit ) ) {
			printf( self::__('<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span><strong>Upgrade Available:</strong> Add more payment gateways and support the future of Sprout Invoices by <a href="%s">upgrading</a>.</p></div>'), si_get_purchase_link() );
		}
		else {
			printf( self::__('<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span><strong>More Payment Gateways Available:</strong> Checkout the Sprout Apps <a href="%s">marketplace</a>.</p></div>'), self::PLUGIN_URL . '/marketplace/' );
		}
	}

	/**
	 * Show the payment options
	 * @return null
	 */
	public static function display_payment_methods_field() {
		$offsite = self::get_registered_processors('offsite');
		$credit = self::get_registered_processors('credit');
		$enabled = self::enabled_processors();

		if ( $offsite ) {
			printf( '<p><strong>%s</strong></p>', self::__('Offsite Processors') );
			foreach ( $offsite as $class => $label ) {
				printf('<p><label><input type="checkbox" name="%s[]" value="%s" %s /> %s</label></p>', self::ENABLED_PROCESSORS_OPTION, esc_attr($class), checked(TRUE, in_array($class, $enabled), FALSE), esc_html($label));
			}
		}
		if ( $credit ) {
			printf( '<br/><p><strong>%s</strong></p>', self::__('Credit Card Processors') );
			printf( '<p><select name="%s[]" class="select2">', self::ENABLED_PROCESSORS_OPTION );
			printf( '<option value="">%s</option>', self::__('-- None --') );
			foreach ( $credit as $class => $label ) {
				printf('<option value="%s" %s>%s</option>', esc_attr($class), selected(TRUE, in_array($class, $enabled), FALSE), esc_html($label));
			}
			echo '</select>';
		}
	}
	
	/**
	 * Settings page 
	 * @param  boolean $prefixed 
	 * @return string            
	 */
	public static function get_settings_page( $prefixed = TRUE ) {
		return ( $prefixed ) ? self::TEXT_DOMAIN . '/' . self::SETTINGS_PAGE : self::SETTINGS_PAGE ;
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
	 * @param  boolean $current 
	 * @return string
	 */
	public static function show_payments_pane( $current = FALSE ) {
		$checkout = SI_Checkouts::get_instance();
		if ( SI_Checkouts::is_checkout_page() ) {
			$current = $checkout->get_current_page();
			do_action( 'payments_pane_action_'.strtolower( $current ), $checkout );
			do_action( 'payments_pane_action', $current, $checkout );
			$processor = $checkout->get_processor();
		}
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
					$processor = self::load_processor($class);
					if ( method_exists( $processor, 'invoice_pane' ) ) {
						$pane .= $processor->invoice_pane( $checkout );
					}
					if ( self::is_cc_processor($class) && method_exists( $processor, 'payments_pane' ) ) {
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
	 * @return SI_Payment|bool FALSE if the payment failed, otherwise a Payment object
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
					if ( !self::is_offsite_processor($class) ) {
						unset($processors[$class]);
					}
				}
				break;
			case 'credit':
				foreach ( $processors as $class => $label ) {
					if ( !self::is_cc_processor($class) ) {
						unset($processors[$class]);
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
		return is_subclass_of($class, 'SI_Credit_Card_Processors');
	}

	/**
	 * Is this a Offsite processor
	 * @param  class  $class 
	 * @return boolean        
	 */
	public static function is_offsite_processor( $class ) {
		return is_subclass_of($class, 'SI_Offsite_Processors');
	}

	/**
	 * Payment processors register by adding themselves.
	 * @param class $class 
	 * @param string $label Name of processor
	 */
	final protected static function add_payment_processor( $class, $label ) {
		self::$potential_processors[$class] = $label;
	}

	/**
	 * Currency option
	 * @return string 
	 */
	public static function get_currency_symbol() {
		return self::$currency_symbol;
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
	public function cancel_recurring_payment( SI_Payment $payment ) {
		$payment->set_status( SI_Payment::STATUS_CANCELLED );
		// it's up to the individual payment processor to handle any other details
	}

	/**
	 * Function to be called via AJAX to manually capture a payment.
	 * Processor must support the si_manually_capture_purchase action.
	 *
	 * @return
	 */
	public static function manually_capture_payment() {
		if ( !isset( $_REQUEST['capture_payment_nonce'] ) )
			self::ajax_fail( 'Forget something?' );

		$nonce = $_REQUEST['capture_payment_nonce'];
		if ( !wp_verify_nonce( $nonce, self::AJAX_NONCE ) )
        	self::ajax_fail( 'Not going to fall for it!' );

        if ( !current_user_can( 'delete_posts' ) )
        	return;

		$payment_id = $_REQUEST['payment_id'];
		$payment = SI_Payment::get_instance( $payment_id );
		$status = $payment->get_status();

		if ( !is_a( $payment, 'SI_Payment' ) )
			self::ajax_fail( 'Payment ID Error.' );

		// Payment processors need to allow for this functionality.
		do_action( 'si_manually_capture_purchase', $payment );

		if ( $payment->get_status() != $status ) {
			header( 'Content-type: application/json' );
			echo json_encode( array( 'response' => si__( 'Payment status updated.' ) ) );
			exit();
		}
		else {
			self::ajax_fail('Failed payment capture.');
		}
	}

	/**
	 * Mark payment complete
	 * 
	 * @return  
	 */
	public static function manually_mark_complete() {
		if ( !isset( $_REQUEST['complete_payment_nonce'] ) )
			self::ajax_fail( 'Forget something?' );

		$nonce = $_REQUEST['complete_payment_nonce'];
		if ( !wp_verify_nonce( $nonce, self::AJAX_NONCE ) )
        	self::ajax_fail( 'Not going to fall for it!' );

        if ( !current_user_can( 'delete_posts' ) )
        	return;

		$payment_id = $_REQUEST['payment_id'];
		$payment = SI_Payment::get_instance( $payment_id );
		$status = $payment->get_status();
		if ( !is_a( $payment, 'SI_Payment' ) )
			self::ajax_fail( 'Payment ID Error.' );

		$payment->set_status( SI_Payment::STATUS_COMPLETE );

		if ( $payment->get_status() != $status ) {
			header( 'Content-type: application/json' );
			echo json_encode( array( 'response' => si__( 'Payment status updated.' ) ) );
			exit();
		}
		else {
			self::ajax_fail('Failed payment capture.');
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
			1 => self::__( '01 - January' ),
			2 => self::__( '02 - February' ),
			3 => self::__( '03 - March' ),
			4 => self::__( '04 - April' ),
			5 => self::__( '05 - May' ),
			6 => self::__( '06 - June' ),
			7 => self::__( '07 - July' ),
			8 => self::__( '08 - August' ),
			9 => self::__( '09 - September' ),
			10 => self::__( '10 - October' ),
			11 => self::__( '11 - November' ),
			12 => self::__( '12 - December' ),
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
		$this_year = (int)date( 'Y' );
		$years = array();
		for ( $i = 0 ; $i < $number ; $i++ ) {
			$years[$this_year+$i] = $this_year+$i;
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
					'title' => self::__( 'About Payment Processing' ),
					'content' => sprintf( '<p>%s</p><p>%s</p>', self::__('By default no payment processors are active. After selecting the Payment Settings tab you will find that there are two types of Payment Processors: Offsite Processors and Credit Card Processors.'), self::__('After selecting the processors you want to accept for invoice payments and saving the processor options will be shown. Each payment process has its own settings, review and complete each option before saving again') )
				) );

			$screen->add_help_tab( array(
					'id' => 'payment-cc',
					'title' => self::__( 'Credit Card Processing' ),
					'content' => sprintf( '<p>%s</p><p>%s</p>', self::__('These are the credit card payment processors. You’ll notice that only one credit card processor can be activated at a time, this is by design since there’s no viable reason to accept CCs from multiple processors.'), self::__('If you ware accepting credit card information on your site you will want to use SSL for your site to keep your client’s data secure. Having SSL on your site is highly recommended for more reasons than accepting CC information, since every WordPress site has at least a login form.') )
				) );

			$screen->add_help_tab( array(
					'id' => 'payment-offsite',
					'title' => self::__( 'Offsite Processing' ),
					'content' => sprintf( '<p>%s</p>', self::__('Essentially a payment processed outside of your site. These payments can include external payment providers like Paypal and Check or P.O. payments. Virtually an unlimited amount of offsite processors can be activated.') )
				) );

			$screen->add_help_tab( array(
					'id' => 'payment-currency',
					'title' => self::__( 'Currency Symbol' ),
					'content' => sprintf( '<p>%s</p>', self::__('If your currency is formatted wight the symbol after the amount, place a % before your currency symbol. For example, %£.') )
				) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', self::__('For more information:') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/payment-settings/', self::__('Documentation') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', self::__('Support') )
			);
		}
	}
}
