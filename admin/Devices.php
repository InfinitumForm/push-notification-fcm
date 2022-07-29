<div class="wrap">
	<h1><?php esc_html_e('All Registered Devices', 'fcmpn'); ?></h1>
	<?php FCMPN_Devices_Table::get_filter_links(); ?>
	<form method="get" id="devices-table-search">
		<p class="search-box">
			<label class="screen-reader-text" for="post-search-input"><?php esc_html_e('Search Devices', 'fcmpn'); ?>:</label>
			<input type="search" id="post-search-input" name="s" value="<?php echo esc_attr( sanitize_text_field($_GET['s'] ?? '') ); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search Devices', 'fcmpn'); ?>">
			<input type="hidden" value="<?php echo esc_attr( sanitize_text_field($_GET['page'] ?? '') ); ?>" name="page">
			<input type="hidden" value="<?php echo esc_attr( sanitize_text_field($_GET['filter'] ?? '') ); ?>" name="filter">
			<input type="hidden" value="<?php echo esc_attr( absint($_GET['subscription'] ?? 0) ); ?>" name="subscription">
			<input type="hidden" value="<?php echo wp_create_nonce('fcmpn-devices') ?>" name="_wpnonce">
		</p>
	</form>
	<form method="post" id="devices-table-form">
		<?php FCMPN_Devices_Table::instance(); ?>
	</form>
</div>