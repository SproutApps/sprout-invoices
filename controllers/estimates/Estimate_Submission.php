<?php

/**
 * Estimates Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Estimates
 */
class SI_Estimate_Submissions extends SI_Controller {
	const SUBMISSION_SHORTCODE = 'estimate_submission';
	const DEFAULT_NONCE = 'si_estimate_submission';
	const SUBMISSION_UPDATE = 'estimate_submission';
	const SUBMISSION_SUCCESS_QV = 'success';

	public static function init() {
		// Register Settings
		add_filter( 'si_settings', array( __CLASS__, 'register_settings' ) );

	}

	///////////////
	// Settings //
	///////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings( $settings = array() ) {
		// Settings
		$settings['estimate_submissions'] = array(
				'title' => __( 'Submissions', 'sprout-invoices' ),
				'weight' => PHP_INT_MAX,
				'tab' => 'start',
				'settings' => array(
					'advanced_submission_integration_addon' => array(
						'label' => __( 'Gravity Forms, Ninja Forms, Formidable, and WP Forms Integrations', 'sprout-invoices' ),
						'option' => array(
							'type' => 'bypass',
							'output' => self::advanced_form_integration_view(),
							),
						),
					),
			);
		return $settings;

	}

	public static function advanced_form_integration_view() {
		ob_start();
		?>
			<div class="single_addon_wrap">
				<article class="type_addon marketplace_addon">
					<div class="section">
						<div class="img_wrap">
							<span class="bundled_addon"><?php _e( 'Free Download!', 'sprout-invoices' ) ?></span>
							<a href="<?php sa_link( 'https://sproutapps.co/marketplace/advanced-form-integration-gravity-ninja-forms/' ) ?>" class="si-button" target="_blank"><img src="<?php echo SI_RESOURCES . 'admin/img/gravity-ninja-formidible-wpforms.png' ?>" /></a>
						</div>
						<div class="info">
							<strong><?php _e( 'Advanced Form Integrations', 'sprout-invoices' ) ?></strong>							
							<div class="addon_description">
								<?php printf( __( 'Sprout Invoices has integrated with the top WordPress form builder plugins and made those integrations free to use.', 'sprout-invoices' ), si_get_sa_link( 'https://sproutapps.co/marketplace/advanced-form-integration-gravity-ninja-forms/' ) ) ?>
								<div class="addon_info_link">
									<a href="<?php sa_link( 'https://sproutapps.co/marketplace/advanced-form-integration-gravity-ninja-forms/' ) ?>" class="si-button" target="_blank"><?php _e( 'Learn More', 'sprout-invoices' ) ?></a>
								</div>
							</div>
						</div>
					</div>
				</article>
			</div><!-- #addons_admin-->
		<?php
		return ob_get_clean();
	}
}
