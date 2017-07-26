<?php

/**
 * Controller
 * Adds meta boxes to client admin.
 */
class SI_Customizer extends SI_Controller {

	public static function init() {
		add_action( 'customize_register', array( __CLASS__, 'customizer' ) );

		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );
	}


	//////////////
	// Utility //
	//////////////


	public static function add_link_to_admin_bar( $items ) {
		if ( is_single() && si_get_doc_context() ) {
			$items[] = array(
				'id' => 'customizer',
				'title' => __( 'Customize', 'sprout-invoices' ),
				'href' => esc_url_raw( add_query_arg( array( 'url' => urlencode( get_permalink() ) ), admin_url( 'customize.php' ) ) ),
				'weight' => 1000,
			);
		}
		return $items;
	}

	public static function customizer( $wp_customize ) {
		// Logo uploader
		$wp_customize->add_section( 'si_custommizer_section' , array(
			'title'       => __( 'Sprout Invoices', 'sprout-invoices' ),
			'priority'    => 300,
			'description' => __( 'Upload a logo to replace the default estimate/invoice logo.', 'sprout-invoices' ),
		) );

		$wp_customize->add_setting( 'si_logo', array(
			'sanitize_callback' => 'esc_url_raw',
			//'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'si_logo', array(
			'label'    => __( 'Invoice & Estimate Logo', 'sprout-invoices' ),
			'section'  => 'si_custommizer_section',
			'settings' => 'si_logo',
		) ) );

	}

	/**
	* Sanitizes a hex color. Identical to core's sanitize_hex_color(), which is not available on the wp_head hook.
	*
	* Returns either '', a 3 or 6 digit hex color (with #), or null.
	* For sanitizing values without a #, see sanitize_hex_color_no_hash().
	*
	* @since 1.7
	*/
	public static function sanitize_hex_color( $color ) {
		if ( '' === $color ) {
			return '';
		}
		// 3 or 6 hex digits, or the empty string.
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}
		return null;
	}

	public static function sanitize_checkbox( $checked = false ) {
		 return ( ( isset( $checked ) && true == $checked ) ? true : false );
	}
}
