<?php

function si_slate_theme_inject_css() {
	$inv_color = SI_Customizer::sanitize_hex_color( get_theme_mod( 'si_invoices_color' ) );
	$est_color = SI_Customizer::sanitize_hex_color( get_theme_mod( 'si_estimates_color' ) );
	?>
		<!-- Debut customizer CSS -->
		<style>
		#doc .doc_total,
		.button.primary_button {
			background-color: <?php echo esc_attr( $est_color ); ?>;
		}

		#invoice #doc .doc_total,
		#invoice .button.primary_button {
			background-color: <?php echo esc_attr( $inv_color ); ?>;
		}

		#invoice.paid #doc .doc_total,
		#invoice .button.deposit_paid {
			background-color: <?php echo esc_attr( $est_color ); ?>;
		}

		#line_total {
			color: <?php echo esc_attr( $est_color ); ?>;
		}

		#invoice #line_total {
			color: <?php echo esc_attr( $inv_color ); ?>;
		}
		</style>
		<?php
}
add_action( 'si_head', 'si_slate_theme_inject_css' );
