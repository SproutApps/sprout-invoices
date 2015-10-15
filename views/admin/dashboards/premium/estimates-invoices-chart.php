<div class="dashboard_widget inside">

	<div id="est_inv_totals" class="chart_filter">
		<span class="spinner si_inline_spinner"></span>
		<input type="text" name="est_inv_totals_chart_segment_span" value="6" id="est_inv_totals_chart_segment_span" class="small-input"/>
		<select id="est_inv_totals_chart_segment_select" name="est_inv_totals_chart_segment" class="chart_segment_select">
			<option value="weeks"><?php _e( 'Weeks', 'sprout-invoices' ) ?></option>
			<option value="months"><?php _e( 'Months', 'sprout-invoices' ) ?></option>
		</select>
		<button id="est_inv_totals_chart_filter" class="button" disabled="disabled"><?php _e( 'Show', 'sprout-invoices' ) ?></button>
	</div>

	<div class="main">
		<canvas id="est_invoices_chart" min-height="300" max-height="500"></canvas>
		<script type="text/javascript" charset="utf-8">
			var est_inv_totals_data = {};
			var est_invoice_chart = null;
			var est_inv_totals_button = jQuery('#est_inv_totals_chart_filter');

			function est_invoices_chart() {
				var can = jQuery('#est_invoices_chart');
				var ctx = can.get(0).getContext("2d");
				// destroy current chart
				if ( est_invoice_chart !== null ) {
					est_invoice_chart.destroy();
				};
				est_invoice_chart = new Chart(ctx).Bar( est_inv_totals_data, {
					multiTooltipTemplate: "<%= value %>",
				} );
			}

			var get_est_inv_totals_data = function () {
				var segment = jQuery('#est_inv_totals_chart_segment_select').val(),
					span = jQuery('#est_inv_totals_chart_segment_span').val();

				est_inv_totals_button.prop('disabled', 'disabled');
				jQuery('#est_inv_totals .spinner').css('visibility','visible');

				jQuery.post( ajaxurl, { 
					action: '<?php echo SI_Reporting::AJAX_ACTION ?>', 
					data: 'est_invoice_totals', 
					segment: segment, 
					span: span,
					refresh_cache: si_js_object.reports_refresh_cache,
					security: '<?php echo wp_create_nonce( SI_Reporting::AJAX_NONCE ) ?>' 
					},
					function( response ) {
						if ( response.error ) {
							est_inv_totals_button.after('<span class="inline_error_message">' + response.response + '</span>');	
							return;
						};
						est_inv_totals_data = {
							labels: response.data.labels,
							datasets: [
								{
									label: "<?php _e( 'Estimates', 'sprout-invoices' ) ?>",
									fillColor: "rgba(255,165,0,0.2)",
									strokeColor: "rgba(255,165,0,1)",
									pointColor: "rgba(255,165,0,1)",
									pointStrokeColor: "#fff",
									pointHighlightFill: "#fff",
									pointHighlightStroke: "rgba(255,165,0,1)",
									data: response.data.estimates
								},
								{
									label: "<?php _e( 'Invoices', 'sprout-invoices' ) ?>",
									fillColor: "rgba(134,189,72,0.2)",
									strokeColor: "rgba(134,189,72,1)",
									pointColor: "rgba(134,189,72,1)",
									pointStrokeColor: "#fff",
									pointHighlightFill: "#fff",
									pointHighlightStroke: "rgba(134,189,72)",
									data: response.data.invoices
								}
							]
						}
						est_invoices_chart();
						// enable select
						est_inv_totals_button.prop('disabled', false);
						jQuery('#est_inv_totals .spinner').css('visibility','hidden');
					}
				);
			};

			jQuery(document).ready(function($) {
				// load chart from the start
				get_est_inv_totals_data();
				// change data if select changes
				est_inv_totals_button.live( 'click', function( e ) {
					// load chart
					get_est_inv_totals_data();
				} );
			});
		</script>
		<p class="description"><?php _e( 'Shows total estimates and invoices by week.', 'sprout-invoices' ) ?></p>
	</div>
</div>