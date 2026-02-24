-- fix_quiz_sessions.sql

DROP TABLE IF EXISTS `answer_logs`;
DROP TABLE IF EXISTS `quiz_sessions`;

CREATE TABLE `quiz_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL, 
  `package_id` INT UNSIGNED NOT NULL, -- MUST MATCH packages.id type exactly!
  `player_name` VARCHAR(100) NULL,
  `session_token` VARCHAR(255) NULL, 
  `current_question_id` INT NULL,  
  
  `score` INT DEFAULT 0,
  `total_q` INT DEFAULT 0,
  `correct` INT DEFAULT 0,
  `wrong` INT DEFAULT 0,
  `duration_sec` INT DEFAULT 0,
  
  `start_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` ENUM('active', 'completed', 'timeout') NOT NULL DEFAULT 'active',
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `answer_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `question_id` INT UNSIGNED NOT NULL, -- questions.id is also unsigned
  `selected_option` CHAR(1) NULL,
  `is_correct` TINYINT(1) DEFAULT 0,
  `answered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
