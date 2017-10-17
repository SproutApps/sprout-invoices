<?php

/**
 * Doc Comments Controller
 *
 * @package Sprout_Invoice
 * @subpackage Line_Item_Types
 */
class SI_Line_Items extends SI_Controller {

	public static function init() {

		// views
		add_action( 'si_get_line_item_type_section', array( __CLASS__, 'item_type_section' ) );
		add_action( 'si_get_line_item_totals_section', array( __CLASS__, 'line_item_totals_section' ) );
		add_action( 'si_line_item_build_option', array( __CLASS__, 'item_build_option' ), 10, 3 );

		// Line Items
		add_action( 'si_doc_line_items', array( __CLASS__, 'front_end_line_items' ) );

		// front end line items
		add_filter( 'si_format_front_end_line_item_value', array( __CLASS__, 'format_front_end_line_item' ), 20, 3 );
		add_filter( 'si_line_item_columns', array( __CLASS__, 'remove_unnecessary_front_end_columns' ), 100, 2 );

		// modify add
		add_filter( 'si_add_line_item', array( __CLASS__, 'add_line_items' ) );

		// Enqueue
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ), 20 );

		add_action( 'wp_ajax_sa_get_item_option',  array( __CLASS__, 'maybe_get_item' ), 10, 0 );

	}

	public static function get_default_type() {
		$default_type = 'task';
		return apply_filters( 'si_get_default_line_item_type', $default_type, self::line_item_types() );
	}

	public static function line_item_types() {
		$types = array(
				'task' => __( 'Task', 'sprout-invoices' ),
				'service' => __( 'Service', 'sprout-invoices' ),
				'product' => __( 'Product', 'sprout-invoices' ),
			);
		return apply_filters( 'si_line_item_types', $types );
	}

	/**
	 * Filterable set of options/columns for each item type.
	 * NOTE: If a new option/column is added to one it needs to be added
	 * to any other, i.e. sku is hidden for those types that don't need it
	 * since otherwise the data wouldn't be saved.
	 * @param  string $type string
	 * @return array
	 */
	public static function line_item_columns( $type = '', $item_data = array(), $position = 0, $prev_type = '', $has_children = false ) {
		if ( '' === $type ) {
			$type = self::get_default_type();
		}
		$columns = array(
				'_id' => array(
						'type' => 'hidden',
						'value' => mt_rand(),
						'weight' => 0,
					),
			);
		switch ( $type ) {
			case 'service':
				$columns += array(
						'desc' => array(
								'label' => __( 'Service', 'sprout-invoices' ),
								'type' => 'textarea',
								'calc' => false,
								'hide_if_parent' => false,
								'weight' => 1,
							),
						'sku' => array(
								'type' => 'hidden',
								'placeholder' => '',
								'calc' => false,
								'numeric' => false,
								'weight' => 3,
							),
						'rate' => array(
								'label' => __( 'Price', 'sprout-invoices' ),
								'type' => 'small-input',
								'calc' => false,
								'hide_if_parent' => true,
								'weight' => 5,
							),
						'qty' => array(
								'type' => 'hidden',
								'placeholder' => 1,
								'calc' => true,
								'hide_if_parent' => true,
								'weight' => 10,
							),
						'tax' => array(
								'label' => sprintf( '&#37; <span class="helptip" title="%s"></span>', __( 'A percentage adjustment per line item, i.e. tax or discount', 'sprout-invoices' ) ),
								'type' => 'small-input',
								'calc' => false,
								'hide_if_parent' => true,
								'weight' => 15,
							),
						'total' => array(
								'label' => __( 'Amount', 'sprout-invoices' ),
								'type' => 'total',
								'placeholder' => sa_get_formatted_money( 0 ),
								'calc' => true,
								'hide_if_parent' => false,
								'weight' => 50,
							),
					);
				break;
			case 'product':
				$columns += array(
						'desc' => array(
								'label' => __( 'Product', 'sprout-invoices' ),
								'type' => 'textarea',
								'calc' => false,
								'hide_if_parent' => false,
								'weight' => 1,
							),
						'sku' => array(
								'label' => __( 'SKU', 'sprout-invoices' ),
								'type' => 'input',
								'calc' => false,
								'numeric' => false,
								'hide_if_parent' => true,
								'weight' => 5,
							),
						'rate' => array(
								'label' => __( 'Price', 'sprout-invoices' ),
								'type' => 'small-input',
								'calc' => false,
								'hide_if_parent' => true,
								'weight' => 10,
							),
						'qty' => array(
								'label' => __( 'Qty', 'sprout-invoices' ),
								'type' => 'small-input',
								'calc' => true,
								'hide_if_parent' => true,
								'weight' => 15,
							),
						'tax' => array(
								'label' => sprintf( '&#37; <span class="helptip" title="%s"></span>', __( 'A percentage adjustment per line item, i.e. tax or discount', 'sprout-invoices' ) ),
								'type' => 'small-input',
								'calc' => false,
								'hide_if_parent' => true,
								'weight' => 20,
							),
						'total' => array(
								'label' => __( 'Amount', 'sprout-invoices' ),
								'type' => 'total',
								'placeholder' => sa_get_formatted_money( 0 ),
								'calc' => true,
								'hide_if_parent' => false,
								'weight' => 50,
							),
					);
				break;

			default:
				$columns += array(
						'desc' => array(
								'label' => __( 'Task', 'sprout-invoices' ),
								'type' => 'textarea',
								'calc' => false,
								'hide_if_parent' => false,
								'weight' => 1,
							),
						'sku' => array(
								'type' => 'hidden',
								'placeholder' => '',
								'calc' => false,
								'numeric' => false,
								'weight' => 3,
							),
						'rate' => array(
								'label' => __( 'Rate', 'sprout-invoices' ),
								'type' => 'small-input',
								'placeholder' => '120',
								'calc' => false,
								'hide_if_parent' => true,
								'weight' => 5,
							),
						'qty' => array(
								'label' => __( 'Qty', 'sprout-invoices' ),
								'type' => 'small-input',
								'placeholder' => 1,
								'calc' => true,
								'hide_if_parent' => true,
								'weight' => 10,
							),
						'tax' => array(
								'label' => sprintf( '&#37; <span class="helptip" title="%s"></span>', __( 'A percentage adjustment per line item, i.e. tax or discount', 'sprout-invoices' ) ),
								'type' => 'small-input',
								'placeholder' => 0,
								'calc' => false,
								'hide_if_parent' => true,
								'weight' => 15,
							),
						'total' => array(
								'label' => __( 'Amount', 'sprout-invoices' ),
								'type' => 'total',
								'placeholder' => sa_get_formatted_money( 0 ),
								'calc' => true,
								'hide_if_parent' => false,
								'weight' => 50,
							),
					);
				break;
		}
		$columns = apply_filters( 'si_line_item_columns', $columns, $type, $item_data, $position, $prev_type, $has_children );
		uasort( $columns, array( __CLASS__, 'sort_by_weight' ) );
		return $columns;
	}

	public static function remove_unnecessary_front_end_columns( $columns = array(), $type = '' ) {

		if ( apply_filters( 'si_remove_unnecessary_front_end_columns', true ) && is_admin() ) {
			return $columns;
		}
		$line_items = si_get_doc_line_items( get_the_id() );
		if ( empty( $columns ) ) {
			return $columns;
		}
		foreach ( $columns as $key => $column ) {
			$has_column = false;
			foreach ( $line_items as $position => $data ) {
				if ( ! isset( $data['type'] ) || '' === $data['type'] ) {
					$data['type'] = self::get_default_type();
				}
				if ( $data['type'] !== $type ) {
					continue;
				}
				if ( isset( $data[ $key ] ) && '' !== $data[ $key ] ) {
					$has_column = true;
					break;
				}
			}
			if ( ! $has_column ) {
				unset( $columns[ $key ] );
			}
		}
		return $columns;
	}

	public static function format_front_end_line_item( $value = '', $column_slug = '', $item_data = array() ) {
		switch ( $column_slug ) {
			case 'total':
			case 'subtotal':
				$value = sa_get_formatted_money( $value );
				break;
			case 'desc':
				$value = apply_filters( 'the_content', $value );
				break;
			case 'rate':
				$value = sa_get_formatted_money( $value );
				break;
			case 'tax':
				if ( is_numeric( $value ) ) {
					$value = $value . '%';
				} else {
					$value = '';
				}
				break;

			default:
				break;
		}
		$localeconv = SI_Admin_Settings::localeconv_options();
		if ( isset( $localeconv['mon_decimal_point'] ) && '.' === $localeconv['mon_decimal_point'] ) {
			if ( apply_filters( 'si_filter_zerod_decimals', true ) ) {
				$value = str_replace( '.00', '', $value );
			}
		}
		return $value;
	}


	public static function line_item_totals( $doc_id = 0 ) {
		if ( ! $doc_id ) {
			$doc_id = get_the_id();
		}
		$context = si_get_doc_context( $doc_id );
		$totals = array();
		switch ( $context ) {
			case 'estimate':
				$totals = self::estimate_line_item_totals( $doc_id );
				break;
			case 'invoice':
			default:
				$totals = self::invoice_line_item_totals( $doc_id );
				break;
		}
		return apply_filters( 'si_line_item_totals', $totals, $doc_id );
	}

	/**
	 * line items totals for estimates
	 * TODO move to invoices controller?
	 * @param  integer $doc_id
	 * @return array
	 */
	public static function estimate_line_item_totals( $doc_id = 0 ) {
		if ( ! $doc_id ) {
			$doc_id = get_the_id();
		}
		$totals = array(
				'subtotal' => array(
						'label' => __( 'Subtotal', 'sprout-invoices' ),
						'value' => si_get_estimate_subtotal( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_estimate_subtotal( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'hide' => false,
						'weight' => 10,
					),
				'taxes' => array(
						'label' => __( 'Taxes', 'sprout-invoices' ),
						'value' => si_get_estimate_taxes_total( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_estimate_taxes_total( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'hide' => ( 0.01 > (float) si_get_estimate_taxes_total( $doc_id ) ),
						'admin_hide' => ( 0.01 > (float) si_get_estimate_taxes_total( $doc_id ) ),
						'weight' => 20,
					),
				'discounts' => array(
						'label' => __( 'Discount', 'sprout-invoices' ),
						'value' => si_get_estimate_discount_total( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_estimate_discount_total( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'hide' => ( 0.01 > (float) si_get_estimate_discount_total( $doc_id ) ),
						'admin_hide' => ( 0.01 > (float) si_get_estimate_discount_total( $doc_id ) ),
						'weight' => 20,
					),
				'total' => array(
						'label' => __( 'Total', 'sprout-invoices' ),
						'value' => si_get_estimate_total( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_estimate_total( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'helptip' => __( 'Total includes discounts and other fees.', 'sprout-invoices' ),
						'hide' => false,
						'weight' => 100,
					),
			);

		$totals = apply_filters( 'estimate_line_item_totals', $totals, $doc_id );
		uasort( $totals, array( __CLASS__, 'sort_by_weight' ) );
		return $totals;
	}

	/**
	 * line items totals for invoices
	 * TODO move to invoices controller?
	 * @param  integer $doc_id
	 * @return array
	 */
	public static function invoice_line_item_totals( $doc_id = 0 ) {
		if ( ! $doc_id ) {
			$doc_id = get_the_id();
		}
		$totals = array(
				'subtotal' => array(
						'label' => __( 'Subtotal', 'sprout-invoices' ),
						'value' => si_get_invoice_subtotal( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_invoice_subtotal( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'hide' => false,
						'admin_hide' => false,
						'weight' => 10,
					),
				'taxes' => array(
						'label' => __( 'Taxes', 'sprout-invoices' ),
						'value' => si_get_invoice_taxes_total( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_invoice_taxes_total( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'hide' => ( 0.01 > (float) si_get_invoice_taxes_total( $doc_id ) ),
						'admin_hide' => ( 0.01 > (float) si_get_invoice_taxes_total( $doc_id ) ),
						'weight' => 20,
					),
				'discounts' => array(
						'label' => __( 'Discount', 'sprout-invoices' ),
						'value' => si_get_invoice_discount_total( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_invoice_discount_total( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'hide' => ( 0.01 > (float) si_get_invoice_discount_total( $doc_id ) ),
						'admin_hide' => ( 0.01 > (float) si_get_invoice_discount_total( $doc_id ) ),
						'weight' => 20,
					),
				'total' => array(
						'label' => __( 'Total', 'sprout-invoices' ),
						'value' => si_get_invoice_calculated_total( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_invoice_calculated_total( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'helptip' => __( 'Total includes discounts and other fees.', 'sprout-invoices' ),
						'hide' => false,
						'admin_hide' => false,
						'weight' => 30,
					),
				'pending_payments' => array(
						'label' => __( 'Pending Payments', 'sprout-invoices' ),
						'value' => si_get_invoice_pending_payments_total( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_invoice_pending_payments_total( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'hide' => ( 0.01 > (float) si_get_invoice_pending_payments_total( $doc_id ) ),
						'admin_hide' => ( 0.01 > (float) si_get_invoice_pending_payments_total( $doc_id ) ),
						'weight' => 40,
					),
				'payments' => array(
						'label' => __( 'Payments', 'sprout-invoices' ),
						'value' => si_get_invoice_payments_total( $doc_id, false ),
						'formatted' => sa_get_formatted_money( si_get_invoice_payments_total( $doc_id, false ), $doc_id, '<span class="money_amount">%s</span>' ),
						'hide' => ( 0.01 > (float) si_get_invoice_payments_total( $doc_id, false ) ),
						'admin_hide' => ( 0.01 > (float) si_get_invoice_payments_total( $doc_id, false ) ),
						'weight' => 40,
					),
				'balance' => array(
						'label' => __( 'Balance', 'sprout-invoices' ),
						'value' => si_get_invoice_balance( $doc_id ),
						'formatted' => sa_get_formatted_money( si_get_invoice_balance( $doc_id ), $doc_id, '<span class="money_amount">%s</span>' ),
						'hide' => ( (float) si_get_invoice_balance( $doc_id ) === (float) si_get_invoice_calculated_total( $doc_id ) ),
						'admin_hide' => ( 0.01 > (float) si_get_invoice_payments_total( $doc_id ) ),
						'weight' => 100,
					),
			);

		$totals = apply_filters( 'invoice_line_item_totals', $totals, $doc_id );
		uasort( $totals, array( __CLASS__, 'sort_by_weight' ) );
		return $totals;
	}

	//////////////
	// Enqueue //
	//////////////

	public static function register_resources() {
		// admin js
		wp_register_script( 'si_line_items', SI_URL . '/resources/admin/js/line_items.js', array( 'jquery' ), self::SI_VERSION );
	}

	public static function admin_enqueue() {
		// doc admin templates
		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( in_array( $screen_post_type, array( SI_Estimate::POST_TYPE, SI_Invoice::POST_TYPE ) ) ) {
			wp_enqueue_script( 'si_line_items' );
		}
	}

	//////////
	// View //
	//////////

	public static function front_end_line_items( $doc_id = 0, $theme = '' ) {
		if ( ! $doc_id ) {
			$doc_id = get_the_id();
		}
		$doc = si_get_doc_object( $doc_id );
		$line_items = $doc->get_line_items();
		$context = ( is_a( $doc, 'SI_Invoice' ) ) ? 'invoice' : 'estimate' ;

		$template_path = self::locate_template( array(
			$context . '/line-items.php',
			'/line-items.php',
		), false );

		self::load_view( $template_path, array(
				'id' => $doc_id,
				'line_items' => $line_items,
				'prev_type' => '',
				'totals' => self::line_item_totals( $doc_id ),
		), false );
	}

	public static function item_type_section( $doc_id = 0 ) {
		if ( ! $doc_id ) {
			$doc_id = get_the_id();
		}
		$doc = si_get_doc_object( $doc_id );
		$line_items = $doc->get_line_items();
		self::load_view( 'admin/sections/line-items', array(
			'id' => $doc_id,
			'line_items' => $line_items,
		), false );
	}

	public static function line_item_totals_section( $doc_id = 0 ) {
		if ( ! $doc_id ) {
			$doc_id = get_the_id();
		}
		$doc = si_get_doc_object( $doc_id );
		$line_items = $doc->get_line_items();
		self::load_view( 'admin/sections/line-item-totals', array(
			'id' => $doc_id,
			'line_items' => $line_items,
			'totals' => self::line_item_totals( $doc_id ),
		), false );
	}

	public static function item_build_option( $position = 1.0, $items = array(), $children = array() ) {

		$item_data = ( ! empty( $items ) && isset( $items[ $position ] ) ) ? $items[ $position ] : array();
		$has_children = ( empty( $children ) ) ? false : true ;
		if ( ! isset( $item_data['type'] ) || '' === $item_data['type'] ) {
			$item_data['type'] = self::get_default_type();
		}
		self::load_view( 'admin/sections/line-item-options', array(
			'columns' => self::line_item_columns( $item_data['type'], $item_data, $position ),
			'item_data' => $item_data,
			'has_children' => $has_children,
			'items' => $items,
			'position' => $position,
			'children' => $children,
		), false );
	}

	public static function add_line_items() {
		$types = self::line_item_types();
		self::load_view( 'admin/sections/add-line-item.php', array(
				'types' => $types,
				'default' => self::get_default_type(),
		), false );
	}


	public static function maybe_get_item() {
		if ( ! current_user_can( 'publish_sprout_invoices' ) ) {
			self::ajax_fail( 'User cannot create an item!' );
		}
		$item_type = '';
		if ( isset( $_REQUEST['item_type'] ) ) {
			$item_type = $_REQUEST['item_type'];
		}

		$doc_id = '';
		if ( isset( $_REQUEST['doc_id'] ) ) {
			$doc_id = $_REQUEST['doc_id'];
		}

		if ( ! $item_type ) {
			wp_send_json_error( array( 'message' => __( 'No item given!', 'sprout-invoices' ) ) );
		}

		ob_start();
		$line_items = array( array( 'type' => $item_type, 'doc_id' => $doc_id ) );
		self::item_build_option( 0, $line_items );
		$option = ob_get_clean();

		$view = sprintf( '<li id="line_item_loaded_%1$s" class="item line_item_type_%1$s" data-id="0">%2$s</li>', $item_type, $option );

		$response = array(
				'option' => $view,
				'type' => $item_type,
			);
		wp_send_json_success( $response );
	}
}
