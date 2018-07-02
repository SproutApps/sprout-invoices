<?php do_action( 'sprout_settings_header' ); ?>

<div id="si_general_settings_admin" class="si_settings">

	<div id="si_settings"  class="si_settings_tabs">

		<?php do_action( 'sprout_settings_messages' ) ?>

		<div id="general_settings_admin">

			<div id="si_settings_subnav">

				<?php foreach ( $tabs as $tab => $label ) :  ?>
					<a href='#<?php echo $tab ?>' v-on:click="makeTabActive('<?php echo $tab ?>')" class="si_tab_<?php echo $tab ?>" v-bind:class="{ active : isActiveTab('<?php echo $tab ?>') == true }"> <?php echo $label ?></a>
				<?php endforeach ?>
				
			</div>
			
			<main id="main" role="main">

				<div class="si_settings_tabs">

					<?php foreach ( $tabs as $tab => $label ) :  ?>
						
						<div id="<?php echo esc_attr( $tab ) ?>" class="row" v-show="isActiveTab('<?php echo esc_attr( $tab ) ?>')">

							<?php uasort( $allsettings, array( 'SI_Controller', 'sort_by_weight' ) ); ?>

							<?php foreach ( $allsettings as $key => $section_settings ) : ?>

								<?php
									// all settings for this tab
								if ( $tab !== $section_settings['tab'] ) :
									continue;
									endif; ?>

								<section id="section_<?php echo $key ?>">

									<?php if ( isset( $section_settings['title'] ) && '' !== $section_settings['title'] ) :  ?>
										
										<h1><?php echo $section_settings['title']  ?></h1>
										
									<?php endif ?>


									<?php if ( isset( $section_settings['description'] ) && '' !== $section_settings['description'] ) :  ?>
										
										<p><?php echo $section_settings['description']  ?></p>
										
									<?php endif ?>

									<?php if ( ! empty( $section_settings['settings'] ) ) :  ?>
										
										<?php do_action( 'si_display_settings', $section_settings['settings'], true ) ?>
										
									<?php endif ?>
								
								</section><!-- #section_php -->

							<?php endforeach ?>

						</div>

					<?php endforeach ?>

				</div>

			</main>

		</div>

		<div class="si-controls">
			<button
				@click='saveOptions'
				:disabled='isSaving'
				id='si-submit-settings' class="si_admin_button lg"><?php _e( 'Save', 'sprout-invoices' ) ?></button>
				<img
				v-if='isSaving == true'
				id='loading-indicator' src='<?php get_site_url() ?>/wp-admin/images/wpspin_light-2x.gif' alt='Loading indicator' />
		</div>
		
		<p v-if='message'>{{ message }}</p>
	</div>
</div>
