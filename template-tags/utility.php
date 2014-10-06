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
	$string = SI_Payment_Processors::get_currency_symbol();
	if ( $filter_location && strstr( $string, '%' ) ) {
		$string = str_replace( '%', '', $string );
	}
	return apply_filters( 'sa_get_currency_symbol', $string );
}

/**
 * Print an amount as formatted money. 
 * @see sa_get_formatted_money()
 * @param integer $amount amount to convert to money format 
 * @return string
 */
function sa_formatted_money( $amount, $decimals = TRUE ) {
	echo apply_filters( 'sa_formatted_money', sa_get_formatted_money( $amount, $decimals ), $amount );
}

/**
 * Return an amount as formatted money. Place symbol based on location.
 * @param integer $amount amount to convert to money format
 * @return string         
 */
function sa_get_formatted_money( $amount, $decimals = TRUE ) {
	$orig_amount = $amount;
	$symbol = sa_get_currency_symbol( FALSE );
	$number = number_format( floatval( $amount ), 2 );
	if ( strstr( $symbol, '%' ) ) {
		$string = str_replace( '%', $number, $symbol );
	} else {
		$string = $symbol . $number;
	}
	if ( $number < 0 ) {
		$string = '-'.str_replace( '-', '', $string );
	}
	if ( !$decimals ) {
		$string = str_replace('.00','', $string);
	}
	return apply_filters( 'sa_get_formatted_money', $string, $orig_amount );
}

function sa_currency_format_before() {
	$symbol = sa_get_currency_symbol( FALSE );
	if ( strstr( $symbol, '%' ) ) {
		$bool = FALSE;
	}
	$bool = TRUE;
	return apply_filters( 'sa_currency_format_before', $bool );
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
	return add_query_arg( array( 'ref' => 'free', 'url' => urlencode( home_url() ) ), $url );	;
}