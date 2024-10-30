<?php
/**
 * @package     Heimdall
 * @author      Rmanaf <me@rmanaf.com>
 * @copyright   2018-2023 WP Heimdall
 * @license     No License (No Permission)
 */

namespace Heimdall;

use Exception;

defined('HEIMDALL_VER') || die;

/**
 * @since 1.4.0
 */
class Communicator
{

    private static $wpheimdall_hostname = 'www.wp-heimdall.com';
    private static $token_cron_recurrence = '15min';
    private static $token_cron_interval = 15 * 60;
    private static $token_cron_display = 'Every 15 Minutes';

    static function init()
    {
        register_activation_hook(HEIMDALL_FILE, [__CLASS__, 'start_job']);
        register_deactivation_hook(HEIMDALL_FILE, [__CLASS__, 'stop_job']);

        add_action('init', [__CLASS__, "rewrite_rules"]);
        add_action('parse_request', [__CLASS__, "parse_request"]);
        add_action('update_token_hook', [__CLASS__, 'update_token']);
        add_action('admin_bar_menu', [__CLASS__, 'toolbar_debug'], PHP_INT_MAX);
        add_action('heimdall__license_key-updated', [__CLASS__, 'update_token']);
        add_action('heimdall__license_key-removed', [__CLASS__, 'clear_token']);

        add_filter('cron_schedules', [__CLASS__, 'cron_schedules']);
        add_filter('query_vars', [__CLASS__, "query_vars"]);

        add_filter('heimdall__core_uninstall-options', function ($options) {
            $options[] = 'server_token';
            $options[] = 'server_token_exp';
            return $options;
        });
    }


    static function start_job()
    {
        if (!wp_next_scheduled('update_token_hook')) {
            wp_schedule_event(time(), self::$token_cron_recurrence, 'update_token_hook');
        }
    }

    static function stop_job()
    {
        wp_clear_scheduled_hook('update_token_hook');
        self::clear_token();
    }

    static function cron_schedules($schedules)
    {
        if (!isset($schedules[self::$token_cron_recurrence])) {
            $schedules[self::$token_cron_recurrence] = array(
                'interval' => self::$token_cron_interval,
                'display' => self::$token_cron_display 
            );
        }
        return $schedules;
    }

    static function rewrite_rules()
    {
        add_rewrite_rule('hmdquery/(.+)', 'index.php?hmdquery_token=$matches[1]', 'top');
        flush_rewrite_rules();
    }

    static function query_vars($query_vars)
    {
        $query_vars[] = 'hmdquery_token';
        return $query_vars;
    }

    static function parse_request(&$wp)
    {

        $heimdall_ip_list = [ gethostbyname(self::$wpheimdall_hostname) , '127.0.0.1' ];

        if (!array_key_exists('hmdquery_token', $wp->query_vars)) {
            return;
        }

        $ip = Helpers::get_ip_address();

        if (!in_array($ip, $heimdall_ip_list)) {
            error_log("Communicator::parse_request() - Unauthorized request.");
            wp_send_json_error();
        }

        $token  =  isset($wp->query_vars['hmdquery_token']) ? $wp->query_vars['hmdquery_token'] : null;
        $action =  isset($_POST['action']) ? $_POST['action'] : null;
        $data   =  isset($_POST['data']) ? $_POST['data'] : null;

        if (is_null($action) || is_null($token)) {
            error_log("Communicator::parse_request() - Invalid parameters.");
            wp_send_json_error();
        }

        if (!self::is_valid_token($token)) {
            error_log("Communicator::parse_request() - Invalid token.");
            wp_send_json_error();
        }

        $result = apply_filters('heimdall__com_handle-request' , [ "action" => $action , "data" => $data ]);

        if(!$result || is_wp_error($result)){
            wp_send_json_error();
        }

        wp_send_json_success($result);
        
    }

    private static function is_valid_token($value = null)
    {
        $now = time();
        $token = get_option(Database::entity('server_token'), false);
        $token_exp = get_option(Database::entity('server_token_exp'), false);

        if(is_null($value)){
            $value = $token;
        }

        if (!$token || !$token_exp || $value !== $token || $now > $token_exp) {
            return false;
        }

        return true;
    }

    static function update_token()
    {

        if (!LicenseManager::has_key() || self::is_valid_token()) {
            return;
        }

        $token = Helpers::create_token();
        $timestamp = strtotime("+15 minute");

        $response = LicenseManager::create_request('token', [
            'body' => [
                "token" => $token,
                "timestamp" => $timestamp
            ]
        ]);

        if (!$response || is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            error_log("Communicator::update_token() - Unable to communicate with server.");
            return;
        }

        try {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            if ($data->success === true) {
                update_option(Database::entity('server_token'), $token);
                update_option(Database::entity('server_token_exp'), $timestamp);
            } else {
                error_log("Communicator::update_token() - Something went wrong!");
            }
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }
    }


    static function clear_token(){
        delete_option(Database::entity('server_token'));
        delete_option(Database::entity('server_token_exp'));
    }


    static function toolbar_debug($wp_admin_bar)
    {

        if (!Helpers::is_in_debug_mode()) {
            return;
        }

        $token = get_option(Database::entity('server_token'));
        $token_exp = get_option(Database::entity('server_token_exp'));

        $args = array(
            'id'    => 'hmd_debug_token',
            'title' => 'Token: ' . ($token ? $token : "Undefined"),
            'parent' => 'hmd_debug_menu'
        );

        $wp_admin_bar->add_node($args);

        $args = array(
            'id'    => 'hmd_debug_token_exp',
            'title' => 'Token Exp: ' . ($token_exp ? date_i18n('Y-m-d H:i:s', $token_exp) : "Undefined"),
            'parent' => 'hmd_debug_menu'
        );
        $wp_admin_bar->add_node($args);
    }

}
