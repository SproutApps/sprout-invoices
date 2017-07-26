<?php


function si_default_theme_customizer_options( $wp_customize ) {
		// Highlight and link color
		$wp_customize->add_setting( 'si_invoices_color', array(
		    'default'           => '#FF5B4D',
		    'sanitize_callback' => 'sanitize_hex_color',
		    //'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_invoices_color', array(
		    'label'	   => __( 'Invoice Highlight Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_invoices_color',
		) ) );

		// Highlight and link color
		$wp_customize->add_setting( 'si_estimates_color', array(
		    'default'           => '#4D9FFF',
		    'sanitize_callback' => 'sanitize_hex_color',
		    //'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_estimates_color', array(
		    'label'	   => __( 'Estimate Highlight Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_estimates_color',
		) ) );
}
add_action( 'customize_register', 'si_default_theme_customizer_options' );


function si_original_theme_inject_css() {
	$inv_color = SI_Customizer::sanitize_hex_color( get_theme_mod( 'si_invoices_color' ) );
	$est_color = SI_Customizer::sanitize_hex_color( get_theme_mod( 'si_estimates_color' ) );
	?>
		<!-- Debut customizer CSS -->
		<style>
		#doc .doc_total,
		.button.primary_button {
			background-color: <?php echo esc_attr( $est_color ); ?>;
		}

		#invoice #doc .doc_total,
		#invoice .button.primary_button {
			background-color: <?php echo esc_attr( $inv_color ); ?>;
		}

		#invoice.paid #doc .doc_total,
		#invoice .button.deposit_paid {
			background-color: <?php echo esc_attr( $est_color ); ?>;
		}

		#line_total {
			color: <?php echo esc_attr( $est_color ); ?>;
		}

		#invoice #line_total {
			color: <?php echo esc_attr( $inv_color ); ?>;
		}
		</style>
		<?php
}
add_action( 'si_head', 'si_original_theme_inject_css' );
