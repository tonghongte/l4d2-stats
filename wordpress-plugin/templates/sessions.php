<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">
    <h2 class="l4d2-title">近期遊戲場次</h2>

    <?php if (empty($sessions)): ?>
        <div class="l4d2-notice">尚無場次記錄。</div>
    <?php else: ?>
        <table id="l4d2-sessions-table" class="l4d2-table display">
            <thead>
                <tr>
                    <th>時間</th>
                    <th>地圖</th>
                    <th>戰役</th>
                    <th>難度</th>
                    <th>時長</th>
                    <th>玩家數</th>
                    <th>玩家</th>
                    <th>結果</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                <tr>
                    <td data-order="<?php echo strtotime($s->start_time); ?>">
                        <a href="<?php echo esc_url(add_query_arg('session_id',
                            (int)$s->session_id,
                            get_permalink(get_page_by_path('session-detail'))
                        )); ?>" class="l4d2-session-link">
                            <?php echo date('Y-m-d H:i', strtotime($s->start_time)); ?>
                        </a>
                    </td>
                    <td>
                        <?php echo esc_html($s->map_name); ?>
                        <?php if ($s->is_finale): ?>
                            <span class="l4d2-badge l4d2-badge-gold">F</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($s->campaign_name); ?></td>
                    <td>
                        <span class="l4d2-difficulty l4d2-diff-<?php echo esc_attr($s->difficulty); ?>">
                            <?php echo esc_html($difficulty_labels[$s->difficulty] ?? ucfirst($s->difficulty)); ?>
                        </span>
                    </td>
                    <td data-order="<?php echo (int)$s->duration; ?>">
                        <?php echo \L4D2Stats\Plugin::format_playtime($s->duration); ?>
                    </td>
                    <td><?php echo (int)$s->survivor_count; ?></td>
                    <td class="l4d2-session-players">
                        <?php echo esc_html($s->player_names ?: '-'); ?>
                    </td>
                    <td>
                        <?php if ($s->campaign_completed): ?>
                            <span class="l4d2-badge l4d2-badge-gold">戰役通關</span>
                        <?php elseif ($s->completed): ?>
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
