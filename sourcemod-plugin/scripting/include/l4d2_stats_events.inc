/**
 * L4D2 Stats - 事件處理
 */

// ============================================================
// 普通感染者擊殺 (最高頻率事件)
// ============================================================
public void Event_InfectedDeath(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int attacker = GetClientOfUserId(event.GetInt("attacker"));
    if (!IsValidHumanSurvivor(attacker) || !g_bPlayerLoaded[attacker]) return;

    bool headshot = event.GetBool("headshot");

    char weaponName[MAX_WEAPON_NAME];
    GetClientActiveWeaponName(attacker, weaponName, sizeof(weaponName));

    g_PlayerStats[attacker].kills_infected++;
    g_iMapKills[attacker]++;

    if (headshot)
    {
        g_PlayerStats[attacker].headshots++;
    }

    IncrementWeaponStat(attacker, weaponName, 1, headshot ? 1 : 0, 0, 0, 0);
}

// ============================================================
// 特殊感染者/倖存者死亡
// ============================================================
public void Event_PlayerDeath(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int victim = GetClientOfUserId(event.GetInt("userid"));
    int attacker = GetClientOfUserId(event.GetInt("attacker"));

    // 倖存者死亡
    if (IsValidHumanSurvivor(victim) && g_bPlayerLoaded[victim])
    {
        g_PlayerStats[victim].deaths++;
        g_iMapDeaths[victim]++;
    }

    // 人類倖存者擊殺特殊感染者
    if (!IsValidHumanSurvivor(attacker) || !g_bPlayerLoaded[attacker]) return;

    // 確認受害者是感染者
    if (victim <= 0 || victim > MaxClients || !IsClientInGame(victim))
        return;

    if (GetClientTeam(victim) != TEAM_INFECTED) return;

    char weaponName[MAX_WEAPON_NAME];
    event.GetString("weapon", weaponName, sizeof(weaponName));
    bool headshot = event.GetBool("headshot");

    // 辨識感染者類型
    int zombieClass = GetEntProp(victim, Prop_Send, "m_zombieClass");

    if (zombieClass == ZC_TANK)
    {
        g_PlayerStats[attacker].kills_tank++;
    }

    g_PlayerStats[attacker].kills_si++;
    g_iMapKills[attacker]++;

    if (headshot)
    {
        g_PlayerStats[attacker].headshots++;
    }

    IncrementWeaponStat(attacker, weaponName, 1, headshot ? 1 : 0, 0, 0, 0);
}

// ============================================================
// Witch 擊殺
// ============================================================
public void Event_WitchKilled(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int attacker = GetClientOfUserId(event.GetInt("userid"));
    if (!IsValidHumanSurvivor(attacker) || !g_bPlayerLoaded[attacker]) return;

    bool oneShot = event.GetBool("oneshot");

    g_PlayerStats[attacker].kills_witch++;
    g_iMapKills[attacker]++;

    char weaponName[MAX_WEAPON_NAME];
    GetClientActiveWeaponName(attacker, weaponName, sizeof(weaponName));
    IncrementWeaponStat(attacker, weaponName, 1, oneShot ? 1 : 0, 0, 0, 0);
}

// ============================================================
// 傷害追蹤
// ============================================================
public void Event_PlayerHurt(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int attacker = GetClientOfUserId(event.GetInt("attacker"));
    int victim = GetClientOfUserId(event.GetInt("userid"));
    int damage = event.GetInt("dmg_health");

    if (damage <= 0) return;

    char weaponName[MAX_WEAPON_NAME];
    event.GetString("weapon", weaponName, sizeof(weaponName));

    // 倖存者攻擊感染者 - 記錄傷害和命中
    if (IsValidHumanSurvivor(attacker) && g_bPlayerLoaded[attacker])
    {
        if (victim > 0 && victim <= MaxClients && IsClientInGame(victim)
            && GetClientTeam(victim) == TEAM_INFECTED)
        {
            g_PlayerStats[attacker].damage_dealt += damage;

            // 每次射擊最多計 1 次命中，避免霰彈槍多顆彈丸使命中率超過 100%
            if (!g_bShotHitThisFire[attacker])
            {
                g_PlayerStats[attacker].shots_hit++;
                g_bShotHitThisFire[attacker] = true;
                IncrementWeaponStat(attacker, weaponName, 0, 0, damage, 0, 1);
            }
            else
            {
                IncrementWeaponStat(attacker, weaponName, 0, 0, damage, 0, 0);
            }
        }
        // 倖存者攻擊倖存者 = 友軍傷害
        else if (victim > 0 && victim <= MaxClients && IsClientInGame(victim)
                 && GetClientTeam(victim) == TEAM_SURVIVOR && victim != attacker)
        {
            g_PlayerStats[attacker].ff_damage += damage;
        }
    }

    // 倖存者受到傷害
    if (IsValidHumanSurvivor(victim) && g_bPlayerLoaded[victim])
    {
        g_PlayerStats[victim].damage_taken += damage;
    }
}

// ============================================================
// 倒地
// ============================================================
public void Event_PlayerIncapacitated(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int victim = GetClientOfUserId(event.GetInt("userid"));
    if (!IsValidHumanSurvivor(victim) || !g_bPlayerLoaded[victim]) return;

    g_PlayerStats[victim].incaps++;
}

// ============================================================
// 救援
// ============================================================
public void Event_ReviveSuccess(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int reviver = GetClientOfUserId(event.GetInt("userid"));
    int subject = GetClientOfUserId(event.GetInt("subject"));

    if (IsValidHumanSurvivor(reviver) && g_bPlayerLoaded[reviver])
    {
        g_PlayerStats[reviver].revives_given++;
    }
    if (IsValidHumanSurvivor(subject) && g_bPlayerLoaded[subject])
    {
        g_PlayerStats[subject].revives_received++;
    }
}

// ============================================================
// 治療
// ============================================================
public void Event_HealSuccess(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int healer = GetClientOfUserId(event.GetInt("userid"));
    int subject = GetClientOfUserId(event.GetInt("subject"));
    int health = event.GetInt("health_restored");

    if (IsValidHumanSurvivor(healer) && g_bPlayerLoaded[healer])
    {
        g_PlayerStats[healer].heals_given++;
        g_PlayerStats[healer].health_restored += health;
    }
    if (IsValidHumanSurvivor(subject) && g_bPlayerLoaded[subject])
    {
        g_PlayerStats[subject].heals_received++;
    }
}

// ============================================================
// 武器射擊 (命中率追蹤)
// ============================================================
public void Event_WeaponFire(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int client = GetClientOfUserId(event.GetInt("userid"));
    if (!IsValidHumanSurvivor(client) || !g_bPlayerLoaded[client]) return;

    char weaponName[MAX_WEAPON_NAME];
    event.GetString("weapon", weaponName, sizeof(weaponName));

    // 近戰武器
    if (StrEqual(weaponName, "melee") || StrEqual(weaponName, "chainsaw"))
    {
        g_PlayerStats[client].melee_swings++;

        // 取得具體近戰武器名稱
        char meleeWeapon[MAX_WEAPON_NAME];
        GetClientActiveWeaponName(client, meleeWeapon, sizeof(meleeWeapon));
        IncrementWeaponStat(client, meleeWeapon, 0, 0, 0, 1, 0);
    }
    else
    {
        g_PlayerStats[client].shots_fired++;
        g_bShotHitThisFire[client] = false;  // 每次射擊重置命中旗標
        IncrementWeaponStat(client, weaponName, 0, 0, 0, 1, 0);
    }
}

// ============================================================
// 友軍傷害
// ============================================================
public void Event_FriendlyFire(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int attacker = GetClientOfUserId(event.GetInt("attacker"));
    if (!IsValidHumanSurvivor(attacker) || !g_bPlayerLoaded[attacker]) return;

    g_PlayerStats[attacker].ff_dealt++;
}

// ============================================================
// 道具使用
// ============================================================
public void Event_PillsUsed(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int client = GetClientOfUserId(event.GetInt("userid"));
    if (!IsValidHumanSurvivor(client) || !g_bPlayerLoaded[client]) return;

    g_PlayerStats[client].pills_used++;
}

public void Event_AdrenalineUsed(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int client = GetClientOfUserId(event.GetInt("userid"));
    if (!IsValidHumanSurvivor(client) || !g_bPlayerLoaded[client]) return;

    g_PlayerStats[client].adrenaline_used++;
}

public void Event_DefibUsed(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    int client = GetClientOfUserId(event.GetInt("userid"));
    if (!IsValidHumanSurvivor(client) || !g_bPlayerLoaded[client]) return;

    g_PlayerStats[client].defibs_used++;
}

// ============================================================
// 場次事件
// ============================================================
public void Event_RoundStart(Event event, const char[] name, bool dontBroadcast)
{
    // 回合開始時重設玩家的場次數據
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsClientInGame(i) && !IsFakeClient(i))
        {
            g_fPlayerJoinTime[i] = GetGameTime();
        }
    }
}

public void Event_MapTransition(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    g_bMapCompleted = true;

    // 所有在線玩家獲得地圖完成數
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsValidHumanSurvivor(i) && g_bPlayerLoaded[i])
        {
            g_PlayerStats[i].maps_completed++;
        }
    }

    // 寫入數據並結束場次
    FlushAllPlayerStats();
    EndSession(true);
}

public void Event_FinaleWin(Event event, const char[] name, bool dontBroadcast)
{
    if (!g_cvEnabled.BoolValue) return;

    g_bMapCompleted = true;

    // 標記場次為戰役完成
    if (g_iSessionId > 0 && g_bDatabaseReady)
    {
        char query[256];
        FormatEx(query, sizeof(query),
            "UPDATE l4d2_sessions SET campaign_completed = 1 WHERE id = %d",
            g_iSessionId);
        g_hDatabase.Query(OnGenericQuery, query);
    }

    // 所有在線玩家獲得戰役完成數
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsValidHumanSurvivor(i) && g_bPlayerLoaded[i])
        {
            g_PlayerStats[i].campaigns_completed++;
            g_PlayerStats[i].maps_completed++;
        }
    }

    // 寫入數據並結束場次（含正確時長）
    FlushAllPlayerStats();
    EndSession(true);
}

// ============================================================
// 玩家離線
// ============================================================
public void Event_PlayerDisconnect(Event event, const char[] name, bool dontBroadcast)
{
    // OnClientDisconnect 已處理數據寫入
    // 這裡不需要額外處理
}
