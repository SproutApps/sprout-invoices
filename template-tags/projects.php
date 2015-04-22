<?php 


if ( !function_exists('si_projects_select') ) :

/**
 * Create a project select list that are grouped by client.
 * @param  integer $selected_id
 * @return string
 */
function si_projects_select( $selected_id = 0, $client_id = 0, $blank = true, $el_id = 'doc_project' ) {
	$selections = array();
	if ( $client_id ) {
		$client = SI_Client::get_instance( $client_id );
		$projects = SI_Project::get_projects_by_client( $client_id );
		$selections[ $client->get_title() ] = $projects;
	}
	else {
		$args = array(
			'post_type' => SI_Project::POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids'
		);
		$projects = get_posts( $args );
		foreach ( $projects as $project_id ) {
			$project = SI_Project::get_instance( $project_id );
			$clients = $project->get_associated_clients();
			foreach ( $clients as $client_id ) {
				$client = SI_Client::get_instance( $client_id );
				if ( is_a( $client, 'SI_Client' ) ) {
					if ( !isset( $selections[ $client->get_title() ] ) ) {
						$selections[ $client->get_title() ] = array();
					}
					$selections[ $client->get_title() ][] = $project_id;
				} 
				else { // no client assigned
					$selections[ si__('Client N/A') ][] = $project_id;
				}

				
			}
		}
	}

	if ( !empty( $selections ) ) {
		$out = '<select name="'.$el_id.'" class="select2">';
		if ( $blank ) {
			$out .= sprintf( '<option>%s</option>', si__('Select Project') );
		}
		foreach ( $selections as $client => $projects ) {
			$out .= sprintf( '<optgroup label="%s">', $client );
				foreach ( $projects as $project_id ) {
					$out .= sprintf( '<option value="%s" %s>%s</option>', $project_id, selected( $project_id, $selected_id, false ), get_the_title( $project_id ) );
				}
			$out .= '</optgroup>';
		}
		$out .= '</select>';
	}
	else {
		$out = '<span>'.sprintf( si__('No <a href="%s" target="_blank">projects</a> found'), admin_url( 'post-new.php?post_type='.SI_Project::POST_TYPE ) ).'</span>';
	}
	
	echo $out;

}
endif;