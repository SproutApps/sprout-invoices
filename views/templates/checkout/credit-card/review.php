<form id="si_checkout_review" action="<?php echo si_get_credit_card_checkout_form_action() ?>" method="post">
	<div class="checkout_block right_form clearfix">

		<div class="paymentform-info">
			<h2 class="section_heading"><?php si_e( 'Your Payment Information' ); ?></h2>
		</div>
		<fieldset id="si-billing">
			<table>
				<tbody>
					<?php foreach ( $billing_fields as $key => $data ): ?>
						<tr>
							<th scope="row"><?php echo esc_html( $data['label'] ); ?></th>
							<td><?php echo esc_html( $data['value'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					<?php foreach ( $cc_fields as $key => $data ): ?>
						<tr>
							<th scope="row"><?php echo esc_html( $data['label'] ); ?></th>
							<td><?php echo esc_html( $data['value'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</fieldset>

	</div>
	<div class="checkout-controls clearfix">
		<?php do_action( 'si_credit_card_form_controls', $checkout ) ?>
		<?php do_action( 'si_credit_card_review_controls', $checkout ) ?>
		<input type="hidden" name="<?php echo SI_Checkouts::CHECKOUT_ACTION ?>" value="<?php echo SI_Checkouts::REVIEW_PAGE ?>" />
		<input class="form-submit submit checkout_next_step" type="submit" value="<?php si_e( 'Submit Order' ); ?>" name="si_checkout_button" />
	</div>
</form>
