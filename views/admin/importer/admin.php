<?php do_action( 'sprout_settings_header' ); ?>

<div id="si_importer_admin" class="si_settings">

	<div id="si_settings"  class="si_settings_tabs non_stick_subnav clearfix">
		
		<?php do_action( 'sprout_settings_messages' ) ?>

		<?php foreach ( $settings as $key => $section_settings ) :  ?>

			<?php if ( ! empty( $section_settings['settings'] ) ) :  ?>
				
				<section id="section_<?php echo $key ?>">
					<?php do_action( 'si_display_settings', $section_settings['settings'], true ) ?>
				</section>

			<?php endif ?>

		<?php endforeach ?>

	</div>
</div>
