<?php

// Since 10.3+ the confirmation template has been removed in favor of duplicating
// the default template. This limits confusion with the templates. However, you can always create a custom template below...

do_action( 'pre_si_invoice_paid_view' );
$url = remove_query_arg( array( 'nonce', 'invoice_payment' ) );
wp_redirect( add_query_arg( array( 'payment_confirmed' => 'true' ), $url ) );
exit();
