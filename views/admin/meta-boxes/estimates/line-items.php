<div class="clearfix">
	<div id="line_items_header">
		<div class="clearfix">
			<div class="line_item">
				<div class="column column_desc">
					<?php si_e('Description') ?>
				</div>
				<div class="column column_rate">
					<span class="helptip rate_ico" title="<?php si_e('Rate') ?>"></span>
				</div>
				<div class="column column_qty">
					<span class="helptip qty_ico" title="<?php si_e('Quantity') ?>"></span>
				</div>
				<div class="column column_tax">
					<span class="helptip percentage_ico" title="<?php si_e('A percentage adjustment per line item, i.e. tax or discount') ?>"></span>
				</div>
				<div class="column column_total">
					<?php si_e('Amount') ?>
				</div>
			</div>

		</div>
	</div>
	<div id="nestable" class="dd">
		<ol class="items_list">

			<?php if ( !empty( $line_items ) ): ?>
				<?php foreach ( $line_items as $position => $data ): ?>
					<?php if ( is_int( $position ) ): // is not a child ?>
						<li class="item" data-id="<?php echo $position ?>">
							<?php
								// get the children of this top level item
								$children = si_line_item_get_children( $position, $line_items ); ?>

							<?php 
								// build single item
								echo si_line_item_build_option( $position, $line_items, $children ) ?>

							<?php if ( !empty( $children ) ): // if has children, loop and show  ?>
								<ol class="items_list">
									<?php foreach ( $children as $child_position ): ?>
										<li class="item" data-id="<?php echo $child_position ?>"><?php echo si_line_item_build_option( $child_position, $line_items ) ?></li>
									<?php endforeach ?>
								</ol>
							<?php endif ?>
						</li>
					<?php endif ?>
				<?php endforeach ?>
			<?php endif ?>

			<li id="line_item_default" class="item" <?php if ( !empty( $line_items ) ) echo 'style="display:none"'; ?> data-id="1">
				<div class="item_action_column">
					<div class="item_action dd-handle"></div>
					<!--<div class="item_action item_clone"></div>-->
					<div class="item_action item_delete"></div>
				</div>
				<div class="line_item">
					<div class="column column_desc">
						<textarea name="line_item_desc[]"></textarea>
					</div>
					<div class="column column_rate">
						<span></span>
						<input class="totalled_input" type="text" name="line_item_rate[]" value="" placeholder="1" size="3">
					</div>
					<div class="column column_qty">
						<span></span>
						<input class="totalled_input" type="text" name="line_item_qty[]" value="1" size="2">
					</div>
					<div class="column column_tax">
						<span></span>
						<input class="totalled_input" type="text" name="line_item_tax[]" value="" placeholder="" size="1" max="100">
					</div>
					<div class="column column_total">
						<?php if ( sa_currency_format_before() ): ?>
							<?php echo sa_get_currency_symbol() ?><span>0.00</span>
						<?php else: ?>
							<span>0.00</span><?php echo sa_get_currency_symbol() ?>
						<?php endif ?>
						<input class="totalled_input" type="hidden" name="line_item_total[]" value="">
					</div>
					<input class="line_item_index" type="hidden" name="line_item_key[]" value="0">
				</div>
			</li>
		</ol>
	</div>
	<div id="line_items_footer" class="clearfix">
		<div class="mngt_wrap clearfix">
			<div id="add_line_item">
				<?php if ( !empty( $item_types_options ) ): ?>
					<span class="add_button_wrap button">
						<a href="javascript:void(0)" class="add_button item_add_type item_add_no_type">&nbsp;<?php si_e('Add') ?></a><a href="javascript:void(0)" class="add_button add_button_drop" data-dropdown="#type_selection"></a>
					</span>
					<div id="type_selection" class="dropdown dropdown-tip dropdown-relative">
						<ul class="dropdown-menu">
							<?php foreach ( $item_types_options as $key => $label ): ?>
								<li><a class="item_add_type" href="javascript:void(0)" data-type-key="<?php self::esc_e( $key ) ?>"><?php self::esc_e( $label ) ?></a></li>
							<?php endforeach ?>
						</ul>
					</div>
				<?php else: ?>
					<span class="add_button_wrap button">
						<a href="javascript:void(0)" class="add_button item_add_type item_add_no_type">&nbsp;<?php si_e('Add') ?></a>
					</span>
					<span title="<?php self::esc_e('Tasks can be created to help with estimate creation by adding default descriptions.') ?>" class="helptip add_item_help"></span>
				<?php endif ?>
			</div>
			<div id="line_items_totals">
				<div id="line_subtotal">
					<b><?php si_e('Subtotal') ?></b>
					<?php if ( sa_currency_format_before() ): ?>
						<?php echo sa_get_currency_symbol() ?><span><?php echo number_format( floatval( $subtotal ), 2 ) ?></span>
					<?php else: ?>
						<span><?php echo number_format( floatval( $subtotal ), 2 ) ?></span><?php echo sa_get_currency_symbol() ?>
					<?php endif ?>
				</div>
				<div id="line_total">
					<b title="Total includes tax and discount." class="helptip"><?php si_e('Total') ?></b>
					<?php if ( sa_currency_format_before() ): ?>
						<?php echo sa_get_currency_symbol() ?><span><?php echo number_format( floatval( $total ), 2 ) ?></span>
					<?php else: ?>
						<span><?php echo number_format( floatval( $total ), 2 ) ?></span><?php echo sa_get_currency_symbol() ?>
					<?php endif ?>
				</div>
			</div>

			<div id="status_updates">
				<div id="publishing-action">
					<span class="spinner"></span>
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
				</div>			
			</div>
		</div>
	</div>

</div>
<div class="cloak">
	<?php foreach ( $item_types as $term ): ?>
		<span id="term_desc_<?php echo $term->term_id ?>"><?php echo $term->description ?></span>
	<?php endforeach ?>
</div>