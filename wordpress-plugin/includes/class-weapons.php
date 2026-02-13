<?php
namespace L4D2Stats;

/**
 * 武器統計
 */
class Weapons {
    public static function render($atts) {
        $atts = shortcode_atts([
            'type' => '',
        ], $atts);

        $db = Database::instance();

        $where = '';
        $params = [];
        if (!empty($atts['type'])) {
            $where = 'WHERE w.weapon_type = %s';
            $params[] = sanitize_text_field($atts['type']);
        }

        $sql = "
            SELECT
                w.display_name, w.weapon_name, w.weapon_type,
                COALESCE(SUM(pws.kills), 0) AS total_kills,
                COALESCE(SUM(pws.headshots), 0) AS total_headshots,
                COALESCE(SUM(pws.damage_dealt), 0) AS total_damage,
                COALESCE(SUM(pws.shots_fired), 0) AS total_shots_fired,
                COALESCE(SUM(pws.shots_hit), 0) AS total_shots_hit,
                CASE WHEN SUM(pws.shots_fired) > 0
                     THEN ROUND(SUM(pws.shots_hit) / SUM(pws.shots_fired) * 100, 1)
                     ELSE 0
                END AS avg_accuracy,
                COUNT(DISTINCT pws.player_id) AS users_count
            FROM l4d2_weapons w
            LEFT JOIN l4d2_player_weapon_stats pws ON pws.weapon_id = w.id
            {$where}
            GROUP BY w.id
            HAVING total_kills > 0
            ORDER BY total_kills DESC
        ";

        $weapons = $db->query($sql, $params, 'weapons_' . ($atts['type'] ?: 'all'), 120);

        // 準備圖表數據
        $chart_labels = [];
        $chart_kills = [];
        foreach (array_slice($weapons, 0, 15) as $w) {
            $chart_labels[] = $w->display_name;
            $chart_kills[] = (int)$w->total_kills;
        }

        // 武器類型中文對照
        $type_labels = [
            'pistol'    => '手槍',
            'smg'       => '衝鋒槍',
            'shotgun'   => '散彈槍',
            'rifle'     => '突擊步槍',
            'sniper'    => '狙擊槍',
            'heavy'     => '重型武器',
            'melee'     => '近戰武器',
            'throwable' => '投擲物',
            'mounted'   => '架設武器',
            'other'     => '其他',
        ];

        ob_start();
        include L4D2_STATS_DIR . 'templates/weapons.php';
        return ob_get_clean();
    }
}
