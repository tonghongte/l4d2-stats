<?php
/**
 * L4D2 Stats - 解除安裝清理
 *
 * 注意：此腳本不會刪除 l4d2_stats 資料庫的資料表，
 * 因為這些資料是由 SourceMod 插件建立的，可能仍在使用中。
 * 如需完全刪除，請手動執行 DROP DATABASE l4d2_stats;
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 清除 WordPress 物件快取中的相關資料
wp_cache_flush();

// 清除 rewrite rules
flush_rewrite_rules();
