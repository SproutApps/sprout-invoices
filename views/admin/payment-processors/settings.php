<?php foreach ( $processor_settings as $key => $section_settings ) :  ?>

	<section id="section_<?php echo $key ?>">

		<?php if ( isset( $section_settings['title'] ) && '' !== $section_settings['title'] ) :  ?>
			
			<h1><?php echo $section_settings['title']  ?></h1>
			
		<?php endif ?>


		<?php if ( isset( $section_settings['description'] ) && '' !== $section_settings['description'] ) :  ?>
			
			<p><?php echo $section_settings['description']  ?></p>
			
		<?php endif ?>

		<div class="si_enable_pp_wrap">
			<label for="<?php echo esc_attr( $class_name ) ?>">

			<input type="checkbox" name='<?php echo esc_attr( $class_name ) ?>' id="<?php echo esc_attr( $class_name ) ?>" class="si_pp_checkbox" v-model.lazy="vm.<?php echo esc_attr( $class_name ) ?>" v-on:change="activatePP" />
			
			<span v-if="vm.<?php echo esc_attr( $class_name ) ?> == true"><span class="dashicons dashicons-yes"></span>&nbsp;<?php printf( '%s Active', $label ) ?></span><span v-else><span class="dashicons dashicons-no"></span>&nbsp;<?php printf( '%s Disabled', $label ) ?></span>
		</label>
		</div>

		<?php if ( ! empty( $section_settings['settings'] ) ) :  ?>
			
			<?php do_action( 'si_display_settings', $section_settings['settings'], true ) ?>

		<?php endif ?>
	</section>

<?php endforeach ?>
