<section class="row" id="paybar">
	<div class="inner">
		<?php do_action( 'si_default_theme_inner_paybar' ) ?>
		
		<?php
			$time_left = si_get_invoice_due_date() - current_time( 'timestamp' );
			$days_left = round( (($time_left / 24) / 60) / 60 );
				?>
		<?php if ( $time_left > 0 ) :  ?>

			<?php if ( 1 === $days_left ) :  ?>
				
				<?php printf( 'Balance of <strong>%1$s</strong> is Due', sa_get_formatted_money( si_get_invoice_balance() ), $days_left ); ?>

			<?php else : ?>

				<?php if ( si_has_invoice_deposit() ) : ?>
					<?php printf( 'Balance of <strong>%2$s</strong> Due in <strong>%3$s Days</strong> & Deposit of <strong>%1$s</strong> Due <strong>Now</strong>', sa_get_formatted_money( si_get_invoice_deposit() ), sa_get_formatted_money( si_get_invoice_balance() ), $days_left ); ?>
				<?php else : ?>
					<?php printf( 'Balance of <strong>%1$s</strong> Due in <strong>%2$s Days</strong>', sa_get_formatted_money( si_get_invoice_balance() ), $days_left ); ?>
				<?php endif; ?>

			<?php endif ?>

		<?php else : ?>
			
			<?php printf( 'Balance of <strong>%1$s</strong> is <strong>Overdue</strong>', sa_get_formatted_money( si_get_invoice_balance() ) ); ?>

		<?php endif ?>

		<?php do_action( 'si_default_theme_pre_payment_button' ) ?>
		
		<?php do_action( 'si_pdf_button' ) ?>

		<?php do_action( 'si_signature_button' ) ?>

		<?php if ( si_has_invoice_deposit() ) : ?>
			<a class="open button" href="#payment"><?php _e( 'Make a <strong>Deposit Payment</strong>', 'sprout-invoices' ) ?></a> 
		<?php else : ?>
			<a class="open button" href="#payment"><?php _e( 'Make a <strong>Payment</strong>', 'sprout-invoices' ) ?></a> 
		<?php endif; ?>
		
		<?php do_action( 'si_default_theme_payment_button' ) ?>
	</div>
</section>

<section class="panel closed" id="payment">
	<a class="close" href="#payment">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<path d="M405 136.798L375.202 107 256 226.202 136.798 107 107 136.798 226.202 256 107 375.202 136.798 405 256 285.798 375.202 405 405 375.202 285.798 256z"/>
		</svg>
	</a>
	
	<div class="inner">
		
		<h2><?php _e( 'Make a Payment', 'sprout-invoices' ) ?></h2>
		
		<?php $payment_options = si_payment_options(); ?>
		
		<?php do_action( 'si_default_theme_pre_payment_options' ) ?>

		<?php if ( count( $payment_options ) === 0 ) : ?>
			
			<p><?php _e( 'Oh no! So sorry. There are no payment options available for you to make a payment. Please contact let me know so I can figure out why.', 'sprout-invoices' ) ?></p>

			<?php do_action( 'si_default_theme_no_payment_options_desc' ) ?>

		<?php else : ?>

			<?php if ( count( $payment_options ) > 1 ) : ?>
				<p><?php _e( 'Please select your payment type and then enter your payment information below to pay this invoice. A receipt for your records will be sent to you. Thank you very much!', 'sprout-invoices' ) ?></p>
			<?php else : ?>
				<p><?php _e( 'Please enter your payment information below to pay this invoice. A receipt for your records will be sent to you. Thank you very much!', 'sprout-invoices' ) ?></p>
			<?php endif; ?>

			<?php do_action( 'si_default_theme_payment_options_desc' ) ?>

			<div class="row toggles">
				<?php foreach ( $payment_options as $slug => $options ) : ?>
					<?php if ( isset( $options['purchase_button_callback'] ) ) : ?>
						<?php call_user_func_array( $options['purchase_button_callback'], array( get_the_ID() ) ) ?>
					<?php else : ?>
						<a href="<?php si_payment_link( get_the_ID(), $slug ) ?>" data-slug="<?php esc_attr_e( $slug ) ?>" data-id="<?php the_ID() ?>" data-nonce="<?php echo wp_create_nonce( SI_Controller::NONCE ) ?>" class="payment_option toggle <?php if ( si_is_cc_processor( $slug ) ) { echo 'cc_processor'; } ?> <?php echo esc_attr( $slug ) ?>">
							<span class="process_label"><?php esc_attr_e( $options['label'] , 'sprout-invoices' ) ?></span>
						</a>
					<?php endif ?>
					<?php do_action( 'si_default_theme_payment_option_desc', $slug ) ?>
				<?php endforeach ?>
			</div>
			<?php do_action( 'si_default_theme_payment_options' ) ?>
		<?php endif; ?>

		<div class="row paytypes">
			<?php do_action( 'si_payments_pane' ); ?>
		</div>

		<?php do_action( 'si_default_theme_pre_payment_panes' ) ?>
		

		<?php if ( count( $payment_options ) === 1 ) : ?>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready( function($) {
					$(".toggles > a").trigger( "click" );
				});
				//]]>
			</script>
		<?php endif; ?>
	</div>
</section>
