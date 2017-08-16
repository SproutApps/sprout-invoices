<?php

function si_default_theme_customizer_options( $wp_customize ) {

		$wp_customize->add_setting( 'si_paybar_top', array(
		    'default'        => false,
		) );

		$wp_customize->add_control( 'si_paybar_top', array(
		    'type' => 'checkbox',
		    'label'	   => __( 'Move Action Bar', 'sprout-invoices' ),
		    'description' => __( 'Sticks the action bar to the top instead of the bottom of the page.' ),
		    'section'  => 'si_custommizer_section',
		) );

		// Invoice main color
		$wp_customize->add_setting( 'si_inv_primary_color', array(
		    'default'           => '#4086b0',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_inv_primary_color', array(
		    'label'	   => __( 'Invoice Primary Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_inv_primary_color',
		) ) );

		// Invoice secondary color
		$wp_customize->add_setting( 'si_inv_secondary_color', array(
		    'default'           => '#438cb7',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_inv_secondary_color', array(
		    'label'	   => __( 'Invoice Header Background', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_inv_secondary_color',
		) ) );

		// Estimate main color
		$wp_customize->add_setting( 'si_est_primary_color', array(
		    'default'           => '#4086b0',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_est_primary_color', array(
		    'label'	   => __( 'Estimate Primary Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_est_primary_color',
		) ) );

		// Estimate secondary color
		$wp_customize->add_setting( 'si_est_secondary_color', array(
		    'default'           => '#438cb7',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_est_secondary_color', array(
		    'label'	   => __( 'Estimate Header Background', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_est_secondary_color',
		) ) );
}
add_action( 'customize_register', 'si_default_theme_customizer_options' );
