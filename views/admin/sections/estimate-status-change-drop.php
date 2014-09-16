<div class="quick_status_update">
	<span id="status_<?php echo $id ?>">
		<span class="status_change" data-dropdown="#status_change_<?php echo $id ?>">
			<?php 
				$status_change_span = '&nbsp;<div class="dashicons dashicons-arrow-down"></div>';
				 ?>
			<?php if ( $status == SI_Estimate::STATUS_PENDING ): ?>
				<?php printf( '<button class="si_status publish tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', self::__( 'Currently Pending.' ), si__('Pending'), $status_change_span ); ?>
			<?php elseif ( $status == SI_Estimate::STATUS_APPROVED ): ?>
				<?php printf( '<button class="si_status complete tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', self::__( 'Currently Approved.' ), si__('Approved'), $status_change_span ); ?>
			<?php elseif ( $status == SI_Estimate::STATUS_DECLINED ): ?>
				<?php printf( '<button class="si_status declined tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', self::__( 'Currently Declined.' ), si__('Declined'), $status_change_span ); ?>
			<?php elseif ( $status == SI_Estimate::STATUS_REQUEST ): ?>
				<?php printf( '<button class="si_status draft tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', self::__( 'New Estimate Request' ), si__('Submission'), $status_change_span ); ?>
			<?php else: ?>
				<?php printf( '<button class="si_status draft tooltip button current_status" title="%s" disabled><span>%s</span>%s</button>', self::__( 'Pending Estimate Request.' ), si__('Draft'), $status_change_span ); ?>
			<?php endif ?>
		</span>
	</span>

	<div id="status_change_<?php echo $id ?>" class="dropdown dropdown-tip dropdown-relative dropdown-anchor-right">
		<ul class="dropdown-menu">
			<?php if ( $status != SI_Estimate::STATUS_PENDING ): ?>
				<?php printf( '<li><a class="doc_status_change pending" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', self::__( 'Mark Pending' ), get_edit_post_link( $id ), $id, SI_Estimate::STATUS_PENDING, wp_create_nonce( SI_Controller::NONCE ), self::__( '<b>Pending: </b> Waiting for Review' ) ); ?>
			<?php endif ?>
			<?php if ( $status != SI_Estimate::STATUS_APPROVED ): ?>
				<?php printf( '<li><a class="doc_status_change publish" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', self::__( 'Mark Approved' ), get_edit_post_link( $id ), $id, SI_Estimate::STATUS_APPROVED, wp_create_nonce( SI_Controller::NONCE ), self::__( '<b>Complete:</b> Estimate Approved' ) ); ?>
			<?php endif ?>
			<?php if ( $status != SI_Estimate::STATUS_DECLINED ): ?>
				<?php printf( '<li><a class="doc_status_change decline" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s">%s</a></li>', self::__( 'Mark Declined' ), get_edit_post_link( $id ), $id, SI_Estimate::STATUS_DECLINED, wp_create_nonce( SI_Controller::NONCE ), self::__( '<b>Void:</b> Estimate Declined' ) ); ?>
			<?php endif ?>
			<li><hr/></li>
			<?php
				if ( current_user_can( 'delete_post', $id ) ) {
					printf( '<li><a class="doc_status_change delete" title="%s" href="%s">%s</a></li>', self::__( 'Delete Estimate' ), get_delete_post_link( $id, '' ), self::__( '<b>Delete:</b> Trash Estimate' ) );
				} ?>
		</ul>
	</div>
</div>