-- ============================================================
-- L4D2 Player Stats - Database Schema
-- 建立資料庫與所有資料表
-- ============================================================

CREATE DATABASE IF NOT EXISTS `l4d2_stats`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `l4d2_stats`;

-- ============================================================
-- l4d2_players: 玩家基本資料
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_players` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `steam_id`        VARCHAR(32)     NOT NULL COMMENT 'STEAM_X:Y:Z 格式',
    `steam_id_64`     VARCHAR(20)     NOT NULL DEFAULT '' COMMENT '76561198... 格式',
    `name`            VARCHAR(128)    NOT NULL DEFAULT 'Unknown',
    `first_seen`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `total_playtime`  INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '秒數',
    `total_sessions`  INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_steam_id` (`steam_id`),
    KEY `idx_last_seen` (`last_seen`),
    KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- l4d2_maps: 地圖資料
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_maps` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `map_name`        VARCHAR(64)     NOT NULL COMMENT '引擎名稱: c1m1_hotel',
    `display_name`    VARCHAR(128)    NOT NULL DEFAULT '',
    `campaign_name`   VARCHAR(128)    NOT NULL DEFAULT '',
    `is_finale`       TINYINT(1)      NOT NULL DEFAULT 0,
    `times_played`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `times_completed` INT UNSIGNED    NOT NULL DEFAULT 0,
    `first_played`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_played`     DATETIME        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_map_name` (`map_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- l4d2_weapons: 武器清單
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_weapons` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `weapon_name`     VARCHAR(64)     NOT NULL COMMENT '引擎 classname',
    `display_name`    VARCHAR(128)    NOT NULL DEFAULT '',
    `weapon_type`     VARCHAR(32)     NOT NULL DEFAULT 'unknown'
                      COMMENT 'pistol|smg|shotgun|rifle|sniper|heavy|melee|throwable|mounted|other',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_weapon_name` (`weapon_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- l4d2_sessions: 遊戲場次 (每張地圖一筆)
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_sessions` (
    `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `map_id`             INT UNSIGNED    NOT NULL,
    `start_time`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `end_time`           DATETIME        NULL DEFAULT NULL,
    `duration`           INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '秒數',
    `difficulty`         VARCHAR(16)     NOT NULL DEFAULT 'normal'
                         COMMENT 'easy|normal|hard|impossible',
    `completed`          TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1=倖存者通關/換圖',
    `survivor_count`     TINYINT         NOT NULL DEFAULT 0 COMMENT '最大人類玩家數',
    `campaign_completed` TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1=finale通關',
    PRIMARY KEY (`id`),
    KEY `idx_map_id` (`map_id`),
    KEY `idx_start_time` (`start_time`),
    CONSTRAINT `fk_sessions_map` FOREIGN KEY (`map_id`)
        REFERENCES `l4d2_maps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- l4d2_session_players: 場次中的玩家
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_session_players` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `session_id`      INT UNSIGNED    NOT NULL,
    `player_id`       INT UNSIGNED    NOT NULL,
    `join_time`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `leave_time`      DATETIME        NULL DEFAULT NULL,
    `duration`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '在此場次的秒數',
    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_player_id` (`player_id`),
    UNIQUE KEY `idx_session_player` (`session_id`, `player_id`),
    CONSTRAINT `fk_sp_session` FOREIGN KEY (`session_id`)
        REFERENCES `l4d2_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sp_player` FOREIGN KEY (`player_id`)
        REFERENCES `l4d2_players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- l4d2_player_stats: 玩家累計數據
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_player_stats` (
    `player_id`           INT UNSIGNED    NOT NULL,
    `kills_infected`      INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '普通感染者擊殺',
    `kills_si`            INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '特殊感染者擊殺',
    `kills_witch`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `kills_tank`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `headshots`           INT UNSIGNED    NOT NULL DEFAULT 0,
    `damage_dealt`        BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '對感染者總傷害',
    `damage_taken`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `deaths`              INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '完全死亡(非倒地)',
    `incaps`              INT UNSIGNED    NOT NULL DEFAULT 0,
    `revives_given`       INT UNSIGNED    NOT NULL DEFAULT 0,
    `revives_received`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `heals_given`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `heals_received`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `health_restored`     INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '治療他人的總HP',
    `pills_used`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `adrenaline_used`     INT UNSIGNED    NOT NULL DEFAULT 0,
    `defibs_used`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `friendly_fire_dealt` INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '友傷次數',
    `friendly_fire_damage`INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '友傷總傷害',
    `shots_fired`         BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `shots_hit`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `melee_swings`        INT UNSIGNED    NOT NULL DEFAULT 0,
    `melee_hits`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `campaigns_completed` INT UNSIGNED    NOT NULL DEFAULT 0,
    `maps_completed`      INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (`player_id`),
    CONSTRAINT `fk_pstats_player` FOREIGN KEY (`player_id`)
        REFERENCES `l4d2_players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- l4d2_player_weapon_stats: 每位玩家各武器累計數據
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_player_weapon_stats` (
    `player_id`       INT UNSIGNED    NOT NULL,
    `weapon_id`       INT UNSIGNED    NOT NULL,
    `kills`           INT UNSIGNED    NOT NULL DEFAULT 0,
    `headshots`       INT UNSIGNED    NOT NULL DEFAULT 0,
    `damage_dealt`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `shots_fired`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `shots_hit`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`player_id`, `weapon_id`),
    KEY `idx_weapon_id` (`weapon_id`),
    CONSTRAINT `fk_pws_player` FOREIGN KEY (`player_id`)
        REFERENCES `l4d2_players` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pws_weapon` FOREIGN KEY (`weapon_id`)
        REFERENCES `l4d2_weapons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- l4d2_player_map_stats: 每位玩家各地圖累計數據
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_player_map_stats` (
    `player_id`       INT UNSIGNED    NOT NULL,
    `map_id`          INT UNSIGNED    NOT NULL,
    `times_played`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `times_completed` INT UNSIGNED    NOT NULL DEFAULT 0,
    `kills`           INT UNSIGNED    NOT NULL DEFAULT 0,
    `deaths`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `playtime`        INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT '在此地圖的總秒數',
    PRIMARY KEY (`player_id`, `map_id`),
    KEY `idx_map_id` (`map_id`),
    CONSTRAINT `fk_pms_player` FOREIGN KEY (`player_id`)
        REFERENCES `l4d2_players` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pms_map` FOREIGN KEY (`map_id`)
        REFERENCES `l4d2_maps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- l4d2_session_player_stats: 每場次每位玩家的數據
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_session_player_stats` (
    `session_id`          INT UNSIGNED    NOT NULL,
    `player_id`           INT UNSIGNED    NOT NULL,
    `kills_infected`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `kills_si`            INT UNSIGNED    NOT NULL DEFAULT 0,
    `kills_witch`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `kills_tank`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `headshots`           INT UNSIGNED    NOT NULL DEFAULT 0,
    `damage_dealt`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `damage_taken`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `deaths`              INT UNSIGNED    NOT NULL DEFAULT 0,
    `incaps`              INT UNSIGNED    NOT NULL DEFAULT 0,
    `revives_given`       INT UNSIGNED    NOT NULL DEFAULT 0,
    `revives_received`    INT UNSIGNED    NOT NULL DEFAULT 0,
    `heals_given`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `heals_received`      INT UNSIGNED    NOT NULL DEFAULT 0,
    `health_restored`     INT UNSIGNED    NOT NULL DEFAULT 0,
    `pills_used`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `adrenaline_used`     INT UNSIGNED    NOT NULL DEFAULT 0,
    `defibs_used`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `friendly_fire_dealt` INT UNSIGNED    NOT NULL DEFAULT 0,
    `friendly_fire_damage`INT UNSIGNED    NOT NULL DEFAULT 0,
    `shots_fired`         BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `shots_hit`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `melee_swings`        INT UNSIGNED    NOT NULL DEFAULT 0,
    `melee_hits`          INT UNSIGNED    NOT NULL DEFAULT 0,
    PRIMARY KEY (`session_id`, `player_id`),
    KEY `idx_sps_player_id` (`player_id`),
    CONSTRAINT `fk_sps_session` FOREIGN KEY (`session_id`)
        REFERENCES `l4d2_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sps_player` FOREIGN KEY (`player_id`)
        REFERENCES `l4d2_players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- l4d2_session_player_weapon_stats: 每場次每位玩家各武器數據
-- ============================================================
CREATE TABLE IF NOT EXISTS `l4d2_session_player_weapon_stats` (
    `session_id`      INT UNSIGNED    NOT NULL,
    `player_id`       INT UNSIGNED    NOT NULL,
    `weapon_id`       INT UNSIGNED    NOT NULL,
    `kills`           INT UNSIGNED    NOT NULL DEFAULT 0,
    `headshots`       INT UNSIGNED    NOT NULL DEFAULT 0,
    `damage_dealt`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `shots_fired`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `shots_hit`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`session_id`, `player_id`, `weapon_id`),
    KEY `idx_spws_session_player` (`session_id`, `player_id`),
    KEY `idx_spws_weapon_id` (`weapon_id`),
    CONSTRAINT `fk_spws_session` FOREIGN KEY (`session_id`)
        REFERENCES `l4d2_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spws_player` FOREIGN KEY (`player_id`)
        REFERENCES `l4d2_players` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_spws_weapon` FOREIGN KEY (`weapon_id`)
        REFERENCES `l4d2_weapons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
