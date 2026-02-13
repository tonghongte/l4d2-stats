<?php
/**
 * Plugin Name: L4D2 Player Stats
 * Plugin URI:  https://github.com/yourname/l4d2-stats
 * Description: 顯示 Left 4 Dead 2 玩家戰績統計，包含排行榜、玩家個人頁面、武器統計、地圖統計等。
 * Version:     1.0.0
 * Author:      L4D2-Stats
 * License:     GPL-2.0+
 * Text Domain: l4d2-stats
 */

if (!defined('ABSPATH')) exit;

define('L4D2_STATS_VERSION', '1.0.0');
define('L4D2_STATS_DIR', plugin_dir_path(__FILE__));
define('L4D2_STATS_URL', plugin_dir_url(__FILE__));

// 自動載入類別
spl_autoload_register(function ($class) {
    $prefix = 'L4D2Stats\\';
    if (strpos($class, $prefix) !== 0) return;

    $relative = substr($class, strlen($prefix));
    $file = L4D2_STATS_DIR . 'includes/class-' .
            strtolower(str_replace('\\', '-', $relative)) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 初始化插件
add_action('plugins_loaded', function () {
    L4D2Stats\Plugin::instance();
});

// 啟用時驗證資料表
register_activation_hook(__FILE__, function () {
    L4D2Stats\Database::instance()->verify_tables();
    flush_rewrite_rules();
});

// 停用時清理 rewrite rules
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
