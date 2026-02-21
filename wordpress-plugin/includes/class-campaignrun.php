<?php
namespace L4D2Stats;

/**
 * 戰役場次分組引擎
 * 將連續遊玩的章節地圖 session 分組為戰役場次 (campaign run)
 */
class CampaignRun {

    /**
     * 連續 session 之間的最大時間差 (秒)
     * 超過此值視為不同戰役場次
     */
    const GAP_THRESHOLD = 300;

    /**
     * 從 map_name 提取章節編號
     * e.g. "c1m2_streets" => 2, "c10m3_ranchhouse" => 3
     *
     * @return int|null
     */
    public static function extract_chapter(string $map_name): ?int {
        if (preg_match('/^c\d+m(\d+)/', $map_name, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    /**
     * 將 session 陣列分組為戰役場次
     *
     * @param array $sessions 按 start_time ASC 排序的 session 物件陣列
     *   每筆需包含: session_id, map_code, campaign_name, start_time, end_time,
     *   difficulty, completed, campaign_completed, is_finale, player_names,
     *   survivor_count, duration
     *
     * @return array 戰役場次陣列 (最新在前)，每筆包含:
     *   first_session_id, campaign_name, difficulty, start_time, end_time,
     *   total_duration, sessions, map_count, player_names, survivor_count,
     *   is_completed, is_in_order
     */
    public static function group_sessions(array $sessions): array {
        if (empty($sessions)) return [];

        $groups = [];
        $current = null;

        foreach ($sessions as $s) {
            $can_join = false;

            if ($current !== null
                && !empty($s->campaign_name)
                && $s->campaign_name === $current['campaign_name']
                && $current['last_end_time'] !== null
                && !$current['_campaign_completed']  // 戰役已通關，不再接受新 session
            ) {
                $gap = strtotime($s->start_time) - strtotime($current['last_end_time']);
                if ($gap >= 0 && $gap <= self::GAP_THRESHOLD) {
                    $can_join = true;
                }
            }

            if ($can_join) {
                $current['sessions'][] = $s;
                $current['last_end_time'] = $s->end_time ?: $s->start_time;
                $current['total_duration'] += (int)$s->duration;
                $current['survivor_count'] = max($current['survivor_count'], (int)$s->survivor_count);
                // 若此 session 觸發戰役通關，標記群組為已完成
                if ((int)$s->campaign_completed === 1) {
                    $current['_campaign_completed'] = true;
                }
                // 合併玩家名稱
                if (!empty($s->player_names)) {
                    foreach (explode(', ', $s->player_names) as $name) {
                        $name = trim($name);
                        if ($name !== '') {
                            $current['_player_set'][$name] = true;
                        }
                    }
                }
            } else {
                // 關閉當前 group
                if ($current !== null) {
                    $groups[] = self::finalize_group($current);
                }
                // 開新 group
                $player_set = [];
                if (!empty($s->player_names)) {
                    foreach (explode(', ', $s->player_names) as $name) {
                        $name = trim($name);
                        if ($name !== '') {
                            $player_set[$name] = true;
                        }
                    }
                }
                $current = [
                    'first_session_id'   => (int)$s->session_id,
                    'campaign_name'      => $s->campaign_name ?? '',
                    'difficulty'         => $s->difficulty,
                    'start_time'         => $s->start_time,
                    'last_end_time'      => $s->end_time ?: $s->start_time,
                    'total_duration'     => (int)$s->duration,
                    'sessions'           => [$s],
                    'survivor_count'     => (int)$s->survivor_count,
                    '_player_set'        => $player_set,
                    '_campaign_completed' => (int)$s->campaign_completed === 1,
                ];
            }
        }

        // 關閉最後一個 group
        if ($current !== null) {
            $groups[] = self::finalize_group($current);
        }

        // 最新在前
        return array_reverse($groups);
    }

    /**
     * 結束一個 group，計算衍生欄位
     */
    private static function finalize_group(array $group): array {
        $sessions = $group['sessions'];
        $last = end($sessions);

        $is_completed = false;
        foreach ($sessions as $s) {
            if ((int)$s->campaign_completed === 1) {
                $is_completed = true;
                break;
            }
        }

        $player_names = array_keys($group['_player_set']);
        sort($player_names);

        return [
            'first_session_id' => $group['first_session_id'],
            'campaign_name'    => $group['campaign_name'],
            'difficulty'       => $group['difficulty'],
            'start_time'       => $group['start_time'],
            'end_time'         => $last->end_time ?: $last->start_time,
            'total_duration'   => $group['total_duration'],
            'sessions'         => $sessions,
            'map_count'        => count($sessions),
            'player_names'     => implode(', ', $player_names),
            'survivor_count'   => $group['survivor_count'],
            'is_completed'     => $is_completed,
            'is_in_order'      => self::check_in_order($sessions),
        ];
    }

    /**
     * 檢查是否「依序通關」
     * 條件: 從第 1 章開始、每章嚴格遞增、同一難度、全部通關、最後是 finale
     *
     * @param array $sessions 同一戰役 run 內的 session 物件陣列 (已按時間排序)
     * @return bool
     */
    public static function check_in_order(array $sessions): bool {
        if (count($sessions) < 2) {
            return false;
        }

        $first_difficulty = $sessions[0]->difficulty;
        $prev_chapter = null;

        foreach ($sessions as $s) {
            // 提取章節編號
            $chapter = self::extract_chapter($s->map_code);
            if ($chapter === null) {
                return false;
            }

            // 第一章必須是 1
            if ($prev_chapter === null && $chapter !== 1) {
                return false;
            }

            // 每章嚴格遞增 (+1)
            if ($prev_chapter !== null && $chapter !== $prev_chapter + 1) {
                return false;
            }

            // 同一難度
            if ($s->difficulty !== $first_difficulty) {
                return false;
            }

            // 必須通關
            if ((int)$s->completed === 0 && (int)$s->campaign_completed === 0) {
                return false;
            }

            $prev_chapter = $chapter;
        }

        // 最後一關必須是 finale
        $last = end($sessions);
        if ((int)$last->is_finale !== 1) {
            return false;
        }

        return true;
    }

    /**
     * 從一個 session_id 找出所屬戰役場次的 first_session_id
     *
     * @param int $session_id
     * @return int|null 所屬戰役場次的 first_session_id，找不到時返回 null
     */
    public static function find_run_for_session(int $session_id): ?array {
        $db = Database::instance();

        // 查詢錨點 session
        $anchor = $db->get_row(
            "SELECT s.id AS session_id, s.start_time, s.end_time, s.duration,
                    s.difficulty, s.completed, s.campaign_completed, s.survivor_count,
                    m.map_name AS map_code, m.display_name AS map_name,
                    m.campaign_name, m.is_finale
             FROM l4d2_sessions s
             JOIN l4d2_maps m ON m.id = s.map_id
             WHERE s.id = %d",
            [$session_id]
        );

        if (!$anchor || empty($anchor->campaign_name)) {
            return null;
        }

        // 查詢前後時間窗口內同一戰役的所有 session
        $time_before = date('Y-m-d H:i:s', strtotime($anchor->start_time) - 7200);
        $time_after = date('Y-m-d H:i:s', strtotime($anchor->start_time) + 21600);

        $nearby = $db->query(
            "SELECT
                s.id AS session_id, s.start_time, s.end_time, s.duration,
                s.difficulty, s.completed, s.campaign_completed, s.survivor_count,
                m.map_name AS map_code, m.display_name AS map_name,
                m.campaign_name, m.is_finale,
                GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS player_names
             FROM l4d2_sessions s
             JOIN l4d2_maps m ON m.id = s.map_id
             LEFT JOIN l4d2_session_players sp ON sp.session_id = s.id
             LEFT JOIN l4d2_players p ON p.id = sp.player_id
             WHERE m.campaign_name = %s
               AND s.start_time BETWEEN %s AND %s
             GROUP BY s.id
             ORDER BY s.start_time ASC",
            [$anchor->campaign_name, $time_before, $time_after]
        );

        if (empty($nearby)) {
            return null;
        }

        $all_runs = self::group_sessions($nearby);

        // 找到包含此 session_id 的 run
        foreach ($all_runs as $run) {
            foreach ($run['sessions'] as $s) {
                if ((int)$s->session_id === $session_id) {
                    return $run;
                }
            }
        }

        return null;
    }
}
