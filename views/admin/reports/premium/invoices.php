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
				<label><?php self::_e('From: ') ?><input type="date" name="start_date" id="start_date" value=""></label>
				<label><?php self::_e('To: ') ?><input type="date" name="end_date" id="end_date" value=""></label>
			</div>
		</div>
		<table id="si_reports_table" class="stripe hover wp-list-table widefat"> 
			<thead>
				<tr>
					<th><?php self::_e('ID') ?></th>
					<th><?php self::_e('Status') ?></th>
					<th><?php self::_e('Issue Date') ?></th>
					<th class="row-title"><?php self::_e('Subject') ?></th>
					<th><?php self::_e('Client') ?></th>
					<th><?php self::_e('Invoiced') ?></th>
					<th><?php self::_e('Paid') ?></th>
					<th><?php self::_e('Balance') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php 
					$table_total_invoiced = 0;
					$table_total_paid = 0;
					$table_total_balance = 0;
					
					$filter = ( isset( $_REQUEST['post_status'] ) ) ? $_REQUEST['post_status'] : 'any';

					$showpage = ( isset( $_GET['showpage'] ) ) ? (int)$_GET['showpage']+1 : 1 ;
					$args = array(
						'post_type' => SI_Invoice::POST_TYPE,
						'post_status' => $filter,
						'posts_per_page' => apply_filters( 'si_reports_show_records', 2500, 'invoices' ),
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

						$table_total_invoiced += si_get_invoice_calculated_total();
						$table_total_paid += si_get_invoice_payments_total();
						$table_total_balance += si_get_invoice_balance();
						$client_name = ( si_get_invoice_client_id() ) ? sprintf( '<a href="%s">%s</a>', get_edit_post_link( si_get_invoice_client_id() ), get_the_title( si_get_invoice_client_id() ) ) : self::__('N/A') ; ?>
						<tr> 
							<td><?php the_ID() ?></td>
							<td><span class="si_status <?php si_invoice_status() ?>"><?php si_invoice_status() ?></span></td>
							<td><?php si_invoice_issue_date() ?></td>
							<td><?php printf( '<a href="%s">%s</a>', get_edit_post_link( get_the_ID() ), get_the_title( get_the_ID() ) ) ?></td>
							<td><?php echo $client_name ?></td>
							<td><?php si_invoice_calculated_total() ?></td>
							<td><?php si_invoice_payments_total() ?></td>
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
					<th colspan="5"><?php self::_e('Totals') ?></th>
					<th><?php sa_formatted_money( $table_total_invoiced ) ?></th>
					<th><?php sa_formatted_money( $table_total_paid ) ?></th>
					<th><?php sa_formatted_money( $table_total_balance ) ?></th>
				</tr>
			</tfoot>
		</table>
	</div>
</div>