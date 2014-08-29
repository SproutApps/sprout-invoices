jQuery.noConflict();

jQuery(function($) {

	/**
	 * Tooltip with qtip
	 * @type {}
	 */
	$('.tooltip[title!=""], .helptip[title!=""]').qtip({
		style: {
        	classes: 'qtip-tipsy'
    	}
    });

    jQuery("#the-list .doc_status_change").on('click', function(e) {
		e.preventDefault();
		var $status_change_link = $( this ),
			$row_actions = $status_change_link.closest( '.row-actions' ),
			$new_status = $status_change_link.data( 'status-change' ),
			$id = $status_change_link.data( 'id' ),
			$nonce = $status_change_link.data( 'nonce' ),
			$status_span = $( '#status_' + $id );

		$status_span.append('<span class="spinner si_inline_spinner" style="display:inline-block;"></span>');
		
		$.post( ajaxurl, { action: 'si_change_doc_status', id: $id, status: $new_status, change_status_nonce: $nonce },
			function( data ) {
				if ( data.error ) {
					$status_span.html( data.response );	
				}
				else {
					$status_span.html( data.response + data.status );
					$row_actions.hide();
				};
				return data;
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
		$button.after('<span class="spinner si_inline_spinner" style="display:inline-block;"></span>');
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
		$button.after('<span class="spinner si_inline_spinner" style="display:inline-block;"></span>');
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