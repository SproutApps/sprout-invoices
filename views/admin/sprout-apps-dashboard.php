<div id="si_dashboard" class="sprout_apps_dash wrap about-wrap">

	<img class="header_sa_logo" src="<?php echo SI_RESOURCES . 'admin/icons/sproutapps.png' ?>" />

	<h1><?php printf( self::__( 'Welcome to <a href="%s">Sprout Apps</a>!' ), self::PLUGIN_URL ); ?></h1>

	<div class="about-text"><?php self::_e('Our mission is to build a suite of apps to help small businesses and freelancers work more efficiently by reducing the tedious business tasks associated with client management...<em>seriously though</em>, I\'m trying to build something awesome that you will love. Thank you for your support.') ?></div>

	<div id="welcome-panel" class="welcome-panel clearfix">
		<div class="welcome-panel-content">
			<h2><?php self::_e('Sprout Apps News and Updates') ?></h2>
			<?php
				$maxitems = 0;
				include_once( ABSPATH . WPINC . '/feed.php' );
				$rss = fetch_feed( self::PLUGIN_URL.'/feed/' ); // FUTURE use feedburner
				if ( !is_wp_error( $rss ) ) :
					$maxitems = $rss->get_item_quantity( 3 );
					$rss_items = $rss->get_items( 0, $maxitems );
				endif;
			?>
			<div class="rss_widget clearfix">
				<?php if ( $maxitems == 0 ) : ?>
					<p><?php self::_e( 'Could not connect to SIserver for updates.' ); ?></p>
				<?php else : ?>
					<?php foreach ( $rss_items as $item ) :
						$excerpt = sa_get_truncate( strip_tags( $item->get_content() ), 30 );
						?>
						<div>
							<h4><a href="<?php echo esc_url( $item->get_permalink() ); ?>" title="<?php echo esc_html( $item->get_title() ); ?>"><?php echo esc_html( $item->get_title() ); ?></a></h4>
							<span class="rss_date"><?php echo esc_html( $item->get_date('j F Y') ); ?></span>
							<p><?php echo esc_html( $excerpt ); ?></p>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<a class="twitter-timeline" href="https://twitter.com/_sproutapps" data-widget-id="492426361349234688">Tweets by @_sproutapps</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</div>
	</div>

	<h2 class="headline_callout"><?php self::_e('Sprout Apps Marketplace') ?></h2>
	<!-- FUTURE make this entirely dynamic and add the ability to purchase from the backend. -->
	<div class="feature-section col three-col clearfix">
		<div class="sa_addon_wrap col-1">
			<div class="add_on_img_wrap">
				<img class="sa_addon_img" src="<?php echo SI_RESOURCES . 'admin/img/gravity-ninja.png' ?>" />
				<a class="purchase_button button button-primary button-large" href="<?php echo self::PLUGIN_URL.'/marketplace/advanced-form-integration-gravity-ninja-forms/' ?>"><?php self::_e('Free Add-on') ?></a>
			</div>
			<h4><?php self::_e('Advanced Form Integration with Gravity and Ninja Forms') ?></h4>
		</div>
		<div class="sa_addon_wrap col-2">
			<div class="add_on_img_wrap">
				<img class="sa_addon_img" src="<?php echo SI_RESOURCES . 'admin/img/stripe.png' ?>" />
				<a class="purchase_button button button-primary button-large" href="<?php echo self::PLUGIN_URL.'/marketplace/stripe-payments/' ?>"><?php self::_e('Purchase') ?></a>
			</div>
			<h4><?php self::_e('Stripe Payments') ?></h4>
		</div>
		<div class="sa_addon_wrap col-3 last-feature">
			<div class="add_on_img_wrap">
				<img class="sa_addon_img" src="<?php echo SI_RESOURCES . 'admin/img/time-calculator.png' ?>" />
				<a class="purchase_button button button-primary button-large" href="<?php echo self::PLUGIN_URL.'/marketplace/hourly-rate-calculations/' ?>"><?php self::_e('Purchase') ?></a>
			</div>
			<h4><?php self::_e('Hourly Rate Calculations') ?></h4>
		</div>
	</div>

</div>

