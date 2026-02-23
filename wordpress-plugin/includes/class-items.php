<?php
namespace L4D2Stats;

/**
 * 道具使用統計
 */
class Items {
    public static function render($atts) {
        $db = Database::instance();

        // 全伺服器道具使用總計
        $totals_sql = "
            SELECT
                COALESCE(SUM(pills_used), 0)      AS total_pills,
                COALESCE(SUM(adrenaline_used), 0) AS total_adrenaline,
                COALESCE(SUM(defibs_used), 0)     AS total_defibs,
                COALESCE(SUM(heals_given), 0)     AS total_heals,
                COALESCE(SUM(revives_given), 0)   AS total_revives
            FROM l4d2_player_stats
        ";
        $totals = $db->query($totals_sql, [], 'items_totals', 120);
        $totals = $totals[0] ?? null;

        // 各玩家道具使用明細（只顯示有使用道具的玩家）
        $players_sql = "
            SELECT
                p.id, p.name, p.steam_id_64,
                p.avatar_url, p.avatar_updated_at,
                ps.pills_used,
                ps.adrenaline_used,
                ps.defibs_used,
                ps.heals_given,
                ps.revives_given,
                (ps.pills_used + ps.adrenaline_used + ps.defibs_used + ps.heals_given + ps.revives_given) AS total_items
            FROM l4d2_player_stats ps
            JOIN l4d2_players p ON p.id = ps.player_id
            WHERE (ps.pills_used + ps.adrenaline_used + ps.defibs_used + ps.heals_given + ps.revives_given) > 0
            ORDER BY total_items DESC
        ";
        $players = $db->query($players_sql, [], 'items_players', 120);

        // 抓取頭像
        $avatars = Plugin::fetch_avatars($players);

        ob_start();
        include L4D2_STATS_DIR . 'templates/items.php';
        return ob_get_clean();
    }
}
