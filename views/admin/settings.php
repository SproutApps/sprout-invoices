<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php 
	$page = ( !isset( $_GET['tab'] ) ) ? $page : self::TEXT_DOMAIN.'/'.$_GET['tab'] ; ?>
<div id="<?php echo $page ?>" class="wrap">
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php 
			$p = ( isset( $_GET['page'] ) ) ? $_GET['page'] : '' ;
			do_action( 'si_settings_page_sub_heading_'.$p ); ?>
	</div>

	<span id="ajax_saving" style="display:none" data-message="<?php self::_e('Saving...') ?>"></span>
	<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'options.php' ); ?>" class="si_settings_form <?php echo $page; if ( $ajax ) echo ' ajax_save'; if ( $ajax_full_page ) echo ' full_page_ajax';  ?>">
		<?php settings_fields( $page ); ?>
		<table class="form-table">
			<?php do_settings_fields( $page, 'default' ); ?>
		</table>
		<?php do_settings_sections( $page ); ?>
		<?php submit_button(); ?>
		<?php if ( $reset ): ?>
			<?php submit_button( si__( 'Reset Defaults' ), 'secondary', $page.'-reset', false ); ?>
		<?php endif ?>
	</form>

	<?php do_action( 'si_settings_page', $page ) ?>
	<?php do_action( 'si_settings_page_'.$page, $page ) ?>
</div>
