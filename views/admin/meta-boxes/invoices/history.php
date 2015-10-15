<dl id="history_list">

	<dt>
		<span class="history_status creation_event"><?php _e( 'Created', 'sprout-invoices' ) ?></span><br/>
		<span class="history_date"><?php echo date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $post->post_date ) ) ?></span>
	</dt>

	<dd><p>
		<?php if ( !empty( $submission_fields ) ): ?>
			<?php if ( $invoice->get_client_id() ): ?>
				<?php printf( __( 'Submitted by <a href="%s">%s</a>', 'sprout-invoices' ), get_edit_post_link( $invoice->get_client_id() ), get_the_title( $invoice->get_client_id() ) ) ?>
			<?php else: ?>
				<?php _e( 'Submitted', 'sprout-invoices' ) ?>
			<?php endif ?>
		<?php elseif( is_a( $post, 'WP_Post') ) : ?>
			<?php $user = get_userdata( $post->post_author ) ?>
			<?php printf( __( 'Added by %s', 'sprout-invoices' ), $user->display_name )  ?>
		<?php else: ?>
			<?php _e( 'Added by SI', 'sprout-invoices' )  ?>
		<?php endif ?>
	</p></dd>
	
	<?php foreach ( $history as $item_id => $data ): ?>
		<dt>
			<span class="history_status <?php echo esc_attr( $data['status_type'] ); ?>"><?php echo esc_attr( $data['type'] ) ?></span><br/>
			<span class="history_date"><?php echo date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $data['post_date'] ) ) ?></span>
		</dt>

		<dd>
			<?php if ( $data['status_type'] == SI_Notifications::RECORD ): ?>
				<p>
					<?php echo esc_html( $data['update_title'] ); ?>
					<br/><a href="#TB_inline?width=600&height=380&inlineId=notification_message_<?php echo (int) $item_id; ?>" id="show_notification_tb_link_<?php echo (int) $item_id; ?>" class="thickbox si_tooltip notification_message" title="<?php _e( 'View Message', 'sprout-invoices' ) ?>"><?php _e( 'View Message', 'sprout-invoices' ) ?></a>
				</p>
				<div id="notification_message_<?php echo (int) $item_id; ?>" class="cloak">
					<?php echo wpautop( $data['content'] ) ?>
				</div>
			<?php elseif ( $data['status_type'] == SI_Importer::RECORD ): ?>
				<p>
					<?php echo esc_html( $data['update_title'] ); ?>
					<br/><a href="#TB_inline?width=600&height=380&inlineId=notification_message_<?php echo (int) $item_id ?>" id="show_notification_tb_link_<?php echo (int) $item_id ?>" class="thickbox si_tooltip notification_message" title="<?php _e( 'View Data', 'sprout-invoices' ) ?>"><?php _e( 'View Data', 'sprout-invoices' ) ?></a>
				</p>
				<div id="notification_message_<?php echo (int) $item_id ?>" class="cloak">
					<?php prp( json_decode( $data['content'] ) ); ?>
				</div>
			<?php elseif ( $data['status_type'] == SI_Invoices::VIEWED_STATUS_UPDATE ) : ?>
				<p>
					<?php echo esc_html( $data['update_title'] ); ?>
				</p>
			<?php else: ?>
				<?php echo wpautop( $data['content'] ) ?>
			<?php endif ?>
			
		</dd>
	<?php endforeach ?>
		
</dl>

<div id="private_note_wrap">
	<p>
		<textarea id="private_note" name="private_note" class="clearfix" disabled="disabled" style="height:40px;"></textarea>
		<?php if (  apply_filters( 'show_upgrade_messaging', true ) ) {
			printf( __( '<span class="helptip" title="Upgrade for Private Notes"></span>', 'sprout-invoices' ), si_get_purchase_link() );
		} ?>
	</p>
</div>