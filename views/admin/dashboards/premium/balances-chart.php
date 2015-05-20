<h3 class="dashboard_widget_title">
	<span><?php self::_e('Outstanding Balances &amp; Payments') ?></span>
</h3>
<div class="dashboard_widget inside">
	<div class="main">
		<canvas id="balance_totals_chart" min-height="300" max-height="500"></canvas>
		<script type="text/javascript" charset="utf-8">
			var balance_data = {};

			function balance_totals_chart() {
				var can = jQuery('#balance_totals_chart');
				var ctx = can.get(0).getContext("2d");                
				var chart = new Chart(ctx).Line( balance_data );
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
				balance_chart_data();
			});
		</script>
		<p class="description"><?php self::_e('Shows total outstanding balance and payments by week') ?></p>
	</div>
</div>