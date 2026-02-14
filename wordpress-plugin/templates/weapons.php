<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">
    <?php echo \L4D2Stats\Plugin::render_nav('l4d2_weapons'); ?>
    <h2 class="l4d2-title">武器統計</h2>

    <?php if (empty($weapons)): ?>
        <div class="l4d2-notice">尚無武器數據。</div>
    <?php else: ?>

        <!-- 武器擊殺排行圖表 -->
        <div class="l4d2-chart-container">
            <canvas id="l4d2-global-weapon-chart"
                    data-weapons='<?php echo esc_attr(json_encode([
                        'labels' => $chart_labels,
                        'kills' => $chart_kills,
                    ])); ?>'
                    height="300"></canvas>
        </div>

        <table id="l4d2-weapons-table" class="l4d2-table display">
            <thead>
                <tr>
                    <th>#</th>
                    <th>武器</th>
                    <th>類型</th>
                    <th>總擊殺</th>
                    <th>總爆頭</th>
                    <th>總傷害</th>
                    <th>平均命中率</th>
                    <th>使用人數</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($weapons as $i => $w): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo esc_html($w->display_name); ?></td>
                    <td>
                        <span class="l4d2-weapon-type l4d2-type-<?php echo esc_attr($w->weapon_type); ?>">
                            <?php echo esc_html($type_labels[$w->weapon_type] ?? $w->weapon_type); ?>
                        </span>
                    </td>
                    <td data-order="<?php echo (int)$w->total_kills; ?>">
                        <?php echo number_format((int)$w->total_kills); ?>
                    </td>
                    <td data-order="<?php echo (int)$w->total_headshots; ?>">
                        <?php echo number_format((int)$w->total_headshots); ?>
                    </td>
                    <td data-order="<?php echo (int)$w->total_damage; ?>">
                        <?php echo number_format((int)$w->total_damage); ?>
                    </td>
                    <td><?php echo $w->avg_accuracy; ?>%</td>
                    <td><?php echo (int)$w->users_count; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
