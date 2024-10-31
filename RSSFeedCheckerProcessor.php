<?php

/**
 * @author Andy Clark
 * @copyright 2011
 * A class for processing of the RSS feeds
 * Todo check handling of some of the feeds, miller welds and 2020 media not processing correctly? 
 *
 * If a feed has feed burner configured then it redirects, wordpress is reporting this as a problem rather than following it
 * Error being raised is from wp-includes\class-http.php line 1118
 * There is a patch for class-http
 * http://core.trac.wordpress.org/changeset/20208
 * Is fixed in Wordpress 3.4

 */

include_once(ABSPATH . WPINC . '/feed.php');

class RSSCheckerProcessor {
    
       const RSSProcTran = 'RSSCheckerProcessor';
       
       static function init() {
            add_action('RSSCheckerCronEvent', array('RSSCheckerProcessor', 'work'));
            add_action('RSSCheckerDeactivate', array('RSSCheckerProcessor', 'stop')); //Remove Transients
       }
       
       static function work(){
       //Loosly based on broken link checker work function
       
       $execution_start_time = microtime_float(true);
       $max_execution_time = RSSCheckerSettings::get_max_run()*1000;
       
       $run = 0;

       if (self::running()) {
            return -1; //Already processing
       }
       if (self::server_too_busy()) {
            return -2;
       }    
       if (RSSCheckerDB::rss_countpending() == 0) { 
            return -3; //Nothing to process
       }
       if (microtime_float(true) - $execution_start_time > $max_execution_time) {
            return -4; //Timeout
       }

       self::run();

       while ($run == 0)
       {
            self::processnext();
            if (self::server_too_busy()) {
                $run = -2;
            }    
            if (RSSCheckerDB::rss_countpending() == 0) { 
               $run = -3;
            }
            if (microtime_float(true) - $execution_start_time > $max_execution_time) {
               $run = -4;
            }            
       }
              
       self::stop();
       return $run;
       }
    
       /* Simple locking mechanism */
       static function running() {
       //return boolean already running?
            if ( false === get_transient( self::RSSProcTran ) ) {
                return false;
            }
            else
                return true;
       }
       static function run() {
            set_transient( self::RSSProcTran, 'In Progress', RSSCheckerSettings::get_max_run()*2 );
       }
       static function stop() {
            delete_transient( self::RSSProcTran);
       }
        

       static function processnext()
       {
            $rss = RSSCheckerDB::rss_getnext();
            if ($rss != "")
                {
                    self::checklink ($rss);
                } 
       }
    
       static function checklink($rssitem){
            
            if (!empty($rssitem)){
                $workresult = self::scanfeed($rssitem);
                RSSCheckerDB::rss_updateitem($rssitem);
            }
            return $workresult;
        } 
    
        static function feed_settings (&$feed)
        {
            $feed->set_timeout(RSSCheckerSettings::get_timeout());
        }
        
        static function feed_cache_duration( $seconds )
        {
        // change the default feed cache (in seconds)
        // set the cache timeout to be half the refresh frequency
            return 60*30*RSSCheckerSettings::get_freq();
        }

       static function scanfeed($rssitem) {
            //Scan feed for updates
            if (!isset($rssitem->rss_address)) { return;}
            
            do_action('RSSStatus',sprintf(__('Processing feed: %s','rssfeedchecker'),$rssitem->rss_address));
            
            $rssitem->last_checked = Date("Y-m-d H:i:s");
            
            $url = $rssitem->rss_address;
            
            add_filter('wp_feed_cache_transient_lifetime' , array('RSSCheckerProcessor', 'feed_cache_duration' ));
            add_action('wp_feed_options', array('RSSCheckerProcessor', 'feed_settings' ) );
                
            $rss = fetch_feed($url); // Get a SimplePie feed object from the specified feed source.
                
            remove_filter('wp_feed_cache_transient_lifetime' , array('RSSCheckerProcessor', 'feed_cache_duration' ));    
            remove_action('wp_feed_options', array('RSSCheckerProcessor', 'feed_settings' ) );    
                              
            if (!is_wp_error( $rss ) ) {  //Get the top item
                
                $maxitems = $rss->get_item_quantity();
    
                if ($maxitems == 0) {
                    $workresult = 'No items in feed';
                }
                else {
                    $rss_latest = $rss->get_item(0); 
                    $rssitem->latest_item_url = $rss_latest->get_permalink();
                    $rssitem->last_updated = $rss_latest->get_date("Y-m-d\TH:i:s");
                    $rssitem->latest_item_title = $rss_latest->get_title();
                    $workresult = "ok";
                    unset($rss_latest);
                    }
                }
            else {
                $workresult = $rss->get_error_message();
            }
            unset($rss);

            return $workresult;
       }
       
   /**
    * From Broken Link Checker 
   * Get the server's load averages.
   *
   * Returns an array with three samples - the 1 minute avg, the 5 minute avg, and the 15 minute avg.
   *
   * @param integer $cache How long the load averages may be cached, in seconds. Set to 0 to get maximally up-to-date data.
   * @return array|null Array, or NULL if retrieving load data is impossible (e.g. when running on a Windows box). 
   */
	function get_server_load($cache = 5){
		static $cached_load = null;
		static $cached_when = 0;
		
		if ( !empty($cache) && ((time() - $cached_when) <= $cache) ){
    		  floatval(reset($cached_load));
		}
		
		$load = null;
		
		if ( function_exists('sys_getloadavg') ){
			$load = sys_getloadavg();
		} else {
			$loadavg_file = '/proc/loadavg';
	        if (@is_readable($loadavg_file)) {
	            $load = explode(' ',file_get_contents($loadavg_file));
	            $load = array_map('floatval', $load);
	        }
		}
		
		$cached_load = $load;
		$cached_when = time();
		return floatval(reset($load));
	}
    
     /**
   * Check if server is currently too overloaded to run the link checker.
   *
   * @return bool
   */
	function server_too_busy(){
		if ( !RSSCheckerSettings::get_loadlimit() ){
			return false;
		}
		
		$loads = self::get_server_load();
		if ( empty($loads) ){
			return false;
		}
		
		return $loads > RSSCheckerSettings::get_loadlimit();
	}

      
    }

?>