<?php
namespace L4D2Stats;

/**
 * 插件核心類別 - 註冊 shortcodes、資源、AJAX
 */
class Plugin {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_shortcodes();
        $this->register_assets();
        $this->register_ajax();
        $this->register_rest_api();
        $this->register_rewrite_rules();
    }

    private function register_shortcodes() {
        add_shortcode('l4d2_leaderboard',     [Leaderboard::class, 'render']);
        add_shortcode('l4d2_player_stats',    [Player::class, 'render']);
        add_shortcode('l4d2_player_search',   [Player::class, 'render_search']);
        add_shortcode('l4d2_weapons',         [Weapons::class, 'render']);
        add_shortcode('l4d2_maps',            [Maps::class, 'render']);
        add_shortcode('l4d2_recent_sessions', [Sessions::class, 'render']);
        add_shortcode('l4d2_session_detail',  [SessionDetail::class, 'render']);
    }

    private function register_assets() {
        add_action('wp_enqueue_scripts', function () {
            // DataTables.js
            wp_enqueue_style('datatables-css',
                'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
                [], '1.13.7');
            wp_enqueue_script('datatables-js',
                'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
                ['jquery'], '1.13.7', true);

            // Chart.js
            wp_enqueue_script('chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                [], '4.4.0', true);

            // 插件自有資源
            wp_enqueue_style('l4d2-stats-css',
                L4D2_STATS_URL . 'assets/css/l4d2-stats.css',
                [], L4D2_STATS_VERSION);
            wp_enqueue_script('l4d2-stats-js',
                L4D2_STATS_URL . 'assets/js/l4d2-stats.js',
                ['jquery', 'datatables-js', 'chartjs'], L4D2_STATS_VERSION, true);

            // 傳遞參數到 JS
            wp_localize_script('l4d2-stats-js', 'l4d2Stats', [
                'rest_url'     => rest_url('l4d2-stats/v1/'),
                'nonce'        => wp_create_nonce('wp_rest'),
                'player_page'  => $this->get_player_page_url(),
                'session_page' => $this->get_session_page_url(),
            ]);
        });
    }

    private function register_ajax() {
        // 保留供舊版快取相容，新版已改用 REST API
    }

    private function register_rest_api() {
        add_action('rest_api_init', function () {
            register_rest_route('l4d2-stats/v1', '/search', [
                'methods'             => 'GET',
                'callback'            => [Player::class, 'rest_search'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'q' => [
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]);
        });
    }

    private function register_rewrite_rules() {
        add_action('init', function () {
            add_rewrite_rule(
                'stats/player/([^/]+)/?$',
                'index.php?pagename=player-stats&steam_id=$matches[1]',
                'top'
            );
            add_rewrite_rule(
                'stats/session/(\d+)/?$',
                'index.php?pagename=session-detail&session_id=$matches[1]',
                'top'
            );
        });

        add_filter('query_vars', function ($vars) {
            $vars[] = 'steam_id';
            $vars[] = 'session_id';
            $vars[] = 'from';
            $vars[] = 'sid';
            $vars[] = 'pid';
            $vars[] = 'pname';
            $vars[] = 'campaign';
            return $vars;
        });
    }

    /**
     * 取得玩家個人頁面的 URL
     */
    private function get_player_page_url() {
        $page = get_page_by_path('player-stats');
        if ($page) {
            return get_permalink($page);
        }
        return home_url('/');
    }

    /**
     * 取得場次詳細頁面的 URL
     */
    private function get_session_page_url() {
        $page = get_page_by_path('session-detail');
        if ($page) {
            return get_permalink($page);
        }
        return home_url('/');
    }

    /**
     * 透過 shortcode 查找包含該 shortcode 的頁面 URL
     * 結果會快取在靜態變數中避免重複查詢
     */
    public static function get_page_url_by_shortcode($shortcode) {
        static $cache = [];
        if (isset($cache[$shortcode])) {
            return $cache[$shortcode];
        }

        global $wpdb;
        $page_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'page' AND post_status = 'publish'
             AND post_content LIKE %s
             LIMIT 1",
            '%[' . $wpdb->esc_like($shortcode) . '%'
        ));

        $url = $page_id ? get_permalink($page_id) : '';
        $cache[$shortcode] = $url;
        return $url;
    }

    /**
     * 渲染共用導航列
     *
     * @param string $current_shortcode 當前頁面的 shortcode 名稱
     * @return string HTML
     */
    public static function render_nav($current_shortcode) {
        $items = [
            ['shortcode' => 'l4d2_leaderboard',     'label' => '排行榜'],
            ['shortcode' => 'l4d2_maps',            'label' => '地圖統計'],
            ['shortcode' => 'l4d2_recent_sessions', 'label' => '場次記錄'],
            ['shortcode' => 'l4d2_weapons',         'label' => '武器統計'],
            ['shortcode' => 'l4d2_player_search',   'label' => '搜尋玩家'],
        ];

        $html = '<nav class="l4d2-nav">';
        foreach ($items as $item) {
            $url = self::get_page_url_by_shortcode($item['shortcode']);
            $active = ($item['shortcode'] === $current_shortcode) ? ' l4d2-nav-active' : '';
            if ($url) {
                $html .= '<a href="' . esc_url($url) . '" class="l4d2-nav-item' . $active . '">'
                       . esc_html($item['label']) . '</a>';
            }
        }
        $html .= '</nav>';
        return $html;
    }

    /**
     * 格式化遊玩時間
     */
    public static function format_playtime($seconds) {
        if ($seconds < 60) return '< 1 分鐘';

        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return $hours . ' 小時 ' . $mins . ' 分鐘';
        }
        return $mins . ' 分鐘';
    }

    /**
     * 格式化數字 (千分位)
     */
    public static function format_number($num) {
        return number_format((int)$num);
    }
}
