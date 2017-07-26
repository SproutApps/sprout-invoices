;(function( $, si, undefined ) {
	
	si.template = {
		config: {

		},
	};
	
	si.template.init = function() {

		$('.open').on('click', function(e) {
			var myelement = $(this).attr('href');

			$(myelement).removeClass('closed closing');
			$(myelement).addClass('opening');

			return false;
		});

		// Panel Close

		$('.close').on('click', function(e) {
			var myelement = $(this).attr('href');

			$(myelement).removeClass('opening');
			$(myelement).addClass('closing');

			setTimeout(function() {
			  $(myelement).addClass('closed');
			}, 500);

			return false;
		});

		// Toggle Toggle

		$('.payment_option').click(function(e){
			$("a.payment_option").removeClass('active');
			$(this).addClass('active');

			$('#credit_card_checkout_wrap').removeClass('active').addClass('inactive');
			$('#check_info_checkout_wrap').removeClass('active').addClass('inactive');
			$('#popayment_info_checkout_wrap').removeClass('active').addClass('inactive');
			$('#bacs_info_checkout_wrap').removeClass('active').addClass('inactive');

			if ( $(this).hasClass('cc_processor') ) {
				e.preventDefault();
				$('#credit_card_checkout_wrap').addClass('active').removeClass('inactive');
			}
			if ( $(this).hasClass('popayment') ) {
				e.preventDefault();
				$('#popayment_info_checkout_wrap').addClass('active').removeClass('inactive');
			}
			if ( $(this).hasClass('checks') ) {
				e.preventDefault();
				$('#check_info_checkout_wrap').addClass('active').removeClass('inactive');
			}
			if ( $(this).hasClass('bacs') ) {
				e.preventDefault();
				$('#bacs_info_checkout_wrap').addClass('active').removeClass('inactive');
			}
		});

	}
	

})( jQuery, window.si = window.si || {} );

// Init
jQuery(function() {
	si.template.init();
});