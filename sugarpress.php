<?php
/*
Plugin Name: SugarPress
Plugin URI: http://www.wilsonlabs.net
Description: SugarPress is a seamless and modular integration of Sugar CRM into WordPress. 
Version: 2.0.0a
Author: Will Wilson
Author URI: http://www.wilsonlabs.net
License: GPLv2 or later
*/

    //  installation options
    $sugar_icon = plugins_url('/imgs/sugar_icon.ico', __FILE__);
    $sugarapi = 'lib/sugarapi.php';
    
    //  register settings
    add_action('admin_init', 'sugarpress_options_init' );
    function sugarpress_options_init() {
        
        //  settings options
        register_setting( 'sugarpress_settings', 'sugar_url');
        register_setting( 'sugarpress_settings', 'sugar_user');
        register_setting( 'sugarpress_settings', 'sugar_pwd');
        register_setting( 'sugarpress_settings', 'sugarpress_metadata');
        register_setting( 'sugarpress_settings', 'sugarpress_users');
        
    }
    
    //  include functions file
    include('lib/functions.php');
    require_once($sugarapi);

    include('sugarforms.php');
    
    //  add any sugar forms to menu if specified
    function sugarforms_menu_items($items) {
        //  query sugarforms
        $args = array( 'post_type' => 'sugarform','meta_key' => 'sugarform_display_menu','meta_value'=>'1','meta_compare'=>'=');
        $loop = new WP_Query( $args );
        //  add to menu
        if($loop->found_posts > 0) {
            foreach($loop->posts as $post) {
                $new_item = wp_setup_nav_menu_item($post);
                $items[] = $new_item;
                //print_r($new_item);
            }
        }
        return $items;
    }
    add_filter('wp_get_nav_menu_items','sugarforms_menu_items');
    

    
    //  create admin menu
    add_action('admin_menu', 'sugarpress_admin');
    function sugarpress_admin() {
		if(function_exists('add_menu_page')) {
            global $sugar_icon;
			$capability = 'manage_options';
			//	create main page
			add_menu_page( 'SugarPress', 'SugarPress', $capability, 'sugarpress-menu', 'sugarpress_main', $sugar_icon,104);
            
            //	SugarCRM Settings Page
			$settings_page = add_submenu_page('sugarpress-menu', 'SugarCRM Settings', 'SugarCRM Settings', $capability, 'sugarpress-menu', 'sugarpress_main');
            
            
		}
	}

    function sugarpress_main() {
		sugarsettings();
	}

    
    function sugarsettings() {
     if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		include('sugarsettings.php');
    }
    function eg_setting_section_callback_function() {
        echo 'new settings!';
    }

    //  include shortcodes
    include('shortcodes.php');

    
    //remove_filter('the_content', 'wpautop');
?>