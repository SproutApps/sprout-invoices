<h3 class="dashboard_widget_title">
	<span><?php self::_e('Estimates &amp; Invoices') ?></span>
</h3>
<div class="dashboard_widget inside">
	<div class="main">
		<canvas id="est_invoices_chart" min-height="300" max-height="500"></canvas>
		<script type="text/javascript" charset="utf-8">
			var est_inv_totals_data = {};

			function est_invoices_chart() {
				var can = jQuery('#est_invoices_chart');
				var ctx = can.get(0).getContext("2d");
				var chart = new Chart(ctx).Bar( est_inv_totals_data, {
					multiTooltipTemplate: "<%= value %>",
				} );
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
				est_inv_totals_data();
			});
		</script>
		<p class="description"><?php self::_e('Shows total estimates and invoices by week.') ?></p>
	</div>
</div>