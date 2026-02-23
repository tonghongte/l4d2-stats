<?php
namespace L4D2Stats;

/**
 * 武器統計
 */
class Weapons {
    public static function render($atts) {
        $atts = shortcode_atts([
            'type' => '',
        ], $atts);

        $db = Database::instance();

        $where = '';
        $params = [];
        if (!empty($atts['type'])) {
            $where = 'WHERE w.weapon_type = %s';
            $params[] = sanitize_text_field($atts['type']);
        }

        $sql = "
            SELECT
                w.display_name, w.weapon_name, w.weapon_type,
                COALESCE(SUM(pws.kills), 0) AS total_kills,
                COALESCE(SUM(pws.headshots), 0) AS total_headshots,
                COALESCE(SUM(pws.damage_dealt), 0) AS total_damage,
                COALESCE(SUM(pws.shots_fired), 0) AS total_shots_fired,
                COALESCE(SUM(pws.shots_hit), 0) AS total_shots_hit,
                CASE WHEN SUM(pws.shots_fired) > 0
                     THEN ROUND(SUM(pws.shots_hit) / SUM(pws.shots_fired) * 100, 1)
                     ELSE 0
                END AS avg_accuracy,
                COUNT(DISTINCT pws.player_id) AS users_count
            FROM l4d2_weapons w
            LEFT JOIN l4d2_player_weapon_stats pws ON pws.weapon_id = w.id
            {$where}
            GROUP BY w.id
            HAVING total_kills > 0
            ORDER BY total_kills DESC
        ";

        $weapons = $db->query($sql, $params, 'weapons_' . ($atts['type'] ?: 'all'), 120);

        // 準備圖表數據
        $chart_labels = [];
        $chart_kills = [];
        foreach (array_slice($weapons, 0, 15) as $w) {
            $chart_labels[] = $w->display_name;
            $chart_kills[] = (int)$w->total_kills;
        }

        // 武器類型中文對照
        $type_labels = [
            'pistol'    => '手槍',
            'smg'       => '衝鋒槍',
            'shotgun'   => '散彈槍',
            'rifle'     => '突擊步槍',
            'sniper'    => '狙擊槍',
            'heavy'     => '重型武器',
            'melee'     => '近戰武器',
            'throwable' => '投擲物',
            'mounted'   => '架設武器',
            'other'     => '其他',
        ];

        // 武器圖示 (L4D2 Wiki)
        $weapon_icons = [
            'pistol'                   => 'https://static.wikia.nocookie.net/left4dead/images/0/0c/Pistolw_2.png',
            'pistol_magnum'            => 'https://static.wikia.nocookie.net/left4dead/images/8/8c/Deaglew_2.png',
            'smg'                      => 'https://static.wikia.nocookie.net/left4dead/images/c/c7/Smgw_2.png',
            'smg_silenced'             => 'https://static.wikia.nocookie.net/left4dead/images/c/c7/Smgw_2.png',
            'smg_mp5'                  => 'https://static.wikia.nocookie.net/left4dead/images/d/d5/MP5.png',
            'pumpshotgun'              => 'https://static.wikia.nocookie.net/left4dead/images/3/3a/Pumpw_2.png',
            'shotgun_chrome'           => 'https://static.wikia.nocookie.net/left4dead/images/d/d5/Chromew_2.png',
            'autoshotgun'              => 'https://static.wikia.nocookie.net/left4dead/images/0/09/Auto_1.png',
            'shotgun_spas'             => 'https://static.wikia.nocookie.net/left4dead/images/b/b1/Spas_12_New.png',
            'rifle'                    => 'https://static.wikia.nocookie.net/left4dead/images/0/0d/M16_Assault_Rifle_New.png',
            'rifle_desert'             => 'https://static.wikia.nocookie.net/left4dead/images/4/49/Scarw_2.png',
            'rifle_ak47'               => 'https://static.wikia.nocookie.net/left4dead/images/9/94/Akw_2.png',
            'rifle_sg552'              => 'https://static.wikia.nocookie.net/left4dead/images/c/c3/SG-552.png',
            'hunting_rifle'            => 'https://static.wikia.nocookie.net/left4dead/images/6/6f/Sniper_1.png',
            'sniper_military'          => 'https://static.wikia.nocookie.net/left4dead/images/c/cf/Sniper_Rifle.png',
            'sniper_scout'             => 'https://static.wikia.nocookie.net/left4dead/images/6/69/Scout.png',
            'sniper_awp'               => 'https://static.wikia.nocookie.net/left4dead/images/9/99/AWSM.png',
            'grenade_launcher'         => 'https://static.wikia.nocookie.net/left4dead/images/5/53/Grenade_Launcher_New.png',
            'rifle_m60'                => 'https://static.wikia.nocookie.net/left4dead/images/9/98/M60_Machine_Gun_New.png',
            'chainsaw'                 => 'https://static.wikia.nocookie.net/left4dead/images/c/cc/Chainsaw.png',
            'baseball_bat'             => 'https://static.wikia.nocookie.net/left4dead/images/a/ad/Bat.png',
            'cricket_bat'              => 'https://static.wikia.nocookie.net/left4dead/images/3/3d/Cricket.png',
            'crowbar'                  => 'https://static.wikia.nocookie.net/left4dead/images/1/18/Crowbar2.png',
            'electric_guitar'          => 'https://static.wikia.nocookie.net/left4dead/images/1/11/Electric_Guitar_Icon.png',
            'fireaxe'                  => 'https://static.wikia.nocookie.net/left4dead/images/a/ab/Fireaxe.png',
            'frying_pan'               => 'https://static.wikia.nocookie.net/left4dead/images/c/ca/Pan.png',
            'golfclub'                 => 'https://static.wikia.nocookie.net/left4dead/images/0/0c/GolfClub.png',
            'katana'                   => 'https://static.wikia.nocookie.net/left4dead/images/e/e9/Katana.png',
            'machete'                  => 'https://static.wikia.nocookie.net/left4dead/images/0/0c/Machete.png',
            'tonfa'                    => 'https://static.wikia.nocookie.net/left4dead/images/e/ef/Tonfa.png',
            'pitchfork'                => 'https://static.wikia.nocookie.net/left4dead/images/9/9f/Pitchfork_Worldmodel.png',
            'shovel'                   => 'https://static.wikia.nocookie.net/left4dead/images/d/dd/Shovel_Worldmodel.png',
            'knife'                    => 'https://static.wikia.nocookie.net/left4dead/images/5/58/Weapon_Knife.png',
            'pipe_bomb'                => 'https://static.wikia.nocookie.net/left4dead/images/9/96/Pipe_1.png',
            'molotov'                  => 'https://static.wikia.nocookie.net/left4dead/images/e/ea/Molotov-1.png',
            'vomitjar'                 => 'https://static.wikia.nocookie.net/left4dead/images/d/dd/Bilebomb-bggrey.png',
            'prop_minigun'             => 'https://static.wikia.nocookie.net/left4dead/images/a/a0/Minigun_1.png',
            'prop_minigun_l4d1'        => 'https://static.wikia.nocookie.net/left4dead/images/a/a0/Minigun_1.png',
            'prop_mounted_machine_gun' => 'https://static.wikia.nocookie.net/left4dead/images/a/a0/Minigun_1.png',
        ];

        ob_start();
        include L4D2_STATS_DIR . 'templates/weapons.php';
        return ob_get_clean();
    }
}
