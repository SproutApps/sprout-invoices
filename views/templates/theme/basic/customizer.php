<?php

add_action( 'si_theme_customizer', 'si_basic_theme_customizer_options' );

// add new options
function si_basic_theme_customizer_options( $wp_customize ) {
	if ( 'basic' === SI_Templating_API::get_invoice_theme_option() ) {

		$wp_customize->add_setting( 'si_basic_paybar_top', array(
		    'default'        => false,
		) );

		$wp_customize->add_control( 'si_basic_paybar_top', array(
		    'type' => 'checkbox',
		    'label'	   => __( 'Move Action Bar', 'sprout-invoices' ),
		    'description' => __( 'Sticks the action bar to the bottom instead of the top of the page.' ),
		    'section'  => 'si_custommizer_section',
		) );

		// Invoice main color
		$wp_customize->add_setting( 'si_basic_inv_primary_color', array(
		    'default'           => '#000000',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_basic_inv_primary_color', array(
		    'label'	   => __( 'Block Background Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_basic_inv_primary_color',
		) ) );

		// Invoice secondary color
		$wp_customize->add_setting( 'si_basic_inv_secondary_color', array(
		    'default'           => '#ffffff',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_basic_inv_secondary_color', array(
		    'label'	   => __( 'Block Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_basic_inv_secondary_color',
		) ) );

		// Invoice accent color
		$wp_customize->add_setting( 'si_basic_inv_paybar_color', array(
		    'default'           => '#67a4e3',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_basic_inv_paybar_color', array(
		    'label'	   => __( 'Button Background Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_basic_inv_paybar_color',
		) ) );

		// Invoice text color
		$wp_customize->add_setting( 'si_basic_inv_text_color', array(
		    'default'           => '#FFF',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_basic_inv_text_color', array(
		    'label'	   => __( 'Button Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_basic_inv_text_color',
		) ) );
	}
	if ( 'basic' === SI_Templating_API::get_estimate_theme_option() ) {
		///////////////
		// Estimates //
		///////////////

		// Invoice main color
		$wp_customize->add_setting( 'si_basic_est_primary_color', array(
		    'default'           => '#000000',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_basic_est_primary_color', array(
		    'label'	   => __( 'Estimate Block Background Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_basic_est_primary_color',
		) ) );

		// Invoice secondary color
		$wp_customize->add_setting( 'si_basic_est_secondary_color', array(
		    'default'           => '#ffffff',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_basic_est_secondary_color', array(
		    'label'	   => __( 'Estimate Block Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_basic_est_secondary_color',
		) ) );

		// Invoice accent color
		$wp_customize->add_setting( 'si_basic_est_paybar_color', array(
		    'default'           => '#67a4e3',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_basic_est_paybar_color', array(
		    'label'	   => __( 'Estimate Button Background Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_basic_est_paybar_color',
		) ) );

		// Invoice text color
		$wp_customize->add_setting( 'si_basic_est_text_color', array(
		    'default'           => '#FFF',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_basic_est_text_color', array(
		    'label'	   => __( 'Estimate Button Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_basic_est_text_color',
		) ) );
	}
}

