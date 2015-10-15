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
		// Store options
		self::register_settings();
	}

	///////////////
	// Settings //
	///////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			'estimate_submissions' => array(
				'title' => __( 'Lead Generation', 'sprout-invoices' ),
				'weight' => 5,
				'tab' => 'settings',
				'callback' => array( __CLASS__, 'submission_settings_description' ),
				'settings' => array(
					'default_submission_page' => array(
						'label' => __( 'Default Submission Form', 'sprout-invoices' ),
						'option' => array(
							'type' => 'bypass',
							'output' => sprintf( '<code>%s</code>', __( 'N/A in free version', 'sprout-invoices' ) ),
							'description' => sprintf( __( 'To get you started, Sprout Invoices provides a <a href="%s" target="_blank">fully customizable form</a> for estimate submissions. Simply add this shortcode to a page and an estimate submission form will be available to prospective clients. Notifications will be sent for each submission and a new estimate (and client) will be generated.', 'sprout-invoices' ), 'https://sproutapps.co/support/knowledgebase/sprout-invoices/advanced/customize-estimate-submission-form/' )
							)
						),
					'advanced_submission_integration_addon' => array(
						'label' => __( 'Gravity Forms and Ninja Forms Integration', 'sprout-invoices' ),
						'option' => array(
							'type' => 'bypass',
							'output' => self::advanced_form_integration_view(),
							'description' => sprintf( __( 'Instead of creating our own advanced form builder we\'ve integrated with the top WordPress form plugins. Make sure to read the <a href="%s" target="_blank">integration guide</a> to make the best use of your custom forms.', 'sprout-invoices' ), self::PLUGIN_URL.'/support/knowledgebase/sprout-invoices/advanced/customize-estimate-submission-form/' )
							)
						),
					)
				)
			);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );

	}

	public static function submission_settings_description() {
		echo '<div class="upgrade_message clearfix"><p><span class="icon-sproutapps-flat"></span>';
		printf( __( '<strong>Upgrade Available:</strong> Enable Estimate Submission integrations and support the future of Sprout Invoices by <a href="%s">upgrading</a>.', 'sprout-invoices' ), si_get_purchase_link() );
		echo '</p></div>';
		printf( __( '<p>Estimate submissions is the start of the <a href="%s">Sprout Invoices workflow</a>.</p>', 'sprout-invoices' ), self::PLUGIN_URL.'/sprout-invoices/' );
	}

	public static function advanced_form_integration_view() {
		// FUTURE pull add-on dynamically
		ob_start();
		?>
			<div class="sa_addon">
				<div class="add_on_img_wrap">
					<img class="sa_addon_img" src="<?php echo SI_RESOURCES . 'admin/img/gravity-ninja.png' ?>" />
					<a class="purchase_button button button-primary button-large" href="<?php echo self::PLUGIN_URL.'/marketplace/advanced-form-integration-gravity-ninja-forms/' ?>"><?php _e( '$0-5', 'sprout-invoices' ) ?></a>
				</div>
				<h4><?php _e( 'Advanced Form Integration with Gravity and Ninja Forms', 'sprout-invoices' ) ?></h4>
			</div>
		<?php
		return ob_get_clean();
	}

}