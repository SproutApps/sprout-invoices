<div id="reports_dashboard" class="wrap">

	<?php screen_icon(); ?>
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php do_action( 'si_settings_page_sub_heading_'.$_GET['page'] ); ?>
	</div>
	
	<?php printf( '<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span><strong>Upgrade Available:</strong> Add awesome reporting and support the future of Sprout Invoices by <a href="%s">upgrading</a>.</p></div>', si_get_purchase_link() ); ?>

	<div id="dashboard-widgets-wrap">
		<div id="dashboard-widgets" class="metabox-holder">
			<div id="postbox-container-1" class="postbox-container">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h3>
							<span><?php self::_e('Invoice Dashboard') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<?php 
									$invoice_data = SI_Reporting::total_invoice_data(); ?>
								<dl>
									<dt><?php self::_e('Outstanding') ?></dt>
									<dd><?php sa_formatted_money( $invoice_data['balance'] )  ?></dd>

									<dt><?php self::_e('Paid (this week)') ?></dt>
									<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>

									<dt><?php self::_e('Paid (last week)') ?></dt>
									<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>

									<dt><?php self::_e('Paid (month to date)') ?></dt>
									<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>

									<dt><?php self::_e('Paid (last month)') ?></dt>
									<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>

									<dt><?php self::_e('Paid (year to date)') ?></dt>
									<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>
								</dl>

								

								<?php 
									$args = array(
										'orderby' => 'modified',
										'post_type' => SI_Invoice::POST_TYPE,
										'post_status' => 'any', // Not Written-off?
										'posts_per_page' => 3,
										'fields' => 'ids',
										);
									$invoices = new WP_Query( $args ); ?>

								<?php if ( !empty( $invoices->posts ) ): ?>
									<b><?php self::_e('Latest Updates') ?></b> 
									<ul>
										<?php foreach ( $invoices->posts as $invoice_id ): ?>
											<li><a href="<?php echo get_edit_post_link( $invoice_id ) ?>"><?php echo get_the_title( $invoice_id ) ?></a> &mdash; <?php echo date_i18n( get_option( 'date_format' ), get_post_modified_time( 'U', false, $invoice_id ) ) ?></li>
										<?php endforeach ?>
									</ul>
								<?php else: ?>
									<p>
										<b><?php self::_e('Latest Updates') ?></b><br/>
										<?php self::_e('No invoices found.') ?>
									</p>
								<?php endif ?>

								<?php 
									$args = array(
										'post_type' => SI_Invoice::POST_TYPE,
										'post_status' => array( SI_Invoice::STATUS_PARTIAL, SI_Invoice::STATUS_PENDING ),
										'posts_per_page' => 3,
										'fields' => 'ids',
										'meta_query' => array(
												array(
													'meta_key' => '_due_date',
													'value' => array( 0, current_time( 'timestamp' ) ),
													'compare' => 'BETWEEN'
													)
											)
										);
									$invoices = new WP_Query( $args ); ?>

								<?php if ( !empty( $invoices->posts ) ): ?>
									<b><?php self::_e('Overdue &amp; Unpaid') ?></b> 
									<ul>
										<?php foreach ( $invoices->posts as $invoice_id ): ?>
											<li><a href="<?php echo get_edit_post_link( $invoice_id ) ?>"><?php echo get_the_title( $invoice_id ) ?></a> &mdash; <?php printf( self::__('Due: %s'), date_i18n( get_option('date_format'), si_get_invoice_due_date( $invoice_id ) ) ) ?></li>
										<?php endforeach ?>
									</ul>
								<?php else: ?>
									<p>
										<b><?php self::_e('Overdue &amp; Unpaid') ?></b><br/>
										<?php self::_e('No overdue or unpaid invoices.') ?>
									</p>
								<?php endif ?>
							</div>
						</div>
					</div>
					<div class="postbox">
						<h3>
							<span><?php self::_e('Invoiced &amp; Payments') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<canvas id="invoice_payments_chart" min-height="300" max-height="500"></canvas>
								<script type="text/javascript" charset="utf-8">
									var inv_data = {};

									function invoice_payments_chart() {
										var can = jQuery('#invoice_payments_chart');
										var ctx = can.get(0).getContext("2d");
										var container = can.parent().parent();

										var $container = jQuery(container);

										can.attr('width', $container.width()); //max width
										can.attr('height', $container.height()); //max height                   

										console.log(inv_data);

										var chart = new Chart(ctx).Line(inv_data, {
												responsive: true,
												maintainAspectRatio: true
											});
									}

									var inv_chart_data = function () {
										jQuery.post( ajaxurl, { 
											action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
											data: 'invoice_payments', 
											segment: 'weeks', 
											span: 6, 
											security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
											},
											function( data ) {
												inv_data = {
													labels: data.labels,
													datasets: [
														{
															label: "<?php self::_e('Invoiced') ?>",
															fillColor: "rgba(134,189,72,0.2)",
															strokeColor: "rgba(134,189,72,1)",
															pointColor: "rgba(134,189,72,1)",
															pointStrokeColor: "#fff",
															pointHighlightFill: "#fff",
															pointHighlightStroke: "rgba(134,189,72)",
															data: data.invoices
														},
														{
															label: "<?php self::_e('Payments') ?>",
															fillColor: "rgba(38,41,44,0.2)",
															strokeColor: "rgba(38,41,44,1)",
															pointColor: "rgba(38,41,44,1)",
															pointStrokeColor: "#fff",
															pointHighlightFill: "#fff",
															pointHighlightStroke: "rgba(38,41,44,1)",
															data: data.payments
														}
													]
												}
												invoice_payments_chart();
											}
										);
									};

									jQuery(document).ready(function($) {
										inv_chart_data();
									});
								</script>
								<p class="description"><?php self::_e('Compares total invoiced and the total payments.') ?></p>
							</div>
						</div>
					</div>
					
					<div class="postbox">
						<h3>
							<span><?php self::_e('Outstanding Balances &amp; Payments') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<a href="<?php si_purchase_link() ?>" target="_blank">Upgrade</a> for this chart and more.
							</div>
						</div>
					</div>

					<div class="postbox">
						<h3>
							<span><?php self::_e('Payment Status') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<a href="<?php si_purchase_link() ?>" target="_blank">Upgrade</a> for this chart and more.
							</div>
						</div>
					</div>

					<div class="postbox">
						<h3>
							<span><?php self::_e('Invoice Status') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<a href="<?php si_purchase_link() ?>" target="_blank">Upgrade</a> for this chart and more.
							</div>
						</div>
					</div>

				</div>
			</div>
			<div id="postbox-container-2" class="postbox-container">
				<div id="side-sortables" class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h3>
							<span><?php self::_e('Estimate Dashboard') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<?php 
									$args = array(
										'orderby' => 'modified',
										'post_type' => SI_Estimate::POST_TYPE,
										'post_status' => 'any', // Not Written-off?
										'posts_per_page' => 3,
										'fields' => 'ids',
										);
									$estimates = new WP_Query( $args ); ?>

								<?php if ( !empty( $estimates->posts ) ): ?>
									<b><?php self::_e('Latest Updates') ?></b> 
									<ul>
										<?php foreach ( $estimates->posts as $estimate_id ): ?>
											<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php echo date_i18n( get_option( 'date_format' ), get_post_modified_time( 'U', false, $estimate_id ) ) ?></li>
										<?php endforeach ?>
									</ul>
								<?php else: ?>
									<p>
										<b><?php self::_e('Latest Updates') ?></b><br/>
										<?php self::_e('No recent estimates found.') ?>
									</p>
								<?php endif ?>

								<?php 
									$args = array(
										'post_type' => SI_Invoice::POST_TYPE,
										'post_status' => array( SI_Estimate::STATUS_REQUEST ),
										'posts_per_page' => 3,
										'fields' => 'ids'
										);
									$estimates = new WP_Query( $args ); ?>

								<?php if ( !empty( $estimates->posts ) ): ?>
									<b><?php self::_e('Recent Requests') ?></b> 
									<ul>
										<?php foreach ( $estimates->posts as $estimate_id ): ?>
											<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php echo date_i18n( get_option( 'date_format' ), get_post_time( 'U', false, $estimate_id ) ) ?></li>
										<?php endforeach ?>
									</ul>
								<?php else: ?>
									<p>
										<b><?php self::_e('Recent Requests') ?></b><br/>
										<?php self::_e('No recently requested estimates.') ?>
									</p>
								<?php endif ?>

								<?php 
									$args = array(
										'orderby' => 'modified',
										'post_type' => SI_Invoice::POST_TYPE,
										'post_status' => array( SI_Estimate::STATUS_DECLINED ),
										'posts_per_page' => 3,
										'fields' => 'ids'
										);
									$estimates = new WP_Query( $args ); ?>

								<?php if ( !empty( $estimates->posts ) ): ?>
									<b><?php self::_e('Recent Declined') ?></b> 
									<ul>
										<?php foreach ( $estimates->posts as $estimate_id ): ?>
											<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php echo date_i18n( get_option( 'date_format' ), get_post_time( 'U', false, $estimate_id ) ) ?></li>
										<?php endforeach ?>
									</ul>
								<?php else: ?>
									<p>
										<b><?php self::_e('Recent Declined') ?></b><br/>
										<?php self::_e('No recently declined estimates.') ?>
									</p>
								<?php endif ?>

								<?php 
									$args = array(
										'post_type' => SI_Estimate::POST_TYPE,
										'post_status' => array( SI_Estimate::STATUS_PENDING ),
										'posts_per_page' => 3,
										'fields' => 'ids',
										'meta_query' => array(
												array(
													'meta_key' => '_expiration_date',
													'value' => array( 0, current_time( 'timestamp' ) ),
													'compare' => 'BETWEEN'
													)
											)
										);
									$estimates = new WP_Query( $args ); ?>

								<?php if ( !empty( $estimates->posts ) ): ?>
									<b><?php self::_e('Expired &amp; Pending') ?></b> 
									<ul>
										<?php foreach ( $estimates->posts as $estimate_id ): ?>
											<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php printf( self::__('Expired: %s'), date_i18n( get_option('date_format'), si_get_estimate_expiration_date( $estimate_id ) ) ) ?></li>
										<?php endforeach ?>
									</ul>
								<?php else: ?>
									<p>
										<b><?php self::_e('Expired &amp; Pending') ?></b><br/>
										<?php self::_e('No recently expired or pending estimates.') ?>
									</p>
								<?php endif ?>
							</div>
						</div>
					</div>
					<div class="postbox">
						<h3>
							<span><?php self::_e('Estimates &amp; Invoices') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<canvas id="est_invoices_chart" min-height="300" max-height="500"></canvas>
								<script type="text/javascript" charset="utf-8">
									var est_inv_totals_data = {};

									function est_invoices_chart() {
										var can = jQuery('#est_invoices_chart');
										var ctx = can.get(0).getContext("2d");
										var container = can.parent().parent();

										var $container = jQuery(container);

										can.attr('width', $container.width()); //max width
										can.attr('height', $container.height()); //max height                   

										var chart = new Chart(ctx).Bar(est_inv_totals_data);
									}

									var est_inv_totals_data = function () {
										jQuery.post( ajaxurl, { 
											action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
											data: 'est_invoice_totals', 
											segment: 'weeks', 
											span: 6, 
											security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
											},
											function( data ) {
												console.log(data);
												est_inv_totals_data = {
													labels: data.labels,
													datasets: [
														{
															label: "<?php self::_e('Estimates') ?>",
															fillColor: "rgba(255,165,0,0.2)",
															strokeColor: "rgba(255,165,0,1)",
															pointColor: "rgba(255,165,0,1)",
															pointStrokeColor: "#fff",
															pointHighlightFill: "#fff",
															pointHighlightStroke: "rgba(255,165,0,1)",
															data: data.estimates
														},
														{
															label: "<?php self::_e('Invoices') ?>",
															fillColor: "rgba(134,189,72,0.2)",
															strokeColor: "rgba(134,189,72,1)",
															pointColor: "rgba(134,189,72,1)",
															pointStrokeColor: "#fff",
															pointHighlightFill: "#fff",
															pointHighlightStroke: "rgba(134,189,72)",
															data: data.invoices
														}
													]
												}
												est_invoices_chart();
											}
										);
									};

									jQuery(document).ready(function($) {
										jQuery(window).resize(est_invoices_chart);
										est_inv_totals_data();
									});
								</script>
								<p class="description"><?php self::_e('Shows total estimates and invoices by week.') ?></p>
							</div>
						</div>
					</div>
					<div class="postbox">
						<h3>
							<span><?php self::_e('Requests &amp; Converted Requests') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<a href="<?php si_purchase_link() ?>" target="_blank">Upgrade</a> for this chart and more.
							</div>
						</div>
					</div>
					
					<div class="postbox">
						<h3>
							<span><?php self::_e('Estimate Status') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<a href="<?php si_purchase_link() ?>" target="_blank">Upgrade</a> for this chart and more.
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
</div>