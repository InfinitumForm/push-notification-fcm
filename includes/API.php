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
				foreach($get_devices as $device) {
					$devices_id[]=$device->post_title;
				}
				unset($get_devices);
				
				if( !empty($devices_id) ) {

					$notification = [
						'title' => $post->post_title,
						'body' => mb_strimwidth( strip_tags($post->post_content), 0, 160, '...' ),
						'sound' => 'default',
						'type' => 1
					];
					
					$data = ['news_id' => $post_id];
					
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
	 */
	private function send_notification( $ids, $notification, $data) {
		$fields = array(
			'registration_ids' => $ids,
			'notification' => $notification,
			'data' => $data
		);

		$headers = array (
			'Authorization: key=' . FCMPN_Settings::get('api_key'),
			'Content-Type: application/json'
		);

		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $this->url );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode($fields) );

		$result = curl_exec ( $ch );
		curl_close ( $ch );
		
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