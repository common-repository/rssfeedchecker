=== RSS Feed Checker ===
Contributors: Workshopshed
Tags: RSS, Blogroll
Donate link: http://www.workshopshed.com/
Requires at least: 2.8
Tested up to: 5.6.2
Stable tag: 1.1
License: GPLv2 or later

== Description ==

The RSS Feed checker looks through your blogroll for links with rss feeds and then stores the name and url of the latest article.

The Links RSS Enhanced widget then displays the latest links in descending order.

The idea for the widget comes from the blogroll widget on blogger.

== Installation ==

= Installation =

1. Upload all the files to a new directory in the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit the RSS Feed Checker Settings to initialise Cron Job and Create Database tables.
1. From Appearance section of the dashboard select widgets
1. Drag the "Links RSS Enhanced" widget into your selected sidebar
1. Enter caption and number of links to show

= Removal =

Because the plugin adds in a database table, options and a cron job (schedule) you need to remove the plugin via Wordpress rather than just deleting it in FTP

1. Deactivate the plugin
1. Delete the plugin

If you only deactivate plugin the setting and table will be kept, only the cron job will be disabled.

== Frequently Asked Questions ==

= Where are the settings? =

The settings are in the links admin panel under RSS Feed Checker

= Where do I enter the RSS feed details? =

The location of the feed can be added into the "advanced" box of the add/edit link screen. To assist with this, an additional metabox has been added that you can turn on with the screen options at the top of the screen. 

= I have a lot of links will this overload my website? =

Firstly, only the links with feeds are processed. The plugin runs every 30 minutes and you can control how long it will run for and how long it will wait for feeds to respond. The server load limit can be configured to stop the checker running if your server is busy.

= How can I display the feed using a shortcode rather than the widget? =

Use the following shortcode syntax.

[LinksRSSEnhanced count="number"]

== Screenshots ==

1. Extra meta box on each of the links
2. Links Menu
3. Blogroll sorted by date
4. Widget UI
5. Link screen options
6. Settings

== Changelog ==

= 1.1 =

* Added the correct details to allow the internationalisation to work automatically.
* Changed textdomain from RSS to rssfeedchecker

= 1.0 =

* The link for show all links now only shows if you've got a page called "Blog-Roll" otherwise is supressed.

= 0.9 =

* Small change to ensure that the Link Manager appears in 3.5

= 0.8 =

* Fix for missing function microtime_float (which was defined in a different plugin) thanks to rotwp for spotting the issue.

= 0.7 =

* Initial release

== Upgrade Notice ==

* Initial release

== Technical section ==

The plugin will create an extra table in your wordpress database to store the latest feed updates, this will be called links_rss with the prefix for your database e.g. wp_links_rss

A Cron (schedule) is created to run every 30 minutes and process links. Note that Cron in WordPress depends on hits, your WordPress website receives. So playing with this plugin within an offline environment (MAMP, XAMPP, WAMP etc.) without anyone or anything triggering the scheduling by sending requests to the WordPress page won't produce any results if you do not trigger it by yourself.

== References ==
A selection of places I've found help for this plugin.

Thanks also to Ozh, Roland Rust and for their explainations and examples

= Admin UI =
* http://codex.wordpress.org/Function_Reference/add_meta_box
* http://codex.wordpress.org/Administration_Menus#Page_Hook_Suffix
* http://wpengineer.com/1295/meta-links-for-wordpress-plugins/
* http://www.code-styling.de/english/how-to-use-wordpress-metaboxes-at-own-plugins
* http://planetozh.com/blog/2008/02/wordpress-snippet-add_meta_box/
* http://wptheming.com/2011/08/admin-notices-in-wordpress/
* http://www.prelovac.com/vladimir/improving-security-in-wordpress-plugins-using-nonces

= Ajax =
* http://return-true.com/2010/01/using-ajax-in-your-wordpress-theme-admin/
* http://amiworks.co.in/talk/simplified-ajax-for-wordpress-plugin-developers-using-jquery/
* http://xplus3.net/2008/10/16/jquery-and-ajax-in-wordpress-plugins-administration-pages/
* http://www.garyc40.com/2010/03/5-tips-for-using-ajax-in-wordpress/
     
= Database =     
* http://createmy.com.au/connecting-your-plugin-to-the-wordpress-database/
* http://webjawns.com/2009/08/the-wordpress-wpdb-class-explained/

= Scheduling =
* http://wpseek.com/source/wp/latest/nav.html?wp-includes/cron.php.html
* Ideas on background processing from the broken link checker

= Settings =
* http://codex.wordpress.org/Settings_API - new way to handle options/settings         
* http://ottopress.com/2009/wordpress-settings-api-tutorial/
* http://planetozh.com/blog/2009/05/handling-plugins-options-in-wordpress-28-with-register_setting/
* http://wpengineer.com/2139/adding-settings-to-an-existing-page-using-the-settings-api/
* http://blog.gneu.org/2010/09/intro-to-the-wordpress-settings-api/
* http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/

= RSS Feeds =
* http://jaewon.mine.nu/jaewon/2010/10/30/how-to-make-your-wordpress-a-rss-reader/
* http://codex.wordpress.org/Plugin_API/Filter_Reference/wp_feed_cache_transient_lifetime
* http://simplepie.org/

