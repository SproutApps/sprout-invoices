<h3 class="dashboard_widget_title">
	<span><?php _e( 'Estimates &amp; Invoices', 'sprout-invoices' ) ?></span>
</h3>
<div class="dashboard_widget inside">
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
					refresh_cache: si_js_object.reports_refresh_cache,
					span: 6, 
					security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
					},
					function( data ) {
						est_inv_totals_data = {
							labels: data.labels,
							datasets: [
								{
									label: "<?php _e( 'Estimates', 'sprout-invoices' ) ?>",
									fillColor: "rgba(255,165,0,0.2)",
									strokeColor: "rgba(255,165,0,1)",
									pointColor: "rgba(255,165,0,1)",
									pointStrokeColor: "#fff",
									pointHighlightFill: "#fff",
									pointHighlightStroke: "rgba(255,165,0,1)",
									data: data.estimates
								},
								{
									label: "<?php _e( 'Invoices', 'sprout-invoices' ) ?>",
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
		<p class="description"><?php _e( 'Shows total estimates and invoices by week.', 'sprout-invoices' ) ?></p>
	</div>
</div>