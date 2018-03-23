<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php
	$page = ( ! isset( $_GET['tab'] ) ) ? $page : self::APP_DOMAIN.'/'.$_GET['tab'] ; ?>
<div id="<?php echo esc_attr( $page ); ?>" class="wrap">
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php if ( apply_filters( 'show_upgrade_messaging', true ) ) : ?>
			<h3><?php _e( 'Addons for Sprout Invoices', 'sprout-invoices' ) ?></h3>
		<?php else : ?>
			<div class="clearfix">
				<ul class="subsubsub">
					<li class="manage"><a href="<?php echo esc_url( remove_query_arg( 'marketplace' ) ) ?>" <?php if ( ! isset( $_GET['marketplace'] ) ) { echo 'class="current"'; } ?>><?php _e( 'Manage Bundled Addons', 'sprout-invoices' ) ?></a> |</li>
					<li class="marketplace"><a href="<?php echo esc_url( add_query_arg( 'marketplace', 'view' ) ) ?>" <?php if ( isset( $_GET['marketplace'] ) ) { echo 'class="current"'; } ?>><?php _e( 'Other Add-ons', 'sprout-invoices' ) ?></a></li>
				</ul>
			</div>
		<?php endif ?>
		<?php printf( '<div class="upgrade_message clearfix"><p><strong>Exclusive Add-ons:</strong> Each <a href="%1$s">pro version of Sprout Invoices</a> bundles a lot of free add-ons. For a complete breakdown checkout <a href="%1$s">the pricing page</a>.</p></div>', si_get_purchase_link() ); ?>
		<?php do_action( 'si_settings_page_sub_heading_'.$page ); ?>
	</div>

	<div id="marketplace_view">
		<main id="main" class="container site-main" role="main">
			<div class="row">
				<div class="products_grid">
					<?php foreach ( $addons as $addon_id => $addon ) : ?>
						<article class="type-download bundled <?php if ( $addon->biz_bundled ) { echo 'biz'; } ?>">
							<div class="section">
								<div class="pic">

									<?php if ( $addon->id === 44588 ) : ?>
										<span class="bundled_addon"><?php _e( 'Exclusive to Corporate License', 'sprout-invoices' ) ?></span>
									<?php elseif ( $addon->biz_bundled ) : ?>
										<span class="bundled_addon"><?php _e( 'Exclusive w/ Business and Corp', 'sprout-invoices' ) ?></span>
									<?php elseif ( $addon->pro_bundled ) : ?>
										<span class="bundled_addon"><?php _e( 'Bundled Free w/ a Pro License', 'sprout-invoices' ) ?></span>
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
			</div>
		</main>
	</div>
	<!-- #marketplace_view -->

	<?php do_action( 'si_settings_page', $page ) ?>
	<?php do_action( 'si_settings_page_'.$page, $page ) ?>
</div>
