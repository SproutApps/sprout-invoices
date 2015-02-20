<div class="quick_status_update">
	<span id="status_<?php echo $id ?>">
		<span class="status_change" data-dropdown="#status_change_<?php echo $id ?>">
			<?php 
				$status_change_span = '&nbsp;<div class="dashicons dashicons-arrow-down"></div>';
				 ?>
			<?php if ( $status == SI_Invoice::STATUS_PENDING ): ?>
				<?php printf( '<span class="si_status publish si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', self::__( 'Pending payment(s)' ), si__('Pending Payment(s)'), $status_change_span ); ?>
			<?php elseif ( $status == SI_Invoice::STATUS_PAID ): ?>
				<?php printf( '<span class="si_status complete si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', self::__( 'Fully Paid' ), si__('Paid'), $status_change_span ); ?>
			<?php elseif ( $status == SI_Invoice::STATUS_PARTIAL ): ?>
				<?php printf( '<span class="si_status publish si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', self::__( 'Outstanding Balance' ), si__('Outstanding Balance'), $status_change_span ); ?>
			<?php elseif ( $status == SI_Invoice::STATUS_WO ): ?>
				<?php printf( '<span class="si_status declined si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', self::__( 'Written-off' ), si__('Written Off'), $status_change_span ); ?>
			<?php else: ?>
				<?php printf( '<span class="si_status temp si_tooltip button current_status" title="%s" disabled><span>%s</span>%s</span>', self::__( 'Temp Invoice' ), si__('Temp'), $status_change_span ); ?>
			<?php endif ?>
		</span>
	</span>
	<div id="status_change_<?php echo $id ?>" class="dropdown dropdown-tip dropdown-relative dropdown-anchor-right">
		<ul class="dropdown-menu">
			<?php if ( $status != SI_Invoice::STATUS_PENDING ): ?>
				<?php printf( '<li><a class="doc_status_change pending" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', self::__( 'Mark Pending Payment(s)' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_PENDING, wp_create_nonce( SI_Controller::NONCE ), self::__( '<b>Active:</b> Pending Payment(s)' ) ); ?>
			<?php endif ?>
			<?php /**/ if ( $status != SI_Invoice::STATUS_PARTIAL ): ?>
				<?php printf( '<li><a class="doc_status_change publish" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', self::__( 'Outstanding Balance.' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_PARTIAL, wp_create_nonce( SI_Controller::NONCE ), self::__( '<b>Active:</b> Partial Payment Received' ) ); ?>
			<?php endif; /**/ ?>
			<?php if ( $status != SI_Invoice::STATUS_PAID ): ?>
				<?php printf( '<li><a class="doc_status_change publish" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', self::__( 'Mark as Paid.' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_PAID, wp_create_nonce( SI_Controller::NONCE ), self::__( '<b>Complete:</b> Paid in Full' ) ); ?>
			<?php endif; ?>
			<?php if ( $status != SI_Invoice::STATUS_TEMP && $status != 'auto-draft' ): ?>
			<li><hr/></li>
				<?php printf( '<li class="casper"><a class="doc_status_change temp" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', self::__( 'Make Temporary' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_TEMP, wp_create_nonce( SI_Controller::NONCE ), self::__( '<b>Temp:</b> Drafted Invoice' ) ); ?>
			<?php endif ?>
			<li><hr/></li>
			<?php if ( $status != SI_Invoice::STATUS_WO ): ?>
				<?php printf( '<li><a class="doc_status_change decline" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', self::__( 'Write-off Invoice' ), get_edit_post_link( $id ), $id, SI_Invoice::STATUS_WO, wp_create_nonce( SI_Controller::NONCE ), self::__( '<b>Void:</b> Write-off Invoice' ) ); ?>
			<?php endif ?>
			<?php
				if ( current_user_can( 'delete_post', $id ) ) {
					printf( '<li><a class="doc_status_delete delete" title="%s" href="%s">%s</a></li>', self::__( 'Delete Invoice' ), get_delete_post_link( $id, '' ), self::__( '<b>Delete:</b> Trash Invoice' ) );
				} ?>
		</ul>
	</div>
</div>