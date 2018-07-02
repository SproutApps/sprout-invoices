<?php

/**
 * Payment processor controller
 *
 * @package Sprout_Invoice
 * @subpackage Payments
 */
class SI_Importer extends SI_Controller {
	const SETTINGS_PAGE = 'import';
	const PROCESS_ACTION = 'start_import';
	const IMPORTER_OPTION = 'import_selection';
	const RECORD = 'si_import';
	const USER_META_PHONE = 'sa_phone_number';
	const USER_META_OFFICE_PHONE = 'sa_office_phone_number';
	const USER_META_TITLE = 'sa_title';
	const PROGRESS_TRACKER = 'si_import_progress';
	private static $importers = array(); // added by each importer processor

	public static function init() {
		// Admin
		add_filter( 'si_sub_admin_pages', array( __CLASS__, 'register_admin_page' ) );

		// Hook into form submission
		add_action( 'init', array( __CLASS__, 'process_importer' ) );

		// Help Sections
		add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

		// AJAX
		add_action( 'wp_ajax_si_import', array( __CLASS__, 'maybe_init_import' ) );
		add_action( 'wp_ajax_nopriv_si_import', array( __CLASS__, 'maybe_init_import' ) );
	}

	public static function get_importers() {
		return apply_filters( 'si_importers', self::$importers );
	}



	////////////
	// admin //
	////////////

	public static function register_admin_page( $admin_pages = array() ) {
		$admin_pages[ self::SETTINGS_PAGE ] = array(
			'slug' => self::SETTINGS_PAGE,
			'title' => __( 'Import', 'sprout-invoices' ),
			'menu_title' => __( 'Import', 'sprout-invoices' ),
			'weight' => 25,
			'section' => 'import',
			'callback' => array( __CLASS__, 'render_importer_page' ),
			'tab_only' => true,
			);
		return $admin_pages;
	}

	public static function render_importer_page() {
		$settings = self::importer_options();
		uasort( $settings, array( __CLASS__, 'sort_by_weight' ) );
		$args = array(
			'settings' => $settings,
		);
		self::load_view( 'admin/importer/admin', $args );
	}

	public static function importer_options() {
		// Settings
		$options = array(
			'si_importer_selection' => array(
				'weight' => 0,
				'settings' => array(
					self::IMPORTER_OPTION => array(
						'label' => null,
						'option' => array( get_class(), 'display_importer_options' ),
					),
				),
			),
		);
		return apply_filters( 'si_importer_options', $options );
	}

	public static function display_importer_options() {
		$importer_options = self::importer_options();
		$importers = self::get_importers();
		uasort( $importers, array( __CLASS__, 'sort_by_weight' ) );

		$importing = false;
		if ( isset( $_POST[ self::PROCESS_ACTION ] ) && wp_verify_nonce( $_POST[ self::PROCESS_ACTION ], self::PROCESS_ACTION ) ) {
			$importing = apply_filters( 'si_importing_action', true );
		}

		if ( ! $importing ) {
			self::load_view( 'admin/importer/settings', array(
				'importer_options' => $importer_options,
				'importers' => $importers,
			), false );
		} else {
			self::load_view( 'admin/importer/importing', array(
				'importer_options' => $importer_options,
				'importers' => $importers,
			), false );
		}
	}

	/**
	 * Settings page
	 *
	 * @param  boolean $prefixed
	 * @return string
	 */
	public static function get_settings_page( $prefixed = true ) {
		return ( $prefixed ) ? self::APP_DOMAIN . '/' . self::SETTINGS_PAGE : self::SETTINGS_PAGE ;
	}

	/**
	 * Importers register by adding themselves.
	 *
	 * @param class  $class
	 * @param string $label Name of processor
	 */
	protected static function add_importer( $class, $label ) {
		self::$importers[ $class ] = $label;
	}

	/**
	 * After importer is selected this will hook into the class method to
	 * start the import process
	 */
	public static function process_importer() {
		if ( ! current_user_can( 'manage_sprout_invoices_importer' ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			return;
		}

		if ( isset( $_GET['page'] ) && 'sprout-invoices-import' === $_GET['page'] ) {
			update_option( self::PROGRESS_TRACKER, true );
		}

		if ( isset( $_POST['importer'] ) && '' !== $_POST['importer'] ) {
			$class = sanitize_text_field( wp_unslash( $_POST['importer'] ) );
			if ( method_exists( $class, 'init' ) ) {
				call_user_func( array( $class, 'init' ) );
			}
		}
	}

	/**
	 * Verify request and start an import method
	 *
	 * @return
	 */
	public static function maybe_init_import() {
		if ( ! isset( $_REQUEST['importer'] ) ) {
			self::ajax_fail( 'Forget something?' );
		}

		if ( ! isset( $_REQUEST['method'] ) ) {
			self::ajax_fail( 'Forget something?' );
		}

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' );
		}

		$class = sanitize_text_field( $_REQUEST['importer'] );
		$method = 'import_' . sanitize_text_field( $_REQUEST['method'] );
		if ( method_exists( $class, $method ) ) {
			call_user_func( array( $class, $method ) );
		}
		exit();
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	protected function __clone() {
		// Cannot be cloned!
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}

	protected function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}

	protected function __construct() {
	}

	////////////////
	// Admin Help //
	////////////////


	public static function help_sections() {
		add_action( 'load-sprout-apps_page_sprout-apps/settings', array( __CLASS__, 'help_tabs' ) );
	}

	public static function help_tabs() {
		if ( isset( $_GET['tab'] ) && sanitize_key( $_GET['tab'] ) === self::SETTINGS_PAGE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
				'id' => 'importing-about',
				'title' => __( 'About Importing', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p><p><a href="%s">%s</a></p>', __( 'This feature provides a way for you to import data from external invoicing services, including Harvest, Freshbooks, or WP-Invoice.', 'sprout-invoices' ), __( ' If you have your data in one of these systems you can import all of your clients, contacts, estimates, invoices, and payments into Sprout Invoices.', 'sprout-invoices' ), si_get_sa_link( 'https://sproutapps.co/news/feature-spotlight-import-freshbooks-harvest-wp-invoice/' ), __( 'More Information', 'sprout-invoices' ) ),
			) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', __( 'For more information:', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/importing/', __( 'Documentation', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', si_get_sa_link( 'https://sproutapps.co/support/' ), __( 'Support', 'sprout-invoices' ) )
			);
		}
	}

	/////////////
	// Utility //
	/////////////

	public static function sanitize_subdomain( $url = '' ) {
		$parsed_url = parse_url( $url );
		if ( ! isset( $parsed_url['host'] ) ) { // the path was given
			return esc_attr( $url );
		}
		$host_segments = explode( '.', $parsed_url['host'] );

		if ( count( $host_segments ) <= 2 ) {
		    return esc_attr( $url ); // subdomain not given
		}

		return esc_attr( $host_segments[0] );
	}
}
