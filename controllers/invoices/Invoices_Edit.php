<?php


/**
 * Invoices Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Invoices
 */
class SI_Invoices_Edit extends SI_Invoices {
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

		if ( is_admin() ) {

			// Title update to subject
			add_filter( 'wp_insert_post_data', array( __CLASS__, 'update_post_data' ), 100, 2 );

			// Meta boxes
			add_action( 'admin_init', array( __CLASS__, 'register_meta_boxes' ), 100 );
			add_action( 'do_meta_boxes', array( __CLASS__, 'modify_meta_boxes' ), 100 );
			add_action( 'edit_form_top', array( __CLASS__, 'quick_links' ), 100 );

			// Single column
			add_filter( 'get_user_option_screen_layout_sa_invoice', array( __CLASS__, 'screen_layout_pref' ) );
			add_filter( 'screen_layout_columns', array( __CLASS__, 'screen_layout_columns' ) );
		}

		// Rewrite rules
		add_filter( 'si_register_post_type_args-'.SI_Invoice::POST_TYPE, array( __CLASS__, 'modify_post_type_slug' ) );

		// Set the default terms and notes
		add_filter( 'get_invoice_terms', array( __CLASS__, 'maybe_set_invoice_terms' ), 10, 2 );
		add_filter( 'get_invoice_notes', array( __CLASS__, 'maybe_set_invoice_notes' ), 10, 2 );
	}

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			'si_invoice_settings' => array(
				'title' => __( 'Invoice Settings', 'sprout-invoices' ),
				'weight' => 20,
				'tab' => 'settings',
				'settings' => array(
					self::SLUG_OPTION => array(
						'label' => __( 'Invoice Permalink Slug', 'sprout-invoices' ),
						'sanitize_callback' => array( __CLASS__, 'sanitize_slug_option' ),
						'option' => array(
							'type' => 'text',
							'attributes' => array(
								'class' => 'medium-text',
							),
							'default' => self::$invoices_slug,
							'description' => sprintf( __( 'Example invoice url: %s/%s/045b41dd14ab8507d80a27b7357630a5/', 'sprout-invoices' ), site_url(), '<strong>'.self::$invoices_slug.'</strong>' ),
						),

					),
					self::TERMS_OPTION => array(
						'label' => __( 'Default Terms', 'sprout-invoices' ),
						'option' => array(
							'type' => 'wysiwyg',
							'default' => self::$default_terms,
							'description' => __( 'These are the default terms for an invoice.', 'sprout-invoices' ),
						),

					),
					self::NOTES_OPTION => array(
						'label' => __( 'Default Note', 'sprout-invoices' ),
						'option' => array(
							'type' => 'wysiwyg',
							'default' => self::$default_notes,
							'description' => __( 'These are the default notes public to a client reviewing their invoice.', 'sprout-invoices' ),
						),
					),
				),
			),
		);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );
	}

	public static function get_default_terms() {
		return self::$default_terms;
	}

	public static function get_default_notes() {
		return self::$default_notes;
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


	public static function update_post_data( $data = array(), $post = array() ) {
		if ( SI_Invoice::POST_TYPE === $post['post_type'] ) {
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
	 * Pre-register post type arguments
	 * @param  array  $args
	 * @return array
	 */
	public static function modify_post_type_slug( $args = array() ) {
		$args['rewrite']['slug'] = self::$invoices_slug;
		return $args;
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
		if ( '' == $option ) {
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
		if ( SI_Invoice::POST_TYPE === $screen ) {
			$columns['post'] = 1;
		}
		return $columns;
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
				'title' => __( 'Management', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_line_items_view' ),
				'save_callback' => array( __CLASS__, 'save_line_items' ),
				'context' => 'normal',
				'priority' => 'high',
				'weight' => 0,
				'save_priority' => 5,
			),
			'si_invoice_update' => array(
				'title' => __( 'Information', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_information_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_meta_box_invoice_information' ),
				'context' => 'normal',
				'priority' => 'high',
				'weight' => 20,
				'save_priority' => 50,
			),
			'si_doc_send' => array(
				'title' => __( 'Send Invoice', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_invoice_send_view' ),
				'save_callback' => array( __CLASS__, 'save_invoice_note' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 30,
				'save_priority' => 500,
			),
			'si_invoice_history' => array(
				'title' => __( 'Invoice History', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_submission_history_view' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 20,
			),
			'si_invoice_notes' => array(
				'title' => __( 'Terms & Notes', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_notes_view' ),
				'save_callback' => array( __CLASS__, 'save_notes' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 100,
			),
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
		if ( get_post_type( $post ) === SI_Invoice::POST_TYPE ) {
			$invoice = SI_Invoice::get_instance( $post->ID );
			$status = ( is_a( $invoice, 'SI_Invoice' ) && $invoice->get_status() !== 'auto-draft' ) ? $invoice->get_status() : SI_Invoice::STATUS_TEMP ;
			self::load_view( 'admin/meta-boxes/invoices/quick-links', array(
					'id' => $post->ID,
					'post' => $post,
					'status' => $status,
					'statuses' => SI_Invoice::get_statuses(),
					'invoice' => $invoice,
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
				'line_items' => $line_items,
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
		if ( ! isset( $_POST['line_item_key'] ) ) {
			return;
		}

		$invoice = SI_Invoice::get_instance( $post_id );
		$line_items = array();
		// The line_item_key sends the order of each item so they can be linked with the other options
		foreach ( $_POST['line_item_key'] as $key => $order ) {
			// make sure there's a description, otherwise it's not an item.
			if ( isset( $_POST['line_item_desc'][ $key ] ) && '' !== $_POST['line_item_desc'][ $key ] ) {
				// loop through all post values...
				foreach ( $_POST as $pkey => $value ) {
					// if the post value starts with line_item_ than it's something that should be stored with the line item
					if ( preg_match( '/line_item_([a-zA-Z0-9_ ]*)/', $pkey, $match ) === 1 ) {
						// the slug/name of the data
						$data_id = $match[1];
						// add the value of the post, associated with the key given in the parent loop.
						if ( ! is_array( $value ) ) {
							continue;
						}
						$line_items[ $order ][ $data_id ] = ( '' !== $value[ $key ] ) ? $value[ $key ] : '' ;
					}
				}
			}
		}

		// Set the line items meta
		$invoice->set_line_items( $line_items );

		do_action( 'si_save_line_items_meta_box', $post_id, $post, $invoice );
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
			'fields' => 'ids',
		);
		$clients = get_posts( apply_filters( 'si_clients_select_get_posts_args', $args ) );
		$client_options = array();
		foreach ( $clients as $client_id ) {
			$client_options[ $client_id ] = get_the_title( $client_id );
		}

		$invoice = SI_Invoice::get_instance( $post->ID );
		$status = ( is_a( $invoice, 'SI_Invoice' ) && $invoice->get_status() !== 'auto-draft' ) ? $invoice->get_status() : SI_Invoice::STATUS_TEMP ;
		$due_date = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_due_date() : current_time( 'timestamp' ) + (DAY_IN_SECONDS * 30);
		$expiration_date = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_expiration_date() : current_time( 'timestamp' ) + (DAY_IN_SECONDS * 30);
		$issue_date = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_issue_date() : strtotime( $post->post_date );
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

		do_action( 'invoice_meta_saved', $invoice );

		$user = get_userdata( get_current_user_id() );
		do_action( 'si_new_record',
			sprintf( __( 'Invoice updated by %s.', 'sprout-invoices' ), $user->display_name ),
			self::HISTORY_UPDATE,
			$invoice->get_id(),
			sprintf( __( 'Data updated for %s.', 'sprout-invoices' ), $invoice->get_id() ),
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
			printf( '<p>%s</p>', __( 'Save this invoice before sending.', 'sprout-invoices' ) );
			return;
		}
		$invoice = SI_Invoice::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/invoices/send', array(
				'id' => $post->ID,
				'post' => $post,
				'invoice' => $invoice,
				'fields' => self::sender_submission_fields( $invoice ),
				'sender_notes' => $invoice->get_sender_note(),
		), false );
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

		$recipients = ( isset( $_REQUEST['sa_metabox_recipients'] ) ) ? $_REQUEST['sa_metabox_recipients'] : array();

		if ( isset( $_REQUEST['sa_metabox_custom_recipient'] ) && '' !== trim( $_REQUEST['sa_metabox_custom_recipient'] ) ) {
			$submitted_recipients = explode( ',', trim( $_REQUEST['sa_metabox_custom_recipient'] ) );
			foreach ( $submitted_recipients as $key => $email ) {
				$email = trim( $email );
				if ( is_email( $email ) ) {
					$recipients[] = $email;
				}
			}
		}

		if ( empty( $recipients ) ) {
			return;
		}

		$from_email = null;
		$from_name = null;
		if ( isset( $_REQUEST['sa_send_metabox_send_as'] ) ) {
			$name_and_email = SI_Notifications_Control::email_split( $_REQUEST['sa_send_metabox_send_as'] );
			if ( is_email( $name_and_email['email'] ) ) {
				$from_name = $name_and_email['name'];
				$from_email = $name_and_email['email'];
			}
		}

		do_action( 'send_invoice', $invoice, $recipients, $from_email, $from_name );
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
			printf( '<p>%s</p>', __( 'No history available.', 'sprout-invoices' ) );
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

	/////////////
	// Utility //
	/////////////



	public static function sender_submission_fields( SI_Invoice $invoice ) {
		$fields = array();

		$from_name = SI_Notifications_Control::from_name( array( 'invoice_id' => $invoice->get_id() ) );
		$from_email = SI_Notifications_Control::from_email( array( 'invoice_id' => $invoice->get_id() ) );
		$fields['send_as'] = array(
			'weight' => 1,
			'label' => __( 'Sender', 'sprout-invoices' ),
			'type' => 'text',
			'placeholder' => '',
			'attributes' => array( 'readonly' => 'readonly' ),
			'default' => $from_name . ' <' . $from_email . '>',
		);

		// options for recipients
		$client = $invoice->get_client();

		$recipient_options = '<div class="form-group"><div class="input_wrap">';

			// client users
		if ( is_a( $client, 'SI_Client' ) ) {
			$client_users = $client->get_associated_users();
			foreach ( $client_users as $user_id ) {
				if ( ! is_wp_error( $user_id ) ) {
					$recipient_options .= sprintf( '<label class="clearfix"><input type="checkbox" name="sa_metabox_recipients[]" value="%1$s"> %2$s</label>', $user_id, esc_attr( SI_Notifications::get_user_email( $user_id ) ) );
				}
			}
		}

		$recipient_options .= sprintf( '<label class="clearfix"><input type="checkbox" name="sa_metabox_custom_recipient_check" disabled="disabled"><input type="text" name="sa_metabox_custom_recipient" placeholder="%1$s"><span class="helptip" title="%2$s"></span></label>', __( 'client@email.com', 'sprout-invoices' ), __( 'Entering an email will prevent some notification shortcodes from working, since it may not be assigned to a client.', 'sprout-invoices' ) );

		// Send to me.
		$recipient_options .= sprintf( '<label class="clearfix"><input type="checkbox" name="sa_metabox_recipients[]" value="%1$s"> %2$s</label>', get_current_user_id(), __( 'Send me a copy', 'sprout-invoices' ) );

		$recipient_options .= '</div></div>';

		$fields['recipients'] = array(
			'weight' => 5,
			'label' => sprintf( '%s <span class="helptip" title="%s"></span>', __( 'Recipients', 'sprout-invoices' ), __( 'A notification will be sent if recipients are selected and this invoice is saved.', 'sprout-invoices' ) ),
			'type' => 'bypass',
			'output' => $recipient_options,
		);

		$fields['sender_note'] = array(
			'weight' => 10,
			'label' => __( 'Note', 'sprout-invoices' ),
			'type' => 'textarea',
			'default' => $invoice->get_sender_note(),
			'description' => __( 'This note will be added to the Invoice Notification via the [admin_note] shortcode.', 'sprout-invoices' ),
		);

		$fields['doc_id'] = array(
			'type' => 'hidden',
			'value' => $invoice->get_id(),
			'weight' => 10000,
		);

		$fields['notification_nonce'] = array(
			'type' => 'hidden',
			'value' => wp_create_nonce( SI_Controller::NONCE ),
			'weight' => 10001,
		);

		$fields = apply_filters( 'si_sender_submission_fields', $fields );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
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
		if ( ! in_array( $invoice->get_status(), array( SI_Invoice::STATUS_TEMP, SI_Invoice::STATUS_PENDING ) ) ) {
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
		if ( ! in_array( $invoice->get_status(), array( SI_Invoice::STATUS_TEMP, SI_Invoice::STATUS_PENDING ) ) ) {
			if ( $notes == '' ) {
				$notes = self::get_default_notes();
			}
		}
		return $notes;
	}
}
