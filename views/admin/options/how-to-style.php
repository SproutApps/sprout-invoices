<h1><?php _e( 'Logo & Color Styling', 'sprout-invoices' ) ?></h1>
<p><?php _e( 'Styling for Invoice and Estimate templates is done via the WordPress customizer.', 'sprout-invoices' ) ?></p>
<img src="<?php echo SI_URL ?>/resources/admin/img/customizer-how-to.gif" class="si_customizer_how_to">

<p><?php _e( 'You can access the customizer from any invoice or estimate but to make things easier below are some helpful links.', 'sprout-invoices' ) ?></p>

<label class="si_input_label"><?php _e( 'Invoices', 'sprout-invoices' ) ?></label>
<?php if ( $invoice_id ) :  ?>
	<?php $invoice_url = esc_url_raw( add_query_arg( array( 'url' => urlencode( get_permalink( $invoice_id ) ) ), admin_url( 'customize.php' ) ) ); ?>
	<p><?php printf( __( 'Start customizing your invoices <a href="%s">here</a>.', 'sprout-invoices' ), $invoice_url )  ?></p>
<?php else : ?>
	<p><?php printf( __( 'Before you start you will need to <a href="%s">create an invoice</a>.', 'sprout-invoices' ), admin_url( 'post-new.php?post_type=sa_invoice' ) )  ?></p>
<?php endif ?>

<label class="si_input_label"><?php _e( 'Estimates', 'sprout-invoices' ) ?></label>
<?php if ( $estimate_id ) :  ?>
	<?php $estimate_url = esc_url_raw( add_query_arg( array( 'url' => urlencode( get_permalink( $estimate_id ) ) ), admin_url( 'customize.php' ) ) ); ?>
	<p><?php printf( __( 'Start customizing your estimates <a href="%s">here</a>.', 'sprout-invoices' ), $estimate_url )  ?></p>
<?php else : ?>
	<p><?php printf( __( 'Before you start you will need to <a href="%s">create an estimate</a>.', 'sprout-invoices' ), admin_url( 'post-new.php?post_type=sa_estimate' ) )  ?></p>
<?php endif ?>

<label class="si_input_label"><?php _e( 'Advanced', 'sprout-invoices' ) ?></label>
<p><?php printf( __( 'Customizing templates for estimates, invoices, or any other front-end generated content from Sprout Invoices is easy. Review the <a href="%s">customization documentation</a> on how to add custom CSS, overriding templates within your (child) theme, and adding custom templates.', 'sprout-invoices' ), 'https://docs.sproutapps.co/article/38-customizing-templates' )  ?></p>
