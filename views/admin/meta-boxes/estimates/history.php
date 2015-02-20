<dl id="history_list">

	<dt>
		<span class="history_status creation_event"><?php si_e('Created') ?></span><br/>
		<span class="history_date"><?php echo date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $post->post_date ) ) ?></span>
	</dt>

	<dd><p>
		<?php if ( !empty( $submission_fields ) ): ?>
			<?php if ( $estimate->get_client_id() ): ?>
				<?php printf( si__('Submitted by <a href="%s">%s</a>'), get_edit_post_link( $estimate->get_client_id() ), get_the_title( $estimate->get_client_id() ) ) ?>
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
			<span class="history_date"><?php echo date_i18n( get_option( 'date_format' ).' @ '.get_option( 'time_format' ), strtotime( $data['post_date'] ) ) ?></span>
		</dt>

		<dd>
			<?php if ( $data['status_type'] == SI_Notifications::RECORD ): ?>
				<p>
					<?php echo $data['update_title'] ?>
					<br/><a href="#TB_inline?width=600&height=380&inlineId=notification_message_<?php echo $item_id ?>" id="show_notification_tb_link_<?php echo $item_id ?>" class="thickbox si_tooltip notification_message" title="<?php si_e('View Message') ?>"><?php si_e('View Message') ?></a>
				</p>
				<div id="notification_message_<?php echo $item_id ?>" class="cloak">
					<?php echo wpautop( $data['content'] ) ?>
				</div>
			<?php elseif ( $data['status_type'] == SI_Importer::RECORD ): ?>
				<p>
					<?php echo $data['update_title'] ?>
					<br/><a href="#TB_inline?width=600&height=380&inlineId=notification_message_<?php echo $item_id ?>" id="show_notification_tb_link_<?php echo $item_id ?>" class="thickbox si_tooltip notification_message" title="<?php si_e('View Data') ?>"><?php si_e('View Data') ?></a>
				</p>
				<div id="notification_message_<?php echo $item_id ?>" class="cloak">
					<?php prp( json_decode( $data['content'] ) ); ?>
				</div>
			<?php elseif ( $data['status_type'] == SI_Invoices::VIEWED_STATUS_UPDATE ) : ?>
				<p>
					<?php echo $data['update_title'] ?>
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
		<?php if (  apply_filters( 'show_upgrade_messaging', TRUE ) ) {
			printf( si__('<span class="helptip" title="Upgrade for Private Notes"></span>'), si_get_purchase_link() );
		} ?>
	</p>
</div>


<?php if ( !empty( $submission_fields ) ): ?>
	<div id="submission_fields_wrap">
		<h3><?php si_e('Form Submission') ?></h3>
		<dl>
			<?php foreach ( $submission_fields as $key => $value ): ?>
				<?php if ( isset( $value['data'] ) ): ?>
					<?php if ( $value['data']['label'] && $value['data']['type'] != 'hidden' ): ?>
						<dt><?php echo $value['data']['label'] ?></dt>
						<?php if ( is_numeric( $value['value'] ) && strpos( $value['data']['label'], self::__('Type') ) !== FALSE ): ?>
							<dd><p><?php 
									$term = get_term_by( 'id', $value['value'], SI_Estimate::PROJECT_TAXONOMY );
									if ( !is_wp_error( $term ) ) {
										self::_e( $term->name );
									}
								 ?></p></dd>
						<?php else: ?>
							<dd><?php echo wpautop( $value['value'] ) ?></dd>
						<?php endif ?>
					<?php endif ?>
				<?php endif ?>
			<?php endforeach ?>
		</dl>
	</div>

	<?php $media = get_attached_media( '' ); ?>
	<?php if ( !empty( $media ) ): ?>
		<p>
			<h3><?php si_e('Attachments') ?></h3>
			<ul>
				<?php foreach ( $media as $id => $mpost ): ?>
					<?php  $img = wp_get_attachment_image_src( $id, 'thumbnail', TRUE ); ?>
					<li><a href="<?php echo wp_get_attachment_url( $id ) ?>" target="_blank" class="attachment_url"><img src="<?php  echo $img[0]; ?>" alt="<?php esc_attr_e( $mpost->post_name ) ?>"></a></li>
				<?php endforeach ?>
			</ul>
		</p>
	<?php endif ?>

<?php endif ?>
