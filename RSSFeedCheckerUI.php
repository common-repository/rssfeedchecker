<?php

/**
 * @author Andy Clark
 * @copyright 2011
 */

    class RSSCheckerUI {  
        //Admin User Interface
              
        static public function init()  
        { 
            if (is_admin()){
            //Load the scripts for our admin page and also for the link page with our metabox on it
            add_action('RSSAdminScriptLoad', array('RSSCheckerUI','loadscripts'));
            add_action('add_meta_boxes_link' , array('RSSCheckerUI','loadscripts'));

            //Extra box on the link form
           	add_action('add_meta_boxes',array('RSSCheckerUI', 'add_link_metabox'));      
               
            //Display install warning
            if (!RSSCheckerDB::is_db_ok()){
                add_action('admin_notices', array('RSSCheckerUI', 'show_admin_notice') );
            }
            
            //Button responses, we are using the same proc to handle all of the requests
            add_action('wp_ajax_RSSCheckerProcNext', array('RSSCheckerUI','ajaxResponseAdmin'));
            add_action('wp_ajax_RSSCheckerRefresh', array('RSSCheckerUI','ajaxResponseAdmin'));
            add_action('wp_ajax_RSSCheckerProcLink', array('RSSCheckerUI','ajaxResponseMeta'));
            add_action('wp_ajax_RSSCheckerRefreshLink', array('RSSCheckerUI','ajaxResponseMeta'));
            }
        }
        
        static function loadscripts(){
            // embed the javascript file that makes the AJAX request
            wp_enqueue_script( 'RSSChecker', plugin_dir_url( __FILE__ ) . 'ajax.js', array( 'jquery' ) );
            wp_localize_script( 'RSSChecker', 'RSSChecker', array(
                                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                                    'check' => wp_create_nonce( 'RSSChecker-nonce' ),
                                    )
            );            
        }
        
        static function show_admin_notice() {
        //Show admin screen if we are not installed and we are not on the screen that's about to install it
                global $current_screen;
                if ($current_screen->id != 'links_page_RSSFeedChecker') {
                    $url = sprintf( '<a href="%s">%s</a>',RSSChecker::PageURL(), __('Settings','rssfeedchecker'));
                    echo '<div class="error"><p>'.sprintf(__('RSS Feedchecker is not yet initialised, visit the RSS Feed Checker %s to install new database table and verify settings','rssfeedchecker'),$url).'</p></div>';
                    }
        }
        
        static function add_link_metabox() {
        	add_meta_box(
        		'linkmodifieddiv', // id of the <div> we'll add
        		'RSS Feed Checker', //title
        		 array('RSSCheckerUI', 'show_linkmeta'), // callback function that will echo the box content
        		'link', // where to add the box: on "post", "page", or "link" page
        		'advanced'  // location, 'normal', 'advanced', or 'side'
        	);
           
        }
        
                  
        // This function echoes the content of our meta box on the 
        static function show_linkmeta($link) {

//todo: Add code to search do the "RSS feed discovery"
//http://keithdevens.com/weblog/archive/2002/Jun/03/RSSAuto-DiscoveryPHP


            printf('<p><label for="rss_uri">%s</label> <input name="link_rss" class="code" type="text" id="rss_uri" value="%s" size="50" style="width: %s" /></p>',__('RSS Address','rssfeedchecker'),isset( $link->link_rss ) ? esc_attr($link->link_rss) : '','95%');

            if (! empty($link->link_id))
            {
            $rss = RSSCheckerDB::rss_getitem($link);
            
            //Show empty form to be populated by the ajax following the load
            printf ('<p>%s: <span id="RSSLinkChecked"></span></p>',__('Last Checked','rssfeedchecker'));
            printf ('<p>%s: <span id="RSSLinkUpdated"></span></p>',__('Last Modified','rssfeedchecker'));
            printf ('<p>%s: <a id="RSSLinkDetail" href="#"></a></p>',__('Lastest article','rssfeedchecker'));

//Issue here: If the user has changed the value but not saved it then we would be processing the wrong feed

               
            self::show_ajaxbutton('#',__('Refresh','rssfeedchecker'),'RSSCheckerRefreshLink','RSSCheckerLink','data_link_id="'.$link->link_id.'"');
            echo '&nbsp;';
            self::show_ajaxbutton('#',__('Process Link','rssfeedchecker'),'RSSCheckerProcLink','RSSCheckerLink','data_link_id="'.$link->link_id.'"');
            echo '&nbsp;<span id="RSSLinkMessage"></span>'; 
            }
            else
            { printf ('<p>%s</p>',__('New Link','rssfeedchecker'));
            }
            
           }
         
         static function show_adminpage($link) {

            //Hook up status proc
            add_action('RSSStatus', array('RSSCheckerUI','show_status'));

           	echo '<div class="wrap">';
            echo '<div id="icon-link-manager" class="icon32"><br></div>';
    		echo '<h2>'.__('RSS Feed Checker','rssfeedchecker').'</h2>';
            
//The information here could be moved to one of those new help pannels            
            $widgetlink = sprintf('%s <a href="widgets.php">%s</a>',__('Links RSS Enhanced','rssfeedchecker'),__('widget','rssfeedchecker'));           
            printf ('<p>%s<br /><br />%s</p>',__('The RSS Feed checker will look through your blogroll links for those with RSS feeds. It will check those feeds to see when they were last updated.','rssfeedchecker'),
                    sprintf(__('This allows the %s to show the links in descending order of last updated along with a link to the latest article and a message stating how long since the last update.','rssfeedchecker'),$widgetlink));
            
            echo '<div id="RSSCheckerAjaxStatus"></div>';
            do_action('RSSCheckerCheckSchedule');
            do_action('RSSCheckerCheckDB');
            self::show_admin_status();
            self::show_settings_form();
            
            echo '</div>';
            
            //Disable status proc
            remove_action('RSSStatus', array('RSSCheckerUI','show_status'));            
           } 

        static function show_admin_status() {
            
            //Display empty labels to be populated by ajax call            
            echo '<div style="padding: 10px; border: 1px solid #cccccc; margin-bottom: 10px">';
            
            printf ('<h3>%s</h3>',__('Link Stats','rssfeedchecker'));
            printf ('<p>%s:&nbsp;<span id="RSSPending">&nbsp;</span>&nbsp;',__("Links pending",'rssfeedchecker'));
            printf ('%s:&nbsp;<span id="RSSLinks">&nbsp;</span></p>', __('Links total','rssfeedchecker'));
            printf ('<p>%s:&nbsp;<span id="RSSLoad">&nbsp;</span></p>', __('Server load','rssfeedchecker'));
            printf ('<p>%s:&nbsp;<span id="RSSDBVer">&nbsp;</span></p>', __('RSS Checker table version','rssfeedchecker'));
            
   			printf('<h3>%s</h3>',__('Schedule','rssfeedchecker'));
    		printf('<p>%s:&nbsp;<span id="RSSTime">&nbsp;</span></p>',__('Time now','rssfeedchecker'));
    		printf('<p>%s:&nbsp;<span id="RSSNext">&nbsp;</span></p>',__('Schedule will be triggered','rssfeedchecker'));

            printf('<p><span style="display: none;" id="RSSRunning">%s</span>&nbsp;',__('RSS Link checker in progress','rssfeedchecker'));
            echo('<span id ="RSSMessage">&nbsp;</span>&nbsp;</p>');
            
            self::show_ajaxbutton('#',__('Refresh','rssfeedchecker'),'RSSCheckerRefresh','RSSChecker','');
            echo '&nbsp;';
            self::show_ajaxbutton('#',__('Process Next Link','rssfeedchecker'),'RSSCheckerProcNext','RSSChecker','style="display: none;"'); //default to hidden
           
            echo '</div>';
        }

        static function formatdate($date)
        {
            return date(get_option('date_format'),$date).' '.date('H:i:s',$date);
        }
        
        static function ajaxResponseAdmin(){

                // get the submitted parameters
                $nonce = $_POST['check'];
                $ajaxsuccess = true;
               
                $execution_start_time = microtime_float(true);
                
                if ( ! wp_verify_nonce( $nonce, 'RSSChecker-nonce' ) )  {
                        $message = 'Security check failed';
                        $action = 'Error';
                        $ajaxsuccess = false;
                }  
                else {
                        $action = $_POST['action'];
                }
                if ($action == 'RSSCheckerProcNext')
                {
                    try {
                         //RSSCheckerProcessor::processnext();
                         $workresult = RSSCheckerProcessor::work();
                    }
                    catch (Exception $e) {
                        $message = $e->getMessage();
                        $ajaxsuccess = false;
                     }
                }
            
             
                // generate the response
    
                $response = json_encode( 
                        array( 'success' => $ajaxsuccess, 
                               'message' => $message,
                               'action' => $action,
                               'pending' => RSSCheckerDB::rss_countpending(),
                               'links' => RSSCheckerDB::rss_countall(),
                               'load' => RSSCheckerProcessor::get_server_load(),
                               'time' => self::formatdate(time()),
                               'next' => self::formatdate(RSSCheckerScheduler::getnext()),
                               'nextlink' => RSSCheckerDB::rss_getnext()->rss_address,
                               'running' => RSSCheckerProcessor::running(),
                               'dbver' => RSSCheckerSettings::db_version(),
                               'duration' => microtime_float(true) - $execution_start_time,
                               'workresult' => $workresult )
                        );

            ob_clean(); //Discard any debug messages or other fluff already sent
            header( 'Content-Type: application/json' );
            echo $response;
            exit; // IMPORTANT: don't forget to 'exit'
        }
        
        static function ajaxResponseMeta(){

                // get the submitted parameters
//Check had wierd behaviour where the date format changes slightly dependant on refresh vs process
                
                $nonce = $_POST['check'];
                $ajaxsuccess = true;
                
                if ( ! wp_verify_nonce( $nonce, 'RSSChecker-nonce' ) )  {
                        $message = 'Security check failed';
                        $action = 'Error';
                        $ajaxsuccess = false;
                }  
                else {
                        $action = $_POST['action'];
                }
                
                $linkid = $_POST['linkid'];
                $rssitem=RSSCheckerDB::rss_getlink($linkid);
                
                if ($action == 'RSSCheckerProcLink')
                {
                    try {

                         $workresult = RSSCheckerProcessor::checklink($rssitem);
                    }
                    catch (Exception $e) {
                        $message = $e->getMessage();
                        $ajaxsuccess = false;
                     }
                }                
             
                // generate the response
                $response = json_encode( 
                        array( 'success' => $ajaxsuccess, 
                               'message' => $message,
                               'action' => $action,
                               'lastupdate' => $rssitem->last_updated,
                               'lastchecked' => $rssitem->last_checked,
                               'lasturl' => esc_url($rssitem->latest_item_url),
                               'lasttitle' => esc_html($rssitem->latest_item_title),
                               'linkid' => $linkid,
                               'workresult' => $workresult )
                        );

            ob_clean(); //Discard any debug messages or other fluff already sent
            header( 'Content-Type: application/json' );
            echo $response;
            exit; // IMPORTANT: don't forget to 'exit'
        }        

        static function show_ajaxbutton($url,$caption,$id,$class,$display)
        {   
            printf ('<a id="%s" class="button %s" href="%s" %s>%s</a>',$id,$class,$url,$display,$caption);
        }
        
        //Show messages (mostly sent by database class)         
        static function show_status($message) {
            printf ('<p>%s</p>',$message);
        }

       
    static function show_settings_form() {

        echo '<br />';
        echo '<div style="padding: 10px; border: 1px solid #cccccc; margin-bottom: 10px">';
        echo '<form method="post" action="options.php">' ;
        settings_fields('RSSChecker_options'); 
        do_settings_sections('RSSChecker_settings_main');

        printf('<p class="submit"><input name="Submit" type="submit" class="button-primary" value="%s" /></p>',__('Save Settings','rssfeedchecker'));
        echo('</form>');
        echo('</div>');
    
        } 
       
}
?>