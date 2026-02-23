<?php if (!defined('ABSPATH')) exit;

$item_icons = [
    'pills'      => 'https://static.wikia.nocookie.net/left4dead/images/9/9e/Pills_1.png',
    'adrenaline' => 'https://static.wikia.nocookie.net/left4dead/images/e/e3/Adrenaline.png',
    'defib'      => 'https://static.wikia.nocookie.net/left4dead/images/3/30/Defibrillator_transparent.png',
    'medkit'     => 'https://static.wikia.nocookie.net/left4dead/images/1/18/Medkit_1.png',
];
?>
<div class="l4d2-stats-container">
    <?php echo \L4D2Stats\Plugin::render_nav('l4d2_items'); ?>
    <h2 class="l4d2-title">道具使用統計</h2>

    <?php if ($totals): ?>
    <!-- 伺服器總計卡片 -->
    <div class="l4d2-items-overview">
        <div class="l4d2-item-card">
            <div class="l4d2-item-icon">
                <img src="<?php echo esc_url($item_icons['pills']); ?>" alt="止痛藥" loading="lazy">
            </div>
            <div class="l4d2-item-label">止痛藥</div>
            <div class="l4d2-item-value"><?php echo number_format((int)$totals->total_pills); ?></div>
        </div>
        <div class="l4d2-item-card">
            <div class="l4d2-item-icon">
                <img src="<?php echo esc_url($item_icons['adrenaline']); ?>" alt="腎上腺素" loading="lazy">
            </div>
            <div class="l4d2-item-label">腎上腺素</div>
            <div class="l4d2-item-value"><?php echo number_format((int)$totals->total_adrenaline); ?></div>
        </div>
        <div class="l4d2-item-card">
            <div class="l4d2-item-icon">
                <img src="<?php echo esc_url($item_icons['defib']); ?>" alt="電擊器" loading="lazy">
            </div>
            <div class="l4d2-item-label">電擊器</div>
            <div class="l4d2-item-value"><?php echo number_format((int)$totals->total_defibs); ?></div>
        </div>
        <div class="l4d2-item-card">
            <div class="l4d2-item-icon">
                <img src="<?php echo esc_url($item_icons['medkit']); ?>" alt="急救包" loading="lazy">
            </div>
            <div class="l4d2-item-label">急救包</div>
            <div class="l4d2-item-value"><?php echo number_format((int)$totals->total_heals); ?></div>
        </div>
        <div class="l4d2-item-card">
            <div class="l4d2-item-icon l4d2-item-icon-emoji">🤝</div>
            <div class="l4d2-item-label">救援次數</div>
            <div class="l4d2-item-value"><?php echo number_format((int)$totals->total_revives); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($players)): ?>
        <div class="l4d2-notice">尚無道具使用數據。</div>
    <?php else: ?>

    <h3 class="l4d2-section-title">玩家道具使用明細</h3>

    <table id="l4d2-items-table" class="l4d2-table display">
        <thead>
            <tr>
                <th>#</th>
                <th>玩家</th>
                <th>
                    <img src="<?php echo esc_url($item_icons['pills']); ?>" alt="止痛藥" class="l4d2-th-icon" loading="lazy">
                    止痛藥
                </th>
                <th>
                    <img src="<?php echo esc_url($item_icons['adrenaline']); ?>" alt="腎上腺素" class="l4d2-th-icon" loading="lazy">
                    腎上腺素
                </th>
                <th>
                    <img src="<?php echo esc_url($item_icons['defib']); ?>" alt="電擊器" class="l4d2-th-icon" loading="lazy">
                    電擊器
                </th>
                <th>
                    <img src="<?php echo esc_url($item_icons['medkit']); ?>" alt="急救包" class="l4d2-th-icon" loading="lazy">
                    急救包
                </th>
                <th>🤝 救援次數</th>
                <th>道具合計</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $player_page = \L4D2Stats\Plugin::get_page_url_by_shortcode('l4d2_player_stats');
            foreach ($players as $i => $p):
                $avatar = $avatars[$p->steam_id_64] ?? \L4D2Stats\Plugin::DEFAULT_AVATAR;
                $profile_url = $player_page ? add_query_arg('steam_id', urlencode($p->name), $player_page) : '';
            ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td>
                    <?php echo \L4D2Stats\Plugin::render_avatar($avatar, 'small'); ?>
                    <?php if ($profile_url): ?>
                        <a href="<?php echo esc_url($profile_url); ?>" class="l4d2-player-link">
                            <?php echo esc_html($p->name); ?>
                        </a>
                    <?php else: ?>
                        <?php echo esc_html($p->name); ?>
                    <?php endif; ?>
                </td>
                <td data-order="<?php echo (int)$p->pills_used; ?>">
                    <?php echo number_format((int)$p->pills_used); ?>
                </td>
                <td data-order="<?php echo (int)$p->adrenaline_used; ?>">
                    <?php echo number_format((int)$p->adrenaline_used); ?>
                </td>
                <td data-order="<?php echo (int)$p->defibs_used; ?>">
                    <?php echo number_format((int)$p->defibs_used); ?>
                </td>
                <td data-order="<?php echo (int)$p->heals_given; ?>">
                    <?php echo number_format((int)$p->heals_given); ?>
                </td>
                <td data-order="<?php echo (int)$p->revives_given; ?>">
                    <?php echo number_format((int)$p->revives_given); ?>
                </td>
                <td data-order="<?php echo (int)$p->total_items; ?>">
                    <strong><?php echo number_format((int)$p->total_items); ?></strong>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
