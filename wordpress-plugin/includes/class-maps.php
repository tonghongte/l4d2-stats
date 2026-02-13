<?php
namespace L4D2Stats;

/**
 * 地圖統計
 */
class Maps {
    public static function render($atts) {
        $atts = shortcode_atts([
            'campaign' => '',
        ], $atts);

        $db = Database::instance();

        $where = 'WHERE m.times_played > 0';
        $params = [];
        if (!empty($atts['campaign'])) {
            $where .= ' AND m.campaign_name = %s';
            $params[] = sanitize_text_field($atts['campaign']);
        }

        $sql = "
            SELECT
                m.map_name, m.display_name, m.campaign_name,
                m.is_finale, m.times_played, m.times_completed,
                m.first_played, m.last_played,
                CASE WHEN m.times_played > 0
                     THEN ROUND(m.times_completed / m.times_played * 100, 1)
                     ELSE 0
                END AS completion_rate,
                (SELECT COUNT(DISTINCT sp.player_id)
                 FROM l4d2_session_players sp
                 JOIN l4d2_sessions s ON s.id = sp.session_id
                 WHERE s.map_id = m.id) AS unique_players
            FROM l4d2_maps m
            {$where}
            ORDER BY m.campaign_name, m.map_name
        ";

        $maps = $db->query($sql, $params, 'maps_' . ($atts['campaign'] ?: 'all'), 120);

        // 按戰役分組
        $campaigns = [];
        foreach ($maps as $map) {
            $cname = $map->campaign_name ?: '其他';
            if (!isset($campaigns[$cname])) {
                $campaigns[$cname] = [];
            }
            $campaigns[$cname][] = $map;
        }

        // 圖表數據: 各戰役總遊玩次數
        $chart_labels = [];
        $chart_plays = [];
        foreach ($campaigns as $name => $campaign_maps) {
            $total = 0;
            foreach ($campaign_maps as $m) {
                $total += (int)$m->times_played;
            }
            $chart_labels[] = $name;
            $chart_plays[] = $total;
        }

        ob_start();
        include L4D2_STATS_DIR . 'templates/maps.php';
        return ob_get_clean();
    }
}
