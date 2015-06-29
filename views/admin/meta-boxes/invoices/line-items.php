<div class="clearfix">
	<div id="line_items_header">
		<div class="clearfix">
			<div class="line_item">
				<?php echo si_line_item_header_columns( 'invoices' ) ?>
			</div>
		</div>
	</div>
	<div id="nestable" class="dd">
		<ol id="line_item_list" class="items_list">

			<?php if ( !empty( $line_items ) ): ?>
				<?php foreach ( $line_items as $position => $data ): ?>
					<?php if ( is_int( $position ) ): // is not a child ?>
						<li class="item" data-id="<?php echo (float) $position ?>">
							<?php
								// get the children of this top level item
								$children = si_line_item_get_children( $position, $line_items ); ?>

							<?php 
								// build single item
								echo si_line_item_build_option( $position, $line_items, $children ) ?>

							<?php if ( !empty( $children ) ): // if has children, loop and show  ?>
								<ol class="items_list">
									<?php foreach ( $children as $child_position ): ?>
										<li class="item" data-id="<?php echo (float) $child_position ?>"><?php echo si_line_item_build_option( $child_position, $line_items ) ?></li>
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
					<?php echo apply_filters( 'si_add_line_item_add_button', '<a href="javascript:void(0)" class="add_line_item add_button item_add_type item_add_no_type button">&nbsp;'.si__('Add').'</a>' ) ?>
				</span>
				<?php if ( apply_filters( 'show_upgrade_messaging', true ) ): ?>
					<span title="<?php self::esc_e('Predefined line-items can be created to help with invoice creation by adding default descriptions. This is a premium feature that will be added with a pro version upgrade.') ?>" class="helptip add_item_help"></span>
					
					<span id="time_importing">
						<?php printf( '<button id="time_import_question_answer_upgrade" class="button disabled si_tooltip" title="%s">%s</button>', self::__('Any billable time can be imported from your projects into your invoices dynamically with a pro version upgrade.'), self::__('Import Time') ) ?>
					</span>
				<?php endif ?>
				
				<?php do_action('si_post_add_line_item') ?>
				<?php do_action('mb_item_types') // TODO deprecated ?>

				<div id="invoice_status_updates" class="sticky_save">
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

				<?php do_action('mb_invoice_save') ?>

			</div>
			<div id="line_items_totals">
				<div id="line_subtotal">
					<b><?php si_e('Subtotal') ?></b>
					<?php sa_formatted_money( $subtotal ) ?>
				</div>
				<div id="payments">
					<b title="Total of all payments" class="helptip"><?php si_e('Payments') ?></b>
					<?php sa_formatted_money( $total_payments ) ?>
				</div>
				<div id="line_total">
					<b title="Total includes tax and discount (minus payments)" class="helptip"><?php si_e('Total Due') ?></b>
					<?php sa_formatted_money( si_get_invoice_balance() ) ?>
				</div>
				<?php if ( apply_filters( 'show_upgrade_messaging', true, 'deposit-line-items' ) ): ?>
					<div id="deposit">
						<b title="Upgrade Sprout Invoices to enable deposits." class="helptip"><?php si_e('Deposit Due') ?></b>
						<input type="number" name="deposit" min="0" max="0" step="any" disabled="disabled">
					</div>
				<?php elseif ( floatval( $total - $total_payments ) > 0.00 || $status == 'auto-draft' || $status == 'temp' ): ?>
					<div id="deposit">
						<b title="Set the amount due for the next payment&mdash;the amount due will be used if blank" class="helptip"><?php si_e('Deposit Due') ?></b>
						<input type="number" name="deposit" value="<?php echo (float) $deposit ?>" min="0" max="<?php echo floatval( $total - $total_payments ) ?>"  step="any">
					</div>
				<?php endif ?>
			</div>
			
		</div>
	</div>

</div>
<?php if ( !empty( $item_types ) ): ?>
<div class="cloak">
	<!-- Used to insert descriptions from adding a pre-defined task -->
	<?php foreach ( $item_types as $term ): ?>
		<span id="term_desc_<?php echo (int) $term->term_id ?>"><?php echo esc_html( $term->description ) ?></span>
	<?php endforeach ?>
</div>
<?php endif ?>