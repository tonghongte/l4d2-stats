<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">

    <!-- 麵包屑 -->
    <div class="l4d2-breadcrumb">
        <?php
        $from = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : '';
        $from_sid = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        switch ($from) {
            case 'search':
                $search_url = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_player_search');
                if ($search_url): ?>
                    <a href="<?php echo esc_url($search_url); ?>">玩家搜尋</a>
                <?php else: ?>
                    <span>玩家搜尋</span>
                <?php endif;
                break;
            case 'session':
                $sessions_url = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_recent_sessions');
                if ($sessions_url): ?>
                    <a href="<?php echo esc_url($sessions_url); ?>">場次列表</a>
                <?php endif;
                if ($from_sid > 0):
                    $detail_url = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_session_detail'); ?>
                    <span class="l4d2-breadcrumb-sep">&rsaquo;</span>
                    <a href="<?php echo esc_url(add_query_arg('session_id', $from_sid, $detail_url)); ?>">場次詳情</a>
                <?php endif;
                break;
            default: // leaderboard or no from
                $lb_url = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_leaderboard');
                if ($lb_url): ?>
                    <a href="<?php echo esc_url($lb_url); ?>">排行榜</a>
                <?php else: ?>
                    <span>排行榜</span>
                <?php endif;
                break;
        } ?>
        <span class="l4d2-breadcrumb-sep">&rsaquo;</span>
        <span><?php echo esc_html($player->name); ?></span>
    </div>

    <!-- 玩家基本資料 -->
    <div class="l4d2-player-header">
        <?php echo \L4D2Stats\Plugin::render_avatar($avatar_url, 'large'); ?>
        <div class="l4d2-player-info">
            <h2 class="l4d2-player-name"><?php echo esc_html($player->name); ?></h2>
            <div class="l4d2-player-meta">
                <span class="l4d2-badge">排名 #<?php echo (int)$rank; ?> / <?php echo (int)$total_players; ?></span>
                <span class="l4d2-meta-item">Steam ID: <code><?php echo esc_html($player->steam_id); ?></code></span>
                <?php if (!empty($player->steam_id_64)): ?>
                    <a href="https://steamcommunity.com/profiles/<?php echo esc_attr($player->steam_id_64); ?>"
                       target="_blank" rel="noopener" class="l4d2-steam-link">Steam 個人頁面</a>
                <?php endif; ?>
            </div>
            <div class="l4d2-player-meta">
                <span>首次上線: <?php echo wp_date('Y-m-d', \L4D2Stats\Plugin::mysql_utc($player->first_seen)); ?></span>
                <span>最後上線: <?php echo human_time_diff(\L4D2Stats\Plugin::mysql_utc($player->last_seen)); ?> 前</span>
                <span>總遊玩時間: <?php echo \L4D2Stats\Plugin::format_playtime($player->total_playtime); ?></span>
            </div>
        </div>
    </div>

    <!-- 數據卡片 -->
    <div class="l4d2-stat-grid">
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format($total_kills); ?></div>
            <div class="l4d2-stat-label">總擊殺</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->kills_si); ?></div>
            <div class="l4d2-stat-label">特感擊殺</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->kills_tank); ?></div>
            <div class="l4d2-stat-label">Tank 擊殺</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->kills_witch); ?></div>
            <div class="l4d2-stat-label">Witch 擊殺</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo $kd_ratio; ?></div>
            <div class="l4d2-stat-label">K/D 比</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo $accuracy; ?>%</div>
            <div class="l4d2-stat-label">特感命中率</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo $hs_rate; ?>%</div>
            <div class="l4d2-stat-label">爆頭率</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->headshots); ?></div>
            <div class="l4d2-stat-label">爆頭數</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->deaths); ?></div>
            <div class="l4d2-stat-label">死亡</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->incaps); ?></div>
            <div class="l4d2-stat-label">倒地</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->revives_given); ?></div>
            <div class="l4d2-stat-label">救援次數</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->heals_given); ?></div>
            <div class="l4d2-stat-label">治療次數</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->friendly_fire_dealt); ?></div>
            <div class="l4d2-stat-label">友軍傷害</div>
        </div>
        <div class="l4d2-stat-card">
            <div class="l4d2-stat-value"><?php echo number_format((int)$player->campaigns_completed); ?></div>
            <div class="l4d2-stat-label">戰役通關</div>
        </div>
    </div>

    <!-- 個人成就 -->
    <?php if (!empty($achievements)): ?>
    <h3 class="l4d2-section-title">個人成就</h3>
    <div class="l4d2-achievements-grid">
        <?php
        $tier_labels = ['gold' => '金', 'silver' => '銀', 'bronze' => '銅', 'special' => '特殊'];
        foreach ($achievements as $ach): ?>
        <div class="l4d2-achievement-card l4d2-ach-<?php echo esc_attr($ach['tier']); ?>"
             title="<?php echo esc_attr($ach['desc']); ?>">
            <div class="l4d2-ach-tier-badge"><?php echo esc_html($tier_labels[$ach['tier']] ?? ''); ?></div>
            <div class="l4d2-ach-name"><?php echo esc_html($ach['name']); ?></div>
            <div class="l4d2-ach-desc"><?php echo esc_html($ach['desc']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 武器統計 -->
    <?php if (!empty($weapons)): ?>
    <h3 class="l4d2-section-title">常用武器</h3>

    <div class="l4d2-chart-container">
        <canvas id="l4d2-weapon-chart"
                data-weapons='<?php echo esc_attr(json_encode([
                    'labels' => $weapon_chart_labels,
                    'kills' => $weapon_chart_kills,
                ])); ?>'
                height="300"></canvas>
    </div>

    <table class="l4d2-table sortable display">
        <thead>
            <tr>
                <th>武器</th>
                <th>類型</th>
                <th>擊殺</th>
                <th>爆頭</th>
                <th>傷害</th>
                <th>射擊</th>
                <th>命中</th>
                <th>特感命中率</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($weapons as $w): ?>
            <tr>
                <td><?php echo esc_html($w->display_name); ?></td>
                <td><?php echo esc_html($w->weapon_type); ?></td>
                <td data-order="<?php echo (int)$w->kills; ?>">
                    <?php echo number_format((int)$w->kills); ?>
                </td>
                <td data-order="<?php echo (int)$w->headshots; ?>">
                    <?php echo number_format((int)$w->headshots); ?>
                </td>
                <td data-order="<?php echo (int)$w->damage_dealt; ?>">
                    <?php echo number_format((int)$w->damage_dealt); ?>
                </td>
                <td data-order="<?php echo (int)$w->shots_fired; ?>">
                    <?php echo number_format((int)$w->shots_fired); ?>
                </td>
                <td data-order="<?php echo (int)$w->shots_hit; ?>">
                    <?php echo number_format((int)$w->shots_hit); ?>
                </td>
                <td><?php echo $w->accuracy; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- 地圖記錄 -->
    <?php if (!empty($maps)): ?>
    <h3 class="l4d2-section-title">地圖記錄</h3>
    <table class="l4d2-table sortable display">
        <thead>
            <tr>
                <th>地圖</th>
                <th>戰役</th>
                <th>遊玩次數</th>
                <th>通關次數</th>
                <th>擊殺</th>
                <th>死亡</th>
                <th>遊玩時間</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($maps as $m): ?>
            <tr>
                <td><?php echo esc_html($m->display_name ?: $m->map_name); ?></td>
                <td><?php echo esc_html($m->campaign_name); ?></td>
                <td><?php echo (int)$m->times_played; ?></td>
                <td><?php echo (int)$m->times_completed; ?></td>
                <td data-order="<?php echo (int)$m->kills; ?>">
                    <?php echo number_format((int)$m->kills); ?>
                </td>
                <td><?php echo (int)$m->deaths; ?></td>
                <td data-order="<?php echo (int)$m->playtime; ?>">
                    <?php echo \L4D2Stats\Plugin::format_playtime($m->playtime); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- 近期場次 -->
    <?php if (!empty($sessions)): ?>
    <h3 class="l4d2-section-title">近期場次</h3>
    <table class="l4d2-table display">
        <thead>
            <tr>
                <th>時間</th>
                <th>地圖</th>
                <th>戰役</th>
                <th>難度</th>
                <th>時長</th>
                <th>結果</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sessions as $s): ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url(add_query_arg([
                        'session_id' => (int)$s->session_id,
                        'from'       => 'player',
                        'pid'        => urlencode($player->steam_id),
                        'pname'      => urlencode($player->name),
                    ], get_permalink(get_page_by_path('session-detail')))); ?>" class="l4d2-session-link">
                        <?php echo wp_date('Y-m-d H:i', \L4D2Stats\Plugin::mysql_utc($s->start_time)); ?>
                    </a>
                </td>
                <td><?php echo esc_html($s->map_name); ?></td>
                <td><?php echo esc_html($s->campaign_name); ?></td>
                <td><?php echo esc_html(ucfirst($s->difficulty)); ?></td>
                <td data-order="<?php echo (int)$s->player_duration; ?>">
                    <?php echo \L4D2Stats\Plugin::format_playtime($s->player_duration ?: $s->duration); ?>
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
