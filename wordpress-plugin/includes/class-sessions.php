<?php
namespace L4D2Stats;

/**
 * 戰役場次記錄 (以戰役為單位分組顯示)
 */
class Sessions {
    public static function render($atts) {
        $atts = shortcode_atts([
            'limit' => 300,
        ], $atts);

        $db = Database::instance();
        $limit = intval($atts['limit']);

        $sql = "
            SELECT
                s.id AS session_id,
                s.start_time, s.end_time, s.duration,
                s.difficulty, s.completed, s.campaign_completed,
                s.survivor_count,
                m.map_name AS map_code,
                m.display_name AS map_name,
                m.campaign_name,
                m.is_finale,
                GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS player_names
            FROM l4d2_sessions s
            JOIN l4d2_maps m ON m.id = s.map_id
            LEFT JOIN l4d2_session_players sp ON sp.session_id = s.id
            LEFT JOIN l4d2_players p ON p.id = sp.player_id
            GROUP BY s.id
            ORDER BY s.start_time DESC
            LIMIT %d
        ";

        $sessions_raw = $db->query($sql, [$limit], 'recent_sessions_raw_' . $limit, 60);

        // 反轉為 ASC 以進行分組
        $sessions_asc = array_reverse($sessions_raw);
        $campaign_runs = CampaignRun::group_sessions($sessions_asc);

        // 難度中文對照
        $difficulty_labels = [
            'easy'       => '簡單',
            'normal'     => '普通',
            'hard'       => '進階',
            'impossible' => '專家',
        ];

        ob_start();
        include L4D2_STATS_DIR . 'templates/sessions.php';
        return ob_get_clean();
    }
}
