<?php

/**
 *
 * These actions are fired for each checkout page.
 *
 * Payment page - 'si_checkout_action_'.SI_Checkouts::PAYMENT_PAGE
 * Review page - 'si_checkout_action_'.SI_Checkouts::REVIEW_PAGE
 * Confirmation page - 'si_checkout_action_'.SI_Checkouts::CONFIRMATION_PAGE
 *
 * Necessary methods:
 * get_instance -- duh
 * get_slug -- slug for the payment process
 * get_options -- used on the invoice payment dropdown
 * process_payment -- called when the checkout is complete before the confirmation page is shown. If a
 * payment fails than the user will be redirected back to the invoice.
 *
 * @package SI
 * @subpackage Payment Processing_Processor
 */
class SI_PO extends SI_Offsite_Processors {
	const PAYMENT_METHOD = 'PO';
	const PAYMENT_SLUG = 'popayment';
	protected static $instance;

	public static function get_instance() {
		if ( ! ( isset( self::$instance ) && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_payment_method() {
		return self::PAYMENT_METHOD;
	}

	public function get_slug() {
		return self::PAYMENT_SLUG;
	}

	public static function register() {
		self::add_payment_processor( __CLASS__, __( 'PO Payment (onsite submission)', 'sprout-invoices' ) );

		if ( is_admin() && self::is_processor_enabled( __CLASS__ ) ) {
			// Meta boxes
			add_action( 'admin_init', array( __CLASS__, 'register_meta_boxes' ) );

			add_filter( 'si_mngt_payments_columns', array( __CLASS__, 'register_columns' ) );
			add_filter( 'si_mngt_payments_column_attachments', array( __CLASS__, 'column_display' ) );
		}
	}

	public static function public_name() {
		return __( 'PO', 'sprout-invoices' );
	}

	public static function checkout_options() {
		$option = array(
			'icons' => array( SI_URL . '/resources/front-end/img/po.png' ),
			'label' => __( 'PO', 'sprout-invoices' ),
			'cc' => array(),
			);
		return apply_filters( 'si_popayment_checkout_options', $option );
	}

	protected function __construct() {
		parent::__construct();

		// Remove pages
		add_filter( 'si_checkout_pages', array( $this, 'remove_checkout_pages' ) );

		add_action( 'checkout_completed', array( $this, 'post_checkout_redirect' ), 10, 2 );
	}



	/**
	 * Loaded via SI_Payment_Processors::show_payments_pane
	 * @param  SI_Checkouts $checkout
	 * @return
	 */
	public function payments_pane( SI_Checkouts $checkout ) {
		self::load_view( 'templates/checkout/popayment/form', array(
				'checkout' => $checkout,
				'type' => self::PAYMENT_SLUG,
				'popayment_fields' => $this->po_info_fields( $checkout ),
		), true );
	}



	/**
	 * Loaded via SI_Payment_Processors::show_payments_pane
	 * @param  SI_Checkouts $checkout
	 * @return
	 */
	public function invoice_pane( SI_Checkouts $checkout ) {
		self::load_view( 'templates/checkout/popayment/form', array(
				'checkout' => null,
				'type' => self::PAYMENT_SLUG,
				'popayment_fields' => self::po_info_fields( $checkout ),
		), true );
	}

	/**
	 * An array of fields for check payments
	 *
	 * @static
	 * @return array
	 */
	public static function po_info_fields( $checkout = '' ) {
		$fields = array(
			'amount' => array(
				'type' => 'text',
				'weight' => 1,
				'label' => __( 'Amount', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => true,
			),
			'check_number' => array(
				'type' => 'text',
				'weight' => 5,
				'label' => __( 'PO Number', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => true,
			),
			'upload' => array(
				'type' => 'file',
				'weight' => 10,
				'label' => __( 'PO Document', 'sprout-invoices' ),
				'required' => true,
			),
			'notes' => array(
				'type' => 'textarea',
				'weight' => 15,
				'label' => __( 'Notes', 'sprout-invoices' ),
				'attributes' => array(
					//'autocomplete' => 'off',
				),
				'required' => false,
			),
			'nonce' => array( // anti-spam honeypot
				'type' => 'hidden',
				'weight' => 50,
				'label' => __( 'Skip this unless you are not human.', 'sprout-invoices' ),
				'required' => true,
				'value' => wp_create_nonce( SI_Controller::NONCE ),
			),
		);
		$fields = apply_filters( 'sa_popayment_fields', $fields, $checkout );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	/**
	 * The review page is unnecessary
	 *
	 * @param array   $pages
	 * @return array
	 */
	public function remove_checkout_pages( $pages ) {
		unset( $pages[ SI_Checkouts::REVIEW_PAGE ] );
		return $pages;
	}

	/**
	 * Process a payment
	 *
	 * @param SI_Checkouts $checkout
	 * @param SI_Invoice $invoice
	 * @return SI_Payment|bool false if the payment failed, otherwise a Payment object
	 */
	public function process_payment( SI_Checkouts $checkout, SI_Invoice $invoice ) {
		$amount = ( isset( $_POST['sa_popayments_amount'] ) ) ? $_POST['sa_popayments_amount'] : false ;
		$number = ( isset( $_POST['sa_popayments_check_number'] ) ) ? $_POST['sa_popayments_check_number'] : false ;
		$notes = ( isset( $_POST['sa_popayment_notes'] ) ) ? $_POST['sa_popayment_notes'] : '' ;

		if ( ! isset( $_POST['sa_popayments_nonce'] ) || ! wp_verify_nonce( $_POST['sa_popayments_nonce'], self::NONCE ) ) {
			return false;
		}

		if ( ! $amount ) {
			return false;
		}

		// create new payment
		$payment_id = SI_Payment::new_payment( array(
			'payment_method' => self::get_payment_method(),
			'invoice' => $invoice->get_id(),
			'amount' => $amount,
			'transaction_id' => $number,
			'data' => array(
			'amount' => $amount,
			'popayment_number' => $number,
			'notes' => $notes,
			),
		), SI_Payment::STATUS_PENDING );
		if ( ! $payment_id ) {
			return false;
		}
		$payment = SI_Payment::get_instance( $payment_id );

		if ( ! empty( $_FILES['sa_popayments_upload'] ) ) {
			// Set the uploaded field as an attachment
			$payment->set_attachement( $_FILES );
		}

		$invoice->set_po_number( $number );

		do_action( 'payment_pending', $payment );
		return $payment;
	}

	public function post_checkout_redirect( SI_Checkouts $checkout, SI_Payment $payment ) {
		if ( ! is_a( $checkout->get_processor(), __CLASS__ ) ) {
			return;
		}
		wp_redirect( $checkout->checkout_confirmation_url( self::PAYMENT_SLUG ) );
		exit();
	}

	/**
	 * Grabs error messages from a PayPal response and displays them to the user
	 *
	 * @param array   $response
	 * @param bool    $display
	 * @return void
	 */
	private function set_error_messages( $message, $display = true ) {
		if ( $display ) {
			self::set_message( $message, self::MESSAGE_STATUS_ERROR );
		} else {
			do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' - error message from paypal', $message );
		}
	}


	/////////////////
	// Meta boxes //
	/////////////////

	/**
	 * Regsiter meta boxes for estimate editing.
	 *
	 * @return
	 */
	public static function register_meta_boxes() {
		// invoice specific
		$args = array(
			'si_po_payment_attachments' => array(
				'title' => __( 'Payment Attachments', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_attachments_meta_box' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 0,
				'save_priority' => 0,
			),
		);
		do_action( 'sprout_meta_box', $args, SI_Invoice::POST_TYPE );
	}


	public static function show_attachments_meta_box( $post, $metabox ) {
		$attachments = self::get_invoice_payment_attachments( $post->ID );

		if ( empty( $attachments ) ) :  ?>
			<?php printf( '<p>%s</p>', __( 'No payment attachments found.', 'sprout-invoices' ) ) ?>
		<?php else : ?>
			<style type="text/css">
				#payment_doc_attachment_thumbnails a {
					margin-right: 30px;
					margin-left: 20px;
				}
				#payment_doc_attachment_thumbnails .payment_attachment {
					display: inline-block;
					margin-left: 20px;
				}
			</style>
			<p id="payment_doc_attachment_thumbnails">
				<?php foreach ( $attachments as $media_id ) : ?>

					<?php
						$file = basename( get_attached_file( $media_id ) );
						$filetype = wp_check_filetype( $file );
						$thumb_url = wp_get_attachment_thumb_url( $media_id );
						?>
					<a href="<?php echo get_edit_post_link( $media_id ) ?>" target="_blank" class="<?php echo esc_attr( $filetype['ext'] ) ?> payment_attachment" data-id="<?php echo esc_attr( $media_id ) ?>"><img src="<?php echo esc_html( $thumb_url ) ?>" /><br/><?php echo esc_html( get_the_title( $media_id ) ) ?></a>
				<?php endforeach ?>
			</p>
		<?php endif;
	}

	///////////
	// Meta //
	///////////

	/**
	 * Get the associated attachments with this doc
	 * @return array
	 */
	public static function get_invoice_payment_attachments( $invoice_id = 0 ) {
		$invoice = SI_Invoice::get_instance( $invoice_id );
		$payment_ids = $invoice->get_payments();
		$attachments = array();
		foreach ( $payment_ids as $payment_id ) {
			$args = array(
				'post_parent' => $payment_id,
				'post_type' => 'attachment',
				'posts_per_page' => -1,
				'orderby' => 'menu_order',
				'order' => 'ASC',
				'fields' => 'ids',
			);
			$attachments += get_children( $args );
		}
		return array_filter( $attachments );
	}

	////////////////////
	// Admin Columns //
	////////////////////

	/**
	 * Overload the columns for the estimate post type admin
	 *
	 * @param array   $columns
	 * @return array
	 */
	public static function register_columns( $columns ) {
		$columns['attachments'] = '<div class="dashicons icon-sproutapps-invoices"></div>';
		return $columns;
	}

	/**
	 * Display the content for the column
	 *
	 * @param string  $column_name
	 * @param int     $id          post_id
	 * @return string
	 */
	public static function column_display( $item ) {
		$args = array(
			'post_parent' => $item->ID,
			'post_type' => 'attachment',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'fields' => 'ids',
		);
		$attachments = get_children( $args );

		if ( empty( $attachments ) ) {
			return; // return for that temp post
		}
		foreach ( $attachments as $media_id ) {
			$file = basename( get_attached_file( $media_id ) );
			$filetype = wp_check_filetype( $file );
			$thumb_url = wp_get_attachment_thumb_url( $media_id );

			printf( '<a href="%s" target="_blank" class="%s payment_attachment" data-id="%s"><img src="%s" height="50px" width="auto"/></a>', get_edit_post_link( $media_id ), esc_attr( $filetype['ext'] ), esc_attr( $media_id ), esc_html( $thumb_url ), esc_html( get_the_title( $media_id ) ) );

		}
	}
}
SI_PO::register();
