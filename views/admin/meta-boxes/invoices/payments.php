<?php do_action( 'si_payments_meta_box_pre' ) ?>
<div id="admin_payments_options_wrap" class="admin_fields clearfix">
	<?php sa_admin_fields( $fields ); ?>
	<div id="admin_payments_option" class="form-group">
		<span class="label_wrap">&nbsp;</span>
		<div class="input_wrap">
			<a href="javascript:void(0)" class="button" id="add_admin_payments"><?php _e( 'Quick Add', 'sprout-invoices' ) ?></a> <span class="helptip" title="<?php _e( 'Add the payment now or save/update the invoice above', 'sprout-invoices' ) ?>"></span>
		</div>
	</div>
</div>
<?php do_action( 'si_payments_meta_box' ) ?>
