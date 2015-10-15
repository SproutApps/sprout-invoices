<div class="dashboard_widget inside">

	<div id="req_est_totals" class="chart_filter">
		<span class="spinner si_inline_spinner"></span>
		<input type="text" name="req_est_totals_chart_segment_span" value="6" id="req_est_totals_chart_segment_span" class="small-input"/>
		<select id="req_est_totals_chart_segment_select" name="req_est_totals_chart_segment" class="chart_segment_select">
			<option value="weeks"><?php _e( 'Weeks', 'sprout-invoices' ) ?></option>
			<option value="months"><?php _e( 'Months', 'sprout-invoices' ) ?></option>
		</select>
		<button id="req_est_totals_chart_filter" class="button" disabled="disabled"><?php _e( 'Show', 'sprout-invoices' ) ?></button>
	</div>

	<div class="main">
		<canvas id="req_estimates_chart" min-height="300" max-height="500"></canvas>
		<script type="text/javascript" charset="utf-8">
			var req_est_totals_data = {};
			var req_estimate_chart = null;
			var req_est_totals_button = jQuery('#req_est_totals_chart_filter');

			function req_estimates_chart() {
				var can = jQuery('#req_estimates_chart');
				var ctx = can.get(0).getContext("2d");
				// destroy current chart
				if ( req_estimate_chart !== null ) {
					req_estimate_chart.destroy();
				};
				req_estimate_chart = new Chart(ctx).Bar( req_est_totals_data );
			}

			var req_est_chart_data = function () {
				var segment = jQuery('#req_est_totals_chart_segment_select').val(),
					span = jQuery('#req_est_totals_chart_segment_span').val();

				req_est_totals_button.prop('disabled', 'disabled');
				jQuery('#req_est_totals .spinner').css('visibility','visible');

				jQuery.post( ajaxurl, { 
					action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
					data: 'req_to_inv_totals',
					segment: segment, 
					span: span,
					refresh_cache: si_js_object.reports_refresh_cache,
					security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
					},
					function( response ) {
						if ( response.error ) {
							req_est_totals_button.after('<span class="inline_error_message">' + response.response + '</span>');	
							return;
						};
						req_est_totals_data = {
							labels: response.data.labels,
							datasets: [
								{
									label: "<?php _e( 'Requests', 'sprout-invoices' ) ?>",
									fillColor: "rgba(85,181,232,0.2)",
									strokeColor: "rgba(85,181,232,1)",
									pointColor: "rgba(85,181,232,1)",
									pointStrokeColor: "#fff",
									pointHighlightFill: "#fff",
									pointHighlightStroke: "rgba(85,181,232,1)",
									data: response.data.requests
								},
								{
									label: "<?php _e( 'Invoices Generated', 'sprout-invoices' ) ?>",
									fillColor: "rgba(134,189,72,0.2)",
									strokeColor: "rgba(134,189,72,1)",
									pointColor: "rgba(134,189,72,1)",
									pointStrokeColor: "#fff",
									pointHighlightFill: "#fff",
									pointHighlightStroke: "rgba(134,189,72)",
									data: response.data.invoices_generated
								}
							]
						}
						req_estimates_chart();
						// enable select
						req_est_totals_button.prop('disabled', false);
						jQuery('#req_est_totals .spinner').css('visibility','hidden');
					}
				);
			};

			jQuery(document).ready(function($) {
				// load chart from the start
				req_est_chart_data();
				// change data if select changes
				req_est_totals_button.live( 'click', function( e ) {
					// load chart
					req_est_chart_data();
				} );
			});
		</script>
		<p class="description"><?php _e( 'Shows total estimate requests and the total converted into invoices.', 'sprout-invoices' ) ?></p>
	</div>
</div>