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
            global $post;
            if (!$post) return;

            $shortcodes = [
                'l4d2_leaderboard', 'l4d2_player_stats', 'l4d2_player_search',
                'l4d2_weapons', 'l4d2_maps', 'l4d2_recent_sessions',
                'l4d2_session_detail',
            ];

            $needs_assets = false;
            foreach ($shortcodes as $sc) {
                if (has_shortcode($post->post_content, $sc)) {
                    $needs_assets = true;
                    break;
                }
            }
            if (!$needs_assets) return;

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
