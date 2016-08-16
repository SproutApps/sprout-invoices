;(function( $, si, undefined ) {

	si.docEdit = {
		config: {
		},
	};

	/**
	 * Select text
	 * @param {} element element id without #
	 */
	si.docEdit.SelectText = function(element) {
		var doc = document, 
			text = doc.getElementById(element), 
			range, 
			selection;    
		if (doc.body.createTextRange) {
			range = document.body.createTextRange();
			range.moveToElementText(text);
			range.select();
		} else if (window.getSelection) {
			selection = window.getSelection();        
			range = document.createRange();
			range.selectNodeContents(text);
			selection.removeAllRanges();
			selection.addRange(range);
		}
	}

	/**
	 * Refresh the client submit sidebar
	 * @param  {int} $client_id 
	 * @return {html}            
	 */
	si.docEdit.refresh_client_submit_meta = function( $client_id ) {
		var $submit_box = $('#si_client_submit .submitbox'),
			$user_modal = $('#user_creation_modal');
		$.post( ajaxurl, { action: 'sa_client_submit_metabox', client_id: $client_id },
			function( data ) {
				$user_modal.remove();
				$submit_box.html(data);
				$('#associated_users').select2();
			}
		);
	};


	si.docEdit.editPrivateNote = function( button ) {
		var $button = $(button),
			record_id = $button.data( 'id' ),
			private_note = $( '#sa_note_note' ).val(),
			nonce = si_js_object.security;

		$('span.inline_error_message').hide();
		$button.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'si_edit_private_note', record_id: record_id, private_note: private_note, nonce: nonce },
			function( response ) {
				$('.spinner').hide();
				if ( response.error ) {
					$button.after('<span class="inline_error_message">' + response.response + '</span>');	
				}
				else {
					// close modal
					self.parent.tb_remove();
					$( 'dd.record-' + record_id + ' p:first-of-type' ).html( private_note );
				}
			}
		);
	};

	si.docEdit.deleteRecord = function( button ) {
		var $button = $(button),
			record_id = $button.data( 'id' ),
			$record_wraps = $( '.record-' + record_id ),
			nonce = si_js_object.security;

		$.post( ajaxurl, { action: 'si_delete_record', record_id: record_id, nonce: nonce },
			function( response ) {
				if ( response.error ) {
					console.log( response.error );
				}
				else {
					$record_wraps.fadeOut();
				}
			}
		);
	};

	/**
	 * Prevent collapse of line items
	 * @return {} 
	 */
	si.docEdit.preventCollapseLineItemsMetaBox = function() {
		// $('#si_invoice_line_items.postbox .hndle').unbind('click.postboxes');
		// $('#si_estimate_line_items.postbox .hndle').unbind('click.postboxes');
		// In case it was closed before the update
		$('#si_invoice_line_items, #si_estimate_line_items').removeClass('closed');
		// remove the class after it's been added since unbind isn't working properly.
		$('#si_invoice_line_items, #si_estimate_line_items').on('click', 'h3.hndle', function(event){
			$('#si_invoice_line_items, #si_estimate_line_items').removeClass('closed');
		});
	};

	/**
	 * Sticky header
	 * @return {} 
	 */
	si.docEdit.stickySave = function() {
		var $sticky_offest = ( $('body').hasClass('admin-bar') ) ? 30: 0;
		$(".sticky_save").sticky( { topSpacing: $sticky_offest, center:true, className: 'stuck' } );
	};

	si.docEdit.timeImportingButton = function( button ) {
		var $select_wrap = $('#time_importing_project_selection'),
			$time_help = $('.add_time_help');
		$(button).hide();
		$time_help.hide();
		$select_wrap.fadeIn();
	};

	si.docEdit.timeImportingProjectSelected = function( select ) {
		var $select = $(select),
			project_id = $select.val(),
			nonce = $('#time_tracking_nonce').val(),
			$info_project_span = $('#project b'),
			$info_project_select = $('[name="doc_project"]');

		$('span.inline_error_message').hide();
		$select.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'sa_projects_time', project_id: project_id, nonce: nonce, billable: true },
			function( response ) {
				$('.spinner').hide();
				if ( response.error ) {
					$select.after('<span class="inline_error_message">' + response.response + '</span>');	
				}
				else {
					$.each( response, function(i, time) {
						si.docEdit.timeAddItem( time );
					});
					$('#time_importing_project_selection').hide();
					$('#time_import_question_answer').fadeIn();
				}
			}
		);

		// Update project dropdown if not other project is selected.
		// This will cause the dropdown to default to the first project.
		if ( $info_project_select.val() < 1 ) {
			$info_project_select.val( project_id );
			$info_project_span.text( $select.find('option:selected').text() );
		};
	};

	si.docEdit.timeAddItem = function( time ) {
		var $type_list = $('ol#line_item_list'),
			$type_header = $('#line_items_header');

		$type_list.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'sa_get_time_item', time: time },
			function( response ) {
				if ( response.success ) {
					var $row = $(response.data.option);

					$('.spinner').hide();
					
					// append the row to the list.
					$type_list.append($row);

					// update key
					si.lineItems.modifyInputKey();
					si.lineItems.calculateEachLineItemTotal();
					
					// Add the redactor
					if ( si_js_object.redactor ) {
						$row.find('.column_desc [name="line_item_desc[]"]').redactor();
					};
				}
				else {
					$type_list.append('<span class="inline_message inline_error_message">' + response.data.message + '</span>');
				}
			}
		);
	};

	/**
	 * Expenses
	 */
	


	si.docEdit.expenseImportingButton = function( button ) {
		var $select_wrap = $('#expense_importing_project_selection'),
			$expense_help = $('.add_expense_help');
		$(button).hide();
		$expense_help.hide();
		$select_wrap.fadeIn();
	};

	si.docEdit.expenseImportingProjectSelected = function( select ) {
		var $select = $(select),
			project_id = $select.val(),
			nonce = $('#expense_tracking_nonce').val(),
			$info_project_span = $('#project b'),
			$info_project_select = $('[name="doc_project"]');

		$('span.inline_error_message').hide();
		$select.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'sa_projects_expense', project_id: project_id, nonce: nonce, billable: true },
			function( response ) {
				$('.spinner').hide();
				if ( response.error ) {
					$select.after('<span class="inline_error_message">' + response.response + '</span>');	
				}
				else {
					$.each( response, function(i, expense) {
						si.docEdit.expenseAddItem( expense );
					});
					$('#expense_importing_project_selection').hide();
					$('#expense_import_question_answer').fadeIn();
				}
			}
		);

		// Update project dropdown if not other project is selected.
		// This will cause the dropdown to default to the first project.
		if ( $info_project_select.val() < 1 ) {
			$info_project_select.val( project_id );
			$info_project_span.text( $select.find('option:selected').text() );
		};
	};

	si.docEdit.expenseAddItem = function( expense ) {
		var $type_list = $('ol#line_item_list'),
			$type_header = $('#line_items_header');

		$type_list.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'sa_get_expense_item', expense: expense },
			function( response ) {
				if ( response.success ) {
					var $row = $(response.data.option);

					$('.spinner').hide();
					
					// append the row to the list.
					$type_list.append($row);

					// update key
					si.lineItems.modifyInputKey();
					si.lineItems.calculateEachLineItemTotal();
					
					// Add the redactor
					if ( si_js_object.redactor ) {
						$row.find('.column_desc [name="line_item_desc[]"]').redactor();
					};
				}
				else {
					$type_list.append('<span class="inline_message inline_error_message">' + response.data.message + '</span>');
				}
			}
		);
	};

	si.docEdit.createNote = function( $add_button ) {
		var $post_id = $add_button.data( 'post-id' ),
			$nonce = $add_button.data( 'nonce' ),
			$private_note = $( '[name="private_note"]' ),
			$add_button_og_text = $add_button.text();
		$add_button.html( '' );
		$add_button.append(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'sa_create_private_note', associated_id: $post_id, notes: $private_note.val(), private_note_nonce: $nonce },
			function( data ) {
				if ( data.id ) {
					var dl = '<dt>' + data.type + '<br/>' + data.post_date + '</dt><dd><p>' + data.content + '</p></dd>';
					$('#history_list').append( dl );
					$private_note.val('');
				}
				else {
					$add_button.after( '<p><code>' + data.error + '</code></p>' );
				};

				$add_button.html( $add_button_og_text );
				return data;
			}
		);
	};

	/**
	 * Status Updates
	 */
	si.docEdit.statusChange = function( $status_change_link ) {
		var $status_button = $status_change_link.closest('.quick_status_update'),
			$new_status = $status_change_link.data( 'status-change' ),
			$new_status_title = $status_change_link.text(),
			$id = $status_change_link.data( 'id' ),
			$nonce = $status_change_link.data( 'nonce' ),
			$status_span = $('#status b'),
			$status_select = $('[name="post_status"]'),
			$publish_button = $('[type="submit"]'),
			$publish_button_text = $publish_button.val(),
			$current_status = si_js_object.doc_status;

		// if a auto-draft the status can't be changed until after it's saved.
		if ( $current_status === 'auto-draft' ) {
			$('[name="post_status"]').val($new_status);
			$('#status.wp-media-buttons-icon b').text( $status_change_link.attr('title') );
			return;
		};

		$status_button.html(si_js_object.inline_spinner);
		$publish_button.val( si_js_object.updating_string );

		$.post( ajaxurl, { action: 'si_change_doc_status', id: $id, status: $new_status, change_status_nonce: $nonce },
			function( data ) {
				if ( data.error ) {
					$status_button.html( data.response );	
				}
				else {
					$button_html = $( data.new_button ).html();
					// swap out the button with the new one
					$status_button.html( $button_html );

					// Update status dropdown
					$status_select.val( $new_status );
					$status_span.text( $status_select.find('option:selected').text() );
					// Change 
					$publish_button.val( $publish_button_text );
				};
				return data;
			}
		);
	};

	/**
	 * methods
	 */
	si.docEdit.init = function() {

		// sticky header
		si.docEdit.stickySave();

		// Select permalink
		$('#permalink-select').live( 'click', function(e) {
			si.docEdit.SelectText('permalink-select');
		});

		// Time importing
		$('#time_import_question_answer').live( 'click', function(e) {
			e.preventDefault();
			si.docEdit.timeImportingButton( this );
		});

		// Time importing
		$('#time_importing_project_selection select').live( 'change', function(e) {
			e.preventDefault();
			si.docEdit.timeImportingProjectSelected( this );
		});

		// Expense importing
		$('#expense_import_question_answer').live( 'click', function(e) {
			e.preventDefault();
			si.docEdit.expenseImportingButton( this );
		});

		// Expense importing
		$('#expense_importing_project_selection select').live( 'change', function(e) {
			e.preventDefault();
			si.docEdit.expenseImportingProjectSelected( this );
		});

		// Create private note
		$("#save_private_note").live('click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			si.docEdit.createNote( $( this ) );
		});
		
		// Status updates
		$("#quick_links .quick_status_update a.doc_status_change").live('click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			si.docEdit.statusChange( $( this ) );
		});

		// scroll to send
		$('#send_doc_quick_link').live( 'click', function(e) {
			$('html, body').animate({
				scrollTop: $("#si_doc_send").offset().top
			}, 200);
		});

		// delete history record
		$('.delete_record').live( 'click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			si.docEdit.deleteRecord( this );
		});

		// edit private note
		$('#save_edit_private_note').live( 'click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			si.docEdit.editPrivateNote( this );
		});

		si.docEdit.preventCollapseLineItemsMetaBox();

		/**
		 * Disable quick send if the form has changed.
		 */
		$('[name="sa_send_metabox_send_as"]').live( 'click', function( e ){
			$(this).prop('readonly', false);
		});

		/**
		 * Disable quick send if the form has changed.
		 */
		$('[name="sa_metabox_custom_recipient"]').live( 'keyup', function( e ){
			var val = $('[name="sa_metabox_custom_recipient"]').val(),
				$checkbox = $('[name="sa_metabox_custom_recipient_check"]');
			if ( val.length > 0 ) {
				$checkbox.prop('checked', true);
			}
			else {
				$checkbox.prop('checked', false);
			};
			
			// $('#quick_send_option #send_doc_notification').attr( 'disabled', 'disabled' );
		});

		/**
		 * Send estimate
		 */
		$('#send_doc_notification').live( 'click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $send_button = $(this),
				$fields_wrap = $('#send_doc_options_wrap'),
				$meta_box = $('#si_doc_send'),
				$fields = $('#send_doc_options_wrap :input').serializeArray();

			$send_button.after(si_js_object.inline_spinner);
			$('span.inline_error_message').hide();
			$.post( ajaxurl, { action: 'sa_send_est_notification', serialized_fields: $fields },
				function( data ) {
					$('.spinner').hide();
					if ( data.error ) {
						$send_button.after('<span class="inline_error_message">' + data.response + '</span>');	
					}
					else {
						$('#si_doc_send :checked').removeAttr('checked');
						$meta_box.before('<div class="updated"><p>' + data.response + '</p></div>');
					}
				}
			);
		});

		/**
		 * Option updates
		 */
		$('.misc-pub-section a.edit_control').live( 'click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $parent = $(this).parents('.misc-pub-section'),
				$id = $parent.data('edit-id'),
				$controls = $parent.find('.control_wrap'),
				$si_tooltip = $parent.find('.si_tooltip');
			$(this).hide();
			$si_tooltip.show();
			$('.misc-pub-section .select2').select2();
			$controls.slideDown('fast');
		});

		$('.misc-pub-section a.save_control').live( 'click', function(e) {
			e.stopPropagation();
			e.preventDefault();

			var $parent = $(this).parents('.misc-pub-section'),
				$id = $parent.data('edit-id'),
				$type = $parent.data('edit-type'),
				$span = $parent.find('span b'),
				$edit_control = $parent.find('.edit_control'),
				$si_tooltip = $parent.find('.si_tooltip'),
				$controls = $parent.find('.control_wrap');

			var $value = ( $type === 'select' ) ? $parent.find($type + ' option:selected').text() : $parent.find('input').val();

			if ( $id === 'expiration_date' ) {
				$value = $parent.find('#exp_mm option:selected').text() + ' ' + $parent.find('#exp_jj').val() + ', ' + $parent.find('#exp_o').val();
				$span.text($value);
			}
			else if ( $id === 'due_date' ) {
				$value = $parent.find('#due_mm option:selected').text() + ' ' + $parent.find('#due_jj').val() + ', ' + $parent.find('#due_o').val();
				$span.text($value);
			}
			else {
				$span.text($value);
			}

			if ( $parent.hasClass('update-total') ) {
				si.lineItems.calculateTotal();
			};

			$si_tooltip.hide();
			$edit_control.show();
			$controls.slideUp('fast');
			return;
		});

		$('.misc-pub-section a.cancel_control').live( 'click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $parent = $(this).parents('.misc-pub-section'),
				$si_tooltip = $parent.find('.si_tooltip'),
				$edit_control = $parent.find('.edit_control');

			$si_tooltip.hide();
			$edit_control.show();
			$(this).parents('.control_wrap').slideUp('fast');
		});

		$('.misc-pub-section select[name="client"]').live( 'change', function(e) {
			var $value = $(this).val();
			if ( $value === 'create_client' ) {
				$('#create_client_tb_link').trigger('click');
			};
		});

		/**
		 * Create client via ajax
		 */
		$('#create_client').live('click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $save_button = $( this ),
				$fields = $( "#client_create_form :input" ).serializeArray(),
				$save_button_og_text = $save_button.text();

			$save_button.after(si_js_object.inline_spinner);
			$.post( ajaxurl, { action: 'sa_create_client', serialized_fields: $fields },
				function( data ) {
					$('.spinner').hide();
					if ( data.error ) {
						$('.spinner').hide();
						$save_button.after('<span class="inline_error_message">' + data.response + '</span>');	
					}
					else {
						// close modal
						self.parent.tb_remove();
						// Remove add button
						$('#create_client_tb_link').hide();
						// change option text
						$('#client b').text(data.title);
						$('[name="sa_metabox_client"]').append($('<option/>', { 
								value: data.id,
								text : data.title 
							})).val(data.id);

						var	$client_controls = $('#client').parents('.misc-pub-section'),
							$edit_control = $client_controls.find('.edit_control'),
							$si_tooltip = $client_controls.find('.si_tooltip'),
							$controls = $client_controls.find('.control_wrap');

						// close the control						
						$si_tooltip.hide();
						$edit_control.show();
						$controls.slideUp('slow');
					}
				}
			);

		});

		/**
		 * Manage users for client list
		 */
		$('#associated_users').select2();
		$('#associated_users').live('change', function(e) {
			var $data = $(this).select2('data')[0],
				$option = $(this).find("option:selected"),
				$user_id = $data.id,
				$user_name = $data.text,
				$edit_url = $option.data('url'),
				$dl = $('#associated_users_list');

			var user_item = '<li id="list_user_id-'+$user_id+'"><a href="'+$edit_url+'">'+$user_name+'</a> <a data-id="'+$user_id+'" class="remove_user del_button">X</a></li>';
			
			$dl.append( user_item );
			$('#hidden_associated_users_list').append($('<input/>', {
								type: 'hidden',
								name: 'associated_users[]',
								value: $user_id
							}));
		});

		/**
		 * Remove user and hidden option associated list
		 */
		$('.remove_user').live('click', function(e) {
			var $user_id = $( this ).data('id');
			$('#list_user_id-'+$user_id).remove();
			$('#hidden_associated_users_list').find( '[value="'+$user_id+'"]' ).remove();
		});

		/**
		 * Create user via ajax
		 */
		$('#create_user').live('click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $save_button = $( this ),
				$fields = $( "#user_create_form :input" ).serializeArray(),
				$client_id = $( "#sa_user_client_id" ).val(),
				$save_button_og_text = $save_button.text();

			$save_button.after(si_js_object.inline_spinner);
			$.post( ajaxurl, { action: 'sa_create_user', serialized_fields: $fields },
				function( data ) {
					$('.spinner').hide();
					if ( data.error ) {
						$('.spinner').hide();
						$save_button.after('<span class="inline_error_message">' + data.response + '</span>');	
					}
					else {
						si.docEdit.refresh_client_submit_meta( $client_id );
						self.parent.tb_remove();
					}
				}
			);
		});

		/**
		 * Add an admin payment
		 */
		$("#add_admin_payments").live('click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $send_button = $(this),
				$fields_wrap = $('#admin_payments_options_wrap'),
				$meta_box = $('#si_invoice_payment'),
				$fields = $('#admin_payments_options_wrap :input').serializeArray();

			$send_button.after(si_js_object.inline_spinner);
			$('span.inline_error_message').hide();
			$.post( ajaxurl, { action: 'sa_admin_payment', serialized_fields: $fields },
				function( data ) {
					$('.spinner').hide();
					if ( data.error ) {
						$send_button.after('<span class="inline_error_message">' + data.response + '</span>');	
					}
					else {
						$meta_box.before('<div class="updated"><p>' + data.response + '</p></div>');
						$fields_wrap.find('input').val('');
					}
				}
			);
		});

		/**
		 * Change the text area to the default, if the text area is blank.
		 * @return {} 
		 */
		$('.column_type select').live( 'change', function() {
			var $val = $(this).val(),
				$description = $('#term_desc_'+$val).text(),
				$parent = $(this).parents('.item').closest('.item'),
				$textarea = $parent.find('.column_desc textarea');
			if ( !$textarea.hasClass('edited') ) {
				$textarea.val($description);
			};
			return false;
		});

	
	}; // end init

})( jQuery, window.si = window.si || {} );

// Init
jQuery(function() {
	si.docEdit.init();
});

/*
 *
 * Copyright (c) 2006-2014 Sam Collett (http://www.texotela.co.uk)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Version 1.4.1
 * Demo: http://www.texotela.co.uk/code/jquery/numeric/
 *
 */
(function($){$.fn.numeric=function(config,callback){if(typeof config==="boolean"){config={decimal:config,negative:true,decimalPlaces:-1}}config=config||{};if(typeof config.negative=="undefined"){config.negative=true}var decimal=config.decimal===false?"":config.decimal||".";var negative=config.negative===true?true:false;var decimalPlaces=typeof config.decimalPlaces=="undefined"?-1:config.decimalPlaces;callback=typeof callback=="function"?callback:function(){};return this.data("numeric.decimal",decimal).data("numeric.negative",negative).data("numeric.callback",callback).data("numeric.decimalPlaces",decimalPlaces).keypress($.fn.numeric.keypress).keyup($.fn.numeric.keyup).blur($.fn.numeric.blur)};$.fn.numeric.keypress=function(e){var decimal=$.data(this,"numeric.decimal");var negative=$.data(this,"numeric.negative");var decimalPlaces=$.data(this,"numeric.decimalPlaces");var key=e.charCode?e.charCode:e.keyCode?e.keyCode:0;if(key==13&&this.nodeName.toLowerCase()=="input"){return true}else if(key==13){return false}var allow=false;if(e.ctrlKey&&key==97||e.ctrlKey&&key==65){return true}if(e.ctrlKey&&key==120||e.ctrlKey&&key==88){return true}if(e.ctrlKey&&key==99||e.ctrlKey&&key==67){return true}if(e.ctrlKey&&key==122||e.ctrlKey&&key==90){return true}if(e.ctrlKey&&key==118||e.ctrlKey&&key==86||e.shiftKey&&key==45){return true}if(key<48||key>57){var value=$(this).val();if($.inArray("-",value.split(""))!==0&&negative&&key==45&&(value.length===0||parseInt($.fn.getSelectionStart(this),10)===0)){return true}if(decimal&&key==decimal.charCodeAt(0)&&$.inArray(decimal,value.split(""))!=-1){allow=false}if(key!=8&&key!=9&&key!=13&&key!=35&&key!=36&&key!=37&&key!=39&&key!=46){allow=false}else{if(typeof e.charCode!="undefined"){if(e.keyCode==e.which&&e.which!==0){allow=true;if(e.which==46){allow=false}}else if(e.keyCode!==0&&e.charCode===0&&e.which===0){allow=true}}}if(decimal&&key==decimal.charCodeAt(0)){if($.inArray(decimal,value.split(""))==-1){allow=true}else{allow=false}}}else{allow=true;if(decimal&&decimalPlaces>0){var dot=$.inArray(decimal,$(this).val().split(""));if(dot>=0&&$(this).val().length>dot+decimalPlaces){allow=false}}}return allow};$.fn.numeric.keyup=function(e){var val=$(this).val();if(val&&val.length>0){var carat=$.fn.getSelectionStart(this);var selectionEnd=$.fn.getSelectionEnd(this);var decimal=$.data(this,"numeric.decimal");var negative=$.data(this,"numeric.negative");var decimalPlaces=$.data(this,"numeric.decimalPlaces");if(decimal!==""&&decimal!==null){var dot=$.inArray(decimal,val.split(""));if(dot===0){this.value="0"+val;carat++;selectionEnd++}if(dot==1&&val.charAt(0)=="-"){this.value="-0"+val.substring(1);carat++;selectionEnd++}val=this.value}var validChars=[0,1,2,3,4,5,6,7,8,9,"-",decimal];var length=val.length;for(var i=length-1;i>=0;i--){var ch=val.charAt(i);if(i!==0&&ch=="-"){val=val.substring(0,i)+val.substring(i+1)}else if(i===0&&!negative&&ch=="-"){val=val.substring(1)}var validChar=false;for(var j=0;j<validChars.length;j++){if(ch==validChars[j]){validChar=true;break}}if(!validChar||ch==" "){val=val.substring(0,i)+val.substring(i+1)}}var firstDecimal=$.inArray(decimal,val.split(""));if(firstDecimal>0){for(var k=length-1;k>firstDecimal;k--){var chch=val.charAt(k);if(chch==decimal){val=val.substring(0,k)+val.substring(k+1)}}}if(decimal&&decimalPlaces>0){var dot=$.inArray(decimal,val.split(""));if(dot>=0){val=val.substring(0,dot+decimalPlaces+1);selectionEnd=Math.min(val.length,selectionEnd)}}this.value=val;$.fn.setSelection(this,[carat,selectionEnd])}};$.fn.numeric.blur=function(){var decimal=$.data(this,"numeric.decimal");var callback=$.data(this,"numeric.callback");var negative=$.data(this,"numeric.negative");var val=this.value;if(val!==""){var re=new RegExp(negative?"-?":""+"^\\d+$|^\\d*"+decimal+"\\d+$");if(!re.exec(val)){callback.apply(this)}}};$.fn.removeNumeric=function(){return this.data("numeric.decimal",null).data("numeric.negative",null).data("numeric.callback",null).data("numeric.decimalPlaces",null).unbind("keypress",$.fn.numeric.keypress).unbind("keyup",$.fn.numeric.keyup).unbind("blur",$.fn.numeric.blur)};$.fn.getSelectionStart=function(o){if(o.type==="number"){return undefined}else if(o.createTextRange&&document.selection){var r=document.selection.createRange().duplicate();r.moveEnd("character",o.value.length);if(r.text=="")return o.value.length;return Math.max(0,o.value.lastIndexOf(r.text))}else{try{return o.selectionStart}catch(e){return 0}}};$.fn.getSelectionEnd=function(o){if(o.type==="number"){return undefined}else if(o.createTextRange&&document.selection){var r=document.selection.createRange().duplicate();r.moveStart("character",-o.value.length);return r.text.length}else return o.selectionEnd};$.fn.setSelection=function(o,p){if(typeof p=="number"){p=[p,p]}if(p&&p.constructor==Array&&p.length==2){if(o.type==="number"){o.focus()}else if(o.createTextRange){var r=o.createTextRange();r.collapse(true);r.moveStart("character",p[0]);r.moveEnd("character",p[1]-p[0]);r.select()}else{o.focus();try{if(o.setSelectionRange){o.setSelectionRange(p[0],p[1])}}catch(e){}}}}})(jQuery);