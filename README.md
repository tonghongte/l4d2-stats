# L4D2 戰績網

Left 4 Dead 2 玩家戰績追蹤系統。透過 SourceMod 插件自動記錄遊戲數據，存入 MySQL 資料庫，再由 WordPress 插件讀取並展示於網頁上。

## 功能一覽

- **玩家排行榜** — 依擊殺數、K/D、命中率等多種排序，附 Steam 頭像
- **玩家個人頁面** — 完整戰績卡片、武器圖表、地圖記錄、近期場次，顯示大頭像
- **武器統計** — 全伺服器武器使用排行，含擊殺數/爆頭數/命中率，附 L4D2 Wiki 武器圖示
- **道具統計** — 全伺服器道具使用總覽（止痛藥/腎上腺素/電擊器/急救包/救援），附玩家明細排行，含道具圖示
- **地圖統計** — 戰役列表概覽（附官方海報縮圖），遊玩場次以獨立戰役場次計算；點進查看戰役所有章節地圖（含未遊玩章節）的遊玩次數、通關率、獨立玩家數
- **戰役場次記錄** — 連續遊玩的章節地圖自動歸組為一場戰役，顯示戰役名稱、章節數、難度、時長、通關狀態；進行中場次即時顯示「遊玩中」狀態
- **戰役詳細** — 點進戰役查看章節地圖列表（以地圖聚合，含重試次數、通關難度），全戰役聚合數據（擊殺/傷害/武器分布圖表），支援「依序通關」徽章（從頭打到尾、不換難度）
- **章節地圖詳情** — 點進個別章節查看該章節的重試紀錄（每次嘗試的時長/難度/結果）、跨所有嘗試的玩家表現與武器使用統計
- **單場次詳細** — 查看單張地圖場次的玩家表現、武器使用、道具使用、友軍傷害，搭配互動圖表
- **玩家搜尋** — 即時搜尋玩家名稱或 Steam ID，結果附頭像
- **Steam 頭像整合** — 透過 Steam Web API 自動抓取並快取玩家頭像，各頁面顯示
- **官方戰役海報** — 14 張官方戰役自動顯示海報縮圖，自訂戰役用佔位圖
- **遊戲內指令** — `!stats` `!rank` `!top` 直接在遊戲中查看

## 系統需求

| 項目 | 需求 |
|------|------|
| L4D2 伺服器 | 已安裝 [SourceMod](https://www.sourcemod.net/) 1.11+ 及 [MetaMod:Source](https://www.metamodsource.net/) |
| 資料庫 | MySQL 5.7+ 或 MariaDB 10.3+ |
| 網站 | WordPress 5.0+ (已有站台) |
| PHP | 7.4+ |
| 架構 | WordPress 與 L4D2 伺服器在同一台機器（或可互通資料庫） |

## 專案結構

```
l4d2-stats/
├── sourcemod-plugin/           # SourceMod 插件（伺服器端）
│   ├── scripting/
│   │   ├── l4d2_stats.sp           # 主程式
│   │   └── include/                # 模組化 .inc 檔案
│   │       ├── l4d2_stats_db.inc       # 資料庫操作
│   │       ├── l4d2_stats_events.inc   # 遊戲事件處理
│   │       ├── l4d2_stats_commands.inc # 遊戲內指令
│   │       ├── l4d2_stats_session.inc  # 場次管理
│   │       └── l4d2_stats_util.inc     # 工具函式
│   ├── configs/
│   │   └── databases.cfg.example   # 資料庫連線設定範例
│   └── sql/
│       ├── schema.sql              # 資料表建立腳本（含場次數據表）
│       ├── migration_session_stats.sql # 場次數據表遷移腳本
│       ├── seed_weapons.sql        # 武器預填資料
│       └── seed_maps.sql           # 地圖預填資料
│
└── wordpress-plugin/           # WordPress 插件（前端顯示）
    ├── l4d2-stats.php              # 插件入口
    ├── uninstall.php               # 解除安裝清理
    ├── includes/                   # PHP 類別
    │   ├── class-plugin.php            # 核心 (shortcode/資源/AJAX)
    │   ├── class-database.php          # 資料庫抽象層
    │   ├── class-leaderboard.php       # 排行榜
    │   ├── class-player.php            # 玩家個人頁 + 搜尋
    │   ├── class-weapons.php           # 武器統計
    │   ├── class-maps.php              # 地圖統計（戰役列表/詳情雙模式）
    │   ├── class-sessions.php          # 戰役場次記錄
    │   ├── class-sessiondetail.php     # 場次詳細（戰役/章節雙模式）
    │   ├── class-campaignrun.php       # 戰役場次分組引擎
    │   └── class-items.php             # 道具使用統計
    ├── templates/                  # HTML 模板
    └── assets/
        ├── css/l4d2-stats.css          # 深色主題樣式
        └── js/l4d2-stats.js            # DataTables + Chart.js + 搜尋
```

---

# 安裝手冊

## 步驟一：建立資料庫（使用 phpMyAdmin）

### 1.1 建立資料庫

1. 登入 phpMyAdmin（通常在 `http://你的IP/phpmyadmin`）
2. 點選上方的 **「資料庫」** 分頁
3. 在「建立資料庫」欄位：
   - 資料庫名稱輸入：`l4d2_stats`
   - 編碼選擇：`utf8mb4_unicode_ci`
4. 點選 **「建立」**

### 1.2 建立資料表

1. 在左側選單點選剛建立的 `l4d2_stats` 資料庫
2. 點選上方的 **「SQL」** 分頁
3. 開啟 `sourcemod-plugin/sql/schema.sql` 檔案，複製**除了前三行**（`CREATE DATABASE` 和 `USE` 那兩行）以外的全部內容
   > 因為你已經手動建立並選取了資料庫，不需要再執行那兩行
4. 貼到 SQL 輸入框中，點選 **「執行」**
5. 應該會看到成功建立 10 張資料表的訊息

### 1.3 填入武器資料

1. 繼續在 `l4d2_stats` 資料庫的 **「SQL」** 分頁
2. 開啟 `sourcemod-plugin/sql/seed_weapons.sql`，複製除了 `USE` 那行以外的全部內容
3. 貼到 SQL 輸入框，點選 **「執行」**
4. 應該會看到成功寫入約 43 筆武器資料

### 1.4 填入地圖資料

1. 同上步驟，開啟 `sourcemod-plugin/sql/seed_maps.sql`
2. 複製除了 `USE` 那行以外的全部內容
3. 貼到 SQL 輸入框，點選 **「執行」**
4. 應該會看到成功寫入約 55 筆地圖資料

### 1.5 建立專用資料庫帳號

1. 在 phpMyAdmin 上方點選 **「使用者帳號」** 分頁
2. 點選 **「新增使用者帳號」**
3. 填寫：
   - 使用者名稱：`l4d2stats`
   - 主機名稱：選擇「本機」或輸入 `127.0.0.1`
   - 密碼：設定一個強密碼（**記下來，之後會用到**）
4. 在下方「使用者帳號的資料庫」區塊：
   - 勾選 **「授予所有 l4d2_stats 資料庫的權限」**
5. 點選 **「執行」**

> **或者用 SQL 指令完成：**
> 在 SQL 分頁執行：
> ```sql
> CREATE USER 'l4d2stats'@'127.0.0.1' IDENTIFIED BY '你的密碼';
> GRANT ALL PRIVILEGES ON l4d2_stats.* TO 'l4d2stats'@'127.0.0.1';
> FLUSH PRIVILEGES;
> ```

### 1.6 驗證安裝

在 phpMyAdmin 左側點選 `l4d2_stats`，確認能看到以下 10 張資料表：

| 資料表 | 用途 |
|--------|------|
| `l4d2_players` | 玩家基本資料 |
| `l4d2_maps` | 地圖資料 |
| `l4d2_weapons` | 武器清單 |
| `l4d2_sessions` | 遊戲場次 |
| `l4d2_session_players` | 場次中的玩家 |
| `l4d2_player_stats` | 玩家累計數據 |
| `l4d2_player_weapon_stats` | 各武器累計數據 |
| `l4d2_player_map_stats` | 各地圖累計數據 |
| `l4d2_session_player_stats` | 每場次每位玩家的數據 |
| `l4d2_session_player_weapon_stats` | 每場次每位玩家各武器數據 |

點選 `l4d2_weapons` 應能看到約 43 筆武器資料，`l4d2_maps` 應有約 55 筆地圖資料。

---

## 步驟二：安裝 SourceMod 插件

### 2.1 設定資料庫連線

1. 用文字編輯器開啟你的 SourceMod 資料庫設定檔：
   ```
   <L4D2伺服器路徑>/left4dead2/addons/sourcemod/configs/databases.cfg
   ```

2. 在 `"Databases"` 區塊的大括號 `{ }` 內，加入以下區塊：

   ```
   "l4d2stats"
   {
       "driver"    "mysql"
       "host"      "127.0.0.1"
       "database"  "l4d2_stats"
       "user"      "l4d2stats"
       "pass"      "你在步驟1.5設定的密碼"
       "port"      "3306"
   }
   ```

3. 儲存檔案

### 2.2 編譯插件

你需要 SourceMod 的編譯器 `spcomp` 來將 `.sp` 原始碼編譯為 `.smx` 執行檔。

**方法 A：使用伺服器上的 spcomp**

```bash
cd <L4D2伺服器路徑>/left4dead2/addons/sourcemod/scripting

# 將 l4d2_stats.sp 和 include/ 資料夾複製到此目錄
# 然後執行：
./spcomp l4d2_stats.sp -o ../plugins/l4d2_stats.smx
```

**方法 B：使用線上編譯器**

1. 前往 [SourceMod Spider](https://spider.limetech.io/) 線上編譯器
2. 上傳 `l4d2_stats.sp` 及 `include/` 資料夾中的所有 `.inc` 檔案
3. 編譯後下載 `l4d2_stats.smx`

### 2.3 部署插件

1. 將編譯好的 `l4d2_stats.smx` 放到：
   ```
   <L4D2伺服器路徑>/left4dead2/addons/sourcemod/plugins/
   ```

2. 重啟 L4D2 伺服器，或在伺服器控制台輸入：
   ```
   sm plugins load l4d2_stats
   ```

3. 確認載入成功，輸入：
   ```
   sm plugins list
   ```
   應該能看到 `L4D2 Player Stats` 在清單中。

### 2.4 插件設定 (可選)

插件首次載入後會自動產生設定檔：
```
<L4D2伺服器路徑>/left4dead2/cfg/sourcemod/l4d2_stats.cfg
```

可調整的設定：

| ConVar | 預設值 | 說明 |
|--------|--------|------|
| `l4d2_stats_enabled` | `1` | 是否啟用戰績追蹤（0=關閉） |
| `l4d2_stats_min_playtime` | `60` | 最少遊玩幾秒才計入排行（過濾短暫加入的玩家） |

---

## 步驟三：安裝 WordPress 插件

### 3.1 上傳插件

1. 將 `wordpress-plugin/` 資料夾**整個**複製到你的 WordPress 插件目錄：
   ```
   <WordPress路徑>/wp-content/plugins/l4d2-stats/
   ```

   > 確保路徑結構是 `wp-content/plugins/l4d2-stats/l4d2-stats.php`

2. 最終目錄結構應為：
   ```
   wp-content/plugins/l4d2-stats/
   ├── l4d2-stats.php
   ├── uninstall.php
   ├── includes/
   ├── templates/
   └── assets/
   ```

### 3.2 設定資料庫連線

用文字編輯器開啟 WordPress 根目錄的 `wp-config.php`，在 `/* That's all, stop editing! */` 這行**之前**加入：

```php
/** L4D2 Stats 資料庫設定 */
define('L4D2_DB_HOST', '127.0.0.1');
define('L4D2_DB_NAME', 'l4d2_stats');
define('L4D2_DB_USER', 'l4d2stats');
define('L4D2_DB_PASSWORD', '你在步驟1.5設定的密碼');
```

> **注意：** 如果你的 WordPress 和 L4D2 Stats 使用同一個 MySQL 帳號，可以只設定 `L4D2_DB_NAME`，其餘會自動使用 WordPress 的資料庫帳密。

### 3.3 啟用插件

1. 登入 WordPress 後台（`/wp-admin`）
2. 前往 **「外掛」** → **「已安裝的外掛」**
3. 找到 **「L4D2 Player Stats」**，點選 **「啟用」**
4. 如果資料表設定正確，不會出現錯誤訊息
   - 如果看到紅色錯誤提示「缺少資料表」，代表步驟一的 SQL 未執行成功，請回到 phpMyAdmin 重新執行

### 3.4 建立頁面

在 WordPress 後台建立以下頁面，每個 shortcode 各自一個獨立頁面。插件會自動在每個頁面頂部渲染共用導航列，讓使用者在各功能間快速切換。

> **注意：** 請勿將多個 shortcode 放在同一頁面的 tabs 中，應各自建立獨立頁面。

#### 頁面 1：排行榜

- 標題：`排行榜`（或你喜歡的名稱）
- 內容：
  ```
  [l4d2_leaderboard]
  ```
- 可選參數：
  ```
  [l4d2_leaderboard limit="100" sort_by="total_kills"]
  ```

#### 頁面 2：玩家查詢

- 標題：`玩家戰績`
- **代稱（slug）務必設為：`player-stats`**（這是系統連結用的固定路徑）
- 內容：
  ```
  [l4d2_player_search]
  [l4d2_player_stats]
  ```

#### 頁面 3：武器統計

- 標題：`武器統計`
- 內容：
  ```
  [l4d2_weapons]
  ```
- 可選：只顯示特定類型
  ```
  [l4d2_weapons type="rifle"]
  ```

#### 頁面 4：地圖統計

- 標題：`地圖統計`
- 內容：
  ```
  [l4d2_maps]
  ```
- 預設顯示戰役列表，點擊戰役名稱自動進入戰役地圖詳情（`?campaign=xxx`）

#### 頁面 5：戰役場次

- 標題：`戰役場次`（或 `近期場次`）
- 內容：
  ```
  [l4d2_recent_sessions]
  ```
- 可選參數：
  ```
  [l4d2_recent_sessions limit="300"]
  ```
  > `limit` 為原始地圖場次上限（分組前），分組後會得到較少的戰役條目

#### 頁面 6：道具統計

- 標題：`道具統計`
- 內容：
  ```
  [l4d2_items]
  ```
- 頂部 5 張卡片顯示全伺服器累計道具使用量，下方為玩家明細排行表格

#### 頁面 7：場次詳細

- 標題：`場次詳細`
- **代稱（slug）務必設為：`session-detail`**（場次列表的連結會指向此頁面）
- 內容：
  ```
  [l4d2_session_detail]
  ```
- 此頁面自動判斷顯示模式：
  - 預設 → 戰役場次詳細（章節地圖列表 + 全戰役聚合統計）
  - `?view=map_run&map_code=xxx` → 章節地圖詳情（重試紀錄 + 跨嘗試合計數據）
  - `?view=map` → 單張地圖場次詳細（原始行為）

### 3.5 設定 Steam Web API Key（玩家頭像）

1. 前往 [Steam Web API Key 註冊頁面](https://steamcommunity.com/dev/apikey) 取得 API Key
2. 在 WordPress 後台前往 **「設定」** → **「L4D2 Stats」**
3. 填入 Steam Web API Key，點選 **「儲存變更」**

> **此步驟為選用。** 若不設定 API Key，所有玩家頭像會顯示 Steam 預設頭像，不影響其他功能。

### 3.6 更新固定網址

1. 前往 **「設定」** → **「固定網址」**
2. 不需修改任何東西，直接點選 **「儲存變更」**
3. 這會重新整理 WordPress 的 URL 路由規則，讓玩家頁面連結正常運作

---

## Shortcode 完整參考

| Shortcode | 說明 | 參數 |
|-----------|------|------|
| `[l4d2_leaderboard]` | 玩家排行榜表格 | `limit`（筆數，預設 50）、`sort_by`（排序欄位） |
| `[l4d2_player_stats]` | 玩家個人戰績頁面 | `steam_id`（指定玩家，或從 URL 自動取得） |
| `[l4d2_player_search]` | 玩家搜尋輸入框 | 無 |
| `[l4d2_weapons]` | 武器使用排行 | `type`（篩選類型：pistol/smg/shotgun/rifle/sniper/heavy/melee/throwable） |
| `[l4d2_maps]` | 地圖統計（戰役列表/詳情雙模式） | `?campaign=Dead Center` 進入戰役詳情 |
| `[l4d2_recent_sessions]` | 戰役場次記錄（自動分組），進行中場次顯示「遊玩中」 | `limit`（原始場次上限，預設 300） |
| `[l4d2_session_detail]` | 場次詳細（三層模式）| `session_id`（從 URL 自動取得）；`?view=map_run&map_code=xxx` 章節地圖詳情；`?view=map` 單地圖場次詳細 |
| `[l4d2_items]` | 道具使用統計（全伺服器總覽 + 玩家明細） | 無 |

**`sort_by` 可用的排序欄位：**
`total_kills`、`kills_si`、`kills_tank`、`headshots`、`deaths`、`kd_ratio`、`accuracy`、`revives_given`、`heals_given`、`total_playtime`、`campaigns_completed`

---

## 遊戲內指令

安裝 SourceMod 插件後，玩家可在聊天欄輸入以下指令：

| 指令 | 說明 |
|------|------|
| `!stats` | 顯示自己的戰績摘要（擊殺、K/D、命中率、救援等） |
| `!rank` | 顯示自己的排名和總擊殺數 |
| `!top` | 顯示擊殺排行榜 Top 10 |

管理員指令（需要 ROOT 權限）：

| 指令 | 說明 |
|------|------|
| `sm_stats_flush` | 強制立即將所有緩衝數據寫入資料庫 |

---

## 資料追蹤說明

### 追蹤的數據類型

**擊殺相關：**
- 普通感染者擊殺、特殊感染者擊殺（Smoker/Boomer/Hunter/Spitter/Jockey/Charger）
- Tank 擊殺、Witch 擊殺
- 爆頭數

**武器相關：**
- 每把武器的擊殺數、爆頭數、傷害量
- 射擊次數、命中次數（計算命中率）
- 近戰揮擊次數

**生存相關：**
- 死亡次數（完全死亡，非倒地）
- 倒地次數
- 受到的傷害量

**團隊相關：**
- 救援次數（救起倒地的隊友）
- 治療次數、治療量
- 藥丸使用、腎上腺素使用、電擊器使用
- 友軍傷害次數與傷害量

**場次相關：**
- 每張地圖的遊玩記錄（開始/結束時間、時長、難度）
- 參與的玩家清單
- 是否通關（地圖通關 / 戰役通關）
- 連續遊玩的章節地圖自動歸組為戰役場次（5 分鐘內的連續地圖換場視為同一戰役）
- 「依序通關」判定（從第 1 章打到最終章、同一難度、全部通關）
- 每場次每位玩家的完整數據（擊殺/傷害/死亡/治療/道具使用等）
- 每場次每位玩家的武器使用明細
- 戰役級聚合統計（全戰役的玩家表現合計）
- 章節重試追蹤：同一章節多次嘗試自動聚合，顯示重試次數與各次嘗試的詳情
- 遊玩任一章節時自動 seed 同戰役所有章節地圖，確保地圖統計顯示完整章節列表（含未遊玩章節）

### 數據寫入機制

插件採用**記憶體緩衝 + 定期寫入**的方式，避免頻繁的資料庫操作影響遊戲效能：

- 遊戲事件觸發時 → 數據暫存在伺服器記憶體
- 每 **2 分鐘**自動批次寫入資料庫
- **換圖時**自動寫入
- **玩家離線時**立即寫入該玩家的數據
- 所有資料庫操作皆使用非同步查詢（`SQL_TQuery`），不會造成遊戲卡頓
- 採用**雙寫機制**：每次寫入同時更新累計數據表與場次數據表，確保場次詳細資訊可用

---

## 故障排除

### 問題：WordPress 顯示「缺少資料表」

**原因：** 資料庫中沒有找到 `l4d2_*` 資料表。

**解決：**
1. 登入 phpMyAdmin，確認 `l4d2_stats` 資料庫存在
2. 點選該資料庫，確認有 10 張資料表
3. 如果沒有，重新執行 `schema.sql`
4. 檢查 `wp-config.php` 中的 `L4D2_DB_NAME` 是否正確

### 問題：SourceMod 插件無法連線資料庫

**原因：** `databases.cfg` 設定有誤。

**解決：**
1. 確認 `databases.cfg` 中有 `"l4d2stats"` 區塊
2. 確認帳號密碼正確
3. 確認 MySQL 允許從 `127.0.0.1` 連線
4. 查看 SourceMod 錯誤記錄：
   ```
   <L4D2伺服器路徑>/left4dead2/addons/sourcemod/logs/errors_*.log
   ```

### 問題：網頁上沒有數據

**原因：** 尚未有玩家遊玩，或數據尚未寫入。

**解決：**
1. 確認 SourceMod 插件已載入（`sm plugins list`）
2. 加入伺服器遊玩一段時間（至少超過 `l4d2_stats_min_playtime` 設定的秒數）
3. 輸入 `sm_stats_flush` 強制寫入
4. 到 phpMyAdmin 查看 `l4d2_players` 表是否有資料
5. 重新整理 WordPress 頁面

### 問題：排行榜上看不到某些玩家

**原因：** 預設排行榜只顯示遊玩時間超過 300 秒（5 分鐘）的玩家。

**解決：** 這是為了過濾短暫加入的玩家。如需調整門檻，修改 `class-leaderboard.php` 中 `WHERE p.total_playtime >= 300` 的數值。

### 問題：玩家個人頁面顯示「找不到此玩家」

**原因：** Steam ID 格式不符或玩家不存在。

**解決：**
1. 確認 URL 中的 `steam_id` 參數格式為 `STEAM_X:Y:Z`
2. 使用搜尋功能 (`[l4d2_player_search]`) 查找玩家
3. 確認 `player-stats` 頁面的代稱（slug）設定正確

### 問題：phpMyAdmin 執行 SQL 時出現錯誤

常見錯誤與解決方式：

| 錯誤訊息 | 解決方式 |
|-----------|---------|
| `Table already exists` | 資料表已存在，可安全忽略（`CREATE TABLE IF NOT EXISTS` 不應出現此錯誤，但如果你手動 `CREATE TABLE` 就會） |
| `Access denied for user` | 帳號密碼錯誤，或該帳號無此資料庫的權限 |
| `Unknown database` | 資料庫名稱打錯，或尚未建立 |
| `Foreign key constraint fails` | 請確認先執行 `schema.sql` 再執行 `seed_*.sql` |

---

## 自訂地圖支援

如果你的伺服器有安裝自訂地圖（非官方地圖），SourceMod 插件會在玩家首次遊玩該地圖時**自動將它加入資料庫**。

但自動加入的地圖只會有引擎名稱（如 `c_raptureremix_m1`），沒有中文顯示名稱。你可以在 phpMyAdmin 手動更新：

1. 點選 `l4d2_maps` 資料表
2. 找到該地圖，點選 **「編輯」**
3. 填入 `display_name` 和 `campaign_name`
4. 點選 **「執行」**

---

## 授權

本專案採用 GPL-2.0 授權。
