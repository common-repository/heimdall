<?php

namespace Heimdall\Addons;

use Heimdall\Database;

class WorldMapDatabase
{

    static $db_version = '1.0.0';
    static $countries_db_version = '1.0.0';

    /**
     * @since 1.3.1
     */
    static function get_find_ip_query($ip)
    {

        $ip_table_name = Database::entity('ip_table');

        return "SELECT * FROM $ip_table_name WHERE `ip` = '$ip'";
    }



    /**
     * @since 1.3.1
     */
    static function insert_ip_data($ip, $data)
    {

        global $wpdb;

        $country_code = isset($data["country_code"]) ? $data["country_code"] : "unknown";

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


        $wpdb->insert(
            Database::entity('ip_table'),
            [
                'ip' => $ip,
                'country_code' => $country_code,
                'data' => $json,
            ]
        );
    }



    /**
     * @since 1.3.1
     */
    static function check_ip_table()
    {

        global $wpdb;

        $table_name  = Database::entity('ip_table');
        $db_opt_name = Database::entity('world_map_ip_table_db_version');

        $dbv = get_option($db_opt_name, '');

        if ($dbv == self::$db_version) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                ip tinytext NOT NULL,
                country_code tinytext,
                data text,
                PRIMARY KEY  (id)
                ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        update_option($db_opt_name, self::$db_version);
    }


    /**
     * @since 1.3.1
     */
    static function get_world_map_data_query()
    {

        // countries table name
        $ctn = Database::entity('countries_table');

        $activities_tb = Database::get_table();

        $query = "SELECT
                        ct.country_name,
                        ct.country_code,
                        ct.lat,
                        ct.lng,
                        (SELECT COUNT(*) FROM $activities_tb WHERE meta_v2 LIKE concat('%\"country_code\": \"' , ct.country_code , '\"%')) AS records
                      FROM $ctn ct";


        return $query;
    }


    /**
     * @since 1.3.1
     */
    static function check_countries_table()
    {

        global $wpdb;

        $table_name  = Database::entity('countries_table');
        $db_opt_name = Database::entity('world_map_countries_table_db_version');

        $dbv = get_option($db_opt_name, '');

        if ($dbv == self::$countries_db_version) {
            return;
        }

        

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                country_code tinytext NOT NULL,
                country_name tinytext,
                lat double,
                lng double,
                PRIMARY KEY  (id)
                ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        update_option($db_opt_name, self::$countries_db_version);

        self::seed_contries_table();
    }



    /**
     * @since 1.3.1
     */
    private static function seed_contries_table()
    {

        global $wpdb;

        $table_name = Database::entity('countries_table');

        $json = file_get_contents(__DIR__ . "/assets/countries.json");

        $data = json_decode($json, true);

        $data = array_map(function ($item) {

            return [
                "name" => $item["name"],
                "code" => $item["code"],
                "lat" => $item["latlng"][0],
                "lng" => $item["latlng"][1],
            ];
        }, $data);

        if (empty($data)) {
            return;
        }

        $wpdb->query("TRUNCATE TABLE  $table_name");

        $values = array_map(function ($item) {
            return "('{$item['code']}', '{$item['name']}' , '{$item['lat']}' , '{$item['lng']}')";
        }, $data);

        $values = join(", ", $values);

        $wpdb->query("INSERT INTO $table_name (`country_code`, `country_name`, `lat`, `lng`) VALUES $values");

        echo $wpdb->last_error;
    }
}
