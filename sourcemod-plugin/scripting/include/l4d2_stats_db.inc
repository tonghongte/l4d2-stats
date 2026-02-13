/**
 * L4D2 Stats - 資料庫操作
 */

// ============================================================
// 資料庫連線
// ============================================================
public void OnDatabaseConnected(Database db, const char[] error, any data)
{
    if (db == null)
    {
        LogError("[L4D2 Stats] 資料庫連線失敗: %s", error);
        CreateTimer(30.0, Timer_RetryConnection);
        return;
    }

    g_hDatabase = db;
    g_bDatabaseReady = true;
    g_hDatabase.SetCharset("utf8mb4");

    LogMessage("[L4D2 Stats] 資料庫連線成功");

    // 載入已連線的玩家
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsClientInGame(i) && !IsFakeClient(i) && !g_bPlayerLoaded[i])
        {
            LoadPlayerFromDB(i);
        }
    }

    // 如果地圖已載入，開始場次
    char mapName[64];
    GetCurrentMap(mapName, sizeof(mapName));
    if (mapName[0] != '\0' && g_iSessionId == -1)
    {
        EnsureMapExists(mapName);
        StartSession(mapName);
    }
}

public Action Timer_RetryConnection(Handle timer)
{
    if (!g_bDatabaseReady)
    {
        LogMessage("[L4D2 Stats] 嘗試重新連線資料庫...");
        Database.Connect(OnDatabaseConnected, "l4d2stats");
    }
    return Plugin_Stop;
}

// 通用查詢回調 (不需要結果的寫入操作)
public void OnGenericQuery(Database db, DBResultSet results, const char[] error, any data)
{
    if (results == null)
    {
        LogError("[L4D2 Stats] 查詢錯誤: %s", error);
    }
}

// ============================================================
// 玩家載入
// ============================================================
void LoadPlayerFromDB(int client)
{
    if (!g_bDatabaseReady) return;

    char steamId[32], name[128], escapedName[257];
    if (!GetClientAuthId(client, AuthId_Steam2, steamId, sizeof(steamId)))
        return;

    GetClientName(client, name, sizeof(name));
    g_hDatabase.Escape(name, escapedName, sizeof(escapedName));

    // 取得 SteamID64
    char steamId64[20];
    GetClientAuthId(client, AuthId_SteamID64, steamId64, sizeof(steamId64));

    // UPSERT: 不存在則建立，存在則更新
    char query[512];
    FormatEx(query, sizeof(query), "INSERT INTO l4d2_players (steam_id, steam_id_64, name, first_seen, last_seen) VALUES ('%s', '%s', '%s', NOW(), NOW()) ON DUPLICATE KEY UPDATE name = '%s', steam_id_64 = '%s', last_seen = NOW()", steamId, steamId64, escapedName, escapedName, steamId64);

    g_hDatabase.Query(OnPlayerUpserted, query, GetClientUserId(client));
}

public void OnPlayerUpserted(Database db, DBResultSet results, const char[] error, any data)
{
    int client = GetClientOfUserId(data);
    if (client <= 0 || !IsClientInGame(client)) return;

    if (results == null)
    {
        LogError("[L4D2 Stats] 玩家 UPSERT 錯誤: %s", error);
        return;
    }

    // 查詢玩家的 DB ID
    char steamId[32], query[256];
    GetClientAuthId(client, AuthId_Steam2, steamId, sizeof(steamId));

    FormatEx(query, sizeof(query), "SELECT id FROM l4d2_players WHERE steam_id = '%s'", steamId);
    g_hDatabase.Query(OnPlayerIdLoaded, query, GetClientUserId(client));
}

public void OnPlayerIdLoaded(Database db, DBResultSet results, const char[] error, any data)
{
    int client = GetClientOfUserId(data);
    if (client <= 0 || !IsClientInGame(client)) return;

    if (results == null)
    {
        LogError("[L4D2 Stats] 查詢玩家 ID 錯誤: %s", error);
        return;
    }

    if (results.FetchRow())
    {
        g_iPlayerDbId[client] = results.FetchInt(0);
        g_bPlayerLoaded[client] = true;

        // 確保 player_stats 列存在
        char query[256];
        FormatEx(query, sizeof(query), "INSERT IGNORE INTO l4d2_player_stats (player_id) VALUES (%d)", g_iPlayerDbId[client]);
        g_hDatabase.Query(OnGenericQuery, query);

        // 加入場次
        if (g_iSessionId > 0)
        {
            AddPlayerToSession(client);
        }

        LogMessage("[L4D2 Stats] 玩家載入完成: %N (DB ID: %d)", client, g_iPlayerDbId[client]);
    }
}

// ============================================================
// 地圖管理
// ============================================================
void EnsureMapExists(const char[] mapName)
{
    if (!g_bDatabaseReady) return;

    char query[512];
    FormatEx(query, sizeof(query), "INSERT INTO l4d2_maps (map_name, first_played, last_played) VALUES ('%s', NOW(), NOW()) ON DUPLICATE KEY UPDATE last_played = NOW(), times_played = times_played + 1", mapName);

    g_hDatabase.Query(OnMapEnsured, query);
}

public void OnMapEnsured(Database db, DBResultSet results, const char[] error, any data)
{
    if (results == null)
    {
        LogError("[L4D2 Stats] 地圖 UPSERT 錯誤: %s", error);
        return;
    }

    // 取得地圖 ID
    char mapName[64], query[256];
    GetCurrentMap(mapName, sizeof(mapName));

    FormatEx(query, sizeof(query), "SELECT id FROM l4d2_maps WHERE map_name = '%s'", mapName);
    g_hDatabase.Query(OnMapIdLoaded, query);
}

public void OnMapIdLoaded(Database db, DBResultSet results, const char[] error, any data)
{
    if (results == null)
    {
        LogError("[L4D2 Stats] 查詢地圖 ID 錯誤: %s", error);
        return;
    }

    if (results.FetchRow())
    {
        g_iMapId = results.FetchInt(0);
        LogMessage("[L4D2 Stats] 地圖 ID: %d", g_iMapId);
    }
}

// ============================================================
// 數據寫入
// ============================================================
void FlushPlayerStats(int client)
{
    if (!g_bDatabaseReady || g_iPlayerDbId[client] <= 0) return;

    PlayerStatBuffer stats;
    stats = g_PlayerStats[client];

    // 檢查是否有數據需要寫入
    if (stats.kills_infected == 0 && stats.kills_si == 0 && stats.deaths == 0
        && stats.shots_fired == 0 && stats.damage_dealt == 0 && stats.headshots == 0
        && stats.revives_given == 0 && stats.heals_given == 0)
        return;

    char query[MAX_QUERY_LENGTH];
    FormatEx(query, sizeof(query), "UPDATE l4d2_player_stats SET kills_infected=kills_infected+%d, kills_si=kills_si+%d, kills_witch=kills_witch+%d, kills_tank=kills_tank+%d, headshots=headshots+%d, damage_dealt=damage_dealt+%d, damage_taken=damage_taken+%d, deaths=deaths+%d, incaps=incaps+%d, revives_given=revives_given+%d, revives_received=revives_received+%d, heals_given=heals_given+%d, heals_received=heals_received+%d, health_restored=health_restored+%d, pills_used=pills_used+%d, adrenaline_used=adrenaline_used+%d, defibs_used=defibs_used+%d, friendly_fire_dealt=friendly_fire_dealt+%d, friendly_fire_damage=friendly_fire_damage+%d, shots_fired=shots_fired+%d, shots_hit=shots_hit+%d, melee_swings=melee_swings+%d, melee_hits=melee_hits+%d, campaigns_completed=campaigns_completed+%d, maps_completed=maps_completed+%d WHERE player_id=%d",
        stats.kills_infected, stats.kills_si, stats.kills_witch, stats.kills_tank,
        stats.headshots, stats.damage_dealt, stats.damage_taken,
        stats.deaths, stats.incaps,
        stats.revives_given, stats.revives_received,
        stats.heals_given, stats.heals_received, stats.health_restored,
        stats.pills_used, stats.adrenaline_used, stats.defibs_used,
        stats.ff_dealt, stats.ff_damage,
        stats.shots_fired, stats.shots_hit,
        stats.melee_swings, stats.melee_hits,
        stats.campaigns_completed, stats.maps_completed,
        g_iPlayerDbId[client]);

    g_hDatabase.Query(OnGenericQuery, query);

    // 重設緩衝
    PlayerStatBuffer empty;
    g_PlayerStats[client] = empty;
}

void FlushPlayerWeaponStats(int client)
{
    if (!g_bDatabaseReady || g_WeaponStats[client] == null) return;

    StringMapSnapshot snap = g_WeaponStats[client].Snapshot();
    if (snap == null) return;

    char weaponName[MAX_WEAPON_NAME];
    char escapedWeapon[MAX_WEAPON_NAME * 2];

    for (int i = 0; i < snap.Length; i++)
    {
        snap.GetKey(i, weaponName, sizeof(weaponName));

        int values[5];
        g_WeaponStats[client].GetArray(weaponName, values, sizeof(values));

        // 跳過空數據
        if (values[0] == 0 && values[1] == 0 && values[2] == 0
            && values[3] == 0 && values[4] == 0)
            continue;

        g_hDatabase.Escape(weaponName, escapedWeapon, sizeof(escapedWeapon));

        char query[768];
        FormatEx(query, sizeof(query), "INSERT INTO l4d2_player_weapon_stats (player_id, weapon_id, kills, headshots, damage_dealt, shots_fired, shots_hit) SELECT %d, w.id, %d, %d, %d, %d, %d FROM l4d2_weapons w WHERE w.weapon_name='%s' ON DUPLICATE KEY UPDATE kills=kills+VALUES(kills), headshots=headshots+VALUES(headshots), damage_dealt=damage_dealt+VALUES(damage_dealt), shots_fired=shots_fired+VALUES(shots_fired), shots_hit=shots_hit+VALUES(shots_hit)",
            g_iPlayerDbId[client],
            values[0], values[1], values[2], values[3], values[4],
            escapedWeapon);

        g_hDatabase.Query(OnGenericQuery, query);
    }

    delete snap;

    // 重建 StringMap
    delete g_WeaponStats[client];
    g_WeaponStats[client] = new StringMap();
}

void UpdatePlayerMapStats(int client)
{
    if (!g_bDatabaseReady || g_iPlayerDbId[client] <= 0 || g_iMapId <= 0) return;

    int playtime = RoundToFloor(GetGameTime() - g_fPlayerJoinTime[client]);
    if (playtime <= 0) playtime = 0;

    char query[768];
    FormatEx(query, sizeof(query), "INSERT INTO l4d2_player_map_stats (player_id, map_id, times_played, times_completed, kills, deaths, playtime) VALUES (%d, %d, 1, %d, %d, %d, %d) ON DUPLICATE KEY UPDATE times_played=times_played+1, times_completed=times_completed+VALUES(times_completed), kills=kills+VALUES(kills), deaths=deaths+VALUES(deaths), playtime=playtime+VALUES(playtime)",
        g_iPlayerDbId[client], g_iMapId,
        g_bMapCompleted ? 1 : 0,
        g_iMapKills[client], g_iMapDeaths[client],
        playtime);

    g_hDatabase.Query(OnGenericQuery, query);
}

void UpdatePlayerPlaytime(int client)
{
    if (!g_bDatabaseReady || g_iPlayerDbId[client] <= 0) return;

    int playtime = RoundToFloor(GetGameTime() - g_fPlayerJoinTime[client]);
    if (playtime <= 0) return;

    char query[256];
    FormatEx(query, sizeof(query), "UPDATE l4d2_players SET total_playtime=total_playtime+%d, total_sessions=total_sessions+1, last_seen=NOW() WHERE id=%d", playtime, g_iPlayerDbId[client]);

    g_hDatabase.Query(OnGenericQuery, query);
}
