<?php

function si_payment_options_view() {
	SI_Controller::load_view( 'invoice/payment-options', array(), true );
}


function si_basic_theme_inject_css() {
	$context = ( SI_Invoice::is_invoice_query() ) ? 'inv' : 'est' ;

	$primary_color = SI_Customizer::sanitize_hex_color( get_theme_mod( 'si_basic_'.$context.'_primary_color' ) );
	$primary_text_color = SI_Customizer::sanitize_hex_color( get_theme_mod( 'si_basic_'.$context.'_secondary_color' ) );

	$button_color = SI_Customizer::sanitize_hex_color( get_theme_mod( 'si_basic_'.$context.'_paybar_color' ) );
	$button_text_color = SI_Customizer::sanitize_hex_color( get_theme_mod( 'si_basic_'.$context.'_text_color' ) );

	$top = ( (bool) get_theme_mod( 'si_basic_paybar_top' ) ) ? true : false ;
	?>
		<!-- Debut customizer CSS -->
		<style>
		<?php if ( $primary_color ) :  ?>
			#items .items .item .column h3,
			#totals .items #line_balance.item
			#header .inner .current_status span,
			#intro .inner .column #total_due.invoice_info,
			#totals .items #line_balance.item,
			#header .inner .current_status span,
			#intro .inner .column #total_due.invoice_info {
				background-color: <?php echo esc_attr( $primary_color ); ?>;
			}

		<?php endif ?>
		<?php if ( $primary_text_color ) :  ?>
			#items .items .item .column h3,
			#totals .items #line_balance.item
			#header .inner .current_status span,
			#intro .inner .column #total_due.invoice_info,
			#totals .items #line_balance.item,
			#header .inner .current_status span,
			#intro .inner .column #total_due.invoice_info {
				color: <?php echo esc_attr( $primary_text_color ); ?>;
			}
		<?php endif ?>
		<?php if ( $button_color ) :  ?>
			.history article .posted,
			#paybar .inner .button,
			#header .sa-message {
				background-color: <?php echo esc_attr( $button_color ); ?>;
			}
			.history article .posted:after {
				border-color: transparent transparent transparent #67a4e3;
			}
			body a {
				color: <?php echo esc_attr( $button_color ); ?>;
			}

		<?php endif ?>
		<?php if ( $button_text_color ) :  ?>
			.history article .posted,
			#paybar .inner .button,
			#header .sa-message {
				color: <?php echo esc_attr( $button_text_color ); ?>;
			}
		<?php endif ?>
		<?php if ( $top ) : ?>
			#paybar {
				bottom: initial !important;
				top: 0;
	    	}
		   .masthead-fixed #paybar {
		     top: 30px;
		   }
	    	#header .messages {
				min-height: 150px;
			}
		<?php endif ?>
		</style>
		<?php
}
add_action( 'si_head', 'si_basic_theme_inject_css' );

function _si_basic_theme_print_to_pdf_button( $button = '' ) {
	$icon = '<svg width="40" height="40" viewBox="0 0 40 40"><g transform="scale(0.03125 0.03125)"><path d="M842.012 589.48c-13.648-13.446-43.914-20.566-89.972-21.172-31.178-0.344-68.702 2.402-108.17 7.928-17.674-10.198-35.892-21.294-50.188-34.658-38.462-35.916-70.568-85.772-90.576-140.594 1.304-5.12 2.414-9.62 3.448-14.212 0 0 21.666-123.060 15.932-164.666-0.792-5.706-1.276-7.362-2.808-11.796l-1.882-4.834c-5.894-13.592-17.448-27.994-35.564-27.208l-10.916-0.344c-20.202 0-36.664 10.332-40.986 25.774-13.138 48.434 0.418 120.892 24.98 214.738l-6.288 15.286c-17.588 42.876-39.63 86.060-59.078 124.158l-2.528 4.954c-20.46 40.040-39.026 74.028-55.856 102.822l-17.376 9.188c-1.264 0.668-31.044 16.418-38.028 20.644-59.256 35.38-98.524 75.542-105.038 107.416-2.072 10.17-0.53 23.186 10.014 29.212l16.806 8.458c7.292 3.652 14.978 5.502 22.854 5.502 42.206 0 91.202-52.572 158.698-170.366 77.93-25.37 166.652-46.458 244.412-58.090 59.258 33.368 132.142 56.544 178.142 56.544 8.168 0 15.212-0.78 20.932-2.294 8.822-2.336 16.258-7.368 20.792-14.194 8.926-13.432 10.734-31.932 8.312-50.876-0.72-5.622-5.21-12.574-10.068-17.32zM211.646 814.048c7.698-21.042 38.16-62.644 83.206-99.556 2.832-2.296 9.808-8.832 16.194-14.902-47.104 75.124-78.648 105.066-99.4 114.458zM478.434 199.686c13.566 0 21.284 34.194 21.924 66.254s-6.858 54.56-16.158 71.208c-7.702-24.648-11.426-63.5-11.426-88.904 0 0-0.566-48.558 5.66-48.558v0zM398.852 637.494c9.45-16.916 19.282-34.756 29.33-53.678 24.492-46.316 39.958-82.556 51.478-112.346 22.91 41.684 51.444 77.12 84.984 105.512 4.186 3.542 8.62 7.102 13.276 10.65-68.21 13.496-127.164 29.91-179.068 49.862v0zM828.902 633.652c-4.152 2.598-16.052 4.1-23.708 4.1-24.708 0-55.272-11.294-98.126-29.666 16.468-1.218 31.562-1.838 45.102-1.838 24.782 0 32.12-0.108 56.35 6.072 24.228 6.18 24.538 18.734 20.382 21.332v0z"></path><path d="M917.806 229.076c-22.21-30.292-53.174-65.7-87.178-99.704s-69.412-64.964-99.704-87.178c-51.574-37.82-76.592-42.194-90.924-42.194h-496c-44.112 0-80 35.888-80 80v864c0 44.112 35.886 80 80 80h736c44.112 0 80-35.888 80-80v-624c0-14.332-4.372-39.35-42.194-90.924v0zM785.374 174.626c30.7 30.7 54.8 58.398 72.58 81.374h-153.954v-153.946c22.982 17.78 50.678 41.878 81.374 72.572v0zM896 944c0 8.672-7.328 16-16 16h-736c-8.672 0-16-7.328-16-16v-864c0-8.672 7.328-16 16-16 0 0 495.956-0.002 496 0v224c0 17.672 14.324 32 32 32h224v624z"></path></g></svg>';
	$pdf_url = apply_filters( 'si_pdf_url', add_query_arg( array( 'pdf' => 1 ) ) );
	$button = sprintf( '<a href="%1$s" id="print_to_pdf_button" class="print_button pdf_button" rel="nofollow">%2$s</a>', $pdf_url, $icon );
	return $button;
}
add_filter( 'si_print_to_pdf_button', '_si_basic_theme_print_to_pdf_button' );

function _si_signature_required_button( $button = '', $doc_id = 0, $url = '' ) {
	$signed = false;
	if ( class_exists( 'ApproveMe_Controller' ) ) {
		$signed = ApproveMe_Controller::is_doc_agreement_signed( $doc_id );
	} elseif ( class_exists( 'eSignature_Controller' ) ) {
		$signed = eSignature_Controller::doc_needs_sig( $doc_id );
	}

	$new_button = '';
	$message = __( 'Signature Required', 'sprout-invoices' );
	if ( $signed ) {
		$message = __( 'Signed', 'sprout-invoices' );
	} else {
		$new_button .= '<style type="text/css">
				#paybar .inner .button.open,
				#paybar .button.accept_estimate.status_change {
				    display: none;
				}
				#paybar .inner .button.status_change[data-status-change="decline"] {
				    display: inline-block !important;
				}
			</style>';
	}

	$new_button .= '<a id="sign_doc" class="button signature_button" href="'.esc_url( $url ).'">'. $message .'</a>';

	return $new_button;
}
add_filter( 'si_signature_required_button', '_si_signature_required_button', 1, 3 );

