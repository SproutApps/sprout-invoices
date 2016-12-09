<div id="destroy_everything">
	
	<span class="button" id="destroy_everything"><?php _e( 'Kill all Sprout Invoices records!', 'sprout-invoices' ) ?></span>

</div><!-- #minor-publishing -->

<script type="text/javascript">
	jQuery(function($) {
		
		jQuery("#destroy_everything").on('click', function(event) {
			if( confirm( '<?php _e( 'Are you sure? This will delete everything that Sprout Invoices ever created.', 'sprout-invoices' ) ?>' ) ) {
				si_destroy_everything( 0 );
				$( this ).html('<p><img id="destroy_everything" src="http://i.giphy.com/8ltnTrJ3krIv6.gif" width="200" height="auto" /></p>');
			}
		});

		function si_destroy_everything ( count ) {
			var $button = jQuery("#destroy_everything");
			if ( count > 50 ) { // some sanity in case things get outta hand.
				console.log( 'heldback' );
				return;
			}
			$button.after(si_js_object.inline_spinner);
			$.post( ajaxurl, { action: '<?php echo $action ?>', nonce: '<?php echo $nonce ?>' },
				function( response ) {
					if ( response.error ) {
						$button.after( response.message );
					}
					else {
						$('.spinner').hide();
						$button.after( response.data.message );
						if ( response.data.runagain !== false ) {
							count++;
							si_destroy_everything( count );
							return;
						}	
					}
				}
			);
		}
	
	});
</script>
