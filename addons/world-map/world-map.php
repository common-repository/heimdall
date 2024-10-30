<?php

namespace Heimdall\Addons;

use Heimdall\Addon;
use Heimdall\Dashboard;
use Heimdall\Database;

class WorldMap extends Addon
{

    private static $service = "http://geolocation-db.com/";


    /**
     * @since 1.3.1
     */
    static function setup()
    {
        self::load_libs(["world-map-database.php"]);

        WorldMapDatabase::check_countries_table();

        WorldMapDatabase::check_ip_table();

        add_action("admin_enqueue_scripts", [__CLASS__, "admin_enqueue_scripts"]);
        add_action("wp_ajax_heimdall_world_map", [__CLASS__, "get_dashboard_world_map_data"]);

        add_filter("heimdall__core_record-metadata-v2", [__CLASS__, "add_country_data"]);
        add_filter("heimdall__core_localize-script", [__CLASS__, "map_data_url"], 10, 1);
        add_filter("heimdall__widget_tabs", [__CLASS__, "widget_tabs"]);
        add_filter("heimdall__widget_content", [__CLASS__, "widget_content"]);
        add_filter("heimdall__com_handle-request" , [__CLASS__, "handle_com_request"]);

        add_filter('heimdall__core_uninstall-options' , function($options){
            $options[] = 'world_map_ip_table_db_version';
            $options[] = 'world_map_countries_table_db_version';
            return $options;
        });
    }


    static function map_data_url($data)
    {
        $data['worldMapDataURL'] = HEIMDALL_URL . '/addons/world-map/assets/countries-50m.json';
        return $data;
    }


    static function widget_tabs($tabs_html)
    {
        return $tabs_html . Dashboard::create_widget_tab(
            self::get_slug(),
            esc_html__("Countries", "heimdall"),
            'admin-site-alt3'
        );
    }


    static function widget_content($tabs_html)
    {
        return $tabs_html . Dashboard::create_widget_tab_content(
            self::get_slug(),
            '<canvas id="statisticsWorldMapDataContainer" ></canvas>'
        );
    }


    /**
     * @since 1.3.1
     */
    static function admin_enqueue_scripts()
    {
        $screen = get_current_screen();
        if (!current_user_can('update_core') || $screen->id  !== 'dashboard') {
            return;
        }
        self::enqueue_script("hmd-chart-js-geo", '/assets/js/chartjs-geo.js', true, ['heimdall', 'hmd-chartjs']);
        self::enqueue_script("heimdall-map-widget", '/assets/js/map-widget.js', true, ['heimdall', 'hmd-chartjs', 'hmd-chart-js-geo']);
    }




    /**
     * @since 1.3.1
     */
    static function get_dashboard_world_map_data()
    {
        global $wpdb;

        check_ajax_referer('heimdall-nonce');

        $data = [];
        $query = WorldMapDatabase::get_world_map_data_query();

        $records = $wpdb->get_results($query, ARRAY_A);
        $records = array_map(function ($o) {
            return [
                "r" => intval($o['records']),
                "n" => $o['country_name'],
                "c" => $o['country_code']
            ];
        } , $records);

        $data["world_map_data"] = $records;
        $data["world_map_max"] = max(array_column($records , 'r'));

        wp_send_json_success($data);
    }



    static function handle_com_request($params){

        $params = false;

        if(empty($params['action']) || empty($params['data'])){
            return false;
        }

        switch($params["action"]){
            
        }

    }


    /**
     * @since 1.3.1
     */
    static function add_country_data($metav2)
    {

        if (isset($metav2["ip"])) {
            $ip = $metav2["ip"];
            $service = self::$service;

            $ip_data = self::get_ip_data($ip);

            if (empty($ip_data)) {

                $response = wp_remote_get("$service/json/$ip");

                if (is_wp_error($response)) {
                    return $metav2;
                }

                $json = wp_remote_retrieve_body($response);

                $data = json_decode($json, true);

                WorldMapDatabase::insert_ip_data($ip, $data);

                $metav2['country_code'] = $data["country_code"];
            } else {

                $geo = json_decode($ip_data[0]['data'], true);

                $metav2['country_code'] = $geo["country_code"];
            }
        }

        return $metav2;
    }




    /**
     * @since 1.3.1
     */
    static function get_ip_data($ip)
    {

        global $wpdb;

        $query = WorldMapDatabase::get_find_ip_query($ip);

        $data = $wpdb->get_results($query, ARRAY_A);

        return $data;
    }


}
