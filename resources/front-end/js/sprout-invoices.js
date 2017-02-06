var $ = jQuery.noConflict();
jQuery(function($) {
	
	/**
	 * Sticky header
	 * @return {} 
	 */
	$(document).ready(function(){
		var $offest = ( $('body').hasClass('admin-bar') ) ? 30: 0;
		$(".sticky_header").sticky( { topSpacing:$offest, center:true, className: 'stuck' } );
	});

	/**
	 * Tooltip with qtip
	 * @type {}
	 */
	$('.si_tooltip[title!=""], .helptip[title!=""]').qtip({
		style: {
			classes: 'qtip-bootstrap'
		}
	});


	/**
	 * Status Updates
	 */
	jQuery("#doc_actions a.status_change").on('click', function(e) {
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

	jQuery("#doc_actions a.cc_processor").on('click', function(e) {
		e.preventDefault();
		$('#check_info_checkout_wrap').slideUp('fast');
		$('#bacs_info_checkout_wrap').slideUp('fast');
		$('#popayment_info_checkout_wrap').slideUp('fast');
		$('#credit_card_checkout_wrap').slideDown();
	});

	jQuery("#doc_actions a.checks").on('click', function(e) {
		e.preventDefault();
		$('#credit_card_checkout_wrap').slideUp('fast');
		$('#bacs_info_checkout_wrap').slideUp('fast');
		$('#popayment_info_checkout_wrap').slideUp('fast');
		$('#check_info_checkout_wrap').slideDown();
	});

	jQuery("#doc_actions a.popayment").on('click', function(e) {
		e.preventDefault();
		$('#credit_card_checkout_wrap').slideUp('fast');
		$('#check_info_checkout_wrap').slideUp('fast');
		$('#bacs_info_checkout_wrap').slideUp('fast');
		$('#popayment_info_checkout_wrap').slideDown();
	});

	jQuery("#doc_actions a.bacs").on('click', function(e) {
		e.preventDefault();
		$('#credit_card_checkout_wrap').slideUp('fast');
		$('#check_info_checkout_wrap').slideUp('fast');
		$('#popayment_info_checkout_wrap').slideUp('fast');
		$('#bacs_info_checkout_wrap').slideDown();
	});

});

// Sticky Plugin v1.0.0 for jQuery
// =============
// Author: Anthony Garand
// Improvements by German M. Bravo (Kronuz) and Ruud Kamphuis (ruudk)
// Improvements by Leonardo C. Daronco (daronco)
// Created: 2/14/2011
// Date: 2/12/2012
// Website: http://labs.anthonygarand.com/sticky
// Description: Makes an element on the page stick on the screen as you scroll
//       It will only set the 'top' and 'position' of your element, you
//       might need to adjust the width in some cases.

(function($) {
	var defaults = {
			topSpacing: 0,
			bottomSpacing: 0,
			className: 'is-sticky',
			wrapperClassName: 'sticky-wrapper',
			center: false,
			getWidthFrom: ''
		},
		$window = $(window),
		$document = $(document),
		sticked = [],
		windowHeight = $window.height(),
		scroller = function() {
			var scrollTop = $window.scrollTop(),
				documentHeight = $document.height(),
				dwh = documentHeight - windowHeight,
				extra = (scrollTop > dwh) ? dwh - scrollTop : 0;

			for (var i = 0; i < sticked.length; i++) {
				var s = sticked[i],
					elementTop = s.stickyWrapper.offset().top,
					etse = elementTop - s.topSpacing - extra;

				if (scrollTop <= etse) {
					if (s.currentTop !== null) {
						s.stickyElement
							.css('position', '')
							.css('top', '');
						s.stickyElement.parent().removeClass(s.className);
						s.currentTop = null;
					}
				}
				else {
					var newTop = documentHeight - s.stickyElement.outerHeight()
						- s.topSpacing - s.bottomSpacing - scrollTop - extra;
					if (newTop < 0) {
						newTop = newTop + s.topSpacing;
					} else {
						newTop = s.topSpacing;
					}
					if (s.currentTop != newTop) {
						s.stickyElement
							.css('position', 'fixed')
							.css('top', newTop);

						if (typeof s.getWidthFrom !== 'undefined') {
							s.stickyElement.css('width', $(s.getWidthFrom).width());
						}

						s.stickyElement.parent().addClass(s.className);
						s.currentTop = newTop;
					}
				}
			}
		},
		resizer = function() {
			windowHeight = $window.height();
		},
		methods = {
			init: function(options) {
				var o = $.extend({}, defaults, options);
				return this.each(function() {
					var stickyElement = $(this);

					var stickyId = stickyElement.attr('id');
					var wrapperId = stickyId ? stickyId + '-' + defaults.wrapperClassName : defaults.wrapperClassName 
					var wrapper = $('<div></div>')
						.attr('id', stickyId + '-sticky-wrapper')
						.addClass(o.wrapperClassName);
					stickyElement.wrapAll(wrapper);

					if (o.center) {
						stickyElement.parent().css({width:stickyElement.outerWidth(),marginLeft:"auto",marginRight:"auto"});
					}

					if (stickyElement.css("float") == "right") {
						stickyElement.css({"float":"none"}).parent().css({"float":"right"});
					}

					var stickyWrapper = stickyElement.parent();
					stickyWrapper.css('height', stickyElement.outerHeight());
					sticked.push({
						topSpacing: o.topSpacing,
						bottomSpacing: o.bottomSpacing,
						stickyElement: stickyElement,
						currentTop: null,
						stickyWrapper: stickyWrapper,
						className: o.className,
						getWidthFrom: o.getWidthFrom
					});
				});
			},
			update: scroller,
			unstick: function(options) {
				return this.each(function() {
					var unstickyElement = $(this);

					var removeIdx = -1;
					for (var i = 0; i < sticked.length; i++) 
					{
						if (sticked[i].stickyElement.get(0) == unstickyElement.get(0))
						{
								removeIdx = i;
						}
					}
					if(removeIdx != -1)
					{
						sticked.splice(removeIdx,1);
						unstickyElement.unwrap();
						unstickyElement.removeAttr('style');
					}
				});
			}
		};

	// should be more efficient than using $window.scroll(scroller) and $window.resize(resizer):
	if (window.addEventListener) {
		window.addEventListener('scroll', scroller, false);
		window.addEventListener('resize', resizer, false);
	} else if (window.attachEvent) {
		window.attachEvent('onscroll', scroller);
		window.attachEvent('onresize', resizer);
	}

	$.fn.sticky = function(method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.sticky');
		}
	};

	$.fn.unstick = function(method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method ) {
			return methods.unstick.apply( this, arguments );
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.sticky');
		}

	};
	$(function() {
		setTimeout(scroller, 0);
	});
})(jQuery);