-- ============================================================
-- Migration: Add skill tracking columns
-- 追蹤來自 l4d2_skill_detect 的技巧事件
-- 執行前確認 schema.sql 已完成建立
-- ============================================================

ALTER TABLE `l4d2_player_stats`
    ADD COLUMN IF NOT EXISTS `skeets`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '射下 Hunter (skeet)',
    ADD COLUMN IF NOT EXISTS `crowns`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '一槍爆女巫 (crown)',
    ADD COLUMN IF NOT EXISTS `levels`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '平壓 Charger (level)',
    ADD COLUMN IF NOT EXISTS `deadstops`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '阻擋 Hunter 撲擊 (deadstop)',
    ADD COLUMN IF NOT EXISTS `rock_skeets`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '打落 Tank 石頭 (rock skeet)',
    ADD COLUMN IF NOT EXISTS `tongue_cuts`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '解 Smoker 舌頭 (tongue cut)',
    ADD COLUMN IF NOT EXISTS `boomer_pops`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '推爆殭屍炸彈 (boomer pop)';

ALTER TABLE `l4d2_session_player_stats`
    ADD COLUMN IF NOT EXISTS `skeets`       INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `crowns`       INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `levels`       INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `deadstops`    INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `rock_skeets`  INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `tongue_cuts`  INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `boomer_pops`  INT UNSIGNED NOT NULL DEFAULT 0;
