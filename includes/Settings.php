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
		add_filter( 'set-screen-option', [&$this, 'set_screen_option'], 10, 3 );
		add_filter( "manage_edit-fcmpn-subscriptions_columns", [&$this, 'custom_column_header'], 30, 1);
		add_action( "manage_fcmpn-subscriptions_custom_column", [&$this, 'custom_column_content'], 10, 3);
		add_action( 'admin_enqueue_scripts', [&$this, 'admin_enqueue_scripts'], 10, 1 );
		
		add_action( 'fcmpn-settings-sidebar', [&$this, 'sidebar_settings__related_plugins'], 10, 0 );
	}
	
	public function sidebar_settings__related_plugins () { ?>
<div class="postbox" id="fcmpn-related-plugins">
	<h3 class="hndle" style="margin-bottom:0;padding-bottom:0;"><span><?php _e('Recommended Plugins', 'fcmpn'); ?></span></h3><hr>
	<div class="inside flex">

		<table border="0" cellpadding="0" cellspacing="15">
		<?php
			$plugins = apply_filters('fcmpn-settings-sidebar-recommended-plugins', [
				'cf-geoplugin',
				'serbian-transliteration',
				'easy-auto-reload',
				'registar-nestalih'
			]);
			
			foreach($plugins as $plugin) :
				$plugin = self::plugin_info(
					[
						'active_installs' => true,
						'rating' => true,
						'icons' => true
					],
					$plugin
				);
				
				$rating = floatval(5*($plugin->rating/100));
			//	echo '<pre>', var_dump($plugin), '</pre>';
		?>
			<tr>
				<td width="30%">
					<a href="<?php echo esc_url($plugin->icons['1x']); ?>">
						<img width="64" height="64" loading="lazy" class="img-responsive" srcset="<?php echo esc_url($plugin->icons['1x']); ?>, <?php echo esc_url($plugin->icons['2x']); ?> 2x" src="<?php echo esc_url($plugin->icons['1x']); ?>" />
					</a>
				</td>
				<td>
					<a href="//wordpress.org/plugins/<?php echo esc_attr($plugin->slug); ?>/" class="title"><?php echo esc_html( apply_filters('widget_title', $plugin->name) ); ?></a>
					<?php if($plugin->active_installs > 10) : ?>
						<br><span class="active-install"><?php
							printf(
								__('Active Installs: %s', 'fcmpn'),
								'<b>+' . esc_html($plugin->active_installs) . '</b>'
							);
						?></span>
					<?php else: ?>
						<br><span class="active-install"><?php _e('Active Installs fewer than 10', 'fcmpn'); ?></span>
					<?php endif; ?>
					
					<?php if($rating) : ?>
						<br><span class="rating">
						<?php
							printf(
								__('Rating: %s', 'fcmpn'),
								'<b>+' . esc_html($rating) . '</b>'
							);
						?></span>
					<?php else: ?>
						<br><span class="rating"><?php _e('Not rated yet', 'fcmpn'); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</table>
		
	</div>
</div>
	<?php }
	
	/**
	 * Enqueue a script in the WordPress admin
	 */
	public function admin_enqueue_scripts( $hook ) {
		switch( $_GET['page'] ?? NULL ){
			case 'push-notification-fcm':
				break;
			case 'push-notification-fcm-settings':
				wp_enqueue_style( 'farbtastic' );
				wp_enqueue_script( 'farbtastic' );
				break;
		}
	}
	
	/*
     * Add custom column
	 */
	public function custom_column_header ( $columns ) {
		if( isset($columns['posts']) ) {
			unset($columns['posts']);
			$columns['devices'] = __( 'Devices', 'fcmpn' );
		}
		return $columns;
	}
	
	/*
     * Add custom column value
	 */
	function custom_column_content( $value, $column_name, $term_id ) {
		if ($column_name === 'devices') {
			global $wpdb;
			
			if( $fcmpn_devices = $wpdb->get_var( $wpdb->prepare("
				SELECT COUNT(1) FROM `{$wpdb->posts}`
				WHERE `{$wpdb->posts}`.`post_type` = 'fcmpn-devices' AND `{$wpdb->posts}`.`ID` IN (
					SELECT `object_id` FROM `{$wpdb->term_relationships}` AS `TR` 
						INNER JOIN `{$wpdb->term_taxonomy}` AS `TT` ON `TR`.`term_taxonomy_id` = `TT`.`term_taxonomy_id`
						INNER JOIN `{$wpdb->terms}` AS `T` ON `TT`.`term_id` = `T`.`term_id`
					WHERE `TT`.`taxonomy` = 'fcmpn-subscriptions' AND `T`.`term_id` = %d
				)
			", $term_id)) ) {
				echo '<a href="' . admin_url('/admin.php?page=push-notification-fcm&subscription=' . $term_id) . '">'
					. $fcmpn_devices 
				. '</a>';
			} else {
				echo 0;
			}
		}
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
		<?php endif; 
		
		if( in_array('push-notification-fcm-settings', [$_GET['page'] ?? NULL]) ) : ?>
<style>
	#fcmpn-related-plugins .img-responsive {
		display:block;
		width: 100%;
		height: auto;
		max-width:100%;
	}
	#fcmpn-related-plugins .title{
		font-size:1em;
	}
	#fcmpn-related-plugins .active-install,
	#fcmpn-related-plugins .rating{
		font-size:0.9em;
		color: #555;
		line-height: 0;
	}
</style>
<script>(function($){
	$(document).ready(function() {
		
		if( !(typeof $.farbtastic === 'function') ) {
			return;
		}
		
		$('#<?php echo esc_attr(self::OPTION_NAME); ?>_notification_color_picker')
			.hide()
			.farbtastic('#<?php echo esc_attr(self::OPTION_NAME); ?>_notification_color');
			
		var toggle_once_color_picker = function() {
			$('#<?php echo esc_attr(self::OPTION_NAME); ?>_notification_color').one('click', function(){
				if($(this).val() == '') {
					$(this).val('#ffffff');
				}
				$('#<?php echo esc_attr(self::OPTION_NAME); ?>_notification_color_picker')
					.slideToggle()
					.append('<a href="#" class="fcm-close-color-picker"><?php esc_html_e( 'Remove color', 'fcmpn' ); ?></a>');
			});
		};
		
		toggle_once_color_picker();
		
		$(document).on('click', '.fcm-close-color-picker', function(e){
			e.preventDefault();
			$('#<?php echo esc_attr(self::OPTION_NAME); ?>_notification_color').val('').css({
				backgroundColor : '',
				color : ''
			});
			$('#<?php echo esc_attr(self::OPTION_NAME); ?>_notification_color_picker').slideToggle();
			$(this).remove();
			toggle_once_color_picker();
		});
	});
}(jQuery||window.jQuery));</script>
		<?php endif;  }
	
	/*
     * Register admin menus
	 */
	public function admin_menu () {
		// Main navigation
		$this->devices_page = add_menu_page(
			__( 'FCM Push Notification', 'fcmpn' ),
			__( 'FCM Push Notification', 'fcmpn' ),
			'manage_options',
			'push-notification-fcm',
			[ &$this, 'devices' ],
			'dashicons-rest-api',
			80
		);
		add_action("load-{$this->devices_page}", [&$this, 'add_devices_page_screen_option']);
		
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
     * Set global screen option
	 */
	public function set_screen_option ($status, $option, $value) {
		if ( 'fcmpn_devices_per_page' == $option ){
			return $value;
		}
	}
	
	/*
     * Add Devices Screen Option
	 */
	public function add_devices_page_screen_option () {
		$screen = get_current_screen();
 
		if(!is_object($screen) || $screen->id != $this->devices_page) {
			return;
		}
	
		add_screen_option( 'per_page', [
			'label' => __( 'Devices per page', 'fcmpn' ),
			'default' => 20,
			'min' => 5,
			'max' => 1000,
			'option' => 'fcmpn_devices_per_page'
		] );
	}
	
	/*
     * Devices
	 */
	public function devices () {
		FCM_Push_Notification::include_once( FCMPN_ROOT . '/admin/Devices.php' );
	}
	
	/*
     * Settings
	 */
	public function settings () {
		FCM_Push_Notification::include_once( FCMPN_ROOT . '/admin/Settings.php' );
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
            'pnfcm_plugin_utilities', // ID
            __( 'Other settings', 'fcmpn' ), // Title
            array( &$this, 'section__pnfcm_plugin_utilities' ), // Callback
            'push-notification-fcm' // Page
        );
		
		add_settings_field(
            'notification_sound', // ID
            __( 'Notification Sound', 'fcmpn' ), // Title 
            array( $this, 'input__notification_sound' ), // Callback
            'push-notification-fcm', // Page
            'pnfcm_plugin_utilities' // Section           
        );
		
		add_settings_field(
            'notification_icon', // ID
            __( 'Notification Icon', 'fcmpn' ), // Title 
            array( $this, 'input__notification_icon' ), // Callback
            'push-notification-fcm', // Page
            'pnfcm_plugin_utilities' // Section           
        );
		
		add_settings_field(
            'notification_color', // ID
            __( 'Notification Color', 'fcmpn' ), // Title 
            array( $this, 'input__notification_color' ), // Callback
            'push-notification-fcm', // Page
            'pnfcm_plugin_utilities' // Section           
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
		printf(
			'<p>%s</p>',
			sprintf(
				__('Your api (server) key is located in the %s.', 'fcmpn'),
				'<a href="https://console.firebase.google.com/" target="_blank">' . __('Firebase console', 'fcmpn') . '</a>'
			)
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
		
		$readonly = !empty($rest_api_key);
		
		printf(
            '<input type="text" id="%1$s_rest_api_key" name="%1$s[rest_api_key]" value="%2$s" style="width:95%%; max-width:50%%; min-width:100px;"%3$s />',
            esc_attr( self::OPTION_NAME ),
			esc_attr( $rest_api_key ),
			( $readonly ? ' readonly' : '' )
        );
		
		if( $readonly ) {
			printf(
				'<p>%s</p>',
				sprintf(
					__('If you want, you can %s. After the change it is important to change the newly generated key in your application.', 'fcmpn'),
					sprintf(
						'<a href="javascript:void(0);" onclick="document.getElementById(\'%1$s_rest_api_key\').readOnly = false;">%2$s</a>',
						esc_attr( self::OPTION_NAME ),
						__('Change REST API Key', 'fcmpn')
					)
				)
			);
		}
	}
	
	
	
	/*
     * Section: Plugin API REST Endpoints
	 */
	public function section__pnfcm_plugin_rest_section () {
		printf(
			'<p>%s</p>',
			__('In order to be able to send push notifications, you need to record the device ID and device token in the site\'s database. Therefore, you have 2 REST endpoints to subscribe the device when the application is installed or launched, and unsubscribe the device during app deletion.', 'fcmpn')
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
					<li><code>device_uuid</code> %2$s</li>
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
					<li><code>device_uuid</code> %2$s</li>
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
     * Section: Firebase Server (API) Settings
	 */
	public function section__pnfcm_plugin_utilities () {
		printf(
			'<p>%s</p>',
			__('These are special additional settings for your application.', 'fcmpn')
		);
	}
	
	/*
     * Input: Notification sound
	 */
	public function input__notification_sound () {
		
		$notification_sound = self::get('notification_sound', 'default');

		if( empty($notification_sound) ) {
			$notification_sound = 'default';
		}

		printf(
            '<input type="text" id="%1$s_notification_sound" name="%1$s[notification_sound]" value="%2$s" style="width:50%%; max-width:200px; min-width:100px;" /> %3$s<p>%4$s</p>',
            esc_attr( self::OPTION_NAME ),
			esc_attr( $notification_sound ),
			__('(optional)', 'fcmpn'),
			__('The sound to play when the device receives the notification. Supports <b>"default"</b> or the filename of a sound resource bundled in the app. Sound files must reside in <b>/res/raw/</b>.', 'fcmpn')
        );
	}
	
	/*
     * Input: Notification icon
	 */
	public function input__notification_icon () {
		
		$notification_icon = self::get('notification_icon');

		printf(
            '<input type="text" id="%1$s_notification_icon" name="%1$s[notification_icon]" value="%2$s" style="width:50%%; max-width:200px; min-width:100px;" /> %3$s<p>%4$s</p>',
            esc_attr( self::OPTION_NAME ),
			esc_attr( $notification_icon ),
			__('(optional)', 'fcmpn'),
			__('The notification\'s icon. Sets the notification icon to <b>"myicon"</b> for drawable resource <b>myicon</b>. If you don\'t send this key in the request, FCM displays the launcher icon specified in your app manifest.', 'fcmpn')
        );
	}
	
	/*
     * Input: Notification icon
	 */
	public function input__notification_color () {
		
		$notification_color = self::get('notification_color');

		printf(
            '<input type="text" id="%1$s_notification_color" name="%1$s[notification_color]" value="%2$s" style="width:50%%; max-width:100px; min-width:100px;" autocomplete="off" /> %3$s<p>%4$s</p><div id="%1$s_notification_color_picker" style="text-align:center;width:200px;"></div>',
            esc_attr( self::OPTION_NAME ),
			esc_attr( $notification_color ),
			__('(optional)', 'fcmpn'),
			__('The notification\'s icon color, expressed in <b>#rrggbb</b> format.', 'fcmpn')
        );
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
			'api_key' 				=> NULL,
			'rest_api_key' 			=> NULL,
			'notification_sound'	=> 'default',
			'notification_icon' 	=> '',
			'notification_color' 	=> '',
			'post_types' 			=> []
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
		
		if( !empty($input['notification_sound']) ) {
			$new_input['notification_sound'] = sanitize_text_field($input['notification_sound']);
		}
		
		if( !empty($input['notification_icon']) ) {
			$new_input['notification_icon'] = sanitize_text_field($input['notification_icon']);
		}
		
		if( !empty($input['notification_color']) ) {
			$new_input['notification_color'] = sanitize_text_field($input['notification_color']);
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
	 * Return plugin informations
	 * @return        array/object
	 * @author        Ivijan-Stefan Stipic
	*/
	public static function plugin_info(array $fields = [], $slug = false, $force_cache = true) {		
		$cache_name = 'fcmpn-plugin_info-' . md5(serialize($fields) . ($slug!==false ? $slug : 'push-notification-fcm'));
		
		if($plugin_data = get_transient($cache_name)) {
			return $plugin_data;
		}
		
		delete_transient($cache_name);
		
        if ( is_admin() ) {
			if ( ! function_exists( 'plugins_api' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}
			/** Prepare our query */
			//donate_link
			//versions
			$plugin_data = plugins_api( 'plugin_information', [
				'slug' => ($slug!==false ? $slug : 'push-notification-fcm'),
				'fields' => array_merge([
					'active_installs' => false,           // rounded int
					'added' => false,                     // date
					'author' => false,                    // a href html
					'author_block_count' => false,        // int
					'author_block_rating' => false,       // int
					'author_profile' => false,            // url
					'banners' => false,                   // array( [low], [high] )
					'compatibility' => false,             // empty array?
					'contributors' => false,              // array( array( [profile], [avatar], [display_name] )
					'description' => false,               // string
					'donate_link' => false,               // url
					'download_link' => false,             // url
					'downloaded' => false,                // int
					// 'group' => false,                  // n/a 
					'homepage' => false,                  // url
					'icons' => false,                     // array( [1x] url, [2x] url )
					'last_updated' => false,              // datetime
					'name' => false,                      // string
					'num_ratings' => false,               // int
					'rating' => false,                    // int
					'ratings' => false,                   // array( [5..0] )
					'requires' => false,                  // version string
					'requires_php' => false,              // version string
					// 'reviews' => false,                // n/a, part of 'sections'
					'screenshots' => false,               // array( array( [src],  ) )
					'sections' => false,                  // array( [description], [installation], [changelog], [reviews], ...)
					'short_description' => false,         // string
					'slug' => false,                      // string
					'support_threads' => false,           // int
					'support_threads_resolved' => false,  // int
					'tags' => false,                      // array( )
					'tested' => false,                    // version string
					'version' => false,                   // version string
					'versions' => false,                  // array( [version] url )
				], $fields)
			]);
		 	
			// Save into current cache		
			set_transient($cache_name, $plugin_data, HOUR_IN_SECONDS);
			
			return $plugin_data;
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