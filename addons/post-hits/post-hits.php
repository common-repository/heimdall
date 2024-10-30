<?php

namespace Heimdall\Addons;

use Heimdall\Addon;
use Heimdall\Database;

class PostHits extends Addon
{

    static function setup()
    {
        add_filter("the_content", [__CLASS__, "filter_content"]);
        add_filter("the_excerpt", [__CLASS__, "filter_excerpt"]);

        add_shortcode('top-posts', [ __CLASS__ , "top_posts_shortcode"] );
    }

    static function top_posts_shortcode($atts = [], $content = null)
    {

        global $wpdb;

        $template = "";

        if (empty($content)) {
            $template = "<a href='{{permalink}}'>{{post_title}} - {{hits}}</a><br>";
        } else {
            $template = $content;
        }

        extract(shortcode_atts([
            'params' => 'unique',
            'hook'  => '',
            'limit' => 5
        ], $atts));

        $query = self::get_most_visited_posts_query(
            explode(',', strtolower(trim($params))),
            $limit,
            $hook
        );

        $posts = $wpdb->get_results($query, ARRAY_A);

        $result = "";

        foreach ($posts as $post) {

            $permalink = get_post_permalink($post["ID"]);

            $item = str_replace("{{permalink}}", $permalink, $template);

            $item = str_replace("{{hits}}", $post["records"], $item);

            foreach (['post_title', 'guid', 'post_content', 'post_excerpt', 'ID'] as $param) {
                $item = str_replace("{{{$param}}}", $post[$param], $item);
            }

            $result .= $item;
        }

        return $result;
    }


    private static function get_most_visited_posts_query($params = [], $limit = 5, $hook = '')
    {

        global $wpdb;

        $table_name = Database::get_table();

        $records = "COUNT(*)";

        $timeCheck = "";

        $hookCheck = "";

        $limit = esc_sql($limit);

        if (in_array('unique', $params)) {
            $records = "COUNT(DISTINCT activity.ip)";
        }

        if (in_array('today', $params)) {
            $timeCheck = "AND DATE(`activity.time`)=CURDATE()";
        }

        if (!empty($hook)) {
            $hookCheck = esc_sql($hook);
            $hookCheck = "AND activity.hook = $hook";
        }

        return "SELECT  post.* , $records AS records FROM `$table_name` AS activity , `$wpdb->posts` AS post
                        WHERE activity.page = post.ID $timeCheck $hookCheck
                        GROUP BY post.ID 
                        ORDER BY `records` DESC 
                        LIMIT $limit";
    }


    static function filter_excerpt($excerpt)
    {

        $option = '';

        if (is_single()) {
            $option = Database::entity('post_position');
        }

        if (empty($option)) {
            return $excerpt;
        }

        $pos = get_option($option, 0);

        $sc =  "[statistic]";

        switch ($pos) {
            case 3:
                return $excerpt . $sc;
            case 4:
                return $sc . $excerpt;
            default:
                return $excerpt;
        }
    }

   

    static function filter_content($content)
    {

        $option = '';

        if (is_page()) {
            $option = Database::entity('page_position');
        }

        if (is_single()) {
            $option = Database::entity('post_position');
        }

        if (empty($option)) {
            return $content;
        }

        $pos = get_option($option, 0);

        $sc =  "[statistic]";

        switch ($pos) {
            case 1:
                return $content . $sc;
            case 2:
                return $sc . $content;
            default:
                return $content;
        }
    }
}
