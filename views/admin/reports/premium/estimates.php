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
				<label><?php self::_e( 'From: ' ) ?><input type="date" name="start_date" id="start_date" value=""></label>
				<label><?php self::_e( 'To: ' ) ?><input type="date" name="end_date" id="end_date" value=""></label>
			</div>
		</div>
		<table id="si_reports_table" class="stripe hover wp-list-table widefat"> 
			<thead>
				<tr>
					<th><?php self::_e( 'ID' ) ?></th>
					<th><?php self::_e( 'Status' ) ?></th>
					<th><?php self::_e( 'Issue Date' ) ?></th>
					<th class="row-title"><?php self::_e( 'Subject' ) ?></th>
					<th><?php self::_e( 'Invoice' ) ?></th>
					<th><?php self::_e( 'Client' ) ?></th>
					<th><?php self::_e( 'Total' ) ?></th>
					<th><?php self::_e( 'Subtotal' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$table_total_estimated = 0;
					$table_subtotal = 0;

					$filter = ( isset( $_REQUEST['post_status'] ) ) ? $_REQUEST['post_status'] : 'any';

					$showpage = ( isset( $_GET['showpage'] ) ) ? (int)$_GET['showpage'] + 1 : 1 ;
					$args = array(
						'post_type' => SI_Estimate::POST_TYPE,
						'post_status' => $filter,
						'posts_per_page' => apply_filters( 'si_reports_show_records', 2500, 'estimates' ),
						'paged' => $showpage
						);

					set_time_limit( 0 ); // run script forever
					// Add a progress bar to show table record collection.
					echo '<tr class="odd" id="progress_row"><td valign="top" colspan="8" class="dataTables_empty"><div id="rows_progress" style="width:100%;border:1px solid #ccc;"></div> <div id="table_progress">'.self::__( 'Preparing rows...' ).'</div></td></tr>';

					$records = new WP_Query( $args );

					$i = 0;
					while ( $records->have_posts() ) : $records->the_post();

						// Calculate the percentage
						$i++;
						$percent = intval( $i / $records->found_posts * 100 ).'%';
						// Javascript for updating the progress bar and information
						echo '<script language="javascript" id="progress_js">
						document.getElementById("rows_progress").innerHTML="<div style=\"width:'.$percent.';background-color:#ddd;\">&nbsp;</div>";
						document.getElementById("table_progress").innerHTML="'.sprintf( self::__( '%o records(s) of %o added.' ), $i, $records->found_posts ).'";
						document.getElementById("progress_js").remove();
						</script>';

						$table_total_estimated += si_get_estimate_total();
						$table_subtotal += si_get_estimate_subtotal();
						$invoice_name = ( si_get_estimate_invoice_id() ) ? sprintf( '<a href="%s">%s</a>', get_edit_post_link( si_get_estimate_invoice_id() ), get_the_title( si_get_estimate_invoice_id() ) ) : self::__( 'N/A' );
						$client_name = ( si_get_estimate_client_id() ) ? sprintf( '<a href="%s">%s</a>', get_edit_post_link( si_get_estimate_client_id() ), get_the_title( si_get_estimate_client_id() ) ) : self::__( 'N/A' ); ?>
						<tr> 
							<td><?php echo si_get_estimate_id( get_the_id() ) ?></td>
							<td><span class="si_status estimate_status <?php si_estimate_status() ?>"><?php si_estimate_status() ?></span></td>
							<td><?php si_estimate_issue_date() ?></td>
							<td><?php printf( '<a href="%s">%s</a>', get_edit_post_link( get_the_ID() ), get_the_title( get_the_ID() ) ) ?></td>
							<td><?php echo $invoice_name; ?></td>
							<td><?php echo $client_name; ?></td>
							<td><?php si_estimate_total() ?></td>
							<td><?php si_estimate_subtotal() ?></td>
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
					<th colspan="6"><?php self::_e( 'Totals' ) ?></th>
					<th><?php sa_formatted_money( $table_total_estimated ) ?></th>
					<th><?php sa_formatted_money( $table_subtotal ) ?></th>
				</tr>
			</tfoot>
		</table>
	</div>
</div>