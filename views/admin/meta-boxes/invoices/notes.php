<?php 
	$editor_settings = array(
			'media_buttons'     => FALSE,
			'textarea_rows'     => 4,
			'theme_styles'		=> FALSE,
		);
 ?>

<h3><?php si_e('Terms') ?> <span class="helptip" title="<?php si_e("Terms will be shown on the invoice.") ?>"></span></h3>
<?php
	wp_editor_styleless( $terms, 'invoice_terms', $editor_settings ); ?>

<h3><?php si_e('Notes') ?> <span class="helptip" title="<?php si_e("These notes will be shown on the invoice.") ?>"></span></h3>
<?php
	wp_editor_styleless( $notes, 'invoice_notes', $editor_settings ); ?>
