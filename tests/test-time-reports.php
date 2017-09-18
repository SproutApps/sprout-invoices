<?php

class Test_Reporting extends WP_UnitTestCase {
	protected $invoice_ids = array();
	protected $payment_ids = array();

	function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();

		// flush
		foreach ( array_merge( $this->invoice_ids, $this->payment_ids ) as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	function build_test_invoice( $total = 0 ) {
		$user_args = array(
			'user_login' => 'unit-tests@sproutapps.co',
			'display_name' => 'Unit Tester',
			'user_pass' => wp_generate_password(), // random password
			'user_email' => 'unit-tests@sproutapps.co',
		);
		$user_id = SI_Clients::create_user( $user_args );

		$args = array(
			'company_name' => 'Test Client',
			'user_id' => $user_id,
		);
		$client_id = SI_Client::new_client( $args );

		$args = array(
			'subject' => 'TESTING Payments',
		);
		$id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_TEMP );
		$this->invoice_ids[] = $id;
		$invoice = SI_Invoice::get_instance( $id );
		$invoice->set_client_id( $client_id );

		// No total set make them random
		if ( ! $total ) {
			$line_items = array();
			for ( $i = 0; $i < 10; $i++ ) {
				$rate = rand( 1000, 4000 );
				$qty = rand( 1, 10 );
				$line_items[] = array(
					'rate' => $rate,
					'qty' => $qty,
					'desc' => 'This is a test line item for a test invoice.',
					'type' => '',
					'total' => $rate * $qty,
					'tax' => 0,
					);
			}
		} else {
			$line_items = array( array(
				'rate' => $total,
				'qty' => 1,
				'desc' => 'This is a test line item for a test invoice.',
				'type' => '',
				'total' => $total,
				'tax' => 0,
				),
			);
		}

		$invoice->set_line_items( $line_items );

		$this->assertTrue( in_array( $id, $this->invoice_ids ) );
		return $id;
	}

	function build_test_payment( $invoice_id, $total = 0, $date = false, $status = '' ) {
		$invoice = SI_Invoice::get_instance( $invoice_id );
		// Randomize if no total is set.
		$payment_total = ( ! $total ) ? rand( 1, 300 ) : $total ;
		$status = ( $status != '' ) ? $status : SI_Payment::STATUS_AUTHORIZED ;
		// create new payment
		$payment_id = SI_Payment::new_payment( array(
			'payment_method' => SI_Paypal_EC::PAYMENT_METHOD,
			'invoice' => $invoice->get_id(),
			'amount' => $payment_total,
			'data' => array(
			'api_response' => array(),
			),
		), $status );

		$this->payment_ids[] = $payment_id;

		$new_payment = SI_Payment::get_instance( $payment_id );
		do_action( 'payment_authorized', $new_payment );

		if ( ! $date ) {
			$date = time();
		}

		if ( ! is_integer( $date ) ) {
			$date = strtotime( $date );
		}

		$new_payment->set_post_date( date( 'Y-m-d H:i:s', $date ) );

		$this->assertTrue( in_array( $payment_id, $this->payment_ids ) );
		return $payment_id;
	}

	function test_compare_basics_of_total_invoice_data_and_total_payment_data() {
		// Build invoices and payments to test against.
		for ( $i = 0; $i < 10; $i++ ) {
			$id = $this->build_test_invoice();
			$this->build_test_payment( $id );
		}

		$invoice_data = SI_Reporting::total_invoice_data();
		$payment_data = SI_Reporting::total_payment_data();

		// Paid vs. Payment Totals
		$this->assertEquals( $invoice_data['paid'], $payment_data['totals'] );

		// Payment count vs. Payment Count
		$this->assertEquals( $invoice_data['payments'], $payment_data['payments'] );

	}

	function test_total_invoice_data_balances() {
		$invoice_totaled = 0;
		$payment_totaled = 0;
		$invoice_totals = array( 1000, 1000, 1000, 1000, 1000, 2000 );
		$payment_totals = array( 500, 200, 400, 50, 700, 100.75 );

		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $payment_totals[ $key ] );

			$invoice_totaled += (float) $total;
			$payment_totaled += (float) $payment_totals[ $key ];
		}

		// Build some invoices with voided payments
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $total, false, SI_Payment::STATUS_VOID );
			// tally the invoice balances
			$invoice_totaled += (float) $total;
		}

		$invoice_data = SI_Reporting::total_invoice_data();

		// Total balance should equal $invoice_totals-$payment_totals
		$this->assertEquals( $invoice_data['balance'], $invoice_totaled -$payment_totaled );

	}

	function test_last_week_paid() {
		$invoice_totaled = 0;
		$payment_totaled = 0;
		$context_totaled = 0;
		$invoice_totals = array( 1000, 1000, 1000, 1000, 1000, 2000 );
		$payment_totals = array( 500, 200, 400, 50, 700, 100.75 ); // 1950.75

		// Build invoices and payments from last week
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $payment_totals[ $key ], time() -(DAY_IN_SECONDS * 7) );

			$invoice_totaled += (float) $total;
			$payment_totaled += (float) $payment_totals[ $key ];
			$context_totaled += (float) $payment_totals[ $key ];
		}

		// Build some invoices with voided payments
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $total, false, SI_Payment::STATUS_VOID );
			// tally the invoice balances
			$invoice_totaled += (float) $total;
		}

		// Build some invoices with old payments
		// Prevents payments with same month/week to be shown in results
		// https://secure.helpscout.net/conversation/56532773/171/
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $payment_totals[ $key ], strtotime( 'last year' ) );
			// tally the invoice balances
			$invoice_totaled += (float) $total;
			$payment_totaled += (float) $payment_totals[ $key ];
		}

		// Build some invoices with old payments
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $total, strtotime( 'last year' ) );
			// tally the invoice balances
			$invoice_totaled += (float) $total;
			$payment_totaled += (float) $payment_totals[ $key ];
		}

		// check payment totals
		$payment_data = SI_Reporting::total_payment_data( 'lastweek' );

		// Totals should equal last weeks payments
		$this->assertEquals( $payment_data['totals'], $context_totaled );
	}

	function test_last_month_paid() {
		$invoice_totaled = 0;
		$payment_totaled = 0;
		$context_totaled = 0;
		$context2_totaled = 0;
		$invoice_totals = array( 1000, 1000, 1000, 1000, 1000, 2000 );
		$payment_totals = array( 500, 200, 400, 50, 700, 100.75 ); // 1950.75

		// time is last month.
		$min_epoch = strtotime( 'first day of previous month' );
		$max_epoch = strtotime( 'last day of previous month' );

		// Build invoices and payments from last month
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );

			$rand_time = rand( $min_epoch, $max_epoch );
			$this->build_test_payment( $id, $payment_totals[ $key ], $rand_time );

			$invoice_totaled += (float) $total;
			$payment_totaled += (float) $payment_totals[ $key ];
			$context_totaled += (float) $payment_totals[ $key ];
		}

		// time not last month.
		$min_epoch = strtotime( '2009-01-01' );
		$max_epoch = strtotime( '2014-10-31' );

		// Random invoices and payments
		for ( $i = 0; $i < 10; $i++ ) {
			$invoice_total = rand( 10000, 100000 );
			$payment_total = rand( 100, 10000 );

			$rand_time = rand( $min_epoch, $max_epoch );

			$id = $this->build_test_invoice( $invoice_total );
			$this->build_test_payment( $id, $payment_total, $rand_time );

			// tally the invoice balances
			$invoice_totaled += (float) $invoice_total;
			$payment_totaled += (float) $payment_totals[ $i ];
		}

		// Build some invoices with voided payments
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $total, false, SI_Payment::STATUS_VOID );
			// tally the invoice balances
			$invoice_totaled += (float) $total;
		}

		// Build some invoices with old payments
		// Prevents payments with same month/week to be shown in results
		// https://secure.helpscout.net/conversation/56532773/171/
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $payment_totals[ $key ], strtotime( 'last year' ) );
			// tally the invoice balances
			$invoice_totaled += (float) $total;
			$payment_totaled += (float) $payment_totals[ $key ];
		}

		// Build some invoices with payments made this month
		$min_epoch = strtotime( 'first day of this month' );
		$max_epoch = strtotime( 'last day of this month' );

		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$rand_time = rand( $min_epoch, $max_epoch );
			$this->build_test_payment( $id, $payment_totals[ $key ], $rand_time );
			// tally the invoice balances
			$invoice_totaled += (float) $total;
			$payment_totaled += (float) $payment_totals[ $key ];
			$context2_totaled += (float) $payment_totals[ $key ];
		}

		// check payment totals
		$payment_data = SI_Reporting::total_payment_data( 'lastmonth' );

		// Totals should equal last months payments
		$this->assertEquals( $payment_data['totals'], $context_totaled );

		// check payment totals
		$payment_data = SI_Reporting::total_payment_data( 'month' );

		// Total should equal all payments, except those that were created for last month.
		$this->assertEquals( $payment_data['totals'], $context2_totaled );
	}

	function test_last_year_paid() {
		$invoice_totaled = 0;
		$payment_totaled = 0;
		$context_totaled = 0;
		$context2_totaled = 0;
		$invoice_totals = array( 1000, 1000, 1000, 1000, 1000, 2000 );
		$payment_totals = array( 500, 200, 400, 50, 700, 100.75 ); // 1950.75

		// Build invoices and payments from last month
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $payment_totals[ $key ], strtotime( '-1 year' ) );

			$invoice_totaled += (float) $total;
			$payment_totaled += (float) $payment_totals[ $key ];
			$context_totaled += (float) $payment_totals[ $key ];
		}

		// time not last year.
		$min_epoch = strtotime( '2009-01-01' );
		$max_epoch = strtotime( '2012-12-21' );

		// Random invoices and payments
		for ( $i = 0; $i < 10; $i++ ) {
			$invoice_total = rand( 10000, 100000 );
			$payment_total = rand( 100, 10000 );

			$rand_time = rand( $min_epoch, $max_epoch );

			$id = $this->build_test_invoice( $invoice_total );
			$this->build_test_payment( $id, $payment_total, $rand_time );

			// tally the invoice balances
			$invoice_totaled += (float) $invoice_total;
			$payment_totaled += (float) $payment_total;
		}

		// Build some invoices with voided payments
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );

			$rand_time = rand( $min_epoch, $max_epoch );

			$this->build_test_payment( $id, $rand_time, false, SI_Payment::STATUS_VOID );
			// tally the invoice balances
			$invoice_totaled += (float) $total;
		}

		// Build some invoices with current payments
		foreach ( $invoice_totals as $key => $total ) {
			$id = $this->build_test_invoice( $total );
			$this->build_test_payment( $id, $payment_totals[ $key ] );
			// tally the invoice balances
			$invoice_totaled += (float) $total;
			$payment_totaled += (float) $payment_totals[ $key ];
			$context2_totaled += (float) $payment_totals[ $key ];
		}

		// check payment totals
		$payment_data = SI_Reporting::total_payment_data( 'lastyear' );

		// Totals should equal last months payments
		$this->assertEquals( $payment_data['totals'], $context_totaled );

		// check payment totals
		$payment_data = SI_Reporting::total_payment_data( 'year' );

		// Total should equal all payments, except those that were created for last month.
		$this->assertEquals( $payment_data['totals'], $context2_totaled );
	}
}






























