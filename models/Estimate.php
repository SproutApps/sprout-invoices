<?php

/**
 * Estimate Model
 * 
 *
 * @package Sprout_Invoices
 * @subpackage Estimate
 */
class SI_Estimate extends SI_Post_Type {

	const POST_TYPE = 'sa_estimate';
	const PROJECT_TAXONOMY = 'si_project_types';
	const LINE_ITEM_TAXONOMY = 'si_line_item_types'; // deprecated, todo remove
	const REWRITE_SLUG = 'sprout-estimate';

	const STATUS_TEMP = 'temp'; // estimate is in a draft state, can't use 'draft' otherwise a url will not be created
	const STATUS_REQUEST = 'request'; // estimate hasn't been approved or declined
	const STATUS_PENDING = 'publish'; // estimate hasn't been approved or declined
	const STATUS_FUTURE = 'future'; // invoice pending payment
	const STATUS_APPROVED = 'approved'; // estimate was approved by client
	const STATUS_DECLINED = 'declined'; // estimate was declined by client

	private static $instances = array();

	// meta fields with a prefixed key of _doc are transferable (when cloned) to invoices
	private static $meta_keys = array(
		'client_id' => '_client_id', // int
		'currency' => '_doc_currency', // string
		'discount' => '_doc_discount', // int
		'estimate_id' => '_estimate_id', // int
		'expiration_date' => '_expiration_date', // int
		'issue_date' => '_estimate_issue_date', // int
		'line_items' => '_doc_line_items', // array
		'notes' => '_estimate_notes', // string
		'po' => '_doc_po_number', // string
		'private_notes' => '_estimate_private_notes', // string
		'project_id' => '_project_id', // int
		'send_notes' => '_estimate_send_notes', // string
		'shipping' => '_doc_shipping', // int
		'submission' => '_submitted_items', // array
		'tax' => '_doc_tax', // int
		'tax2' => '_doc_tax2', // int
		'total' => '_total', // int
		'terms' => '_doc_terms', // string
		'user_id' => '_user_id', // int
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.


	public static function init() {
		// register Estimate post type
		$post_type_args = array(
			'public' => TRUE,
			'exclude_from_search' => TRUE,
			'has_archive' => FALSE,
			'show_in_menu' => TRUE,
			'show_in_nav_menus' => FALSE,
			'rewrite' => array(
				'slug' => self::REWRITE_SLUG,
				'with_front' => FALSE,
			),
			'supports' => array( '' ),
			'show_in_nav_menus' => FALSE
		);
		self::register_post_type( self::POST_TYPE, 'Estimate', 'Estimates', $post_type_args );

		// register category taxonomy
		// TODO remove since it's now deprecated
		$singular = 'Task';
		$plural = 'Tasks';
		$taxonomy_args = array(
			'meta_box_cb' => FALSE,
			'hierarchical' => false
		);
		self::register_taxonomy( self::LINE_ITEM_TAXONOMY, array(), $singular, $plural, $taxonomy_args );
		
		self::register_post_statuses();
	}

	public static function get_statuses() {
		$statuses = array(
			self::STATUS_TEMP => self::__('Draft'),
			self::STATUS_REQUEST => self::__('Request'),
			self::STATUS_PENDING => self::__('Pending'),
			self::STATUS_FUTURE => self::__('Scheduled'),
			self::STATUS_APPROVED => self::__('Approved'),
			self::STATUS_DECLINED => self::__('Declined'),
		);
		return $statuses;
	}

	/**
	 * Post statuses for payments
	 * @return  
	 */
	private static function register_post_statuses() {
		$statuses = self::get_statuses();
		foreach ( $statuses as $status => $label ) {
			register_post_status( $status, array(
				'label' => $label,
				'public' => TRUE,
				'exclude_from_search' => FALSE,
				'show_in_admin_all_list' => TRUE,
          		'show_in_admin_status_list' => TRUE,
          		'label_count' => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' )
			));
		}
	}

	/**
	 * Is the current query for an estimate(s)
	 * @param  object  $query 
	 * @return boolean        
	 */
	public static function is_estimate_query( WP_Query $query = NULL ) {
		if ( is_null( $query ) ) {
			global $wp_query;
			$query = $wp_query;
		}
		if ( !isset( $query->query_vars['post_type'] ) ) {
			return FALSE; // normal posts query
		}
		if ( $query->query_vars['post_type'] == self::POST_TYPE ) {
			return TRUE;
		}
		if ( is_array( $query->query_vars['post_type'] ) && in_array( self::POST_TYPE, $query->query_vars['post_type'] ) ) {
			return TRUE;
		}
		return FALSE;
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Sprout_Invoices_Estimate
	 */
	public static function get_instance( $id = 0 ) {
		if ( !$id )
			return NULL;
		
		if ( !isset( self::$instances[$id] ) || !self::$instances[$id] instanceof self )
			self::$instances[$id] = new self( $id );

		if ( !isset( self::$instances[$id]->post->post_type ) )
			return NULL;
		
		if ( self::$instances[$id]->post->post_type != self::POST_TYPE )
			return NULL;
		
		return self::$instances[$id];
	}

	public static function create_estimate( $args, $status = self::STATUS_REQUEST ) {
		$defaults = array(
			'subject' => sprintf( self::__('New Estimate: %s'), date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), current_time( 'timestamp' ) ) ),
			'requirements' => self::__('No requirements submitted. Check to make sure the "requirements" field is required.'),
		);
		$parsed_args = wp_parse_args( $args, $defaults );
		extract( $parsed_args );

		$id = wp_insert_post( array(
			'post_status' => $status,
			'post_type' => self::POST_TYPE,
			'post_title' => $subject
		) );
		if ( is_wp_error( $id ) ) {
			return 0;
		}

		$estimate = self::get_instance( $id );

		if ( isset( $fields ) ) {
			$estimate->set_submission_fields( $fields );
		}

		// Set the submitted user id if logged in.
		if ( is_user_logged_in() ) {
			$estimate->set_user_id( get_current_user_id() );
		}

		do_action( 'sa_new_estimate', $estimate, $parsed_args );
		return $id;
	}

	/////////////
	// Status //
	/////////////

	public function get_status() {
		return $this->post->post_status;
	}

	public function set_status( $status ) {
		// Don't do anything if there's no true change
		$current_status = $this->get_status();
		if ( $current_status == $status )
			return;

		// confirm the status exists
		if ( !in_array( $status, array_keys(self::get_statuses()) ) ) {
			switch ( $status ) {
				case self::__('approve'):
				case self::__('accept'):
					$status = self::STATUS_APPROVED;	
					break;
				case self::__('decline'):
				case self::__('pushback'):
					$status = self::STATUS_DECLINED;	
					break;
				
				default:
					return; // stop
					break;
			}
		}
			

		$this->post->post_status = $status;
		$this->save_post();
		do_action( 'si_estimate_status_updated', $this, $status, $current_status );
	}

	public function set_as_request() {
		$this->set_status( self::STATUS_REQUEST );
	}

	public function set_pending() {
		$this->set_status( self::STATUS_PENDING );
	}

	public function set_approved() {
		$this->set_status( self::STATUS_APPROVED );
	}

	public function set_declined() {
		$this->set_status( self::STATUS_DECLINED );
	}

	public function get_status_label( $status = '' ) {
		if ( $status == '' ) {
			$status = $this->get_status();
		}
		$statuses = self::get_statuses();
		return $statuses[$status];
	}

	///////////
	// Meta //
	///////////

	/**
	 * Get the submission fields
	 * @return array 
	 */
	public function get_submission_fields() {
		return $this->get_post_meta( self::$meta_keys['submission'] );
	}

	/**
	 * Save the submitted fields
	 * @param array $fields 
	 */
	public function set_submission_fields( $fields = array() ) {
		$this->save_post_meta( array(
				self::$meta_keys['submission'] => $fields,
			) );
		return $fields;
	}

	/**
	 * Issue date
	 */
	public function get_issue_date() {
		$date = (int)$this->get_post_meta( self::$meta_keys['issue_date'] );
		if ( !$date ) {
			$date = strtotime( $this->post->post_date );
		};
		return $date;
	}

	public function set_issue_date( $issue_date = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['issue_date'] => $issue_date,
			) );
		return $issue_date;
	}

	/**
	 * Expiration date
	 */
	public function get_expiration_date() {
		$date = (int)$this->get_post_meta( self::$meta_keys['expiration_date'] );
		if ( !$date ) {
			$date = strtotime( $this->post->post_date )+(60*60*24*30);
		};
		return $date;
	}

	public function set_expiration_date( $expiration_date = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['expiration_date'] => $expiration_date,
			) );
		return $expiration_date;
	}

	/**
	 * Estimate ID
	 */
	public function get_estimate_id() {
		$id = $this->get_post_meta( self::$meta_keys['estimate_id'] );
		if ( !$id ) {
			$id = $this->get_id();
			$this->set_estimate_id( $id );
		}
		return $id;
	}

	public function set_estimate_id( $estimate_id = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['estimate_id'] => $estimate_id,
			) );
		return $estimate_id;
	}

	/**
	 * PO number
	 */
	public function get_po_number() {
		return $this->get_post_meta( self::$meta_keys['po'] );
	}

	public function set_po_number( $po_number = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['po'] => $po_number,
			) );
		return $po_number;
	}

	/**
	 * Client
	 */

	public function get_client() {
		if ( !$this->get_client_id() ) {
			return new WP_Error( 'no_client', self::__('No client associated with this invoice.') );
		}
		return SI_Client::get_instance( $this->get_client_id() );
	}

	public function get_client_id() {
		return (int)$this->get_post_meta( self::$meta_keys['client_id'] );
	}

	public function set_client_id( $client_id = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['client_id'] => $client_id,
			) );
		return $client_id;
	}

	/**
	 * discount
	 */
	public function get_discount() {
		return (float)$this->get_post_meta( self::$meta_keys['discount'] );
	}

	public function set_discount( $discount = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['discount'] => $discount,
			) );
		return $discount;
	}

	/**
	 * Tax
	 */
	public function get_tax() {
		return $this->get_post_meta( self::$meta_keys['tax'] );
	}

	public function set_tax( $tax = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['tax'] => $tax,
			) );
		return $tax;
	}

	public function get_tax_total() {
		$tax = (float)$this->get_tax();
		$subtotal = $this->get_subtotal();
		$calculated_total = floatval( $subtotal * ( $tax / 100 ) );
		return round( $calculated_total, 2 );
	}

	/**
	 * Tax
	 */
	public function get_tax2() {
		return (float)$this->get_post_meta( self::$meta_keys['tax2'] );
	}

	public function set_tax2( $tax = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['tax2'] => $tax,
			) );
		return $tax;
	}

	public function get_tax2_total() {
		$tax = $this->get_tax2();
		$subtotal = $this->get_subtotal();
		$calculated_total = floatval( $subtotal * ( $tax / 100 ) );
		return round( $calculated_total, 2 );
	}

	/**
	 * Project
	 */
	public function get_project_id() {
		return (int)$this->get_post_meta( self::$meta_keys['project_id'] );
	}

	public function set_project_id( $project_id = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['project_id'] => $project_id,
			) );
		return $project_id;
	}

	public function get_project() {
		if ( class_exists( 'SI_Project' ) ) {		
			$project_id = $this->get_project_id();
			$project = SI_Project::get_instance( $project_id );
			return $project;
		}
	}

	/**
	 * totals
	 */

	public function get_total() {
		$total = $this->get_post_meta( self::$meta_keys['total'] );
		return $total;
	}

	public function set_total( $total = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['total'] => $total,
			) );
		return $total;
	}

	/**
	 * Calculated total includes any taxes and discounts.
	 * @return  
	 */
	public function get_calculated_total() { 
		$subtotal = $this->get_subtotal();
		if ( $subtotal < 0.01 ) { // In case the line items are zero but the total has a value
			$subtotal = $this->get_total();
		}
		$tax_total = $subtotal * ( ( $this->get_tax() ) / 100 );
		$tax2_total = $subtotal * ( ( $this->get_tax2() ) / 100 );
		$pre_disc_total = $subtotal+$tax_total+$tax2_total;
		$total = $pre_disc_total * ( ( 100 - $this->get_discount() ) / 100 );
		return $total;
	}

	public function set_calculated_total() {
		$total = $this->get_calculated_total();
		$this->save_post_meta( array(
				self::$meta_keys['total'] => $total,
			) );
		return $total;
	}

	public function get_subtotal() {
		$subtotal = 0;
		$line_items = $this->get_line_items();
		if ( !empty( $line_items ) ) {
			foreach ( $line_items as $key => $data ) {
				if ( $data['rate'] ) {
					$calc = ( $data['rate']*$data['qty'] ) * ( ( 100 - $data['tax'] ) / 100 );
					$subtotal += apply_filters( 'si_line_item_total', $calc, $data );
				}
			}
		}
		return $subtotal;
	}

	/**
	 * Terms
	 */
	public function get_terms() {
		$terms = $this->get_post_meta( self::$meta_keys['terms'] );
		return apply_filters( 'get_estimate_terms', $terms, $this );
	}

	public function set_terms( $terms = '' ) {
		$this->save_post_meta( array(
				self::$meta_keys['terms'] => $terms,
			) );
		return $terms;
	}

	/**
	 * Notes
	 */
	public function get_notes() {
		$notes = $this->get_post_meta( self::$meta_keys['notes'] );
		return apply_filters( 'get_estimate_notes', $notes, $this );
	}

	public function set_notes( $notes = '' ) {
		$this->save_post_meta( array(
				self::$meta_keys['notes'] => $notes,
			) );
		return $notes;
	}

	/**
	 * Send notes
	 */
	public function get_sender_note() {
		$send_notes = $this->get_post_meta( self::$meta_keys['send_notes'] );
		return apply_filters( 'get_sender_note', $send_notes, $this );
	}

	public function set_sender_note( $send_notes = '' ) {
		$this->save_post_meta( array(
				self::$meta_keys['send_notes'] => $send_notes,
			) );
		return $send_notes;
	}

	/**
	 * Line items
	 */
	public function get_line_items() {
		$line_items = $this->get_post_meta( self::$meta_keys['line_items'] );
		if ( !is_array( $line_items ) ) {
			$line_items = array();
		}
		return $line_items;
	}

	public function set_line_items( $line_items = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['line_items'] => $line_items,
			) );
		return $line_items;
	}

	/**
	 * Currency
	 */

	public function get_currency() {
		$code = $this->get_post_meta( self::$meta_keys['currency'] );
		if ( !$code ) {
			$code = 'USD';
			$this->set_currency($code);
		}
		return $code;
	}

	public function set_currency( $currency = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['currency'] => $currency,
			) );
		return $currency;
	}

	/**
	 * User id
	 */

	public function get_user_id() {
		return (int)$this->get_post_meta( self::$meta_keys['user_id'] );
	}

	public function set_user_id( $user_id = 0 ) {
		$this->save_post_meta( array(
				self::$meta_keys['user_id'] => $user_id,
			) );
		return $user_id;
	}



	//////////////
	// Utility //
	//////////////

	public function get_invoice_id() {
		$invoice_ids = SI_Invoice::get_invoices_by_estimate_id( $this->ID );
		return array_pop($invoice_ids); // A single invoice should be associated
	}


	public function get_history( $type = '' ) {
		return SI_Record::get_records_by_association( $this->ID );
	}

}