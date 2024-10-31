<?php

/*
Display the top N blog roll items sorted by date
and link to blog roll page if it exists.

Either widget or shortcode [LinksRSSEnhanced count="number"]

Nice enhancement would be filter the links by category
Nice enhancement would be to show icons

*/
    class RSSCheckWidget extends WP_Widget {
       static function init(){
            add_action('widgets_init', create_function('', 'return register_widget("RSSCheckWidget");'));
            add_shortcode('LinksRSSEnhanced', array('RSSCheckWidget','RenderShortcode' ));
       } 
        
        // [LinksRSSEnhanced count="number"]
       static function RenderShortcode( $atts ) {
        	extract( shortcode_atts( array(
        		'count' => 5,
        	), $atts ) );
	    return self::renderlinks($count);
        }

        
       function RSSCheckWidget() {
        //Constructor
       $widget_ops = array('classname' => 'RSSCheckWidget', 'description' => __('A widget to show blog roll links ordered by most recent','rssfeedchecker'));
       $this->WP_Widget('RSSCheckWidget', __('Links RSS Enhanced','rssfeedchecker'), $widget_ops);
       
       if ( is_active_widget( false, false, $this->id_base ) && !is_admin() ) {
            wp_enqueue_style("RSSFeedCheckerStyle",plugins_url('/RSSFeedChecker.css', __FILE__));
        }
       }

        public function form_input_text($field,$caption,$value) {
         printf ('<p><label for="%s">%s: <input class="widefat" id="%s" name="%s" type="text" value="%s" " /></label></p>',$this->get_field_id($field),esc_html($caption),$this->get_field_id($field),$this->get_field_name($field),esc_attr($value));
        }
        
        public function form_input_option($field,$caption,$value,$options){
            
        foreach ($options as $option) {    
            if ($option == $value) {$select = 'selected="selected"'; } else {$select = '';}
            $opthtml = sprintf('%s<option %s>%s</option>',$opthtml,$select,$option );
        }
        printf('<p><label for="%s">%s:</label><select id="%s" name="%s" class="widefat" >%s</select></p>',$this->get_field_id($field),esc_html($caption),$this->get_field_id($field),$this->get_field_name($field),$opthtml);
        }
        
        public function form_input_checkbox($field,$caption,$value){
            if ($value) {
                $checked = 'checked="checked"';
            }
            printf ('<p><input id="%s" class="checkbox" type="checkbox" name="%s" %s /><label for="%s"> %s</label></p>',$this->get_field_id($field),$this->get_field_name($field),$checked,$this->get_field_id($field),esc_html($caption));
    }
        
       function widget($args, $instance) {
        // prints the widget to the user
            extract($args, EXTR_SKIP);
    
            $title = apply_filters('widget_title', $instance['title']);
            $widget_id = $args['widget_id'];
            $count = absint($instance['count']);
     
            echo $before_widget;
            if ( !empty( $title ) ) { 
                echo $before_title . $title . $after_title; 
            };

            echo self::renderlinks($count);
        
            echo $after_widget;    
       }
       
       function renderlinks($count){
            $htmlout = '<ul class="RSSFeedBlogRoll blogroll">';
            $links = RSSCheckerDB::rss_list($count);
            foreach ($links as $link){
                $htmlout = $htmlout . sprintf('<li class="blogrollitem"><a href="%s" class="blogrollbloglink">%s</a><p style="margin-left:px;"><a href="%s" class="blogrollfeedlink">%s</a><br />%s</p></li>',esc_url($link->link_url),esc_html($link->link_name),esc_url($link->latest_item_url),esc_html($link->latest_item_title),self::pretty_date($link->last_updated));
            }
            $htmlout = $htmlout . '</ul>';
            
            // display link to blog roll page if it exists
            global $wpdb;
            $blogrollid = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name='Blog-Roll'");      
            $link = get_permalink($blogrollid);
            if ($link != '') {
                $htmlout = $htmlout . sprintf('<p><a href="%s">%s</a></p>',$link,__('Show all links','rssfeedchecker'));
            }

            return $htmlout;
       }
       
       function pretty_date($date)
       {

            //Based on http://www.zachleat.com/web/php-pretty-date/
            //Changed strings to match that of javascript version used for twitter.
            //Added Localisation of strings
            $compareTo = time();

            $diff =  abs($compareTo - strtotime($date));
            $dayDiff = floor($diff / 86400);
    
            if(is_nan($dayDiff) || $dayDiff < 0) {
                $dtPretty = '';
            }
            else
            {
                if($dayDiff == 0) {
                    if($diff < 60) {
                        $dtPretty =  __('less than a minute ago','rssfeedchecker');
                    } elseif($diff < 120) {
                        $dtPretty =  __('about a minute ago','rssfeedchecker');
                    } elseif($diff < 3600) {
                        $dtPretty =  sprintf(__('%u minutes ago','rssfeedchecker'),floor($diff/60));
                    } elseif($diff < 7200) {
                        $dtPretty =  __('about an hour ago','rssfeedchecker');
                    } elseif($diff < 86400) {
                        $dtPretty =  sprintf(__('%u hours ago','rssfeedchecker'),floor($diff/3600));
                    }
                } elseif($dayDiff == 1) {
                    $dtPretty =  __('Yesterday','rssfeedchecker');
                } elseif($dayDiff < 7) {
                    $dtPretty =  sprintf(__('%u days ago','rssfeedchecker'),ceil($dayDiff));
                } elseif($dayDiff == 7) {
                    $dtPretty =  __('1 week ago','rssfeedchecker');
                } elseif($dayDiff < (7*6)) { // Modifications Start Here
                    // 6 weeks at most
                    $dtPretty =   sprintf(__('%u weeks ago','rssfeedchecker'),ceil($dayDiff/7));
                } elseif($dayDiff < 365) {
                    $dtPretty =  sprintf(__('%u months ago','rssfeedchecker'),ceil($dayDiff/(365/12)));
                } else {
                    //Return the date
                    $dtPretty =  $date;
                }
            }
            
            return sprintf('<abbr class="datetime" title="%s">%s</abbr>',date("c",strtotime($date)) ,$dtPretty);
        } 

        
       function update($new_instance, $old_instance) {
        //save the widget
        $instance = $old_instance;
        //check and save the new values
        $instance['title'] = strip_tags($new_instance['title']);
        $count = absint(strip_tags($new_instance['count'])); 
        if ($count > 50) { $count = 50;}
        if ($count < 1) { $count = 1;}
        $instance['count'] = $count;
            
        return $instance;
       }
        
       function form($instance) {
        //admin ui
        $instance = wp_parse_args( (array) $instance, array( 'title' => __('Blog Roll','rssfeedchecker'), 'count' => 5 ) );
        $title = strip_tags($instance['title']);
        $count = absint($instance['count']);

        $this->form_input_text('title',__('Title','rssfeedchecker'),$title);
        $this->form_input_text('count',__('Maximum number of links to display (default = 5)','rssfeedchecker'),$count);

        
       }
    
       } 


?>