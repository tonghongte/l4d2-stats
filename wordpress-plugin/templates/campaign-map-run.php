<?php if (!defined('ABSPATH')) exit; ?>
<div class="l4d2-stats-container">

    <!-- 麵包屑 -->
    <div class="l4d2-breadcrumb">
        <?php
        $bc_from  = isset($_GET['from'])  ? sanitize_text_field($_GET['from'])  : '';
        $bc_pid   = isset($_GET['pid'])   ? sanitize_text_field($_GET['pid'])   : '';
        $bc_pname = isset($_GET['pname']) ? sanitize_text_field($_GET['pname']) : '';

        if ($bc_from === 'player' && !empty($bc_pid)):
            $player_url = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_player_stats'); ?>
            <a href="<?php echo esc_url(add_query_arg('steam_id', urlencode($bc_pid), $player_url)); ?>"><?php echo esc_html($bc_pname ?: $bc_pid); ?></a>
        <?php else:
            $sessions_url = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_recent_sessions');
            if ($sessions_url): ?>
                <a href="<?php echo esc_url($sessions_url); ?>">場次列表</a>
            <?php else: ?>
                <span>場次列表</span>
            <?php endif;
        endif; ?>
        <span class="l4d2-breadcrumb-sep">&rsaquo;</span>
        <?php
        $campaign_run_url = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_session_detail');
        if ($campaign_run_url): ?>
            <a href="<?php echo esc_url(add_query_arg('session_id', (int)$run['first_session_id'], $campaign_run_url)); ?>">
                <?php echo esc_html($run['campaign_name']); ?>
            </a>
        <?php else: ?>
            <span><?php echo esc_html($run['campaign_name']); ?></span>
        <?php endif; ?>
        <span class="l4d2-breadcrumb-sep">&rsaquo;</span>
        <span><?php echo esc_html($map_info->map_name); ?></span>
    </div>

    <!-- 地圖標題卡 -->
    <?php
    $play_count = count($map_sessions);
    $total_dur  = array_sum(array_column(array_map(fn($s) => ['d' => (int)$s->duration], $map_sessions), 'd'));

    // 計算通關情形
    $map_completed          = false;
    $map_campaign_completed = false;
    $map_diff               = null;
    foreach ($map_sessions as $s) {
        if ((int)$s->campaign_completed) {
            $map_campaign_completed = true;
            $map_diff = $s->difficulty;
            break;
        } elseif ((int)$s->completed && !$map_completed) {
            $map_completed = true;
            $map_diff = $s->difficulty;
        }
    }
    ?>
    <div class="l4d2-session-header">
        <?php echo \L4D2Stats\Plugin::render_campaign_thumbnail($run['campaign_name'], 'large'); ?>
        <div class="l4d2-session-info">
            <h3 class="l4d2-session-map-name">
                <?php echo esc_html($map_info->map_name); ?>
                <?php if ((int)$map_info->is_finale): ?>
                    <span class="l4d2-badge l4d2-badge-gold">Finale</span>
                <?php endif; ?>
            </h3>
            <div class="l4d2-player-meta">
                <span>戰役: <?php echo esc_html($run['campaign_name']); ?></span>
                <span>遊玩次數: <?php echo $play_count; ?> 次
                    <?php if ($play_count > 1): ?>
                        <span class="l4d2-badge l4d2-badge-retry">重試 <?php echo $play_count - 1; ?> 次</span>
                    <?php endif; ?>
                </span>
                <span>總時長: <?php echo \L4D2Stats\Plugin::format_playtime($total_dur); ?></span>
            </div>
            <div class="l4d2-player-meta" style="margin-top: 8px;">
                <?php if ($map_campaign_completed): ?>
                    <span class="l4d2-badge l4d2-badge-gold">戰役通關</span>
                <?php elseif ($map_completed): ?>
                    <span class="l4d2-badge l4d2-badge-success">通關</span>
                <?php else: ?>
                    <span class="l4d2-badge l4d2-badge-fail">未通關</span>
                <?php endif; ?>
                <?php if ($map_diff): ?>
                    <span class="l4d2-difficulty l4d2-diff-<?php echo esc_attr($map_diff); ?>">
                        <?php echo esc_html($difficulty_labels[$map_diff] ?? ucfirst($map_diff)); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 重試紀錄 -->
    <h3 class="l4d2-section-title">遊玩紀錄</h3>
    <?php
    $detail_base_url = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_session_detail');
    ?>
    <table class="l4d2-table">
        <thead>
            <tr>
                <th>#</th>
                <th>開始時間</th>
                <th>時長</th>
                <th>難度</th>
                <th>結果</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($map_sessions as $idx => $s): ?>
            <tr>
                <td><span class="l4d2-chapter-num"><?php echo $idx + 1; ?></span></td>
                <td><?php echo wp_date('Y-m-d H:i', \L4D2Stats\Plugin::mysql_utc($s->start_time)); ?></td>
                <td><?php echo \L4D2Stats\Plugin::format_playtime($s->duration); ?></td>
                <td>
                    <span class="l4d2-difficulty l4d2-diff-<?php echo esc_attr($s->difficulty); ?>">
                        <?php echo esc_html($difficulty_labels[$s->difficulty] ?? ucfirst($s->difficulty)); ?>
                    </span>
                </td>
                <td>
                    <?php if ((int)$s->campaign_completed): ?>
                        <span class="l4d2-badge l4d2-badge-gold">戰役通關</span>
                    <?php elseif ((int)$s->completed): ?>
                        <span class="l4d2-badge l4d2-badge-success">通關</span>
                    <?php else: ?>
                        <span class="l4d2-badge l4d2-badge-fail">未通關</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo esc_url(add_query_arg([
                        'session_id' => (int)$s->session_id,
                        'view'       => 'map',
                    ], $detail_base_url)); ?>"
                       class="l4d2-chapter-detail-link">
                        查看詳細
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!$has_stats): ?>
        <div class="l4d2-notice">
            此地圖尚無詳細統計數據。
        </div>
    <?php endif; ?>

    <!-- 圖表區 -->
    <?php if ($has_stats && !empty($chart_kills_data) && array_sum($chart_kills_data) > 0): ?>
    <div class="l4d2-chart-row">
        <div class="l4d2-chart-container l4d2-chart-half">
            <h4 class="l4d2-chart-title">玩家擊殺比較 (所有嘗試合計)</h4>
            <canvas id="l4d2-session-kills-chart"
                    data-chart='<?php echo esc_attr(json_encode([
                        'labels' => $chart_kills_labels,
                        'data'   => $chart_kills_data,
                    ])); ?>'
                    height="250"></canvas>
        </div>
        <div class="l4d2-chart-container l4d2-chart-half">
            <h4 class="l4d2-chart-title">玩家傷害比較 (所有嘗試合計)</h4>
            <canvas id="l4d2-session-damage-chart"
                    data-chart='<?php echo esc_attr(json_encode([
                        'labels' => $chart_damage_labels,
                        'data'   => $chart_damage_data,
                    ])); ?>'
                    height="250"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($chart_weapon_data) && array_sum($chart_weapon_data) > 0): ?>
    <div class="l4d2-chart-container">
        <h4 class="l4d2-chart-title">武器使用分布 (所有嘗試合計)</h4>
        <canvas id="l4d2-session-weapon-chart"
                data-chart='<?php echo esc_attr(json_encode([
                    'labels' => $chart_weapon_labels,
                    'data'   => $chart_weapon_data,
                ])); ?>'
                height="300"></canvas>
    </div>
    <?php endif; ?>

    <!-- 玩家數據表 -->
    <?php if (!empty($players)): ?>
    <h3 class="l4d2-section-title">玩家表現 (所有嘗試合計)</h3>
    <table id="l4d2-session-players-table" class="l4d2-table display">
        <thead>
            <tr>
                <th>玩家</th>
                <th>總擊殺</th>
                <th>特感</th>
                <th>Witch</th>
                <th>Tank</th>
                <th>爆頭</th>
                <th>傷害</th>
                <th>承受傷害</th>
                <th>死亡</th>
                <th>倒地</th>
                <th>特感命中率</th>
                <th>友傷</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $p): ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url(add_query_arg([
                        'steam_id' => urlencode($p->steam_id),
                    ], get_permalink(get_page_by_path('player-stats')))); ?>" class="l4d2-player-link">
                        <?php echo \L4D2Stats\Plugin::render_avatar($avatars[$p->steam_id_64] ?? '', 'small'); ?>
                        <?php echo esc_html($p->name); ?>
                    </a>
                </td>
                <td data-order="<?php echo (int)$p->total_kills; ?>">
                    <?php echo number_format((int)$p->total_kills); ?>
                </td>
                <td><?php echo number_format((int)$p->kills_si); ?></td>
                <td><?php echo (int)$p->kills_witch; ?></td>
                <td><?php echo (int)$p->kills_tank; ?></td>
                <td data-order="<?php echo (int)$p->headshots; ?>">
                    <?php echo number_format((int)$p->headshots); ?>
                </td>
                <td data-order="<?php echo (int)$p->damage_dealt; ?>">
                    <?php echo number_format((int)$p->damage_dealt); ?>
                </td>
                <td data-order="<?php echo (int)$p->damage_taken; ?>">
                    <?php echo number_format((int)$p->damage_taken); ?>
                </td>
                <td><?php echo (int)$p->deaths; ?></td>
                <td><?php echo (int)$p->incaps; ?></td>
                <td><?php echo $p->accuracy; ?>%</td>
                <td>
                    <?php $ff = (int)$p->friendly_fire_damage; ?>
                    <?php if ($ff > 0): ?>
                        <span class="l4d2-ff-warning"><?php echo number_format($ff); ?></span>
                    <?php else: ?>
                        0
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 團隊互助 / 道具使用 -->
    <h3 class="l4d2-section-title">團隊互助 / 道具使用 (所有嘗試合計)</h3>
    <table class="l4d2-table display">
        <thead>
            <tr>
                <th>玩家</th>
                <th>救援</th>
                <th>被救</th>
                <th>治療</th>
                <th>被治療</th>
                <th>治療量</th>
                <th>藥丸</th>
                <th>腎上腺素</th>
                <th>電擊器</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($players as $p): ?>
            <tr>
                <td><?php echo esc_html($p->name); ?></td>
                <td><?php echo (int)$p->revives_given; ?></td>
                <td><?php echo (int)$p->revives_received; ?></td>
                <td><?php echo (int)$p->heals_given; ?></td>
                <td><?php echo (int)$p->heals_received; ?></td>
                <td><?php echo number_format((int)$p->health_restored); ?></td>
                <td><?php echo (int)$p->pills_used; ?></td>
                <td><?php echo (int)$p->adrenaline_used; ?></td>
                <td><?php echo (int)$p->defibs_used; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- 武器明細表 -->
    <?php if (!empty($weapons)): ?>
    <h3 class="l4d2-section-title">武器使用明細 (所有嘗試合計)</h3>
    <table id="l4d2-session-weapons-table" class="l4d2-table display">
        <thead>
            <tr>
                <th>玩家</th>
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
                <td><?php echo esc_html($w->player_name); ?></td>
                <td><?php echo esc_html($w->weapon_name); ?></td>
                <td>
                    <span class="l4d2-weapon-type l4d2-type-<?php echo esc_attr($w->weapon_type); ?>">
                        <?php echo esc_html($w->weapon_type); ?>
                    </span>
                </td>
                <td data-order="<?php echo (int)$w->kills; ?>">
                    <?php echo number_format((int)$w->kills); ?>
                </td>
                <td><?php echo number_format((int)$w->headshots); ?></td>
                <td data-order="<?php echo (int)$w->damage_dealt; ?>">
                    <?php echo number_format((int)$w->damage_dealt); ?>
                </td>
                <td><?php echo number_format((int)$w->shots_fired); ?></td>
                <td><?php echo number_format((int)$w->shots_hit); ?></td>
                <td><?php echo $w->accuracy; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</div>
