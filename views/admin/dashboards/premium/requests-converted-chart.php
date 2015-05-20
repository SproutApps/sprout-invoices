<h3 class="dashboard_widget_title">
	<span><?php self::_e('Requests &amp; Converted Requests') ?></span>
</h3>
<div class="dashboard_widget inside">
	<div class="main">
		<canvas id="req_estimates_chart" min-height="300" max-height="500"></canvas>
		<script type="text/javascript" charset="utf-8">
			var req_est_totals_data = {};

			function req_estimates_chart() {
				var can = jQuery('#req_estimates_chart');
				var ctx = can.get(0).getContext("2d");
				var chart = new Chart(ctx).Bar( req_est_totals_data );
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
				req_est_totals_data();
			});
		</script>
		<p class="description"><?php self::_e('Shows total estimate requests and the total converted into invoices.') ?></p>
	</div>
</div>