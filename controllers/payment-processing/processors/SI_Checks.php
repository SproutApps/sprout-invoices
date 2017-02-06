<?php

/**
 * Paypal offsite payment processor.
 *
 * These actions are fired for each checkout page.
 *
 * Payment page - 'si_checkout_action_'.SI_Checkouts::PAYMENT_PAGE
 * Review page - 'si_checkout_action_'.SI_Checkouts::REVIEW_PAGE
 * Confirmation page - 'si_checkout_action_'.SI_Checkouts::CONFIRMATION_PAGE
 *
 * Necessary methods:
 * get_instance -- duh
 * get_slug -- slug for the payment process
 * get_options -- used on the invoice payment dropdown
 * process_payment -- called when the checkout is complete before the confirmation page is shown. If a
 * payment fails than the user will be redirected back to the invoice.
 *
 * @package SI
 * @subpackage Payment Processing_Processor
 */
class SI_Checks extends SI_Offsite_Processors {
	const PAYMENT_METHOD = 'Check';
	const PAYMENT_SLUG = 'checks';
	protected static $instance;

	public static function get_instance() {
		if ( ! ( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_payment_method() {
		return self::PAYMENT_METHOD;
	}

	public function get_slug() {
		return self::PAYMENT_SLUG;
	}

	public static function register() {
		self::add_payment_processor( __CLASS__, __( 'Check (onsite submission)', 'sprout-invoices' ) );
	}

	public static function public_name() {
		return __( 'Check', 'sprout-invoices' );
	}

	public static function checkout_options() {
		$option = array(
			'icons' => array( SI_URL . '/resources/front-end/img/check.png' ),
			'label' => __( 'Check', 'sprout-invoices' ),
			'cc' => array(),
			);
		return apply_filters( 'si_checks_checkout_options', $option );
	}

	protected function __construct() {
		parent::__construct();

		// Remove pages
		add_filter( 'si_checkout_pages', array( $this, 'remove_checkout_pages' ) );

		add_action( 'checkout_completed', array( $this, 'post_checkout_redirect' ), 10, 2 );
	}



	/**
	 * Loaded via SI_Payment_Processors::show_payments_pane
	 * @param  SI_Checkouts $checkout
	 * @return
	 */
	public function payments_pane( SI_Checkouts $checkout ) {
		self::load_view( 'templates/checkout/checks/form', array(
				'checkout' => $checkout,
				'type' => self::PAYMENT_SLUG,
				'check_fields' => $this->check_info_fields( $checkout ),
		), true );
	}



	/**
	 * Loaded via SI_Payment_Processors::show_payments_pane
	 * @param  SI_Checkouts $checkout
	 * @return
	 */
	public function invoice_pane( SI_Checkouts $checkout ) {
		self::load_view( 'templates/checkout/checks/form', array(
				'checkout' => null,
				'type' => self::PAYMENT_SLUG,
				'check_fields' => self::check_info_fields( $checkout ),
		), true );
	}

	/**
	 * An array of fields for check payments
	 *
	 * @static
	 * @return array
	 */
	public static function check_info_fields( $checkout = '' ) {
		$fields = array(
			'amount' => array(
				'type' => 'text',
				'weight' => 1,
				'label' => __( 'Amount', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => true,
			),
			'check_number' => array(
				'type' => 'text',
				'weight' => 5,
				'label' => __( 'Check Number', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => true,
			),
			'mailed' => array(
				'type' => 'date',
				'weight' => 10,
				'label' => __( 'Date Mailed', 'sprout-invoices' ),
				'attributes' => array(
					'autocomplete' => 'off',
				),
				'default' => date_i18n( 'Y-m-d' ),
				'required' => true,
			),
			'notes' => array(
				'type' => 'textarea',
				'weight' => 15,
				'label' => __( 'Notes', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => false,
			),
			'nonce' => array( // anti-spam honeypot
				'type' => 'hidden',
				'weight' => 50,
				'label' => __( 'Skip this unless you are not human.', 'sprout-invoices' ),
				'required' => true,
				'value' => wp_create_nonce( SI_Controller::NONCE ),
			),
		);
		$fields = apply_filters( 'sa_checks_fields', $fields, $checkout );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	/**
	 * The review page is unnecessary
	 *
	 * @param array   $pages
	 * @return array
	 */
	public function remove_checkout_pages( $pages ) {
		unset( $pages[ SI_Checkouts::REVIEW_PAGE ] );
		return $pages;
	}

	/**
	 * Process a payment
	 *
	 * @param SI_Checkouts $checkout
	 * @param SI_Invoice $invoice
	 * @return SI_Payment|bool false if the payment failed, otherwise a Payment object
	 */
	public function process_payment( SI_Checkouts $checkout, SI_Invoice $invoice ) {
		$amount = ( isset( $_POST['sa_checks_amount'] ) ) ? $_POST['sa_checks_amount'] : false ;
		$number = ( isset( $_POST['sa_checks_check_number'] ) ) ? $_POST['sa_checks_check_number'] : false ;
		$date = ( isset( $_POST['sa_checks_mailed'] ) ) ? $_POST['sa_checks_mailed'] : false ;
		$notes = ( isset( $_POST['sa_checks_notes'] ) ) ? $_POST['sa_checks_notes'] : '' ;

		if ( ! isset( $_POST['sa_checks_nonce'] ) || ! wp_verify_nonce( $_POST['sa_checks_nonce'], self::NONCE ) ) {
			return false;
		}

		if ( ! $amount ) {
			return false;
		}

		// create new payment
		$payment_id = SI_Payment::new_payment( array(
			'payment_method' => self::get_payment_method(),
			'invoice' => $invoice->get_id(),
			'amount' => $amount,
			'transaction_id' => $number,
			'data' => array(
			'amount' => $amount,
			'check_number' => $number,
			'date' => strtotime( $date ),
			'notes' => $notes,
			),
		), SI_Payment::STATUS_PENDING );
		if ( ! $payment_id ) {
			return false;
		}
		$payment = SI_Payment::get_instance( $payment_id );
		if ( $date != '' ) {
			$payment->set_post_date( date( 'Y-m-d H:i:s', strtotime( $date ) ) );
		}
		do_action( 'payment_pending', $payment );
		return $payment;
	}

	public function post_checkout_redirect( SI_Checkouts $checkout, SI_Payment $payment ) {
		if ( ! is_a( $checkout->get_processor(), __CLASS__ ) ) {
			return;
		}
		wp_redirect( $checkout->checkout_confirmation_url( self::PAYMENT_SLUG ) );
		exit();
	}

	/**
	 * Grabs error messages from a PayPal response and displays them to the user
	 *
	 * @param array   $response
	 * @param bool    $display
	 * @return void
	 */
	private function set_error_messages( $message, $display = true ) {
		if ( $display ) {
			self::set_message( $message, self::MESSAGE_STATUS_ERROR );
		} else {
			do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' - error message from paypal', $message );
		}
	}
}
SI_Checks::register();
