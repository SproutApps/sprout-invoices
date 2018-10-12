</div>

<div class="row description">
	
	<p><?php _e( 'Please select from the payment types below. Saving your payment information is secure and makes things easier for the future' , 'sprout-invoices' ) ?></p>

</div>
<div class="row">

	<span class="input_wrap">
		<span class="sa-form-field sa-form-field-radios sa-form-field-required">
			<?php
			if ( ! empty( $cards ) ) : ?>

				<?php
					$icon = '<svg width="20" height="20" viewBox="0 0 40 40"><g transform="scale(0.03125 0.03125)"><path d="M128 320v640c0 35.2 28.8 64 64 64h576c35.2 0 64-28.8 64-64v-640h-704zM320 896h-64v-448h64v448zM448 896h-64v-448h64v448zM576 896h-64v-448h64v448zM704 896h-64v-448h64v448z"></path><path d="M848 128h-208v-80c0-26.4-21.6-48-48-48h-224c-26.4 0-48 21.6-48 48v80h-208c-26.4 0-48 21.6-48 48v80h832v-80c0-26.4-21.6-48-48-48zM576 128h-192v-63.198h192v63.198z"></path></g></svg>';
						?>
				<?php foreach ( $cards as $payment_profile_id => $name ) : ?>
					<span class="sa-form-field-radio clearfix">
						<label for="sa_credit_payment_method_<?php echo $payment_profile_id ?>">
							<input type="radio" name="sa_credit_payment_method" id="sa_credit_payment_method_<?php echo $payment_profile_id ?>" value="<?php echo $payment_profile_id ?>"><?php printf( '%2$s <a href="javascript:void(0)" data-ref="%3$s" data-invoice-id="%5$s" class="cim_delete_card" title="%4$s">%6$s</a>', __( 'Previously used' , 'sprout-invoices' ), $name, $payment_profile_id, __( 'Remove this CC from your account.' , 'sprout-invoices' ), (int) $invoice_id, $icon ) ?>
						</label>
					</span>
				<?php endforeach ?>
			<?php endif ?>
			<span class="sa-form-field-radio clearfix">
				<label for="sa_credit_payment_method_credit">
				<input type="radio" name="sa_credit_payment_method" id="sa_credit_payment_method_credit" value="new_credit" checked="checked"><b><?php _e( 'New Credit/Debit Card' , 'sprout-invoices' ) ?></b></label>
			</span>
		</span>
	</span>

<!-- div closed in another template, started in card-selection.php -->
