<?php
/*
Plugin Name: RSS Feed Checker
Plugin URI: #
Description: Checks your links RSS feeds and stores the date that it was last modified so that it can be used in a Links RSS Enhanced widget 
Version: 1.1
Author: Andy Clark.
Author URI: #
Text Domain: rssfeedchecker

*/

    include_once dirname( __FILE__ ) . '/RSSFeedCheckerSettings.php';  
    include_once dirname( __FILE__ ) . '/RSSFeedCheckerSchedule.php';
    include_once dirname( __FILE__ ) . '/RSSFeedCheckerDB.php';
    include_once dirname( __FILE__ ) . '/RSSFeedCheckerProcessor.php';        
    include_once dirname( __FILE__ ) . '/RSSFeedCheckerWidget.php'; 
    include_once dirname( __FILE__ ) . '/RSSFeedCheckerUI.php';

    class RSSChecker {  

        //This class is our main plugin class that glues everything together.              
        static public function init()  
        {  
            add_action('init',array('RSSChecker', 'init_locale'));
      
            if (is_admin())
            {
                add_action('admin_menu',array('RSSChecker', 'menu_init'));
                add_filter('plugin_row_meta',array('RSSChecker', 'set_plugin_meta'), 10, 2 );
                
                //New for 3.5 a trick from http://wordpress.org/extend/plugins/link-manager/ to
                //ensure that the link manager stays enabled
                add_filter( 'pre_option_link_manager_enabled', '__return_true' );
            }

           RSSCheckerSettings::init();   //Initialise settings           
           RSSCheckerDB::init();         //Initialise db layer
           RSSCheckerScheduler::init();  //Initialise scheduler
           RSSCheckerUI::init();         //Initialise Admin UI
           RSSCheckerProcessor::init();  //Initialise RSS Feed Processor
           RSSCheckWidget::init();       //Initialise Widget
    
        }  

        static function activate() {
            do_action('RSSCheckerActivate');
        }

        static function deactivate() {
            do_action('RSSCheckerDeactivate');
        }
        
        static function init_locale() {
           	load_plugin_textdomain('rssfeedchecker',false, dirname( plugin_basename( __FILE__ ) ) . '/locale/');
        }

        //Add all of the admin menu
        static function menu_init() {
            $hook_suffix = add_links_page( 'RSS Feed Checker', 'RSS Feed Checker', 'manage_links', 'RSSFeedChecker', array('RSSCheckerUI', 'show_adminpage') );
            add_action( 'admin_head-'. $hook_suffix ,  array('RSSChecker', 'pageload') );
            add_action( 'admin_print_scripts-' . $hook_suffix ,  array('RSSChecker', 'scriptload') );
        }
        
        //Remap the load-xxxx? function to a known action so we can respond only to our page.
        static function pageload() {
            do_action('RSSAdminPageLoad');
        }

        //Remap the admin_print_script-xxxx? function to a known action so we can respond only to our page.
        static function scriptload() {
            do_action('RSSAdminScriptLoad');
        }
        
        static function PageURL(){
            $url = admin_url('/link-manager.php?page=RSSFeedChecker');
            return $url;
        }
        
        //Add a link to the above menu page from the plugins page
        static function set_plugin_meta($links, $file) {
            $plugin = plugin_basename(__FILE__);
            if ($file == $plugin) {
                return array_merge($links,array( sprintf( '<a href="%s">%s</a>',self::PageURL(), __('Settings','rssfeedchecker') ) ), array( sprintf( '<a href="widgets.php">%s</a>', __('Widgets','rssfeedchecker') ) ));
            }
            return $links;
        }
        
    }  
    
    /**
     * Timing function
     */
    $version = explode('.', PHP_VERSION);

    if ($version[0] < 5) { 
        if ( !function_exists( 'microtime_float' ) ) {
        	function microtime_float()
        	{
        	    list($usec, $sec) = explode(" ", microtime());
        	    return ((float)$usec + (float)$sec);
        	}
        }
    }
    else {
        if ( !function_exists( 'microtime_float' ) ) {
        	function microtime_float()
        	{
        	    return  microtime(true);
        	}
        }
    } 
    
    //Instantiate the plugin
    RSSChecker::init();
    
    register_deactivation_hook(__FILE__,  array('RSSChecker', 'deactivate'));
    register_activation_hook(__FILE__, array('RSSChecker', 'activate'));
    //N.B. Uninstall is handled by separate file not a hook


?>