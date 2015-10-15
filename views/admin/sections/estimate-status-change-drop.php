<div class="quick_status_update">
	<span id="status_<?php echo (int) $id ?>">
		<span class="status_change" data-dropdown="#status_change_<?php echo (int) $id ?>">
			<?php
				$status_change_span = '&nbsp;<div class="dashicons dashicons-arrow-down"></div>';
					?>
			<?php if ( $status == SI_Estimate::STATUS_PENDING ) : ?>
				<?php printf( '<button class="si_status publish si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', __( 'Currently Pending.', 'sprout-invoices' ), __( 'Pending', 'sprout-invoices' ), $status_change_span ); ?>
			<?php elseif ( $status == SI_Estimate::STATUS_APPROVED ) : ?>
				<?php printf( '<button class="si_status complete si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', __( 'Currently Approved.', 'sprout-invoices' ), __( 'Approved', 'sprout-invoices' ), $status_change_span ); ?>
			<?php elseif ( $status == SI_Estimate::STATUS_DECLINED ) : ?>
				<?php printf( '<button class="si_status declined si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', __( 'Currently Declined.', 'sprout-invoices' ), __( 'Declined', 'sprout-invoices' ), $status_change_span ); ?>
			<?php elseif ( $status == SI_Estimate::STATUS_REQUEST ) : ?>
				<?php printf( '<button class="si_status draft si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', __( 'New Estimate Request', 'sprout-invoices' ), __( 'Submission', 'sprout-invoices' ), $status_change_span ); ?>
			<?php else : ?>
				<?php printf( '<button class="si_status draft si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', __( 'Pending Estimate Request.', 'sprout-invoices' ), __( 'Draft', 'sprout-invoices' ), $status_change_span ); ?>
			<?php endif ?>
		</span>
	</span>

	<div id="status_change_<?php echo (int) $id ?>" class="dropdown dropdown-tip dropdown-relative dropdown-anchor-right">
		<ul class="dropdown-menu">
			<?php if ( $status != SI_Estimate::STATUS_PENDING ) : ?>
				<?php printf( '<li><a class="doc_status_change pending" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', __( 'Mark Pending', 'sprout-invoices' ), get_edit_post_link( $id ), $id, SI_Estimate::STATUS_PENDING, wp_create_nonce( SI_Controller::NONCE ), __( '<b>Pending: </b> Waiting for Review', 'sprout-invoices' ) ); ?>
			<?php endif ?>
			<?php if ( $status != SI_Estimate::STATUS_APPROVED ) : ?>
				<?php printf( '<li><a class="doc_status_change publish" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', __( 'Mark Approved', 'sprout-invoices' ), get_edit_post_link( $id ), $id, SI_Estimate::STATUS_APPROVED, wp_create_nonce( SI_Controller::NONCE ), __( '<b>Complete:</b> Estimate Approved', 'sprout-invoices' ) ); ?>
			<?php endif ?>
			<?php if ( $status != SI_Estimate::STATUS_DECLINED ) : ?>
				<?php printf( '<li><a class="doc_status_change decline" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', __( 'Mark Declined', 'sprout-invoices' ), get_edit_post_link( $id ), $id, SI_Estimate::STATUS_DECLINED, wp_create_nonce( SI_Controller::NONCE ), __( '<b>Void:</b> Estimate Declined', 'sprout-invoices' ) ); ?>
			<?php endif ?>
			<li><hr/></li>
			<?php
			if ( current_user_can( 'delete_post', $id ) ) {
				printf( '<li><a class="doc_status_delete delete" title="%s" href="%s">%s</a></li>', __( 'Delete Estimate', 'sprout-invoices' ), get_delete_post_link( $id, '' ), __( '<b>Delete:</b> Trash Estimate', 'sprout-invoices' ) );
			} ?>
		</ul>
	</div>
</div>