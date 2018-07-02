<?php

/**
 * Sprout Invoices Payment Model
 *
 * Payment object for payment processors.
 *
 * @package Sprout_Invoices
 * @subpackage Payment
 */
class SI_Payment extends SI_Post_Type {
	const POST_TYPE = 'sa_payment';
	const STATUS_PENDING = 'pending'; // payment has been created for later authorization
	const STATUS_AUTHORIZED = 'authorized'; // payment has been authorized, but not yet captured
	const STATUS_COMPLETE = 'publish'; // payment has been authorized and fully captured
	const STATUS_PARTIAL = 'payment-partial'; // payment has been authorized and partially captured
	const STATUS_VOID = 'void'; // payment has been voided
	const STATUS_REFUND = 'refunded'; // payment has been voided

	const STATUS_RECURRING = 'recurring'; // a recurring payment has been created and is ongoing
	const STATUS_CANCELLED = 'cancelled'; // a recurring payment has been cancelled
	private static $instances = array();

	protected static $meta_keys = array(
		'amount' => '_amount', // int|float
		'data' => '_payment_data', // array - Misc. data saved by the payment processor
		'invoice' => '_payment_invoice', // array - Info about which invoice this pays for, and how much of each
		'payment_method' => '_payment_method', // string
		'purchase_id' => '_purchase_id', // int
		'shipping_address' => '_shipping_address', // array - Address
		'source' => '_source', // int|float Another tracking method, used for affiliate.
		'transaction_id' => '_trans_id', // int for the payment gateway's transaction id.
		'tracking' => '_tracking', // array - Misc info for later tracking.
		'type' => '_type', // standard, deposit, or term
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.

	public static function init() {
		$post_type_args = array(
			'public' => false,
			'show_ui' => true,
			//'show_in_menu' => 'edit.php?post_type='.SI_Invoice::POST_TYPE,
			'rewrite' => false,
			'has_archive' => false,
			'supports' => array( 'title' ),
		);
		self::register_post_type( self::POST_TYPE, 'Payment', 'Payments', $post_type_args );
		self::register_post_statuses();
	}

	/**
	 * Post statuses for payments
	 * @return
	 */
	private static function register_post_statuses() {
		$statuses = array(
			self::STATUS_AUTHORIZED => __( 'Authorized', 'sprout-invoices' ),
			self::STATUS_CANCELLED => __( 'Cancelled', 'sprout-invoices' ),
			self::STATUS_PARTIAL => __( 'Partial Payment', 'sprout-invoices' ),
			self::STATUS_VOID => __( 'Void', 'sprout-invoices' ),
			self::STATUS_REFUND => __( 'Refunded', 'sprout-invoices' ),
			self::STATUS_RECURRING => __( 'Recurring', 'sprout-invoices' ),
		);
		foreach ( $statuses as $status => $label ) {
			register_post_status( $status, array(
				'label' => $label,
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
		  		'show_in_admin_status_list' => true,
		  		'label_count' => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' ),
			));
		}
	}

	public function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return SI_Payment
	 */
	public static function get_instance( $id = 0 ) {
		if ( ! $id ) {
			return null; }

		if ( ! isset( self::$instances[ $id ] ) || ! self::$instances[ $id ] instanceof self ) {
			self::$instances[ $id ] = new self( $id ); }

		if ( ! isset( self::$instances[ $id ]->post->post_type ) ) {
			return null; }

		if ( self::$instances[ $id ]->post->post_type != self::POST_TYPE ) {
			return null; }

		return self::$instances[ $id ];
	}

	public static function new_payment( $passed_args, $status = self::STATUS_COMPLETE ) {
		$defaults = array(
			'transaction_id' => microtime(),
			'status' => $status,
			'payment_method' => __( 'API', 'sprout-invoices' ),
			'amount' => (float) 0,
			'invoice_id' => 0,
			'invoice' => 0,
			'data' => array(),
			'type' => 'standard',
		);
		$args = wp_parse_args( $passed_args, $defaults );

		$id = wp_insert_post( array(
			'post_title' => sprintf( __( 'Payment #%d', 'sprout-invoices' ), $args['transaction_id'] ),
			'post_status' => $args['status'],
			'post_type' => self::POST_TYPE,
		) );
		if ( is_wp_error( $id ) ) {
			return 0;
		}

		$payment = self::get_instance( $id );

		$payment->set_title( sprintf( __( 'Payment #%d', 'sprout-invoices' ), $id ) );
		$payment->set_transaction_id( $args['transaction_id'] );
		$payment->set_payment_method( $args['payment_method'] );
		$payment->set_amount( $args['amount'] );
		$payment->set_status( $args['status'] );
		$payment->set_data( $args['data'] );

		if ( $args['invoice'] ) {
			if ( is_a( $args['invoice'], 'SI_Invoice' ) ) {
				$args['invoice'] = $args['invoice']->get_id();
			}
			$payment->set_invoice_id( $args['invoice'] );
		} elseif ( $args['invoice_id'] ) {
			$payment->set_invoice_id( $args['invoice_id'] );
		} else {
			do_action( 'si_error', 'Payment created without an invoice associated!', $args );
		}

		do_action( 'si_new_payment', $payment, $args );
		return $id;
	}

	/**
	 * Find all Payments associated with a specific purchase
	 *
	 * @static
	 * @param int     $purchase_id ID of the purchase to search by
	 * @return array List of IDs of Payments associated with the given purchase
	 */
	public static function get_payments_for_purchase( $purchase_id ) {
		$payment_ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['purchase_id'] => $purchase_id ) );
		return $payment_ids;
	}

	public static function get_pending_payments( $method = null, $suppress_filters = true ) {
		$args = array(
			'post_type' => self::POST_TYPE,
			'post_status' => array( self::STATUS_PARTIAL, self::STATUS_AUTHORIZED ),
			'posts_per_page' => -1,
			'fields' => 'ids',
			'sa_bypass_filter' => true,
			'suppress_filters' => $suppress_filters,
		);
		if ( $method ) {
			$args['meta_query'] = array(
				array(
					'key' => self::$meta_keys['payment_method'],
					'value' => $method,
				),
			);
		}
		$posts = get_posts( $args );
		return $posts;
	}

	public function get_status() {
		return $this->post->post_status;
	}

	public function set_status( $status ) {
		$this->post->post_status = $status;
		$this->save_post();
		do_action( 'si_payment_status_updated', $this, $status );
	}

	public function set_purchase( $purchase_id ) {
		$this->save_post_meta( array(
			self::$meta_keys['purchase_id'] => $purchase_id,
		) );
	}

	public function get_purchase() {
		return $this->get_post_meta( self::$meta_keys['purchase_id'] );
	}

	public function set_payment_method( $method ) {
		$this->save_post_meta( array(
			self::$meta_keys['payment_method'] => $method,
		) );
	}

	public function get_payment_method() {
		return $this->get_post_meta( self::$meta_keys['payment_method'] );
	}

	public function set_amount( $amount ) {
		$this->save_post_meta( array(
			self::$meta_keys['amount'] => sa_get_unformatted_money( $amount ),
		) );
	}

	public function get_amount() {
		return $this->get_post_meta( self::$meta_keys['amount'] );
	}

	public function set_invoice_id( $invoice ) {
		$this->save_post_meta( array(
			self::$meta_keys['invoice'] => $invoice,
		) );
	}

	public function get_invoice_id() {
		return $this->get_post_meta( self::$meta_keys['invoice'] );
	}

	public function set_data( $data ) {
		if ( ! is_array( $data ) ) {
			$data = array( $data );
		}
		$this->save_post_meta( array(
			self::$meta_keys['data'] => $data,
		) );
	}

	public function get_data() {
		return $this->get_post_meta( self::$meta_keys['data'] );
	}

	public function set_source( $source ) {
		$this->save_post_meta( array(
			self::$meta_keys['source'] => $source,
		) );
	}

	public function get_source() {
		return $this->get_post_meta( self::$meta_keys['source'] );
	}

	public function set_transaction_id( $trans_id ) {
		$this->save_post_meta( array(
			self::$meta_keys['transaction_id'] => $trans_id,
		) );
	}

	public function get_transaction_id() {
		return $this->get_post_meta( self::$meta_keys['transaction_id'] );
	}

	public function set_shipping_address( $shipping_address ) {
		$this->save_post_meta( array(
			self::$meta_keys['shipping_address'] => $shipping_address,
		) );
	}

	public function get_shipping_address() {
		return $this->get_post_meta( self::$meta_keys['shipping_address'] );
	}

	public function set_tracking( $tracking ) {
		if ( ! is_array( $tracking ) ) {
			$tracking = array( $tracking );
		}
		$this->save_post_meta( array(
			self::$meta_keys['tracking'] => $tracking,
		) );
	}

	public function get_tracking() {
		return $this->get_post_meta( self::$meta_keys['tracking'] );
	}

	public function get_type() {
		return $this->get_post_meta( self::$meta_keys['type'] );
	}

	public function set_type( $type ) {
		if ( ! is_array( $type ) ) {
			$type = array( $type );
		}
		$this->save_post_meta( array(
			self::$meta_keys['type'] => $type,
		) );
	}

	public function get_client() {
		$invoice_id = $this->get_invoice_id();
		if ( ! $invoice_id ) {
			return null;
		}
		$invoice = SI_Invoice::get_instance( $invoice_id );
		if ( ! is_a( $invoice, 'SI_Invoice' ) ) {
			return null;
		}
		return $invoice->get_client();
	}

	public function is_recurring() {
		if ( in_array( $this->get_status(), array( self::STATUS_RECURRING, self::STATUS_CANCELLED ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if a recurring payment is still active and in good standing
	 *
	 * @param bool    $refresh Whether to re-verify with the payment processor
	 * @return bool
	 */
	public function is_active( $refresh = false ) {
		if ( ! $this->is_recurring() ) {
			return false; // non-recurring payments are never active
		}
		if ( $this->get_status() == self::STATUS_CANCELLED ) {
			return false;
		}
		if ( $refresh ) {
			$payment_processor = SI_Payment_Processors::get_payment_processor();
			$payment_processor->verify_recurring_payment( $this );
		}
		if ( $this->get_status() == self::STATUS_RECURRING ) {
			return true;
		}
		return false;
	}

	/**
	 * Cancel a recurring payment
	 *
	 * @return void
	 */
	public function cancel() {
		if ( $this->get_status() == self::STATUS_RECURRING ) {
			do_action( 'sa_cancelling_recurring_payment', $this );

			// cancel the actual payment
			$payment_processor = SI_Payment_Processors::get_payment_processor();
			$payment_processor->cancel_recurring_payment( $this );

			$this->set_status( self::STATUS_CANCELLED );

			// notify plugins that this has been cancelled
			$purchase_id = $this->get_purchase();
			do_action( 'sa_recurring_payment_cancelled', $this, $purchase_id );
		}
	}


	/**
	 * Get a list of Purchase IDs, filtered by $args
	 *
	 * @static
	 * @param array   $args
	 *  - invoices - limit to purchases that include this invoice ID
	 *  - user - limit to purchases by this user ID
	 *  - client - limit to purchases by this client ID (ignored if user ID is also given)
	 * @param array   $meta Default and allow for direct query
	 * @return array The IDs of all purchases meeting the criteria
	 */
	public static function get_payments( $args = array(), $meta = array() ) {
		if ( isset( $args['invoices'] ) ) {
			if ( is_array( $args['invoices'] ) ) {
				$payment_ids = array();
				foreach ( $args['invoices'] as $invoice_id ) {
					$meta[ self::$meta_keys['invoice'] ] = $invoice_id;
					$payment_ids = array_merge( $payment_ids, self::find_by_meta( self::POST_TYPE, $meta ) );
				}
				return $payment_ids; // End early since we're returning an array.
			} else {
				$meta[ self::$meta_keys['invoice'] ] = $args['invoices'];
			}
		}
		$payment_ids = self::find_by_meta( self::POST_TYPE, $meta );
		return $payment_ids;
	}
}
