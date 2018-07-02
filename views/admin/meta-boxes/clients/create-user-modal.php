<!-- User Creation Form -->
<div id="user_creation_modal" style="display:none;">
	<div id="user_create_form" class="clearfix">
		<?php sa_form_fields( $fields, 'user' ); ?>
	</div>
	<p>
		<a href="javascript:void(0)" id="create_user" class="si_admin_button"><?php _e( 'Create user', 'sprout-invoices' ) ?></a>
	</p>
</div>
