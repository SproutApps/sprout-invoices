<?php 

/**
 * Fixes other plugins issues.
 *
 * @package Sprout_Invoice
 * @subpackage Compatibility
 */
class SI_Compatibility extends SI_Controller {

	public static function init() {
		// WP SEO
		add_filter( 'init', array( __CLASS__, 'prevent_wpseo_from_being_assholes_about_admin_columns' ), 10 );
		add_filter( 'add_meta_boxes', array( __CLASS__, 'prevent_wpseo_from_being_assholes_about_private_cpts_metaboxes' ), 10 );
		// Gravity Forms fix
		add_filter( 'gform_display_add_form_button', array( __CLASS__, 'si_maybe_remove_gravity_forms_add_button' ), 10, 1 );

		if ( class_exists('acf') ) {
			// ACF Fix
			add_filter( 'post_submitbox_start', array( __CLASS__, '_acf_post_submitbox_start' ) );
		}
	}

	public static function prevent_wpseo_from_being_assholes_about_admin_columns() {
		if ( self::is_si_admin() ) {
			// Disable Yoast admin columns.
			add_filter( 'wpseo_use_page_analysis', '__return_false' );
		}
	}

	public static function prevent_wpseo_from_being_assholes_about_private_cpts_metaboxes() {
		if ( self::is_si_admin() ) {
			// Disable Yoast metabox
			$cpts = array( SI_Invoice::POST_TYPE, SI_Estimate::POST_TYPE, SI_Client::POST_TYPE, SI_Project::POST_TYPE );
			foreach ( $cpts as $cpt ) {
				remove_meta_box( 'wpseo_meta', $cpt, 'normal' );
			}
		}
	}

	public static function si_maybe_remove_gravity_forms_add_button( $is_post_edit_page ) {
		if ( is_admin() ) {
		    if ( strpos( get_post_type(), 'sa_' ) !== false ) {
		    	return false;
		    }
		}
		return $is_post_edit_page;
	}

	function _acf_post_submitbox_start() {
		if ( ! SI_Controller::is_si_admin() ) {
			return;
		}
		?>
			<script type="text/javascript">
			(function($){
				acf.add_action('submit', function( $el ){
					$('input[type="submit"]').removeClass('disabled button-disabled button-primary-disabled');
				});
			})(jQuery);
			</script>
		<?php
	}

}
