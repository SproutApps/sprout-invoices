<?php


/**
 * Send notifications, apply shortcodes and create management screen.
 *
 * @package Sprout_Invoice
 * @subpackage Reporting
 */
class SI_Reporting extends SI_Controller {
	const SETTINGS_PAGE = 'reporting';
	const REPORT_QV = 'report';
	const CACHE_KEY_PREFIX = 'si_report_cache_v6a_';
	const AJAX_ACTION = 'si_report_data';
	const AJAX_NONCE = 'si_report_nonce';
	const CACHE_TIMEOUT = 172800; // 48 hours

	public static function init() {
		// register settings
		self::register_settings();
		add_filter( 'si_settings_page_sub_heading_sprout-apps/settings', array( get_class(), 'reports_subtitle' ) );

		// Help Sections
		add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

		add_action( 'wp_ajax_'.self::AJAX_ACTION,  array( __CLASS__, 'get_chart_data' ), 10, 0 );

		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 15, 1 );

		// Enqueue
		add_action( 'init', array( __CLASS__, 'register_resources' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ) );
	}

	////////////
	// admin //
	////////////

	public static function register_resources() {
		// Charting
		wp_register_script( 'chartjs', SI_URL . '/resources/admin/plugins/chartjs/chart.min.js', array( 'jquery' ), false, false );

	}

	public static function admin_enqueue() {
		// Only on the dashboard
		if ( isset( $_GET['tab'] ) && !isset( $_GET[self::REPORT_QV] ) && $_GET['tab'] == self::SETTINGS_PAGE ) {
			wp_enqueue_script( 'chartjs' );
		}
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
			'title' => self::__( 'Reports Dashboard' ),
			'menu_title' => self::__( 'Reports' ),
			'weight' => 5,
			'reset' => FALSE,
			'section' => 'settings',
			'tab_only' => TRUE,
			'callback' => array( __CLASS__, 'reports_dashboard' )
			);
		do_action( 'sprout_settings_page', $args );
	}

	public static function reports_dashboard() {
		$report_dash = ( isset( $_GET[self::REPORT_QV] ) ) ? $_GET[self::REPORT_QV] : FALSE ;
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
		if ( isset( $_GET['tab'] ) && $_GET['tab'] == self::SETTINGS_PAGE ) {
			$current_report = ( isset( $_GET[self::REPORT_QV] ) ) ? $_GET[self::REPORT_QV] : 'dashboard' ;
			?>
				<ul class="subsubsub">
					<li class="invoices"><a href="<?php echo remove_query_arg( self::REPORT_QV ) ?>" <?php if ( $current_report == 'dashboard' ) echo 'class="current"' ?>><?php self::_e('Dashboard') ?></a> |</li>
					<li class="invoices"><a href="<?php echo add_query_arg( self::REPORT_QV, 'invoices' ) ?>" <?php if ( $current_report == 'invoices' ) echo 'class="current"' ?>><?php self::_e('Invoices') ?></a> |</li>
					<li class="estimates"><a href="<?php echo add_query_arg( self::REPORT_QV, 'estimates' ) ?>" <?php if ( $current_report == 'estimates' ) echo 'class="current"' ?>><?php self::_e('Estimates') ?></a> |</li>
					<li class="payments"><a href="<?php echo add_query_arg( self::REPORT_QV, 'payments' ) ?>" <?php if ( $current_report == 'payments' ) echo 'class="current"' ?>><?php self::_e('Payments') ?></a> |</li>
					<li class="clients"><a href="<?php echo add_query_arg( self::REPORT_QV, 'clients' ) ?>" <?php if ( $current_report == 'clients' ) echo 'class="current"' ?>><?php self::_e('Clients') ?></a></li>
				</ul>
			<?php
		}
	}

	///////////////////////////
	// AJAX Chart Callbacks //
	///////////////////////////

	public static function get_chart_data() {
		if ( !isset( $_REQUEST['security'] ) )
			self::ajax_fail( 'Forget something?' );

		$nonce = $_REQUEST['security'];
		if ( !wp_verify_nonce( $nonce, self::AJAX_NONCE ) )
			self::ajax_fail( 'Not going to fall for it!' );

		// FUTURE segment and span

		switch ( $_REQUEST['data'] ) {
			case 'invoice_payments':
				self::invoice_payments_chart();
				break;
			case 'balance_invoiced':
				self::balance_invoiced_chart();
				break;
			case 'est_invoice_totals':
				self::est_invoice_totals_chart();
				break;
			case 'req_to_inv_totals':
				self::req_to_inv_totals_chart();
				break;
			case 'payment_statuses':
				self::payment_statuses_chart();
				break;
			case 'invoice_statuses':
				self::invoice_statuses_chart();
				break;
			case 'estimates_statuses':
				self::estimate_statuses_chart();
				break;
			
			default:
				# code...
				break;
		}
	}

	/**
	 * Return two data sets
	 * @param  string  $segment 
	 * @param  integer $span    
	 * @return  json array
	 */
	public static function invoice_payments_chart( $segment = 'weeks', $span = 6 ) {
		$return = array(
				'labels' => array(),
				'invoices' => array(),
				'payments' => array()
			);
		$data = self::total_invoice_data_by_date_segment();
		foreach ( $data as $segment => $seg_data ) {
			$return['labels'][] = date_i18n( 'M d', strtotime( date( 'Y' ) . 'W' . $segment ) );
			$return['invoices'][] = $seg_data['totals'];
			$return['payments'][] = $seg_data['paid'];
		}
		header( 'Content-type: application/json' );
		echo json_encode( $return );
		exit();
	}

	/**
	 * Return two data sets
	 * @param  string  $segment 
	 * @param  integer $span    
	 * @return  json array
	 */
	public static function balance_invoiced_chart( $segment = 'weeks', $span = 6 ) {
		$return = array(
				'labels' => array(),
				'balances' => array(),
				'paid' => array()
			);
		$data = self::total_invoice_data_by_date_segment();
		foreach ( $data as $segment => $seg_data ) {
			$return['labels'][] = date_i18n( 'M d', strtotime( date( 'Y' ) . 'W' . $segment ) );
			$return['payments'][] = $seg_data['paid'];
			$return['balances'][] = $seg_data['balance'];
		}
		header( 'Content-type: application/json' );
		echo json_encode( $return );
		exit();
	}

	/**
	 * Return two data sets
	 * @param  string  $segment 
	 * @param  integer $span    
	 * @return  json array
	 */
	public static function est_invoice_totals_chart( $segment = 'weeks', $span = 6 ) {
		$return = array(
				'labels' => array(),
				'estimates' => array(),
				'invoices' => array()
			);
		$inv_data = self::total_invoice_data_by_date_segment();
		$est_data = self::total_estimate_data_by_date_segment();
		foreach ( $inv_data as $segment => $seg_data ) {
			$return['labels'][] = date_i18n( 'M d', strtotime( date( 'Y' ) . 'W' . $segment ) );
			$return['invoices'][] = $seg_data['invoices'];
			$return['estimates'][] = $est_data[$segment]['estimates'];
		}
		header( 'Content-type: application/json' );
		echo json_encode( $return );
		exit();
	}

	/**
	 * Return two data sets
	 * @param  string  $segment 
	 * @param  integer $span    
	 * @return  json array
	 */
	public static function req_to_inv_totals_chart( $segment = 'weeks', $span = 6 ) {
		$return = array(
				'labels' => array(),
				'requests' => array(),
				'invoices' => array()
			);
		$est_data = self::total_estimate_data_by_date_segment();
		foreach ( $est_data as $segment => $seg_data ) {
			$return['labels'][] = date_i18n( 'M d', strtotime( date( 'Y' ) . 'W' . $segment ) );
			$return['requests'][] = $seg_data['requests'];
			$return['invoices_generated'][] = $seg_data['invoices_generated'];
		}
		header( 'Content-type: application/json' );
		echo json_encode( $return );
		exit();
	}

	/**
	 * Return two data sets
	 * @param  string  $segment 
	 * @param  integer $span    
	 * @return  json array
	 */
	public static function payment_statuses_chart( $segment = 'weeks', $span = 6 ) {
		$return = array(
				'status_pending' => 0,
				'status_complete' => 0,
				'status_void' => 0
			);
		$data = array_slice( self::total_payment_data_by_date_segment(), -3 );
		foreach ( $data as $segment => $seg_data ) {
			$return['status_pending'] += $seg_data['status_pending'];
			$return['status_complete'] += $seg_data['status_complete'];
			$return['status_void'] += $seg_data['status_void'];
		}
		header( 'Content-type: application/json' );
		echo json_encode( $return );
		exit();
	}

	/**
	 * Return two data sets
	 * @param  string  $segment 
	 * @param  integer $span    
	 * @return  json array
	 */
	public static function invoice_statuses_chart( $segment = 'weeks', $span = 6 ) {
		$return = array(
			'status_temp' => 0,
			'status_pending' => 0,
			'status_partial' => 0,
			'status_complete' => 0,
			'status_writeoff' => 0
			);
		$data = array_slice( self::total_invoice_data_by_date_segment(), -3 );
		foreach ( $data as $segment => $seg_data ) {
			$return['status_temp'] += $seg_data['status_temp'];
			$return['status_pending'] += $seg_data['status_pending'];
			$return['status_partial'] += $seg_data['status_partial'];
			$return['status_complete'] += $seg_data['status_complete'];
			$return['status_writeoff'] += $seg_data['status_writeoff'];
		}
		header( 'Content-type: application/json' );
		echo json_encode( $return );
		exit();
	}

	/**
	 * Return two data sets
	 * @param  string  $segment 
	 * @param  integer $span    
	 * @return  json array
	 */
	public static function estimate_statuses_chart( $segment = 'weeks', $span = 6 ) {
		$return = array(
			'status_request' => 0,
			'status_pending' => 0,
			'status_approved' => 0,
			'status_declined' => 0
			);
		$data = array_slice( self::total_estimate_data_by_date_segment(), -3 );
		foreach ( $data as $segment => $seg_data ) {
			$return['status_request'] += $seg_data['status_request'];
			$return['status_pending'] += $seg_data['status_pending'];
			$return['status_approved'] += $seg_data['status_approved'];
			$return['status_declined'] += $seg_data['status_declined'];
		}
		header( 'Content-type: application/json' );
		echo json_encode( $return );
		exit();
	}



	/////////////////////////
	// Data for Reporting //
	/////////////////////////

	public static function total_invoice_data_by_date_segment( $segment = 'weeks', $span = 6 ) {
		// Return cache if present.
		$cache = self::get_cache(__FUNCTION__);
		if ( $cache ) {
			return $cache;
		}
	
		// FUTURE charts should be dynamic based on selected segment.
		$weeks = self::walk_back_x_span( $span, $segment );

		// Build data array, without a explicit build segments without posts will not show.
		$data = array();
		foreach ( $weeks as $week_num ) {
			$data[$week_num] = array(
					'invoices' => 0,
					'payments' => 0,
					'totals' => 0,
					'subtotals' => 0,
					'paid' => 0,
					'balance' => 0,
					'status_temp' => 0,
					'status_pending' => 0,
					'status_partial' => 0,
					'status_complete' => 0,
					'status_writeoff' => 0
				);
		}
		$args = array(
			'post_type' => SI_Invoice::POST_TYPE,
			'post_status' => array( SI_Invoice::STATUS_PENDING, SI_Invoice::STATUS_PARTIAL, SI_Invoice::STATUS_PAID ),
			'posts_per_page' => -1,
			'orderby' => 'date',
			'fields' => 'ids',
			'date_query' => array(
					array(
						'after'     => date( "Y-m-d", strtotime( date( 'Y' ) . 'W' . array_shift($weeks) ) ),
						'inclusive' => true,
					)
				)
			);
		$invoices = new WP_Query( $args );
		foreach ( $invoices->posts as $invoice_id ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
			$week = get_the_time( 'W', $invoice_id );
			$data[$week]['invoices'] += 1;
			$data[$week]['payments'] += count( $invoice->get_payments() );
			$data[$week]['totals'] += si_get_invoice_calculated_total( $invoice_id );
			$data[$week]['subtotals'] += si_get_invoice_subtotal( $invoice_id );
			$data[$week]['paid'] += si_get_invoice_payments_total( $invoice_id );
			$data[$week]['balance'] += si_get_invoice_balance( $invoice_id );
			switch ( get_post_status( $invoice_id ) ) {
				case 'draft':
				case SI_Invoice::STATUS_TEMP:
					$data[$week]['status_temp'] += 1;
					break;
				case SI_Invoice::STATUS_PENDING:
					$data[$week]['status_pending'] += 1;
					break;
				case SI_Invoice::STATUS_PARTIAL:
					$data[$week]['status_partial'] += 1;
					break;
				case SI_Invoice::STATUS_PAID:
					$data[$week]['status_complete'] += 1;
					break;
				case SI_Invoice::STATUS_WO:
					$data[$week]['status_writeoff'] += 1;
					break;
				default:
					break;
			}
			
		}
		return self::set_cache( __FUNCTION__, $data );
	}

	public static function total_invoice_data( $this = 'century' ) {
		// Return cache if present.
		$cache = self::get_cache(__FUNCTION__.$this);
		if ( $cache ) {
			return $cache;
		}
		$expire = self::CACHE_TIMEOUT;

		// Build data array, without a explicit build segments without posts will not show.
		$data = array(
				'invoices' => 0,
				'payments' => 0,
				'totals' => 0,
				'subtotals' => 0,
				'paid' => 0,
				'balance' => 0,
				'status_temp' => 0,
				'status_pending' => 0,
				'status_partial' => 0,
				'status_complete' => 0,
				'status_writeoff' => 0
			);
		$args = array(
			'post_type' => SI_Invoice::POST_TYPE,
			'post_status' => array( SI_Invoice::STATUS_PENDING, SI_Invoice::STATUS_PARTIAL, SI_Invoice::STATUS_PAID ),
			'posts_per_page' => -1,
			'fields' => 'ids',
			);

		// If date filtered
		if ( $this != 'century' ) {
			switch ( $this ) {
				case 'week':
					$args['date_query'] = array(
						array(
							'week' => date( 'W', strtotime('this week') ),
							'inclusive' => true,
						)
					);
					$expire = strtotime( date( "Y-m-t" ), strtotime('this Sunday') )-current_time('timestamp');
					break;
				case 'lastweek':
					$args['date_query'] = array(
						array(
							'week' => date( 'W', strtotime('-1 week') ),
							'inclusive' => true,
						)
					);
					$expire = strtotime( date( "Y-m-t" ), strtotime('this Sunday') )-current_time('timestamp');
					break;
				case 'month':
					$args['date_query'] = array(
						array(
							'month' => date('m'),
							'inclusive' => true,
						)
					);
					$expire = strtotime( date( "Y-m-t" ) )-current_time('timestamp');
					break;
				case 'lastmonth':
					$args['date_query'] = array(
						array(
							'month' => date( 'm', strtotime('-1 month') ),
							'inclusive' => true,
						)
					);
					$expire = strtotime( date( "Y-m-t" ) )-current_time('timestamp');
					break;
				case 'year':
					$args['date_query'] = array(
						array(
							'year' => date('Y'),
							'inclusive' => true,
						)
					);
					$expire = strtotime( date( "Y-m-t" ), strtotime('12/31') )-current_time('timestamp');
					break;
				case 'lastyear':
					$args['date_query'] = array(
						array(
							'year' => date( 'Y', strtotime('-1  year') ),
							'inclusive' => true,
						)
					);
					$expire = strtotime( date( "Y-m-t" ), strtotime('12/31') )-current_time('timestamp');
					break;
				default:
					break;
			}
		}
		$invoices = new WP_Query( $args );
		foreach ( $invoices->posts as $invoice_id ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
			$data['invoices'] += 1;
			$data['payments'] += count( $invoice->get_payments() );
			$data['totals'] += si_get_invoice_calculated_total( $invoice_id );
			$data['subtotals'] += si_get_invoice_subtotal( $invoice_id );
			$data['paid'] += si_get_invoice_payments_total( $invoice_id );
			$data['balance'] += si_get_invoice_balance( $invoice_id );
			switch ( get_post_status( $invoice_id ) ) {
				case 'draft':
				case SI_Invoice::STATUS_TEMP:
					$data['status_temp'] += 1;
					break;
				case SI_Invoice::STATUS_PENDING:
					$data['status_pending'] += 1;
					break;
				case SI_Invoice::STATUS_PARTIAL:
					$data['status_partial'] += 1;
					break;
				case SI_Invoice::STATUS_PAID:
					$data['status_complete'] += 1;
					break;
				case SI_Invoice::STATUS_WO:
					$data['status_writeoff'] += 1;
					break;
				default:
					break;
			}
			unset( $invoice );
			
		}
		return self::set_cache( __FUNCTION__.$this, $data, $expire );
	}

	public static function total_estimate_data_by_date_segment( $segment = 'weeks', $span = 6 ) {
		// Return cache if present.
		$cache = self::get_cache(__FUNCTION__);
		if ( $cache ) {
			return $cache;
		}
	
		// FUTURE charts should be dynamic based on selected segment.
		$weeks = self::walk_back_x_span( $span, $segment );

		// Build data array, without a explicit build segments without posts will not show.
		$data = array();
		foreach ( $weeks as $week_num ) {
			$data[$week_num] = array(
					'estimates' => 0,
					'requests' => 0,
					'totals' => 0,
					'subtotals' => 0,
					'invoices_generated' => 0,
					'status_request' => 0,
					'status_pending' => 0,
					'status_approved' => 0,
					'status_declined' => 0
				);
		}
		$args = array(
			'post_type' => SI_Estimate::POST_TYPE,
			'post_status' => 'any', // Not Written-off?
			'posts_per_page' => -1,
			'orderby' => 'date',
			'fields' => 'ids',
			'date_query' => array(
					array(
						'after'     => date( "Y-m-d", strtotime( date( 'Y' ) . 'W' . array_shift($weeks) ) ),
						'inclusive' => true,
					)
				)
			);
		$estimates = new WP_Query( $args );
		foreach ( $estimates->posts as $estimate_id ) {
			$week = get_the_time( 'W', $estimate_id );
			$data[$week]['estimates'] += 1;
			$data[$week]['totals'] += si_get_estimate_total( $estimate_id );
			$data[$week]['subtotals'] += si_get_estimate_subtotal( $estimate_id );
			if ( si_get_estimate_invoice_id( $estimate_id ) ) {
				$data[$week]['invoices_generated'] += 1;
			}
			// If there are submission fields than it's a request
			if ( si_is_estimate_submission( $estimate_id ) ) {
				$data[$week]['requests'] += 1;
			}
			switch ( get_post_status( $estimate_id ) ) {
				case SI_Estimate::STATUS_REQUEST:
					$data[$week]['status_request'] += 1;
					break;
				case SI_Estimate::STATUS_PENDING:
					$data[$week]['status_pending'] += 1;
					break;
				case SI_Estimate::STATUS_APPROVED:
					$data[$week]['status_approved'] += 1;
					break;
				case SI_Estimate::STATUS_DECLINED:
					$data[$week]['status_declined'] += 1;
					break;
				default:
					break;
			}
		}
		return self::set_cache( __FUNCTION__, $data );
	}

	public static function total_payment_data( $this = 'century' ) {
		// Return cache if present.
		$cache = self::get_cache(__FUNCTION__.$this);
		if ( $cache ) {
			return $cache;
		}
		$expire = self::CACHE_TIMEOUT;

		// Build data array, without a explicit build segments without posts will not show.
		$data = array(
				'payments' => 0,
				'totals' => 0,
				'status_pending' => 0,
				'status_authorized' => 0,
				'status_complete' => 0,
				'status_partial' => 0,
				'status_void' => 0
			);
		$args = array(
			'post_type' => SI_Payment::POST_TYPE,
			'post_status' => 'any', // Not Written-off?
			'posts_per_page' => -1,
			'orderby' => 'date',
			'fields' => 'ids',
			);

		// If date filtered
		if ( $this != 'century' ) {
			switch ( $this ) {
				case 'week':
					$args['date_query'] = array(
						array(
							'week' => date( 'W', strtotime('this week') ),
							'year' => date( 'o', strtotime('this week') ),
							'inclusive' => true,
						)
					);
					$expire = strtotime('tomorrow')-current_time('timestamp');
					break;
				case 'lastweek':
					$args['date_query'] = array(
						array(
							'week' => date( 'W', strtotime('-1 week') ),
							'year' => date( 'o', strtotime('-1 week') ),
							'inclusive' => true,
						)
					);
					$expire = strtotime('next week')-current_time('timestamp');
					break;
				case 'month':
					$args['date_query'] = array(
						array(
							'month' => date( 'm', strtotime('first day of this month') ),
							'year' => date( 'o', strtotime('first day of this month') ),
							'inclusive' => true,
						)
					);
					$expire = strtotime('tomorrow')-current_time('timestamp');
					break;
				case 'lastmonth':
					$args['date_query'] = array(
						array(
							'month' => date( 'm', strtotime('first day of previous month') ),
							'year' => date( 'o', strtotime('first day of previous month') ),
							'inclusive' => true,
						)
					);
					$expire = strtotime('first day of next month')-current_time('timestamp');
					break;
				case 'year':
					$args['date_query'] = array(
						array(
							'year' => date( 'Y', strtotime('first day of this year') ),
							'inclusive' => true,
						)
					);
					$expire = strtotime('tomorrow')-current_time('timestamp');
					break;
				case 'lastyear':
					$args['date_query'] = array(
						array(
							'year' => date( 'Y', strtotime( 'first day of previous year' ) ),
							'inclusive' => true,
						)
					);
					$expire = strtotime('last day of year')-current_time('timestamp');
					break;
				default:
					break;
			}
		}
		$payments = new WP_Query( $args );
		foreach ( $payments->posts as $payment_id ) {
			$payment = SI_Payment::get_instance( $payment_id );
			$data['payments'] += 1;
			if ( $payment->get_status() !== SI_Payment::STATUS_VOID ) {
				$data['totals'] += $payment->get_amount();
			}
			switch ( get_post_status( $payment_id ) ) {
				case SI_Payment::STATUS_PENDING:
					$data['status_pending'] += 1;
					break;
				case SI_Payment::STATUS_AUTHORIZED:
					$data['status_authorized'] += 1;
					break;
				case SI_Payment::STATUS_COMPLETE:
					$data['status_complete'] += 1;
					break;
				case SI_Payment::STATUS_PARTIAL:
					$data['status_partial'] += 1;
					break;
				case SI_Payment::STATUS_VOID:
					$data['status_void'] += 1;
					break;
				default:
					break;
			}
		}
		return self::set_cache( __FUNCTION__.$this, $data, $expire );
	}

	public static function total_payment_data_by_date_segment( $segment = 'weeks', $span = 6 ) {
		// Return cache if present.
		$cache = self::get_cache(__FUNCTION__);
		if ( $cache ) {
			return $cache;
		}
	
		// FUTURE charts should be dynamic based on selected segment.
		$weeks = self::walk_back_x_span( $span, $segment );

		// Build data array, without a explicit build segments without posts will not show.
		$data = array();
		foreach ( $weeks as $week_num ) {
			$data[$week_num] = array(
					'payments' => 0,
					'totals' => 0,
					'status_pending' => 0,
					'status_authorized' => 0,
					'status_complete' => 0,
					'status_partial' => 0,
					'status_void' => 0
				);
		}
		$args = array(
			'post_type' => SI_Payment::POST_TYPE,
			'post_status' => 'any', // Not Written-off?
			'posts_per_page' => -1,
			'orderby' => 'date',
			'fields' => 'ids',
			'date_query' => array(
					array(
						'after'     => date( "Y-m-d", strtotime( date( 'Y' ) . 'W' . array_shift($weeks) ) ),
						'inclusive' => true,
					)
				)
			);
		$payments = new WP_Query( $args );
		foreach ( $payments->posts as $payment_id ) {
			$payment = SI_Payment::get_instance( $payment_id );
			$week = get_the_time( 'W', $payment_id );
			$data[$week]['payments'] += 1;
			$data[$week]['totals'] += $payment->get_amount();
			switch ( get_post_status( $payment_id ) ) {
				case SI_Payment::STATUS_PENDING:
					$data[$week]['status_pending'] += 1;
					break;
				case SI_Payment::STATUS_AUTHORIZED:
					$data[$week]['status_authorized'] += 1;
					break;
				case SI_Payment::STATUS_COMPLETE:
					$data[$week]['status_complete'] += 1;
					break;
				case SI_Payment::STATUS_PARTIAL:
					$data[$week]['status_partial'] += 1;
					break;
				case SI_Payment::STATUS_VOID:
					$data[$week]['status_void'] += 1;
					break;
				default:
					break;
			}
		}
		return self::set_cache( __FUNCTION__, $data );
	}

	//////////////
	// Caching //
	//////////////
	
	public static function get_cache( $data_name = '' ) {
		if ( self::DEBUG || isset( $_GET['nocache'] ) ) { // If dev than don't cache.
			return FALSE;
		}
		$cache = get_transient( self::CACHE_KEY_PREFIX.$data_name );
		// If cache is empty return false.
		return ( !empty( $cache ) ) ? $cache : FALSE;
	}

	public static function set_cache( $data_name = '', $data = array(), $expire = 0 ) {
		$timeout = ( $expire ) ? $expire : self::CACHE_TIMEOUT ;
		set_transient( self::CACHE_KEY_PREFIX.$data_name, $data, $timeout ); // cache for a week.
		return $data;
	}

	public static function delete_cache( $data_name = '' ) {
		delete_transient( self::CACHE_KEY_PREFIX.$data_name );
	}

	//////////////
	// Utility //
	//////////////
	
	public static function walk_back_x_span( $x = 6, $span = 'months' ) {
		switch ( $span ) {
			case 'months':
				$stretch = array( date( 'Y-m' ) );
				break;
			case 'weeks':
				$stretch = array( date( 'W' ) );
				break;
			case 'days':
				$stretch = array( date( 'Y-m-d' ) );
				break;
			
			default:
				$stretch = array();
				break;
		}

		for ($i = 1; $i <= $x; $i++) {
			switch ( $span ) {
				case 'months':
					$stretch[] = date( 'Y-m', strtotime( date( 'Y-m-01' ) . " -$i " . $span ) );
					break;
				case 'weeks':
					$stretch[] = date( 'W', strtotime( "-$i week" ) );
					break;
				case 'days':
					$stretch[] = date( 'Y-m-d', strtotime( date( 'Y-m-d' ) . " -$i " . $span ) );
					break;
				
				default:
					# code...
					break;
			}
		}
		return array_reverse( $stretch );
	}

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'si_dashboard',
			'title' => self::__( 'Dashboard' ),
			'href' => admin_url( 'admin.php?page=sprout-apps/settings&tab=reporting' ),
			'weight' => 0,
		);
		return $items;
	}
	
	////////////////
	// Admin Help //
	////////////////

	public static function help_sections() {
		add_action( 'load-sprout-apps_page_sprout-apps/settings', array( __CLASS__, 'help_tabs' ) );
	}

	public static function help_tabs() {
		if ( isset( $_GET['tab'] ) && $_GET['tab'] == self::SETTINGS_PAGE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
					'id' => 'reports-about',
					'title' => self::__( 'About Reports' ),
					'content' => sprintf( '<p>%s</p><p>%s</p><p>%s</p>', self::__('The Reports dashboard links to the many single reports that Sprout Invoice provides, don’t miss them.'), self::__('<b>Dashboard</b> - Is the place to get a quick status overview. See what was recently updated, what’s currently overdue or unpaid, and other important information about your business.'), self::__('<b>Reports</b> - Reports have advanced filtering and are highly customizable. All data is dynamically updated without reloading.') )
				) );

			$screen->add_help_tab( array(
					'id' => 'reports-tables',
					'title' => self::__( 'Report Tables' ),
					'content' => sprintf( '<p>%s</p><p>%s</p><p>%s</p><p>%s</p>', self::__('<b>Date filtering</b> is available and can be used to retrieve data in-between t, dates, or after a date, or before a date.'), self::__('<b>Modify columns</b> within the table with the “Show / hide columns” button.'), self::__('<b>Export</b> the table, filtered or not, to many formats, including CSV, Excel, PDF or your computers clipboard.'), self::__('Records are <em>limited to 2,500 items</em>. If you want to return more use the ‘si_reports_show_records’ filter.') )
				) );

			if ( !isset( $_GET['report'] ) ) {
				$screen->add_help_tab( array(
						'id' => 'reports-refresh',
						'title' => self::__( 'Dashboard Refresh' ),
						'content' => sprintf( '<p>%s</p><p><span class="cache_button_wrap casper clearfix"><a href="%s">%s</a></span></p></p>', si__('The reports dashboard is cached and if new invoices or estimates were just created the values under "Invoice Dashboard" may be out of date. Use the refresh button below to flush the cache and get the latest stats.'), add_query_arg( array( 'nocache' => 1 ) ), si__('Refresh') )
					) );
			}

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', self::__('For more information:') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/reports/', self::__('Documentation') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', self::__('Support') )
			);
		}
	}

}