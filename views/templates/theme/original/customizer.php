<?php

function si_original_theme_customizer_options( $wp_customize ) {
		// Highlight and link color
		$wp_customize->add_setting( 'si_invoices_color', array(
		    'default'           => '#FF5B4D',
		    'sanitize_callback' => 'sanitize_hex_color',
		    //'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_invoices_color', array(
		    'label'	   => __( 'Invoice Highlight Color (Original Theme)', 'sprout-invoices' ),
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
		    'label'	   => __( 'Estimate Highlight Color (Original Theme)', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_estimates_color',
		) ) );
}
add_action( 'customize_register', 'si_original_theme_customizer_options' );
