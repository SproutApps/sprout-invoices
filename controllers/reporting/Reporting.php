<?php


/**
 * Send notifications, apply shortcodes and create management screen.
 *
 * @package Sprout_Invoice
 * @subpackage Reporting
 */
class SI_Reporting extends SI_Dashboard {
	const CACHE_KEY_PREFIX = 'si_rprt_v3_';
	const AJAX_ACTION = 'si_report_data';
	const AJAX_NONCE = 'si_report_nonce';
	const CACHE_TIMEOUT = 172800; // 48 hours

	public static function init() {

		// Help Sections
		add_action( 'admin_menu', array( __CLASS__, 'help_sections' ) );

		add_action( 'wp_ajax_'.self::AJAX_ACTION,  array( __CLASS__, 'get_chart_data' ), 10, 0 );

		// Admin bar
		add_filter( 'si_admin_bar', array( __CLASS__, 'add_link_to_admin_bar' ), 15, 1 );

		// refresh cache
		add_filter( 'si_sprout_doc_scripts_localization',  array( __CLASS__, 'maybe_refresh_cache' ) );

		// Record updated
		add_action( 'save_post', array( __CLASS__, 'maybe_clear_report_cache' ), 10, 2 );
	}

	///////////////////////////
	// AJAX Chart Callbacks //
	///////////////////////////

	public static function get_chart_data() {
		if ( ! isset( $_REQUEST['security'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::AJAX_NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		$segment = ( isset( $_REQUEST['segment'] ) ) ? $_REQUEST['segment'] : 'weeks' ;
		$span = ( isset( $_REQUEST['span'] ) ) ? $_REQUEST['span'] : 'span' ;

		switch ( $_REQUEST['data'] ) {
			case 'invoice_payments':
				self::invoice_payments_chart( $segment, $span );
				break;
			case 'payments':
				self::payments_chart( $segment, $span );
				break;
			case 'balance_invoiced':
				self::balance_invoiced_chart( $segment, $span );
				break;
			case 'est_invoice_totals':
				self::est_invoice_totals_chart( $segment, $span );
				break;
			case 'req_to_inv_totals':
				self::req_to_inv_totals_chart( $segment, $span );
				break;
			case 'payment_statuses':
				self::payment_statuses_chart( $segment, $span );
				break;
			case 'invoice_statuses':
				self::invoice_statuses_chart( $segment, $span );
				break;
			case 'estimates_statuses':
				self::estimate_statuses_chart( $segment, $span );
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
				'payments' => array(),
			);
		$data = self::total_invoice_data_by_date_segment( $segment, $span );
		foreach ( $data as $seg => $seg_data ) {
			$return['labels'][] = self::get_segment_label( $seg, $segment );
			$return['invoices'][] = $seg_data['totals'];
			$return['payments'][] = $seg_data['paid'];
		}
		wp_send_json_success( $return );
	}

	/**
	 * Return two data sets
	 * @param  string  $segment
	 * @param  integer $span
	 * @return  json array
	 */
	public static function payments_chart( $segment = 'weeks', $span = 6 ) {
		$return = array(
				'labels' => array(),
				'totals' => array(),
				'payments' => array(),
			);
		$data = self::total_payment_data_by_date_segment( $segment, $span );
		foreach ( $data as $seg => $seg_data ) {
			$return['labels'][] = self::get_segment_label( $seg, $segment );
			$return['totals'][] = $seg_data['totals'];
			$return['payments'][] = $seg_data['payments'];
		}
		wp_send_json_success( $return );
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
				'paid' => array(),
			);
		$data = self::total_invoice_data_by_date_segment( $segment, $span );
		foreach ( $data as $seg => $seg_data ) {
			$return['labels'][] = self::get_segment_label( $seg, $segment );
			$return['payments'][] = $seg_data['paid'];
			$return['balances'][] = $seg_data['balance'];
		}
		wp_send_json_success( $return );
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
				'invoices' => array(),
			);
		$inv_data = self::total_invoice_data_by_date_segment( $segment, $span );
		$est_data = self::total_estimate_data_by_date_segment( $segment, $span );
		foreach ( $inv_data as $seg => $seg_data ) {
			$return['labels'][] = self::get_segment_label( $seg, $segment );
			$return['invoices'][] = $seg_data['invoices'];
			$return['estimates'][] = $est_data[ $seg ]['estimates'];
		}
		wp_send_json_success( $return );
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
				'invoices' => array(),
			);
		$est_data = self::total_estimate_data_by_date_segment( $segment, $span );
		foreach ( $est_data as $seg => $seg_data ) {
			$return['labels'][] = self::get_segment_label( $seg, $segment );
			$return['requests'][] = $seg_data['requests'];
			$return['invoices_generated'][] = $seg_data['invoices_generated'];
		}
		wp_send_json_success( $return );
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
				'status_void' => 0,
			);
		$data = array_slice( self::total_payment_data_by_date_segment(), -3 );
		foreach ( $data as $segment => $seg_data ) {
			$return['status_pending'] += $seg_data['status_pending'];
			$return['status_complete'] += $seg_data['status_complete'];
			$return['status_void'] += $seg_data['status_void'];
		}
		header( 'Content-type: application/json' );
		echo wp_json_encode( $return );
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
			'status_writeoff' => 0,
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
		echo wp_json_encode( $return );
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
			'status_declined' => 0,
			);
		$data = array_slice( self::total_estimate_data_by_date_segment(), -3 );
		foreach ( $data as $segment => $seg_data ) {
			$return['status_request'] += $seg_data['status_request'];
			$return['status_pending'] += $seg_data['status_pending'];
			$return['status_approved'] += $seg_data['status_approved'];
			$return['status_declined'] += $seg_data['status_declined'];
		}
		header( 'Content-type: application/json' );
		echo wp_json_encode( $return );
		exit();
	}



	/////////////////////////
	// Data for Reporting //
	/////////////////////////

	public static function total_invoice_data_by_date_segment( $segment = 'weeks', $span = 6 ) {

		// Return cache if present.
		$cache = self::get_cache( __FUNCTION__ . $segment . $span );
		if ( $cache ) {
			return $cache;
		}

		$frames = self::walk_back_x_span( $span, $segment );
		$date_format = self::get_segment_date_format( $segment );
		$year = date( 'Y', strtotime( $span . ' ' . $segment . ' ago' ) );
		$start_date = date( 'Y-m-d', strtotime( $span . ' ' . $segment . ' ago' ) );

		// Build data array, without a explicit build segments without posts will not show.
		$data = array();
		foreach ( $frames as $frame_date ) {
			$data[ $frame_date ] = array(
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
					'status_writeoff' => 0,
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
						'after'     => $start_date,
						'inclusive' => true,
					),
				),
			);
		$invoices = new WP_Query( $args );
		foreach ( $invoices->posts as $invoice_id ) {
			$invoice = SI_Invoice::get_instance( $invoice_id );
			$frame = get_the_time( $date_format, $invoice_id );
			$data[ $frame ]['invoices'] += 1;
			$data[ $frame ]['payments'] += count( $invoice->get_payments() );
			$data[ $frame ]['totals'] += si_get_invoice_calculated_total( $invoice_id );
			$data[ $frame ]['subtotals'] += si_get_invoice_subtotal( $invoice_id );
			$data[ $frame ]['paid'] += si_get_invoice_payments_total( $invoice_id );
			$data[ $frame ]['balance'] += si_get_invoice_balance( $invoice_id );
			switch ( get_post_status( $invoice_id ) ) {
				case 'draft':
				case SI_Invoice::STATUS_TEMP:
					$data[ $frame ]['status_temp'] += 1;
					break;
				case SI_Invoice::STATUS_PENDING:
					$data[ $frame ]['status_pending'] += 1;
					break;
				case SI_Invoice::STATUS_PARTIAL:
					$data[ $frame ]['status_partial'] += 1;
					break;
				case SI_Invoice::STATUS_PAID:
					$data[ $frame ]['status_complete'] += 1;
					break;
				case SI_Invoice::STATUS_WO:
					$data[ $frame ]['status_writeoff'] += 1;
					break;
				default:
					break;
			}
		}
		return self::set_cache( __FUNCTION__ . $segment . $span, $data );
	}

	public static function total_invoice_data( $span = 'century' ) {
		// Return cache if present.
		$cache = self::get_cache( __FUNCTION__ . $span );
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
				'status_writeoff' => 0,
			);
		$args = array(
			'post_type' => SI_Invoice::POST_TYPE,
			'post_status' => array( SI_Invoice::STATUS_PENDING, SI_Invoice::STATUS_PARTIAL, SI_Invoice::STATUS_PAID ),
			'posts_per_page' => -1,
			'fields' => 'ids',
			);

		// If date filtered
		if ( 'century' !== $span ) {
			$args = self::_get_date_query( $span, $args );
			$expire = self::_get_date_query( $span, $args, true );
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
		return self::set_cache( __FUNCTION__ . $span, $data, $expire );
	}

	public static function total_estimate_data_by_date_segment( $segment = 'weeks', $span = 6 ) {
		// Return cache if present.
		$cache = self::get_cache( __FUNCTION__ . $segment . $span );
		if ( $cache ) {
			return $cache;
		}

		$frames = self::walk_back_x_span( $span, $segment );
		$date_format = self::get_segment_date_format( $segment );
		$year = date( 'Y', strtotime( $span . ' ' . $segment . ' ago' ) );
		$start_date = date( 'Y-m-d', strtotime( $span . ' ' . $segment . ' ago' ) );

		// Build data array, without a explicit build segments without posts will not show.
		$data = array();
		foreach ( $frames as $frame_date ) {
			$data[ $frame_date ] = array(
					'estimates' => 0,
					'requests' => 0,
					'totals' => 0,
					'subtotals' => 0,
					'invoices_generated' => 0,
					'status_request' => 0,
					'status_pending' => 0,
					'status_approved' => 0,
					'status_declined' => 0,
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
						'after'     => $start_date,
						'inclusive' => true,
					),
				),
			);
		$estimates = new WP_Query( $args );
		foreach ( $estimates->posts as $estimate_id ) {
			$frame = get_the_time( $date_format, $estimate_id );
			$data[ $frame ]['estimates'] += 1;
			$data[ $frame ]['totals'] += si_get_estimate_total( $estimate_id );
			$data[ $frame ]['subtotals'] += si_get_estimate_subtotal( $estimate_id );
			if ( si_get_estimate_invoice_id( $estimate_id ) ) {
				$data[ $frame ]['invoices_generated'] += 1;
			}
			// If there are submission fields than it's a request
			if ( si_is_estimate_submission( $estimate_id ) ) {
				$data[ $frame ]['requests'] += 1;
			}
			switch ( get_post_status( $estimate_id ) ) {
				case SI_Estimate::STATUS_REQUEST:
					$data[ $frame ]['status_request'] += 1;
					break;
				case SI_Estimate::STATUS_PENDING:
					$data[ $frame ]['status_pending'] += 1;
					break;
				case SI_Estimate::STATUS_APPROVED:
					$data[ $frame ]['status_approved'] += 1;
					break;
				case SI_Estimate::STATUS_DECLINED:
					$data[ $frame ]['status_declined'] += 1;
					break;
				default:
					break;
			}
		}
		return self::set_cache( __FUNCTION__ . $segment . $span, $data, self::CACHE_TIMEOUT );
	}

	public static function total_payment_data( $span = 'century' ) {
		// Return cache if present.
		$cache = self::get_cache( __FUNCTION__.$span );
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
				'status_void' => 0,
			);
		$args = array(
			'post_type' => SI_Payment::POST_TYPE,
			'post_status' => 'any', // Totals are not tallied for voided payments below.
			'posts_per_page' => -1,
			'orderby' => 'date',
			'fields' => 'ids',
			);

		// If date filtered
		if ( 'century' !== $span ) {
			$args = self::_get_date_query( $span, $args );
			$expire = self::_get_date_query( $span, $args, true );
		}
		$payments = new WP_Query( $args );
		foreach ( $payments->posts as $payment_id ) {
			$payment = SI_Payment::get_instance( $payment_id );
			$data['payments'] += 1;
			switch ( $payment->get_status() ) {
				case SI_Payment::STATUS_PENDING:
					$data['status_pending'] += 1;
					$data['totals'] += $payment->get_amount();
					break;
				case SI_Payment::STATUS_AUTHORIZED:
					$data['status_authorized'] += 1;
					$data['totals'] += $payment->get_amount();
					break;
				case SI_Payment::STATUS_COMPLETE:
					$data['status_complete'] += 1;
					$data['totals'] += $payment->get_amount();
					break;
				case SI_Payment::STATUS_PARTIAL:
					$data['status_partial'] += 1;
					$data['totals'] += $payment->get_amount();
					break;
				case SI_Payment::STATUS_VOID:
				case SI_Payment::STATUS_REFUND:
					$data['status_void'] += 1;
					break;
				default:
					break;
			}
		}

		return self::set_cache( __FUNCTION__.$span, $data, $expire );
	}

	public static function _get_date_query( $span = 'century', $args = array(), $return_expiration = false ) {
		switch ( $span ) {
			case 'week':
				$args['date_query'] = array(
					array(
						'week' => date( 'W', strtotime( 'this week' ) ),
						'year' => date( 'o', strtotime( 'this week' ) ),
						'inclusive' => true,
					),
				);
				$expire = strtotime( 'tomorrow' ) -current_time( 'timestamp' );
				break;
			case 'lastweek':
				$args['date_query'] = array(
					array(
						'week' => date( 'W', strtotime( '-1 week' ) ),
						'year' => date( 'o', strtotime( '-1 week' ) ),
						'inclusive' => true,
					),
				);
				$expire = strtotime( 'next week' ) -current_time( 'timestamp' );
				break;
			case 'month':
				$args['date_query'] = array(
					array(
						'month' => date( 'm', strtotime( 'first day of this month' ) ),
						'year' => date( 'o', current_time( 'timestamp' ) ),
						'inclusive' => true,
					),
				);
				$expire = strtotime( 'tomorrow' ) -current_time( 'timestamp' );
				break;
			case 'lastmonth':
				$args['date_query'] = array(
					array(
						'month' => date( 'm', strtotime( '-1 month' ) ),
						'year' => date( 'o', strtotime( '-1 month' ) ),
						'inclusive' => true,
					),
				);
				$expire = strtotime( 'first day of next month' ) -current_time( 'timestamp' );
				break;
			case 'year':
				$args['date_query'] = array(
					array(
						'year' => date( 'Y', current_time( 'timestamp' ) ),
						'inclusive' => true,
					),
				);
				$expire = strtotime( 'tomorrow' ) -current_time( 'timestamp' );
				break;
			case 'lastyear':
				$args['date_query'] = array(
					array(
						'year' => date( 'Y', strtotime( 'first day of previous year' ) ),
						'inclusive' => true,
					),
				);
				$expire = strtotime( 'last day of year' ) -current_time( 'timestamp' );
				break;
			default:
				break;
		}
		if ( $return_expiration ) {
			return $expire;
		}
		return $args;
	}

	public static function total_payment_data_by_date_segment( $segment = 'weeks', $span = 6 ) {
		// Return cache if present.
		$cache = self::get_cache( __FUNCTION__ . $segment . $span );
		if ( $cache ) {
			return $cache;
		}

		// FUTURE charts should be dynamic based on selected segment.
		$weeks = self::walk_back_x_span( $span, $segment );
		$year = date( 'Y', strtotime( $span . ' weeks ago' ) );

		// Build data array, without a explicit build segments without posts will not show.
		$data = array();
		foreach ( $weeks as $week_num ) {
			$data[ $week_num ] = array(
					'payments' => 0,
					'totals' => 0,
					'status_pending' => 0,
					'status_authorized' => 0,
					'status_complete' => 0,
					'status_partial' => 0,
					'status_void' => 0,
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
						'after'     => date( 'Y-m-d', strtotime( $year . 'W' . array_shift( $weeks ) ) ),
						'inclusive' => true,
					),
				),
			);
		$payments = new WP_Query( $args );
		foreach ( $payments->posts as $payment_id ) {
			$payment = SI_Payment::get_instance( $payment_id );
			if ( in_array( $payment->get_status(), array( SI_Payment::STATUS_VOID, SI_Payment::STATUS_REFUND, SI_Payment::STATUS_RECURRING, SI_Payment::STATUS_CANCELLED ) ) ) {
				continue;
			}
			$week = get_the_time( 'W', $payment_id );
			$data[ $week ]['payments'] += 1;
			$data[ $week ]['totals'] += $payment->get_amount();
			switch ( get_post_status( $payment_id ) ) {
				case SI_Payment::STATUS_PENDING:
					$data[ $week ]['status_pending'] += 1;
					break;
				case SI_Payment::STATUS_AUTHORIZED:
					$data[ $week ]['status_authorized'] += 1;
					break;
				case SI_Payment::STATUS_COMPLETE:
					$data[ $week ]['status_complete'] += 1;
					break;
				case SI_Payment::STATUS_PARTIAL:
					$data[ $week ]['status_partial'] += 1;
					break;
				case SI_Payment::STATUS_VOID:
					$data[ $week ]['status_void'] += 1;
					break;
				default:
					break;
			}
		}
		return self::set_cache( __FUNCTION__ . $segment . $span, $data, self::CACHE_TIMEOUT );
	}

	//////////////
	// Caching //
	//////////////

	public static function maybe_refresh_cache( $js_object = array() ) {
		$js_object['reports_refresh_cache'] = false;
		if ( isset( $_GET['reports_refresh_cache'] ) ) {
			$js_object['reports_refresh_cache'] = true;
		}
		return $js_object;
	}

	public static function get_cache( $data_name = '' ) {
		if ( self::DEBUG || isset( $_REQUEST['reports_refresh_cache'] ) ) { // If dev than don't cache.
			return false;
		}

		if ( apply_filters( 'si_disable_reporting_cache', false ) ) {
			return false;
		}

		$key = self::get_hashed_transient_key( $data_name );
		$cache = get_transient( $key );

		// If cache is empty return false.
		return ( ! empty( $cache ) ) ? $cache : false;
	}

	public static function set_cache( $data_name = '', $data = array(), $expire = 0 ) {
		$timeout = ( $expire > 0 ) ? $expire : self::CACHE_TIMEOUT ;
		$key = self::get_hashed_transient_key( $data_name );

		set_transient( $key, $data, $timeout ); // cache for a week.
		return $data;
	}

	public static function delete_cache( $data_name = '' ) {
		$key = self::get_hashed_transient_key( $data_name );
		delete_transient( $key );
	}

	public static function get_hashed_transient_key( $data_name ) {
		$data_name = md5( $data_name );
		return substr( self::CACHE_KEY_PREFIX . $data_name, 0, 45 );
	}

	public static function maybe_clear_report_cache( $post_id, $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			return;
		}
		if ( ! in_array( $post->post_type, array( SI_Invoice::POST_TYPE, SI_Estimate::POST_TYPE, SI_Client::POST_TYPE, SI_Project::POST_TYPE, SI_Payment::POST_TYPE ) ) ) {
			return;
		}

		self::clear_report_cache();
	}

	public static function clear_report_cache() {
		global $wpdb;
		$sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s";
		$sql = $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' . self::CACHE_KEY_PREFIX ) . '%', $wpdb->esc_like( '_transient_timeout_' . self::CACHE_KEY_PREFIX ) . '%' );
		$result = $wpdb->query( $sql );
	}

	//////////////
	// Utility //
	//////////////

	public static function walk_back_x_span( $x = 6, $span = 'months' ) {
		$date_format = self::get_segment_date_format( $span );
		$stretch = array( date( $date_format ) );

		for ( $i = 1; $i <= $x; $i++ ) {
			switch ( $span ) {
				case 'months':
					$stretch[] = date( $date_format, strtotime( date( 'Y-m-01' ) . " -$i " . $span ) );
					break;
				case 'weeks':
					$stretch[] = date( $date_format, strtotime( "-$i week" ) );
					break;
				case 'days':
					$stretch[] = date( $date_format, strtotime( date( 'Y-m-d' ) . " -$i " . $span ) );
					break;

				default:
					# code...
					break;
			}
		}
		return array_reverse( $stretch );
	}

	public static function get_segment_date_format( $segment ) {
		switch ( $segment ) {
			case 'years':
				$format = 'Y';
				break;
			case 'months':
				$format = 'Y-m';
				break;
			case 'days':
				$format = 'Y-m-d';
				break;

			case 'weeks':
			default:
				$format = 'W';
				break;
		}

		return $format;
	}

	public static function get_segment_label( $current_segment = '', $segment = 'weeks' ) {
		switch ( $segment ) {
			case 'years':
				$format = 'Y';
				$current_segment_time = strtotime( 'Y' . $current_segment );
				break;
			case 'months':
				$format = 'M y';
				$current_segment_time = strtotime( date( 'Y' ) . 'M' . $current_segment );
				break;
			case 'weeks':
				$format = 'M d';
				$current_segment_time = strtotime( date( 'Y' ) . 'W' . $current_segment );
				break;
			case 'days':
			default:
				$format = 'M d';
				$current_segment_time = strtotime( date( 'Y' ) . 'd' . $current_segment );
				break;
		}

		return date_i18n( $format, $current_segment_time );
	}

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'si_dashboard',
			'title' => __( 'Dashboard', 'sprout-invoices' ),
			'href' => admin_url( 'admin.php?page=sprout-apps/settings&tab=reporting' ),
			'weight' => 0,
		);
		return $items;
	}

	////////////////
	// Admin Help //
	////////////////

	public static function help_sections() {
		add_action( 'load-dashboard_page_sprout-invoices-stats', array( __CLASS__, 'help_tabs' ) );
	}

	public static function help_tabs() {
		// get screen and add sections.
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id' => 'reports-about',
			'title' => __( 'About Reports', 'sprout-invoices' ),
			'content' => sprintf( '<p>%s</p><p>%s</p><p>%s</p>', __( 'The Reports dashboard links to the many single reports that Sprout Invoice provides, don’t miss them.', 'sprout-invoices' ), __( '<b>Dashboard</b> - Is the place to get a quick status overview. See what was recently updated, what’s currently overdue or unpaid, and other important information about your business.', 'sprout-invoices' ), __( '<b>Reports</b> - Reports have advanced filtering and are highly customizable. All data is dynamically updated without reloading.', 'sprout-invoices' ) ),
		) );

		$screen->add_help_tab( array(
			'id' => 'reports-tables',
			'title' => __( 'Report Tables', 'sprout-invoices' ),
			'content' => sprintf( '<p>%s</p><p>%s</p><p>%s</p>', __( '<b>Date filtering</b> is available and can be used to retrieve data between two dates, after a date, or before a date.', 'sprout-invoices' ), __( '<b>Export</b> the table, filtered or not, to many formats, including CSV, Excel, PDF or your computers clipboard.', 'sprout-invoices' ), __( 'Records are <em>limited to 2,500 items</em>. If you want to return more use the ‘si_reports_show_records’ filter.', 'sprout-invoices' ) ),
		) );

		if ( ! isset( $_GET['report'] ) ) {
			$screen->add_help_tab( array(
				'id' => 'reports-refresh',
				'title' => __( 'Dashboard Refresh', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p><span class="cache_button_wrap casper clearfix"><a href="%s">%s</a></span></p></p>', __( 'The reports dashboard is cached and if new invoices or estimates were just created the values under "Invoice Dashboard" may be out of date. Use the refresh button below to flush the cache and get the latest stats.', 'sprout-invoices' ), esc_url( add_query_arg( array( 'reports_refresh_cache' => 1 ) ) ), __( 'Refresh', 'sprout-invoices' ) ),
			) );
		}

		$screen->set_help_sidebar(
			sprintf( '<p><strong>%s</strong></p>', __( 'For more information:', 'sprout-invoices' ) ) .
			sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/reports/', __( 'Documentation', 'sprout-invoices' ) ) .
			sprintf( '<p><a href="%s" class="button">%s</a></p>', si_get_sa_link( 'https://sproutapps.co/support/' ), __( 'Support', 'sprout-invoices' ) )
		);
	}
}
