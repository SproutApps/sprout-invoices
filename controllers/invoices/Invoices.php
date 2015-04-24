<?php


/**
 * Clients Controller
 * 	
 *
 * @package Sprout_Invoice
 * @subpackage Clients
 */
class SI_Invoices extends SI_Controller {
	const HISTORY_UPDATE = 'si_history_update';
	const HISTORY_STATUS_UPDATE = 'si_history_status_update';
	const VIEWED_STATUS_UPDATE = 'si_viewed_status_update';
	const TERMS_OPTION = 'si_default_invoice_terms';
	const NOTES_OPTION = 'si_default_invoice_notes';
	const SLUG_OPTION = 'si_invoices_perma_slug';
	private static $default_terms;
	private static $default_notes;
	private static $invoices_slug;

	public static function init() {

		// Settings
		self::$default_terms = get_option( self::TERMS_OPTION, 'We do expect payment within 21 days, so please process this invoice within that time. There will be a 1.5% interest charge per month on late invoices.' );
		self::$default_notes = get_option( self::NOTES_OPTION, 'Thank you; we really appreciate your business.' );
		self::$invoices_slug = get_option( self::SLUG_OPTION, SI_Invoice::REWRITE_SLUG );
		
		self::register_settings();

		// Help Sections
		add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

		if ( is_admin() ) {
			// Meta boxes
			add_action( 'admin_init', array( __CLASS__, 'register_meta_boxes' ), 100 );
			add_filter( 'wp_insert_post_data', array( __CLASS__, 'update_post_data' ), 100, 2 );
			add_action( 'do_meta_boxes', array( __CLASS__, 'modify_meta_boxes' ), 100 );
			add_action( 'edit_form_top', array( __CLASS__, 'quick_links' ), 100 );

			// Admin columns
			add_filter( 'manage_edit-'.SI_Invoice::POST_TYPE.'_columns', array( __CLASS__, 'register_columns' ) );
			add_filter( 'manage_'.SI_Invoice::POST_TYPE.'_posts_custom_column', array( __CLASS__, 'column_display' ), 10, 2 );
			add_filter( 'manage_edit-'.SI_Invoice::POST_TYPE.'_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
			add_filter( 'views_edit-'.SI_Invoice::POST_TYPE, array( __CLASS__, 'filter_status_view' ) );
			add_filter( 'display_post_states', array( __CLASS__, 'filter_post_states' ), 10, 2 );

			// Remove quick edit from admin and add some row actions
			add_action( 'bulk_actions-edit-sa_invoice', array( __CLASS__, 'modify_bulk_actions' ) );
			add_action( 'post_row_actions', array( __CLASS__, 'modify_row_actions' ), 10, 2 );

			// Single column
			add_filter( 'get_user_option_screen_layout_sa_invoice', array( __CLASS__, 'screen_layout_pref' ) );
			add_filter( 'screen_layout_columns', array( __CLASS__, 'screen_layout_columns' ) );
		}

		// Unique urls
		add_filter( 'wp_unique_post_slug', array( __CLASS__, 'post_slug'), 10, 4 );

		// Templating
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'remove_scripts_and_styles' ), PHP_INT_MAX );
		add_action( 'wp_print_scripts', array( __CLASS__, 'remove_scripts_and_styles_from_stupid_themes_and_plugins' ), -PHP_INT_MAX ); // can't rely on themes to abide by enqueing correctly
		
		// Create invoice when estimate is approved.
		add_action( 'doc_status_changed',  array( __CLASS__, 'create_invoice_on_est_acceptance' ), 0 ); // fire before any others
		add_action( 'doc_status_changed',  array( __CLASS__, 'create_payment_when_invoice_marked_as_paid' ) );

		// Mark paid or partial after payment
		add_action( 'si_new_payment',  array( __CLASS__, 'change_status_after_payment' ) );

		// Status updates
		add_action( 'si_invoice_status_updated',  array( __CLASS__, 'maybe_create_status_update_record' ), 10, 3 );

		// Cloning from estimates
		add_action( 'si_cloned_post',  array( __CLASS__, 'associate_invoice_after_clone' ), 10, 3 );

		// Adjust invoice id and status after clone
		add_action( 'si_cloned_post',  array( __CLASS__, 'adjust_cloned_invoice' ), 10, 3 );

		// Notifications
		add_filter( 'wp_ajax_sa_send_est_notification', array( __CLASS__, 'maybe_send_notification' ) );

		// Set the default terms and notes
		add_filter( 'get_invoice_terms', array( __CLASS__, 'maybe_set_invoice_terms' ), 10, 2 );
		add_filter( 'get_invoice_notes', array( __CLASS__, 'maybe_set_invoice_notes' ), 10, 2 );

		// Invoice Payment Remove deposit
		add_filter( 'processed_payment', array( __CLASS__, 'maybe_remove_deposit' ), 10, 2 );

		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );

		// Rewrite rules
		add_filter( 'si_register_post_type_args-'.SI_Invoice::POST_TYPE, array( __CLASS__, 'modify_post_type_slug' ) );

		add_filter( 'si_line_item_content', array( __CLASS__, 'line_item_content_filter' ) );

	}

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			'si_invoice_settings' => array(
				'title' => self::__('Invoice Settings'),
				'weight' => 20,
				'tab' => 'settings',
				'settings' => array(
					self::SLUG_OPTION => array(
						'label' => self::__( 'Invoice Permalink Slug' ),
						'sanitize_callback' => array( __CLASS__, 'sanitize_slug_option' ),
						'option' => array(
							'type' => 'text',
							'attributes' => array(
								'class' => 'medium-text',
							),
							'default' => self::$invoices_slug,
							'description' => sprintf( self::__( 'Example invoice url: %s/%s/045b41dd14ab8507d80a27b7357630a5/' ), site_url(), '<strong>'.self::$invoices_slug.'</strong>' )
						),
						
					),
					self::TERMS_OPTION => array(
						'label' => self::__( 'Default Terms' ),
						'option' => array(
							'type' => 'wysiwyg',
							'default' => self::$default_terms,
							'description' => self::__( 'These are the default terms for an invoice.' )
						),
						
					),
					self::NOTES_OPTION => array(
						'label' => self::__( 'Default Note' ),
						'option' => array(
							'type' => 'wysiwyg',
							'default' => self::$default_notes,
							'description' => self::__( 'These are the default notes public to a client reviewing their invoice.' )
						),
					)
				)
			)
		);
		do_action( 'sprout_settings', $settings );
	}

	public static function get_default_terms() {
		return self::$default_terms;
	}

	public static function get_default_notes() {
		return self::$default_notes;
	}

	/**
	 * Used to add the invoice post type to some taxonomy registrations.
	 * @param array $post_types 
	 */
	public static function add_invoice_post_type_to_taxonomy( $post_types ) {
		$post_types[] = SI_Invoice::POST_TYPE;
		return $post_types;
	}

	////////////////////
	// rewrite slugs //
	////////////////////

	/**
	 * PRe-register post type arguments
	 * @param  array  $args 
	 * @return array       
	 */
	public static function modify_post_type_slug( $args = array() ) {
		$args['rewrite']['slug'] = self::$invoices_slug;
		return $args;
	}
	
	/**
	 * Don't allow for a blank value
	 * @param  string $option 
	 * @return string
	 */
	public static function sanitize_slug_option( $option = '' ) {
		// Trim and remove someone from entering the full url.
		$option = str_replace( site_url(), '', trim( $option ) );
		if ( $option == '' ) {
			$option = SI_Invoice::REWRITE_SLUG;
		}
		return sanitize_title_with_dashes( $option );
	}

	/////////////////
	// Meta boxes //
	/////////////////

	/**
	 * Regsiter meta boxes for invoice editing.
	 *
	 * @return
	 */
	public static function register_meta_boxes() {
		// invoice specific
		$args = array(
			'si_invoice_line_items' => array(
				'title' => si__( 'Management' ),
				'show_callback' => array( __CLASS__, 'show_line_items_view' ),
				'save_callback' => array( __CLASS__, 'save_line_items' ),
				'context' => 'normal',
				'priority' => 'high',
				'weight' => 0,
				'save_priority' => 5
			),
			'si_invoice_update' => array(
				'title' => si__( 'Information' ),
				'show_callback' => array( __CLASS__, 'show_information_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_meta_box_invoice_information' ),
				'context' => 'normal',
				'priority' => 'high',
				'weight' => 20,
				'save_priority' => 50
			),
			'si_doc_send' => array(
				'title' => si__( 'Send Invoice' ),
				'show_callback' => array( __CLASS__, 'show_invoice_send_view' ),
				'save_callback' => array( __CLASS__, 'save_invoice_note' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 30,
				'save_priority' => 500
			),
			'si_invoice_history' => array(
				'title' => si__( 'Invoice History' ),
				'show_callback' => array( __CLASS__, 'show_submission_history_view' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 20
			),
			'si_invoice_notes' => array(
				'title' => si__( 'Terms & Notes' ),
				'show_callback' => array( __CLASS__, 'show_notes_view' ),
				'save_callback' => array( __CLASS__, 'save_notes' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 100
			)
		);
		do_action( 'sprout_meta_box', $args, SI_Invoice::POST_TYPE );
	}

	/**
	 * Remove publish box and add something custom for invoices
	 *
	 * @param string  $post_type
	 * @return
	 */
	public static function modify_meta_boxes( $post_type ) {
		if ( $post_type == SI_Invoice::POST_TYPE ) {
			remove_meta_box( 'submitdiv', null, 'side' );
		}
	}

	/**
	 * Add quick links
	 * @param  object $post
	 * @return
	 */
	public static function quick_links( $post ) {
		if ( get_post_type( $post ) == SI_Invoice::POST_TYPE ) {
			$invoice = SI_Invoice::get_instance( $post->ID );
			$status = ( is_a( $invoice, 'SI_Invoice' ) && $invoice->get_status() != 'auto-draft' ) ? $invoice->get_status() : SI_Invoice::STATUS_TEMP ;
			self::load_view( 'admin/meta-boxes/invoices/quick-links', array(
					'id' => $post->ID,
					'post' => $post,
					'status' => $status,
					'statuses' => SI_Invoice::get_statuses(),
					'invoice' => $invoice
				) );
		}
	}

	/**
	 * Show custom line item mngt box.
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_line_items_view( $post, $metabox ) {
		$invoice = SI_Invoice::get_instance( $post->ID );
		$total = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_total() : '0.00' ;
		$subtotal = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_subtotal() : '0.00' ;
		$payments_total = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_payments_total() : '0.00' ;
		$deposit = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_deposit() : '0.00' ;
		$status = ( is_a( $invoice, 'SI_Invoice' ) && $invoice->get_status() != 'auto-draft' ) ? $invoice->get_status() : SI_Invoice::STATUS_TEMP ;
		$line_items = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_line_items() : array() ;
		self::load_view( 'admin/meta-boxes/invoices/line-items', array(
				'id' => $post->ID,
				'post' => $post,
				'status' => $status,
				'total' => $total,
				'total_payments' => $payments_total,
				'subtotal' => $subtotal,
				'deposit' => $deposit,
				'line_items' => $line_items
			), false );
	}

	/**
	 * Saving line items first thing since totals are calculated later based on other options.
	 * @param  int $post_id       
	 * @param  object $post          
	 * @param  array $callback_args 
	 * @param  int $invoice_id   
	 * @return                 
	 */
	public static function save_line_items( $post_id, $post, $callback_args, $invoice_id = null ) {
		if ( !isset( $_POST['line_item_key'] ) )
			return;
		
		$invoice = SI_Invoice::get_instance( $post_id );
		$line_items = array();
		foreach ( $_POST['line_item_key'] as $key => $order ) {
			if ( isset( $_POST['line_item_desc'][$key] ) && $_POST['line_item_desc'][$key] != '' ) {
				$line_items[$order] = array(
					'rate' => ( isset( $_POST['line_item_rate'][$key] ) && $_POST['line_item_rate'][$key] != '' ) ? $_POST['line_item_rate'][$key] : 0,
					'qty' => ( isset( $_POST['line_item_qty'][$key] ) && $_POST['line_item_qty'][$key] != '' ) ? $_POST['line_item_qty'][$key] : 0,
					'tax' => ( isset( $_POST['line_item_tax'][$key] ) && $_POST['line_item_tax'][$key] != '' ) ? $_POST['line_item_tax'][$key] : 0,
					'desc' => $_POST['line_item_desc'][$key],
					'type' => ( isset( $_POST['line_item_type'][$key] ) && $_POST['line_item_type'][$key] != '' ) ? $_POST['line_item_type'][$key] : 0,
					'total' => ( isset( $_POST['line_item_total'][$key] ) && $_POST['line_item_total'][$key] != '' ) ? $_POST['line_item_total'][$key] : 0,
					);
			}
		}

		// Set the line items meta
		$invoice->set_line_items( $line_items );

		// Deposits are not supported without the premium version.
		$invoice->set_deposit( $invoice->get_balance() );
		
		do_action( 'si_save_line_items_meta_box', $post_id, $post, $invoice );
	}

	public static function update_post_data( $data = array(), $post = array() ) {
		if ( $post['post_type'] == SI_Invoice::POST_TYPE ) {
			$title = $post['post_title'];
			if ( isset( $_POST['subject'] ) && $_POST['subject'] != '' ) {
				$title = $_POST['subject'];
			}
			// modify the post title
			$data['post_title'] = $title;
		}
		return $data;
	}

	/**
	 * Show custom information box.
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_information_meta_box( $post, $metabox ) {
		// For client creation
		add_thickbox();

		$args = array(
			'post_type' => SI_Client::POST_TYPE,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids'
		);
		$clients = get_posts( $args );
		$client_options = array();
		foreach ( $clients as $client_id ) {
			$client_options[$client_id] = get_the_title( $client_id );
		}

		$invoice = SI_Invoice::get_instance( $post->ID );
		$status = ( is_a( $invoice, 'SI_Invoice' ) && $invoice->get_status() != 'auto-draft' ) ? $invoice->get_status() : SI_Invoice::STATUS_TEMP ;
		$due_date = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_due_date() : current_time( 'timestamp' )+(60*60*24*30);
		$expiration_date = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_expiration_date() : current_time( 'timestamp' )+(60*60*24*30);
		$issue_date = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_issue_date() : strtotime( $post->post_date ) ;
		$estimate_id = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_estimate_id() : 0 ;
		$invoice_id = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_invoice_id() : '00001';
		$po_number = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_po_number() : '';
		$client_id = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_client_id() : 0;
		$deposit = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_deposit() : '';
		$discount = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_discount() : '';
		$tax = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_tax() : '';
		$tax2 = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_tax2() : '';
		$currency = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_currency() : '';

		self::load_view( 'admin/meta-boxes/invoices/information', array(
				'id' => $post->ID,
				'post' => $post,
				'invoice' => $invoice,
				'status' => $status,
				'status_options' => SI_Invoice::get_statuses(),
				'estimate_id' => $estimate_id,
				'invoice_id' => $invoice_id,
				'expiration_date' => $expiration_date,
				'due_date' => $due_date,
				'issue_date' => $issue_date,
				'client_id' => $client_id,
				'client_options' => $client_options,
				'clients' => $clients,
				'po_number' => $po_number,
				'deposit' => $deposit,
				'discount' => $discount,
				'tax' => $tax,
				'tax2' => $tax2,
				'currency' => $currency,
			), false );

		// add the client modal
		self::load_view( 'admin/meta-boxes/clients/creation-modal', array( 'fields' => SI_Clients::form_fields( false ) ) );
	}

	/**
	 * Saving line items first thing since totals are calculated later based on other options.
	 * @param  int $post_id       
	 * @param  object $post          
	 * @param  array $callback_args 
	 * @param  int $invoice_id   
	 * @return                 
	 */
	public static function save_meta_box_invoice_information( $post_id, $post, $callback_args, $invoice_id = null ) {
		$invoice = SI_Invoice::get_instance( $post_id );

		$status = ( isset( $_POST['status'] ) && $_POST['status'] != '' ) ? $_POST['status'] : '' ;
		$due_m = ( isset( $_POST['due_mm'] ) && $_POST['due_mm'] != '' ) ? $_POST['due_mm'] : '' ;
		$due_j = ( isset( $_POST['due_j'] ) && $_POST['due_j'] != '' ) ? $_POST['due_j'] : '' ;
		$due_o = ( isset( $_POST['due_o'] ) && $_POST['due_o'] != '' ) ? $_POST['due_o'] : '' ;
		$invoice_id = ( isset( $_POST['invoice_id'] ) && $_POST['invoice_id'] != '' ) ? $_POST['invoice_id'] : '' ;
		$po_number = ( isset( $_POST['po_number'] ) && $_POST['po_number'] != '' ) ? $_POST['po_number'] : '' ;
		$client_id = ( isset( $_POST['sa_metabox_client'] ) && $_POST['sa_metabox_client'] != '' ) ? $_POST['sa_metabox_client'] : '' ;
		$discount = ( isset( $_POST['discount'] ) && $_POST['discount'] != '' ) ? $_POST['discount'] : '' ;
		$tax = ( isset( $_POST['tax'] ) && $_POST['tax'] != '' ) ? $_POST['tax'] : '' ;
		$tax2 = ( isset( $_POST['tax2'] ) && $_POST['tax2'] != '' ) ? $_POST['tax2'] : '' ;
		$currency = ( isset( $_POST['currency'] ) && $_POST['currency'] != '' ) ? $_POST['currency'] : '' ;

		$invoice->set_status( $status );
		$invoice->set_due_date( strtotime( $due_m  . '/' . $due_j . '/' . $due_o ) );
		$invoice->set_invoice_id( $invoice_id );
		$invoice->set_po_number( $po_number );
		$invoice->set_client_id( $client_id );
		$invoice->set_discount( $discount );
		$invoice->set_tax( $tax );
		$invoice->set_tax2( $tax2 );
		$invoice->set_currency( $currency );

		// Last thing to do is set the total based on the options set, including the line items.
		$invoice->set_calculated_total();

		$user = get_userdata( get_current_user_id() );
		do_action( 'si_new_record', 
			sprintf( si__('Invoice updated by %s.'), $user->display_name ), 
			self::HISTORY_UPDATE, 
			$invoice->get_id(), 
			sprintf( si__('Data updated for %s.'), $invoice->get_id() ), 
			0, 
			false );
	}

	/**
	 * Show the invoice sending options
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_invoice_send_view( $post, $metabox ) {
		if ( $post->post_status == 'auto-draft' ) {
			printf( '<p>%s</p>', si__( 'Save this invoice before sending.' ) );
			return;
		}
		$invoice = SI_Invoice::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/invoices/send', array(
				'id' => $post->ID,
				'post' => $post,
				'invoice' => $invoice,
				'fields' => self::sender_submission_fields( $invoice ),
				'sender_notes' => $invoice->get_sender_note()
			), false );
	}

	public static function sender_submission_fields( SI_Invoice $invoice ) {
		$fields = array();

		$from_name = get_option( SI_Notifications::EMAIL_FROM_NAME, get_bloginfo( 'name' ) );
		$from_email = get_option( SI_Notifications::EMAIL_FROM_EMAIL, get_bloginfo( 'admin_email' ) );
		$fields['send_as'] = array(
			'weight' => 1,
			'label' => self::__( 'Send As' ),
			'type' => 'text',
			'placeholder' => '',
			'attributes' => array( 'disabled' => 'disabled' ),
			'default' => $from_name . ' <' . $from_email . '>'
		);

		// options for recipients
		$client = $invoice->get_client();
		
		$recipient_options = '<div class="form-group"><div class="input_wrap">';
		
			// client users
			if ( is_a( $client , 'SI_Client') ) {
				$client_users = $client->get_associated_users();
				foreach ( $client_users as $user_id ) {
					if ( !is_wp_error( $user_id ) ) {
						$recipient_options .= sprintf( '<label class="clearfix"><input type="checkbox" name="sa_metabox_recipients[]" value="%1$s"> %2$s</label>', $user_id, esc_attr( SI_Notifications::get_user_email( $user_id ) ) );
					}
				}
			}
			// Send to me.
			$recipient_options .= sprintf( '<label class="clearfix"><input type="checkbox" name="sa_metabox_recipients[]" value="%1$s"> %2$s</label>', get_current_user_id(), si__('Send me a copy') );

		$recipient_options .= '</div></div>';

		$fields['recipients'] = array(
			'weight' => 5,
			'label' => sprintf( '%s <span class="helptip" title="%s"></span>', si__('Recipients'), si__("A notification will be sent if recipients are selected and this invoice is saved.") ),
			'type' => 'bypass',
			'output' => $recipient_options
		);

		$fields['sender_note'] = array(
			'weight' => 10,
			'label' => self::__( 'Note' ),
			'type' => 'textarea',
			'default' => $invoice->get_sender_note(),
			'description' => si__('This note will be added to the Invoice Notification via the [admin_note] shortcode.')
		);

		$fields['doc_id'] = array(
			'type' => 'hidden',
			'value' => $invoice->get_id(),
			'weight' => 10000
		);

		$fields['notification_nonce'] = array(
			'type' => 'hidden',
			'value' => wp_create_nonce( SI_Controller::NONCE ),
			'weight' => 10001
		);

		$fields = apply_filters( 'si_sender_submission_fields', $fields );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	/**
	 * Save the sender's note.
	 * @param  int $post_id       
	 * @param  object $post          
	 * @param  array $callback_args 
	 * @param  int $invoice_id   
	 * @return                 
	 */
	public static function save_invoice_note( $post_id, $post, $callback_args, $invoice_id = null ) {
		$invoice = SI_Invoice::get_instance( $post_id );

		$sender_notes = ( isset( $_POST['sender_notes'] ) && $_POST['sender_notes'] !== '' ) ? $_POST['sender_notes'] : '' ;
		if ( $sender_notes === '' ) { // check to make sure the sender note option wasn't updated for the send.
			$sender_notes = ( isset( $_POST['sa_send_metabox_sender_note'] ) && $_POST['sa_send_metabox_sender_note'] !== '' ) ? $_POST['sa_send_metabox_sender_note'] : '' ;
		}
		$invoice->set_sender_note( $sender_notes );

		if ( !isset( $_REQUEST['sa_metabox_recipients'] ) || empty( $_REQUEST['sa_metabox_recipients'] ) ) {
			return;
		}
		do_action( 'send_invoice', $invoice, $_REQUEST['sa_metabox_recipients'] );
	}

	/**
	 * Show the invoice history, including the submission fields
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_submission_history_view( $post, $metabox ) {
		if ( $post->post_status == 'auto-draft' ) {
			printf( '<p>%s</p>', si__( 'No history available.' ) );
			return;
		}
		$invoice = SI_Invoice::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/invoices/history', array(
				'id' => $post->ID,
				'post' => $post,
				'invoice' => $invoice,
				'history' => si_doc_history_records( $post->ID, false ),
				'submission_fields' => $invoice->get_submission_fields(),
			), false );
	}

	/**
	 * Show terms and notes
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_notes_view( $post, $metabox ) {
		$invoice = SI_Invoice::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/invoices/notes', array(
				'id' => $post->ID,
				'post' => $post,
				'invoice' => $invoice,
				'terms' => $invoice->get_terms(),
				'notes' => $invoice->get_notes(),
			), false );
	}

	/**
	 * Saving line items first thing since totals are calculated later based on other options.
	 * @param  int $post_id       
	 * @param  object $post          
	 * @param  array $callback_args 
	 * @param  int $invoice_id   
	 * @return                 
	 */
	public static function save_notes( $post_id, $post, $callback_args, $invoice_id = null ) {
		$invoice = SI_Invoice::get_instance( $post_id );

		$invoice_terms = ( isset( $_POST['invoice_terms'] ) && $_POST['invoice_terms'] != '' ) ? $_POST['invoice_terms'] : '' ;
		$invoice_notes = ( isset( $_POST['invoice_notes'] ) && $_POST['invoice_notes'] != '' ) ? $_POST['invoice_notes'] : '' ;

		$invoice->set_terms( $invoice_terms );
		$invoice->set_notes( $invoice_notes );

	}


	////////////////////
	// Admin Columns //
	////////////////////

	/**
	 * Overload the columns for the invoice post type admin
	 *
	 * @param array   $columns
	 * @return array
	 */
	public static function register_columns( $columns ) {
		// Remove all default columns
		unset( $columns['date'] );
		unset( $columns['title'] );
		unset( $columns['comments'] );
		unset( $columns['author'] );
		$columns['title'] = self::__( 'Invoice' );
		$columns['status'] = self::__( 'Status' );
		$columns['total'] = self::__( 'Paid' );
		$columns['client'] = self::__( 'Client' );
		$columns['doc_link'] = '<div class="dashicons icon-sproutapps-estimates"></div>';
		return $columns;
	}

	/**
	 * Display the content for the column
	 *
	 * @param string  $column_name
	 * @param int     $id          post_id
	 * @return string
	 */
	public static function column_display( $column_name, $id ) {
		$invoice = SI_Invoice::get_instance( $id );

		if ( !is_a( $invoice, 'SI_Invoice' ) )
			return; // return for that temp post

		switch ( $column_name ) {
		case 'doc_link':
			$estimate_id = $invoice->get_estimate_id();
			if ( $estimate_id ) {
				printf( '<a class="doc_link" title="%s" href="%s">%s</a>', self::__( 'Invoice\'s Estimate' ), get_edit_post_link( $estimate_id ), '<div class="dashicons icon-sproutapps-estimates"></div>' );
			}
			break;
		case 'status': 
			
			self::status_change_dropdown( $id );

			break;

		case 'total':
			printf( '%s <span class="description">(%s %s)</span>', sa_get_formatted_money( $invoice->get_payments_total() ), self::__('of'), sa_get_formatted_money( $invoice->get_total(), $invoice->get_id() ) );

			echo '<div class="row-actions">';
			printf( '<a class="payments_link" title="%s" href="%s&s=%s">%s</a>', self::__( 'Review payments.' ), get_admin_url( '','/edit.php?post_type=sa_invoice&page=sprout-apps/invoice_payments' ), $id, self::__( 'Payments' ) );

			break;

		case 'client':
			if ( $invoice->get_client_id() ) {
				$client = $invoice->get_client();
				printf( '<b><a href="%s">%s</a></b><br/><em>%s</em>', get_edit_post_link( $client->get_ID() ), get_the_title( $client->get_ID() ), $client->get_website() );
			}
			else {
				printf( '<b>%s</b> ', si__('No client') );
			}

			break;

		default:
			// code...
			break;
		}

	}

	/**
	 * Filter sortable columns and make total column sortable
	 *
	 * @param array   $columns
	 * @return array
	 */
	public static function sortable_columns( $columns ) {
		$columns['total'] = 'total';
		return $columns;
	}

	/**
	 * Filter the array of row action links below the title.
	 *
	 * @param array   $actions An array of row action links.
	 * @param WP_Post $post    The post object.
	 */
	public static function modify_row_actions( $actions = array(), $post = array() ) {
		if ( $post->post_type == SI_Invoice::POST_TYPE ) {
			unset( $actions['trash'] );
			// remove quick edit
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}

	/**
	 * Filter the list table Bulk Actions drop-down.
	 *
	 * @param array   $actions An array of the available bulk actions.
	 */
	public static function modify_bulk_actions( $actions = array() ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Filter the strings on the taxonomy edit pages.
	 *
	 * @param array  $views
	 * @return array
	 */
	public static function filter_status_view( $views = array() ) {
		if ( isset( $views['publish'] ) ) {
			$views['publish'] = str_replace( 'Published', 'Pending', $views['publish'] );
		}
		return $views;
	}

	/**
	 * Filter the default post display states used in the Posts list table.
	 *
	 * @param array $post_states An array of post display states. Values include 'Password protected',
	 *                           'Private', 'Draft', 'Pending', and 'Sticky'.
	 * @param int   $post        The post.
	 */
	public static function filter_post_states( $post_states = array(), WP_Post $post )	{
		if ( get_post_type( $post ) == SI_Invoice::POST_TYPE ) {
			$post_states = array();
			$invoice = SI_Invoice::get_instance( $post->ID );
			if ( $invoice->get_status() == SI_Invoice::STATUS_TEMP ) {
				// FUTURE show "New" with some sort of logic
				// $post_states[$invoice->get_status()] = si__('New');
			}
		}
		return $post_states;
	}

	///////////////////////////
	// single column layout //
	///////////////////////////

	/**
	 * set the layout to a single column if no preference is set.
	 *
	 * @param string  $option
	 * @return string
	 */
	public static function screen_layout_pref( $option = '' ) {
		if ( $option == '' ) {
			return 1;
		}
		return $option;
	}

	/**
	 * Filter layout columns
	 *
	 * @param array   $columns
	 * @return array
	 */
	public static function screen_layout_columns( $columns = array(), $screen = '' ) {
		if ( $screen == SI_Invoice::POST_TYPE ) {
			$columns['post'] = 1;
		}
		return $columns;
	}


	/////////////////
	// Templating //
	/////////////////


	/**
	 * Remove all actions to wp_print_scripts since stupid themes (and plugins) want to use it as a 
	 * hook to enqueue scripts and plugins. Ideally we would live in a world where this wasn't necessary
	 * but it is. 
	 * @return
	 */
	public static function remove_scripts_and_styles_from_stupid_themes_and_plugins() {
		if ( SI_Invoice::is_invoice_query() && is_single() ) {
			if ( apply_filters( 'si_remove_scripts_styles_on_doc_pages', '__return_true' ) ) {
				remove_all_actions( 'wp_print_scripts' );
			}
		}
	}

	/**
	 * Remove all scripts and styles from the estimate view and then add those specific to si.
	 * @return  
	 */
	public static function remove_scripts_and_styles() {
		if ( SI_Invoice::is_invoice_query() && is_single() ) {
			if ( apply_filters( 'si_remove_scripts_styles_on_doc_pages', '__return_true' ) ) {
				global $wp_scripts, $wp_styles;
				$allowed_scripts = apply_filters( 'si_allowed_doc_scripts', array( 'sprout_doc_scripts', 'qtip', 'dropdown' ) );
				$allowed_admin_scripts = apply_filters( 'si_allowed_admin_doc_scripts', array_merge( array( 'admin-bar' ), $allowed_scripts ) );
				if ( current_user_can( 'edit_posts' ) ) {
					$wp_scripts->queue = $allowed_admin_scripts;
				}
				else {
					$wp_scripts->queue = $allowed_scripts;
				}
				$allowed_styles = apply_filters( 'si_allowed_doc_styles', array( 'sprout_doc_style', 'qtip', 'dropdown' ) );
				$allowed_admin_styles = apply_filters( 'si_allowed_admin_doc_styles', array_merge( array( 'admin-bar' ), $allowed_styles ) );
				if ( current_user_can( 'edit_posts' ) ) {
					$wp_styles->queue = $allowed_admin_styles;
				}
				else {
					$wp_styles->queue = $allowed_styles;
				}
				do_action( 'si_doc_enqueue_filtered' );
			}
			else {
				// scripts
				wp_enqueue_script( 'sprout_doc_scripts' );
				wp_enqueue_script( 'dropdown' );
				wp_enqueue_script( 'qtip' );
				// Styles
				wp_enqueue_style( 'sprout_doc_style' );
				wp_enqueue_style( 'dropdown' );
				wp_enqueue_style( 'qtip' );
			}
			
		}
	}



	/////////////////////
	// AJAX Callbacks //
	/////////////////////

	public static function maybe_send_notification() {
		// form maybe be serialized
		if ( isset( $_REQUEST['serialized_fields'] ) ) {
			foreach ( $_REQUEST['serialized_fields'] as $key => $data ) {
				if ( strpos( $data['name'], '[]' ) !== false ) {
					$_REQUEST[str_replace('[]', '', $data['name'])][] = $data['value'];
				}
				else {
					$_REQUEST[$data['name']] = $data['value'];
				}
			}
		}
		if ( !isset( $_REQUEST['sa_send_metabox_notification_nonce'] ) )
			self::ajax_fail( 'Forget something (nonce)?' );

		$nonce = $_REQUEST['sa_send_metabox_notification_nonce'];
		if ( !wp_verify_nonce( $nonce, SI_Controller::NONCE ) )
			self::ajax_fail( 'Not going to fall for it!' );

		if ( !isset( $_REQUEST['sa_send_metabox_doc_id'] ) )
			self::ajax_fail( 'Forget something (id)?' );

		if ( !isset( $_REQUEST['sa_metabox_recipients'] ) || empty( $_REQUEST['sa_metabox_recipients'] ) )
			self::ajax_fail( 'A recipient is required.' );

		if ( get_post_type( $_REQUEST['sa_send_metabox_doc_id'] ) != SI_Invoice::POST_TYPE ) {
			return;
		}

		$invoice = SI_Invoice::get_instance( $_REQUEST['sa_send_metabox_doc_id'] );
		$invoice->set_sender_note( $_REQUEST['sa_send_metabox_sender_note'] );
		do_action( 'send_invoice', $invoice, $_REQUEST['sa_metabox_recipients'] );

		// If status is temp than change to pending.
		if ( $invoice->get_status() == SI_Invoice::STATUS_TEMP ) {
			$invoice->set_pending();
		}

		header( 'Content-type: application/json' );
		if ( self::DEBUG ) header( 'Access-Control-Allow-Origin: *' );
		echo wp_json_encode( array( 'response' => si__('Notification Queued') ) );
		exit();
	}



	/////////////////////
	// Record Keeping //
	/////////////////////

	/**
	 * Maybe create a status update record
	 * @param  SI_Estimate $estimate        
	 * @param  string      $status          
	 * @param  string      $original_status 
	 * @return null                       
	 */
	public static function maybe_create_status_update_record( SI_Invoice $invoice, $status = '', $original_status = '' ) {
		do_action( 'si_new_record',
			sprintf( si__('Status changed: %s to <b>%s</b>.'), SI_Invoice::get_status_label( $original_status ), SI_Invoice::get_status_label( $status ) ),
			self::HISTORY_STATUS_UPDATE, 
			$invoice->get_id(), 
			sprintf( si__('Status update for %s.'), $invoice->get_id() ), 
			0, 
			false );
	}

	////////////
	// Misc. //
	////////////

	public static function change_status_after_payment( SI_Payment $payment ) {
		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance( $invoice_id );
		// If the invoice has a balance the status should be changed to partial.
		if ( $invoice->get_balance() >= 0.01 ) { 
			$invoice->set_as_partial();
		}
		else { // else there's no balance
			$invoice->set_as_paid();
		}
	}

	/**
	 * Create invoice when estimate is accepted.
	 * @param  object $doc estimate or invoice object
	 * @return int cloned invoice id.
	 */
	public static function create_payment_when_invoice_marked_as_paid( $doc ) {
		if ( !is_a( $doc, 'SI_Invoice' ) ) {
			return;
		}
		// Check if status changed was to approved.
		if ( $doc->get_status() != SI_Invoice::STATUS_PAID ) {
			return;
		}
		$balance = $doc->get_balance();
		if ( $balance < 0.01 ) {
			return;
		}
		SI_Admin_Payment::create_admin_payment( $doc->get_id(), $balance, '', 'Now', self::__('This payment was automatically added to settle the balance after it was marked as "Paid".') );
	}

	/**
	 * Create invoice when estimate is accepted.
	 * @param  object $doc estimate or invoice object
	 * @return int cloned invoice id.
	 */
	public static function create_invoice_on_est_acceptance( $doc ) {
		if ( !is_a( $doc, 'SI_Estimate' ) ) {
			return;
		}
		// Check if status changed was to approved.
		if ( $doc->get_status() != SI_Estimate::STATUS_APPROVED ) {
			return;
		}
		$invoice_post_id = self::clone_post( $doc->get_id(), SI_Invoice::STATUS_TEMP, SI_Invoice::POST_TYPE );
		$invoice = SI_Invoice::get_instance( $invoice_post_id );
		$invoice->set_sender_note();
		return $invoice_post_id;
	}

	/**
	 * Associate a newly cloned invoice with the estimate cloned from
	 * @param  integer $new_post_id   
	 * @param  integer $cloned_post_id       
	 * @param  string  $new_post_type 
	 * @return                  
	 */
	public static function associate_invoice_after_clone( $new_post_id = 0, $cloned_post_id = 0, $new_post_type = '' ) {
		if ( get_post_type( $cloned_post_id ) == SI_Estimate::POST_TYPE ) {
			if ( $new_post_type == SI_Invoice::POST_TYPE ) {
				$invoice = SI_Invoice::get_instance( $new_post_id );
				$invoice->set_estimate_id( $cloned_post_id );
				$invoice->set_as_temp();
			}
		}
	}

	/**
	 * Adjust the invoice id
	 * @param  integer $new_post_id   
	 * @param  integer $cloned_post_id       
	 * @param  string  $new_post_type 
	 * @return                  
	 */
	public static function adjust_cloned_invoice( $new_post_id = 0, $cloned_post_id = 0, $new_post_type = '' ) {
		if ( get_post_type( $cloned_post_id ) == SI_Estimate::POST_TYPE ) {
			$estimate = SI_Estimate::get_instance( $cloned_post_id );
			$est_id = $estimate->get_invoice_id();
			$invoice = SI_Invoice::get_instance( $new_post_id );

			// Adjust invoice id
			$new_id = apply_filters( 'si_adjust_cloned_invoice_id', $est_id . '-' . $new_post_id, $new_post_id, $cloned_post_id );
			$invoice->set_invoice_id( $new_id );

			// Adjust status
			$invoice->set_as_temp();
		}
	}

	/**
	 * Filter the unique post slug.
	 *
	 * @param string $slug          The post slug.
	 * @param int    $post_ID       Post ID.
	 * @param string $post_status   The post status.
	 * @param string $post_type     Post type.
	 * @param int    $post_parent   Post parent ID
	 * @param string $original_slug The original post slug.
	 */
	public static function post_slug( $slug, $post_ID, $post_status, $post_type ) {
		if ( $post_type == SI_Invoice::POST_TYPE ) {
			return $post_ID;
		}
		return $slug;
	}

	public static function maybe_remove_deposit( SI_Payment $payment, SI_Checkouts $checkout ) {
		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance($invoice_id);
		$payment_amount = $payment->get_amount();
		$invoice_deposit = $invoice->get_deposit();
		if ( $payment_amount >= $invoice_deposit ) {
			// Reset the deposit since the payment made covers it.
			$invoice->set_deposit('');
		}
	}

	///////////////////
	// Set Defaults //
	///////////////////

	/**
	 * Set the default invoice terms based on current status. The idea is that anything edited will have a different status.
	 *
	 * @param string  $terms
	 * @param SI_Invoice $invoice
	 * @return string
	 */
	public static function maybe_set_invoice_terms( $terms = '', SI_Invoice $invoice ) {
		if ( !in_array( $invoice->get_status(), array( SI_Invoice::STATUS_TEMP, SI_Invoice::STATUS_PENDING ) ) ) {
			if ( $terms == '' ) {
				$terms = self::get_default_terms();
			}
		}
		return $terms;
	}

	/**
	 * Set the default invoice notes based on current status. The idea is that anything edited will have a different status.
	 *
	 * @param string  $terms
	 * @param SI_Invoice $invoice
	 * @return string
	 */
	public static function maybe_set_invoice_notes( $notes = '', SI_Invoice $invoice ) {
		if ( !in_array( $invoice->get_status(), array( SI_Invoice::STATUS_TEMP, SI_Invoice::STATUS_PENDING ) ) ) {
			if ( $notes == '' ) {
				$notes = self::get_default_notes();
			}
		}
		return $notes;
	}

	//////////////
	// Utility //
	//////////////

	public static function line_item_content_filter( $description = '' ) {
		if ( apply_filters( 'si_the_content_filter_line_item_descriptions', true ) ) {
			$content = apply_filters( 'the_content', $description );
		}
		else {
			$content = wpautop( $description );
		}
		return $content;
	}

	public static function is_edit_screen() {
		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( $screen_post_type == SI_Invoice::POST_TYPE ) {
			return true;
		}
		return false;
	}

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'edit_invoices',
			'title' => self::__( 'Invoices' ),
			'href' => admin_url( 'edit.php?post_type='.SI_Invoice::POST_TYPE ),
			'weight' => 0,
		);
		return $items;
	}

	public static function status_change_dropdown( $id ) {
		if ( !$id ) {
			global $post;
			$id = $post->ID;
		}
		$invoice = SI_Invoice::get_instance( $id );

		if ( !is_a( $invoice, 'SI_Invoice' ) )
			return; // return for that temp post

		self::load_view( 'admin/sections/invoice-status-change-drop', array(
				'id' => $id,
				'status' => $invoice->get_status()
			), false );

	}

	////////////////
	// Admin Help //
	////////////////

	public static function help_sections() {
		add_action( 'load-edit.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-post.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-post-new.php', array( get_class(), 'help_tabs' ) );
	}

	public static function help_tabs() {
		$post_type = '';
		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( $screen_post_type == SI_Invoice::POST_TYPE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
					'id' => 'manage-invoices',
					'title' => self::__( 'Manage Invoices' ),
					'content' => sprintf( '<p>%s</p><p>%s</p><p>%s</p>', self::__('The status on the invoice table view can be updated without having to go the edit screen by click on the current status and selecting a new one.'), self::__('Payments are tallied and shown in the Paid column. Hovering over the invoice row will show a Payments link.'),  self::__('If the invoice has an associated estimate the icon linking to the edit page of the estimate will show in the last column.') )
				) );

			$screen->add_help_tab( array(
					'id' => 'edit-invoices',
					'title' => self::__( 'Editing Invoices' ),
					'content' => sprintf( '<p>%s</p><p><a href="%s">%s</a></p>', self::__('Editing invoices is intentionally easy to do but a review here would exhaust this limited space. Please review the knowledgeable for a complete overview.'), 'https://sproutapps.co/support/knowledgebase/sprout-invoices/invoices/', self::__('Knowledgebase Article') ),
				) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', self::__('For more information:') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/invoices/', self::__('Documentation') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', self::__('Support') )
			);
		}
	}

}