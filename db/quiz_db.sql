-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for quiz_db
CREATE DATABASE IF NOT EXISTS `quiz_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `quiz_db`;

-- Dumping structure for table quiz_db.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table quiz_db.categories: ~0 rows (approximately)
INSERT INTO `categories` (`id`, `name`) VALUES
	(1, 'Matematika'),
	(2, 'Bahasa Indonesia'),
	(3, 'Pendidikan Pancasila'),
	(4, 'Bahasa Inggris');

-- Dumping structure for table quiz_db.packages
CREATE TABLE IF NOT EXISTS `packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `packages_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table quiz_db.packages: ~0 rows (approximately)
INSERT INTO `packages` (`id`, `category_id`, `name`, `description`) VALUES
	(1, 1, 'MTK PAKET A', ''),
	(2, 1, 'MTK PAKET B', ''),
	(3, 1, 'MTK PAKET C', ''),
	(4, 1, 'MTK PAKET D', ''),
	(5, 2, 'B.INDO PAKET A', ''),
	(6, 2, 'B.INDO PAKET B', ''),
	(7, 2, 'B.INDO PAKET C', ''),
	(8, 4, 'ENG PAKET A', ''),
	(9, 4, 'ENG PAKET B', ''),
	(10, 4, 'ENG PAKET C', '');

-- Dumping structure for table quiz_db.questions
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `package_id` int NOT NULL,
  `question_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `option_a` text COLLATE utf8mb4_general_ci NOT NULL,
  `option_b` text COLLATE utf8mb4_general_ci NOT NULL,
  `option_c` text COLLATE utf8mb4_general_ci NOT NULL,
  `option_d` text COLLATE utf8mb4_general_ci NOT NULL,
  `correct_option` char(1) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table quiz_db.questions: ~0 rows (approximately)
INSERT INTO `questions` (`id`, `category_id`, `package_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `sort_order`) VALUES
	(2, 1, 1, '50 - 25 =', '50', '12', '25', '5', 'C', 2),
	(3, 1, 1, '12 x 3 =', '32', '36', '42', '48', 'B', 1),
	(4, 1, 2, 'Jika x + 5 = 10, maka x adalah...', '2', '3', '5', '10', 'C', 1),
	(5, 2, 5, 'Lawan kata dari "Rajin" adalah...', 'Malas', 'Pandai', 'Giat', 'Cerdas', 'A', 1),
	(6, 2, 5, 'Ibu pergi ke pasar membeli sayur. Subjek kalimat tersebut adalah...', 'Pasar', 'Sayur', 'Ibu', 'Membeli', 'C', 2),
	(7, 4, 8, 'English for "Buku" is...', 'Pen', 'Book', 'Ruler', 'Eraser', 'B', 1);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
