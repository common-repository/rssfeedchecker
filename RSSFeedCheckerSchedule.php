<?php

/* 
RSS Feedchecker cron 

Ref: Demo Cron by Roland Rust
     WP-Cron Dashboard - used for analysis status of Cron Jobs

*/

    class RSSCheckerScheduler {  
        static public function init()  
        {  
            add_filter('cron_schedules', array('RSSCheckerScheduler','CustomSchedule'));
            add_action('RSSCheckerCheckSchedule', array('RSSCheckerScheduler', 'checkschedule'));
            add_action('RSSCheckerActivate', array('RSSCheckerScheduler', 'checkschedule'));
            add_action('RSSCheckerDeactivate', array('RSSCheckerScheduler', 'removeschedule'));
        }

        static function checkschedule()
        {
            if (!wp_next_scheduled('RSSCheckerCronEvent')) {
                do_action('RSSStatus',__('Adding schedule','rssfeedchecker'));
    			wp_schedule_event(time(), 'Half-hourly', 'RSSCheckerCronEvent');
    		}
        }
        
        static function removeschedule(){
            do_action('RSSStatus',__('Removing schedule','rssfeedchecker'));
    		wp_clear_scheduled_hook('RSSCheckerCronEvent');
        }
        
        static function getschedule(){
            $crons = _get_cron_array();
  			 foreach ( $crons as $timestamp => $cron ) {
    			 if ( isset( $cron['RSSCheckerCronEvent'] ) ) {
    			         foreach ($cron['RSSCheckerCronEvent'] as $mycron)
                         {
                            $mycron['Next'] = $timestamp;
                            return $mycron ;
                        }
                        break;
    			 }
  			 } 
        }
        
        static function getnext(){
            $mycron = self::getschedule();
            if (isset( $mycron )){
                return $mycron['Next'];
            }
        }
        
    
    /* a reccurence has to be added to the cron_schedules array */
    static function CustomSchedule($recc) {
    	$recc['Half-hourly'] = array('interval' => 60*30, 'display' => __('Half-hourly','rssfeedchecker'));
    	return $recc;
    }
    
    static function cron_installed() {
        return (wp_next_scheduled('RSSCheckerCronEvent'));
    }
    
}

?>
