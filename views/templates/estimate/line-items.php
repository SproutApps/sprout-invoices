<ol id="items">

	<?php do_action( 'si_document_line_items' ) ?>

	<?php foreach ( $line_items as $position => $item_data ) : ?>

		<?php if ( is_int( $position ) ) : // is not a child ?>

			<?php
				$children = si_line_item_get_children( $position, $line_items );
				$has_children = ( ! empty( $children ) ) ? true : false ; 
				$item_type = ( isset( $item_data['type'] ) ) ? $item_data['type'] : SI_Line_Items::DEFAULT_TYPE; ?>

			<li class="item item_type_<?php echo esc_attr( $item_type ) ?> <?php if ( $has_children ) { echo esc_attr( 'line_item_has_children' ); } ?>" data-id="<?php echo (float) $position ?>">

				<?php si_front_end_line_item( $item_data, $position, $prev_type, $has_children ) ?>

				<?php if ( $has_children ) : ?>

					<ol class="items_list child_items">

						<?php foreach ( $children as $child_position => $item_data ) : ?>

							<li class="item sub_item item_type_<?php echo esc_attr( $item_type ) ?>" data-id="<?php echo (float) $child_position ?>">
								<?php si_front_end_line_item( $line_items[ $child_position ], $child_position, $item_type, false ) ?>
							</li>

						<?php endforeach ?>

					</ol>

				<?php endif ?>

				<?php $prev_type = $item_type; ?>

			</li>

		<?php endif ?>

	<?php endforeach ?>

</ol>

<footer id="line_items_footer" class="clearfix">
	
	<?php do_action( 'si_document_line_items_footer' ) ?>


	<div id="line_items_totals">

		<?php foreach ( $totals as $slug => $items_total ) : ?>

			<?php if ( isset( $items_total['hide'] ) && $items_total['hide'] ) : ?>
				<?php continue; ?>
			<?php endif ?>


			<div id="line_<?php echo esc_attr( $slug ) ?>">
				
				<?php if ( isset( $items_total['helptip'] ) ) : ?>
					<b title="<?php echo esc_attr( $items_total['helptip'] ) ?>" class="helptip"><?php echo $items_total['label'] ?></b>
				<?php else : ?>
					<b><?php echo $items_total['label'] ?></b>
				<?php endif ?>

				<?php echo $items_total['formatted'] ?>

			</div>

		<?php endforeach ?>

	</div>

</footer>