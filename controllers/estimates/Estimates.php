<?php

/**
 * Estimates Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Estimates
 */
class SI_Estimates extends SI_Controller {
	const SETTINGS_PAGE = 'estimates';
	const HISTORY_UPDATE = 'si_history_update';
	const HISTORY_STATUS_UPDATE = 'si_history_status_update';
	const HISTORY_INVOICE_CREATED = 'si_invoice_created';
	const VIEWED_STATUS_UPDATE = 'si_viewed_status_update';
	const TERMS_OPTION = 'si_default_estimate_terms';
	const NOTES_OPTION = 'si_default_estimate_notes';
	const SLUG_OPTION = 'si_estimates_perma_slug';
	private static $default_terms;
	private static $default_notes;
	private static $estimates_slug;

	public static function init() {

		// Settings
		self::$default_terms = get_option( self::TERMS_OPTION, 'We do expect payment within 21 days, so please process this invoice within that time. There will be a 1.5% interest charge per month on late invoices.' );
		self::$default_notes = get_option( self::NOTES_OPTION, 'Thank you; we really appreciate your business.' );
		self::$estimates_slug = get_option( self::SLUG_OPTION, SI_Estimate::REWRITE_SLUG );

		self::register_settings();

		// Help Sections
		add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

		if ( is_admin() ) {
			// Admin columns
			add_filter( 'manage_edit-'.SI_Estimate::POST_TYPE.'_columns', array( __CLASS__, 'register_columns' ) );
			add_filter( 'manage_'.SI_Estimate::POST_TYPE.'_posts_custom_column', array( __CLASS__, 'column_display' ), 10, 2 );
			add_filter( 'manage_edit-'.SI_Estimate::POST_TYPE.'_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
			add_filter( 'views_edit-sa_estimate', array( __CLASS__, 'filter_status_view' ) );
			add_filter( 'display_post_states', array( __CLASS__, 'filter_post_states' ), 10, 2 );

			// Remove quick edit from admin and add some row actions
			add_action( 'bulk_actions-edit-sa_estimate', array( __CLASS__, 'modify_bulk_actions' ) );
			add_action( 'post_row_actions', array( __CLASS__, 'modify_row_actions' ), 10, 2 );

			// Meta boxes
			add_action( 'admin_init', array( __CLASS__, 'register_meta_boxes' ), 100 );
			add_action( 'do_meta_boxes', array( __CLASS__, 'modify_meta_boxes' ), 100 );
			add_action( 'edit_form_top', array( __CLASS__, 'quick_links' ), 100 );

			// Single column
			add_filter( 'get_user_option_screen_layout_sa_estimate', array( __CLASS__, 'screen_layout_pref' ) );
			add_filter( 'screen_layout_columns', array( __CLASS__, 'screen_layout_columns' ) );
		}

		// Unique urls
		add_filter( 'wp_unique_post_slug', array( __CLASS__, 'post_slug'), 10, 4 );

		// Templating
		// add_action( 'wp_enqueue_scripts', array( __CLASS__, 'remove_scripts_and_styles' ), PHP_INT_MAX );
		add_action( 'wp_print_scripts', array( __CLASS__, 'remove_scripts_and_styles' ), PHP_INT_MAX ); // can't rely on themes to abide by enqueing correctly

		// Status updates
		add_action( 'si_estimate_status_updated',  array( __CLASS__, 'maybe_create_status_update_record' ), 10, 3 );

		// Mark estimate viewed
		add_action( 'estimate_viewed',  array( __CLASS__, 'maybe_log_estimate_view' ) );

		// Record when invoice is created
		add_action( 'si_cloned_post',  array( __CLASS__, 'create_record_of_cloned_invoice' ), 10, 3 );

		// Notifications
		add_filter( 'wp_ajax_sa_send_est_notification', array( __CLASS__, 'maybe_send_notification' ) );

		// Set the default terms and notes
		add_filter( 'get_estimate_terms', array( __CLASS__, 'maybe_set_estimate_terms' ), 10, 2 );
		add_filter( 'get_estimate_notes', array( __CLASS__, 'maybe_set_estimate_notes' ), 10, 2 );

		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );

		// Rewrite rules
		add_filter( 'si_register_post_type_args-'.SI_Estimate::POST_TYPE, array( __CLASS__, 'modify_post_type_slug' ) );
	}

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			'si_estimate_settings' => array(
				'title' => 'Estimate Settings',
				'weight' => 25,
				'tab' => 'settings',
				'settings' => array(
					self::SLUG_OPTION => array(
						'label' => self::__( 'Estimate Permalink Slug' ),
						'sanitize_callback' => array( __CLASS__, 'sanitize_slug_option' ),
						'option' => array(
							'type' => 'text',
							'attributes' => array(
								'class' => 'medium-text',
							),
							'default' => self::$estimates_slug,
							'description' => sprintf( self::__( 'Example estimate url: %s/%s/045b41dd14ab8507d80a27b7357630a5/' ), site_url(), '<strong>'.self::$estimates_slug.'</strong>' )
						),
						
					),
					self::TERMS_OPTION => array(
						'label' => self::__( 'Default Terms' ),
						'option' => array(
							'type' => 'wysiwyg',
							'default' => self::$default_terms,
							'description' => self::__( 'These are the default terms for an estimate.' )
						),
					),
					self::NOTES_OPTION => array(
						'label' => self::__( 'Default Note' ),
						'option' => array(
							'type' => 'wysiwyg',
							'default' => self::$default_notes,
							'description' => self::__( 'These are the default notes public to a client reviewing their estimate.' )
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

	////////////////////
	// rewrite slugs //
	////////////////////

	/**
	 * PRe-register post type arguments
	 * @param  array  $args 
	 * @return array       
	 */
	public static function modify_post_type_slug( $args = array() ) {
		$args['rewrite']['slug'] = self::$estimates_slug;
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
			$option = SI_Estimate::REWRITE_SLUG;
		}
		return sanitize_title_with_dashes( $option );
	}


	/////////////////
	// Meta boxes //
	/////////////////

	/**
	 * Regsiter meta boxes for estimate editing.
	 *
	 * @return
	 */
	public static function register_meta_boxes() {
		// estimate specific
		$args = array(
			'si_estimate_line_items' => array(
				'title' => si__( 'Management' ),
				'show_callback' => array( __CLASS__, 'show_line_items_view' ),
				'save_callback' => array( __CLASS__, 'save_line_items' ),
				'context' => 'normal',
				'priority' => 'high',
				'weight' => 0,
				'save_priority' => 5
			),
			'si_estimate_update' => array(
				'title' => si__( 'Information' ),
				'show_callback' => array( __CLASS__, 'show_information_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_meta_box_estimate_information' ),
				'context' => 'normal',
				'priority' => 'high',
				'weight' => 20,
				'save_priority' => 50
			),
			'si_doc_send' => array(
				'title' => si__( 'Send Estimate' ),
				'show_callback' => array( __CLASS__, 'show_estimate_send_view' ),
				'save_callback' => array( __CLASS__, 'save_estimate_note' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 30,
				'save_priority' => 500
			),
			'si_estimate_history' => array(
				'title' => si__( 'Estimate History' ),
				'show_callback' => array( __CLASS__, 'show_submission_history_view' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 20
			),
			'si_estimate_notes' => array(
				'title' => si__( 'Terms & Notes' ),
				'show_callback' => array( __CLASS__, 'show_notes_view' ),
				'save_callback' => array( __CLASS__, 'save_notes' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 100
			)
		);
		do_action( 'sprout_meta_box', $args, SI_Estimate::POST_TYPE );
	}

	/**
	 * Remove publish box and add something custom for estimates
	 *
	 * @param string  $post_type
	 * @return
	 */
	public static function modify_meta_boxes( $post_type ) {
		if ( $post_type == SI_Estimate::POST_TYPE ) {
			remove_meta_box( 'submitdiv', null, 'side' );
		}
	}

	/**
	 * Add quick links
	 * @param  object $post
	 * @return
	 */
	public static function quick_links( $post ) {
		if ( get_post_type( $post ) == SI_Estimate::POST_TYPE ) {
			$estimate = SI_Estimate::get_instance( $post->ID );
			$status = ( is_a( $estimate, 'SI_Estimate' ) && $estimate->get_status() != 'auto-draft' ) ? $estimate->get_status() : SI_Estimate::STATUS_TEMP ;
			self::load_view( 'admin/meta-boxes/estimates/quick-links', array(
					'id' => $post->ID,
					'post' => $post,
					'status' => $status,
					'statuses' => SI_Estimate::get_statuses(),
					'estimate' => $estimate
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

		$item_types = get_terms( array( SI_Estimate::LINE_ITEM_TAXONOMY ), array( 'hide_empty' => FALSE, 'fields' => 'all' ) );
		$type_options = array();
		foreach ( $item_types as $item_type ) {
			$type_options[$item_type->term_id] = $item_type->name;
		}

		$estimate = SI_Estimate::get_instance( $post->ID );
		$total = ( is_a( $estimate, 'SI_Estimate' ) ) ? $estimate->get_total() : '0.00' ;
		$subtotal = ( is_a( $estimate, 'SI_Estimate' ) ) ? $estimate->get_subtotal() : '0.00' ;
		$status = ( is_a( $estimate, 'SI_Estimate' ) && $estimate->get_status() != 'auto-draft' ) ? $estimate->get_status() : SI_Estimate::STATUS_TEMP ;
		$line_items = ( is_a( $estimate, 'SI_Estimate' ) ) ? $estimate->get_line_items() : array() ;
		self::load_view( 'admin/meta-boxes/estimates/line-items', array(
				'id' => $post->ID,
				'post' => $post,
				'status' => $status,
				'total' => $total,
				'subtotal' => $subtotal,
				'line_items' => $line_items,
				'item_types' => $item_types,
				'item_types_options' => $type_options
			), FALSE );
	}

	/**
	 * Saving line items first thing since totals are calculated later based on other options.
	 * @param  int $post_id       
	 * @param  object $post          
	 * @param  array $callback_args 
	 * @param  int $estimate_id   
	 * @return                 
	 */
	public static function save_line_items( $post_id, $post, $callback_args, $estimate_id = NULL ) {

		if ( !isset( $_POST['line_item_key'] ) )
			return;
		
		$estimate = SI_Estimate::get_instance( $post_id );
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
		$estimate->set_line_items($line_items);

		$subject = ( isset( $_POST['subject'] ) && $_POST['subject'] != '' ) ? $_POST['subject'] : 0 ;
		if ( $subject && $subject != get_the_title( $post_id ) ) {
			$est_post = array(
				'ID' => $post_id,
				'post_title' => $subject
				);

			// Update the post into the database
			wp_update_post( $est_post );
		}

		do_action( 'si_save_line_items_meta_box', $post_id, $post, $estimate );
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

		$estimate = SI_Estimate::get_instance( $post->ID );
		$status = ( is_a( $estimate, 'SI_Estimate' ) && $estimate->get_status() != 'auto-draft' ) ? $estimate->get_status() : SI_Estimate::STATUS_TEMP ;
		self::load_view( 'admin/meta-boxes/estimates/information', array(
				'id' => $post->ID,
				'post' => $post,
				'estimate' => $estimate,
				'status' => $status,
				'status_options' => SI_Estimate::get_statuses(),
				'invoice_id' => $estimate->get_invoice_id(),
				'expiration_date' => $estimate->get_expiration_date(),
				'client_id' => $estimate->get_client_id(),
				'client_options' => $client_options,
				'clients' => $clients
			), FALSE );

		// add the client modal
		self::load_view( 'admin/meta-boxes/estimates/client-creation-modal', array( 'fields' => SI_Clients::form_fields( FALSE ) ) );
	}

	/**
	 * Saving line items first thing since totals are calculated later based on other options.
	 * @param  int $post_id       
	 * @param  object $post          
	 * @param  array $callback_args 
	 * @param  int $estimate_id   
	 * @return                 
	 */
	public static function save_meta_box_estimate_information( $post_id, $post, $callback_args, $estimate_id = NULL ) {
		$estimate = SI_Estimate::get_instance( $post_id );

		$status = ( isset( $_POST['status'] ) && $_POST['status'] != '' ) ? $_POST['status'] : '' ;
		$expiration_m = ( isset( $_POST['expiration_mm'] ) && $_POST['expiration_mm'] != '' ) ? $_POST['expiration_mm'] : '' ;
		$expiration_j = ( isset( $_POST['expiration_j'] ) && $_POST['expiration_j'] != '' ) ? $_POST['expiration_j'] : '' ;
		$expiration_o = ( isset( $_POST['expiration_o'] ) && $_POST['expiration_o'] != '' ) ? $_POST['expiration_o'] : '' ;
		$estimate_id = ( isset( $_POST['estimate_id'] ) && $_POST['estimate_id'] != '' ) ? $_POST['estimate_id'] : '' ;
		$po_number = ( isset( $_POST['po_number'] ) && $_POST['po_number'] != '' ) ? $_POST['po_number'] : '' ;
		$client_id = ( isset( $_POST['client'] ) && $_POST['client'] != '' ) ? $_POST['client'] : '' ;
		$discount = ( isset( $_POST['discount'] ) && $_POST['discount'] != '' ) ? $_POST['discount'] : '' ;
		$tax = ( isset( $_POST['tax'] ) && $_POST['tax'] != '' ) ? $_POST['tax'] : '' ;
		$tax2 = ( isset( $_POST['tax2'] ) && $_POST['tax2'] != '' ) ? $_POST['tax2'] : '' ;
		$currency = ( isset( $_POST['currency'] ) && $_POST['currency'] != '' ) ? $_POST['currency'] : '' ;

		$estimate->set_status( $status );
		$estimate->set_expiration_date( strtotime( $expiration_m  . '/' . $expiration_j . '/' . $expiration_o ) );
		$estimate->set_estimate_id( $estimate_id );
		$estimate->set_po_number( $po_number );
		$estimate->set_client_id( $client_id );
		$estimate->set_discount( $discount );
		$estimate->set_tax( $tax );
		$estimate->set_tax2( $tax2 );
		$estimate->set_currency( $currency );

		// Last thing to do is set the total based on the options set, including the line items.
		$estimate->set_calculated_total();

		$user = get_userdata( get_current_user_id() );
		do_action( 'si_new_record', 
			sprintf( si__('Estimate updated by %s.'), $user->display_name ), 
			self::HISTORY_UPDATE, 
			$estimate->get_id(), 
			sprintf( si__('Data updated for %s.'), $estimate->get_id() ), 
			0, 
			FALSE );
	}

	/**
	 * Show the estimate sending options
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_estimate_send_view( $post, $metabox ) {
		if ( $post->post_status == 'auto-draft' ) {
			printf( '<p>%s</p>', si__( 'Save this estimate before sending.' ) );
			return;
		}
		$estimate = SI_Estimate::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/estimates/send', array(
				'id' => $post->ID,
				'post' => $post,
				'estimate' => $estimate,
				'fields' => self::sender_submission_fields( $estimate ),
				'sender_notes' => $estimate->get_sender_note()
			), FALSE );
	}

	public static function sender_submission_fields( SI_Estimate $estimate ) {
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
		$client = $estimate->get_client();
		$current_user = get_userdata( get_current_user_id() );
		
		$recipient_options = '<span class="label_wrap"><label for="sa_metabox_recipients">'.si__('Recipients').'</label></span>';
		$recipient_options = '<div class="form-group"><div class="input_wrap">';
		
			// client users
			if ( is_a( $client , 'SI_Client') ) {
				$client_users = $client->get_associated_users();
				foreach ( $client_users as $user_id ) {
					$recipient_options .= sprintf( '<label class="clearfix"><input type="checkbox" name="sa_metabox_recipients[]" value="%1$s"> %2$s</label>', $user_id, esc_attr( SI_Notifications::get_user_email( $user_id ) ) );
				}
			}
			// Send to me.
			$recipient_options .= sprintf( '<label class="clearfix"><input type="checkbox" name="sa_metabox_recipients[]" value="%1$s"> %2$s</label>', get_current_user_id(), si__('Send me a copy') );

		$recipient_options .= '</div></div>';

		$fields['recipients'] = array(
			'weight' => 5,
			'label' => sprintf( '%s <span class="helptip" title="%s"></span>', si__('Recipients'), si__("A notification will be sent if recipients are selected and this estimate is saved.") ),
			'type' => 'bypass',
			'output' => $recipient_options
		);

		$fields['sender_note'] = array(
			'weight' => 10,
			'label' => self::__( 'Note' ),
			'type' => 'textarea',
			'default' => $estimate->get_sender_note(),
			'description' => si__('This note will be added to the Estimate Notification via the [sender_note] shortcode.')
		);

		$fields['doc_id'] = array(
			'type' => 'hidden',
			'value' => $estimate->get_id(),
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
	 * @param  int $estimate_id   
	 * @return                 
	 */
	public static function save_estimate_note( $post_id, $post, $callback_args, $estimate_id = NULL ) {
		$estimate = SI_Estimate::get_instance( $post_id );

		$sender_notes = ( isset( $_POST['sender_notes'] ) && $_POST['sender_notes'] != '' ) ? $_POST['sender_notes'] : '' ;
		if ( $sender_notes == '' ) { // check to make sure the sender note option wasn't updated for the send.
			$sender_notes = ( isset( $_POST['sa_metabox_sender_note'] ) && $_POST['sa_metabox_sender_note'] != '' ) ? $_POST['sa_metabox_sender_note'] : '' ;
		}
		$estimate->set_sender_note( $sender_notes );

		if ( !empty( $_POST['sa_metabox_recipients'] ) ) {
			do_action( 'send_estimate', $estimate, $_POST['sa_metabox_recipients'] );
		}
	}

	/**
	 * Show the estimate history, including the submission fields
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
		$estimate = SI_Estimate::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/estimates/history', array(
				'id' => $post->ID,
				'post' => $post,
				'estimate' => $estimate,
				'history' => si_doc_history_records( $post->ID, FALSE ),
				'submission_fields' => $estimate->get_submission_fields(),
			), FALSE );
	}



	/**
	 * Show terms and notes
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_notes_view( $post, $metabox ) {
		$estimate = SI_Estimate::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/estimates/notes', array(
				'id' => $post->ID,
				'post' => $post,
				'estimate' => $estimate,
				'terms' => $estimate->get_terms(),
				'notes' => $estimate->get_notes(),
			), FALSE );
	}

	/**
	 * Saving line items first thing since totals are calculated later based on other options.
	 * @param  int $post_id       
	 * @param  object $post          
	 * @param  array $callback_args 
	 * @param  int $estimate_id   
	 * @return                 
	 */
	public static function save_notes( $post_id, $post, $callback_args, $estimate_id = NULL ) {
		$estimate = SI_Estimate::get_instance( $post_id );

		$estimate_terms = ( isset( $_POST['estimate_terms'] ) && $_POST['estimate_terms'] != '' ) ? $_POST['estimate_terms'] : '' ;
		$estimate_notes = ( isset( $_POST['estimate_notes'] ) && $_POST['estimate_notes'] != '' ) ? $_POST['estimate_notes'] : '' ;

		$estimate->set_terms( $estimate_terms );
		$estimate->set_notes( $estimate_notes );

	}

	/////////////////
	// Templating //
	/////////////////

	/**
	 * Remove all scripts and styles from the estimate view and then add those specific to si.
	 * @return  
	 */
	public static function remove_scripts_and_styles() {
		if ( SI_Estimate::is_estimate_query() && is_single() ) {
			if ( apply_filters( 'si_remove_scripts_styles_on_doc_pages', '__return_true' ) ) {
				global $wp_scripts, $wp_styles;
				$allowed_scripts = apply_filters( 'si_allowed_admin_doc_scripts', array( 'sprout_doc_scripts', 'qtip', 'dropdown' ) );
				$allowed_admin_scripts = apply_filters( 'si_allowed_admin_doc_scripts', array_merge( array( 'admin-bar' ), $allowed_scripts ) );
				foreach( $wp_scripts->queue as $handle ) {
					if ( current_user_can( 'edit_posts' ) ) {
						$wp_scripts->queue = $allowed_admin_scripts;
					}
					else {
						$wp_scripts->queue = $allowed_scripts;
					}
				}
				$allowed_styles = apply_filters( 'si_allowed_admin_doc_scripts', array( 'sprout_doc_style', 'qtip', 'dropdown' ) );
				$allowed_admin_styles = apply_filters( 'si_allowed_admin_doc_scripts', array_merge( array( 'admin-bar' ), $allowed_styles ) );
				foreach( $wp_styles->queue as $handle ) {
					if ( current_user_can( 'edit_posts' ) ) {
						$wp_styles->queue = $allowed_admin_styles;
					}
					else {
						$wp_styles->queue = $allowed_styles;
					}
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

		if ( get_post_type( $_REQUEST['sa_send_metabox_doc_id'] ) != SI_Estimate::POST_TYPE ) {
			return;
		}

		$estimate = SI_Estimate::get_instance( $_REQUEST['sa_send_metabox_doc_id'] );
		$estimate->set_sender_note( $_REQUEST['sa_send_metabox_sender_note'] );
		do_action( 'send_estimate', $estimate, $_REQUEST['sa_metabox_recipients'] );

		header( 'Content-type: application/json' );
		if ( self::DEBUG ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( array( 'response' => si__('Notification Queued') ) );
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
	public static function maybe_create_status_update_record( SI_Estimate $estimate, $status = '', $original_status = '' ) {
		do_action( 'si_new_record', 
			sprintf( si__('Status changed: %s to <b>%s</b>.'), ucfirst( str_replace( 'publish', self::__('Pending'), $original_status ) ), ucfirst( str_replace( 'publish', self::__('Pending'), $status ) ) ), 
			self::HISTORY_STATUS_UPDATE, 
			$estimate->get_id(), 
			sprintf( si__('Status update for %s.'), $estimate->get_id() ), 
			0, 
			FALSE );
	}

	/**
	 * Create a record of the new invoice created.
	 * @param  integer $new_post_id   
	 * @param  integer $cloned_post_id       
	 * @param  string  $new_post_type 
	 * @return                  
	 */
	public static function create_record_of_cloned_invoice( $new_post_id = 0, $cloned_post_id = 0, $new_post_type = '' ) {
		if ( get_post_type( $cloned_post_id ) == SI_Estimate::POST_TYPE ) {
			if ( $new_post_type == SI_Invoice::POST_TYPE ) {
				do_action( 'si_new_record', 
					sprintf( si__('Invoice Created: <a href="%s">%s</a>.'), get_edit_post_link( $new_post_id ), get_the_title( $new_post_id ) ),
					self::HISTORY_INVOICE_CREATED, 
					$cloned_post_id,
					sprintf( si__('Invoice Created: %s.'), get_the_title( $new_post_id ) ),
					0, 
					FALSE );
			}
		}
	}

	public static function maybe_log_estimate_view() {
		global $post;

		if ( !is_single() )
			return;

		// Make sure this is an estimate we're viewing
		if ( $post->post_type != SI_Estimate::POST_TYPE )
			return;
		
		// Don't log the authors views
		if ( $post->post_author == get_current_user_id() )
			return;

		if ( is_user_logged_in() ) {
			$user = get_userdata( get_current_user_id() );
			$name = $user->first_name . ' ' . $user->last_name;
			$whom = $name . ' (' . $user->user_login. ')';
		}
		else {
			$whom = self::get_user_ip();
		}
		$estimate = SI_Estimate::get_instance( $post->ID );
		do_action( 'si_new_record', 
			$_SERVER, 
			self::VIEWED_STATUS_UPDATE, 
			$estimate->get_id(), 
			sprintf( si__('Estimate viewed by %s.'), esc_html( $whom ) ) );
	}


	////////////////////
	// Admin Columns //
	////////////////////

	/**
	 * Overload the columns for the estimate post type admin
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
		$columns['title'] = self::__( 'Estimate' );
		$columns['status'] = self::__( 'Status' );
		$columns['total'] = self::__( 'Total' );
		$columns['client'] = self::__( 'Client' );
		$columns['doc_link'] = '<div class="dashicons icon-sproutapps-invoices"></div>';
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
		$estimate = SI_Estimate::get_instance( $id );

		if ( !is_a( $estimate, 'SI_Estimate' ) )
			return; // return for that temp post

		switch ( $column_name ) {

		case 'doc_link':
			$invoice_id = $estimate->get_invoice_id();
			if ( $invoice_id ) {
				printf( '<a class="doc_link" title="%s" href="%s">%s</a>', self::__( 'Invoice for this estimate.' ), get_edit_post_link( $invoice_id ), '<div class="dashicons icon-sproutapps-invoices"></div>' );
			}
			break;
		case 'status': 
				self::status_change_dropdown( $id );
			break;

		case 'total':
			printf( '<span class="estimate_total">%s</span>', sa_get_formatted_money( $estimate->get_total() ) );
			break;

		case 'client':
			if ( $estimate->get_client_id() ) {
				$client = $estimate->get_client();
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
		if ( $post->post_type == SI_Estimate::POST_TYPE ) {
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
		if ( get_post_type( $post ) == SI_Estimate::POST_TYPE ) {
			$post_states = array();
			$estimate = SI_Estimate::get_instance( $post->ID );
			if ( $estimate->get_status() == SI_Estimate::STATUS_REQUEST ) {
				// FUTURE show "New" with some sort of logic
				// $post_states[$estimate->get_status()] = si__('New');
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
		if ( $screen == SI_Estimate::POST_TYPE ) {
			$columns['post'] = 1;
		}
		return $columns;
	}

	///////////////////
	// Set Defaults //
	///////////////////

	/**
	 * Set the default estimate terms based on current status. The idea is that anything edited will have a different status.
	 *
	 * @param string  $terms
	 * @param SI_Estimate $estimate
	 * @return string
	 */
	public static function maybe_set_estimate_terms( $terms = '', SI_Estimate $estimate ) {
		if ( !in_array( $estimate->get_status(), array( SI_Estimate::STATUS_PENDING, SI_Estimate::STATUS_APPROVED, SI_Estimate::STATUS_DECLINED ) ) ) {
			if ( $terms == '' ) {
				$terms = self::get_default_terms();
			}
		}
		return $terms;
	}

	/**
	 * Set the default estimate notes based on current status. The idea is that anything edited will have a different status.
	 *
	 * @param string  $terms
	 * @param SI_Estimate $estimate
	 * @return string
	 */
	public static function maybe_set_estimate_notes( $notes = '', SI_Estimate $estimate ) {
		if ( !in_array( $estimate->get_status(), array( SI_Estimate::STATUS_PENDING, SI_Estimate::STATUS_APPROVED, SI_Estimate::STATUS_DECLINED ) ) ) {
			if ( $notes == '' ) {
				$notes = self::get_default_notes();
			}
		}
		return $notes;
	}

	////////////
	// Misc. //
	////////////

	public static function is_edit_screen() {
		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : -1;
		if ( ( isset( $_GET['post_type'] ) && SI_Estimate::POST_TYPE == $_GET['post_type'] ) || SI_Estimate::POST_TYPE == get_post_type( $post_id ) ) {
			return TRUE;
		}
		return FALSE;
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
		if ( $post_type == SI_Estimate::POST_TYPE ) {
			return $post_ID;
		}
		return $slug;
	}

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'edit_estimates',
			'title' => self::__( 'Estimates' ),
			'href' => admin_url( 'edit.php?post_type='.SI_Estimate::POST_TYPE ),
			'weight' => 0,
		);
		return $items;
	}

	public static function status_change_dropdown( $id ) {
		if ( !$id ) {
			global $post;
			$id = $post->ID;
		}
		$estimate = SI_Estimate::get_instance( $id );

		if ( !is_a( $estimate, 'SI_Estimate' ) )
			return; // return for that temp post

		self::load_view( 'admin/sections/estimate-status-change-drop', array(
				'id' => $id,
				'status' => $estimate->get_status()
			), FALSE );

	}

	////////////////
	// Admin Help //
	////////////////

	public static function help_sections() {
		add_action( 'load-edit.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-post.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-post-new.php', array( get_class(), 'help_tabs' ) );
		add_action( 'load-edit-tags.php', array( get_class(), 'help_tabs' ) );
	}

	public static function help_tabs() {
		$post_type = '';
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == SI_Estimate::POST_TYPE ) {
			$post_type = SI_Estimate::POST_TYPE;
		}
		if ( $post_type == '' && isset( $_GET['post'] ) ) {
			$post_type = get_post_type( $_GET['post'] );
		}
		if ( !isset( $_GET['taxonomy'] ) && $post_type == SI_Estimate::POST_TYPE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
					'id' => 'manage-estimates',
					'title' => self::__( 'Manage Estimates' ),
					'content' => sprintf( '<p>%s</p><p>%s</p>', self::__('The status on the estimate table view can be updated without having to go the edit screen by click on the current status and selecting a new one.'), self::__('If an invoice is associated an icon linking to the edit page will show in the last column.') ),
				) );

			$screen->add_help_tab( array(
					'id' => 'edit-estimates',
					'title' => self::__( 'Editing Estimates' ),
					'content' => sprintf( '<p>%s</p><p><a href="%s">%s</a></p>', self::__('Editing estimates is intentionally easy to do but a review here would exhaust this limited space. Please review the knowledgeable for a complete overview.'), 'https://sproutapps.co/support/knowledgebase/sprout-invoices/estimates/', self::__('Knowledgebase Article') ),
				) );

			$screen->add_help_tab( array(
					'id' => 'mng-payments',
					'title' => self::__( 'Predefined Tasks' ),
					'content' => sprintf( '<p>%s</p><p>%s</p>', self::__('An admin to manage your tasks is found under Estimates > Tasks  in the admin. When adding a new task the “Name” is what you will select when adding new line items, the description is used to dill the line item description field.'), self::__('Pre-defined tasks are used for both Estimates and Invoices.') ),
				) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', self::__('For more information:') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/estimates/', self::__('Documentation') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', self::__('Support') )
			);
		}
		if ( isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] == SI_Estimate::LINE_ITEM_TAXONOMY && $post_type == SI_Estimate::POST_TYPE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
					'id' => 'mng-payments',
					'title' => self::__( 'Line Item Tasks' ),
					'content' => sprintf( '<p>%s</p><p>%s</p><p>%s</p>', self::__('An admin to manage your tasks is found under Estimates > Tasks  in the admin. When adding a new task the “Name” is what you will select when adding new line items, the description is used to dill the line item description field.'), self::__('Pre-defined tasks are used for both Estimates and Invoices.'), self::__('Subscribe to the blog or twitter account to get updates when new features are added to pre-defined tasks, since the ability to add a pre-defined rate, quantity and percentage is in the works.') ),
				) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', self::__('For more information:') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/invoices/predefined-tasks-line-items/', self::__('Documentation') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', self::__('Support') )
			);
		}

	}

}