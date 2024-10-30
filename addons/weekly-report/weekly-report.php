<?php

namespace Heimdall\Addons;

use DateInterval;
use DateTime;
use Heimdall\Addon;
use Heimdall\Dashboard;
use Heimdall\Database;

class WeeklyReport extends Addon
{


    static function setup()
    {
        add_action("admin_enqueue_scripts", [__CLASS__, "admin_enqueue_scripts"]);
        add_action("wp_ajax_heimdall_weekly_report", [__CLASS__, "get_weekly_report_data"]);

        add_filter("heimdall__widget_tabs", [__CLASS__, "widget_tabs"]);
        add_filter("heimdall__widget_content", [__CLASS__, "widget_content"]);
    }


    static function admin_enqueue_scripts()
    {
        $screen = get_current_screen();

        if (current_user_can('administrator') && $screen->id  == 'dashboard') {
            self::enqueue_script("weekly-report", '/assets/js/statistic-admin.js', true, ['jquery', 'heimdall']);
        }
    }


    static function widget_tabs($tabs_html)
    {
        return $tabs_html . Dashboard::create_widget_tab(
            self::get_slug(),
            esc_html__("Visits (7 days)", "heimdall"),
            'calendar'
        );
    }

    static function widget_content($tabs_html)
    {
        $template = '<div class="chart-container" style="%1$s">%2$s</div>';
        $style = 'position: relative; width:100%; height:300px;';
        $canvas = '<canvas id="statisticChart"></canvas>';

        return $tabs_html . Dashboard::create_widget_tab_content(
            self::get_slug(),
            sprintf($template, $style, $canvas)
        );
    }

    static function get_weekly_report_data()
    {

        global $wpdb;

        check_ajax_referer("heimdall-nonce");

        // get GMT
        $cdate = current_time('mysql', 1);

        // start from 6 days ago
        $start = new DateTime($cdate);
        $start->sub(new DateInterval('P6D'));

        // today
        $end = new DateTime($cdate);

        $data = $wpdb->get_results(self::get_chart_query($start, $end), ARRAY_A);

        wp_send_json_success($data);
    }

    /**
     * @since 1.0.0
     */
    static function get_chart_query($start, $end)
    {

        // convert dates to mysql format
        $start = $start->format('Y-m-d H:i:s');
        $end   = $end->format('Y-m-d H:i:s');

        $blog_id = get_current_blog_id();

        $extra_field = is_multisite() ? ", SUM(case when blog='$blog_id' then 1 else 0 end) w" : "";

        $table_name = Database::get_table();

        return "SELECT WEEKDAY(time) x,
                COUNT(DISTINCT ip) y,
                COUNT(*) z,
                SUM(case when type='1' then 1 else 0 end) p
                $extra_field
                FROM $table_name
                WHERE (time BETWEEN '$start' AND '$end')
                AND type != '4'
                GROUP BY x";
    }
}
