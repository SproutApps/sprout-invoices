<?php

if ( !function_exists('si_doc_header_logo') ) :
/**
 * Get the document logo from the theme or the default logo from the plugin.
 * @return  string
 */
function si_doc_header_logo_url() {
	$fullpath = si_locate_file( array(
					'logo.png',
					'logo.jpg',
					'logo.gif',
				) );
	$path = str_replace( WP_CONTENT_DIR, '', $fullpath );
	return content_url( $path );
}
endif;

if ( !function_exists('si_locate_file') ) :
/**
 * Locate the template file, either in the current theme or the public views directory
 *
 * @static
 * @param array   $possibilities
 * @return string
 */
function si_locate_file( $possibilities = array() ) {
	$possibilities = apply_filters( 'si_locate_file_possibilites', $possibilities );

	// check if the theme has an override for the template
	$theme_overrides = array();
	foreach ( $possibilities as $p ) {
		$theme_overrides[] = SI_Controller::get_template_path().'/'.$p;
	}
	if ( $found = locate_template( $theme_overrides, FALSE ) ) {
		return $found;
	}

	// check for it in the templates directory
	foreach ( $possibilities as $p ) {
		if ( file_exists( SI_PATH.'/views/templates/'.$p ) ) {
			return SI_PATH.'/views/templates/'.$p;
		}
	}

	// we don't have it
	return $default;
}
endif;

if ( !function_exists('si_address') ) :
/**
 * Echo a formatted address
 * @param  array  $address 
 * @return           
 */
function si_address( $address = array() ) {
	$address = si_format_address( $address, 'string', '<br/>' );
	return apply_filters( 'si_address', sprintf( '<address class="vcard"><span>%s</span></address>', $address ), $address );
}
endif;

if ( !function_exists('si_get_company_email') ) :
/**
 * Get the site company email
 * @param  integer $id 
 * @return string      
 */
function si_get_company_email() {
	$address = si_get_doc_address();
	$email = ( isset( $address['email'] ) ) ? $address['email'] : get_bloginfo( 'email' ) ;
	return apply_filters( 'si_get_company_email', $email );
}
endif;

if ( !function_exists('si_company_email') ) :
/**
 * Echo the site company email
 * @param  integer $id 
 * @return string      
 */
function si_company_email() {
	echo apply_filters( 'si_company_email', si_get_company_email() );
}
endif;

if ( !function_exists('si_get_company_name') ) :
/**
 * Get the site company name
 * @param  integer $id 
 * @return string      
 */
function si_get_company_name() {
	$address = si_get_doc_address();
	$name = ( isset( $address['name'] ) ) ? $address['name'] : get_bloginfo( 'name' ) ;
	return apply_filters( 'si_get_company_name', $name );
}
endif;

if ( !function_exists('si_company_name') ) :
/**
 * Echo the site company name
 * @param  integer $id 
 * @return string      
 */
function si_company_name() {
	echo apply_filters( 'si_company_name', si_get_company_name() );
}
endif;

if ( !function_exists('si_get_doc_address') ) :
/**
 * Get the formatted site address
 * @param  integer $id 
 * @return string      
 */
function si_get_doc_address() {
	return SI_Admin_Settings::get_site_address();
}
endif;

if ( !function_exists('si_doc_address') ) :
/**
 * Echo a formatted site address
 * @param  integer $id 
 * @return string      
 */
function si_doc_address() {
	echo apply_filters( 'si_doc_address', si_address( si_get_doc_address() ) );
}
endif;

function si_line_item_build( $position = 1.0, $items = array(), $children = array() ) {
	$data = $items[$position];
	
	$has_percentage_adj = FALSE;
	foreach ( $items as $b_position => $b_data ) {
		if ( isset( $b_data['tax'] ) && $b_data['tax'] ) {
			$has_percentage_adj = TRUE;
		}
	}

	$total = $data['total'];
	if ( !empty( $children ) ) {
		$total = 0;
		foreach ( $children as $child_position ) {
			$child_data = $items[$child_position];
			$total += $child_data['total'];
		}
		$data['rate'] = '';
		$data['qty'] = '';
		$data['tax'] = '';
	}
	ob_start(); ?>
		<div class="line_item<?php if ( !empty( $children ) ) echo ' has_children' ?>">
			<div class="column column_type">
				<?php 
					if ( isset( $data['type'] ) && $data['type'] ) {
						$term = get_term( $data['type'], SI_Estimate::LINE_ITEM_TAXONOMY );
						if ( !is_wp_error( $term ) ) {
							printf( '<span class="line_item_type tooltip" title="%s"></span>', esc_attr__( $term->name ) );
						}
					} ?>
			</div>
			<div class="column column_desc">
				<?php echo apply_filters( 'the_content', $data['desc'] ) ?>
			</div>
			<div class="column column_rate">
				<?php esc_attr_e( $data['rate'] ) ?>
			</div>
			<div class="column column_qty">
				<?php esc_attr_e( $data['qty'] ) ?>
			</div>
			<?php if ( $has_percentage_adj ): ?>
				<div class="column column_tax">
					<?php if ( isset( $data['tax'] ) && $data['tax'] ): ?>
						<?php esc_attr_e( $data['tax'] ) ?>%
					<?php endif ?>
				</div>
			<?php endif ?>
			<div class="column column_total">
				<?php sa_formatted_money($total) ?>
			</div>
		</div>
	<?php
	$data = ob_get_contents();
	ob_end_clean();
	return apply_filters( 'si_line_item_build', $data, $position, $items );
}

function si_line_item_build_option( $position = 1.0, $items = array(), $children = array() ) {
	$item_types = get_terms( array( SI_Estimate::LINE_ITEM_TAXONOMY ), array( 'hide_empty' => FALSE, 'fields' => 'all' ) );
	$type_options = array();
	foreach ( $item_types as $item_type ) {
		$type_options[$item_type->term_id] = $item_type->name;
	}
	$data = $items[$position];
	ob_start(); ?>
		<div class="item_action_column">
			<div class="item_action dd-handle"></div>
			<!--<div class="item_action item_clone"></div>-->
			<div class="item_action item_delete"></div>
		</div>
		<div class="line_item<?php if ( !empty( $children ) ) echo ' has_children' ?>">
			<div class="column column_desc">
				<textarea name="line_item_desc[]"><?php esc_attr_e( $data['desc'] ) ?></textarea>
			</div>
			<div class="column column_rate">
				<span></span>
				<input class="totalled_input" type="text" name="line_item_rate[]" value="<?php esc_attr_e( $data['rate'] ) ?>" placeholder="1" size="3">
			</div>
			<div class="column column_qty">
				<span></span>
				<input class="totalled_input" type="text" name="line_item_qty[]" value="<?php esc_attr_e( $data['qty'] ) ?>" size="2">
			</div>
			<div class="column column_tax">
				<span></span>
				<input class="totalled_input" type="text" name="line_item_tax[]" value="<?php esc_attr_e( $data['tax'] ) ?>" placeholder="" size="1" max="100">
			</div>
			<div class="column column_total">
				<?php if ( sa_currency_format_before() ): ?>
					<?php echo sa_get_currency_symbol() ?><span><?php esc_attr_e( $data['total'] ) ?></span>
				<?php else: ?>
					<span><?php esc_attr_e( $data['total'] ) ?></span><?php echo sa_get_currency_symbol() ?>
				<?php endif ?>
				<input class="totalled_input" type="hidden" name="line_item_total[]" value="<?php esc_attr_e( $data['total'] ) ?>">
			</div>
			<input class="line_item_index" type="hidden" name="line_item_key[]" value="<?php echo $position ?>">
		</div>
	<?php
	$data = ob_get_contents();
	ob_end_clean();
	return apply_filters( 'si_line_item_build_option', $data, $position, $items );
}

function si_line_item_get_children( $position = 1, $items = array() ) {
	$children = array();
	foreach ( $items as $key => $value ) {
		if ( $key != $position ) {
			if ( floor($key) == $position ) {
				$children[] = $key;
			}
		}
	}
	return apply_filters( 'si_line_item_get_children', $children, $position, $items );
}

if ( !function_exists('si_display_messages') ) :
	function si_display_messages( $type = '' ) {
		print SI_Controller::display_messages( $type );
	}
endif;

function si_get_credit_card_img( $cc_type ) {
	return SI_RESOURCES.'/front-end/img/'.$cc_type.'.png';
}

