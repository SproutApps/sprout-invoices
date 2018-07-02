<article class="type_addon" v-bind:class="{ activated : vm.<?php echo $vm_key ?> == true }">
	<div class="section" v-bind:class="{ activating : isSaving == true }">
		<div class="img_wrap">
			<input type="checkbox" name='<?php echo $name  ?>' id="<?php echo $key ?>" class="si_addon_checkbox" v-model.lazy="vm.<?php echo $vm_key ?>" v-on:change="activateAddOn( '<?php echo $key ?>', $event )" /><label for="<?php echo $key ?>"><img src="<?php echo $img  ?>" /></label>
			<div class="addon_status">
				<span v-if="vm.<?php echo $vm_key ?> == true"><span class="dashicons dashicons-yes"></span><?php _e( 'Enabled', 'sprout-invoices' ) ?></span>
				<span v-else><span class="dashicons dashicons-no"></span><?php _e( 'Disabled', 'sprout-invoices' ) ?></span>
			</div>
		</div>
		<div class="info">
			<strong><?php echo wp_kses( $title, wp_kses_allowed_html( 'post' ) ); ?></strong>
		</div>						
		<div class="addon_description">
			<div class="addon_description">
				<?php echo wp_kses( $description, wp_kses_allowed_html( 'post' ) ); ?>
				<div class="addon_info_link">
					<a href="<?php echo si_get_sa_link( $url, 'add-ons' ) ?>" class="si-button" target="_blank"><?php _e( 'Learn More', 'sprout-invoices' ) ?></a>
				</div>
			</div>
		</div>
	</div>
</article>
