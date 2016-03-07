<?php


/**
 * Send notifications, apply shortcodes and create management screen.
 *
 * @package Sprout_Invoice
 * @subpackage Reporting
 */
class SI_Dashboard extends SI_Controller {
	const SETTINGS_PAGE = 'reporting';
	const STATS_PAGE = 'sprout-invoices-stats';
	const REPORT_QV = 'report';
	const CACHE_KEY_PREFIX = 'si_rprt_';
	const AJAX_ACTION = 'si_report_data';
	const AJAX_NONCE = 'si_report_nonce';
	const CACHE_TIMEOUT = 172800; // 48 hours

	public static function init() {
		// register settings
		self::register_settings();

		// Dashboards
		add_filter( 'si_settings_page_sub_heading_sprout-apps/settings', array( get_class(), 'reports_subtitle' ) );

		// Add reports to Dashboard
		add_action( 'admin_menu', array( __CLASS__, 'add_dash_page' ), 10, 0 );
		add_action( 'sprout_settings_tabs', array( __CLASS__, 'add_stats_heading' ), 10, 0 );
		add_filter( 'si_settings_page_sub_heading_sprout-invoices-stats', array( __CLASS__, 'reports_subtitle' ) );

		add_action( 'admin_init', array( __CLASS__, 'redirect_to_stats' ) );

		// Enqueue
		add_action( 'admin_init', array( __CLASS__, 'register_resources' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ) );
		add_action( 'admin_print_scripts', array( __CLASS__, 'admin_dash_js' ), 1000 );

		// Dashboard widgets
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widgets' ), 10, 0 );
	}

	//////////////
	// Enqueue //
	//////////////

	public static function register_resources() {
		// Charting
		wp_register_script( 'chartjs', SI_URL . '/resources/admin/plugins/chartjs/chart.min.js', array( 'jquery' ), false, false );

	}

	public static function admin_enqueue() {

		// WP Dashboard
		$screen = get_current_screen();
		if ( 'dashboard' === $screen->id ) {
			if ( self::show_charts_on_wp_dash() ) {
				wp_enqueue_script( 'chartjs' );
			}
			// don't continue for wp dash
			return;
		}

		// SI Dashboard
		if ( self::is_si_dash() ) {

			self::enqueue_general_scripts_styles();

			wp_enqueue_script( 'chartjs' );

			self::si_dashboard_setup();
			wp_enqueue_script( 'dashboard' );

			add_thickbox();

			if ( wp_is_mobile() ) {
				wp_enqueue_script( 'jquery-touch-punch' );
			}
		}
	}

	public static function admin_dash_js() {
		$screen = get_current_screen();
		if ( ! in_array( $screen->id, array( 'dashboard', 'dashboard_page_sprout-invoices-stats' ) ) ) {
			return;
		}
		if ( isset( $_GET['report'] ) && '' !== $_GET['report'] ) {
			return;
		}
		if ( 'dashboard' === $screen->id && ! self::show_charts_on_wp_dash() ) {
			return;
		}
		?>
		<script type="text/javascript">
			// chart defaults
			Chart.defaults.global.responsive = true;
			Chart.defaults.global.maintainAspectRatio = true;
			// default to currency formatted
			Chart.defaults.global.multiTooltipTemplate = function(label){
				return label.datasetLabel + ': ' + si_format_money( label.value );
			};
		</script>
		<?php
	}




	////////////
	// admin //
	////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Option page
		$args = array(
			'slug' => self::SETTINGS_PAGE,
			'title' => __( 'Reports Dashboard', 'sprout-invoices' ),
			'menu_title' => __( 'Reports', 'sprout-invoices' ),
			'weight' => 5,
			'reset' => false,
			'section' => 'settings',
			'tab_only' => true,
			'callback' => null,
			);
		do_action( 'sprout_settings_page', $args );
	}

	//////////////////////
	// Stats Dashboard //
	//////////////////////

	public static function redirect_to_stats() {
		if ( isset( $_GET['tab'] ) && self::SETTINGS_PAGE === $_GET['tab'] && ! isset( $_GET[ self::REPORT_QV ] ) ) {
			wp_redirect( admin_url() . 'index.php?page=' . self::STATS_PAGE );
			exit;
		}
	}

	public static function add_dash_page() {
		add_submenu_page( 'index.php', __( 'Sprout Apps', 'sprout-invoices' ), __( 'Sprout Invoices', 'sprout-invoices' ), 'manage_sprout_invoices_options', self::STATS_PAGE, array( __CLASS__, 'reports_dashboard' ) );
	}

	public static function add_stats_heading() {
		if ( isset( $_GET['page'] ) && self::STATS_PAGE === $_GET['page'] ) {
			$section = 'settings';
			$tabs = apply_filters( 'si_option_tabs', SA_Settings_API::get_option_tabs() );
			uasort( $tabs, array( __CLASS__, 'sort_by_weight' ) );
			// loop through tabs and build markup
			foreach ( $tabs as $key => $data ) :
				if ( $data['section'] === $section ) {
					$new_title = __( $data['tab_title'], 'sprout-invoices' );
					$current = ( $data['slug'] === 'reporting' ) ? ' nav-tab-active' : '';
					$url = ( $data['tab_only'] ) ? add_query_arg( array( 'page' => self::APP_DOMAIN . '/settings', 'tab' => $data['slug'] ), 'admin.php' ) : add_query_arg( array( 'page' => self::APP_DOMAIN . '/settings' ), 'admin.php' );
					echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . $current . '" id="si_options_tab_' . $data['slug'] . '">' . $new_title . '</a>';
				}
			endforeach;
		}
	}

	public static function si_dashboard_setup() {

		require_once( ABSPATH . 'wp-admin/includes/dashboard.php' );

		global $wp_registered_widgets, $wp_registered_widget_controls, $wp_dashboard_control_callbacks;

		$wp_dashboard_control_callbacks = array();
		$screen = get_current_screen();

		do_action( 'si_dashboard_setup', $screen->id );
		SI_Dashboard::add_dashboard_widgets( $screen->id );

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['widget_id'] ) ) {
			check_admin_referer( 'edit-dashboard-widget_' . $_POST['widget_id'], 'dashboard-widget-nonce' );
			ob_start(); // hack - but the same hack wp-admin/widgets.php uses
			wp_dashboard_trigger_widget_control( $_POST['widget_id'] );
			ob_end_clean();
			wp_redirect( remove_query_arg( 'edit' ) );
			exit;
		}

		/** This action is documented in wp-admin/edit-form-advanced.php */
		do_action( 'do_meta_boxes', $screen->id, 'normal', '' );

		/** This action is documented in wp-admin/edit-form-advanced.php */
		do_action( 'do_meta_boxes', $screen->id, 'side', '' );
	}

	public static function reports_dashboard() {
		$report_dash = ( isset( $_GET[ self::REPORT_QV ] ) ) ? $_GET[ self::REPORT_QV ] : false ;
		switch ( $report_dash ) {
			case 'invoices':
				self::load_view( 'admin/reports/invoices.php', array() );
				break;
			case 'estimates':
				self::load_view( 'admin/reports/estimates.php', array() );
				break;
			case 'payments':
				self::load_view( 'admin/reports/payments.php', array() );
				break;
			case 'clients':
				self::load_view( 'admin/reports/clients.php', array() );
				break;
			default:
				self::load_view( 'admin/reports/dashboard.php', array() );
				break;
		}
	}

	public static function reports_subtitle() {
		if ( self::is_report_page() ) {
			$current_report = ( isset( $_GET[ self::REPORT_QV ] ) ) ? $_GET[ self::REPORT_QV ] : 'dashboard' ;
			?>
				<ul class="subsubsub">
					<li class="invoices"><a href="<?php echo esc_url( remove_query_arg( self::REPORT_QV ) ) ?>" <?php if ( $current_report == 'dashboard' ) { echo 'class="current"'; } ?>><?php _e( 'Dashboard', 'sprout-invoices' ) ?></a> |</li>
					<li class="invoices"><a href="<?php echo esc_url( add_query_arg( self::REPORT_QV, 'invoices' ) ) ?>" <?php if ( $current_report == 'invoices' ) { echo 'class="current"'; } ?>><?php _e( 'Invoices', 'sprout-invoices' ) ?></a> |</li>
					<li class="estimates"><a href="<?php echo esc_url( add_query_arg( self::REPORT_QV, 'estimates' ) ) ?>" <?php if ( $current_report == 'estimates' ) { echo 'class="current"'; } ?>><?php _e( 'Estimates', 'sprout-invoices' ) ?></a> |</li>
					<li class="payments"><a href="<?php echo esc_url( add_query_arg( self::REPORT_QV, 'payments' ) ) ?>" <?php if ( $current_report == 'payments' ) { echo 'class="current"'; } ?>><?php _e( 'Payments', 'sprout-invoices' ) ?></a> |</li>
					<li class="clients"><a href="<?php echo esc_url( add_query_arg( self::REPORT_QV, 'clients' ) ) ?>" <?php if ( $current_report == 'clients' ) { echo 'class="current"'; } ?>><?php _e( 'Clients', 'sprout-invoices' ) ?></a></li>
				</ul>
			<?php
		}
	}

	//////////////
	// Utility //
	//////////////

	public static function is_report_page() {
		// stats page
		if ( isset( $_GET['page'] ) && self::STATS_PAGE === $_GET['page'] ) {
			return true;
		}
		if ( isset( $_GET['tab'] ) && self::SETTINGS_PAGE === $_GET['tab'] ) {
			return true;
		}
		return false;
	}

	public static function is_si_dash() {
		if ( isset( $_GET[ self::REPORT_QV ] ) ) {
			return false;
		}
		$screen = get_current_screen();
		return in_array( $screen->id, array( 'dashboard', 'dashboard_page_sprout-invoices-stats', 'sprout-apps_page_sprout-apps/settings' ) );
	}

	public static function show_charts_on_wp_dash() {
		return apply_filters( 'si_show_all_dash_widgets', false );
	}

	//////////////
	// Widgets //
	//////////////

	public static function add_dashboard_widgets( $context = 'dashboard' ) {
		if ( ! current_user_can( 'view_sprout_invoices_dashboard' ) ) {
			return;
		}

		add_meta_box(
			'invoice_dashboard',
			__( 'Invoices Dashboard', 'sprout-invoices' ),
			array( __CLASS__, 'invoices_dashboard' ),
			$context,
			'normal',
			'high'
		);

		add_meta_box(
			'estimates_dashboard',
			__( 'Estimates Dashboard', 'sprout-invoices' ),
			array( __CLASS__, 'estimates_dashboard' ),
			$context,
			'side',
			'high'
		);

		if ( ! self::show_charts_on_wp_dash() && 'dashboard' === $context ) {
			return;
		}

		add_meta_box(
			'invoice_payments_chart_dashboard',
			__( 'Invoiced Payments', 'sprout-invoices' ),
			array( __CLASS__, 'invoice_payments_chart_dashboard' ),
			$context,
			'normal',
			'high'
		);

		add_meta_box(
			'payments_chart_dashboard',
			__( 'Payments', 'sprout-invoices' ),
			array( __CLASS__, 'payments_chart_dashboard' ),
			$context,
			'normal',
			'high'
		);

		add_meta_box(
			'balances_chart_dashboard',
			__( 'Invoice Balances', 'sprout-invoices' ),
			array( __CLASS__, 'balances_chart_dashboard' ),
			$context,
			'normal',
			'high'
		);

		add_meta_box(
			'payments_status_chart_dashboard',
			__( 'Payments Status', 'sprout-invoices' ),
			array( __CLASS__, 'payments_status_chart_dashboard' ),
			$context,
			'normal',
			'high'
		);

		add_meta_box(
			'invoices_status_chart_dashboard',
			__( 'Invoice Status', 'sprout-invoices' ),
			array( __CLASS__, 'invoices_status_chart_dashboard' ),
			$context,
			'normal',
			'high'
		);

		add_meta_box(
			'estimates_invoices_chart_dashboard',
			__( 'Estimates &amp; Invoices', 'sprout-invoices' ),
			array( __CLASS__, 'estimates_invoices_chart_dashboard' ),
			$context,
			'side',
			'high'
		);

		add_meta_box(
			'requests_converted_chart_dashboard',
			__( 'Estimate Requests Converted', 'sprout-invoices' ),
			array( __CLASS__, 'requests_converted_chart_dashboard' ),
			$context,
			'side',
			'high'
		);

		add_meta_box(
			'estimates_status_chart_dashboard',
			__( 'Estimates Status', 'sprout-invoices' ),
			array( __CLASS__, 'estimates_status_chart_dashboard' ),
			$context,
			'side',
			'high'
		);
	}

	public static function invoices_dashboard() {
		self::load_view( 'admin/dashboards/invoices.php', array() );
	}

	public static function invoice_payments_chart_dashboard() {
		self::load_view( 'admin/dashboards/invoice-payments-chart.php', array() );
	}

	public static function payments_chart_dashboard() {
		self::load_view( 'admin/dashboards/payments-chart.php', array() );
	}

	public static function balances_chart_dashboard() {
		self::load_view( 'admin/dashboards/balances-chart.php', array() );
	}

	public static function payments_status_chart_dashboard() {
		self::load_view( 'admin/dashboards/payments-status-chart.php', array() );
	}

	public static function invoices_status_chart_dashboard() {
		self::load_view( 'admin/dashboards/invoices-status-chart.php', array() );
	}

	public static function estimates_dashboard() {
		self::load_view( 'admin/dashboards/estimates.php', array() );
	}

	public static function estimates_invoices_chart_dashboard() {
		self::load_view( 'admin/dashboards/estimates-invoices-chart.php', array() );
	}

	public static function requests_converted_chart_dashboard() {
		self::load_view( 'admin/dashboards/requests-converted-chart.php', array() );
	}

	public static function estimates_status_chart_dashboard() {
		self::load_view( 'admin/dashboards/estimates-status-chart.php', array() );
	}
}
