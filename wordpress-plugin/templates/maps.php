<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">
    <h2 class="l4d2-title">地圖統計</h2>

    <?php if (empty($campaigns)): ?>
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

        <table id="l4d2-maps-table" class="l4d2-table display">
            <thead>
                <tr>
                    <th>戰役</th>
                    <th>章節數</th>
                    <th>總遊玩次數</th>
                    <th>總通關次數</th>
                    <th>通關率</th>
                    <th>獨立玩家數</th>
                    <th>最後遊玩</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $c): ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg('campaign',
                            urlencode($c->campaign_name),
                            get_permalink()
                        )); ?>" class="l4d2-campaign-link">
                            <?php echo esc_html($c->campaign_name); ?>
                        </a>
                    </td>
                    <td><?php echo (int)$c->chapter_count; ?></td>
                    <td data-order="<?php echo (int)$c->total_plays; ?>">
                        <?php echo number_format((int)$c->total_plays); ?>
                    </td>
                    <td data-order="<?php echo (int)$c->total_completions; ?>">
                        <?php echo number_format((int)$c->total_completions); ?>
                    </td>
                    <td data-order="<?php echo $c->avg_completion_rate; ?>">
                        <div class="l4d2-progress-bar">
                            <div class="l4d2-progress-fill" style="width: <?php echo min(100, $c->avg_completion_rate); ?>%">
                                <?php echo $c->avg_completion_rate; ?>%
                            </div>
                        </div>
                    </td>
                    <td><?php echo (int)$c->unique_players; ?></td>
                    <td data-order="<?php echo $c->last_played ? strtotime($c->last_played) : 0; ?>">
                        <?php if ($c->last_played): ?>
                            <?php echo human_time_diff(strtotime($c->last_played)); ?> 前
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>
