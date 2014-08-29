<form id="gb_checkout_review" action="<?php echo si_get_credit_card_checkout_form_action() ?>" method="post">
	<div class="checkout_block right_form clearfix">

		<div class="paymentform-info">
			<h2 class="section_heading gb_ff"><?php si_e( 'Your Payment Information' ); ?></h2>
		</div>
		<fieldset id="gb-billing">
			<table>
				<tbody>
					<?php foreach ( $billing_fields as $key => $data ): ?>
						<tr>
							<th scope="row"><?php echo $data['label']; ?></th>
							<td><?php echo $data['value']; ?></td>
						</tr>
					<?php endforeach; ?>
					<?php foreach ( $cc_fields as $key => $data ): ?>
						<tr>
							<th scope="row"><?php echo $data['label']; ?></th>
							<td><?php echo $data['value']; ?></td>
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
