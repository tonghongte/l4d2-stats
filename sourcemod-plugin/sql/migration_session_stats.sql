-- ============================================================
-- L4D2 Stats - 場次數據遷移腳本
-- 為現有安裝新增場次詳細數據表
-- ============================================================

USE `l4d2_stats`;

-- l4d2_session_player_stats: 每場次每位玩家的數據
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

-- l4d2_session_player_weapon_stats: 每場次每位玩家各武器數據
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
