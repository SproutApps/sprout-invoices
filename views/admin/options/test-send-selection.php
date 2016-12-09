<div id="test_send_selection">
	<span id="notification_type_wrap">
		<select name="notification_type" id="notification_type">
			<option></option>
			<?php foreach ( $notification_types as $notification_slug => $data ) : ?>
				<?php $notification_name = esc_html( $data['test_name'] ); ?>
				<option 
					value="<?php echo esc_attr( $notification_slug ) ?>" 
					data-action_name="<?php echo esc_attr( $data['action_name'] ) ?>"
					<?php foreach ( $data['record_types'] as $type ) :  ?>
						<?php printf( 'data-record-type="%s"', esc_attr( $type ) ) ?>
					<?php endforeach ?>
					><?php echo esc_html( $data['test_name'] ) ?></option>
			<?php endforeach ?>
		</select>
	</span>

	<p class="description help_block"><?php _e( 'Select a type of notification to send a test.', 'sprout-invoices' ) ?></p>

	<?php foreach ( $notification_types as $notification_slug => $data ) { ?>
		<p id="notification_type_description_<?php echo esc_attr( $notification_slug ); ?>" class="notification_type_description description">
			<?php echo esc_html( $data['description'] ); ?>
		</p>
	<?php } ?>
	<div class="clear"></div>

	<?php if ( ! empty( $recent_invoices ) ) :  ?>
	<span id="recent_type_wrap_invoices" class="recent_records_select_wrap">
		<select name="recent_invoices_type" id="recent_invoices_type">
			<?php foreach ( $recent_invoices as $invoice_id ) : ?>
				<?php $invoice = Si_Invoice::get_instance( $invoice_id ); ?>
				<option value="<?php echo esc_attr( $invoice_id ) ?>" ><?php echo esc_html( $invoice->get_title() ) ?></option>
			<?php endforeach ?>
		</select>
	</span>
	<?php else : ?>
		<?php _e( 'Create an invoice and come back to run a test send.', 'sprout-invoices' ) ?>
	<?php endif ?>

	<?php if ( ! empty( $recent_estimates ) ) :  ?>
	<span id="recent_type_wrap_estimates" class="recent_records_select_wrap">
		<select name="recent_estimates_type" id="recent_estimates_type">
			<?php foreach ( $recent_estimates as $estimate_id ) : ?>
				<?php $estimate = SI_Estimate::get_instance( $estimate_id ); ?>
				<option value="<?php echo esc_attr( $estimate_id ) ?>" ><?php echo esc_html( $estimate->get_title() ) ?></option>
			<?php endforeach ?>
		</select>
	</span>
	<?php else : ?>
		<?php _e( 'Create an estimate and come back to run a test send.', 'sprout-invoices' ) ?>
	<?php endif ?>

	<?php if ( ! empty( $recent_clients ) ) :  ?>
	<span id="recent_type_wrap_clients" class="recent_records_select_wrap">
		<select name="recent_clients_type" id="recent_clients_type">
			<?php foreach ( $recent_clients as $client_id ) : ?>
				<?php $client = SI_Client::get_instance( $client_id ); ?>
				<option value="<?php echo esc_attr( $client_id ) ?>" ><?php echo esc_html( $client->get_title() ) ?></option>
			<?php endforeach ?>
		</select>
	</span>
	<?php else : ?>
		<?php _e( 'Create a client and come back to run a test send.', 'sprout-invoices' ) ?>
	<?php endif ?>


	<?php if ( ! empty( $recent_payments ) ) :  ?>
	<span id="recent_type_wrap_payments" class="recent_records_select_wrap">
		<select name="recent_payments_type" id="recent_payments_type">
			<?php foreach ( $recent_payments as $payment_id ) : ?>
				<?php $payment = SI_Payment::get_instance( $payment_id ); ?>
				<option value="<?php echo esc_attr( $payment_id ) ?>" ><?php echo esc_html( $payment->get_title() ) ?></option>
			<?php endforeach ?>
		</select>
	</span>
	<?php else : ?>
		<?php _e( 'Create a payment and come back to run a test send.', 'sprout-invoices' ) ?>
	<?php endif ?>

	<span class="button" id="send_test_notification" style="display:none;"><?php _e( 'Send', 'sprout-invoices' ) ?></span>

</div><!-- #minor-publishing -->
