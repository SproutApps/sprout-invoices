<?php

class Test_Payments extends WP_UnitTestCase {
	protected $invoice_id;
	protected $invoice;

	function setUp() {
		parent::setUp();

		$user_args = array(
			'user_login' => 'unit-tests@sproutapps.co',
			'display_name' => 'Unit Tester',
			'user_pass' => wp_generate_password(), // random password
			'user_email' => 'unit-tests@sproutapps.co',
		);
		$user_id = SI_Clients::create_user( $user_args );

		$args = array(
			'company_name' => 'Test Client',
			'user_id' => $user_id
		);
		$client_id = SI_Client::new_client( $args );

		$args = array(
			'subject' => 'TEST Payment'
		);
		$this->invoice_id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_TEMP );
		$this->invoice = SI_Invoice::get_instance( $this->invoice_id );
		$this->invoice->set_client_id( $client_id );

		$line_items = array();
		for ($i=0; $i < 10; $i++) { 
			$rate = rand( 100, 1000 );
			$qty = rand( 1, 10 );
			$line_items[] = array( 
				'rate' => $rate,
				'qty' => $qty,
				'desc' => 'This is a test line item for a test invoice.',
				'type' => '',
				'total' => $rate*$qty,
				'tax' => 0,
				);
		}
		$this->invoice->set_line_items( $line_items );
	}

	function test_deposit_conversion() {
		// Copy of Stripe
		$deposit = '1,001230.01';
		// strip out commas
		$value = preg_replace( "/\,/i", "", $deposit );
		// strip out all but numbers, dash, and dot
		$value = preg_replace( "/([^0-9\.\-])/i", "", $value );
		// make sure we are dealing with a proper number now, no +.4393 or 3...304 or 76.5895,94
		if ( !is_numeric( $value ) ) {
			$this->assertEquals( false, true );
		}
		// convert to a float explicitly
		$value = (float)$value;
		$converted = round( $value, 2 )*100;

		$this->assertEquals( 100123001, $converted );
	}

	function test_add_payment_deposit() {
		$total = $this->invoice->get_calculated_total();
		$deposit = si_get_number_format( $total/3 ); // pay 1/3
		$this->invoice->set_deposit( $deposit );

		$payment_total = $this->invoice->get_deposit();
		// create new payment
		$payment_id = SI_Payment::new_payment( array(
				'payment_method' => SI_Paypal_EC::PAYMENT_METHOD,
				'invoice' => $this->invoice->get_id(),
				'amount' => $payment_total,
				'data' => array(
					'api_response' => array()
				),
			), SI_Payment::STATUS_AUTHORIZED );
		if ( !$payment_id ) {
			return false;
		}
		$payment = SI_Payment::get_instance( $payment_id );
		do_action( 'payment_authorized', $payment );
		
		// Complete and fire actions
		$payment->set_status( SI_Payment::STATUS_COMPLETE );
		do_action( 'payment_complete', $payment );

		$this->assertEquals( $this->invoice->get_balance(), $total-$deposit );
	}

	function test_add_payment() {
		$payment_total = $this->invoice->get_balance();
		// create new payment
		$payment_id = SI_Payment::new_payment( array(
				'payment_method' => SI_Paypal_EC::PAYMENT_METHOD,
				'invoice' => $this->invoice->get_id(),
				'amount' => $payment_total,
				'data' => array(
					'api_response' => array()
				),
			), SI_Payment::STATUS_AUTHORIZED );
		if ( !$payment_id ) {
			return false;
		}
		$payment = SI_Payment::get_instance( $payment_id );
		do_action( 'payment_authorized', $payment );
		
		// Complete and fire actions
		$payment->set_status( SI_Payment::STATUS_COMPLETE );
		do_action( 'payment_complete', $payment );


		$this->assertEquals( $this->invoice->get_balance(), 0 );
	}
}

