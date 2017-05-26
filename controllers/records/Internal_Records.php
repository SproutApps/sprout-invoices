<?php

if ( class_exists( 'SI_Internal_Records' ) ) {
	return;
}

/**
 * Internal Record Controller
 * Records can be used from anything from logs to temporary payment records. Internal records should be considered temporary.
 *
 * @package Sprout_Invoices
 * @subpackage Records
 */
class SI_Internal_Records extends SI_Controller {
	const SETTINGS_PAGE = 'si_records';
	const NONCE = 'si_records';
	const RECORD_PURGE_NONCE = 'si_record_purge_nonce';
	private static $instance;

	public static function get_admin_page( $prefixed = true ) {
		return ( $prefixed ) ? self::APP_DOMAIN . '/' . self::SETTINGS_PAGE : self::SETTINGS_PAGE ;
	}

	public static function init() {
		add_action( 'si_new_record', array( __CLASS__, 'new_record' ), 10, 6 );

		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ), 10, 0 );

		add_action( 'deleted_post', array( __CLASS__, 'attempt_associated_record_deletion' ) );

		add_action( 'wp_ajax_si_delete_record',  array( get_class(), 'maybe_delete_record' ), 10, 0 );
		add_action( 'wp_ajax_si_edit_private_note',  array( get_class(), 'maybe_update_private_note' ), 10, 0 );
		// ajax views
		add_action( 'wp_ajax_si_edit_private_note_view',  array( __CLASS__, 'edit_private_note' ), 10, 0 );

		self::add_admin_page();
	}

	/**
	 * Add menu under tools.
	 */
	public static function add_admin_page() {
		// Option page
		$args = array(
			'parent' => 'tools.php',
			'slug' => self::SETTINGS_PAGE,
			'title' => __( 'Sprout Invoices Records and Logs', 'sprout-invoices' ),
			'menu_title' => __( 'Sprout Records', 'sprout-invoices' ),
			'weight' => 10,
			'reset' => false,
			'callback' => array( __CLASS__, 'display_table' ),
			);
		do_action( 'sprout_settings_page', $args );
	}

	public static function new_record( $data = array(), $type = 'mixed', $associate_id = -1, $title = '', $author_id = 0, $encoded = true ) {

		if ( ! $author_id && is_user_logged_in() ) {
			$author_id = get_current_user_id();
		}

		if ( is_object( $data ) ) {
			$data = json_decode( json_encode( $data ), true );
		}

		$status = ( isset( $data['status'] ) && '' !== $data['status'] ) ? $data['status'] : 'publish' ;
		$post_date = ( isset( $data['post_date'] ) && $data['post_date'] ) ? (int) $data['post_date'] : current_time( 'timestamp' );

		$post = array(
			'post_title' => $title,
			'post_author' => $author_id,
			'post_status' => $status,
			'post_type' => SI_Record::POST_TYPE,
			'post_parent' => $associate_id,
		);
		$id = wp_insert_post( $post );
		if ( $id && ! is_wp_error( $id ) ) {
			$record = SI_Record::get_instance( $id );
			$record->set_data( $data, $encoded );
			$record->set_associate_id( $associate_id );
			$record->set_type( $type );
			$record->set_post_date( date( 'Y-m-d H:i:s', $post_date ) );
			$record->activate();
		}
		do_action( 'si_record_created', $record );
		return $id;
	}

	public static function maybe_purge_records() {
		if ( ! isset( $_REQUEST[ self::RECORD_PURGE_NONCE ] ) ) {
			return; }

		if ( ! wp_verify_nonce( $_REQUEST[ self::RECORD_PURGE_NONCE ], self::RECORD_PURGE_NONCE ) ) {
			return; }

		if ( isset( $_GET['purge_records'] ) ) {
			self::purge_records_display( $_GET['purge_records'] ); }
	}

	public static function purge_records_display( $type = 0 ) {

		ignore_user_abort( 1 ); // run script in background
		set_time_limit( 0 ); // run script forever

		echo '<div id="deletion_progress" style="width:100%;border:1px solid #ccc;"></div> <div id="deletion_information">'.__( 'Preparing purge...', 'sprout-invoices' ).'</div>';

		$args = array(
			'post_type' => SI_Record::POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => 2500,
			'fields' => 'ids',
		);
		if ( $type ) {
			$tax_query = array(
					'tax_query' => array(
							array(
								'taxonomy' => SI_Record::TAXONOMY,
								'field' => 'id',
								'terms' => $type,
							),
						),
				);
			$args = array_merge( $args, $tax_query );
		}
		$records = get_posts( $args );
		$i = 0;
		$total = count( $records );
		foreach ( $records as $record_id ) {
			$i++;
			// Calculate the percentage
			$percent = intval( $i / $total * 100 ).'%';
			// Javascript for updating the progress bar and information
			echo '<script language="javascript" id="progress_js">
			document.getElementById("deletion_progress").innerHTML="<div style=\"width:'.$percent.';background-color:#ddd;\">&nbsp;</div>";
			document.getElementById("deletion_information").innerHTML="'.sprintf( __( '%o records(s) of %o deleted.', 'sprout-invoices' ), $i, $total ).'";
			document.getElementById("progress_js").remove();
			</script>';

			// Send output to browser immediately
			flush();

			// delete the post
			wp_delete_post( $record_id, true );
		}
		echo '<script language="javascript">document.getElementById("deletion_information").innerHTML="'.sprintf( __( 'Complete. %o deleted.', 'sprout-invoices' ), $total ).'"</script>';
	}

	/**
	 * Attempt to delete any records associated with the post just deleted.
	 * @param  integer $post_id
	 * @return
	 */
	public static function attempt_associated_record_deletion( $post_id = 0 ) {
		// prevent looping and checking if a record has a record associated with it.
		if ( get_post_type( $post_id ) !== SI_Record::POST_TYPE ) {
			global $wpdb;
			$record_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = '%s'", $post_id, SI_Record::POST_TYPE ) );

			foreach ( $record_ids as $record_id ) {
				wp_delete_post( $record_id, true );
			}
		}
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	private function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}
	private function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function sort_callback( $a, $b ) {
		if ( $a == $b ) {
			return 0;
		}
		return ( $a < $b ) ? 1 : -1;
	}

	///////////
	// AJAX //
	///////////

	public static function maybe_delete_record() {
		if ( ! isset( $_REQUEST['nonce'] ) ) {
			wp_die( 'Forget something?' );
		}

		$nonce = $_REQUEST['nonce'];
		if ( ! wp_verify_nonce( $nonce, SI_Controller::NONCE ) ) {
			wp_die( 'Not going to fall for it!' );
		}

		if ( current_user_can( 'manage_sprout_invoices_records' ) ) {
			$record_id = $_REQUEST['record_id'];
			wp_delete_post( $record_id, true );
			do_action( 'si_deleted_record', $record_id );
		}
	}

	public static function edit_private_note() {
		if ( ! current_user_can( 'edit_sprout_invoices' ) ) {
			self::ajax_fail( 'User cannot create new posts!' );
		}
		if ( ! isset( $_REQUEST['note_id'] ) ) {
			self::ajax_fail( 'No id given!' );
		}
		$record_id = $_REQUEST['note_id'];
		$record = SI_Record::get_instance( $record_id );
		$record_post = $record->get_post();
		$fields = array();
		$fields['note'] = array(
			'weight' => 0,
			'label' => __( 'Private Note', 'sprout-invoices' ),
			'type' => 'textarea',
			'default' => stripslashes_from_strings_only( $record_post->post_content ),
		);
		$fields['record_id'] = array(
			'weight' => 50,
			'type' => 'hidden',
			'value' => $record_id,
		);
		self::load_view( 'admin/sections/edit-private-note', array(
				'fields' => $fields,
				'record_id' => $record_id,
		), false );
		exit();
	}

	public static function maybe_update_private_note() {
		if ( ! isset( $_REQUEST['nonce'] ) ) {
			wp_die( 'Forget something?' );
		}

		$nonce = $_REQUEST['nonce'];
		if ( ! wp_verify_nonce( $nonce, SI_Controller::NONCE ) ) {
			wp_die( 'Not going to fall for it!' );
		}

		if ( ! isset( $_REQUEST['record_id'] ) ) {
			self::ajax_fail( 'No id given!' );
		}

		$record_id = $_REQUEST['record_id'];
		$private_note = $_REQUEST['private_note'];
		$record = SI_Record::get_instance( $record_id );
		$record->set_data( $private_note, false );
		exit();
	}

	public static function display_table() {
		add_thickbox();
		//Create an instance of our package class...
		$wp_list_table = new SI_Records_Table();
		//Fetch, prepare, sort, and filter our data...
		$wp_list_table->prepare_items();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			jQuery(".show_record_detail").live('click', function(e) {
				e.preventDefault();
				var record_id = $(this).parent().attr("id");
				$('#'+record_id).remove();
				$('#record_detail_'+record_id).toggle();
			});
		});
	</script>
	<div class="wrap">
		<h2>
			<?php _e( 'Sprout Invoices Records', 'sprout-invoices' ) ?>
		</h2>
		<?php self::maybe_purge_records(); ?>
		<form id="records-filter" method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
			<?php $wp_list_table->display() ?>
		</form>
	</div>
	<?php
	}
}
