<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">
    <h2 class="l4d2-title">地圖統計</h2>

    <?php if (empty($maps)): ?>
        <div class="l4d2-notice">尚無地圖數據。</div>
    <?php else: ?>

        <!-- 戰役遊玩次數圖表 -->
        <div class="l4d2-chart-container">
            <canvas id="l4d2-campaign-chart"
                    data-campaigns='<?php echo esc_attr(json_encode([
                        'labels' => $chart_labels,
                        'plays' => $chart_plays,
                    ])); ?>'
                    height="300"></canvas>
        </div>

        <?php foreach ($campaigns as $campaign_name => $campaign_maps): ?>
        <h3 class="l4d2-section-title"><?php echo esc_html($campaign_name); ?></h3>
        <table class="l4d2-table sortable display">
            <thead>
                <tr>
                    <th>地圖</th>
                    <th>遊玩次數</th>
                    <th>通關次數</th>
                    <th>通關率</th>
                    <th>獨立玩家數</th>
                    <th>最後遊玩</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaign_maps as $m): ?>
                <tr>
                    <td>
                        <?php echo esc_html($m->display_name ?: $m->map_name); ?>
                        <?php if ($m->is_finale): ?>
                            <span class="l4d2-badge l4d2-badge-gold">Finale</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format((int)$m->times_played); ?></td>
                    <td><?php echo number_format((int)$m->times_completed); ?></td>
                    <td>
                        <div class="l4d2-progress-bar">
                            <div class="l4d2-progress-fill" style="width: <?php echo min(100, $m->completion_rate); ?>%">
                                <?php echo $m->completion_rate; ?>%
                            </div>
                        </div>
                    </td>
                    <td><?php echo (int)$m->unique_players; ?></td>
                    <td>
                        <?php if ($m->last_played): ?>
                            <?php echo human_time_diff(strtotime($m->last_played)); ?> 前
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>

    <?php endif; ?>
</div>
