-- ============================================================
-- L4D2 武器預填資料
-- ============================================================

USE `l4d2_stats`;

INSERT INTO l4d2_weapons (weapon_name, display_name, weapon_type) VALUES
-- 手槍
('pistol',                      'P220 手槍',              'pistol'),
('pistol_magnum',               'Magnum 手槍',            'pistol'),

-- 衝鋒槍
('smg',                         'Uzi 衝鋒槍',             'smg'),
('smg_silenced',                '消音衝鋒槍',              'smg'),
('smg_mp5',                     'MP5',                     'smg'),

-- 散彈槍
('pumpshotgun',                 '幫浦散彈槍',              'shotgun'),
('shotgun_chrome',              '鍍鉻散彈槍',              'shotgun'),
('autoshotgun',                 '自動散彈槍',              'shotgun'),
('shotgun_spas',                'SPAS 散彈槍',            'shotgun'),

-- 突擊步槍
('rifle',                       'M16 突擊步槍',            'rifle'),
('rifle_desert',                'SCAR 沙漠步槍',           'rifle'),
('rifle_ak47',                  'AK-47',                   'rifle'),
('rifle_sg552',                 'SG 552',                  'rifle'),

-- 狙擊槍
('hunting_rifle',               '獵槍',                    'sniper'),
('sniper_military',             '軍用狙擊槍',              'sniper'),
('sniper_scout',                'Scout 狙擊槍',            'sniper'),
('sniper_awp',                  'AWP',                     'sniper'),

-- 重型武器
('grenade_launcher',            '榴彈發射器',              'heavy'),
('rifle_m60',                   'M60',                     'heavy'),
('chainsaw',                    '電鋸',                    'heavy'),

-- 近戰武器
('baseball_bat',                '棒球棒',                  'melee'),
('cricket_bat',                 '板球拍',                  'melee'),
('crowbar',                     '撬棍',                    'melee'),
('electric_guitar',             '電吉他',                  'melee'),
('fireaxe',                     '消防斧',                  'melee'),
('frying_pan',                  '平底鍋',                  'melee'),
('golfclub',                    '高爾夫球桿',              'melee'),
('katana',                      '武士刀',                  'melee'),
('machete',                     '開山刀',                  'melee'),
('tonfa',                       '警棍',                    'melee'),
('pitchfork',                   '乾草叉',                  'melee'),
('shovel',                      '鏟子',                    'melee'),
('knife',                       '小刀',                    'melee'),
('riotshield',                  '防暴盾',                  'melee'),

-- 投擲物
('pipe_bomb',                   '土製炸彈',                'throwable'),
('molotov',                     '乙醇燃燒瓶',              'throwable'),
('vomitjar',                    '膽汁罐',                  'throwable'),

-- 架設武器
('prop_minigun',                '機槍',                    'mounted'),
('prop_minigun_l4d1',           '機槍 (L4D1)',             'mounted'),
('prop_mounted_machine_gun',    '.50 口徑機槍',            'mounted'),

-- 環境/其他
('entityflame',                 '火焰 (環境)',             'other'),
('inferno',                     '火焰 (燃燒瓶)',           'other'),
('env_explosion',               '爆炸',                    'other'),
('world',                       '世界/墜落傷害',           'other'),
('prop_physics',                '物理物件',                'other')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), weapon_type = VALUES(weapon_type);
