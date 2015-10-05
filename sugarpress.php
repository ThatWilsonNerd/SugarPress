<?php
/*
Plugin Name: SugarPress
Plugin URI: http://www.emery-jackson.com/sugarpress
Description: SugarPress is a seamless and modular integration of Sugar CRM into WordPress. 
Version: 2.0.0
Author: Emery-Jackson Technologies
Author URI: http://www.emery-jackson.com
License: GPLv2 or later
*/

    //  installation options
    $sugar_icon = plugins_url('/imgs/sugar_icon.ico', __FILE__);
    $sugarapi = 'sugarapi-2.0.0.php';
    
    //  register settings
    add_action('admin_init', 'sugarpress_options_init' );
    function sugarpress_options_init() {
        //  main options
        register_setting( 'sugarpress', 'sugarportal_installed' );
        update_option('sugarportal_installed',true);
        register_setting( 'sugarpress', 'sugarforms_installed' );
        update_option('sugarforms_installed',true);
        register_setting( 'sugarpress', 'sugarblog_installed' );
        update_option('sugarblog_installed',false);
        register_setting( 'sugarpress', 'sugarview_installed' );
        update_option('sugarview_installed',false);
        
        //  settings options
        register_setting( 'sugarpress_settings', 'sugar_url');
        register_setting( 'sugarpress_settings', 'sugar_user');
        register_setting( 'sugarpress_settings', 'sugar_pwd');
        register_setting( 'sugarpress_settings', 'sugarpress_metadata');
        register_setting( 'sugarpress_settings', 'sugarpress_users');
        
        //  portal options
        register_setting( 'sugarportal_settings', 'sp_enable_login');
        register_setting( 'sugarportal_settings', 'sp_login_module');
        register_setting( 'sugarportal_settings', 'sp_login_id');
        register_setting( 'sugarportal_settings', 'sp_login_pwd');
        register_setting( 'sugarportal_settings', 'sp_login_email');
        register_setting( 'sugarportal_settings', 'sp_login_fname');
        register_setting( 'sugarportal_settings', 'sp_login_lname');
        register_setting( 'sugarportal_settings', 'sp_sync_field1');
        register_setting( 'sugarportal_settings', 'sp_landing_page');
        register_setting( 'sugarportal_settings', 'sp_protected_pages');
        register_setting( 'sugarportal_settings', 'sp_disable_wp_login');
    }
    
    //  include functions file
    include('lib/functions.php');
    require_once('lib/'.$sugarapi);

    if(get_option('sugarforms_installed') == true) {
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
    }

    //  sugarportal stuff
    if(get_option('sugarportal_installed') == true) {
    
        //  disable admin bar for all users except WP admins
        function sp_remove_admin_bar() {
            if (!current_user_can('administrator') && !is_admin()) {
                show_admin_bar(false);
            }
        }
        add_action('after_setup_theme', 'sp_remove_admin_bar');

        //  SugarPortal Login Widget
        include('sugarportal-login-widget.php');
        
        //  protect pages when enabled (ie. prevent from displaying in menu or prevent direct access)
        if(get_option('sp_enable_login') == true) {
            function sugarportal_landing( $template_path ) {
                //  get landing and protected pages
                $protected = get_option('sp_protected_pages');
                $protected[] = get_option('sp_landing_page');
                if(in_array(get_the_ID(),$protected)) {
                    if(!is_user_logged_in()) {
                        //  send user to login page
                        wp_redirect( wp_login_url( get_permalink() ) ); exit;
                    }
                }
                return $template_path;
            }
            add_filter('template_include','sugarportal_landing',1);
            
            //  prevent protected and landing pages from showing in nav menu
            function sugarportal_menu_items($items) {
                //  get landing and protected pages
                $new_items = array();
                $protected = get_option('sp_protected_pages');
                $protected[] = get_option('sp_landing_page');
                foreach($items as $item) {
                    //print_r($item);
                    if(in_array($item->object_id,$protected)) {
                        if(is_user_logged_in()) {
                            $new_items[] = $item;
                        }
                    }
                    else {
                        $new_items[] = $item;
                    }
                }
                return $new_items;
            }
            add_filter('wp_get_nav_menu_items','sugarportal_menu_items');
        }
        
    }
    
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
            
            //  SugarPortal
            if(get_option('sugarportal_installed') == true) {
                add_submenu_page('sugarpress-menu', 'SugarPortal', 'SugarPortal', $capability, 'sugarpress-portal', 'sugarportal');
            }
            
			//  SugarBlog
            if(get_option('sugarblog_installed') == true) {
                add_submenu_page('sugarpress-menu', 'SugarBlog', 'SugarBlog', $capability, 'sugarpress-blog', 'sugarblog');
            }
            
            //	SugarView
            if(get_option('sugarview_installed') == true) {
                add_submenu_page('sugarpress-menu', 'SugarView', 'SugarView', $capability, 'sugarpress-menu', 'sugarview');
            }
            
		}
	}

    function sugarpress_main() {
		sugarsettings();
	}

    function sugarportal() {
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		include('sugarportal.php');
    }
    
    function sugarview() {
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		include('sugarview.php');
    }
    
    function sugarblog() {
        if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		include('sugarblog.php');
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

    
    remove_filter('the_content', 'wpautop');
?>