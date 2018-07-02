<div class="si_settings si-settings-admin-header">
	<header class="si_header">
		<a href="#">
			<img class="header_sa_logo" src="<?php echo SI_RESOURCES . 'admin/icons/sproutapps-flat-small-white.png' ?>" />
			<h1 class="si_title"><b>Sprout</b> Invoices</h1>
		</a>
	</header>
	<nav id="si-header-nav" class="si-header-nav">
		<ul>
			<?php
				$ifactive = ( isset( $_GET['page'] ) && 'sprout-invoices' === $_GET['page']  ) ? 'nav-tab-active' : '' ;
					?>
			<a href="<?php echo admin_url( 'admin.php?page=sprout-invoices' )  ?>" class="nav-item <?php echo $ifactive ?>"><?php _e( 'Getting Started', 'sprout-invoices' ) ?></a>
			<?php foreach ( $sub_pages as $slug => $subpage ) : ?>
				<?php
					$ifactive = ( isset( $_GET['page'] ) && 'sprout-invoices-' . $slug === $_GET['page']  ) ? 'nav-tab-active' : '' ;
						?>
				<a href="<?php echo admin_url( 'admin.php?page=sprout-invoices-' . $slug );  ?>" class="nav-item <?php echo $ifactive ?>"><?php echo $subpage['menu_title'] ?></a>
			<?php endforeach ?>
			<a href="javascript:HS.beacon.open()" id="open_beacon" class="nav-item si_tooltip" aria-label="<?php _e( 'Click for some Help', 'sprout-invoices' ) ?>"><span class="si_icon icon-question"></span></a>
		</ul>
	</nav>

	<div id="si_progress_tracker_wrap">
		<?php do_action( 'sprout_settings_progress' ) ?>
	</div><!-- #si_progress_tracker_wrap -->
</div>

<script>!function(e,o,n){window.HSCW=o,window.HS=n,n.beacon=n.beacon||{};var t=n.beacon;t.userConfig={},t.readyQueue=[],t.config=function(e){this.userConfig=e},t.ready=function(e){this.readyQueue.push(e)},o.config={docs:{enabled:!0,baseUrl:"https://sproutapps.helpscoutdocs.com/"},contact:{enabled:!0,formId:"431cd2e5-75b4-11e8-8d65-0ee9bb0328ce"}};var r=e.getElementsByTagName("script")[0],c=e.createElement("script");c.type="text/javascript",c.async=!0,c.src="https://djtflbt20bdde.cloudfront.net/",r.parentNode.insertBefore(c,r)}(document,window.HSCW||{},window.HS||{});</script>
<script type="text/javascript">
	HS.beacon.config({
		icon: 'search',
		modal: true,
		color: '#6FABE2',
		poweredBy: false,
		topArticles: true,
	});
</script>
