<section id="header_attachments_info" class="clearfix">
	<div class="attachments_info">
		<h3><?php _e( 'Attachments', 'sprout-invoices' ) ?></h3>
		<div>
			<?php foreach ( $attachments as $media_id ) : ?>
				<?php
					$file = basename( get_attached_file( $media_id ) );
					$filetype = wp_check_filetype( $file );
					$icon = SI_Attachment_Downloads::get_attachment_icon( $media_id );
					?>
				<span>
					<a href="<?php echo wp_get_attachment_url( $media_id ) ?>" download>
						<img src="<?php echo esc_url_raw( $icon ) ?>" title="<?php echo get_the_title( $media_id ) ?>" class="doc_attachment attachment_type_<?php echo esc_attr( $filetype['ext'] ) ?>" style="width: 40px; height: auto;">
					</a>
				</span>
			<?php endforeach ?>
		</div>
	</div>
</section>
