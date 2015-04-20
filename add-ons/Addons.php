<?php 

/**
* Addons: Admin purchasing, check for updates, etc.
* 
*/
class SA_Addons extends SI_Controller {
	const SETTINGS_PAGE = 'addons';
	const ADDON_OPTION = 'si_active_addons_v1';
	const API_CB = 'https://sproutapps.co/';
	private static $active_addons = array();
	
	public static function init() {
		self::$active_addons = get_option( self::ADDON_OPTION, self::default_active_addons() );
		if ( !is_array( self::$active_addons ) ) {
			self::$active_addons = self::default_active_addons();
		}
		self::register_importer_admin();
		self::load_addons();
	}

	////////////
	// Admin //
	////////////

	/**
	 * Register the addons management screen
	 * @return  
	 */
	public static function register_importer_admin() {

		// Addon page
		$args = array(
			'slug' => self::get_settings_page( FALSE ),
			'title' => self::__( 'Sprout Invoices Add-ons' ),
			'menu_title' => self::__( 'Add-ons' ),
			'weight' => 30,
			'section' => 'settings',
			'tab_only' => TRUE,
			'ajax' => TRUE,
			'callback' => array( __CLASS__, 'addons_admin' ),
			);
		do_action( 'sprout_settings_page', $args );

		// Settings
		$settings = array(
			'si_addons_mngt' => array(
				'title' => null,
				'weight' => 0,
				'tab' => self::get_settings_page( FALSE ),
				'settings' => array(
					self::ADDON_OPTION => array(
						'label' => NULL,
						'option' => array( get_class(), 'display_addons_options' ),
						'sanitize_callback' => array( __CLASS__, 'save_active_addons' )
					)
				)
			)
		);
		do_action( 'sprout_settings', $settings );
	}

	public static function display_addons_options() {
		$addons = self::get_addons();
		foreach ( $addons as $path => $details ) {
			$key = self::get_addon_key( $path, $details );
			$value = self::ADDON_OPTION.'['.$key.']';
			$title = ( isset( $details['PluginURI'] ) && $details['PluginURI'] != '' ) ? '<b><a href="'.$details['PluginURI'].'" target="_blank">'.$details['Name'].'</a></b>' : '<b>'.$details['Name'].'</b>';
			$title = str_replace( 'Sprout Invoices Add-on - ', '', $title );
			printf( '<span class="check_slider"><input type="checkbox" name="%1$s" id="%2$s" value="%1$s" %3$s /> <label for="%2$s" ></label></span> %4$s<p class="description">%5$s</p>', $value, $key, checked( TRUE, self::is_enabled( $key ), FALSE ), $title, $details['Description'] );
		}
	}

	public static function save_active_addons( $active_addons = array() ) {
		$sanitized_active_addons = array();
		foreach ( $active_addons as $key => $value ) {
			$sanitized_active_addons[] = $key;
		}
		return $sanitized_active_addons;
	}

	/**
	 * Default is to activate all add-ons
	 * @return array 
	 */
	public static function default_active_addons() {
		$addons = self::get_addons();
		$active_addons = array();
		foreach ( $addons as $path => $details ) {
			$key = self::get_addon_key( $path, $details );
			$active_addons[] = $key;
		}
		return $active_addons;
	}

	/**
	 * Is addon enabled
	 * @param string  $addon path of the plugin
	 * @return boolean
	 */
	public static function is_enabled( $addon_key ) {
		if ( in_array( $addon_key, self::$active_addons ) ) {
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Settings page 
	 * @param  boolean $prefixed 
	 * @return string            
	 */
	public static function get_settings_page( $prefixed = TRUE ) {
		return ( $prefixed ) ? self::TEXT_DOMAIN . '/' . self::SETTINGS_PAGE : self::SETTINGS_PAGE ;
	}

	public static function addons_admin() {
		$addons = self::get_addons();
		if ( !empty( $addons ) && !isset( $_GET['marketplace']) ) {
			self::load_view( 'admin/addons/options-admin', array(
					'addons' => self::get_addons()
				), FALSE );
		}
		else {
			$addons = self::get_marketplace_addons();
			self::load_view( 'admin/addons/marketplace', array(
				'addons' => $addons,
				), FALSE );
		}
		
	}



	public static function load_addons() {
		if ( SI_FREE_TEST ) {
			return;
		}
		/**
		 * `!apply_filters( 'is_bundle_addon', FALSE )` is used within plugins
		 * to determine if the add-on is a plugin or loaded as a bundle.
		 */
		add_filter( 'is_bundle_addon', '__return_true' );

		$addons = self::get_addons();
		foreach ( $addons as $path => $data ) {
			$key = self::get_addon_key( $path, $data );
			if ( in_array( $key, self::$active_addons ) ) {
				require SI_PATH.'/add-ons/' . $path;
			}
		}

		remove_all_filters( 'is_bundle_addon' );
	}

	private static function get_addons( $addon_folder = '' ) {

		if ( ! $cache_addons = wp_cache_get( 'si_addons', 'si_addons' ) )
			$cache_addons = array();

		if ( isset($cache_addons[ $addon_folder ]) )
			return apply_filters( 'si_get_addons', $cache_addons[ $addon_folder ], TRUE );

		$si_addons = array();
		$addon_root = SI_PATH . '/add-ons/';

		if ( !empty($addon_folder) )
			$addon_root .= $addon_folder;

		// Files in wp-content/addons directory
		$addons_dir = @ opendir( $addon_root );

		$addon_files = array();
		if ( $addons_dir ) {
			while ( ($file = readdir( $addons_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;
				if ( is_dir( $addon_root.'/'.$file ) ) {
					$addons_subdir = @ opendir( $addon_root.'/'.$file );
					if ( $addons_subdir ) {
						while (($subfile = readdir( $addons_subdir ) ) !== false ) {
							if ( substr($subfile, 0, 1) == '.' )
								continue;
							if ( substr($subfile, -4) == '.php' )
								$addon_files[] = "$file/$subfile";
						}
						closedir( $addons_subdir );
					}
				} else {
					if ( substr($file, -4) == '.php' )
						$addon_files[] = $file;
				}
			}
			closedir( $addons_dir );
		}

		if ( empty($addon_files) )
			return apply_filters( 'si_get_addons', $si_addons );

		foreach ( $addon_files as $addon_file ) {
			if ( !is_readable( "$addon_root/$addon_file" ) )
				continue;

			$addon_data = self::get_addon_data( "$addon_root/$addon_file" );

			if ( empty ( $addon_data['Name'] ) )
				continue;

			$si_addons[plugin_basename( $addon_file )] = $addon_data;
		}

		$cache_addons[ $addon_folder ] = $si_addons;
		wp_cache_set('si_addons', $cache_addons, 'si_addons');

		return apply_filters( 'si_get_addons', $si_addons );
	}

	public static function get_addon_data( $addon_file ) {
		$default_headers = array(
			'Name' => 'Plugin Name',
			'PluginURI' => 'Plugin URI',
			'Version' => 'Version',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
		);

		$addon_data = get_file_data( $addon_file, $default_headers, 'plugin' );

		$addon_data['Title']      = $addon_data['Name'];
		$addon_data['AuthorName'] = $addon_data['Author'];

		return $addon_data;
	}

	public static function get_addon_key( $addon_file = '', $data = array() ) {
		if ( empty( $data ) ) {
			$data = self::get_addon_data( $addon_file );
		}
		$key = str_replace( '.php', '', $data['Name'] ) . '-' . $data['Version'];
		return sanitize_title( $key );
	}

	//////////////////
	// Marketplace //
	//////////////////

	public static function get_marketplace_addons() {
		$cache_key = '_si_marketplace_addons_v'.self::SI_VERSION;
		$cached_addons = get_transient( $cache_key );
		if ( $cached_addons ) {
			if ( !empty( $cached_addons ) ) {
				return $cached_addons;
			}
		}
		
		$uid = ( class_exists('SI_Free_License') ) ? SI_Free_License::uid() : 0 ;
		$ref = ( $uid ) ? $uid : 'na' ;
		// data to send in our API request
		$api_params = array( 
			'action' => 'sa_marketplace_api',
			'item_name' => urlencode( self::PLUGIN_NAME ),
			'url' => home_url(),
			'uid' => $uid,
			'ref' => $ref
		);

		// Call the custom API.
		$response = wp_remote_get( esc_url( add_query_arg( $api_params, self::API_CB . 'wp-admin/admin-ajax.php' ) ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$marketplace_items = json_decode( wp_remote_retrieve_body( $response ) );
		set_transient( $cache_key, $marketplace_items, 60*60*24*5 );
		return $marketplace_items;
	}
	
}