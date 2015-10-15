<?php

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class SI_Payments_Table extends WP_List_Table {
	protected static $post_type = SI_Payment::POST_TYPE;

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
				'singular' => 'payment',     // singular name of the listed records
				'plural' => 'payments', // plural name of the listed records
				'ajax' => false     // does this table support ajax?
			) );

	}

	function get_views() {

		$status_links = array();
		$num_posts = wp_count_posts( self::$post_type, 'readable' );
		$allposts = '';

		$total_posts = array_sum( (array) $num_posts );

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state )
			$total_posts -= $num_posts->$state;

		$class = empty( $_REQUEST['post_status'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href='edit.php?post_type=sa_invoice&page=sprout-apps/invoice_payments{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class = '';
			
			$status_name = $status->name;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset( $_REQUEST['post_status'] ) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			// replace "Published" with "Complete".
			$label = str_replace( 'Published', 'Complete', translate_nooped_plural( $status->label_count, $num_posts->$status_name ) );
			$status_links[$status_name] = "<a href='edit.php?post_type=sa_invoice&page=sprout-apps/invoice_payments&post_status=$status_name'$class>" . sprintf( $label, number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		return $status_links;
	}

	function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions"> <?php
		if ( 'top' == $which && !is_singular() ) {

			$this->months_dropdown( self::$post_type );

			submit_button( __( 'Filter' ), 'secondary', false, false, array( 'id' => 'post-query-submit' ) );
		} ?>
		</div>
		<?php
	}


	/**
	 *
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @param array   $item        A singular item (one full row's worth of data)
	 * @param array   $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
		default:
			return apply_filters( 'si_mngt_payments_column_'.$column_name, $item ); // do action for those columns that are filtered in
		}
	}


	/**
	 *
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array   $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 */
	function column_title( $item ) {
		// wp_delete_post( $item->ID, true );
		$payment = SI_Payment::get_instance( $item->ID );	
		$invoice_id = $payment->get_invoice_id();
		$invoice = SI_Invoice::get_instance($invoice_id);
		$client = $payment->get_client();

		//Build row actions
		$actions = array();
		if ( is_a( $invoice, 'SI_Invoice' ) ) { // Check if purchase wasn't deleted
			$actions += array(
				'invoice'    => sprintf( '<a href="%s">Invoice</a>', get_edit_post_link( $invoice_id ) )
			);
		}
		if ( is_a( $client, 'SI_Client' ) ) { // Check if purchase wasn't deleted
			$actions += array(
				'client'  => sprintf( '<a href="%s">'.__( 'Client', 'sprout-invoices' ).'</a>', get_edit_post_link( $client->get_ID() ) ),
			);
		} 
		if ( empty( $actions ) ) {
			$actions = array(
				'error'    => __( 'Associated records cannot be found.', 'sprout-invoices' ),
			);
		}

		//Return the title contents
		return sprintf( '%1$s <span style="color:silver">(invoice&nbsp;id:%2$s)</span>%3$s',
			$item->post_title,
			( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_invoice_id() : __( 'unknown', 'sprout-invoices' ),
			$this->row_actions( $actions )
		);
	}

	function column_total( $item ) {
		$payment = SI_Payment::get_instance( $item->ID );
		$invoice_id = $payment->get_invoice_id();
		if ( $invoice_id ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
			if ( is_a( $invoice, 'SI_Invoice' ) ) {
				echo '<strong>'.__( 'Payment Total', 'sprout-invoices' ).':</strong> '.sa_get_formatted_money( $payment->get_amount() ).'<br/>';
				echo '<em>'.__( 'Invoice Balance', 'sprout-invoices' ).': '.sa_get_formatted_money( $invoice->get_balance(), $invoice->get_id() ).'</em><br/>';
				echo '<em>'.__( 'Invoice Total', 'sprout-invoices' ).': '.sa_get_formatted_money( $invoice->get_total(), $invoice->get_id() ).'</em>';
			}
			else {
				_e( 'No invoice found', 'sprout-invoices' );
			}
		}
		else {
			printf( __( 'No invoice associated with this payment.', 'sprout-invoices' ) );
		}
	}

	function column_data( $item ) {
		$payment_id = $item->ID;
		$payment = SI_Payment::get_instance( $payment_id );
		$method = $payment->get_payment_method();
		$data = $payment->get_data();
		$detail = '';
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = sprintf( '<pre id="payment_detail_%s" style="width="500px"; white-space:pre-wrap; text-align: left; font: normal normal 11px/1.4 menlo, monaco, monospaced; padding: 5px;">%s</pre>', $payment_id, print_r( $value, true ) );
				}
				if ( is_string( $value ) ) {
					$detail .= '<dl>
						<dt><b>'.ucfirst(str_replace( '_', ' ', $key )).'</b></dt>
						<dd>'.$value.'</dd>
					</dl>';
				}
				
			}
		}

		//Build row actions
		$actions = array(
			'detail'    => sprintf( '<a href="#TB_inline?width=900&height=600&inlineId=data_id_%s" class="thickbox button" title="'.__( 'Transaction Data', 'sprout-invoices' ).'">'.__( 'Transaction Data', 'sprout-invoices' ).'</a><div id="data_id_%s" style="display:none;">%s</div>', $payment_id, $payment_id, $detail )
		);

		//Return the title contents
		return sprintf( '%1$s %2$s', $method, $this->row_actions( $actions ) );
	}

	function column_status( $item ) {
		$payment_id = $item->ID;
		
		$actions = array();
		if ( in_array( $item->post_status, array( SI_Payment::STATUS_PENDING, SI_Payment::STATUS_AUTHORIZED, SI_Payment::STATUS_COMPLETE, SI_Payment::STATUS_PARTIAL ) ) ) {
			
			$actions['trash'] = '<a href="#TB_inline?width=900&height=260&inlineId=void_payment_'.$payment_id.'" class="thickbox" id="void_link_'.$payment_id.'" title="'.__( 'Void Payment', 'sprout-invoices' ).'">'.__( 'Void Payment', 'sprout-invoices' ).'</a>';

			if ( $item->post_status == SI_Payment::STATUS_AUTHORIZED ) {
				$actions['attempt_capture'] = '<a href="javascript:void(0)" class="si_attempt_capture" ref="'.$payment_id.'">'.__( 'Attempt Capture', 'sprout-invoices' ).'</a>';
			}

			if ( $item->post_status == SI_Payment::STATUS_PENDING ) {
				$actions['mark_complete'] = '<a href="javascript:void(0)" class="si_mark_complete" ref="'.$payment_id.'">'.__( 'Mark Complete', 'sprout-invoices' ).'</a>';
			}
		}

		$void_form = '<div id="void_payment_'.$payment_id.'" style="display:none;"><p><textarea name="transaction_data_'.$payment_id.'" id="transaction_data_'.$payment_id.'" style="width:99%" rows="10" placeholder="'.__( 'These notes will be added to the transaction data.', 'sprout-invoices' ).'"></textarea><a href="javascript:void(0)" class="si_void_payment button" id="'.$payment_id.'_void" ref="'.$payment_id.'">'.__( 'Void Payment', 'sprout-invoices' ).'</a></p></div>';

		$status = ucfirst( str_replace( 'publish', 'complete', $item->post_status ) );
		$status .= '<br/><span style="color:silver">';
		$status .= mysql2date( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), $item->post_date );
		$status .= '</span>';
		$status .= $void_form;
		$status .= $this->row_actions( $actions );
		return $status;
	}


	/**
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 * */
	function get_columns() {
		$columns = array(
			'status'  => __( 'Status', 'sprout-invoices' ),
			'title'  => __( 'Payment', 'sprout-invoices' ),
			'total'  => __( 'Totals', 'sprout-invoices' ),
			'data'  => __( 'Data', 'sprout-invoices' )
		);
		return apply_filters( 'si_mngt_payments_columns', $columns );
	}

	/**
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 * */
	function get_sortable_columns() {
		$sortable_columns = array(
		);
		return apply_filters( 'si_mngt_payments_sortable_columns', $sortable_columns );
	}


	/**
	 * Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 * */
	function get_bulk_actions() {
		$actions = array();
		return apply_filters( 'si_mngt_payments_bulk_actions', $actions );
	}


	/**
	 * Prep data.
	 *
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 * */
	function prepare_items() {

		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 25;


		/**
		 * Define our column headers.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();


		/**
		 * REQUIRED. Build an array to be used by the class for column
		 * headers.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$filter = ( isset( $_REQUEST['post_status'] ) ) ? $_REQUEST['post_status'] : array( SI_Payment::STATUS_PENDING, SI_Payment::STATUS_AUTHORIZED, SI_Payment::STATUS_COMPLETE, SI_Payment::STATUS_PARTIAL, SI_Payment::STATUS_RECURRING, SI_Payment::STATUS_CANCELLED );

		$args = array(
			'post_type' => SI_Payment::POST_TYPE,
			'post_status' => $filter,
			'posts_per_page' => $per_page,
			'paged' => $this->get_pagenum()
		);
		// Check based on post_type id
		if ( isset( $_REQUEST['s'] ) && is_numeric( $_REQUEST['s'] ) ) {
			$post_id = $_REQUEST['s'];
			switch ( get_post_type( $post_id ) ) {
				case SI_Payment::POST_TYPE :
					$payment_ids = array( $post_id );
					break;

				case SI_Invoice::POST_TYPE :
					$invoice = SI_Invoice::get_instance( $post_id );
					$payment_ids = $invoice->get_payments();
					break;

				case SI_Client::POST_TYPE :
					$client = SI_Client::get_instance( $post_id );
					$payment_ids = $client->get_payments();
					break;

				default:
					$payment_ids = false;
					break;
			}
			if ( $payment_ids ) {
				$meta_query = array(
					'post__in' => $payment_ids,
				);
				$args = array_merge( $args, $meta_query );
			}
		}
		// Search
		elseif ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
			$args = array_merge( $args, array( 's' => sanitize_text_field( $_GET['s'] ) ) );
		}
		// Filter by date
		if ( isset( $_GET['m'] ) && $_GET['m'] != '' ) {
			$args = array_merge( $args, array( 'm' => sanitize_text_field( $_GET['m'] ) ) );
		}
		
		$payments = new WP_Query( $args );

		/**
		 * REQUIRED. *Sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = apply_filters( 'si_mngt_payments_items', $payments->posts );

		/**
		 * REQUIRED. Register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
				'total_items' => $payments->found_posts,                //WE have to calculate the total number of items
				'per_page'  => $per_page,                    //WE have to determine how many items to show on a page
				'total_pages' => $payments->max_num_pages   //WE have to calculate the total number of pages
			) );
	}

}
