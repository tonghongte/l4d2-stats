/**
 * L4D2 Player Stats - SourceMod Plugin
 * 追蹤戰役模式(Campaign Co-op)的玩家遊戲數據
 *
 * 功能：
 * - 武器使用追蹤 (擊殺/爆頭/命中率)
 * - 地圖遊玩記錄
 * - 玩家排行榜
 * - 遊戲場次記錄
 */

#include <sourcemod>
#include <sdktools>
#include <sdkhooks>

#pragma semicolon 1
#pragma newdecls required

// ============================================================
// Plugin Info
// ============================================================
public Plugin myinfo = {
    name        = "L4D2 Player Stats",
    author      = "L4D2-Stats",
    description = "Campaign Co-op statistics tracking for L4D2",
    version     = "1.0.0",
    url         = ""
};

// ============================================================
// Constants
// ============================================================
#define FLUSH_INTERVAL      120.0   // 每2分鐘寫入一次資料庫
#define MAX_WEAPON_NAME     64
#define MAX_QUERY_LENGTH    2048
#define TEAM_SURVIVOR       2
#define TEAM_INFECTED       3

// Zombie classes
#define ZC_SMOKER   1
#define ZC_BOOMER   2
#define ZC_HUNTER   3
#define ZC_SPITTER  4
#define ZC_JOCKEY   5
#define ZC_CHARGER  6
#define ZC_WITCH    7
#define ZC_TANK     8

// ============================================================
// Global Variables
// ============================================================

// 資料庫
Database g_hDatabase = null;
bool g_bDatabaseReady = false;

// 場次
int g_iSessionId = -1;
int g_iMapId = -1;
bool g_bSessionActive = false;
float g_fSessionStartTime;
bool g_bMapCompleted = false;

// 玩家資料
int g_iPlayerDbId[MAXPLAYERS + 1];
bool g_bPlayerLoaded[MAXPLAYERS + 1];
float g_fPlayerJoinTime[MAXPLAYERS + 1];

// 玩家數據緩衝
enum struct PlayerStatBuffer {
    int kills_infected;
    int kills_si;
    int kills_witch;
    int kills_tank;
    int headshots;
    int damage_dealt;
    int damage_taken;
    int deaths;
    int incaps;
    int revives_given;
    int revives_received;
    int heals_given;
    int heals_received;
    int health_restored;
    int pills_used;
    int adrenaline_used;
    int defibs_used;
    int ff_dealt;
    int ff_damage;
    int shots_fired;
    int shots_hit;
    int melee_swings;
    int melee_hits;
    int campaigns_completed;
    int maps_completed;
}

PlayerStatBuffer g_PlayerStats[MAXPLAYERS + 1];

// 武器數據緩衝: StringMap per player, key=weapon_name, value=int[5]
// values[0]=kills, [1]=headshots, [2]=damage, [3]=shots_fired, [4]=shots_hit
StringMap g_WeaponStats[MAXPLAYERS + 1];

// 地圖數據緩衝
int g_iMapKills[MAXPLAYERS + 1];
int g_iMapDeaths[MAXPLAYERS + 1];

// Timer
Handle g_hFlushTimer = null;

// ConVars
ConVar g_cvEnabled;
ConVar g_cvMinPlaytime;

// ============================================================
// Include Files
// ============================================================
#include "include/l4d2_stats_util.inc"
#include "include/l4d2_stats_db.inc"
#include "include/l4d2_stats_session.inc"
#include "include/l4d2_stats_events.inc"
#include "include/l4d2_stats_commands.inc"

// ============================================================
// Plugin Lifecycle
// ============================================================
public void OnPluginStart()
{
    // ConVars
    g_cvEnabled = CreateConVar("l4d2_stats_enabled", "1", "啟用戰績追蹤", FCVAR_NOTIFY, true, 0.0, true, 1.0);
    g_cvMinPlaytime = CreateConVar("l4d2_stats_min_playtime", "60",
        "最少遊玩秒數才記錄玩家場次", FCVAR_NOTIFY, true, 0.0);

    // 連接資料庫
    Database.Connect(OnDatabaseConnected, "l4d2stats");

    // Hook 遊戲事件
    HookEvent("infected_death",         Event_InfectedDeath);
    HookEvent("player_death",           Event_PlayerDeath);
    HookEvent("witch_killed",           Event_WitchKilled);
    HookEvent("player_hurt",            Event_PlayerHurt);
    HookEvent("player_incapacitated",   Event_PlayerIncapacitated);
    HookEvent("revive_success",         Event_ReviveSuccess);
    HookEvent("heal_success",           Event_HealSuccess);
    HookEvent("weapon_fire",            Event_WeaponFire);
    HookEvent("friendly_fire",          Event_FriendlyFire);
    HookEvent("pills_used",            Event_PillsUsed);
    HookEvent("adrenaline_used",        Event_AdrenalineUsed);
    HookEvent("defibrillator_used",     Event_DefibUsed);
    HookEvent("round_start",           Event_RoundStart);
    HookEvent("map_transition",         Event_MapTransition);
    HookEvent("finale_win",            Event_FinaleWin);
    HookEvent("player_disconnect",      Event_PlayerDisconnect, EventHookMode_Pre);

    // 管理員指令
    RegAdminCmd("sm_stats_flush", Cmd_FlushStats, ADMFLAG_ROOT, "強制寫入數據到資料庫");

    // 玩家指令
    RegConsoleCmd("sm_stats", Cmd_ShowStats, "顯示你的戰績");
    RegConsoleCmd("sm_rank",  Cmd_ShowRank,  "顯示你的排名");
    RegConsoleCmd("sm_top",   Cmd_ShowTop,   "顯示排行榜");

    AutoExecConfig(true, "l4d2_stats");

    // Late load: 載入已連線的玩家
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsClientInGame(i) && !IsFakeClient(i))
        {
            InitPlayerBuffers(i);
        }
    }
}

public void OnPluginEnd()
{
    FlushAllPlayerStats();
    EndSession(false);
}

public void OnMapStart()
{
    g_bMapCompleted = false;

    if (!g_bDatabaseReady) return;

    char mapName[64];
    GetCurrentMap(mapName, sizeof(mapName));

    // 註冊地圖到資料庫
    EnsureMapExists(mapName);

    // 開始新場次
    StartSession(mapName);

    // 啟動定期寫入 timer
    if (g_hFlushTimer != null)
    {
        KillTimer(g_hFlushTimer);
    }
    g_hFlushTimer = CreateTimer(FLUSH_INTERVAL, Timer_FlushStats, _, TIMER_REPEAT);
}

public void OnMapEnd()
{
    // 寫入所有緩衝數據
    FlushAllPlayerStats();

    // 結束場次
    if (!g_bMapCompleted)
    {
        EndSession(false);
    }

    if (g_hFlushTimer != null)
    {
        KillTimer(g_hFlushTimer);
        g_hFlushTimer = null;
    }
}

public void OnClientPostAdminCheck(int client)
{
    if (IsFakeClient(client)) return;

    InitPlayerBuffers(client);

    if (g_bDatabaseReady)
    {
        LoadPlayerFromDB(client);
    }
}

public void OnClientDisconnect(int client)
{
    if (IsFakeClient(client)) return;

    // 立即寫入此玩家的數據
    if (g_bPlayerLoaded[client] && g_bDatabaseReady)
    {
        FlushPlayerStats(client);
        FlushPlayerWeaponStats(client);
        UpdatePlayerMapStats(client);
        RemovePlayerFromSession(client);
        UpdatePlayerPlaytime(client);
    }

    // 清理
    CleanupPlayerBuffers(client);
}

// 初始化玩家緩衝
void InitPlayerBuffers(int client)
{
    PlayerStatBuffer empty;
    g_PlayerStats[client] = empty;

    if (g_WeaponStats[client] != null)
    {
        delete g_WeaponStats[client];
    }
    g_WeaponStats[client] = new StringMap();

    g_bPlayerLoaded[client] = false;
    g_iPlayerDbId[client] = -1;
    g_fPlayerJoinTime[client] = GetGameTime();
    g_iMapKills[client] = 0;
    g_iMapDeaths[client] = 0;
}

// 清理玩家緩衝
void CleanupPlayerBuffers(int client)
{
    if (g_WeaponStats[client] != null)
    {
        delete g_WeaponStats[client];
        g_WeaponStats[client] = null;
    }
    g_bPlayerLoaded[client] = false;
    g_iPlayerDbId[client] = -1;
}

// 定期寫入 Timer
public Action Timer_FlushStats(Handle timer)
{
    if (!g_bDatabaseReady) return Plugin_Continue;
    FlushAllPlayerStats();
    return Plugin_Continue;
}

// 寫入所有在線玩家數據
void FlushAllPlayerStats()
{
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsClientInGame(i) && !IsFakeClient(i) && g_bPlayerLoaded[i])
        {
            FlushPlayerStats(i);
            FlushPlayerWeaponStats(i);
        }
    }
}
