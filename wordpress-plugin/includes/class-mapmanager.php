<?php
namespace L4D2Stats;

/**
 * WordPress 後台地圖管理頁面
 * - 植入官方 L4D2 地圖
 * - 新增 / 刪除自訂戰役章節地圖
 */
class MapManager {

    public static function register() {
        add_action('admin_menu',  [self::class, 'register_menu']);
        add_action('admin_post_l4d2_seed_maps',   [self::class, 'handle_seed_maps']);
        add_action('admin_post_l4d2_add_map',     [self::class, 'handle_add_map']);
        add_action('admin_post_l4d2_delete_map',  [self::class, 'handle_delete_map']);
    }

    public static function register_menu() {
        add_submenu_page(
            'options-general.php',
            'L4D2 地圖管理',
            'L4D2 地圖管理',
            'manage_options',
            'l4d2-stats-maps',
            [self::class, 'render_page']
        );
    }

    // ── 處理「植入官方地圖」 ──────────────────────────────────────

    public static function handle_seed_maps() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('l4d2_seed_maps');

        Plugin::seed_official_maps();

        wp_redirect(add_query_arg(['page' => 'l4d2-stats-maps', 'l4d2msg' => 'seeded'],
            admin_url('options-general.php')));
        exit;
    }

    // ── 處理「新增地圖」 ─────────────────────────────────────────

    public static function handle_add_map() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('l4d2_add_map');

        $map_name      = sanitize_text_field($_POST['map_name']      ?? '');
        $display_name  = sanitize_text_field($_POST['display_name']  ?? '');
        $campaign_name = sanitize_text_field($_POST['campaign_name'] ?? '');
        $is_finale     = isset($_POST['is_finale']) ? 1 : 0;

        $msg = 'add_empty';
        if (!empty($map_name) && !empty($campaign_name)) {
            $wpdb = Database::instance()->get_db();
            $rows = $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO l4d2_maps (map_name, display_name, campaign_name, is_finale)
                 VALUES (%s, %s, %s, %d)",
                $map_name,
                $display_name ?: $map_name,
                $campaign_name,
                $is_finale
            ));
            $msg = ($rows > 0) ? 'added' : 'add_dup';
        }

        wp_redirect(add_query_arg(['page' => 'l4d2-stats-maps', 'l4d2msg' => $msg],
            admin_url('options-general.php')));
        exit;
    }

    // ── 處理「刪除地圖」 ─────────────────────────────────────────

    public static function handle_delete_map() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('l4d2_delete_map');

        $map_id = intval($_POST['map_id'] ?? 0);
        if ($map_id > 0) {
            $wpdb = Database::instance()->get_db();
            // 只允許刪除從未遊玩的地圖（times_played = 0）
            $wpdb->query($wpdb->prepare(
                "DELETE FROM l4d2_maps WHERE id = %d AND times_played = 0",
                $map_id
            ));
        }

        wp_redirect(add_query_arg(['page' => 'l4d2-stats-maps', 'l4d2msg' => 'deleted'],
            admin_url('options-general.php')));
        exit;
    }

    // ── 渲染管理頁面 ─────────────────────────────────────────────

    public static function render_page() {
        if (!current_user_can('manage_options')) return;

        $db   = Database::instance();
        $maps = $db->query(
            "SELECT id, map_name, display_name, campaign_name, is_finale, times_played
             FROM l4d2_maps
             ORDER BY campaign_name ASC, map_name ASC"
        );

        // 依戰役分組
        $campaigns = [];
        foreach ($maps as $m) {
            $campaigns[$m->campaign_name][] = $m;
        }

        $msg = isset($_GET['l4d2msg']) ? sanitize_text_field($_GET['l4d2msg']) : '';
        $notices = [
            'seeded'    => ['success', '官方地圖植入完成！'],
            'added'     => ['success', '地圖新增成功！'],
            'add_dup'   => ['warning', '地圖代碼已存在，未重複新增。'],
            'add_empty' => ['error',   '戰役名稱與地圖代碼不可為空。'],
            'deleted'   => ['success', '地圖已刪除。'],
        ];
        ?>
        <div class="wrap">
            <h1>L4D2 地圖管理</h1>

            <?php if (isset($notices[$msg])): [$type, $text] = $notices[$msg]; ?>
                <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
                    <p><?php echo esc_html($text); ?></p>
                </div>
            <?php endif; ?>

            <!-- ── 植入官方地圖 ── -->
            <h2>植入官方地圖</h2>
            <p>將全部 56 張官方 L4D2 / L4D1 章節地圖一次性植入資料庫。若地圖代碼已存在則略過，不覆蓋現有資料。</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="l4d2_seed_maps">
                <?php wp_nonce_field('l4d2_seed_maps'); ?>
                <?php submit_button('植入官方地圖', 'secondary', 'submit', false); ?>
            </form>

            <hr>

            <!-- ── 新增自訂地圖 ── -->
            <h2>新增自訂地圖</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="l4d2_add_map">
                <?php wp_nonce_field('l4d2_add_map'); ?>
                <table class="form-table" style="max-width:600px;">
                    <tr>
                        <th><label for="l4d2_campaign">戰役名稱 <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="l4d2_campaign" name="campaign_name" class="regular-text"
                                   placeholder="例: My Custom Campaign" required>
                            <p class="description">與 SourceMod 記錄一致的戰役名稱（大小寫相同）</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="l4d2_mapname">地圖代碼 <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="l4d2_mapname" name="map_name" class="regular-text"
                                   placeholder="例: custom_map1" required>
                            <p class="description">SourceMod 記錄的地圖引擎名稱</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="l4d2_displayname">顯示名稱</label></th>
                        <td>
                            <input type="text" id="l4d2_displayname" name="display_name" class="regular-text"
                                   placeholder="例: Chapter 1 - The Beginning">
                            <p class="description">留空則自動使用地圖代碼</p>
                        </td>
                    </tr>
                    <tr>
                        <th>最終章</th>
                        <td><label><input type="checkbox" name="is_finale" value="1"> 此地圖為 Finale</label></td>
                    </tr>
                </table>
                <?php submit_button('新增地圖'); ?>
            </form>

            <hr>

            <!-- ── 地圖清單 ── -->
            <h2>地圖清單
                <span style="font-size:14px; font-weight:normal; color:#666; margin-left:8px;">
                    共 <?php echo count($maps); ?> 張 / <?php echo count($campaigns); ?> 個戰役
                </span>
            </h2>
            <p style="color:#666; font-size:13px;">
                「刪除」按鈕只對 <strong>從未遊玩</strong>（遊玩次數 = 0）的地圖顯示。
            </p>

            <?php if (empty($campaigns)): ?>
                <p>資料庫中尚無地圖資料。請先植入官方地圖或新增自訂地圖。</p>
            <?php else: ?>
                <?php foreach ($campaigns as $campaign_name => $campaign_maps): ?>
                <h3 style="margin-top:20px; border-left:4px solid #0073aa; padding-left:10px;">
                    <?php echo esc_html($campaign_name ?: '(無戰役名稱)'); ?>
                    <span style="font-weight:normal; font-size:13px; color:#888; margin-left:6px;">
                        (<?php echo count($campaign_maps); ?> 章)
                    </span>
                </h3>
                <table class="wp-list-table widefat fixed striped"
                       style="max-width:860px; margin-bottom:10px;">
                    <thead>
                        <tr>
                            <th style="width:36px;">#</th>
                            <th>地圖代碼</th>
                            <th>顯示名稱</th>
                            <th style="width:60px; text-align:center;">Finale</th>
                            <th style="width:70px; text-align:right;">遊玩次數</th>
                            <th style="width:70px; text-align:center;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaign_maps as $idx => $m): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><code><?php echo esc_html($m->map_name); ?></code></td>
                            <td><?php echo esc_html($m->display_name); ?></td>
                            <td style="text-align:center;">
                                <?php echo (int)$m->is_finale ? '✔' : ''; ?>
                            </td>
                            <td style="text-align:right;"><?php echo number_format((int)$m->times_played); ?></td>
                            <td style="text-align:center;">
                                <?php if ((int)$m->times_played === 0): ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="l4d2_delete_map">
                                    <input type="hidden" name="map_id" value="<?php echo (int)$m->id; ?>">
                                    <?php wp_nonce_field('l4d2_delete_map'); ?>
                                    <button type="submit" class="button button-small button-link-delete"
                                            onclick="return confirm('確定刪除「<?php echo esc_js($m->display_name ?: $m->map_name); ?>」？')">
                                        刪除
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span style="color:#aaa;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
