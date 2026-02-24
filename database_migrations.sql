-- database_migrations.sql
-- Run this script in your MySQL instance connected to the `quiz_db` database

-- 1. Rename 'teachers' to 'users' and update columns
-- Since we already renamed it in the previous failed step, 'users' already exists. We should check if we need to rename it first.
-- Let's assume it has been renamed, so we will just work with `users`
-- RENAME TABLE `teachers` TO `users`; -- commented out to avoid errors if already renamed

-- Fix data truncation issue before altering column:
UPDATE `users` SET `level_access` = 'SD' WHERE `level_access` = 'SD,SMP,SMA'; 
-- Then change the column
ALTER TABLE `users`
CHANGE COLUMN `level_access` `level` ENUM('sd', 'smp', 'sma', 'all') NOT NULL DEFAULT 'sd',
ADD COLUMN `role` ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'teacher';

-- After changing, set the admin back to 'all' level and 'admin' role
UPDATE `users` SET `level` = 'all', `role` = 'admin' WHERE `id` = 1;


-- 2. Updates to current 'packages' table
ALTER TABLE `packages`
ADD COLUMN `target_access` ENUM('guest', 'internal', 'both') NOT NULL DEFAULT 'both',
ADD COLUMN `target_level` ENUM('sd', 'smp', 'sma', 'all') NOT NULL DEFAULT 'all',
ADD COLUMN `timer_type` ENUM('none', 'per_packet', 'per_question') NOT NULL DEFAULT 'none';

-- 3. Updates to current 'questions' table
ALTER TABLE `questions`
ADD COLUMN `image_url` TEXT NULL,
ADD COLUMN `explanation` TEXT NULL;

-- 4. NEW TABLE: quiz_sessions
CREATE TABLE IF NOT EXISTS `quiz_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `package_id` INT NOT NULL,
  `session_token` VARCHAR(255) NOT NULL,
  `current_question_id` INT NULL,
  `start_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` ENUM('active', 'completed', 'timeout') NOT NULL DEFAULT 'active',
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE
  -- If 'users' id is strictly enforced: FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. NEW TABLE: answer_logs
CREATE TABLE IF NOT EXISTS `answer_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `selected_option` CHAR(1) NULL,
  `is_correct` TINYINT(1) DEFAULT 0,
  `answered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
