<div class="wrap" id="push-notification-fcm-settings">
	<h1><?php esc_html_e('Firebase Push Notification', 'fcmpn'); ?></h1>
	<?php
		if( $update_option_saved = get_transient( 'update_option_' . FCMPN_Settings::OPTION_NAME . '_saved' ) ) :
		delete_transient( 'update_option_' . FCMPN_Settings::OPTION_NAME . '_saved' );
	?>
	<div class="updated notice">
		<p><?php echo esc_html($update_option_saved); ?></p>
	</div>
	<?php endif; ?>
	<hr>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
	
		<div id="post-body">
			<div id="post-body-content">
				<form method="post" action="<?php echo esc_url( admin_url('options.php') ); ?>">
				<?php
					settings_fields( 'push_notification_fcm' );
					do_settings_sections( 'push-notification-fcm' );
					submit_button();
				?>
				</form>
			</div>
		</div>
	
		<div class="inner-sidebar" id="cfmpn-settings-sidebar">
			<div id="side" class="meta-box">
				<?php do_action( 'fcmpn-settings-sidebar' ); ?>
			</div>
		</div>
		
	</div>
</div>