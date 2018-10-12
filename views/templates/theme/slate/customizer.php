<?php

add_action( 'si_theme_customizer', 'si_slate_theme_customizer_options' );

function si_slate_theme_customizer_options( $wp_customize ) {

	if ( 'slate' === SI_Templating_API::get_invoice_theme_option() ) {
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
	}
	if ( 'slate' === SI_Templating_API::get_estimate_theme_option() ) {
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
}
