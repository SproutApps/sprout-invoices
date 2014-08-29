<div class="wrap">

	<?php screen_icon(); ?>
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php do_action( 'si_settings_page_sub_heading_'.$_GET['page'] ); ?>
	</div>
	<div id="si_report" class="clearfix">
		<a href="<?php si_purchase_link() ?>" target="_blank"><img src="<?php echo SI_RESOURCES . 'admin/img/upgrade/invoices-report-upgrade.png' ?>" alt="Upgrade" width="100%" height="auto" style="margin-top: 20px;"></a>
	</div>
</div>