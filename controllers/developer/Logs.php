<?php

/**
 * Notification Model
 *
 *
 * @package Sprout_Invoices
 * @subpackage Records
 */
class SI_Dev_Logs extends SI_Controller {
	const LOG_TYPE = 'dev_log';
	const ERROR_TYPE = 'si_error';
	const LOG_OPTION = 'si_record_logs_option';

	private static $record_logs;
	private static $recorded_logs = array();
	private static $recorded_errors = array();

	public static function init() {
		// Admin option
		self::$record_logs = (bool) get_option( self::LOG_OPTION, 0 );

		// Register Settings
		add_filter( 'si_settings', array( __CLASS__, 'register_settings' ) );

		// after
		add_action( 'init', array( __CLASS__, 'record_stored_logs_and_errors' ), PHP_INT_MAX );

		// action to log
		add_action( 'si_log', array( __CLASS__, 'log' ), 10, 2 );
		add_action( 'si_error', array( __CLASS__, 'error' ), 10, 3 );

		// purge old logs
		add_action( self::DAILY_CRON_HOOK, array( __CLASS__, 'purge_old_logs' ) );
	}

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings( $settings = array() ) {
		// Settings
		$settings['si_developer'] = array(
				'title' => __( 'Logs', 'sprout-invoices' ),
				'description' => __( 'Developer settings.', 'sprout-invoices' ),
				'weight' => 2000,
				'tab' => 'advanced',
				'settings' => array(
					self::LOG_OPTION => array(
						'label' => __( 'Save Logs', 'sprout-invoices' ),
						'option' => array(
							'label' => __( 'Save all logs as a sprout apps records (dev_log).', 'sprout-invoices' ),
							'type' => 'checkbox',
							'default' => self::$record_logs,
							'value' => '1',
							'description' => __( 'Note: This should only be used for testing and troubleshooting. Records are found under Tools.', 'sprout-invoices' ),
							),
						),
					),
				);
		return $settings;
	}

	/**
	 * Log
	 * Writes to PHP error logs if DEBUG is enabled.
	 *
	 * @param  string $subject short title
	 * @param  array  $data    data
	 * @return null
	 */
	public static function log( $subject = '', $data = array() ) {

		if ( self::DEBUG ) {
			error_log( '+++' . $subject . ' +++++++++++++++++++++' );
			if ( ! empty( $data ) ) {
				error_log( print_r( $data, true ) );
				error_log( 'backtrace: ' . print_r( wp_debug_backtrace_summary( null, 0, false ), true ) );
				error_log( '--------------------- ' . $subject . ' END ---------------------' );
			}
		}

		if ( self::$record_logs ) {
			if ( function_exists( 'wp_get_current_user' ) ) {
				self::record_log( $subject, $data );
			} else {
				self::$recorded_logs[ $subject ] = $data;
			}
		}
	}

	/**
	 * Records log error
	 * Writes to PHP error logs if DEBUG is enabled.
	 *
	 * @param  string $subject short title
	 * @param  array  $data    data
	 * @return null
	 */
	public static function error( $subject = '', $data = array(), $record_error = true ) {

		if ( self::DEBUG ) {
			error_log( '--- ' . $subject . ' ---------------------' );
			if ( ! empty( $data ) ) {
				error_log( print_r( $data, true ) );
				// error_log( '--------------------- ' . $subject . ' END ---------------------' );
			}
		}

		if ( $record_error ) {
			if ( function_exists( 'wp_get_current_user' ) ) {
				self::record_log( $subject, $data, true );
			} else {
				self::$recorded_errors[ $subject ] = $data;
			}
		}
	}

	/**
	 * Fired on init(), logs are recorded if stored.
	 *
	 * @return null
	 */
	public static function record_stored_logs_and_errors() {
		// record logs
		if ( ! empty( self::$recorded_logs ) ) {
			foreach ( self::$recorded_logs as $subject => $data ) {
				self::record_log( $subject, $data );
			}
			// empty
			self::$recorded_logs = array();
		}
		// records errors
		if ( ! empty( self::$recorded_errors ) ) {
			foreach ( self::$recorded_errors as $subject => $data ) {
				self::record_log( $subject, $data , true );
			}
			// empty
			self::$recorded_errors = array();
		}
	}

	/**
	 * Utility function for si_new_record
	 * @param  string  $subject      short title
	 * @param  array   $data         array of data for the record
	 * @param  boolean $error        is the type an error or log
	 * @param  integer $associate_id NOT USED
	 * @return null
	 */
	public static function record_log( $subject = '', $data = array(), $error = false, $associate_id = 0 ) {
		$type = ( $error ) ? self::ERROR_TYPE : self::LOG_TYPE ;
		do_action( 'si_new_record',
			$data,
			$type,
			$associate_id,
			$subject,
		1 );
	}

	/**
	 * Periodically query for logs older than si_logs_purge_filter_delay (defaults to 15 days) and delete them.
	 *
	 * @return null
	 */
	public static function purge_old_logs() {
		$args = array(
			'post_type' => SI_Record::POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => SI_Record::TAXONOMY,
						'field'    => 'slug',
						'terms'    => self::LOG_TYPE,
					),
				),
		);

		add_filter( 'posts_where', array( __CLASS__, 'filter_where_with_when' ) ); // add filter to base return on dates
		$records = new WP_Query( $args );

		remove_filter( 'posts_where', array( __CLASS__, 'filter_where_with_when' ) ); // Remove filter
		foreach ( $records->posts as $record_id ) {
			if ( has_term( self::LOG_TYPE, SI_Record::TAXONOMY, $record_id ) ) { // confirm
				wp_delete_post( $record_id, true );
			}
		}
	}

	/**
	 * Filter WP_Query with a post_date
	 * @param  string $where query
	 * @return string        full query
	 */
	public static function filter_where_with_when( $where = '' ) {
		// posts 15+ old
		$offset = apply_filters( 'si_logs_purge_filter_delay', date_i18n( 'Y-m-d', strtotime( '-1 days' ) ), $where );
		$where .= " AND post_date <= '" . $offset . "'";
		return $where;
	}
}
