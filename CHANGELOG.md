# Changelog

## [1.2.0] - 2025-02-13

### 戰役場次分組

將「遊戲場次」從逐地圖顯示改為以戰役 (campaign run) 為單位分組顯示，支援三層導航：場次列表 → 戰役詳細 → 章節詳細。

#### 新增

- **戰役場次分組引擎** (`class-campaignrun.php`) — 自動將連續遊玩的章節地圖歸組為一場戰役（同一 campaign、間隔 ≤ 5 分鐘）
- **戰役場次詳細頁面** (`campaign-detail.php`) — 章節地圖列表 + 全戰役聚合玩家統計/武器統計/互動圖表
- **「依序通關」徽章** — 從第 1 章依序打到最終章、同一難度、全部通關時顯示金色標記
- **麵包屑導航** — 場次列表 → 戰役詳細 → 章節詳細

#### 變更

- **場次列表** — 每列改為一場戰役（原本為一張地圖），欄位：時間、戰役、章節數、難度、時長、玩家數、玩家、結果
- **場次詳細路由** — 預設進入戰役詳細頁；加上 `?view=map` 參數進入單地圖章節詳細（原始行為）
- **結果徽章** — 「依序通關」（金色）/「通關」（綠色）/「未通關」（紅色）三種狀態
- 玩家個人頁面的近期場次連結自動指向戰役視圖
- 不需修改資料庫 schema 或 SourceMod 插件，分組邏輯純在 WordPress PHP 層完成

#### 新增檔案

- `wordpress-plugin/includes/class-campaignrun.php`
- `wordpress-plugin/templates/campaign-detail.php`

---

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
