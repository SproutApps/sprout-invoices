<?php

// remove original theme options
remove_action( 'customize_register', 'si_original_theme_customizer_options' );

// add new options
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
		    'label'	   => __( 'Invoice Primary Background Color', 'sprout-invoices' ),
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

		// Invoice text color
		$wp_customize->add_setting( 'si_inv_text_color', array(
		    'default'           => '',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_inv_text_color', array(
		    'label'	   => __( 'Invoice Primary Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_inv_text_color',
		) ) );

		// Invoice text color
		$wp_customize->add_setting( 'si_inv_title_text_color', array(
		    'default'           => '',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_inv_title_text_color', array(
		    'label'	   => __( 'Invoice Accent Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_inv_title_text_color',
		) ) );

		// Invoice accent background color
		$wp_customize->add_setting( 'si_inv_paybar_background_color', array(
		    'default'           => '',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_inv_paybar_background_color', array(
		    'label'	   => __( 'Accent Background Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_inv_paybar_background_color',
		) ) );

		// Invoice accent color
		$wp_customize->add_setting( 'si_inv_paybar_color', array(
		    'default'           => '',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_inv_paybar_color', array(
		    'label'	   => __( 'Accent Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_inv_paybar_color',
		) ) );

		///////////////
		// Estimates //
		///////////////

		// Estimate main color
		$wp_customize->add_setting( 'si_est_primary_color', array(
		    'default'           => '#4086b0',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_est_primary_color', array(
		    'label'	   => __( 'Estimate Primary Background Color', 'sprout-invoices' ),
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

		// Estimate text color
		$wp_customize->add_setting( 'si_est_text_color', array(
		    'default'           => '',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_est_text_color', array(
		    'label'	   => __( 'Estimate Primary Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_est_text_color',
		) ) );

		// Estimate text color
		$wp_customize->add_setting( 'si_est_title_text_color', array(
		    'default'           => '',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_est_title_text_color', array(
		    'label'	   => __( 'Estimate Accent Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_est_title_text_color',
		) ) );

		// Estimate accent background color
		$wp_customize->add_setting( 'si_est_paybar_background_color', array(
		    'default'           => '',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_est_paybar_background_color', array(
		    'label'	   => __( 'Accent Background Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_est_paybar_background_color',
		) ) );

		// Estimate accent color
		$wp_customize->add_setting( 'si_est_paybar_color', array(
		    'default'           => '',
		    'sanitize_callback' => 'sanitize_hex_color',
		    // 'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_est_paybar_color', array(
		    'label'	   => __( 'Accent Text Color', 'sprout-invoices' ),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_est_paybar_color',
		) ) );
}
add_action( 'customize_register', 'si_default_theme_customizer_options' );
