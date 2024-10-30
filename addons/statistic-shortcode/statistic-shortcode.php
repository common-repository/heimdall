<?php

namespace Heimdall\Addons;

use Heimdall\Addon;
use Heimdall\Database;
use Heimdall\Helpers;
use Heimdall\Options;

class StatisticShortcode extends Addon
{

    private static $query_params = ['unique',   'today', 'visitors',  'network'];

    static function setup()
    {
        add_shortcode('statistic', [__CLASS__, "statistic_shortcode"]);
    }


    static function statistic_shortcode($atts = [], $content = null)
    {

        global $wpdb;

        extract(shortcode_atts([
            'class'  => '',
            'params' => 'unique',
            'hook'  => '',
            'tag'   => 'p'
        ], $atts));

        $query = self::get_shortcode_query(
            explode(',', strtolower(trim($params))),
            $hook
        );

        $count = $wpdb->get_var($query);

        $style = self::get_style($count);

        $class = apply_filters("heimdall__statistic-class" , $class);

        $tag = apply_filters("heimdall__statistic-tag" , strtolower(trim($tag)));

        $title = apply_filters("heimdall__statistic-title" , esc_html__("Views:" , "heimdall"));

        $result = "<$tag data-statistic-value=\"$count\" class=\"$class statistic-$style\">" .
            "<span class=\"statistic-title\">$title</span>" .
            "<span class=\"statistic-value\">$count</span>" .
            "</$tag>";

        return apply_filters("heimdall__statistic-html", $result);
        
    }


    /**
     * @since 1.0.0
     */
    private static function get_style($v)
    {

        $result = $v < 10 ? "lt-10" : "gt-5m";

        $factor = 1;

        for($i = 1 ; $i < 10 && $v > 10 ; $i++){
            $factor *= 10;
            $ms = $factor;
            $me = $factor * 5;
            if($v < $me && $v > $ms){
                $result =  strtolower("lt-" . Helpers::number_shorten($me) . " gt-" . Helpers::number_shorten($ms));
            }
        }

        return $result;

    }


    static function get_shortcode_query($params = [], $hook = '')
    {
        global $post;

        $table_name = Database::get_table();

        $query = "SELECT ";

        /**
         * $query_params data:
         *      0 is unique
         *      1 is today
         *      2 is visitors
         *      3 is network
         */
        if (in_array('unique', $params)) {
            $query = $query . "COUNT(DISTINCT ip)";
        } else {
            $query = $query . "COUNT(*)";
        }


        $query = $query . " FROM $table_name";

        $today = in_array('today', $params);

        if ($today) {

            $query = $query . " WHERE DATE(`time`)=CURDATE()";
        }

        if (!in_array('visitors', $params)) {

            $prefix =  $today ? " AND " : " WHERE ";

            if (is_home()) {
                $query = $query . "{$prefix}type='1'";
            } else if (isset($post)) {
                $query = $query . "{$prefix}page='$post->ID'";
            }
        } else if (!in_array('network', $params)) {

            $prefix =  $today ? " AND " : " WHERE ";

            $blog = get_current_blog_id();

            $query = $query . "{$prefix}blog='$blog'";
        }

        if (empty($hook)) {
            $hook = Options::get_statistic_hook();
        }

        if (strpos($query, 'WHERE') !== false) {
            $query = $query . " AND hook='$hook'";
        } else {
            $query = $query . " WHERE hook='$hook'";
        }

        return $query;
    }
}
