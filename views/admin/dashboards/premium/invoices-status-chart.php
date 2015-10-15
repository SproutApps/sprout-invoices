<div class="dashboard_widget inside">
	<div class="main">
		<canvas id="invoice_status_chart" min-height="300" max-height="500"></canvas>
		<script type="text/javascript" charset="utf-8">

			var invoice_status_data = {};

			function invoice_status_chart() {
				var can = jQuery('#invoice_status_chart');
				var ctx = can.get(0).getContext("2d");
				var chart = new Chart(ctx).Doughnut(invoice_status_data );
			}

			var invoice_status_data = function () {
				jQuery.post( ajaxurl, { 
					action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
					data: 'invoice_statuses', 
					segment: 'weeks', 
					refresh_cache: si_js_object.reports_refresh_cache,
					span: 6, 
					security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
					},
					function( data ) {
						invoice_status_data = [
							{
								value: data.status_temp,
								color:"rgba(85,181,232,1)",
								highlight: "rgba(85,181,232,.8)",
								label: "<?php _e( 'Temp', 'sprout-invoices' ) ?>"
							},
							{
								value: data.status_pending,
								color:"rgba(255,165,0,1)",
								highlight: "rgba(255,165,0,.8)",
								label: "<?php _e( 'Pending', 'sprout-invoices' ) ?>"
							},
							{
								value: data.status_partial,
								color:"rgba(38,41,44,1)",
								highlight: "rgba(38,41,44,.8)",
								label: "<?php _e( 'Partial', 'sprout-invoices' ) ?>"
							},
							{
								value: data.status_complete,
								color: "rgba(134,189,72,1)",
								highlight: "rgba(134,189,72,.8)",
								label: "<?php _e( 'Complete', 'sprout-invoices' ) ?>"
							},
							{
								value: data.status_writeoff,
								color:"rgba(38,41,44,1)",
								highlight: "rgba(38,41,44,.8)",
								label: "<?php _e( 'Written Off', 'sprout-invoices' ) ?>"
							}
						];
						invoice_status_chart();
					}
				);
			};

			jQuery(document).ready(function($) {
				invoice_status_data();
			});
		</script>
		<p class="description"><?php _e( 'Statuses from invoices from the last 3 weeks', 'sprout-invoices' ) ?></p>
	</div>
</div>