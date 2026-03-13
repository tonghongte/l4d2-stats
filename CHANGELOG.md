# Changelog

## [1.8.0] - 2026-03-13

### CS2 風格玩家卡片 + 技巧追蹤系統

#### 新增

- **CS2 風格玩家卡片** — 戰役詳細頁面的玩家區塊全面重設計，仿照 CS2 對局結算畫面

  - 每場戰役顯示最多 4 張玩家卡片，以 Grid 排版呈現
  - 每張卡片包含：分數（擊殺數）、玩家頭像、名稱連結、英文頭銜（附 hover tooltip 說明）、2×2 數據格（擊殺/傷害/命中率/死·倒）、頭銜徽章
  - 頭銜顏色以 CSS 自訂屬性 `--card-accent` 動態套用至卡片邊框與徽章
  - 每位玩家卡片下方顯示一條本局亮點或吐槽，四人不重複
  - 所有卡片下方顯示一句關於本局最值得吐槽的幽默評語

- **17 種玩家頭銜** — 貪心演算法依優先序為每位玩家分配唯一頭銜

  **基本 10 種：**
  | 頭銜 | 觸發條件 |
  |------|---------|
  | 殲滅者 The Exterminator | 全場擊殺最高 |
  | 破壞之神 The Demolisher | 全場傷害輸出最高 |
  | 爆頭獵人 The Headhunter | 全場爆頭數最高 |
  | 特感剋星 The SI Slayer | 特感擊殺最多 |
  | 神射手 The Marksman | 命中率最高（需 ≥100 發） |
  | 救命稻草 The Lifeline | 救援隊友次數最多 |
  | 移動藥局 The Pharmacy | 治療隊友次數最多 |
  | 人形坦克 The Meatshield | 承受傷害最多 |
  | 替死鬼 The Martyr | 死亡次數最多 |
  | 友傷製造 The Team Hazard | 造成友傷傷害最高 |

  **技巧 7 種（需 ≥1 次才可獲得）：**
  | 頭銜 | 觸發條件 |
  |------|---------|
  | 空中截擊手 The Skeet King | 本局 Skeet 次數最多 |
  | 女王剋星 The Crown Queen | 本局 Crown 次數最多 |
  | 衝鋒拆解 The Leveler | 本局 Level 次數最多 |
  | 反射之神 The Reflex God | 本局 Deadstop 次數最多 |
  | 岩石毀滅 The Rock Crusher | 本局 Rock Skeet 次數最多 |
  | 切舌救主 The Tongue Cutter | 本局切舌解救次數最多 |
  | 炸彈終結 The Boomer Buster | 本局 Boomer Pop 次數最多 |

- **亮點與吐槽系統** — 每局每位玩家顯示一條不重複的個人評語，涵蓋：
  - 正面：零倒地零死亡、高命中率、高爆頭率、救援達人、Tank/Witch 剋星、破壞力驚人、電擊器大師
  - 技巧正面：Skeet ≥3、Crown ≥2、Level ≥3、Deadstop ≥3、Rock Skeet ≥2、Tongue Cut ≥5、Boomer Pop ≥4
  - 負面：友傷過高、頻繁死亡、過多倒地、命中率過低
  - 技巧吐槽：擊殺多但四項技巧全零（「全靠亂槍打鳥」）

- **技巧追蹤系統（完整三層管道）** — 追蹤來自 [l4d2_skill_detect](https://github.com/Tabun/skill_detect) 插件的 7 種技巧事件

  - **資料庫遷移** (`migration_skill_stats.sql`)：為 `l4d2_player_stats` 與 `l4d2_session_player_stats` 各新增 7 個欄位（`skeets`、`crowns`、`levels`、`deadstops`、`rock_skeets`、`tongue_cuts`、`boomer_pops`）
  - **SourceMod 模組** (`l4d2_stats_skills.inc`)：監聽 l4d2_skill_detect 的全域 forwards（Skeet/SkeetMelee/SkeetSniper/SkeetGL/WitchCrown/ChargerLevel/HunterDeadstop/TankRockSkeeted/TongueCut/SmokerSelfClear/BoomerPop），累計至記憶體緩衝
  - **PHP 個人成就**：玩家個人頁面新增 7 種技巧階梯成就（銅/銀/金）

  | 成就 | 門檻（銅/銀/金） |
  |------|----------------|
  | 空中截擊（Skeet） | 5 / 20 / 50 |
  | 女王殺手（Crown） | 3 / 10 / 30 |
  | 地平壓制（Level） | 5 / 20 / 50 |
  | 反應之神（Deadstop） | 5 / 20 / 50 |
  | 岩石粉碎（Rock Skeet） | 3 / 10 / 20 |
  | 切舌俠（Tongue Cut） | 15 / 50 / 100 |
  | 炸彈拆除（Boomer Pop） | 5 / 20 / 50 |

#### 變更

- `class-sessiondetail.php` — 新增 `compute_player_cards()`、`pick_unique_highlight()`、`compute_funny_line()` 三個靜態方法；聚合 SQL 新增 7 個技巧欄位
- `class-player.php` — `compute_player_achievements()` 新增 7 種技巧成就
- `templates/campaign-detail.php` — 玩家亮點卡片區塊改為 CS2 風格玩家卡片
- `sourcemod-plugin/scripting/l4d2_stats.sp` — `MAX_QUERY_LENGTH` 提升至 8192；`PlayerStatBuffer` struct 新增 7 個技巧欄位；加入 `#include "include/l4d2_stats_skills.inc"`
- `sourcemod-plugin/scripting/include/l4d2_stats_db.inc` — `FlushPlayerStats` 與 session INSERT 查詢加入 7 個技巧欄位
- `l4d2-stats.css` — 新增 CS2 風格卡片完整樣式（`.l4d2-match-players`、`.l4d2-match-card`、`.l4d2-mc-*`、`.l4d2-mc-hl-good/bad`、`.l4d2-mc-funny`）

#### 新增檔案

- `sourcemod-plugin/sql/migration_skill_stats.sql`
- `sourcemod-plugin/scripting/include/l4d2_stats_skills.inc`

#### 前置需求（技巧追蹤）

技巧追蹤需另外在伺服器安裝 **l4d2_skill_detect** 插件（by Tabun），`l4d2_stats.smx` 才能收到技巧事件。未安裝時插件仍可正常運作，技巧欄位維持 0。

---

## [1.7.0] - 2026-03-02

### 成就系統 + 玩家亮點卡片

#### 新增

- **戰役場次成就徽章** — 戰役詳細頁面標題卡新增 5 種額外徽章，與既有「依序通關」並列顯示

  | 徽章 | 觸發條件 |
  |------|---------|
  | 零重試 | 通關且每個章節只嘗試一次（無重試） |
  | 速通 | 通關且平均每章遊玩時間 < 25 分鐘 |
  | 專家通關 | 在專家（Expert）難度下完成戰役通關 |
  | 零死亡 | 通關且全程所有玩家死亡數為零 |
  | 零友傷 | 通關且全程無任何友軍傷害 |

- **玩家亮點卡片** — 戰役詳細頁面新增「玩家亮點」區塊（多玩家時顯示），以卡片形式突顯每個類別最出色的玩家

  - 最多擊殺、最高傷害、最多爆頭、特感獵人（最多特感擊殺）、神槍手（最高命中率）
  - 救援英雄（最多救援）、醫療兵（最多治療）、吸彈海綿（最多承受傷害）
  - 替死鬼（最多死亡）、隊友之殤（最多友軍傷害）
  - 每張卡片顯示：類別名稱、玩家頭像 + 名稱、數值
  - 各類別以色彩區分（紅/橙/紫/綠/藍/青/灰等）

- **玩家個人成就系統** — 玩家個人頁面新增「個人成就」區塊，顯示已解鎖的成就卡片

  - **階梯式成就（銅 / 銀 / 金三階）：**
    - 殺戮機器（總擊殺 ≥ 1,000 / 5,000 / 10,000）
    - 爆頭王（爆頭數 ≥ 300 / 1,000 / 3,000）
    - 特感獵人（特感擊殺 ≥ 50 / 200 / 500）
    - 坦克剋星（Tank 擊殺 ≥ 10 / 50 / 100）
    - 女巫剋星（Witch 擊殺 ≥ 5 / 20 / 50）
    - 救援英雄（救援次數 ≥ 30 / 100 / 200）
    - 醫療兵（治療次數 ≥ 20 / 50 / 100）
    - 戰役達人（戰役通關 ≥ 5 / 20 / 50 次）
    - 格鬥家（近身命中 ≥ 50 / 200 / 500）
    - 電擊重生（電擊器使用 ≥ 5 / 20 / 50 次）
    - 地圖探索家（遊玩不同地圖種類 ≥ 10 / 30 / 50）
    - 老手（遊玩時間 ≥ 5h / 10h / 20h）
    - 神槍手（命中率 ≥ 30% / 40% / 50%，需射擊 ≥ 500 發）
  - **特殊成就（藍色，一次性解鎖）：**
    - 友善隊友（遊玩超過 5 小時且從未造成友傷）
    - 彈藥庫（累計射擊 ≥ 100,000 發）
    - 不死鳥（從未死亡且擊殺 ≥ 100）
    - 急救大師（累計恢復血量 ≥ 10,000）
  - 成就卡片顯示：成就名稱、說明（數值）、階級標籤（金/銀/銅/特殊）
  - 未達門檻的成就不顯示（只顯示已解鎖的最高階）

#### 變更

- `class-campaignrun.php` — `finalize_group()` 新增 `is_no_retry`、`is_speed_run`、`is_expert_clear` 三個欄位
- `class-sessiondetail.php` — `render_campaign_detail()` 新增 `is_no_death`、`is_no_ff` 計算；新增 `compute_spotlights()` 靜態方法
- `class-player.php` — 新增 `compute_player_achievements()` 靜態方法
- `templates/campaign-detail.php` — 標題卡徽章區擴充；新增玩家亮點卡片區塊
- `templates/player-profile.php` — 數據卡片後新增個人成就區塊
- `l4d2-stats.css` — 新增徽章樣式（`badge-cyan`、`badge-speed`、`badge-expert`、`badge-platinum`、`badge-teal`）；新增亮點卡片樣式（`.l4d2-spotlights`、`.l4d2-spotlight-card`、`.l4d2-sp-*`）；新增成就卡片樣式（`.l4d2-achievements-grid`、`.l4d2-achievement-card`、`.l4d2-ach-*`）

---

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
