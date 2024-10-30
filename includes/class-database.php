<?php

namespace Heimdall;

use DateInterval;
use DateTime;

defined('HEIMDALL_VER') || die;

class Database
{

    private static $db_version = "1.0.0";

    static function init()
    {
        self::check_db();

        add_filter('heimdall__core_uninstall-options' , function($options){
            $options[] = 'db_version';
            return $options;
        });
    }

    static function insert(
        $ip,
        $page,
        $type,
        $hook,
        $uri,
        $ua,
        $is_mobile = 0,
        $os = null,
        $browser = null,
        $meta = null,
        $metav2 = null,
        $time = null,
        $blog = null,
        $user = null
    ) {
        global $wpdb;

        self::check_params($time, $blog, $user);

        if(!empty($ua)){
            $salt = Helpers::get_salt( self::entity('agent_salt') );
            $ua = Cryptor::encrypt($ua , $salt);
        }

        $wpdb->insert(
            self::get_table(),
            [
                'time' => $time,
                'ip' => $ip,
                'page' =>  $page,
                'type' =>  $type,
                'blog' => $blog,
                'user' => $user,
                'hook' => $hook,
                'uri' => $uri,
                'ua' => $ua,
                'is_mobile' =>  $is_mobile,
                'os' => $os,
                'browser' => $browser,
                'meta' => $meta,
                'meta_v2' => $metav2,
                'dbv' => self::get_version()
            ]
        );

        echo $wpdb->last_error;
    }


    static function insert_once(
        $ip,
        $page,
        $type,
        $hook,
        $uri,
        $ua,
        $is_mobile = 0,
        $os = null,
        $browser = null,
        $meta = null,
        $metav2 = null,
        $time = null,
        $blog = null,
        $user = null
    ) {
        global $wpdb;

        self::check_params($time, $blog, $user);

        $query = self::get_record_exists_query($ip, $page, $type, $hook, $meta, 7, $time, $blog, $user);

        $count = $wpdb->get_var($query);

        if ($count == 0) {
            self::insert(
                $ip,
                $page,
                $type,
                $hook,
                $uri,
                $ua,
                $is_mobile,
                $os,
                $browser,
                $meta,
                $metav2,
                $time,
                $blog,
                $user
            );
        }
    }


    static function get_record_exists_query($ip, $page, $type,  $filter, $meta = null, $threshold = 3,  $time = null, $blog = null, $user = null)
    {

        $table_name = self::get_table();

        self::check_params($time, $blog, $user);

        $start = new DateTime($time);

        $start->sub(new DateInterval("PT{$threshold}S"));

        $start_date = $start->format('Y-m-d H:i:s');

        $page_param = is_null($page) ? "`page` IS NULL" : "`page` = '$page'";

        $meta_param = is_null($meta) ? "`meta` IS NULL" : "`meta` = '$meta'";

        return  "SELECT COUNT(*) FROM `$table_name` WHERE `ip` = '$ip' AND 
                    $page_param AND 
                    $meta_param AND
                    `type` = '$type' AND 
                    `hook` = '$filter' AND
                    `blog` = '$blog' AND
                    `user` = '$user' AND
                    `time` BETWEEN '$start_date' AND '$time'";
    }


    private static function check_params(&$time, &$blog, &$user)
    {

        if ($blog == null)
            $blog = get_current_blog_id();

        if ($user == null)
            $user = get_current_user_id();

        if ($time == null)
            $time = current_time('mysql', 1);
    }


    private static function check_db(){
        global $wpdb;

        $dbv = get_option( self::entity('db_version') , '');

        if ($dbv == self::get_version()) {
            return;
        }

        $table_name = self::get_table();

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                blog bigint(20) NOT NULL,
                time datetime DEFAULT '0000-01-01 00:00:00' NOT NULL,
                ip tinytext,
                page bigint(20),
                type smallint,
                user smallint,
                hook tinytext,
                os tinytext,
                browser tinytext,
                is_mobile smallint,
                uri text,
                ua text,
                meta tinytext,
                meta_v2 text,
                dbv tinytext,
                PRIMARY KEY  (id)
                ) $charset_collate;";


        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        update_option( self::entity('db_version') , self::get_version() );
    }


    static function get_version()
    {
        return self::$db_version;
    }

    static function get_table()
    {
        return self::entity('activities');
    }

    static function get_heimdall_tables(){
        return apply_filters('heimdall__db_table_names' , [
            self::entity('activities')
        ]);
    }

    static function entity($suffix){
        return "dcp_heimdall_" . $suffix;
    }
}
