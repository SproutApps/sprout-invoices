<?php

/**
 * Admin capabilities controller.
 *
 * @package Sprout_Invoice
 * @subpackage Capabilities
 */
class SI_Admin_Capabilities extends SI_Controller {

	public static function init() {
		add_action( 'si_plugin_activation_hook', array( __CLASS__, 'maybe_add_caps' ), 100 );
		add_action( 'si_plugin_deactivation_hook', array( __CLASS__, 'remove_caps' ) );
	}

	public static function maybe_add_caps( $new_version = 0 ) {
		$si_version = get_option( 'si_current_version', self::SI_VERSION );
		if ( version_compare( 7.0, $si_version, '<' ) ) {
			self::add_caps();
		}
	}

	public static function si_caps() {
		$caps = array(
			// 'role' => 'new_cap',
			'administrator' => array(
					'manage_sprout_invoices_options',
					'view_sprout_invoices_dashboard',
					'manage_sprout_invoices_payments',
					'manage_sprout_invoices_records',
					'manage_sprout_invoices_importer',
					'edit_sprout_invoices',
					'delete_sprout_invoices',
					'publish_sprout_invoices',
				),
			'editor' => array(
					'edit_sprout_invoices',
					'delete_sprout_invoices',
					'publish_sprout_invoices',
				),
			'author' => array(
					'edit_sprout_invoices',
					'delete_sprout_invoices',
					'publish_sprout_invoices',
				),
			'contributer' => array(
					'edit_sprout_invoices',
				),
			);
		return $caps;
	}

	/**
	 * Add new capabilities
	 */
	public static function add_caps() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			foreach ( self::si_caps() as $role => $new_caps ) {
				foreach ( $new_caps as $new_cap ) {
					$wp_roles->add_cap( $role, $new_cap );
				}
			}
		}
	}


	/**
	 * Remove core post type capabilities (called on uninstall)
	 */
	public static function remove_caps() {
		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			foreach ( self::si_caps() as $role => $new_caps ) {
				foreach ( $new_caps as $new_cap ) {
					$wp_roles->remove_cap( $role, $new_cap );
				}
			}
		}
	}
}