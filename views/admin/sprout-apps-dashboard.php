<div id="si_dashboard" class="sprout_apps_dash wrap about-wrap">

	<img class="header_sa_logo" src="<?php echo SI_RESOURCES . 'admin/icons/sproutapps.png' ?>" />

	<h1><?php printf( __( 'Welcome to <a href="%s">Sprout Apps</a>!', 'sprout-invoices' ), self::PLUGIN_URL ); ?></h1>

	<div class="about-text"><?php _e( 'Our mission is to build a suite of apps to help small businesses and freelancers work more efficiently by reducing the tedious business tasks associated with client management...<em>seriously though</em>, I\'m trying to build something awesome that you will love. Thank you for your support.', 'sprout-invoices' ) ?></div>

	<div id="welcome-panel" class="welcome-panel clearfix">
		<div class="welcome-panel-content">
			<h2><?php _e( 'Sprout Apps News and Updates', 'sprout-invoices' ) ?></h2>
			<?php
				$maxitems = 0;
				include_once( ABSPATH . WPINC . '/feed.php' );
				$rss = fetch_feed( 'https://sproutapps.co/feed/' ); // FUTURE use feedburner
			if ( ! is_wp_error( $rss ) ) :
				$maxitems = $rss->get_item_quantity( 3 );
				$rss_items = $rss->get_items( 0, $maxitems );
				endif;
			?>
			<div class="rss_widget clearfix">
				<?php if ( $maxitems == 0 ) : ?>
					<p><?php _e( 'Could not connect to SIserver for updates.', 'sprout-invoices' ); ?></p>
				<?php else : ?>
					<?php foreach ( $rss_items as $item ) :
						$excerpt = sa_get_truncate( strip_tags( $item->get_content() ), 30 );
						?>
						<div>
							<h4><a href="<?php echo esc_url( $item->get_permalink() ); ?>" title="<?php echo esc_html( $item->get_title() ); ?>"><?php echo esc_html( $item->get_title() ); ?></a></h4>
							<span class="rss_date"><?php echo esc_html( $item->get_date( 'j F Y' ) ); ?></span>
							<p><?php echo esc_html( $excerpt ); ?></p>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<a class="twitter-timeline" href="https://twitter.com/_sproutapps" data-widget-id="492426361349234688">Tweets by @_sproutapps</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</div>
	</div>

	<h2 class="headline_callout"><?php _e( 'Sprout Apps Marketplace', 'sprout-invoices' ) ?></h2>
			<a href="<?php echo si_get_sa_link( 'https://sproutapps.co/marketplace/', 'add-ons' ) ?>" class="button"><span class="edd-add-to-cart-label"><?php _e( 'View More Add-ons', 'sprout-invoices' ) ?></span></a>
	<!-- FUTURE make this entirely dynamic and add the ability to purchase from the backend. -->
	<div id="marketplace_view">
		<main id="main" class="container site-main" role="main">
			<div class="row">
				<div class="products_grid">
					<?php
						$all_addons = (array) SA_Addons::get_marketplace_addons();
						$addons = array_slice( $all_addons, 0, 6 ); ?>
					<?php foreach ( $addons as $addon_id => $addon ) : ?>
						<article class="type-download bundled <?php if ( $addon->biz_bundled ) { echo 'biz'; } ?>">
							<div class="section">
								<div class="pic">

									<?php if ( $addon->id === 44588 ) : ?>
										<span class="bundled_addon"><?php _e( 'Exclusive w/ Corporate Only', 'sprout-invoices' ) ?></span>
									<?php elseif ( $addon->biz_bundled ) : ?>
										<span class="bundled_addon"><?php _e( 'Exclusive w/ Business and Corp', 'sprout-invoices' ) ?></span>
									<?php elseif ( $addon->pro_bundled ) : ?>
										<span class="bundled_addon"><?php _e( 'Bundled Free w/ All Pro Licenses', 'sprout-invoices' ) ?></span>
									<?php endif ?>
									<a href="<?php echo si_get_sa_link( $addon->url, 'add-ons' ) ?>">
										<?php echo $addon->thumb; ?>
									</a>
									<div class="download_purchase_link">
										<a href="<?php echo si_get_sa_link( $addon->url, 'add-ons' ) ?>" class="button"><span class="edd-add-to-cart-label"><?php _e( 'View Details', 'sprout-invoices' ) ?></span></a>
									</div>
								</div>
								<div class="info">
									<strong><?php echo wp_kses( $addon->post_title, wp_kses_allowed_html( 'post' ) ); ?></strong>							
									<div class="product-info">
										<?php echo wp_kses( $addon->excerpt, wp_kses_allowed_html( 'post' ) ); ?>
									</div>
								</div>
							</div>
						</article>
					<?php endforeach ?>
				</div>
			<a href="<?php echo si_get_sa_link( 'https://sproutapps.co/marketplace/', 'add-ons' ) ?>" class="button"><span class="edd-add-to-cart-label"><?php _e( 'View More Add-ons', 'sprout-invoices' ) ?></span></a>
			</div>
		</main>
	</div>

</div>

