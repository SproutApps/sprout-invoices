/**
 * UI enhancements for SA Notification editor
 */

jQuery(document).ready(function($){

	$('.recent_records_select_wrap').hide();
	$('.notification_type_description').hide();
	function show_hide_notification_test_records( type ) {
		$('#test_send_selection .recent_records_select_wrap').each(function(){
			var $this = $(this),
				$option = $('#notification_type').val(),
				id = $this.attr('id');
			// Only mess with shortcode meta boxes
			// If it is the selected notification type, show it. Otherwise, hide it.
			if ( id.match( new RegExp('^recent_type_wrap_' + type + '$') ) ) {
				$this.show();
				$('.notification_type_description').hide();
				$('#test_send_selection p.description.help_block').hide();
				$('#notification_type_description_' + $option).fadeIn();
				$('#send_test_notification').fadeIn();
			} else {
				$this.hide();
			}
		});
	}
	$('#notification_type').change(function(){
		var $select = $(this),
			$option = $select.find('option:selected'),
			record_type = $option.data('record-type');
		show_hide_notification_test_records( record_type );
	});

	$("#send_test_notification").on('click', function(event) {
		event.preventDefault();
		
		var $send_button = $(this),
			$select = $('#notification_type'),
			notification_type = $('#notification_type').val(),
			$option = $select.find('option:selected'),
			record_type = $option.data('record-type')
			$record_select = $('#test_send_selection').find('#recent_' + record_type + '_type' ),
			record_selected = $record_select.val();

		$send_button.after(si_js_object.inline_spinner);

		jQuery.post( ajaxurl, { action: test_notification.action, notification: notification_type, record_selected: record_selected  },
			function( response ) {
				console.log(response);
				if ( response.error ) {
					$send_button.after( response.message );  
				}
				else {
					$('.spinner').hide();
					$send_button.after( response.data.message );
					$send_button.html( test_notification.sent_button_text );
				}
			}
		);
	});
});