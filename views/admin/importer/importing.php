<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php
	$page = ( ! isset( $_GET['tab'] ) ) ? $page : self::APP_DOMAIN.'/'.$_GET['tab'] ; ?>

<div id="<?php echo esc_attr( $page ); ?>" class="wrap">

	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php do_action( 'si_settings_page_sub_heading_'.$page ); ?>
	</div>

	<?php if ( apply_filters( 'si_show_importer_settings', '__return_true' ) ) : ?>
		<form method="post" class="si_settings_form" enctype="multipart/form-data">
			<h3><?php _e( 'Import Clients, Users, Estimates, Invoices and Payments', 'sprout-invoices' ) ?></h3>
			<?php settings_fields( $page ); ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php _e( 'Select Import Source', 'sprout-invoices' ) ?></th>
						<td>
							<select name="importer">
								<?php foreach ( $importers as $key => $name ) : ?>
									<?php
										$current = ( isset( $_POST['importer'] ) && $_POST['importer'] != '' ) ? $_POST['importer'] : '' ;
											?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $current ) ?>><?php echo esc_html( $name ) ?></option>
								<?php endforeach ?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<table class="form-table">
				<?php do_settings_fields( $page, 'default' ); ?> 
			</table>
			<?php do_settings_sections( $page ); ?>
			<?php submit_button( __( 'Start Import', 'sprout-invoices' ) ); ?>
		</form>
	<?php else : ?>
		<script type="text/javascript">
			jQuery(function($) {
				
				// Authenticate and start the process
				start_import_ajax_method('authentication');

				function start_import_ajax_method ( $method ) {
					var $importer = '<?php echo esc_js( $_POST['importer'] ) ?>',
						$nonce = si_js_object.security;
					$.post( ajaxurl, { action: 'si_import', importer: $importer, method: $method, security: $nonce },
						function( data ) {
							if ( data.error ) {
								$('#import_error p').html( data.message );
								$('#import_error').removeClass('cloak');
								$('#authentication_import_progress').css( { width: '100%' } ).addClass( 'progress-bar-danger' );
								$('#authentication_import_progress').attr( 'aria-valuenow', 100 );
							}
							else {
								$.each( data, function( method, response ) {
									// update the informational rows
									$('#'+method+'_import_progress').css( { width: response.progress+'%' } );
									$('#'+method+'_import_progress').attr( 'aria-valuenow', response.progress );
									if ( response.progress >= 100 ) {
										$('#'+method+'_import_progress').removeClass( 'active progress-bar-striped' ).addClass( 'progress-bar-success' );
									};
									if ( response.message ) {
										$('#'+method+'_import_information').text( response.message );
									};
									// continue the process until complete
									if ( response.next_step ) {
										if ( response.next_step === 'complete' ) {
											$('#authentication_import_information').text( si_js_object.done_string );
											$('#complete_import').removeClass('cloak');
											return true;
										};
										start_import_ajax_method( response.next_step );
									};
								});
								
							};
							return true;
						}
					);
				}
			
			});

		</script>

		<div id="import_error" class="error cloak">
			<p></p>
		</div>
		<div id="si_importer" class="wrap about-wrap">

			<p>
				<div id="authentication_import_information"><?php _e( 'Attempting to validate credentials...', 'sprout-invoices' ) ?></div>
				<div class="progress">
					<div id="authentication_import_progress" class="progress-bar progress-bar-striped active" style="overflow:hidden;"   role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">&nbsp;</div>
				</div>
			</p>

			<p>
				<div id="clients_import_information"><?php _e( 'No clients imported yet...', 'sprout-invoices' ) ?></div>
				<div class="progress">
					<div id="clients_import_progress" class="progress-bar progress-bar-striped active" style="overflow:hidden;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">&nbsp;</div>
				</div>
			</p>

			<p>
				<div id="contacts_import_information"><?php _e( 'No contacts imported yet...', 'sprout-invoices' ) ?></div>
				<div class="progress">
					<div id="contacts_import_progress" class="progress-bar progress-bar-striped active" style="overflow:hidden;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">&nbsp;</div>
				</div>
			</p>

			<p>
				<div id="estimates_import_information"><?php _e( 'No estimates imported yet...', 'sprout-invoices' ) ?></div>
				<div class="progress">
					<div id="estimates_import_progress" class="progress-bar progress-bar-striped active" style="overflow:hidden;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">&nbsp;</div>
				</div>
			</p>

			<p>
				<div id="invoices_import_information"><?php _e( 'No invoices imported yet...', 'sprout-invoices' ) ?></div>
				<div class="progress">
					<div id="invoices_import_progress" class="progress-bar progress-bar-striped active" style="overflow:hidden;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">&nbsp;</div>
				</div>
			</p>

			<p>
				<div id="payments_import_information"><?php _e( 'No payments imported yet...', 'sprout-invoices' ) ?></div>
				<div class="progress">
					<div id="payments_import_progress" class="progress-bar progress-bar-striped active" style="overflow:hidden;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">&nbsp;</div>
				</div>
			</p>

			<div id="complete_import" class="cloak"><?php printf( '<a href="%s">All Done!</a>', admin_url( 'edit.php?post_type=sa_invoice' ) ) ?></div>

			<?php do_action( 'si_import_progress', $page ) ?>

		</div>
	<?php endif ?>


	<?php do_action( 'si_settings_page', $page ) ?>
	<?php do_action( 'si_settings_page_'.$page, $page ) ?>
</div>
