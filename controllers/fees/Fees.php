<?php

/**
 * Doc Feess
 *
 * @package Sprout_Invoice
 * @subpackage SI_Fees
 */
class SI_Fees extends SI_Controller {

	public static function init() {
		// filter the line item totals
		add_filter( 'invoice_line_item_totals', array( __CLASS__, 'modify_line_item_totals' ), 10, 2 );
		add_filter( 'estimate_line_item_totals', array( __CLASS__, 'modify_line_item_totals' ), 10, 2 );

		// Shipping fee, great example of a fee
		add_filter( 'si_doc_fees', array( __CLASS__, 'add_shipping_as_fee' ), 10, 2 );
		// Shipping option
		add_action( 'doc_information_meta_box_last', array( __CLASS__, 'add_shipping_option' ) );
		add_action( 'invoice_meta_saved', array( __CLASS__, 'save_shipping_option' ) );
		add_action( 'estimate_meta_saved', array( __CLASS__, 'save_shipping_option' ) );

	}

	public static function modify_line_item_totals( $totals = array(), $doc_id = 0 ) {

		$doc = si_get_doc_object( $doc_id );
		$fees = $doc->get_fees();
		if ( empty( $fees ) ) {
			return $totals;
		}

		uasort( $fees, array( __CLASS__, 'sort_by_weight' ) );

		$count = 1;
		foreach ( $fees as $fee_key => $data ) {

			if ( isset( $data['total_callback'] ) && is_callable( $data['total_callback'] ) ) {
					$fee_total = call_user_func_array( $data['total_callback'], array( $doc, $data ) );
			} elseif ( $data['total'] ) {
				$fee_total = $data['total'];
			}

			$hide = ( isset( $data['always_show'] ) && $data['always_show'] ) ? false : ( 0.01 > (float) $fee_total );

			$weight = ( isset( $data['weight'] ) ) ? $data['weight'] : $count;

			$totals[ 'fee_' . $fee_key ] = array(
					'label' => $data['label'],
					'value' => $fee_total,
					'formatted' => sa_get_formatted_money( $fee_total, $doc_id, '<span class="money_amount">%s</span>' ),
					'hide' => $hide,
					'admin_hide' => $hide,
					'weight' => $weight,
				);

			$count++;
		}

		return $totals;
	}

	//////////////
	// Shipping //
	//////////////

	public static function add_shipping_as_fee( $fees ) {
		$fees['shipping'] = array(
				'label' => __( 'Shipping', 'sprout-invoices' ),
				'always_show' => false,
				'total_callback' => array( __CLASS__, 'calculate_shipping_fee' ),
				'weight' => 21,
			);
		return $fees;
	}

	public static function calculate_shipping_fee( $doc, $data = array() ) {
		$fee_total = $doc->get_shipping();
		return $fee_total;
	}

	public static function save_shipping_option( $doc ) {
		$shipping = ( isset( $_POST['shipping'] ) && $_POST['shipping'] != '' ) ? $_POST['shipping'] : '' ;
		$doc->set_shipping( $shipping );
	}

	public static function add_shipping_option( $doc ) {
		$shipping = $doc->get_shipping();
		?>
<!-- Shipping -->
<div class="misc-pub-section update-total" data-edit-id="shipping">
	<span id="shipping" class="wp-media-buttons-icon"><?php _e( 'Shipping', 'sprout-invoices' ) ?> <b><?php sa_formatted_money( $shipping, $doc->get_id() ) ?></b></span>

	<a href="#edit_shipping" class="edit-shipping hide-if-no-js edit_control" >
		<span aria-hidden="true"><?php _e( 'Edit', 'sprout-invoices' ) ?></span> <span class="screen-reader-text"><?php _e( 'Edit tax', 'sprout-invoices' ) ?></span>
	</a>
	<span title="<?php _e( 'Shipping is applied before the discount.', 'sprout-invoices' ) ?>" class="si_tooltip"></span>

	<div id="shipping_div" class="control_wrap hide-if-js">
		<div class="shipping-wrap">
			<input type="text" name="shipping" value="<?php echo (float) $shipping ?>" size="3">
 		</div>
 		
		<p>
			<a href="#edit_shipping" class="save_control save-shipping hide-if-no-js button"><?php _e( 'OK', 'sprout-invoices' ) ?></a>
			<a href="#edit_shipping" class="cancel_control cancel-shipping hide-if-no-js button-cancel"><?php _e( 'Cancel', 'sprout-invoices' ) ?></a>
		</p>
 	</div>
</div>
		<?php
	}
}
