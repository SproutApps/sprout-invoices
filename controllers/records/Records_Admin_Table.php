<?php

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if ( class_exists( 'SI_Records_Table' ) ) {
	return;
}
class SI_Records_Table extends WP_List_Table {
	protected static $post_type = SI_Record::POST_TYPE;

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
				'singular' => 'record',     // singular name of the listed records
				'plural' => 'records', // plural name of the listed records
				'ajax' => false     // does this table support ajax?
			) );

	}

	function get_views() {
		global $post_type_object, $locked_post_status, $avail_post_stati;

		$post_type = $post_type_object->name;

		if ( !empty($locked_post_status) )
			return array();

		$status_links = array();
		$num_posts = wp_count_posts( $post_type, 'readable' );
		$class = '';
		$allposts = '';

		$current_user_id = get_current_user_id();

		if ( $this->user_posts_count ) {
			if ( isset( $_GET['author'] ) && ( $_GET['author'] == $current_user_id ) )
				$class = ' class="current"';
			$status_links['mine'] = "<a href='edit.php?post_type=$post_type&author=$current_user_id'$class>" . sprintf( _nx( 'Mine <span class="count">(%s)</span>', 'Mine <span class="count">(%s)</span>', $this->user_posts_count, 'posts' ), number_format_i18n( $this->user_posts_count ) ) . '</a>';
			$allposts = '&all_posts=1';
		}

		$total_posts = array_sum( (array) $num_posts );

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( array('show_in_admin_all_list' => false) ) as $state )
			$total_posts -= $num_posts->$state;

		$class = empty( $class ) && empty( $_REQUEST['post_status'] ) && empty( $_REQUEST['show_sticky'] ) ? ' class="current"' : '';
		$status_links['all'] = "<a href='edit.php?post_type=$post_type{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

		foreach ( get_post_stati(array('show_in_admin_status_list' => true), 'objects') as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( !in_array( $status_name, $avail_post_stati ) )
				continue;

			if ( empty( $num_posts->$status_name ) )
				continue;

			if ( isset($_REQUEST['post_status']) && $status_name == $_REQUEST['post_status'] )
				$class = ' class="current"';

			$status_links[$status_name] = "<a href='edit.php?post_status=$status_name&amp;post_type=$post_type'$class>" . sprintf( translate_nooped_plural( $status->label_count, $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}

		if ( ! empty( $this->sticky_posts_count ) ) {
			$class = ! empty( $_REQUEST['show_sticky'] ) ? ' class="current"' : '';

			$sticky_link = array( 'sticky' => "<a href='edit.php?post_type=$post_type&amp;show_sticky=1'$class>" . sprintf( _nx( 'Sticky <span class="count">(%s)</span>', 'Sticky <span class="count">(%s)</span>', $this->sticky_posts_count, 'posts' ), number_format_i18n( $this->sticky_posts_count ) ) . '</a>' );

			// Sticky comes after Publish, or if not listed, after All.
			$split = 1 + array_search( ( isset( $status_links['publish'] ) ? 'publish' : 'all' ), array_keys( $status_links ) );
			$status_links = array_merge( array_slice( $status_links, 0, $split ), $sticky_link, array_slice( $status_links, $split ) );
		}

		return $status_links;
	}

	function extra_tablenav( $which ) {
		global $post_type_object, $cat;
		$term_id = 0;
		?>
		<div class="alignleft actions"> <?php

		if ( 'top' == $which && !is_singular() ) {

			$this->months_dropdown( self::$post_type );

			if ( is_object_in_taxonomy( self::$post_type, SI_Record::TAXONOMY ) ) {
				$term_id = ( isset( $_GET[SI_Record::TAXONOMY] ) ) ? $_GET[SI_Record::TAXONOMY] : 0 ;
				$dropdown_options = array(
					'taxonomy' => SI_Record::TAXONOMY,
					'show_option_all' => __( 'View all types' ),
					'hide_empty' => 0,
					'hierarchical' => 1,
					'show_count' => 0,
					'orderby' => 'name',
					'selected' => $term_id,
					'name' => SI_Record::TAXONOMY
				);
				wp_dropdown_categories( $dropdown_options );
			}
			do_action( 'restrict_manage_posts' );
			submit_button( __( 'Filter' ), 'secondary', false, false, array( 'id' => 'post-query-submit' ) );

			// Purge
			if ( count( $this->items ) > 0 ) {
				if ( isset( $_GET[SI_Record::TAXONOMY] ) && $_GET[SI_Record::TAXONOMY] ) {
					$term = get_term( $_GET[SI_Record::TAXONOMY], SI_Record::TAXONOMY );
					$button_label = __('Purge') . ' ' . $term->name . ' ' . __('Type');
				}
				$button_label = ( isset( $button_label ) ) ? $button_label : __('Purge All Types') ;
				printf( '<button type="submit" name="purge_records" class="button" value="%s">%s</button>', $term_id, $button_label );
				printf( '<input type="hidden" name="%s" value="%s" />', SI_Internal_Records::RECORD_PURGE_NONCE, wp_create_nonce( SI_Internal_Records::RECORD_PURGE_NONCE ) );
			}

		} ?>

		</div> <?php
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
			return apply_filters( 'si_mngt_record_column_'.$column_name, $item ); // do action for those columns that are filtered in
		}
	}

	function column_recorded( $item ) {
		$name = date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $item->post_date ) );
		echo esc_html( $name );
	}


	/**
	 *
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array   $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 */
	function column_type( $item ) {
		$record = SI_Record::get_instance( $item->ID );
		echo esc_html( $record->get_type() );

	}

	function column_title( $item ) {
		$name = $item->post_title;
		echo esc_html( $name );
	}

	function column_associate( $item ) {
		$record = SI_Record::get_instance( $item->ID );
		$associate_id = $record->get_associate_id();
		if ( $associate_id > 1 ) {
			echo '<a href="'.get_edit_post_link( $associate_id ).'">'.get_the_title($associate_id).' #'.$associate_id.'</a>';
		}
	}

	function column_data( $item ) {
		$record = SI_Record::get_instance( $item->ID );
		$data = $record->get_data();
		if ( $data != '' ) {
			?>
				<a href="#TB_inline?width=900&height=600&inlineId=data_id_<?php echo esc_attr( $item->ID ); ?>" class="thickbox button" title="<?php echo esc_attr( $item->post_title ); ?> <?php _e( 'Data', 'sprout-invoices' ) ?>"><?php _e( 'View Data', 'sprout-invoices' ) ?></a>
				<?php if ( is_array( $data ) ): ?>
					<div id="data_id_<?php echo esc_attr( $item->ID ); ?>" style="display:none;"><pre style="white-space:pre-wrap; text-align: left; font: normal normal 11px/1.4 menlo, monaco, monospaced; padding: 5px;"><?php print_r( $data ) ?></pre></div>
				<?php else: ?>
					<div id="data_id_<?php echo esc_attr( $item->ID ); ?>" style="display:none;"><?php echo apply_filters( 'the_content', $data ) ?></div>
				<?php endif ?>
			<?php
		}
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
			'recorded'  => __( 'Date', 'sprout-invoices' ),
			'title'  => __( 'Subject', 'sprout-invoices' ),
			'data'  => __( 'Data', 'sprout-invoices' ),
			'associate'  => __( 'Association', 'sprout-invoices' ),
			'type' => __( 'Type', 'sprout-invoices' )
		);
		return apply_filters( 'si_mngt_record_columns', $columns );
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
		return apply_filters( 'si_mngt_record_sortable_columns', $sortable_columns );
	}


	/**
	 * Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 * */
	function get_bulk_actions() {
		$actions = array();
		return apply_filters( 'si_mngt_records_bulk_actions', $actions );
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
		$per_page = 100;


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

		$args=array(
			'post_type' => SI_Record::POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => $per_page,
			'paged' => $this->get_pagenum(),
		);
		// Search
		if ( isset( $_GET['s'] ) && $_GET['s'] != '' ) {
			$args = array_merge( $args, array( 's' => sanitize_text_field( $_GET['s'] ) ) );
		}
		// Filter by date
		if ( isset( $_GET['m'] ) && $_GET['m'] != '' ) {
			$args = array_merge( $args, array( 'm' => sanitize_text_field( $_GET['m'] ) ) );
		}
		// Filter by taxonomy
		if ( isset( $_GET[SI_Record::TAXONOMY] ) && $_GET[SI_Record::TAXONOMY] && $_GET[SI_Record::TAXONOMY] != '' ) {
			$tax_query = array(
					'tax_query' => array(
							array(
								'taxonomy' => SI_Record::TAXONOMY,
								'field' => 'id',
								'terms' => $_GET[SI_Record::TAXONOMY]
							)
						)
				);
			$args = array_merge( $args, $tax_query );
		}
		$records = new WP_Query( $args );

		/**
		 * REQUIRED. *Sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = apply_filters( 'si_mngt_records_items', $records->posts );

		/**
		 * REQUIRED. Register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
				'total_items' => $records->found_posts,                //WE have to calculate the total number of items
				'per_page'  => $per_page,                    //WE have to determine how many items to show on a page
				'total_pages' => $records->max_num_pages   //WE have to calculate the total number of pages
			) );
	}

}