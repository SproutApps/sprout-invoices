<?php


/**
 * Estimates Controller
 *
 *
 * @package Sprout_Estimate
 * @subpackage Estimates
 */
class SI_Estimates_Admin extends SI_Estimates {

	public static function init() {

		//views
		add_action( 'si_estimate_status_update', array( __CLASS__, 'status_change_dropdown' ) );

		if ( is_admin() ) {

			// Help Sections
			add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

			// Admin columns
			add_filter( 'manage_edit-'.SI_Estimate::POST_TYPE.'_columns', array( __CLASS__, 'register_columns' ) );
			add_filter( 'manage_'.SI_Estimate::POST_TYPE.'_posts_custom_column', array( __CLASS__, 'column_display' ), 10, 2 );
			add_filter( 'manage_edit-'.SI_Estimate::POST_TYPE.'_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
			add_filter( 'views_edit-sa_estimate', array( __CLASS__, 'filter_status_view' ) );
			add_filter( 'display_post_states', array( __CLASS__, 'filter_post_states' ), 10, 2 );

			// Remove quick edit from admin and add some row actions
			add_action( 'bulk_actions-edit-sa_estimate', array( __CLASS__, 'modify_bulk_actions' ) );
			add_action( 'post_row_actions', array( __CLASS__, 'modify_row_actions' ), 10, 2 );

			// Improve admin search
			add_filter( 'si_admin_meta_search', array( __CLASS__, 'filter_admin_search' ), 10, 2 );
		}

		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );
	}

	///////////
	// Views //
	///////////

	public static function status_change_dropdown( $id ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$estimate = SI_Estimate::get_instance( $id );

		if ( ! is_a( $estimate, 'SI_Estimate' ) ) {
			return; // return for that temp post
		}
		self::load_view( 'admin/sections/estimate-status-change-drop', array(
				'id' => $id,
				'status' => $estimate->get_status(),
		), false );

	}

	///////////////
	// Admin bar //
	///////////////

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'edit_estimates',
			'title' => __( 'Estimates', 'sprout-invoices' ),
			'href' => admin_url( 'edit.php?post_type='.SI_Estimate::POST_TYPE ),
			'weight' => 0,
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
		add_action( 'load-edit-tags.php', array( get_class(), 'help_tabs' ) );
	}

	public static function help_tabs() {
		$post_type = '';

		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( $screen_post_type == SI_Estimate::POST_TYPE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
				'id' => 'manage-estimates',
				'title' => __( 'Manage Estimates', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p>', __( 'The status on the estimate table view can be updated without having to go the edit screen by click on the current status and selecting a new one.', 'sprout-invoices' ), __( 'If an invoice is associated an icon linking to the edit page will show in the last column.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'edit-estimates',
				'title' => __( 'Editing Estimates', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p><a href="%s">%s</a></p>', __( 'Editing estimates is intentionally easy to do but a review here would exhaust this limited space. Please review the knowledgebase for a complete overview.', 'sprout-invoices' ), 'https://sproutapps.co/support/knowledgebase/sprout-invoices/estimates/', __( 'Knowledgebase Article', 'sprout-invoices' ) ),
			) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', __( 'For more information:', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/estimates/', __( 'Documentation', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', __( 'Support', 'sprout-invoices' ) )
			);
		}

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
		$columns['title'] = __( 'Estimate', 'sprout-invoices' );
		$columns['status'] = __( 'Status', 'sprout-invoices' );
		$columns['total'] = __( 'Total', 'sprout-invoices' );
		$columns['client'] = __( 'Client', 'sprout-invoices' );
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

		if ( ! is_a( $estimate, 'SI_Estimate' ) ) {
			return; // return for that temp post
		}
		switch ( $column_name ) {

			case 'doc_link':
				$invoice_id = $estimate->get_invoice_id();
				if ( $invoice_id ) {
					printf( '<a class="doc_link si_status %1$s" title="%2$s" href="%3$s">%4$s</a>', si_get_invoice_status( $invoice_id ), __( 'Invoice for this estimate.', 'sprout-invoices' ), get_edit_post_link( $invoice_id ), '<div class="dashicons icon-sproutapps-invoices"></div>' );
				}
			break;
			case 'status':
				self::status_change_dropdown( $id );
			break;

			case 'total':
				printf( '<span class="estimate_total">%s</span>', sa_get_formatted_money( $estimate->get_total(), $estimate->get_id() ) );
			break;

			case 'client':
				if ( $estimate->get_client_id() ) {
					$client = $estimate->get_client();
					printf( '<b><a href="%s">%s</a></b><br/><em>%s</em>', get_edit_post_link( $client->get_ID() ), get_the_title( $client->get_ID() ), $client->get_website() );
				} else {
					printf( '<b>%s</b> ', __( 'No client', 'sprout-invoices' ) );
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
		//$columns['total'] = 'total';
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
	public static function filter_post_states( $post_states = array(), WP_Post $post ) {
		if ( get_post_type( $post ) == SI_Estimate::POST_TYPE ) {
			$post_states = array();
			$estimate = SI_Estimate::get_instance( $post->ID );
			if ( $estimate->get_status() == SI_Estimate::STATUS_REQUEST ) {
				// FUTURE show "New" with some sort of logic
				// $post_states[$estimate->get_status()] = __( 'New', 'sprout-invoices' );
			}
		}
		return $post_states;
	}

	public static function filter_admin_search( $meta_search = '', $post_type = '' ) {
		if ( SI_Estimate::POST_TYPE !== $post_type ) {
			return array();
		}
		$meta_search = array(
			'_client_id',
			'_estimate_id',
			'_invoice_id',
			'_invoice_notes',
			'_project_id',
			'_doc_terms',
		);
		return $meta_search;
	}
}
