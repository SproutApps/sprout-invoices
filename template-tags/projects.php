<?php


if ( ! function_exists( 'si_projects_select' ) ) :

	/**
 * Create a project select list that are grouped by client.
 * @param  integer $selected_id
 * @return string
 */
	function si_projects_select( $selected_id = 0, $client_id = 0, $blank = true, $el_id = 'doc_project' ) {
		$selections = array();
		if ( $client_id ) {
			$client = SI_Client::get_instance( $client_id );
			if ( is_a( $client, 'SI_Client' ) ) {
				$projects = SI_Project::get_projects_by_client( $client_id );
				$selections[ $client->get_title() ] = $projects;
			}
		}
		if ( empty( $selections ) ) {
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
						if ( ! isset( $selections[ $client->get_title() ] ) ) {
							$selections[ $client->get_title() ] = array();
						}
						$selections[ $client->get_title() ][] = $project_id;
					}
					else { // no client assigned
						$selections[ __( 'Client N/A', 'sprout-invoices' ) ][] = $project_id;
					}
				}
			}
		}

		if ( ! empty( $selections ) ) {
			$out = '<select name="'.$el_id.'" class="select2">';
			if ( $blank ) {
				$out .= sprintf( '<option value="0">%s</option>', __( 'Select Project', 'sprout-invoices' ) );
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
			$out = '<span>'.sprintf( __( 'No <a href="%s" target="_blank">projects</a> found', 'sprout-invoices' ), admin_url( 'post-new.php?post_type='.SI_Project::POST_TYPE ) ).'</span>';
		}

		echo $out;

	}
endif;

if ( ! function_exists( 'si_get_docs_project_id' ) ) :
	function si_get_docs_project_id( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$doc = si_get_doc_object( $id );
		if ( '' === $doc ) {
			return 0;
		}
		return apply_filters( 'si_get_docs_project_id', $doc->get_project_id(), $doc );
	}
endif;


if ( ! function_exists( 'si_get_project_start_date' ) ) :
	function si_get_project_start_date( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$project = SI_Project::get_instance( $id );
		return apply_filters( 'si_get_project_start_date', $project->get_start_date(), $project );
	}
endif;

if ( ! function_exists( 'si_project_start_date' ) ) :
	function si_project_start_date( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_project_start_date', date_i18n( get_option( 'date_format' ), si_get_project_start_date( $id ) ), $id );
	}
endif;


if ( ! function_exists( 'si_get_project_end_date' ) ) :
	function si_get_project_end_date( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$project = SI_Project::get_instance( $id );
		return apply_filters( 'si_get_project_end_date', $project->get_end_date(), $project );
	}
endif;

if ( ! function_exists( 'si_project_end_date' ) ) :
	function si_project_end_date( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_project_end_date', date_i18n( get_option( 'date_format' ), si_get_project_end_date( $id ) ), $id );
	}
endif;

if ( ! function_exists( 'si_get_project_website' ) ) :
	function si_get_project_website( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$project = SI_Project::get_instance( $id );
		return apply_filters( 'si_get_project_website', $project->get_website(), $project );
	}
endif;

if ( ! function_exists( 'si_project_website' ) ) :
	function si_project_website( $id = 0 ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		echo apply_filters( 'si_project_website', si_get_project_website( $id ), $id );
	}
endif;