/**
 * L4D2 Stats - 玩家/管理員指令
 */

// ============================================================
// !stats - 顯示個人戰績
// ============================================================
public Action Cmd_ShowStats(int client, int args)
{
    if (client <= 0 || !IsClientInGame(client)) return Plugin_Handled;

    if (!g_bDatabaseReady || !g_bPlayerLoaded[client])
    {
        PrintToChat(client, "\x04[戰績] \x01數據載入中，請稍後再試。");
        return Plugin_Handled;
    }

    char query[512];
    FormatEx(query, sizeof(query), "SELECT kills_infected, kills_si, kills_witch, kills_tank, headshots, deaths, incaps, revives_given, heals_given, shots_fired, shots_hit, friendly_fire_dealt, campaigns_completed, maps_completed FROM l4d2_player_stats WHERE player_id = %d", g_iPlayerDbId[client]);

    g_hDatabase.Query(OnStatsQueryResult, query, GetClientUserId(client));
    return Plugin_Handled;
}

public void OnStatsQueryResult(Database db, DBResultSet results, const char[] error, any data)
{
    int client = GetClientOfUserId(data);
    if (client <= 0 || !IsClientInGame(client)) return;

    if (results == null || !results.FetchRow())
    {
        PrintToChat(client, "\x04[戰績] \x01無法取得戰績數據。");
        return;
    }

    int kills_ci   = results.FetchInt(0);
    int kills_si   = results.FetchInt(1);
    int kills_witch = results.FetchInt(2);
    int kills_tank = results.FetchInt(3);
    int headshots  = results.FetchInt(4);
    int deaths     = results.FetchInt(5);
    int incaps     = results.FetchInt(6);
    int revives    = results.FetchInt(7);
    int heals      = results.FetchInt(8);
    int shotsFired = results.FetchInt(9);
    int shotsHit   = results.FetchInt(10);
    int ff         = results.FetchInt(11);
    int campaigns  = results.FetchInt(12);
    int maps       = results.FetchInt(13);

    // 加上目前緩衝中的數據
    kills_ci   += g_PlayerStats[client].kills_infected;
    kills_si   += g_PlayerStats[client].kills_si;
    kills_witch += g_PlayerStats[client].kills_witch;
    kills_tank += g_PlayerStats[client].kills_tank;
    headshots  += g_PlayerStats[client].headshots;
    deaths     += g_PlayerStats[client].deaths;
    incaps     += g_PlayerStats[client].incaps;
    revives    += g_PlayerStats[client].revives_given;
    heals      += g_PlayerStats[client].heals_given;
    shotsFired += g_PlayerStats[client].shots_fired;
    shotsHit   += g_PlayerStats[client].shots_hit;
    ff         += g_PlayerStats[client].ff_dealt;
    campaigns  += g_PlayerStats[client].campaigns_completed;
    maps       += g_PlayerStats[client].maps_completed;

    int totalKills = kills_ci + kills_si;
    float kdRatio = deaths > 0 ? float(totalKills) / float(deaths) : float(totalKills);
    float accuracy = shotsFired > 0 ? float(shotsHit) / float(shotsFired) * 100.0 : 0.0;

    PrintToChat(client, "\x04══════ 個人戰績 ══════");
    PrintToChat(client, "\x04[擊殺] \x01總計: \x05%d \x01| 特感: \x05%d \x01| Witch: \x05%d \x01| Tank: \x05%d",
        totalKills, kills_si, kills_witch, kills_tank);
    PrintToChat(client, "\x04[表現] \x01爆頭: \x05%d \x01| K/D: \x05%.2f \x01| 命中率: \x05%.1f%%",
        headshots, kdRatio, accuracy);
    PrintToChat(client, "\x04[團隊] \x01救援: \x05%d \x01| 治療: \x05%d \x01| 友傷: \x05%d",
        revives, heals, ff);
    PrintToChat(client, "\x04[進度] \x01戰役通關: \x05%d \x01| 地圖通關: \x05%d \x01| 死亡: \x05%d \x01| 倒地: \x05%d",
        campaigns, maps, deaths, incaps);
}

// ============================================================
// !rank - 顯示排名
// ============================================================
public Action Cmd_ShowRank(int client, int args)
{
    if (client <= 0 || !IsClientInGame(client)) return Plugin_Handled;

    if (!g_bDatabaseReady || !g_bPlayerLoaded[client])
    {
        PrintToChat(client, "\x04[戰績] \x01數據載入中，請稍後再試。");
        return Plugin_Handled;
    }

    // 查詢排名 (依總擊殺數)
    int minPlaytime = g_cvMinPlaytime.IntValue;
    int playerId = g_iPlayerDbId[client];

    char query[768];
    FormatEx(query, sizeof(query), "SELECT (SELECT COUNT(*)+1 FROM l4d2_player_stats ps2 JOIN l4d2_players p2 ON p2.id=ps2.player_id WHERE (ps2.kills_infected+ps2.kills_si)>(SELECT kills_infected+kills_si FROM l4d2_player_stats WHERE player_id=%d) AND p2.total_playtime>=%d) AS rank_pos, (SELECT COUNT(*) FROM l4d2_players WHERE total_playtime>=%d) AS total_players, ps.kills_infected+ps.kills_si AS total_kills FROM l4d2_player_stats ps WHERE ps.player_id=%d", playerId, minPlaytime, minPlaytime, playerId);

    g_hDatabase.Query(OnRankQueryResult, query, GetClientUserId(client));
    return Plugin_Handled;
}

public void OnRankQueryResult(Database db, DBResultSet results, const char[] error, any data)
{
    int client = GetClientOfUserId(data);
    if (client <= 0 || !IsClientInGame(client)) return;

    if (results == null || !results.FetchRow())
    {
        PrintToChat(client, "\x04[戰績] \x01無法取得排名數據。");
        return;
    }

    int rank = results.FetchInt(0);
    int total = results.FetchInt(1);
    int kills = results.FetchInt(2);

    PrintToChat(client, "\x04[排名] \x05%N \x01排名: \x05#%d / %d \x01| 總擊殺: \x05%d",
        client, rank, total, kills);
}

// ============================================================
// !top - 顯示排行榜
// ============================================================
public Action Cmd_ShowTop(int client, int args)
{
    if (client <= 0 || !IsClientInGame(client)) return Plugin_Handled;

    if (!g_bDatabaseReady)
    {
        PrintToChat(client, "\x04[戰績] \x01資料庫未連線。");
        return Plugin_Handled;
    }

    char query[512];
    FormatEx(query, sizeof(query), "SELECT p.name, ps.kills_infected+ps.kills_si AS total_kills, ps.headshots, ps.deaths FROM l4d2_players p JOIN l4d2_player_stats ps ON ps.player_id=p.id WHERE p.total_playtime>=%d ORDER BY total_kills DESC LIMIT 10", g_cvMinPlaytime.IntValue);

    g_hDatabase.Query(OnTopQueryResult, query, GetClientUserId(client));
    return Plugin_Handled;
}

public void OnTopQueryResult(Database db, DBResultSet results, const char[] error, any data)
{
    int client = GetClientOfUserId(data);
    if (client <= 0 || !IsClientInGame(client)) return;

    if (results == null)
    {
        PrintToChat(client, "\x04[戰績] \x01無法取得排行榜數據。");
        return;
    }

    PrintToChat(client, "\x04══════ 擊殺排行榜 Top 10 ══════");

    int rank = 0;
    while (results.FetchRow())
    {
        rank++;
        char playerName[128];
        results.FetchString(0, playerName, sizeof(playerName));
        int kills = results.FetchInt(1);
        int headshots = results.FetchInt(2);
        int deaths = results.FetchInt(3);

        float kd = deaths > 0 ? float(kills) / float(deaths) : float(kills);

        PrintToChat(client, "\x04#%d \x05%s \x01| 擊殺: \x05%d \x01| 爆頭: \x05%d \x01| K/D: \x05%.2f",
            rank, playerName, kills, headshots, kd);
    }

    if (rank == 0)
    {
        PrintToChat(client, "\x01尚無排行數據。");
    }
}

// ============================================================
// 管理員指令: 強制寫入
// ============================================================
public Action Cmd_FlushStats(int client, int args)
{
    FlushAllPlayerStats();

    if (client > 0)
    {
        PrintToChat(client, "\x04[戰績] \x01已強制寫入所有數據到資料庫。");
    }
    else
    {
        PrintToServer("[L4D2 Stats] 已強制寫入所有數據到資料庫。");
    }

    return Plugin_Handled;
}
