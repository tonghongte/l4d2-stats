<?php
namespace L4D2Stats;

/**
 * 玩家個人頁面與搜尋
 */
class Player {

    /**
     * 渲染玩家個人頁面
     */
    public static function render($atts) {
        $atts = shortcode_atts([
            'steam_id' => '',
        ], $atts);

        // 從 shortcode、URL query var 或 GET 參數取得 steam_id
        $steam_id = $atts['steam_id'];
        if (empty($steam_id)) {
            $steam_id = get_query_var('steam_id', '');
        }
        if (empty($steam_id)) {
            $steam_id = isset($_GET['steam_id']) ? sanitize_text_field(wp_unslash($_GET['steam_id'])) : '';
        }

        if (empty($steam_id)) {
            return '<div class="l4d2-notice">請指定玩家。使用 [l4d2_player_search] 搜尋玩家。</div>';
        }

        $db = Database::instance();

        // 基本資料與數據
        $player = $db->get_row(
            "SELECT p.*, ps.*
             FROM l4d2_players p
             JOIN l4d2_player_stats ps ON ps.player_id = p.id
             WHERE p.steam_id = %s",
            [$steam_id]
        );

        if (!$player) {
            return '<div class="l4d2-notice">找不到此玩家。</div>';
        }

        // 計算衍生數據
        $total_kills = $player->kills_infected + $player->kills_si;
        $kd_ratio = $player->deaths > 0
            ? round($total_kills / $player->deaths, 2)
            : $total_kills;
        $accuracy = $player->shots_fired > 0
            ? round($player->shots_hit / $player->shots_fired * 100, 1)
            : 0;
        $hs_rate = $total_kills > 0
            ? round($player->headshots / $total_kills * 100, 1)
            : 0;

        // 排名
        $rank = $db->get_var(
            "SELECT COUNT(*) + 1
             FROM l4d2_player_stats ps2
             JOIN l4d2_players p2 ON p2.id = ps2.player_id
             WHERE (ps2.kills_infected + ps2.kills_si) >
                   (SELECT kills_infected + kills_si
                    FROM l4d2_player_stats WHERE player_id = %d)
             AND p2.total_playtime >= 300",
            [$player->id]
        );

        $total_players = $db->get_var(
            "SELECT COUNT(*) FROM l4d2_players WHERE total_playtime >= 300"
        );

        // 武器 Top 15
        $weapons = $db->query(
            "SELECT w.display_name, w.weapon_name, w.weapon_type,
                    pws.kills, pws.headshots, pws.damage_dealt,
                    pws.shots_fired, pws.shots_hit,
                    CASE WHEN pws.shots_fired > 0
                         THEN ROUND(pws.shots_hit / pws.shots_fired * 100, 1)
                         ELSE 0
                    END AS accuracy
             FROM l4d2_player_weapon_stats pws
             JOIN l4d2_weapons w ON w.id = pws.weapon_id
             WHERE pws.player_id = %d
             ORDER BY pws.kills DESC
             LIMIT 15",
            [$player->id]
        );

        // 地圖數據
        $maps = $db->query(
            "SELECT m.display_name, m.campaign_name, m.map_name,
                    pms.times_played, pms.times_completed,
                    pms.kills, pms.deaths, pms.playtime
             FROM l4d2_player_map_stats pms
             JOIN l4d2_maps m ON m.id = pms.map_id
             WHERE pms.player_id = %d
             ORDER BY pms.times_played DESC",
            [$player->id]
        );

        // 近期場次
        $sessions = $db->query(
            "SELECT s.start_time, s.end_time, s.duration, s.completed,
                    s.campaign_completed, s.difficulty,
                    m.display_name AS map_name, m.campaign_name,
                    sp.duration AS player_duration
             FROM l4d2_session_players sp
             JOIN l4d2_sessions s ON s.id = sp.session_id
             JOIN l4d2_maps m ON m.id = s.map_id
             WHERE sp.player_id = %d
             ORDER BY s.start_time DESC
             LIMIT 20",
            [$player->id]
        );

        // 準備 Chart.js 武器圖表數據
        $weapon_chart_labels = [];
        $weapon_chart_kills = [];
        foreach (array_slice($weapons, 0, 10) as $w) {
            $weapon_chart_labels[] = $w->display_name;
            $weapon_chart_kills[] = (int)$w->kills;
        }

        ob_start();
        include L4D2_STATS_DIR . 'templates/player-profile.php';
        return ob_get_clean();
    }

    /**
     * 渲染搜尋介面
     */
    public static function render_search($atts) {
        ob_start();
        include L4D2_STATS_DIR . 'templates/player-search.php';
        return ob_get_clean();
    }

    /**
     * AJAX 玩家搜尋
     */
    public static function ajax_search() {
        check_ajax_referer('l4d2_stats_nonce', 'nonce');

        $search = sanitize_text_field(wp_unslash($_POST['search'] ?? ''));
        if (strlen($search) < 2) {
            wp_send_json_error('搜尋字串太短');
        }

        $db = Database::instance();
        $results = $db->query(
            "SELECT name, steam_id, steam_id_64, last_seen, total_playtime
             FROM l4d2_players
             WHERE name LIKE %s OR steam_id LIKE %s
             ORDER BY last_seen DESC
             LIMIT 20",
            ['%' . $db->get_db()->esc_like($search) . '%',
             '%' . $db->get_db()->esc_like($search) . '%']
        );

        wp_send_json_success($results);
    }
}
