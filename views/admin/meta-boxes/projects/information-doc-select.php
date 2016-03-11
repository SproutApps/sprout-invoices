<div class="misc-pub-section" data-edit-id="project" data-edit-type="select">
	<span id="project" class="wp-media-buttons-icon"><b><?php echo esc_html( $title ); ?></b> <span title="<?php _e( 'Select an existing project.', 'sprout-invoices' ) ?>" class="helptip"></span></span>

		<a href="#edit_project" class="edit-project hide-if-no-js edit_control" >
			<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Select different project', 'sprout-invoices' ) ?></span>
		</a>

		<div id="project_div" class="control_wrap hide-if-js">
			<div class="project-wrap">
				<?php si_projects_select( $project_id, $client_id ) ?>
	 		</div>
			<p>
				<a href="#edit_project" class="save_control save-project hide-if-no-js button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
				<a href="#edit_project" class="cancel_control cancel-project hide-if-no-js button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
			</p>
	 	</div>
</div>
