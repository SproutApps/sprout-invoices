<div class="wrap">
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php
			$page = ( isset( $_GET['page'] ) ) ? $_GET['page'] : '' ;
			do_action( 'si_settings_page_sub_heading_'.$page ); ?>
	</div>
	<div id="si_report" class="clearfix">
		<div class="tablenav top">
			<div class="alignleft">
				<label><?php _e( 'From: ', 'sprout-invoices' ) ?><input type="date" name="start_date" id="start_date" value=""></label>
				<label><?php _e( 'To: ', 'sprout-invoices' ) ?><input type="date" name="end_date" id="end_date" value=""></label>
			</div>
		</div>
		<table id="si_reports_table" class="stripe hover wp-list-table widefat"> 
			<thead>
				<tr>
					<th><?php _e( 'ID', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Status', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Issue Date', 'sprout-invoices' ) ?></th>
					<th class="row-title"><?php _e( 'Subject', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Client', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Invoiced', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Paid', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Tax', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Fees', 'sprout-invoices' ) ?></th>
					<?php if ( apply_filters( 'si_show_gst_in_reports', class_exists( 'SI_Hearts_Canada' ) ) ) :  ?>
						<th><?php _e( 'GST', 'sprout-invoices' ) ?></th>
					<?php endif ?>
					<th><?php _e( 'Balance', 'sprout-invoices' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$table_total_invoiced = 0;
					$table_total_paid = 0;
					$table_total_fees = 0;
					$table_total_tax = 0;
					$table_gst_total_tax = 0;
					$table_total_balance = 0;

					$filter = ( isset( $_REQUEST['post_status'] ) ) ? $_REQUEST['post_status'] : 'any';

					$showpage = ( isset( $_GET['showpage'] ) ) ? (int) $_GET['showpage'] + 1 : 1 ;
					$args = array(
						'post_type' => SI_Invoice::POST_TYPE,
						'post_status' => $filter,
						'posts_per_page' => apply_filters( 'si_reports_show_records', 2500, 'invoices' ),
						'paged' => $showpage,
						);

					set_time_limit( 0 ); // run script forever
					// Add a progress bar to show table record collection.
					echo '<tr class="odd" id="progress_row"><td valign="top" colspan="8" class="dataTables_empty"><div id="rows_progress" style="width:100%;border:1px solid #ccc;"></div> <div id="table_progress">'.__( 'Preparing rows...', 'sprout-invoices' ).'</div></td></tr>';

					$records = new WP_Query( $args );

					$i = 0;
					while ( $records->have_posts() ) : $records->the_post();

						// Calculate the percentage
						$i++;
						$percent = intval( $i / $records->found_posts * 100 ).'%';
						// Javascript for updating the progress bar and information
						echo '<script language="javascript" id="progress_js">
						document.getElementById("rows_progress").innerHTML="<div style=\"width:'.$percent.';background-color:#ddd;\">&nbsp;</div>";
						document.getElementById("table_progress").innerHTML="'.sprintf( __( '%o records(s) of %o added.', 'sprout-invoices' ), $i, $records->found_posts ).'";
						document.getElementById("progress_js").remove();
						</script>';

						$table_total_invoiced += si_get_invoice_calculated_total();
						$table_total_paid += si_get_invoice_payments_total();
						$table_total_fees += si_get_invoice_fees_total();
						$table_total_tax += si_get_invoice_taxes_total();
						$table_total_balance += si_get_invoice_balance();
						$client_name = ( si_get_invoice_client_id() ) ? sprintf( '<a href="%s">%s</a>', get_edit_post_link( si_get_invoice_client_id() ), get_the_title( si_get_invoice_client_id() ) ) : __( 'N/A', 'sprout-invoices' );

						if ( apply_filters( 'si_show_gst_in_reports', class_exists( 'SI_Hearts_Canada' ) ) ) {
							$gst_for_line_item = 0;
							$line_items = si_get_doc_line_items();
							if ( ! empty( $line_items ) ) {
								foreach ( $line_items as $position => $data ) {
									if ( isset( $data['tax_gst'] ) && $data['tax_gst'] ) {
										$gst_for_line_item = SI_Hearts_Canada::calculate_tax( $data, 'tax_gst' );
										$table_gst_total_tax += $gst_for_line_item;
									}
								}
							}
						} ?>
						<tr> 
							<td><?php echo si_get_invoice_id( get_the_id() ) ?></td>
							<td><span class="si_status <?php si_invoice_status() ?>"><?php si_invoice_status() ?></span></td>
							<td><?php si_invoice_issue_date() ?></td>
							<td><?php printf( '<a href="%s">%s</a>', get_edit_post_link( get_the_ID() ), get_the_title( get_the_ID() ) ) ?></td>
							<td><?php echo $client_name; ?></td>
							<td><?php si_invoice_calculated_total() ?></td>
							<td><?php si_invoice_payments_total() ?></td>
							<td><?php sa_formatted_money( si_get_invoice_taxes_total() ) ?></td>
							<td><?php sa_formatted_money( si_get_invoice_fees_total() ) ?></td>
							<?php if ( apply_filters( 'si_show_gst_in_reports', class_exists( 'SI_Hearts_Canada' ) ) ) :  ?>
								<th><?php sa_formatted_money( $gst_for_line_item ) ?></th>
							<?php endif ?>
							<td><?php si_invoice_balance() ?></td>
						</tr>
						<?php
						// Send output to browser immediately
						flush();
					endwhile;
					// Remove progress row
					echo '<script language="javascript">document.getElementById("progress_row").remove();</script>'; ?>

			</tbody>
			<tfoot>
				<tr>
					<th colspan="5"><?php _e( 'Totals', 'sprout-invoices' ) ?></th>
					<th><span id="footer_total_invoices"></span>&nbsp;<?php printf( __( '(of %s)', 'sprout-invoices' ), sa_get_formatted_money( $table_total_invoiced ) ) ?></th>
					<th><span id="footer_total_paid"></span>&nbsp;<?php printf( __( '(of %s)', 'sprout-invoices' ), sa_get_formatted_money( $table_total_paid ) ) ?></th>
					<th><span id="footer_total_tax"></span>&nbsp;<?php printf( __( '(of %s)', 'sprout-invoices' ), sa_get_formatted_money( $table_total_tax ) ) ?></th>
					<th><span id="footer_total_fees"></span>&nbsp;<?php printf( __( '(of %s)', 'sprout-invoices' ), sa_get_formatted_money( $table_total_fees ) ) ?></th>
					<?php if ( apply_filters( 'si_show_gst_in_reports', class_exists( 'SI_Hearts_Canada' ) ) ) :  ?>
						<th><span id="footer_total_tax"></span>&nbsp;<?php printf( __( '(of %s)', 'sprout-invoices' ), sa_get_formatted_money( $table_gst_total_tax ) ) ?></th>
					<?php endif ?>
					<th><span id="footer_total_balance"></span>&nbsp;<?php printf( __( '(of %s)', 'sprout-invoices' ), sa_get_formatted_money( $table_total_balance ) ) ?></th>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<script type="text/javascript" charset="utf-8">
	jQuery(function($) {
		$(document).ready(function() {
			var table = $('#si_reports_table').dataTable( {
				stateSave: true,
				responsive: true,
				dom: 'B<"clearfix">lfrtip',
				buttons: [ 'copy', 'csv', 'pdf' ],
				footerCallback: function ( row, data, start, end, display ) {
					var api = this.api(), data;
		 
					// Remove the formatting to get integer data for summation
					var intVal = function ( i ) {
						return typeof i === 'string' ?
							i.replace(/[\$,]/g, '')*1 :
							typeof i === 'number' ?
								i : 0;
					};
		 
					// Invoiced total over this page
					pageTotal = api
						.column( 5, { page: 'current'} )
						.data()
						.reduce( function (a, b) {
							return intVal(a) + intVal(b);
						}, 0 );

					// Paid total over this page
					pagePaid = api
						.column( 6, { page: 'current'} )
						.data()
						.reduce( function (a, b) {
							return intVal(a) + intVal(b);
						}, 0 );

					// Tax over this page
					pageTax = api
						.column( 7, { page: 'current'} )
						.data()
						.reduce( function (a, b) {
							return intVal(a) + intVal(b);
						}, 0 );

					// Balance over this page
					pageBalance = api
						.column( 8, { page: 'current'} )
						.data()
						.reduce( function (a, b) {
							return intVal(a) + intVal(b);
						}, 0 );
		 
					// Update footer
					$( '#footer_total_invoices' ).html(
						si_js_object.currency_symbol + pageTotal.toFixed(2)
					);
					$( '#footer_total_paid' ).html(
						si_js_object.currency_symbol + pagePaid.toFixed(2)
					);
					$( '#footer_total_tax' ).html(
						si_js_object.currency_symbol + pageTax.toFixed(2)
					);
					$( '#footer_total_balance' ).html(
						si_js_object.currency_symbol + pageBalance.toFixed(2)
					);
				}
			} );

			$("#start_date").change(function() {	
				minDateFilter = new Date( this.value ).getTime();
				table.fnDraw();
			});

			$("#end_date").change(function() {
				maxDateFilter = new Date( this.value ).getTime();
				table.fnDraw();
			});

			// Date range filter
			minDateFilter = '';
			maxDateFilter = '';

			$.fn.dataTableExt.afnFiltering.push(
				function(oSettings, aData, iDataIndex) {
					if (typeof aData._date == 'undefined') {
						aData._date = new Date( aData[2] ).getTime()-(new Date( aData[2] ).getTimezoneOffset()*60000);
					}

					if (minDateFilter && !isNaN(minDateFilter)) {
						if (aData._date < minDateFilter) {
							return false;
						}
					}

					if (maxDateFilter && !isNaN(maxDateFilter)) {
						if (aData._date > maxDateFilter) {
							return false;
						}
					}

					return true;
				}
			);

		} );
	});
</script>
