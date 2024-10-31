<?php

/**
 * @author Andy Clark
 * @copyright 2012
 * 
 * Tidy up things left over by the plugin
 * Keep this as simple as possible so we don't stall the uninstallation process
 */

    do_action('RSSStatus',__('Removing settings...','RSS'));
    delete_option('RSSChecker');

    do_action('RSSStatus',__('Removing scheduled event...','RSS'));
    wp_clear_scheduled_hook("RSSCheckerCronEvent");                

    do_action('RSSStatus',__('Dropping links table...','RSS'));
    global $wpdb;
    $tblrss = $wpdb->prefix . 'links_rss';
    $wpdb->query("DROP TABLE IF EXISTS $tblrss");
            

?>