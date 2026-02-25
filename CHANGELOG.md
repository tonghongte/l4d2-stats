# Changelog

## [1.6.0] - 2026-02-25

### 戰役場次章節重試統計 + 地圖統計全章節顯示 + 場次計算改版

#### 新增

- **章節地圖詳情視圖** (`?view=map_run`) — 在戰役場次詳細的「查看詳情」進入單章節詳情頁 (`campaign-map-run.php`)
  - 標題卡顯示該章節遊玩次數、重試次數（「重試 N 次」badge）、總時長、最終通關難度與結果
  - 「遊玩紀錄」表格：列出每次嘗試的開始時間、時長、難度、通關結果，可點入單地圖場次詳細
  - 玩家表現、武器使用明細（跨所有嘗試合計）

- **地圖統計全章節顯示** — 戰役地圖詳情（`[l4d2_maps]?campaign=xxx`）顯示該戰役所有章節地圖，包含尚未遊玩的章節
  - 未遊玩地圖整列淡化（`.l4d2-row-not-played`），各數據欄顯示 `-`，地圖名稱後加「未遊玩」標記

#### 變更

- **地圖統計遊玩場次計算** — 「總遊玩次數」改以**獨立戰役場次**計算，不再加總各章節 `times_played`
  - 判斷邏輯：同一戰役的 session，若距上一場結束超過 300 秒、前一場為戰役通關、或無前一場，則視為新場次起點
  - 「總通關次數」改為 `SUM(campaign_completed)`（finale 通關場次數）；通關率 = 通關場次 ÷ 總場次
  - 欄位標題「總遊玩次數」→「總遊玩場次」，標頭「總遊玩: X 次」→「總遊玩: X 場次」

- **戰役場次詳細 — 章節地圖列表重構** (`campaign-detail.php`)
  - 由「每個 session 一列」改為**以地圖聚合**：同一章節的多次嘗試合併為一列
  - 欄位：章節序號、地圖名稱、遊玩次數（含「重試 N 次」badge）、遊玩總時長、通關難度、通關情形
  - 未遊玩章節整列淡化，通關情形顯示「未遊玩」badge
  - 「操作」欄連結至章節地圖詳情（`?view=map_run`）

- **戰役場次詳細 — 難度欄** — 章節地圖表格新增「通關難度」欄，顯示通關時的難度（彩色標記）

- **SourceMod — 戰役地圖自動 seed** — 任一章節地圖被遊玩時，自動將同戰役所有章節寫入 `l4d2_maps`（`times_played = 0`），確保地圖統計顯示完整章節

- **SourceMod — Finale 偵測** — 改善 finale 章節辨識機制，確保 `is_finale` 欄位正確更新

- **時間格式** — 所有模板由 `date()` 改為 `wp_date()`，依 WordPress 設定格式化時間

#### 新增檔案

- `wordpress-plugin/templates/campaign-map-run.php`

#### 變更檔案

- `class-maps.php` — 場次計算 SQL 重寫、移除詳情查詢的 `times_played > 0` 篩選
- `class-sessiondetail.php` — 新增 `map_run` 視圖路由、章節地圖聚合邏輯
- `templates/campaign-detail.php` — 章節列表重構（地圖聚合、重試 badge、難度欄、未遊玩列）
- `templates/campaign-maps-detail.php` — 未遊玩地圖顯示、欄位標題調整
- `templates/maps.php` — 欄位標題更新
- `l4d2-stats.css` — 重試 badge 樣式（`.l4d2-badge-retry`）

---

## [1.5.0] - 2026-02-23

### 道具統計頁面 + 武器/道具圖示 + 進行中場次狀態 + 場次顯示修正

#### 新增

- **道具使用統計頁面** (`[l4d2_items]`) — 全新獨立頁面，顯示伺服器道具使用狀況
  - 頂部 5 張卡片顯示全伺服器累計：止痛藥、腎上腺素、電擊器、急救包、救援次數
  - 下方玩家明細表格，可依各道具欄位排序，附 Steam 頭像與玩家連結
  - 導航列新增「道具統計」按鈕

- **武器圖示** — 武器統計表格的武器名稱欄加入對應圖示
  - 圖示來源：L4D2 Wiki (static.wikia.nocookie.net)，涵蓋全部 35 種武器
  - 固定寬度圖示區（90px）靠右對齊，確保所有武器名稱文字左側整齊排列

- **道具圖示** — 道具統計頁面的卡片與表頭使用 L4D2 Wiki 官方道具圖示
  - 止痛藥、腎上腺素、電擊器、急救包各對應 wiki 圖片

- **場次進行中狀態** — 場次記錄可辨識目前正在遊玩的戰役
  - 偵測依據：最後一個 session 的 `end_time IS NULL`
  - 結果欄顯示綠色「遊玩中」badge，帶脈動圓點動畫
  - 章節欄顯示目前正在遊玩的地圖名稱
  - 進行中場次整列綠色背景高亮，並強制排序至最頂端

#### 修正

- **場次記錄顯示不完整** — `l4d2_sessions JOIN l4d2_maps` 改為 `LEFT JOIN`，修正因地圖記錄不存在而導致大量舊場次被 INNER JOIN 過濾消失的問題
  - 沒有地圖對應記錄的場次仍會顯示，戰役欄顯示「自訂地圖」，章節欄顯示「未知地圖」

#### 新增檔案

- `wordpress-plugin/includes/class-items.php`
- `wordpress-plugin/templates/items.php`

#### 變更

- `class-plugin.php` — 新增 `l4d2_items` shortcode 註冊，導航列加入「道具統計」
- `class-weapons.php` — 新增 `$weapon_icons` 陣列（35 種武器 → Wiki 圖示 URL 對應）
- `class-sessions.php` — `JOIN l4d2_maps` 改為 `LEFT JOIN`
- `class-campaignrun.php` — `finalize_group()` 新增 `is_active`、`current_map` 欄位
- `templates/weapons.php` — 武器名稱欄加入固定寬度圖示容器
- `templates/sessions.php` — 加入進行中狀態 badge、目前地圖、頂端排序邏輯、未知地圖 fallback
- `l4d2-stats.css` — 新增武器/道具圖示樣式、表頭小圖示、遊玩中 badge 與脈動動畫、進行中列高亮

---

## [1.4.0] - 2026-02-14

### 玩家頭像 + 戰役海報縮圖 + 時區修正

為各頁面加入 Steam 玩家頭像與官方戰役海報縮圖，提升視覺化體驗。同時修正 MySQL UTC 時間在 WordPress 前端顯示偏差的問題。

#### 新增

- **Steam 玩家頭像** — 透過 Steam Web API 批次抓取玩家頭像，快取於資料庫（24 小時過期）
  - 排行榜：玩家名稱前顯示小頭像 (28px)
  - 玩家個人頁：header 區域顯示大頭像 (96px)
  - 戰役場次詳細 / 章節場次詳細：玩家表現表格中顯示小頭像
  - 玩家搜尋結果：顯示中頭像 (40px)
  - 無 API Key 或 API 失敗時自動 fallback 至 Steam 預設頭像
- **官方戰役海報縮圖** — 引用 L4D Wiki (Fandom) 上的 14 張官方戰役海報
  - 地圖統計列表：戰役名稱前顯示小縮圖
  - 戰役地圖詳情 / 戰役場次詳細 / 章節場次詳細：標題卡顯示大縮圖
  - 自訂戰役自動顯示 CSS 佔位圖（灰底地圖圖示）
- **WordPress 設定頁面** — 後台 Settings > L4D2 Stats，可設定 Steam Web API Key
- **資料庫自動遷移** — 首次啟用時自動為 `l4d2_players` 新增 `avatar_url` 和 `avatar_updated_at` 欄位，版本旗標避免重複執行
- **時間處理 Helper** — `Plugin::mysql_utc()` 統一處理 MySQL UTC datetime 轉換

#### 修正

- **時區偏差** — MySQL `NOW()` 儲存 UTC 時間，但 PHP `strtotime()` 以 WordPress 本地時區解讀，導致所有「X 時間前」顯示偏差（如 UTC+8 地區一律多 8 小時）。所有模板的時間顯示改用 `Plugin::mysql_utc()` 正確解析

#### 變更

- `class-plugin.php` — 新增 `$campaign_thumbnails` 映射、`render_campaign_thumbnail()`、`render_avatar()`、`fetch_avatars()`、`mysql_utc()`、設定頁面、DB 遷移
- `class-leaderboard.php` — SQL 查詢加入 `avatar_url`、`avatar_updated_at`，批次抓取頭像
- `class-player.php` — 查詢加入頭像欄位，REST 搜尋回傳 `avatar_url`
- `class-sessiondetail.php` — 兩種模式皆加入頭像抓取
- 7 個模板檔案加入頭像 / 縮圖顯示 + 時區修正
- `l4d2-stats.js` — 搜尋結果加入頭像
- `l4d2-stats.css` — 頭像樣式（圓形、3 種尺寸）、縮圖樣式、佔位圖、響應式調整

---

## [1.3.0] - 2025-02-14

### 地圖統計重構 + 共用導航列

地圖統計頁面從一次性顯示所有地圖改為戰役列表 → 詳情的雙層導航。同時新增共用導航列取代原本的 tabs 頁面配置，各功能改為獨立 WordPress 頁面。

#### 新增

- **地圖統計戰役列表** — 以 DataTable 顯示所有戰役的章節數、總遊玩/通關次數、通關率、獨立玩家數、最後遊玩時間
- **戰役地圖詳情頁** (`campaign-maps-detail.php`) — 點擊戰役名稱進入，含戰役統計卡片、各章節遊玩次數圖表、章節地圖表格
- **共用導航列** — 所有主要頁面（排行榜、地圖統計、場次記錄、武器統計、搜尋玩家）頂部自動渲染膠囊按鈕式導航列，當前頁面高亮

#### 變更

- **地圖統計路由** — 採用雙模式路由（同場次詳細的模式）：預設顯示戰役列表，`?campaign=xxx` 進入戰役地圖詳情
- **頁面架構** — 從同一頁面的 tabs 配置改為各自獨立的 WordPress 頁面，透過共用導航列串聯
- **`class-maps.php`** — 重構為 `render_campaign_list()` + `render_campaign_detail()` 雙模式

#### 新增檔案

- `wordpress-plugin/templates/campaign-maps-detail.php`

---

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
