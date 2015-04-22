<div class="clearfix">
	<div id="line_items_header">
		<div class="clearfix">
			<div class="line_item">
				<?php echo si_line_item_header_columns( 'estimates' ) ?>
			</div>
		</div>
	</div>
	<div id="nestable" class="dd">
		<ol id="line_item_list" class="items_list">

			<?php if ( !empty( $line_items ) ): ?>
				<?php foreach ( $line_items as $position => $data ): ?>
					<?php if ( is_int( $position ) ): // is not a child ?>
						<li class="item" data-id="<?php echo esc_attr( $position ); ?>">
							<?php
								// get the children of this top level item
								$children = si_line_item_get_children( $position, $line_items ); ?>

							<?php 
								// build single item
								echo si_line_item_build_option( $position, $line_items, $children ) ?>

							<?php if ( !empty( $children ) ): // if has children, loop and show  ?>
								<ol class="items_list">
									<?php foreach ( $children as $child_position ): ?>
										<li class="item" data-id="<?php echo esc_attr( $child_position ); ?>"><?php echo si_line_item_build_option( $child_position, $line_items ) ?></li>
									<?php endforeach ?>
								</ol>
							<?php endif ?>
						</li>
					<?php endif ?>
				<?php endforeach ?>
			<?php endif ?>

			<li id="line_item_default" class="item" style="display:none" data-id="1">
				<?php 
					// build single item
					echo si_line_item_build_option( 0 ) ?>
			</li>
		</ol>
	</div>
	<div id="line_items_footer" class="clearfix">
		<div class="mngt_wrap clearfix">
			<div id="add_line_item">
				
				<?php do_action('si_add_line_item') ?>

				<span class="add_button_wrap">
					<?php echo apply_filters( 'si_add_line_item_add_button', '<a href="javascript:void(0)" class="add_line_item add_button item_add_type item_add_no_type">&nbsp;'.si__('Add').'</a>' ) ?>
				</span>
				<?php if ( apply_filters( 'show_upgrade_messaging', true ) ): ?>
					<span title="<?php self::esc_e('Predefined line-items can be created to help with estimate creation by adding default descriptions. This is a premium feature that will be added with a pro version upgrade.') ?>" class="helptip add_item_help"></span>
				<?php endif ?>
			</div>

			<div id="line_items_totals">
				<div id="line_subtotal">
					<b><?php si_e('Subtotal') ?></b>
					<?php sa_formatted_money( $subtotal ) ?>
				</div>
				<div id="line_total">
					<b title="<?php si_e('Total includes tax and discount.') ?>" class="helptip"><?php si_e('Total') ?></b>
					<?php sa_formatted_money( $total ) ?>
				</div>
			</div>

			<div id="status_updates" class="sticky_save">
				<div id="publishing-action">
					<?php
					$post_type = $post->post_type;
					$post_type_object = get_post_type_object($post_type);
					$can_publish = current_user_can($post_type_object->cap->publish_posts);
					if ( 0 == $post->ID || $status == 'auto-draft' ) {
						if ( $can_publish ) : ?>
							<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Publish') ?>" />
							<?php submit_button( __( 'Save' ), 'primary button-large', 'save', false, array( 'accesskey' => 'p' ) ); ?>
						<?php else : ?>
							<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Submit for Review') ?>" />
							<?php submit_button( __( 'Submit for Review' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
						<?php endif;
					} else { ?>
							<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
							<input name="save" type="submit" class="button button-primary button-large" id="save" accesskey="p" value="<?php esc_attr_e('Save') ?>" />
					<?php
					} ?>
					<span class="spinner"></span>
				</div>			
			</div>
		</div>
	</div>
</div>