<div class="wrap">

	<?php screen_icon(); ?>
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php do_action( 'si_settings_page_sub_heading_'.$_GET['page'] ); ?>
	</div>
	<div id="si_report" class="clearfix">
		<div class="tablenav top">
			<div class="alignleft">
				<label><?php self::_e('From: ') ?><input type="date" name="start_date" id="start_date" value=""></label>
				<label><?php self::_e('To: ') ?><input type="date" name="end_date" id="end_date" value=""></label>
			</div>
		</div>
		<table id="si_reports_table" class="stripe hover wp-list-table widefat"> 
			<thead>
				<tr>
					<th><?php self::_e('ID') ?></th>
					<th><?php self::_e('Name') ?></th>
					<th><?php self::_e('Date') ?></th>
					<th><?php self::_e('#Estimates') ?></th>
					<th><?php self::_e('#Accepted') ?></th>
					<th><?php self::_e('#Invoices') ?></th>
					<th><?php self::_e('#Paid') ?></th>
					<th><?php self::_e('Total Invoiced') ?></th>
					<th><?php self::_e('Total Payments') ?></th>
					<th><?php self::_e('Total Outstanding') ?></th>
					<th><?php self::_e('Total Written-off') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php 
					$table_total_estimate_count = 0;
					$table_total_estimate_complete_count = 0;
					$table_total_invoice_count = 0;
					$table_total_invoices_complete_count = 0;
					$table_total_invoiced = 0;
					$table_total_payments = 0;
					$table_total_outstanding = 0;
					$table_total_written_off = 0;
					
					$filter = ( isset( $_REQUEST['post_status'] ) ) ? $_REQUEST['post_status'] : 'any';

					$showpage = ( isset( $_GET['showpage'] ) ) ? (int)$_GET['showpage']+1 : 1 ;
					$args = array(
						'post_type' => SI_Client::POST_TYPE,
						'post_status' => $filter,
						'posts_per_page' => apply_filters( 'si_reports_show_records', 2500, 'estimates' ),
						'paged' => $showpage
						);
					
					set_time_limit(0); // run script forever
					// Add a progress bar to show table record collection.
					echo '<tr class="odd" id="progress_row"><td valign="top" colspan="8" class="dataTables_empty"><div id="rows_progress" style="width:100%;border:1px solid #ccc;"></div> <div id="table_progress">'.self::__('Preparing rows...').'</div></td></tr>';

					$records = new WP_Query( $args );

					$i = 0;
					while ( $records->have_posts() ) : $records->the_post();
						// Calculate the percentage
						$i++;
						$percent = intval($i/$records->found_posts * 100)."%";
						// Javascript for updating the progress bar and information
						echo '<script language="javascript" id="progress_js">
						document.getElementById("rows_progress").innerHTML="<div style=\"width:'.$percent.';background-color:#ddd;\">&nbsp;</div>";
						document.getElementById("table_progress").innerHTML="'.sprintf( self::__('%o records(s) of %o added.'), $i, $records->found_posts ).'";
						document.getElementById("progress_js").remove();
						</script>';

						$client = SI_Client::get_instance( get_the_ID() );
						$estimates = $client->get_estimates();
						$invoices = $client->get_invoices();

						$number_estimate = count( $estimates );
						$number_invoices = count( $invoices );

						$number_estimate_complete = 0;
						if ( !empty( $estimates ) ) {
							foreach ( $estimates as $estimate_id ) {
								if ( get_post_status( $estimate_id ) == SI_Estimate::STATUS_APPROVED ) {
									$number_estimate_complete += 1;
								}
							}
						}

						$number_invoices_complete = 0;
						$total_invoiced = 0;
						$total_payments = 0;
						$total_outstanding = 0;
						$total_written_off = 0;
						if ( !empty( $invoices ) ) {
							foreach ( $invoices as $invoice_id ) {
								$invoice = SI_Invoice::get_instance( $invoice_id );
								if ( $invoice->get_status() == SI_Invoice::STATUS_PAID ) {
									$number_invoices_complete += 1;
									// paid invoices may have a balance but shouldn't
									// total written off is the balance since it's marked as paid with a balance.
									$total_outstanding += $invoice->get_balance();
									$total_written_off += $invoice->get_balance();
								}
								elseif ( $invoice->get_status() == SI_Invoice::STATUS_WO ) {
									// written off invoices do not contribute to the total outstanding
									$total_written_off += $invoice->get_balance();
								}
								else {
									// all others with a balance contribute to the outstanding balance
									// they don't contribute to any written off totals and they're 
									// not complete
									$total_outstanding += $invoice->get_balance();
								}
								$total_invoiced += $invoice->get_total();
								$total_payments += $invoice->get_payments_total();
							}
						}

						// Add to the totals for the footer
						$table_total_estimate_count += $number_estimate;
						$table_total_estimate_complete_count += $number_estimate_complete;
						$table_total_invoice_count += $number_invoices;
						$table_total_invoices_complete_count += $number_invoices_complete;
						$table_total_invoiced += $total_invoiced;
						$table_total_payments += $total_payments;
						$table_total_outstanding += $total_outstanding;
						$table_total_written_off += $total_written_off; ?>
						<tr> 
							<td><?php the_ID() ?></td>
							<td><?php printf( '<a href="%s">%s</a>', get_edit_post_link( get_the_ID() ), get_the_title( get_the_ID() ) ) ?></td>
							<td><?php echo date_i18n( get_option('date_format'), strtotime( $client->get_post_date() ) ) ?></td>
							<td><?php echo $number_estimate ?></td>
							<td><?php echo $number_estimate_complete ?></td>
							<td><?php echo $number_invoices ?></td>
							<td><?php echo $number_invoices_complete ?></td>
							<td><?php sa_formatted_money( $total_invoiced ) ?></td>
							<td><?php sa_formatted_money( $total_payments ) ?></td>
							<td><?php sa_formatted_money( $total_outstanding ) ?></td>
							<td><?php sa_formatted_money( $total_written_off ) ?></td>
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
					<th colspan="3"><?php self::_e('Totals') ?></th>
					<th><?php echo $table_total_estimate_count ?></th>
					<th><?php echo $table_total_estimate_complete_count ?></th>
					<th><?php echo $table_total_invoice_count ?></th>
					<th><?php echo $table_total_invoices_complete_count ?></th>
					<th><?php sa_formatted_money( $table_total_invoiced ) ?></th>
					<th><?php sa_formatted_money( $table_total_payments ) ?></th>
					<th><?php sa_formatted_money( $table_total_outstanding ) ?></th>
					<th><?php sa_formatted_money( $table_total_written_off ) ?></th>
				</tr>
			</tfoot>
		</table>
	</div>
</div>