<h3 class="dashboard_widget_title">
	<span><?php self::_e('Invoice Dashboard') ?></span>
</h3>
<div class="dashboard_widget inside">
	<div class="main">
		<?php 
			$invoice_data = SI_Reporting::total_invoice_data(); ?>
		<dl>
			<dt><?php self::_e('Outstanding') ?></dt>
			<dd><?php sa_formatted_money( $invoice_data['balance'] )  ?></dd>

			<dt><?php self::_e('Paid (this week)') ?></dt>
			<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>

			<dt><?php self::_e('Paid (last week)') ?></dt>
			<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>

			<dt><?php self::_e('Paid (month to date)') ?></dt>
			<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>

			<dt><?php self::_e('Paid (last month)') ?></dt>
			<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>

			<dt><?php self::_e('Paid (year to date)') ?></dt>
			<dd>N/A<span title="<?php self::esc_e('Data available with upgraded version of Sprout Invoices') ?>" class="helptip add_item_help"></span></dd>
		</dl>

		

		<?php 
			$args = array(
				'orderby' => 'modified',
				'post_type' => SI_Invoice::POST_TYPE,
				'post_status' => 'any', // Not Written-off?
				'posts_per_page' => 3,
				'fields' => 'ids',
				);
			$invoices = new WP_Query( $args ); ?>

		<?php if ( !empty( $invoices->posts ) ): ?>
			<b><?php self::_e('Latest Updates') ?></b> 
			<ul>
				<?php foreach ( $invoices->posts as $invoice_id ): ?>
					<li><a href="<?php echo get_edit_post_link( $invoice_id ) ?>"><?php echo get_the_title( $invoice_id ) ?></a> &mdash; <?php echo date_i18n( get_option( 'date_format' ), get_post_modified_time( 'U', false, $invoice_id ) ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php else: ?>
			<p>
				<b><?php self::_e('Latest Updates') ?></b><br/>
				<?php self::_e('No invoices found.') ?>
			</p>
		<?php endif ?>

		<?php 
			$invoices = SI_Invoice::get_overdue_invoices(); ?>

		<?php if ( !empty( $invoices ) ): ?>
			<b><?php self::_e('Overdue &amp; Unpaid') ?></b> 
			<ul>
				<?php foreach ( $invoices as $invoice_id ): ?>
					<li><a href="<?php echo get_edit_post_link( $invoice_id ) ?>"><?php echo get_the_title( $invoice_id ) ?></a> &mdash; <?php printf( self::__('Due: %s'), date_i18n( get_option('date_format'), si_get_invoice_due_date( $invoice_id ) ) ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php else: ?>
			<p>
				<b><?php self::_e('Overdue &amp; Unpaid') ?></b><br/>
				<?php self::_e('No overdue or unpaid invoices.') ?>
			</p>
		<?php endif ?>
	</div>
</div>