<?php

/**
 * Copy of the post_submit_meta_box function
 *
 */

$post_type = $post->post_type;
$post_type_object = get_post_type_object( $post_type );
$can_publish = current_user_can( $post_type_object->cap->publish_posts );
?>
<div class="submitbox" id="submitpost">

	<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
	<div style="display:none;">
		<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
	</div>

	<div id="minor-publishing">
		<span id="notification_type_wrap">
			<select name="notification_type" id="notification_type">
				<option></option>
				<?php foreach ( $notification_types as $notification_slug => $data ) : ?>
					<?php $notification_name = esc_html( $data['name'] ); ?>
					<option value="<?php echo esc_attr( $notification_slug ) ?>" <?php selected( isset( $notifications_option[ $notification_slug ] ) ? $notifications_option[ $notification_slug ] : '', $id ) ?>><?php echo esc_html( $data['name'] ) ?></option>
				<?php endforeach ?>
			</select>
		</span>
		<?php foreach ( $notification_types as $notification_slug => $data ) { ?>
			<p id="notification_type_description_<?php echo esc_attr( $notification_slug ); ?>" class="notification_type_description description">
				<?php echo esc_html( $data['description'] ); ?>
			</p>
		<?php } ?>

		<p>
			<span id="notification_type_disabled_wrap">
				<input type="checkbox" id="notification_type_disabled" name="notification_type_disabled" value="TRUE" <?php checked( 'TRUE', $disabled ) ?> />&nbsp;<?php _e( 'Disabled', 'sprout-invoices' ) ?>
			</span>
		</p>

	</div><!-- #minor-publishing -->

	<div id="major-publishing-actions">
		<?php do_action( 'post_submitbox_start' ); ?>
		<div id="delete-action">

			<?php
			if ( current_user_can( 'delete_post', $post->ID ) ) { ?>
				<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID, null, true ); ?>"><?php _e( 'Delete', 'sprout-invoices' ) ?></a><?php
			} ?>
		</div>

		<div id="publishing-action">
			<?php
			if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ) ) || 0 == $post->ID ) {
				if ( $can_publish ) : ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ) ?>" />
					<?php submit_button( __( 'Publish' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
				<?php else : ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Submit for Review' ) ?>" />
					<?php submit_button( __( 'Submit for Review' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) ); ?>
				<?php endif;
			} else { ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ) ?>" />
					<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="<?php esc_attr_e( 'Update' ) ?>" />
			<?php
			} ?>
			<span class="spinner"></span>
		</div>
	<div class="clear"></div>
	</div><!-- #major-publishing-actions -->
</div>
