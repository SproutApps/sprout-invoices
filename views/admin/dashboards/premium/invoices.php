<h3 class="dashboard_widget_title">
	<span><?php self::_e('Invoice Dashboard') ?></span>
</h3>
<div class="dashboard_widget inside">
	<div class="main">
		<?php 
			$invoice_data = SI_Reporting::total_invoice_data();

			$week_payment_data = SI_Reporting::total_payment_data('week');

			$last_week_payment_data = SI_Reporting::total_payment_data('lastweek');
			$month_payment_data = SI_Reporting::total_payment_data('month');
			$last_month_payment_data = SI_Reporting::total_payment_data('lastmonth');
			$year_payment_data = SI_Reporting::total_payment_data('year');
			$last_year_payment_data = SI_Reporting::total_invoice_data('lastyear'); ?>

		<dl>
			<dt><?php self::_e('Outstanding') ?></dt>
			<dd><?php sa_formatted_money( $invoice_data['balance'] )  ?></dd>

			<dt><?php self::_e('Paid (this week)') ?></dt>
			<dd><?php sa_formatted_money( $week_payment_data['totals'] )  ?></dd>

			<dt><?php self::_e('Paid (last week)') ?></dt>
			<dd><?php sa_formatted_money( $last_week_payment_data['totals'] )  ?></dd>

			<dt><?php self::_e('Paid (month to date)') ?></dt>
			<dd><?php sa_formatted_money( $month_payment_data['totals'] )  ?></dd>

			<dt><?php self::_e('Paid (last month)') ?></dt>
			<dd><?php sa_formatted_money( $last_month_payment_data['totals'] )  ?></dd>

			<dt><?php self::_e('Paid (year to date)') ?></dt>
			<dd><?php sa_formatted_money( $year_payment_data['totals'] )  ?></dd>

			<dt><?php self::_e('Paid (last year)') ?></dt>
			<dd><?php sa_formatted_money( $last_year_payment_data['totals'] )  ?></dd>
		</dl>

		<?php 
			$args = array(
				'orderby' => 'modified',
				'post_type' => SI_Invoice::POST_TYPE,
				'post_status' => 'any', // Not Written-off?
				'posts_per_page' => 5,
				'fields' => 'ids',
				);
			$invoices = new WP_Query( $args ); ?>

		<?php if ( !empty( $invoices->posts ) ): ?>
			<b><?php self::_e('Latest Updates') ?></b> 
			<ul>
				<?php foreach ( $invoices->posts as $invoice_id ): ?>
					<li><a href="<?php echo get_edit_post_link( $invoice_id ) ?>"><?php echo get_the_title( $invoice_id ) ?></a> &mdash; <?php echo date( get_option( 'date_format' ), get_post_modified_time( 'U', false, $invoice_id ) ) ?></li>
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