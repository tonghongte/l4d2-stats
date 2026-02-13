<?php
namespace L4D2Stats;

/**
 * 場次詳細資訊
 */
class SessionDetail {

    /**
     * 渲染場次詳細頁面 (shortcode: [l4d2_session_detail])
     */
    public static function render($atts) {
        $atts = shortcode_atts([
            'session_id' => 0,
        ], $atts);

        $session_id = intval($atts['session_id']);
        if ($session_id <= 0) {
            $session_id = intval(get_query_var('session_id', 0));
        }
        if ($session_id <= 0) {
            $session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
        }

        if ($session_id <= 0) {
            return '<div class="l4d2-notice">請指定場次 ID。</div>';
        }

        $db = Database::instance();

        // 場次基本資訊
        $session = $db->get_row(
            "SELECT s.*, m.display_name AS map_name, m.campaign_name, m.is_finale
             FROM l4d2_sessions s
             JOIN l4d2_maps m ON m.id = s.map_id
             WHERE s.id = %d",
            [$session_id]
        );

        if (!$session) {
            return '<div class="l4d2-notice">找不到此場次。</div>';
        }

        // 場次玩家數據
        $players = $db->query(
            "SELECT
                p.name, p.steam_id, p.steam_id_64,
                sp.join_time, sp.leave_time, sp.duration AS player_duration,
                COALESCE(sps.kills_infected, 0) AS kills_infected,
                COALESCE(sps.kills_si, 0) AS kills_si,
                COALESCE(sps.kills_witch, 0) AS kills_witch,
                COALESCE(sps.kills_tank, 0) AS kills_tank,
                COALESCE(sps.headshots, 0) AS headshots,
                COALESCE(sps.damage_dealt, 0) AS damage_dealt,
                COALESCE(sps.damage_taken, 0) AS damage_taken,
                COALESCE(sps.deaths, 0) AS deaths,
                COALESCE(sps.incaps, 0) AS incaps,
                COALESCE(sps.revives_given, 0) AS revives_given,
                COALESCE(sps.revives_received, 0) AS revives_received,
                COALESCE(sps.heals_given, 0) AS heals_given,
                COALESCE(sps.heals_received, 0) AS heals_received,
                COALESCE(sps.health_restored, 0) AS health_restored,
                COALESCE(sps.pills_used, 0) AS pills_used,
                COALESCE(sps.adrenaline_used, 0) AS adrenaline_used,
                COALESCE(sps.defibs_used, 0) AS defibs_used,
                COALESCE(sps.friendly_fire_dealt, 0) AS friendly_fire_dealt,
                COALESCE(sps.friendly_fire_damage, 0) AS friendly_fire_damage,
                COALESCE(sps.shots_fired, 0) AS shots_fired,
                COALESCE(sps.shots_hit, 0) AS shots_hit,
                COALESCE(sps.melee_swings, 0) AS melee_swings,
                COALESCE(sps.melee_hits, 0) AS melee_hits,
                (COALESCE(sps.kills_infected, 0) + COALESCE(sps.kills_si, 0)) AS total_kills,
                CASE WHEN COALESCE(sps.shots_fired, 0) > 0
                     THEN ROUND(sps.shots_hit / sps.shots_fired * 100, 1)
                     ELSE 0
                END AS accuracy,
                CASE WHEN (COALESCE(sps.kills_infected, 0) + COALESCE(sps.kills_si, 0)) > 0
                     THEN ROUND(sps.headshots / (sps.kills_infected + sps.kills_si) * 100, 1)
                     ELSE 0
                END AS hs_rate
             FROM l4d2_session_players sp
             JOIN l4d2_players p ON p.id = sp.player_id
             LEFT JOIN l4d2_session_player_stats sps
                  ON sps.session_id = sp.session_id AND sps.player_id = sp.player_id
             WHERE sp.session_id = %d
             ORDER BY total_kills DESC",
            [$session_id]
        );

        // 檢查是否有場次數據
        $has_stats = false;
        foreach ($players as $p) {
            if ((int)$p->total_kills > 0 || (int)$p->damage_dealt > 0 || (int)$p->shots_fired > 0) {
                $has_stats = true;
                break;
            }
        }

        // 場次武器數據
        $weapons = $db->query(
            "SELECT
                p.name AS player_name,
                w.display_name AS weapon_name, w.weapon_type,
                spws.kills, spws.headshots, spws.damage_dealt,
                spws.shots_fired, spws.shots_hit,
                CASE WHEN spws.shots_fired > 0
                     THEN ROUND(spws.shots_hit / spws.shots_fired * 100, 1)
                     ELSE 0
                END AS accuracy
             FROM l4d2_session_player_weapon_stats spws
             JOIN l4d2_players p ON p.id = spws.player_id
             JOIN l4d2_weapons w ON w.id = spws.weapon_id
             WHERE spws.session_id = %d
             ORDER BY spws.kills DESC",
            [$session_id]
        );

        // 武器擊殺彙總 (甜甜圈圖)
        $weapon_summary = $db->query(
            "SELECT
                w.display_name,
                SUM(spws.kills) AS total_kills
             FROM l4d2_session_player_weapon_stats spws
             JOIN l4d2_weapons w ON w.id = spws.weapon_id
             WHERE spws.session_id = %d
             GROUP BY spws.weapon_id
             HAVING total_kills > 0
             ORDER BY total_kills DESC
             LIMIT 10",
            [$session_id]
        );

        // 準備圖表數據
        $chart_kills_labels = [];
        $chart_kills_data = [];
        $chart_damage_labels = [];
        $chart_damage_data = [];
        foreach ($players as $p) {
            $chart_kills_labels[] = $p->name;
            $chart_kills_data[] = (int)$p->total_kills;
            $chart_damage_labels[] = $p->name;
            $chart_damage_data[] = (int)$p->damage_dealt;
        }

        $chart_weapon_labels = [];
        $chart_weapon_data = [];
        foreach ($weapon_summary as $ws) {
            $chart_weapon_labels[] = $ws->display_name;
            $chart_weapon_data[] = (int)$ws->total_kills;
        }

        // 難度中文對照
        $difficulty_labels = [
            'easy'       => '簡單',
            'normal'     => '普通',
            'hard'       => '進階',
            'impossible' => '專家',
        ];

        ob_start();
        include L4D2_STATS_DIR . 'templates/session-detail.php';
        return ob_get_clean();
    }
}
