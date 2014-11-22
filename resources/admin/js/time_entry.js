;(function( $, si, undefined ) {
	
	si.timeEntries = {
		config: {
			entries_table: '#time_entries',
			inline_spinner: '<span class="spinner si_inline_spinner" style="display:inline-block;"></span>'	
		},
	};

	/**
	 * Save time entry
	 * @param  {array} data
	 */
	si.timeEntries.save = function( data, $save_button ) {
		data.action = 'sa_save_time';
		$save_button.after(si.timeEntries.config.inline_spinner);
		$.post( ajaxurl, data,
			function( response ) {
				if ( response.error ) {
					$('.spinner').hide();
					$save_button.after('<span class="inline_error_message">' + response.response + '</span>');	
				}
				else {
					$('.spinner').hide();
					$save_button.after('<span class="inline_success_message">' + si_js_object.time_tracker_success_message + '</span>');
					si.timeEntries.RefreshTable();
				}
			}
		);
	};

	/**
	 * Delete the time entry within the admin view of time entries
	 */
	si.timeEntries.deleteTimeEntry = function( $button, data, to_remove ) {
		data.action = 'sa_remove_time_entry';
		si.timeEntries.deleteActionTableRemoval( $button, data, to_remove );
	};

	/**
	 * Delete the time activity within the admin view of times
	 */
	si.timeEntries.deleteTimeActivity = function( $button, data, to_remove ) {
		data.action = 'sa_remove_time';
		si.timeEntries.deleteActionTableRemoval( $button, data, to_remove );
	};

	si.timeEntries.deleteActionTableRemoval = function( $button, data, to_remove ) {
		$button.hide();
		$button.after(si.timeEntries.config.inline_spinner);
		$.post( ajaxurl, data,
			function( response ) {
				console.log(response);
				if ( response.error ) {
					$('.spinner').hide();
					$button.after('<span class="inline_error_message">' + response.response + '</span>');	
				}
				else {
					$('.spinner').hide();
					$('#'+to_remove).fadeOut();
				}
			}
		);
	};

	/**
	 * use AJAX function to get table view
	 */
	si.timeEntries.RefreshTable = function() {
		var project_id = $('#time_entries').data('project-id');
		$.post( ajaxurl, { action: 'sa_time_entries_table', project_id: project_id },
			function( response ) {
				$('#time_entries').html( response );
				$('#sa_time_time_inc').val('');
			}
		);
	};

	/**
	 * Create time
	 * @param  {array} data
	 */
	si.timeEntries.saveActivity = function( data, $save_button ) {
		data.action = 'sa_create_activity';
		$save_button.after(si.timeEntries.config.inline_spinner);
		$.post( ajaxurl, data,
			function( response ) {
				si.timeEntries.closeModalAfterTimeSave( response, $save_button );
			}
		);
	};

	si.timeEntries.closeModalAfterTimeSave = function( response, $save_button ) {
		if ( response.error ) {
			$('.spinner').hide();
			$save_button.after('<span class="inline_error_message">' + response.response + '</span>');	
		}
		else {
			// close modal
			self.parent.tb_remove();

			// Clear inputs
			$('#sa_time_name').val('');
			$('#sa_time_rate').val('');
			$('#sa_time_percentage').val('');

			// change option text
			$('[name="sa_time_activity_id"]').append($('<option/>', { 
					value: response.id,
					text : response.option_name 
				})).val(response.id);

		}
	};

	si.timeEntries.timeCreationModal = function( $drop ) {
		// thickbox
		tb_show( si_js_object.time_creation_modal_title, si_js_object.time_creation_modal_url );
	};

	si.timeEntries.timeTrackerModal = function( $drop ) {
		// thickbox
		tb_show( si_js_object.time_tracker_modal_title, si_js_object.time_tracker_modal_url );
	};

	/**
	 * methods
	 */
	si.timeEntries.init = function() {
		// Save time entry
		$( '#create_time_entry' ).live( 'click', function( e ) {
			e.stopPropagation();
			e.preventDefault();
			console.log('click');
			var data = {
				project_id: $('#sa_time_project_id').val(),
				activity_id: $('#sa_time_activity_id').val(),
				time_val: $('#sa_time_time_inc').val(),
				note: $('#sa_time_note').val(),
				date: $('#sa_time_date').val(),
				nonce: $('#sa_time_nonce').val()
			};
			$('span.inline_error_message').hide();
			$('span.inline_success_message').hide();
			si.timeEntries.save( data, $( this ) );
		} );


		// Save time activity
		$( '#create_time_activity' ).live( 'click', function( e ) {
			e.stopPropagation();
			e.preventDefault();

			var data = {
				name: $('#sa_time_name').val(),
				rate: $('#sa_time_rate').val(),
				percentage: $('#sa_time_percentage').val(),
				nonce: $('#sa_time_nonce').val()
			};

			if ( $('#sa_time_billable').is(':checked') ) {
				data.billable = true;
			};

			$('span.inline_error_message').hide();
			si.timeEntries.saveActivity( data, $( this ) );

		} );


		// Remove time entry
		$( '.time_entry_deletion' ).live( 'click', function( e ) {
			var data = {
					project_id: $(this).data('project-id'),
					id: $(this).data('id'),
					nonce: $(this).data('nonce')
				};
			$('span.inline_error_message').hide();
			si.timeEntries.deleteTimeEntry( $(this), data, $(this).data('id') );
		} );

		// Dynamically insert the time creation form.
		$( '#show_time_creation_modal' ).live( 'click', function( e ) {
			si.timeEntries.timeCreationModal();
		} );

		// Dynamically add the time tracking form.
		$( '.time_tracker_popup' ).live( 'click', function( e ) {
			si.timeEntries.timeTrackerModal();
		} );


		// Remove time activity
		$( '.time_activity_deletion' ).live( 'click', function( e ) {
			var data = {
					id: $(this).data('id'),
					nonce: $(this).data('nonce')
				};
			$('span.inline_error_message').hide();
			si.timeEntries.deleteTimeActivity( $(this), data, $(this).data('id') );
		} );
	};
	
})( jQuery, window.si = window.si || {} );

// Init
jQuery(function() {
	si.timeEntries.init();
});
