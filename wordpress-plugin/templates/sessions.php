<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">
    <?php echo \L4D2Stats\Plugin::render_nav('l4d2_recent_sessions'); ?>
    <h2 class="l4d2-title">戰役場次記錄</h2>

    <?php
    $filter_options = [
        '7d'  => '近 7 天',
        '30d' => '近 30 天',
        '3m'  => '近 3 個月',
        '6m'  => '近 6 個月',
        'all' => '全部',
    ];
    $base_url = strtok($_SERVER['REQUEST_URI'], '?');
    ?>
    <div class="l4d2-filter-bar">
        <?php foreach ($filter_options as $key => $label): ?>
            <a href="<?php echo esc_url($base_url . '?session_filter=' . $key); ?>"
               class="l4d2-filter-btn<?php echo ($current_filter === $key) ? ' l4d2-filter-btn-active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($campaign_runs)): ?>
        <div class="l4d2-notice">尚無場次記錄。</div>
    <?php else: ?>
        <table id="l4d2-sessions-table" class="l4d2-table display">
            <thead>
                <tr>
                    <th>時間</th>
                    <th>戰役</th>
                    <th>章節</th>
                    <th>難度</th>
                    <th>時長</th>
                    <th>玩家數</th>
                    <th>玩家</th>
                    <th>結果</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaign_runs as $run):
                    $is_active = !empty($run['is_active']);
                    // 進行中的場次排序值用極大值推至頂端
                    $time_order = $is_active ? PHP_INT_MAX : \L4D2Stats\Plugin::mysql_utc($run['start_time']);
                ?>
                <tr<?php echo $is_active ? ' class="l4d2-row-active"' : ''; ?>>
                    <td data-order="<?php echo $time_order; ?>">
                        <a href="<?php echo esc_url(add_query_arg('session_id',
                            (int)$run['first_session_id'],
                            get_permalink(get_page_by_path('session-detail'))
                        )); ?>" class="l4d2-session-link">
                            <?php echo wp_date('Y-m-d H:i', \L4D2Stats\Plugin::mysql_utc($run['start_time'])); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($run['campaign_name'] ?: '自訂地圖'); ?></td>
                    <td>
                        <?php
                        $chapter_names = [];
                        foreach ($run['sessions'] as $s) {
                            $chapter_names[] = $s->map_name ?: ($s->map_code ?: '未知地圖');
                        }
                        ?>
                        <span title="<?php echo esc_attr(implode(' → ', $chapter_names)); ?>">
                            <?php echo (int)$run['map_count']; ?> 章
                            <?php if ($is_active && $run['current_map']): ?>
                                <span class="l4d2-current-map">(<?php echo esc_html($run['current_map']); ?>)</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <span class="l4d2-difficulty l4d2-diff-<?php echo esc_attr($run['difficulty']); ?>">
                            <?php echo esc_html($difficulty_labels[$run['difficulty']] ?? ucfirst($run['difficulty'])); ?>
                        </span>
                    </td>
                    <td data-order="<?php echo (int)$run['total_duration']; ?>">
                        <?php echo \L4D2Stats\Plugin::format_playtime($run['total_duration']); ?>
                    </td>
                    <td><?php echo (int)$run['survivor_count']; ?></td>
                    <td class="l4d2-session-players">
                        <?php echo esc_html($run['player_names'] ?: '-'); ?>
                    </td>
                    <td>
                        <?php if ($is_active): ?>
                            <span class="l4d2-badge l4d2-badge-active">
                                <span class="l4d2-pulse"></span>遊玩中
                            </span>
                        <?php elseif ($run['is_in_order']): ?>
                            <span class="l4d2-badge l4d2-badge-gold">依序通關</span>
                        <?php elseif ($run['is_completed']): ?>
                            <span class="l4d2-badge l4d2-badge-success">通關</span>
                        <?php else: ?>
                            <span class="l4d2-badge l4d2-badge-fail">未通關</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
