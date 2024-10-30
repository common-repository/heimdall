<?php

namespace Heimdall;

defined('HEIMDALL_VER') || die;

class Core
{

    static function init()
    {
        self::add_actions();
        self::init_hooks();
        self::activate_addons();
    }

    private static function add_actions()
    {
        // Register scripts
        add_action("wp_enqueue_scripts", [__CLASS__,    "register_heimdall_script"]);
        add_action("admin_enqueue_scripts", [__CLASS__, "register_heimdall_script"]);
        add_action("login_enqueue_scripts", [__CLASS__, "register_heimdall_script"]);

        // Enqueue scripts
        add_action('admin_print_scripts', [__CLASS__, "admin_enqueue_scripts"]);
        add_action("wp_enqueue_scripts",  [__CLASS__, "wp_enqueue_scripts"]);

        // Text domain
        add_action('plugins_loaded', [__CLASS__, "load_plugin_textdomain"]);

        register_uninstall_hook(HEIMDALL_FILE , [__CLASS__ , "uninstall_plugin"]);
    }

    private static function init_hooks()
    {

        $hooks = Options::get_hooks();

        foreach ($hooks as $hook) {

            $params = array_filter(explode("|", $hook));

            $prefix = $params[0];
            $method = isset($params[1]) ? $params[1] : null;
            $option = isset($params[2]) ? $params[2] : null;

            if ($prefix === "ajax") {

                add_filter('heimdall__core_localize-script', function ($data) use ($hook) {

                    if (!isset($data['ajaxhooks'])) {
                        $data['ajaxhooks'] = [];
                    }

                    if (!isset($data['ajaxdata'])) {
                        $type_post_dt = self::get_request_type_and_page();
                        $data['ajaxdata'] = [
                            'type_post_dt' => $type_post_dt,
                            'uri' =>  Helpers::get_url(),
                            'meta' => $type_post_dt["type"] == 5 ? get_queried_object() : null
                        ];
                    }

                    $data['ajaxhooks'][] = $hook;

                    return $data;
                });

                add_action("wp_ajax_{$hook}", [__CLASS__, "record_ajax_activity"]);

                add_action("wp_ajax_nopriv_{$hook}", [__CLASS__, "record_ajax_activity"]);

                continue;
            }

            if (in_array($prefix, apply_filters('heimdall__core_hooks-prefix', [
                /**
                 * e.g. ajax , meta , wp ...
                 */
            ]))) {
                do_action("heimdall__core_hooks-$hook");
                continue;
            }

            add_action($hook, function () use ($hook) {
                if (did_action($hook) > 1) {
                    return;
                }
                self::record_activity();
            });
        }
    }


    static function activate_addons()
    {
        $addons_dir = rtrim(HEIMDALL_DIR, '/') . "/addons";

        foreach (glob("$addons_dir/*", GLOB_ONLYDIR) as $addon) {
            $fileName = basename($addon);
            $path = path_join($addon, "{$fileName}.php");

            if (file_exists($path)) {

                require_once $path;

                $className = '\\Heimdall\\Addons\\' . Helpers::addon_file_to_class($fileName);

                if (class_exists($className)) {
                    $className::setup();
                }
            }
        }
    }

    static function register_heimdall_script()
    {
        wp_register_script(
            'heimdall',
            Helpers::get_asset_url("js/heimdall.js"),
            [],
            HEIMDALL_VER,
            false
        );
        wp_localize_script(
            'heimdall',
            'HeimdallData',
            apply_filters("heimdall__core_localize-script", [
                'version' => HEIMDALL_VER,
                'ajaxurl' => admin_url('admin-ajax.php'),
                'ajaxnonce' => wp_create_nonce('heimdall-nonce')
            ])
        );
    }


    static function wp_enqueue_scripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('heimdall');
        wp_enqueue_script(
            "hmd-client-script",
            Helpers::get_asset_url('js/client-script.js'),
            ['jquery', 'heimdall'],
            HEIMDALL_VER,
            false
        );
    }


    static function admin_enqueue_scripts()
    {
        $screen = get_current_screen();

        add_filter("heimdall__core_localize-script", function ($data) {
            $data['is_multisite'] = is_multisite();
            return $data;
        });

        wp_enqueue_script('heimdall');

        wp_enqueue_style(
            "heimdall",
            Helpers::get_asset_url('css/admin-style.css'),
            [],
            HEIMDALL_VER,
            "all"
        );

        wp_enqueue_script(
            "hmd-admin-script",
            Helpers::get_asset_url('js/admin-script.js'),
            ['jquery', 'jquery-ui-tabs' , 'heimdall'],
            HEIMDALL_VER,
            true
        );

        if (current_user_can('manage_options') && $screen->id  == 'dashboard') {
            wp_enqueue_script(
                "hmd-chartjs",
                Helpers::get_asset_url('js/chart.min.js'),
                [],
                HEIMDALL_VER,
                false
            );
        }
    }


    private static function get_request_type_and_page()
    {

        global $post;

        $type = 0;
        $page = null;

        /**
         * type 0 is undefined
         * type 1 is homepage
         * type 2 is page
         * type 3 is post
         * type 4 is search
         * type 5 is 404
         */
        if (is_home() || is_front_page()) {
            $type = 1;
        } else if (is_404()) {
            $type = 5;
        } else if (is_page() && !is_front_page()) {
            $page = $post->ID;
            $type = 2;
        } else if (is_single()) {
            $page = $post->ID;
            $type = 3;
        }

        return [
            "page" => $page,
            "type" => $type
        ];
    }

    static function record_activity(
        $type = null,
        $pid = null,
        $ip = null,
        $type_post_dt = null,
        $filter = null,
        $uri = null,
        $meta = null
    ) {

        if (is_null($type_post_dt)) {
            $type_post_dt = self::get_request_type_and_page();
        }

        if (is_null($ip)) {
            $ip = Helpers::get_ip_address();
        }

        if (is_null($filter)) {
            $filter = current_filter();
            $filter = str_replace("wp_ajax_nopriv_", "", $filter);
            $filter = str_replace("wp_ajax_", "", $filter);
            $filter = str_replace("|repeat", "", $filter);
        }

        if (is_null($meta)) {
            $meta = apply_filters(
                "heimdall__core_record-metadata",
                $type_post_dt["type"] == 5 ? get_queried_object() : null
            );
        }

        $metav2 = [];

        $metav2 = apply_filters(
            "heimdall__core_record-metadata-v2",
            $metav2
        );

        $metav2_str = json_encode($metav2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $is_mobile = Helpers::is_mobile_device();

        Database::insert_once(
            $ip,
            is_null($pid) ?  $type_post_dt["page"] : $pid,
            is_null($type) ? $type_post_dt["type"] : $type,
            $filter,
            is_null($uri) ? Helpers::get_url() : $uri,
            $_SERVER['HTTP_USER_AGENT'] ?: null,
            is_null($is_mobile) ? 0 : ($is_mobile ? 2 : 1),
            Helpers::get_os(),
            Helpers::get_browser(),
            empty($meta) ? null : $meta,
            empty($metav2) ? null : $metav2_str
        );
    }


    static function record_ajax_activity()
    {
        $type_post_dt = isset($_REQUEST['type_post_dt']) ? $_REQUEST['type_post_dt'] : null;
        $meta = isset($_REQUEST['meta']) ? $_REQUEST['meta'] : null;
        $uri = isset($_REQUEST['uri']) ? $_REQUEST['uri'] :  null;

        self::record_activity(null, null, null, $type_post_dt, null, $uri, $meta);
        
        wp_send_json_success();
    }



    static function load_plugin_textdomain()
    {
        load_plugin_textdomain("heimdall", FALSE, basename(HEIMDALL_DIR) . '/languages/');
    }



    static function uninstall_plugin(){
        foreach(apply_filters('heimdall__core_uninstall-options' , []) as $option){
            delete_option(Database::entity($option));
        }
    }

}
