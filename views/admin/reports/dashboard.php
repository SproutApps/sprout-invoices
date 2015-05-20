<div id="reports_dashboard" class="wrap">
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php 
			$page = ( isset( $_GET['page'] ) ) ? $_GET['page'] : '' ;
			do_action( 'si_settings_page_sub_heading_'.$page ); ?>
	</div>
	
	<?php if ( apply_filters( 'show_upgrade_messaging', true ) ): ?>
		<?php printf( '<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span><strong>Upgrade Available:</strong> Add awesome reporting and support the future of Sprout Invoices by <a href="%s">upgrading</a>.</p></div>', si_get_purchase_link() ); ?>
	<?php endif ?>

	<div class="wrap">
		<div id="dashboard-widgets-wrap">
		<?php wp_dashboard(); ?>
		</div><!-- dashboard-widgets-wrap -->
	</div><!-- wrap -->

</div>