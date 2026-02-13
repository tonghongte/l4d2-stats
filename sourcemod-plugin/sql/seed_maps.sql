-- ============================================================
-- L4D2 官方地圖預填資料
-- ============================================================

USE `l4d2_stats`;

INSERT INTO l4d2_maps (map_name, display_name, campaign_name, is_finale) VALUES
-- Dead Center (死亡中心)
('c1m1_hotel',              'The Hotel',            'Dead Center',      0),
('c1m2_streets',            'The Streets',          'Dead Center',      0),
('c1m3_mall',               'The Mall',             'Dead Center',      0),
('c1m4_atrium',             'The Atrium',           'Dead Center',      1),

-- Dark Carnival (黑色嘉年華)
('c2m1_highway',            'The Highway',          'Dark Carnival',    0),
('c2m2_fairgrounds',        'The Fairgrounds',      'Dark Carnival',    0),
('c2m3_coaster',            'The Coaster',          'Dark Carnival',    0),
('c2m4_barns',              'The Barns',            'Dark Carnival',    0),
('c2m5_concert',            'The Concert',          'Dark Carnival',    1),

-- Swamp Fever (沼澤瘟疫)
('c3m1_plankcountry',       'Plank Country',        'Swamp Fever',      0),
('c3m2_swamp',              'The Swamp',            'Swamp Fever',      0),
('c3m3_shantytown',         'Shanty Town',          'Swamp Fever',      0),
('c3m4_plantation',         'The Plantation',       'Swamp Fever',      1),

-- Hard Rain (暴風驟雨)
('c4m1_milltown_a',         'Milltown (Start)',     'Hard Rain',        0),
('c4m2_sugarmill_a',        'Sugar Mill (Going)',   'Hard Rain',        0),
('c4m3_sugarmill_b',        'Sugar Mill (Return)',  'Hard Rain',        0),
('c4m4_milltown_b',         'Milltown (Return)',    'Hard Rain',        0),
('c4m5_milltown_escape',    'Milltown Escape',      'Hard Rain',        1),

-- The Parish (教區)
('c5m1_waterfront',         'The Waterfront',       'The Parish',       0),
('c5m2_park',               'The Park',             'The Parish',       0),
('c5m3_cemetery',           'The Cemetery',         'The Parish',       0),
('c5m4_quarter',            'The Quarter',          'The Parish',       0),
('c5m5_bridge',             'The Bridge',           'The Parish',       1),

-- The Passing (短暫之時)
('c6m1_riverbank',          'The Riverbank',        'The Passing',      0),
('c6m2_bedlam',             'The Underground',      'The Passing',      0),
('c6m3_port',               'The Port',             'The Passing',      1),

-- The Sacrifice (犧牲)
('c7m1_docks',              'The Docks',            'The Sacrifice',    0),
('c7m2_barge',              'The Barge',            'The Sacrifice',    0),
('c7m3_port',               'Port Finale',          'The Sacrifice',    1),

-- No Mercy (毫不留情)
('c8m1_apartment',          'The Apartments',       'No Mercy',         0),
('c8m2_subway',             'The Subway',           'No Mercy',         0),
('c8m3_sewers',             'The Sewers',           'No Mercy',         0),
('c8m4_interior',           'The Hospital',         'No Mercy',         0),
('c8m5_rooftop',            'Rooftop Finale',       'No Mercy',         1),

-- Crash Course (墜機險途)
('c9m1_alleys',             'The Alleys',           'Crash Course',     0),
('c9m2_lots',               'The Truck Depot',      'Crash Course',     1),

-- Death Toll (死亡喪鐘)
('c10m1_caves',             'The Caves',            'Death Toll',       0),
('c10m2_drainage',          'The Drains',           'Death Toll',       0),
('c10m3_ranchhouse',        'The Church',           'Death Toll',       0),
('c10m4_mainstreet',        'The Town',             'Death Toll',       0),
('c10m5_houseboat',         'Boathouse Finale',     'Death Toll',       1),

-- Dead Air (死寂之空)
('c11m1_greenhouse',        'The Greenhouse',       'Dead Air',         0),
('c11m2_offices',           'The Crane',            'Dead Air',         0),
('c11m3_garage',            'The Construction Site', 'Dead Air',        0),
('c11m4_terminal',          'The Terminal',         'Dead Air',         0),
('c11m5_runway',            'Runway Finale',        'Dead Air',         1),

-- Blood Harvest (嗜血豐收)
('c12m1_hilltop',           'The Woods',            'Blood Harvest',    0),
('c12m2_traintunnel',       'The Tunnel',           'Blood Harvest',    0),
('c12m3_bridge',            'The Bridge',           'Blood Harvest',    0),
('c12m4_barn',              'The Train Station',    'Blood Harvest',    0),
('c12m5_cornfield',         'Farmhouse Finale',     'Blood Harvest',    1),

-- Cold Stream (冷澗溪流)
('c13m1_alpinecreek',       'Alpine Creek',         'Cold Stream',      0),
('c13m2_southpinestream',   'South Pine Stream',    'Cold Stream',      0),
('c13m3_memorialbridge',    'Memorial Bridge',      'Cold Stream',      0),
('c13m4_cutthroatcreek',    'Cut-throat Creek',     'Cold Stream',      1),

-- The Last Stand (最後戰役)
('c14m1_junkyard',          'The Junkyard',         'The Last Stand',   0),
('c14m2_lighthouse',        'Lighthouse Finale',    'The Last Stand',   1)
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), campaign_name = VALUES(campaign_name), is_finale = VALUES(is_finale);
