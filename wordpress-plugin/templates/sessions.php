<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">
    <?php echo \L4D2Stats\Plugin::render_nav('l4d2_recent_sessions'); ?>
    <h2 class="l4d2-title">戰役場次記錄</h2>

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
                <?php foreach ($campaign_runs as $run): ?>
                <tr>
                    <td data-order="<?php echo strtotime($run['start_time']); ?>">
                        <a href="<?php echo esc_url(add_query_arg('session_id',
                            (int)$run['first_session_id'],
                            get_permalink(get_page_by_path('session-detail'))
                        )); ?>" class="l4d2-session-link">
                            <?php echo date('Y-m-d H:i', strtotime($run['start_time'])); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($run['campaign_name'] ?: '自訂地圖'); ?></td>
                    <td>
                        <?php
                        $chapter_names = [];
                        foreach ($run['sessions'] as $s) {
                            $chapter_names[] = $s->map_name;
                        }
                        ?>
                        <span title="<?php echo esc_attr(implode(' → ', $chapter_names)); ?>">
                            <?php echo (int)$run['map_count']; ?> 章
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
                        <?php if ($run['is_in_order']): ?>
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
