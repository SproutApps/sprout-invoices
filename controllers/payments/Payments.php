<?php

/**
 * Payments Controller
 *
 * @package Sprout_Invoice
 * @subpackage Payments
 */
class SI_Payments extends SI_Controller {
	const SETTINGS_PAGE = 'invoice_payments';
	const PAYMENT_QV = 'pay_invoice';

	public static function get_admin_page( $prefixed = TRUE ) {
		return ( $prefixed ) ? self::TEXT_DOMAIN . '/' . self::SETTINGS_PAGE : self::SETTINGS_PAGE ;
	}

	public static function init() {
		self::register_settings();

		// Help Sections
		add_action( 'in_admin_header', array( get_class(), 'help_sections' ) );

		add_filter( 'views_sprout-invoices_page_sprout-invoices/payment_records', array( __CLASS__, 'modify_views' ) );

		add_action( 'wp_ajax_si_void_payment',  array( get_class(), 'maybe_void_payment' ), 10, 0 );

		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );
	}

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		
		// Option page
		$args = array(
			'parent' => 'edit.php?post_type='.SI_Invoice::POST_TYPE,
			'slug' => self::SETTINGS_PAGE,
			'title' => self::__( 'Payments' ),
			'menu_title' => self::__( 'Payments' ),
			'weight' => 14,
			'reset' => FALSE,
			'callback' => array( __CLASS__, 'display_table' )
			);
		do_action( 'sprout_settings_page', $args );
	}

	public function modify_views( $views ) {
		$auth_class = ( isset( $_GET['post_status'] ) && $_GET['post_status'] == SI_Payment::STATUS_AUTHORIZED ) ? 'class="current"' : '';
		$views['authorized_payments'] = '<a href="'.add_query_arg( array( 'post_status' => SI_Payment::STATUS_AUTHORIZED ) ).'" '.$auth_class.'>'.self::__('Authorized/Temp').'</a>';


		$auth_class = ( isset( $_GET['post_status'] ) && $_GET['post_status'] == SI_Payment::STATUS_PARTIAL ) ? 'class="current"' : '';
		$views['partial_payments'] = '<a href="'.add_query_arg( array( 'post_status' => SI_Payment::STATUS_PARTIAL ) ).'" '.$auth_class.'>'.self::__('Partial').'</a>';

		$void_class = ( isset( $_GET['post_status'] ) && $_GET['post_status'] == SI_Payment::STATUS_VOID ) ? 'class="current"' : '';
		$views['voided_payments'] = '<a href="'.add_query_arg( array( 'post_status' => SI_Payment::STATUS_VOID ) ).'" '.$void_class.'>'.self::__('Voided').'</a>';

		$refund_class = ( isset( $_GET['post_status'] ) && $_GET['post_status'] == SI_Payment::STATUS_REFUND ) ? 'class="current"' : '';
		$views['refunded_payments'] = '<a href="'.add_query_arg( array( 'post_status' => SI_Payment::STATUS_REFUND ) ).'" '.$refund_class.'>'.self::__('Refunded').'</a>';
		return $views;
	}

	public static function maybe_void_payment() {
		if ( !isset( $_REQUEST['void_payment_nonce'] ) )
			wp_die( 'Forget something?' );

		$nonce = $_REQUEST['void_payment_nonce'];
		if ( !wp_verify_nonce( $nonce, SI_Controller::NONCE ) )
        	wp_die( 'Not going to fall for it!' );

        if ( current_user_can( 'delete_posts' ) ) {
			$payment_id = $_REQUEST['payment_id'];
			$data = ( isset( $_REQUEST['notes'] ) ) ? $_REQUEST['notes'] : '' ;
			self::void_payment( $payment_id, $data );
			do_action( 'si_payment_voided', $payment_id );
		}
	}

	/**
	 * Void a payment
	 * @param  integet  $payment_id   Payment ID
	 * @return
	 */
	public static function void_payment( $payment_id, $new_data = '' ) {
		// Mark as refunded and change the
		$payment = SI_Payment::get_instance( $payment_id );
		if ( !is_a( $payment, 'SI_Payment' ) )
				return;

		$payment->set_status( SI_Payment::STATUS_VOID );
		$payment->set_payment_method( self::__( 'Admin Void' ) );
		// Merge old data with new updated message
		$new_data = wp_parse_args( $payment->get_data(), array( 'void_notes' => $new_data, 'updated' => sprintf( self::__( 'Voided by User #%s on %s' ), get_current_user_id(), date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ) ) ) ) );
		$payment->set_data( $new_data );

		add_action( 'si_void_payment', $payment_id, $new_data );
	}

	public static function display_table() {
		add_thickbox();
		//Create an instance of our package class...
		$wp_list_table = new SI_Payments_Table();
		//Fetch, prepare, sort, and filter our data...
		$wp_list_table->prepare_items();
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				jQuery(".si_void_payment").on('click', function(event) {
					event.preventDefault();
					var $void_button = $( this ),
					void_payment_id = $void_button.attr( 'ref' ),
					notes_form = $( '#transaction_data_' + void_payment_id ).val();
					$void_button.html("<?php si_e('Working...') ?>");
					$.post( ajaxurl, { action: 'si_void_payment', payment_id: void_payment_id, notes: notes_form, void_payment_nonce: '<?php echo wp_create_nonce( SI_Controller::NONCE ) ?>' },
						function( data ) {
							self.parent.tb_remove();
							$('#void_link_'+void_payment_id).closest('tr').fadeOut('slow');
						}
					);
				});
				jQuery(".si_attempt_capture").on('click', function(event) {
					event.preventDefault();
					if( confirm( '<?php si_e( "Are you sure? This will force a capture attempt on this payment." ) ?>' ) ) {
						var $capture_link = $( this ),
						capture_payment_id = $capture_link.attr( 'ref' );
						$capture_link.html('<?php si_e("Working...") ?>');
						$.post( ajaxurl, { action: 'si_manually_capture_payment', payment_id: capture_payment_id, capture_payment_nonce: '<?php echo wp_create_nonce( SI_Payment_Processors::AJAX_NONCE ) ?>' },
							function( data ) {
								window.location = window.location.pathname + "?post_type=sa_invoice&page=sprout-apps/invoice_payments&s=" + escape( capture_payment_id );
							}
						);
					}
				});
				jQuery(".si_mark_complete").on('click', function(event) {
					event.preventDefault();
					if( confirm( '<?php si_e( "Are you sure? This will mark the payment as complete." ) ?>' ) ) {
						var $complete_link = $( this ),
						complete_payment_id = $complete_link.attr( 'ref' );
						$complete_link.html('<?php si_e("Working...") ?>');
						$.post( ajaxurl, { action: 'si_mark_payment_complete', payment_id: complete_payment_id, complete_payment_nonce: '<?php echo wp_create_nonce( SI_Payment_Processors::AJAX_NONCE ) ?>' },
							function( data ) {
								window.location = window.location.pathname + "?post_type=sa_invoice&page=sprout-apps/invoice_payments&s=" + escape( complete_payment_id );
							}
						);
					}
				});
			});
		</script>
		<div class="wrap">
			
			<h2>
				<?php si_e('Invoice Payments') ?>
			</h2>

			<?php $wp_list_table->views() ?>
			<form id="payments-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $wp_list_table->search_box( self::__( 'Search' ), 'payment_id' ); ?>
				<?php $wp_list_table->display() ?>
			</form>
		</div>
		<?php
	}

	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'mng_payments',
			'title' => self::__( 'Payments' ),
			'href' => admin_url( 'edit.php?post_type='.SI_Invoice::POST_TYPE.'&page=sprout-apps/invoice_payments' ),
			'weight' => 0,
		);
		return $items;
	}

	////////////////
	// Admin Help //
	////////////////


	public static function help_sections() {
		// get screen and add sections.
		$screen = get_current_screen();
		if ( $screen->base == 'sa_invoice_page_sprout-apps/invoice_payments' ) {
			$screen->add_help_tab( array(
					'id' => 'about-payments',
					'title' => self::__( 'About Payments' ),
					'content' => sprintf( '<p>%s</p><p>%s</p>', self::__('Payment statuses include:'), self::__('<b>Pending</b> - the payment could be waiting for admin approval or waiting for the payment processor.<br/><b>Authorized</b> – a payment status set for signifying that the payment was authorized by the processor and a capture of the payment will be attempted later.<br/><b>Void</b> - payment was voided by the admin or declined by the payment processor after it was authorized or pending.') ),
				) );

			$screen->add_help_tab( array(
					'id' => 'mng-payments',
					'title' => self::__( 'Managing Payments' ),
					'content' => sprintf( '<p>%s</p><p>%s</p><p>%s</p><p>%s</p><p>%s</p>', self::__('Hovering over a payment brings up multiple links and options:'), self::__('<b>Void Payment</b> - Allows you to void a payment and add a note that will be added to the Transaction Data.'), self::__('<b>Transaction Data</b> – Used to troubleshoot a payment, this is the raw data stored by a payment processor.'), self::__('<b>Invoice and Client</b> – A link to the associated invoice and client edit pages.'), self::__('The payment totals are current and are not at the moment of the payment. The payment type is shown under the Data column.') ),
				) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', self::__('For more information:') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/payments/', self::__('Documentation') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', self::__('Support') )
			);
		}
	}
}