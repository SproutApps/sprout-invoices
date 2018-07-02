<?php

/**
 * Project Model
 *
 *
 * @package Sprout_Invoices
 * @subpackage Project
 */
class SI_Project extends SI_Post_Type {
	const POST_TYPE = 'sa_project';
	const REWRITE_SLUG = 'sprout-project';
	private static $instances = array();

	private static $meta_keys = array(
		'associated_clients' => '_clients',
		'associated_invoices' => '_invoice_ids', // associated invoices
		'associated_time' => '_assoc_time_records',
		'associated_expenses' => '_assoc_expense_records',
		'clocked_time' => '_clocked_time',
		'expensed_time' => '_expensed_time',
		'start_date' => 'start_date',
		'end_date' => 'end_date',
		'website' => '_website',
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.


	public static function init() {
		// register Project post type
		$post_type_args = array(
			'public' => false,
			'has_archive' => false,
			'show_ui' => true,
			'show_in_menu' => 'edit.php?post_type='.SI_Invoice::POST_TYPE,
			'rewrite' => array(
				'slug' => self::REWRITE_SLUG,
				'with_front' => false,
			),
			'supports' => array( '' ),
		);
		self::register_post_type( self::POST_TYPE, 'Project', 'Projects', $post_type_args );
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Sprout_Invoices_Project
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

	/**
	 * Create a project
	 * @param  array $args
	 * @return int
	 */
	public static function new_project( $args ) {
		$defaults = array(
			'project_name' => sprintf( __( 'New Project: %s', 'sprout-invoices' ), date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), current_time( 'timestamp' ) ) ),
			'associated_clients' => array(),
			'project_description' => '',
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$id = wp_insert_post( array(
			'post_status' => 'publish',
			'post_type' => self::POST_TYPE,
			'post_title' => $parsed_args['project_name'],
			'post_content' => $parsed_args['project_description'],
		) );
		if ( is_wp_error( $id ) ) {
			return 0;
		}

		$project = self::get_instance( $id );

		if ( is_numeric( $parsed_args['associated_clients'] ) ) {
			$parsed_args['associated_clients'] = array( $parsed_args['associated_clients'] );
		}
		$project->set_associated_clients( $parsed_args['associated_clients'] );

		do_action( 'sa_new_project', $project, $parsed_args );
		return $id;
	}

	///////////
	// Meta //
	///////////


	/**
	 * Get the associated clients with this project
	 * @return array
	 */
	public function get_associated_clients() {
		$clients = $this->get_post_meta( self::$meta_keys['associated_clients'], true );
		if ( ! is_array( $clients ) ) {
			if ( is_numeric( $clients ) ) {
				$clients = array( $clients );
			} else {
				$clients = array();
			}
		}
		return array_unique( $clients );
	}

	/**
	 * Save the associated clients with this project
	 * @param array $clients
	 */
	public function set_associated_clients( $clients = array() ) {
		$this->save_post_meta( array(
			self::$meta_keys['associated_clients'] => $clients,
		) );
		return $clients;
	}

	/**
	 * Clear out the associated clients
	 * @param array $clients
	 */
	public function clear_associated_clients() {
		$this->delete_post_meta( array(
			self::$meta_keys['associated_clients'] => '',
		) );
	}

	/**
	 * Add single client to associated array
	 * @param integer $client_id
	 *
	 * TODO Use non-unique meta instead of an array.
	 */
	public function add_associated_client( $client_id = 0 ) {
		if ( $client_id && ! $this->is_client_associated( $client_id ) ) {
			$this->clear_associated_clients(); // only single user ATM
			$this->add_post_meta( array(
				self::$meta_keys['associated_clients'] => array( $client_id ),
			) );
		}
	}

	public function is_client_associated( $client_id ) {
		$associated_clients = $this->get_associated_clients();
		if ( empty( $associated_clients ) ) { return; }
		return in_array( $client_id, $associated_clients );
	}


	/**
	 * Get the associated times with this project
	 * @return array
	 */
	public function get_associated_times() {
		$times = $this->get_post_meta( self::$meta_keys['associated_time'] );
		if ( ! is_array( $times ) ) {
			$times = array();
		}
		return array_filter( $times );
	}

	/**
	 * Save the associated times with this project
	 * @param array $times
	 */
	public function set_associated_times( $times = array() ) {
		$this->save_post_meta( array(
			self::$meta_keys['associated_time'] => $times,
		) );
		return $times;
	}

	/**
	 * Clear out the associated times
	 * @param array $times
	 */
	public function clear_associated_times() {
		$this->delete_post_meta( array(
			self::$meta_keys['associated_time'] => '',
		) );
	}

	/**
	 * Add single time to associated array
	 * @param array $time_data
	 */
	public function create_associated_time( $time_data = array() ) {
		$time = false;
		if ( isset( $time_data['activity_id'] ) ) {
			$time = SI_Time::get_instance( $time_data['activity_id'] );
		}
		if ( ! $time || ! is_a( $time, 'SI_Time' ) ) {
			// get default time to clock time to.
			$activity_id = SI_Time::default_time();
			$time = SI_Time::get_instance( $activity_id );
		}
		// Create time entry record
		$new_time_id = $time->new_time( $time_data );
		// Add to the associated array on this project
		$this->add_associated_time( $new_time_id );
		$this->save_post(); // update modified time.
		return $new_time_id;
	}

	/**
	 * Add single time to associated array
	 * @param int $time_id
	 */
	public function add_associated_time( $time_id = 0 ) {
		$times = $this->get_associated_times();
		$times[] = $time_id;
		$this->set_associated_times( $times );
	}

	/**
	 * Remove single time to associated array
	 * @param int $time_id
	 */
	public function remove_time_associated( $time_id = 0 ) {
		// Delete time record
		$time = SI_Record::get_instance( $time_id );
		if ( is_a( $time, 'SI_Record' ) ) {
			$activity_id = $time->get_associate_id();
			$activity = SI_Time::get_instance( $activity_id );
			$activity->delete_time( $time_id );
		}
		// Remove from associated array
		$times = $this->get_associated_times();
		if ( ( $key = array_search( $time_id, $times ) ) !== false ) {
			unset( $times[ $key ] );
		}
		$this->set_associated_times( $times );
	}

	/**
	 * Remove single time to associated array
	 * @param array $time_data
	 */
	public function update_time_with_invoice_id( $time_data = array() ) {
		// Invoice id will show as billed
		$original_data = $time_data;
		unset( $original_data['invoice_id'] );
		update_post_meta( $this->ID, 'time_invoice_id', $time_data, $original_data );
	}

	/////////////
	// Expense //
	/////////////

	/**
	 * Get the associated expenses with this project
	 * @return array
	 */
	public function get_associated_expenses() {
		$expenses = $this->get_post_meta( self::$meta_keys['associated_expenses'] );
		if ( ! is_array( $expenses ) ) {
			$expenses = array();
		}
		return array_filter( $expenses );
	}

	/**
	 * Save the associated expenses with this project
	 * @param array $expenses
	 */
	public function set_associated_expenses( $expenses = array() ) {
		$this->save_post_meta( array(
			self::$meta_keys['associated_expenses'] => $expenses,
		) );
		return $expenses;
	}

	/**
	 * Clear out the associated expenses
	 * @param array $expenses
	 */
	public function clear_associated_expenses() {
		$this->delete_post_meta( array(
			self::$meta_keys['associated_expenses'] => '',
		) );
	}

	/**
	 * Add single expense to associated array
	 * @param array $expense_data
	 */
	public function create_associated_expense( $expense_data = array() ) {
		$expense = false;
		if ( isset( $expense_data['category_id'] ) ) {
			$expense = SI_Expense::get_instance( $expense_data['category_id'] );
		}
		if ( ! $expense || ! is_a( $expense, 'SI_Expense' ) ) {
			// get default expense to track expense to.
			$category_id = SI_Expense::default_expense();
			$expense = SI_Expense::get_instance( $category_id );
		}
		// Create expense entry record
		$new_expense_id = $expense->new_expense( $expense_data );
		// Add to the associated array on this project
		$this->add_associated_expense( $new_expense_id );
		$this->save_post(); // update modified expense.
		return $new_expense_id;
	}

	/**
	 * Add single expense to associated array
	 * @param int $expense_id
	 */
	public function add_associated_expense( $expense_id = 0 ) {
		$expenses = $this->get_associated_expenses();
		$expenses[] = $expense_id;
		$this->set_associated_expenses( $expenses );
	}

	/**
	 * Remove single expense to associated array
	 * @param int $expense_id
	 */
	public function remove_expense_associated( $expense_id = 0 ) {
		// Delete expense record
		$expense = SI_Record::get_instance( $expense_id );
		if ( is_a( $expense, 'SI_Record' ) ) {
			$activity_id = $expense->get_associate_id();
			$activity = SI_Expense::get_instance( $activity_id );
			$activity->delete_expense( $expense_id );
		}
		// Remove from associated array
		$expenses = $this->get_associated_expenses();
		if ( ( $key = array_search( $expense_id, $expenses ) ) !== false ) {
			unset( $expenses[ $key ] );
		}
		$this->set_associated_expenses( $expenses );
	}

	/**
	 * Remove single expense to associated array
	 * @param array $expense_data
	 */
	public function update_expense_with_invoice_id( $expense_data = array() ) {
		// Invoice id will show as billed
		$original_data = $expense_data;
		unset( $original_data['invoice_id'] );
		update_post_meta( $this->ID, 'expense_invoice_id', $expense_data, $original_data );
	}


	/**
	 * Get the associated invoices with this project
	 * @return array
	 */
	public function get_associated_invoices() {
		$invoices = $this->get_post_meta( self::$meta_keys['associated_invoices'], false );
		if ( ! is_array( $invoices ) ) {
			$invoices = array();
		}
		return array_filter( $invoices );
	}

	/**
	 * Save the associated invoices with this project
	 * @param array $invoices
	 */
	public function set_associated_invoices( $invoices = array() ) {
		$this->save_post_meta( array(
			self::$meta_keys['associated_invoices'] => $invoices,
		) );
		return $invoices;
	}

	/**
	 * Clear out the associated invoices
	 * @param array $invoices
	 */
	public function clear_associated_invoices() {
		$this->delete_post_meta( array(
			self::$meta_keys['associated_invoices'] => '',
		) );
	}

	/**
	 * Add single invoice to associated array
	 * @param integer $invoice_id
	 */
	public function add_associated_invoice( $invoice_id = 0 ) {
		if ( $invoice_id && ! $this->is_invoice_associated( $invoice_id ) ) {
			$this->add_post_meta( array(
				self::$meta_keys['associated_invoices'] => $invoice_id,
			) );
		}
	}

	public function is_invoice_associated( $invoice_id ) {
		$associated_invoices = $this->get_associated_invoices();
		if ( empty( $associated_invoices ) ) { return; }
		return in_array( $invoice_id, $associated_invoices );
	}

	public function get_start_date() {
		return $this->get_post_meta( self::$meta_keys['start_date'] );
	}

	public function set_start_date( $start_date ) {
		if ( ! is_numeric( $start_date ) ) {
			$start_date = strtotime( $start_date );
		}
		return $this->save_post_meta( array( self::$meta_keys['start_date'] => $start_date ) );
	}

	public function get_end_date() {
		return $this->get_post_meta( self::$meta_keys['end_date'] );
	}

	public function set_end_date( $end_date ) {
		if ( ! is_numeric( $end_date ) ) {
			$end_date = strtotime( $end_date );
		}
		return $this->save_post_meta( array( self::$meta_keys['end_date'] => $end_date ) );
	}

	public function get_website() {
		return $this->get_post_meta( self::$meta_keys['website'] );
	}

	public function set_website( $website ) {
		return $this->save_post_meta( array( self::$meta_keys['website'] => $website ) );
	}


	//////////////
	// Utility //
	//////////////

	/**
	 * Get the associated invoices by all associated clients.
	 * @return array
	 */
	public function get_invoices() {
		$invoices = self::find_by_meta( SI_Invoice::POST_TYPE, array( '_project_id' => $this->ID ) );
		return $invoices;
	}

	/**
	 * Get the associated estimates by all associated clients.
	 * @return array
	 */
	public function get_estimates() {
		$estimates = self::find_by_meta( SI_Estimate::POST_TYPE, array( '_project_id' => $this->ID ) );
		return $estimates;
	}

	/**
	 * Get the projects that are associated with the invoice
	 * @param  integer $invoice_id
	 * @return array
	 */
	public static function get_projects_by_invoice( $invoice_id = 0 ) {
		$projects = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['associated_invoices'] => $invoice_id ), true );
		return $projects;
	}

	/**
	 * Get the projects that are associated with the client
	 * @param  integer $client_id
	 * @return array
	 */
	public static function get_projects_by_client( $client_id = 0 ) {
		$projects = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['associated_clients'] => $client_id ), true );
		return $projects;
	}

	public function get_payments() {
		$payments = array();
		$invoices = $this->get_invoices();
		foreach ( $invoices as $invoice_id ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
			$payments = array_merge( $payments, $invoice->get_payments() );
		}
		return $payments;
	}

	/**
	 * Get all payments from this project.
	 * @param  integer $project_id
	 * @return
	 */
	public static function get_payments_by_project( $project_id = 0 ) {
		$project = self::get_instance( $project_id );
		$payments = $project->get_payments();
		return $payments;
	}

	public function get_history( $type = '' ) {
		return SI_Record::get_records_by_association( $this->ID );
	}
}
