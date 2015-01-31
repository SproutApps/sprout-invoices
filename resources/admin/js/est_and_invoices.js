;(function( $, si, undefined ) {

	si.docEdit = {
		config: {
			inline_spinner: '<span class="spinner si_inline_spinner" style="display:inline-block;"></span>'	
		},
	};

	/**
	 * methods
	 */
	si.docEdit.init = function() {

		/**
		 * Use the nestable jquery plugin on the line items.
		 */
		$('#nestable').nestable({ 
			maxDepth: 2,
			listClass: 'items_list',
			itemClass: 'item',
			expandBtnHTML: '<button data-action="expand" type="button"></button>',
			collapseBtnHTML: '<button data-action="collapse" type="button"></button>'
		}).on( 'change', function(e) {
			modify_input_key();
			handle_parents();
			calculate_totals();
		});

		// sticky header
		si.docEdit.stickySave();

		// WYSIWYG
		if ( si_js_object.redactor ) {
			$('.item:not(#line_item_default) .column_desc [name="line_item_desc[]"]').redactor();
		};
		
		// Select permalink
		$('#permalink-select').live( 'click', function(e) {
			SelectText('permalink-select');
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

		// Add initial item if none exist.
		if ( $('#nestable > ol.items_list > li').length === 1 ) {
			$('.item_add_type.item_add_no_type').trigger('click');
		};

		// Create private note
		$("#save_private_note").on('click', function(e) {
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
		$select.after(si.docEdit.config.inline_spinner);
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
		// clone the default line item.
		var $row = $('#line_item_default').clone().attr('id','').attr('style',''),
			$dropdown = $('#type_selection'),
			description = time.description,
			qty = time.qty,
			rate = time.activity_rate,
			tax = time.activity_tax;

		$('#line_item_default').hide();

		// remove any children
		$($row).children('ol').remove();

		// append the row to the list.
		$('ol.items_list > li:last').after($row);

		// clear out totals and inputs
		$row.find('.column input').val('');

		// Set the values
		$row.find('.column_total span').html('');
		$row.find('.column_desc textarea').val( description );
		$row.find('[name="line_item_qty[]"]').val( qty );
		$row.find('[name="line_item_rate[]"]').val( rate );
		$row.find('[name="line_item_tax[]"]').val( tax );
		$row.find('[name="line_item_time_id[]"]').val( time.id );

		handle_parents();

		// update key
		modify_input_key();

		// calculate
		$row.find('.totalled_input').trigger('keyup');

		// redactor
		if ( si_js_object.redactor ) {
			$row.find('.column_desc [name="line_item_desc[]"]').redactor();
		};
		
		return;
	};

	si.docEdit.createNote = function( $add_button ) {
		var $post_id = $add_button.data( 'post-id' ),
			$nonce = $add_button.data( 'nonce' ),
			$private_note = $( '[name="private_note"]' ),
			$add_button_og_text = $add_button.text();
		$add_button.html( '' );
		$add_button.append('<span class="spinner" style="display:block;"></span>');
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

		$status_button.html('<span class="spinner si_inline_spinner" style="display:inline-block;"></span>');
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

	///////////////////////
	// TODO standardize //
	///////////////////////

	

	

	/**
	 * Disable quick send if the form has changed.
	 */
	$('form#post').live( 'keyup change', 'input:not([name="sa_metabox_recipients[]"]), select, textarea:not([name="sa_metabox_sender_note"])', function( e ){
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

		$send_button.after('<span class="spinner si_inline_spinner" style="display:inline-block;"></span>');
		$('span.inline_error_message').hide();
		$.post( ajaxurl, { action: 'sa_send_est_notification', serialized_fields: $fields },
			function( data ) {
				$('.spinner').hide();
				if ( data.error ) {
					$send_button.after('<span class="inline_error_message">' + data.response + '</span>');	
				}
				else {
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
			$tooltip = $parent.find('.tooltip');
		$(this).hide();
		$tooltip.show();
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
			$tooltip = $parent.find('.tooltip'),
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
			calculate_total();
		};

		$tooltip.hide();
		$edit_control.show();
		$controls.slideUp('fast');
		return;
	});

	$('.misc-pub-section a.cancel_control').live( 'click', function(e) {
		e.stopPropagation();
		e.preventDefault();
		var $parent = $(this).parents('.misc-pub-section'),
			$tooltip = $parent.find('.tooltip'),
			$edit_control = $parent.find('.edit_control');

		$tooltip.hide();
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

		$save_button.after('<span class="spinner si_inline_spinner" style="display:inline-block;"></span>');
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
						$tooltip = $client_controls.find('.tooltip'),
						$controls = $client_controls.find('.control_wrap');

					// close the control						
					$tooltip.hide();
					$edit_control.show();
					$controls.slideUp('slow');
				}
			}
		);
	});

	/**
	 * Manage users for client list
	 */
	$('#associated_users').live('change', function(e) {
		var $user_id = $(this).select2('data').id,
			$user_name = $(this).select2('data').text,
			$option = $(this).find("option:selected"),
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

		$save_button.after('<span class="spinner si_inline_spinner" style="display:inline-block;"></span>');
		$.post( ajaxurl, { action: 'sa_create_user', serialized_fields: $fields },
			function( data ) {
				$('.spinner').hide();
				if ( data.error ) {
					$('.spinner').hide();
					$save_button.after('<span class="inline_error_message">' + data.response + '</span>');	
				}
				else {
					refresh_client_submit_meta( $client_id );
					self.parent.tb_remove();
				}
			}
		);
	});

	/**
	 * Refresh the client submit sidebar
	 * @param  {int} $client_id 
	 * @return {html}            
	 */
	function refresh_client_submit_meta ( $client_id ) {
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

		$send_button.after('<span class="spinner si_inline_spinner" style="display:inline-block;"></span>');
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
	 * Line Item Management
	 */

	handle_parents();
	calculate_parent_line_item_totals();

	/**
	 * Add a line items
	 * @return {} 
	 */
	$('.item_add_type').live( 'click', function() {
		// clone the first line item.
		var $row = $('#line_item_default').clone().attr('id','').attr('style',''),
			$dropdown = $('#type_selection'),
			$type = $(this).data('type-key'),
			$type_description = $('#term_desc_'+$type).text();
		// remove any children
		$($row).children('ol').remove();
		// append the row to the list.
		$('ol.items_list > li:last').after($row);
		// clear out totals and inputs
		$row.find('.column input').val('');
		//$row.find('.column textarea').val('');
		$row.find('.column_total span').html('');
		$row.find('.column_desc textarea').val($type_description);
		handle_parents();
		// update key
		modify_input_key();
		// hide the dropdown
		$('#type_selection').dropdown('hide');

		// Add the redactor
		if ( si_js_object.redactor ) {
			$row.find('.column_desc [name="line_item_desc[]"]').redactor();
		};
		

		return false;
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

	/**
	 * Add a line items
	 * @return {} 
	 */
	$('.item_action.item_clone').live( 'click', function() {
		var $row = $(this).closest('.item').clone().attr('id','');
		$(this).closest('.items_list').append($row);
		// update key
		modify_input_key();
		return false;
	});

	/**
	 * Delete a line item
	 * @return {} 
	 */
	$('.item_action.item_delete').live( 'click', function() {
		$(this).closest('.item').remove();
		modify_input_key();
		return false;
	});

	/**
	 * Add an edited class so that the item type select desc.
	 * doesn't populate if the field has been edited.
	 * @return {} 
	 */
	$('.column_desc textarea').live( 'keyup', function() {
		$(this).addClass('edited');
		return false;
	});
	$('.column_desc textarea').each(function(i, ta) {
		$textarea = $(ta);
		var $textarea_value = $textarea.val();
		if ( $textarea_value !== '' ) {
			$textarea.addClass('edited');
		};
	});

	/**
	 * Store the line item index 
	 * @return {} 
	 */
	function modify_input_key() {
		$('ol.items_list').each(function(i, ol) {
			ol = $(ol);
			level1 = ol.closest('li').index() + 1;

			ol.children('li').each(function(i, li) {
				li = $(li);
				$index = ( level1 === 0 ) ? li.index() + 1 : level1 + '.' + (li.index() + 1);
				li.find('.line_item_index').val($index);
			});
		});
	}

	/**
	 * Hide the inputs for parent line items
	 * @return {} 
	 */
	function handle_parents() {
		$('ol.items_list .item').each(function(i, li) {
			// If has children
			if ( $(li).children('ol').length > 0 ) {
				// hide the parent input fields
				$(li).find('.column.parent_hide input').attr( "type", "hidden" );
			}
			else {
				$(li).find('.column.parent_hide input').attr( "type", "text" );
			}
		});
		$('ol.items_list .has_children').each(function(i, parent) {
			$(parent).find('.column.parent_hide input').val('');
		});
	}


	/**
	 * calculate totals whenever an input is updated
	 * @return {} 
	 */
	$('.totalled_input').live( 'keyup', function() {
		calculate_totals();
		calculate_subtotal();
		calculate_total();
		forceNumeric( $(this) );
	});

	/**
	 * Loop through all line items that are not parents.
	 * Calculation of parent line items will be after.
	 */
	function calculate_totals() {
		$('ol.items_list .item').each(function(i, li) {
			// If no children
			if ( $(li).children('ol').length === 0 ) {
				calculate_line_item_totals( li );
				calculate_parent_line_item_totals();
			}
		});
	}

	/**
	 * Calculate line items based on the input
	 * @param  {object} list item (li) 
	 * @return {}    
	 */
	function calculate_line_item_totals( li ) {
		// Clear out the totals if there's no more children
		$(li).find('.column_rate span').html('');
		$(li).find('.column_qty span').html('');
		$(li).find('.column_tax span').html('');
		$(li).find('.column_total span').html('');

		// do the totals
		var $rate_total = $(li).find('.column_rate input').val();
		var $qty_total = $(li).find('.column_qty input').val();
		var $tax_total = $(li).find('.column_tax input').val();
		var $total = ( $rate_total * $qty_total ) * ( ( 100 - $tax_total ) / 100 );
		
		$(li).find('.column_total span').html( parseFloat( $total ).toFixed(2) );
		$(li).find('.column_total input').val( parseFloat( $total ).toFixed(2) );

		$('ol.items_list').trigger( 'calculated_line_item_totals', [ li, $total ] );

	}

	/**
	 * Calculate parent line item totals based on children that were updated prior.
	 * FUTURE average out the rate and discount and show total qty.
	 * @return {} 
	 */
	function calculate_parent_line_item_totals() {
		$('ol.items_list .item').each(function(i, li) {
			if ( $(li).children('ol').length > 0 ) {
				var $totals = 0;
				$(li).children('ol').find('.item .column_total input').each(function(i,n){
					$val = ( $(n).val() === '' ) ? 0 : $(n).val();
					$totals += parseFloat($val);
				});
				var $parent_total_span = $(li).find('.column_total span').first(),
					$parent_total = parseFloat( $totals ).toFixed(2);

				total_update( $parent_total_span, $parent_total );

				/*/
				var $rate_total = 0;
				var $qty_total = 0;
				var $tax_total = 0;
				$(li).children('ol').each(function(i, ol){

					$(ol).find('.column_rate input').each(function(i,n){
						$val = ( $(n).val() === '' ) ? 0 : $(n).val();
						$rate_total += parseFloat($val); 
					});
					$(ol).find('.column_qty input').each(function(i,n){
						$val = ( $(n).val() === '' ) ? 1 : $(n).val();
						$qty_total += parseFloat($val); 
					});
					$(ol).find('.column_tax input').each(function(i,n){
						$val = ( $(n).val() === '' ) ? 0 : $(n).val();
						$tax_total += parseFloat($val); 
					});
				});
				var $children_count = $(li).find('li').length;
				// Show the totals from all children
				$(li).find('.column_rate span').html( parseFloat( $rate_total / $children_count ).toFixed(2) );
				$(li).find('.column_qty span').html($qty_total);
				$(li).find('.column_tax span').html( parseFloat( $tax_total / $children_count ).toFixed(2) );
				/**/
			}
		});
		$('ol.items_list').trigger( 'calculate_parent_line_item_totals' );
	}

	function calculate_subtotal() {
		var $totals = 0;
		$('ol.items_list .item').each(function(i, li) {
			// If no children
			if ( $(li).children('ol').length === 0 ) {
				$totals += parseFloat( $(li).find('.column_total input').val() );
			}
		});

		var $subtotal_span = $('#line_subtotal span'),
			$formatted_total = parseFloat( $totals ).toFixed(2);

		total_update( $subtotal_span, $formatted_total );
		return $formatted_total;
	}

	function calculate_total() {
		var $total = 0,
			$sub_total = parseFloat( $('#line_subtotal span').text().replace(/[^0-9\.]+/g,"") ),
			$tax = parseFloat( $('input[name="tax"]').val() ),
			$tax2 = parseFloat( $('input[name="tax2"]').val() ),
			$discount = parseFloat( $('input[name="discount"]').val() );

		var $tax_total = 0;
		if ( $tax > 0 ) {
			// adjust for tax
			$tax_total = $sub_total * ( ( $tax ) / 100 );
		};

		var $tax2_total = 0;
		if ( $tax2 > 0 ) {
			// adjust for tax2
			$tax2_total = $sub_total * ( ( $tax2 ) / 100 );
		};

		// total after tax
		$total = $sub_total + $tax_total + $tax2_total;
		
		if ( $discount > 0 ) {
			// adjust for discount
			$total = $total * ( ( 100 - $discount ) / 100 );
		};

		var $total_span = $('#line_total span'),
			$formatted_total = parseFloat( $total ).toFixed(2);

		total_update( $total_span, $formatted_total );

		$('ol.items_list').trigger( 'calculate_total', [ $formatted_total ] );
	}

	/**
	 * Simple function to highlight the total update.
	 * @param  {object} span  span that will get the total
	 * @param  {float} total total that will be showed
	 * @return {}       
	 */
	function total_update( span, total ) {
		$total = ( isNaN(total) ) ? '0.00' : total;
		span.hide().html($total).fadeIn();
	}

	/**
	 * Select text
	 * @param {} element element id without #
	 */
	function SelectText(element) {
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


	// forceNumeric() plug-in implementation
	function forceNumeric(element) {
		element.numeric();
	}


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