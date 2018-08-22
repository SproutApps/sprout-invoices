<?php do_action( 'sprout_settings_header' ); ?>

<div id="si_notifications_admin" class="si_settings">

	<div id="si_settings"  class="si_settings_tabs non_stick_subnav">

		<?php do_action( 'sprout_settings_messages' ) ?>

		<div id="importer_admin">

			<div id="si_settings_subnav">
				
				<a href='#import_start' v-on:click="makeTabActive('start')" v-bind:class="{ active : isActiveTab('start') == true }"><span class="si_icon icon-golf"></span> <?php _e( 'Getting Started', 'sprout-invoices' ) ?></a>
				<hr/>
				<?php $assigned = array(); ?>
				<?php foreach ( $notifications as $notification_key => $data ) :  ?>

					<?php
					if ( ! isset( $data['post_id'] ) || ! is_int( $data['post_id'] ) ) {
						continue;
					}
						$notification_id = $data['post_id'];
						$assigned[] = $notification_id;
						$name = SI_Notifications::$notifications[ $notification_key ]['name'];
						$notification = SI_Notification::get_instance( $notification_id );
						$status = ( $notification->get_disabled() ) ? '<span class="si_status_icon dashicons dashicons-no"></span>' : '<span class="si_status_icon dashicons dashicons-yes"></span>' ; ?>
						
						<a href='#notification_<?php echo $notification_key ?>' v-on:click="makeTabActive('<?php echo $notification_key ?>')" v-bind:class="{ active : isActiveTab('<?php echo $notification_key ?>') == true }" class="si_smaller_nav"><?php printf( __( '<span class="dashicons dashicons-email-alt"></span> %s %s', 'sprout-invoices' ), $name, $status ) ?></a>

				<?php endforeach ?>

				<hr/>

				<?php foreach ( $notification_posts as $notification_post_id ) :  ?>
					<?php
					if ( in_array( $notification_post_id, $assigned ) ) {
						continue;
					}
						$name = __( 'Archived & Unassigned', 'sprout-invoices' );
						$status = '<span class="si_status_icon dashicons dashicons-no"></span>' ; ?>
						
						<a href='#notification_<?php echo $notification_post_id ?>' v-on:click="makeTabActive('<?php echo $notification_post_id ?>')" v-bind:class="{ active : isActiveTab('<?php echo $notification_post_id ?>') == true }" class="si_smaller_nav"><?php printf( __( '<span class="dashicons dashicons-email-alt"></span> %s %s', 'sprout-invoices' ), $name, $status ) ?></a>
				<?php endforeach ?>
				
			</div>
				<main id="main" role="main">

					<div class="si_settings_tabs">

						<div id="start" class="row" v-show="isActiveTab('start')">

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
							<?php $shown = array(); ?>
							<?php foreach ( $notifications as $notification_key => $data ) :  ?>
								
								<?php
								if ( ! isset( $data['post_id'] ) || ! is_int( $data['post_id'] ) ) {
									continue;
								}
									$notification_id = $data['post_id'];
									$name = SI_Notifications::$notifications[ $notification_key ]['name'];
									$notification = SI_Notification::get_instance( $notification_id );
									$shown[] = $data['post_id'];
									?>

									<div id="<?php echo $notification_key ?>" class="row" v-show="isActiveTab('<?php echo $notification_key ?>')" style="display: none;">
										
										<section id="section_<?php echo $notification_key ?>">

											<div class="title_and_actions">
												<h1><?php echo esc_html( $name ) ?></h1>
												<a class="si_admin_button" href="<?php echo get_edit_post_link( $notification_id ) ?>"><?php _e( 'Edit Notification', 'sprout-invoices' ) ?></a>&nbsp;<a class="si_admin_button si_muted si_tooltip" href="<?php echo add_query_arg( array( 'refresh-notification' => $notification_id ) ) ?>" aria-label="<?php _e( 'This will reset the notification to the default template', 'sprout-invoices' ) ?>"><?php _e( 'Reset', 'sprout-invoices' ) ?></a>
											</div>
											
											<h2><?php echo esc_html( $notification->get_title() ) ?></h2>

											<div class="notification_content">
												
												<iframe src="<?php echo add_query_arg( array( 'show-notification' => $notification_id ) ) ?>"></iframe>
												
											</div>
										</section>
									</div>

							<?php endforeach ?>

							<?php foreach ( $notification_posts as $notification_post_id ) :  ?>
								
								<?php

								if ( in_array( $notification_post_id, $shown ) ) {
									continue;
								}

									$name = __( 'Archived & Unassigned', 'sprout-invoices' );
									$status = '<span class="si_status_icon dashicons dashicons-no"></span>' ;
									$notification = SI_Notification::get_instance( $notification_id );
									?>

									<div id="<?php echo $notification_post_id ?>" class="row" v-show="isActiveTab('<?php echo $notification_post_id ?>')" style="display: none;">
										
										<section id="section_<?php echo $notification_post_id ?>">

											<div class="title_and_actions">
												<h1><?php echo esc_html( $name ) ?></h1>
												<a class="si_admin_button" href="<?php echo get_edit_post_link( $notification_post_id ) ?>"><?php _e( 'Edit Notification', 'sprout-invoices' ) ?></a>&nbsp;<?php if ( current_user_can( 'delete_post', $notification_post_id ) ) { ?><a class="si_admin_button si_muted si_tooltip" aria-label="<?php _e( 'Delete this unnassigned notification', 'sprout-invoices' ) ?>" href="<?php echo get_delete_post_link( $notification_post_id, null, true ); ?>"><?php _e( 'Delete', 'sprout-invoices' ) ?></a><?php } ?>
											</div>
											
											<h2><?php echo esc_html( $notification->get_title() ) ?></h2>

											<div class="notification_content">
												
												<iframe src="<?php echo add_query_arg( array( 'show-notification' => $notification_post_id ) ) ?>"></iframe>
												
											</div>
										</section>
									</div>

							<?php endforeach ?>

					</div>
				</main>
		</div>

	</div>
</div>
