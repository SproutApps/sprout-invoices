<?php do_action( 'sprout_settings_header' ); ?>

<div id="si_payment_processors_admin" class="si_settings">

	<div id="si_settings"  class="si_settings_tabs non_stick_subnav">

		<?php do_action( 'sprout_settings_messages' ) ?>

		<div id="payment_processors_admin">

			<div id="si_settings_subnav">
				
				<a href='#payments_start' v-on:click="makeTabActive('start')" v-bind:class="{ active : isActiveTab('start') == true }"><span class="si_icon icon-golf"></span> <?php _e( 'Getting Started', 'sprout-invoices' ) ?></a>
				
				<hr/>

				<?php foreach ( $credit as $class_name => $label ) :  ?>
					<a href='#pp_tab<?php echo esc_attr( $class_name ) ?>' v-on:click="makeTabActive('tab<?php echo esc_attr( $class_name ) ?>')" v-bind:class="{ active : isActiveTab('tab<?php echo esc_attr( $class_name ) ?>') == true }" class="si_smaller_nav">
						<?php printf( __( '<span class="si_icon icon-register"></span> %s', 'sprout-invoices' ), $label ) ?><div v-if="vm.<?php echo esc_attr( $class_name ) ?> == true" class="si_status_wrap"><span class="si_status_icon dashicons dashicons-yes"></div>
					</a>

				<?php endforeach ?>

				<hr/>

				<?php foreach ( $offsite as $class_name => $label ) :  ?>
					<a href='#pp_tab<?php echo esc_attr( $class_name ) ?>' v-on:click="makeTabActive('tab<?php echo esc_attr( $class_name ) ?>')" v-bind:class="{ active : isActiveTab('tab<?php echo esc_attr( $class_name ) ?>') == true }" class="si_smaller_nav">
						<?php printf( __( '<span class="si_icon icon-vault"></span> %s', 'sprout-invoices' ), $label ) ?><div v-if="vm.<?php echo esc_attr( $class_name ) ?> == true" class="si_status_wrap"><span class="si_status_icon dashicons dashicons-yes"></div><div v-else class="si_status_wrap"><span class="si_status_icon dashicons dashicons-no"></span></div>
					</a>

				<?php endforeach ?>
				
			</div>
			<main id="main" role="main">

				<div class="si_settings_tabs">

					<div id="start" class="row" v-show="isActiveTab('start')">

						<section id="section_start">

							<h1><?php _e( 'Select On-site Credit Card Processor', 'sprout-invoices' ) ?></h1>

							<p><?php _e( 'While only one onsite credit card processor <span class="si_icon icon-register"></span> can be enabled all of the other payment processors <span class="si_icon icon-vault"></span> can be enabled.', 'sprout-invoices' ) ?></p>

							<div class="si_enable_cc_wrap">
								<label for="si_cc_pp_select" class="si_input_label"><?php _e( 'Credit Card Processor Selection', 'sprout-invoices' ) ?></label>
								<select type="select" name="si_cc_pp_select" id="si_cc_pp_select"  v-model.lazy="vm.si_cc_pp_select" v-on:change="activateCCPP">
									<option value="false" <?php selected( false, $active_cc ) ?>><?php _e( '&mdash;Disabled&mdash;', 'sprout-invoices' ) ?></option>
									<?php foreach ( $credit as $class_name => $option_label ) : ?>
										<option value="<?php echo esc_attr( $class_name ); ?>" <?php selected( $class_name, $active_cc ) ?>><?php echo esc_html( $option_label ); ?></option>
									<?php endforeach; ?>
									<?php foreach ( $all_credit as $class_name => $option_label ) : ?>
										<?php if ( ! array_key_exists( $class_name, $credit ) ) :  ?>
											<option value="<?php echo esc_attr( $class_name ); ?>" disabled="disabled"><?php echo esc_html( $option_label ); ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
								<p><?php _e( 'This is where you will select which credit card processor you want enabled on your site.', 'sprout-invoices' ) ?></p>
							</div>
						</section>

						<?php foreach ( $settings as $key => $section_settings ) :  ?>

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
							</section>
						<?php endforeach ?>


						<div class="si-controls">
							<button
								@click='saveOptions'
								:disabled='isSaving'
								id='si-submit-settings' class="si_admin_button lg"><?php _e( 'Save', 'sprout-invoices' ) ?></button>
								<img
								v-if='isSaving == true'
								id='loading-indicator' src='<?php echo get_site_url() ?>/wp-admin/images/wpspin_light-2x.gif' alt='Loading indicator' />
						</div>

						<p class="si_setting_message" v-if='message'>{{ message }}</p>
					</div>

					<?php
						$all_processors = array_merge( $offsite, $credit );
							?>

					<?php foreach ( $all_processors as $class_name => $label ) :  ?>

						<div id="tab<?php echo esc_attr( $class_name ) ?>" class="row" v-show="isActiveTab('tab<?php echo esc_attr( $class_name ) ?>')">

							<?php if ( method_exists( $class_name, 'register_settings' ) ) :  ?>
							
								<?php
									$pp_settings = call_user_func( array( $class_name, 'register_settings' ) );
									$processor_settings = reset( $pp_settings ); ?>
								
								<?php if ( in_array( $class_name, array_keys( $credit ) ) ) :  ?>
									<?php include 'cc-settings.php'; ?>
								<?php else : ?>
									<?php include 'settings.php'; ?>
								<?php endif ?>

								<?php unset( $processor_settings ) ?>

							<?php else : ?>
								<?php if ( in_array( $class_name, array_keys( $credit ) ) ) : ?>
									<?php include 'cc-no-settings.php'; ?>
								<?php else : ?>
									<?php include 'no-settings.php'; ?>
								<?php endif ?>
							<?php endif ?>

							<div class="si-controls">
								<button
									@click='saveOptions'
									:disabled='isSaving'
									id='si-submit-settings' class="si_admin_button lg"><?php _e( 'Save', 'sprout-invoices' ) ?></button>
									<img
									v-if='isSaving == true'
									id='loading-indicator' src='<?php echo get_site_url() ?>/wp-admin/images/wpspin_light-2x.gif' alt='Loading indicator' />
							</div>

							<p class="si_setting_message" v-if='message'>{{ message }}</p>
						</div>
						
					<?php endforeach ?>


				</div>
			</main>
		</div>

	</div>
</div>
