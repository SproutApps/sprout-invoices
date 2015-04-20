<dl id="history_list">

	<dt>
		<span class="history_status creation_event"><?php si_e('Created') ?></span><br/>
		<span class="history_date"><?php echo date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $post->post_date ) ) ?></span>
	</dt>
	<dd><p>
		&nbsp;
	</p></dd>

	<?php foreach ( $historical_records as $record_id ): ?>
		<?php 
			$record = SI_Record::get_instance( $record_id );
			// If no type is set than just keep on moving.
			if ( $record->get_type() == SI_Record::DEFAULT_TYPE ) {
				continue;
			}
			$r_post = $record->get_post();
			switch ( $record->get_type() ) {
				case SI_Controller::PRIVATE_NOTES_TYPE:
					$type = si__('Private Note');
					break;

				case SI_Estimates::HISTORY_UPDATE:
					$type = si__('Estimate Updated');
					break;

				case SI_Estimates::VIEWED_STATUS_UPDATE:
					$type = si__('Estimate Viewed');
					break;

				case SI_Notifications::RECORD:
					$type = si__('Notification');
					break;

				case SI_Estimates::HISTORY_INVOICE_CREATED:
					$type = si__('Invoice Created');
					break;

				case SI_Estimates::HISTORY_STATUS_UPDATE:
				default:
					$type = si__('Status Update');
					break;
			} ?>
		<dt>
			<span class="history_status <?php echo esc_attr( $record->get_type() ) ?>"><?php echo esc_html( $type ); ?></span><br/>
			<span class="history_date"><?php echo date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $r_post->post_date ) ) ?></span>
		</dt>

		<dd>
			<?php if ( $record->get_type() == SI_Notifications::RECORD ): ?>
				<p>
					<?php echo esc_html( $r_post->post_title ) ?>
					<br/><a href="#TB_inline?width=600&height=380&inlineId=notification_message_<?php echo (int) $r_post->ID ?>" id="show_notification_tb_link_<?php echo (int) $r_post->ID ?>" class="thickbox si_tooltip notification_message" title="<?php si_e('View Message') ?>"><?php si_e('View Message') ?></a>
				</p>
				<div id="notification_message_<?php echo (int) $r_post->ID ?>" class="cloak">
					<?php echo wpautop( $r_post->post_content ) ?>
				</div>
			<?php else: ?>
				<?php echo wpautop( $r_post->post_content ) ?>
			<?php endif ?>
			
		</dd>
	<?php endforeach ?>
</dl>

<div id="private_note_wrap">
	<p>
		<textarea id="private_note" name="private_note" class="clearfix"></textarea>
		<a href="javascript:void(0)" id="save_private_note" class="button" data-post-id="<?php the_ID() ?>" data-nonce="<?php echo wp_create_nonce( SI_Internal_Records::NONCE ) ?>"><?php si_e('Save') ?></a> <span class="helptip" title="<?php si_e("These private notes will be added to the history.") ?>"></span>
	</p>
</div>