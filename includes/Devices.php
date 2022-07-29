<?php if ( ! defined( 'WPINC' ) ) { die( "Don't mess with us." ); } 

// Fetch WP List Table if is not loaded
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-wp-list-table.php';
}

// Create table
if (!class_exists('FCMPN_Devices_Table')): class FCMPN_Devices_Table extends WP_List_Table
{
	private static $run;
	public function __construct()
	{
		parent::__construct([
			'singular' => 'fcmpn-device', //Singular label
			'plural' => 'fcmpn-devices', //plural label, also this well be one of the table css class
			'ajax' => false
			
		]);
		$this->prepare_items();
		$this->display();
	}
	
	/**
	 * Generate filter links for the table
	 */
	public static function get_filter_links() {
		global $wpdb;
		
		$count = [
			'enabled' => 0,
			'disabled' => 0
		];
		
		$filter = sanitize_text_field($_GET['filter'] ?? NULL);
		
		$query = "SELECT COUNT(1) FROM `{$wpdb->posts}` WHERE `{$wpdb->posts}`.`post_type` = 'fcmpn-devices'";
		
		/* -- Search -- */
		if(wp_verify_nonce(($_GET['_wpnonce'] ?? NULL), 'fcmpn-devices') && ($s = sanitize_text_field($_GET['s'] ?? ''))){
			$query.=$wpdb->prepare(
				" AND (
					`{$wpdb->posts}`.`post_title` LIKE %s 
					OR `{$wpdb->posts}`.`post_content` LIKE %s 
					OR `{$wpdb->posts}`.`post_excerpt` LIKE %s 
					OR `{$wpdb->posts}`.`post_name` LIKE %s
					OR (
						SELECT 1 FROM `{$wpdb->postmeta}` 
						WHERE `{$wpdb->postmeta}`.`meta_key` = '_device_name' 
						AND `{$wpdb->postmeta}`.`post_id` = `{$wpdb->posts}`.`ID`
						AND `{$wpdb->postmeta}`.`meta_value` LIKE %s
					) OR (
						SELECT 1 FROM `{$wpdb->postmeta}` 
						WHERE `{$wpdb->postmeta}`.`meta_key` = '_os_version' 
						AND `{$wpdb->postmeta}`.`post_id` = `{$wpdb->posts}`.`ID`
						AND `{$wpdb->postmeta}`.`meta_value` LIKE %s
					)
				) ",
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%'
			);
		}
		
		if( 0 < ( $subscription = absint($_GET['subscription'] ?? 0) ) ) {
			$query.=$wpdb->prepare(" AND `{$wpdb->posts}`.`ID` IN (
				SELECT `object_id` FROM `{$wpdb->term_relationships}` AS `TR` 
					INNER JOIN `{$wpdb->term_taxonomy}` AS `TT` ON `TR`.`term_taxonomy_id` = `TT`.`term_taxonomy_id`
					INNER JOIN `{$wpdb->terms}` AS `T` ON `TT`.`term_id` = `T`.`term_id`
				WHERE `TT`.`taxonomy` = 'fcmpn-subscriptions' AND `T`.`term_id` = %d
			)", $subscription);
		}
		
		$count['enabled'] = absint( $wpdb->get_var( $query . " AND `{$wpdb->posts}`.`post_status` = 'private'" ) );
		$count['disabled'] = absint( $wpdb->get_var( $query . " AND `{$wpdb->posts}`.`post_status` = 'trash'" ) );

		?>
		<ul class="subsubsub">
			<li class="all"><a href="<?php echo add_query_arg('filter',NULL); ?>"<?php echo add_query_arg('filter','enabled'); ?>"<?php
				if($filter == NULL) {
					echo ' class="current" aria-current="page"';
				}
			?>><?php esc_html_e('All', 'fcmpn'); ?> <span class="count">(<?php echo ($count['enabled']+$count['disabled']); ?>)</span></a> |</li>
			<li class="enabled"><a href="<?php echo add_query_arg('filter','enabled'); ?>"<?php
				if($filter == 'enabled') {
					echo ' class="current" aria-current="page"';
				}
			?>><?php esc_html_e('Enabled', 'fcmpn'); ?> <span class="count">(<?php echo $count['enabled']; ?>)</span></a> 
			<?php if($count['disabled']) : ?>|</li>
			<li class="disabled"><a href="<?php echo add_query_arg('filter','disabled'); ?>"<?php echo add_query_arg('filter','enabled'); ?>"<?php
				if($filter == 'disabled') {
					echo ' class="current" aria-current="page"';
				}
			?>><?php esc_html_e('Disabled', 'fcmpn'); ?> <span class="count">(<?php echo $count['disabled']; ?>)</span></a></li>
			<?php else : ?>
			</li>
			<?php endif; ?>
		</ul>
		<?php
	}
	
	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	function prepare_items()
	{
		global $wpdb, $_wp_column_headers;
		
		// Set bulk actions
		$this->process_bulk_action();
		// get the current user ID
		$user = get_current_user_id();
		// get the current admin screen
		$screen = get_current_screen();
		// retrieve the "per_page" option
		$screen_option = $screen->get_option('per_page', 'option');
		// retrieve the value of the option stored for the current user
		$perpage = get_user_meta($user, $screen_option, true);
		// get the default value if none is set
		if ( empty ( $perpage) || $perpage < 1 ) {
			$perpage = $screen->get_option( 'per_page', 'default' );
		}
		// Make it absolute integer
		if( empty($perpage) ) {
			$perpage = 0;
		} else {
			$perpage = (int)$perpage;
		}
		/* -- Preparing your query -- */
		$query = "
			SELECT
				`ID`,
				`post_title`,
				`post_status`,
				`post_modified`,
				(
					SELECT `meta_value` FROM `{$wpdb->postmeta}` 
					WHERE `{$wpdb->postmeta}`.`meta_key` = '_os_version' 
					AND `{$wpdb->postmeta}`.`post_id` = `{$wpdb->posts}`.`ID`
				) AS `os_version`,
				(
					SELECT `meta_value` FROM `{$wpdb->postmeta}` 
					WHERE `{$wpdb->postmeta}`.`meta_key` = '_device_name' 
					AND `{$wpdb->postmeta}`.`post_id` = `{$wpdb->posts}`.`ID`
				) AS `device_name`,
				`post_excerpt` AS `devce_key`
			FROM
				`{$wpdb->posts}`
			WHERE `{$wpdb->posts}`.`post_type` = 'fcmpn-devices'
		";
		
		/* -- Search -- */
		if(wp_verify_nonce(($_GET['_wpnonce'] ?? NULL), 'fcmpn-devices') && ($s = sanitize_text_field($_GET['s'] ?? ''))){
			$query.=$wpdb->prepare(
				" AND (
					`{$wpdb->posts}`.`post_title` LIKE %s 
					OR `{$wpdb->posts}`.`post_content` LIKE %s 
					OR `{$wpdb->posts}`.`post_excerpt` LIKE %s 
					OR `{$wpdb->posts}`.`post_name` LIKE %s
					OR (
						SELECT 1 FROM `{$wpdb->postmeta}` 
						WHERE `{$wpdb->postmeta}`.`meta_key` = '_device_name' 
						AND `{$wpdb->postmeta}`.`post_id` = `{$wpdb->posts}`.`ID`
						AND `{$wpdb->postmeta}`.`meta_value` LIKE %s
					) OR (
						SELECT 1 FROM `{$wpdb->postmeta}` 
						WHERE `{$wpdb->postmeta}`.`meta_key` = '_os_version' 
						AND `{$wpdb->postmeta}`.`post_id` = `{$wpdb->posts}`.`ID`
						AND `{$wpdb->postmeta}`.`meta_value` LIKE %s
					)
				) ",
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%',
				'%'.$wpdb->esc_like($s).'%'
			);
		}
		
		if( 0 < ( $subscription = absint($_GET['subscription'] ?? 0) ) ) {
			$query.=$wpdb->prepare(" AND `{$wpdb->posts}`.`ID` IN (
				SELECT `object_id` FROM `{$wpdb->term_relationships}` AS `TR` 
					INNER JOIN `{$wpdb->term_taxonomy}` AS `TT` ON `TR`.`term_taxonomy_id` = `TT`.`term_taxonomy_id`
					INNER JOIN `{$wpdb->terms}` AS `T` ON `TT`.`term_id` = `T`.`term_id`
				WHERE `TT`.`taxonomy` = 'fcmpn-subscriptions' AND `T`.`term_id` = %d
			)", $subscription);
		}
		
		if($filter = sanitize_text_field($_GET['filter'] ?? '')) {
			
			if($filter === 'enabled') {
				$filter = 'private';
			} else if($filter === 'disabled') {
				$filter = 'trash';
			}
			
			$query.=$wpdb->prepare( " AND `{$wpdb->posts}`.`post_status` = %s", $wpdb->esc_like($filter) );
		}

		/* -- Ordering parameters -- */
		//Parameters that are going to be used to order the result
		$orderby = sanitize_text_field($_GET['orderby'] ?? 'ID');
		$order = sanitize_text_field($_GET['order'] ?? 'desc');
		
		if (!empty($orderby) & !empty($order))
		{
			if(
				in_array(strtolower($order), array('asc', 'desc'))
				&& in_array($orderby, array(
					'ID',
					'post_title',
					'post_content',
					'post_excerpt',
					'post_name',
					'post_date',
					'post_modified'
				))
			){
				$query .= " ORDER BY `{$wpdb->posts}`.`{$orderby}` {$order}";
			}
		}

		/* -- Pagination parameters -- */
		//Number of elements in your table?
		$totalitems = $wpdb->query($query); //return the total number of affected rows
		//Which page is this?
		$paged = absint(sanitize_text_field($_GET['paged'] ?? 0));
		//Page Number
		if (empty($paged) || !is_numeric($paged) || $paged <= 0)
		{
			$paged = 1;
		}
		//How many pages do we have in total?
		$totalpages = ceil($totalitems / $perpage);
		//adjust the query to take pagination into account
		if (!empty($paged) && !empty($perpage))
		{
			$offset = (int)(($paged - 1) * $perpage);
			$query .= " LIMIT {$offset},{$perpage}";
		}
		/* -- Register the pagination -- */
		$this->set_pagination_args(array(
			'total_items' => $totalitems,
			'total_pages' => $totalpages,
			'per_page' => $perpage,
		));
		//The pagination links are automatically built according to those parameters
		/* -- Register the Columns -- */
		$columns = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$_wp_column_headers[$screen->id] = $columns;

		/* -- Fetch the items -- */
		$this->_column_headers = array(
			$columns,
			array('ID'),
			$sortable
		);
		
		$this->items = $wpdb->get_results($query);
	}
	
	/**
	 * Generate bulk actions
	 */
	public function get_bulk_actions() {

		return array(
			'enable' => __( 'Enable Device', 'fcmpn' ),
			'disable' => __( 'Disable Device', 'fcmpn' ),
			'delete' => __( 'Delete', 'fcmpn' )
		);

	}
	
	/**
	 * Process bulk actions
	 */
	public function process_bulk_action() {

		// security check!
		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

			$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) )
				wp_die( __( 'Nope! Security check failed!', 'fcmpn' ) );

		}

		$action = $this->current_action();
		
		if( isset($_POST['bulk_devices_id']) && !empty($_POST['bulk_devices_id']) && is_array($_POST['bulk_devices_id']) ) {
			$checkboxes = array_map( 'sanitize_text_field', $_POST['bulk_devices_id'] );
			$checkboxes = array_map( 'absint', $checkboxes );
			$checkboxes = array_filter( $checkboxes );
						
			if( !empty($checkboxes) ) {
				global $wpdb;
				
				$checkboxes_prepare = implode( ',', array_fill( 0, count( $checkboxes ), '%d' ) );
				
				switch ( $action ) {

					case 'delete':
						$wpdb->query(
							$wpdb->prepare(
								"DELETE FROM `{$wpdb->posts}` WHERE `{$wpdb->posts}`.`ID` IN ({$checkboxes_prepare})",
								$checkboxes
							)
						);
						$wpdb->query(
							$wpdb->prepare(
								"DELETE FROM `{$wpdb->postmeta}` WHERE `{$wpdb->postmeta}`.`post_id` IN ({$checkboxes_prepare})",
								$checkboxes
							)
						);
						break;
						
					case 'enable':
					case 'disable':
						$enable_disable = ( $action === 'disable' ? 'trash' : 'private' );
						$wpdb->query(
							$wpdb->prepare(
								"UPDATE `{$wpdb->posts}` SET `post_status` = %s",
								$enable_disable
							) . $wpdb->prepare(
								" WHERE `{$wpdb->posts}`.`ID` IN ({$checkboxes_prepare})",
								$checkboxes
							)
						);
						break;
				}
			}
		}

		return;
	}	
	
	/**
	 * Define the columns that are going to be used in the table
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns()
	{
		return [
			'cb'    => '<input type="checkbox">',
			'device_uuid' => esc_html__('Device UUID', 'fcmpn'),
			'devce_key' => esc_html__('Device Key', 'fcmpn'),
			'subscription' => esc_html__('Subscription', 'fcmpn'),
			'device_name' => esc_html__('Device Name', 'fcmpn'),
			'os_version' => esc_html__('OS Version', 'fcmpn'),
			'date' => esc_html__('Subscribed', 'fcmpn')
		];
	}
	
	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns()
	{
		return [
			'device_uuid' => ['post_excerpt', true],
			'devce_key' => ['post_title', true],
			'date' => ['post_modified', true]
		];
	}
	
	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	function display_rows()
	{

		//Get the records registered in the prepare_items method
		$records = $this->items;

		//Get the columns registered in the get_columns and get_sortable_columns methods
		list($columns, $hidden) = $this->get_column_info();

		//Loop for each record
		if (!empty($records))
		{
			foreach ($records as $i=>$rec)
			{
				//Open the line
				echo '<tr id="fcmpn-devices-' . absint($rec->ID) . '" class="iedit author-self fcmpn-devices-'.absint($rec->ID).' type-fcmpn-devices status-'.esc_attr($rec->post_status).' format-standard hentry">';
				
				$date_format = get_option('date_format') . ' ' . get_option('time_format');
				
				foreach ($columns as $column_name => $column_display_name)
				{
					$class = 'class="'.esc_attr($column_name).' column-'.esc_attr($column_name).'"';
					$style = ' style="';
					if($column_name == 'cb'){
						$style.=  'width:2.2em;';
					}
					if (in_array($column_name, $hidden)) $style.= 'display:none;';
					if( $rec->post_status === 'trash' ) {
						$style.= 'color:#999;';
					}
					$style.='"';
					$attributes = $class . $style;
					
					// Display the cell
					switch ($column_name)
					{
						case 'cb':
							echo '<th scope="row" class="check-column">' . sprintf(
								'<label class="screen-reader-text" for="cb-select-%1$d"></label>
								<input type="checkbox" id="cb-select-%1$d" name="bulk_devices_id[]" value="%1$d" />',
								absint($rec->ID),
								__('Select Device', 'fcmpn')
							). '</th>';
						break;
						case 'devce_key':
							if( empty($rec->devce_key) ) {
								printf(
									'<td %1$s>%2$s</td>',
									$attributes,
									' - '
								);
							} else {							
								printf(
									'<td %1$s title="%2$s">%3$s</td>',
									$attributes,
									esc_attr($rec->devce_key),
									(
										$rec->post_status === 'trash' 
										? '<span style="color:#cc0000">' . __('Disabled', 'fcmpn') . ':</span> ' 
										: ''
									) . esc_html(mb_strimwidth( $rec->devce_key, 0, 30, '...' )) 
								);
							}
						break;
						case 'device_uuid':
							if( empty($rec->post_title) ) {
								printf(
									'<td %1$s>%2$s</td>',
									$attributes,
									' - '
								);
							} else {							
								printf(
									'<td %1$s title="%2$s">%3$s</td>',
									$attributes,
									esc_attr($rec->post_title),
									esc_html(mb_strimwidth( $rec->post_title, 0, 30, '...' )) 
								);
							}
						break;
						case 'subscription':
							if( $get_terms = get_the_terms((int)$rec->ID, 'fcmpn-subscriptions') ) {
								$links = [];
								foreach($get_terms as $term) {
									$links[]=sprintf(
										'<a href="%1$s" target="_blank">%2$s</a>',
										esc_url( admin_url('/edit-tags.php?taxonomy=fcmpn-subscriptions&post_type=post&s=' . $term->slug) ),
										esc_html($term->name)
									);
								}
								
								printf(
									'<td %1$s>%2$s</td>',
									$attributes,
									join(', ', $links)
								);
							} else {
								printf(
									'<td %1$s>%2$s</td>',
									$attributes,
									__('undefined', 'fcmpn')
								);
							}							
						break;
						case 'device_name':
							if( empty($rec->device_name) ) {
								printf(
									'<td %1$s>%2$s</td>',
									$attributes,
									' - '
								);
							} else {
								$device_name = trim(strtolower($rec->device_name));
								
								// @source: https://github.com/EgoistDeveloper/operating-system-logos
								$device_icon = NULL;
								if( in_array($device_name, ['android']) ) {
									$device_icon = 'AND.png';
								} else if( in_array($device_name, ['ios', 'apple', 'iphone']) ) {
									$device_icon = 'IOS.png';
									if($device_name == 'ios') {
										$rec->device_name = 'iOS';
									}
								} else if( in_array($device_name, ['tvos']) ) {
									$device_icon = 'ATV.png';
								} else if( strpos($device_name, 'chrome') !== false ) {
									$device_icon = 'COS.png';
								} else if( strpos($device_name, 'firefox') !== false ) {
									$device_icon = 'FOS.png';
								} else if( strpos($device_name, 'windows') !== false ) {
									$device_icon = 'WIN.png';
								} else if(
									strpos($device_name, 'linux') !== false 
									|| strpos($device_name, 'gnu') !== false
								) {
									$device_icon = 'LIN.png';
								} else if( strpos($device_name, 'ubuntu') !== false ) {
									$device_icon = 'UBT.png';
								} else if( strpos($device_name, 'xbox') !== false ) {
									$device_icon = 'XBX.png';
								} else if( strpos($device_name, 'webos') !== false ) {
									$device_icon = 'WOS.png';
								} else if( $device_name === 'aix' ) {
									$device_icon = 'AIX.png';
								} else if( $device_name === 'debian' ) {
									$device_icon = 'DEB.png';
								}
								
								if($device_icon) {
									$device_name = '<img src="' . FCMPN_URL . '/assets/16x16/' . $device_icon . '" alt="' . esc_attr($rec->device_name) . '" style="float:left; margin:1px 5px 0 0;"> <span>' . esc_html($rec->device_name) . '</span>';
								} else {
									$device_name = esc_html($rec->device_name);
								}
								
								printf(
									'<td %1$s>%2$s</td>',
									$attributes,
									$device_name
								);
							}
						break;
						case 'os_version':
							if( empty($rec->os_version) ) {
								printf(
									'<td %1$s>%2$s</td>',
									$attributes,
									' - '
								);
							} else {
								printf(
									'<td %1$s>%2$s</td>',
									$attributes,
									esc_html($rec->os_version)
								);
							}
						break;
						case 'date':
							printf(
								'<td %1$s>%2$s</td>',
								$attributes,
								esc_html(date( $date_format, strtotime($rec->post_modified)))
							);
						break;
					}
				}
			}
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