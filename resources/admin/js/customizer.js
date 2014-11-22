( function( $ ) {	
	wp.customize('si_estimates_color',function( value ) {
		value.bind(function(to) {
			$('#doc .doc_total').css('background-color', to );
			$('.button.primary_button').css('background-color', to );
			$('#invoice.paid #doc .doc_total').css('background-color', to );
			$('#invoice .button.deposit_paid').css('background-color', to );
			$('#line_total').css('color', to );
		});
	});
	wp.customize('si_invoices_color',function( value ) {
		value.bind(function(to) {
			$('#invoice #doc .doc_total').css('background-color', to );
			$('#invoice .button.primary_button').css('background-color', to );
			$('#invoice #line_total').css('color', to );
		});
	});
	wp.customize('si_logo',function( value ) {
		value.bind(function(to) {
			$('#logo img').attr('src', to );
		});
	});
} )( jQuery );