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
				<label><?php _e( 'From: ', 'sprout-invoices' ) ?><input type="date" name="start_date" id="start_date" value="<?php _e( 'From... mm/dd/yyy ', 'sprout-invoices' ) ?>"></label>
				<label><?php _e( 'To: ', 'sprout-invoices' ) ?><input type="date" name="end_date" id="end_date" value=""></label>
			</div>
		</div>
		<table id="si_reports_table" class="stripe hover wp-list-table widefat">  
			<thead>
				<tr>
					<th><?php _e( 'ID', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Status', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Date', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Method', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Invoice', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Client', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Invoiced', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Paid', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Invoice Balance', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Payment Total', 'sprout-invoices' ) ?></th>
					<th><?php _e( 'Voided Total', 'sprout-invoices' ) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php 
					$table_payment_total = 0;
					$table_voided_payment_total = 0;
					
					$filter = ( isset( $_REQUEST['post_status'] ) ) ? $_REQUEST['post_status'] : 'any';

					$showpage = ( isset( $_GET['showpage'] ) ) ? (int)$_GET['showpage']+1 : 1 ;
					$args = array(
						'post_type' => SI_Payment::POST_TYPE,
						'post_status' => $filter,
						'posts_per_page' => apply_filters( 'si_reports_show_records', 2500, 'estimates' ),
						'paged' => $showpage
						);
					
					set_time_limit(0); // run script forever
					// Add a progress bar to show table record collection.
					echo '<tr class="odd" id="progress_row"><td valign="top" colspan="8" class="dataTables_empty"><div id="rows_progress" style="width:100%;border:1px solid #ccc;"></div> <div id="table_progress">'.__( 'Preparing rows...', 'sprout-invoices' ).'</div></td></tr>';
					
					$records = new WP_Query( $args );

					$i = 0;
					while ( $records->have_posts() ) : $records->the_post();
						// Calculate the percentage
						$i++;
						$percent = intval($i/$records->found_posts * 100)."%";
						// Javascript for updating the progress bar and information
						echo '<script language="javascript" id="progress_js">
						document.getElementById("rows_progress").innerHTML="<div style=\"width:'.$percent.';background-color:#ddd;\">&nbsp;</div>";
						document.getElementById("table_progress").innerHTML="'.sprintf( __( '%o records(s) of %o added.', 'sprout-invoices' ), $i, $records->found_posts ).'";
						document.getElementById("progress_js").remove();
						</script>'; 

						$payment = SI_Payment::get_instance( get_the_ID() );
						$invoice_id = $payment->get_invoice_id();

						if ( $payment->get_status() == SI_Payment::STATUS_VOID ) {
							$table_voided_payment_total += $payment->get_amount();
							$payment_total = 0;
							$payment_void_total = $payment->get_amount();
						}
						else {
							$table_payment_total += $payment->get_amount();
							$payment_total = $payment->get_amount();
							$payment_void_total = 0;
						}
						
						$payment_link = sprintf( '<a class="payments_link" title="%s" href="%s&s=%s">#%s</a>', __( 'Payment', 'sprout-invoices' ), get_admin_url( '','/edit.php?post_type=sa_invoice&page=sprout-apps/invoice_payments' ), get_the_ID(), get_the_ID() );
						$payments_link = sprintf( '<a class="payments_link" title="%s" href="%s&s=%s">%s</a>', __( 'Invoice Payments', 'sprout-invoices' ), get_admin_url( '','/edit.php?post_type=sa_invoice&page=sprout-apps/invoice_payments' ), $invoice_id, sa_get_formatted_money( si_get_invoice_payments_total( $invoice_id ) ) );
						$invoice_name = ( $invoice_id ) ? sprintf( '<a href="%s">%s</a>', get_edit_post_link( $invoice_id ), get_the_title( $invoice_id ) ) : __( 'N/A', 'sprout-invoices' ) ;
						$client_name = ( si_get_invoice_client_id( $invoice_id ) ) ? sprintf( '<a href="%s">%s</a>', get_edit_post_link( si_get_invoice_client_id( $invoice_id ) ), get_the_title( si_get_invoice_client_id( $invoice_id ) ) ) : __( 'N/A', 'sprout-invoices' ) ; ?>
						<tr> 
							<td><?php echo $payment_link; ?></td>
							<td><span class="si_status payment_status <?php echo esc_attr( $payment->get_status() ); ?>"><?php echo str_replace( 'Publish', 'Complete', ucfirst( $payment->get_status() ) ) ?></span></td>
							<td><?php echo date( get_option('date_format'), strtotime( $payment->get_post_date() ) ) ?></td>
							<td><?php echo $payment->get_payment_method(); ?></td>
							<td><?php echo $invoice_name; ?></td>
							<td><?php echo $client_name; ?></td>
							<td><?php si_invoice_calculated_total( $invoice_id ) ?></td>
							<td><?php echo $payments_link; ?></td>
							<td><?php si_invoice_balance( $invoice_id ) ?></td>
							<td><?php sa_formatted_money( $payment_total ) ?></td>
							<td><?php sa_formatted_money( $payment_void_total ) ?></td>
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
					<th colspan="9"><?php _e( 'Totals', 'sprout-invoices' ) ?></th>
					<th><?php sa_formatted_money( $table_payment_total ) ?></th>
					<th><?php sa_formatted_money( $table_voided_payment_total ) ?></th>
				</tr>
			</tfoot>
		</table>
	</div>
</div>