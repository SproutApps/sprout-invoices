<?php

/**
 * Credit Card Processors parent class, extends all payment processors.
 *
 * @package Sprout_Invoice
 * @subpackage Payments
 */
abstract class SI_Credit_Card_Processors extends SI_Payment_Processors {
	protected $cc_cache = array();

	protected function __construct() {
		parent::__construct();
		// Cache CC info into hidden field
		add_filter( 'si_credit_card_form_controls', array( $this, 'credit_card_cache' ), 10, 2 );
		add_action( 'si_checkout_action', array( $this, 'process_credit_card_cache' ), 10, 2 );

		// Add CC input fields and process & validate
		add_action( 'si_checkout_action_'.SI_Checkouts::PAYMENT_PAGE, array( $this, 'process_payment_page' ), 10, 1 );
	}

	/**
	 * Can't store credit card info in the database,
	 * pass it from page to page in a hidden form field.
	 * Making it all more important to have SSL.
	 *
	 * @param array   $panes
	 * @param SI_Checkouts/string $checkout
	 * @return array
	 */
	public function credit_card_cache( $checkout = '' ) {
		if ( $this->cc_cache && $checkout->get_current_page() != SI_Checkouts::PAYMENT_PAGE ) {
			$data = array(
				'type' => 'hidden',
				'value' => esc_attr( serialize( $this->cc_cache ) ),
			);
			sa_form_field( 'cc_cache', $data, 'credit' );
		}
	}

	/**
	 * Process the CC cache being posted and make it a variable.
	 * @param  string       $action   form actions
	 * @param  SI_Checkouts $checkout
	 * @return
	 */
	public function process_credit_card_cache( $action, SI_Checkouts $checkout ) {
		if ( isset( $_POST['sa_credit_cc_cache'] ) ) {
			$cache = unserialize( stripslashes( $_POST['sa_credit_cc_cache'] ) );
			if ( $this->validate_credit_card( $cache, $checkout ) ) {
				$this->cc_cache = $cache;

			}
		}
	}

	/**
	 * Validate the submitted credit card info
	 * Store the submitted credit card info in memory for processing the payment later
	 *
	 * @param SI_Checkouts $checkout
	 * @return void
	 */
	public function process_payment_page( SI_Checkouts $checkout ) {
		$fields = $this->payment_fields( $checkout );
		foreach ( array_keys( $fields ) as $key ) {
			if ( $key == 'cc_number' ) { // catch the cc_number so it can be sanatized
				if ( isset( $_POST['sa_credit_cc_number'] ) && strlen( $_POST['sa_credit_cc_number'] ) > 0 ) {
					$this->cc_cache['cc_number'] = preg_replace( '/\D+/', '', $_POST['sa_credit_cc_number'] );
				}
			} elseif ( isset( $_POST[ 'sa_credit_'.$key ] ) && strlen( $_POST[ 'sa_credit_'.$key ] ) > 0 ) {
				$this->cc_cache[ $key ] = $_POST[ 'sa_credit_'.$key ];
			}
		}
		$valid = $this->validate_billing( $checkout );
		$valid = $this->validate_credit_card( $this->cc_cache, $checkout );
		return $valid;
	}

	/**
	 * An array of standard credit card fields
	 *
	 * @static
	 * @return array
	 */
	public static function default_credit_fields( $checkout = '' ) {
		$fields = array(
			'cc_name' => array(
				'type' => 'text',
				'weight' => 1,
				'label' => __( 'Cardholder Name', 'sprout-invoices' ),
				'attributes' => array(
					'autocomplete' => 'off',
				),
				'required' => true,
			),
			'cc_number' => array(
				'type' => 'text',
				'weight' => 5,
				'label' => __( 'Card Number', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => true,
			),
			'cc_expiration_month' => array(
				'type' => 'select',
				'weight' => 20,
				'options' => self::get_month_options(),
				'label' => __( 'Expiration Date', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => true,
			),
			'cc_expiration_year' => array(
				'type' => 'select',
				'weight' => 21,
				'options' => self::get_year_options(),
				'label' => __( 'Expiration Date', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => true,
			),
			'cc_cvv' => array(
				'type' => 'text',
				'size' => 5,
				'weight' => 23,
				'label' => __( 'Security Code', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => true,
			),
		);
		$fields = apply_filters( 'sa_credit_card_fields', $fields, $checkout );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}


	/**
	 * Validate the credit card number
	 *
	 * Code borrowed from Ubercart.
	 *
	 * @see http://api.ubercart.org/api/function/_valid_card_number/2
	 *
	 * @static
	 * @param string  $number   The credit card number
	 * @param bool    $sanitize Clean up the string so it's only digits.
	 * @return bool
	 */
	public static function is_valid_credit_card( $number, $sanitize = false ) {
		if ( $sanitize ) {
			$number = preg_replace( '/\D+/', '', $number );
		}
		if ( ! ctype_digit( $number ) ) {
			return false; // not a number
		}

		$total = 0;
		for ( $i = 0; $i < strlen( $number ); $i++ ) {
			$digit = substr( $number, $i, 1 );
			if ( ( strlen( $number ) - $i - 1 ) % 2 ) {
				$digit *= 2;
				if ( $digit > 9 ) {
					$digit -= 9;
				}
			}
			$total += $digit;
		}

		if ( $total % 10 != 0 ) {
			return false; // invalid checksum
		}

		return true; // seems valid
	}

	/**
	 * Make sure the CVV is 3 or 4 digits long
	 *
	 * @static
	 * @param string  $cvv
	 * @return bool
	 */
	public static function is_valid_cvv( $cvv ) {
		if ( ! is_numeric( $cvv ) || strlen( $cvv ) > 4 || strlen( $cvv ) < 3 ) {
			return false;
		}
		return true;
	}

	/**
	 * Determine if the given date is in the past
	 *
	 * Code borrowed from Ubercart.
	 *
	 * @see http://api.ubercart.org/api/function/_valid_card_expiration/2
	 *
	 * @static
	 * @param int|string $year
	 * @param int_string $month
	 * @return bool
	 */
	public static function is_expired( $year, $month ) {
		if ( $year < date( 'Y' ) ) {
			return true;
		} elseif ( $year == date( 'Y' ) ) {
			if ( $month < date( 'n' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Mask a credit card number by replacing all but the first digit and last
	 * four digits with $filler
	 *
	 * @static
	 * @param string  $number
	 * @param string  $filler
	 * @return string
	 */
	public static function mask_card_number( $number, $filler = 'x' ) {
		$length = strlen( $number ) -5;
		$masked = sprintf( "%s%'".$filler.$length.'s%s', substr( $number, 0, 1 ), '', substr( $number, -4 ) );
		return $masked;
	}

	/**
	 * Payment fields
	 * @param  SI_Checkouts $checkout
	 * @return array
	 */
	protected function payment_billing_fields( SI_Checkouts $checkout ) {
		$billing_fields = self::get_standard_address_fields( true, get_current_user_id() );
		$billing_fields = apply_filters( 'si_payment_billing_fields', $billing_fields, __CLASS__, $checkout );
		uasort( $billing_fields, array( __CLASS__, 'sort_by_weight' ) );
		return $billing_fields;
	}

	/**
	 * Payment fields
	 * @param  SI_Checkouts $checkout
	 * @return array
	 */
	protected function payment_fields( $checkout = null ) {
		$fields = self::default_credit_fields( $checkout );
		foreach ( array_keys( $fields ) as $key ) {
			if ( isset( $this->cc_cache[ $key ] ) ) {
				$fields[ $key ]['default'] = $this->cc_cache[ $key ];
			}
		}
		$fields = apply_filters( 'sa_credit_fields', $fields, $checkout );
		uasort( $fields, array( get_class(), 'sort_by_weight' ) );
		return $fields;
	}

	/**
	 * Loaded via SI_Payment_Processors::show_payments_pane
	 * @param  SI_Checkouts $checkout
	 * @return
	 */
	public function payments_pane( SI_Checkouts $checkout ) {
		do_action( 'si_cc_payments_pane' );
		self::load_view( 'templates/checkout/credit-card/form', array(
				'billing_fields' => $this->payment_billing_fields( $checkout ),
				'cc_fields' => $this->payment_fields( $checkout ),
				'checkout' => $checkout,
		), true );
	}

	public function review_pane( SI_Checkouts $checkout ) {
		$cache = wp_parse_args($this->cc_cache, array(
			'cc_name' => '',
			'cc_number' => '',
			'cc_expiration_month' => '',
			'cc_expiration_year' => '',
			'cc_cvv' => '',
		));
		$cc_fields = array(
			'cc_name' => array(
				'label' => __( 'Cardholder', 'sprout-invoices' ),
				'value' => $cache['cc_name'],
				'weight' => 1,
			),
			'cc_number' => array(
				'label' => __( 'Card Number', 'sprout-invoices' ),
				'value' => $cache['cc_number']?self::mask_card_number( $cache['cc_number'] ):'',
				'weight' => 2,
			),
			'cc_expiration' => array(
				'label' => __( 'Expiration Date', 'sprout-invoices' ),
				'value' => $cache['cc_expiration_month'].'/'.$cache['cc_expiration_year'],
				'weight' => 3,
			),
			'cc_cvv' => array(
				'label' => __( 'CVV', 'sprout-invoices' ),
				'value' => $cache['cc_cvv'],
				'weight' => 4,
			),
		);
		$cc_fields = apply_filters( 'si_payment_review_cc_fields', $cc_fields, $checkout );
		uasort( $cc_fields, array( get_class(), 'sort_by_weight' ) );

		$bill_cache = wp_parse_args( $checkout->cache['billing'], array(
			'first_name' => '',
			'last_name' => '',
			'street' => '',
			'city' => '',
			'zone' => '',
			'postal_code' => '',
			'country' => '',
		));
		$bill_fields = array(
			'full_name' => array(
				'label' => __( 'Full Name', 'sprout-invoices' ),
				'value' => esc_attr( $bill_cache['first_name'] ) . ' ' . esc_attr( $bill_cache['last_name'] ),
				'weight' => 1,
			),
			'street' => array(
				'label' => __( 'Address', 'sprout-invoices' ),
				'value' => esc_attr( $bill_cache['street'] ),
				'weight' => 2,
			),
			'city_zone_postal' => array(
				'label' => '',
				'value' => esc_attr( $bill_cache['city'] ) . ', ' . esc_attr( $bill_cache['zone'] ) . ' ' . esc_attr( $bill_cache['postal_code'] ),
				'weight' => 3,
			),
			'country' => array(
				'label' => '',
				'value' => esc_attr( $bill_cache['country'] ),
				'weight' => 4,
			),
		);
		$bill_fields = apply_filters( 'si_payment_review_billing_fields', $bill_fields, $checkout );
		uasort( $bill_fields, array( get_class(), 'sort_by_weight' ) );

		self::load_view( 'templates/checkout/credit-card/review', array(
				'billing_fields' => $bill_fields,
				'cc_fields' => $cc_fields,
				'checkout' => $checkout,
		), true );
	}

	public function confirmation_pane( SI_Checkouts $checkout ) {
		self::load_view( 'templates/checkout/credit-card/confirmation', array(
				'checkout' => $checkout,
				'payment_id' => $checkout->get_payment_id(),
		), true );
	}

	/**
	 * Process the payment form
	 *
	 * @return bool
	 */
	public function validate_billing( SI_Checkouts $checkout ) {
		$valid = true;
		if ( apply_filters( 'si_valid_process_payment_page_fields', true ) ) {
			$fields = $this->payment_billing_fields( $checkout );
			foreach ( $fields as $key => $data ) {
				$checkout->cache['billing'][ $key ] = isset( $_POST[ 'sa_billing_'.$key ] )?$_POST[ 'sa_billing_'.$key ]:'';
				if ( isset( $data['required'] ) && $data['required'] && ! ( isset( $checkout->cache['billing'][ $key ] ) && $checkout->cache['billing'][ $key ] != '' ) ) {
					$valid = false;
					self::set_message( sprintf( __( '"%s" field is required.', 'sprout-invoices' ), $data['label'] ), self::MESSAGE_STATUS_ERROR );
				}
			}
		}
		$valid = apply_filters( 'si_validate_billing_cc', $valid, $checkout );
		if ( ! $valid ) {
			$this->invalidate_checkout( $checkout );
		}
		return $valid;
	}

	/**
	 * Validate if CC submission is valid.
	 * @param  array       $cc_data
	 * @param  SI_Checkouts $checkout [description]
	 * @return bool
	 */
	protected function validate_credit_card( $cc_data, SI_Checkouts $checkout ) {
		$valid = true;
		if ( apply_filters( 'si_valid_process_payment_page_fields', true ) ) {
			$cc_fields = $this->payment_fields( $checkout );
			foreach ( $cc_fields as $key => $data ) {
				if ( isset( $data['required'] ) && $data['required'] && ! ( isset( $cc_data[ $key ] ) && strlen( $cc_data[ $key ] ) > 0 ) ) {
					self::set_message( sprintf( __( '"%s" field is required.', 'sprout-invoices' ), $cc_fields[ $key ]['label'] ), self::MESSAGE_STATUS_ERROR );
					$valid = false;
				}
			}
			if ( isset( $cc_data['cc_number'] ) ) {
				if ( ! self::is_valid_credit_card( $cc_data['cc_number'] ) ) {
					self::set_message( __( 'Invalid credit card number', 'sprout-invoices' ), self::MESSAGE_STATUS_ERROR );
					$valid = false;
				}
			}

			if ( isset( $cc_data['cc_cvv'] ) ) {
				if ( ! self::is_valid_cvv( $cc_data['cc_cvv'] ) ) {
					self::set_message( __( 'Invalid credit card security code', 'sprout-invoices' ), self::MESSAGE_STATUS_ERROR );
					$valid = false;
				}
			}

			if ( ! empty( $fields['cc_expiration_year']['required'] ) && isset( $cc_data['cc_expiration_year'] ) ) {
				if ( self::is_expired( $cc_data['cc_expiration_year'], $cc_data['cc_expiration_month'] ) ) {
					self::set_message( __( 'Credit card is expired.', 'sprout-invoices' ), self::MESSAGE_STATUS_ERROR );
					$valid = false;
				}
			}
		}

		$valid = apply_filters( 'si_validate_credit_card_cc', $valid, $checkout );
		if ( ! $valid ) {
			$this->invalidate_checkout( $checkout );
		}
		return $valid;
	}

	/**
	 * Return the card type based on number
	 *
	 * @see http://en.wikipedia.org/wiki/Bank_card_number
	 *
	 * @static
	 * @param string  $number
	 * @return string
	 */
	public static function get_card_type( $cc_number ) {
		if ( preg_match( '/^(6334[5-9][0-9]|6767[0-9]{2})[0-9]{10}([0-9]{2,3}?)?$/', $cc_number ) ) {

			return 'Solo'; // is also a Maestro product

		} elseif ( preg_match( '/^(49369[8-9]|490303|6333[0-4][0-9]|6759[0-9]{2}|5[0678][0-9]{4}|6[0-9][02-9][02-9][0-9]{2})[0-9]{6,13}?$/', $cc_number ) ) {

			return 'Maestro';

		} elseif ( preg_match( '/^(49030[2-9]|49033[5-9]|4905[0-9]{2}|49110[1-2]|49117[4-9]|49918[0-2]|4936[0-9]{2}|564182|6333[0-4][0-9])[0-9]{10}([0-9]{2,3}?)?$/', $cc_number ) ) {

			return 'Maestro'; // SWITCH is now Maestro

		} elseif ( preg_match( '/^4[0-9]{12}([0-9]{3})?$/', $cc_number ) ) {

			return 'Visa';

		} elseif ( preg_match( '/^5[1-5][0-9]{14}$/', $cc_number ) ) {

			return 'MasterCard';

		} elseif ( preg_match( '/^3[47][0-9]{13}$/', $cc_number ) ) {

			return 'Amex';

		} elseif ( preg_match( '/^3(0[0-5]|[68][0-9])[0-9]{11}$/', $cc_number ) ) {

			return 'Diners';

		} elseif ( preg_match( '/^(6011[0-9]{12}|622[1-9][0-9]{12}|64[4-9][0-9]{13}|65[0-9]{14})$/', $cc_number ) ) {

			return 'Discover';

		} elseif ( preg_match( '/^(35(28|29|[3-8][0-9])[0-9]{12}|2131[0-9]{11}|1800[0-9]{11})$/', $cc_number ) ) {

			return 'JCB';

		} else {

			return 'Unknown';

		}
	}
}
