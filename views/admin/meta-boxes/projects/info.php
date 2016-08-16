<div id="project_fields" class="admin_fields clearfix">
	<?php sa_admin_fields( $fields ); ?>
</div>


<h3><?php _e( 'Project Brief', 'sprout-invoices' ) ?> <span class="helptip" title="<?php _e( 'General project brief.', 'sprout-invoices' ) ?>"></span></h3>
<?php
	$editor_settings = array(
			'media_buttons'     => false,
			'textarea_rows'     => 4,
			'theme_styles'		=> false,
		);
	wp_editor_styleless( $project->get_content(), 'content', $editor_settings ); ?>
