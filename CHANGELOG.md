# Changelog

## [1.1.0] - 2025-02-13

### 新增

- **場次詳細頁面** — 點擊近期場次的時間連結即可查看完整場次資訊
  - 場次資訊卡片：地圖、戰役、難度、人數、時長、通關結果
  - 玩家表現表格：總擊殺、特感/Witch/Tank 擊殺、爆頭、傷害、承受傷害、死亡、倒地、命中率、友軍傷害
  - 團隊互助表格：救援、治療、藥丸、腎上腺素、電擊器使用次數
  - 武器使用明細：每位玩家各武器的擊殺/爆頭/傷害/射擊/命中率
  - 圖表：玩家擊殺比較（水平長條圖）、玩家傷害比較（水平長條圖）、武器使用分布（甜甜圈圖）
- **資料庫新增 2 張表**
  - `l4d2_session_player_stats` — 每場次每位玩家的戰績數據
  - `l4d2_session_player_weapon_stats` — 每場次每位玩家各武器數據
- **SourceMod 插件雙寫機制** — `FlushPlayerStats()` 與 `FlushPlayerWeaponStats()` 現在同時寫入累計表與場次表
- **新增 Shortcode** `[l4d2_session_detail]` — 場次詳細頁面
- **新增 URL 路由** `stats/session/{id}` — 場次詳細頁面永久連結
- **遷移腳本** `migration_session_stats.sql` — 供現有安裝者新增場次數據表

### 變更

- 近期場次列表的時間欄位改為可點擊的連結，指向場次詳細頁面
- 玩家個人頁面的近期場次同樣加入場次詳細連結
- `MAX_QUERY_LENGTH` 從 2048 提升至 4096，以容納場次寫入查詢
- `class-plugin.php` 新增 `session_id` query var 與 `session_page` URL 傳遞至前端
- `class-database.php` 的 `verify_tables()` 新增 2 張場次數據表的檢查

### 新增檔案

- `sourcemod-plugin/sql/migration_session_stats.sql`
- `wordpress-plugin/includes/class-sessiondetail.php`
- `wordpress-plugin/templates/session-detail.php`

---

## [1.0.0] - 2025-02-12

### 初始版本

- SourceMod 插件：追蹤戰役模式的玩家遊戲數據
  - 擊殺追蹤（普通感染者、特殊感染者、Witch、Tank）
  - 武器使用追蹤（擊殺/爆頭/傷害/命中率）
  - 生存追蹤（死亡、倒地、受傷）
  - 團隊追蹤（救援、治療、道具使用、友軍傷害）
  - 場次管理（開始/結束、玩家加入/離開）
  - 遊戲內指令（`!stats`、`!rank`、`!top`）
  - 記憶體緩衝 + 非同步定期寫入
- WordPress 插件：網頁前端展示
  - 玩家排行榜（多種排序方式）
  - 玩家個人頁面（戰績卡片、武器圖表、地圖記錄）
  - 武器統計頁面
  - 地圖統計頁面
  - 近期場次列表
  - 即時玩家搜尋（AJAX）
  - 深色主題 + RWD 響應式設計
- 資料庫：8 張資料表 + 武器/地圖預填資料
