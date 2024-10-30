<?php

namespace Heimdall;

defined('HEIMDALL_VER') || die;

class Dashboard
{


    static function init()
    {
        add_action("wp_dashboard_setup", [__CLASS__, "wp_dashboard_setup"]);
    }

    static function wp_dashboard_setup()
    {
        $roles = explode(',', get_option(Database::entity('widget_access'), 'administrator'));

        $usercan = false;

        foreach ($roles as $role) {
            $usercan = $usercan || current_user_can($role);
        }

        if ($usercan) {
            wp_add_dashboard_widget(
                Database::entity('statistics'),
                'Heimdall',
                [__CLASS__, "admin_dashboard_widget"]
            );
        }
    }

    static function admin_dashboard_widget()
    {
        
        do_action("heimdall__widget_before-content");

        printf(
            '<div id="statisticTabs" class="hmd-tabs"><ul>%1$s</ul>%2$s</div>',
            apply_filters("heimdall__widget_tabs", ""),
            apply_filters("heimdall__widget_content", "")
        );

    }


    static function create_widget_tab($tab_id, $title, $icon = '')
    {
        $template = '<li><a href="#%1$s">%2$s</a></li>';
        return sprintf(
            $template,
            $tab_id,
            empty($icon) ? $title : "<span class='dashicons dashicons-$icon'></span>" . $title
        );
    }


    static function create_widget_tab_content($tab_id, $content, $ajax = true)
    {
        $template = '<div id="%1$s" class="hmd-tabcontent %4$s">%3$s%2$s</div>';
        $spinner = '<div class="hmd-spinner-container"><span class="spinner"></span></div>';
        return sprintf($template, $tab_id, $content, $ajax ? $spinner : '', $ajax ?  'busy' : '');
    }
}
