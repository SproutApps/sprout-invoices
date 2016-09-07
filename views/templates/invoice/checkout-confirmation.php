<?php

// Since 10.3+ the confirmation template has been removed in favor od duplicating
// the default template. This limits confusion with the templates. However, you can always create a custom template below...

do_action( 'pre_si_invoice_paid_view' );
wp_redirect( remove_query_arg( array( 'nonce', 'invoice_payment' ) ) );
exit();
