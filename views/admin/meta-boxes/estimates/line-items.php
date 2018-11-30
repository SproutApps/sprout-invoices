<div id="line_item_types_wrap">
	<div id="nestable" class="nestable dd">
		<ol id="line_item_list" class="items_list">
			<?php do_action( 'si_get_line_item_type_section', $id ) ?>
		</ol>
	</div>
</div>
<div id="line_items_footer" class="clearfix">
	<div class="mngt_wrap clearfix">
		<div id="add_line_item">
			
			<?php do_action( 'si_add_line_item' ) ?>

			<?php if ( apply_filters( 'show_upgrade_messaging', true ) ) : ?>
				<span title="<?php esc_attr_e( 'Predefined line items can be created to help with estimate creation by adding default descriptions. This is a premium feature that will be added with a pro version upgrade.', 'sprout-invoices' ) ?>" class="helptip add_item_help"></span>
			<?php endif ?>

			<?php do_action( 'si_post_add_line_item' ) ?>
			
		</div>

		<?php do_action( 'si_get_line_item_totals_section', $id ) ?>

		<div id="status_updates" class="sticky_save">
			<div id="publishing-action">
				<?php
				$post_type = $post->post_type;
				$post_type_object = get_post_type_object( $post_type );
				$can_publish = current_user_can( $post_type_object->cap->publish_posts );
				if ( 0 == $post->ID || $status == 'auto-draft' ) {
					if ( $can_publish ) : ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ) ?>" />
						<?php submit_button( __( 'Save' ), 'primary button-large', 'save', false, array( 'accesskey' => 'p' ) ); ?>
					<?php else : ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Submit for Review' ) ?>" />
						<?php submit_button( __( 'Submit for Review' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
					<?php endif;
				} else { ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ) ?>" />
						<input name="save" type="submit" class="button button-primary button-large" id="save" accesskey="p" value="<?php esc_attr_e( 'Save' ) ?>" />
				<?php
				} ?>
				<span class="spinner"></span>
			</div>			
		</div>
	</div>
</div>

<?php
	$num_posts = wp_count_posts( SI_Estimate::POST_TYPE );
	$num_posts->{'auto-draft'} = 0; // remove auto-drafts
	$total_posts = array_sum( (array) $num_posts );
if ( $total_posts >= 10 && apply_filters( 'show_upgrade_messaging', true ) ) {

	$class = 'upgrade_message';
	$message = sprintf( '<img class="header_sa_logo" src="%s" height="64" width="auto" style="float: left;margin-top: 0px;margin-right: 8px;z-index: auto;padding: 0 10px;"/><strong style="font-size: 1.3em;margin-bottom: 5px;display: block;">Congrats on your %s Estimate!</strong>Please consider supporting the future of Sprout Invoices by purchasing a <a href="%s">discounted pro license</a> and/or writing a <a href="%s">positive 5 &#9733; review</a>.<br/><small>Dan Cameron - Founder, Lead Developer, and Small Business Owner </small>', SI_RESOURCES . 'admin/img/sprout/yipee.png', self::number_ordinal_suffix( $total_posts ), si_get_purchase_link(), 'http://wordpress.org/support/view/plugin-reviews/sprout-invoices?filter=5' );
	;

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
} ?>
