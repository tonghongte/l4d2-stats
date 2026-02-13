/**
 * L4D2 Stats - 場次管理
 */

// ============================================================
// 開始場次
// ============================================================
void StartSession(const char[] mapName)
{
    if (!g_bDatabaseReady) return;

    char difficulty[16];
    GetCurrentDifficulty(difficulty, sizeof(difficulty));

    g_fSessionStartTime = GetGameTime();
    g_bSessionActive = true;

    // 先取得 map_id，再建立場次
    char query[256];
    FormatEx(query, sizeof(query), "SELECT id FROM l4d2_maps WHERE map_name = '%s'", mapName);

    // 用 DataPack 傳遞 difficulty 字串
    DataPack dp = new DataPack();
    dp.WriteString(difficulty);

    g_hDatabase.Query(OnMapIdForSession, query, dp);
}

public void OnMapIdForSession(Database db, DBResultSet results, const char[] error, DataPack dp)
{
    dp.Reset();
    char difficulty[16];
    dp.ReadString(difficulty, sizeof(difficulty));
    delete dp;

    if (results == null || !results.FetchRow())
    {
        LogError("[L4D2 Stats] 無法取得地圖 ID 建立場次: %s", error);
        return;
    }

    g_iMapId = results.FetchInt(0);

    int survivorCount = GetHumanSurvivorCount();

    char query[512];
    FormatEx(query, sizeof(query), "INSERT INTO l4d2_sessions (map_id, start_time, difficulty, survivor_count) VALUES (%d, NOW(), '%s', %d)", g_iMapId, difficulty, survivorCount);

    g_hDatabase.Query(OnSessionCreated, query);
}

public void OnSessionCreated(Database db, DBResultSet results, const char[] error, any data)
{
    if (results == null)
    {
        LogError("[L4D2 Stats] 建立場次錯誤: %s", error);
        return;
    }

    g_iSessionId = results.InsertId;
    LogMessage("[L4D2 Stats] 場次開始: ID=%d", g_iSessionId);

    // 將所有已載入的玩家加入場次
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsClientInGame(i) && !IsFakeClient(i) && g_bPlayerLoaded[i])
        {
            AddPlayerToSession(i);
        }
    }
}

// ============================================================
// 結束場次
// ============================================================
void EndSession(bool completed)
{
    if (!g_bDatabaseReady || g_iSessionId <= 0) return;

    int duration = RoundToFloor(GetGameTime() - g_fSessionStartTime);
    if (duration < 0) duration = 0;

    int completedVal = completed ? 1 : 0;
    int survCount = GetHumanSurvivorCount();

    char query[256];
    FormatEx(query, sizeof(query), "UPDATE l4d2_sessions SET end_time=NOW(), duration=%d, completed=%d, survivor_count=%d WHERE id=%d", duration, completedVal, survCount, g_iSessionId);

    g_hDatabase.Query(OnGenericQuery, query);

    // 如果通關，更新地圖通關次數
    if (completed && g_iMapId > 0)
    {
        char mapQuery[256];
        FormatEx(mapQuery, sizeof(mapQuery), "UPDATE l4d2_maps SET times_completed = times_completed + 1 WHERE id = %d", g_iMapId);
        g_hDatabase.Query(OnGenericQuery, mapQuery);
    }

    // 更新所有玩家的離開時間
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsClientInGame(i) && !IsFakeClient(i) && g_bPlayerLoaded[i])
        {
            RemovePlayerFromSession(i);
        }
    }

    LogMessage("[L4D2 Stats] 場次結束: ID=%d, 通關=%d, 時長=%d秒", g_iSessionId, completed, duration);

    g_iSessionId = -1;
    g_bSessionActive = false;
}

// ============================================================
// 玩家加入/離開場次
// ============================================================
void AddPlayerToSession(int client)
{
    if (!g_bDatabaseReady || g_iSessionId <= 0 || g_iPlayerDbId[client] <= 0) return;

    char query[256];
    FormatEx(query, sizeof(query), "INSERT IGNORE INTO l4d2_session_players (session_id, player_id, join_time) VALUES (%d, %d, NOW())", g_iSessionId, g_iPlayerDbId[client]);

    g_hDatabase.Query(OnGenericQuery, query);
}

void RemovePlayerFromSession(int client)
{
    if (!g_bDatabaseReady || g_iSessionId <= 0 || g_iPlayerDbId[client] <= 0) return;

    int duration = RoundToFloor(GetGameTime() - g_fPlayerJoinTime[client]);
    if (duration < 0) duration = 0;

    char query[256];
    FormatEx(query, sizeof(query), "UPDATE l4d2_session_players SET leave_time=NOW(), duration=%d WHERE session_id=%d AND player_id=%d", duration, g_iSessionId, g_iPlayerDbId[client]);

    g_hDatabase.Query(OnGenericQuery, query);
}
