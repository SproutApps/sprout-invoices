<?php do_action( 'sprout_settings_header' ); ?>

<div id="si_dashboard" class="wrap about-wrap">

	<div id="si_settings" class="welcome_content clearfix">

		<?php do_action( 'sprout_settings_messages' ) ?>

		<h1 class="headline_callout"><?php _e( 'Welcome to Sprout Invoices', 'sprout-invoices' ) ?></h1>
		
		<p class="heading_desc"><?php _e( 'Thank you for downloading the free version of Sprout Invoices. For those new to Sprout Invoices below is a quick way to register your site, review how SI can help you get paid, and a checklist of items to complete your setup to make things easier.', 'sprout-invoices' ) ?></p>

		<?php if ( false === SI_Free_License::license_status() ) : ?>

		<div class="license-overview">

			<div class="activate_message clearfix">
				
				<div id="mascot_start">
					<img src="<?php echo SI_RESOURCES . 'admin/img/sprout/got-it.png' ?>" title="Hi! My name is Sprout."/>
				</div><!-- #mascot_start -->

				<div id="si_activate_wrap">
					
					<h3><?php _e( 'Register Your Site First', 'sprout-invoices' ) ?></h3>

					<div class="activation_inputs clearfix">
						<input type="text" name="<?php echo SI_Free_License::LICENSE_KEY_OPTION ?>" id="<?php echo SI_Free_License::LICENSE_KEY_OPTION ?>" value="<?php echo SI_Free_License::license_key() ?>" class="text-input fat-input <?php echo 'license_'.SI_Free_License::license_status() ?>" size="40" placeholder="<?php echo get_option( 'admin_email' ) ?>">
						
						<button id="activate_license" class="si_admin_button lg" @click="activateLicense('si_get_license')" :disabled='isSaving'><?php _e( 'Generate License', 'sprout-invoices' ) ?></button>

						<img
							v-if='isSaving == true'
							id='loading-indicator' src='<?php get_site_url() ?>/wp-admin/images/wpspin_light-2x.gif' alt='Loading indicator' />

						<span id="si_html_message"></span>

						<span class="input_desc help_block"><?php printf( 'This email will be securly sent to <a href="%s">sproutinvoices.com</a> and not be shared.', si_get_sa_link( 'https://sproutinvoices.com/account/' ) ) ?></span>
					</div>
				

					<p class="activation_msg clearfix">
						<?php printf( __( 'Generating a free license key is not required but takes seconds! Your email will <em>only</em> be used to create a unique Sprout Invoices license key that will allow for some future advanced features. Only <u>one</u> email will be sent from the founder to help you get started.', 'sprout-invoices' ), si_get_sa_link( 'https://sproutinvoices.com/support/terms/' ) ) ?></p>
				</div><!-- #activate_wrap -->
			</div>
		</div>

		<?php endif ?>

	</div>

	<div class="welcome_content">
		<div class="workflow-overview">

			<h2 class="subheadline_callout"><?php _e( 'Create and Send Your First Invoice', 'sprout-invoices' ) ?></h2>

			<p class="heading_desc"><?php printf( __( "Here's a quick video on how to create your first invoice. If you're interested we have a growing library of support videos <a href='%s'>here</a>.", 'sprout-invoices' ), 'https://www.youtube.com/channel/UCB9QjvGY_f_ayxtPNGhr7Xw/videos' ) ?></p>


			<iframe width="100%" height="500" src="https://www.youtube-nocookie.com/embed/_Y73TchithM?rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>

		</div>

	</div>

	<div class="welcome_content">

		<div class="progress_tracker">

			<div class="activate_message clearfix">
				
				<div id="mascot_progress">
					<img src="<?php echo SI_RESOURCES . 'admin/img/sprout/struggle-is-real.png' ?>" title="Hi! My name is Sprout."/>
				</div><!-- #mascot_start -->

				<h3><?php _e( 'Complete Your Setup', 'sprout-invoices' ) ?></h3>

				<p><?php _e( 'Finish setting things up and let Sprout Invoices do all the hard work!', 'sprout-invoices' ) ?></p>

				<p>	
					<?php $progress = SI_Settings_API::progress_track(); ?>
					<?php foreach ( $progress as $key => $progress_item ) :  ?>
						<?php
							$status = ( $progress_item['status'] ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>' ;
						if ( $progress_item['status'] ) {
							$complete[] = $key;
						} ?>
						<li class="si_tooltip" aria-label="<?php echo $progress_item['aria-label'] ?>">
							<?php echo $status ?>&nbsp;<a href="<?php echo $progress_item['link'] ?>"><?php echo $progress_item['label'] ?></a>
						</li>			
					<?php endforeach ?>
				</p>
			</div>

		</div>

	</div>

	<div class="welcome_content">
		

		<div class="workflow-overview">

			<h2 class="subheadline_callout"><?php _e( 'Contact Forms and Getting Paid', 'sprout-invoices' ) ?></h2>

			<div class="feature-section col three-col clearfix">
				<div class="col-1">
					<span class="flow_icon icon-handshake"></span>
					<h4><?php _e( 'Requests', 'sprout-invoices' ); ?></h4>
					<p><?php printf( __( "Receiving estimate requests on your site is simplified with Sprout Invoices and one of the <a href='%s'>major WordPress form builder integrations</a>, e.g. Gravity Forms, WPForms, Ninja Forms, and Formidable.", 'sprout-invoices' ), 'https://sproutinvoices.com/sprout-invoices/integrations/sprout-invoices-premium-form-integrations/' ); ?></p>

					<p><?php printf( __( "If you're not accepting estimate or invoice requests on your site skip over to creating your first invoice below.", 'sprout-invoices' ) ); ?></p>
					<p><?php printf( "<a href='https://sproutinvoices.com/sprout-invoices/integrations/sprout-invoices-premium-form-integrations/' target='_blank' class='si_admin_button si_muted'>%s</a>", __( 'Download Integrations', 'sprout-invoices' ) ); ?></p>
				</div>
				<div class="col-2">
					<span class="flow_icon icon-sproutapps-estimates"></span>
					<h4><?php _e( 'Estimating', 'sprout-invoices' ); ?></h4>
					<p><?php printf( __( "After a request from a form builder is submitted an <a href='%s'>estimate</a> is automatically created. To help complete the estimate so you can send it to your client a <a href='%s'>notification</a> is sent immediatly after this new estimate is created.", 'sprout-invoices' ), admin_url( 'post-new.php?post_type='.SI_Estimate::POST_TYPE ),  admin_url( 'admin.php?page=sprout-invoices-notifications' ) ); ?></p>
					<p><?php printf( "<a href='https://sproutinvoices.com/support/knowledgebase/sprout-invoices/estimates/' target='_blank' class='si_admin_button si_muted'>%s</a>", __( 'Documentation', 'sprout-invoices' ) ); ?></p>
				</div>
				<div class="col-3 last-feature">
					<span class="flow_icon icon-sproutapps-invoices"></span>
					<h4><?php _e( 'Invoicing', 'sprout-invoices' ); ?></h4>
					<p><?php printf( __( "After any estimate is accepted an <a href='%s'>invoice</a> is automatically created and another notification is sent letting you know. You can finalize some details and send it to your client for a payment, including a depsoit payment.", 'sprout-invoices' ), admin_url( 'post-new.php?post_type='.SI_Invoice::POST_TYPE ) ); ?></p>
					<p><?php printf( "<a href='https://sproutinvoices.com/support/knowledgebase/sprout-invoices/invoices/' target='_blank' class='si_admin_button si_muted'>%s</a>", __( 'Documentation', 'sprout-invoices' ) ); ?></p>
				</div>
			</div>

		</div>
	</div>

	<div class="welcome_content">
		
		<h2 class="subheadline_callout"><?php _e( 'Some FAQs', 'sprout-invoices' ) ?></h2>

		<div class="feature-section col three-col clearfix">
			<div>
				<h4><?php _e( 'Where do I start?', 'sprout-invoices' ); ?></h4>
				<p>
					<ol>
						<li><?php printf( __( "While Sprout Invoices tried to set some good defaults you'll need to go to <a href='%s'>General Settings</a> and finish settings things up.", 'sprout-invoices' ), admin_url( 'admin.php?page=sprout-invoices-settings' ) ); ?></li>
						<li><?php printf( __( "Setup <a href='%s'>Payment Processor</a> so you can start collecting money! Don't forget to test since you don't want to let your client find out you've configured things incorrectly.", 'sprout-invoices' ), admin_url( 'admin.php?page=sprout-invoices-payments' ) ); ?></li>
						<li><?php printf( __( "There are a lot of <a href='%s'>notifications</a> sent throughout the entire client project process, make sure they have your personality and represent your brand well.", 'sprout-invoices' ), admin_url( 'admin.php?page=sprout-invoices-notifications' ) ); ?></li>
						<li><?php printf( __( "Start <a href='%s'>importing</a> your data from other services (i.e. WP-Invoice, Freshbooks or Harvest).", 'sprout-invoices' ), admin_url( 'admin.php?page=sprout-invoices-import' ) ); ?></li>
						<li><?php _e( 'Grow your business while not forgetting about your loved ones...and the occasional round of golf.', 'sprout-invoices' ) ?></li>
					</ol>
					<p><?php printf( "<a href='https://sproutinvoices.com/support/knowledgebase/sprout-invoices/sprout-invoices-getting-started/' target='_blank' class='si_admin_button si_muted'>%s</a>", __( 'Documentation', 'sprout-invoices' ) ); ?></p>
				</p>
			</div>
			<div>

				<h4><?php _e( 'Clients &amp; WordPress Users?', 'sprout-invoices' ); ?></h4>
				<p><?php printf( __( "<a href='%s'>Clients</a> have WordPress users associated with them and clients are not limited to a single user either. This allows for you to have multiple points of contact for a company/client.", 'sprout-invoices' ), admin_url( 'edit.php?post_type=sa_client' ) ); ?></p>

				<p><?php printf( "<a href='https://sproutinvoices.com/support/knowledgebase/sprout-invoices/clients/' target='_blank' class='si_admin_button si_muted'>%s</a>", __( 'Documentation', 'sprout-invoices' ) ); ?></p>

				<h4><?php _e( 'What are Predefined Line Items?', 'sprout-invoices' ); ?></h4>
				<p><?php printf( __( "Predefined line-tems help with the creation of your estimates and invoices by pre-filling line items. Create some tasks that matter to your business before creating your first estimate or invoice and you'll see how they can save you a lot of time.", 'sprout-invoices' ) ); ?></p>

				<p><?php printf( "<a href='https://sproutinvoices.com/support/knowledgebase/sprout-invoices/invoices/predefined-tasks-line-items/' target='_blank' class='si_admin_button si_muted'>%s</a>", __( 'Documentation', 'sprout-invoices' ) ); ?></p>

				<h4><?php _e( 'How well am I doing?', 'sprout-invoices' ); ?></h4>				
				<p><?php printf( __( "The <a href='%s'>Reports Dashboard</a> should key you on how well your're growing your business. There are reports for the estimates, invoices, payments and clients available for filtering and exporting.", 'sprout-invoices' ), admin_url( 'admin.php?page=sprout-invoices-reports' ) ); ?></p>

				<p><?php printf( "<a href='https://sproutinvoices.com/support/knowledgebase/sprout-invoices/reports/' target='_blank' class='si_admin_button si_muted'>%s</a>", __( 'Documentation', 'sprout-invoices' ) ); ?></p>
			</div>
			<div class="last-feature">
				<h4><?php _e( 'Can I import from X service or WP-Invoice?', 'sprout-invoices' ); ?></h4>
				<p><?php printf( __( 'Yes! WP-Invoice, Harvest and Freshbooks importers are now available.', 'sprout-invoices' ) ); ?></p>
				<p><?php printf( "<a href='https://sproutinvoices.com/support/knowledgebase/sprout-invoices/sprout-invoices-getting-started/importing/' target='_blank' class='si_admin_button si_muted'>%s</a>", __( 'Documentation', 'sprout-invoices' ) ); ?></p>

				<h4><?php _e( 'I need help! Where is the support?', 'sprout-invoices' ); ?></h4>
				<p><?php printf( __( "We want to make sure using Sprout Invoices is enjoyable and not a hassle. Sprout Invoices has some pretty awesome <a href='%s'>support</a> and a budding <a href='%s'>knowledgebase</a> that will help you get anything resolved.", 'sprout-invoices' ), 'https://sproutinvoices.com/support/', 'https://sproutinvoices.com/support/knowledgebase/' ); ?></p>

				<p><?php printf( "<a href='%s' target='_blank' class='si_admin_button si_muted'>%s</a>", si_get_sa_link( 'https://sproutinvoices.com/support/' ), __( 'Support', 'sprout-invoices' ) ); ?>&nbsp;<?php printf( "<a href='https://sproutinvoices.com/support/knowledgebase/sprout-invoices/' target='_blank' class='si_admin_button si_muted'>%s</a>", __( 'Documentation', 'sprout-invoices' ) ); ?></p>

			</div>
		</div>

	</div>

</div>
