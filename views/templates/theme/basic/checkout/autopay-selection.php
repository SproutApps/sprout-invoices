</div>

<div class="row description">
	


</div>

<div class="row sa-control-group billingallow_to_autobill">
	<div class="sa-controls input_wrap">
		<label for="sa_billing_allow_to_autobill" class="sa-checkbox">
			<span class="sa-form-field sa-form-field-checkbox">
				<input type="checkbox" name="sa_billing_allow_to_autobill" id="sa_billing_allow_to_autobill" checked="checked" value="<?php if ( $default_autopay ) { echo 'On'; }  ?>" class="checkbox">
			</span>
		<?php printf( __( 'I authorize %s to automatically charge the payment method listed above on the bill day below.' , 'sprout-invoices' ), $company_name ) ?></label>
	</div>
</div>

<div class="row">

	<div id="modify_invoice_start_date_wrap" class="sa-control-group">

		<span class="input_wrap">
			<span class="sa-form-field sa-form-field-number">
				<label for="sa_billing_autopay_billdate" style="white-space: nowrap;">
					<?php _e( 'Select Recurring Payment Date:' , 'sprout-invoices' ) ?>&nbsp;
					<select name="sa_billing_autopay_billdate">
						<?php foreach ( $autopay_date_options as $num => $label ) :  ?>
							<option value="<?php echo $num ?>" <?php selected( $num, $default_autopay_date, true ); ?>><?php echo $label ?></option>
						<?php endforeach ?>
					</select>
				</label>
			</span>
		</span>
	</div>

<!-- div closed in another template, started in card-selection.php -->
