<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php 
	$page = ( !isset( $_GET['tab'] ) ) ? $page : self::TEXT_DOMAIN.'/'.$_GET['tab'] ; ?>

<div id="<?php echo $page ?>" class="wrap">

	<?php screen_icon(); ?>
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php do_action( 'si_settings_page_sub_heading_'.$_GET['page'] ); ?>
	</div>

	<?php if ( apply_filters( 'si_show_importer_settings', '__return_true' ) ): ?>
		<form method="post" class="si_settings_form">
			<h3><?php self::_e('Import Clients, Users, Estimates, Invoices and Payments') ?></h3>
			<?php settings_fields( $page ); ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php self::_e('Select Import Source') ?></th>
						<td>
							<select name="importer">
								<?php foreach ( $importers as $key => $name ): ?>
									<?php 
										$current = ( isset( $_POST['importer'] ) && $_POST['importer'] != '' ) ? $_POST['importer'] : '' ;
										 ?>
									<option value="<?php echo $key ?>" <?php selected( $key, $current ) ?>><?php echo $name ?></option>
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
			<?php submit_button( self::__('Start Import') ); ?>
		</form>
	<?php else: ?>
		<div id="patience" class="updated cloak">
			<p><?php self::_e('Please be patient, this might take a while. If the import stops refresh the page; it\'s likely your server will time this process if you have a lot of records.') ?></p>
		</div>
		<div id="auth_patience" class="error cloak">
			<p><?php printf( self::__('Possible authentication error. <a href="%s">Go back</a> and review your settings.'), admin_url('admin.php?page=sprout-apps/settings&tab=import') )?></p>
		</div>
		<div id="si_importer" class="wrap about-wrap">

			<p>
				<div id="com_import_progress" style="width:100%;border:1px solid #ccc;"></div>
				<div id="com_import_information"><?php self::_e('Authorizing Harvest API Connection...') ?></div>
			</p>

			<p>
				<div id="clients_import_progress" style="width:100%;border:1px solid #ccc;"></div>
				<div id="clients_import_information"><?php self::_e('Importing Clients...') ?></div>
			</p>

			<p>
				<div id="contacts_import_progress" style="width:100%;border:1px solid #ccc;"></div>
				<div id="contacts_import_information"><?php self::_e('Importing Contacts...') ?></div>
			</p>

			<p>
				<div id="estimates_import_progress" style="width:100%;border:1px solid #ccc;"></div>
				<div id="estimates_import_information"><?php self::_e('Importing Estimates...') ?></div>
			</p>

			<p>
				<div id="invoices_import_progress" style="width:100%;border:1px solid #ccc;"></div>
				<div id="invoices_import_information"><?php self::_e('Importing Invoices...') ?></div>
			</p>

			<p>
				<div id="payments_import_progress" style="width:100%;border:1px solid #ccc;"></div>
				<div id="payments_import_information"><?php self::_e('Importing Payments...') ?></div>
			</p>

			<div id="complete_import" class="cloak"><?php printf( '<a href="%s">All Done!</a>', admin_url( 'edit.php?post_type=sa_invoice' ) ) ?></div>

			<?php do_action( 'si_import_progress', $page ) ?>

		</div>
	<?php endif ?>


	<?php do_action( 'si_settings_page', $page ) ?>
	<?php do_action( 'si_settings_page_'.$page, $page ) ?>
</div>
