<?php 

/**
* Addons: Admin purchasing, check for updates, etc.
* 
*/
class SA_Addons extends SI_Controller {
	
	public static function init() {
		
		/**
		 * `!apply_filters( 'is_bundle_addon', FALSE )` is used within plugins
		 * to determine if the add-on is a plugin or loaded as a bundle.
		 */
		add_filter( 'is_bundle_addon', '__return_true' );
		
		self::load_addons();

		remove_all_filters( 'is_bundle_addon' );
	}

	public static function load_addons() {
		if ( SI_FREE_TEST ) {
			return;
		}
		
		$addons = self::get_addons();
		foreach ( $addons as $path => $data ) {
			require SI_PATH.'/add-ons/' . $path;
		}
	}

	private static function get_addons( $addon_folder = '' ) {

		if ( ! $cache_addons = wp_cache_get( 'si_addons', 'si_addons' ) )
			$cache_addons = array();

		if ( isset($cache_addons[ $addon_folder ]) )
			return $cache_addons[ $addon_folder ];

		$wp_addons = array();
		$addon_root = SI_PATH . '/add-ons/';

		if ( !empty($addon_folder) )
			$addon_root .= $addon_folder;

		// Files in wp-content/addons directory
		$addons_dir = @ opendir( $addon_root );

		$addon_files = array();
		if ( $addons_dir ) {
			while (($file = readdir( $addons_dir ) ) !== false ) {
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
			return $wp_addons;

		foreach ( $addon_files as $addon_file ) {
			if ( !is_readable( "$addon_root/$addon_file" ) )
				continue;

			$addon_data = self::get_plugin_data( "$addon_root/$addon_file" );

			if ( empty ( $addon_data['Name'] ) )
				continue;

			$wp_addons[plugin_basename( $addon_file )] = $addon_data;
		}

		$cache_addons[ $addon_folder ] = $wp_addons;
		wp_cache_set('si_addons', $cache_addons, 'si_addons');

		return $wp_addons;
	}

	public static function get_plugin_data( $plugin_file ) {
		$default_headers = array(
			'Name' => 'Plugin Name',
			'PluginURI' => 'Plugin URI',
			'Version' => 'Version',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
		);

		$plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );

		$plugin_data['Title']      = $plugin_data['Name'];
		$plugin_data['AuthorName'] = $plugin_data['Author'];

		return $plugin_data;
	}
	
}
