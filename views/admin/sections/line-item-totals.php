<div id="line_items_totals">

	<?php do_action( 'si_line_item_totals_section_start', $id ) ?>

	<?php if ( ! empty( $totals ) ) :  ?>
		<?php foreach ( $totals as $slug => $items_total ) : ?>

			<?php if ( isset( $items_total['admin_hide'] ) && $items_total['admin_hide'] ) : ?>
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
	<?php endif ?>

	<?php do_action( 'si_line_item_totals_section', $id ) ?>

</div>
