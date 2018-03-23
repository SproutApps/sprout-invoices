<?php

/**
 * Load the SI application
 * (function called at the bottom of this page)
 *
 * @package Sprout_Invoices
 * @return void
 */
function sprout_invoices_load() {
	if ( class_exists( 'Sprout_Invoices' ) ) {
		error_log( '** Sprout_Invoices Already Loaded **' );
		return; // already loaded, or a name collision
	}

	do_action( 'sprout_invoices_preload' );

	//////////
	// Load //
	//////////

	// Master class
	require_once SI_PATH.'/Sprout_Invoices.class.php';

	// base classes
	require_once SI_PATH.'/models/_Model.php';
	require_once SI_PATH.'/controllers/_Controller.php';
	do_action( 'si_require_base_classes' );

	// models
	require_once SI_PATH.'/models/Client.php';
	require_once SI_PATH.'/models/Estimate.php';
	require_once SI_PATH.'/models/Invoice.php';
	require_once SI_PATH.'/models/Notification.php';
	require_once SI_PATH.'/models/Payment.php';
	require_once SI_PATH.'/models/Record.php';

	// Premium models
	require_once SI_PATH.'/models/Project.php';

	// i18n
	require_once SI_PATH.'/controllers/i18n/Countries_States.php';
	require_once SI_PATH.'/controllers/i18n/Locales.php';

	do_action( 'si_require_model_classes' );

	/////////////////
	// Controllers //
	/////////////////

	// settings
	require_once SI_PATH.'/controllers/admin/Settings.php';

	if ( ! class_exists( 'SA_Settings_API' ) ) {
		require_once SI_PATH.'/controllers/admin/Settings_API.php';
	}

	require_once SI_PATH.'/controllers/admin/Capabilities.php';

	require_once SI_PATH.'/controllers/admin/Help.php';

	// json api
	// require_once SI_PATH.'/controllers/api/JSON_API.php';

	// checkouts
	require_once SI_PATH.'/controllers/checkout/Checkouts.php';

	// clients
	require_once SI_PATH.'/controllers/clients/Clients.php';

	// developer logs
	require_once SI_PATH.'/controllers/developer/Logs.php';

	// Estimates
	require_once SI_PATH.'/controllers/estimates/Estimate_Submission.php';
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/estimates/Estimate_Submission_Premium.php' ) ) {
		require_once SI_PATH.'/controllers/estimates/Estimate_Submission_Premium.php';
	}
	require_once SI_PATH.'/controllers/estimates/Estimates.php';
	require_once SI_PATH.'/controllers/estimates/Estimates_Admin.php';
	require_once SI_PATH.'/controllers/estimates/Estimates_Edit.php';
	require_once SI_PATH.'/controllers/estimates/Estimates_Records.php';
	require_once SI_PATH.'/controllers/estimates/Estimates_Template.php';
	require_once SI_PATH.'/controllers/estimates/Estimates_Scheduled.php';
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/estimates/Estimates_Premium.php' ) ) {
		require_once SI_PATH.'/controllers/estimates/Estimates_Premium.php';
	}

	// invoices
	require_once SI_PATH.'/controllers/invoices/Invoices.php';
	require_once SI_PATH.'/controllers/invoices/Invoices_Admin.php';
	require_once SI_PATH.'/controllers/invoices/Invoices_Edit.php';
	require_once SI_PATH.'/controllers/invoices/Invoices_Records.php';
	require_once SI_PATH.'/controllers/invoices/Invoices_Template.php';
	require_once SI_PATH.'/controllers/invoices/Invoices_Deposit.php';
	require_once SI_PATH.'/controllers/invoices/Invoices_Scheduled.php';
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/invoices/Invoices_Premium.php' ) ) {
		require_once SI_PATH.'/controllers/invoices/Invoices_Premium.php';
	}

	// Line Items
	require_once SI_PATH.'/controllers/line-items/Line_Items.php';

	// Fees
	require_once SI_PATH.'/controllers/fees/Fees.php';
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/fees/Shipping_Fee.php' ) ) {
		require_once SI_PATH.'/controllers/fees/Shipping_Fee.php';
	}

	// notifications
	require_once SI_PATH.'/controllers/notifications/Notifications_Control.php';
	require_once SI_PATH.'/controllers/notifications/Notifications.php';
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/notifications/Notifications_Premium.php' ) ) {
		require_once SI_PATH.'/controllers/notifications/Notifications_Premium.php';
	}
	require_once SI_PATH.'/controllers/notifications/Notifications_Admin_Table.php';

	require_once SI_PATH.'/controllers/notifications/Notifications_Test.php';

	// payment processing
	require_once SI_PATH.'/controllers/payment-processing/Payment_Processors.php';
	require_once SI_PATH.'/controllers/payment-processing/Credit_Card_Processors.php';
	require_once SI_PATH.'/controllers/payment-processing/Offsite_Processors.php';

	// payment processors
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/payment-processing/processors/SI_Paypal_EC.php' ) ) {
		require_once SI_PATH.'/controllers/payment-processing/processors/SI_Paypal_EC.php';
	}
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/payment-processing/processors/SI_Paypal_Pro.php' ) ) {
		require_once SI_PATH.'/controllers/payment-processing/processors/SI_Paypal_Pro.php';
	}
	require_once SI_PATH.'/controllers/payment-processing/processors/SI_Checks.php';
	require_once SI_PATH.'/controllers/payment-processing/processors/SI_PO.php';
	require_once SI_PATH.'/controllers/payment-processing/processors/SI_BACS.php';
	require_once SI_PATH.'/controllers/payment-processing/processors/SI_Admin_Payment.php';

	require_once SI_PATH.'/bundles/Addon_Payment_Processors.php';

	do_action( 'si_payment_processors_loaded' );

	// payments
	require_once SI_PATH.'/controllers/payments/Payments.php';
	require_once SI_PATH.'/controllers/payments/Payments_Admin_Table.php';

	// Projects
	require_once SI_PATH.'/controllers/projects/Projects.php';
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/projects/Projects_Premium.php' ) ) {
		require_once SI_PATH.'/controllers/projects/Projects_Premium.php';
	}

	// internal records
	require_once SI_PATH.'/controllers/records/Internal_Records.php';
	require_once SI_PATH.'/controllers/records/Records_Admin_Table.php';

	// reporting
	require_once SI_PATH.'/controllers/reporting/Dashboard.php';
	require_once SI_PATH.'/controllers/reporting/Reporting.php';
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/reporting/Reporting_Premium.php' ) ) {
		require_once SI_PATH.'/controllers/reporting/Reporting_Premium.php';
	}
	require_once SI_PATH.'/controllers/templating/Templating.php';

	require_once SI_PATH.'/controllers/templating/Customizer.php';

	// updates
	if ( ! SI_FREE_TEST && file_exists( SI_PATH.'/controllers/updates/Updates.php' ) ) {
		require_once SI_PATH.'/controllers/updates/Updates.php';
	}
	if ( file_exists( SI_PATH.'/controllers/updates/Free_License.php' ) ) {
		require_once SI_PATH.'/controllers/updates/Free_License.php';
	}
	if ( file_exists( SI_PATH.'/controllers/updates/Pro.php' ) ) {
		require_once SI_PATH.'/controllers/updates/Pro.php';
	}

	// importers
	require_once SI_PATH.'/importers/Importer.php';
	require_once SI_PATH.'/importers/Freshbooks.php';
	require_once SI_PATH.'/importers/Harvest.php';
	require_once SI_PATH.'/importers/Sliced-Invoices.php';
	require_once SI_PATH.'/importers/WP-Invoice.php';
	require_once SI_PATH.'/importers/CSV.php';

	do_action( 'si_importers_loaded' );

	// Fix others problems
	require_once SI_PATH.'/controllers/compat/Compatibility.php';
	require_once SI_PATH.'/controllers/admin/Destroyer_of_Worlds.php';

	// all done
	do_action( 'si_require_controller_classes' );

	// Template tags
	require_once SI_PATH.'/template-tags/estimates.php';
	require_once SI_PATH.'/template-tags/clients.php';
	require_once SI_PATH.'/template-tags/forms.php';
	require_once SI_PATH.'/template-tags/invoices.php';
	require_once SI_PATH.'/template-tags/line-items.php';
	require_once SI_PATH.'/template-tags/projects.php';
	require_once SI_PATH.'/template-tags/ui.php';
	require_once SI_PATH.'/template-tags/utility.php';
	require_once SI_PATH.'/template-tags/docs.php';

	// l18n
	require_once SI_PATH.'/languages/SI_l10n.php';
	require_once SI_PATH.'/languages/SI_Strings.php';

	// i18n & l10n
	SI_l10n::init();
	SI_Strings::load_additional_strings();
	SI_Locales::init();
	SI_Countries_States::init();

	///////////////////
	// init() models //
	///////////////////
	do_action( 'si_models_init' );
	SI_Post_Type::init(); // _Model

	SI_Record::init();
	SI_Notification::init();
	SI_Invoice::init();
	SI_Estimate::init();
	SI_Client::init();
	SI_Payment::init();

	SI_Project::init();

	/////////////////////////
	// init() controllers //
	/////////////////////////
	do_action( 'si_controllers_init' );
	SI_Controller::init();
	SA_Settings_API::init();
	SI_Templating_API::init();
	SI_Customizer::init();

	SI_Admin_Capabilities::init();

	// updates
	if ( ! SI_FREE_TEST && class_exists( 'SI_Updates' ) ) {
		SI_Updates::init();
	}
	if ( ! SI_FREE_TEST && class_exists( 'SI_Pro' ) ) {
		SI_Pro::init();
	}
	if ( ! SI_PRO && class_exists( 'SI_Free_License' ) ) {
		SI_Free_License::init();
	}

	// api
	// SI_JSON_API::init();

	// reports
	SI_Dashboard::init();
	SI_Reporting::init();
	if ( ! SI_FREE_TEST && class_exists( 'SI_Reporting_Premium' ) ) {
		SI_Reporting_Premium::init();
	}

	// records and logs
	SI_Internal_Records::init();
	SI_Dev_Logs::init();

	// settings
	SI_Admin_Settings::init();

	// payments and processing
	SA_Init_Addon_Processors::init();
	SI_Payment_Processors::init();
	SI_Payments::init();

	// notifications
	SI_Notifications::init(); // Hooks come before parent class.
	if ( ! SI_FREE_TEST && class_exists( 'SI_Notifications_Premium' ) ) {
		SI_Notifications_Premium::init();
	}
	SI_Notifications_Control::init();
	SI_Notifications_Test::init();

	// clients
	SI_Clients::init();

	// estimates
	SI_Estimates::init();
	if ( ! SI_FREE_TEST && class_exists( 'SI_Estimates_Premium' ) ) {
		SI_Estimates_Premium::init();
	}
	if ( ! SI_FREE_TEST && class_exists( 'SI_Estimates_Submission_Premium' ) ) {
		SI_Estimates_Submission_Premium::init();
	}
	SI_Estimate_Submissions::init();
	SI_Estimates_Admin::init();
	SI_Estimates_Edit::init();
	SI_Estimates_Scheduled::init();
	SI_Estimates_Template::init();
	SI_Estimates_Records::init();

	// checkouts
	SI_Checkouts::init();

	// invoices
	SI_Invoices::init();
	SI_Invoices_Admin::init();
	SI_Invoices_Edit::init();
	SI_Invoices_Scheduled::init();
	SI_Invoices_Template::init();
	SI_Invoices_Records::init();
	SI_Invoices_Deposit::init();
	if ( ! SI_FREE_TEST && class_exists( 'SI_Invoices_Premium' ) ) {
		SI_Invoices_Premium::init();
	}

	// Fees
	SI_Fees::init();
	if ( ! SI_FREE_TEST && class_exists( 'SI_Shipping_Fee' ) ) {
		SI_Shipping_Fee::init();
	}

	// Line items
	SI_Line_Items::init();

	// projects
	SI_Projects::init();
	if ( ! SI_FREE_TEST && class_exists( 'SI_Projects_Premium' ) ) {
		SI_Projects_Premium::init();
	}

	// importer
	SI_Importer::init();

	// help
	SI_Help::init();

	// Compat
	SI_Compatibility::init();

	// addons
	require_once SI_PATH.'/bundles/Addons.php';
	require_once SI_PATH.'/bundles/updates/edd_plugin_updater.class.php';
	SA_Addons::init();

	SI_Killing_Machine::init();

	do_action( 'sprout_invoices_loaded' );
}

function sprout_invoices_delayed_load() {
	do_action( 'si_delayed_load' );
}
