<?php

/**
 * Disabled
 * Basic JSON implementation at the moment for reporting.
 * TODO: Hook into new WP API.
 *
 * @package Sprout_Invoice
 * @subpackage API
 */
class SI_JSON_API extends SI_Controller {


	///////////////
	// Endpoints //
	///////////////

	/**
	 * Ping
	 *
	 */
	private static function ping() {
		return array(
			'status' => 'verified',
		);
	}

	public static function create_invoice( $data = array() ) {
		$invoice_id = SI_Invoice::create_invoice( $data );
		return self::invoice_data( $invoice_id );
	}

	public static function create_estimate( $data = array() ) {
		$estimate_id = SI_Estimate::create_estimate( $data );
		return self::estimate_data( $estimate_id );
	}

	public static function create_payment( $data = array() ) {
		$payment_id = SI_Payment::new_payment( $data );
		return self::payment_data( $payment_id );
	}

	public static function create_client( $data = array() ) {
		$client_id = SI_Client::new_client( $data );
		return self::client_data( $client_id );
	}

	public static function invoice( $data = array() ) {
		if ( ! isset( $data['id'] ) ) {
			$data['id'] = $_GET['id'];
		}
		$invoice = SI_Invoice::get_instance( $data['id'] );
		if ( ! is_a( $invoice, 'SI_Invoice' ) ) {
			return;
		}
		return self::invoice_data( $invoice );
	}

	public static function estimate( $data = array() ) {
		if ( ! isset( $data['id'] ) ) {
			$data['id'] = $_GET['id'];
		}
		$estimate = SI_Estimate::get_instance( $data['id'] );
		if ( ! is_a( $estimate, 'SI_Estimate' ) ) {
			return;
		}
		return self::estimate_data( $estimate );
	}

	public static function payment( $data = array() ) {
		if ( ! isset( $data['id'] ) ) {
			$data['id'] = $_GET['id'];
		}
		$payment = SI_Payment::get_instance( $data['id'] );
		if ( ! is_a( $payment, 'SI_Payment' ) ) {
			return;
		}
		return self::payment_data( $payment );
	}

	public static function client( $data = array() ) {
		if ( ! isset( $data['id'] ) ) {
			$data['id'] = $_GET['id'];
		}
		$client = SI_Client::get_instance( $data['id'] );
		if ( ! is_a( $client, 'SI_Client' ) ) {
			return;
		}
		return self::client_data( $client );
	}

	///////////
	// Data //
	///////////

	public static function estimate_data( SI_Estimate $estimate ) {
		$estimate_data = array(
			'title' => $estimate->get_title(),
			'id' => $estimate->get_id(),
			'estimate_id' => $estimate->get_estimate_id(),
			'invoice_id' => $estimate->get_invoice_id(),
			'client_id' => $estimate->get_client_id(),
			'client_data' => array(),
			'status' => $estimate->get_status(),
			'issue_date' => $estimate->get_issue_date(),
			'expiration_date' => $estimate->get_expiration_date(),
			'po_number' => $estimate->get_po_number(),
			'discount' => $estimate->get_discount(),
			'tax' => $estimate->get_tax(),
			'tax2' => $estimate->get_tax2(),
			'currency' => $estimate->get_currency(),
			'total' => $estimate->get_total(),
			'subtotal' => $estimate->get_subtotal(),
			'calculated_total' => $estimate->get_calculated_total(),
			'project_id' => $estimate->get_project_id(),
			'terms' => $estimate->get_terms(),
			'notes' => $estimate->get_notes(),
			'line_items' => $estimate->get_line_items(),
			'user_id' => $estimate->get_user_id(),
			);
		if ( $estimate->get_client_id() ) {
			$client = SI_Client::get_instance( $estimate->get_client_id() );
			if ( is_a( $client, 'SI_Client' ) ) {
				$estimate_data['client_data'] = self::client_data( $client );
			}
		}
		return $estimate_data;
	}

	public static function invoice_data( SI_Invoice $invoice ) {
		$invoice_data = array(
			'title' => $invoice->get_title(),
			'id' => $invoice->get_id(),
			'invoice_id' => $invoice->get_invoice_id(),
			'status' => $invoice->get_status(),
			'balance' => $invoice->get_balance(),
			'deposit' => $invoice->get_deposit(),
			'issue_date' => $invoice->get_issue_date(),
			'estimate_id' => $invoice->get_estimate_id(),
			'due_date' => $invoice->get_due_date(),
			'expiration_date' => $invoice->get_expiration_date(),
			'client_id' => $invoice->get_client_id(),
			'client_data' => array(),
			'po_number' => $invoice->get_po_number(),
			'discount' => $invoice->get_discount(),
			'tax' => $invoice->get_tax(),
			'tax2' => $invoice->get_tax2(),
			'currency' => $invoice->get_currency(),
			'subtotal' => $invoice->get_subtotal(),
			'calculated_total' => $invoice->get_calculated_total(),
			'project_id' => $invoice->get_project_id(),
			'terms' => $invoice->get_terms(),
			'notes' => $invoice->get_notes(),
			'line_items' => $invoice->get_line_items(),
			'user_id' => $invoice->get_user_id(),
			'payment_ids' => $invoice->get_payments(),
			);
		if ( $invoice->get_client_id() ) {
			$client = SI_Client::get_instance( $invoice->get_client_id() );
			if ( is_a( $client, 'SI_Client' ) ) {
				$invoice_data['client_data'] = self::client_data( $client );
			}
		}
		return $invoice_data;
	}

	public static function payment_data( SI_Payment $payment ) {
		$payment_data = array(
			'title' => $payment->get_title(),
			'id' => $payment->get_id(),
			'status' => $payment->get_status(),
			'payment_method' => $payment->get_payment_method(),
			'amount' => $payment->get_amount(),
			'invoice_id' => $payment->get_invoice_id(),
			'data' => $payment->get_data(),
			);
		$invoice = SI_Invoice::get_instance( $payment->get_invoice_id() );
		if ( is_a( $invoice, 'SI_Invoice' ) ) {
			$payment_data['invoice_data'] = self::invoice_data( $invoice );
		}
		return $payment_data;
	}

	public static function client_data( SI_Client $client ) {
		$emails = array();
		$associated_users = $client->get_associated_users();
		if ( ! empty( $associated_users ) ) {
			foreach ( $associated_users as $user_id ) {
				$user = get_userdata( $user_id );
				if ( $user ) {
					$emails[] = $user->user_email;
				}
			}
		}
		$client_data = array(
			'company_name' => $client->get_title(),
			'address' => $client->get_address(),
			'user_ids' => $associated_users,
			'user_emails' => $emails,
			'phone' => $client->get_phone(),
			'website' => $client->get_website(),
			'estimate_ids' => $client->get_invoices(),
			'invoice_ids' => $client->get_estimates(),
			'payment_ids' => $client->get_payments(),
			);
		return $client_data;
	}

	public static function project_data( SI_Project $project ) {
		$project_data = array();
		return $project_data;
	}
}
