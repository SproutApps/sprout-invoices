<!-- Create Client Form -->
<div id="client_creation_modal" style="display:none;">
	<div id="client_create_form" class="clearfix">
		<?php sa_form_fields( $fields, 'client' ); ?>
	</div>	
	<p>
		<a href="javascript:void(0)" id="create_client" class="si_admin_button"><?php _e( 'Create client', 'sprout-invoices' ) ?></a>
	</p>
</div>
