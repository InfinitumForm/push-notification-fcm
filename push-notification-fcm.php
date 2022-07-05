<?php
/**
 * Plugin Name:       Firebase Push Notification
 * Plugin URI:        https://wordpress.org/plugins/push-notification-fcm/
 * Description:       Firebase Cloud Messaging (FCM) to iOS and Android when content is published or updated.
 * Version:           1.0.0
 * Author:            Ivijan-Stefan Stipić
 * Author URI:        https://profiles.wordpress.org/ivijanstefan/
 * Requires at least: 5.0
 * Tested up to:      6.0
 * Requires PHP:      7.0
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fcmpn
 * Domain Path:       /languages
 * Network:           true
 *
 * Copyright (C) 2022 Ivijan-Stefan Stipic
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
 
// If someone try to called this file directly via URL, abort.
if ( ! defined( 'WPINC' ) ) { die( "Don't mess with us." ); }
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Define plugin file (changes not allowed)
if ( ! defined( 'FCMPN_FILE' ) ) {
	define( 'FCMPN_FILE', __FILE__ );
}

// Define plugin path (changes not allowed)
if ( ! defined( 'FCMPN_ROOT' ) ) {
	define( 'FCMPN_ROOT', rtrim(plugin_dir_path( FCMPN_FILE ), '\\/') );
}

// Define plugin url (changes not allowed)
if ( ! defined( 'FCMPN_URL' ) ) {
	define( 'FCMPN_URL', rtrim(plugin_dir_url( FCMPN_FILE ), '\\/') );
}

// Includes
if ( ! defined( 'FCMPN_INC' ) ) {
	define( 'FCMPN_INC', FCMPN_ROOT . '/includes' );
}

################
## Start plugin
################
if(!class_exists('FCM_Push_Notification')) : class FCM_Push_Notification {
	
	/*
     * Main construct
	 */
	private static $run;
	private function __construct(){
		self::include_once( FCMPN_INC . '/Settings.php' );
		self::include_once( FCMPN_INC . '/REST.php' );
		self::include_once( FCMPN_INC . '/Devices.php' );
		self::include_once( FCMPN_INC . '/Metabox.php' );
		self::include_once( FCMPN_INC . '/API.php' );
		
		add_action( 'init', [&$this, 'init'] );
		
		FCMPN_Settings::instance();
		FCMPN_REST::instance();
		FCMPN_Metabox::instance();
		FCMPN_API::instance();
	}
	
	
	/*
     * initialize plugin functionality
	 */
	public function init(){
		
		if(!post_type_exists('fcmpn-devices')) {
			register_post_type( 'fcmpn-devices', [
				'labels'				=> [
					'name'               		=> __( 'Devices','fcmpn' ),
					'singular_name'      		=> __( 'Device','fcmpn' ),
					'add_new'            		=> __( 'Add New Device','fcmpn'),
					'add_new_item'       		=> __( "Add New Device",'fcmpn'),
					'edit_item'          		=> __( "Edit Device",'fcmpn'),
					'new_item'           		=> __( "New Device",'fcmpn'),
					'view_item'          		=> __( "View Device",'fcmpn'),
					'search_items'       		=> __( "Search Devices",'fcmpn'),
					'not_found'          		=> __( 'No Device Found','fcmpn'),
					'not_found_in_trash' 		=> __( 'No Device Found in Trash','fcmpn'),
					'parent_item_colon'  		=> '',
					'featured_image'	 		=> __('Device Image','fcmpn'),
					'set_featured_image'		=> __('Select Device Image','fcmpn'),
					'remove_featured_image'		=> __('Remove Device Image','fcmpn'),
					'use_featured_image'		=> __('Use Device Image','fcmpn'),
					'insert_into_item'			=> __('Insert Into Device','fcmpn')
				],
				'public'            	=> false,
				'exclude_from_search'	=> true,
				'publicly_queryable'	=> false, 
				'show_in_nav_menus'   	=> false,
				'show_ui'           	=> false,
				'query_var'         	=> true,
				'hierarchical'      	=> false,
				'menu_position'     	=> 20,
				'capability_type'   	=> 'post',
				'supports'          	=> [],
				'show_in_menu'			=> false
			] );
		}
		
		if(!taxonomy_exists( 'fcmpn-subscriptions' ))
		{
			register_taxonomy(
				'fcmpn-subscriptions', 'fcmpn-devices',
				[
					'labels'			=> [
						'name' 					=> __('Subscriptions','fcmpn'),
						'singular_name' 		=> __('Subscription','fcmpn'),
						'menu_name' 			=> __('Subscription','fcmpn'),
						'all_items' 			=> __('All Subscription','fcmpn'),
						'edit_item' 			=> __('Edit Subscription','fcmpn'),
						'view_item' 			=> __('View Subscription','fcmpn'),
						'update_item' 			=> __('Update Subscription','fcmpn'),
						'add_new_item' 			=> __('Add New Subscription','fcmpn'),
						'new_item_name' 		=> __('New Subscription Name','fcmpn'),
						'parent_item' 			=> __('Parent Subscription','fcmpn'),
						'parent_item_colon' 	=> __('Parent Subscription','fcmpn'),
					],
					'hierarchical'		=> false,
					'show_ui'			=> true,
					'public'		 	=> false,
					'label'          	=> __('Subscriptions','fcmpn'),
					'singular_label' 	=> __('Subscription','fcmpn'),
					'rewrite'        	=> true,
					'query_var'			=> false,
					'show_tagcloud'		=> false,
					'show_in_nav_menus'	=> false,
					'hierarchical'		=> false
				]
			);
		}
	}

	
	/*
	 * The include_once statement includes and evaluates the specified file during the execution of the script.
	 *
	 * @param  $path
	 *
	 * @return bool
	 */
	public static function include_once( $path ) {
		if( ! is_array($path) ) {
			$path = [$path];
		}

		if( '\\' === DIRECTORY_SEPARATOR ) {
			$path = array_map(function($include){
				return str_replace('/', DIRECTORY_SEPARATOR, $include);
			}, $path);
		}
		
		$i = 0;
		foreach($path as $include){
			if( file_exists($include) ) {
				include_once $include;
				++$i;
			}
		}
		
		return ($i > 0);
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

##############
## Run plugin
##############
FCM_Push_Notification::instance();