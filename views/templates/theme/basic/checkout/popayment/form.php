<div id="popayment_info_checkout_wrap" class="paytype inactive">
	<form action="<?php echo si_get_payment_link( get_the_ID(), $type ) ?>" method="post" accept-charset="utf-8" class="sa-form sa-form-aligned" enctype="multipart/form-data">
		<div class="row">
			<?php sa_form_fields( $popayment_fields, 'popayments' ); ?>
		</div>
		<?php do_action( 'si_popayment_form_controls', $checkout ) ?>
		<?php do_action( 'si_popayment_payment_controls', $checkout ) ?>
		<input type="hidden" name="<?php echo SI_Checkouts::CHECKOUT_ACTION ?>" value="<?php echo SI_Checkouts::PAYMENT_PAGE ?>" />
		<button type="submit" class="button"><?php _e( 'Submit PO Info', 'sprout-invoices' ) ?></button>
	</form>
</div><!-- #popayment_info_checkout_wrap -->
