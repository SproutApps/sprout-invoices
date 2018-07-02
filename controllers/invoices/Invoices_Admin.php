<?php


/**
 * Invoices Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Invoices
 */
class SI_Invoices_Admin extends SI_Invoices {

	public static function init() {

		//views
		add_action( 'si_invoice_status_update', array( __CLASS__, 'status_change_dropdown' ) );

		if ( is_admin() ) {
			// Help Sections
			add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

			// Admin columns
			add_filter( 'manage_edit-'.SI_Invoice::POST_TYPE.'_columns', array( __CLASS__, 'register_columns' ) );
			add_filter( 'manage_'.SI_Invoice::POST_TYPE.'_posts_custom_column', array( __CLASS__, 'column_display' ), 10, 2 );
			add_filter( 'manage_edit-'.SI_Invoice::POST_TYPE.'_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
			add_filter( 'views_edit-'.SI_Invoice::POST_TYPE, array( __CLASS__, 'filter_status_view' ) );
			add_filter( 'display_post_states', array( __CLASS__, 'filter_post_states' ), 10, 2 );

			// Remove quick edit from admin and add some row actions
			add_action( 'bulk_actions-edit-sa_invoice', array( __CLASS__, 'modify_bulk_actions' ) );
			add_action( 'post_row_actions', array( __CLASS__, 'modify_row_actions' ), 10, 2 );

			add_filter( 'post_row_actions', array( __CLASS__, 'si_add_duplication_link' ), 10, 2 );

			// Improve admin search
			add_filter( 'si_admin_meta_search', array( __CLASS__, 'filter_admin_search' ), 10, 2 );
		}

		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );
	}

	///////////
	// Views //
	///////////

	public static function status_change_dropdown( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$invoice = SI_Invoice::get_instance( $id );

		if ( ! is_a( $invoice, 'SI_Invoice' ) ) {
			return; // return for that temp post
		}
		self::load_view( 'admin/sections/invoice-status-change-drop', array(
				'id' => $id,
				'status' => $invoice->get_status(),
		), false );

	}

	////////////////////
	// Admin Columns //
	////////////////////

	public static function si_add_duplication_link( $actions, $post ) {
		if ( $post->post_type == SI_Invoice::POST_TYPE ) {
			$actions['duplicate_link'] = sprintf( '<a href="%s"  id="duplicate_estimate_quick_link" class="pr_duplicate" title="%s">%s</a>', SI_Controller::get_clone_post_url( $post->ID ), __( 'Duplicate this invoice', 'sprout-invoices' ), __( 'Duplicate', 'sprout-invoices' ) );
		}
		return $actions;
	}

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
		$columns['notification_status'] = sprintf( '<mark class="helptip notification_status_wrap column_title" title="%s">&nbsp;</mark>', __( 'Notification Status', 'sprout-invoices' ) );
		$columns['title'] = __( 'Invoice', 'sprout-invoices' );
		$columns['status'] = __( 'Status', 'sprout-invoices' );
		$columns['number'] = __( 'Invoice #', 'sprout-invoices' );
		$columns['dates'] = __( 'Dates', 'sprout-invoices' );
		$columns['total'] = __( 'Paid', 'sprout-invoices' );
		$columns['client'] = __( 'Client', 'sprout-invoices' );
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

		if ( ! is_a( $invoice, 'SI_Invoice' ) ) {
			return; // return for that temp post
		}
		switch ( $column_name ) {
			case 'doc_link':
				$estimate_id = $invoice->get_estimate_id();
				if ( $estimate_id ) {
					printf( '<a class="doc_link" title="%s" href="%s">%s</a>', __( 'Invoice\'s Estimate', 'sprout-invoices' ), get_edit_post_link( $estimate_id ), '<div class="dashicons icon-sproutapps-estimates"></div>' );
				}
			break;
			case 'dates':

				printf( __( '<time>Issued: <b>%s</b></time>', 'sprout-invoices' ), date_i18n( get_option( 'date_format' ), $invoice->get_issue_date() ) );
				echo '<br/>';
				$due_date = $invoice->get_due_date();
				if ( $due_date ) {
					printf( __( '<small><time>Due: <b>%s</b></time></small>', 'sprout-invoices' ), date_i18n( get_option( 'date_format' ), $due_date ) );
				}

			break;
			case 'number':

				printf( '<a title="%s" href="%s">%s</a>', __( 'View Invoice', 'sprout-invoices' ), get_edit_post_link( $id ), $invoice->get_invoice_id() );

			break;
			case 'status':

				self::status_change_dropdown( $id );

			break;
			case 'notification_status':

				if ( ! si_doc_notification_sent() ) {
					printf( '<mark class="helptip notification_status_wrap %1$s" title="%2$s">&nbsp;</mark>', 'not_sent', __( 'Not Sent', 'sprout-invoices' ) );
				} elseif ( si_doc_notification_sent() && ! si_was_doc_viewed() ) {
					printf( '<mark class="helptip notification_status_wrap %s" title="%2$s">&nbsp;</mark>', 'sent', __( 'Sent, Invoice Not Viewed', 'sprout-invoices' ) );
				} elseif ( si_doc_notification_sent() && si_was_doc_viewed() ) {
					printf( '<mark class="helptip notification_status_wrap %s" title="%2$s">&nbsp;</mark>', 'sent_viewed', __( 'Sent, and Viewed', 'sprout-invoices' ) );
				} else {
					printf( '<mark class="helptip notification_status_wrap %s" title="%2$s">&nbsp;</mark>', 'danger', __( 'Not Sure', 'sprout-invoices' ) );
				}

			break;

			case 'total':
				$total = $invoice->get_payments_total();
				printf( '%s <span class="description">(%s %s)</span>', sa_get_formatted_money( $total ), __( 'of', 'sprout-invoices' ), sa_get_formatted_money( $invoice->get_total(), $invoice->get_id() ) );

				if ( 0.00 < $total ) {
					echo '<div class="row-actions">';
					printf( '<a class="payments_link" title="%s" href="%s&s=%s">%s</a>', __( 'Review payments.', 'sprout-invoices' ), get_admin_url( '','/edit.php?post_type=sa_invoice&page=sprout-apps/invoice_payments' ), $id, __( 'Payments', 'sprout-invoices' ) );
				}

			break;

			case 'client':
				$client = ( $invoice->get_client_id() ) ? $invoice->get_client() : false ;
				if ( is_a( $client, 'SI_Client' ) ) {
					$client = $invoice->get_client();
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
	public static function filter_post_states( $post_states = array(), WP_Post $post ) {
		if ( get_post_type( $post ) == SI_Invoice::POST_TYPE ) {
			$post_states = array();
			$invoice = SI_Invoice::get_instance( $post->ID );
			if ( $invoice->get_status() == SI_Invoice::STATUS_TEMP ) {
				// FUTURE show "New" with some sort of logic
				// $post_states[$invoice->get_status()] = __( 'New', 'sprout-invoices' );
			}
		}
		return $post_states;
	}

	public static function filter_admin_search( $meta_search = '', $post_type = '' ) {
		if ( SI_Invoice::POST_TYPE !== $post_type ) {
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

	///////////////
	// Admin bar //
	///////////////

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'edit_invoices',
			'title' => __( 'Invoices', 'sprout-invoices' ),
			'href' => admin_url( 'edit.php?post_type='.SI_Invoice::POST_TYPE ),
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
				'title' => __( 'Manage Invoices', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p><p>%s</p>', __( 'The status on the invoice table view can be updated without having to go the edit screen by click on the current status and selecting a new one.', 'sprout-invoices' ), __( 'Payments are tallied and shown in the Paid column. Hovering over the invoice row will show a Payments link.', 'sprout-invoices' ),  __( 'If the invoice has an associated estimate the icon linking to the edit page of the estimate will show in the last column.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'edit-invoices',
				'title' => __( 'Editing Invoices', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p><a href="%s">%s</a></p>', __( 'Editing invoices is intentionally easy to do but a review here would exhaust this limited space. Please review the knowledgebase for a complete overview.', 'sprout-invoices' ), 'https://sproutapps.co/support/knowledgebase/sprout-invoices/invoices/', __( 'Knowledgebase Article', 'sprout-invoices' ) ),
			) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', __( 'For more information:', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/invoices/', __( 'Documentation', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', si_get_sa_link( 'https://sproutapps.co/support/' ), __( 'Support', 'sprout-invoices' ) )
			);
		}
	}
}
