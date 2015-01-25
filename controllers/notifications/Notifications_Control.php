<?php

/**
 * Send notifications, apply shortcodes and create management screen.
 *
 * @package Sprout_Invoice
 * @subpackage Notification
 */
class SI_Notifications_Control extends SI_Controller {
	const SETTINGS_PAGE = 'notifications';
	const RECORD = 'si_notification';
	const META_BOX_PREFIX = 'si_notification_shortcodes_';
	const NOTIFICATIONS_OPTION_NAME = 'si_notifications';
	const EMAIL_FROM_NAME = 'si_notification_name';
	const EMAIL_FROM_EMAIL = 'si_notification_email';
	const EMAIL_FORMAT = 'si_notification_format';
	const NOTIFICATION_SUB_OPTION = 'si_subscription_notifications';

	private static $notification_from_name;
	private static $notification_from_email;
	private static $notification_format;

	public static $notifications;
	protected static $shortcodes;
	private static $data;

	public static function init() {
		// Store options
		self::$notification_from_name = get_option( self::EMAIL_FROM_NAME, get_bloginfo( 'name' ) );
		self::$notification_from_email = get_option( self::EMAIL_FROM_EMAIL, get_bloginfo( 'admin_email' ) );
		self::$notification_format = get_option( self::EMAIL_FORMAT, 'TEXT' );

		// Default notifications
		add_action( 'init', array( __CLASS__, 'notifications_and_shortcodes' ), 5 );

		// register settings
		self::register_settings();

		// Meta boxes
		add_action( 'admin_init', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'do_meta_boxes', array( __CLASS__, 'modify_meta_boxes' ) );

		// Admin js for notification management
		add_action( 'load-post.php', array( __CLASS__, 'queue_notification_js' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'queue_notification_js' ) );

		// Redirect away from WP generated post_type table
		add_action( 'current_screen', array( __CLASS__, 'maybe_redirect_away_from_notification_admin_table' ) );

		// Create default notifications
		add_action( 'admin_init', array( __CLASS__, 'create_notifications' ) );

		// Help Sections
		add_action( 'admin_menu', array( get_class(), 'help_sections' ) );

	}

	////////////
	// admin //
	////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Option page
		$args = array(
			'slug' => self::SETTINGS_PAGE,
			'title' => self::__( 'Notifications' ),
			'menu_title' => self::__( 'Notifications' ),
			'weight' => 20,
			'reset' => FALSE,
			'section' => 'settings',
			'tab_only' => TRUE,
			'callback' => array( __CLASS__, 'display_table' )
			);
		do_action( 'sprout_settings_page', $args );

		// Settings
		$settings = array(
			'notifications' => array(
				'title' => self::__('Notification Settings'),
				'weight' => 30,
				'tab' => 'settings',
				'settings' => array(
					self::EMAIL_FROM_NAME => array(
						'label' => self::__( 'From name' ),
						'option' => array(
							'type' => 'text',
							'default' => self::$notification_from_name
							)
						),
					self::EMAIL_FROM_EMAIL => array(
						'label' => self::__( 'From email' ),
						'option' => array(
							'type' => 'text',
							'default' => self::$notification_from_email
							)
						),
					self::EMAIL_FORMAT => array(
						'label' => self::__( 'Email format' ),
						'option' => array(
							'type' => 'select',
							'options' => array(
									'HTML' => self::__( 'HTML' ),
									'TEXT' => self::__( 'Plain Text' )
								),
							'default' => self::$notification_format,
							'description' => self::__('Default notifications are in plain text. If set to HTML, custom HTML notifications are required.')
							)
						)
					)
				)
			);
		do_action( 'sprout_settings', $settings );
	}

	public static function get_admin_page( $prefixed = TRUE ) {
		return ( $prefixed ) ? self::TEXT_DOMAIN . '/' . self::SETTINGS_PAGE : self::SETTINGS_PAGE ;
	}

	//////////////
	// Utility //
	//////////////

	public static function html_notifications() {
		return ( self::$notification_format == 'HTML' );
	}

	public static function notifications_and_shortcodes() {
		if ( !isset( self::$notifications ) ) {
			// Notification types include a name and a list of shortcodes
			$default_notifications = array(); // defaults are in the hooks class
			self::$notifications = apply_filters( 'sprout_notifications', $default_notifications );
		}
		if ( !isset( self::$shortcodes ) ) {
			// Notification shortcodes include the code, a description, and a callback
			// Most shortcodes should be defined by a different controller using the 'si_notification_shortcodes' filter
			$default_shortcodes = array(); // Default shortcodes are in the hooks class
			self::$shortcodes = apply_filters( 'sprout_notification_shortcodes', $default_shortcodes );
		}
	}

	/**
	 * Create the default notifications
	 * @return
	 */
	public static function create_notifications() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'sprout-apps/settings' ) {
			foreach ( self::$notifications as $notification_id => $data ) {
				$notification = self::get_notification_instance( $notification_id );
				if ( is_null( $notification ) ) {
					$post_id = wp_insert_post( array(
							'post_status' => 'publish',
							'post_type' => SI_Notification::POST_TYPE,
							'post_title' => $data['default_title'],
							'post_content' => $data['default_content']
						) );
					$notification = SI_Notification::get_instance( $post_id );
					self::save_meta_box_notification_submit( $post_id, $notification->get_post(), array(), $notification_id );
					if ( isset( $data['default_disabled'] ) && $data['default_disabled'] ) {
						$notification->set_disabled( 'TRUE' );
					}
				}
				// Don't allow for a notification to enabled if specifically shouldn't
				if ( isset( $data['always_disabled'] ) && $data['always_disabled'] ) {
					$notification->set_disabled( 'TRUE' );
				}
			}
		}
	}

	/////////////////
	// Meta boxes //
	/////////////////

	/**
	 * enqueue admin js for notification management
	 * @return  
	 */
	public static function queue_notification_js() {
		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : -1;
		if ( ( isset( $_GET['post_type'] ) && SI_Notification::POST_TYPE == $_GET['post_type'] ) || SI_Notification::POST_TYPE == get_post_type( $post_id ) ) {
			wp_register_script( 'si_admin_notifications', SI_URL . '/resources/admin/js/notification.js', array( 'jquery' ), self::SI_VERSION );
			wp_enqueue_script( 'si_admin_notifications' );
		}
	}

	/**
	 * Regsiter meta boxes for notification editing.
	 * @return 
	 */
	public static function register_meta_boxes() {
		// notification specific
		$args = array(
				'si_notification_submit' => array(
					'title' => 'Update',
					'show_callback' => array( __CLASS__, 'show_submit_meta_box' ),
					'save_callback' => array( __CLASS__, 'save_meta_box_notification_submit' ),
					'context' => 'side',
					'priority' => 'high'
				)
			);

		foreach ( self::$notifications as $notification => $data ) {
			$name = ( isset( $data['name'] ) ) ? $data['name'] : self::__('N/A') ;
			$args[self::META_BOX_PREFIX . $notification] = array(
					'title' => sprintf( self::__( '%s Shortcodes' ), $name ),
					'show_callback' => array( __CLASS__, 'show_shortcode_meta_box' )
				);
		}
		do_action( 'sprout_meta_box', $args, SI_Notification::POST_TYPE );
	}

	/**
	 * Remove publish box and add something custom for notifications
	 * @param  string $post_type 
	 * @return             
	 */
	public static function modify_meta_boxes( $post_type ) {	
		remove_meta_box( 'submitdiv', SI_Notification::POST_TYPE, 'side');
		remove_meta_box( 'slugdiv', SI_Notification::POST_TYPE, 'normal' );
	}

	/**
	 * View for notification shortcodes
	 * @param  SI_Notification $notification 
	 * @param  WP_Post $post         
	 * @param  array $metabox      
	 * @return                
	 */
	public static function show_shortcode_meta_box( $post, $metabox ) {
		$notification = SI_Notification::get_instance( $post->ID );
		$id = preg_replace( '/^' . preg_quote( self::META_BOX_PREFIX ) . '/', '', $metabox['id'] );
		if ( isset( self::$notifications[$id] ) ) {
			self::load_view( 'admin/meta-boxes/notifications/shortcodes', array(
					'id' => $id,
					'type' => self::$notifications[$id],
					'shortcodes' => self::$shortcodes
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
		$notification = SI_Notification::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/notifications/submit', array(
				'id' => $post->ID,
				'notification_types' => self::$notifications,
				'notifications_option' => get_option( self::NOTIFICATIONS_OPTION_NAME, array() ),
				'post' => $post,
				'disabled' => $notification->get_disabled()
			), FALSE );
	}

	/**
	 * main cllback for saving the notification
	 * @param  object  $notification      
	 * @param  string $notification_id 
	 * @return                      
	 */
	public static function save_meta_box_notification_submit( $post_id, $post, $callback_args, $notification_type = NULL ) {
		if ( $notification_type === NULL && isset( $_POST['notification_type'] ) ) {
			$notification_type = $_POST['notification_type'];
		}

		if ( is_null( $post_id ) ) {
			if ( isset( $_POST['ID'] ) ) {
				$post_id = $_POST['ID'];
			}
		}
		if ( get_post_type( $post_id ) != SI_Notification::POST_TYPE ) {
			return;
		}

		// Remove any existing notification types that point to the post currently being saved
		$notification_set = get_option( self::NOTIFICATIONS_OPTION_NAME, array() );
		foreach ( $notification_set as $op_type => $note_id ) {
			if ( $note_id == $post_id ) {
				unset( $notification_set[$post_id] );
			}
		}

		if ( isset( self::$notifications[$notification_type] ) ) {

			// Associate this post with the given notification type
			$notification_set[$notification_type] = $post_id;
			update_option( self::NOTIFICATIONS_OPTION_NAME, $notification_set );
		}

		$notification = SI_Notification::get_instance( $post_id );

		// Mark as disabled or not.
		if ( isset( $_POST['notification_type_disabled'] ) && $_POST['notification_type_disabled'] == 'TRUE' ) {
			$notification->set_disabled( 'TRUE' );
		} else {
			$notification->set_disabled( 0 );
		}
	}

	//////////////
	// Utility //
	//////////////

	/**
	 * Get notification instance.
	 * @param  string $notification the slug for the notification
	 * @return SI_Notification/FALSE            
	 */
	public static function get_notification_instance( $notification ) {
		if ( isset( self::$notifications[$notification] ) ) {
			$notifications = get_option( self::NOTIFICATIONS_OPTION_NAME );
			if ( isset( $notifications[$notification] ) ) {
				$notification_id = $notifications[$notification];
				$notification = SI_Notification::get_instance( $notification_id );
				if ( $notification != null ) {
					$post = $notification->get_post();

					// Don't return the notification if isn't published (excludes deleted, draft, and future posts)
					if ( 'publish' == $post->post_status ) {
						return $notification;
					}
				}
			}
		}
		return NULL; // return null and not a boolean for the sake of validity checks elsewhere
	}

	/**
	 * Is the notification disabled.
	 * @param  string  $notification_name 
	 * @return boolean                    
	 */
	public static function is_disabled( $notification_name ) {
		$notification = self::get_notification_instance( $notification_name );
		if ( is_a( $notification, 'SI_Notification' ) ) {
			return $notification->is_disabled();
		}
		return TRUE;
	}

	/**
	 * Get the notification subject from post title
	 * @param  string $notification_name 
	 * @param  array $data              
	 * @return string                  
	 */
	public static function get_notification_instance_subject( $notification_name = '', $data = array() ) {
		self::$data = $data;
		$title = '';
		$notification = self::get_notification_instance( $notification_name );
		if ( !is_null( $notification ) ) {
			$notification_post = $notification->get_post();
			$title = $notification_post->post_title;
			$title = self::do_shortcodes( $notification_name, $title );
			return apply_filters( 'si_get_notification_instance_subject', $title, $notification_name, $data );
		} elseif ( isset( self::$notifications[$notification_name] ) && isset( self::$notifications[$notification_name]['default_title'] ) ) {
			$title = self::$notifications[$notification_name]['default_title'];
			$title = self::do_shortcodes( $notification_name, $title );
			return apply_filters( 'si_get_notification_instance_subject', $title, $notification_name, $data );
		}

		return apply_filters( 'si_get_notification_instance_subject', '', $notification_name, $data );
	}

	/**
	 * Get the content for the notification.
	 * @param  string $notification_name 
	 * @param  array  $data              
	 * @return string
	 */
	public static function get_notification_instance_content( $notification_name = '', $data = array() ) {
		self::$data = $data;
		$content = '';
		$notification = self::get_notification_instance( $notification_name );
		if ( !is_null( $notification ) ) {
			$notification_post = $notification->get_post();
			$content = $notification_post->post_content;
			$content = self::do_shortcodes( $notification_name, $content );
			return apply_filters( 'si_get_notification_instance_content', $content, $notification_name, $data );
		} elseif ( isset( self::$notifications[$notification_name] ) && isset( self::$notifications[$notification_name]['default_content'] ) ) {
			$content = self::$notifications[$notification_name]['default_content'];
			$content = self::do_shortcodes( $notification_name, $content );
			return apply_filters( 'si_get_notification_instance_content', $content, $notification_name, $data );
		}
		return apply_filters( 'si_get_notification_instance_content', '', $notification_name, $data );
	}

	/**
	 * Send the notification
	 * @param  string $notification_name 
	 * @param  array  $data              
	 * @param  string $to                
	 * @param  string $from_email        
	 * @param  string $from_name         
	 * @param  bool $html              
	 * @return 
	 */
	public static function send_notification( $notification_name, $data = array(), $to, $from_email = null, $from_name = null, $html = null ) {
		// The options registered in the notification type array
		$registered_notification = self::$notifications[$notification_name];

		// don't send disabled notifications
		if ( apply_filters( 'suppress_notifications', FALSE ) || self::is_disabled( $notification_name ) ) {
			return;
		}

		// So shortcode handlers know whether the email is being sent as html or plaintext
		if ( null == $html ) {
			$html = ( self::$notification_format == 'HTML' ) ? TRUE : FALSE ;
		}
		$data['html'] = $html;

		$notification_title = self::get_notification_instance_subject( $notification_name, $data );
		$notification_content = self::get_notification_instance_content( $notification_name, $data );

		// Don't send notifications with empty titles or content
		if ( empty( $notification_title ) || empty( $notification_content ) ) {
			return;
		}

		// don't send a notification that has already been sent
		if ( apply_filters( 'si_was_notification_sent_check', TRUE ) && self::was_notification_sent( $notification_name, $data, $to, $notification_content ) ) {
			do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' - Notifications: Message Already Sent', $data );
			return;
		}

		// Plugin addons can suppress specific notifications by filtering 'si_suppress_notification'
		$suppress_notification = apply_filters( 'si_suppress_notification', FALSE, $notification_name, $data, $from_email, $from_name, $html );
		if ( $suppress_notification ) {
			return;
		}

		$from_email = ( null == $from_email ) ? self::$notification_from_email : $from_email ;
		$from_name = ( null == $from_name ) ? self::$notification_from_name : $from_name ;

		if ( $html ) {
			$headers = array(
				"From: ".$from_name." <".$from_email.">",
				"Content-Type: text/html"
			);
		} else {
			$headers = array(
				"From: ".$from_name." <".$from_email.">",
			);
		}
		$headers = implode( "\r\n", $headers ) . "\r\n";
		$filtered_headers = apply_filters( 'si_notification_headers', $headers, $notification_name, $data, $from_email, $from_name, $html );
		
		// Use the wp_email function
		$sent = wp_mail( $to, $notification_title, $notification_content, $filtered_headers );
		
		if ( $sent != false ) {
			// Create notification record
			self::notification_record( $notification_name, $data, $to, $notification_title, $notification_content );
		}
		else {
			do_action( 'si_error', 'FAILED NOTIFICATION - Attempted e-mail: ' . $to, $data );
			return FALSE;
		}

		// Mark the notification as sent.
		self::mark_notification_sent( $notification_name, $data, $to );
	}

	/**
	 * Create a record that a notification was sent.
	 * @param  string $notification_name    
	 * @param  array $data                 
	 * @param  string $to                   
	 * @param  string $notification_title   
	 * @param  string $notification_content 
	 * @return null                       
	 */
	public static function notification_record( $notification_name, $data, $to, $notification_title, $notification_content ) {
		$associated_record = 0;
		if ( isset( $data['estimate'] ) && is_a( $data['estimate'], 'SI_Estimate' ) ) {
			$associated_record = $data['estimate']->get_id();
		}
		if ( isset( $data['invoice'] ) && is_a( $data['invoice'], 'SI_Invoice' ) ) {
			$associated_record = $data['invoice']->get_id();
		}
		$content = '';
		$content .= "<b>" . $notification_title . "</b>\r\n\r\n";
		$content .= $notification_content;
		do_action( 'si_new_record', 
				$content, // content
				self::RECORD, // type slug
				$associated_record, // post id
				sprintf( si__('Notification sent to %s.'), esc_html($to) ), // title
				0, // user id
				FALSE // don't encode
				);
	}

	/**
	 * Log that a notification as sent
	 *
	 * @static
	 * @param string  $notification_name
	 * @param array   $data
	 * @param string  $to
	 * @return
	 */
	public static function mark_notification_sent( $notification_name, $data, $to ) {
		global $blog_id;
		$user_id = self::get_notification_instance_user_id( $to, $data );
		if ( !$user_id ) {
			return; // don't know who it is, so we can't log it
		}
		add_user_meta( $user_id, $blog_id.'_si_notification-'.$notification_name, self::get_hash( $data ) );
	}

	/**
	 *
	 *
	 * @static
	 * @param string  $notification_name
	 * @param array   $data
	 * @param string  $to
	 * @return bool Whether this notification was previously sent
	 */
	public static function was_notification_sent( $notification_name, $data, $to, $notification_content = '' ) {
		global $blog_id;
		$user_id = self::get_notification_instance_user_id( $to, $data );
		if ( !$user_id ) {
			return FALSE;
		}
		if ( $notification_content != '' ) {
			$data['content'] = $notification_content;
		}
		$meta = get_user_meta( $user_id, $blog_id.'_si_notification-'.$notification_name, FALSE );
		if ( in_array( self::get_hash( $data ), $meta ) ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Convert the data array into a hash
	 *
	 * @static
	 * @param array   $data
	 * @return string
	 */
	private static function get_hash( $data ) {
		foreach ( $data as $key => $value ) {
			// many objects can't be serialized, so convert them to something else
			if ( is_object( $value ) && method_exists( $value, 'get_id' ) ) {
				$data[$key] = array( 'class' => get_class( $value ), 'id' => $value->get_id() );
			}
		}
		return md5( serialize( $data ) );
	}

	/**
	 * Utility function to get the user ID that the given information would be sent to.
	 *
	 * @static
	 * @param string  $to   The user's email address
	 * @param array   $data
	 * @return int
	 */
	protected static function get_notification_instance_user_id( $to = '', $data = array() ) {
		$user_id = 0;
		// first, see if it's stored in the data
		if ( isset( $data['user_id'] ) ) {
			$user_id = $data['user_id'];
		} elseif ( isset( $data['user'] ) ) {
			if ( is_numeric( $data['user'] ) ) {
				$user_id = $data['user'];
			} elseif ( is_object( $data['user'] ) && isset( $data['user']->ID ) ) {
				$user_id = $data['user']->ID;
			}
		}
		if ( isset( $data['user'] ) && is_a( $data['user'], 'WP_User' ) ) {
			return $data['user']->ID;
		}
		// then try to determine based on email address
		if ( !$user_id ) {
			$email = ( isset( $data['user_email'] ) && $data['user_email'] != '' ) ? $data['user_email'] : $to ;
			$user = get_user_by( 'email', $to );
			if ( $user && isset( $user->ID ) ) {
				$user_id = $user->ID;
			}
		}

		return $user_id;
	}

	public static function get_user_email( $user = false ) {
		if ( false == $user ) {
			$user = get_current_user_id();
		}
		if ( is_numeric( $user ) ) {
			$user = get_userdata( $user );
		}
		if ( !is_a( $user, 'WP_User' ) ) {
			do_action( 'si_error', __CLASS__ . '::' . __FUNCTION__ . ' - Get User Email FAILED', $user );
			return FALSE;
		}
		$user_email = $user->user_email;
		$name = $user->first_name . ' ' . $user->last_name;

		if ( $name == ' ' ) {
			$to = $user_email;
		} else {
			$to = "$name <$user_email>";
		}

		// compensate for strange bug where the name came through but the email wasn't.
		if ( strpos( $to, $user_email ) !== false ) {
			$to = $user_email;
		}

		return $to;
	}

	/**
	 * Get associated client user ids
	 * @param  object $doc Invoice/Estimate
	 * @return array      
	 */
	public static function get_document_recipients( $doc ) {
		$client = $doc->get_client();
		$client_users = array();
		// get the user ids associated with this doc.
		if ( !is_wp_error( $client ) && is_a( $client, 'SI_Client' ) ) {
			$client_users = $client->get_associated_users();
		}
		else { // no client associated
			$user_id = $doc->get_user_id(); // check to see if a user id is associated
			if ( $user_id ) {
				$client_users = array( $user_id );
			}
		}
		if ( is_wp_error( $client_users ) || !is_array( $client_users ) ) {
			do_action( 'si_error', 'get_document_recipients ERROR', $client_users );
		}
		return $client_users;
	}

	//////////////////////////
	// Shortcode callbacks //
	//////////////////////////


	/**
	 * Add the shortcodes via the appropriate WP actions, apply the shortcodes to the content and
	 * remove the shortcodes after the content has been filtered.
	 * 
	 * @param  string $notification_name 
	 * @param  string $content           
	 * @return string                    
	 */
	public static function do_shortcodes( $notification_name, $content ) {
		foreach ( self::$notifications[$notification_name]['shortcodes'] as $shortcode ) {
			add_shortcode( $shortcode, array( __CLASS__, 'notification_shortcode' ) );
		}
		$content = do_shortcode( $content );
		foreach ( self::$notifications[$notification_name]['shortcodes'] as $shortcode ) {
			remove_shortcode( $shortcode );
		}
		return $content;
	}

	/**
	 * Shortcode callbacks.
	 * @param  array $atts    
	 * @param  string $content 
	 * @param  string $code    
	 * @param  array $data    
	 * @return string          filtered content
	 */
	public static function notification_shortcode( $atts, $content, $code ) {
		if ( isset( self::$shortcodes[$code] ) ) {
			$shortcode = call_user_func( self::$shortcodes[$code]['callback'], $atts, $content, $code, self::$data );
			return apply_filters( 'si_notification_shortcode_'.$code, $shortcode, $atts, $content, $code, self::$data );

		}
		return '';
	}

	////////////
	// Table //
	////////////

	public static function display_table() {
		//Create an instance of our package class...
		$wp_list_table = new SI_Notifications_Table();
		//Fetch, prepare, sort, and filter our data...
		$wp_list_table->prepare_items();
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2 class="nav-tab-wrapper">
			<?php do_action( 'sprout_settings_tabs' ); ?>
		</h2>

		<form id="payments-filter" method="get">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<?php $wp_list_table->display() ?>
		</form>
	</div>
	<?php
	}

	public static function maybe_redirect_away_from_notification_admin_table( $current_screen ) {
		if ( isset( $_GET['noredirect'] ) ) {
			return;
		}
		if ( SI_Notification::POST_TYPE == $current_screen->post_type && 'edit' == $current_screen->base ) {
			wp_redirect( admin_url( 'admin.php?page=' . self::get_admin_page() ) );
			exit();
		}
	}

	////////////////
	// Admin Help //
	////////////////

	public static function help_sections() {
		add_action( 'load-edit.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-post.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-sprout-apps_page_sprout-apps/settings', array( __CLASS__, 'help_tabs' ) );
	}

	public static function help_tabs() {
		$post_type = '';
		if ( isset( $_GET['tab'] ) && $_GET['tab'] == self::SETTINGS_PAGE ) {
			$post_type = SI_Notification::POST_TYPE;
		}
		if ( $post_type == '' && isset( $_GET['post'] ) ) {
			$post_type = get_post_type( $_GET['post'] );
		}
		if ( $post_type == SI_Notification::POST_TYPE ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
					'id' => 'notification-customizations',
					'title' => self::__( 'About Notifications' ),
					'content' => sprintf( '<p>%s</p><p>%s</p>', self::__('Notifications include the emails sent to you and your clients, including responses to prospective clients after submitting an estimate request.'), self::__('Each one of your notifications can be customized; hover over the notification you want and click the edit link.') ),
				) );

			$screen->add_help_tab( array(
					'id' => 'notification-disable',
					'title' => self::__( 'Disable Notifications' ),
					'content' => sprintf( '<p>%s</p>', self::__('The notifications edit screen will have an option next to the "Update" button to disable the notification from being sent.') ),
				) );

			$screen->add_help_tab( array(
					'id' => 'notification-editing',
					'title' => self::__( 'Notification Editing' ),
					'content' => sprintf( '<p>%s</p><p>%s</p><p>%s</p><p>%s</p>', self::__('<b>Subject</b> - The first input is for the notifications subject. If the notification is an e-mail than it would be subject line for that e-mail notification.'), self::__('<b>Message Body</b> - The main editor is the notification body. Use the available shortcodes to have dynamic information included when the notification is received. Make sure to change the Notification Setting if HTML formatting is added to your notifications.'), self::__('<b>Shortcodes</b> – A list of shortcodes is provided with descriptions for each.'), self::__('<b>Update</b> - The select list can be used if you want to change the current notification to a different type; it’s recommended you go to the notification you want to edit instead of using this option. The Disabled option available to prevent this notification from sending.') ),
				) );

			$screen->add_help_tab( array(
					'id' => 'notification-advanced',
					'title' => self::__( 'Advanced' ),
					'content' => sprintf( '<p><b>HTML Emails</b> - Enable HTML notifications within the <a href="%s">General Settings</a> page. Make sure to change use HTML on all notifications.</p>', admin_url('admin.php?page=sprout-apps/settings') ),
				) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', self::__('For more information:') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/notifications/', self::__('Documentation') ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', self::__('Support') )
			);
		}
	}

}