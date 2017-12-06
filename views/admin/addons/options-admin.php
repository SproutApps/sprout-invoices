<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php
	$page = ( ! isset( $_GET['tab'] ) ) ? $page : self::APP_DOMAIN.'/'.$_GET['tab'] ; ?>
<div id="<?php echo esc_attr( $page ); ?>" class="wrap">
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<ul class="subsubsub">
			<li class="manage"><a href="<?php echo esc_url( remove_query_arg( 'marketplace' ) ) ?>" <?php if ( ! isset( $_GET['marketplace'] ) ) { echo 'class="current"'; } ?>><?php _e( 'Manage Bundled Addons', 'sprout-invoices' ) ?></a> |</li>
			<li class="marketplace"><a href="<?php echo esc_url( add_query_arg( 'marketplace', 'view' ) ) ?>" <?php if ( isset( $_GET['marketplace'] ) ) { echo 'class="current"'; } ?>><?php _e( 'Other Add-ons', 'sprout-invoices' ) ?></a></li>
		</ul>
		<?php do_action( 'si_settings_page_sub_heading_'.$page ); ?>
	</div>

	<span id="ajax_saving" style="display:none" data-message="<?php _e( 'Saving...', 'sprout-invoices' ) ?>"></span>
	<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'options.php' ); ?>" class="si_settings_form ajax_save">
		<?php settings_fields( $page ); ?>
		<table class="form-table">
			<?php do_settings_fields( $page, 'default' ); ?>
		</table>
		<?php do_settings_sections( $page ); ?>
		<?php submit_button(); ?>
	</form>

	<?php do_action( 'si_settings_page', $page ) ?>
	<?php do_action( 'si_settings_page_'.$page, $page ) ?>
</div>
