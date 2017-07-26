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

			$fee_total = 0.00;
			if ( isset( $data['total_callback'] ) && is_callable( $data['total_callback'] ) ) {
					$fee_total = call_user_func_array( $data['total_callback'], array( $doc, $data ) );
			} elseif ( isset( $data['total'] ) ) {
				$fee_total = $data['total'];
			}

			if ( isset( $data['label_callback'] ) && is_callable( $data['label_callback'] ) ) {
					$label = call_user_func_array( $data['label_callback'], array( $doc, $data ) );
			} elseif ( $data['label'] ) {
				$label = $data['label'];
			}

			$hide = ( isset( $data['always_show'] ) && $data['always_show'] ) ? false : ( 0.01 > (float) $fee_total );

			$weight = ( isset( $data['weight'] ) ) ? $data['weight'] : $count;

			$totals[ 'fee_' . $fee_key ] = array(
					'label' => $label,
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
}
