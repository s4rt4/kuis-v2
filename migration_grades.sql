-- Migration: Add remedial columns to quiz_sessions
ALTER TABLE `quiz_sessions`
  ADD COLUMN IF NOT EXISTS `is_remedial` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = input remedial manual oleh guru',
  ADD COLUMN IF NOT EXISTS `remedial_note` VARCHAR(255) NULL COMMENT 'Catatan remedial';
