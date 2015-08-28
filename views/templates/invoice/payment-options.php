<?php 
	$payment_string = ( si_has_invoice_deposit() ) ? si__( 'Pay Deposit' ) : si__( 'Pay Invoice' );
	 ?>
<?php if ( count( $payment_options ) === 0 ) : ?>
	<!-- no payment options -->
<?php elseif ( count( $payment_options ) === 1 ) : ?>
	<?php foreach ( $payment_options as $slug => $options ) : ?>
		<?php if ( isset( $options['purchase_button_callback'] ) ): ?>
			<?php call_user_func_array( $options['purchase_button_callback'], array( get_the_ID() ) ) ?>
		<?php else: ?>
			<a href="<?php si_payment_link( get_the_ID(), $slug ) ?>" href="<?php si_payment_link( get_the_ID(), $slug ) ?>" data-slug="<?php esc_attr_e( $slug ) ?>" data-id="<?php the_ID() ?>" data-nonce="<?php echo wp_create_nonce( SI_Controller::NONCE ) ?>" class="button primary_button payment_option <?php if ( si_is_cc_processor( $slug ) ) { echo 'cc_processor'; } ?> <?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $payment_string ); ?></a>
		<?php endif ?>
	<?php endforeach ?>
<?php else : ?>
	<a href="#pay" class="button primary_button purchase_button" data-id="<?php the_ID() ?>" data-nonce="<?php echo wp_create_nonce( SI_Controller::NONCE ) ?>" data-dropdown="#payment_selection"><?php echo esc_html( $payment_string ); ?></a>
	<div id="payment_selection" class="dropdown dropdown-tip dropdown-anchor-right dropdown-relative">
		<ul class="dropdown-menu">
			<?php foreach ( $payment_options as $slug => $options ) : ?>
				<li id="<?php esc_attr_e( $slug ) ?>" class="payment_option">
					<?php if ( isset( $options['purchase_button_callback'] ) ): ?>
						<?php call_user_func_array( $options['purchase_button_callback'], array( get_the_ID() ) ) ?>
					<?php else: ?>
						<a href="<?php si_payment_link( get_the_ID(), $slug ) ?>" data-slug="<?php esc_attr_e( $slug ) ?>" data-id="<?php the_ID() ?>" data-nonce="<?php echo wp_create_nonce( SI_Controller::NONCE ) ?>" class="payment_option <?php if ( si_is_cc_processor( $slug ) ) { echo 'cc_processor'; } ?> <?php echo esc_attr( $slug ) ?>">
							<?php if ( isset( $options['icons'] ) ) : ?>
								<?php foreach ( $options['icons'] as $path ) : ?>
									<img src="<?php si_esc_e( $path ) ?>" alt="<?php si_esc_e( $options['label'] ) ?>" height="48" />
								<?php endforeach ?>
							<?php else : ?>
								<span class="process_label"><?php si_esc_e( $options['label'] ) ?></span>
							<?php endif ?>
						</a>
					<?php endif ?>
				</li>
			<?php endforeach ?>
		</ul>
	</div>
<?php endif ?>