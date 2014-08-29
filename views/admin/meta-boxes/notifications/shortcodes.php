<p>
	<?php si_e( 'Use the following shortcodes to customize the email sent.' ); ?>
</p>
<?php
foreach ( $type['shortcodes'] as $shortcode ) : if ( isset( $shortcodes[$shortcode] ) ) : ?>
<p>
	<strong>[<?php echo $shortcode; ?>]</strong> &mdash;
	<?php echo $shortcodes[$shortcode]['description']; ?>
</p>
<?php endif; endforeach; ?>
