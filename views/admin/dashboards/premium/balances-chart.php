<div class="dashboard_widget inside">

	<div id="balance_totals" class="chart_filter">
		<span class="spinner si_inline_spinner"></span>
		<input type="text" name="balance_totals_chart_segment_span" value="6" id="balance_totals_chart_segment_span" class="small-input"/>
		<select id="balance_totals_chart_segment_select" name="balance_totals_chart_segment" class="chart_segment_select">
			<option value="weeks"><?php _e( 'Weeks', 'sprout-invoices' ) ?></option>
			<option value="months"><?php _e( 'Months', 'sprout-invoices' ) ?></option>
		</select>
		<button id="balance_totals_chart_filter" class="button" disabled="disabled"><?php _e( 'Show', 'sprout-invoices' ) ?></button>
	</div>

	<div class="main">
		<canvas id="balance_totals_chart" min-height="300" max-height="500"></canvas>
		<script type="text/javascript" charset="utf-8">
			var balance_data = {};
			var balance_total_chart = null;
			var balance_totals_button = jQuery('#balance_totals_chart_filter');

			function load_balance_totals_chart() {
				var can = jQuery('#balance_totals_chart');
				var ctx = can.get(0).getContext("2d");
				// destroy current chart
				if ( balance_total_chart !== null ) {
					balance_total_chart.destroy();
				};
				balance_total_chart = new Chart(ctx).Line( balance_data );
			}

			var balance_chart_data = function () {
				var segment = jQuery('#balance_totals_chart_segment_select').val(),
					span = jQuery('#balance_totals_chart_segment_span').val();

				balance_totals_button.prop('disabled', 'disabled');
				jQuery('#balance_totals .spinner').css('visibility','visible');

				jQuery.post( ajaxurl, { 
					action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
					data: 'balance_invoiced',
					segment: segment, 
					span: span,
					refresh_cache: si_js_object.reports_refresh_cache,
					security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
					},
					function( response ) {
						if ( response.error ) {
							balance_totals_button.after('<span class="inline_error_message">' + response.response + '</span>');	
							return;
						};
						balance_data = {
							labels: response.data.labels,
							datasets: [
								{
									label: "<?php _e( 'Invoice Balances', 'sprout-invoices' ) ?>",
									fillColor: "rgba(255,90,94,0.2)",
									strokeColor: "rgba(255,90,94,1)",
									pointColor: "rgba(255,90,94,1)",
									pointStrokeColor: "#fff",
									pointHighlightFill: "#fff",
									pointHighlightStroke: "rgba(255,90,94,1)",
									data: response.data.balances
								},
								{
									label: "<?php _e( 'Payments', 'sprout-invoices' ) ?>",
									fillColor: "rgba(38,41,44,0.2)",
									strokeColor: "rgba(38,41,44,1)",
									pointColor: "rgba(38,41,44,1)",
									pointStrokeColor: "#fff",
									pointHighlightFill: "#fff",
									pointHighlightStroke: "rgba(38,41,44,1)",
									data: response.data.payments
								}
							]
						}
						load_balance_totals_chart();
						// enable select
						balance_totals_button.prop('disabled', false);
						jQuery('#balance_totals .spinner').css('visibility','hidden');
					}
				);
			};

			jQuery(document).ready(function($) {
				// load chart from the start
				balance_chart_data();
				// change data if select changes
				balance_totals_button.live( 'click', function( e ) {
					// load chart
					balance_chart_data();
				} );
			});
		</script>
		<p class="description"><?php _e( 'Shows total outstanding balance and payments by week', 'sprout-invoices' ) ?></p>
	</div>
</div>
