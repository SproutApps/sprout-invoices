<?php
/**
 * SI Utility Template Functions
 *
 * @package Sprout_Invoices
 * @subpackage Utility
 * @category Template Tags
 */

if ( ! function_exists( 'si_split_full_name' ) ) :
	/**
	 * Return a part of a name when a full name is provided
	 * @param  string $full_name
	 * @param  string $return    first, _first, last or return an array.
	 * @return string/array
	 */
	function si_split_full_name( $full_name = '', $return = '' ) {
		$name = array();
		preg_match( '#^(\w+\.)?\s*([\'\’\w]+)\s+([\'\’\w]+)\s*(\w+\.?)?$#', $full_name, $name );
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
endif;

if ( ! function_exists( 'si_format_address' ) ) :

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
		if ( ! empty( $address['first_name'] ) || ! empty( $address['last_name'] ) ) {
			$lines[] = $address['first_name'].' '.$address['last_name'];
		}
		if ( ! empty( $address['street'] ) ) {
			$street_lines = explode( "\n", $address['street'] );
			if ( ! empty( $street_lines ) ) {
				foreach ( $street_lines as $key => $street ) {
					$lines[] = $street;
				}
			}
		}
		$city_line = '';
		if ( ! empty( $address['city'] ) ) {
			$city_line .= $address['city'];
		}
		if ( $city_line != '' && ( ! empty( $address['zone'] ) || ! empty( $address['postal_code'] ) ) ) {
			$city_line .= ', ';
			if ( ! empty( $address['zone'] ) ) {
				$city_line .= $address['zone'];
			}
			if ( ! empty( $address['postal_code'] ) ) {
				$city_line = rtrim( $city_line ).' '.$address['postal_code'];
			}
		}
		$lines[] = rtrim( $city_line );
		if ( ! empty( $address['country'] ) ) {
			$lines[] = $address['country'];
		}
		switch ( $return ) {
			case 'array':
			return $lines;
			default:
			return apply_filters( 'si_format_address', implode( $separator, $lines ), $address, $return, $separator );
		}
	}

endif;

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
function sa_get_currency_symbol( $filter_location = true ) {
	$string = apply_filters( 'sa_get_currency_symbol_pre', SI_Payment_Processors::get_currency_symbol() );
	if ( $filter_location && strstr( $string, '%' ) ) {
		$string = str_replace( '%', '', $string );
	}
	// If no position is set add it.
	if ( ! $filter_location && ! strstr( $string, '%' ) ) {
		$locale_formatting = si_localeconv();
		if ( ! $locale_formatting['p_cs_precedes'] ) {
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
	$bool = true;
	$symbol = sa_get_currency_symbol( false );
	if ( strstr( $symbol, '%' ) ) {
		$bool = false;
	}
	return apply_filters( 'sa_currency_format_before', $bool );
}

/**
 * Prints the amount as formatted money. Place symbol based on location.
 * @param  integer  $amount      amount to convert
 * @param  string  $amount_wrap strintf
 * @param  integer $doc_id      used for filtering
 * @return string
 */
function sa_formatted_money( $amount, $doc_id = 0, $amount_wrap = '<span class="money_amount">%s</span>' ) {
	echo apply_filters( 'sa_formatted_money', sa_get_formatted_money( $amount, $doc_id, $amount_wrap ), $amount, $doc_id );
}

/**
 * Return an amount as formatted money. Place symbol based on location.
 * @param  integer  $amount      amount to convert
 * @param  string  $amount_wrap strintf
 * @param  integer $doc_id      used for filtering
 * @return integer
 */
function sa_get_formatted_money( $amount, $doc_id = 0, $amount_wrap = '%s' ) {
	if ( strpos( $doc_id, '%s' ) !== false ) {
		$amount_wrap = $doc_id; // flip parameters for backwards compatibility.
		$doc_id = 0;
	}
	$orig_amount = $amount;

	$formated_money = si_money_format( '%n', (double) $amount, $doc_id );
	$number = sprintf( $amount_wrap, $formated_money );

	return apply_filters( 'sa_get_formatted_money', $number, $orig_amount, $doc_id, $amount_wrap );
}

if ( ! function_exists( 'sa_get_unformatted_money' ) ) :
	/**
 * Unformat money
 * @param  string $money
 * @return float
 */
	function sa_get_unformatted_money( $money ) {
		$cleanString = preg_replace( '/([^0-9\.,])/i', '', $money );
		$onlyNumbersString = preg_replace( '/([^0-9])/i', '', $money );

		$separatorsCountToBeErased = strlen( $cleanString ) - strlen( $onlyNumbersString ) - 1;

		$stringWithCommaOrDot = preg_replace( '/([,\.])/', '', $cleanString, $separatorsCountToBeErased );
		$removedThousendSeparator = preg_replace( '/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot );

		$float = (float) str_replace( ',', '.', $removedThousendSeparator );
		return apply_filters( 'sa_get_unformatted_money', $float, $money );
	}
endif;


/**
 * Convert string to a number format
 * Not recommended, instead use number_format_i18n.
 * @param integer $value         number to format
 * @param string  $dec_point     Decimal
 * @param string  $thousands_sep Thousand separator
 * @return string
 */
function si_get_number_format( $value = 1, $dec_point = '.', $thousands_sep = '' ) {
	$fraction = ( is_null( $dec_point ) || ! $dec_point ) ? 0 : 2 ;
	return apply_filters( 'si_get_number_format', number_format( floatval( $value ), $fraction, $dec_point, $thousands_sep ), $value );
}
function si_number_format( $value = 1, $dec_point = '.', $thousands_sep = '', $fraction = 2 ) {
	echo apply_filters( 'si_number_format', si_get_number_format( $value, $dec_point, $thousands_sep ), $value );
}

if ( ! function_exists( 'sa_get_truncate' ) ) :
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

if ( ! function_exists( 'sa_day_ordinal_formatter' ) ) :
	/**
	 * Formats numbers with th or whatever.
	 * @param  integer $number
	 * @return string
	 */
	function sa_day_ordinal_formatter( $day = 0 ) {
		if ( class_exists( 'NumberFormatter' ) ) {
			$nf = new NumberFormatter( get_locale(), NumberFormatter::ORDINAL );
			$formatted_day = $nf->format( $day );
		} else {
			$ends = array( 'th','st','nd','rd','th','th','th','th','th','th' );
			if ( (($day % 100) >= 11) && (($day % 100) <= 13) ) {
				$formatted_day = $day. 'th';
			} else {
				$formatted_day = $day. $ends[ $day % 10 ];
			}
		}
		return apply_filters( 'sa_day_ordinal_formatter', $formatted_day, $day );
	}

endif;

if ( ! function_exists( 'si_get_days_ago' ) ) :
	/**
	 * Get the days since based on today
	 * @param  integer $number
	 * @return string
	 */
	function si_get_days_ago( $last_updated = 0 ) {
		$time_between_update = current_time( 'timestamp' ) - $last_updated;
		$days_since = round( (($time_between_update / 24) / 60) / 60 );
		return apply_filters( 'si_get_days_ago', $days_since );
	}

endif;

/////////////////////
// Developer Tools //
/////////////////////

if ( ! function_exists( 'prp' ) ) {
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

if ( ! function_exists( 'pp' ) ) {
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

		if ( ! empty( $vars ) ) {
			foreach ( $vars as $var ) {
				if ( is_bool( $var ) ) {
					$msgs[] = ( $var ? 'true' : 'false' );
				} elseif ( is_scalar( $var ) ) {
					$msgs[] = $var;
				} else {
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
		error_log( 'backtrace: ' . print_r( wp_debug_backtrace_summary( null, 0, false ), true ) );
	}
}

/**
 * Purchase links for upgrades
 */

/**
 * URL to purchase this app
 * @return string
 */
function si_purchase_link( $url = '', $campaign = 'free' ) {
	echo si_get_purchase_link( $url, $campaign );
}

/**
 * URL to purchase this app
 * @return string
 */
function si_get_purchase_link( $url = '', $campaign = 'free' ) {
	if ( $url == '' ) {
		$url = 'https://sproutapps.co/sprout-invoices/purchase/';
	}
	$url = add_query_arg( array( 'utm_medium' => 'link', 'utm_campaign' => $campaign, 'utm_source' => urlencode( home_url() ) ), $url );
	return apply_filters( 'si_get_purchase_link', esc_url_raw( $url ) );
}


function sa_link( $url = '', $campaign = 'free' ) {
	echo si_get_sa_link( $url, $campaign );
}

/**
 * URL to purchase this app
 * @return string
 */
function si_get_sa_link( $url = '', $campaign = 'free' ) {
	if ( $url == '' ) {
		$url = 'https://sproutapps.co/';
	}
	$url = add_query_arg( array( 'utm_medium' => 'link', 'utm_campaign' => $campaign, 'utm_source' => urlencode( home_url() ) ), $url );
	return apply_filters( 'si_get_sa_link', esc_url_raw( $url ) );
}


if ( ! function_exists( 'si_localeconv' ) ) :
	function si_localeconv( $doc_id = 0 ) {
		$localeconv = array();
		// Allow locale to be filtered, e.g. client
		$locale = apply_filters( 'sa_set_monetary_locale', false, $doc_id );
		if ( $locale !== false ) {
			// attempt to get localeconv based on local
			setlocale( LC_MONETARY, $locale );
			$localeconv = ( function_exists( 'localeconv' ) ) ? localeconv() : array();

			if ( isset( $localeconv['int_curr_symbol'] ) ) {
				switch ( $localeconv['int_curr_symbol'] ) {
					case 'AUS':
					case 'GBP':
						$localeconv['currency_symbol'] = '£';
						break;
					case 'EUR':
						$localeconv['currency_symbol'] = '€';
						break;

					default:
						break;
				}
			}
		}
		// if localeconv wasn't set already, from above, filter it.
		// settings sets the defaults with this filter.
		if ( empty( $localeconv ) || $localeconv['int_curr_symbol'] == '' ) {
			$localeconv = apply_filters( 'si_localeconv', $localeconv, $locale );
		}
		return apply_filters( 'si_get_localeconv', $localeconv, $doc_id, $locale );
	}
endif;

if ( ! function_exists( 'si_money_format' ) ) :
	/**
 * Replacement for php money_format function
 * @param  string $format
 * @param  float $number
 * @return
 */
	function si_money_format( $format, $number, $doc_id = 0 ) {
		$regex  = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?'.
			  '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/';
		$locale = si_localeconv( $doc_id );

		if ( empty( $locale['mon_grouping'] ) ) {
			return $number;
		}

		preg_match_all( $regex, $format, $matches, PREG_SET_ORDER );
		foreach ( $matches as $fmatch ) {
			$value = floatval( $number );
			$flags = array(
			'fillchar'  => preg_match( '/\=(.)/', $fmatch[1], $match ) ?
						   $match[1] : ' ',
			'nogroup'   => preg_match( '/\^/', $fmatch[1] ) > 0,
			'usesignal' => preg_match( '/\+|\(/', $fmatch[1], $match ) ?
						   $match[0] : '+',
			'nosimbol'  => preg_match( '/\!/', $fmatch[1] ) > 0,
			'isleft'    => preg_match( '/\-/', $fmatch[1] ) > 0,
			);
			$width      = trim( $fmatch[2] ) ? (int) $fmatch[2] : 0;
			$left       = trim( $fmatch[3] ) ? (int) $fmatch[3] : 0;
			$right      = trim( $fmatch[4] ) ? (int) $fmatch[4] : $locale['int_frac_digits'];
			$conversion = $fmatch[5];

			$positive = true;
			if ( $value < 0 ) {
				$positive = false;
				$value  *= -1;
			}
			$letter = $positive ? 'p' : 'n';

			$prefix = $suffix = $cprefix = $csuffix = $signal = '';

			$signal = $positive ? $locale['positive_sign'] : $locale['negative_sign'];
			switch ( true ) {
				case $locale[ "{$letter}_sign_posn" ] == 1 && $flags['usesignal'] == '+':
					$prefix = $signal;
					break;
				case $locale[ "{$letter}_sign_posn" ] == 2 && $flags['usesignal'] == '+':
					$suffix = $signal;
					break;
				case $locale[ "{$letter}_sign_posn" ] == 3 && $flags['usesignal'] == '+':
					$cprefix = $signal;
					break;
				case $locale[ "{$letter}_sign_posn" ] == 4 && $flags['usesignal'] == '+':
					$csuffix = $signal;
					break;
				case $flags['usesignal'] == '(':
				case $locale[ "{$letter}_sign_posn" ] == 0:
					$prefix = '(';
					$suffix = ')';
					break;
			}
			if ( ! $flags['nosimbol'] ) {
				$currency = $cprefix .
						($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) .
						$csuffix;
			} else {
				$currency = '';
			}
			$space  = $locale[ "{$letter}_sep_by_space" ] ? ' ' : '';

			$value = number_format($value, $right, $locale['mon_decimal_point'],
			$flags['nogroup'] ? '' : $locale['mon_thousands_sep']);
			$value = @explode( $locale['mon_decimal_point'], $value );

			$n = strlen( $prefix ) + strlen( $currency ) + strlen( $value[0] );
			if ( $left > 0 && $left > $n ) {
				$value[0] = str_repeat( $flags['fillchar'], $left - $n ) . $value[0];
			}
			$value = implode( $locale['mon_decimal_point'], $value );
			if ( $locale[ "{$letter}_cs_precedes" ] ) {
				$value = $prefix . $currency . $space . $value . $suffix;
			} else {
				$value = $prefix . $value . $space . $currency . $suffix;
			}
			if ( $width > 0 ) {
				$value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ?
				STR_PAD_RIGHT : STR_PAD_LEFT);
			}

			$format = str_replace( $fmatch[0], $value, $format );
		}
		return $format;
	}
endif;

function _convert_content_file_path_to_url( $file_path = '' ) {
	$file_path = str_replace( WP_CONTENT_DIR, 'replace-this-with-content-url', $file_path );
	$url = str_replace( 'replace-this-with-content-url', content_url(), $file_path );
	return $url;
}
function si_set_img_transparency( $image ) {

	$img_w = imagesx( $image );
	$img_h = imagesy( $image );

	$new_image = imagecreatetruecolor( $img_w, $img_h );
	imagesavealpha( $new_image, true );
	$rgb = imagecolorallocatealpha( $new_image, 0, 0, 0, 127 );
	imagefill( $new_image, 0, 0, $rgb );

	$color = imagecolorat( $image, $img_w -1, 1 );

	for ( $x = 0; $x < $img_w; $x++ ) {
		for ( $y = 0; $y < $img_h; $y++ ) {
			$c = imagecolorat( $image, $x, $y );
			if ( $color != $c ) {
					imagesetpixel( $new_image, $x, $y,    $c );
			}
		}
	}

	imagedestroy( $image );
	return $new_image;
}
