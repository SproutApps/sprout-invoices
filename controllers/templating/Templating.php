<?php


/**
 * Templating API
 * shortcodes, page creation, etc.
 *
 * @package Sprout_Invoice
 * @subpackage TEmplating
*/
class SI_Templating_API extends SI_Controller {
	const TEMPLATE_OPTION = '_doc_template_option';
	const FILTER_QUERY_VAR = 'filter_doc';

	private static $pages = array();
	private static $shortcodes = array();
	
	public static function get_template_pages() {
		return self::$pages;
	}

	public static function get_shortcodes() {
		return self::$shortcodes;
	}

	public static function init() {
		// Register Shortcodes
		add_action( 'sprout_shortcode', array( __CLASS__, 'register_shortcode' ), 0, 3 );
		// Add shortcodes
		add_action( 'init', array( __CLASS__, 'add_shortcodes' ) );

		// Determine template for estimates or invoices
		add_filter( 'template_include', array( __CLASS__, 'override_template' ) );

		add_action( 'doc_information_meta_box_client_row_last', array( __CLASS__, 'doc_template_selection' ) );
		add_action( 'si_save_line_items_meta_box', array( __CLASS__, 'save_doc_template_selection' ) );
	}

	/////////////////
	// Shortcodes //
	/////////////////

	/**
	 * Wrapper for the add_shorcode function WP provides
	 * @param string the shortcode
	 * @param array $callback
	 * @param array $args FUTURE
	 */
	public static function register_shortcode( $tag = '', $callback = array(), $args = array() ) {
		// FUTURE $args
		self::$shortcodes[$tag] = $callback;
	}

	/**
	 * Loop through registered shortcodes and use the WP function.
	 * @return  
	 */
	public static function add_shortcodes(){
		foreach ( self::$shortcodes as $tag => $callback ) {
			add_shortcode( $tag, $callback );
		}
	}


	////////////////////
	// Doc Templates //
	////////////////////

	/**
	 * Get all invoice templates within a user's theme
	 * @return array 
	 */
	public static function get_invoice_templates() {
		$templates = array( '' => self::__('Default Template') );
		$templates += self::get_doc_templates( 'invoice' );
		return $templates;
	}

	/**
	 * Get all estimate templates within a user's theme
	 * @return array 
	 */
	public static function get_estimate_templates() {
		$templates = array( '' => self::__('Default Template') );
		$templates += self::get_doc_templates( 'estimate' );
		return $templates;
	}

	/**
	 * Get the template for the current doc
	 * @param  string $doc 
	 * @return 
	 */
	public static function get_doc_current_template( $doc_id ) {
		$template_id = get_post_meta( $doc_id, self::TEMPLATE_OPTION, TRUE );
		return $template_id;
	}

	/**
	 * Save the template selection for a doc by post id
	 * @param  integer $post_id      
	 * @param  string  $doc_template 
	 * @return                 
	 */
	public static function save_doc_current_template( $doc_id = 0, $doc_template = '' ) {
		update_post_meta( $doc_id, self::TEMPLATE_OPTION, $doc_template );
	}

	/**
	 * Override the template and use something custom.
	 * @param  string $template 
	 * @return string           full path.
	 */
	public static function override_template( $template ) {

		// Invoicing
		if ( SI_Invoice::is_invoice_query() ) {
			if ( is_single() ) {
				$custom_template = self::get_doc_current_template( get_the_id() );
				$custom_path = ( $custom_template != '' ) ? 'invoice/'.$custom_template : '' ;
				$template = self::locate_template( array(
						$custom_path,
						'invoice.php',
						'invoice/invoice.php',
					), $template );
			} else {
				$status = get_query_var( self::FILTER_QUERY_VAR );
				$template = self::locate_template( array(
						'invoice/'.$status.'-invoices.php',
						$status.'-invoices.php',
						'invoices.php',
						'invoice/invoices.php'
					), $template );
			}
		}

		// Estimates
		if ( SI_Estimate::is_estimate_query() ) {
			if ( is_single() ) {
				$custom_template = self::get_doc_current_template( get_the_id() );
				$custom_path = ( $custom_template != '' ) ? 'estimate/'.$custom_template : '' ;
				$template = self::locate_template( array(
						$custom_path,
						'estimate.php',
						'estimate/estimate.php',
					), $template );
			} else {
				$status = get_query_var( self::FILTER_QUERY_VAR );
				$template = self::locate_template( array(
						'estimate/'.$status.'-estimates.php',
						$status.'-estimates.php',
						'estimates.php',
						'estimate/estimates.php'
					), $template );
			}
		}
		return apply_filters( 'si_doc_template', $template );
	}

	/////////////////
	// Meta boxes //
	/////////////////

	public static function doc_template_selection( $doc ) {
		if ( is_a( $doc, 'SI_Invoice' ) ) {
			$template_options = self::get_invoice_templates();
		}
		elseif ( is_a( $doc, 'SI_Estimate' ) ) {
			$template_options = self::get_estimate_templates();
		}
		if ( ! isset( $template_options ) || empty( $template_options ) ) {
			return;
		}
		$doc_type_name = ( is_a( $doc, 'SI_Invoice' ) ) ? 'invoice' : self::__('estimate') ;
		$template = self::get_doc_current_template( $doc->get_id() ); ?>
		<div class="misc-pub-section" data-edit-id="template" data-edit-type="select">
			<span id="template" class="wp-media-buttons-icon"><b><?php echo $template_options[$template] ?></b> <span title="<?php printf( self::__('Select a custom %s template.'), $doc_type_name ) ?>" class="helptip"></span></span>

			<?php if ( count( $template_options ) > 1 ): ?>
				<a href="#edit_template" class="edit-template hide-if-no-js edit_control" >
					<span aria-hidden="true"><?php si_e('Edit') ?></span> <span class="screen-reader-text"><?php si_e('Select different template') ?></span>
				</a>

				<div id="template_div" class="control_wrap hide-if-js">
					<div class="template-wrap">
						<select name="doc_template">
							<?php foreach ( $template_options as $template_key => $template_name ): ?>
								<?php printf( '<option value="%s" %s>%s</option>', $template_key, selected( $template_key, $template, FALSE ), $template_name ) ?>
							<?php endforeach ?>
						</select>
			 		</div>
					<p>
						<a href="#edit_template" class="save_control save-template hide-if-no-js button"><?php si_e('OK') ?></a>
						<a href="#edit_template" class="cancel_control cancel-template hide-if-no-js button-cancel"><?php si_e('Cancel') ?></a>
					</p>
			 	</div>
			<?php endif ?>
		</div>
		<?php
	}

	/**
	 * Save the template selection for a doc by post id
	 * @param  integer $post_id      
	 * @param  string  $doc_template 
	 * @return                 
	 */
	public static function save_doc_template_selection( $post_id = 0 ) {
		$doc_template = ( isset( $_POST['doc_template'] ) ) ? $_POST['doc_template'] : '' ;
		self::save_doc_current_template( $post_id, $doc_template );
	}

	//////////////
	// Utility //
	//////////////


	/**
	 * Search for files in the templates, within the sa directory.
	 * @return array 
	 */
	public static function get_sa_files( $type = '' ) {
		if ( $type != '' ) {
			$type = '/'.$type;
		}
		$theme = wp_get_theme();
		$files = (array) self::scandir( $theme->get_stylesheet_directory().'/'.self::get_template_path().$type, 'php', 1 );

		if ( $theme->parent() ){
			$files += (array) self::scandir( $theme->get_template_directory().'/'.self::get_template_path().$type, 'php', 1 );
		}

		return array_filter($files);
	}


	/**
	 * Returns the theme's doc templates.
	 *
	 * @since 3.4.0
	 * @access public
	 *
	 * @param WP_Post|null $post Optional. The post being edited, provided for context.
	 * @return array Array of page templates, keyed by filename, with the value of the translated header name.
	 */
	public static function get_doc_templates( $type = null ) {
		
		$doc_templates = FALSE;

		if ( ! is_array( $doc_templates ) ) {
			$doc_templates = array();

			$files = (array) self::get_sa_files( $type );

			foreach ( $files as $file => $full_path ) {
				if ( ! preg_match( '|SA Template Name:(.*)$|mi', file_get_contents( $full_path ), $header ) )
					continue;
				$doc_templates[ $file ] = _cleanup_header_comment( $header[1] );
			}

			// add cache
		}

		$return = apply_filters( 'theme_doc_templates', $doc_templates, $type );

		return array_intersect_assoc( $return, $doc_templates );
	}

	/**
	 * Scans a directory for files of a certain extension.
	 *
	 * Copied from WP_Theme
	 * @since 3.4.0
	 * @access private
	 *
	 * @param string $path Absolute path to search.
	 * @param mixed  Array of extensions to find, string of a single extension, or null for all extensions.
	 * @param int $depth How deep to search for files. Optional, defaults to a flat scan (0 depth). -1 depth is infinite.
	 * @param string $relative_path The basename of the absolute path. Used to control the returned path
	 * 	for the found files, particularly when this function recurses to lower depths.
	 */
	private static function scandir( $path, $extensions = null, $depth = 0, $relative_path = '' ) {
		if ( ! is_dir( $path ) )
			return false;

		if ( $extensions ) {
			$extensions = (array) $extensions;
			$_extensions = implode( '|', $extensions );
		}

		$relative_path = trailingslashit( $relative_path );
		if ( '/' == $relative_path )
			$relative_path = '';

		$results = scandir( $path );
		$files = array();

		foreach ( $results as $result ) {
			if ( '.' == $result[0] ) {
				continue;
			}
			if ( is_dir( $path . '/' . $result ) ) {
				if ( ! $depth || 'CVS' == $result ) {
					continue;
				}
				$found = self::scandir( $path . '/' . $result, $extensions, $depth - 1 , $relative_path . $result );
				$files = array_merge_recursive( $files, $found );
			} elseif ( ! $extensions || preg_match( '~\.(' . $_extensions . ')$~', $result ) ) {
				$files[ $relative_path . $result ] = $path . '/' . $result;
			}
		}

		return $files;
	}



}