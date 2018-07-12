/**
 * The main Vue instance for our plugin settings page
 * @link https://vuejs.org/v2/guide/instance.html
 */
new Vue( {

	// DOM selector for our app's main wrapper element
	el: '#si_settings',

	// Data that will be proxied by Vue.js to provide reactivity to our template
	data: {

		isSaving: false,
		isLoading: false,
		viewNotifications: false,
		message: '',
		addonAdminToggle: 'start',
		vm: SI_Settings.options,

	},
	mounted: function(){
		// var up vue for callback
		var self = this;
		// after vue is completed
		this.$nextTick(function () {
			jQuery(function() {
				if ( si_js_object.redactor ) {
					// setup redactor
					jQuery('.si_wysiwyg').redactor({
						callbacks: {
							// after mouse leaves
					        blur: function(e) {
					        	var name = this.source.getName(),
									html = this.source.getCode();
								// prop the vue data
								self.vm[name] = html;
					        }
					    }
					
					});
				}
			});
		})
	},
	// Methods that can be invoked from within our template
	methods: {


		toggleNotifications: function() {
			if ( true === this.viewNotifications ) {
				this.viewNotifications = false;
			}
			else {
				this.viewNotifications = true;
			}
		},

		makeTabActive: function( val ) {
			this.scrollTo( 'wpbody-content' );
			this.addonAdminToggle = val;
		},
		isActiveTab: function( val ) {
			return this.addonAdminToggle === val;
		},


		scrollTo: function( elId ) {
			var elmnt = document.getElementById( elId );
    		elmnt.scrollIntoView();
		},

		activateLicense: function( action ) {
			
			var response = '',
				$license_key = $('#si_license_key').val(),
				$license_message = $('#license_message');

			this.isSaving = true;

			this.vm['si_license_key'] = $license_key;

			jQuery.ajax( {

					url: ajaxurl,
					method: 'POST',
					data: { action: action, license: $license_key, security: si_js_object.security },

					// set the nonce in the request header
					beforeSend: function( request ) {
						request.setRequestHeader( 'X-WP-Nonce', SI_Settings.nonce );
					},

					// callback to run upon successful completion of our request
					success: ( data ) => {
						this.refreshProgress();
						if ( data.error ) {
							
							response = '<span class="inline_error_message">' + data.response + '</span>';

						}
						else { // success

							if ( 'si_deactivate_license' === action ) {
								jQuery('#deactivate_license').hide();
								jQuery('#activate_license').removeAttr('disabled').removeClass('si_muted');
							}
							else {
								jQuery('#activate_license').hide();	
							}

							response = '<span class="inline_success_message">' + data.response + '</span>';
						}

						// display message
						jQuery('#si_html_message').html(response);
					},

					// callback to run if our request caused an error
					error: ( data ) => this.message = data.responseText,

					// when our request is complete (successful or not), reset the state to indicate we are no longer saving
					complete: () => this.isSaving = false,
				});
		},

		refreshProgress: function( ) {
			

			jQuery( "#si_progress_track" ).fadeOut();

			jQuery.ajax( {

					url: ajaxurl,
					method: 'POST',
					data: { action: 'si_progress_view' },

					// set the nonce in the request header
					beforeSend: function( request ) {
						request.setRequestHeader( 'X-WP-Nonce', SI_Settings.nonce );
					},

					// callback to run upon successful completion of our request
					success: ( html ) => {
						jQuery( "#si_progress_track" ).replaceWith( html ).fadeIn();
					},

					// callback to run if our request caused an error
					error: ( data ) => this.message = data.responseText,

					// when our request is complete (successful or not), reset the state to indicate we are no longer saving
					complete: () => this.isLoading = false,
				});
		},

		// Save the options to the database
		activateCCPP: function( event ) {

			var processor = event.target.value,
				select = document.getElementById("si_cc_pp_select"),
				i;

			// set the state so that another save cannot happen while processing
			this.isSaving = true;

			// change all to false
			for (i = 0; i < select.length; i++) {
				this.vm[select.options[i].value] = false;
			}

			// handle checkboxes
			if ( event.target.type == 'checkbox' ) {
				// processor is the checkbox name
				processor = event.target.name;

				// unchecking sets value to 0
				if ( ! event.target.checked ) {
					processor = 'false';
				}
			}

			// prop the data
			this.vm[processor] = true;
			this.vm.si_cc_pp_select = processor;

			// Make a POST request to the REST API route that we registered in our PHP file
			jQuery.ajax( {

				url: SI_Settings.siteUrl + '/wp-json/si-settings/v1/manage-pp',
				method: 'POST',
				data: { 'activate': processor, 'update_cc': true },

				// set the nonce in the request header
				beforeSend: function( request ) {
					request.setRequestHeader( 'X-WP-Nonce', SI_Settings.nonce );
				},

				// callback to run upon successful completion of our request
				success: ( result ) => {
					this.refreshProgress();
				},

				// callback to run if our request caused an error
				error: ( data ) => this.message = data.responseText,

				// when our request is complete (successful or not), reset the state to indicate we are no longer saving
				complete: () => this.isSaving = false,
			});
			
		}, // end: saveOptions

		// Save the options to the database
		activatePP: function( event ) {

			var processor = event.target.name,
				action = { 'activate': processor };

			// deactivate if not checked
			if ( ! event.target.checked ) {
				action = { 'deactivate': processor };
			}

			// set the state so that another save cannot happen while processing
			this.isSaving = true;

			// Make a POST request to the REST API route that we registered in our PHP file
			jQuery.ajax( {

				url: SI_Settings.siteUrl + '/wp-json/si-settings/v1/manage-pp',
				method: 'POST',
				data: action,

				// set the nonce in the request header
				beforeSend: function( request ) {
					request.setRequestHeader( 'X-WP-Nonce', SI_Settings.nonce );
				},

				// callback to run upon successful completion of our request
				success: () => {
					this.refreshProgress();
				},

				// callback to run if our request caused an error
				error: ( data ) => this.message = data.responseText,

				// when our request is complete (successful or not), reset the state to indicate we are no longer saving
				complete: () => this.isSaving = false,
			});
			
		}, // end: saveOptions

		// Save the options to the database
		activateAddOn: function( addOn, event ) {

			var addOnEl = jQuery(event.target),
				action = { 'activate': addOn };

			// Don't enable an add-on that was already enabled.
			if ( ! event.target.checked ) {
				action = { 'deactivate': addOn };
			}

			// set the state so that another save cannot happen while processing
			this.isSaving = true;

			// Make a POST request to the REST API route that we registered in our PHP file
			jQuery.ajax( {

				url: SI_Settings.siteUrl + '/wp-json/si-settings/v1/manage-addon',
				method: 'POST',
				data: action,

				// set the nonce in the request header
				beforeSend: function( request ) {
					request.setRequestHeader( 'X-WP-Nonce', SI_Settings.nonce );
				},

				// callback to run upon successful completion of our request
				success: () => {
					this.refreshProgress();
				},

				// callback to run if our request caused an error
				error: ( data ) => this.message = data.responseText,

				// when our request is complete (successful or not), reset the state to indicate we are no longer saving
				complete: () => this.isSaving = false,
			});
			
		}, // end: saveOptions

		// Save the options to the database
		loadHTMLTemplates: function() {

			// set the state so that another save cannot happen while processing
			this.isLoading = true;


			if( confirm( 'Are you sure? This will delete any customized notifications and replace them with the default HTML templates.' ) ) {

				jQuery.ajax( {

					url: ajaxurl,
					method: 'POST',
					data: { action: 'si_load_html_templates' },

					// set the nonce in the request header
					beforeSend: function( request ) {
						request.setRequestHeader( 'X-WP-Nonce', SI_Settings.nonce );
					},

					// callback to run upon successful completion of our request
					success: () => {
						this.refreshProgress();
						this.message = 'Templates Loaded';
						setTimeout( () => this.message = '', 1000 );
					},

					// callback to run if our request caused an error
					error: ( data ) => this.message = data.responseText,

					// when our request is complete (successful or not), reset the state to indicate we are no longer saving
					complete: () => this.isLoading = false,
				});
			}
			
		}, // end: saveOptions

		// Save the options to the database
		saveOptions: function() {

			// set the state so that another save cannot happen while processing
			this.isSaving = true;

			// Make a POST request to the REST API route that we registered in our PHP file
			jQuery.ajax( {

				url: SI_Settings.siteUrl + '/wp-json/si-settings/v1/save',
				method: 'POST',
				data: this.vm,

				// set the nonce in the request header
				beforeSend: function( request ) {
					request.setRequestHeader( 'X-WP-Nonce', SI_Settings.nonce );
				},

				// callback to run upon successful completion of our request
				success: () => {
					this.refreshProgress();
					this.message = 'Options saved';
					setTimeout( () => this.message = '', 1000 );
				},

				// callback to run if our request caused an error
				error: ( data ) => this.message = data.responseText,

				// when our request is complete (successful or not), reset the state to indicate we are no longer saving
				complete: () => this.isSaving = false,
			});
			
		}, // end: saveOptions

	}, // end: methods

}); // end: Vue()


;( function( $, window, document, undefined )
{

	$("#destroy_everything").on('click', function(event) {
		if( confirm( si_js_object.destroy_confirm ) ) {
			si_destroy_everything( 0 );
			$( this ).html('<p><img id="destroy_everything" src="http://i.giphy.com/8ltnTrJ3krIv6.gif" width="200" height="auto" /></p>');
		}
	});

	function si_destroy_everything ( count ) {
		var $button = $("#destroy_everything");
		if ( count > 50 ) { // some sanity in case things get outta hand.
			console.log( 'heldback' );
			return;
		}
		$button.after(si_js_object.inline_spinner);
		$.post( ajaxurl, { action: si_js_object.destroy_action, nonce: si_js_object.destroy_nonce },
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
})( jQuery, window, document );

/**
 * jQuery for file input
 */
;( function( $, window, document, undefined )
{
	$( '.si_input_file' ).each( function()
	{
		var $input	 = $( this ),
			$label	 = $input.next( 'label' ),
			labelVal = $label.html();

		$input.on( 'change', function( e )
		{
			var fileName = '';

			if( this.files && this.files.length > 1 )
				fileName = ( this.getAttribute( 'data-multiple-caption' ) || '' ).replace( '{count}', this.files.length );
			else if( e.target.value )
				fileName = e.target.value.split( '\\' ).pop();

			if( fileName )
				$label.find( 'span' ).html( fileName );
			else
				$label.html( labelVal );
		});

		// Firefox bug fix
		$input
		.on( 'focus', function(){ $input.addClass( 'has-focus' ); })
		.on( 'blur', function(){ $input.removeClass( 'has-focus' ); });
	});
})( jQuery, window, document );