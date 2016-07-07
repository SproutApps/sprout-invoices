<?php
/*/
	$num_posts = wp_count_posts( SI_Invoice::POST_TYPE );
	$num_posts->{'auto-draft'} = 0; // remove auto-drafts
	$total_posts = array_sum( (array) $num_posts );
if ( $total_posts >= 10 && apply_filters( 'show_upgrade_messaging', true ) ) {
	printf( '<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span><strong>Congrats on your %s Invoice!</strong> Please consider supporting the future of Sprout Invoices by: <a href="%s">upgrading</a> or writing a &#9733;&#9733;&#9733;&#9733;&#9733; <a href="%s">review</a>.</p></div>', self::number_ordinal_suffix( $total_posts ), si_get_purchase_link(), 'http://wordpress.org/support/view/plugin-reviews/sprout-invoices?filter=5' );
}
/**/?>

<div id="subject_header" class="clearfix">
	<div id="subject_header_actions" class="clearfix">
		<div id="subject_input_wrap">
			<?php $title = ( $status != 'auto-draft' && get_the_title( $id ) != __( 'Auto Draft' ) ) ? get_the_title( $id ) : '' ; ?>
			<input type="text" name="subject" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php _e( 'Subject...', 'sprout-invoices' ) ?>">
		</div>

		<?php if ( $statuses ) : ?>
			<div id="quick_links">
				
				<?php do_action( 'si_invoice_status_update', $id ) ?>

				<a href="#send_invoice" id="send_doc_quick_link" class="send si_tooltip button" title="<?php _e( 'Send this invoice', 'sprout-invoices' ) ?>"><span>&nbsp;</span></a>
				
				<a href="<?php echo self::get_clone_post_url( $id ) ?>" id="duplicate_invoice_quick_link" class="duplicate si_tooltip button" title="<?php _e( 'Duplicate this invoice', 'sprout-invoices' ) ?>"><span>&nbsp;</span></a>

			</div>
		<?php endif ?>
	</div>


	<div id="edit-slug-box" class="clearfix">
		<b><?php _e( 'Permalink', 'sprout-invoices' ) ?></b>
		<span id="permalink-select" tabindex="-1"><?php echo get_permalink( $id ) ?></span>
		<span id="view-post-btn"><a href="<?php echo get_permalink( $id ) ?>" class="button button-small"><?php _e( 'View Invoice', 'sprout-invoices' ) ?></a></span>
		<?php if (  apply_filters( 'show_upgrade_messaging', true ) ) {
			printf( __( '<span class="helptip" title="Upgrade for Private URLs"></span>', 'sprout-invoices' ), si_get_purchase_link() );
} ?>
	</div>


</div>
