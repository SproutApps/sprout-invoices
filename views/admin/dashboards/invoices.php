<h3 class="dashboard_widget_title">
	<span><?php _e( 'Invoice Dashboard', 'sprout-invoices' ) ?></span>
</h3>
<div class="dashboard_widget inside">
	<div class="main">
		<?php
			$invoice_data = SI_Reporting::total_invoice_data(); ?>
		<dl>
			<dt><?php esc_attr_e( 'Outstanding', 'sprout-invoices' ) ?></dt>
			<dd><?php sa_formatted_money( $invoice_data['balance'] )  ?></dd>

			<dt><?php esc_attr_e( 'Paid (this week)', 'sprout-invoices' ) ?></dt>
			<dd>N/A<span title="<?php esc_attr_e( 'Data available with upgraded version of Sprout Invoices', 'sprout-invoices' ) ?>" class="helptip add_item_help"></span></dd>

			<dt><?php esc_attr_e( 'Paid (last week)', 'sprout-invoices' ) ?></dt>
			<dd>N/A<span title="<?php esc_attr_e( 'Data available with upgraded version of Sprout Invoices', 'sprout-invoices' ) ?>" class="helptip add_item_help"></span></dd>

			<dt><?php esc_attr_e( 'Paid (month to date)', 'sprout-invoices' ) ?></dt>
			<dd>N/A<span title="<?php esc_attr_e( 'Data available with upgraded version of Sprout Invoices', 'sprout-invoices' ) ?>" class="helptip add_item_help"></span></dd>

			<dt><?php esc_attr_e( 'Paid (last month)', 'sprout-invoices' ) ?></dt>
			<dd>N/A<span title="<?php esc_attr_e( 'Data available with upgraded version of Sprout Invoices', 'sprout-invoices' ) ?>" class="helptip add_item_help"></span></dd>

			<dt><?php esc_attr_e( 'Paid (year to date)', 'sprout-invoices' ) ?></dt>
			<dd>N/A<span title="<?php esc_attr_e( 'Data available with upgraded version of Sprout Invoices', 'sprout-invoices' ) ?>" class="helptip add_item_help"></span></dd>
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

		<?php if ( ! empty( $invoices->posts ) ) : ?>
			<b><?php esc_attr_e( 'Latest Updates', 'sprout-invoices' ) ?></b> 
			<ul>
				<?php foreach ( $invoices->posts as $invoice_id ) : ?>
					<li><a href="<?php echo get_edit_post_link( $invoice_id ) ?>"><?php echo get_the_title( $invoice_id ) ?></a> &mdash; <?php echo date_i18n( get_option( 'date_format' ), get_post_modified_time( 'U', false, $invoice_id ) ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php else : ?>
			<p>
				<b><?php esc_attr_e( 'Latest Updates', 'sprout-invoices' ) ?></b><br/>
				<?php esc_attr_e( 'No invoices found.', 'sprout-invoices' ) ?>
			</p>
		<?php endif ?>

		<?php
			$invoices = SI_Invoice::get_overdue_invoices( apply_filters( 'si_dashboard_get_overdue_invoices_from', current_time( 'timestamp' ) - ( DAY_IN_SECONDS * 14 ) ), apply_filters( 'si_dashboard_get_overdue_invoices_to', current_time( 'timestamp' ) ) ); ?>

		<?php if ( ! empty( $invoices ) ) : ?>
			<b><?php _e( 'Recently Overdue &amp; Unpaid', 'sprout-invoices' ) ?></b> 
			<ul>
				<?php foreach ( $invoices as $invoice_id ) : ?>
					<li><a href="<?php echo get_edit_post_link( $invoice_id ) ?>"><?php echo get_the_title( $invoice_id ) ?></a> &mdash; <?php printf( __( 'Due: %s', 'sprout-invoices' ), date_i18n( get_option( 'date_format' ), si_get_invoice_due_date( $invoice_id ) ) ) ?></li>
				<?php endforeach ?>
			</ul>
		<?php else : ?>
			<p>
				<b><?php esc_attr_e( 'Overdue &amp; Unpaid', 'sprout-invoices' ) ?></b><br/>
				<?php esc_attr_e( 'No overdue or unpaid invoices.', 'sprout-invoices' ) ?>
			</p>
		<?php endif ?>
	</div>
</div>
