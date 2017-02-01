<?php

/**
 * Projects Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Projects
 */
class SI_Projects extends SI_Controller {
	const SUBMISSION_NONCE = 'si_project_submission';
	const HISTORY_STATUS_UPDATE = 'si_history_status_update';
	const LINE_ITEM_TYPE = 'project';
	const DEFAULT_RATE_META = 'si_default_project_rate';

	public static function init() {

		// add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ), 20 );

		if ( is_admin() ) {

			// Help Sections
			add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

			// Meta boxes
			add_action( 'admin_init', array( __CLASS__, 'register_meta_boxes' ), 100 );
			add_filter( 'wp_insert_post_data', array( __CLASS__, 'update_post_data' ), 100, 2 );
			add_action( 'do_meta_boxes', array( __CLASS__, 'modify_meta_boxes' ), 100 );
			add_action( 'edit_form_top', array( __CLASS__, 'name_box' ), 100 );

			// Estimate/Invoice
			add_action( 'doc_information_meta_box_client_row_after_client', array( __CLASS__, 'doc_project_selection' ) );
			add_action( 'si_save_line_items_meta_box', array( __CLASS__, 'save_doc_project_selection' ) );

			// Admin columns
			add_filter( 'manage_edit-'.SI_Project::POST_TYPE.'_columns', array( __CLASS__, 'register_columns' ) );
			add_filter( 'manage_'.SI_Project::POST_TYPE.'_posts_custom_column', array( __CLASS__, 'column_display' ), 10, 2 );
			add_action( 'post_row_actions', array( __CLASS__, 'modify_row_actions' ), 10, 2 );

			// Add projects to client admin
			add_action( 'client_submit_pre_invoices', array( __CLASS__, 'add_projects_to_clients_admin' ) );

		}

		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 25, 1 );

		// Add Time Type
		add_filter( 'si_line_item_types',  array( __CLASS__, 'add_time_line_item_type' ) );
		add_filter( 'si_line_item_columns',  array( __CLASS__, 'add_time_line_item_type_columns' ), -10, 3 );
	}

	//////////////
	// Enqueue //
	//////////////


	/**
	 * Enqueue resources on admin pages
	 *
	 */
	public static function admin_enqueue() {
		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( $screen_post_type == SI_Project::POST_TYPE ) {
			// only projects
		}
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
			'si_project_timetracking' => array(
				'title' => __( 'Time Tracking', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_upgrade_notice' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'normal',
				'priority' => 'high',
				'save_priority' => 0,
			),
			'si_project_information' => array(
				'title' => __( 'Information', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_information_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_meta_box_project_information' ),
				'context' => 'normal',
				'priority' => 'high',
				'save_priority' => 0,
			),
			'si_project_submit' => array(
				'title' => 'Update',
				'show_callback' => array( __CLASS__, 'show_submit_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_submit_meta_box' ),
				'context' => 'side',
				'priority' => 'high',
			),
			'si_project_history' => array(
				'title' => __( 'History', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_project_history_view' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'normal',
				'priority' => 'low',
			),
			'psp_project_info' => array(
				'title' => 'Project Panorama',
				'show_callback' => array( __CLASS__, 'show_psp_meta_box' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'side',
				'priority' => 'low',
			),
		);
		do_action( 'sprout_meta_box', $args, SI_Project::POST_TYPE );
	}

	/**
	 * Remove publish box and add something custom for estimates
	 *
	 * @param string  $post_type
	 * @return
	 */
	public static function modify_meta_boxes( $post_type ) {
		if ( $post_type == SI_Project::POST_TYPE ) {
			remove_meta_box( 'submitdiv', null, 'side' );
		}
	}
	/**
	 * Add quick links
	 * @param  object $post
	 * @return
	 */
	public static function name_box( $post ) {
		if ( get_post_type( $post ) == SI_Project::POST_TYPE ) {
			$project = SI_Project::get_instance( $post->ID );
			self::load_view( 'admin/meta-boxes/projects/name', array(
					'project' => $project,
					'id' => $post->ID,
					'status' => $post->post_status,
			) );
		}
	}

	public static function show_upgrade_notice() {
		if ( apply_filters( 'show_upgrade_messaging', true ) ) {
			printf( '<p><strong>Upgrade Available:</strong> Add time tracking and support the future of Sprout Invoices by <a href="%s">upgrading</a>.</p>', si_get_purchase_link() );
		}
	}

	/**
	 * Show custom submit box.
	 * @param  WP_Post $post
	 * @param  array $metabox
	 * @return
	 */
	public static function show_submit_meta_box( $post, $metabox ) {
		$project = SI_Project::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/projects/submit', array(
				'id' => $post->ID,
				'project' => $project,
				'post' => $post,
				'invoices' => $project->get_invoices(),
				'estimates' => $project->get_estimates(),
		), false );
	}

	/**
	 * Information
	 * @param  object $post
	 * @return
	 */
	public static function show_information_meta_box( $post ) {
		if ( get_post_type( $post ) == SI_Project::POST_TYPE ) {
			$project = SI_Project::get_instance( $post->ID );
			self::load_view( 'admin/meta-boxes/projects/info', array(
					'project' => $project,
					'id' => $post->ID,
					'fields' => self::form_fields( $project ),
			) );

			// For client creation
			add_thickbox();

			// add the client modal
			self::load_view( 'admin/meta-boxes/clients/creation-modal', array( 'fields' => SI_Clients::form_fields( false ) ) );
		}
	}

	/**
	 * Project Panaorama
	 * @param  object $post
	 * @return
	 */
	public static function show_psp_meta_box( $post ) {
		printf( '<p class="description help_block"><a href="%s"><img src="%s" /></a><br/>%s</p>', 'https://sproutapps.co/sprout-invoices/integrations/', SI_RESOURCES . 'admin/img/project-panorama-logo.png', __( 'SI now integrates with <a href="https://sproutapps.co/sprout-invoices/integrations/">Project Panorama</a>.', 'sprout-invoices' ) );
	}

	/**
	 * Saving info meta
	 * @param  int $post_id
	 * @param  object $post
	 * @param  array $callback_args
	 * @param  int $estimate_id
	 * @return
	 */
	public static function save_meta_box_project_information( $post_id, $post, $callback_args, $estimate_id = null ) {
		// name is set by update_post_data
		$client = ( isset( $_POST['sa_metabox_client'] ) && $_POST['sa_metabox_client'] != '' ) ? $_POST['sa_metabox_client'] : '' ;
		$website = ( isset( $_POST['sa_metabox_website'] ) && $_POST['sa_metabox_website'] != '' ) ? $_POST['sa_metabox_website'] : '' ;
		$start_date = ( isset( $_POST['sa_metabox_start_date'] ) && $_POST['sa_metabox_start_date'] != '' ) ? $_POST['sa_metabox_start_date'] : '' ;
		$end_date = ( isset( $_POST['sa_metabox_end_date'] ) && $_POST['sa_metabox_end_date'] != '' ) ? $_POST['sa_metabox_end_date'] : '' ;
		$default_rate = ( isset( $_POST['sa_metabox_default_rate'] ) && $_POST['sa_metabox_default_rate'] != '' ) ? $_POST['sa_metabox_default_rate'] : '' ;

		$project = SI_Project::get_instance( $post_id );
		$project->set_website( $website );
		$project->set_start_date( $start_date );
		$project->set_end_date( $end_date );
		$project->add_associated_client( $client );

		update_post_meta( $post_id, self::DEFAULT_RATE_META, (float) $default_rate );

		do_action( 'project_meta_saved', $project );
	}

	public static function update_post_data( $data = array(), $post = array() ) {
		if ( $post['post_type'] == SI_Project::POST_TYPE ) {
			$title = $post['post_title'];
			if ( isset( $_POST['sa_metabox_name'] ) && $_POST['sa_metabox_name'] != '' ) {
				$title = $_POST['sa_metabox_name'];
			}
			// modify the post title
			$data['post_title'] = $title;
		}
		return $data;
	}

	/**
	 * Saving submit meta
	 * @param  int $post_id
	 * @param  object $post
	 * @param  array $callback_args
	 * @param  int $estimate_id
	 * @return
	 */
	public static function save_submit_meta_box( $post_id, $post, $callback_args, $estimate_id = null ) {

	}


	/**
	 * Show the history
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_project_history_view( $post, $metabox ) {
		if ( $post->post_status == 'auto-draft' ) {
			printf( '<p>%s</p>', __( 'No history available.', 'sprout-invoices' ) );
			return;
		}
		$project = SI_Project::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/projects/history', array(
				'id' => $post->ID,
				'post' => $post,
				'project' => $project,
				'historical_records' => array_reverse( $project->get_history() ),
		), false );
	}

	/////////////////////
	// Doc Meta boxes //
	/////////////////////

	public static function doc_project_selection( $doc ) {
		$client_id = ( $doc ) ? $doc->get_client_id() : 0;
		$project_id = ( $doc ) ? $doc->get_project_id() : 0;
		$project = ( $doc ) ? SI_Project::get_instance( $project_id ) : 0;

		$title = ( is_a( $project, 'SI_Project' ) ) ? $project->get_title() : __( 'No Project Selected', 'sprout-invoices' );

		self::load_view( 'admin/meta-boxes/projects/information-doc-select', array(
				'doc' => $doc,
				'project_id' => $project_id,
				'title' => $title,
				'client_id' => $client_id,
		), false );
	}

	/**
	 * Save the template selection for a doc by post id
	 * @param  integer $post_id
	 * @param  string  $doc_template
	 * @return
	 */
	public static function save_doc_project_selection( $post_id = 0 ) {
		$doc_project = ( isset( $_POST['doc_project'] ) ) ? $_POST['doc_project'] : '' ;
		$doc = si_get_doc_object( $post_id );
		$doc->set_project_id( $doc_project );
	}


	////////////
	// Misc. //
	////////////


	public static function form_fields( $project = 0, $required = false ) {
		$fields = array();

		$associated_client = ( $project ) ? $project->get_associated_clients() : array( 0 ) ;

		$client_id = array_pop( $associated_client );

		$fields['name'] = array(
			'weight' => 1,
			'label' => __( 'Project Name', 'sprout-invoices' ),
			'type' => 'text',
			'required' => true, // always necessary
			'default' => ( $project ) ? $project->get_title() : '',
		);

		$client_options = array();
		$client_options[0] = '';
		$client_options += SI_Client::get_all_clients();

		$description = ( $client_id ) ? sprintf( __( 'Edit <a href="%s">%s</a>, select another client or <a href="%s">create a new client</a>.', 'sprout-invoices' ), get_edit_post_link( $client_id ), get_the_title( $client_id ), '#TB_inline?width=600&height=450&inlineId=client_creation_modal" id="client_creation_modal_link" class="thickbox' ) : sprintf( __( 'Select an existing client or <a href="%s">create a new client</a>.', 'sprout-invoices' ), '#TB_inline?width=600&height=420&inlineId=client_creation_modal" id="client_creation_modal_link" class="thickbox' );

		$fields['client'] = array(
			'weight' => 3,
			'label' => __( 'Client', 'sprout-invoices' ),
			'type' => 'select',
			'options' => $client_options,
			'required' => true,
			'default' => ( $client_id ) ? $client_id : 0,
			'attributes' => array( 'class' => 'select2' ),
			'description' => $description,
		);

		$fields['start_date'] = array(
			'weight' => 100,
			'label' => __( 'Start Date', 'sprout-invoices' ),
			'type' => 'date',
			'required' => $required,
			'default' => ( $project && $project->get_start_date() ) ? date( 'Y-m-d', $project->get_start_date() ) : '',
			'placeholder' => '',
		);

		$fields['end_date'] = array(
			'weight' => 100,
			'label' => __( 'End Date', 'sprout-invoices' ),
			'type' => 'date',
			'required' => $required,
			'default' => ( $project && $project->get_end_date() ) ? date( 'Y-m-d', $project->get_end_date() ) : '',
			'placeholder' => '',
		);

		$fields['website'] = array(
			'weight' => 120,
			'label' => __( 'Website', 'sprout-invoices' ),
			'type' => 'text',
			'required' => $required,
			'default' => ( $project ) ? $project->get_website() : '',
			'placeholder' => 'http://',
		);

		$fields['default_rate'] = array(
			'weight' => 200,
			'label' => __( 'Default Rate', 'sprout-invoices' ),
			'type' => 'text',
			'required' => $required,
			'default' => ( $project ) ? get_post_meta( $project->get_id(), self::DEFAULT_RATE_META, true ) : '',
			'placeholder' => '120.00',
			'description' => __( 'Default rate for the "Project" line item, shown only if the invoice/estimate has a project associated first.', 'sprout-invoices' ),
		);

		$fields['nonce'] = array(
			'type' => 'hidden',
			'value' => wp_create_nonce( self::SUBMISSION_NONCE ),
			'weight' => 10000,
		);

		$fields = apply_filters( 'si_project_form_fields', $fields );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	//////////////
	// Clients //
	//////////////

	public static function add_projects_to_clients_admin() {
		echo self::load_view( 'admin/meta-boxes/projects/client-submit', array(
				'projects' => SI_Project::get_projects_by_client( get_the_id() ),
		) );
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
		$columns['title'] = __( 'Project', 'sprout-invoices' );
		$columns['info'] = __( 'Info', 'sprout-invoices' );
		$columns['estimates'] = __( 'Estimates', 'sprout-invoices' );
		$columns['invoices'] = __( 'Invoices', 'sprout-invoices' );
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
		$project = SI_Project::get_instance( $id );

		if ( ! is_a( $project, 'SI_Project' ) ) {
			return; // return for that temp post
		}
		switch ( $column_name ) {

			case 'info':

				$associated_clients = $project->get_associated_clients();
				echo '<p>';
				printf( '<b>%s</b>: ', __( 'Client', 'sprout-invoices' ) );
				if ( ! empty( $associated_clients ) ) {
					$clients_print = array();
					foreach ( $associated_clients as $client_id ) {
						$clients_print[] = sprintf( '<span class="associated_client"><a href="%s">%s</a></span>', get_edit_post_link( $client_id ) , get_the_title( $client_id ) );
					}
				}
				if ( ! empty( $clients_print ) ) {
					echo implode( ', ', $clients_print );
				} else {
					echo __( 'No associated clients', 'sprout-invoices' );
				}
				echo '</p>';

				echo '<p>';
				printf( '<b>%s</b>: ', __( 'Site', 'sprout-invoices' ) );
				echo make_clickable( esc_url( $project->get_website() ) );
				echo '</p>';

			break;

			case 'invoices':

				$invoices = $project->get_invoices();
				$invoiced_total = 0;
				$outstanding_balance = 0;
				foreach ( $invoices as $invoice_id ) {
					$invoice = SI_Invoice::get_instance( $invoice_id );
					$invoiced_total += $invoice->get_calculated_total();
					$outstanding_balance += $invoice->get_balance();
				}
				$split = 2;
				$split_invoices = array_slice( $invoices, 0, $split );
				if ( ! empty( $split_invoices ) ) {
					echo '<dl>';
					foreach ( $split_invoices as $invoice_id ) {
						printf( '<dt>%s</dt><dd><a href="%s">%s</a></dd>', get_post_time( get_option( 'date_format' ), false, $invoice_id ), get_edit_post_link( $invoice_id ), get_the_title( $invoice_id ) );
					}
					echo '</dl>';
					if ( count( $invoices ) > $split ) {
						printf( '<span class="description">' . __( '...%s of <a href="%s">%s</a> most recent shown', 'sprout-invoices' ) . '</span>', $split, get_edit_post_link( $id ), count( $invoices ) );
					}
				} else {
					printf( '<em>%s</em>', __( 'No invoices', 'sprout-invoices' ) );
				}

				echo '<hr/>';

				printf( '<p><b>%s</b>: %s</p>', __( 'Total Invoiced ', 'sprout-invoices' ), sa_get_formatted_money( $invoiced_total ) );

				if ( 0.00 < $outstanding_balance ) {

					printf( '<p><b>%s</b>: %s</p>', __( 'Outstanding Balance ', 'sprout-invoices' ), sa_get_formatted_money( $outstanding_balance ) );
				}

			break;

			case 'estimates':

				$estimates = $project->get_estimates();
				$split = 2;
				$split_estimates = array_slice( $estimates, 0, $split );
				if ( ! empty( $split_estimates ) ) {
					echo '<dl>';
					foreach ( $split_estimates as $estimate_id ) {
						printf( '<dt>%s</dt><dd><a href="%s">%s</a></dd>', get_post_time( get_option( 'date_format' ), false, $estimate_id ), get_edit_post_link( $estimate_id ), get_the_title( $estimate_id ) );
					}
					echo '</dl>';
					if ( count( $estimates ) > $split ) {
						printf( '<span class="description">' . __( '...%s of <a href="%s">%s</a> most recent shown', 'sprout-invoices' ) . '</span>', $split, get_edit_post_link( $id ), count( $estimates ) );
					}
				} else {
					printf( '<em>%s</em>', __( 'No estimates', 'sprout-invoices' ) );
				}
			break;

			default:
				// code...
			break;
		}

	}

	/**
	 * Filter the array of row action links below the title.
	 *
	 * @param array   $actions An array of row action links.
	 * @param WP_Post $post    The post object.
	 */
	public static function modify_row_actions( $actions = array(), $post = array() ) {
		if ( $post->post_type == SI_Project::POST_TYPE ) {
			unset( $actions['trash'] );
			// remove quick edit
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}


	//////////////
	// Utility //
	//////////////

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'edit_projects',
			'title' => __( 'Projects', 'sprout-invoices' ),
			'href' => admin_url( 'edit.php?post_type='.SI_Project::POST_TYPE ),
			'weight' => 100,
		);
		return $items;
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
		if ( $screen_post_type == SI_Project::POST_TYPE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
				'id' => 'edit-projects',
				'title' => __( 'Manage Projects', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p>', '', '' ),
			) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', __( 'For more information:', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/projects/', __( 'Documentation', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', si_get_sa_link( 'https://sproutapps.co/support/' ), __( 'Support', 'sprout-invoices' ) )
			);
		}
	}

	///////////////
	// Line Item //
	///////////////

	public static function add_time_line_item_type( $types = array() ) {
		$project_id = si_get_docs_project_id( get_the_id() );
		if ( ! $project_id ) {
			return $types;
		}
		$default_rate = get_post_meta( $project_id, self::DEFAULT_RATE_META, true );
		if ( ! is_numeric( $default_rate ) || ! $default_rate ) {
			return $types;
		}
		$types = array_merge( $types, array( self::LINE_ITEM_TYPE => __( 'Project', 'sprout-invoices' ) ) );
		return $types;
	}

	public static function add_time_line_item_type_columns( $columns = array(), $type = '', $item_data = array() ) {
		if ( self::LINE_ITEM_TYPE !== $type ) {
			return $columns;
		}

		$default_rate = '';
		if ( isset( $item_data['doc_id'] ) ) {
			$project_id = si_get_docs_project_id( $item_data['doc_id'] );
			$default_rate = get_post_meta( $project_id, self::DEFAULT_RATE_META, true );
		}
		$columns = array(
			'desc' => array(
					'label' => __( 'Project', 'sprout-invoices' ),
					'type' => 'textarea',
					'calc' => false,
					'hide_if_parent' => false,
					'weight' => 1,
				),
			'rate' => array(
					'label' => __( 'Rate', 'sprout-invoices' ),
					'type' => 'small-input',
					'placeholder' => '120',
					'value' => ( is_numeric( $default_rate ) ) ? $default_rate : '',
					'calc' => false,
					'hide_if_parent' => true,
					'weight' => 5,
				),
			'qty' => array(
					'label' => __( 'Qty', 'sprout-invoices' ),
					'type' => 'small-input',
					'placeholder' => 1,
					'calc' => true,
					'hide_if_parent' => true,
					'weight' => 10,
				),
			'tax' => array(
					'label' => sprintf( '&#37; <span class="helptip" title="%s"></span>', __( 'A percentage adjustment per line item, i.e. tax or discount', 'sprout-invoices' ) ),
					'type' => 'small-input',
					'placeholder' => 0,
					'calc' => false,
					'hide_if_parent' => true,
					'weight' => 15,
				),
			'total' => array(
					'label' => __( 'Amount', 'sprout-invoices' ),
					'type' => 'total',
					'placeholder' => sa_get_formatted_money( 0 ),
					'calc' => true,
					'hide_if_parent' => false,
					'weight' => 50,
				),
			'sku' => array(
					'type' => 'hidden',
					'placeholder' => '',
					'calc' => false,
					'weight' => 50,
				),
			'project_id' => array(
					'type' => 'hidden',
					'placeholder' => '',
					'calc' => false,
					'weight' => 50,
				),
		);
		return $columns;
	}
}
