<dl id="history_list">

	<dt>
		<span class="history_status creation_event"><?php si_e('Created') ?></span><br/>
		<span class="history_date"><?php echo date( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $post->post_date ) ) ?></span>
	</dt>

	<dd><p>
		<?php if ( !empty( $submission_fields ) ): ?>
			<?php if ( $invoice->get_client_id() ): ?>
				<?php printf( si__('Submitted by <a href="%s">%s</a>'), get_edit_post_link( $invoice->get_client_id() ), get_the_title( $invoice->get_client_id() ) ) ?>
			<?php else: ?>
				<?php si_e('Submitted') ?>
			<?php endif ?>
		<?php else: ?>
			<?php $user = get_userdata( $post->post_author ) ?>
			<?php printf( si_e('Added by %s'), $user->display_name )  ?>
		<?php endif ?>
	</p></dd>
	
	<?php foreach ( $history as $item_id => $data ): ?>
		<dt>
			<span class="history_status <?php echo $data['status_type'] ?>"><?php echo $data['type']; ?></span><br/>
			<span class="history_date"><?php echo date( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $data['post_date'] ) ) ?></span>
		</dt>

		<dd>
			<?php if ( $data['status_type'] == SI_Notifications::RECORD ): ?>
				<p>
					<?php echo $data['update_title'] ?>
					<br/><a href="#TB_inline?width=600&height=380&inlineId=notification_message_<?php echo $item_id ?>" id="show_notification_tb_link_<?php echo $item_id ?>" class="thickbox tooltip notification_message" title="<?php si_e('View Message') ?>"><?php si_e('View Message') ?></a>
				</p>
				<div id="notification_message_<?php echo $item_id ?>" class="cloak">
					<?php echo apply_filters( 'the_content', $data['content'] ) ?>
				</div>
			<?php elseif ( $data['status_type'] == SI_Importer::RECORD ): ?>
				<p>
					<?php echo $data['update_title'] ?>
					<br/><a href="#TB_inline?width=600&height=380&inlineId=notification_message_<?php echo $item_id ?>" id="show_notification_tb_link_<?php echo $item_id ?>" class="thickbox tooltip notification_message" title="<?php si_e('View Data') ?>"><?php si_e('View Data') ?></a>
				</p>
				<div id="notification_message_<?php echo $item_id ?>" class="cloak">
					<?php prp( json_decode( $data['content'] ) ); ?>
				</div>
			<?php elseif ( $data['status_type'] == SI_Invoices::VIEWED_STATUS_UPDATE ) : ?>
				<p>
					<?php echo $data['update_title'] ?>
				</p>
			<?php else: ?>
				<?php echo apply_filters( 'the_content', $data['content'] ) ?>
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