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
	if ( $found = locate_template( $theme_overrides, false ) ) {
		return $found;
	}

	// check for it in the templates directory
	foreach ( $possibilities as $p ) {
		if ( file_exists( SI_PATH.'/views/templates/'.$p ) ) {
			return SI_PATH.'/views/templates/'.$p;
		}
	}

	// we don't have it
	return '';
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
	
	$has_percentage_adj = false;
	foreach ( $items as $b_position => $b_data ) {
		if ( isset( $b_data['tax'] ) && $b_data['tax'] ) {
			$has_percentage_adj = true;
		}
	}

	$desc = ( isset( $data['desc'] ) ) ? $data['desc'] : '' ;
	$rate = ( isset( $data['rate'] ) ) ? $data['rate'] : 0 ;
	$qty = ( isset( $data['qty'] ) ) ? $data['qty'] : 0 ;
	$total = ( isset( $data['total'] ) ) ? $data['total'] : 0 ;
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
		<?php do_action( 'si_line_item_build_pre_row', $data, $items, $position, $children ) ?>
		<div class="line_item<?php if ( !empty( $children ) ) echo ' has_children' ?>">
			<div class="column column_desc">
				

				<?php echo apply_filters( 'si_line_item_content', $desc ) ?>

				<?php do_action( 'si_line_item_build_desc', $data, $items, $position, $children ) ?>
			</div><!-- / item_action_column -->
			<div class="column column_rate">
				<?php if ( empty( $children ) ): ?>
					<?php esc_attr_e( sa_get_formatted_money( $rate ) ) ?>
				<?php endif ?>
				<?php do_action( 'si_line_item_build_rate', $data, $items, $position, $children ) ?>
			</div><!-- / column_rate -->
			<div class="column column_qty">
				<?php if ( empty( $children ) ): ?>
					<?php esc_attr_e( $qty ) ?>
				<?php endif ?>
				<?php do_action( 'si_line_item_build_qty', $data, $items, $position, $children ) ?>
			</div><!-- / column_qty -->
			<?php if ( $has_percentage_adj ): ?>
				<div class="column column_tax">
					<?php if ( isset( $data['tax'] ) && $data['tax'] ): ?>
						<?php esc_attr_e( $data['tax'] ) ?>%
					<?php endif ?>
				<?php do_action( 'si_line_item_build_tax', $data, $items, $position, $children ) ?>
				</div><!-- / column_tax -->
			<?php endif ?>
			<div class="column column_total">
				<?php sa_formatted_money($total) ?>
				<?php do_action( 'si_line_item_build_total', $data, $items, $position, $children ) ?>
			</div><!-- / column_total -->
		</div>
		<?php do_action( 'si_line_item_build_row', $data, $items, $position, $children ) ?>
	<?php
	$data = ob_get_contents();
	ob_end_clean();
	return apply_filters( 'si_line_item_build', $data, $position, $items, $children );
}


function si_line_item_build_plain( $position = 1.0, $items = array(), $children = array(), $doc_id = 0 ) {
	$data = $items[$position];
	
	$has_percentage_adj = false;
	foreach ( $items as $b_position => $b_data ) {
		if ( isset( $b_data['tax'] ) && $b_data['tax'] ) {
			$has_percentage_adj = true;
		}
	}

	$desc = ( isset( $data['desc'] ) ) ? $data['desc'] : '' ;
	$rate = ( isset( $data['rate'] ) ) ? $data['rate'] : 0 ;
	$qty = ( isset( $data['qty'] ) ) ? $data['qty'] : 0 ;
	$total = ( isset( $data['total'] ) ) ? $data['total'] : 0 ;
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
	ob_start(); ?><?php echo apply_filters( 'si_line_item_content', $desc ) ?>
<?php si_e('Rate:') ?> <?php esc_attr_e( $rate ) ?>  <?php si_e('Qty:') ?> <?php esc_attr_e( $qty ) ?>
<?php if ( $has_percentage_adj ): ?>
<?php if ( isset( $data['tax'] ) && $data['tax'] ): ?>
  <?php si_e('Adjustment:') ?> <?php esc_attr_e( $data['tax'] ) ?>%
<?php endif ?>
<?php endif ?>
  <?php si_e('Total:') ?> <?php sa_formatted_money( $total, $doc_id, '%s' ) ?>
	<?php
	$data = ob_get_contents();
	ob_end_clean();
	return apply_filters( 'si_line_item_build_plain', $data, $position, $items, $children );
}

function si_line_item_build_option( $position = 1.0, $items = array(), $children = array() ) {
	$data = ( !empty( $items ) && isset( $items[$position] ) ) ? $items[$position] : array();
	$desc = ( isset( $data['desc'] ) ) ? $data['desc'] : '' ;
	$rate = ( isset( $data['rate'] ) ) ? $data['rate'] : '' ;
	$qty = ( isset( $data['qty'] ) ) ? $data['qty'] : '' ;
	$tax = ( isset( $data['tax'] ) ) ? $data['tax'] : '' ;
	$total = ( isset( $data['total'] ) ) ? $data['total'] : '' ;
	
	ob_start(); ?>
		<div class="item_action_column">
			<div class="item_action dd-handle"></div>
			<!--<div class="item_action item_clone"></div>-->
			<div class="item_action item_delete"></div>
			<?php do_action( 'si_line_item_build_option_action_row', $data, $items, $position, $children ) ?>
		</div><!-- / item_action_column -->
		<div class="line_item<?php if ( !empty( $children ) ) echo ' has_children' ?>">
			<div class="column column_desc">
				<textarea name="line_item_desc[]"><?php esc_attr_e( $desc ) ?></textarea>
				<!-- desc -->
			</div><!-- / column_desc -->
			<div class="column parent_hide column_rate">
				<span></span>
				<input class="totalled_input" type="text" name="line_item_rate[]" value="<?php esc_attr_e( $rate ) ?>" placeholder="80" size="3">
				<!-- rate -->
			</div><!-- / column_rate -->
			<div class="column parent_hide column_qty">
				<span></span>
				<input class="totalled_input" type="text" name="line_item_qty[]" value="<?php esc_attr_e( $qty ) ?>" placeholder="1" size="2">
				<!-- qty -->
			</div><!-- / column_qty -->
			<div class="column parent_hide column_tax">
				<span></span>
				<input class="totalled_input" type="text" name="line_item_tax[]" value="<?php esc_attr_e( $tax ) ?>" placeholder="" size="1" max="100">
				<!-- tax -->
			</div><!-- / column_tax -->
			<div class="column column_total">
				<?php sa_formatted_money( $total ) ?>
				<input class="totalled_input" type="hidden" name="line_item_total[]" value="<?php esc_attr_e( $total ) ?>">
				<!-- total -->
			</div><!-- / column_total -->
			<input class="line_item_index" type="hidden" name="line_item_key[]" value="<?php echo esc_attr( $position ); ?>">
			<!-- hidden -->
		</div>
	<?php
	$data = ob_get_contents();
	ob_end_clean();
	return apply_filters( 'si_line_item_build_option', $data, $position, $items, $children );
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

function si_line_item_header_columns( $context = '') {
	ob_start(); ?>
		<div class="column column_desc">
			<?php si_e('Description') ?>
		</div>
		<!-- desc -->
		<div class="column column_rate">
			<?php si_e('Rate') ?>
		</div>
		<!-- rate -->
		<div class="column column_qty">
			<?php si_e('Qty') ?>
		</div>
		<!-- qty -->
		<div class="column column_tax">
			<?php si_e('%') ?><span class="helptip" title="<?php si_e('A percentage adjustment per line item, i.e. tax or discount') ?>"></span>
		</div>
		<!-- tax -->
		<div class="column column_total">
			<?php si_e('Amount') ?>
		</div>
		<!-- amount -->
	<?php
	$header = ob_get_clean();
	return apply_filters( 'si_line_item_header_columns', $header, $context );
}

function si_line_item_header_front_end( $context = '', $show_tax = true ) {
	ob_start(); ?>
		<div class="column column_desc">
			<?php si_e('Description') ?>
		</div>
		<!-- / desc -->
		<div class="column column_rate">
			<?php si_e('Rate') ?>
		</div>
		<!-- / rate -->
		<div class="column column_qty">
			<?php si_e('Qty') ?>
		</div>
		<!-- / qty -->
		<?php if ( $show_tax ): ?>
			<div class="column column_tax">
				<?php si_e('%') ?><span class="helptip" title="<?php si_e('A percentage adjustment per line item, i.e. tax or discount') ?>"></span>
			</div>
			<!-- / tax -->
		<?php endif ?>
		<!-- amount -->
		<div class="column column_total">
			<?php si_e('Amount') ?>
		</div>
		<!-- / amount -->
	<?php
	$header = ob_get_clean();
	return apply_filters( 'si_line_item_header_front_end', $header, $context, $show_tax );
}

if ( !function_exists('si_display_messages') ) :
	function si_display_messages( $type = '' ) {
		print SI_Controller::display_messages( $type );
	}
endif;

function si_get_credit_card_img( $cc_type ) {
	return SI_RESOURCES.'/front-end/img/'.$cc_type.'.png';
}

if ( !function_exists('wp_editor_styleless') ) :
/**
 * Removes those pesky theme styles from the theme.
 * @see  wp_editor()
 * @return wp_editor()
 */
function wp_editor_styleless( $content, $editor_id, $settings = array() ) {
    add_filter( 'mce_css', '__return_null' );
    $return = wp_editor( $content, $editor_id, $settings );
    remove_filter( 'mce_css', '__return_null' );
    return $return;
}
endif;