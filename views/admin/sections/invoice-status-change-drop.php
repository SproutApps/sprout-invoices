<div class="quick_status_update">
	<?php do_action( 'si_start_status_change_drop', $id ) ?>
	<span id="status_<?php echo(int) $id ?>">
		<span class="status_change" data-dropdown="#status_change_<?php echo (int) $id ?>">
			<?php
				$status_change_span = '&nbsp;<div class="dashicons dashicons-arrow-down"></div>';
					?>
			<?php if ( $status == SI_Invoice::STATUS_PENDING ) : ?>
				<?php printf( '<span class="si_status publish si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', __( 'Pending payment(s)', 'sprout-invoices' ), __( 'Pending Payment(s)', 'sprout-invoices' ), $status_change_span ); ?>
			<?php elseif ( $status == SI_Invoice::STATUS_PAID ) : ?>
				<?php printf( '<span class="si_status complete si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', __( 'Fully Paid', 'sprout-invoices' ), __( 'Paid', 'sprout-invoices' ), $status_change_span ); ?>
			<?php elseif ( $status == SI_Invoice::STATUS_PARTIAL ) : ?>
				<?php printf( '<span class="si_status publish si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', __( 'Outstanding Balance', 'sprout-invoices' ), __( 'Outstanding Balance', 'sprout-invoices' ), $status_change_span ); ?>
			<?php elseif ( $status == SI_Invoice::STATUS_WO ) : ?>
				<?php printf( '<span class="si_status declined si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', __( 'Written-off', 'sprout-invoices' ), __( 'Written Off', 'sprout-invoices' ), $status_change_span ); ?>
			<?php elseif ( $status === SI_Invoice::STATUS_FUTURE ) : ?>
				<?php printf( '<span class="si_status temp si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', __( 'Temp Invoice', 'sprout-invoices' ), __( 'Scheduled', 'sprout-invoices' ), $status_change_span ); ?>
			<?php elseif ( $status === SI_Invoice::STATUS_ARCHIVED ) : ?>
				<?php printf( '<span class="si_status temp si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', __( 'Archived Invoice', 'sprout-invoices' ), __( 'Archived', 'sprout-invoices' ), $status_change_span ); ?>
			<?php elseif ( apply_filters( 'si_is_invoice_currently_custom_status', false, $id ) ) : ?>
				<?php do_action( 'si_invoice_custom_status_current_label', $id ); ?>
			<?php else : ?>
				<?php printf( '<span class="si_status temp si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', __( 'Temp Invoice', 'sprout-invoices' ), __( 'Temp', 'sprout-invoices' ), $status_change_span ); ?>
			<?php endif ?>
		</span>
	</span>
	<div id="status_change_<?php echo (int) $id ?>" class="dropdown dropdown-tip dropdown-relative dropdown-anchor-right">
		<ul class="si-dropdown-menu">
			<?php if ( SI_Invoice::STATUS_FUTURE !== $status ) : ?>
				<?php if ( $status != SI_Invoice::STATUS_PENDING ) : ?>
					<?php printf( '<li><a class="doc_status_change pending" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', __( 'Mark Pending Payment(s)', 'sprout-invoices' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_PENDING, wp_create_nonce( SI_Controller::NONCE ), __( '<b>Active:</b> Pending Payment(s)', 'sprout-invoices' ) ); ?>
				<?php endif ?>
			<?php endif ?>
			<?php if ( $status != SI_Invoice::STATUS_PARTIAL ) : ?>
				<?php printf( '<li><a class="doc_status_change publish" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', __( 'Outstanding Balance.', 'sprout-invoices' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_PARTIAL, wp_create_nonce( SI_Controller::NONCE ), __( '<b>Active:</b> Partial Payment Received', 'sprout-invoices' ) ); ?>
			<?php endif; ?>
			<?php if ( $status != SI_Invoice::STATUS_PAID ) : ?>
				<?php printf( '<li><a class="doc_status_change publish" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', __( 'Mark as Paid.', 'sprout-invoices' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_PAID, wp_create_nonce( SI_Controller::NONCE ), __( '<b>Complete:</b> Paid in Full', 'sprout-invoices' ) ); ?>
			<?php endif; ?>
			<?php if ( ! apply_filters( 'si_is_invoice_currently_custom_status', false, $id ) ) : ?>
				<?php do_action( 'si_invoice_custom_status_current_option', $id ); ?>
			<?php endif; ?>
			<?php if ( $status != SI_Invoice::STATUS_TEMP && $status != 'auto-draft' ) : ?>
			<li><hr/></li>
				<?php printf( '<li class="casper"><a class="doc_status_change temp" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', __( 'Make Temporary', 'sprout-invoices' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_TEMP, wp_create_nonce( SI_Controller::NONCE ), __( '<b>Temp:</b> Drafted Invoice', 'sprout-invoices' ) ); ?>
			<?php endif ?>
			<li><hr/></li>
			<?php if ( $status != SI_Invoice::STATUS_ARCHIVED ) : ?>
				<?php printf( '<li><a class="doc_status_change decline" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', __( 'Write-off Invoice', 'sprout-invoices' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_ARCHIVED, wp_create_nonce( SI_Controller::NONCE ), __( '<b>Archive:</b> No Client Access', 'sprout-invoices' ) ); ?>
			<?php endif ?>
			<?php if ( $status != SI_Invoice::STATUS_WO ) : ?>
				<?php printf( '<li><a class="doc_status_change decline" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', __( 'Write-off Invoice', 'sprout-invoices' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_WO, wp_create_nonce( SI_Controller::NONCE ), __( '<b>Void:</b> Write-off Invoice', 'sprout-invoices' ) ); ?>
			<?php endif ?>
			<?php
			if ( current_user_can( 'delete_post', $id ) ) {
				printf( '<li><a class="doc_status_delete delete" title="%s" href="%s">%s</a></li>', __( 'Delete Invoice', 'sprout-invoices' ), get_delete_post_link( $id, '' ), __( '<b>Delete:</b> Trash Invoice', 'sprout-invoices' ) );
			} ?>
		</ul>
	</div>
	<?php do_action( 'si_end_status_change_drop', $id ) ?>
</div>
<?php do_action( 'si_status_change_drop_outside', $id ) ?>
