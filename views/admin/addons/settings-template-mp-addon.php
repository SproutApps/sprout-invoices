<article class="type_addon marketplace_addon">
	<div class="section">
		<div class="img_wrap">
			<?php if ( $mp_addon->corp_bundled ) : ?>
				<span class="bundled_addon corp"><?php _e( 'Exclusive to Corporate License', 'sprout-invoices' ) ?></span>
			<?php elseif ( $mp_addon->biz_bundled ) : ?>
				<span class="bundled_addon biz"><?php _e( 'Exclusive w/ Business and Corp', 'sprout-invoices' ) ?></span>
			<?php elseif ( $mp_addon->pro_bundled ) : ?>
				<span class="bundled_addon pro"><?php _e( 'Bundled Free w/ a Pro License', 'sprout-invoices' ) ?></span>
			<?php elseif ( $mp_addon->free_addon ) : ?>
				<span class="bundled_addon free"><?php _e( 'Free Download!', 'sprout-invoices' ) ?></span>
			<?php endif ?>
			<a href="<?php echo si_get_sa_link( $url, 'add-ons' ) ?>" class="si-button" target="_blank"><img src="<?php echo $img  ?>" /></a>
		</div>
		<div class="info">
			<strong><?php echo wp_kses( $title, wp_kses_allowed_html( 'post' ) ); ?></strong>							
			<div class="addon_description">
				<?php echo wp_kses( $description, wp_kses_allowed_html( 'post' ) ); ?>
				<div class="addon_info_link">
					<a href="<?php echo si_get_sa_link( $url, 'add-ons' ) ?>" class="si-button" target="_blank"><?php _e( 'Learn More', 'sprout-invoices' ) ?></a>
				</div>
			</div>
		</div>
	</div>
</article>
