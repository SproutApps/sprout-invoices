var $ = jQuery.noConflict();

function si_format_money ( value ) {

	if ( typeof Intl !== 'object' ) {
		var parts = value.toString().split(si_js_object.localeconv.mon_decimal_point);
		parts[0] = parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, si_js_object.localeconv.mon_thousands_sep );
		return si_js_object.localeconv.currency_symbol + parts.join(si_js_object.localeconv.mon_decimal_point);
	}
	var cformatter = new Intl.NumberFormat( si_js_object.locale_standard, {
		style: 'currency',
		currency: si_js_object.localeconv.int_curr_symbol.trim(),
		maximumFractionDigits: si_js_object.localeconv.int_frac_digits,
		minimumFractionDigits: si_js_object.localeconv.int_frac_digits,
	});
	return cformatter.format( value );
}

jQuery(function($) {

	if (typeof define === 'function' && define.select2) {
		/**
	 * select2 init
	 */
	$('.select2').select2({
		// Support for optgroup searching
		matcher: function modelMatcher (params, data) {
				data.parentText = data.parentText || "";

				// Always return the object if there is nothing to compare
				if ($.trim(params.term) === '') {
					return data;
				}

				// Do a recursive check for options with children
				if (data.children && data.children.length > 0) {
					// Clone the data object if there are children
					// This is required as we modify the object to remove any non-matches
					var match = $.extend(true, {}, data);

					// Check each child of the option
					for (var c = data.children.length - 1; c >= 0; c--) {
						var child = data.children[c];
						child.parentText += data.parentText + " " + data.text;

						var matches = modelMatcher(params, child);

						// If there wasn't a match, remove the object in the array
						if (matches == null) {
							match.children.splice(c, 1);
						}
					}

					// If any children matched, return the new object
					if (match.children.length > 0) {
						return match;
					}

					// If there were no matching children, check just the plain object
					return modelMatcher(params, match);
				}

				// If the typed-in term matches the text of this term, or the text from any
				// parent term, then it's a match.
				var original = (data.parentText + ' ' + data.text).toUpperCase();
				var term = params.term.toUpperCase();


				// Check if the text contains the term
				if (original.indexOf(term) > -1) {
					return data;
				}

				// If it doesn't contain the term, don't return anything
				return null;
			}
		});
	}

	/**
	 * Tooltip with qtip
	 * @type {}
	 */
	$('.si_tooltip[title!=""], .helptip[title!=""]').qtip({
		style: {
			classes: 'qtip-bootstrap'
		}
	});

    jQuery("#the-list .doc_status_change").live('click', function(e) {
		e.preventDefault();
		var $status_change_link = $( this ),
			$status_button = $( this ).closest('.quick_status_update'),
			$row_actions = $status_change_link.closest( '.row-actions' ),
			$new_status = $status_change_link.data( 'status-change' ),
			$id = $status_change_link.data( 'id' ),
			$nonce = $status_change_link.data( 'nonce' ),
			$status_span = $( '#status_' + $id );

		$status_button.html(si_js_object.inline_spinner);
		
		$.post( ajaxurl, { action: 'si_change_doc_status', id: $id, status: $new_status, change_status_nonce: $nonce },
			function( data ) {
				if ( data.error ) {
					$status_span.html( data.response );	
				}
				else {
					$button_html = $( data.new_button ).html();
					// swap out the button with the new one
					$status_button.html( $button_html );
				};
				return data;
			}
		);
	});


	/**
	 * License Activation
	 */
	$('#free_license').on('click', function(e) {
		e.stopPropagation();
		e.preventDefault();
		var $button = $( this ),
			$license_key = $('#si_license_key').val(),
			$license_message = $('#license_message');

		$button.hide();
		$button.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'si_get_license', license: $license_key, security: si_js_object.security },
			function( data ) {
				$('.spinner').hide();
				if ( data.error ) {
					$button.show();
					$license_message.html('<span class="inline_error_message">' + data.response + '</span>');
				}
				else {
					$('#si_license_key').val(data.license);
					$license_message.html('<span class="inline_success_message">' + data.response + '</span>');
				}
			}
		);
	});

	/**
	 * License Activation
	 */
	$('#activate_license').on('click', function(e) {
		e.stopPropagation();
		e.preventDefault();
		var $button = $( this ),
			$license_key = $('#si_license_key').val(),
			$license_message = $('#license_message');

		$button.hide();
		$button.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'si_activate_license', license: $license_key, security: si_js_object.security },
			function( data ) {
				$('.spinner').hide();
				if ( data.error ) {
					$button.show();
					$license_message.html('<span class="inline_error_message">' + data.response + '</span>');	
				}
				else {
					$license_message.html('<span class="inline_success_message">' + data.response + '</span>');
				}
			}
		);
	});

	/**
	 * License Deactivation
	 */
	$('#deactivate_license').on('click', function(e) {
		e.stopPropagation();
		e.preventDefault();
		var $button = $( this ),
			$activate_button = $('#activate_license');
			$license_key = $('#si_license_key').val(),
			$license_message = $('#license_message');

		$button.hide();
		$button.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'si_deactivate_license', license: $license_key, security: si_js_object.security },
			function( data ) {
				$('.spinner').hide();
				if ( data.error ) {
					$button.show();
					$license_message.html('<span class="inline_error_message">' + data.response + '</span>');	
				}
				else {
					$activate_button.hide();
					$activate_button.removeAttr('disabled').addClass('button-primary').fadeIn();
					$license_message.html('<span class="inline_success_message">' + data.response + '</span>');
				}
			}
		);
	});


});

;(function( $, si, undefined ) {

	si.siAdmin = {
		config: {
			failed_save: false
		},
	};

	si.siAdmin.InitAjaxSettings = function() {

		$(".ajax_save").change( function( e ) {

			// If the form is failing don't attempt again.
			if ( si.siAdmin.config.failed_save ) { return };

			// handle the payments form differently, only post if the payment selector is chosen.
			if ( $(this).hasClass('group-buying/payment') ) {
				if ( e.target.id == 'si_payment_processor' ) {
					si.siAdmin.ajax_post_form( $(this) );
					return;
				};
			}
			// handle the full page ajax pages differently 
			else if ( $(this).hasClass( 'full_page_ajax' ) ) {
				si.siAdmin.ajax_post_form( $(this) );
				return;
			}
			else {
				si.siAdmin.ajax_post_options( $(this) );
				return;
			};
		});
	};



	// Use wp_ajax to update each option without a full page return.
	si.siAdmin.ajax_post_options = function( form ) {
		var $form_dialog = $("#ajax_saving");
		si.siAdmin.show_dialog();
		$.post( ajaxurl, { action: 'si_save_options', options: form.serialize() },
			function( data ) {
				$form_dialog.html(data).fadeOut();
			}
		);
	};

	// submit the form in the background and replace the DOM
	si.siAdmin.ajax_post_form = function( form ) {
		var $form_dialog = $("#ajax_saving");
		si.siAdmin.show_dialog();
		$.ajax( {
			type: "POST",
			url: form.attr( 'action' ),
			data: form.serialize(),
			success: function( response ) {
				var new_form = $('<div />').html(response).find('form.ajax_save').html();
				if ( new_form.length > 0 ) {
					$form_dialog.html('Saved').fadeOut();
					form.html(new_form);
				}
				else {
					$form_dialog.html('Auto save failed, use "Save Changes" button.').fadeOut();
					si.siAdmin.config.failed_save = true;
				};
			}
		});
	};

	si.siAdmin.show_dialog = function( $html ) {
		var $form_dialog = $("#ajax_saving");
		$form_dialog.html( $('#ajax_saving').data( 'message' ) );
		$form_dialog
			.css('position', 'fixed')
			.css('left', '45%')
			.css('top', '45%')
			.show();
	};

})( jQuery, window.si = window.si || {} );

// Init
jQuery(function() {
	si.siAdmin.InitAjaxSettings();
});