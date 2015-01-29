<?php
/**
 * SI Utility Template Functions
 *
 * @package Sprout_Invoices
 * @subpackage Utility
 * @category Template Tags
 */

/**
 * A wrapper around WP's __() to add the plugin's text domain
 * @see Sprout_Invoices::__()
 * @param string  $string
 * @return string|void
 */
function si__( $string ) {
	return Sprout_Invoices::__( $string );
}

/**
 * A wrapper around WP's _e() to add the plugin's text domain
 * @see Sprout_Invoices::_e()
 * @param string  $string
 * @return void
 */
function si_e( $string ) {
	return Sprout_Invoices::_e( $string );
}

/**
 * A wrapper around WP's __() to add the plugin's text domain
 * @see Sprout_Invoices::__()
 * @param string  $string
 * @return string|void
 */
function si_esc__( $string ) {
	return Sprout_Invoices::esc__( $string );
}

/**
 * A wrapper around WP's _e() to add the plugin's text domain
 * @see Sprout_Invoices::_e()
 * @param string  $string
 * @return void
 */
function si_esc_e( $string ) {
	return Sprout_Invoices::esc_e( $string );
}

/**
 * Return a part of a name when a full name is provided
 * @param  string $full_name
 * @param  string $return    first, _first, last or return an array.
 * @return string/array           
 */
function si_split_full_name( $full_name = '', $return = '' ) {
	$name = array();
	preg_match('#^(\w+\.)?\s*([\'\’\w]+)\s+([\'\’\w]+)\s*(\w+\.?)?$#', $full_name, $name );
	switch ( $return ) {
		case 'first':
			return $name[1] .  ' ' . $name[2];
			break;

		case '_first': // without prefix
			return $name[2];
			break;

		case 'last':
			return $name[3];
			break;

		case 'suffix':
			if ( isset( $name[4] ) ) {
				return $name[4];
			}
			break;
		
		default:
			return $name;
			break;
	}
}

/**
 * Return a formatted address
 * @param  array $address   an address array
 * @param  string $return    return an array or a string with separation
 * @param  string $separator if not returning an array what should the fields be separated by
 * @return array|string            return an array by default of a string based on $return
 */
function si_format_address( $address, $return = 'array', $separator = "\n" ) {
	if ( empty( $address ) ) {
		return '';
	}
	$lines = array();
	if ( !empty($address['first_name']) || !empty($address['last_name']) ) {
		$lines[] = $address['first_name'].' '.$address['last_name'];
	}
	if ( !empty( $address['street'] ) ) {
		$lines[] = $address['street'];
	}
	$city_line = '';
	if ( !empty( $address['city'] ) ) {
		$city_line .= $address['city'];
	}
	if ( $city_line != '' && ( !empty( $address['zone'] ) || !empty( $address['postal_code'] ) ) ) {
		$city_line .= ', ';
		if ( !empty( $address['zone'] ) ) {
			$city_line .= $address['zone'];
		}
		if ( !empty( $address['postal_code'] ) ) {
			$city_line = rtrim( $city_line ).' '.$address['postal_code'];
		}
	}
	$lines[] = rtrim( $city_line );
	if ( !empty( $address['country'] ) ) {
		$lines[] = $address['country'];
	}
	switch ( $return ) {
	case 'array':
		return $lines;
	default:
		return apply_filters( 'si_format_address', implode( $separator, $lines ), $address, $return, $separator );
	}
}

//////////////
// Payments //
//////////////

/**
 * Print the currency symbol option
 * @see sa_get_currency_symbol()
 * @return string
 */
function sa_currency_symbol() {
	echo apply_filters( 'sa_currency_symbol', sa_get_currency_symbol() );
}

/**
 * Get the currency symbol, filtering out the location string(%)
 * @param boolean $filter_location filter out the location string from return
 * @return return                  
 */
function sa_get_currency_symbol( $filter_location = TRUE ) {
	$string = apply_filters( 'sa_get_currency_symbol_pre', SI_Payment_Processors::get_currency_symbol() );
	if ( $filter_location && strstr( $string, '%' ) ) {
		$string = str_replace( '%', '', $string );
	}
	// If no position is set add it.
	if ( !$filter_location && !strstr( $string, '%' )) {
		$locale_formatting = si_localeconv();
		if ( !$locale_formatting['p_cs_precedes'] ) {
			$string . '%';
		}
	}
	return apply_filters( 'sa_get_currency_symbol', $string );
}

/**
 * Is the currency symbol before or after amount.
 * @return bool
 */
function sa_currency_format_before() {
	$bool = TRUE;
	$symbol = sa_get_currency_symbol( FALSE );
	if ( strstr( $symbol, '%' ) ) {
		$bool = FALSE;
	}
	return apply_filters( 'sa_currency_format_before', $bool );
}

/**
 * Print an amount as formatted money.
 * @see sa_get_formatted_money()
 * @param integer $amount amount to convert to money format
 * @return string
 */
function sa_formatted_money( $amount, $amount_wrap = '<span class="money_amount">%s</span>' ) {
	echo apply_filters( 'sa_formatted_money', sa_get_formatted_money( $amount, $amount_wrap ), $amount );
}

/**
 * Return an amount as formatted money. Place symbol based on location.
 * @param integer $amount amount to convert to money format
 * @return string        
 */
function sa_get_formatted_money( $amount, $amount_wrap = '%s' ) {
	$orig_amount = $amount;

	$formated_money = si_money_format( '%n', (double) $amount );
	$number = sprintf( $amount_wrap, $formated_money );
	
	return apply_filters( 'sa_get_formatted_money', $number, $orig_amount, $amount_wrap );
}

if ( !function_exists( 'sa_get_unformatted_money' ) ) :
/**
 * Unformat money
 * @param  string $money
 * @return float       
 */
function sa_get_unformatted_money( $money ) {
	$cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
	$onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

	$separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

	$stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
	$removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);

	$float = (float) str_replace( ',', '.', $removedThousendSeparator );
	return apply_filters( 'sa_get_unformatted_money', $float, $money );
}
endif;


/**
 * Convert string to a number format
 * @param integer $value         number to format
 * @param string  $dec_point     Decimal
 * @param string  $thousands_sep Thousand separator
 * @return string                
 */
function si_get_number_format( $value = 1, $dec_point = '.' , $thousands_sep = '' ) {
	// TODO possibly use number_format_i18n.
	$fraction = ( is_null($dec_point) || !$dec_point ) ? 0 : 2 ;
	return apply_filters( 'si_get_number_format', number_format( floatval( $value ), $fraction, $dec_point, $thousands_sep ) );
}
function si_number_format( $value = 1, $dec_point = '.' , $thousands_sep = '', $fraction = 2 ) {
	echo apply_filters( 'si_number_format', si_get_number_format( $value, $dec_point, $thousands_sep ) );
}

if ( !function_exists('sa_get_truncate') ) :
/**
 * Truncate a string, strip tags and append a more link
 * @param string  $text           string to truncate
 * @param integer $excerpt_length output length
 * @param boolean $more_link      add a more link
 * @return string                  truncated string w or w/o more link
 */
function sa_get_truncate( $text, $excerpt_length = 44, $more_link = false ) {

	$text = strip_shortcodes( $text );

	$text = apply_filters( 'the_excerpt', $text );
	$text = str_replace( ']]>', ']]&gt;', $text );
	$text = strip_tags( $text );

	$words = explode( ' ', $text, $excerpt_length + 1 );
	if ( count( $words ) > $excerpt_length ) {
		array_pop( $words );
		$text = implode( ' ', $words );
		$text = rtrim( $text );
		$text .= '&hellip;';
	}
	if ( $more_link ) {
		$text = $text.' '.'<a href="'.$more_link.'" class="more">&nbsp;&raquo;</a>';
	}
	return apply_filters( 'sa_get_truncate', $text, $excerpt_length, $more_link );
}
endif;

/////////////////////
// Developer Tools //
/////////////////////

if ( !function_exists( 'prp' ) ) {
	/**
	 * print_r with a <pre> wrap
	 * @param array $array
	 * @return
	 */
	function prp( $array ) {
		echo '<pre style="white-space:pre-wrap;">';
		print_r( $array );
		echo '</pre>';
	}
}

if ( !function_exists( 'pp' ) ) {
	/**
	 * more elegant way to print_r an array
	 * @return string
	 */
	function pp() {
		$msg = __v_build_message( func_get_args() );
		echo '<pre style="white-space:pre-wrap; text-align: left; '.
			'font: normal normal 11px/1.4 menlo, monaco, monospaced; '.
			'background: white; color: black; padding: 5px;">'.$msg.'</pre>';
	}
	/**
	 * more elegant way to display a var dump
	 * @return string
	 */
	function dp() {
		$msg = __v_build_message( func_get_args(), 'var_dump' );
		echo '<pre style="white-space:pre-wrap;; text-align: left; '.
			'font: normal normal 11px/1.4 menlo, monaco, monospaced; '.
			'background: white; color: black; padding: 5px;">'.$msg.'</pre>';
	}

	/**
	 * simple error logging function
	 * @return [type] [description]
	 */
	function ep() {
		$msg = __v_build_message( func_get_args() );
		error_log( '**: '.$msg );
	}

	/**
	 * utility for ep, pp, dp
	 * @param array $vars
	 * @param string $func function
	 * @param string $sep  seperator
	 * @return void|string
	 */
	function __v_build_message( $vars, $func = 'print_r', $sep = ', ' ) {
		$msgs = array();

		if ( !empty( $vars ) ) {
			foreach ( $vars as $var ) {
				if ( is_bool( $var ) ) {
					$msgs[] = ( $var ? 'true' : 'false' );
				}
				elseif ( is_scalar( $var ) ) {
					$msgs[] = $var;
				}
				else {
					switch ( $func ) {
					case 'print_r':
					case 'var_export':
						$msgs[] = $func( $var, true );
						break;
					case 'var_dump':
						ob_start();
						var_dump( $var );
						$msgs[] = ob_get_clean();
						break;
					}
				}
			}
		}

		return implode( $sep, $msgs );
	}

	function wpbt() {
		error_log( 'backtrace: ' . print_r( wp_debug_backtrace_summary( null, 0, FALSE ), TRUE ) );
	}
}

/**
 * Purchase links for upgrades
 */

/**
 * URL to purchase this app
 * @return string
 */
function si_purchase_link( $url = '' ) {
	echo si_get_purchase_link( $url );
}

/**
 * URL to purchase this app
 * @return string
 */
function si_get_purchase_link( $url = '' ) {
	if ( $url == '' ) {
		$url = 'https://sproutapps.co/sprout-invoices/purchase/';
	}
	$url = add_query_arg( array( 'utm_medium' => 'link', 'utm_campaign' => 'free', 'utm_source' => urlencode( home_url() ) ), $url );
	return apply_filters( 'si_get_purchase_link', $url );
}

/**
 * URL to purchase this app
 * @return string
 */
function si_get_sa_link( $url = '' ) {
	if ( $url == '' ) {
		$url = 'https://sproutapps.co/';
	}
	$url = add_query_arg( array( 'utm_medium' => 'link', 'utm_campaign' => 'free', 'utm_source' => urlencode( home_url() ) ), $url );
	return apply_filters( 'si_get_sa_link', $url );
}



if ( !function_exists('si_localeconv') ) :
function si_localeconv( ) {
	$locale = apply_filters( 'sa_set_monetary_locale', get_locale() );
	setlocale( LC_MONETARY, $locale );

	// Set some symbols automatically.
	if ( isset( $locale['int_curr_symbol'] ) ) {
		switch ( $locale['int_curr_symbol'] ) {
			case 'AUS':
			case 'GBP':
				$locale['currency_symbol'] = '£';
				break;
			case 'EUR':
				$locale['currency_symbol'] = '€';
				break;
			
			default:
				break;
		}
	}
	$locale = (function_exists( 'localeconv' )) ? localeconv() : array() ;
	return apply_filters( 'si_localeconv', $locale );
}
endif;

if ( !function_exists('si_money_format') ) :
/**
 * Replacement for php money_format function
 * @param  string $format
 * @param  float $number
 * @return         
 */
function si_money_format( $format, $number )  {
	$regex  = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?'.
			  '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/';
	$locale = si_localeconv();

	if ( empty( $locale['mon_grouping'] ) ) {
		return $number;
	}
	
	preg_match_all($regex, $format, $matches, PREG_SET_ORDER);
	foreach ($matches as $fmatch) {
		$value = floatval($number);
		$flags = array(
			'fillchar'  => preg_match('/\=(.)/', $fmatch[1], $match) ?
						   $match[1] : ' ',
			'nogroup'   => preg_match('/\^/', $fmatch[1]) > 0,
			'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ?
						   $match[0] : '+',
			'nosimbol'  => preg_match('/\!/', $fmatch[1]) > 0,
			'isleft'    => preg_match('/\-/', $fmatch[1]) > 0
		);
		$width      = trim($fmatch[2]) ? (int)$fmatch[2] : 0;
		$left       = trim($fmatch[3]) ? (int)$fmatch[3] : 0;
		$right      = trim($fmatch[4]) ? (int)$fmatch[4] : $locale['int_frac_digits'];
		$conversion = $fmatch[5];

		$positive = true;
		if ($value < 0) {
			$positive = false;
			$value  *= -1;
		}
		$letter = $positive ? 'p' : 'n';

		$prefix = $suffix = $cprefix = $csuffix = $signal = '';

		$signal = $positive ? $locale['positive_sign'] : $locale['negative_sign'];
		switch (true) {
			case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+':
				$prefix = $signal;
				break;
			case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+':
				$suffix = $signal;
				break;
			case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+':
				$cprefix = $signal;
				break;
			case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+':
				$csuffix = $signal;
				break;
			case $flags['usesignal'] == '(':
			case $locale["{$letter}_sign_posn"] == 0:
				$prefix = '(';
				$suffix = ')';
				break;
		}
		if (!$flags['nosimbol']) {
			$currency = $cprefix .
						($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) .
						$csuffix;
		} else {
			$currency = '';
		}
		$space  = $locale["{$letter}_sep_by_space"] ? ' ' : '';

		$value = number_format($value, $right, $locale['mon_decimal_point'],
				 $flags['nogroup'] ? '' : $locale['mon_thousands_sep']);
		$value = @explode($locale['mon_decimal_point'], $value);
		
		$n = strlen($prefix) + strlen($currency) + strlen($value[0]);
		if ($left > 0 && $left > $n) {
			$value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0];
		}
		$value = implode($locale['mon_decimal_point'], $value);
		if ($locale["{$letter}_cs_precedes"]) {
			$value = $prefix . $currency . $space . $value . $suffix;
		} else {
			$value = $prefix . $value . $space . $currency . $suffix;
		}
		if ($width > 0) {
			$value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ?
					 STR_PAD_RIGHT : STR_PAD_LEFT);
		}

		$format = str_replace($fmatch[0], $value, $format);
	}
	return $format;
}
endif;