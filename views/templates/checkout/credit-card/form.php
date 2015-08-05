<div id="credit_card_checkout_wrap">
	<?php do_action( 'si_credit_card_checkout_wrap' ) ?>
	<form action="<?php echo si_get_credit_card_checkout_form_action() ?>" method="post" accept-charset="utf-8" class="sa-form sa-form-aligned" id="si_credit_card_form">
		<?php do_action( 'si_billing_credit_card_form', $checkout ) ?>
		<div id="billing_cc_fields" class="clearfix">
			<fieldset id="billing_fields" class="sa-fieldset">
				<legend><?php si_e( 'Billing' ) ?></legend>
				<?php sa_form_fields( $billing_fields, 'billing' ); ?>
				<?php do_action( 'si_billing_payment_fields', $checkout ) ?>
			</fieldset>
			<fieldset id="credit_card_fields" class="sa-fieldset">
				<legend><?php si_e( 'Credit Card' ) ?></legend>
				<?php sa_form_fields( $cc_fields, 'credit' ); ?>
				<?php do_action( 'si_credit_card_payment_fields', $checkout ) ?>
			</fieldset>
			<?php do_action( 'si_credit_card_form_controls', $checkout ) ?>
			<?php do_action( 'si_credit_card_payment_controls', $checkout ) ?>
			<input type="hidden" name="<?php echo SI_Checkouts::CHECKOUT_ACTION ?>" value="<?php echo SI_Checkouts::PAYMENT_PAGE ?>" />
			<button type="submit" class="button button-primary" id="credit_card_submit"><?php si_e( 'Submit' ) ?></button>
		</div><!-- #billing_cc_fields -->
		<?php do_action( 'si_billing_credit_card_form_bottom', $checkout ) ?>
	</form>
</div><!-- #credit_card_checkout_wrap -->
<?php do_action( 'si_credit_card_checkout_post_wrap' ) ?>