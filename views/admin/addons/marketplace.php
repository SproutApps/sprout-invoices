<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php 
	$page = ( !isset( $_GET['tab'] ) ) ? $page : self::TEXT_DOMAIN.'/'.$_GET['tab'] ; ?>
<div id="<?php echo esc_attr( $page ); ?>" class="wrap">
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php if ( apply_filters( 'show_upgrade_messaging', TRUE ) ): ?>
			<h3><?php si_e('Addons from the Sprout Invoices Marketplace') ?></h3>
		<?php else: ?>
			<div class="clearfix">
				<ul class="subsubsub">
					<li class="manage"><a href="<?php echo esc_url( remove_query_arg( 'marketplace' ) ) ?>" <?php if ( !isset( $_GET['marketplace'] ) ) echo 'class="current"' ?>><?php self::_e('Manage Bundled Addons') ?></a> |</li>
					<li class="marketplace"><a href="<?php echo esc_url( add_query_arg( 'marketplace', 'view' ) ) ?>" <?php if ( isset( $_GET['marketplace'] ) ) echo 'class="current"' ?>><?php self::_e('Marketplace') ?></a></li>
				</ul>
			</div>
		<?php endif ?>
		<?php printf( '<div class="upgrade_message clearfix"><p><strong>DISCOUNTS NOT SHOWN:</strong> Sprout Invoices <a href="%s">license holders</a> receive up to 50&percnt; off the price of any marketplace add-on.</p></div>', si_get_purchase_link() ); ?>
		<?php do_action( 'si_settings_page_sub_heading_'.$page ); ?>
	</div>

	<div id="marketplace_view">
		<main id="main" class="container site-main" role="main">
			<div class="row">
				<div class="products_grid">
					<?php foreach ( $addons as $addon_id => $addon ): ?>
						<article class="type-download <?php if ( $addon->bundled ) echo 'bundled' ?>">
							<div class="section">
								<div class="pic">
									<?php if ( $addon->bundled ): ?>
										<span class="bundled_addon"><?php si_e('Bundled Free w/ License') ?></span>
									<?php endif ?>
									<a href="<?php echo si_get_sa_link( $addon->url, 'add-ons' ) ?>">
										<?php echo esc_html( $addon->thumb ); ?>
									</a>
									<div class="download_purchase_link">
										<a href="<?php echo si_get_sa_link( $addon->purchase_url, 'add-ons' ) ?>" class="button"><span class="edd-add-to-cart-label"><?php echo esc_html( $addon->price ); ?>&nbsp;–&nbsp;<?php si_e('Add to Cart') ?></span></a>
									</div>
								</div>
								<div class="info">
									<strong><?php echo esc_html( $addon->post_title ); ?></strong>							
									<div class="product-info">
										<?php echo esc_html( $addon->excerpt ); ?>
									</div>
									<a class="view-details" href="<?php echo si_get_sa_link( $addon->url, 'add-ons' ) ?>"><?php si_e('View Details') ?></a>
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
