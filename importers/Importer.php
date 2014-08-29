<?php

/**
 * Payment processor controller
 *
 * @package Sprout_Invoice
 * @subpackage Payments
 */
class SI_Importer extends SI_Controller {
	const SETTINGS_PAGE = 'import';
	const RECORD = 'si_import';
	const USER_META_PHONE = 'sa_phone_number';
	const USER_META_OFFICE_PHONE = 'sa_office_phone_number';
	const USER_META_TITLE = 'sa_title';
	private static $importers = array(); // added by each importer processor

	public static function init() {
		// Admin
		self::register_importer_admin();
		// Hook into form submission
		add_action( 'init', array( __CLASS__, 'process_importer' ) );
	}

	public static function get_importers() {
		return apply_filters( 'si_importers', self::$importers );
	}

	/**
	 * Register the payment settings
	 * @return  
	 */
	public static function register_importer_admin() {

		// Addon page
		$args = array(
			'slug' => self::get_settings_page( FALSE ),
			'title' => self::__( 'Sprout Invoices Importing' ),
			'menu_title' => self::__( 'Import' ),
			'weight' => 50,
			'section' => 'settings',
			'tab_only' => TRUE,
			'callback' => array( __CLASS__, 'importer_page' ),
			);
		do_action( 'sprout_settings_page', $args );
	}
	
	/**
	 * Settings page 
	 * @param  boolean $prefixed 
	 * @return string            
	 */
	public static function get_settings_page( $prefixed = TRUE ) {
		return ( $prefixed ) ? self::TEXT_DOMAIN . '/' . self::SETTINGS_PAGE : self::SETTINGS_PAGE ;
	}

	/**
	 * Importers register by adding themselves.
	 * @param class $class 
	 * @param string $label Name of processor
	 */
	protected static function add_importer( $class, $label ) {
		self::$importers[$class] = $label;
	}

	public function importer_page() {
		self::load_view( 'admin/importer/importing', array(
				'importers' => self::get_importers()
			), FALSE );
	}

	/**
	 * After importer is selected this will hook into the class method to 
	 * start the import process
	 * 
	 */
	public static function process_importer() {
		if ( isset( $_POST['importer'] ) && $_POST['importer'] != '' ) {
			$class = $_POST['importer'];
			if ( method_exists( $class, 'init' ) ) {
				call_user_func( array( $class, 'init' ) );
			}
		}
	}


	/**
	 * Create a client 
	 * @param  array       $args 
	 * @return                    
	 */
	public static function new_client( $args = array() ) {	
		$address = array(
			'street' => isset( $args['street'] ) ?self::esc__( $args['contact_street']) : '',
			'city' => isset( $args['city'] ) ? self::esc__($args['contact_city']) : '',
			'zone' => isset( $args['zone'] ) ? self::esc__($args['contact_zone']) : '',
			'postal_code' => isset( $args['code'] ) ? self::esc__($args['contact_postal_code']) : '',
			'country' => isset( $args['country'] ) ? self::esc__($args['contact_country']) : '',
		);

		$args = array(
			'company_name' => isset( $args['name'] ) ? self::esc__($args['estimate_client_name']) : '',
			'website' => isset( $args['website'] ) ? self::esc__($args['website']) : '',
			'address' => $address
		);

		$client_id = SI_Client::new_client( $args );
		return $client_id;
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	protected function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}

	protected function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}

	protected function __construct() {
		//
	}

}
