<?php


/**
 * Admin help controller.
 *
 * @package Sprout_Invoice
 * @subpackage Help
 */
class SI_Help extends SI_Controller {
	const NONCE = 'si_pointer_nonce';
	protected static $pointer_key = 'si_pointer_hook';

	public static function init() {
		if ( is_admin() ) {
			add_filter( 'posts_where_request', array( __CLASS__, 'filter_admin_search' ) );
		}
		add_filter( 'admin_footer_text', array( __CLASS__, 'please_rate_si' ), 1, 2 );
	}

	public static function please_rate_si( $footer_text ) {
		if ( self::is_si_admin() ) {
			$footer_text = sprintf( __( 'Please support the future of <strong>Sprout Invoices</strong> by rating the free version <a href="%1$s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%1$s" target="_blank">WordPress.org</a>. Have an awesome %2$s!', 'sprout-invoices' ), 'http://wordpress.org/support/view/plugin-reviews/sprout-invoices?filter=5', date_i18n( 'l' ) );
		}
		return $footer_text;
	}

	//////////////////
	// Admin Search //
	//////////////////

	public static function filter_admin_search( $where = '' ) {
		if ( ! is_admin() || ! is_search() ) {
			return $where;
		}

		global $wpdb, $wp;
		if ( ! isset( $_REQUEST['s'] ) || $wp->query_vars['s'] !== $_REQUEST['s'] ) {
			return $where;
		}

		$custom_fields = apply_filters( 'si_admin_meta_search', array(), $wp->query_vars['post_type'] );
		if ( empty( $custom_fields ) ) {
			return $where;
		}

		foreach ( $custom_fields as $cf ) {
			// append meta search after title search
			$where = preg_replace(
				"/({$wpdb->posts}.post_title LIKE '%{$wp->query_vars['s']}%')/i",
				"$0 OR ({$wpdb->postmeta}.meta_key = '{$cf}' AND {$wpdb->postmeta}.meta_value LIKE '%{$wp->query_vars['s']}%')",
				$where
			);
		}
		// join meta and make distinct
		add_filter( 'posts_join_request', array( __CLASS__, 'filter_admin_search_join' ) );
		add_filter( 'posts_distinct_request', array( __CLASS__, 'filter_admin_search_distinct' ) );
		return $where;
	}

	public static function filter_admin_search_join( $join ) {
		global $wpdb;
		$join .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
		return $join;
	}

	public static function filter_admin_search_distinct( $distinct ) {
		$distinct = 'DISTINCT';
		return $distinct;
	}
}
