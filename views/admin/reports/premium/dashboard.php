<div id="reports_dashboard" class="wrap">

	<?php screen_icon(); ?>
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<?php echo $page ?>
	<div class="clearfix">
		<?php do_action( 'si_settings_page_sub_heading_'.$_GET['page'] ); ?>
	</div>
	
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
									$invoice_data = SI_Reporting::total_invoice_data();
									$week_invoice_data = SI_Reporting::total_invoice_data('week');
									$last_week_invoice_data = SI_Reporting::total_invoice_data('lastweek');
									$month_invoice_data = SI_Reporting::total_invoice_data('month');
									$last_month_invoice_data = SI_Reporting::total_invoice_data('lastmonth');
									$year_invoice_data = SI_Reporting::total_invoice_data('year');
									// $last_year_invoice_data = SI_Reporting::total_invoice_data('lastyear'); ?>

								<dl>
									<dt><?php self::_e('Outstanding') ?></dt>
									<dd><?php sa_formatted_money( $invoice_data['balance'] )  ?></dd>

									<dt><?php self::_e('Paid (this week)') ?></dt>
									<dd><?php sa_formatted_money( $week_invoice_data['paid'] )  ?></dd>

									<dt><?php self::_e('Paid (last week)') ?></dt>
									<dd><?php sa_formatted_money( $last_week_invoice_data['paid'] )  ?></dd>

									<dt><?php self::_e('Paid (month to date)') ?></dt>
									<dd><?php sa_formatted_money( $month_invoice_data['paid'] )  ?></dd>

									<dt><?php self::_e('Paid (last month)') ?></dt>
									<dd><?php sa_formatted_money( $last_month_invoice_data['paid'] )  ?></dd>

									<dt><?php self::_e('Paid (year to date)') ?></dt>
									<dd><?php sa_formatted_money( $year_invoice_data['paid'] )  ?></dd>
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
											<li><a href="<?php echo get_edit_post_link( $invoice_id ) ?>"><?php echo get_the_title( $invoice_id ) ?></a> &mdash; <?php echo date( get_option( 'date_format' ), get_post_modified_time( 'U', false, $invoice_id ) ) ?></li>
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
											<li><a href="<?php echo get_edit_post_link( $invoice_id ) ?>"><?php echo get_the_title( $invoice_id ) ?></a> &mdash; <?php printf( self::__('Due: %s'), date( get_option('date_format'), si_get_invoice_due_date( $invoice_id ) ) ) ?></li>
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

										var chart = new Chart(ctx).Line(inv_data);
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
												console.log(data);
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
										jQuery(window).resize(invoice_payments_chart);
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
								<canvas id="balance_totals_chart" min-height="300" max-height="500"></canvas>
								<script type="text/javascript" charset="utf-8">
									var balance_data = {};

									function balance_totals_chart() {
										var can = jQuery('#balance_totals_chart');
										var ctx = can.get(0).getContext("2d");
										var container = can.parent().parent();

										var $container = jQuery(container);

										can.attr('width', $container.width()); //max width
										can.attr('height', $container.height()); //max height                   

										var chart = new Chart(ctx).Line(balance_data);
									}

									var balance_chart_data = function () {
										jQuery.post( ajaxurl, { 
											action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
											data: 'balance_invoiced', 
											segment: 'weeks', 
											span: 6, 
											security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
											},
											function( data ) {
												console.log(data);
												balance_data = {
													labels: data.labels,
													datasets: [
														{
															label: "<?php self::_e('Invoice Balances') ?>",
															fillColor: "rgba(255,90,94,0.2)",
															strokeColor: "rgba(255,90,94,1)",
															pointColor: "rgba(255,90,94,1)",
															pointStrokeColor: "#fff",
															pointHighlightFill: "#fff",
															pointHighlightStroke: "rgba(255,90,94,1)",
															data: data.balances
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
												balance_totals_chart();
											}
										);
									};

									jQuery(document).ready(function($) {
										jQuery(window).resize(balance_totals_chart);
										balance_chart_data();
									});
								</script>
								<p class="description"><?php self::_e('Shows total outstanding balance and payments by week') ?></p>
							</div>
						</div>
					</div>

					<div class="postbox">
						<h3>
							<span><?php self::_e('Payment Status') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<canvas id="payments_status_chart" min-height="300" max-height="500"></canvas>
								<script type="text/javascript" charset="utf-8">

									var payment_status_data = {};

									function payments_status_chart() {
										var can = jQuery('#payments_status_chart');
										var ctx = can.get(0).getContext("2d");
										var container = can.parent().parent();

										var $container = jQuery(container);

										can.attr('width', $container.width()); //max width
										can.attr('height', $container.height()); //max height                   

										var chart = new Chart(ctx).Doughnut(payment_status_data);
									}

									var payments_status_chart_data = function () {
										jQuery.post( ajaxurl, { 
											action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
											data: 'payment_statuses', 
											segment: 'weeks', 
											span: 6, 
											security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
											},
											function( data ) {
												payment_status_data = [
													{
														value: data.status_pending,
														color:"rgba(255,165,0,1)",
														highlight: "rgba(255,165,0,.8)",
														label: "Pending"
													},
													{
														value: data.status_complete,
														color: "rgba(134,189,72,1)",
														highlight: "rgba(134,189,72,.8)",
														label: "Paid"
													},
													{
														value: data.status_void,
														color:"rgba(38,41,44,1)",
														highlight: "rgba(38,41,44,.8)",
														label: "Void"
													}
												];
												payments_status_chart();
											}
										);
									};

									jQuery(document).ready(function($) {
										jQuery(window).resize(payments_status_chart);
										payments_status_chart_data();
									});
								</script>
								<p class="description"><?php self::_e('Statuses from payments from the last 3 weeks') ?></p>
							</div>
						</div>
					</div>

					<div class="postbox">
						<h3>
							<span><?php self::_e('Invoice Status') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<canvas id="invoice_status_chart" min-height="300" max-height="500"></canvas>
								<script type="text/javascript" charset="utf-8">

									var invoice_status_data = {};

									function invoice_status_chart() {
										var can = jQuery('#invoice_status_chart');
										var ctx = can.get(0).getContext("2d");
										var container = can.parent().parent();

										var $container = jQuery(container);

										can.attr('width', $container.width()); //max width
										can.attr('height', $container.height()); //max height                   

										var chart = new Chart(ctx).Doughnut(invoice_status_data);
									}

									var invoice_status_data = function () {
										jQuery.post( ajaxurl, { 
											action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
											data: 'invoice_statuses', 
											segment: 'weeks', 
											span: 6, 
											security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
											},
											function( data ) {
												console.log('payment_statuses: ');
												console.log(data);
												invoice_status_data = [
													{
														value: data.status_temp,
														color:"rgba(85,181,232,1)",
														highlight: "rgba(85,181,232,.8)",
														label: "<?php self::_e('Temp') ?>"
													},
													{
														value: data.status_pending,
														color:"rgba(255,165,0,1)",
														highlight: "rgba(255,165,0,.8)",
														label: "<?php self::_e('Pending') ?>"
													},
													{
														value: data.status_partial,
														color:"rgba(38,41,44,1)",
														highlight: "rgba(38,41,44,.8)",
														label: "<?php self::_e('Partial') ?>"
													},
													{
														value: data.status_complete,
														color: "rgba(134,189,72,1)",
														highlight: "rgba(134,189,72,.8)",
														label: "<?php self::_e('Complete') ?>"
													},
													{
														value: data.status_writeoff,
														color:"rgba(38,41,44,1)",
														highlight: "rgba(38,41,44,.8)",
														label: "<?php self::_e('Written Off') ?>"
													}
												];
												invoice_status_chart();
											}
										);
									};

									jQuery(document).ready(function($) {
										jQuery(window).resize(invoice_status_chart);
										invoice_status_data();
									});
								</script>
								<p class="description"><?php self::_e('Statuses from invoices from the last 3 weeks') ?></p>
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
											<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php echo date( get_option( 'date_format' ), get_post_modified_time( 'U', false, $estimate_id ) ) ?></li>
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
											<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php echo date( get_option( 'date_format' ), get_post_time( 'U', false, $estimate_id ) ) ?></li>
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
											<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php echo date( get_option( 'date_format' ), get_post_time( 'U', false, $estimate_id ) ) ?></li>
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
											<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php printf( self::__('Expired: %s'), date( get_option('date_format'), si_get_estimate_expiration_date( $estimate_id ) ) ) ?></li>
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
								<canvas id="req_estimates_chart" min-height="300" max-height="500"></canvas>
								<script type="text/javascript" charset="utf-8">
									var req_est_totals_data = {};

									function req_estimates_chart() {
										var can = jQuery('#req_estimates_chart');
										var ctx = can.get(0).getContext("2d");
										var container = can.parent().parent();

										var $container = jQuery(container);

										can.attr('width', $container.width()); //max width
										can.attr('height', $container.height()); //max height                   

										var chart = new Chart(ctx).Bar(req_est_totals_data);
									}

									var req_est_totals_data = function () {
										jQuery.post( ajaxurl, { 
											action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
											data: 'req_to_inv_totals', 
											segment: 'weeks', 
											span: 6, 
											security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
											},
											function( data ) {
												console.log(data);
												req_est_totals_data = {
													labels: data.labels,
													datasets: [
														{
															label: "<?php self::_e('Requests') ?>",
															fillColor: "rgba(85,181,232,0.2)",
															strokeColor: "rgba(85,181,232,1)",
															pointColor: "rgba(85,181,232,1)",
															pointStrokeColor: "#fff",
															pointHighlightFill: "#fff",
															pointHighlightStroke: "rgba(85,181,232,1)",
															data: data.requests
														},
														{
															label: "<?php self::_e('Invoices Generated') ?>",
															fillColor: "rgba(134,189,72,0.2)",
															strokeColor: "rgba(134,189,72,1)",
															pointColor: "rgba(134,189,72,1)",
															pointStrokeColor: "#fff",
															pointHighlightFill: "#fff",
															pointHighlightStroke: "rgba(134,189,72)",
															data: data.invoices_generated
														}
													]
												}
												req_estimates_chart();
											}
										);
									};

									jQuery(document).ready(function($) {
										jQuery(window).resize(req_estimates_chart);
										req_est_totals_data();
									});
								</script>
								<p class="description"><?php self::_e('Shows total estimate requests and the total converted into invoices.') ?></p>
							</div>
						</div>
					</div>
					
					<div class="postbox">
						<h3>
							<span><?php self::_e('Estimate Status') ?></span>
						</h3>
						<div class="inside">
							<div class="main">
								<canvas id="estimate_status_chart" min-height="300" max-height="500"></canvas>
								<script type="text/javascript" charset="utf-8">

									var estimate_status_data = {};

									function estimate_status_chart() {
										var can = jQuery('#estimate_status_chart');
										var ctx = can.get(0).getContext("2d");
										var container = can.parent().parent();

										var $container = jQuery(container);

										can.attr('width', $container.width()); //max width
										can.attr('height', $container.height()); //max height                   

										var chart = new Chart(ctx).Doughnut(estimate_status_data);
									}

									var estimate_status_data = function () {
										jQuery.post( ajaxurl, { 
											action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
											data: 'estimates_statuses', 
											segment: 'weeks', 
											span: 6, 
											security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
											},
											function( data ) {
												console.log('estimates_statuses: ');
												console.log(data);
												estimate_status_data = [
													{
														value: data.status_request,
														color:"rgba(85,181,232,1)",
														highlight: "rgba(85,181,232,.8)",
														label: "<?php self::_e('Request') ?>"
													},
													{
														value: data.status_pending,
														color:"rgba(255,165,0,1)",
														highlight: "rgba(255,165,0,.8)",
														label: "<?php self::_e('Pending') ?>"
													},
													{
														value: data.status_approved,
														color: "rgba(134,189,72,1)",
														highlight: "rgba(134,189,72,.8)",
														label: "<?php self::_e('Approved') ?>"
													},
													{
														value: data.status_declined,
														color:"rgba(38,41,44,1)",
														highlight: "rgba(38,41,44,.8)",
														label: "<?php self::_e('Declined') ?>"
													}
												];
												estimate_status_chart();
											}
										);
									};

									jQuery(document).ready(function($) {
										jQuery(window).resize(estimate_status_chart);
										estimate_status_data();
									});
								</script>
								<p class="description"><?php self::_e('Statuses from estimates from the last 3 weeks') ?></p>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
</div>