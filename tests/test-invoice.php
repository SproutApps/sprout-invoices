<?php

class Test_Invoices extends WP_UnitTestCase {
	protected $invoice_id;
	protected $invoice;

	function setUp() {
		parent::setUp();

		$args = array(
			'subject' => 'TEST'
		);
		$this->invoice_id = SI_Invoice::create_invoice( $args, SI_Invoice::STATUS_TEMP );
		$this->invoice = SI_Invoice::get_instance( $this->invoice_id );

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

	function test_set_deposit_with_high_value() {
		$total = $this->invoice->get_calculated_total();
		$dep = 5000000000000000;
		$this->invoice->set_deposit( $dep );
		$this->assertEquals( $this->invoice->get_deposit(), $total );
	}
}

