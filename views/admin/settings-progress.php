<?php
$complete = array();
foreach ( $progress as $key => $progress_item ) {
	if ( $progress_item['status'] ) {
		$complete[] = $key;
	}
}
$percentage_complete = count( $complete ) / count( $progress ) * 100;
$percentage_complete = round( $percentage_complete );
//$percentage_complete = 100;
?>	

<?php if ( defined( 'DOING_AJAX' ) && DOING_AJAX && $percentage_complete >= 100 ) :  ?>
	<img src="<?php echo SI_RESOURCES . 'admin/img/sprout/yipee.png' ?>" id="happy_sprout" title="You did it!" width="120" height="auto"/>
<?php else : ?>
	<div id="si_progress_track" class="si_progress_track <?php if ( $percentage_complete >= 100 ) { echo 'progress_completed'; } ?>">
		<nav>
			<span class="progress_header"><?php _e( 'Your Progress', 'sprout-invoices' ) ?></span>
			<ol>
				<?php foreach ( $progress as $key => $progress_item ) :  ?>
					<?php
						$status = ( $progress_item['status'] ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>' ; ?>
					<li class="si_tooltip" aria-label="<?php echo $progress_item['aria-label'] ?>">
						<?php echo $status ?>&nbsp;<a href="<?php echo $progress_item['link'] ?>"><?php echo $progress_item['label'] ?></a>
					</li>			
				<?php endforeach ?>

				<?php if ( $percentage_complete >= 100 ) :  ?>
					<li><img src="<?php echo SI_RESOURCES . 'admin/img/sprout/yipee.png' ?>" id="happy_sprout" title="You did it!" width="100" height="auto"/></li>
				<?php endif ?>	
			</ol>
			<?php if ( $percentage_complete >= 100 ) :  ?>
				<span class="progress_footer si_tooltip" aria-label="<?php _e( 'You did it!', 'sprout-invoices' ) ?>"><?php _e( 'Awesome &mdash; 100% Complete!', 'sprout-invoices' ) ?></span>
			<?php else : ?>
				<span class="progress_footer si_tooltip" aria-label="<?php _e( 'You\'ve got this!', 'sprout-invoices' ) ?>"><span class="si_icon icon-hour-glass"></span>&nbsp;<?php printf( __( '%s%% Complete', 'sprout-invoices' ), $percentage_complete ) ?></span>
			<?php endif ?>
		</nav>
	</div>

<?php endif ?>
