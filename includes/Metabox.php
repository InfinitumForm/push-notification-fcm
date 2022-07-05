<?php if ( ! defined( 'WPINC' ) ) { die( "Don't mess with us." ); } 

if(!class_exists('FCMPN_Metabox')) : class FCMPN_Metabox {
	
	/*
     * Main construct
	 */
	private static $run;
	private function __construct(){
		add_action( 'add_meta_boxes', [&$this, 'add_meta_boxes'] );
		add_action( 'save_post', [&$this, 'save_post'], 10, 3 );
	}
	
	/*
	 * Register meta boxes.
	 */
	public function add_meta_boxes ( $post_type ) {
		
		$post_types = FCMPN_Settings::get('post_types', []);
		if( in_array($post_type, $post_types) ) {
			add_meta_box(
				'fcm-push-notification',
				__( 'Push Notification', 'fcmpn' ),
				[&$this, 'send_notification__callback'],
				$post_types,
				'side',
				'high'
			);
		}
	}
	
	/*
	 * Register meta boxes.
	 */
	public function send_notification__callback ( $post ) {
		$terms = get_terms([
			'taxonomy' => 'fcmpn-subscriptions',
			'hide_empty' => false,
		]);
		if( !empty($terms) ) :
		$push_notification = get_post_meta( $post->ID, 'fcm-push-notification', true );
		if( !in_array($push_notification, ['yes', 'no']) ) {
			$push_notification = 'yes';
		}
		$push_notification_terms = get_post_meta( $post->ID, 'fcm-push-notification-terms', true );
		if(!is_array($push_notification_terms)) {
			$push_notification_terms = wp_list_pluck($terms, 'term_id');
		}
		?>
		<p><label for="fcm-push-notification">
			<input id="fcm-push-notification" type="checkbox" name="fcm_push_notification" value="yes"<?php echo (empty($terms) ? ' disabled="disabled"' : '') . checked($push_notification, 'yes'); ?>> <?php _e('Send notification on post publish', 'fcmpn'); ?>
		</label></p>
		<hr>
		<strong><?php _e('Select the subscription you want to notify', 'fcmpn'); ?></strong>
		
			<?php foreach( $terms as $i=>$term ) : ?>
			<p><label for="fcm-push-notification-terms-<?php echo esc_attr( absint($i) ); ?>">
				<input id="fcm-push-notification-terms-<?php echo esc_attr( absint($i) ); ?>" type="checkbox" name="fcm_push_notification_terms[<?php echo esc_attr( absint($i) ); ?>]" value="<?php echo esc_attr( absint($term->term_id) ); ?>"<?php echo ($push_notification_terms && in_array($term->term_id, $push_notification_terms) ? ' checked="checked"' : ''); ?>> <?php echo esc_html( $term->name ); ?>
			</label></p>
			<?php endforeach; ?>
		<?php else :  ?>
		<p><?php _e('There are currently no registered subscriptions', 'fcmpn'); ?></p>
		<?php endif; ?>
	<?php }
	
	
	/*
	 * Save meta boxes.
	 */
	public function save_post ( $post_id, $post, $update ) {
		// Only want to set if this is a new post!
		if ( !$update ){
			return;
		}
	
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
		
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
		
		if ( !in_array($post->post_type, FCMPN_Settings::get('post_types', [])) ) {
			return;
		}
		
		if( isset($_POST['fcm_push_notification']) ) {
			$push_notification = ($_POST['fcm_push_notification'] == 'yes' ? 'yes' : 'no');
			update_post_meta( $post_id, 'fcm-push-notification', $push_notification );
		} else {
			update_post_meta( $post_id, 'fcm-push-notification', 'no' );
		}
		
		$push_notification_terms = ($_POST['fcm_push_notification_terms'] ?? []);
		if($push_notification_terms && is_array($push_notification_terms)) {
			$push_notification_terms = array_map('absint', $push_notification_terms);
			update_post_meta( $post_id, 'fcm-push-notification-terms', $push_notification_terms );
		} else {
			update_post_meta( $post_id, 'fcm-push-notification-terms', [] );
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