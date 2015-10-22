<div class="clearfix">
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
					<span title="<?php esc_attr_e( 'Predefined line-items can be created to help with estimate creation by adding default descriptions. This is a premium feature that will be added with a pro version upgrade.', 'sprout-invoices' ) ?>" class="helptip add_item_help"></span>
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
</div>