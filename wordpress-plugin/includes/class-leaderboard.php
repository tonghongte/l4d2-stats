<?php
namespace L4D2Stats;

/**
 * 排行榜
 */
class Leaderboard {
    public static function render($atts) {
        $atts = shortcode_atts([
            'limit'   => 50,
            'sort_by' => 'total_kills',
        ], $atts);

        $db = Database::instance();
        $limit = intval($atts['limit']);
        $sort = self::sanitize_sort($atts['sort_by']);

        $sql = "
            SELECT
                p.id, p.name, p.steam_id, p.steam_id_64,
                p.total_playtime, p.last_seen,
                p.avatar_url, p.avatar_updated_at,
                EXISTS (
                    SELECT 1 FROM l4d2_session_players sp2
                    JOIN l4d2_sessions s2 ON s2.id = sp2.session_id
                    WHERE sp2.player_id = p.id
                    AND sp2.leave_time IS NULL
                    AND s2.end_time IS NULL
                    AND s2.start_time >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 4 HOUR)
                ) AS is_online,
                ps.kills_infected + ps.kills_si AS total_kills,
                ps.kills_si,
                ps.kills_tank,
                ps.headshots,
                ps.deaths,
                ps.incaps,
                ps.revives_given,
                ps.heals_given,
                CASE WHEN ps.deaths > 0
                     THEN ROUND((ps.kills_infected + ps.kills_si) / ps.deaths, 2)
                     ELSE ps.kills_infected + ps.kills_si
                END AS kd_ratio,
                CASE WHEN ps.shots_fired > 0
                     THEN ROUND(ps.shots_hit / ps.shots_fired * 100, 1)
                     ELSE 0
                END AS accuracy,
                ps.campaigns_completed,
                ps.friendly_fire_dealt
            FROM l4d2_players p
            JOIN l4d2_player_stats ps ON ps.player_id = p.id
            WHERE p.total_playtime >= 300
            ORDER BY {$sort} DESC
            LIMIT %d
        ";

        $players = $db->query($sql, [$limit], "leaderboard_{$sort}_{$limit}", 60);

        // 批次抓取頭像
        $avatars = Plugin::fetch_avatars($players);

        ob_start();
        include L4D2_STATS_DIR . 'templates/leaderboard.php';
        return ob_get_clean();
    }

    private static function sanitize_sort($sort) {
        $allowed = [
            'total_kills', 'kills_si', 'kills_tank', 'headshots',
            'deaths', 'kd_ratio', 'accuracy', 'revives_given',
            'heals_given', 'total_playtime', 'campaigns_completed',
        ];
        return in_array($sort, $allowed, true) ? $sort : 'total_kills';
    }
}
