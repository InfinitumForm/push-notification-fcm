<?php if ( ! defined( 'WPINC' ) ) { die( "Don't mess with us." ); } 

if(!class_exists('FCMPN_API')) : class FCMPN_API {
	
	private $url = 'https://fcm.googleapis.com/fcm/send';
	
	/*
     * Main construct
	 */
	private static $run;
	private function __construct(){
		if( $post_types = FCMPN_Settings::get('post_types', []) ) {
			foreach ($post_types as $post_type) {
				if( preg_match('/[0-9a-z\-\_]+/i', $post_type) ) {
					add_action("publish_{$post_type}", [&$this, 'push_notification'], 100, 2);
				}
			}
		}
	}
	
	/*
     * Run the plugin
	 */
	public function push_notification( $post_id, $post ){
		if( isset($_POST['fcm_push_notification']) ) {
			$push_notification = ($_POST['fcm_push_notification'] == 'yes' ? 'yes' : 'no');
		}
		
		$push_notification_terms = ($_POST['fcm_push_notification_terms'] ?? []);
		if($push_notification_terms && is_array($push_notification_terms)) {
			$push_notification_terms = array_map('absint', $push_notification_terms);
		}
		
		if( $push_notification && $push_notification_terms ) {
			$tax_query = [];
			foreach($push_notification_terms as $term_id) {
				$tax_query[] = [
					'taxonomy' => 'fcmpn-subscriptions',
					'field'    => 'term_id',
					'terms'    => $term_id
				];
			}
			
			if(count($tax_query) > 1) {
				$tax_query['relation']='OR';
			}

			$devices_id = [];
			if( $get_devices = get_posts([
				'post_type' => 'fcmpn-devices',
				'post_status' => 'private',
				'posts_per_page'=> -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => $tax_query
			]) ) {
				unset($tax_query);
				
				$devices_id = wp_list_pluck($get_devices, 'post_excerpt');
				$devices_id = apply_filters('fcmpm_api_send_notification_devices', $devices_id, $get_devices, $post_id, $post);
				unset($get_devices);
				
				if( !empty($devices_id) ) {

					$notification = [
						'title' => apply_filters('fcmpm_api_send_notification_post_title', $post->post_title),
						'body' => mb_strimwidth(
							strip_tags($post->post_content),
							0,
							apply_filters('fcmpm_api_send_notification_post_content_max_length', 160),
							'...'
						),
						'sound' => FCMPN_Settings::get('notification_sound', 'default'),
						'type' => 1
					];
					
					if( $notification_icon = FCMPN_Settings::get('notification_icon') ) {
						$notification['icon'] = $notification_icon;
					}
					
					if( $notification_color = FCMPN_Settings::get('notification_color') ) {
						$notification['color'] = $notification_color;
					}
					
					$notification = apply_filters('fcmpm_api_send_notification', $notification, $post_id, $post);
					
					$data = [
						'news_id' => $post_id
					];
					
					if( $img_src = get_the_post_thumbnail_url($post_id, apply_filters('fcmpm_api_send_notification_thumbnail_size', 'medium')) ) {
						$data['image'] = apply_filters('fcmpm_api_send_notification_thumbnail_url', $img_src, $post_id);
					}
					
					$data = apply_filters('fcmpm_api_send_notification_data', $data, $post_id, $post);
					
					$this->send_notification(
						$devices_id,
						$notification,
						$data
					);
				}
			}
		}
	}
	
	/*
     * PRIVATE: Send notification
	 *
	 * wp_remote_request have some problems here and we must use pure cURL
	 */
	private function send_notification( $ids, $notification, $data) {
		
		$fields = array(
			'registration_ids' => $ids,
			'notification' => $notification,
			'data' => $data
		);

		$headers = array (
			'Authorization: key=' . FCMPN_Settings::get('api_key'),
			'Content-Type: application/json; charset=UTF-8',
			'Accept: application/json'
		);

		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $this->url );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode($fields) );

		$result = curl_exec ( $ch );
		
		if( defined('WP_DEBUG') && WP_DEBUG ) {
			$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ( $http_code !== 200 ) {
				$result = NULL;
				error_log( sprintf(
					__( 'FCMPN: An error occurred while communicating with the Firebase API. (HTTP code: %d)','fcmpn' ),
					$http_code
				), 0 );
			}
		}
		
		curl_close ( $ch );
		
		if( ! empty($result) ) {
			$result = json_decode($result);
		}
			
		return $result;
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