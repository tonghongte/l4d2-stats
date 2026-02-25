<?php
namespace L4D2Stats;

/**
 * 地圖統計 — 雙模式路由
 * 預設: 戰役列表
 * ?campaign=xxx: 戰役地圖詳情
 */
class Maps {
    public static function render($atts) {
        $atts = shortcode_atts([], $atts);

        $campaign = isset($_GET['campaign']) ? sanitize_text_field($_GET['campaign']) : '';

        if (!empty($campaign)) {
            return self::render_campaign_detail($campaign);
        }

        return self::render_campaign_list();
    }

    /**
     * 戰役列表模式
     */
    private static function render_campaign_list() {
        $db = Database::instance();

        // 基本戰役統計 (通關次數以 campaign_completed 計算)
        $sql = "
            SELECT
                m.campaign_name,
                COUNT(DISTINCT m.id) AS chapter_count,
                COALESCE(SUM(s.campaign_completed), 0) AS total_completions,
                MAX(m.last_played) AS last_played
            FROM l4d2_maps m
            LEFT JOIN l4d2_sessions s ON s.map_id = m.id AND s.survivor_count > 0
            WHERE m.times_played > 0 AND m.campaign_name IS NOT NULL AND m.campaign_name != ''
            GROUP BY m.campaign_name
        ";

        $campaigns = $db->query($sql, [], 'maps_campaign_list', 120);

        // 各戰役獨立場次數 (遊玩次數以獨立戰役場次計算)
        // 判斷場次起點: 無前一場同戰役 session，或距上一場結束超過 300 秒
        $run_sql = "
            SELECT t.campaign_name, COUNT(*) AS run_count
            FROM (
                SELECT
                    m.campaign_name,
                    s.id,
                    s.start_time,
                    (
                        SELECT MAX(s2.end_time)
                        FROM l4d2_sessions s2
                        JOIN l4d2_maps m2 ON m2.id = s2.map_id
                        WHERE m2.campaign_name = m.campaign_name
                          AND s2.survivor_count > 0
                          AND s2.end_time IS NOT NULL
                          AND s2.campaign_completed = 0
                          AND s2.start_time < s.start_time
                    ) AS prev_end
                FROM l4d2_sessions s
                JOIN l4d2_maps m ON m.id = s.map_id
                WHERE s.survivor_count > 0
                  AND m.campaign_name IS NOT NULL AND m.campaign_name != ''
            ) t
            WHERE t.prev_end IS NULL
               OR TIMESTAMPDIFF(SECOND, t.prev_end, t.start_time) > 300
            GROUP BY t.campaign_name
        ";

        $run_counts_raw = $db->query($run_sql, [], 'maps_campaign_runs', 120);
        $run_counts = [];
        foreach ($run_counts_raw as $r) {
            $run_counts[$r->campaign_name] = (int)$r->run_count;
        }

        // 合併場次數與通關率
        foreach ($campaigns as $c) {
            $runs = $run_counts[$c->campaign_name] ?? 0;
            $c->total_plays = $runs;
            $c->avg_completion_rate = $runs > 0
                ? round((int)$c->total_completions / $runs * 100, 1)
                : 0;
        }

        // 按遊玩場次降序排序
        usort($campaigns, fn($a, $b) => $b->total_plays - $a->total_plays);

        // 各戰役獨立玩家數
        foreach ($campaigns as $c) {
            $players = $db->get_var(
                "SELECT COUNT(DISTINCT sp.player_id)
                 FROM l4d2_session_players sp
                 JOIN l4d2_sessions s ON s.id = sp.session_id
                 JOIN l4d2_maps m ON m.id = s.map_id
                 WHERE m.campaign_name = %s",
                [$c->campaign_name]
            );
            $c->unique_players = (int)$players;
        }

        // 圖表數據: 各戰役遊玩場次數
        $chart_labels = [];
        $chart_plays = [];
        foreach ($campaigns as $c) {
            $chart_labels[] = $c->campaign_name;
            $chart_plays[] = (int)$c->total_plays;
        }

        ob_start();
        include L4D2_STATS_DIR . 'templates/maps.php';
        return ob_get_clean();
    }

    /**
     * 戰役地圖詳情模式
     */
    private static function render_campaign_detail($campaign_name) {
        $db = Database::instance();

        // 查詢該戰役的所有地圖
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
            WHERE m.times_played > 0 AND m.campaign_name = %s
            ORDER BY m.map_name
        ";

        $maps = $db->query($sql, [$campaign_name], 'maps_detail_' . $campaign_name, 120);

        if (empty($maps)) {
            return '<div class="l4d2-notice">找不到此戰役的地圖資料。</div>';
        }

        // 戰役總體統計
        $campaign_stats = (object)[
            'campaign_name'   => $campaign_name,
            'chapter_count'   => count($maps),
            'total_plays'     => 0,
            'total_completions' => 0,
            'avg_completion_rate' => 0,
            'unique_players'  => 0,
            'first_played'    => null,
            'last_played'     => null,
        ];

        foreach ($maps as $m) {
            if ($m->first_played && (!$campaign_stats->first_played || $m->first_played < $campaign_stats->first_played)) {
                $campaign_stats->first_played = $m->first_played;
            }
            if ($m->last_played && (!$campaign_stats->last_played || $m->last_played > $campaign_stats->last_played)) {
                $campaign_stats->last_played = $m->last_played;
            }
        }

        // 遊玩次數以獨立戰役場次計算
        $campaign_run_count = (int)$db->get_var(
            "SELECT COUNT(*) FROM (
                SELECT s.id, s.start_time,
                    (SELECT MAX(s2.end_time)
                     FROM l4d2_sessions s2
                     JOIN l4d2_maps m2 ON m2.id = s2.map_id
                     WHERE m2.campaign_name = %s
                       AND s2.survivor_count > 0
                       AND s2.end_time IS NOT NULL
                       AND s2.campaign_completed = 0
                       AND s2.start_time < s.start_time
                    ) AS prev_end
                FROM l4d2_sessions s
                JOIN l4d2_maps m ON m.id = s.map_id
                WHERE s.survivor_count > 0 AND m.campaign_name = %s
             ) t
             WHERE t.prev_end IS NULL OR TIMESTAMPDIFF(SECOND, t.prev_end, t.start_time) > 300",
            [$campaign_name, $campaign_name]
        );

        $campaign_completed_count = (int)$db->get_var(
            "SELECT COALESCE(SUM(s.campaign_completed), 0)
             FROM l4d2_sessions s
             JOIN l4d2_maps m ON m.id = s.map_id
             WHERE m.campaign_name = %s AND s.survivor_count > 0",
            [$campaign_name]
        );

        $campaign_stats->total_plays = $campaign_run_count;
        $campaign_stats->total_completions = $campaign_completed_count;
        if ($campaign_stats->total_plays > 0) {
            $campaign_stats->avg_completion_rate = round(
                $campaign_stats->total_completions / $campaign_stats->total_plays * 100, 1
            );
        }

        // 獨立玩家數
        $campaign_stats->unique_players = (int)$db->get_var(
            "SELECT COUNT(DISTINCT sp.player_id)
             FROM l4d2_session_players sp
             JOIN l4d2_sessions s ON s.id = sp.session_id
             JOIN l4d2_maps m ON m.id = s.map_id
             WHERE m.campaign_name = %s",
            [$campaign_name]
        );

        // 圖表數據: 各章節遊玩次數
        $chart_labels = [];
        $chart_plays = [];
        $chart_completion = [];
        foreach ($maps as $m) {
            $chart_labels[] = $m->display_name ?: $m->map_name;
            $chart_plays[] = (int)$m->times_played;
            $chart_completion[] = (float)$m->completion_rate;
        }

        ob_start();
        include L4D2_STATS_DIR . 'templates/campaign-maps-detail.php';
        return ob_get_clean();
    }
}
