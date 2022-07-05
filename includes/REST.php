<?php if ( ! defined( 'WPINC' ) ) { die( "Don't mess with us." ); } 

if(!class_exists('FCMPN_REST')) : class FCMPN_REST {
	
	const NAMESPACE = 'fcm/pn';
	
	/*
     * Main construct
	 */
	private static $run;
	private function __construct(){
		add_action('rest_api_init', [&$this, 'rest_api_init']);
	}
	
	public function rest_api_init () {
		
		// Subscribe
		register_rest_route( self::NAMESPACE, '/subscribe', array(
			'methods' => ['POST', 'GET'],
			'permission_callback' => '__return_true',
			'callback' => [&$this, '__callback_subscribe'],
				'args' => [
					'rest_api_key' => [
						'validate_callback' => function($param, $request, $key) {
							return !empty( $param );
						},
						'required' => true
					],
					'user_email' => [
						'validate_callback' => function($param, $request, $key) {
							return !empty( $param );
						},
						'required' => true
					],
					'device_token' => [
						'validate_callback' => function($param, $request, $key) {
							return !empty( $param );
						},
						'required' => true
					],
					'subscription' => [
						'validate_callback' => function($param, $request, $key) {
							return !empty( $param );
						},
						'required' => true
					],
					'device_name' => [
						'required' => false
					],
					'os_version' => [
						'required' => false
					]
				]
		), [], true );
		
		// Unsubscribe
		register_rest_route( self::NAMESPACE, '/unsubscribe', array(
			'methods' => ['POST', 'GET'],
			'permission_callback' => '__return_true',
			'callback' => [&$this, '__callback_unsubscribe'],
				'args' => [
					'rest_api_key' => [
						'validate_callback' => function($param, $request, $key) {
							return FCMPN_Settings::get('rest_api_key') === $param;
						},
						'required' => true
					],
					'device_token' => [
						'validate_callback' => function($param, $request, $key) {
							return !empty( $param );
						},
						'required' => true
					]
				]
		), [], true );
		
	}
	
	/*
     * Subscribe
	 */
	public function __callback_subscribe ( WP_REST_Request $request ) {
		// Validate API KEY
		if( FCMPN_Settings::get('rest_api_key') !== $request->get_param( 'rest_api_key' ) ) {
			return new WP_REST_Response([
				'error' => true,
				'message' => __( 'REST API key is not valid','fcmpn' ),
				'subscription_id' => NULL
			]);
		}
		
		// Validate email
		$user_email = sanitize_email($request->get_param( 'user_email' ));
		if( !is_email( $user_email ) ) {
			return new WP_REST_Response([
				'error' => true,
				'message' => __( 'Email address is not valid','fcmpn' ),
				'subscription_id' => NULL
			]);
		}
		
		// Validate token
		$device_token = sanitize_text_field($request->get_param( 'device_token' ) ?? '');
		if( strlen($device_token) < 10 ) {
			return new WP_REST_Response([
				'error' => true,
				'message' => __( 'There is an error in the token you sent','fcmpn' ),
				'subscription_id' => NULL
			]);
		}
		
		// Is token exists
		if( $device = get_page_by_title($device_token, OBJECT, 'fcmpn-devices') ) {
			return new WP_REST_Response([
				'error' => false,
				'message' => __( 'Device token registered','fcmpn' ),
				'subscription_id' => $device->ID
			]);
		}
		
		// Validate subscription
		$subscription = sanitize_text_field($request->get_param( 'subscription' ) ?? '');
		$term_id = NULL;
		if( $term = get_term_by('name', $subscription, 'fcmpn-subscriptions') ) {
			$term_id = $term->term_id;
		} else {
			if( $term = get_term_by('slug', $subscription, 'fcmpn-subscriptions') ) {
				$term_id = $term->term_id;
			} {
				$term_id = $term->term_id;
			}
		}
		
		// Add new term
		if( !$term_id ) {
			$term = wp_insert_term($subscription, 'fcmpn-subscriptions');
			if( !is_wp_error($term) ) {
				$term_id = $term['term_id'];
			}
		}
		
		// Validate term
		if( !$term_id ) {
			return new WP_REST_Response([
				'error' => true,
				'message' => __( 'Not able to subscribe','fcmpn' ),
				'subscription_id' => NULL
			]);
		}
		
		// Save device
		$post_id = wp_insert_post( [
			'post_title'    => $device_token,
			'post_excerpt'	=> $device_token,
			'post_status'   => 'private',
			'post_type'		=> 'fcmpn-devices',
			'meta_input'	=> [
				'_device_name' => sanitize_text_field($request->get_param( 'device_name' ) ?? ''),
				'_os_version' => sanitize_text_field($request->get_param( 'os_version' ) ?? ''),
				'_email' => $user_email
			]
		] );
		
		// Validate device
		if( is_wp_error($post_id) ) {
			return new WP_REST_Response([
				'error' => true,
				'message' => __( 'Device is not saved','fcmpn' ),
				'subscription_id' => NULL
			]);
		}
		
		// Assign terms
		wp_set_object_terms($post_id, $term_id, 'fcmpn-subscriptions');
		
		// Return
		return new WP_REST_Response([
			'error' => false,
			'message' => __( 'Device token registered','fcmpn' ),
			'subscription_id' => $post_id
		]);
	}
	
	/*
     * Unsubscribe
	 */
	public function __callback_unsubscribe ( WP_REST_Request $request ) {
		// Validate API KEY
		if( FCMPN_Settings::get('rest_api_key') !== $request->get_param( 'rest_api_key' ) ) {
			return new WP_REST_Response([
				'error' => true,
				'message' => __( 'REST API key is not valid','fcmpn' ),
				'subscription_id' => NULL
			]);
		}
		
		// Validate token
		$device_token = sanitize_text_field($request->get_param( 'device_token' ) ?? '');
		if( strlen($device_token) < 10 ) {
			return new WP_REST_Response([
				'error' => true,
				'message' => __( 'There is an error in the token you sent','fcmpn' ),
				'subscription_id' => NULL
			]);
		}
		
		if( $device = get_page_by_title($device_token, OBJECT, 'fcmpn-devices') ) {
		
			wp_delete_post($device->ID, true);
			
			// Return
			return new WP_REST_Response([
				'error' => false,
				'message' => __( 'The device token was successfully removed','fcmpn' ),
				'subscription_id' => $post_id
			]);
		}
		
		return new WP_REST_Response([
			'error' => true,
			'message' => __( 'No device token found','fcmpn' ),
			'subscription_id' => NULL
		]);
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