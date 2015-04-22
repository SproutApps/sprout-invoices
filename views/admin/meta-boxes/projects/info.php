<div id="project_fields" class="admin_fields clearfix">
	<?php sa_admin_fields( $fields ); ?>
</div>


<h3><?php si_e('Project Brief') ?> <span class="helptip" title="<?php si_e("General project brief.") ?>"></span></h3>
<?php 
	$editor_settings = array(
			'media_buttons'     => false,
			'textarea_rows'     => 4,
			'theme_styles'		=> false
		);
	wp_editor_styleless( $project->get_content(), 'content', $editor_settings ); ?>