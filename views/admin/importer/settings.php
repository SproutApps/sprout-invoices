<div id="importer_admin">

	<div id="si_settings_subnav">
		
		<a href='#import_start' v-on:click="makeTabActive('start')" v-bind:class="{ active : isActiveTab('start') == true }"><span class="si_icon icon-golf"></span> <?php _e( 'Getting Started', 'sprout-invoices' ) ?></a>
		<hr/>

		<?php foreach ( $importers as $class => $label ) :  ?>

			<?php if ( method_exists( $class, 'get_id' ) ) :  ?>
			
				<?php
					$id = call_user_func( array( $class, 'get_id' ) ); ?>
			
				<a href='#import_<?php echo $id ?>' v-on:click="makeTabActive('<?php echo $id ?>')" v-bind:class="{ active : isActiveTab('<?php echo $id ?>') == true }"><?php printf( __( '<span class="dashicons dashicons-update"></span> %s', 'sprout-invoices' ), $label ) ?></a>

			<?php endif ?>

		<?php endforeach ?>
		
	</div>

	<main id="main" role="main">

		<div class="si_settings_tabs">

			<div id="start" class="row" v-show="isActiveTab('start')">

				<h1><?php _e( 'Choose an Import Method', 'sprout-invoices' ) ?></h1>

				<p><span class="dashicons dashicons-arrow-left-alt" style="    margin-top: 2px;"></span>&nbsp;&nbsp;<?php _e( 'Start by selecting an import method to the left.', 'sprout-invoices' ) ?></p>

			</div>


				<?php foreach ( $importers as $class => $label ) :  ?>
					
					<?php if ( method_exists( $class, 'get_id' ) ) :  ?>
						
						<?php
							$id = call_user_func( array( $class, 'get_id' ) );
							$settings = call_user_func( array( $class, 'get_options' ) ); ?>

						<div id="<?php echo $id ?>" class="row" v-show="isActiveTab('<?php echo $id ?>')">
							<?php foreach ( $settings as $key => $section_settings ) :  ?>
								<section id="section_<?php echo $key ?>">
									<?php if ( isset( $section_settings['title'] ) && '' !== $section_settings['title'] ) :  ?>
										
										<h1><?php echo $section_settings['title']  ?></h1>
										
									<?php endif ?>


									<?php if ( isset( $section_settings['description'] ) && '' !== $section_settings['description'] ) :  ?>
										
										<p><?php echo $section_settings['description']  ?></p>
										
									<?php endif ?>

									<?php if ( ! empty( $section_settings['settings'] ) ) :  ?>
										
										<form id="form_<?php echo $id ?>" action="" method="POST" accept-charset="utf-8" enctype="multipart/form-data">
												
												<?php do_action( 'si_display_settings', $section_settings['settings'] ) ?>

												<input type="hidden" name="importer" value="<?php echo esc_attr( $class ); ?>" />

												<button type="submit" form="form_<?php echo $id ?>" value="Submit" class="si_admin_button lg"><?php printf( __( 'Start %s Import', 'sprout-invoices' ), $label ) ?></button>

										</form>

									<?php endif ?>
								</section>
							<?php endforeach ?>

						</div>

					<?php endif ?>

				<?php endforeach ?>
			</fieldset>

		</div>
	</main>
</div>
