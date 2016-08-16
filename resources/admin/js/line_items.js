;(function( $, si, undefined ) {
	
	si.lineItems = {
		config: {
		},
	};

	si.lineItems.addLineItem = function( $link ) {
		var item_type = $link.data('item-id'),
			doc_id = $link.data('doc-id');
		si.lineItems.addNewItemRow( item_type, doc_id );
	};


	si.lineItems.addNewItemRow = function( item_type, doc_id ) {
		var $type_list = $('ol#line_item_list'),
			$type_header = $('#line_items_header');

		$type_list.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: 'sa_get_item_option', item_type: item_type, doc_id: doc_id },
			function( response ) {
				if ( response.success ) {
					var $row = $(response.data.option);

					$('.spinner').hide();
					
					// append the row to the list.
					$type_list.append($row);

					// update key
					si.lineItems.modifyInputKey();

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
	 * Hide the inputs for parent line items
	 * @return {} 
	 */
	si.lineItems.handleParents = function() {
		$('ol#line_item_list .item').each(function(i, li) {
			var type = $(li).find('.line_item_option_wrap').data('type');
			//console.log(type);
			// If has children
			if ( $(li).children('ol').length > 0 ) {
				// hide the parent input fields
				$(li).find('.column.parent_hide input.sa_option_text').addClass('cloak');
				has_different_sub_type = false;
				$(li).find('.line_item_option_wrap').each(function(i, li_wrap) {
					var subtype = $(li_wrap).data('type');
					if ( subtype !== type ) {
						has_different_sub_type = true;
					};
				});
				if ( ! has_different_sub_type ) {
					$(li).addClass('hide_subheading_columns');
				};
			}
			else {
				$(li).find('.column.parent_hide input.sa_option_text').removeClass('cloak');
				$(li).removeClass('hide_subheading_columns');
			}
		});
		
	};

	/**
	 * Store the line item index 
	 * @return {} 
	 */
	si.lineItems.modifyInputKey = function() {
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
	 * Loop through all line items that are not parents.
	 * Calculation of parent line items will be after.
	 */
	si.lineItems.calculateEachLineItemTotal = function() {
		$('ol.items_list .item').each(function(i, li) {
			// If no children
			if ( $(li).children('ol').length === 0 ) {
				si.lineItems.calculateLineItemTotals( li );
				si.lineItems.calculateParentTotals();
			}
		});
	}

	/**
	 * Calculate line items based on the input
	 * @param  {object} list item (li) 
	 * @return {}    
	 */
	si.lineItems.calculateLineItemTotals = function( li ) {
		// Clear out the totals if there's no more children
		$(li).find('.column_rate span').html('');
		$(li).find('.column_qty span').html('');
		$(li).find('.column_tax span').html('');
		$(li).find('.column_total span').html('');

		// do the totals
		var $rate_total = $(li).find('.column_rate input').val();
		if ( $rate_total === undefined ) {
			$rate_total = 0;
		};
		var $qty_total = $(li).find('.column_qty input').val();
		if ( $qty_total === undefined ) {
			$qty_total = 1;
		};
		var $tax_total = $(li).find('.column_tax input').val();
		if ( $tax_total === undefined ) {
			$tax_total = 0;
		};
		var $total = ( $rate_total * $qty_total ) * ( ( 100 - $tax_total ) / 100 );

		$(li).find('.column_total span').html( parseFloat( $total ).toFixed(2) );
		$(li).find('.column_total input').val( parseFloat( $total ).toFixed(2) );

		$('ol.items_list').trigger( 'siCalculateLineItemTotals', [ li, $total ] );

	}

	/**
	 * Calculate parent line item totals based on children that were updated prior.
	 * FUTURE average out the rate and discount and show total qty.
	 * @return {} 
	 */
	si.lineItems.calculateParentTotals = function() {
		$('ol.items_list .item').each(function(i, li) {
			if ( $(li).children('ol').length > 0 ) {
				var $totals = 0;
				$(li).children('ol').find('.item .column_total input').each(function(i,n){
					$val = ( $(n).val() === '' ) ? 0 : $(n).val();
					$totals += parseFloat($val);
				});
				var $parent_total_span = $(li).find('.column_total span').first(),
					$parent_total_input = $(li).find('.column_total input').first(),
					$parent_total = parseFloat( $totals ).toFixed(2);

				$parent_total_input.val( parseFloat( $parent_total ).toFixed(2) );
				si.lineItems.totalUpdate( $parent_total_span, $parent_total );
			}
		});
		$('ol.items_list').trigger( 'siCalculateParentTotals' );
	}

	si.lineItems.calculateLineItemsTotals = function() {
		si.lineItems.calculateSubtotal();
		si.lineItems.calculateTotal();
	}

	si.lineItems.calculateSubtotal = function() {
		var $totals = 0;
		$('ol.items_list .item').each(function(i, li) {
			// If no children
			if ( $(li).children('ol').length === 0 ) {
				$totals += parseFloat( $(li).find('.column_total input').val() );
			}
		});

		var $subtotal_span = $('#line_subtotal span'),
			$formatted_total = parseFloat( $totals ).toFixed(2);

		si.lineItems.totalUpdate( $subtotal_span, $formatted_total );
		return $formatted_total;
	}

	si.lineItems.calculateTotal = function() {
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

		var $total_span = $('#line_balance span'),
			$formatted_total = parseFloat( $total ).toFixed(2);

		var $total_span = $('#line_total span'),
			$formatted_total = parseFloat( $total ).toFixed(2);

		si.lineItems.totalUpdate( $total_span, $formatted_total );

		$('#deposit input').attr('max',parseFloat( $total ).toFixed(2));

		$('ol.items_list').trigger( 'siCalculateTotal', [ $formatted_total ] );
	}

	/**
	 * Simple function to highlight the total update.
	 * @param  {object} span  span that will get the total
	 * @param  {float} total total that will be showed
	 * @return {}       
	 */
	si.lineItems.totalUpdate = function( span, total ) {
		$total = ( isNaN(total) ) ? '0.00' : total;
		span.hide().html($total).fadeIn();
	}


	// si.lineItems.forceNumeric() plug-in implementation
	si.lineItems.forceNumeric = function(element) {
		if ( element.hasClass('input_value_is_numeric') ) {
			element.numeric();
		};
	}

	/**
	 * methods
	 */
	si.lineItems.init = function() {

		/**
		 * Line Item Management
		 */

		si.lineItems.handleParents();
		si.lineItems.calculateParentTotals();

		/**
		 * Use the nestable jquery plugin on the line items.
		 */
		$('.nestable').nestable({ 
			maxDepth: 2,
			listClass: 'items_list',
			itemClass: 'item',
			expandBtnHTML: '<button data-action="expand" type="button"></button>',
			collapseBtnHTML: '<button data-action="collapse" type="button"></button>'
		}).on( 'change', function(e) {
			si.lineItems.modifyInputKey();
			si.lineItems.handleParents();
			si.lineItems.calculateEachLineItemTotal();
		});

		// WYSIWYG
		if ( si_js_object.redactor ) {
			$('.item:not(#line_item_default) .column_desc [name="line_item_desc[]"]').redactor();
			$('.si_redactorize').redactor();
		};

		// Add item
		$('.item_add_type').live('click', function(e) {
			si.lineItems.addLineItem( $( this ) );
		});

		/**
		 * Add a line items
		 * @return {} 
		 */
		$('.item_action.item_clone').live( 'click', function() {
			var $row = $(this).closest('.item').clone().attr('id','');
			var rand_num = Math.floor( Math.random() * 10000000 );
			// change the unique id
			$row.find('[name="line_item__id[]"]').val( rand_num );

			// WYSIWYG
			if ( si_js_object.redactor ) {
				var $ta = $row.find('.sa_option_textarea').clone().removeClass('edited');
				$row.find('.redactor-box').remove();
				$row.find('.line_item_option_row .column_desc').append($ta);
				$row.find('.column_desc textarea').redactor();
			};

			// add
			$(this).closest('.items_list').append($row);
			// update key
			si.lineItems.modifyInputKey();

			return false;
		});

		/**
		 * Delete a line item
		 * @return {} 
		 */
		$('.item_action.item_delete').live( 'click', function() {
			$(this).closest('.item').remove();
			si.lineItems.modifyInputKey();
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
		 * calculate totals whenever an input is updated
		 * @return {} 
		 */
		$('.totalled_input').live( 'keyup', function() {
			si.lineItems.calculateEachLineItemTotal();
			si.lineItems.calculateSubtotal();
			si.lineItems.calculateTotal();
			si.lineItems.forceNumeric( $(this) );
		});

		// Add initial item if none exist.
		if ( $('ol#line_item_list li').length === 0 ) {
			$('.item_add_type.default_type').trigger('click');
		};
	};
	
})( jQuery, window.si = window.si || {} );

// Init
jQuery(function() {
	si.lineItems.init();
});
