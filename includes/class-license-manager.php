<?php

namespace Heimdall;

use Error;

defined('HEIMDALL_VER') || die;

class LicenseManager
{

    private static $license_key;
    private static $license_salt;

    static function init(){
        add_action('added_option', [__CLASS__, 'added_option'], 10, 2);
        add_action('updated_option', [__CLASS__, 'updated_option'], 10, 3);
        add_filter('heimdall__core_uninstall-options' , function($options){
            $options[] = 'license_key';
            $options[] = 'license_salt';
            return $options;
        });
    }


    static function has_key()
    {
        return !empty(self::get_key());
    }


    static function check_key($key)
    {
        if (!self::has_key()) {
            return false;
        }
        return $key == self::retrieve_license();
    }


    static function create_request($endpoint, $args)
    {
        $server = 'https://notrep.local';
        $endpoint = ltrim(rtrim($endpoint , '/') , '/');
        $license = self::retrieve_license();

        if(empty($license)){
            return null;
        }

        $url =  $server . '/client/' . $license . '/' . $endpoint;
        
        return wp_remote_post($url ,  $args);
    }


    private static function retrieve_license()
    {
        if (!self::has_key()) {
            return null;
        }

        return Cryptor::decrypt(self::get_key(), self::get_salt());
    }


    private static function get_key()
    {
        if (empty(self::$license_key)) {
            self::$license_key = get_option(Database::entity("license_key"), false);
        }
        return self::$license_key;
    }


    private static function get_salt()
    {
        if (empty(self::$license_salt)) {
            self::$license_salt = get_option(Database::entity("license_salt"), false);
        }

        return self::$license_salt;
    }


    static function added_option($option, $value){

        if ($option != Database::entity('license_key')) {
            return;
        }

        if(empty($value)){
            do_action('heimdall__license_key-removed');
            return;
        }

        $salt = Helpers::get_salt(Database::entity('license_salt'));

        $new_value = Cryptor::encrypt($value,  $salt);

        update_option($option, $new_value);

        do_action('heimdall__license_key-updated');

    }


    static function updated_option($option, $old_value, $value)
    {

        $gag_license_key = '#heimdallkey#';

        if ($option != Database::entity('license_key')) {
            return;
        }

        if(empty($value)){
            do_action('heimdall__license_key-removed');
            return;
        }

        remove_action('updated_option', [__CLASS__, 'updated_option'], 10);

        if (!empty($old_value) && $value == $gag_license_key) {
            update_option($option, $old_value);
            return;
        }

        $salt = Helpers::get_salt(Database::entity('license_salt'));

        $new_value = Cryptor::encrypt($value,  $salt);

        update_option($option, $new_value);

        add_action('updated_option', [__CLASS__, 'updated_option'], 10, 3);

        do_action('heimdall__license_key-updated');
    }
}
