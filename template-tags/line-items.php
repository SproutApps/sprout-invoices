<?php

/**
 * Get the line item columns
 * @param  string $type
 * @return array
 * @since 8.0
 */
function si_get_line_item_columns( $type = '', $item_data = array(), $position = 1.0, $prev_type = '', $has_children = false ) {
	return SI_Line_Items::line_item_columns( $type, $item_data, $position, $prev_type, $has_children );
}

function si_line_item_get_children( $position = 1.0, $items = array() ) {
	$children = array();
	if ( ! si_line_item_is_child( $position ) ) {
		foreach ( $items as $key => $data ) {
			if ( (float) $key !== (float) $position ) {
				if ( (int) floor( $key ) === (int) $position ) {
					$children[ $key ] = $data;
				}
			}
		}
	}
	return apply_filters( 'si_line_item_get_children', $children, $position, $items );
}

function si_line_item_is_parent( $position = 1.0, $items = array() ) {
	if ( si_line_item_is_child( $position ) ) {
		return false;
	}
	foreach ( $items as $key => $data ) {
		if ( (float) $key !== (float) $position ) {
			if ( (int) floor( $key ) === (int) $position ) {
				return true;
			}
		}
	}
	return false;
}

function si_line_item_is_child( $position = 1.0 ) {
	if ( ceil( $position ) > floor( $position ) ) {
		return true;
	}
	return false;
}


function si_get_first_line_item( $doc_id = 0 ) {
	if ( ! $doc_id ) {
		$doc_id = get_the_id();
	}
	$doc = si_get_doc_object( $doc_id );
	$line_items = $doc->get_line_items();
	if ( empty( $line_items ) || ! isset( $line_items[1] ) ) {
		return array();
	}
	return $line_items[1];
}

function si_line_item_header_front_end() {
	_deprecated_function( __FUNCTION__, '8.0', 'si_get_front_end_line_item()' );
	$first_line_item = si_get_first_line_item();
	$type = ( isset( $first_line_item['type'] ) ) ? $first_line_item['type'] : '' ;
	$columns = si_get_line_item_columns( $type );
	ob_start(); ?>
	<?php foreach ( $columns as $column_slug => $column ) : ?>
			
		<?php if ( 'hidden' !== $column['type'] ) : ?>
			
			<div class="column column_<?php echo esc_attr( $column_slug ) ?>">
				<?php echo $column['label'] ?>
			</div>
			<!-- <?php echo esc_attr( $column_slug ) ?> -->

		<?php endif ?>

	<?php endforeach ?>
	<?php
	$view = ob_get_contents();
	ob_end_clean();
	return $view;
}

function si_line_item_build( $position = 0, $line_items = array(), $children = array() ) {
	_deprecated_function( __FUNCTION__, '8.0', 'si_get_front_end_line_item()' );
	$first_key_position = (int) current( array_keys( $line_items ) );
	if ( is_array( $position ) ) {
		$position = (float) $position['key'];
	}
	$item_data = $line_items[ $position ];
	$prev_type = '';
	if ( strpos( $position, '.' ) !== false ) { // child items don't get the header
		$prev_type = $item_data['type'];
	} elseif ( (int) $position === $first_key_position ) { // Don't add the header for the first line item since it was already added
		$prev_type = $item_data['type'];
	} elseif ( $first_key_position < (int) $position ) { // check to see what the previous line item was
		$prev_pos = (int) $position - 1;
		$prev_type = $line_items[ $prev_pos ]['type'];
	}
	$has_children = ( ! empty( $children ) ) ? true : false ;
	si_front_end_line_item( $item_data, $position, $prev_type, $has_children );
}

function si_get_front_end_line_item( $item_data = array(), $position = 0, $prev_type = '', $has_children = false ) {
	$type = ( isset( $item_data['type'] ) && '' !== $item_data['type'] ) ? $item_data['type'] : SI_Line_Items::get_default_type();
	$columns = si_get_line_item_columns( $type, $item_data, $position, $prev_type, $has_children );
	ob_start(); ?>
	<div class="line_item_option_wrap line_item_type_<?php echo esc_attr( $type ) ?>" data-type="<?php echo esc_attr( $type ) ?>">

		<?php if ( $type !== $prev_type ) : ?>

			<div class="line_items_header">
			
				<?php do_action( 'si_get_front_end_line_item_header', $type, $item_data, $position, $prev_type, $has_children ) ?>
				
				<div class="line_item">

					<?php foreach ( $columns as $column_slug => $column ) : ?>
						
						<?php if ( 'hidden' === $column['type'] ) : ?>
							<?php continue; ?>
						<?php endif ?>

						<?php if ( $has_children && isset( $column['hide_if_parent'] ) && (bool) $column['hide_if_parent'] ) : ?>
							<?php continue; ?>
						<?php endif ?>

						<div class="column column_<?php echo esc_attr( $column_slug ) ?>">
							<?php echo $column['label'] ?>
						</div>
						<!-- <?php echo esc_attr( $column_slug ) ?> -->

					<?php endforeach ?>

				</div>

			</div>

		<?php endif ?>

		<?php do_action( 'si_get_front_end_line_item_pre_row', $item_data, $position, $prev_type, $has_children ) ?>

		<div class="line_item<?php if ( $has_children ) { echo ' has_children'; } ?>">

			<?php foreach ( $columns as $column_slug => $column ) {

				$value = ( isset( $item_data[ $column_slug ] ) ) ? $item_data[ $column_slug ] : '' ;

				$type = ( isset( $column['type'] ) ) ? $column['type'] : 0 ;

				if ( ! $type || 'hidden' === $type ) {
					continue;
				}

				if ( $has_children && isset( $column['hide_if_parent'] ) && (bool) $column['hide_if_parent'] ) {
					continue;
				} ?>

				<div class="column column_<?php echo esc_attr( $column_slug ) ?>">
					<?php echo apply_filters( 'si_format_front_end_line_item_value', $value, $column_slug, $item_data ) ?>
				</div>
				<!-- <?php echo esc_attr( $column_slug ) ?> -->
				
			<?php } // end foreach ?>

		</div>

		<?php do_action( 'si_get_front_end_line_item_post_row', $item_data, $position, $prev_type, $has_children ) ?>
	</div>

	<?php
	$view = ob_get_contents();
	ob_end_clean();
	return apply_filters( 'si_get_front_end_line_item', $view, $item_data, $position );
}

function si_front_end_line_item( $item_data = array(), $position = 0, $prev_type = '', $has_children = false ) {
	print si_get_front_end_line_item( $item_data, $position, $prev_type, $has_children );
}


function si_line_item_build_plain( $position = 1.0, $items = array(), $children = array(), $doc_id = 0 ) {
	_deprecated_function( __FUNCTION__, '8.0', 'si_plain_text_line_item()' );
	$first_key_position = (int) current( array_keys( $line_items ) );
	if ( is_array( $position ) ) {
		$position = (float) $position['key'];
	}
	$item_data = $line_items[ $position ];
	$prev_type = '';
	if ( strpos( $position, '.' ) !== false ) { // child items don't get the header
		$prev_type = $item_data['type'];
	} elseif ( (int) $position === $first_key_position ) { // Don't add the header for the first line item since it was already added
		$prev_type = $item_data['type'];
	} elseif ( $first_key_position < (int) $position ) { // check to see what the previous line item was
		$prev_pos = (int) $position - 1;
		$prev_type = $line_items[ $prev_pos ]['type'];
	}
	$has_children = ( ! empty( $children ) ) ? true : false ;
	return si_plain_text_line_item( $item_data, $position, $prev_type, $has_children );
}


function si_get_plain_text_line_item( $item_data = array(), $position = 0, $prev_type = '', $has_children = false ) {
	$type = ( isset( $item_data['type'] ) && '' !== $item_data['type'] ) ? $item_data['type'] : 'task' ;
	$columns = si_get_line_item_columns( $type, $item_data, $position, $prev_type, $has_children );

	$item = '';

	foreach ( $columns as $column_slug => $column ) {

		if ( 'hidden' !== $column['type'] ) {

			$value = ( isset( $item_data[ $column_slug ] ) ) ? $item_data[ $column_slug ] : false ;
			if ( ! $value ) {
				continue;
			}

			$label = '';
			if ( 'desc' !== $column_slug ) {
				$tab_child = '';
				if ( strpos( $position, '.' ) !== false ) {
					$tab_child = "\t";
				}
				$label = $tab_child . $column['label'] . '.....';
			} else {
				$value = str_replace( '</p><p>', "\n  ", $value ); // newline the paragraphs
				$value = str_replace( '<p>', '', $value ); // remove start
				$value = str_replace( '</p>', '', $value ); // remove end
				$value = $value . "\n";
			}

			$item .= sprintf( "%s%s\t", $label, $value );

		}
	}
	return apply_filters( 'si_get_plain_text_line_item', $item, $item_data, $position, $prev_type, $has_children );
}

function si_get_line_item_value( $doc_id, $position, $data_slug ) {
	if ( ! $doc_id ) {
		$doc_id = get_the_id();
	}
	$doc = si_get_doc_object( $doc_id );
	if ( '' === $doc ) {
		return '';
	}
	$line_items = $doc->get_line_items();
	if ( empty( $line_items ) ) {
		return '';
	}
	$value = '';
	foreach ( $line_items as $key => $data ) {
		if ( ! isset( $data[ $data_slug ] ) ) {
			continue;
		}
		if ( (float) $position === (float) $key ) {
			$value = $data[ $data_slug ];
		}
	}

	return $value;
}


function si_front_end_line_item_columns( $item_data = array(), $position = 0, $prev_type = '', $has_children = false ) {
	print si_get_front_end_line_item_columns( $item_data, $position, $prev_type, $has_children );
}

function si_get_front_end_line_item_columns( $item_data = array(), $position = 0, $prev_type = '', $has_children = false ) {
	$item_type = ( isset( $item_data['type'] ) && '' !== $item_data['type'] ) ? $item_data['type'] : SI_Line_Items::get_default_type();
	$columns = si_get_line_item_columns( $item_type, $item_data, $position, $prev_type, $has_children );

	ob_start(); ?>

		<?php do_action( 'si_get_front_end_line_item_column_pre_row', $item_data, $position, $prev_type, $has_children ) ?>

		<?php foreach ( $columns as $column_slug => $column ) {

			$value = ( isset( $item_data[ $column_slug ] ) ) ? $item_data[ $column_slug ] : '' ;

			$column_type = ( isset( $column['type'] ) ) ? $column['type'] : 0 ;

			if ( ! $column_type || 'hidden' === $column_type ) {
				continue;
			}

			if ( $has_children && isset( $column['hide_if_parent'] ) && (bool) $column['hide_if_parent'] ) {
				continue;
			} ?>

			<div class="column column_<?php echo esc_attr( $column_slug ) ?>">

				<?php if ( $prev_type !== $item_type || apply_filters( 'si_show_all_line_item_headers', true ) ) :  ?>
					<?php if ( 'textarea' === $column_type ) :  ?>
						<h3><?php printf( __( '%2$s <small>%1$s</small>', 'sprout-invoices' ), number_format( (float) $position, 1, '.', '' ), $column['label'] ) ?></h3>
					<?php else : ?>
						<h3><?php echo $column['label'] ?></h3>	
					<?php endif ?>
				<?php endif ?>
				

				<div class="content">
					<?php echo apply_filters( 'si_format_front_end_line_item_value', $value, $column_slug, $item_data ) ?>
				</div>
			</div><!-- <?php echo esc_attr( $column_slug ) ?> -->
			
		<?php } // end foreach ?>

	<?php do_action( 'si_get_front_end_line_item_column_post_row', $item_data, $position, $prev_type, $has_children ) ?>

	<?php
	$view = ob_get_contents();
	ob_end_clean();
	return apply_filters( 'si_get_front_end_line_item_column', $view, $item_data, $position );
}
