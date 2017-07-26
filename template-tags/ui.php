<?php

if ( ! function_exists( 'si_doc_header_logo' ) ) :
	/**
 * Get the document logo from the theme or the default logo from the plugin.
 * @return  string
 */
	function si_doc_header_logo_url() {
		$fullpath = si_locate_file( array(
			'logo.png',
			'logo.jpg',
			'logo.gif',
		) );
		$path = str_replace( WP_CONTENT_DIR, '', $fullpath );
		return content_url( $path );
	}
endif;

if ( ! function_exists( 'si_locate_file' ) ) :
	/**
 * Locate the template file, either in the current theme or the public views directory
 *
 * @static
 * @param array   $possibilities
 * @return string
 */
	function si_locate_file( $possibilities = array() ) {
		$possibilities = apply_filters( 'si_locate_file_possibilites', $possibilities );

		// check if the theme has an override for the template
		$theme_overrides = array();
		foreach ( $possibilities as $p ) {
			$theme_overrides[] = SI_Controller::get_template_path().'/'.$p;
		}
		if ( $found = locate_template( $theme_overrides, false ) ) {
			return $found;
		}

		// check for it in the templates directory
		foreach ( $possibilities as $p ) {
			if ( file_exists( SI_PATH.'/views/templates/'.$p ) ) {
				return SI_PATH.'/views/templates/'.$p;
			}
		}

		// we don't have it
		return '';
	}
endif;

if ( ! function_exists( 'si_address' ) ) :
	/**
 * Echo a formatted address
 * @param  array  $address
 * @return
 */
	function si_address( $address = array() ) {
		$address = si_format_address( $address, 'string', '<br/>' );
		return apply_filters( 'si_address', sprintf( '<address class="vcard"><span>%s</span></address>', $address ), $address );
	}
endif;

if ( ! function_exists( 'si_get_company_email' ) ) :
	/**
 * Get the site company email
 * @param  integer $id
 * @return string
 */
	function si_get_company_email() {
		$address = si_get_doc_address();
		$email = ( isset( $address['email'] ) ) ? $address['email'] : get_bloginfo( 'email' );
		return apply_filters( 'si_get_company_email', $email );
	}
endif;

if ( ! function_exists( 'si_company_email' ) ) :
	/**
 * Echo the site company email
 * @param  integer $id
 * @return string
 */
	function si_company_email() {
		echo apply_filters( 'si_company_email', si_get_company_email() );
	}
endif;

if ( ! function_exists( 'si_get_company_name' ) ) :
	/**
 * Get the site company name
 * @param  integer $id
 * @return string
 */
	function si_get_company_name() {
		$address = si_get_doc_address();
		$name = ( isset( $address['name'] ) ) ? $address['name'] : get_bloginfo( 'name' );
		return apply_filters( 'si_get_company_name', $name );
	}
endif;

if ( ! function_exists( 'si_company_name' ) ) :
	/**
 * Echo the site company name
 * @param  integer $id
 * @return string
 */
	function si_company_name() {
		echo apply_filters( 'si_company_name', si_get_company_name() );
	}
endif;

if ( ! function_exists( 'si_get_doc_address' ) ) :
	/**
 * Get the formatted site address
 * @param  integer $id
 * @return string
 */
	function si_get_doc_address() {
		return SI_Admin_Settings::get_site_address();
	}
endif;

if ( ! function_exists( 'si_doc_address' ) ) :
	/**
 * Echo a formatted site address
 * @param  integer $id
 * @return string
 */
	function si_doc_address() {
		echo apply_filters( 'si_doc_address', si_address( si_get_doc_address() ) );
	}
endif;


///////////
// misc. //
///////////


if ( ! function_exists( 'si_display_messages' ) ) :
	function si_display_messages( $type = '' ) {
		print SI_Controller::display_messages( $type );
	}
endif;

function si_get_credit_card_img( $cc_type ) {
	return SI_RESOURCES.'/front-end/img/'.$cc_type.'.png';
}

if ( ! function_exists( 'wp_editor_styleless' ) ) :
	/**
 * Removes those pesky theme styles from the theme.
 * @see  wp_editor()
 * @return wp_editor()
 */
	function wp_editor_styleless( $content, $editor_id, $settings = array() ) {
		add_filter( 'mce_css', '__return_null' );
		$return = wp_editor( $content, $editor_id, $settings );
		remove_filter( 'mce_css', '__return_null' );
		return $return;
	}
endif;
