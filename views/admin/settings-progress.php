<div id="si_progress_track">
	<nav>
		<span class="progress_header"><?php _e( 'Your Progress', 'sprout-invoices' ) ?></span>
		<ol>
			<?php $complete = array() ?>
			<?php foreach ( $progress as $key => $progress_item ) :  ?>
				<?php
					$status = ( $progress_item['status'] ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>' ;
				if ( $progress_item['status'] ) {
					$complete[] = $key;
				} ?>
				<li class="si_tooltip" aria-label="<?php echo $progress_item['aria-label'] ?>">
					<?php echo $status ?>&nbsp;<a href="<?php echo $progress_item['link'] ?>"><?php echo $progress_item['label'] ?></a>
				</li>			
			<?php endforeach ?>
		</ol>

		<?php
			$percentage_complete = count( $complete ) / count( $progress ) * 100;
			$percentage_complete = round( $percentage_complete );
				?>
		<?php if ( $percentage_complete >= 100 ) :  ?>
			<span class="progress_footer si_tooltip" aria-label="<?php _e( 'You did it!', 'sprout-invoices' ) ?>"><?php _e( 'Awesome &mdash; 100% Complete!', 'sprout-invoices' ) ?></span>
		<?php else : ?>
			<span class="progress_footer si_tooltip" aria-label="<?php _e( 'You\'ve got this!', 'sprout-invoices' ) ?>"><span class="si_icon icon-hour-glass"></span>&nbsp;<?php printf( __( '%s%% Complete', 'sprout-invoices' ), $percentage_complete ) ?></span>
		<?php endif ?>
	</nav>
</div>
