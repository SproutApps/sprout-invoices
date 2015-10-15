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
		<p>
			<b><?php _e( 'Associated Users', 'sprout-invoices' ) ?></b> <span class="helptip" title="<?php _e( 'Clients can have multiple users associated. Each user will receive notifications.', 'sprout-invoices' ) ?>"></span>
			<select id="associated_users" style="width:100%" class="select2">
				<option></option>
				<?php foreach ( $users as $user ) : ?>
					<?php if ( ! in_array( $user->ID, $associated_users ) ) : ?>
						<option value="<?php echo (int) $user->ID ?>" <?php selected( in_array( $user->ID, $associated_users ), true ) ?> data-url="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user->ID ) ) ?>" data-user-email="<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->display_name ) ?></option>
					<?php endif ?>
				<?php endforeach ?>
			</select>
			<?php if ( ! empty( $associated_users ) ) : ?>
				<ul id="associated_users_list">
					<?php foreach ( $associated_users as $a_user_id ) : ?>
						<?php
							$u = get_userdata( $a_user_id );
						if ( ! is_a( $u, 'WP_User' ) ) {
							continue;
						} ?>
						<li id="list_user_id-<?php echo (int) $a_user_id ?>"><?php printf( '<a href="%s" class="si_tooltip" title="%s">%s</a>', admin_url( 'user-edit.php?user_id=' . $a_user_id ), $u->user_email, $u->display_name ) ?>  <a data-id="<?php echo (int) $a_user_id ?>" class="remove_user del_button">X</a> <?php do_action( 'client_associated_user_list', $a_user_id ) ?></li>
					<?php endforeach ?>
				</ul>
				<div id="hidden_associated_users_list" class="cloak">
					<?php foreach ( $associated_users as $a_user_id ) : ?>
						<input type="hidden" name="associated_users[]" value="<?php echo (int) $a_user_id ?>" />
					<?php endforeach ?>
				</div>
			<?php else : ?>
				<ul id="associated_users_list"></ul>
				<div id="hidden_associated_users_list" class="cloak"></div>
			<?php endif ?>

			<a href="#TB_inline?width=300&height=200&inlineId=user_creation_modal" id="user_creation_modal_tb_link" class="thickbox button" title="<?php _e( 'Create new user for this client', 'sprout-invoices' ) ?>"><?php _e( 'New User', 'sprout-invoices' ) ?></a>
		</p>
		<hr/>
		<?php do_action( 'client_submit_pre_invoices' ) ?>
		<p>
			<b><?php _e( 'Invoices', 'sprout-invoices' ) ?></b>
			<?php if ( ! empty( $invoices ) ) : ?>
				<dl>
					<?php foreach ( $invoices as $invoice_id ) : ?>
						<dt><?php echo get_post_time( get_option( 'date_format' ), false, $invoice_id ) ?></dt>
						<dd><?php printf( '<a href="%s">%s</a>', get_edit_post_link( $invoice_id ), get_the_title( $invoice_id ) ) ?></dd>
					<?php endforeach ?>
				</dl>
			<?php else : ?>
				<em><?php _e( 'No invoices', 'sprout-invoices' ) ?></em>
			<?php endif ?>
		</p>
		<hr/>
		<?php do_action( 'client_submit_pre_estimates' ) ?>
		<p>
			<b><?php _e( 'Estimates', 'sprout-invoices' ) ?></b>
			<?php if ( ! empty( $estimates ) ) : ?>
				<dl>
					<?php foreach ( $estimates as $estimate_id ) : ?>
						<dt><?php echo get_post_time( get_option( 'date_format' ), false, $estimate_id ) ?></dt>
						<dd><?php printf( '<a href="%s">%s</a>', get_edit_post_link( $estimate_id ), get_the_title( $estimate_id ) ) ?></dd>
					<?php endforeach ?>
				</dl>
			<?php else : ?>
				<em><?php _e( 'No estimates', 'sprout-invoices' ) ?></em>
			<?php endif ?>
		</p>
		<div class="clear"></div>
	</div><!-- #minor-publishing -->

	<div id="major-publishing-actions">
		<?php do_action( 'post_submitbox_start' ); ?>
		<div id="delete-action">
			<?php
			if ( current_user_can( 'delete_sprout_invoices', $post->ID ) ) { ?>
				<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID, null, true ); ?>"><?php _e( 'Delete', 'sprout-invoices' ) ?></a><?php
			} ?>
		</div>

		<div id="publishing-action">
			<?php
			if ( ! in_array( $post->post_status, array('publish', 'future', 'private') ) || 0 == $post->ID ) {
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