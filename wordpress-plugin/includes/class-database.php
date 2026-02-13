<?php
namespace L4D2Stats;

/**
 * 資料庫抽象層
 * 支援使用 WordPress 同一資料庫或獨立資料庫連線
 */
class Database {
    private static $instance = null;
    private $db;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // 預設使用獨立資料庫連線 (l4d2_stats)
        // 如果 l4d2_stats 資料表在 WordPress 同一資料庫中，可改用 global $wpdb
        $db_host = defined('L4D2_DB_HOST') ? L4D2_DB_HOST : DB_HOST;
        $db_user = defined('L4D2_DB_USER') ? L4D2_DB_USER : DB_USER;
        $db_pass = defined('L4D2_DB_PASSWORD') ? L4D2_DB_PASSWORD : DB_PASSWORD;
        $db_name = defined('L4D2_DB_NAME') ? L4D2_DB_NAME : 'l4d2_stats';

        $this->db = new \wpdb($db_user, $db_pass, $db_name, $db_host);
        $this->db->show_errors(false);
    }

    public function get_db() {
        return $this->db;
    }

    /**
     * 執行查詢並傳回結果，支援快取
     */
    public function query($sql, $params = [], $cache_key = '', $cache_ttl = 300) {
        if ($cache_key) {
            $cached = wp_cache_get($cache_key, 'l4d2_stats');
            if ($cached !== false) return $cached;
        }

        $prepared = empty($params)
            ? $sql
            : $this->db->prepare($sql, ...$params);

        $results = $this->db->get_results($prepared);

        if ($cache_key && $results !== null) {
            wp_cache_set($cache_key, $results, 'l4d2_stats', $cache_ttl);
        }

        return $results ?: [];
    }

    /**
     * 取得單列
     */
    public function get_row($sql, $params = []) {
        $prepared = empty($params)
            ? $sql
            : $this->db->prepare($sql, ...$params);

        return $this->db->get_row($prepared);
    }

    /**
     * 取得單值
     */
    public function get_var($sql, $params = []) {
        $prepared = empty($params)
            ? $sql
            : $this->db->prepare($sql, ...$params);

        return $this->db->get_var($prepared);
    }

    /**
     * 驗證所有必要的資料表是否存在
     */
    public function verify_tables() {
        $tables = [
            'l4d2_players', 'l4d2_maps', 'l4d2_weapons',
            'l4d2_sessions', 'l4d2_session_players',
            'l4d2_player_stats', 'l4d2_player_weapon_stats',
            'l4d2_player_map_stats'
        ];

        $missing = [];
        foreach ($tables as $table) {
            $exists = $this->db->get_var(
                $this->db->prepare("SHOW TABLES LIKE %s", $table)
            );
            if (!$exists) {
                $missing[] = $table;
            }
        }

        if (!empty($missing)) {
            add_action('admin_notices', function () use ($missing) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>[L4D2 Stats]</strong> 缺少資料表: ' . implode(', ', $missing);
                echo '<br>請先執行 <code>schema.sql</code> 建立資料表。';
                echo '</p></div>';
            });
        }
    }
}
