<h3 class="dashboard_widget_title">
	<span><?php self::_e('Invoiced &amp; Payments') ?></span>
</h3>
<div class="dashboard_widget inside">
	<div class="main">
		<canvas id="invoice_payments_chart" min-height="300" max-height="500"></canvas>
		<script type="text/javascript" charset="utf-8">
			var inv_data = {};
			
			function invoice_payments_chart() {
				var can = jQuery('#invoice_payments_chart');
				var ctx = can.get(0).getContext("2d");

				var chart = new Chart(ctx).Line( inv_data );
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