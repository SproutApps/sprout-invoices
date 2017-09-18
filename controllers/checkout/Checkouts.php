<?php

/**
 * Checkouts Controller
 *
 * At the moment the checkout is
 *
 * @package Sprout_Invoice
 * @subpackage Checkouts
 */
class SI_Checkouts extends SI_Controller {
	// query variables
	const CHECKOUT_QUERY_VAR = 'invoice_payment'; // param is the checkout action
	const CHECKOUT_ACTION = 'si_payment_action'; // param is the checkout action


	// Meta key
	const CACHE_META_KEY = 'si_checkout_cache'; // Combine with $blog_id to get the actual meta key

	// Pages
	const PAYMENT_PAGE = 'payment';
	const REVIEW_PAGE = 'review';
	const CONFIRMATION_PAGE = 'confirmation';

	// controller and pages
	private static $checkout_controller = null;
	private $pages = array();
	private $current_page = '';

	private $invoice;
	private $payment_processor;
	public $checkout_complete = false;
	public $cache = array();


	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'override_template' ), 100 );
		// FUTURE Rewrite rules for each payment action.
		self::register_query_var( self::CHECKOUT_QUERY_VAR, array( __CLASS__, 'checkout' ) );
	}

	/**
	 * We're on the checkout page. Time to auto-instantiate!
	 *
	 * @static
	 * @return void
	 */
	public static function checkout() {
		self::get_instance();
	}

	public static function is_checkout_page() {
		return isset( $_GET[ self::CHECKOUT_QUERY_VAR ] );
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	private function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}

	private function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}
	public static function get_instance() {
		if ( ! ( self::$checkout_controller && is_a( self::$checkout_controller, __CLASS__ ) ) ) {
			self::$checkout_controller = new self();
		}
		return self::$checkout_controller;
	}

	private function __construct() {
		self::do_not_cache(); // never cache the checkout pages
		$this->load_cache();
		$this->load_invoice();
		$this->payment_processor = SI_Payment_Processors::get_payment_processor();
		$this->load_pages();
		$this->handle_action( isset( $_REQUEST[ self::CHECKOUT_ACTION ] )?$_REQUEST[ self::CHECKOUT_ACTION ]:'' );
	}

	public function checkout_url( $processor_slug = '1' ) {
		return esc_url_raw( add_query_arg( array( self::CHECKOUT_QUERY_VAR => $processor_slug ), get_permalink( $this->invoice->get_id() ) ) );
	}

	public function checkout_complete_url( $processor_slug = '1' ) {
		return esc_url_raw( add_query_arg( array( self::CHECKOUT_QUERY_VAR => $processor_slug, self::CHECKOUT_ACTION => self::REVIEW_PAGE ), get_permalink( $this->invoice->get_id() ) ) );
	}

	public function checkout_confirmation_url( $processor_slug = '1' ) {
		return esc_url_raw( add_query_arg( array( self::CHECKOUT_QUERY_VAR => $processor_slug, self::CHECKOUT_ACTION => self::CONFIRMATION_PAGE ), get_permalink( $this->invoice->get_id() ) ) );
	}

	/**
	 * Override the template and use something custom.
	 * @param  string $template
	 * @return string           full path.
	 */
	public static function override_template( $template ) {
		if ( isset( $_REQUEST[ self::CHECKOUT_QUERY_VAR ] ) ) {
			if ( SI_Invoice::is_invoice_query() ) {
				$checkout = self::get_instance();
				$current = $checkout->get_current_page();
				$template = self::locate_template( array(
					'checkout/'.$current.'.php',
					'checkout-'.$current.'.php',
					'checkout.php',
					'invoice/checkout-'.$current.'.php',
					'invoice/checkout.php',
					'invoice.php',
					'invoice/invoice.php',
				), $template );
			}
		}
		return $template;
	}

	private function handle_action( $action ) {
		// do the callback for the just-submitted checkout page
		if ( $action ) {
			if ( ! $this->checkout_complete ) {
				// save state in case we're redirected elsewhere
				add_filter( 'wp_redirect', array( $this, 'save_cache_on_redirect' ), 10, 1 );

				// The action callback for the page should add data to the cache as necessary,
				// in addition to any other processing it needs to do.
				// Under no circumstances should credit card information be stored in the cache.
				do_action( 'si_checkout_action_'.strtolower( $action ), $this );
				do_action( 'si_checkout_action', $action, $this );
				$this->save_cache();
			}
			$current = $this->get_current_page( true );
			if ( $current == self::CONFIRMATION_PAGE && ! $this->checkout_complete ) {
				$this->complete_checkout();
			}
		} else {
			// we're starting over. Clear any cached checkout data
			$this->clear_cache();
			$this->get_current_page();
		}
	}

	private function load_pages() {
		$pages = array(
			self::PAYMENT_PAGE => array(
				'title' => __( 'Payment', 'sprout-invoices' ),
				'weight' => 10,
			),
			self::REVIEW_PAGE => array(
				'title' => __( 'Review', 'sprout-invoices' ),
				'weight' => 1000,
			),
			self::CONFIRMATION_PAGE => array(
				'title' => __( 'Confirmation', 'sprout-invoices' ),
				'weight' => PHP_INT_MAX, // this must go last
			),
		);
		$this->pages = apply_filters( 'si_checkout_pages', $pages );
		$this->pages[ self::CONFIRMATION_PAGE ]['weight'] = PHP_INT_MAX; // in case anything stupid happened
		uasort( $pages, array( $this, 'sort_by_weight' ) );

		$this->register_payment_page();
		$this->register_review_page();
		$this->register_confirmation_page();
	}

	/**
	 * Load all data cached from previous checkout pages
	 *
	 * @return void
	 */
	private function load_cache() {
		global $blog_id;
		$this->cache = get_user_meta( get_current_user_id(), $blog_id.'_'.self::CACHE_META_KEY, true );
		if ( ! is_array( $this->cache ) ) {
			$this->cache = array();
		}
		if ( isset( $this->cache['payment_id'] ) && $this->cache['payment_id'] ) {
			$this->checkout_complete = true;
		}
	}

	/**
	 * Save all data from submitted checkout pages
	 *
	 * @return void
	 */
	private function save_cache() {
		global $blog_id;
		update_user_meta( get_current_user_id(), $blog_id.'_'.self::CACHE_META_KEY, $this->cache );
	}

	/**
	 * Before a redirect save the cache.
	 * @param  [type] $location
	 * @return
	 */
	public function save_cache_on_redirect( $location ) {
		$this->save_cache();
		return $location;
	}

	/**
	 * Clear out all data from checkout
	 *
	 * @return void
	 */
	private function clear_cache() {
		global $blog_id;
		update_user_meta( get_current_user_id(), $blog_id.'_'.self::CACHE_META_KEY, array() );
		$this->cache = array();
	}

	private function load_invoice() {

		$invoice_id = 0;
		if ( isset( $_GET['sa_invoice'] ) && ! self::using_permalinks() ) {
			if ( is_numeric( $_GET['sa_invoice'] ) ) {
				$invoice_id = (int) $_GET['sa_invoice'];
			} // slugs are used in some strange circumstances
			else {
				$posts = get_posts(array(
					'name' => $_GET['sa_invoice'],
					'posts_per_page' => 1,
					'post_type' => SI_Invoice::POST_TYPE,
				) );
				$post = $posts[0];
				$invoice_id = $post->ID;
			}
		}
		// If permalinks are used this is the default method of finding the post id.
		if ( ! $invoice_id ) {
			$invoice_id = url_to_postid( esc_url_raw( $_SERVER['REQUEST_URI'] ) );
		}

		if ( ! $invoice_id || get_post_type( $invoice_id ) !== SI_Invoice::POST_TYPE ) {
			self::set_message( __( 'Invoice ID invalid. Payments are disabled.', 'sprout-invoices' ), self::MESSAGE_STATUS_ERROR );
			return;
		}

		$this->invoice = SI_Invoice::get_instance( $invoice_id );

		if ( ! $this->invoice && ! $this->checkout_complete ) {
			self::set_message( __( 'No invoice associated with this checkout.', 'sprout-invoices' ), self::MESSAGE_STATUS_ERROR );
			wp_redirect( '/', 303 );
			exit();
		}
		do_action( 'si_load_invoice', $this, $this->invoice );
	}

	/**
	 * Get private invoice
	 * @return
	 */
	public function get_invoice() {
		return $this->invoice;
	}

	/**
	 * Get private invoice
	 * @return
	 */
	public function get_processor() {
		return $this->payment_processor;
	}

	/**
	 * Get private payment object added after a complete payment
	 * @return
	 */
	public function get_payment_id() {
		return $this->cache['payment_id'];
	}

	/**
	 * Sets $this->current_page to the next incomplete page
	 *
	 * @return mixed The current page key
	 */
	private function set_current_page() {
		if ( isset( $this->cache['completed'] ) && is_array( $this->cache['completed'] ) ) {
			foreach ( $this->pages as $key => $info ) {
				// get the next page that is not completed
				if ( ! $this->is_page_complete( $key ) ) {
					$this->current_page = $key;
					return $this->current_page;
				}
			}
		} else {
			$array_keys = array_keys( $this->pages );
			$this->current_page = array_shift( $array_keys );
			return $this->current_page;
		}
	}

	/**
	 *
	 *
	 * @param bool    $reload Whether to check the page cache again
	 * @return mixed The key of the current page
	 */
	public function get_current_page( $reload = false ) {
		if ( ! $this->current_page || $reload ) {
			$this->set_current_page();
		}
		return $this->current_page;
	}

	/**
	 * Add the given page to the completed array
	 *
	 * @param string  $page
	 * @return void
	 */
	public function mark_page_complete( $page ) {
		if ( ! isset( $this->cache['completed'] ) || ! is_array( $this->cache['completed'] ) ) {
			$this->cache['completed'] = array();
		}
		if ( ! in_array( $page, $this->cache['completed'] ) ) {
			$this->cache['completed'][] = $page;
		}
	}

	/**
	 * Remove the given page (and all following pages) from the
	 * completed pages array
	 *
	 * @param string  $page
	 * @return void
	 */
	public function mark_page_incomplete( $page ) {
		// if there are no completed page, there's nothing to do
		if ( ! isset( $this->cache['completed'] ) || ! is_array( $this->cache['completed'] ) ) {
			$this->cache['completed'] = array();
			return;
		}

		// get a list of page keys before the incomplete $page
		$keys = array_keys( $this->pages );
		$position = array_search( $page, $keys );
		$keep = array_slice( $keys, 0, $position );

		// remove the offending keys
		$this->cache['completed'] = array_intersect( $keep, $this->cache['completed'] );
	}

	/**
	 * Check if the given page is in the completed pages array
	 *
	 * @param string  $page
	 * @return bool
	 */
	public function is_page_complete( $page ) {
		if ( ! isset( $this->cache['completed'] ) || ! is_array( $this->cache['completed'] ) ) {
			$this->cache['completed'] = array();
		}
		return in_array( $page, $this->cache['completed'] );
	}

	/**
	 * Register action hooks for displaying and processing the payment page
	 *
	 * @return void
	 */
	private function register_payment_page() {
		add_action( 'si_checkout_action_'.self::PAYMENT_PAGE, array( $this, 'process_payment_page' ), 100, 1 );
	}

	/**
	 * Process the payment form
	 *
	 * @return void
	 */
	public function process_payment_page( SI_Checkouts $checkout ) {
		$checkout->mark_page_complete( self::PAYMENT_PAGE );
	}

	/**
	 * Register action hooks for displaying and processing the payment page
	 *
	 * @return void
	 */
	private function register_review_page() {
		add_action( 'si_checkout_action_'.self::REVIEW_PAGE, array( $this, 'process_review_page' ), 100, 1 );
	}

	/**
	 * Process the review page
	 *
	 * @param SI_Checkouts $checkout
	 * @return void
	 */
	public function process_review_page( SI_Checkouts $checkout ) {
		$checkout->mark_page_complete( self::REVIEW_PAGE );
	}

	/**
	 * Register action hooks for displaying the confirmation page
	 *
	 * @return void
	 */
	private function register_confirmation_page() {
		// No action to process. This is the last page.
	}

	private function get_billing_fields() {
		$fields = $this->get_standard_address_fields();
		$fields = apply_filters( 'si_checkout_fields_billing', $fields );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	/**
	 * All the checkout pages have been processed. Data should be in the cache. Do something with it.
	 *
	 * @return void
	 */
	private function complete_checkout() {
		// process the payment
		do_action( 'checkout_processing_payment', $this );
		$payment = $this->payment_processor->process_payment( $this, $this->invoice );
		do_action( 'checkout_process_payment', $this, $payment );
		do_action( 'si_log', __CLASS__ . '::' . __FUNCTION__ . ' - complete_checkout payment', $payment );

		if ( ! is_a( $payment, 'SI_Payment' ) ) {
			// payment wasn't successful; delete the purchase and go back to the payment page
			$this->mark_page_incomplete( self::PAYMENT_PAGE );
			$this->get_current_page( true );
			do_action( 'checkout_failed', $this );
			return;
		}
		$this->cache['payment_id'] = $payment->get_id();
		do_action( 'processed_payment', $payment, $this );

		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance( $invoice_id );

		// Messaging
		if ( $payment->get_status() === SI_Payment::STATUS_PENDING ) {
			if ( $invoice->get_balance() < 0.01 ) {
				self::set_message( __( 'Payment Pending', 'sprout-invoices' ), self::MESSAGE_STATUS_INFO );
			} else {
				self::set_message( sprintf( __( 'Pending Payment Received. Current Balance: %s', 'sprout-invoices' ), sa_get_formatted_money( $invoice->get_balance() ) ), self::MESSAGE_STATUS_INFO );
			}
		} else {
			if ( $invoice->get_balance() < 0.01 ) {
				self::set_message( __( 'Payment Received & Invoice Paid!', 'sprout-invoices' ), self::MESSAGE_STATUS_INFO );
			} else {
				self::set_message( sprintf( __( 'Partial Payment Received. Current Balance: %s', 'sprout-invoices' ), sa_get_formatted_money( $invoice->get_balance() ) ), self::MESSAGE_STATUS_INFO );
			}
		}

		// wrap up checkout and tell the purchase we're done
		do_action( 'completing_checkout', $this, $payment );
		$this->checkout_complete = true;
		do_action( 'checkout_completed', $this, $payment );
	}
}
