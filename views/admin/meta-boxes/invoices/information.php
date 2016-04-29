<!-- issue date -->
<?php
global $action;
$datef = __( 'M j, Y @ G:i' );
if ( 0 != $post->ID ) {
	$stamp = __( 'Issued on: <b>%1$s</b>', 'sprout-invoices' );
	$date = date_i18n( $datef, $issue_date );
} else { // draft (no saves, and thus no date specified)
	$stamp = __( 'Issue <b>immediately</b>', 'sprout-invoices' );
	$date = date_i18n( $datef, strtotime( current_time( 'mysql' ) ) );
} ?>

<?php do_action( 'doc_information_meta_box_first', $invoice ) ?>

<div class="misc-pub-section" data-edit-id="status" data-edit-type="select">
	<span id="status" class="wp-media-buttons-icon"><?php _e( 'Status:', 'sprout-invoices' ) ?> <b><?php echo esc_html( $status_options[ $status ] ) ?></b></span>

	<a href="#edit_status" class="edit-status hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Select different status', 'sprout-invoices' ) ?></span>
	</a>

	<div id="status_div" class="control_wrap hide-if-js">
		<div class="status-wrap">
			<select name="post_status">
				<?php foreach ( $status_options as $status_key => $status_name ) :  ?>
					<?php printf( '<option value="%s" %s>%s</option>', $status_key, selected( $status_key, $status, false ), $status_name ) ?>
				<?php endforeach ?>
			</select>
 		</div>
		<p>
			<a href="#edit_status" class="save_control save-status hide-if-no-js button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
			<a href="#edit_status" class="cancel_control cancel-status hide-if-no-js button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
		</p>
 	</div>
</div>

<div id="submitdiv">
	<div class="misc-pub-section curtime misc-pub-curtime">
		<span id="timestamp"><?php printf( $stamp, $date ); ?></span>

		<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit date and time' ); ?></span></a>
		<div id="timestampdiv" class="hide-if-js"><?php touch_time( ($action == 'edit'), 1 ); ?></div>
	</div>
</div>


<!-- due date -->
<div class="misc-pub-section" data-edit-id="due_date" data-edit-type="date">
	<span id="due_date" class="wp-media-buttons-icon"><?php _e( 'Due by:', 'sprout-invoices' ) ?> <b><?php echo date( 'M j, Y', $due_date ) ?></b></span>

	<a href="#edit_due_date" class="edit-due_date hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Edit due date and time', 'sprout-invoices' ) ?></span>
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
			<a href="#edit_due_date" class="save_control save-due_date hide-if-no-js button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
			<a href="#edit_due_date" class="cancel_control cancel-due_date hide-if-no-js button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
		</p>
 	</div>
</div>

<?php do_action( 'doc_information_meta_box_date_row_last', $invoice ) ?>

<hr/>

<?php do_action( 'doc_information_meta_box_client_row', $invoice ) ?>

<!-- Client -->
<div class="misc-pub-section" data-edit-id="client" data-edit-type="select">
	<?php
		$client_name = ( $client_id ) ? sprintf( '<a href="%s">%s</a>', get_edit_post_link( $client_id ), get_the_title( $client_id ) ) : __( 'Client N/A', 'sprout-invoices' );
			?>
	<span id="client" class="wp-media-buttons-icon"><?php _e( 'Invoice for', 'sprout-invoices' ) ?> <b><?php echo $client_name ?></b></span>

	<a href="#edit_client" class="edit-client hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Select different client', 'sprout-invoices' ) ?></span>
	</a>

	<div id="client_div" class="control_wrap hide-if-js">
		<div class="client-wrap">
			<select name="sa_metabox_client" class="select2">
				<option value="create_client"><?php _e( 'Create client', 'sprout-invoices' ) ?></option>
				<?php foreach ( $client_options as $id => $client_name ) :  ?>
					<?php printf( '<option value="%s" %s>%s</option>', $id, selected( $id, $client_id, false ), $client_name ) ?>
				<?php endforeach ?>
			</select>

			<a href="#TB_inline?width=600&height=420&inlineId=client_creation_modal" id="create_client_tb_link" class="thickbox si_tooltip" title="<?php _e( 'Create new client', 'sprout-invoices' ) ?>"></a>

 		</div>
		<p>
			<a href="#edit_client" class="save_control save-client hide-if-no-js button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
			<a href="#edit_client" class="cancel_control cancel-client hide-if-no-js button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
		</p>
 	</div>
</div>
<?php do_action( 'doc_information_meta_box_client_row_after_client', $invoice ) ?>

<?php if ( $estimate_id ) :  ?>
	<!-- Invoice -->
	<div class="misc-pub-section" data-edit-id="estimate_id">
		<span id="estimate_id" class="wp-media-buttons-icon"><?php _e( 'Associated Estimate:', 'sprout-invoices' ) ?> <b><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a></b></span>
	</div>
<?php endif ?>


<!-- ID -->
<div class="misc-pub-section" data-edit-id="invoice_id">
	<span id="invoice_id" class="wp-media-buttons-icon"><?php _e( 'Invoice ID', 'sprout-invoices' ) ?> #<b><?php echo esc_html( $invoice_id ); ?></b></span>

	<a href="#edit_invoice_id" class="edit-invoice_id hide-if-no-js edit_control">
		<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Edit invoice id', 'sprout-invoices' ) ?></span>
	</a>

	<div id="invoice_id_div" class="control_wrap hide-if-js">
		<div class="invoice_id-wrap">
			<input type="text" name="invoice_id" value="<?php echo esc_attr( $invoice_id ) ?>" size="10">
 		</div>
		<p>
			<a href="#edit_invoice_id" class="save_control save-invoice_id button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
			<a href="#edit_invoice_id" class="cancel_control cancel-invoice_id button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
		</p>
 	</div>
</div>

<?php do_action( 'doc_information_meta_box_client_row_last', $invoice ) ?>

<hr/>

<?php do_action( 'doc_information_meta_box_meta_row', $invoice ) ?>

<!-- PO Number -->
<div class="misc-pub-section" data-edit-id="po_number">
	<span id="po_number" class="wp-media-buttons-icon"><?php _e( 'PO Number', 'sprout-invoices' ) ?> #<b><?php echo esc_html( $po_number ) ?></b></span>

	<a href="#edit_po_number" class="edit-po_number hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Edit PO number', 'sprout-invoices' ) ?></span>
	</a>

	<div id="po_number_div" class="control_wrap hide-if-js">
		<div class="po_number-wrap">
			<input type="text" name="po_number" value="<?php echo esc_attr( $po_number ); ?>" size="10">
 		</div>
		<p>
			<a href="#edit_po_number" class="save_control save-po_number hide-if-no-js button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
			<a href="#edit_po_number" class="cancel_control cancel-po_number hide-if-no-js button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
		</p>
 	</div>
</div>

<!-- Discount -->
<div class="misc-pub-section update-total" data-edit-id="discount">
	<span id="discount" class="wp-media-buttons-icon"><?php _e( 'Discount', 'sprout-invoices' ) ?> <b><?php echo (float) $discount ?></b>%</span>

	<a href="#edit_discount" class="edit-discount hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Edit discount', 'sprout-invoices' ) ?></span>
	</a>
 	<span title="<?php _e( 'The discount is applied after tax.', 'sprout-invoices' ) ?>" class="helptip"></span>

	<div id="discount_div" class="control_wrap hide-if-js">
		<div class="discount-wrap">
			<input type="text" name="discount" value="<?php echo (float) $discount ?>" size="3">%
 		</div>
		<p>
			<a href="#edit_discount" class="save_control save-discount hide-if-no-js button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
			<a href="#edit_discount" class="cancel_control cancel-discount hide-if-no-js button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
		</p>
 	</div>
</div>

<!-- Tax -->
<div class="misc-pub-section update-total" data-edit-id="tax">
	<span id="tax" class="wp-media-buttons-icon"><?php _e( 'Tax', 'sprout-invoices' ) ?> <b><?php echo (float) $tax ?></b>%</span>

	<a href="#edit_tax" class="edit-tax hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Edit tax', 'sprout-invoices' ) ?></span>
	</a>
	<span title="<?php _e( 'Tax is applied before the discount.', 'sprout-invoices' ) ?>" class="helptip"></span>

	<div id="tax_div" class="control_wrap hide-if-js">
		<div class="tax-wrap">
			<input type="text" name="tax" value="<?php echo (float) $tax ?>" size="3">%
 		</div>
 		
		<p>
			<a href="#edit_tax" class="save_control save-tax hide-if-no-js button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
			<a href="#edit_tax" class="cancel_control cancel-tax hide-if-no-js button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
		</p>
 	</div>
</div>

<!-- Tax2 -->
<div class="misc-pub-section update-total" data-edit-id="tax2">
	<span id="tax2" class="wp-media-buttons-icon"><?php _e( 'Tax', 'sprout-invoices' ) ?> <b><?php echo (float) $tax2 ?></b>%</span>

	<a href="#edit_tax2" class="edit-tax2 hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Edit tax', 'sprout-invoices' ) ?></span>
	</a>
	<span title="<?php _e( 'Tax is applied before the discount.', 'sprout-invoices' ) ?>" class="si_tooltip"></span>

	<div id="tax2_div" class="control_wrap hide-if-js">
		<div class="tax2-wrap">
			<input type="text" name="tax2" value="<?php echo (float) $tax2 ?>" size="3">%
 		</div>
 		
		<p>
			<a href="#edit_tax2" class="save_control save-tax2 hide-if-no-js button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
			<a href="#edit_tax2" class="cancel_control cancel-tax2 hide-if-no-js button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
		</p>
 	</div>
</div>

<?php do_action( 'doc_information_meta_box_last', $invoice ) ?>
