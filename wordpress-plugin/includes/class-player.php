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

        // 抓取頭像
        $avatars = Plugin::fetch_avatars([$player]);
        $avatar_url = $avatars[$player->steam_id_64] ?? Plugin::DEFAULT_AVATAR;

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
            "SELECT s.id AS session_id,
                    s.start_time, s.end_time, s.duration, s.completed,
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

        // 計算個人成就
        $achievements = self::compute_player_achievements($player, $maps, $weapons);

        ob_start();
        include L4D2_STATS_DIR . 'templates/player-profile.php';
        return ob_get_clean();
    }

    /**
     * 計算玩家個人成就陣列
     * 每項成就包含: id, name, desc, tier (gold/silver/bronze/special)
     */
    private static function compute_player_achievements($player, array $maps, array $weapons = []): array {
        $achievements = [];
        $total_kills  = (int)$player->kills_infected + (int)$player->kills_si;
        $map_count    = count($maps);
        $pt           = (int)$player->total_playtime;
        $fired        = (int)$player->shots_fired;
        $acc          = $fired > 0 ? round((int)$player->shots_hit / $fired * 100, 1) : 0;

        // 階梯式成就：[id, 顯示名稱, 說明前綴, [金門檻, 銀門檻, 銅門檻], 實際數值]
        $tiered = [
            ['kill_machine',    '殺戮機器', '總擊殺',       [10000, 5000, 1000],      $total_kills],
            ['hs_king',         '爆頭王',   '爆頭數',       [3000, 1000, 300],        (int)$player->headshots],
            ['si_hunter',       '特感獵人', '特感擊殺',     [500, 200, 50],           (int)$player->kills_si],
            ['tank_slayer',     '坦克剋星', 'Tank 擊殺',    [100, 50, 10],            (int)$player->kills_tank],
            ['witch_slayer',    '女巫剋星', 'Witch 擊殺',   [50, 20, 5],              (int)$player->kills_witch],
            ['rescue_hero',     '救援英雄', '救援次數',     [200, 100, 30],           (int)$player->revives_given],
            ['medic',           '醫療兵',   '治療次數',     [100, 50, 20],            (int)$player->heals_given],
            ['campaign_master', '戰役達人', '戰役通關',     [50, 20, 5],              (int)$player->campaigns_completed],
            ['melee_master',    '格鬥家',   '近身命中',     [500, 200, 50],           (int)$player->melee_hits],
            ['defibrillator',   '電擊重生', '電擊器使用',   [50, 20, 5],              (int)$player->defibs_used],
            ['explorer',        '地圖探索家','遊玩地圖種類', [50, 30, 10],             $map_count],
            ['damage_dealer',   '傷害製造者','對感染者總傷害',[2000000, 500000, 100000], (int)$player->damage_dealt],
            ['iron_man',        '打不死',   '承受總傷害',   [100000, 50000, 10000],   (int)$player->damage_taken],
            ['incap_pro',       '倒地常客', '倒地次數',     [200, 100, 30],           (int)$player->incaps],
            ['pill_junkie',     '藥罐子',   '止痛藥使用',   [200, 100, 30],           (int)$player->pills_used],
            ['map_conqueror',   '地圖征服者','地圖通關次數', [200, 100, 30],           (int)$player->maps_completed],
            ['rescued',         '被救達人', '被救次數',     [100, 50, 20],            (int)$player->revives_received],
            // 技巧類成就（來自 skill_detect）
            ['skeet',       '空中截擊', 'Skeet 次數',     [50, 20, 5],  (int)($player->skeets      ?? 0)],
            ['crown',       '女王殺手', 'Crown 次數',     [30, 10, 3],  (int)($player->crowns      ?? 0)],
            ['level',       '地平壓制', 'Level 次數',     [50, 20, 5],  (int)($player->levels      ?? 0)],
            ['deadstop',    '反應之神', 'Deadstop 次數',  [50, 20, 5],  (int)($player->deadstops   ?? 0)],
            ['rock_skeet',  '岩石粉碎', 'Rock Skeet 次數',[20, 10, 3],  (int)($player->rock_skeets ?? 0)],
            ['tongue_cut',  '切舌俠',   '切舌/自救次數',  [100, 50, 15],(int)($player->tongue_cuts ?? 0)],
            ['boomer_pop',  '炸彈拆除', 'Boomer Pop 次數',[50, 20, 5],  (int)($player->boomer_pops ?? 0)],
        ];

        foreach ($tiered as [$id, $name, $desc_base, [$gold, $silver, $bronze], $val]) {
            if ($val >= $gold)
                $achievements[] = ['id' => $id, 'name' => $name, 'desc' => "{$desc_base}: {$val}", 'tier' => 'gold'];
            elseif ($val >= $silver)
                $achievements[] = ['id' => $id, 'name' => $name, 'desc' => "{$desc_base}: {$val}", 'tier' => 'silver'];
            elseif ($val >= $bronze)
                $achievements[] = ['id' => $id, 'name' => $name, 'desc' => "{$desc_base}: {$val}", 'tier' => 'bronze'];
        }

        // 老手（遊玩時間）
        if ($pt >= 72000)
            $achievements[] = ['id' => 'veteran', 'name' => '老手', 'desc' => '遊玩時間: ' . Plugin::format_playtime($pt), 'tier' => 'gold'];
        elseif ($pt >= 36000)
            $achievements[] = ['id' => 'veteran', 'name' => '老手', 'desc' => '遊玩時間: ' . Plugin::format_playtime($pt), 'tier' => 'silver'];
        elseif ($pt >= 18000)
            $achievements[] = ['id' => 'veteran', 'name' => '老手', 'desc' => '遊玩時間: ' . Plugin::format_playtime($pt), 'tier' => 'bronze'];

        // 神槍手（命中率，需 shots_fired >= 500）
        if ($fired >= 500) {
            if ($acc >= 50)
                $achievements[] = ['id' => 'dead_eye', 'name' => '神槍手', 'desc' => "命中率: {$acc}%", 'tier' => 'gold'];
            elseif ($acc >= 40)
                $achievements[] = ['id' => 'dead_eye', 'name' => '神槍手', 'desc' => "命中率: {$acc}%", 'tier' => 'silver'];
            elseif ($acc >= 30)
                $achievements[] = ['id' => 'dead_eye', 'name' => '神槍手', 'desc' => "命中率: {$acc}%", 'tier' => 'bronze'];
        }

        // 特殊成就（一次性解鎖，無階級）
        // 友善隊友：超過5小時但從未造成友傷
        if ((int)$player->friendly_fire_dealt === 0 && $pt >= 18000)
            $achievements[] = ['id' => 'team_player', 'name' => '友善隊友', 'desc' => '遊玩超過5小時且從未造成友傷', 'tier' => 'special'];

        // 彈藥庫：射擊超過10萬發
        if ($fired >= 100000)
            $achievements[] = ['id' => 'bullet_hose', 'name' => '彈藥庫', 'desc' => '累計射擊 ' . number_format($fired) . ' 發子彈', 'tier' => 'special'];

        // 不死鳥：從未死亡且擊殺數 >= 100
        if ((int)$player->deaths === 0 && $total_kills >= 100)
            $achievements[] = ['id' => 'immortal', 'name' => '不死鳥', 'desc' => '從未死亡 (擊殺 ≥ 100)', 'tier' => 'special'];

        // 急救大師：治療量超過10000
        if ((int)$player->health_restored >= 10000)
            $achievements[] = ['id' => 'heal_master', 'name' => '急救大師', 'desc' => '累計恢復血量 ' . number_format((int)$player->health_restored), 'tier' => 'special'];

        // 鋼鐵意志：倒地 >= 20 次但從未真正死亡
        if ((int)$player->incaps >= 20 && (int)$player->deaths === 0)
            $achievements[] = ['id' => 'iron_will', 'name' => '鋼鐵意志', 'desc' => '倒地 ' . (int)$player->incaps . ' 次卻從未真正死亡', 'tier' => 'special'];

        // 吃藥上癮：止痛藥 + 腎上腺素合計使用 >= 150
        $total_stims = (int)$player->pills_used + (int)$player->adrenaline_used;
        if ($total_stims >= 150)
            $achievements[] = ['id' => 'drug_addict', 'name' => '吃藥上癮', 'desc' => '止痛藥+腎上腺素合計使用 ' . $total_stims . ' 次', 'tier' => 'special'];

        // 人形坦克：承受傷害 >= 200000
        if ((int)$player->damage_taken >= 200000)
            $achievements[] = ['id' => 'damage_sponge', 'name' => '人形坦克', 'desc' => '累計承受傷害 ' . number_format((int)$player->damage_taken), 'tier' => 'special'];

        // 武器收藏家：使用過 6 種以上不同類型武器
        if (!empty($weapons)) {
            $weapon_types = array_unique(array_column($weapons, 'weapon_type'));
            $type_count = count($weapon_types);
            if ($type_count >= 6)
                $achievements[] = ['id' => 'weapon_collector', 'name' => '武器收藏家', 'desc' => '使用過 ' . $type_count . ' 種不同類型武器', 'tier' => 'special'];
        }

        // 百發百中：射擊 >= 1000 發且命中率 >= 60%
        if ($fired >= 1000 && $acc >= 60)
            $achievements[] = ['id' => 'dead_shot', 'name' => '百發百中', 'desc' => "射擊 {$fired} 發，命中率 {$acc}%", 'tier' => 'special'];

        // 戰地護士：救援 + 治療合計 >= 500 且從未友傷
        $support_total = (int)$player->revives_given + (int)$player->heals_given;
        if ($support_total >= 500 && (int)$player->friendly_fire_dealt === 0)
            $achievements[] = ['id' => 'field_medic', 'name' => '戰地護士', 'desc' => '救援+治療合計 ' . $support_total . ' 次且從未友傷', 'tier' => 'special'];

        return $achievements;
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
     * REST API 玩家搜尋
     */
    public static function rest_search(\WP_REST_Request $request) {
        $search = $request->get_param('q');
        if (strlen($search) < 2) {
            return new \WP_Error('too_short', '搜尋字串太短', ['status' => 400]);
        }

        $db = Database::instance();
        $results = $db->query(
            "SELECT name, steam_id, steam_id_64, last_seen, total_playtime, avatar_url
             FROM l4d2_players
             WHERE name LIKE %s OR steam_id LIKE %s
             ORDER BY last_seen DESC
             LIMIT 20",
            ['%' . $db->get_db()->esc_like($search) . '%',
             '%' . $db->get_db()->esc_like($search) . '%']
        );

        return rest_ensure_response($results);
    }
}
