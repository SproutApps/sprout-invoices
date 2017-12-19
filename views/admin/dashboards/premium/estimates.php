<div class="reports_widget inside">
	<div class="main">
		<?php
			$args = array(
				'orderby' => 'modified',
				'post_type' => SI_Estimate::POST_TYPE,
				'post_status' => 'any', // Not Written-off?
				'posts_per_page' => 3,
				'fields' => 'ids',
				);
			$estimates = new WP_Query( $args ); ?>

		<?php if ( ! empty( $estimates->posts ) ) :  ?>
			<b><?php _e( 'Latest Updates', 'sprout-invoices' ) ?></b> 
			<ul>
				<?php foreach ( $estimates->posts as $estimate_id ) :  ?>
					<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php echo date( get_option( 'date_format' ), get_post_modified_time( 'U', false, $estimate_id ) ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php else : ?>
			<p>
				<b><?php _e( 'Latest Updates', 'sprout-invoices' ) ?></b><br/>
				<?php _e( 'No recent estimates found.', 'sprout-invoices' ) ?>
			</p>
		<?php endif ?>

		<?php
			$args = array(
				'post_type' => SI_Estimate::POST_TYPE,
				'post_status' => array( SI_Estimate::STATUS_REQUEST ),
				'posts_per_page' => 3,
				'fields' => 'ids',
				);
			$estimates = new WP_Query( $args ); ?>

		<?php if ( ! empty( $estimates->posts ) ) :  ?>
			<b><?php _e( 'Recent Requests', 'sprout-invoices' ) ?></b> 
			<ul>
				<?php foreach ( $estimates->posts as $estimate_id ) :  ?>
					<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php echo date( get_option( 'date_format' ), get_post_time( 'U', false, $estimate_id ) ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php else : ?>
			<p>
				<b><?php _e( 'Recent Requests', 'sprout-invoices' ) ?></b><br/>
				<?php _e( 'No recently requested estimates.', 'sprout-invoices' ) ?>
			</p>
		<?php endif ?>

		<?php
			$args = array(
				'orderby' => 'modified',
				'post_type' => SI_Estimate::POST_TYPE,
				'post_status' => array( SI_Estimate::STATUS_DECLINED ),
				'posts_per_page' => 3,
				'fields' => 'ids',
				);
			$estimates = new WP_Query( $args ); ?>

		<?php if ( ! empty( $estimates->posts ) ) :  ?>
			<b><?php _e( 'Recent Declined', 'sprout-invoices' ) ?></b> 
			<ul>
				<?php foreach ( $estimates->posts as $estimate_id ) :  ?>
					<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php echo get_the_title( $estimate_id ) ?></a> &mdash; <?php echo date( get_option( 'date_format' ), get_post_time( 'U', false, $estimate_id ) ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php else : ?>
			<p>
				<b><?php _e( 'Recent Declined', 'sprout-invoices' ) ?></b><br/>
				<?php _e( 'No recently declined estimates.', 'sprout-invoices' ) ?>
			</p>
		<?php endif ?>

		<?php
			$args = array(
				'post_type' => SI_Estimate::POST_TYPE,
				'post_status' => array( SI_Estimate::STATUS_PENDING ),
				'posts_per_page' => 3,
				'fields' => 'ids',
				'meta_query' => array(
						array(
							'meta_key' => '_expiration_date',
							'value' => array( 0, current_time( 'timestamp' ) ),
							'compare' => 'BETWEEN',
							),
					),
				);
			$estimates = new WP_Query( $args ); ?>

		<?php if ( ! empty( $estimates->posts ) ) :  ?>
			<b><?php _e( 'Expired &amp; Pending', 'sprout-invoices' ) ?></b> 
			<ul>
				<?php foreach ( $estimates->posts as $estimate_id ) :  ?>
					<li><a href="<?php echo get_edit_post_link( $estimate_id ) ?>"><?php
						$expired_or_pending = ( si_get_estimate_expiration_date( $estimate_id ) > current_time( 'timestamp' ) ) ? __( 'Expired', 'sprout-invoices' ) : __( 'Pending', 'sprout-invoices' );
						echo get_the_title( $estimate_id ) ?></a> &mdash; <?php printf( '<b>%s<b/>: %s', $expired_or_pending, date_i18n( get_option( 'date_format' ), si_get_estimate_expiration_date( $estimate_id ) ) ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php else : ?>
			<p>
				<b><?php _e( 'Expired &amp; Pending', 'sprout-invoices' ) ?></b><br/>
				<?php _e( 'No recently expired or pending estimates.', 'sprout-invoices' ) ?>
			</p>
		<?php endif ?>
	</div>
</div>
