/**
 * UI enhancements for SA Notification editor
 */

jQuery(document).ready(function($){

	// Apply classes to list items for highlighting
	$('#toplevel_page_sprout-invoices').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
	$('#toplevel_page_sprout-invoices a[href="admin.php?page=sprout-apps"]').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
	$('#toplevel_page_sprout-invoices li a[href*="admin.php?page=sprout-invoices-notifications"]').addClass('current').parent().addClass('current');

	// Remove add new button
	$('h2 a[href*="post-new.php?post_type=sa_notification"]').remove();

	function show_hide_notification_type_descriptions( type ) {
		$('#normal-sortables .postbox').each(function(){
			var $this = $(this);
			var id = $this.attr('id');
			// Only mess with shortcode meta boxes
			if ( id.match(/si_notification_shortcodes_/) ) {
				// If it is the selected notification type, show it. Otherwise, hide it.
				if ( id.match( new RegExp('^si_notification_shortcodes_' + type + '$') ) ) {
					$this.show();
					$('.submitdelete').hide();
				} else {
					$this.hide();
				}
			}
		});

		// Show and hide the appropriate notification type descriptions
		$('.notification_type_description').hide();
		$('#notification_type_description_' + type).show();
	}
	$('#notification_type').change(function(){
		show_hide_notification_type_descriptions( $(this).val() );
	});
	show_hide_notification_type_descriptions($('#notification_type').val());
});


