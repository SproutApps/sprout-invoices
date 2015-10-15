<div id="check_info_checkout_wrap">
	<form action="<?php echo si_get_payment_link( get_the_ID(), $type ) ?>" method="post" accept-charset="utf-8" class="sa-form sa-form-aligned">
		<fieldset id="checks_fields" class="sa-fieldset">
			<?php sa_form_fields( $check_fields, 'checks' ); ?>
		</fieldset>
		<?php do_action( 'si_checks_form_controls', $checkout ) ?>
		<?php do_action( 'si_checks_payment_controls', $checkout ) ?>
		<input type="hidden" name="<?php echo SI_Checkouts::CHECKOUT_ACTION ?>" value="<?php echo SI_Checkouts::PAYMENT_PAGE ?>" />
		<button type="submit" class="button button-primary"><?php _e( 'Submit', 'sprout-invoices' ) ?></button>
	</form>
</div><!-- #credit_card_checkout_wrap -->