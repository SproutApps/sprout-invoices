<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php
	$page = ( ! isset( $_GET['tab'] ) ) ? $page : self::APP_DOMAIN.'/'.$_GET['tab'] ; ?>
<div id="<?php echo esc_attr( $page ) ?>" class="wrap">
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php do_action( 'si_settings_page_sub_heading_'.$page ); ?>
	</div>

	<span id="ajax_saving" style="display:none" data-message="<?php _e( 'Saving...', 'sprout-invoices' ) ?>"></span>
	
	<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'options.php' ); ?>" class="si_settings_form <?php echo esc_attr( $page ); if ( $ajax ) { echo ' ajax_save'; } if ( $ajax_full_page ) { echo ' full_page_ajax'; }  ?>">
		<?php settings_fields( $page ); ?>
		<table class="form-table">
			<?php do_settings_fields( $page, 'default' ); ?>
		</table>
		<?php do_settings_sections( $page ); ?>
		<?php submit_button(); ?>
		<?php if ( $reset ) : ?>
			<?php submit_button( __( 'Reset Defaults', 'sprout-invoices' ), 'secondary', $page.'-reset', false ); ?>
		<?php endif ?>
	</form>

	<?php do_action( 'si_settings_page', $page ) ?>
	<?php do_action( 'si_settings_page_'.$page, $page ) ?>
</div>
