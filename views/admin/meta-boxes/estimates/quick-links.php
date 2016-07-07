<?php
	$current_status = '';
	$disabled = ''; ?>

<?php
/*/
	$num_posts = wp_count_posts( SI_Estimate::POST_TYPE );
	$num_posts->{'auto-draft'} = 0; // remove auto-drafts
	$total_posts = array_sum( (array) $num_posts );
if ( $total_posts >= 10 && apply_filters( 'show_upgrade_messaging', true ) ) {
	printf( '<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span><strong>Congrats on your %s Estimate!</strong> Please consider supporting the future of Sprout Invoices by: <a href="%s">upgrading</a> or writing a &#9733;&#9733;&#9733;&#9733;&#9733; <a href="%s">review</a>.</p></div>', self::number_ordinal_suffix( $total_posts ), si_get_purchase_link(), 'http://wordpress.org/support/view/plugin-reviews/sprout-invoices?filter=5' );
}
/**/
?>

<div id="subject_header" class="clearfix">
	<div id="subject_header_actions" class="clearfix">

		<div id="subject_input_wrap" class="clearfix">
			<?php $title = ( $status != 'auto-draft' && get_the_title( $id ) != __( 'Auto Draft' ) ) ? get_the_title( $id ) : '' ; ?>
			<input type="text" name="subject" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php _e( 'Subject...', 'sprout-invoices' ) ?>">
		</div>

		<?php if ( $statuses ) : ?>

			<div id="quick_links">

				<?php do_action( 'si_estimate_status_update', $id ) ?>

				<a href="#send_estimate" id="send_doc_quick_link" class="send si_tooltip button" title="<?php _e( 'Send this estimate.', 'sprout-invoices' ) ?>"><span>&nbsp;</span></a>
				
				<a href="<?php echo self::get_clone_post_url( $id ) ?>" id="duplicate_estimate_quick_link" class="duplicate si_tooltip button" title="<?php _e( 'Duplicate this estimate', 'sprout-invoices' ) ?>"><span>&nbsp;</span></a>

				<?php
				if ( current_user_can( 'delete_post', $id ) ) {
					echo "<a class='submitdelete si_tooltip button clock' title='" . __( 'Delete this estimate permanently', 'sprout-invoices' ). "' href='" . get_delete_post_link( $id, '' ) . "'><span>&nbsp;</span></a>";
				} ?>

			</div>
		<?php endif ?>
	</div>


	<div id="edit-slug-box" class="clearfix">
		<b><?php _e( 'Permalink', 'sprout-invoices' ) ?></b>
		<span id="permalink-select" tabindex="-1"><?php echo get_permalink( $id ) ?></span>
		<span id="view-post-btn"><a href="<?php echo get_permalink( $id ) ?>" class="button button-small"><?php _e( 'View Estimate', 'sprout-invoices' ) ?></a></span>
		<?php if (  apply_filters( 'show_upgrade_messaging', true ) ) {
			printf( __( '<span class="helptip" title="Upgrade for Private URLs"></span>', 'sprout-invoices' ), si_get_purchase_link() );
} ?>
	</div>


</div>
