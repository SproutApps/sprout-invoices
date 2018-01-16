<div class="line_item_option_wrap line_item_type_<?php echo esc_attr( $item_data['type'] ) ?>" data-type="<?php echo esc_attr( $item_data['type'] ) ?>">
	<div class="line_items_header dd-handle">
		<div class="line_items_header_inner_wrap clearfix">
			<span class="dashicons dashicons-randomize"></span>
			<div class="line_item">
				<?php foreach ( $columns as $column_slug => $column ) : ?>
					<?php if ( 'hidden' !== $column['type'] ) : ?>
						<div class="column column_<?php echo esc_attr( $column_slug ) ?>">
							<?php echo $column['label'] ?>
						</div>
						<!-- <?php echo esc_attr( $column_slug ) ?> -->
					<?php endif ?>
				<?php endforeach ?>
			</div>
		</div>
	</div>
	<div class="line_item_option_row">
		<div class="item_action_column">
			<div class="item_action item_clone"></div>
			<div class="item_action item_delete"></div>
			<?php do_action( 'si_line_item_build_option_action_row', $item_data, $items, $position, $children ) ?>
		</div><!-- / item_action_column -->
		<div class="line_item<?php if ( $has_children ) { echo ' has_children'; } ?>">
			<?php
			foreach ( $columns as $column_slug => $column ) {
				// stuff
				$placeholder = ( isset( $column['placeholder'] ) ) ? $column['placeholder'] : '' ;
				$val = ( isset( $column['value'] ) ) ? $column['value'] : '' ;
				$value = ( isset( $item_data[ $column_slug ] ) ) ? $item_data[ $column_slug ] : $val ;
				$hide_if_parent = ( isset( $column['hide_if_parent'] ) && $column['hide_if_parent'] ) ? 'parent_hide' : '' ;

				$numeric = ( isset( $column['numeric'] ) ) ? $column['numeric'] : true ;
				$force_numeric = ' input_value_is_numeric'; // add a space at the beginning.
				if ( ! $numeric ) {
					$force_numeric = '';
				}


				// start the view
				$option = '';
				$wrap = true;
				switch ( $column['type'] ) {
					case 'textarea':
						$option .= sprintf( '<textarea name="line_item_%2$s[]" class="sa_option_textarea" rows="4">%1$s</textarea>', wp_kses( $value, wp_kses_allowed_html( 'post' ) ), $column_slug );
						break;
					case 'small-input':
						$option .= '<span></span>';
						$option .= sprintf( '<input class="totalled_input sa_option_text%4$s" type="text" name="line_item_%2$s[]" value="%1$s" placeholder="%3$s" size="3">', esc_attr( $value ), $column_slug, $placeholder, $force_numeric );
						break;
					case 'input':
						$option .= '<span></span>';
						$option .= sprintf( '<input class="totalled_input sa_option_text%4$s" type="text" name="line_item_%2$s[]" value="%1$s" placeholder="%3$s" size="6">', esc_attr( $value ), $column_slug, $placeholder, $force_numeric );
						break;
					case 'hidden':
						$wrap = false;
						if ( '' === $value ) {
							$value = $placeholder;
						}
						$option .= sprintf( '<input class="sa_option_hidden" type="hidden" name="line_item_%2$s[]" value="%1$s">', esc_attr( $value ), $column_slug );
						break;
					case 'checkbox':

						$checked = checked( $value, $val, false );
						if ( isset( $column['check_by_default'] ) && ! isset( $item_data[ $column_slug ] ) ) {
							$checked = ( $column['check_by_default'] ) ? checked( true, true, false ) : checked( false, true, false );
						}
						$option .= sprintf( '<input class="sa_option_checkbox" type="hidden" name="line_item_%2$s[]" value="%1$s">', $value, $column_slug );

						$option .= sprintf( '<input class="%2$s_decoy_checkbox sa_option_checkbox" type="checkbox" name="line_item_%2$s_decoy_checkbox" value="%1$s" %3$s>', $val, $column_slug, $checked );
						break;
					case 'money':
					case 'total':
						$option .= sa_get_formatted_money( $value, 0, '<span class="money_amount">%s</span>' );
						$option .= sprintf( '<input class="totalled_input sa_option_hidden" type="hidden" name="line_item_%2$s[]" value="%1$s">', esc_attr( $value ), $column_slug );
						break;

					default:
						$option .= sprintf( '<!-- no option built for column_%s -->', $column_slug );
						break;
				}

				$option = apply_filters( 'si_line_item_option', $option, $column_slug, $item_data );

				if ( $wrap ) {
					printf( '<div class="column %3$s column_%2$s">%1$s</div><!-- / column_%2$s -->', $option, $column_slug, $hide_if_parent, $column['label'] );
				} else {
					print $option;
				}
			} ?>

			<?php
				printf( '<input class="line_item_type sa_option_hidden" type="hidden" name="line_item_type[]" value="%1$s">', $item_data['type'] ); ?>
			<?php
				printf( '<input class="line_item_index sa_option_hidden" type="hidden" name="line_item_key[]" value="%1$s">', $position ); ?>

		</div>
	</div>
</div>
