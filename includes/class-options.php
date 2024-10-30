<?php

namespace Heimdall;

defined('HEIMDALL_VER') || die;

class Options
{

    private static $hit_hooks = [];

    private static $default_hit_hooks = ['ajax|document:ready'];

    static function init()
    {
        add_action('admin_init', [__CLASS__, "admin_init"]);
        add_action('admin_menu', [__CLASS__, "admin_menu"]);
        add_action('admin_print_scripts', [__CLASS__, "admin_print_scripts"]);
        add_action('admin_bar_menu', [__CLASS__, 'toolbar_debug'] , PHP_INT_MAX);
    }

    static function admin_init()
    {

        $group = 'heimdall-general';

        $section = Database::entity('plugin');

        if (!extension_loaded('openssl')) {
            add_action('admin_notices', function () {
                $message = __('PHP OpenSSL extension is not installed on the server. It is required for encryption to work properly. Please contact your server administrator or hosting provider and ask them to install it.', 'heimdall');
                printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
            });
        }

        // settings section
        add_settings_section($section, "", [__CLASS__, "settings_section_cb"], $group);

        foreach ([
            'license_key'    => [
                "title" => __("License Key", "heimdall")
            ],
            'active_hooks'   => [
                "title" => __("Hooks", "heimdall"),
                "default" => implode(',', self::get_hooks())
            ],
            'widget_access'  => [
                "title" => __("Dashboard Widgets", "heimdall"),
                "default" => "administrator"
            ],
            'post_position'  => [
                "title" => __("Post Statistic", "heimdall"),
                "default" => 0
            ],
            'page_position'  => [
                "title" => __("Page Statistic", "heimdall"),
                "default" => 0
            ],
            'statistic_hook' => [
                "title" => __("Statistic Hook", "heimdall")
            ],
        ] as $option => $info) {

            $option_name = Database::entity($option);
            $default = isset($info['default']) ? $info['default'] : '';

            register_setting($group, $option_name,  $default);

            add_settings_field(
                $option_name,
                $info['title'],
                [__CLASS__, "settings_field_cb"],
                $group,
                $section,
                ['label_for' => $option, 'default' =>  $default]
            );
        }
    }

    static function get_statistic_hook()
    {
        $hooks = self::get_hooks();
        return get_option(Database::entity("statistic_hook"), isset($hooks[0]) ? $hooks[0] : "");
    }

    static function admin_print_scripts()
    {
        $screen = get_current_screen();

        if ($screen->id == 'settings_page_heimdall-general') {
            wp_enqueue_style('hmd-tag-editor', Helpers::get_asset_url('css/jquery.tag-editor.css'), [], HEIMDALL_VER, 'all');

            wp_enqueue_script('hmd-caret', Helpers::get_asset_url('js/jquery.caret.min.js'), ['jquery'], HEIMDALL_VER, true);
            wp_enqueue_script('hmd-tag-editor', Helpers::get_asset_url('js/jquery.tag-editor.min.js'), [], HEIMDALL_VER, true);
        }
    }

    static function settings_section_cb()
    {
    }

    static function settings_page_cb()
    {
        ob_start();

        settings_fields('heimdall-general');

        do_settings_sections('heimdall-general');

        submit_button();

        $content = ob_get_clean();

        $title = esc_html__('Heimdall', "heimdall");

        $title_version = "<span class='hmd-version-box'>v" . HEIMDALL_VER . "</span>";

        $template = '<div class="wrap"><h1 class="hmd-option-title">%1$s</h1><form method="POST" action="options.php">%2$s</form></div>';

        printf(
            $template,
            is_rtl() ? $title_version . $title : $title . $title_version,
            $content
        );
    }

    
    static function get_hooks()
    {
        if (empty(self::$hit_hooks)) {
            $hooks = get_option(Database::entity('active_hooks'),  '');
            if(!empty($hooks)){
                self::$hit_hooks = explode(',' , $hooks);
            }
        }

        if (empty(self::$hit_hooks)) {
            self::$hit_hooks = self::$default_hit_hooks;
        }

        return self::$hit_hooks;
    }


    static function admin_menu()
    {
        add_submenu_page(
            'options-general.php',
            __('Heimdall', 'heimdall'),
            __('Heimdall', 'heimdall'),
            'manage_options',
            'heimdall-general',
            [__CLASS__, 'settings_page_cb']
        );
    }

    static function settings_field_cb($args)
    {
        $option = Database::entity($args['label_for']);

        $value = get_option($option, isset($args['default']) ? $args['default'] : '');

        switch ($args['label_for']) {

            case 'license_key':

                $gag_license_key = '#heimdallkey#';

                $template = '<input type="password" class="regular-text" id="%1$s" name="%1$s" value="%2$s" />';

                printf($template, esc_attr($option), esc_html(empty($value) ? '' : $gag_license_key));

                break;

            case 'active_hooks':

                self::create_tag_editor(esc_attr__("Hooks...", "heimdall"), $option, $value);

                self::create_description('e.g.&nbsp;&nbsp;&nbsp;&nbsp;wp_footer,&nbsp;&nbsp;wp_head ...');

                break;

            case 'statistic_hook':

                $sh = self::get_statistic_hook();

                $ho = array_merge(...array_map(function ($item) {
                    return [$item => $item];
                },  self::get_hooks()));

                self::create_dropdown($ho, $option, $sh, esc_html__("No hook defined!", "heimdall"));

                break;

            case 'page_position':

                self::create_dropdown(
                    [
                        esc_html__('— Do not show —', "heimdall"),
                        esc_html__('After Content', "heimdall"),
                        esc_html__('Before Content', "heimdall")
                    ],
                    $option,
                    $value
                );

                self::create_description(
                    sprintf(
                        esc_html__('You can also use the statistic shortcode: %s', "heimdall"),
                        "<code>[statistic class='' params='' hook='']</code>"
                    )
                );

                break;

            case 'post_position':

                self::create_dropdown(
                    [
                        esc_html__('— Do not show —', "heimdall"),
                        esc_html__('After Content', "heimdall"),
                        esc_html__('Before Content', "heimdall"),
                        esc_html__('After Excerpt', "heimdall"),
                        esc_html__('Before Excerpt', "heimdall")
                    ],
                    $option,
                    $value
                );

                break;

            case 'widget_access':

                self::create_tag_editor(
                    esc_attr__("Roles...", "heimdall"),
                    $option,
                    $value
                );

                self::create_description(esc_html__(
                    "Those who can see the widgets. e.g. administrator, update_core ...",
                    "heimdall"
                ));

                break;
        }
    }


    static function toolbar_debug($wp_admin_bar)
    {

        if(!Helpers::is_in_debug_mode()){
            return;
        }

        $args = array(
            'id'    => 'hmd_debug_menu',
            'title' => 'Debug',
        );
        
        $wp_admin_bar->add_node($args);

    }


    private static function create_dropdown($list, $for, $current, $empty_msg = '')
    {
        $template = '<select name="%1$s" id="%1$s">%2$s</select>';
        $items = '';

        if (empty($list)) {
            $items .= sprintf('<option selected >%1$s</option>', $empty_msg);
        }

        foreach ($list as $index => $label) {
            $items .= sprintf('<option %1$s value="%2$s">%3$s</option>', selected($current, $index, false),  $index,  $label);
        }

        printf($template, $for, $items);
    }


    private static function create_tag_editor($placeholder, $for, $current)
    {
        $template = '<div style="overflow: hidden;position: relative;">' .
            '<textarea data-placeholder="%3$s" class="hmd-tag-editor large-text code" id="%1$s" name="%1$s">%2$s</textarea>' .
            '</div>';

        printf($template, esc_attr($for), esc_html($current), $placeholder);
    }


    private static function create_description($description)
    {
        $template = '<dl><dd><p class="description">%1$s</p></dd></dl>';
        printf($template, $description);
    }
}
