<?php


function add_requirments_to_line_item( $estimate, $data ) {
	$line_items = array();
	$line_items[] = array( 
		'rate' => 100,
		'qty' => 1,
		'tax' => 0,
		'total' => 100,
		'desc' => isset( $data['requirements'] ) ? $data['requirements'] : '',
		);
	$estimate->set_line_items( $line_items );
}
// Default form submission
add_action( 'estimate_submitted', 'add_requirments_to_line_item', 10, 2 );
// Advanced forms integration
add_action( 'estimate_submitted_from_adv_form', 'add_requirments_to_line_item', 10, 2 );