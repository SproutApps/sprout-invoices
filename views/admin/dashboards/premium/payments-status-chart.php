<div class="dashboard_widget inside">
	<div class="main">
		<canvas id="payments_status_chart" min-height="300" max-height="500"></canvas>
		<script type="text/javascript" charset="utf-8">

			var payment_status_data = {};

			function payments_status_chart() {
				var can = jQuery('#payments_status_chart');
				var ctx = can.get(0).getContext("2d");
				var chart = new Chart(ctx).Doughnut(payment_status_data );
			}

			var payments_status_chart_data = function () {
				jQuery.post( ajaxurl, { 
					action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
					data: 'payment_statuses', 
					segment: 'weeks', 
					refresh_cache: si_js_object.reports_refresh_cache,
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
				payments_status_chart_data();
			});
		</script>
		<p class="description"><?php _e( 'Statuses from payments from the last 3 weeks', 'sprout-invoices' ) ?></p>
	</div>
</div>