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
        $this->register_settings();
        $this->maybe_migrate_db();
    }

    private function register_shortcodes() {
        add_shortcode('l4d2_leaderboard',     [Leaderboard::class, 'render']);
        add_shortcode('l4d2_player_stats',    [Player::class, 'render']);
        add_shortcode('l4d2_player_search',   [Player::class, 'render_search']);
        add_shortcode('l4d2_weapons',         [Weapons::class, 'render']);
        add_shortcode('l4d2_maps',            [Maps::class, 'render']);
        add_shortcode('l4d2_recent_sessions', [Sessions::class, 'render']);
        add_shortcode('l4d2_session_detail',  [SessionDetail::class, 'render']);
        add_shortcode('l4d2_items',           [Items::class, 'render']);
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

            // 插件自有資源（版本號使用檔案修改時間，確保每次更新都能清快取）
            $css_ver = filemtime(L4D2_STATS_DIR . 'assets/css/l4d2-stats.css') ?: L4D2_STATS_VERSION;
            $js_ver  = filemtime(L4D2_STATS_DIR . 'assets/js/l4d2-stats.js')  ?: L4D2_STATS_VERSION;
            wp_enqueue_style('l4d2-stats-css',
                L4D2_STATS_URL . 'assets/css/l4d2-stats.css',
                [], $css_ver);
            wp_enqueue_script('l4d2-stats-js',
                L4D2_STATS_URL . 'assets/js/l4d2-stats.js',
                ['jquery', 'datatables-js', 'chartjs'], $js_ver, true);

            // 傳遞參數到 JS
            wp_localize_script('l4d2-stats-js', 'l4d2Stats', [
                'rest_url'     => rest_url('l4d2-stats/v1/'),
                'nonce'        => wp_create_nonce('wp_rest'),
                'player_page'  => $this->get_player_page_url(),
                'session_page' => $this->get_session_page_url(),
            ]);
        });

        // 防止快取/優化外掛對關鍵腳本加上 defer 或 async，避免載入順序錯亂
        add_filter('script_loader_tag', function ($tag, $handle) {
            $no_defer = ['chartjs', 'l4d2-stats-js', 'datatables-js'];
            if (in_array($handle, $no_defer, true)) {
                $tag = str_replace([' defer', ' async'], '', $tag);
            }
            return $tag;
        }, 999, 2);
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
            ['shortcode' => 'l4d2_items',           'label' => '道具統計'],
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

    // ============================================================
    // 戰役縮圖
    // ============================================================

    /** 官方戰役海報圖 URL 映射 (L4D Wiki) */
    private static $campaign_thumbnails = [
        'Dead Center'    => 'https://static.wikia.nocookie.net/left4dead/images/6/6e/DeadCenter.jpg',
        'Dark Carnival'  => 'https://static.wikia.nocookie.net/left4dead/images/6/6c/Dark_Carnival02.png',
        'Swamp Fever'    => 'https://static.wikia.nocookie.net/left4dead/images/6/65/TheNewSwampFever.JPG',
        'Hard Rain'      => 'https://static.wikia.nocookie.net/left4dead/images/c/cd/HardRain.jpg',
        'The Parish'     => 'https://static.wikia.nocookie.net/left4dead/images/e/e8/TheNewParish.jpg',
        'The Passing'    => 'https://static.wikia.nocookie.net/left4dead/images/f/f3/The_Passing_Poster.jpg',
        'The Sacrifice'  => 'https://static.wikia.nocookie.net/left4dead/images/f/ff/It%27s_Your_Funeral.jpg',
        'No Mercy'       => 'https://static.wikia.nocookie.net/left4dead/images/d/d2/No_Mercy.jpg',
        'Crash Course'   => 'https://static.wikia.nocookie.net/left4dead/images/8/89/Crash_Course.jpg',
        'Death Toll'     => 'https://static.wikia.nocookie.net/left4dead/images/9/93/Death_Toll.jpg',
        'Dead Air'       => 'https://static.wikia.nocookie.net/left4dead/images/8/8c/Dead_Air.jpg',
        'Blood Harvest'  => 'https://static.wikia.nocookie.net/left4dead/images/0/03/Blood_Harvest.jpg',
        'Cold Stream'    => 'https://static.wikia.nocookie.net/left4dead/images/2/2c/COLDSTEAMPOSTER.png',
        'The Last Stand' => 'https://static.wikia.nocookie.net/left4dead/images/9/9e/The_Last_Stand.jpg',
    ];

    /**
     * 取得戰役縮圖 URL
     */
    public static function get_campaign_thumbnail($campaign_name) {
        return self::$campaign_thumbnails[$campaign_name] ?? '';
    }

    /**
     * 渲染戰役縮圖 HTML
     *
     * @param string $campaign_name 戰役名稱
     * @param string $size 'small' | 'medium' | 'large'
     */
    public static function render_campaign_thumbnail($campaign_name, $size = 'small') {
        $url = self::get_campaign_thumbnail($campaign_name);
        if ($url) {
            return '<img src="' . esc_url($url) . '" alt="' . esc_attr($campaign_name) . '" class="l4d2-campaign-thumb l4d2-thumb-' . esc_attr($size) . '" loading="lazy">';
        }
        return '<span class="l4d2-campaign-thumb l4d2-thumb-' . esc_attr($size) . ' l4d2-thumb-placeholder" title="' . esc_attr($campaign_name) . '">&#x1f5fa;</span>';
    }

    // ============================================================
    // 時間處理 (MySQL UTC → PHP timestamp)
    // ============================================================

    /**
     * 將 MySQL datetime (UTC) 轉為 Unix timestamp
     * SourceMod 使用 NOW() 存入的是 MySQL server 時間 (UTC)，
     * 但 PHP strtotime() 會以 WP 本地時區解讀，導致偏差。
     */
    public static function mysql_utc($datetime) {
        if (empty($datetime)) return 0;
        return strtotime($datetime . ' UTC');
    }

    // ============================================================
    // Steam 頭像
    // ============================================================

    const DEFAULT_AVATAR = 'https://avatars.steamstatic.com/fef49e7fa7e1997310d705b2a6158ff8dc1cdfeb_medium.jpg';

    public static function get_steam_api_key() {
        return get_option('l4d2_stats_steam_api_key', '');
    }

    /**
     * 批次抓取並快取 Steam 頭像
     *
     * @param array $players 含 steam_id_64, avatar_url, avatar_updated_at 的物件陣列
     * @return array steam_id_64 => avatar_url 映射
     */
    public static function fetch_avatars($players) {
        $api_key = self::get_steam_api_key();
        $avatars = [];
        $need_fetch = [];

        foreach ($players as $p) {
            $sid64 = $p->steam_id_64 ?? '';
            if (empty($sid64)) {
                continue;
            }
            if (!empty($p->avatar_url) && !empty($p->avatar_updated_at)
                && strtotime($p->avatar_updated_at) > time() - 86400) {
                $avatars[$sid64] = $p->avatar_url;
            } else {
                $need_fetch[] = $sid64;
                $avatars[$sid64] = $p->avatar_url ?: self::DEFAULT_AVATAR;
            }
        }

        if (!empty($need_fetch) && !empty($api_key)) {
            foreach (array_chunk($need_fetch, 100) as $chunk) {
                $ids_param = implode(',', $chunk);
                $api_url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$api_key}&steamids={$ids_param}";
                $response = wp_remote_get($api_url, ['timeout' => 5]);
                if (is_wp_error($response)) continue;

                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (empty($body['response']['players'])) continue;

                $db = Database::instance();
                foreach ($body['response']['players'] as $sp) {
                    $sid = $sp['steamid'];
                    $avatar = $sp['avatarmedium'] ?? self::DEFAULT_AVATAR;
                    $avatars[$sid] = $avatar;
                    $db->get_db()->query($db->get_db()->prepare(
                        "UPDATE l4d2_players SET avatar_url = %s, avatar_updated_at = NOW() WHERE steam_id_64 = %s",
                        $avatar, $sid
                    ));
                }
            }
        }

        return $avatars;
    }

    /**
     * 渲染玩家頭像 HTML
     *
     * @param string $avatar_url 頭像 URL
     * @param string $size 'small' (28px) | 'medium' (40px) | 'large' (96px)
     */
    public static function render_avatar($avatar_url, $size = 'small') {
        $url = $avatar_url ?: self::DEFAULT_AVATAR;
        return '<img src="' . esc_url($url) . '" alt="avatar" class="l4d2-avatar l4d2-avatar-' . esc_attr($size) . '" loading="lazy">';
    }

    // ============================================================
    // WordPress 後台設定頁面
    // ============================================================

    private function register_settings() {
        add_action('admin_menu', function () {
            add_options_page(
                'L4D2 Stats 設定',
                'L4D2 Stats',
                'manage_options',
                'l4d2-stats-settings',
                [self::class, 'render_settings_page']
            );
        });

        add_action('admin_init', function () {
            register_setting('l4d2_stats_settings', 'l4d2_stats_steam_api_key', [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ]);
            add_settings_section(
                'l4d2_stats_steam_section',
                'Steam API',
                function () {
                    echo '<p>設定 Steam Web API Key 以啟用玩家頭像功能。<a href="https://steamcommunity.com/dev/apikey" target="_blank" rel="noopener">取得 API Key</a></p>';
                },
                'l4d2-stats-settings'
            );
            add_settings_field(
                'l4d2_stats_steam_api_key',
                'Steam Web API Key',
                function () {
                    $key = get_option('l4d2_stats_steam_api_key', '');
                    echo '<input type="text" name="l4d2_stats_steam_api_key" value="' . esc_attr($key) . '" class="regular-text" placeholder="輸入你的 Steam Web API Key">';
                },
                'l4d2-stats-settings',
                'l4d2_stats_steam_section'
            );
        });
    }

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>L4D2 Stats 設定</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('l4d2_stats_settings');
                do_settings_sections('l4d2-stats-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // ============================================================
    // DB Migration
    // ============================================================

    private function maybe_migrate_db() {
        $db_version = get_option('l4d2_stats_db_version', 0);
        if ($db_version >= 1) return;

        $db = Database::instance();
        $wpdb = $db->get_db();
        $column = $wpdb->get_results("SHOW COLUMNS FROM l4d2_players LIKE 'avatar_url'");
        if (empty($column)) {
            $wpdb->query("ALTER TABLE l4d2_players ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL");
            $wpdb->query("ALTER TABLE l4d2_players ADD COLUMN avatar_updated_at DATETIME DEFAULT NULL");
        }
        update_option('l4d2_stats_db_version', 1);
    }

    // ============================================================
    // 工具函式
    // ============================================================

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
