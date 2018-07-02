<?php do_action( 'sprout_settings_header' ); ?>

<div id="si_reports_admin" class="si_settings">

	<div id="si_basic_settings"  class="si_settings_tabs non_stick_subnav">

		<div id="si_report">

			<div id="si_settings_subnav">

				<a href="<?php echo esc_url( add_query_arg( $query_var, 'dashboard' ) ) ?>" class="<?php if ( $current_report == 'dashboard' || $current_report == '' ) { echo ' active'; } ?>""><span class="dashicons dashicons-chart-pie"></span><?php _e( 'Dashboard', 'sprout-invoices' ) ?></a>
				<hr/>
				<a href="<?php echo esc_url( add_query_arg( $query_var, 'invoices' ) ) ?>" class="<?php if ( $current_report == 'invoices' ) { echo ' active'; } ?>""><span class="dashicons dashicons-analytics"></span><?php _e( 'Invoice Reports', 'sprout-invoices' ) ?></a>
				<a href="<?php echo esc_url( add_query_arg( $query_var, 'estimates' ) ) ?>" class="<?php if ( $current_report == 'estimates' ) { echo ' active'; } ?>""><span class="dashicons dashicons-analytics"></span><?php _e( 'Estimate Reports', 'sprout-invoices' ) ?></a>
				<a href="<?php echo esc_url( add_query_arg( $query_var, 'payments' ) ) ?>" class="<?php if ( $current_report == 'payments' ) { echo ' active'; } ?>""><span class="dashicons dashicons-analytics"></span><?php _e( 'Payment Reports', 'sprout-invoices' ) ?></a>
				<a href="<?php echo esc_url( add_query_arg( $query_var, 'clients' ) ) ?>" class="<?php if ( $current_report == 'clients' ) { echo ' active'; } ?>""><span class="dashicons dashicons-analytics"></span><?php _e( 'Client Reports', 'sprout-invoices' ) ?></a>

			</div>

			<div class="si_settings_tabs clearfix">
				
				<main id="main" role="main">

					<?php self::load_view( $view, array() ) ?>	

				</main>

			</div>
			
		</div>

	</div>
</div>
