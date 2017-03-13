<dl id="history_list">

	<dt>
		<span class="history_status creation_event"><?php _e( 'Created', 'sprout-invoices' ) ?></span><br/>
		<span class="history_date"><?php echo date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $post->post_date ) ) ?></span>
	</dt>
	<dd><p>
		&nbsp;
	</p></dd>

	<?php foreach ( $historical_records as $record_id ) :  ?>
		<?php
			$record = SI_Record::get_instance( $record_id );
			// If no type is set than just keep on moving.
		if ( $record->get_type() == SI_Record::DEFAULT_TYPE ) {
			continue;
		}
			$r_post = $record->get_post();
		switch ( $record->get_type() ) {
			case SI_Controller::PRIVATE_NOTES_TYPE:
				$type = __( 'Private Note', 'sprout-invoices' );
				break;

			case SI_Notifications::RECORD:
				$type = __( 'Notification', 'sprout-invoices' );
				break;

			case SI_Projects::HISTORY_STATUS_UPDATE:
			default:
				$type = __( 'Status Update', 'sprout-invoices' );
				break;
		} ?>
		<dt class="record record-<?php echo $record_id ?>">
			<span class="history_deletion"><button data-id="<?php echo $record_id ?>" class="delete_record del_button">X</button></span>
			<span class="history_status <?php echo esc_attr( $record->get_type() ); ?>"><?php echo esc_html( $type ); ?></span><br/>
			<span class="history_date"><?php echo date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $r_post->post_date ) ) ?></span>
		</dt>

		<dd class="record record-<?php echo $record_id ?>">
			<?php if ( $record->get_type() == SI_Notifications::RECORD ) :  ?>
				<p>
					<?php echo esc_html( $r_post->post_title ) ?>
					<br/><a href="#TB_inline?width=600&height=380&inlineId=notification_message_<?php echo (int) $r_post->ID ?>" id="show_notification_tb_link_<?php echo (int) $r_post->ID ?>" class="thickbox si_tooltip notification_message" title="<?php _e( 'View Message', 'sprout-invoices' ) ?>"><?php _e( 'View Message', 'sprout-invoices' ) ?></a>
				</p>
				<div id="notification_message_<?php echo (int) $r_post->ID ?>" class="cloak">
					<?php echo wpautop( stripslashes_from_strings_only( $r_post->post_content ) ) ?>
				</div>
			<?php elseif ( SI_Controller::PRIVATE_NOTES_TYPE === $record->get_type() ) :  ?>
				<?php echo wpautop( stripslashes_from_strings_only( $r_post->post_content ) ) ?>
				<p>
					<a class="thickbox si_tooltip edit_private_note" href="<?php echo admin_url( 'admin-ajax.php?action=si_edit_private_note_view&width=600&height=350&note_id=' . $record_id ) ?>" id="show_edit_private_note_tb_link_<?php echo (int) $record_id ?>" title="<?php _e( 'Edit Note', 'sprout-invoices' ) ?>"><?php _e( 'Edit', 'sprout-invoices' ) ?></a>
				</p>
			<?php else : ?>
				<?php echo wpautop( stripslashes_from_strings_only( $r_post->post_content ) ) ?>
			<?php endif ?>
			
		</dd>
	<?php endforeach ?>
</dl>

<div id="private_note_wrap">
	<p>
		<textarea id="private_note" name="private_note" class="clearfix"></textarea>
		<a href="javascript:void(0)" id="save_private_note" class="button" data-post-id="<?php the_ID() ?>" data-nonce="<?php echo wp_create_nonce( SI_Internal_Records::NONCE ) ?>"><?php _e( 'Save', 'sprout-invoices' ) ?></a> <span class="helptip" title="<?php _e( 'These private notes will be added to the history.', 'sprout-invoices' ) ?>"></span>
	</p>
</div>
