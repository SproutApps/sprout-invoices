;(function( $, si, undefined ) {
	
	si.template = {
		config: {

		},
	};
	
	si.template.init = function() {

		$('.open').on('click', function(e) {
			var myelement = $(this).attr('href');

			$('html').css('overflow', 'hidden');
			$('body').bind('touchmove', function(e) {
				e.preventDefault()
			});

			$(myelement).removeClass('closed closing');
			$(myelement).addClass('opening');

			return false;
		});

		// Panel Close

		$('.close').on('click', function(e) {
			var myelement = $(this).attr('href');

			$('html').css('overflow', 'scroll');
			$('body').unbind('touchmove');

			$(myelement).removeClass('opening');
			$(myelement).addClass('closing');

			setTimeout(function() {
			  $(myelement).addClass('closed');
			}, 500);

			return false;
		});
		
		/**
		 * Status Updates
		 */
		$("#paybar .inner a.status_change").on('click', function(e) {
			e.preventDefault();
			var $status_change_link = $( this ),
				$action_links = $( this ).parent(),
				$new_status = $status_change_link.data( 'status-change' ),
				$id = $status_change_link.data( 'id' ),
				$nonce = $status_change_link.data( 'nonce' );

			$action_links.html(si_js_object.inline_spinner);

			$.post( si_js_object.ajax_url, { action: 'si_change_doc_status', id: $id, status: $new_status, change_status_nonce: $nonce },
				function( data ) {
					if ( data.error ) {
						$action_links.html( data.response );  
					}
					else {          
						if ( $new_status === 'decline' ) {
							$action_links.html( si_js_object.sorry_string );
						}
						else {
							$action_links.html( si_js_object.thank_you_string );
						};
						$(document).trigger( 'status_updated' );
					};
					return data;
				}
			);
		});

	}
	

})( jQuery, window.si = window.si || {} );

// Init
jQuery(function() {
	si.template.init();
});