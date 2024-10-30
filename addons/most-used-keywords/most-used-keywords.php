<?php

namespace Heimdall\Addons;

use DateInterval;
use DateTime;
use Heimdall\Addon;
use Heimdall\Dashboard;
use Heimdall\Database as db;
use Heimdall\Helpers as hp;

class MostUsedKeywords extends Addon
{

    static function setup()
    {
        add_action('pre_get_posts', [__CLASS__, "pre_get_posts"]);
        add_action("admin_enqueue_scripts", [__CLASS__, "admin_enqueue_scripts"]);
        add_action("heimdall__widget_before-content", [__CLASS__, "dashboard_widget"], 10);

        add_filter("heimdall__core_localize-script", [__CLASS__, "get_kewords_data"], 10, 1);
    }


    static function admin_enqueue_scripts()
    {
        $screen = get_current_screen();

        if (current_user_can('administrator') && $screen->id  == 'dashboard') {
            wp_enqueue_style("muk-styles",  self::get_url('/assets/css/muk-styles.css'),  [],  HEIMDALL_VER, "all");
            wp_enqueue_script("muk-script", self::get_url('/assets/js/muk-scripts.js'), ["jquery"],  HEIMDALL_VER, true);
        }
    }

    /**
     * @since 1.0.0
     */
    static function dashboard_widget()
    {
        $template = '<h3>%1$s</h3><ul id="most-used-keywords" class="keywords"><li>%2$s</li></ul>';
        $title = esc_html__("The Most Searched Terms of the Past 7 Days:", "heimdall");
        $content = esc_html__("No terms found.", "heimdall");

        printf($template, $title, $content);
    }


    /**
     * @since 1.0.0
     */
    static function get_kewords_data($data)
    {
        global $wpdb;

        // get GMT
        $cdate = current_time('mysql', 1);

        $start = new DateTime($cdate);
        $start->sub(new DateInterval('P6D'));

        // today
        $end = new DateTime($cdate);

        $dt = $wpdb->get_results(self::get_most_used_keywords_query($start, $end), ARRAY_A);

        $data['keywords'] = $dt;

        return $data;
    }


    /**
     * @since 1.0.0
     */
    static function get_most_used_keywords_query($start, $end)
    {
        // convert dates to mysql format
        $start = $start->format('Y-m-d H:i:s');
        $end   = $end->format('Y-m-d H:i:s');

        $blog_id = get_current_blog_id();

        $table_name = db::get_table();

        return "SELECT COUNT(*) count, meta
                    FROM $table_name
                    WHERE `type`='4' AND `blog`='$blog_id' AND `meta` IS NOT NULL AND (`time` BETWEEN '$start' AND '$end')
                    GROUP BY meta
                    ORDER BY count DESC
                    LIMIT 20";
    }


    static function pre_get_posts($query)
    {

        if (is_admin() || is_customize_preview()) {
            return;
        }

        if ($query->is_search() && $query->is_main_query()) {

            $keyword = get_search_query();

            // ignore whitespace and empty values
            if (empty(trim($keyword))) {
                return;
            }

            $is_mobile = hp::is_mobile_device();

            db::insert_once(
                hp::get_ip_address(),
                null,
                4,
                'pre_get_posts',
                hp::get_url(false),
                $_SERVER['HTTP_USER_AGENT'] ?: null,
                is_null($is_mobile) ? 0 : ($is_mobile ? 2 : 1),
                hp::get_os(),
                hp::get_browser(),
                $keyword
            );
        }
    }
}
