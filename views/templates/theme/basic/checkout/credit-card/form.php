<div id="credit_card_checkout_wrap" class="paytype inactive">
	<?php do_action( 'si_credit_card_checkout_wrap' ) ?>
	<form action="<?php echo si_get_credit_card_checkout_form_action() ?>" autocomplete="on" method="post" accept-charset="utf-8" id="si_credit_card_form">
		<div id="billing_cc_fields">
			<?php do_action( 'si_billing_credit_card_form', $checkout ) ?>
			<div class="row">
				<?php sa_form_fields( $billing_fields, 'billing' ); ?>
				<?php do_action( 'si_billing_payment_fields', $checkout ) ?>
			</div>
			<div class="row">
				<?php sa_form_fields( $cc_fields, 'credit' ); ?>
				<?php do_action( 'si_credit_card_payment_fields', $checkout ) ?>
			</div>
			<?php do_action( 'si_credit_card_form_controls', $checkout ) ?>
			<?php do_action( 'si_credit_card_payment_controls', $checkout ) ?>
			<input type="hidden" name="<?php echo SI_Checkouts::CHECKOUT_ACTION ?>" value="<?php echo SI_Checkouts::PAYMENT_PAGE ?>" />
			<button type="submit" class="button"><?php printf( __( 'Submit Payment', 'sprout-invoices' ), sa_get_formatted_money( si_get_invoice_total() ) ); ?></button>
		</div>
		<?php do_action( 'si_billing_credit_card_form_bottom', $checkout ) ?>
	</form>
</div><!-- #credit_card_checkout_wrap -->
<?php do_action( 'si_credit_card_checkout_post_wrap' ) ?>
