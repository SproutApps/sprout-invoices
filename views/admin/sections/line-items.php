<?php if ( ! empty( $line_items ) ) : ?>
	<?php foreach ( $line_items as $position => $data ) : ?>
		<?php if ( is_int( $position ) ) : // is not a child ?>
			<?php
			if ( ! isset( $data['type'] ) ) {
				$data['type'] = SI_Line_Items::get_default_type();
			} ?>
			<li class="item item_type_<?php echo esc_attr( $data['type'] ) ?>" data-id="<?php echo (float) $position ?>">
				<?php
					// get the children of this top level item
					$children = si_line_item_get_children( $position, $line_items ); ?>

				<?php
					// build single item
					do_action( 'si_line_item_build_option', $position, $line_items, $children ) ?>

				<?php if ( ! empty( $children ) ) : // if has children, loop and show  ?>
					<ol class="items_list child_items_list">
						<?php foreach ( $children as $child_position => $child_data ) : ?>
							<li class="item child_item item_type_<?php echo esc_attr( $data['type'] ) ?>" data-id="<?php echo (float) $child_position ?>"><?php do_action( 'si_line_item_build_option', $child_position, $line_items ) ?></li>
						<?php endforeach ?>
					</ol>
				<?php endif ?>
			</li>
		<?php endif ?>
	<?php endforeach ?>
<?php endif ?>
