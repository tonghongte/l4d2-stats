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
            'pistol'                   => 'https://static.wikia.nocookie.net/left4dead/images/0/0c/Pistolw_2.png/revision/latest?cb=20250624221517',
            'pistol_magnum'            => 'https://static.wikia.nocookie.net/left4dead/images/8/8c/Deaglew_2.png/revision/latest?cb=20101021023849',
            'smg'                      => 'https://static.wikia.nocookie.net/left4dead/images/c/c7/Smgw_2.png/revision/latest?cb=20101021022710',
            'smg_silenced'             => 'https://static.wikia.nocookie.net/left4dead/images/c/c7/Smgw_2.png/revision/latest?cb=20101021022710',
            'smg_mp5'                  => 'https://static.wikia.nocookie.net/left4dead/images/d/d5/MP5.png/revision/latest?cb=20250528113058',
            'pumpshotgun'              => 'https://static.wikia.nocookie.net/left4dead/images/3/3a/Pumpw_2.png/revision/latest?cb=20101021023902',
            'shotgun_chrome'           => 'https://static.wikia.nocookie.net/left4dead/images/d/d5/Chromew_2.png/revision/latest?cb=20101021022708',
            'autoshotgun'              => 'https://static.wikia.nocookie.net/left4dead/images/0/09/Auto_1.png/revision/latest?cb=20101021021751',
            'shotgun_spas'             => 'https://static.wikia.nocookie.net/left4dead/images/b/b1/Spas_12_New.png/revision/latest?cb=20201107184615',
            'rifle'                    => 'https://static.wikia.nocookie.net/left4dead/images/0/0d/M16_Assault_Rifle_New.png/revision/latest?cb=20201107185438',
            'rifle_desert'             => 'https://static.wikia.nocookie.net/left4dead/images/4/49/Scarw_2.png/revision/latest?cb=20101021022718',
            'rifle_ak47'               => 'https://static.wikia.nocookie.net/left4dead/images/9/94/Akw_2.png/revision/latest?cb=20101021022719',
            'rifle_sg552'              => 'https://static.wikia.nocookie.net/left4dead/images/c/c3/SG-552.png/revision/latest?cb=20250528113449',
            'hunting_rifle'            => 'https://static.wikia.nocookie.net/left4dead/images/6/6f/Sniper_1.png',
            'sniper_military'          => 'https://static.wikia.nocookie.net/left4dead/images/c/cf/Sniper_Rifle.png/revision/latest?cb=20201107183256',
            'sniper_scout'             => 'https://static.wikia.nocookie.net/left4dead/images/6/69/Scout.png/revision/latest?cb=20250528111835',
            'sniper_awp'               => 'https://static.wikia.nocookie.net/left4dead/images/9/99/AWSM.png/revision/latest?cb=20250528112602',
            'grenade_launcher'         => 'https://static.wikia.nocookie.net/left4dead/images/5/53/Grenade_Launcher_New.png/revision/latest?cb=20201107191234',
            'rifle_m60'                => 'https://static.wikia.nocookie.net/left4dead/images/9/98/M60_Machine_Gun_New.png/revision/latest?cb=20201107191640',
            'chainsaw'                 => 'https://static.wikia.nocookie.net/left4dead/images/c/cc/Chainsaw.png/revision/latest?cb=20101021024714',
            'baseball_bat'             => 'https://static.wikia.nocookie.net/left4dead/images/a/ad/Bat.png/revision/latest?cb=20101021024703',
            'cricket_bat'              => 'https://static.wikia.nocookie.net/left4dead/images/3/3d/Cricket.png/revision/latest?cb=20101021024718',
            'crowbar'                  => 'https://static.wikia.nocookie.net/left4dead/images/1/18/Crowbar2.png',
            'electric_guitar'          => 'https://static.wikia.nocookie.net/left4dead/images/1/11/Electric_Guitar_Icon.png/revision/latest?cb=20201107153539',
            'fireaxe'                  => 'https://static.wikia.nocookie.net/left4dead/images/a/ab/Fireaxe.png/revision/latest?cb=20201107144808',
            'frying_pan'               => 'https://static.wikia.nocookie.net/left4dead/images/c/ca/Pan.png/revision/latest?cb=20101021024724',
            'golfclub'                 => 'https://static.wikia.nocookie.net/left4dead/images/0/0c/GolfClub.png/revision/latest?cb=20100424134037',
            'katana'                   => 'https://static.wikia.nocookie.net/left4dead/images/e/e9/Katana.png/revision/latest?cb=20101023225052',
            'machete'                  => 'https://static.wikia.nocookie.net/left4dead/images/0/0c/Machete.png/revision/latest?cb=20101021031134',
            'tonfa'                    => 'https://static.wikia.nocookie.net/left4dead/images/e/ef/Tonfa.png/revision/latest?cb=20101021025719',
            'pitchfork'                => 'https://static.wikia.nocookie.net/left4dead/images/9/9f/Pitchfork_Worldmodel.png/revision/latest?cb=20201106072015',
            'shovel'                   => 'https://static.wikia.nocookie.net/left4dead/images/d/dd/Shovel_Worldmodel.png/revision/latest?cb=20201106072734',
            'knife'                    => 'https://static.wikia.nocookie.net/left4dead/images/5/58/Weapon_Knife.png/revision/latest?cb=20210413091011',
            'pipe_bomb'                => 'https://static.wikia.nocookie.net/left4dead/images/9/96/Pipe_1.png/revision/latest?cb=20240729101753',
            'molotov'                  => 'https://static.wikia.nocookie.net/left4dead/images/e/ea/Molotov-1.png/revision/latest?cb=20230620105802',
            'vomitjar'                 => 'https://static.wikia.nocookie.net/left4dead/images/d/dd/Bilebomb-bggrey.png/revision/latest?cb=20211017224556',
            'prop_minigun'             => 'https://static.wikia.nocookie.net/left4dead/images/a/a0/Minigun_1.png/revision/latest?cb=20240517021844',
            'prop_minigun_l4d1'        => 'https://static.wikia.nocookie.net/left4dead/images/a/a0/Minigun_1.png/revision/latest?cb=20240517021844',
            'prop_mounted_machine_gun' => 'https://static.wikia.nocookie.net/left4dead/images/a/a0/Minigun_1.png/revision/latest?cb=20240517021844',
        ];

        ob_start();
        include L4D2_STATS_DIR . 'templates/weapons.php';
        return ob_get_clean();
    }
}
