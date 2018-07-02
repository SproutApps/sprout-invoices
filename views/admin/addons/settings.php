<?php do_action( 'sprout_settings_header' ); ?>

<div class="si_settings">

	<div id="si_settings" class="si-form si-form-aligned">

		<?php do_action( 'sprout_settings_messages' ) ?>

			<div id="addons_admin">

			<div id="si_settings_subnav">
				<a href='#manage_addons' v-on:click="makeTabActive('start')"  v-bind:class="{ active : isActiveTab('start') == true }"><span class="si_icon icon-abacus"></span><?php _e( 'Manage Bundled Add-ons', 'sprout-invoices' ) ?></a>
				<hr/>
				<a href='#marketplace_addons' v-on:click="makeTabActive('marketplace_addons')" v-bind:class="{ active : isActiveTab('marketplace_addons') == true }"><span class="si_icon icon-info"></span><?php _e( 'Available Add-ons', 'sprout-invoices' ) ?></a>
			</div>

			<div class="si_settings_tabs clearfix">

				<main id="main" class="container site-main" role="main">

					<div id="manage_addons" class="row" v-show="isActiveTab('start')">

						<section id="section_start">

							<h1><?php _e( 'Bundled Add-ons', 'sprout-invoices' ) ?></h1>
							<p><?php _e( 'Here are the add-ons you have available. Individually enable or disable them to make Sprout Invoices work best for you!', 'sprout-invoices' ) ?></p>

							<div class="addons_grid">
								<?php $mp_addons_shown = array(); ?>
								<?php foreach ( $addons as $path => $details ) :  ?>

									<?php
										$key = SA_Addons::get_addon_key( $path, $details );
										$vm_key = SI_Settings_API::_sanitize_input_for_vue( $key );
										$name = sprintf( '%1$s["%2$s"]', $option, $key );
										$mpaddon = false;

									if ( isset( $details['ID'] ) && (int) $details['ID'] ) {
										foreach ( $mp_addons as $aokey => $ao ) {
											if ( (int) $details['ID'] === (int) $ao->id ) {
												$mp_addons_shown[] = $aokey;
												$mpaddon = $ao;
											}
										}
									}

									if ( ! $mpaddon ) {
										$url = ( isset( $details['PluginURI'] ) && $details['PluginURI'] !== '' ) ? esc_url( $details['PluginURI'] ) :  false ;
										$title = str_replace( 'Sprout Invoices Add-on - ', '', $details['Name'] );
										$img = SI_URL . '/bundles/default.png';
										$description = sprintf( '<p>%s</p>', $details['Description'] );
									} else {
										$url = $mpaddon->url;
										$title = $mpaddon->post_title;
										$img = $mpaddon->thumb_url;
										$description = $mpaddon->excerpt;
									} ?>

									<?php include 'settings-template-addon.php'; ?>

								<?php endforeach ?>
							</div>
						</section>
					</div>

					<div id="marketplace_addons" class="row" v-show="isActiveTab('marketplace_addons')">

						<section id="section_<?php echo $key ?>">

							<h1><?php _e( 'More Add-ons', 'sprout-invoices' ) ?></h1>

							<?php if ( apply_filters( 'show_upgrade_messaging', true ) ) :  ?>
								<p><?php printf( 'Here are some add-ons currently available if you were to <a href="%s">upgrade</a> to a pro license.</p>', si_get_purchase_link() ) ?></p>
							<?php else : ?>
								<p><?php printf( 'Here are some add-ons currently available that you you don\'t have bundled, maybe you need to <a href="%s">upgrade</a> your license.</p>', si_get_purchase_link() ) ?></p>
							<?php endif ?>

							<div class="addons_grid">

								<?php foreach ( $mp_addons as $mp_addon_key => $mp_addon ) :

									if ( $mp_addon->pro_bundled && SA_Addons::is_pro_installed() ) {
										continue;
									}

									if ( $mp_addon->biz_bundled && SA_Addons::is_biz_installed() ) {
										continue;
									}
									if ( $mp_addon->corp_bundled && SA_Addons::is_corp_installed() ) {
										continue;
									} ?>

									<?php
										$url = $mp_addon->url;
										$title = $mp_addon->post_title;
										$img = $mp_addon->thumb_url;
										$description = $mp_addon->excerpt; ?>

									<?php include 'settings-template-mp-addon.php'; ?>

								<?php endforeach ?>

							</div>
						</section>
					</div>
				</main>
			</div>	
		</div>


	</div>
</div>
