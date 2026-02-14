<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">
    <?php echo \L4D2Stats\Plugin::render_nav('l4d2_maps'); ?>

    <!-- 麵包屑 -->
    <div class="l4d2-breadcrumb">
        <?php
        $maps_url = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_maps');
        if ($maps_url): ?>
            <a href="<?php echo esc_url($maps_url); ?>">地圖統計</a>
        <?php else: ?>
            <span>地圖統計</span>
        <?php endif; ?>
        <span class="l4d2-breadcrumb-sep">&rsaquo;</span>
        <span><?php echo esc_html($campaign_stats->campaign_name); ?></span>
    </div>

    <!-- 戰役標題卡 -->
    <div class="l4d2-session-header">
        <div class="l4d2-session-info">
            <h3 class="l4d2-session-map-name">
                <?php echo esc_html($campaign_stats->campaign_name); ?>
            </h3>
            <div class="l4d2-player-meta">
                <span>章節: <?php echo (int)$campaign_stats->chapter_count; ?> 章</span>
                <span>總遊玩: <?php echo number_format((int)$campaign_stats->total_plays); ?> 次</span>
                <span>總通關: <?php echo number_format((int)$campaign_stats->total_completions); ?> 次</span>
            </div>
            <div class="l4d2-player-meta">
                <span>通關率:
                    <div class="l4d2-progress-bar" style="display: inline-block; width: 120px; vertical-align: middle;">
                        <div class="l4d2-progress-fill" style="width: <?php echo min(100, $campaign_stats->avg_completion_rate); ?>%">
                            <?php echo $campaign_stats->avg_completion_rate; ?>%
                        </div>
                    </div>
                </span>
                <span>獨立玩家: <?php echo (int)$campaign_stats->unique_players; ?></span>
            </div>
            <div class="l4d2-player-meta">
                <?php if ($campaign_stats->first_played): ?>
                    <span>首次遊玩: <?php echo date('Y-m-d', strtotime($campaign_stats->first_played)); ?></span>
                <?php endif; ?>
                <?php if ($campaign_stats->last_played): ?>
                    <span>最後遊玩: <?php echo human_time_diff(strtotime($campaign_stats->last_played)); ?> 前</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 章節遊玩次數圖表 -->
    <div class="l4d2-chart-container">
        <h4 class="l4d2-chart-title">各章節遊玩次數</h4>
        <canvas id="l4d2-campaign-chart"
                data-campaigns='<?php echo esc_attr(json_encode([
                    'labels' => $chart_labels,
                    'plays' => $chart_plays,
                ])); ?>'
                height="250"></canvas>
    </div>

    <!-- 章節地圖表格 -->
    <h3 class="l4d2-section-title">章節地圖</h3>
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
            <?php foreach ($maps as $m): ?>
            <tr>
                <td>
                    <?php echo esc_html($m->display_name ?: $m->map_name); ?>
                    <?php if ($m->is_finale): ?>
                        <span class="l4d2-badge l4d2-badge-gold">Finale</span>
                    <?php endif; ?>
                </td>
                <td data-order="<?php echo (int)$m->times_played; ?>">
                    <?php echo number_format((int)$m->times_played); ?>
                </td>
                <td data-order="<?php echo (int)$m->times_completed; ?>">
                    <?php echo number_format((int)$m->times_completed); ?>
                </td>
                <td data-order="<?php echo $m->completion_rate; ?>">
                    <div class="l4d2-progress-bar">
                        <div class="l4d2-progress-fill" style="width: <?php echo min(100, $m->completion_rate); ?>%">
                            <?php echo $m->completion_rate; ?>%
                        </div>
                    </div>
                </td>
                <td><?php echo (int)$m->unique_players; ?></td>
                <td data-order="<?php echo $m->last_played ? strtotime($m->last_played) : 0; ?>">
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

</div>
