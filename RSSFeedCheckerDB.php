<?php

/**
 * @author Andy Clark
 * @copyright 2011
 * A class to encapsulate all of the access to the link tables
 * 
 * Todo: See this with regards "Prepare" and passing in parameters
 * http://codex.wordpress.org/wpdb_Class#Protect_Queries_Against_SQL_Injection_Attacks
 * 
 * N.B. The drop table code is not here but in uninstall.php
 * 
 */

class RSSCheckerDB {
    
    static $tablename = 'links_rss'; //When using this prefix with  $wpdb->prefix;
    
    static function init()
    {
        if (is_admin())
        {
        add_action('edit_link',array('RSSCheckerDB', 'rss_update'));
        add_action('add_link',array('RSSCheckerDB', 'rss_update'));
        add_action('delete_link', array('RSSCheckerDB', 'rss_delete'));
        add_action('RSSCheckerCheckDB', array('RSSCheckerDB', 'check_db'));
        }
    }
    
    static function rss_delete($link_ID) {
            global $wpdb;
            $tblrss = $wpdb->prefix .self::$tablename ;
            $wpdb->query("DELETE FROM $tblrss where link_id = $link_ID");
        }

    static function rss_update($link_ID) {
        	global $wpdb;
            
            $wpdb->update( $wpdb->links , array('link_updated' => Date("Y-m-d H:i:s")), array('link_id' => $link_ID) );
            
            $tblrss = $wpdb->prefix . self::$tablename;
            $rss = $wpdb->get_var("SELECT link_rss FROM $wpdb->links where link_id = $link_ID" );
            $rowexists = $wpdb->get_var("SELECT COUNT(link_id) FROM $tblrss where link_id = $link_ID" );
            
            //If source RSS is now blank then delete
            //If exists then update else edit.
            if (empty($rss)) {
                   self::rss_delete($link_ID);
                }
            else{
                if ($rowexists == 0) {
                    $wpdb->insert($tblrss,array( 'link_id' => $link_ID,'rss_address' => $rss ));
                }
                else {
                    $wpdb->query("UPDATE $tblrss SET rss_address = '$rss',last_checked = '0000-00-00 00:00:00' ,last_updated = '0000-00-00 00:00:00', latest_item_url = '',latest_item_title = '' WHERE link_id = $link_ID and rss_address <> '$rss'");
                }
            }
        }
    
        static function rss_list($count) {
        //Return an array? of upto $count links
            global $wpdb;
            $tblrss = $wpdb->prefix . self::$tablename;
            $links=$wpdb->get_results("SELECT l.link_id,l.link_name,l.link_url,rss_address,latest_item_url,latest_item_title,last_updated FROM $tblrss r inner join $wpdb->links l on r.link_id = l.link_id where latest_item_url is not null and l.link_visible = 'Y' order by last_updated desc limit 0, $count");
            return $links;          
        }
        
        static function rss_getitem($link) {
        //Return the rss details of the selected link
            return self::rss_getlink($link->link_id);
        }
        
        static function rss_getlink($linkid) {
        //Return the rss details of the selected link
            global $wpdb;
            $tblrss = $wpdb->prefix . self::$tablename;
            $rss=$wpdb->get_row("SELECT link_id,rss_address,last_checked,last_updated,latest_item_url,latest_item_title FROM $tblrss where link_id = $linkid");
            return $rss;
        }        
        
        static function rss_getnext() {
        //Get first record ordered by last checked, last updated (exclude records recently updated or checked)
        //Return the rss details of the next item to check
            global $wpdb;
            $tblrss = $wpdb->prefix .self::$tablename;
            $rss=$wpdb->get_row("SELECT link_id,rss_address,last_checked,last_updated,latest_item_url,latest_item_title FROM $tblrss order by last_checked, last_updated limit 0,1");
            return $rss;         
        }
        
        static function rss_countpending(){
        //count those that have not been "recently" checked
       	    global $wpdb;
            $tblrss = $wpdb->prefix . self::$tablename;
            $datebefore = self::datebefore();
            $rss = $wpdb->get_var("SELECT count(*) FROM $tblrss where last_checked < '$datebefore'" );
            return $rss;
        }
        
        static function datebefore()
        {
            //What is the date before which we need to check dates again?
            $date = new DateTime();
            $hours = RSSCheckerSettings::get_freq();
            $mod = sprintf('-%d hours',$hours);
            $date->modify($mod);
            return $date->format('Y-m-d H:i:s');
            
        }
        
        static function rss_countall(){
            global $wpdb;
            $tblrss = $wpdb->prefix . self::$tablename;
            $rss = $wpdb->get_var("SELECT count(*) FROM $tblrss" ); 
            return $rss;
        }
        
        static function rss_updateitem($rssitem) {
        //Update the various fields of the rss link item
            global $wpdb;
            $tblrss = $wpdb->prefix . self::$tablename;
            $wpdb->update( $tblrss , array('last_checked' => $rssitem->last_checked,
                                           'last_updated' => $rssitem->last_updated,
                                           'latest_item_url' => $rssitem->latest_item_url,
                                           'latest_item_title' => $rssitem->latest_item_title),
                                     array('link_id' => $rssitem->link_id) );
        }
        
        static function check_db(){
        //Are the DB Tables there? If not then add them in and populate them with data
            if (!self::is_db_ok()) {
                self::db_install();
                self::db_refresh();
            }
        }                    
        
         static function is_db_ok() {
         //Check db tables
            global $wpdb;
            $tblrss = $wpdb->prefix . self::$tablename;
            
            $tablecheck = $wpdb->get_var("SELECT Table_Name FROM information_schema.TABLES WHERE Table_Name = '$tblrss'" ); 
            if (empty($tablecheck))
            {
                do_action('RSSStatus',__('RSS Links table missing','rssfeedchecker'));
                return false;
            }
         
            return true;
         }
         
         static function db_install() {
    
          //Install / Upgrade database tables
         do_action('RSSStatus',__('Installing tables....','rssfeedchecker'));
         //run install / upgrade as necessary
         
         global $wpdb;
         $tblrss = $wpdb->prefix . self::$tablename;
         $wpdb->query("CREATE TABLE IF NOT EXISTS $tblrss (
                          link_id bigint(20) NOT NULL,
                          rss_address varchar(255) NOT NULL,
                          last_checked datetime default '0000-00-00 00:00:00',
                          last_updated datetime default '0000-00-00 00:00:00',
                          latest_item_url varchar(255) default NULL,
                          latest_item_title varchar(255) default NULL,
                          PRIMARY KEY  (link_id),
                          KEY ix_checked (last_checked),
                          KEY ix_updated (last_updated)
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHARACTER SET utf8 COLLATE utf8_general_ci;
                      ");
         }

         static function db_refresh() {
            do_action('RSSStatus',__('Refreshing data....','rssfeedchecker'));
            global $wpdb;
            $tblrss = $wpdb->prefix . self::$tablename;
            $wpdb->query("insert $tblrss(link_id,rss_address) select link_id,link_rss from $wpdb->links where link_rss is not null and link_rss <> '' and link_id not in (select link_id from $tblrss)");
         }        
        
    }

?>