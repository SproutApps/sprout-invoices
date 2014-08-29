<div id="si_dashboard" class="wrap about-wrap">

	<h1><?php printf( self::__( 'Thanks for using <a href="%s">Sprout Invoices</a>!' ), self::PLUGIN_URL, self::SI_VERSION ); ?></h1>

	<div class="about-text"><?php printf( self::__( 'The future of <a href="%s">Sprout Invoices</a> relies on happy customers supporting Sprout Apps by purchasing upgraded versions. If you like this free version of Sprout Invoices please consider <a href="%s">purchasing an upgrade</a>.' ), self::PLUGIN_URL, si_get_purchase_link() ); ?></div>


	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>

	<div class="welcome_content clearfix">
		<div class="license-overview">

			<h2 class="headline_callout"><?php self::_e('The Sprout Invoices Flow') ?></h2>

			<div class="feature-section col three-col clearfix">
				<div class="col-1">
					<span class="flow_icon icon-handshake"></span>
					<h4><?php self::_e( 'Lead Generation' ); ?></h4>
					<p><?php printf( self::__( "Receiving estimate requests on your site is simplified with Sprout Invoices. <a href='%s'>General Settings</a> has more information on how to add a form to your site as well as settings to integrate with an advanced form plugin, e.g. Gravity Forms or Ninja Forms." ), admin_url('admin.php?page=sprout-apps/settings') ); ?></p>
					<p><?php printf( "<a href='https://sproutapps.co/support/knowledgebase/sprout-invoices/advanced/customize-estimate-submission-form/' target='_blank' class='button'>%s</a>", self::__('Documentation') ); ?></p>
				</div>
				<div class="col-2">
					<span class="flow_icon icon-sproutapps-estimates"></span>
					<h4><?php self::_e( 'Estimating' ); ?></h4>
					<p><?php printf( self::__( "A new <a href='%s'>estimate</a> is automatically created and notifications are sent after every estimate request submission. The <a href='%s'>notification</a> to you will provide a link to this new estimate; allowing you to review, update, and send the estimate to your prospective client without having to communicate via email." ), admin_url('post-new.php?post_type='.SI_Estimate::POST_TYPE),  admin_url('admin.php?page=sprout-apps/notifications') ); ?></p>
					<p><?php printf( "<a href='https://sproutapps.co/support/knowledgebase/sprout-invoices/estimates/' target='_blank' class='button'>%s</a>", self::__('Documentation') ); ?></p>
				</div>
				<div class="col-3 last-feature">
					<span class="flow_icon icon-sproutapps-invoices"></span>
					<h4><?php self::_e( 'Invoicing' ); ?></h4>
					<p><?php printf( self::__( "An <a href='%s'>invoice</a> is automatically created from an accepted estimate. By default these newly created invoices are <em>not</em> sent to the client, instead you  will need to review them before sending. Your <a href='%s'>notifications</a> are meant to be setup to help review, mark, and send them out quickly." ), admin_url('post-new.php?post_type='.SI_Invoice::POST_TYPE),  admin_url('admin.php?page=sprout-apps/notifications') ); ?></p>
					<p><?php printf( "<a href='https://sproutapps.co/support/knowledgebase/sprout-invoices/invoices/' target='_blank' class='button'>%s</a>", self::__('Documentation') ); ?></p>
				</div>
			</div>

		</div>
	</div>

	<hr />

	<div class="welcome_content">
		<h3><?php self::_e( 'FAQs' ); ?></h3>

		<div class="feature-section col three-col clearfix">
			<div>
				<h4><?php self::_e( 'Where do I start?' ); ?></h4>
				<p>
					<ol>
						<li><?php printf( self::__( "Start <a href='%s'>importing</a> your data from other services (i.e. WP-Invoice, Freshbooks or Harvest)." ), admin_url('admin.php?page=sprout-apps/settings&tab=import') ); ?></li>
						<li><?php printf( self::__( "Even with the defaults set in <a href='%s'>General Settings</a> the 'Company Info' should be filled out first." ), admin_url('admin.php?page=sprout-apps/settings') ); ?></li>
						<li><?php printf( self::__( "Review your <a href='%s'>Payment Settings</a> and provide different methods of payments for your clients. Don't let your client find out you've configured the payment processor incorrectly&mdash;make sure to test." ), admin_url('admin.php?page=sprout-apps/settings&tab=payments') ); ?></li>
						<li><?php printf( self::__( "There are a lot of <a href='%s'>notifications</a> sent throughout the entire client acquisition process, make sure they have your personality and represent your brand well. You can send HTML emails, check your <a href='%s'>General Settings</a> for more information." ), admin_url('admin.php?page=sprout-apps/notifications'), admin_url('admin.php?page=sprout-apps/settings') ); ?></li>
						<li><?php self::_e('Grow your business while not forgetting about your loved ones...and the occasional round of golf.') ?></li>
					</ol>
					<p><?php printf( "<a href='https://sproutapps.co/support/knowledgebase/sprout-invoices/sprout-invoices-getting-started/' target='_blank' class='button'>%s</a>", self::__('Documentation') ); ?></p>
				</p>
			</div>
			<div>
				<h4><?php self::_e( 'How well am I doing?' ); ?></h4>				
				<p><?php printf( self::__( "The <a href='%s'>Reports Dashboard</a> should key you on how well your're growing your business. There are reports for the estimates, invoices, payments and clients available for filtering and exporting." ), admin_url('admin.php?page=sprout-apps/settings&tab=reporting') ); ?></p>

				<p><?php printf( "<a href='https://sproutapps.co/support/knowledgebase/sprout-invoices/reports/' target='_blank' class='button'>%s</a>", self::__('Documentation') ); ?></p>


				<h4><?php self::_e( 'Clients &amp; WordPress Users?' ); ?></h4>
				<p><?php printf( self::__( "<a href='%s'>Clients</a> have WordPress users associated with them and clients are not limited to a single user either. This allows for you to have multiple points of contact for a company/client." ), admin_url('edit.php?post_type=sa_client') ); ?></p>

				<p><?php printf( "<a href='https://sproutapps.co/support/knowledgebase/sprout-invoices/clients/' target='_blank' class='button'>%s</a>", self::__('Documentation') ); ?></p>

				<h4><?php self::_e( 'What are tasks?' ); ?></h4>
				<p><?php printf( self::__( "<a href='%s'>Tasks</a> help with the creation of your estimates and invoices by pre-filling line items. Create some tasks that matter to your business before creating your first estimate or invoice and you'll see how they can save you a lot of time." ), admin_url('edit-tags.php?taxonomy=si_line_item_types&post_type=sa_estimate') ); ?></p>

				<p><?php printf( "<a href='https://sproutapps.co/support/knowledgebase/sprout-invoices/invoices/predefined-tasks-line-items/' target='_blank' class='button'>%s</a>", self::__('Documentation') ); ?></p>
			</div>
			<div class="last-feature">
				<h4><?php self::_e( 'Can I import from X service or WP-Invoice?' ); ?></h4>
				<p><?php printf( self::__( 'Yes! WP-Invoice, Harvest and Freshbooks importers are now available.' ) ); ?></p>
				<p><?php printf( "<a href='https://sproutapps.co/support/knowledgebase/sprout-invoices/importing/' target='_blank' class='button'>%s</a>", self::__('Documentation') ); ?></p>

				<h4><?php self::_e( 'I need help! Where is the support?' ); ?></h4>
				<p><?php printf( self::__( "We want to make sure using Sprout Invoices is enjoyable and not a hassle. Sprout Apps has some pretty awesome <a href='%s'>support</a> and a budding <a href='%s'>knowledgebase</a> (for paid members) that will help you get anything resolved. Support and documentation is limited for this free version." ), self::PLUGIN_URL.'/support/', self::PLUGIN_URL.'/support/knowledgebase/' ); ?></p>

				<p><?php printf( "<a href='https://sproutapps.co/support/' target='_blank' class='button'>%s</a>", self::__('Support') ); ?>&nbsp;<?php printf( "<a href='https://sproutapps.co/support/knowledgebase/sprout-invoices/' target='_blank' class='button'>%s</a>", self::__('Documentation') ); ?></p>

				<p><img class="footer_sa_logo" src="<?php echo SI_RESOURCES . 'admin/icons/sproutapps.png' ?>" /></p>

			</div>
		</div>

	</div>

</div>

