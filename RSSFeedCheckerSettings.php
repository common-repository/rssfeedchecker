<?php

/**
 * @author Andy Clark
 * @copyright 2011
 * Settings class
 * 
 * don't need to worry about too many calls to get_option as the actual
 * db reads are cached (apparently) otherwise would want to change this to a
 * non static class that reads setting on start           
 * 
 * Might be worth looking at how the settings are defaulted, this does it on read but we could do it on install
 * 
 */

    class RSSCheckerSettings {
        
        static function init(){
                add_action('admin_init', array('RSSCheckerSettings', 'registersetting') );
        }
        
        static function registersetting(){
            register_setting( 'RSSChecker_options', 'RSSChecker', array('RSSCheckerSettings', 'validate'));
        	add_settings_section('RSSChecker_settings', 'Settings', array('RSSCheckerSettings', 'show_settings_header'),'RSSChecker_settings_main');
        	add_settings_field('rssrecheckfreq', 'Recheck Frequency', array('RSSCheckerSettings', 'show_freq'), 'RSSChecker_settings_main', 'RSSChecker_settings');
        	add_settings_field('rsstimeout', 'Timeout (s)', array('RSSCheckerSettings', 'show_timeout'), 'RSSChecker_settings_main', 'RSSChecker_settings');
        	add_settings_field('rssmaxrun', 'Runtime (s)', array('RSSCheckerSettings', 'show_runtime'), 'RSSChecker_settings_main', 'RSSChecker_settings');
        	add_settings_field('rssloadlimit', 'Server Load limit', array('RSSCheckerSettings', 'show_loadlimit'), 'RSSChecker_settings_main', 'RSSChecker_settings');
        }

        static function form_input_text($field,$name,$caption,$value) {
            printf ('<input id="%s" name="%s" type="text" value="%s" /><label for="%s"> %s</label>',$field,$name,esc_attr($value),$field,esc_html($caption));
        }
    
        static function show_freq()
        {
            self::form_input_text('frequency','RSSChecker[frequency]','How many hours to wait before rechecking a blog (1 - 99)',self::get_freq());
        }
        static function show_timeout()
        {
            self::form_input_text('timeout','RSSChecker[timeout]','How many seconds to wait for feed to respond (10 - 60)',self::get_timeout());
        }        
        static function show_runtime()
        {
            self::form_input_text('maxrun','RSSChecker[maxrun]','How many seconds spend processing links (10 - 360)',self::get_max_run());
        }
        static function show_loadlimit()
        {
            self::form_input_text('serverload','RSSChecker[serverload]','Limit this process if server is busy (0.10 - 5.00)',self::get_loadlimit());
        }                 
    
        static function show_settings_header() {
            //Nothing to do just yet.
        }            
        
        static function validate_range($varint,$minint,$maxint){
            $varint = absint($varint);
            if ($varint < $minint) {$varint = $minint;}
            if ($varint > $maxint) {$varint = $maxint;}
            return $varint;
        }
        
        static function validate($input)
        {   
            
//See http://codex.wordpress.org/Function_Reference/add_settings_error for reporting errors            
            $options = get_option('RSSChecker');
            $options['frequency'] = self::validate_range($input['frequency'],1,99);
            $options['timeout'] = self::validate_range($input['timeout'],10,60);   
            $options['maxrun'] = self::validate_range($input['maxrun'],10,360);
            $options['serverload'] = self::validate_range($input['serverload']*100.0,10,500)/100.0;  //floar
            return $options;
        }
        
        //Now the settings
        static function db_version()
        {
            $options = get_option('RSSChecker');
            $dbver = $options['dbversion'];
            if (empty($dbver)){
                $dbver = '1.0.0';
            }
            return $dbver;
        }

        static function get_max_run(){
        //How long the link check should run for before being terminated
        //
            $options = get_option('RSSChecker');
            $runtime = $options['maxrun']; 
            if (empty($runtime)){
                $runtime = 180;
            }
            return $runtime;
        }

        static function get_freq(){
        //How often to check links e.g. a link will be re-checked if it is more than x hours old
            $options = get_option('RSSChecker');
            $frequency = $options['frequency']; 
            if (empty($frequency)){
                $frequency = 4;
            }
            return $frequency;
        }
        
        static function get_loadlimit(){
            $options = get_option('RSSChecker');
            $loadlimit = $options['serverload']; 
            if (empty($loadlimit)){
                $loadlimit = 0.8;
            }
            return $loadlimit;
        }        

        static function get_timeout(){
        //If a site does not respond in this time then check it again later
        //time in S
            $options = get_option('RSSChecker');
            $timeout = intval($options['timeout']);
            if (empty($timeout)){
                $timeout = 30;
            }
            return $timeout; 
        }
        
        
        
    }
   

?>