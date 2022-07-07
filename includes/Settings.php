<?php if ( ! defined( 'WPINC' ) ) { die( "Don't mess with us." ); } 

if(!class_exists('FCMPN_Settings')) : class FCMPN_Settings {
	const OPTION_NAME = 'fcmpn_settings';
	private static $options;
	
	/*
     * Main construct
	 */
	private static $run;
	private function __construct(){
		add_action( 'admin_init', [&$this, 'register_settings'], 10, 0 );
		add_action( 'admin_menu', [&$this, 'admin_menu'], 90, 1 );
		add_action( 'admin_footer', [&$this, 'admin_footer'] );
		add_filter( 'plugin_row_meta', [&$this, 'action_links'], 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename(FCMPN_FILE), [&$this, 'plugin_action_links'] );
	}
	
	/*
     * Fix admin menus
	 */
	public function admin_footer () {
		if( in_array('fcmpn-subscriptions', [$_GET['taxonomy'] ?? NULL]) ) : ?>
<script>(function($){
	$('#menu-posts')
		.removeClass('wp-menu-open open-if-no-js wp-has-current-submenu')
		.addClass('wp-not-current-submenu')
		.find('.wp-menu-open')
		.removeClass('wp-menu-open open-if-no-js wp-has-current-submenu')
		.addClass('wp-not-current-submenu');
	
	$('#toplevel_page_push-notification-fcm')
		.addClass('wp-has-current-submenu wp-menu-open')
		.removeClass('wp-not-current-submenu')
		.find(' > a')
		.addClass('wp-has-current-submenu wp-menu-open')
		.removeClass('wp-not-current-submenu')
		.closest('#toplevel_page_push-notification-fcm')
		.find('.wp-submenu.wp-submenu-wrap > li:nth-child(3)')
		.addClass('current')
		.find('a')
		.addClass('current');
}(jQuery||window.jQuery));</script>
		<?php endif; }
	
	/*
     * Register admin menus
	 */
	public function admin_menu () {
		// Main navigation
		add_menu_page(
			__( 'FCM Push Notification', 'fcmpn' ),
			__( 'FCM Push Notification', 'fcmpn' ),
			'manage_options',
			'push-notification-fcm',
			[ &$this, 'devices' ],
			'dashicons-rest-api',
			80
		);
		// Subscriptions
		add_submenu_page(
			'push-notification-fcm',
			__( 'Subscriptions', 'fcmpn' ),
			__( 'Subscriptions', 'fcmpn' ),
			'manage_options',
			admin_url('edit-tags.php?taxonomy=fcmpn-subscriptions'),
			NULL,
			1
		);
		// Settings
		add_submenu_page(
			'push-notification-fcm',
			__( 'Settings', 'fcmpn' ),
			__( 'Settings', 'fcmpn' ),
			'manage_options',
			'push-notification-fcm-settings',
			[ &$this, 'settings' ],
			2
		);
		// Rename submenu
		global $submenu;
		$submenu['push-notification-fcm'][0][0] = __( 'Devices', 'fcmpn' );
	}
	
	/*
     * Settings
	 */
	public function settings () {
		FCM_Push_Notification::include_once( FCMPN_ROOT . '/admin/Settings.php' );
	}
	
	/*
     * Settings
	 */
	public function devices () {
		FCM_Push_Notification::include_once( FCMPN_ROOT . '/admin/Devices.php' );
	}
	
	/*
     * Register settings
	 */
	public function register_settings () {
		register_setting(
            'push_notification_fcm',
            self::OPTION_NAME,
            array( &$this, 'sanitize' )
        );
		
		
		
		add_settings_section(
            'pnfcm_api_settings', // ID
            __( 'Firebase Server (API) Settings', 'fcmpn' ), // Title
            array( &$this, 'section__firebase_api_settings' ), // Callback
            'push-notification-fcm' // Page
        );
		
		add_settings_field(
            'api_key', // ID
            __( 'Server (API) Key', 'fcmpn' ), // Title 
            array( $this, 'input__fib_api_key' ), // Callback
            'push-notification-fcm', // Page
            'pnfcm_api_settings' // Section           
        );
		
		
		
		add_settings_section(
            'pnfcm_plugin_settings', // ID
            __( 'Plugin settings', 'fcmpn' ), // Title
            array( &$this, 'section__pnfcm_plugin_settings' ), // Callback
            'push-notification-fcm' // Page
        );
		
		add_settings_field(
            'rest_api_key', // ID
            __( 'REST API Key', 'fcmpn' ), // Title 
            array( $this, 'input__rest_api_key' ), // Callback
            'push-notification-fcm', // Page
            'pnfcm_plugin_settings' // Section           
        );
		
		
		
		add_settings_section(
            'pnfcm_post_types_section', // ID
            __( 'Enable in Post Types', 'fcmpn' ), // Title
            array( &$this, 'section__pnfcm_post_types_section' ), // Callback
            'push-notification-fcm' // Page
        );
		
		add_settings_field(
            'post_types', // ID
            __( 'Choose Post Types', 'fcmpn' ), // Title 
            array( $this, 'input__post_types' ), // Callback
            'push-notification-fcm', // Page
            'pnfcm_post_types_section' // Section           
        );
		
		
		
		add_settings_section(
            'pnfcm_plugin_rest_section', // ID
            __( 'REST API Endpoints', 'fcmpn' ), // Title
            array( &$this, 'section__pnfcm_plugin_rest_section' ), // Callback
            'push-notification-fcm' // Page
        );
		
		add_settings_field(
            'rest_api_subscribe', // ID
            __( 'Subscribe', 'fcmpn' ), // Title 
            array( $this, 'input__rest_api_subscribe' ), // Callback
            'push-notification-fcm', // Page
            'pnfcm_plugin_rest_section' // Section           
        );
		
		add_settings_field(
            'rest_api_unsubscribe', // ID
            __( 'Unsubscribe', 'fcmpn' ), // Title 
            array( $this, 'input__rest_api_unsubscribe' ), // Callback
            'push-notification-fcm', // Page
            'pnfcm_plugin_rest_section' // Section           
        );
		
	}
	
	
	
	/*
     * Section: Firebase Server (API) Settings
	 */
	public function section__firebase_api_settings () {
		printf(
			'<p>%s</p>',
			__('An API key is a unique string that\'s used to route requests to your Firebase project when interacting with Firebase and Google services.', 'fcmpn')
		);
	}
	
	/*
     * Input: Firebase Server (API) Key
	 */
	public function input__fib_api_key () {
		
		if( defined('FCMPN_DEV_MODE') && FCMPN_DEV_MODE ) {
			$value = esc_attr( self::get('api_key', '') );
		} else {
			$value = esc_attr( self::get('api_key', '') ? '••••••••••••••••••••••••••••••••••' : '' );
		}
		printf(
            '<input type="text" id="%1$s_api_key" name="%1$s[api_key]" value="%2$s" style="width:95%%; max-width:50%%; min-width:100px;" />',
            esc_attr( self::OPTION_NAME ),
			$value
        );
	}
	
	
	
	
	/*
     * Section: Plugin settings
	 */
	public function section__pnfcm_plugin_settings () {
		printf(
			'<p>%s</p>',
			__('Important settings for this plugin.', 'fcmpn')
		);
	}
	
	/*
     * Input: Firebase Server (API) Key
	 */
	public function input__rest_api_key () {
		
		$rest_api_key = self::get('rest_api_key');
		if( !$rest_api_key ) {
			$sep = ['.',':','-', '_'];
			$rest_api_key = join($sep[ mt_rand(0, count($sep)-1) ], [
				self::generate_token(10) . $sep[ mt_rand(0, count($sep)-1) ] . self::generate_token( mt_rand(6,16) ),
				self::generate_token( mt_rand(16,24) ),
				self::generate_token( mt_rand(24,32) )
			]);
		}
		
		printf(
            '<input type="text" id="%1$s_rest_api_key" name="%1$s[rest_api_key]" value="%2$s" style="width:95%%; max-width:50%%; min-width:100px;" />',
            esc_attr( self::OPTION_NAME ),
			esc_attr( $rest_api_key )
        );
	}
	
	
	
	/*
     * Section: Plugin API REST Endpoints
	 */
	public function section__pnfcm_plugin_rest_section () {
		printf(
			'<p>%s</p>',
			__('In order to be able to send push notifications, you need to record the device ID in the site\'s database. Therefore, you have 2 REST endpoints to subscribe the device ID during app installation or unsubscribe the device ID during app deletion.', 'fcmpn')
		);
	}
	
	/*
     * Input: Subscribe API endpoint
	 */
	public function input__rest_api_subscribe () {
		printf(
			'<p><code style="padding:8px 10px;">%s</code></p><br>',
			esc_url( home_url('/wp-json/fcm/pn/subscribe') )
		);
		printf(
			'<p>
				<strong>%1$s</strong><br>
				<ul>
					<li><code>rest_api_key</code> %2$s</li>
					<li><code>user_email</code> %2$s</li>
					<li><code>device_token</code> %2$s</li>
					<li><code>subscription</code> %2$s - %4$s</li>
					<li><code>device_name</code> %3$s</li>
					<li><code>os_version</code> %3$s</li>
				</ul>
			</p>',
			__('Parameters:', 'fcmpn'),
			__('(required)', 'fcmpn'),
			__('(optional)', 'fcmpn'),
			__('This would be the category in which the device is registered, if there is no category exists in WordPress it’ll be created automatically.', 'fcmpn')
		);
		printf(
			'<p>
				<strong>%1$s</strong><br>
				<pre style="display: block; background: antiquewhite; width: 95%%; padding: 10px 15px;">{
	"error": false,
	"message": "%2$s",
	"subscription_id": 123
}</pre>
			</p>',
			__('Returns:', 'fcmpn'),
			esc_html__('Device token registered', 'fcmpn')
		);
	}
	
	/*
     * Input: Unsubscribe API endpoint
	 */
	public function input__rest_api_unsubscribe () {
		printf(
			'<p><code style="padding:8px 10px;">%s</code></p><br>',
			esc_url( home_url('/wp-json/fcm/pn/unsubscribe') )
		);
		printf(
			'<p>
				<strong>%1$s</strong><br>
				<ul>
					<li><code>rest_api_key</code> %2$s</li>
					<li><code>device_token</code> %2$s</li>
				</ul>
			</p>',
			__('Parameters:', 'fcmpn'),
			__('(required)', 'fcmpn')
		);
		printf(
			'<p>
				<strong>%1$s</strong><br>
				<pre style="display: block; background: antiquewhite; width: 95%%; padding: 10px 15px;">{
	"error": false,
	"message": "%2$s"
}</pre>
			</p>',
			__('Returns:', 'fcmpn'),
			esc_html__('The device token was successfully removed', 'fcmpn')
		);
	}
	
	

	
	/*
     * Section: Enable in Post Types
	 */
	public function section__pnfcm_post_types_section () {
		printf(
			'<p>%s</p>',
			__('Allow notifications in selected post types', 'fcmpn')
		);
	}
	
	/*
     * Input: Choose Post Types
	 */
	public function input__post_types () {
		$post_types = get_post_types( [
		   'publicly_queryable'   => true
		], 'objects' );
		
		if( isset($post_types['attachment']) ) {
			unset($post_types['attachment']);
		}
		
		$selected = self::get('post_types', ['post']);
		
		$i = 0;
		foreach($post_types as $post_type=>$post_type_obj) {
			printf(
				'<label for="%2$s_post_types_%1$d"><input type="checkbox" id="%2$s_post_types_%1$d" name="%2$s[post_types][%1$d]" value="%3$s"%5$s /> %4$s</label><br>',
				$i,
				esc_attr( self::OPTION_NAME ),
				esc_attr( $post_type ),
				esc_html( $post_type_obj->label ),
				(in_array($post_type, $selected) ? ' checked="checked"' : NULL)
			);
			++$i;
		}
	}
	
	
	
	/*
     * Get single option
     */
    public static function get( $name = NULL, $default = NULL )
    {
		if( $name ) {
			return (self::getAll()[$name] ?? $default);
		}
		
		return $default;
	}
	
	/*
     * Get all options
     */
    public static function getAll()
    {
		if( !self::$options ) {
			self::$options = get_option( self::OPTION_NAME );
		}
		
		return self::$options;
	}
	
	/*
     * Sanitize each setting field as needed
     */
    public function sanitize( $input )
    {
        $new_input = [
			'api_key' => NULL,
			'rest_api_key' => NULL,
			'post_types' => []
		];

		if( isset($input['api_key']) ) {
			if( strpos($input['api_key'], '••••••') !== false ) {
				$new_input['api_key'] = self::get('api_key');
			} else {
				$new_input['api_key'] = sanitize_text_field($input['api_key']);
			}
		}
		
		if( isset($input['rest_api_key']) ) {
			if( empty($input['rest_api_key']) ) {
				$sep = ['.',':','-', '_'];
				$rest_api_key = join($sep[ mt_rand(0, count($sep)-1) ], [
					self::generate_token(10) . $sep[ mt_rand(0, count($sep)-1) ] . self::generate_token( mt_rand(6,16) ),
					self::generate_token( mt_rand(16,24) ),
					self::generate_token( mt_rand(24,32) )
				]);
				$new_input['rest_api_key'] = sanitize_text_field($rest_api_key);
			} else {
				$new_input['rest_api_key'] = sanitize_text_field($input['rest_api_key']);
			}
		}
		
		if( isset($input['post_types']) ) {
			$new_input['post_types'] = array_map('sanitize_text_field', $input['post_types']);
		}
		
        return $new_input;
    }
	
	/*
	 * Plugin action links after details
	 */
	public function action_links( $links, $file )
	{
		if( plugin_basename( FCMPN_FILE ) == $file )
		{			
			$links['registar_nestalih_donate'] = sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" class="fcmpn-plugins-action-donation">%s</a>',
				esc_url( 'https://www.buymeacoffee.com/ivijanstefan' ),
				esc_html__( 'Donate', 'fcmpn' )
			);
			/*
			$links['registar_nestalih_vote'] = sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" class="fcmpn-plugins-action-vote" title="%s"><span style="color:#ffa000; font-size: 15px; bottom: -1px; position: relative;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> %s</a>',
				esc_url( 'https://wordpress.org/support/plugin/push-notification-fcm/reviews/?filter=5' ).'#new-post',
				esc_attr__( 'Give us five if you like!', 'fcmpn' ),
				esc_html__( '5 Stars?', 'fcmpn' )
			);
			*/
		}
		
		return $links;
	}
	
	/*
	 * WP Hidden links by plugin setting page
	 */
	public function plugin_action_links( $links ) {
		$links = array_merge( $links, [
			'settings'	=> sprintf(
				'<a href="' . self_admin_url( 'admin.php?page=push-notification-fcm-settings' ) . '" class="fcmpn-plugins-action-settings">%s</a>', 
				esc_html__( 'Settings', 'fcmpn' )
			),
			'devices'	=> sprintf(
				'<a href="' . self_admin_url( 'admin.php?page=push-notification-fcm' ) . '" class="fcmpn-plugins-action-devices">%s</a>', 
				esc_html__( 'Devices', 'fcmpn' )
			)
		] );
		return $links;
	}
	
	/* 
	 * Generate unique token
	 * @author        Ivijan-Stefan Stipic
	*/
	public static function generate_token(int $length=16){
		if(function_exists('openssl_random_pseudo_bytes') || function_exists('random_bytes'))
		{
			if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
				return substr(str_rot13(bin2hex(random_bytes(ceil($length * 2)))), 0, $length);
			} else {
				return substr(str_rot13(bin2hex(openssl_random_pseudo_bytes(ceil($length * 2)))), 0, $length);
			}
		}
		else
		{
			return substr(str_replace(['.',' ','_'],mt_rand(1000,9999),uniqid('t'.microtime())), 0, $length);
		}
	}

	/*
     * Run the plugin
	 */
	public static function instance(){
		if(!self::$run) {
			self::$run = new self;
		}
		
		return self::$run;
	}
} endif;