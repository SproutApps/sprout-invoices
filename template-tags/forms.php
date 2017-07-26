<?php

/**
 * Sprout Apps Forms Template Functions
 *
 * @package Sprout_Invoice
 * @subpackage Utility
 * @category Template Tags
 */

if ( ! function_exists( 'sa_form_fields' ) ) :
	/**
 * Loop through the fields set for the form and build the markup.
 * @param  array $fields
 * @param  string $context
 * @return string
 */
	function sa_admin_fields( $fields, $context = 'metabox' ) {
		foreach ( $fields as $key => $data ) : ?>
			<div id="si_admin_field_<?php echo esc_attr( $context ) ?>_<?php echo esc_attr( $key ) ?>" class="form-group<?php if ( $data['type'] == 'hidden' ) { echo ' hidden'; } ?>">
				<?php if ( $data['type'] == 'heading' ) : ?>
					<legend class="legend form-heading" ><?php _e( $data['label'], 'sprout-invoices' ); ?></legend>
				<?php elseif ( $data['type'] != 'checkbox' ) : ?>
					<span class="label_wrap"><?php sa_form_label( $key, $data, $context ); ?></span>
					<div class="input_wrap"><?php sa_form_field( $key, $data, $context ); ?></div>
				<?php else : ?>
					<div class="checkbox input_wrap">
						<label for="sa_<?php echo esc_attr( $context ) ?>_<?php echo esc_attr( $key ) ?>">
							<?php
								// add class by modifying the attributes.
								$data['attributes']['class'] = 'checkbox'; ?>
							<?php sa_form_field( $key, $data, $context ); ?> <?php _e( $data['label'], 'sprout-invoices' ); ?>
						</label>
						<?php if ( ! empty( $data['description'] ) ) : ?>
							<p class="description help_block"><?php _e( $data['description'], 'sprout-invoices' ) ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach;
	}
endif;

if ( ! function_exists( 'sa_form_fields' ) ) :
	/**
 * Loop through the fields set for the form and build the markup.
 * @param  array $fields
 * @param  string $context
 * @return string
 */
	function sa_form_fields( $fields, $context = 'contact', $wrap_class = '' ) {
		foreach ( $fields as $key => $data ) : ?>
				<?php if ( $data['type'] == 'heading' ) : ?>
				<legend class="legend form-heading <?php echo $context . $key . ' ' . $wrap_class ?>" ><?php echo esc_html( $data['label'] ) ?></legend>
			<?php elseif ( $data['type'] != 'checkbox' ) : ?>
				<div class="sa-control-group <?php echo $context . $key . ' ' . $wrap_class ?> <?php esc_attr_e( $data['type'] ) ?>">
					<span class="label_wrap"><?php sa_form_label( $key, $data, $context ); ?></span>
					<span class="input_wrap"><?php sa_form_field( $key, $data, $context ); ?></span>
				</div>
			<?php else : ?>
				<div class="sa-control-group <?php echo $context . $key . ' ' . $wrap_class ?> <?php esc_attr_e( $data['type'] ) ?>">
					<div class="sa-controls input_wrap">
						<label for="sa_<?php echo esc_attr( $context ) ?>_<?php echo esc_attr( $key ) ?>" class="sa-checkbox">
							<?php
								// add class by modifying the attributes.
								$data['attributes']['class'] = 'checkbox'; ?>
							<?php sa_form_field( $key, $data, $context ); ?> <?php echo esc_html( $data['label'] ) ?>
						</label>
					</div>
				</div>
			<?php endif; ?>
		<?php endforeach;
	}
endif;


if ( ! function_exists( 'sa_form_field' ) ) :
	/**
 * Print form field
 * @see sa_get_form_field()
 * @param string $key      Form field key
 * @param array $data      Array of data to build form field
 * @param string $category group the form field belongs to
 * @return string           form field input, select, radio, etc.
 */
	function sa_form_field( $key, $data, $category ) {
		echo apply_filters( 'sa_form_field', sa_get_form_field( $key, $data, $category ), $key, $data, $category );
	}
endif;

if ( ! function_exists( 'sa_get_form_field' ) ) :
	/**
 * Build and return form field
 * @param string $key      Form field key
 * @param array $data      Array of data to build form field
 * @param string $category group the form field belongs to
 * @return string           form field input, select, radio, etc.
 */
	function sa_get_form_field( $key, $data, $category ) {
		if ( ! isset( $data['default'] ) ) {
			$data['default'] = '';
		}
		if ( empty( $data['default'] ) && isset( $_REQUEST[ 'sa_'.$category.'_'.$key ] ) && $_REQUEST[ 'sa_'.$category.'_'.$key ] != '' ) {
			$data['default'] = $_REQUEST[ 'sa_'.$category.'_'.$key ];
		}
		if ( ! isset( $data['attributes'] ) || ! is_array( $data['attributes'] ) ) {
			$data['attributes'] = array();
		}
		foreach ( array_keys( $data['attributes'] ) as $attr ) {
			if ( in_array( $attr, array( 'name', 'type', 'id', 'rows', 'cols', 'value', 'placeholder', 'size', 'checked' ) ) ) {
				unset( $data['attributes'][ $attr ] ); // certain attributes are dealt with in other ways
			}
		}
		ob_start();
?>
	<span class="<?php sa_form_field_classes( $data ); ?>">
	<?php if ( $data['type'] == 'textarea' ) : ?>
		<textarea name="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" id="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" rows="<?php echo isset( $data['rows'] )?$data['rows']:4; ?>" cols="<?php echo isset( $data['cols'] )?esc_attr( $data['cols'] ):40; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) { echo 'required'; } ?> placeholder="<?php echo isset( $data['placeholder'] )?esc_attr( $data['placeholder'] ):esc_attr( $data['label'] ); ?>"><?php echo esc_html( $data['default'] ) ?></textarea>
	<?php elseif ( $data['type'] == 'select-state' ) :  // FUTURE AJAX based on country selection  ?>
		<select name="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" id="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) { echo 'required'; } ?>>
			<?php foreach ( $data['options'] as $group => $states ) : ?>
				<optgroup label="<?php echo esc_attr( $group ); ?>">
					<?php foreach ( $states as $option_key => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_key ) ?>" <?php selected( $option_key, $data['default'] ) ?>><?php echo esc_html( $option_label ) ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	<?php elseif ( $data['type'] == 'select' ) : ?>
		<select name="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" id="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) { echo 'required'; } ?>>
			<?php foreach ( $data['options'] as $option_key => $option_label ) : ?>
			<option value="<?php echo esc_attr( $option_key ) ?>" <?php selected( $option_key, $data['default'] ) ?>><?php echo esc_html( $option_label ) ?></option>
			<?php endforeach; ?>
		</select>
	<?php elseif ( $data['type'] == 'multiselect' ) : ?>
		<select name="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>[]" id="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" '; } ?> multiple="multiple" <?php if ( isset( $data['required'] ) && $data['required'] ) { echo 'required'; } ?>>
			<?php foreach ( $data['options'] as $option_key => $option_label ) : ?>
				<option value="<?php echo esc_attr( $option_key ) ?>" <?php if ( in_array( $option_key, $data['default'] ) ) { echo 'selected="selected"'; } ?>><?php echo esc_html( $option_label ) ?></option>
			<?php endforeach; ?>
		</select>
	<?php elseif ( $data['type'] == 'radios' ) : ?>
		<?php foreach ( $data['options'] as $option_key => $option_label ) : ?>
			<span class="sa-form-field-radio">
				<label for="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>_<?php esc_attr_e( $option_key ); ?>"><input type="radio" name="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" id="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>_<?php esc_attr_e( $option_key ); ?>" value="<?php esc_attr_e( $option_key ); ?>" <?php checked( $option_key, $data['default'] ) ?> />&nbsp;<?php echo esc_html( $option_label ); ?></label>
			</span>
		<?php endforeach; ?>
	<?php elseif ( $data['type'] == 'checkbox' ) : ?>
		<input type="checkbox" name="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" id="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" <?php checked( true, $data['default'] ); ?> value="<?php echo isset( $data['value'] )?$data['value']:'On'; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) { echo 'required'; } ?>/>
	<?php elseif ( $data['type'] == 'hidden' ) : ?>
		<input type="hidden" name="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" id="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" value="<?php echo esc_attr( $data['value'] ) ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" '; } ?> />
	<?php elseif ( $data['type'] == 'file' ) : ?>
		<input type="file" name="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" id="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" <?php if ( isset( $data['required'] ) && $data['required'] ) { echo 'required'; } ?>/>
	<?php elseif ( $data['type'] == 'bypass' ) : ?>
		<?php echo $data['output'] // not escaped ?>
	<?php else : ?>
		<input type="<?php echo esc_attr( $data['type'] ) ?>" name="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" id="sa_<?php echo esc_attr( $category ) ?>_<?php echo esc_attr( $key ) ?>" class="text-input" value="<?php echo esc_attr( $data['default'] ) ?>" placeholder="<?php echo isset( $data['placeholder'] )?$data['placeholder']:$data['label']; ?>" size="<?php echo isset( $data['size'] )?$data['size']:40; ?>" <?php foreach ( $data['attributes'] as $attr => $attr_value ) { echo esc_attr( $attr ).'="'.esc_attr( $attr_value ).'" '; } ?> <?php if ( isset( $data['required'] ) && $data['required'] ) { echo 'required'; } ?>/>
	<?php endif; ?>
	<?php if ( ! empty( $data['description'] ) && $data['type'] != 'checkbox' ) : ?>
		<p class="description help_block"><?php echo $data['description'] ?></p>
	<?php endif; ?>
	</span>
	<?php
	return apply_filters( 'sa_get_form_field', ob_get_clean(), $key, $data, $category );
	}
endif;

if ( ! function_exists( 'sa_form_field_classes' ) ) :
	/**
 * Utility to print form field classes
 * @see sa_get_form_field_classes()
 * @param array $data  array of data that builds the form field
 * @return string       space separated set of classes
 */
	function sa_form_field_classes( $data ) {
		$classes = implode( ' ', sa_get_form_field_classes( $data ) );
		echo apply_filters( 'sa_form_field_classes', $classes, $data );
	}
endif;

if ( ! function_exists( 'sa_get_form_field_classes' ) ) :
	/**
 * Utility to build an array of a form fields classes
 * @param array $data  array of data that builds the form field
 * @return array
 */
	function sa_get_form_field_classes( $data ) {
		$classes = array(
		'sa-form-field',
		'sa-form-field-'.$data['type'],
		);
		if ( isset( $data['required'] ) && $data['required'] ) {
			$classes[] = 'sa-form-field-required';
		}
		return apply_filters( 'sa_get_form_field_classes', $classes, $data );
	}
endif;

if ( ! function_exists( 'sa_form_label' ) ) :
	/**
 * Print form field label
 * @see sa_get_form_label()
 * @param string $key      Form field key
 * @param array $data      Array of data to build form field
 * @param string $category group the form field belongs to
 * @return string           <label>
 */
	function sa_form_label( $key, $data, $category ) {
		echo apply_filters( 'sa_form_label', sa_get_form_label( $key, $data, $category ), $key, $data, $category );
	}
endif;

if ( ! function_exists( 'sa_get_form_label' ) ) :
	/**
 * Build and return a form field label
 * @param string $key      Form field key
 * @param array $data      Array of data to build form field
 * @param string $category group the form field belongs to
 * @return string           <label>
 */
	function sa_get_form_label( $key, $data, $category ) {
		if ( $data['type'] == 'hidden' ) {
			$out = '';
		} else {
			if ( ! isset( $data['label'] ) ) {
				$data['label'] = '';
			}
			$out = '<label for="sa_'.$category.'_'.$key.'">'.__( $data['label'], 'sprout-invoices' ).'</label>';
			if ( isset( $data['required'] ) && $data['required'] ) {
				$out .= ' <span class="required">*</span>';
			}
		}
		return apply_filters( 'sa_get_form_label', $out, $key, $data, $category );
	}
endif;

if ( ! function_exists( 'sa_get_quantity_select' ) ) :
	/**
 * Return a quantity select option
 * @param integer $start    where to start
 * @param integer $end      when to end
 * @param integer $selected default option
 * @param string  $name     option name
 * @return string
 */
	function sa_get_quantity_select( $start = 1, $end = 10, $selected = 1, $name = 'quantity_select' ) {
		if ( ( $end - $start ) > apply_filters( 'sa_get_quantity_select_threshold', 25 ) ) {
			$input = '<input type="number" name="'.$name.'" value="'.$selected.'" min="'.$start.'" max="'.$end.'">';
			return $input;
		}
		$select = '<select name="'.$name.'">';
		for ( $i = $start; $i < $end + 1; $i++ ) {
			$select .= '<option value="'.$i.'" '.selected( $selected, $i, false ).'>'.$i.'</option>';
		}
		$select .= '<select>';
		return apply_filters( 'sa_get_quantity_select', $select, $start, $end, $selected, $name );
	}
endif;
