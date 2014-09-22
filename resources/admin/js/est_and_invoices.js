jQuery.noConflict();

jQuery(function($) {
	var SI = SI || {};


	$('#permalink-select').live( 'click', function(e) {
		SelectText('permalink-select');
	});

	/**
	 * Status Updates
	 */
	jQuery("#quick_links .quick_status_update a.doc_status_change").live('click', function(e) {
		e.preventDefault();
		var $status_change_link = $( this ),
			$status_button = $( this ).closest('.quick_status_update'),
			$new_status = $status_change_link.data( 'status-change' ),
			$new_status_title = $status_change_link.text(),
			$id = $status_change_link.data( 'id' ),
			$nonce = $status_change_link.data( 'nonce' ),
			$status_span = $('#status b'),
			$status_select = $('[name="post_status"]'),
			$publish_button = $('[type="submit"]'),
			$publish_button_text = $publish_button.val();

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
	});

	$('#send_doc_quick_link').live( 'click', function(e) {
		$('html, body').animate({
			scrollTop: $("#si_doc_send").offset().top
		}, 200);
	});

	/**
	 * Disable quick send if the form has changed.
	 */
	$('form#post').on( 'keyup change', 'input:not([name="sa_metabox_recipients[]"]), select, textarea:not([name="sa_metabox_sender_note"])', function( e ){
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
	$('#create_client').on('click', function(e) {
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
					$send_button.after('<span class="inline_error_message">' + data.response + '</span>');	
				}
				else {
					// close modal
					self.parent.tb_remove();
					// Remove add button
					$('#create_client_tb_link').hide();
					// change option text
					$('#client b').text(data.title);
					// add the new option to the select option
					$('[name="client"]').append($('<option/>', { 
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
	 * Create private note
	 */
	$("#save_private_note").on('click', function(e) {
		e.stopPropagation();
		e.preventDefault();
		var $add_button = $( this ),
			$post_id = $add_button.data( 'post-id' ),
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
	});

	/**
	 * Add an admin payment
	 */
	$("#add_admin_payments").on('click', function(e) {
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
	 * Use the nestable jquery plugin on the line items.
	 */
	$('#nestable').nestable({ 
		maxDepth: 2,
		listClass: 'items_list',
		itemClass: 'item',
		expandBtnHTML: '<button data-action="expand" type="button"></button>',
		collapseBtnHTML: '<button data-action="collapse" type="button"></button>',
		callback: function(l,e){
			// l is the main container
			// e is the element that was moved
			modify_input_key();
			handle_parents();
			calculate_totals();
		}
	});

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
		$('ol.items_list li:last').after($row);
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
				$(li).find('.column_rate input').attr( "type", "hidden" );
				$(li).find('.column_qty input').attr( "type", "hidden" );
				$(li).find('.column_tax input').attr( "type", "hidden" );
			}
			else {
				$(li).find('.column_rate input').attr( "type", "text" );
				$(li).find('.column_qty input').attr( "type", "text" );
				$(li).find('.column_tax input').attr( "type", "text" );
			}
		});
		$('ol.items_list .has_children').each(function(i, parent) {
			$(parent).find('.column_rate input').val('');
			$(parent).find('.column_qty input').val('');
			$(parent).find('.column_tax input').val('');
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
		var $rate_totals = $(li).find('.column_rate input').val();
		var $qty_totals = $(li).find('.column_qty input').val();
		var $tax_totals = $(li).find('.column_tax input').val();
		var $total_totals = ( $rate_totals * $qty_totals ) * ( ( 100 - $tax_totals ) / 100 );
		
		$(li).find('.column_total span').html( parseFloat( $total_totals ).toFixed(2) );
		$(li).find('.column_total input').val( parseFloat( $total_totals ).toFixed(2) );

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

});