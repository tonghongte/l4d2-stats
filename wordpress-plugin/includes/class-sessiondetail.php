<?php
namespace L4D2Stats;

/**
 * 場次詳細資訊 — 雙模式路由
 * 預設: 戰役場次詳細 (campaign run detail)
 * ?view=map: 單地圖場次詳細 (原始行為)
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

        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';

        if ($view === 'map') {
            return self::render_map_detail($session_id);
        }

        if ($view === 'map_run') {
            $map_code = isset($_GET['map_code']) ? sanitize_text_field($_GET['map_code']) : '';
            return self::render_map_in_run_detail($session_id, $map_code);
        }

        return self::render_campaign_detail($session_id);
    }

    /**
     * 戰役場次詳細頁面
     */
    private static function render_campaign_detail($session_id) {
        $db = Database::instance();

        // 透過 CampaignRun 找出包含此 session 的戰役場次
        $run = CampaignRun::find_run_for_session($session_id);

        // 如果找不到戰役 run (例如自訂地圖)，fallback 到單地圖
        if (!$run) {
            return self::render_map_detail($session_id);
        }

        // 收集所有 session_id
        $session_ids = array_map(function($s) {
            return (int)$s->session_id;
        }, $run['sessions']);
        $ids_placeholder = implode(',', $session_ids);

        // 聚合玩家數據
        $players = $db->query(
            "SELECT
                p.name, p.steam_id, p.steam_id_64,
                p.avatar_url, p.avatar_updated_at,
                SUM(COALESCE(sps.kills_infected, 0)) AS kills_infected,
                SUM(COALESCE(sps.kills_si, 0)) AS kills_si,
                SUM(COALESCE(sps.kills_witch, 0)) AS kills_witch,
                SUM(COALESCE(sps.kills_tank, 0)) AS kills_tank,
                SUM(COALESCE(sps.headshots, 0)) AS headshots,
                SUM(COALESCE(sps.damage_dealt, 0)) AS damage_dealt,
                SUM(COALESCE(sps.damage_taken, 0)) AS damage_taken,
                SUM(COALESCE(sps.deaths, 0)) AS deaths,
                SUM(COALESCE(sps.incaps, 0)) AS incaps,
                SUM(COALESCE(sps.revives_given, 0)) AS revives_given,
                SUM(COALESCE(sps.revives_received, 0)) AS revives_received,
                SUM(COALESCE(sps.heals_given, 0)) AS heals_given,
                SUM(COALESCE(sps.heals_received, 0)) AS heals_received,
                SUM(COALESCE(sps.health_restored, 0)) AS health_restored,
                SUM(COALESCE(sps.pills_used, 0)) AS pills_used,
                SUM(COALESCE(sps.adrenaline_used, 0)) AS adrenaline_used,
                SUM(COALESCE(sps.defibs_used, 0)) AS defibs_used,
                SUM(COALESCE(sps.friendly_fire_dealt, 0)) AS friendly_fire_dealt,
                SUM(COALESCE(sps.friendly_fire_damage, 0)) AS friendly_fire_damage,
                SUM(COALESCE(sps.shots_fired, 0)) AS shots_fired,
                SUM(COALESCE(sps.shots_hit, 0)) AS shots_hit,
                SUM(COALESCE(sps.melee_swings, 0)) AS melee_swings,
                SUM(COALESCE(sps.melee_hits, 0)) AS melee_hits,
                SUM(COALESCE(sps.skeets, 0))      AS skeets,
                SUM(COALESCE(sps.crowns, 0))      AS crowns,
                SUM(COALESCE(sps.levels, 0))      AS levels,
                SUM(COALESCE(sps.deadstops, 0))   AS deadstops,
                SUM(COALESCE(sps.rock_skeets, 0)) AS rock_skeets,
                SUM(COALESCE(sps.tongue_cuts, 0)) AS tongue_cuts,
                SUM(COALESCE(sps.boomer_pops, 0)) AS boomer_pops,
                (SUM(COALESCE(sps.kills_infected, 0)) + SUM(COALESCE(sps.kills_si, 0))) AS total_kills,
                CASE WHEN SUM(COALESCE(sps.shots_fired, 0)) > 0
                     THEN LEAST(100, ROUND((SUM(COALESCE(sps.shots_hit, 0)) + SUM(COALESCE(sps.kills_infected, 0))) / SUM(COALESCE(sps.shots_fired, 0)) * 100, 1))
                     ELSE 0
                END AS accuracy,
                CASE WHEN (SUM(COALESCE(sps.kills_infected, 0)) + SUM(COALESCE(sps.kills_si, 0))) > 0
                     THEN ROUND(SUM(COALESCE(sps.headshots, 0)) / (SUM(COALESCE(sps.kills_infected, 0)) + SUM(COALESCE(sps.kills_si, 0))) * 100, 1)
                     ELSE 0
                END AS hs_rate
             FROM l4d2_session_players sp
             JOIN l4d2_players p ON p.id = sp.player_id
             LEFT JOIN l4d2_session_player_stats sps
                  ON sps.session_id = sp.session_id AND sps.player_id = sp.player_id
             WHERE sp.session_id IN ({$ids_placeholder})
             GROUP BY p.id
             ORDER BY total_kills DESC"
        );

        // 檢查是否有數據
        $has_stats = false;
        foreach ($players as $p) {
            if ((int)$p->total_kills > 0 || (int)$p->damage_dealt > 0 || (int)$p->shots_fired > 0) {
                $has_stats = true;
                break;
            }
        }

        // 聚合武器數據
        $weapons = $db->query(
            "SELECT
                p.name AS player_name,
                w.display_name AS weapon_name, w.weapon_type,
                SUM(spws.kills) AS kills,
                SUM(spws.headshots) AS headshots,
                SUM(spws.damage_dealt) AS damage_dealt,
                SUM(spws.shots_fired) AS shots_fired,
                SUM(spws.shots_hit) AS shots_hit,
                CASE WHEN SUM(spws.shots_fired) > 0
                     THEN ROUND(SUM(spws.shots_hit) / SUM(spws.shots_fired) * 100, 1)
                     ELSE 0
                END AS accuracy
             FROM l4d2_session_player_weapon_stats spws
             JOIN l4d2_players p ON p.id = spws.player_id
             JOIN l4d2_weapons w ON w.id = spws.weapon_id
             WHERE spws.session_id IN ({$ids_placeholder})
             GROUP BY spws.player_id, spws.weapon_id
             ORDER BY kills DESC"
        );

        // 武器彙總 (甜甜圈圖)
        $weapon_summary = $db->query(
            "SELECT
                w.display_name,
                SUM(spws.kills) AS total_kills
             FROM l4d2_session_player_weapon_stats spws
             JOIN l4d2_weapons w ON w.id = spws.weapon_id
             WHERE spws.session_id IN ({$ids_placeholder})
             GROUP BY spws.weapon_id
             HAVING total_kills > 0
             ORDER BY total_kills DESC
             LIMIT 10"
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

        // 批次抓取頭像
        $avatars = Plugin::fetch_avatars($players);

        // 附加成就（需玩家數據）
        $run_total_deaths = 0;
        $run_total_ff     = 0;
        foreach ($players as $p) {
            $run_total_deaths += (int)$p->deaths;
            $run_total_ff     += (int)$p->friendly_fire_damage;
        }
        $is_no_death = $has_stats && $run['is_completed'] && $run_total_deaths === 0;
        $is_no_ff    = $has_stats && $run['is_completed'] && $run_total_ff === 0;

        // 玩家亮點卡片（多玩家才顯示）
        $spotlights = [];
        if ($has_stats && count($players) > 1) {
            $spotlights = self::compute_spotlights($players, $avatars);
        }

        // CS2 風格玩家卡片
        $player_cards = [];
        $funny_line   = '';
        if ($has_stats && !empty($players)) {
            $player_cards = self::compute_player_cards($players, $avatars);
            $funny_line   = self::compute_funny_line($players);
        }

        // 按 map_code 分組（保留首次出現順序）
        $map_groups = [];
        foreach ($run['sessions'] as $s) {
            $key = $s->map_code;
            if (!isset($map_groups[$key])) {
                $map_groups[$key] = [
                    'map_code'              => $s->map_code,
                    'map_name'              => $s->map_name,
                    'is_finale'             => (int)$s->is_finale,
                    'sessions'              => [],
                    'total_duration'        => 0,
                    'completed'             => false,
                    'campaign_completed'    => false,
                    'completion_difficulty' => null,
                    'not_played'            => false,
                ];
            }
            $map_groups[$key]['sessions'][] = $s;
            $map_groups[$key]['total_duration'] += (int)$s->duration;
            if ((int)$s->campaign_completed) {
                $map_groups[$key]['campaign_completed'] = true;
                $map_groups[$key]['completion_difficulty'] = $s->difficulty;
            } elseif ((int)$s->completed
                && !$map_groups[$key]['completed']
                && !$map_groups[$key]['campaign_completed']
            ) {
                $map_groups[$key]['completed'] = true;
                $map_groups[$key]['completion_difficulty'] = $s->difficulty;
            }
        }

        // 取得此戰役的完整地圖清單，依章節順序排列
        $all_campaign_maps = $db->query(
            "SELECT map_name, display_name, is_finale
             FROM l4d2_maps
             WHERE campaign_name = %s
             ORDER BY map_name ASC",
            [$run['campaign_name']]
        );
        // 依章節編號排序（相容標準地圖 c\d+m\d+ 及自訂地圖尾數編號）
        if (!empty($all_campaign_maps)) {
            usort($all_campaign_maps, fn($a, $b) =>
                CampaignRun::map_chapter_order($a->map_name) <=> CampaignRun::map_chapter_order($b->map_name)
            );
        }

        // 以完整清單為基準建立最終 map_list；沒遊玩的補上 not_played 項目
        if (!empty($all_campaign_maps)) {
            $map_list = [];
            $idx = 0;
            foreach ($all_campaign_maps as $cm) {
                $idx++;
                $key = $cm->map_name;
                if (isset($map_groups[$key])) {
                    $entry = $map_groups[$key];
                } else {
                    $entry = [
                        'map_code'              => $cm->map_name,
                        'map_name'              => $cm->display_name ?: $cm->map_name,
                        'is_finale'             => (int)$cm->is_finale,
                        'sessions'              => [],
                        'total_duration'        => 0,
                        'completed'             => false,
                        'campaign_completed'    => false,
                        'completion_difficulty' => null,
                        'not_played'            => true,
                    ];
                }
                $entry['chapter_idx'] = $idx;
                $map_list[] = $entry;
            }
        } else {
            // 自訂地圖或查無資料時，退回僅列出本次遊玩的地圖
            $map_list = [];
            $idx = 0;
            foreach ($map_groups as $entry) {
                $idx++;
                $entry['chapter_idx'] = $idx;
                $map_list[] = $entry;
            }
        }

        ob_start();
        include L4D2_STATS_DIR . 'templates/campaign-detail.php';
        return ob_get_clean();
    }

    /**
     * 地圖重試彙總頁面 — 某次戰役 run 中單一地圖的所有嘗試
     */
    private static function render_map_in_run_detail($session_id, $map_code) {
        if (empty($map_code)) {
            return self::render_campaign_detail($session_id);
        }

        $db = Database::instance();

        $run = CampaignRun::find_run_for_session($session_id);
        if (!$run) {
            return self::render_map_detail($session_id);
        }

        // 篩選此地圖的所有 session
        $map_sessions = array_values(array_filter($run['sessions'], function ($s) use ($map_code) {
            return $s->map_code === $map_code;
        }));

        if (empty($map_sessions)) {
            return self::render_campaign_detail($session_id);
        }

        $map_info    = $map_sessions[0];
        $session_ids = array_map(fn($s) => (int)$s->session_id, $map_sessions);
        $ids_ph      = implode(',', $session_ids);

        // 聚合玩家數據
        $players = $db->query(
            "SELECT
                p.name, p.steam_id, p.steam_id_64,
                p.avatar_url, p.avatar_updated_at,
                SUM(COALESCE(sps.kills_infected, 0)) AS kills_infected,
                SUM(COALESCE(sps.kills_si, 0)) AS kills_si,
                SUM(COALESCE(sps.kills_witch, 0)) AS kills_witch,
                SUM(COALESCE(sps.kills_tank, 0)) AS kills_tank,
                SUM(COALESCE(sps.headshots, 0)) AS headshots,
                SUM(COALESCE(sps.damage_dealt, 0)) AS damage_dealt,
                SUM(COALESCE(sps.damage_taken, 0)) AS damage_taken,
                SUM(COALESCE(sps.deaths, 0)) AS deaths,
                SUM(COALESCE(sps.incaps, 0)) AS incaps,
                SUM(COALESCE(sps.revives_given, 0)) AS revives_given,
                SUM(COALESCE(sps.revives_received, 0)) AS revives_received,
                SUM(COALESCE(sps.heals_given, 0)) AS heals_given,
                SUM(COALESCE(sps.heals_received, 0)) AS heals_received,
                SUM(COALESCE(sps.health_restored, 0)) AS health_restored,
                SUM(COALESCE(sps.pills_used, 0)) AS pills_used,
                SUM(COALESCE(sps.adrenaline_used, 0)) AS adrenaline_used,
                SUM(COALESCE(sps.defibs_used, 0)) AS defibs_used,
                SUM(COALESCE(sps.friendly_fire_dealt, 0)) AS friendly_fire_dealt,
                SUM(COALESCE(sps.friendly_fire_damage, 0)) AS friendly_fire_damage,
                SUM(COALESCE(sps.shots_fired, 0)) AS shots_fired,
                SUM(COALESCE(sps.shots_hit, 0)) AS shots_hit,
                SUM(COALESCE(sps.melee_swings, 0)) AS melee_swings,
                SUM(COALESCE(sps.melee_hits, 0)) AS melee_hits,
                SUM(COALESCE(sps.skeets, 0))      AS skeets,
                SUM(COALESCE(sps.crowns, 0))      AS crowns,
                SUM(COALESCE(sps.levels, 0))      AS levels,
                SUM(COALESCE(sps.deadstops, 0))   AS deadstops,
                SUM(COALESCE(sps.rock_skeets, 0)) AS rock_skeets,
                SUM(COALESCE(sps.tongue_cuts, 0)) AS tongue_cuts,
                SUM(COALESCE(sps.boomer_pops, 0)) AS boomer_pops,
                (SUM(COALESCE(sps.kills_infected, 0)) + SUM(COALESCE(sps.kills_si, 0))) AS total_kills,
                CASE WHEN SUM(COALESCE(sps.shots_fired, 0)) > 0
                     THEN LEAST(100, ROUND((SUM(COALESCE(sps.shots_hit, 0)) + SUM(COALESCE(sps.kills_infected, 0))) / SUM(COALESCE(sps.shots_fired, 0)) * 100, 1))
                     ELSE 0
                END AS accuracy,
                CASE WHEN (SUM(COALESCE(sps.kills_infected, 0)) + SUM(COALESCE(sps.kills_si, 0))) > 0
                     THEN ROUND(SUM(COALESCE(sps.headshots, 0)) / (SUM(COALESCE(sps.kills_infected, 0)) + SUM(COALESCE(sps.kills_si, 0))) * 100, 1)
                     ELSE 0
                END AS hs_rate
             FROM l4d2_session_players sp
             JOIN l4d2_players p ON p.id = sp.player_id
             LEFT JOIN l4d2_session_player_stats sps
                  ON sps.session_id = sp.session_id AND sps.player_id = sp.player_id
             WHERE sp.session_id IN ({$ids_ph})
             GROUP BY p.id
             ORDER BY total_kills DESC"
        );

        $has_stats = false;
        foreach ($players as $p) {
            if ((int)$p->total_kills > 0 || (int)$p->damage_dealt > 0 || (int)$p->shots_fired > 0) {
                $has_stats = true;
                break;
            }
        }

        // 武器數據
        $weapons = $db->query(
            "SELECT
                p.name AS player_name,
                w.display_name AS weapon_name, w.weapon_type,
                SUM(spws.kills) AS kills,
                SUM(spws.headshots) AS headshots,
                SUM(spws.damage_dealt) AS damage_dealt,
                SUM(spws.shots_fired) AS shots_fired,
                SUM(spws.shots_hit) AS shots_hit,
                CASE WHEN SUM(spws.shots_fired) > 0
                     THEN ROUND(SUM(spws.shots_hit) / SUM(spws.shots_fired) * 100, 1)
                     ELSE 0
                END AS accuracy
             FROM l4d2_session_player_weapon_stats spws
             JOIN l4d2_players p ON p.id = spws.player_id
             JOIN l4d2_weapons w ON w.id = spws.weapon_id
             WHERE spws.session_id IN ({$ids_ph})
             GROUP BY spws.player_id, spws.weapon_id
             ORDER BY kills DESC"
        );

        $weapon_summary = $db->query(
            "SELECT
                w.display_name,
                SUM(spws.kills) AS total_kills
             FROM l4d2_session_player_weapon_stats spws
             JOIN l4d2_weapons w ON w.id = spws.weapon_id
             WHERE spws.session_id IN ({$ids_ph})
             GROUP BY spws.weapon_id
             HAVING total_kills > 0
             ORDER BY total_kills DESC
             LIMIT 10"
        );

        $chart_kills_labels  = [];
        $chart_kills_data    = [];
        $chart_damage_labels = [];
        $chart_damage_data   = [];
        foreach ($players as $p) {
            $chart_kills_labels[]  = $p->name;
            $chart_kills_data[]    = (int)$p->total_kills;
            $chart_damage_labels[] = $p->name;
            $chart_damage_data[]   = (int)$p->damage_dealt;
        }

        $chart_weapon_labels = [];
        $chart_weapon_data   = [];
        foreach ($weapon_summary as $ws) {
            $chart_weapon_labels[] = $ws->display_name;
            $chart_weapon_data[]   = (int)$ws->total_kills;
        }

        $difficulty_labels = [
            'easy'       => '簡單',
            'normal'     => '普通',
            'hard'       => '進階',
            'impossible' => '專家',
        ];

        $avatars = Plugin::fetch_avatars($players);

        ob_start();
        include L4D2_STATS_DIR . 'templates/campaign-map-run.php';
        return ob_get_clean();
    }

    /**
     * 計算玩家亮點卡片（每個類別最突出的玩家）
     */
    private static function compute_spotlights(array $players, array $avatars): array {
        $spots = [];
        $mx_kills = $mx_damage = $mx_hs = $mx_si = null;
        $mx_revives = $mx_heals = $mx_deaths = $mx_ff = null;
        $mx_taken = $mx_acc = null;

        foreach ($players as $p) {
            if ($mx_kills   === null || (int)$p->total_kills          > (int)$mx_kills->total_kills)          $mx_kills   = $p;
            if ($mx_damage  === null || (int)$p->damage_dealt         > (int)$mx_damage->damage_dealt)        $mx_damage  = $p;
            if ($mx_hs      === null || (int)$p->headshots            > (int)$mx_hs->headshots)               $mx_hs      = $p;
            if ($mx_si      === null || (int)$p->kills_si             > (int)$mx_si->kills_si)                $mx_si      = $p;
            if ($mx_revives === null || (int)$p->revives_given        > (int)$mx_revives->revives_given)      $mx_revives = $p;
            if ($mx_heals   === null || (int)$p->heals_given          > (int)$mx_heals->heals_given)          $mx_heals   = $p;
            if ($mx_deaths  === null || (int)$p->deaths               > (int)$mx_deaths->deaths)              $mx_deaths  = $p;
            if ($mx_ff      === null || (int)$p->friendly_fire_damage > (int)$mx_ff->friendly_fire_damage)    $mx_ff      = $p;
            if ($mx_taken   === null || (int)$p->damage_taken         > (int)$mx_taken->damage_taken)         $mx_taken   = $p;
            if ((int)$p->shots_fired >= 100 && ($mx_acc === null || (float)$p->accuracy > (float)$mx_acc->accuracy)) {
                $mx_acc = $p;
            }
        }

        $push = function($label, $player, $value, $color) use (&$spots, $avatars) {
            $spots[] = [
                'label'  => $label,
                'player' => $player,
                'value'  => $value,
                'color'  => $color,
                'avatar' => $avatars[$player->steam_id_64] ?? Plugin::DEFAULT_AVATAR,
            ];
        };

        if ($mx_kills   !== null && (int)$mx_kills->total_kills > 0)
            $push('最多擊殺', $mx_kills, number_format((int)$mx_kills->total_kills) . ' 殺', 'red');
        if ($mx_damage  !== null && (int)$mx_damage->damage_dealt > 0)
            $push('最高傷害', $mx_damage, number_format((int)$mx_damage->damage_dealt), 'orange');
        if ($mx_hs      !== null && (int)$mx_hs->headshots > 0)
            $push('最多爆頭', $mx_hs, number_format((int)$mx_hs->headshots) . ' 次', 'purple');
        if ($mx_si      !== null && (int)$mx_si->kills_si > 0)
            $push('特感獵人', $mx_si, number_format((int)$mx_si->kills_si) . ' 殺', 'green');
        if ($mx_acc     !== null)
            $push('神槍手', $mx_acc, $mx_acc->accuracy . '%', 'blue');
        if ($mx_revives !== null && (int)$mx_revives->revives_given > 0)
            $push('救援英雄', $mx_revives, (int)$mx_revives->revives_given . ' 次', 'teal');
        if ($mx_heals   !== null && (int)$mx_heals->heals_given > 0)
            $push('醫療兵', $mx_heals, (int)$mx_heals->heals_given . ' 次', 'cyan');
        if ($mx_taken   !== null && (int)$mx_taken->damage_taken > 0)
            $push('吸彈海綿', $mx_taken, number_format((int)$mx_taken->damage_taken), 'gray');
        if ($mx_deaths  !== null && (int)$mx_deaths->deaths > 0)
            $push('替死鬼', $mx_deaths, (int)$mx_deaths->deaths . ' 死', 'dark');
        if ($mx_ff      !== null && (int)$mx_ff->friendly_fire_damage > 0)
            $push('隊友之殤', $mx_ff, number_format((int)$mx_ff->friendly_fire_damage), 'darkred');

        return $spots;
    }

    /**
     * CS2 風格玩家戰績卡片
     * 為每位玩家分配一個唯一頭銜，回傳卡片資料陣列
     */
    public static function compute_player_cards(array $players, array $avatars): array {
        if (empty($players)) return [];

        // 頭銜定義（依優先順序排列）
        $role_defs = [
            ['id'=>'kills',    'stat'=>'total_kills',         'name'=>'殲滅者',   'en'=>'The Exterminator', 'color'=>'#ef5350', 'desc'=>'全場擊殺最高'],
            ['id'=>'damage',   'stat'=>'damage_dealt',         'name'=>'破壞之神', 'en'=>'The Demolisher',   'color'=>'#ff7043', 'desc'=>'全場傷害輸出最高'],
            ['id'=>'headshot', 'stat'=>'headshots',            'name'=>'爆頭獵人', 'en'=>'The Headhunter',   'color'=>'#ab47bc', 'desc'=>'全場爆頭數最高'],
            ['id'=>'si',       'stat'=>'kills_si',             'name'=>'特感剋星', 'en'=>'The SI Slayer',    'color'=>'#42a5f5', 'desc'=>'特感擊殺最多'],
            ['id'=>'accuracy', 'stat'=>'accuracy',             'name'=>'神射手',   'en'=>'The Marksman',     'color'=>'#ffca28', 'desc'=>'命中率最高（需≥100發）'],
            ['id'=>'revives',  'stat'=>'revives_given',        'name'=>'救命稻草', 'en'=>'The Lifeline',     'color'=>'#26c6da', 'desc'=>'救援隊友次數最多'],
            ['id'=>'heals',    'stat'=>'heals_given',          'name'=>'移動藥局', 'en'=>'The Pharmacy',     'color'=>'#66bb6a', 'desc'=>'治療隊友次數最多'],
            ['id'=>'taken',    'stat'=>'damage_taken',         'name'=>'人形坦克', 'en'=>'The Meatshield',   'color'=>'#78909c', 'desc'=>'承受傷害最多'],
            ['id'=>'deaths',   'stat'=>'deaths',               'name'=>'替死鬼',   'en'=>'The Martyr',       'color'=>'#607d8b', 'desc'=>'死亡次數最多'],
            ['id'=>'ff',       'stat'=>'friendly_fire_damage', 'name'=>'友傷製造',   'en'=>'The Team Hazard',    'color'=>'#c62828', 'desc'=>'造成友傷傷害最高'],
            // 技巧頭銜（需 ≥1 次才可獲得）
            ['id'=>'skeet',      'stat'=>'skeets',      'name'=>'空中截擊手', 'en'=>'The Skeet King',    'color'=>'#00e5ff', 'desc'=>'本局 Skeet 次數最多',      'min_val'=>1],
            ['id'=>'crown',      'stat'=>'crowns',      'name'=>'女王剋星',   'en'=>'The Crown Queen',   'color'=>'#e91e63', 'desc'=>'本局 Crown 次數最多',      'min_val'=>1],
            ['id'=>'level',      'stat'=>'levels',      'name'=>'衝鋒拆解',   'en'=>'The Leveler',       'color'=>'#ff6d00', 'desc'=>'本局 Level 次數最多',      'min_val'=>1],
            ['id'=>'deadstop',   'stat'=>'deadstops',   'name'=>'反射之神',   'en'=>'The Reflex God',    'color'=>'#76ff03', 'desc'=>'本局 Deadstop 次數最多',   'min_val'=>1],
            ['id'=>'rock_skeet', 'stat'=>'rock_skeets', 'name'=>'岩石毀滅',   'en'=>'The Rock Crusher',  'color'=>'#ffd740', 'desc'=>'本局 Rock Skeet 次數最多', 'min_val'=>1],
            ['id'=>'tongue_cut', 'stat'=>'tongue_cuts', 'name'=>'切舌救主',   'en'=>'The Tongue Cutter', 'color'=>'#69f0ae', 'desc'=>'本局切舌解救次數最多',     'min_val'=>1],
            ['id'=>'boomer_pop', 'stat'=>'boomer_pops', 'name'=>'炸彈終結',   'en'=>'The Boomer Buster', 'color'=>'#ffab40', 'desc'=>'本局 Boomer Pop 次數最多', 'min_val'=>1],
        ];

        // 貪心分配：每個頭銜只給最符合的玩家（且每人只有一個頭銜）
        $assigned = []; // steam_id => ['role' => ..., 'val' => float]
        $taken    = []; // role_id => true

        foreach ($role_defs as $role) {
            if (count($assigned) >= count($players)) break;
            $best = null; $best_val = -1;
            foreach ($players as $p) {
                if (isset($assigned[$p->steam_id])) continue;
                if ($role['id'] === 'accuracy' && (int)$p->shots_fired < 100) continue;
                $val = (float)($p->{$role['stat']} ?? 0);
                if (isset($role['min_val']) && $val < $role['min_val']) continue;
                if ($best === null || $val > $best_val) { $best_val = $val; $best = $p; }
            }
            if ($best !== null && !isset($taken[$role['id']])) {
                $assigned[$best->steam_id] = ['role' => $role, 'val' => $best_val];
                $taken[$role['id']] = true;
            }
        }

        // 未被分配到的玩家（罕見）給第一個還未被用的頭銜
        foreach ($players as $p) {
            if (!isset($assigned[$p->steam_id])) {
                foreach ($role_defs as $role) {
                    if (!isset($taken[$role['id']])) {
                        $assigned[$p->steam_id] = ['role' => $role, 'val' => 0];
                        $taken[$role['id']] = true;
                        break;
                    }
                }
            }
        }

        // 組建卡片
        $used_hl = [];
        $cards   = [];
        foreach ($players as $p) {
            $role_info  = $assigned[$p->steam_id] ?? ['role' => $role_defs[0], 'val' => 0];
            $highlight  = self::pick_unique_highlight($p, $used_hl);
            $used_hl[]  = $highlight['type'];
            $cards[] = [
                'player'    => $p,
                'avatar'    => $avatars[$p->steam_id_64] ?? Plugin::DEFAULT_AVATAR,
                'score'     => (int)$p->total_kills,
                'role'      => $role_info['role'],
                'highlight' => $highlight,
            ];
        }
        return $cards;
    }

    /**
     * 為單一玩家挑選一項本局亮點／失誤，確保與已使用類型不重複
     */
    private static function pick_unique_highlight($p, array $used): array {
        $acc      = (float)$p->accuracy;
        $kills    = (int)$p->total_kills;
        $ff       = (int)$p->friendly_fire_damage;
        $deaths   = (int)$p->deaths;
        $incaps   = (int)$p->incaps;
        $revives  = (int)$p->revives_given;
        $headshots= (int)$p->headshots;
        $damage   = (int)$p->damage_dealt;
        $shots    = (int)$p->shots_fired;
        $hs_rate  = $kills > 0 ? round($headshots / $kills * 100, 1) : 0;
        $skeets    = (int)($p->skeets      ?? 0);
        $crowns    = (int)($p->crowns      ?? 0);
        $levels    = (int)($p->levels      ?? 0);
        $deadstops = (int)($p->deadstops   ?? 0);
        $rock_skts = (int)($p->rock_skeets ?? 0);
        $tongue    = (int)($p->tongue_cuts ?? 0);
        $boomer    = (int)($p->boomer_pops ?? 0);

        $candidates = [];

        // 正面亮點（好的事蹟）
        if ($deaths === 0 && $incaps === 0)
            $candidates[] = ['type'=>'zero_dmg',   'text'=>'本局全程零倒地零死亡，無懈可擊', 'good'=>true];
        if ($deaths === 0 && $incaps > 0)
            $candidates[] = ['type'=>'no_death',   'text'=>"倒地 {$incaps} 次卻從未真正死亡，不死之軀", 'good'=>true];
        if ($acc >= 50 && $shots >= 100)
            $candidates[] = ['type'=>'high_acc',   'text'=>sprintf('命中率 %.0f%%，每顆子彈都物盡其用', $acc), 'good'=>true];
        if ($hs_rate >= 60 && $headshots >= 15)
            $candidates[] = ['type'=>'hs_king',    'text'=>sprintf('爆頭率高達 %.0f%%，專攻弱點', $hs_rate), 'good'=>true];
        if ($revives >= 5)
            $candidates[] = ['type'=>'savior',     'text'=>"救援隊友 {$revives} 次，全場 MVP 非他莫屬", 'good'=>true];
        if ((int)$p->kills_tank >= 2)
            $candidates[] = ['type'=>'tank_kill',  'text'=>'協助擊殺 Tank ' . (int)$p->kills_tank . ' 次，坦克終結者', 'good'=>true];
        if ((int)$p->kills_witch >= 3)
            $candidates[] = ['type'=>'witch_kill', 'text'=>'獵殺 Witch ' . (int)$p->kills_witch . ' 次，勇者無畏', 'good'=>true];
        if ($damage >= 50000)
            $candidates[] = ['type'=>'big_dmg',    'text'=>'本局輸出 ' . number_format($damage) . ' 傷害，破壞力驚人', 'good'=>true];
        if ((int)$p->defibs_used >= 2)
            $candidates[] = ['type'=>'defib',      'text'=>'使用電擊器 ' . (int)$p->defibs_used . ' 次，起死回生大師', 'good'=>true];

        // 技巧正面亮點
        if ($skeets >= 3)
            $candidates[] = ['type'=>'skeet',      'text'=>"本局 Skeet 了 {$skeets} 次，Hunter 見到他就想回老家", 'good'=>true];
        if ($crowns >= 2)
            $candidates[] = ['type'=>'crown',      'text'=>"一槍爆頭女巫 {$crowns} 次，她現在估計有創傷後壓力症候群", 'good'=>true];
        if ($levels >= 3)
            $candidates[] = ['type'=>'level',      'text'=>"平壓 Charger {$levels} 次，衝刺對他來說毫無意義", 'good'=>true];
        if ($deadstops >= 3)
            $candidates[] = ['type'=>'deadstop',   'text'=>"推擋 Hunter 撲擊 {$deadstops} 次，反應速度快到令人懷疑外掛", 'good'=>true];
        if ($rock_skts >= 2)
            $candidates[] = ['type'=>'rock_skeet', 'text'=>"打落 Tank 石頭 {$rock_skts} 次，Tank 表示：投什麼都沒用", 'good'=>true];
        if ($tongue >= 5)
            $candidates[] = ['type'=>'tongue_cut', 'text'=>"切舌／自救 {$tongue} 次，Smoker 已放棄對他出招", 'good'=>true];
        if ($boomer >= 4)
            $candidates[] = ['type'=>'boomer_pop', 'text'=>"推爆殭屍炸彈 {$boomer} 次，Boomer 現在聞到他的腳步聲就膽戰心驚", 'good'=>true];

        // 技巧負面吐槽（技巧全部為零）
        if ($skeets === 0 && $crowns === 0 && $levels === 0 && $deadstops === 0 && $kills > 5)
            $candidates[] = ['type'=>'no_skill',   'text'=>'擊殺雖多，但整局零技巧，全靠亂槍打鳥', 'good'=>false];

        // 負面亮點（失誤）
        if ($ff >= 500)
            $candidates[] = ['type'=>'ff_blunder', 'text'=>'對隊友造成 ' . number_format($ff) . ' 點傷害，隊友已懷疑人生', 'good'=>false];
        if ($deaths >= 3)
            $candidates[] = ['type'=>'death_blunder','text'=>"本局死了 {$deaths} 次，隊友的救援手腕快磨出繭", 'good'=>false];
        if ($incaps >= 5)
            $candidates[] = ['type'=>'incap_blunder','text'=>"倒地 {$incaps} 次，是隊伍指定倒地員", 'good'=>false];
        if ($acc < 20 && $shots >= 200)
            $candidates[] = ['type'=>'bad_acc',    'text'=>sprintf('命中率僅 %.0f%%，建議換用霰彈槍', $acc), 'good'=>false];

        // Fallback
        $candidates[] = ['type'=>'generic', 'text'=>"{$kills} 擊殺 / " . number_format($damage) . ' 傷害', 'good'=>true];

        foreach ($candidates as $c) {
            if (!in_array($c['type'], $used)) return $c;
        }
        return $candidates[count($candidates) - 1];
    }

    /**
     * 產生一句關於這局最值得吐槽的幽默評語
     */
    public static function compute_funny_line(array $players): string {
        $ff_max = 0;    $ff_p    = null;
        $death_max = 0; $death_p = null;
        $incap_max = 0; $incap_p = null;
        $worst_acc = 100; $worst_acc_p = null;
        $top_kills = 0; $top_kills_p = null;
        $total_kills = 0;

        foreach ($players as $p) {
            $total_kills += (int)$p->total_kills;
            if ((int)$p->friendly_fire_damage > $ff_max)       { $ff_max    = (int)$p->friendly_fire_damage; $ff_p    = $p; }
            if ((int)$p->deaths > $death_max)                  { $death_max = (int)$p->deaths;               $death_p = $p; }
            if ((int)$p->incaps > $incap_max)                  { $incap_max = (int)$p->incaps;               $incap_p = $p; }
            if ((int)$p->shots_fired >= 200 && (float)$p->accuracy < $worst_acc) {
                $worst_acc = (float)$p->accuracy; $worst_acc_p = $p;
            }
            if ((int)$p->total_kills > $top_kills) { $top_kills = (int)$p->total_kills; $top_kills_p = $p; }
        }

        $lines = [];
        if ($ff_p && $ff_max >= 300)
            $lines[] = '🔥 ' . esc_html($ff_p->name) . ' 竟然對隊友打了 ' . number_format($ff_max) . ' 點傷害，下次記得分清楚誰是殭屍！';
        if ($death_p && $death_max >= 4)
            $lines[] = '💀 ' . esc_html($death_p->name) . " 這局死了 {$death_max} 次，隊友的救援手腕已磨出老繭。";
        if ($worst_acc_p && $worst_acc < 20)
            $lines[] = '🎯 ' . esc_html($worst_acc_p->name) . sprintf(' 命中率僅 %.0f%%，是在用槍給殭屍按摩嗎？', $worst_acc);
        if ($incap_p && $incap_max >= 7)
            $lines[] = '🏥 ' . esc_html($incap_p->name) . " 倒地了 {$incap_max} 次，隊友幾乎把扶人當做日常任務。";
        if ($top_kills_p && $total_kills > 0 && $top_kills >= $total_kills * 0.6)
            $lines[] = '🔫 ' . esc_html($top_kills_p->name) . ' 一人包辦了 ' . round($top_kills / $total_kills * 100) . '% 的擊殺，隊友紛紛轉職為啦啦隊。';

        if (!empty($lines)) return $lines[0];
        return '🧟 一場酣暢淋漓的戰鬥，感謝殭屍們的積極配合。';
    }

    /**
     * 單地圖場次詳細頁面 (原始行為)
     */
    private static function render_map_detail($session_id) {
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

        // 找出所屬戰役場次的 first_session_id (用於麵包屑)
        $campaign_run_id = null;
        if (!empty($session->campaign_name)) {
            $run = CampaignRun::find_run_for_session($session_id);
            if ($run) {
                $campaign_run_id = $run['first_session_id'];
            }
        }

        // 場次玩家數據
        $players = $db->query(
            "SELECT
                p.name, p.steam_id, p.steam_id_64,
                p.avatar_url, p.avatar_updated_at,
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
                     THEN LEAST(100, ROUND((sps.shots_hit + COALESCE(sps.kills_infected, 0)) / sps.shots_fired * 100, 1))
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

        // 批次抓取頭像
        $avatars = Plugin::fetch_avatars($players);

        ob_start();
        include L4D2_STATS_DIR . 'templates/session-detail.php';
        return ob_get_clean();
    }
}
