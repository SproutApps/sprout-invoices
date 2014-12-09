<?php

/**
 * Change the currency symbol
 */
function change_default_currency_symbol() {
	return '$';
}
add_filter( 'sa_get_currency_symbol_pre', 'change_default_currency_symbol' );

/**
 * Change the currency formatting
 */
function change_default_monetary_locale() {
	return 'en_US'; // or fr_CA?
}
add_filter( 'sa_set_monetary_locale', 'change_default_monetary_locale' );