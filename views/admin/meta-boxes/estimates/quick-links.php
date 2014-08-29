<?php 
	$current_status = '';
	$disabled = ''; ?>

<?php 
	$num_posts = wp_count_posts( SI_Estimate::POST_TYPE );
	$num_posts->{'auto-draft'} = 0; // remove auto-drafts
	$total_posts = array_sum( (array) $num_posts );
	if ( $total_posts >= 10 && apply_filters( 'show_upgrade_messaging', '__return_true' ) ) {
		printf( si__('<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span><strong>Congrats on your %s Estimate!</strong> Please consider supporting the future of Sprout Invoices by <a href="%s">upgrading</a>.</p></div>'), self::number_ordinal_suffix($total_posts), si_get_purchase_link() );
	} ?>

<div id="subject_header" class="clearfix">
	<div id="subject_header_actions" class="clearfix">
		<div id="subject_input_wrap">
			<?php $title = ( $status != 'auto-draft' ) ? get_the_title( $id ) : '' ; ?>
			<input type="text" name="subject" value="<?php echo $title ?>" placeholder="<?php si_e('Subject...') ?>">
		</div>

		<?php if ( $statuses ): ?>
			<div id="quick_links">
				<?php 
					unset($statuses[SI_Estimate::STATUS_REQUEST]); // Requests is a temp status.
					foreach ( $statuses as $status_key => $status_name ) {
						$current_status = ( $status_key == $status ) ? 'current_status' : '' ;
						$disabled = ( $status_key == $status ) ? 'disabled="true"' : '' ;
						switch ( $status_key ) {
							case SI_Estimate::STATUS_REQUEST:
								$title = self::__( 'Request' );
								break;
							case SI_Estimate::STATUS_PENDING:
								$title = self::__( 'Pending Approval' );
								break;
							case SI_Estimate::STATUS_APPROVED:
								$title = self::__( 'Approved' );
								break;
							case SI_Estimate::STATUS_DECLINED:
								$title = self::__( 'Declined' );
								break;
							
							default:
								$title = sprintf( self::__( 'Quickly mark as %s.' ), $status_name );
								break;
						}
						printf( self::__( '<button class="doc_status_change %s tooltip button %s" title="%s" href="%s" data-id="%s" data-status-change="%s" data-nonce="%s" %s><span>%s</span></button>' ), $status_key, $current_status, $title, get_edit_post_link( $id ), $id, $status_key, wp_create_nonce( SI_Estimates::NONCE ), $disabled, $status_name );
					} ?>
				
				<a href="#send_estimate" id="send_doc_quick_link" class="send tooltip button" title="<?php si_e('Send this estimate.') ?>"><span><?php si_e('') ?></span></a>
				
				<a href="<?php echo self::get_clone_post_url( $id ) ?>" id="duplicate_estimate_quick_link" class="duplicate tooltip button" title="<?php si_e('Duplicate this estimate') ?>"><span><?php si_e('') ?></span></a>

				<?php
					if ( current_user_can( 'delete_post', $id ) ) {
						echo "<a class='submitdelete tooltip button' title='" . si__( 'Delete this estimate permanently' ). "' href='" . get_delete_post_link( $id, '' ) . "'><span>".si__('')."</span></a>";
					} ?>
			</div>
		<?php endif ?>
	</div>


	<div id="edit-slug-box" class="clearfix">
		<b><?php si_e('Permalink') ?></b>
		<span id="permalink-select" tabindex="-1"><?php echo get_permalink( $id ) ?></span>
		<span id="view-post-btn"><a href="<?php echo get_permalink( $id ) ?>" class="button button-small"><?php si_e('View Estimate') ?></a></span>
		<?php if (  apply_filters( 'show_upgrade_messaging', '__return_true' ) ) {
			printf( si__('<span class="helptip" title="Upgrade for Private URLs"></span>'), si_get_purchase_link() );
		} ?>
	</div>


</div>