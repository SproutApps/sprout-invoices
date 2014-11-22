<p>
	<b><?php si_e('Projects') ?></b>
	<?php if ( !empty( $projects ) ): ?>
		<dl>
			<?php foreach ( $projects as $project_id ): ?>
				<?php $project = SI_Project::get_instance( $project_id ) ?>
				<dt><?php printf( '<a href="%s">%s</a>', get_edit_post_link( $project_id ), get_the_title( $project_id ) ) ?></dt>
				<dd>
					<?php if ( $project->get_start_date() && $project->get_end_date() ): ?>
						<?php printf( '%s&mdash;%s', date_i18n( get_option('date_format'), $project->get_start_date() ), date_i18n( get_option('date_format'), $project->get_end_date() ) ) ?>
					<?php elseif ( $project->get_start_date() ): ?>
						<?php printf( '<b>Start</b>&mdash;%s', date_i18n( get_option('date_format'), $project->get_start_date() ) ) ?>
					<?php elseif ( $project->get_end_date() ): ?>
						<?php printf( '<b>End</b>&mdash;%s', date_i18n( get_option('date_format'), $project->get_end_date() ) ) ?>
					<?php else: ?>
						<?php si_e('No start and/or end date set.') ?>
					<?php endif ?>
					
				</dd>
			<?php endforeach ?>
		</dl>
	<?php else: ?>
		<em><?php si_e('No projects') ?></em>
	<?php endif ?>
</p>
<hr/>