<?php 

/**
 * Controller
 * Adds meta boxes to client admin.
 */
class SI_Customizer extends SI_Controller {

	public static function init() {
		add_action( 'customize_register', array( __CLASS__, 'customizer' ) );
		add_action( 'customize_preview_init', array( __CLASS__, 'customizer_js' ) );
		add_action( 'wp_head', array( __CLASS__, 'inject_css' ) );


		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );
	}


	//////////////
	// Utility //
	//////////////


	public static function add_link_to_admin_bar( $items ) {
		if ( is_single() && si_get_doc_context() ) {
			$items[] = array(
				'id' => 'customizer',
				'title' => self::__( 'Customize' ),
				'href' => esc_url_raw( add_query_arg( array( 'url' => urlencode( get_permalink() ) ), admin_url( 'customize.php' ) ) ),
				'weight' => 1000,
			);
		}
		return $items;
	}


	public static function customizer_js() {
		wp_enqueue_script(
			'si_customizer',
			SI_URL . '/resources/admin/js/customizer.js',
			array( 'jquery', 'customize-preview' ),
			'0.3.0',
			true
		);
		add_filter( 'si_allowed_admin_doc_scripts', array( __CLASS__, 'allow_customizer_js' ) );
	}

	public static function allow_customizer_js( $queue = array() ) {
		$queue[] = 'customize-preview';
		$queue[] = 'si_customizer';
		return $queue;
	}

	public static function customizer( $wp_customize ) {

		// Logo uploader
		$wp_customize->add_section( 'si_custommizer_section' , array(
			'title'       => self::__('Sprout Invoices'),
			'priority'    => 300,
			'description' => self::__('Upload a logo to replace the default estimate/invoice logo.'),
			) );

		$wp_customize->add_setting( 'si_logo', array(
			'sanitize_callback' => 'esc_url_raw',
			//'transport' => 'postMessage',
			) );

		$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'si_logo', array(
			'label'    => self::__('Invoice & Estimate Logo'),
			'section'  => 'si_custommizer_section',
			'settings' => 'si_logo',
			) ) );

		// Highlight and link color
		$wp_customize->add_setting( 'si_invoices_color', array(
		    'default'           => '#FF5B4D',
		    'sanitize_callback' => 'sanitize_hex_color',
		    //'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_invoices_color', array(
		    'label'	   => self::__('Invoice Highlight Color'),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_invoices_color',
		) ) );

		// Highlight and link color
		$wp_customize->add_setting( 'si_estimates_color', array(
		    'default'           => '#4D9FFF',
		    'sanitize_callback' => 'sanitize_hex_color',
		    //'transport' => 'postMessage',
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'si_estimates_color', array(
		    'label'	   => self::__('Estimate Highlight Color'),
		    'section'  => 'si_custommizer_section',
		    'settings' => 'si_estimates_color',
		) ) );
	}

	public static function inject_css() {
		$inv_color = self::sanitize_hex_color( get_theme_mod( 'si_invoices_color' ) );
		$est_color = self::sanitize_hex_color( get_theme_mod( 'si_estimates_color' ) );
		?>
			<!-- Debut customizer CSS -->
			<style>
			#doc .doc_total,
			.button.primary_button {
				background-color: <?php echo esc_attr( $est_color ); ?>;
			}

			#invoice #doc .doc_total,
			#invoice .button.primary_button {
				background-color: <?php echo esc_attr( $inv_color ); ?>;
			}

			#invoice.paid #doc .doc_total,
			#invoice .button.deposit_paid {
				background-color: <?php echo esc_attr( $est_color ); ?>;
			}

			#line_total {
				color: <?php echo esc_attr( $est_color ); ?>;
			}

			#invoice #line_total {
				color: <?php echo esc_attr( $inv_color ); ?>;
			}
			</style>
		<?php
	}

	/**
	* Sanitizes a hex color. Identical to core's sanitize_hex_color(), which is not available on the wp_head hook.
	*
	* Returns either '', a 3 or 6 digit hex color (with #), or null.
	* For sanitizing values without a #, see sanitize_hex_color_no_hash().
	*
	* @since 1.7
	*/
	public static function sanitize_hex_color( $color ) {
		if ( '' === $color ) {
			return '';
		}
		// 3 or 6 hex digits, or the empty string.
		if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}
		return null;
	}

}