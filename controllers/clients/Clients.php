<?php

/**
 * Clients Controller
 *
 *
 * @package Sprout_Invoice
 * @subpackage Clients
 */
class SI_Clients extends SI_Controller {
	const SUBMISSION_NONCE = 'si_client_submission';


	public static function init() {

		add_filter( 'si_submission_form_fields', array( __CLASS__, 'filter_estimate_submission_fields' ) );
		add_action( 'estimate_submitted',  array( __CLASS__, 'create_client_from_submission' ), 10, 2 );

		if ( is_admin() ) {

			// Help Sections
			add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

			// Meta boxes
			add_action( 'admin_init', array( __CLASS__, 'register_meta_boxes' ) );
			add_filter( 'wp_insert_post_data', array( __CLASS__, 'update_post_data' ), 100, 2 );
			add_action( 'do_meta_boxes', array( __CLASS__, 'modify_meta_boxes' ) );
			add_action( 'edit_form_top', array( __CLASS__, 'name_box' ) );

			// Admin columns
			add_filter( 'manage_edit-'.SI_Client::POST_TYPE.'_columns', array( __CLASS__, 'register_columns' ) );
			add_filter( 'manage_'.SI_Client::POST_TYPE.'_posts_custom_column', array( __CLASS__, 'column_display' ), 10, 2 );
			add_action( 'post_row_actions', array( __CLASS__, 'modify_row_actions' ), 10, 2 );

			// User Admin columns
			add_filter( 'manage_users_columns', array( __CLASS__, 'user_register_columns' ) );
			add_filter( 'manage_users_custom_column', array( __CLASS__, 'user_column_display' ), 10, 3 );

			// AJAX
			add_action( 'wp_ajax_sa_create_client',  array( __CLASS__, 'maybe_create_client' ), 10, 0 );
			add_action( 'wp_ajax_sa_create_user',  array( __CLASS__, 'maybe_create_user' ), 10, 0 );

			add_action( 'wp_ajax_sa_client_submit_metabox',  array( __CLASS__, 'submit_meta_box_view' ), 10, 0 );

			// Improve admin search
			add_filter( 'si_admin_meta_search', array( __CLASS__, 'filter_admin_search' ), 10, 2 );

		}

		// Prevent Client role admin access
		add_action( 'admin_init', array( __CLASS__, 'redirect_clients' ) );

		// Admin bar
		add_filter( 'si_admin_bar', array( get_class(), 'add_link_to_admin_bar' ), 10, 1 );

		// Currency Formatting
		add_filter( 'sa_get_currency_symbol_pre', array( __CLASS__, 'maybe_filter_currency_symbol' ) );
		add_filter( 'sa_set_monetary_locale', array( __CLASS__, 'maybe_filter_money_format_money_format' ), 10, 2 );

		// Currency Code Change
		add_filter( 'si_currency_code', array( get_class(), 'maybe_change_currency_code' ), 10, 2 );

		// handle deletions
		add_action( 'before_delete_post', array( __CLASS__, 'maybe_disassociate_records' ) );

	}

	public static function maybe_disassociate_records( $post_id = 0 ) {
		if ( SI_Client::POST_TYPE === get_post_type( $post_id ) ) {
			$client = SI_Client::get_instance( $post_id );
			$invoices = $client->get_invoices();
			foreach ( $invoices as $invoice_id ) {
				$invoice = SI_Invoice::get_instance( $invoice_id );
				$invoice->set_client_id( 0 );
			}

			$estimates = $client->get_estimates();
			foreach ( $estimates as $estimate_id ) {
				$estimate = SI_Estimate::get_instance( $estimate_id );
				$estimate->set_client_id( 0 );
			}

			$projects = SI_Project::get_projects_by_client( $post_id );
			foreach ( $projects as $project_id ) {
				$project = SI_Project::get_instance( $project_id );
				$project->set_associated_clients( array() );
			}
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
		// estimate specific
		$args = array(
			'si_client_information' => array(
				'title' => __( 'Information', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_information_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_meta_box_client_information' ),
				'context' => 'normal',
				'priority' => 'high',
				'save_priority' => 0,
				'weight' => 10,
			),
			'si_client_submit' => array(
				'title' => 'Update',
				'show_callback' => array( __CLASS__, 'show_submit_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_submit_meta_box' ),
				'context' => 'side',
				'priority' => 'high',
			),
			'si_client_advanced' => array(
				'title' => __( 'Advanced', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_adv_information_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_meta_box_client_adv_information' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 50,
			),
			'si_client_history' => array(
				'title' => __( 'History', 'sprout-invoices' ),
				'show_callback' => array( __CLASS__, 'show_client_history_view' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 100,
			),
		);
		do_action( 'sprout_meta_box', $args, SI_Client::POST_TYPE );
	}

	/**
	 * Remove publish box and add something custom for estimates
	 *
	 * @param string  $post_type
	 * @return
	 */
	public static function modify_meta_boxes( $post_type ) {
		if ( $post_type == SI_Client::POST_TYPE ) {
			remove_meta_box( 'submitdiv', null, 'side' );
		}
	}

	/**
	 * Add quick links
	 * @param  object $post
	 * @return
	 */
	public static function name_box( $post ) {
		if ( get_post_type( $post ) == SI_Client::POST_TYPE ) {
			$client = SI_Client::get_instance( $post->ID );
			self::load_view( 'admin/meta-boxes/clients/name', array(
					'client' => $client,
					'id' => $post->ID,
					'status' => $post->post_status,
			) );
		}
	}

	/**
	 * Show custom submit box.
	 * @param  WP_Post $post
	 * @param  array $metabox
	 * @return
	 */
	public static function show_submit_meta_box( $post, $metabox ) {
		$client = SI_Client::get_instance( $post->ID );

		$args = apply_filters( 'si_get_users_for_association_args', array( 'fields' => array( 'ID', 'user_email', 'display_name' ) ) );
		$users = get_users( $args );
		self::load_view( 'admin/meta-boxes/clients/submit', array(
				'id' => $post->ID,
				'client' => $client,
				'post' => $post,
				'associated_users' => $client->get_associated_users(),
				'users' => $users,
				'invoices' => $client->get_invoices(),
				'estimates' => $client->get_estimates(),
		), false );

		add_thickbox();

		// add the user creation modal
		$fields = self::user_form_fields( $post->ID );
		self::load_view( 'admin/meta-boxes/clients/create-user-modal', array( 'fields' => $fields ) );
	}

	/**
	 * Information
	 * @param  object $post
	 * @return
	 */
	public static function show_information_meta_box( $post ) {
		if ( get_post_type( $post ) == SI_Client::POST_TYPE ) {
			$client = SI_Client::get_instance( $post->ID );
			self::load_view( 'admin/meta-boxes/clients/info', array(
					'client' => $client,
					'id' => $post->ID,
					'associated_users' => $client->get_associated_users(),
					'fields' => self::form_fields( false, $client ),
					'address' => $client->get_address(),
					'website' => $client->get_website(),
			) );
		}
	}

	/**
	 * Information
	 * @param  object $post
	 * @return
	 */
	public static function show_adv_information_meta_box( $post ) {
		if ( get_post_type( $post ) == SI_Client::POST_TYPE ) {
			$client = SI_Client::get_instance( $post->ID );
			self::load_view( 'admin/meta-boxes/clients/advanced', array(
					'client' => $client,
					'id' => $post->ID,
					'fields' => self::adv_form_fields( false, $client ),
			) );
		}
	}

	/**
	 * Saving info meta
	 * @param  int $post_id
	 * @param  object $post
	 * @param  array $callback_args
	 * @param  int $estimate_id
	 * @return
	 */
	public static function save_meta_box_client_information( $post_id, $post, $callback_args, $estimate_id = null ) {
		// name is filtered via update_post_data
		$website = ( isset( $_POST['sa_metabox_website'] ) && $_POST['sa_metabox_website'] != '' ) ? $_POST['sa_metabox_website'] : '' ;

		$address = array(
			'street' => isset( $_POST['sa_metabox_street'] ) ? $_POST['sa_metabox_street'] : '',
			'city' => isset( $_POST['sa_metabox_city'] ) ? $_POST['sa_metabox_city'] : '',
			'zone' => isset( $_POST['sa_metabox_zone'] ) ? $_POST['sa_metabox_zone'] : '',
			'postal_code' => isset( $_POST['sa_metabox_postal_code'] ) ? $_POST['sa_metabox_postal_code'] : '',
			'country' => isset( $_POST['sa_metabox_country'] ) ? $_POST['sa_metabox_country'] : '',
		);

		$client = SI_Client::get_instance( $post_id );
		$client->set_website( $website );
		$client->set_address( $address );

		$user_id = 0;
		// Attempt to create a user
		if ( isset( $_POST['sa_metabox_email'] ) && $_POST['sa_metabox_email'] != '' ) {
			$user_args = array(
				'user_login' => esc_html( $_POST['sa_metabox_email'], 'sprout-invoices' ),
				'display_name' => isset( $_POST['sa_metabox_name'] ) ? esc_html( $_POST['sa_metabox_name'], 'sprout-invoices' ) : esc_html( $_POST['sa_metabox_email'], 'sprout-invoices' ),
				'user_pass' => wp_generate_password(), // random password
				'user_email' => isset( $_POST['sa_metabox_email'] ) ? esc_html( $_POST['sa_metabox_email'], 'sprout-invoices' ) : '',
				'first_name' => isset( $_POST['sa_metabox_first_name'] ) ? esc_html( $_POST['sa_metabox_first_name'], 'sprout-invoices' ) : '',
				'last_name' => isset( $_POST['sa_metabox_last_name'] ) ? esc_html( $_POST['sa_metabox_last_name'], 'sprout-invoices' ) : '',
				'user_url' => isset( $_POST['sa_metabox_website'] ) ? esc_html( $_POST['sa_metabox_website'], 'sprout-invoices' ) : '',
			);
			$user_id = self::create_user( $user_args );
		}

		if ( $user_id ) {
			$client->add_associated_user( $user_id );
		}
	}

	public static function update_post_data( $data = array(), $post = array() ) {
		if ( $post['post_type'] == SI_Client::POST_TYPE ) {
			$title = $post['post_title'];
			if ( isset( $_POST['sa_metabox_name'] ) && $_POST['sa_metabox_name'] != '' ) {
				$title = $_POST['sa_metabox_name'];
			}
			// modify the post title
			$data['post_title'] = $title;
		}
		return $data;
	}

	/**
	 * Saving info meta
	 * @param  int $post_id
	 * @param  object $post
	 * @param  array $callback_args
	 * @param  int $estimate_id
	 * @return
	 */
	public static function save_meta_box_client_adv_information( $post_id, $post, $callback_args, $estimate_id = null ) {
		$currency = ( isset( $_POST['sa_metabox_currency'] ) && $_POST['sa_metabox_currency'] != '' ) ? $_POST['sa_metabox_currency'] : '' ;
		$currency_symbol = ( isset( $_POST['sa_metabox_currency_symbol'] ) && $_POST['sa_metabox_currency_symbol'] != '' ) ? $_POST['sa_metabox_currency_symbol'] : '' ;
		$money_format = ( isset( $_POST['sa_metabox_money_format'] ) && $_POST['sa_metabox_money_format'] != '' ) ? $_POST['sa_metabox_money_format'] : '' ;

		$client = SI_Client::get_instance( $post_id );

		$client->set_currency( $currency );
		$client->set_currency_symbol( $currency_symbol );
		$client->set_money_format( $money_format );
	}

	/**
	 * Saving submit meta
	 * @param  int $post_id
	 * @param  object $post
	 * @param  array $callback_args
	 * @param  int $estimate_id
	 * @return
	 */
	public static function save_submit_meta_box( $post_id, $post, $callback_args, $estimate_id = null ) {

		$client = SI_Client::get_instance( $post_id );
		$client->clear_associated_users();

		if ( ! isset( $_POST['associated_users'] ) ) {
			return;
		}

		foreach ( $_POST['associated_users'] as $user_id ) {
			$client->add_associated_user( $user_id );
		}

	}


	/**
	 * Show the history
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_client_history_view( $post, $metabox ) {
		if ( $post->post_status == 'auto-draft' ) {
			printf( '<p>%s</p>', __( 'No history available.', 'sprout-invoices' ) );
			return;
		}
		$client = SI_Client::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/clients/history', array(
				'id' => $post->ID,
				'post' => $post,
				'estimate' => $client,
				'historical_records' => array_reverse( $client->get_history() ),
		), false );
	}


	////////////
	// Misc. //
	////////////

	/**
	 * Redirect any clients away from the admin.
	 * @return
	 */
	public static function redirect_clients() {
		// Don't redirect admin-ajax.php requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! isset( $user->roles ) || ( ! empty( $user->roles ) && $user->roles[0] == 'sa_client' ) ) {
			wp_redirect( home_url() );
			exit();
		}

	}


	////////////////////
	// Admin Columns //
	////////////////////

	/**
	 * Overload the columns for the invoice post type admin
	 *
	 * @param array   $columns
	 * @return array
	 */
	public static function register_columns( $columns ) {
		// Remove all default columns
		unset( $columns['date'] );
		unset( $columns['title'] );
		unset( $columns['comments'] );
		unset( $columns['author'] );
		$columns['title'] = __( 'Client', 'sprout-invoices' );
		$columns['info'] = __( 'Info', 'sprout-invoices' );
		$columns['invoices'] = __( 'Invoices', 'sprout-invoices' );
		$columns['estimates'] = __( 'Estimates', 'sprout-invoices' );
		return apply_filters( 'si_client_columns', $columns );
	}

	/**
	 * Display the content for the column
	 *
	 * @param string  $column_name
	 * @param int     $id          post_id
	 * @return string
	 */
	public static function column_display( $column_name, $id ) {
		$client = SI_Client::get_instance( $id );

		if ( ! is_a( $client, 'SI_Client' ) ) {
			return; // return for that temp post
		}
		switch ( $column_name ) {

			case 'info':

				echo '<p>';
				$address = si_format_address( $client->get_address(), 'string', '<br/>' );
				echo $address;
				if ( $address != '' ) {
					echo '<br/>';
				}
				echo make_clickable( esc_url( $client->get_website() ) );
				echo '</p>';

				$associated_users = $client->get_associated_users();
				echo '<p>';
				printf( '<b>%s</b>: ', __( 'Users', 'sprout-invoices' ) );
				if ( ! empty( $associated_users ) ) {
					$users_print = array();
					foreach ( $associated_users as $user_id ) {
						$user = get_userdata( $user_id );
						if ( $user ) {
							$users_print[] = sprintf( '<span class="associated_user"><a href="%s">%s</a></span>', get_edit_user_link( $user_id ), $user->display_name );
						}
					}
				}
				if ( ! empty( $users_print ) ) {
					echo implode( ', ', $users_print );
				} else {
					echo __( 'No associated users', 'sprout-invoices' );
				}
				echo '</p>';

			break;

			case 'invoices':

				$invoices = $client->get_invoices();
				$split = 3;
				$split_invoices = array_slice( $invoices, 0, $split );
				if ( ! empty( $split_invoices ) ) {
					echo '<dl>';
					foreach ( $split_invoices as $invoice_id ) {
						printf( '<dt>%s</dt><dd><a href="%s">%s</a></dd>', get_post_time( get_option( 'date_format' ), false, $invoice_id ), get_edit_post_link( $invoice_id ), get_the_title( $invoice_id ) );
					}
					echo '</dl>';
					if ( count( $invoices ) > $split ) {
						printf( '<span class="description">' . __( '...%s of <a href="%s">%s</a> most recent shown', 'sprout-invoices' ) . '</span>', $split, get_edit_post_link( $id ), count( $invoices ) );
					}
				} else {
					printf( '<em>%s</em>', __( 'No invoices', 'sprout-invoices' ) );
				}
			break;

			case 'estimates':

				$estimates = $client->get_estimates();
				$split = 3;
				$split_estimates = array_slice( $estimates, 0, $split );
				if ( ! empty( $split_estimates ) ) {
					echo '<dl>';
					foreach ( $split_estimates as $estimate_id ) {
						printf( '<dt>%s</dt><dd><a href="%s">%s</a></dd>', get_post_time( get_option( 'date_format' ), false, $estimate_id ), get_edit_post_link( $estimate_id ), get_the_title( $estimate_id ) );
					}
					echo '</dl>';
					if ( count( $estimates ) > $split ) {
						printf( '<span class="description">' . __( '...%s of <a href="%s">%s</a> most recent shown', 'sprout-invoices' ) . '</span>', $split, get_edit_post_link( $id ), count( $estimates ) );
					}
				} else {
					printf( '<em>%s</em>', __( 'No estimates', 'sprout-invoices' ) );
				}
			break;

			default:
				// code...
			break;
		}

	}

	/**
	 * Register the client column. In CSS make it small.
	 * @param  array $columns
	 * @return array
	 */
	public static function user_register_columns( $columns ) {
		$columns['client'] = '<div class="dashicons dashicons-id-alt"></div>';
		return $columns;
	}

	/**
	 * User column display
	 * @param  string $empty
	 * @param  string $column_name
	 * @param  int $id
	 * @return string
	 */
	public static function user_column_display( $empty = '', $column_name, $id ) {
		switch ( $column_name ) {
			case 'client':
				$client_ids = SI_Client::get_clients_by_user( $id );

				if ( ! empty( $client_ids ) ) {
					$string = '';
					foreach ( $client_ids as $client_id ) {
						$string .= sprintf( __( '<a class="doc_link" title="%s" href="%s">%s</a>', 'sprout-invoices' ), get_the_title( $client_id ), get_edit_post_link( $client_id ), '<div class="dashicons dashicons-id-alt"></div>' );
					}
					return $string;
				}
				break;

			default:
				return $empty;
				break;
		}
	}

	/**
	 * Filter the array of row action links below the title.
	 *
	 * @param array   $actions An array of row action links.
	 * @param WP_Post $post    The post object.
	 */
	public static function modify_row_actions( $actions = array(), $post = array() ) {
		if ( $post->post_type == SI_Client::POST_TYPE ) {
			unset( $actions['trash'] );
			// remove quick edit
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}

	public static function filter_admin_search( $meta_search = '', $post_type = '' ) {
		if ( SI_Client::POST_TYPE !== $post_type ) {
			return array();
		}
		$meta_search = array(
			'_phone',
			'_website',
		);
		return $meta_search;
	}


	////////////
	// Forms //
	////////////

	public static function form_fields( $required = true, $client = 0 ) {
		$fields = array();
		$fields['name'] = array(
			'weight' => 1,
			'label' => __( 'Company Name', 'sprout-invoices' ),
			'type' => 'text',
			'required' => $required, // always necessary
			'default' => '',
		);

		if ( ! $client ) {
			$fields['email'] = array(
				'weight' => 3,
				'label' => __( 'Email', 'sprout-invoices' ),
				'type' => 'text',
				'required' => $required,
				'description' => __( 'This e-mail will be used to create a new client user. Leave blank if associating an existing user.', 'sprout-invoices' ),
				'default' => '',
			);
		}

		$fields['website'] = array(
			'weight' => 120,
			'label' => __( 'Website', 'sprout-invoices' ),
			'type' => 'text',
			'required' => $required,
			'default' => ( $client ) ? $client->get_website() : '',
			'placeholder' => 'http://',
		);

		$fields['nonce'] = array(
			'type' => 'hidden',
			'value' => wp_create_nonce( self::SUBMISSION_NONCE ),
			'weight' => 10000,
		);

		$fields = array_merge( $fields, self::get_standard_address_fields( $required ) );

		// Don't add fields to add new clients when the client exists
		if ( $client ) {
			unset( $fields['first_name'] );
			unset( $fields['last_name'] );
		}

		$fields = apply_filters( 'si_client_form_fields', $fields, $client );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	public static function user_form_fields( $client_id = 0 ) {
		$fields = array();
		$fields['display_name'] = array(
			'weight' => 1,
			'label' => __( 'Full Name & Title', 'sprout-invoices' ),
			'type' => 'text',
			'required' => false,
		);

		$fields['email'] = array(
			'weight' => 3,
			'label' => __( 'Email', 'sprout-invoices' ),
			'type' => 'text',
			'required' => false, // required but the modal will block updates
			'default' => '',
		);

		$fields['first_name'] = array(
			'weight' => 50,
			'label' => __( 'First Name', 'sprout-invoices' ),
			'placeholder' => __( 'First Name', 'sprout-invoices' ),
			'type' => 'text',
			'required' => false,
		);
		$fields['last_name'] = array(
			'weight' => 51,
			'label' => __( 'Last Name', 'sprout-invoices' ),
			'placeholder' => __( 'Last Name', 'sprout-invoices' ),
			'type' => 'text',
			'required' => false,
		);

		$fields['client_id'] = array(
			'type' => 'hidden',
			'value' => $client_id,
			'weight' => 10000,
		);

		$fields['nonce'] = array(
			'type' => 'hidden',
			'value' => wp_create_nonce( self::SUBMISSION_NONCE ),
			'weight' => 10000,
		);

		$fields = apply_filters( 'si_user_form_fields', $fields );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	public static function adv_form_fields( $required = true, $client = 0 ) {
		$money_format = ( $client ) ? $client->get_money_format() : get_locale();
		$si_localeconv = si_localeconv();

		$fields = array();

		$fields['currency'] = array(
			'weight' => 220,
			'label' => __( 'Currency Code', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( $client ) ? $client->get_currency() : '',
			'required' => $required,
			'placeholder' => $si_localeconv['int_curr_symbol'],
			'attributes' => array( 'size' => '8' ),
			'description' => __( 'This setting will override the setting for each payment processor that supports differing currency codes.', 'sprout-invoices' ),
		);

		/*/
		$fields['currency_symbol'] = array(
			'weight' => 225,
			'label' => __( 'Currency Symbol', 'sprout-invoices' ),
			'type' => 'text',
			'default' => ( $client ) ? $client->get_currency_symbol() : '$',
			'required' => $required,
			'placeholder' => '$',
			'attributes' => array( 'class' => 'small-text' ),
			'description' => __( 'This setting will override the default payments setting. If your currency has the symbol after the amount place a % before your currency symbol. Example, % &pound;', 'sprout-invoices' )
		);
		/**/

		$fields['money_format'] = array(
			'weight' => 230,
			'label' => __( 'Money Format', 'sprout-invoices' ),
			'type' => 'select',
			'default' => $money_format,
			'options' => $required,
			'options' => array_flip( SI_Locales::$locales ),
			'attributes' => array( 'class' => 'select2' ),
			'description' => sprintf( __( 'Current format: %1$s. The default money formatting (%2$s) can be overridden for all client estimates and invoices here.', 'sprout-invoices' ), sa_get_formatted_money( rand( 11000, 9999999 ), get_the_id() ), '<code>'.$si_localeconv['int_curr_symbol'].'</code>' ),
		);

		$fields['nonce'] = array(
			'type' => 'hidden',
			'value' => wp_create_nonce( self::SUBMISSION_NONCE ),
			'weight' => 10000,
		);

		$fields = apply_filters( 'si_client_adv_form_fields', $fields, $client );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	/**
	 * Maybe create a user if one is not already created.
	 * @param  array  $args
	 * @return $user_id
	 */
	public static function create_user( $args = array() ) {
		$defaults = array(
			'user_login' => '',
			'user_name' => '',
			'user_pass' => '',
			'user_email' => '',
			'first_name' => '',
			'last_name' => '',
			'user_url' => '',
			'role' => SI_Client::USER_ROLE,
		);
		$parsed_args = wp_parse_args( apply_filters( 'si_create_user_args', $args ), $defaults );

		// check if the user already exists.
		if ( $user = get_user_by( 'email', $parsed_args['user_email'] ) ) {
			return $user->ID;
		}

		$user_id = wp_insert_user( $parsed_args );
		do_action( 'si_user_created', $user_id, $parsed_args );
		return $user_id;
	}

	///////////////////////////////
	// money Formatting Filters //
	///////////////////////////////

	public static function maybe_filter_currency_symbol( $symbol = '' ) {
		switch ( get_post_type( get_the_id() ) ) {
			case SI_Client::POST_TYPE:
				$client = SI_Client::get_instance( get_the_id() );
				break;
			case SI_Invoice::POST_TYPE:
				$invoice = SI_Invoice::get_instance( get_the_id() );
				$client = $invoice->get_client();
				break;
			case SI_Estimate::POST_TYPE:
				$estimate = SI_Estimate::get_instance( get_the_id() );
				$client = $estimate->get_client();
				break;

			default:
				$client = null;
				break;
		}
		if ( is_a( $client, 'SI_Client' ) ) {
			$client_symbol = $client->get_currency_symbol();
			if ( $client_symbol != '' ) {
				$symbol = $client_symbol;
			}
		}
		return $symbol;
	}

	public static function maybe_filter_money_format_money_format( $money_format = '', $doc_id = 0 ) {
		if ( ! $doc_id ) {
			$doc_id = get_the_ID();
		}
		switch ( get_post_type( $doc_id ) ) {
			case SI_Client::POST_TYPE:
				$client = SI_Client::get_instance( $doc_id );
				break;
			case SI_Invoice::POST_TYPE:
				$invoice = SI_Invoice::get_instance( $doc_id );
				$client = $invoice->get_client();
				break;
			case SI_Estimate::POST_TYPE:
				$estimate = SI_Estimate::get_instance( $doc_id );
				$client = $estimate->get_client();
				break;

			default:
				$client = null;
				break;
		}
		if ( is_a( $client, 'SI_Client' ) ) {
			$client_money_format = $client->get_money_format();
			if ( $client_money_format != '' ) {
				$money_format = $client_money_format;
			}
		}
		return $money_format;
	}

	//////////////////
	// Submissions //
	//////////////////

	/**
	 * Filter the submission fields if the user is logged in and a client is already created.
	 * @param  array  $fields
	 * @return array
	 */
	public static function filter_estimate_submission_fields( $fields = array() ) {
		if ( is_user_logged_in() ) {
			$client_id = 0;
			$client_ids = SI_Client::get_clients_by_user( get_current_user_id() );
			if ( ! empty( $client_ids ) ) {
				$client_id = array_pop( $client_ids );
			}
			if ( get_post_type( $client_id ) == SI_Client::POST_TYPE ) {
				// If the client exists don't show the client fields
				unset( $fields['name'] );
				unset( $fields['client_name'] );
				unset( $fields['email'] );
				unset( $fields['website'] );
				$fields['client_id'] = array(
					'type' => 'hidden',
					'value' => $client_id,
				);
			}
		}
		return $fields;
	}


	/**
	 * Hooked into the estimate submission form. Create a client
	 * if one already doesn't exist.
	 * @param  SI_Estimate $estimate
	 * @param  array       $parsed_args
	 * @return
	 */
	public static function create_client_from_submission( SI_Estimate $estimate, $parsed_args = array() ) {
		$client_id = ( isset( $_REQUEST['client_id'] ) && get_post_type( $_REQUEST['client_id'] ) == SI_Client::POST_TYPE ) ? $_REQUEST['client_id'] : 0;
		$user_id = get_current_user_id();

		// check to see if the user exists by email
		if ( isset( $_REQUEST['sa_estimate_email'] ) && $_REQUEST['sa_estimate_email'] != '' ) {
			if ( $user = get_user_by( 'email', $_REQUEST['sa_estimate_email'] ) ) {
				$user_id = $user->ID;
			}
		}

		// Check to see if the user is assigned to a client already
		if ( ! $client_id ) {
			$client_ids = SI_Client::get_clients_by_user( $user_id );
			if ( ! empty( $client_ids ) ) {
				$client_id = array_pop( $client_ids );
			}
		}

		// Create a user for the submission if an email is provided.
		if ( ! $user_id ) {
			// email is critical
			if ( isset( $_REQUEST['sa_estimate_email'] ) && $_REQUEST['sa_estimate_email'] != '' ) {
				$user_args = array(
					'user_login' => esc_html( $_REQUEST['sa_estimate_email'], 'sprout-invoices' ),
					'display_name' => isset( $_REQUEST['sa_estimate_client_name'] ) ? esc_html( $_REQUEST['sa_estimate_client_name'], 'sprout-invoices' ) : esc_html( $_REQUEST['sa_estimate_email'], 'sprout-invoices' ),
					'user_pass' => wp_generate_password(), // random password
					'user_email' => isset( $_REQUEST['sa_estimate_email'] ) ? esc_html( $_REQUEST['sa_estimate_email'], 'sprout-invoices' ) : '',
					'first_name' => si_split_full_name( esc_html( $_REQUEST['sa_estimate_name'], 'sprout-invoices' ), 'first' ),
					'last_name' => si_split_full_name( esc_html( $_REQUEST['sa_estimate_name'], 'sprout-invoices' ), 'last' ),
					'user_url' => isset( $_REQUEST['sa_estimate_website'] ) ? esc_html( $_REQUEST['sa_estimate_website'], 'sprout-invoices' ) : '',
				);
				$user_id = self::create_user( $user_args );
			}
		}

		// create the client based on what's submitted.
		if ( ! $client_id ) {
			$address = array(
				'street' => isset( $_REQUEST['sa_contact_street'] ) ? esc_html( $_REQUEST['sa_contact_street'], 'sprout-invoices' ) : '',
				'city' => isset( $_REQUEST['sa_contact_city'] ) ? esc_html( $_REQUEST['sa_contact_city'], 'sprout-invoices' ) : '',
				'zone' => isset( $_REQUEST['sa_contact_zone'] ) ? esc_html( $_REQUEST['sa_contact_zone'], 'sprout-invoices' ) : '',
				'postal_code' => isset( $_REQUEST['sa_contact_postal_code'] ) ? esc_html( $_REQUEST['sa_contact_postal_code'], 'sprout-invoices' ) : '',
				'country' => isset( $_REQUEST['sa_contact_country'] ) ? esc_html( $_REQUEST['sa_contact_country'], 'sprout-invoices' ) : '',
			);

			$args = array(
				'company_name' => isset( $_REQUEST['sa_estimate_client_name'] ) ? esc_html( $_REQUEST['sa_estimate_client_name'], 'sprout-invoices' ) : '',
				'website' => isset( $_REQUEST['sa_estimate_website'] ) ? esc_html( $_REQUEST['sa_estimate_website'], 'sprout-invoices' ) : '',
				'address' => $address,
				'user_id' => $user_id,
			);

			$client_id = SI_Client::new_client( $args );
		}

		// Set the estimates client
		$estimate->set_client_id( $client_id );

	}

	/**
	 * AJAX submission from admin.
	 * @return json response
	 */
	public static function maybe_create_client() {
		// form maybe be serialized
		if ( isset( $_REQUEST['serialized_fields'] ) ) {
			foreach ( $_REQUEST['serialized_fields'] as $key => $data ) {
				$_REQUEST[ $data['name'] ] = $data['value'];
			}
		}

		if ( ! isset( $_REQUEST['sa_client_nonce'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['sa_client_nonce'];
		if ( ! wp_verify_nonce( $nonce, self::SUBMISSION_NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! current_user_can( 'publish_sprout_invoices' ) ) {
			self::ajax_fail( 'User cannot create new posts!' ); }

		if ( ! isset( $_REQUEST['sa_client_name'] ) || $_REQUEST['sa_client_name'] == '' ) {
			self::ajax_fail( 'A company name is required' );
		}

		$user_id = 0;
		// Attempt to create a user
		if ( isset( $_REQUEST['sa_client_email'] ) && $_REQUEST['sa_client_email'] != '' ) {
			$user_args = array(
				'user_login' => esc_html( $_REQUEST['sa_client_email'] ),
				'display_name' => isset( $_REQUEST['sa_client_name'] ) ? esc_html( $_REQUEST['sa_client_name'] ) : esc_html( $_REQUEST['sa_client_email'] ),
				'user_pass' => wp_generate_password(), // random password
				'user_email' => isset( $_REQUEST['sa_client_email'] ) ? esc_html( $_REQUEST['sa_client_email'] ) : '',
				'first_name' => isset( $_REQUEST['sa_client_first_name'] ) ? esc_html( $_REQUEST['sa_client_first_name'] ) : '',
				'last_name' => isset( $_REQUEST['sa_client_last_name'] ) ? esc_html( $_REQUEST['sa_client_last_name'] ) : '',
				'user_url' => isset( $_REQUEST['sa_client_website'] ) ? esc_html( $_REQUEST['sa_client_website'] ) : '',
			);
			$user_id = self::create_user( $user_args );
		}

		// Create the client
		$address = array(
			'street' => isset( $_REQUEST['sa_client_street'] ) ? esc_html( $_REQUEST['sa_client_street'] ) : '',
			'city' => isset( $_REQUEST['sa_client_city'] ) ? esc_html( $_REQUEST['sa_client_city'] ) : '',
			'zone' => isset( $_REQUEST['sa_client_zone'] ) ? esc_html( $_REQUEST['sa_client_zone'] ) : '',
			'postal_code' => isset( $_REQUEST['sa_client_postal_code'] ) ? esc_html( $_REQUEST['sa_client_postal_code'] ) : '',
			'country' => isset( $_REQUEST['sa_client_country'] ) ? esc_html( $_REQUEST['sa_client_country'] ) : '',
		);
		$args = array(
			'company_name' => isset( $_REQUEST['sa_client_name'] ) ? esc_html( $_REQUEST['sa_client_name'] ) : '',
			'website' => isset( $_REQUEST['sa_client_website'] ) ? esc_html( $_REQUEST['sa_client_website'] ) : '',
			'address' => $address,
			'user_id' => $user_id,
		);
		$client_id = SI_Client::new_client( $args );

		$response = array(
				'id' => $client_id,
				'title' => get_the_title( $client_id ),
			);

		header( 'Content-type: application/json' );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode( $response );
		exit();
	}

	/**
	 * AJAX submission
	 * @return
	 */
	public static function maybe_create_user() {

		// form maybe be serialized
		if ( isset( $_REQUEST['serialized_fields'] ) ) {
			foreach ( $_REQUEST['serialized_fields'] as $key => $data ) {
				$_REQUEST[ $data['name'] ] = $data['value'];
			}
		}

		if ( ! isset( $_REQUEST['sa_user_nonce'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['sa_user_nonce'];
		if ( ! wp_verify_nonce( $nonce, self::SUBMISSION_NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! current_user_can( 'publish_sprout_invoices' ) ) {
			self::ajax_fail( 'User cannot create new posts!' );
		}

		// Attempt to create a user
		if ( ! isset( $_REQUEST['sa_user_email'] ) || $_REQUEST['sa_user_email'] == '' ) {
			self::ajax_fail( 'An e-mail is required' );
		}

		$client = SI_Client::get_instance( $_REQUEST['sa_user_client_id'] );

		if ( ! is_a( $client, 'SI_Client' ) ) {
			self::ajax_fail( 'Client not found' );
		}

		$user_args = array(
			'user_login' => esc_html( $_REQUEST['sa_user_email'] ),
			'display_name' => isset( $_REQUEST['sa_user_display_name'] ) ? esc_html( $_REQUEST['sa_user_display_name'] ) : esc_html( $_REQUEST['sa_user_email'] ),
			'user_pass' => wp_generate_password(), // random password
			'user_email' => isset( $_REQUEST['sa_user_email'] ) ? esc_html( $_REQUEST['sa_user_email'] ) : '',
			'first_name' => isset( $_REQUEST['sa_user_first_name'] ) ? esc_html( $_REQUEST['sa_user_first_name'] ) : '',
			'last_name' => isset( $_REQUEST['sa_user_last_name'] ) ? esc_html( $_REQUEST['sa_user_last_name'] ) : '',
		);
		$user_id = self::create_user( $user_args );

		$client->add_associated_user( $user_id );
	}

	////////////////
	// AJAX View //
	////////////////

	/**
	 * Meta box view
	 * Abstracted to be called via AJAX
	 * @param int $client_id
	 *
	 */
	public static function submit_meta_box_view( $client_id = 0 ) {
		if ( ! current_user_can( 'edit_sprout_invoices' ) ) {
			self::ajax_fail( 'User cannot create new posts!' );
		}

		if ( ! $client_id && isset( $_REQUEST['client_id'] ) ) {
			$client_id = $_REQUEST['client_id'];
		}

		$client = SI_Client::get_instance( $client_id );

		if ( ! is_a( $client, 'SI_Client' ) ) {
			self::ajax_fail( 'Client not found.' );
		}

		global $post;
		$post = $client->get_post();
		print self::show_submit_meta_box( $client->get_post(), array() );
		exit();
	}

	//////////////
	// filters //
	//////////////



	/**
	 * Filtering the payment processor currency code option based on some predefined options.
	 * @param  string  $currency_code
	 * @param  integer $invoice_id
	 * @param  string  $payment_method
	 * @return string
	 */
	public static function maybe_change_currency_code( $currency_code = '', $invoice_id = 0, $payment_method = '' ) {
		$invoice = SI_Invoice::get_instance( $invoice_id );
		$client = $invoice->get_client();
		if ( ! is_wp_error( $client ) ) {
			$client_currency = $client->get_currency();
			if ( $client_currency != '' ) {
				$currency_code = $client_currency;
			}
		}
		return $currency_code;
	}


	//////////////
	// Utility //
	//////////////


	public static function add_link_to_admin_bar( $items ) {
		$items[] = array(
			'id' => 'edit_clients',
			'title' => __( 'Clients', 'sprout-invoices' ),
			'href' => admin_url( 'edit.php?post_type='.SI_Client::POST_TYPE ),
			'weight' => 0,
		);
		return $items;
	}

	////////////////
	// Admin Help //
	////////////////

	public static function help_sections() {
		add_action( 'load-edit.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-post.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-post-new.php', array( get_class(), 'help_tabs' ) );
	}

	public static function help_tabs() {
		$post_type = '';

		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( $screen_post_type == SI_Client::POST_TYPE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
				'id' => 'edit-clients',
				'title' => __( 'Manage Clients', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p>', __( 'The information here is used for estimates and invoices and includes settings to: Edit Company Name, Edit the company address, and Edit their website url.', 'sprout-invoices' ), __( '<b>Important note:</b> when clients are created new WordPress users are also created and given the “client” role. Creating users will allow for future functionality, i.e. client dashboards.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'associate-users',
				'title' => __( 'Associated Users', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'When clients are created a WP user is created and associated and clients are not limited to a single user. Not limited a client to a single user allows for you to have multiple points of contact at/for a company/client. Example, the recipients for sending estimate and invoice notifications are these associated users.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'client-history',
				'title' => __( 'Client History', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'Important points are shown in the client history and just like estimate and invoices private notes can be added for only you and other team members to see.', 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'client-invoices',
				'title' => __( 'Invoices and Estimates', 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'All invoices and estimates associated with the client are shown below the associated users option. This provides a quick way to jump to the record you need to see.', 'sprout-invoices' ) ),
			) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', __( 'For more information:', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/clients/', __( 'Documentation', 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', si_get_sa_link( 'https://sproutapps.co/support/' ), __( 'Support', 'sprout-invoices' ) )
			);
		}
	}
}
