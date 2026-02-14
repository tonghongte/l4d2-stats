<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">
    <?php echo \L4D2Stats\Plugin::render_nav('l4d2_leaderboard'); ?>
    <h2 class="l4d2-title">玩家排行榜</h2>

    <?php if (empty($players)): ?>
        <div class="l4d2-notice">尚無排行數據。</div>
    <?php else: ?>
        <table id="l4d2-leaderboard" class="l4d2-table display">
            <thead>
                <tr>
                    <th>#</th>
                    <th>玩家</th>
                    <th>總擊殺</th>
                    <th>特感擊殺</th>
                    <th>爆頭</th>
                    <th>死亡</th>
                    <th>K/D</th>
                    <th>命中率</th>
                    <th>救援</th>
                    <th>戰役通關</th>
                    <th>遊玩時間</th>
                    <th>最後上線</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $i => $p): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg([
                            'steam_id' => urlencode($p->steam_id),
                            'from'     => 'leaderboard',
                        ], get_permalink(get_page_by_path('player-stats')))); ?>" class="l4d2-player-link">
                            <?php echo \L4D2Stats\Plugin::render_avatar($avatars[$p->steam_id_64] ?? '', 'small'); ?>
                            <?php echo esc_html($p->name); ?>
                        </a>
                    </td>
                    <td data-order="<?php echo (int)$p->total_kills; ?>">
                        <?php echo number_format((int)$p->total_kills); ?>
                    </td>
                    <td data-order="<?php echo (int)$p->kills_si; ?>">
                        <?php echo number_format((int)$p->kills_si); ?>
                    </td>
                    <td data-order="<?php echo (int)$p->headshots; ?>">
                        <?php echo number_format((int)$p->headshots); ?>
                    </td>
                    <td data-order="<?php echo (int)$p->deaths; ?>">
                        <?php echo number_format((int)$p->deaths); ?>
                    </td>
                    <td><?php echo $p->kd_ratio; ?></td>
                    <td><?php echo $p->accuracy; ?>%</td>
                    <td data-order="<?php echo (int)$p->revives_given; ?>">
                        <?php echo number_format((int)$p->revives_given); ?>
                    </td>
                    <td data-order="<?php echo (int)$p->campaigns_completed; ?>">
                        <?php echo number_format((int)$p->campaigns_completed); ?>
                    </td>
                    <td data-order="<?php echo (int)$p->total_playtime; ?>">
                        <?php echo \L4D2Stats\Plugin::format_playtime($p->total_playtime); ?>
                    </td>
                    <td data-order="<?php echo strtotime($p->last_seen); ?>">
                        <?php echo human_time_diff(strtotime($p->last_seen)); ?> 前
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
