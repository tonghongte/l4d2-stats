/**
 * L4D2 Stats — Skill Detection Integration
 *
 * 監聽 l4d2_skill_detect (by Tabun) 的全域 forwards，
 * 將技巧事件累計到 g_PlayerStats 緩衝中。
 *
 * 前置需求：伺服器須安裝 l4d2_skill_detect.smx
 * https://github.com/Tabun/skill_detect
 *
 * 支援的技巧：
 *   Skeet      — 射下空中 Hunter
 *   Crown      — 一槍爆頭 Witch
 *   Level      — 平壓 Charger 衝刺
 *   Deadstop   — 推擊阻擋 Hunter 撲擊
 *   Rock Skeet — 打落 Tank 投出的石頭
 *   Tongue Cut — 射殺 Smoker 解救被抓隊友
 *   Boomer Pop — 推倒殭屍炸彈使其無法噴吐
 */

// ── 輔助：確認 client 是合法的倖存者 ──
static bool IsValidSurvivor(int client)
{
    return client >= 1
        && client <= MaxClients
        && IsClientInGame(client)
        && GetClientTeam(client) == TEAM_SURVIVOR
        && IsPlayerAlive(client)
        && !IsFakeClient(client)
        && g_bPlayerLoaded[client];
}

// ============================================================
// Skeet — 霰彈槍 / 手槍射下 Hunter
// ============================================================
public void OnSkeet(int survivor, int hunter)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].skeets++;
}

public void OnSkeetMelee(int survivor, int hunter)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].skeets++;
}

public void OnSkeetSniper(int survivor, int hunter)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].skeets++;
}

public void OnSkeetGL(int survivor, int hunter)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].skeets++;
}

// ============================================================
// Crown — 一槍爆頭 Witch
// ============================================================
public void OnWitchCrown(int survivor, int damage)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].crowns++;
}

// ============================================================
// Level — 平壓 Charger 衝刺 (在衝刺中將 Charger 打死)
// ============================================================
public void OnChargerLevel(int survivor, int charger)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].levels++;
}

// ============================================================
// Deadstop — 推擊阻止 Hunter 撲擊
// ============================================================
public void OnHunterDeadstop(int survivor, int hunter)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].deadstops++;
}

// ============================================================
// Rock Skeet — 打落 Tank 石頭
// ============================================================
public void OnTankRockSkeeted(int survivor, int tank)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].rock_skeets++;
}

// ============================================================
// Tongue Cut — 射殺 Smoker 解救被抓隊友
// ============================================================
public void OnTongueCut(int survivor, int smoker)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].tongue_cuts++;
}

// 自救（被 Smoker 抓住但自己解脫）也計入 tongue_cuts
public void OnSmokerSelfClear(int survivor, int smoker, bool withShove)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].tongue_cuts++;
}

// ============================================================
// Boomer Pop — 推倒殭屍炸彈使其爆炸
// ============================================================
public void OnBoomerPop(int survivor, int boomer, int shoveCount, float timeAlive)
{
    if (!IsValidSurvivor(survivor)) return;
    g_PlayerStats[survivor].boomer_pops++;
}
