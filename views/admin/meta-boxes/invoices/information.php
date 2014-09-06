<?php 
	$due_date = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_due_date() : current_time( 'timestamp' )+(60*60*24*30);
	$issue_date = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_issue_date() : strtotime( $post->post_date ) ;
	$estimate_id = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_estimate_id() : 0 ;
	$invoice_id = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_invoice_id() : '00001';
	$po_number = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_po_number() : '';
	$client_id = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_client_id() : 0;
	$deposit = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_deposit() : '';
	$discount = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_discount() : '';
	$tax = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_tax() : '';
	$tax2 = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_tax2() : '';
	$currency = ( is_a( $invoice, 'SI_Invoice' ) ) ? $invoice->get_currency() : '';
	 ?>
<!-- issue date -->
<?php
global $action;
$datef = __( 'M j, Y @ G:i' );
if ( 0 != $post->ID ) {
	$stamp = __('Issued on: <b>%1$s</b>');
	$date = date_i18n( $datef, $issue_date );
} else { // draft (no saves, and thus no date specified)
	$stamp = __('Issue <b>immediately</b>');
	$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
} ?>


<div class="misc-pub-section" data-edit-id="status" data-edit-type="select">
	<span id="status" class="wp-media-buttons-icon"><?php si_e('Status:') ?> <b><?php echo $status_options[$status] ?></b></span>

	<a href="#edit_status" class="edit-status hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Select different status') ?></span>
	</a>

	<div id="status_div" class="control_wrap hide-if-js">
		<div class="status-wrap">
			<select name="post_status">
				<?php foreach ( $status_options as $status_key => $status_name ): ?>
					<?php printf( '<option value="%s" %s>%s</option>', $status_key, selected( $status_key, $status, FALSE ), $status_name ) ?>
				<?php endforeach ?>
			</select>
 		</div>
		<p>
			<a href="#edit_status" class="save_control save-status hide-if-no-js button"><?php si_e('OK') ?></a>
			<a href="#edit_status" class="cancel_control cancel-status hide-if-no-js button-cancel"><?php si_e('Cancel') ?></a>
		</p>
 	</div>
</div>

<div id="submitdiv">
	<div class="misc-pub-section curtime misc-pub-curtime">
		<span id="timestamp"><?php printf($stamp, $date); ?></span>

		<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit date and time' ); ?></span></a>
		<div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'), 1); ?></div>
	</div>
</div>


<!-- due date -->
<div class="misc-pub-section" data-edit-id="due_date" data-edit-type="date">
	<span id="due_date" class="wp-media-buttons-icon"><?php si_e('Due by:') ?> <b><?php echo date( 'M j, Y', $due_date ) ?></b></span>

	<a href="#edit_due_date" class="edit-due_date hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Edit due date and time') ?></span>
	</a>

	<div id="due_date_div" class="control_wrap hide-if-js">
		<div class="due_date-wrap">
			<select id="due_mm" name="due_mm">
				<option value="01" <?php selected( date( 'm', $due_date ), '01' ) ?>>01-Jan</option>
				<option value="02" <?php selected( date( 'm', $due_date ), '02' ) ?>>02-Feb</option>
				<option value="03" <?php selected( date( 'm', $due_date ), '03' ) ?>>03-Mar</option>
				<option value="04" <?php selected( date( 'm', $due_date ), '04' ) ?>>04-Apr</option>
				<option value="05" <?php selected( date( 'm', $due_date ), '05' ) ?>>05-May</option>
				<option value="06" <?php selected( date( 'm', $due_date ), '06' ) ?>>06-Jun</option>
				<option value="07" <?php selected( date( 'm', $due_date ), '07' ) ?>>07-Jul</option>
				<option value="08" <?php selected( date( 'm', $due_date ), '08' ) ?>>08-Aug</option>
				<option value="09" <?php selected( date( 'm', $due_date ), '09' ) ?>>09-Sep</option>
				<option value="10" <?php selected( date( 'm', $due_date ), '10' ) ?>>10-Oct</option>
				<option value="11" <?php selected( date( 'm', $due_date ), '11' ) ?>>11-Nov</option>
				<option value="12" <?php selected( date( 'm', $due_date ), '12' ) ?>>12-Dec</option>
			</select>
 			<input type="text" id="due_jj" name="due_j" value="<?php echo date( 'j', $due_date ) ?>" size="2" maxlength="2" autocomplete="off">, <input type="text" id="due_o" name="due_o" value="<?php echo date( 'o', $due_date ) ?>" size="4" maxlength="4" autocomplete="off">
 		</div>
		<p>
			<a href="#edit_due_date" class="save_control save-due_date hide-if-no-js button"><?php si_e('OK') ?></a>
			<a href="#edit_due_date" class="cancel_control cancel-due_date hide-if-no-js button-cancel"><?php si_e('Cancel') ?></a>
		</p>
 	</div>
</div>

<hr/>

<!-- Client -->
<div class="misc-pub-section" data-edit-id="client" data-edit-type="select">
	<?php 
		$client_name = ( $client_id ) ? sprintf( '<a href="%s">%s</a>', get_edit_post_link( $client_id ), get_the_title( $client_id ) ) : si__('Client N/A') ;
		 ?>
	<span id="client" class="wp-media-buttons-icon"><?php si_e('Invoice for') ?> <b><?php echo $client_name ?></b></span>

	<a href="#edit_client" class="edit-client hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Select different client') ?></span>
	</a>

	<div id="client_div" class="control_wrap hide-if-js">
		<div class="client-wrap">
			<select name="client">
				<option value="create_client"><?php si_e('Create client') ?></option>
				<?php foreach ( $client_options as $id => $client_name ): ?>
					<?php printf( '<option value="%s" %s>%s</option>', $id, selected( $id, $client_id, FALSE ), $client_name ) ?>
				<?php endforeach ?>
			</select>

			<a href="#TB_inline?width=600&height=380&inlineId=client_creation_modal" id="create_client_tb_link" class="thickbox helptip" title="<?php si_e('Create new client') ?>"></a>

 		</div>
		<p>
			<a href="#edit_client" class="save_control save-client hide-if-no-js button"><?php si_e('OK') ?></a>
			<a href="#edit_client" class="cancel_control cancel-client hide-if-no-js button-cancel"><?php si_e('Cancel') ?></a>
		</p>
 	</div>
</div>

<?php if ( $estimate_id ): ?>
	<!-- Invoice -->
	<div class="misc-pub-section" data-edit-id="estimate_id">
		<span id="estimate_id" class="wp-media-buttons-icon"><?php si_e('Associated Estimate:') ?> <b><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a></b></span>
	</div>
<?php endif ?>


<!-- ID -->
<div class="misc-pub-section" data-edit-id="invoice_id">
	<span id="invoice_id" class="wp-media-buttons-icon"><?php si_e('Invoice ID') ?> #<b><?php echo $invoice_id ?></b></span>

	<a href="#edit_invoice_id" class="edit-invoice_id hide-if-no-js edit_control">
		<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Edit invoice id') ?></span>
	</a>

	<div id="invoice_id_div" class="control_wrap hide-if-js">
		<div class="invoice_id-wrap">
			<input type="text" name="invoice_id" value="<?php echo $invoice_id ?>" size="10">
 		</div>
		<p>
			<a href="#edit_invoice_id" class="save_control save-invoice_id button"><?php si_e('OK') ?></a>
			<a href="#edit_invoice_id" class="cancel_control cancel-invoice_id button-cancel"><?php si_e('Cancel') ?></a>
		</p>
 	</div>
</div>
<hr/>

<!-- PO Number -->
<div class="misc-pub-section" data-edit-id="po_number">
	<span id="po_number" class="wp-media-buttons-icon"><?php si_e('PO Number') ?> #<b><?php echo $po_number ?></b></span>

	<a href="#edit_po_number" class="edit-po_number hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Edit PO number') ?></span>
	</a>

	<div id="po_number_div" class="control_wrap hide-if-js">
		<div class="po_number-wrap">
			<input type="text" name="po_number" value="<?php echo $po_number ?>" size="10">
 		</div>
		<p>
			<a href="#edit_po_number" class="save_control save-po_number hide-if-no-js button"><?php si_e('OK') ?></a>
			<a href="#edit_po_number" class="cancel_control cancel-po_number hide-if-no-js button-cancel"><?php si_e('Cancel') ?></a>
		</p>
 	</div>
</div>

<!-- Discount -->
<div class="misc-pub-section update-total" data-edit-id="discount">
	<span id="discount" class="wp-media-buttons-icon"><?php si_e('Discount') ?> <b><?php echo $discount ?></b>%</span>

	<a href="#edit_discount" class="edit-discount hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Edit discount') ?></span>
	</a>
 	<span title="The discount is applied after tax." class="helptip"></span>

	<div id="discount_div" class="control_wrap hide-if-js">
		<div class="discount-wrap">
			<input type="text" name="discount" value="<?php echo $discount ?>" size="3">%
 		</div>
		<p>
			<a href="#edit_discount" class="save_control save-discount hide-if-no-js button"><?php si_e('OK') ?></a>
			<a href="#edit_discount" class="cancel_control cancel-discount hide-if-no-js button-cancel"><?php si_e('Cancel') ?></a>
		</p>
 	</div>
</div>

<!-- Tax -->
<div class="misc-pub-section update-total" data-edit-id="tax">
	<span id="tax" class="wp-media-buttons-icon"><?php si_e('Tax') ?> <b><?php echo $tax ?></b>%</span>

	<a href="#edit_tax" class="edit-tax hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Edit tax') ?></span>
	</a>
	<span title="Tax is applied before the discount." class="helptip"></span>

	<div id="tax_div" class="control_wrap hide-if-js">
		<div class="tax-wrap">
			<input type="text" name="tax" value="<?php echo $tax ?>" size="3">%
 		</div>
 		
		<p>
			<a href="#edit_tax" class="save_control save-tax hide-if-no-js button"><?php si_e('OK') ?></a>
			<a href="#edit_tax" class="cancel_control cancel-tax hide-if-no-js button-cancel"><?php si_e('Cancel') ?></a>
		</p>
 	</div>
</div>

<!-- Tax2 -->
<div class="misc-pub-section update-total" data-edit-id="tax2">
	<span id="tax2" class="wp-media-buttons-icon"><?php si_e('Tax') ?> <b><?php echo $tax2 ?></b>%</span>

	<a href="#edit_tax2" class="edit-tax2 hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Edit tax') ?></span>
	</a>
	<span title="Tax is applied before the discount." class="tooltip"></span>

	<div id="tax2_div" class="control_wrap hide-if-js">
		<div class="tax2-wrap">
			<input type="text" name="tax2" value="<?php echo $tax2 ?>" size="3">%
 		</div>
 		
		<p>
			<a href="#edit_tax2" class="save_control save-tax2 hide-if-no-js button"><?php si_e('OK') ?></a>
			<a href="#edit_tax2" class="cancel_control cancel-tax2 hide-if-no-js button-cancel"><?php si_e('Cancel') ?></a>
		</p>
 	</div>
</div>
<?php 
	// FUTURE
	// Bring the currency option from payments into the invoice/estimate so each estimate can have it's own currency.
	/*/
	 ?>
<!-- Currency -->
<div class="misc-pub-section" data-edit-id="currency">
	<span id="currency" class="wp-media-buttons-icon"><?php si_e('Currency Code') ?> <b><?php echo $currency ?></b></span>

	<a href="#edit_currency" class="edit-currency hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Edit invoice id') ?></span>
	</a>

	<div id="currency_div" class="control_wrap hide-if-js">
		<div class="currency-wrap">
			<input type="text" name="currency" value="<?php echo $currency ?>" size="10">
 		</div>
		<p>
			<a href="#edit_currency" class="save_control save-currency hide-if-no-js button"><?php si_e('OK') ?></a>
			<a href="#edit_currency" class="cancel_control cancel-currency hide-if-no-js button-cancel"><?php si_e('Cancel') ?></a>
		</p>
 	</div>
</div>
<?php /**/ ?>