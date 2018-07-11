<section id="section_<?php echo $class_name ?>">
	<h1><?php echo $label ?></h1>

	<div class="si_enable_pp_wrap">
		<label for="<?php echo esc_attr( $class_name ) ?>">

			<input type="checkbox" name='<?php echo esc_attr( $class_name ) ?>' id="<?php echo esc_attr( $class_name ) ?>" class="si_pp_checkbox" v-model.lazy="vm.<?php echo esc_attr( $class_name ) ?>" v-on:change="activateCCPP" />
			
			<span v-if="vm.<?php echo esc_attr( $class_name ) ?> == true"><span class="dashicons dashicons-yes"></span>&nbsp;<?php printf( '%s Active', $label ) ?></span><span v-else><span class="dashicons dashicons-no"></span>&nbsp;<?php printf( '%s Disabled', $label ) ?></span>
		</label>
	</div>
</section>
