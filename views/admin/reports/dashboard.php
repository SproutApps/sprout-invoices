<div id="dashboard-widgets-wrap" class="row">
	<?php if ( function_exists( 'wp_dashboard' ) ) :  ?>
		<?php wp_dashboard(); ?>
	<?php else : ?>
		<?php _e( 'Function wp_dashboard() not available.', 'sprout-invoices' ) ?>
	<?php endif ?>
</div>
