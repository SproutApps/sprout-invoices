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
				<span title="<?php esc_attr_e( 'Predefined line items can be created to help with invoice creation by adding default descriptions. This is a premium feature that will be added with a pro version upgrade.', 'sprout-invoices' ) ?>" class="helptip add_item_help"></span>
				
				<span id="time_importing">
					<?php printf( '<button id="time_import_question_answer_upgrade" class="button disabled si_tooltip" title="%s">%s</button>', __( 'Any billable time can be imported from your projects into your invoices dynamically with a pro version upgrade.', 'sprout-invoices' ), __( 'Import Time', 'sprout-invoices' ) ) ?>
				</span>
			<?php endif ?>
			
			<?php do_action( 'si_post_add_line_item' ) ?>
			<?php do_action( 'mb_item_types' ) // TODO deprecated ?>

			<div id="invoice_status_updates" class="sticky_save">
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

			<?php do_action( 'mb_invoice_save' ) ?>

		</div>

		<?php do_action( 'si_get_line_item_totals_section', $id ) ?>
		
	</div>
</div>
<?php if ( ! empty( $item_types ) ) : ?>
	<div class="cloak">
		<!-- Used to insert descriptions from adding a pre-defined task -->
		<?php foreach ( $item_types as $term ) : ?>
			<span id="term_desc_<?php echo (int) $term->term_id ?>"><?php echo esc_html( $term->description ) ?></span>
		<?php endforeach ?>
	</div>
<?php endif ?>
