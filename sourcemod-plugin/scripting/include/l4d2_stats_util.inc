/**
 * L4D2 Stats - 工具函式
 */

// 取得玩家手持武器名稱 (去除 weapon_ 前綴)
void GetClientActiveWeaponName(int client, char[] buffer, int maxlen)
{
    int weapon = GetEntPropEnt(client, Prop_Send, "m_hActiveWeapon");
    if (weapon == -1)
    {
        strcopy(buffer, maxlen, "unknown");
        return;
    }

    GetEdictClassname(weapon, buffer, maxlen);

    // 去除 "weapon_" 前綴
    if (strncmp(buffer, "weapon_", 7) == 0)
    {
        char temp[MAX_WEAPON_NAME];
        strcopy(temp, sizeof(temp), buffer[7]);
        strcopy(buffer, maxlen, temp);
    }

    // 近戰武器需取得具體名稱
    if (StrEqual(buffer, "melee"))
    {
        GetEntPropString(weapon, Prop_Data, "m_strMapSetScriptName", buffer, maxlen);
        if (buffer[0] == '\0')
        {
            strcopy(buffer, maxlen, "melee");
        }
    }
}

// 取得目前難度
void GetCurrentDifficulty(char[] buffer, int maxlen)
{
    ConVar cvDifficulty = FindConVar("z_difficulty");
    if (cvDifficulty != null)
    {
        cvDifficulty.GetString(buffer, maxlen);
        // 轉小寫
        for (int i = 0; buffer[i] != '\0'; i++)
        {
            buffer[i] = CharToLower(buffer[i]);
        }
    }
    else
    {
        strcopy(buffer, maxlen, "normal");
    }
}

// 增加武器數據緩衝
// values: [kills, headshots, damage, shots_fired, shots_hit]
void IncrementWeaponStat(int client, const char[] weaponName, int kills, int headshots, int damage, int shots_fired, int shots_hit)
{
    if (g_WeaponStats[client] == null) return;
    if (weaponName[0] == '\0') return;

    int values[5];
    if (!g_WeaponStats[client].GetArray(weaponName, values, sizeof(values)))
    {
        // 不存在，初始化為 0
        values[0] = 0;
        values[1] = 0;
        values[2] = 0;
        values[3] = 0;
        values[4] = 0;
    }

    values[0] += kills;
    values[1] += headshots;
    values[2] += damage;
    values[3] += shots_fired;
    values[4] += shots_hit;

    g_WeaponStats[client].SetArray(weaponName, values, sizeof(values));
}

// 格式化遊玩時間
void FormatPlaytime(int seconds, char[] buffer, int maxlen)
{
    int hours = seconds / 3600;
    int mins = (seconds % 3600) / 60;

    if (hours > 0)
    {
        FormatEx(buffer, maxlen, "%d小時%d分鐘", hours, mins);
    }
    else
    {
        FormatEx(buffer, maxlen, "%d分鐘", mins);
    }
}

// 計算存活人類玩家數
int GetHumanSurvivorCount()
{
    int count = 0;
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsClientInGame(i) && !IsFakeClient(i) && GetClientTeam(i) == TEAM_SURVIVOR)
        {
            count++;
        }
    }
    return count;
}

// 檢查是否為有效的人類倖存者
bool IsValidHumanSurvivor(int client)
{
    return (client > 0 && client <= MaxClients
            && IsClientInGame(client)
            && !IsFakeClient(client)
            && GetClientTeam(client) == TEAM_SURVIVOR);
}

// 檢查是否為有效的人類玩家 (任何隊伍)
bool IsValidHumanPlayer(int client)
{
    return (client > 0 && client <= MaxClients
            && IsClientInGame(client)
            && !IsFakeClient(client));
}
