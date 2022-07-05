<div class="wrap">
	<h1><?php _e('Firebase Push Notification', 'fcmpn'); ?></h1>
	<form method="post" action="<?php echo admin_url('options.php'); ?>">
	<?php
		settings_fields( 'push_notification_fcm' );
		do_settings_sections( 'push-notification-fcm' );
		submit_button();
	?>
	</form>
</div>