-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Apr 06, 2026 at 07:51 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tour_company`
--

-- --------------------------------------------------------

--
-- Table structure for table `airlines`
--

CREATE TABLE `airlines` (
  `id` int NOT NULL,
  `iata` char(2) DEFAULT NULL,
  `icao` char(3) DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `country_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `airlines`
--

INSERT INTO `airlines` (`id`, `iata`, `icao`, `name`, `country_id`) VALUES
(1, 'TG', 'THA', 'Thai Airways', 1),
(2, 'FD', 'AIQ', 'Thai AirAsia', 1),
(3, 'SL', 'TLM', 'Thai Lion Air', 1),
(4, 'SQ', 'SIA', 'Singapore Airlines', 8),
(5, 'MH', 'MAS', 'Malaysia Airlines', 9),
(6, 'VN', 'HVN', 'Vietnam Airlines', 10),
(7, 'GA', 'GIA', 'Garuda Indonesia', 13),
(8, 'JL', 'JAL', 'Japan Airlines', 2),
(9, 'NH', 'ANA', 'All Nippon Airways', 2),
(10, 'QF', 'QFA', 'Qantas', 14),
(11, 'EK', 'UAE', 'Emirates', 19),
(12, 'BA', 'BAW', 'British Airways', 5),
(13, 'AF', 'AFR', 'Air France', 3),
(14, 'LH', 'DLH', 'Lufthansa', 15),
(15, 'DL', 'DAL', 'Delta Air Lines', 4),
(16, 'UA', 'UAL', 'United Airlines', 4),
(17, 'AA', 'AAL', 'American Airlines', 4),
(18, 'KE', 'KAL', 'Korean Air', 7),
(19, 'OZ', 'AAR', 'Asiana Airlines', 7),
(20, 'AI', 'AIC', 'Air India', 20),
(21, '6E', 'IGO', 'IndiGo', 20);

-- --------------------------------------------------------

--
-- Table structure for table `airports`
--

CREATE TABLE `airports` (
  `id` int NOT NULL,
  `iata` char(3) DEFAULT NULL,
  `icao` char(4) DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `city_id` int DEFAULT NULL,
  `country_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `airports`
--

INSERT INTO `airports` (`id`, `iata`, `icao`, `name`, `city_id`, `country_id`) VALUES
(1, 'BKK', 'VTBS', 'Suvarnabhumi Airport', 1, 1),
(2, 'DMK', 'VTBD', 'Don Mueang International Airport', 1, 1),
(3, 'HKT', 'VTSP', 'Phuket International Airport', 3, 1),
(4, 'CNX', 'VTCC', 'Chiang Mai International Airport', 2, 1),
(5, 'HND', 'RJTT', 'Tokyo Haneda Airport', 4, 2),
(6, 'NRT', 'RJAA', 'Narita International Airport', 4, 2),
(7, 'KIX', 'RJBB', 'Kansai International Airport', 5, 2),
(8, 'CDG', 'LFPG', 'Charles de Gaulle Airport', 6, 3),
(9, 'ORY', 'LFPO', 'Paris Orly Airport', 6, 3),
(10, 'LHR', 'EGLL', 'London Heathrow Airport', 10, 5),
(11, 'LGW', 'EGKK', 'London Gatwick Airport', 10, 5),
(12, 'FRA', 'EDDF', 'Frankfurt Airport', 26, 15),
(13, 'MUC', 'EDDM', 'Munich Airport', 27, 15),
(14, 'SIN', 'WSSS', 'Singapore Changi Airport', 16, 8),
(15, 'KUL', 'WMKK', 'Kuala Lumpur International Airport', 17, 9),
(16, 'HAN', 'VVNB', 'Noi Bai International Airport', 18, 10),
(17, 'SGN', 'VVTS', 'Tan Son Nhat International Airport', 19, 10),
(18, 'VTE', 'VLVT', 'Wattay International Airport', 20, 11),
(19, 'PNH', 'VDPP', 'Phnom Penh International Airport', 21, 12),
(20, 'CGK', 'WIII', 'Soekarno–Hatta International Airport', 22, 13),
(21, 'DPS', 'WADD', 'Ngurah Rai International Airport', 23, 13),
(22, 'SYD', 'YSSY', 'Sydney Kingsford Smith Airport', 24, 14),
(23, 'MEL', 'YMML', 'Melbourne Airport', 25, 14),
(24, 'DXB', 'OMDB', 'Dubai International Airport', 33, 19),
(25, 'AUH', 'OMAA', 'Abu Dhabi International Airport', 34, 19),
(26, 'DEL', 'VIDP', 'Indira Gandhi International Airport', 35, 20),
(27, 'BOM', 'VABB', 'Chhatrapati Shivaji Maharaj International Airport', 36, 20),
(28, 'ICN', 'RKSI', 'Incheon International Airport', 14, 7),
(29, 'GMP', 'RKSS', 'Gimpo International Airport', 14, 7),
(30, 'JFK', 'KJFK', 'John F. Kennedy International Airport', 8, 4),
(31, 'LAX', 'KLAX', 'Los Angeles International Airport', 9, 4);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint NOT NULL,
  `action` varchar(100) NOT NULL,
  `actor_id` int DEFAULT NULL,
  `actor_role` enum('director','manager','officer','non-admin','guest') DEFAULT NULL,
  `target_table` varchar(100) DEFAULT NULL,
  `target_id` varchar(100) DEFAULT NULL,
  `details` json DEFAULT NULL,
  `ip` varbinary(16) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `action`, `actor_id`, `actor_role`, `target_table`, `target_id`, `details`, `ip`, `user_agent`, `created_at`) VALUES
(1, 'post_create', 1, 'director', 'posts', '5', '{\"by\": \"test\", \"title\": \"test1\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 09:59:50'),
(2, 'post_create', 3, 'officer', 'posts', '6', '{\"by\": \"ประภาภรณ์ อมรสิงห์\", \"title\": \"test2\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:00:31'),
(3, 'change_role', 1, 'director', 'users', '3', '{\"new_role\": \"manager\", \"old_role\": \"officer\", \"target_name\": \"ประภาภรณ์ อมรสิงห์\", \"target_email\": \"test2@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:01:10'),
(4, 'change_role', 1, 'director', 'users', '3', '{\"new_role\": \"officer\", \"old_role\": \"manager\", \"target_name\": \"ประภาภรณ์ อมรสิงห์\", \"target_email\": \"test2@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:01:26'),
(5, 'post_edit', 1, 'director', 'posts', '6', '{\"by\": \"test\", \"title\": \"test2333\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:05:25'),
(6, 'change_role', 1, 'director', 'users', '1', '{\"new_role\": \"officer\", \"old_role\": \"director\", \"target_name\": \"test\", \"target_email\": \"test@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:05:56'),
(7, 'change_role', 1, 'director', 'users', '1', '{\"new_role\": \"director\", \"old_role\": \"officer\", \"target_name\": \"test\", \"target_email\": \"test@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:05:59'),
(8, 'change_role', 1, 'director', 'users', '3', '{\"new_role\": \"non-admin\", \"old_role\": \"officer\", \"target_name\": \"ประภาภรณ์ อมรสิงห์\", \"target_email\": \"test2@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:06:01'),
(9, 'change_role', 1, 'director', 'users', '3', '{\"new_role\": \"director\", \"old_role\": \"non-admin\", \"target_name\": \"ประภาภรณ์ อมรสิงห์\", \"target_email\": \"test2@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:06:04'),
(10, 'change_role', 1, 'director', 'users', '3', '{\"new_role\": \"manager\", \"old_role\": \"director\", \"target_name\": \"ประภาภรณ์ อมรสิงห์\", \"target_email\": \"test2@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:06:06'),
(11, 'change_role', 1, 'director', 'users', '3', '{\"new_role\": \"officer\", \"old_role\": \"manager\", \"target_name\": \"ประภาภรณ์ อมรสิงห์\", \"target_email\": \"test2@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:06:08'),
(12, 'post_delete', 1, 'director', 'posts', '6', '{\"by\": \"test\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:08:07'),
(13, 'change_role', 1, 'director', 'users', '3', '{\"new_role\": \"non-admin\", \"old_role\": \"officer\", \"target_name\": \"ประภาภรณ์ อมรสิงห์\", \"target_email\": \"test2@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:50:15'),
(14, 'change_role', 1, 'director', 'users', '3', '{\"new_role\": \"officer\", \"old_role\": \"non-admin\", \"target_name\": \"ประภาภรณ์ อมรสิงห์\", \"target_email\": \"test2@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:50:17'),
(15, 'post_create', 1, 'director', 'posts', '7', '{\"by\": \"test\", \"title\": \"zdsg\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:50:29'),
(16, 'post_delete', 1, 'director', 'posts', '7', '{\"by\": \"test\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:50:32'),
(17, 'post_delete', 1, 'director', 'posts', '5', '{\"by\": \"test\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:50:34'),
(18, 'post_create', 3, 'officer', 'posts', '8', '{\"by\": \"ประภาภรณ์ อมรสิงห์\", \"title\": \"agva\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:50:57'),
(19, 'post_edit', 3, 'officer', 'posts', '8', '{\"by\": \"ประภาภรณ์ อมรสิงห์\", \"title\": \"agva\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:51:06'),
(20, 'post_create', 1, 'director', 'posts', '9', '{\"by\": \"test\", \"title\": \"ดอิปดเิื\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:58:27'),
(21, 'post_create', 1, 'director', 'posts', '10', '{\"by\": \"test\", \"title\": \"หผกด้เหผ\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 10:58:33'),
(22, 'post_create', 1, 'director', 'posts', '11', '{\"by\": \"test\", \"title\": \"หผกด้เหผ\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 11:21:48'),
(23, 'post_create', 1, 'director', 'posts', '12', '{\"by\": \"test\", \"title\": \"กดอฟดกอ\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 11:36:56'),
(24, 'post_delete', 1, 'director', 'posts', '12', '{\"by\": \"test\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 11:37:04'),
(25, 'post_delete', 1, 'director', 'posts', '11', '{\"by\": \"test\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 11:37:05'),
(26, 'change_role', 1, 'director', 'users', '4', '{\"new_role\": \"director\", \"old_role\": \"non-admin\", \"target_name\": \"Pokin Wong\", \"target_email\": \"pokin.wong30@gmail.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 11:42:10'),
(27, 'guide_approve', 4, 'director', 'guides', '9', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 12:06:13'),
(28, 'guide_approve', 4, 'director', 'guides', '8', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 12:06:16'),
(29, 'guide_approve', 4, 'director', 'guides', '7', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 12:06:19'),
(30, 'guide_approve', 4, 'director', 'guides', '6', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 12:06:21'),
(31, 'guide_approve', 4, 'director', 'guides', '16', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 13:23:41'),
(32, 'guide_approve', 4, 'director', 'guides', '15', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 13:23:43'),
(33, 'guide_approve', 4, 'director', 'guides', '14', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 13:24:13'),
(34, 'change_role', 4, 'director', 'users', '3', '{\"new_role\": \"manager\", \"old_role\": \"officer\", \"target_name\": \"ประภาภรณ์ อมรสิงห์\", \"target_email\": \"test2@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 13:27:40'),
(35, 'change_role', 4, 'director', 'users', '1', '{\"new_role\": \"officer\", \"old_role\": \"director\", \"target_name\": \"test\", \"target_email\": \"test@test.com\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 13:27:43'),
(36, 'guide_approve', 4, 'director', 'guides', '13', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 13:35:45'),
(37, 'tour_edit', 4, 'director', 'tours', '1', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 14:48:44'),
(38, 'tour_edit', 4, 'director', 'tours', '1', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 14:49:39'),
(39, 'tour_edit', 4, 'director', 'tours', '1', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 14:51:14'),
(40, 'tour_delete', 4, 'director', 'tours', '1', '{\"by\": 4, \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 14:52:17'),
(41, 'tour_create', 4, 'director', 'tours', '2', '{\"code\": \"dfghsfgh\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 14:55:41'),
(42, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 14:56:15'),
(43, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:02:17'),
(44, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:02:36'),
(45, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:03:22'),
(46, 'tour_create', 4, 'director', 'tours', '3', '{\"code\": \"pa-12345\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:37:46'),
(47, 'tour_edit', 4, 'director', 'tours', '3', '{\"by\": 4, \"code\": \"pa-12345\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:38:04'),
(48, 'tour_edit', 4, 'director', 'tours', '3', '{\"by\": 4, \"code\": \"pa-12345\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:39:35'),
(49, 'tour_delete', 4, 'director', 'tours', '3', '{\"by\": 4, \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:39:44'),
(50, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:40:06'),
(51, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:40:22'),
(52, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:57:13'),
(53, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:57:34'),
(54, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:57:49'),
(55, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 15:58:18'),
(56, 'tour_days.create', 4, 'director', 'tour_days', '10', '{\"day_no\": 3, \"tour_id\": 2}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:09:21'),
(57, 'tour_days.update', 4, 'director', 'tour_days', '4', '{\"after\": {\"day_no\": 2}, \"before\": {\"day_no\": 2}, \"tour_id\": 2}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:09:29'),
(58, 'tour_days.delete', 4, 'director', 'tour_days', '10', '{\"before\": {\"day_no\": 3}, \"tour_id\": 2}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:09:41'),
(59, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:10:09'),
(60, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfgh\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:10:22'),
(61, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfghดเ้่ดเ\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:10:55'),
(62, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfghดเ้่ดเ\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:11:35'),
(63, 'tour_edit', 4, 'director', 'tours', '2', '{\"by\": 4, \"code\": \"dfghsfghดเ้่ดเ\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:11:48'),
(64, 'tour_days.delete', 4, 'director', 'tour_days', '3', '{\"before\": {\"day_no\": 1}, \"tour_id\": 2}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:11:55'),
(65, 'tour_days.create', 4, 'director', 'tour_days', '11', '{\"day_no\": 2, \"tour_id\": 2}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:12:12'),
(66, 'tour_days.delete', 4, 'director', 'tour_days', '4', '{\"before\": {\"day_no\": 1}, \"tour_id\": 2}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:12:21'),
(67, 'tour_days.create', 4, 'director', 'tour_days', '12', '{\"day_no\": 2, \"tour_id\": 2}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:12:30'),
(68, 'tour_delete', 4, 'director', 'tours', '2', '{\"by\": 4, \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:12:39'),
(69, 'tour_create', 4, 'director', 'tours', '4', '{\"code\": \"CH-12225\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:13:37'),
(70, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:46:27'),
(71, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:46:41'),
(72, 'tour_days.delete', 4, 'director', 'tour_days', '15', '{\"before\": {\"day_no\": 3}, \"tour_id\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:46:51'),
(73, 'tour_days.update', 4, 'director', 'tour_days', '13', '{\"after\": {\"day_no\": 1}, \"before\": {\"day_no\": 1}, \"tour_id\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:46:58'),
(74, 'tour_days.update', 4, 'director', 'tour_days', '14', '{\"after\": {\"day_no\": 2}, \"before\": {\"day_no\": 2}, \"tour_id\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:47:04'),
(75, 'tour_days.create', 4, 'director', 'tour_days', '16', '{\"day_no\": 3, \"tour_id\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:47:12'),
(76, 'tour_edit', 4, 'director', 'tours', '4', '{\"by\": 4, \"code\": \"CH-12225555555\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:47:31'),
(77, 'tour_edit', 4, 'director', 'tours', '4', '{\"by\": 4, \"code\": \"CH-12225555555\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:47:55'),
(78, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:47:59'),
(79, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:48:02'),
(80, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:54:29'),
(81, 'tour_days.update', 4, 'director', 'tour_days', '13', '{\"after\": {\"day_no\": 1}, \"before\": {\"day_no\": 1}, \"tour_id\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:54:37'),
(82, 'tour_days.update', 4, 'director', 'tour_days', '14', '{\"after\": {\"day_no\": 2}, \"before\": {\"day_no\": 2}, \"tour_id\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:54:41'),
(83, 'tour_days.create', 4, 'director', 'tour_days', '17', '{\"day_no\": 4, \"tour_id\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:54:49'),
(84, 'tour_edit', 4, 'director', 'tours', '4', '{\"by\": 4, \"code\": \"CH-12225555555\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:54:58'),
(85, 'tour_edit', 4, 'director', 'tours', '4', '{\"by\": 4, \"code\": \"CH-12225555555\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:55:09'),
(86, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:55:15'),
(87, 'tour_create', 4, 'director', 'tours', '5', '{\"code\": \"IT5555\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:56:15'),
(88, 'tour_toggle_sale', 4, 'director', 'tours', '5', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:56:17'),
(89, 'tour_toggle_sale', 4, 'director', 'tours', '5', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:56:19'),
(90, 'tour_days.delete', 4, 'director', 'tour_days', '20', '{\"before\": {\"day_no\": 3}, \"tour_id\": 5}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:56:28'),
(91, 'tour_edit', 4, 'director', 'tours', '5', '{\"by\": 4, \"code\": \"IT5555\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:56:37'),
(92, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:56:45'),
(93, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:56:49'),
(94, 'tour_toggle_sale', 4, 'director', 'tours', '5', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:56:55'),
(95, 'tour_toggle_sale', 4, 'director', 'tours', '5', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:56:57'),
(96, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 16:57:00'),
(97, 'tour_create', 4, 'director', 'tours', '6', '{\"code\": \"CH-12225555555\", \"name\": \"เที่ยวเวียดนาม\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:30:33'),
(98, 'tour_create', 4, 'director', 'tours', '7', '{\"code\": \"CH-12225555555\", \"name\": \"เที่ยวเวียดนาม\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:32:03'),
(99, 'tour_create', 4, 'director', 'tours', '8', '{\"code\": \"CH-12225555555\", \"name\": \"เที่ยวเวียดนาม\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:32:33'),
(100, 'tour_create', 4, 'director', 'tours', '9', '{\"code\": \"CH-12225555555\", \"name\": \"เที่ยวเวียดนาม\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:32:55'),
(101, 'tour_delete', 4, 'director', 'tours', '9', '{\"by\": 4, \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:33:23'),
(102, 'tour_delete', 4, 'director', 'tours', '8', '{\"by\": 4, \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:33:29'),
(103, 'tour_delete', 4, 'director', 'tours', '7', '{\"by\": 4, \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:33:34'),
(104, 'tour_days.delete', 4, 'director', 'tour_days', '23', '{\"before\": {\"day_no\": 3}, \"tour_id\": 6}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:35:09'),
(105, 'tour_edit', 4, 'director', 'tours', '6', '{\"by\": 4, \"code\": \"CH-12225555555\", \"name\": \"เที่ยวเวียดนาม22\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:35:20'),
(106, 'tour_edit', 4, 'director', 'tours', '6', '{\"by\": 4, \"code\": \"CH-12225555555\", \"name\": \"เที่ยวเวียดนาม22\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:35:58'),
(107, 'tour_edit', 4, 'director', 'tours', '6', '{\"by\": 4, \"code\": \"CH-12225555555\", \"name\": \"เที่ยวเวียดนาม22\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:36:07'),
(108, 'tour_toggle_sale', 4, 'director', 'tours', '6', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:40:37'),
(109, 'tour_toggle_sale', 4, 'director', 'tours', '5', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:40:39'),
(110, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:40:41'),
(111, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:40:43'),
(112, 'tour_toggle_sale', 4, 'director', 'tours', '5', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:40:44'),
(113, 'tour_toggle_sale', 4, 'director', 'tours', '6', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:40:46'),
(114, 'tour_create', 4, 'director', 'tours', '10', '{\"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:52:32'),
(115, 'tour_days.create', 4, 'director', 'tour_days', '0', '{\"day_no\": 6, \"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:53:19'),
(116, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"11TVZ3241\", \"name\": \"11ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:54:03'),
(117, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"11TVZ3241\", \"name\": \"11ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:54:14'),
(118, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"11TVZ3241\", \"name\": \"11ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:58:27'),
(119, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"11ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:58:44'),
(120, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"11ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:59:06'),
(121, 'tour_days.delete', 4, 'director', 'tour_days', '38', '{\"before\": {\"day_no\": 6}, \"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:59:20'),
(122, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"11ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:59:34'),
(123, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 17:59:50'),
(124, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:00:01'),
(125, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:00:10'),
(126, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:00:20'),
(127, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:00:34'),
(128, 'tour_toggle_sale', 4, 'director', 'tours', '6', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:00:39'),
(129, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:00:40'),
(130, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:00:42'),
(131, 'tour_toggle_sale', 4, 'director', 'tours', '6', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:00:44'),
(132, 'guide_approve', 4, 'director', 'guides', '12', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:02:00'),
(133, 'depart_create_bulk', 4, 'director', 'tour_departures', NULL, '{\"count\": 3, \"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:15:21'),
(134, 'depart_update', 4, 'director', 'tour_departures', '3', '{\"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:15:31'),
(135, 'depart_create_bulk', 4, 'director', 'tour_departures', NULL, '{\"count\": 1, \"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:15:51'),
(136, 'tour_days.delete', 4, 'director', 'tour_days', '37', '{\"before\": {\"day_no\": 5}, \"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:16:32'),
(137, 'tour_days.create', 4, 'director', 'tour_days', '0', '{\"day_no\": 5, \"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:16:42'),
(138, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"555TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:17:03'),
(139, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"555TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:19:26'),
(140, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"555TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:19:38'),
(141, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"555TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:19:47'),
(142, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:20:00'),
(143, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:20:25'),
(144, 'depart_create_bulk', 4, 'director', 'tour_departures', NULL, '{\"count\": 1, \"tour_id\": 6}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:20:44'),
(145, 'depart_create_bulk', 4, 'director', 'tour_departures', NULL, '{\"count\": 1, \"tour_id\": 6}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:20:58'),
(146, 'depart_delete', 4, 'director', 'tour_departures', '6', '{\"tour_id\": 6}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:21:07'),
(147, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:21:41'),
(148, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:21:43'),
(149, 'depart_update', 4, 'director', 'tour_departures', '1', '{\"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:36:24'),
(150, 'depart_update', 4, 'director', 'tour_departures', '1', '{\"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:38:48'),
(151, 'depart_update', 4, 'director', 'tour_departures', '1', '{\"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:38:52'),
(152, 'depart_update', 4, 'director', 'tour_departures', '1', '{\"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:44:30'),
(153, 'depart_update', 4, 'director', 'tour_departures', '1', '{\"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:44:34'),
(154, 'depart_update', 4, 'director', 'tour_departures', '5', '{\"tour_id\": 6}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:44:48'),
(155, 'tour_days.create', 4, 'director', 'tour_days', '0', '{\"day_no\": 6, \"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:45:08'),
(156, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:45:27'),
(157, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:45:43'),
(158, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:45:52'),
(159, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:46:06'),
(160, 'tour_edit', 4, 'director', 'tours', '10', '{\"by\": 4, \"code\": \"TVZ3241\", \"name\": \"ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:46:19'),
(161, 'depart_update', 4, 'director', 'tour_departures', '5', '{\"tour_id\": 6}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:48:03'),
(162, 'depart_update', 4, 'director', 'tour_departures', '5', '{\"tour_id\": 6}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:53:52'),
(163, 'depart_create_bulk', 4, 'director', 'tour_departures', NULL, '{\"count\": 1, \"tour_id\": 5}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:59:14'),
(164, 'depart_update', 4, 'director', 'tour_departures', '7', '{\"tour_id\": 5}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:59:18'),
(165, 'tour_edit', 4, 'director', 'tours', '5', '{\"by\": 4, \"code\": \"IT5555\", \"name\": \"ไปจีนกันดีกว่าาา\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 18:59:52');
INSERT INTO `audit_logs` (`id`, `action`, `actor_id`, `actor_role`, `target_table`, `target_id`, `details`, `ip`, `user_agent`, `created_at`) VALUES
(166, 'tour_edit', 4, 'director', 'tours', '5', '{\"by\": 4, \"code\": \"IT5555\", \"name\": \"ไปจีนกันดีกว่าาา\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-04 19:00:01'),
(167, 'tour_days.delete', 4, 'director', 'tour_days', '40', '{\"before\": {\"day_no\": 6}, \"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 02:20:42'),
(168, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:14:44'),
(169, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:14:46'),
(170, 'tour_create', 4, 'director', 'tours', '11', '{\"code\": \"xfgn\", \"name\": \"dsfgnd\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:15:20'),
(171, 'tour_days.delete', 4, 'director', 'tour_days', '42', '{\"before\": {\"day_no\": 2}, \"tour_id\": 11}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:15:35'),
(172, 'tour_edit', 4, 'director', 'tours', '11', '{\"by\": 4, \"code\": \"xfgn\", \"name\": \"dsfgnd\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:15:48'),
(173, 'guide_approve', 4, 'director', 'guides', '11', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:16:13'),
(174, 'depart_update', 4, 'director', 'tour_departures', '1', '{\"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:16:28'),
(175, 'depart_update', 4, 'director', 'tour_departures', '1', '{\"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:16:33'),
(176, 'tour_toggle_sale', 4, 'director', 'tours', '11', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:34:45'),
(177, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:34:55'),
(178, 'tour_toggle_sale', 4, 'director', 'tours', '6', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:34:57'),
(179, 'tour_toggle_sale', 4, 'director', 'tours', '5', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:34:58'),
(180, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:34:59'),
(181, 'tour_toggle_sale', 4, 'director', 'tours', '11', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:35:07'),
(182, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:35:08'),
(183, 'tour_toggle_sale', 4, 'director', 'tours', '6', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:35:10'),
(184, 'tour_toggle_sale', 4, 'director', 'tours', '5', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:35:11'),
(185, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:35:12'),
(186, 'tour_edit', 4, 'director', 'tours', '6', '{\"by\": 4, \"code\": \"CH-12225555555\", \"name\": \"เที่ยวเวียดนาม22\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 03:52:18'),
(187, 'depart_delete', 4, 'director', 'tour_departures', '3', '{\"tour_id\": 10}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 04:03:49'),
(188, 'tour_toggle_sale', 4, 'director', 'tours', '11', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 04:04:06'),
(189, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 04:04:08'),
(190, 'tour_toggle_sale', 4, 'director', 'tours', '11', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 04:04:09'),
(191, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 04:04:12'),
(192, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 04:04:27'),
(193, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 05:06:06'),
(194, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 05:06:12'),
(195, 'depart_create_bulk', 4, 'director', 'tour_departures', NULL, '{\"count\": 1, \"tour_id\": 5}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 05:24:23'),
(196, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 06:28:34'),
(197, 'tour_toggle_sale', 4, 'director', 'tours', '10', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 06:28:40'),
(198, 'tour_create', 4, 'director', 'tours', '12', '{\"code\": \"ดกหะิ้หพะ\", \"name\": \"ไทย\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 06:56:31'),
(199, 'depart_create_bulk', 4, 'director', 'tour_departures', NULL, '{\"count\": 1, \"tour_id\": 12}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 11:33:20'),
(200, 'tour_toggle_sale', 4, 'director', 'tours', '12', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:23:28'),
(201, 'tour_toggle_sale', 4, 'director', 'tours', '11', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:23:29'),
(202, 'tour_toggle_sale', 4, 'director', 'tours', '11', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:23:36'),
(203, 'tour_toggle_sale', 4, 'director', 'tours', '12', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:23:38'),
(204, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:23:39'),
(205, 'tour_days.update', 4, 'director', 'tour_days', '46', '{\"after\": {\"day_no\": 4}, \"before\": {\"day_no\": 4}, \"tour_id\": 12}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:23:50'),
(206, 'tour_days.delete', 4, 'director', 'tour_days', '46', '{\"before\": {\"day_no\": 4}, \"tour_id\": 12}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:23:55'),
(207, 'tour_edit', 4, 'director', 'tours', '12', '{\"by\": 4, \"code\": \"ดกหะิ้หพะ5555\", \"name\": \"ไทย\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:24:09'),
(208, 'tour_toggle_sale', 4, 'director', 'tours', '12', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:24:20'),
(209, 'tour_toggle_sale', 4, 'director', 'tours', '12', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:24:22'),
(210, 'tour_edit', 4, 'director', 'tours', '12', '{\"by\": 4, \"code\": \"ดกหะิ้หพะ5555\", \"name\": \"ไทย\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 12:24:29'),
(211, 'guide_approve', 4, 'director', 'guides', '10', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 13:23:56'),
(212, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 13:27:50'),
(213, 'tour_toggle_sale', 4, 'director', 'tours', '4', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 13:28:00'),
(214, 'depart_create_bulk', 4, 'director', 'tour_departures', NULL, '{\"count\": 1, \"tour_id\": 12}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 13:28:43'),
(215, 'tour_toggle_sale', 4, 'director', 'tours', '11', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 13:42:38'),
(216, 'tour_toggle_sale', 4, 'director', 'tours', '11', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-05 13:42:42'),
(217, 'guide_approve', 4, 'director', 'guides', '5', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-06 17:09:26'),
(218, 'booking_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:11:17'),
(219, 'booking_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:13:04'),
(220, 'booking_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:13:43'),
(221, 'booking_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:16:58'),
(222, 'booking_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:17:26'),
(223, 'booking_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:17:46'),
(224, 'booking_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:17:56'),
(225, 'booking_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:18:19'),
(226, 'booking_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:19:06'),
(227, 'hotel_create', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:29:17'),
(228, 'hotel_update', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:29:23'),
(229, 'hotel_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:29:27'),
(230, 'hotel_create', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:29:39'),
(231, 'transport_create', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:44:19'),
(232, 'transport_create', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:50:51'),
(233, 'transport_create', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:52:36'),
(234, 'transport_create', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 06:53:18'),
(235, 'transport_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 07:26:34'),
(236, 'transport_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 07:26:36'),
(237, 'transport_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 07:26:37'),
(238, 'transport_delete', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 07:26:38'),
(239, 'transport_create', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 07:28:00'),
(240, 'tour_toggle_sale', 4, 'director', 'tours', '12', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 14:54:35'),
(241, 'tour_toggle_sale', 4, 'director', 'tours', '12', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 1}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 14:54:37'),
(242, 'transport_update', 4, 'director', 'Array', NULL, NULL, 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 13:36:27'),
(243, 'tour_create', 4, 'director', 'tours', '13', '{\"code\": \"TVZ324556\", \"name\": \"ไทยเที่ยวไทย\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 10:46:06'),
(244, 'tour_create', 4, 'director', 'tours', '14', '{\"code\": \"th-2522\", \"name\": \"ไทยเที่ยวไทย2\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 10:52:22'),
(245, 'tour_create', 4, 'director', 'tours', '15', '{\"code\": \"TVZ3241666\", \"name\": \"ไทยเที่ยวไทย3\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 11:03:39'),
(246, 'tour_create', 4, 'director', 'tours', '16', '{\"code\": \"TVZ3241-8899\", \"name\": \"ไทยเที่ยวไทย4\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 11:22:35'),
(247, 'tour_create', 4, 'director', 'tours', '17', '{\"code\": \"TVZ3241-8899\", \"name\": \"ไทยเที่ยวไทย4\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 11:23:54'),
(248, 'tour_create', 4, 'director', 'tours', '18', '{\"code\": \"TVZ3241-9988\", \"name\": \"ไทยเที่ยวไทย5\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 11:26:09'),
(249, 'tour_create', 4, 'director', 'tours', '19', '{\"code\": \"rrtt22\", \"name\": \"japan1\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 05:08:17'),
(250, 'tour_create', 4, 'director', 'tours', '20', '{\"code\": \"gadfga\", \"name\": \"ergarga\", \"by_role\": \"director\", \"created_by\": 4}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-07 05:37:49'),
(251, 'guide_approve', 4, 'director', 'guides', '4', '{\"by\": \"Pokin Wong\", \"by_role\": \"director\"}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 07:42:09'),
(252, 'tour_toggle_sale', 4, 'director', 'tours', '12', '{\"by\": 4, \"by_role\": \"director\", \"is_on_sale\": 0}', 0x00000000000000000000000000000001, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 07:43:15');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint UNSIGNED NOT NULL,
  `ref_code` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `tour_id` bigint UNSIGNED DEFAULT NULL,
  `tour_code` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `departure_id` bigint UNSIGNED DEFAULT NULL,
  `member_id` bigint UNSIGNED DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `contact_time` time DEFAULT NULL,
  `qty` smallint UNSIGNED NOT NULL DEFAULT '1',
  `unit_price` decimal(12,2) DEFAULT NULL,
  `total_price` decimal(14,2) DEFAULT NULL,
  `note` text COLLATE utf8mb4_general_ci,
  `status` enum('pending','confirmed','paid','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `ref_code`, `tour_id`, `tour_code`, `departure_id`, `member_id`, `full_name`, `email`, `phone`, `contact_time`, `qty`, `unit_price`, `total_price`, `note`, `status`, `created_at`, `created_ip`, `user_agent`) VALUES
(7, 'BK-202510-4511', 12, 'ดกหะิ้หพะ5555', 10, NULL, 'ประภาภรณ์ อมรสิงห์', 'test@test.com', 'ทดเ้่ท', NULL, 6, 2500.00, 15000.00, NULL, 'pending', '2025-10-07 00:02:24', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(11, 'BK-202508-0003', 3, 'ทัวร์เยอรมัน', 15, 195, 'ปวริศา สรรพกิจ', 'user1002@test.com', '0255341928', NULL, 3, 55900.00, 167700.00, NULL, 'cancelled', '2025-08-15 08:42:14', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(12, 'BK-202508-0004', 8, 'ทัวร์จีน', 3, 206, 'ปวริศา พัฒนาวงศ์', 'user1003@mail.com', '0953767242', '16:34:00', 5, 89900.00, 449500.00, NULL, 'paid', '2025-08-12 11:43:20', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(13, 'BK-202504-0001', 25, 'ทัวร์ไทย', 4, 79, 'ธนกฤต จิตต์ดี', 'user1004@example.com', '0669784801', NULL, 5, 35900.00, 179500.00, NULL, 'confirmed', '2025-04-23 10:32:31', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(14, 'BK-202501-0001', 17, 'ทัวร์เยอรมัน', 12, NULL, 'ศศิธร อัครเดช', 'user1005@mail.com', '0252880957', '13:56:00', 5, 22900.00, 114500.00, NULL, 'paid', '2025-01-10 19:56:46', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(15, 'BK-202503-0001', 25, 'ทัวร์เวียดนาม', 9, NULL, 'พรทิพย์ ศักดา', 'user1006@mail.com', '0489638346', NULL, 6, 55900.00, 335400.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'confirmed', '2025-03-06 15:52:04', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(16, 'BK-202504-0002', 8, 'ทัวร์เวียดนาม', 38, NULL, 'นฤมล จันทราภา', 'user1007@mail.com', '0105183473', '19:59:00', 8, 22900.00, 183200.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-04-25 13:01:37', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(17, 'BK-202503-0002', 14, 'ทัวร์ญี่ปุ่น', 30, 28, 'ศศิธร จันทราภา', 'user1008@demo.co.th', '0513338726', '15:15:00', 2, 55900.00, 111800.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-03-10 18:27:22', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(18, 'BK-202502-0001', 30, 'ทัวร์ไทย', 16, NULL, 'พรทิพย์ หาญกล้า', 'user1009@mail.com', '0602606474', '19:50:00', 8, 17900.00, 143200.00, NULL, 'pending', '2025-02-17 16:53:00', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(20, 'BK-202507-0002', 8, 'ทั้วจีน', 17, NULL, 'ปิยบุตร สรรพกิจ', 'user1011@test.com', '0107991183', '10:59:00', 6, 15900.00, 95400.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'confirmed', '2025-07-09 12:13:42', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(21, 'BK-202506-0001', 26, 'ทัวร์สวิตเซอร์แลนด์', 34, NULL, 'กชกร อัครเดช', 'user1012@example.com', '0241182449', '13:13:00', 5, 69900.00, 349500.00, NULL, 'cancelled', '2025-06-20 16:45:19', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(22, 'BK-202503-0003', 1, 'ทัวร์จีน', 22, NULL, 'ปวริศา โสภณ', 'user1013@demo.co.th', '0868011280', NULL, 3, 45900.00, 137700.00, NULL, 'confirmed', '2025-03-09 14:53:17', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(23, 'BK-202509-0001', 12, 'ทัวร์ไทย', 36, 209, 'ณัฐชา มณีโชติ', 'user1014@mail.com', '0226025634', '19:06:00', 7, 12900.00, 90300.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'pending', '2025-09-20 11:43:15', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(24, 'BK-202505-0001', 8, 'ทัวร์อินเดีย', 2, NULL, 'ธีรเดช พงศ์ศิริ', 'user1015@test.com', '0145868501', NULL, 5, 17900.00, 89500.00, NULL, 'cancelled', '2025-05-30 21:50:55', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(25, 'BK-202503-0004', 14, 'ทั้วจีน', 39, 60, 'ธีรเดช จตุพร', 'user1016@mail.com', '0406088356', '18:58:00', 6, 89900.00, 539400.00, NULL, 'paid', '2025-03-16 14:22:46', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(26, 'BK-202509-0002', 11, 'ทัวร์ญี่ปุ่น', 26, NULL, 'กชกร มณีโชติ', 'user1017@mail.com', '0662994680', '11:27:00', 6, 55900.00, 335400.00, NULL, 'paid', '2025-09-20 16:19:42', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(27, 'BK-202506-0002', 10, 'ทัวร์ไทย', 33, NULL, 'ณัฐชา พงศ์ศิริ', 'user1018@example.com', '0343320037', '20:04:00', 8, 45900.00, 367200.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-06-28 20:47:10', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(29, 'BK-202506-0003', 24, 'ทัวร์ยุโรป', 31, NULL, 'ณัฐวุฒิ วัฒนศิริ', 'user1020@demo.co.th', '0347143455', NULL, 2, 17900.00, 35800.00, NULL, 'confirmed', '2025-06-18 16:28:57', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(30, 'BK-202508-0006', 18, 'ทั้วจีน', 30, NULL, 'สิทธิชัย จิตต์ดี', 'user1021@demo.co.th', '0909670546', NULL, 7, 69900.00, 489300.00, NULL, 'paid', '2025-08-16 09:26:26', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(31, 'BK-202509-0003', 16, 'ทัวร์ญี่ปุ่น', 2, NULL, 'ธีรเดช โสภณ', 'user1022@demo.co.th', '0298069901', '10:55:00', 8, 17900.00, 143200.00, NULL, 'confirmed', '2025-09-20 15:14:17', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(32, 'BK-202506-0004', 9, 'ทัวร์ญี่ปุ่น', 27, NULL, 'นฤมล หาญกล้า', 'user1023@example.com', '0805310033', NULL, 3, 22900.00, 68700.00, NULL, 'paid', '2025-06-24 13:48:56', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(33, 'BK-202509-0004', 6, 'ทั้วจีน', 39, NULL, 'ศศิธร โสภณ', 'user1024@test.com', '0190496631', '18:15:00', 2, 27900.00, 55800.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-09-29 15:44:16', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(34, 'BK-202509-0005', 18, 'ทั้วจีน', 28, NULL, 'นฤมล อิสระ', 'user1025@test.com', '0067165726', '16:41:00', 5, 89900.00, 449500.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-09-02 17:50:02', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(35, 'BK-202506-0005', 11, 'ทัวร์เยอรมัน', 16, 45, 'ปวริศา สรรพกิจ', 'user1026@mail.com', '0799650752', '13:51:00', 5, 35900.00, 179500.00, NULL, 'paid', '2025-06-26 21:46:37', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(36, 'BK-202507-0003', 24, 'ทัวร์อินเดีย', 27, NULL, 'ณัฐวุฒิ หาญกล้า', 'user1027@demo.co.th', '0701436349', '16:33:00', 6, 45900.00, 275400.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-07-16 16:12:05', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(37, 'BK-202508-0007', 4, 'ทัวร์อินเดีย', 13, NULL, 'กชกร โสภณ', 'user1028@mail.com', '0374989413', '13:11:00', 5, 12900.00, 64500.00, NULL, 'pending', '2025-08-15 12:19:16', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(38, 'BK-202510-0001', 21, 'ทัวร์ยุโรป', 32, NULL, 'ประภาภรณ์ จตุพร', 'user1029@test.com', '0777520471', NULL, 7, 55900.00, 391300.00, NULL, 'paid', '2025-10-03 16:18:44', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(39, 'BK-202504-0003', 8, 'ทัวร์ไทย', 8, NULL, 'ภานุวัฒน์ รัตนเศรษฐ์', 'user1030@mail.com', '0867749649', '17:47:00', 2, 22900.00, 45800.00, NULL, 'confirmed', '2025-04-01 20:36:19', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(40, 'BK-202504-0004', 1, 'ทัวร์ยุโรป', 27, NULL, 'ณัฐชา หาญกล้า', 'user1031@test.com', '0034471349', NULL, 4, 45900.00, 183600.00, NULL, 'pending', '2025-04-03 10:35:04', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(41, 'BK-202509-0006', 6, 'ทัวร์จีน', 20, NULL, 'พิมพ์ชนก อัครเดช', 'user1032@demo.co.th', '0174648877', '08:56:00', 7, 35900.00, 251300.00, NULL, 'pending', '2025-09-22 21:09:04', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(42, 'BK-202510-0002', 1, 'ทัวร์สวิตเซอร์แลนด์', 18, NULL, 'ธนกฤต หาญกล้า', 'user1033@demo.co.th', '0429671756', '18:06:00', 3, 35900.00, 107700.00, NULL, 'confirmed', '2025-10-01 21:55:36', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(43, 'BK-202509-0007', 15, 'ทัวร์จีน', 6, NULL, 'อรทัย บุญชู', 'user1034@demo.co.th', '0808760385', '15:40:00', 8, 12900.00, 103200.00, NULL, 'paid', '2025-09-28 21:48:35', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(44, 'BK-202506-0006', 21, 'ทัวร์จีน', 39, NULL, 'ธนกฤต อัครเดช', 'user1035@example.com', '0861317127', NULL, 5, 69900.00, 349500.00, NULL, 'confirmed', '2025-06-20 19:31:07', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(45, 'BK-202510-0003', 13, 'ทัวร์ยุโรป', 13, 261, 'วราภรณ์ พัฒนาวงศ์', 'user1036@test.com', '0658404499', NULL, 8, 17900.00, 143200.00, NULL, 'confirmed', '2025-10-04 11:29:35', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(46, 'BK-202507-0004', 28, 'ทั้วจีน', 13, 123, 'พิมพ์ชนก คงคา', 'user1037@mail.com', '0605766270', '17:21:00', 2, 55900.00, 111800.00, NULL, 'cancelled', '2025-07-16 16:24:29', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(47, 'BK-202508-0008', 3, 'ทัวร์ยุโรป', 31, NULL, 'ปวริศา พงศ์ศิริ', 'user1038@demo.co.th', '0158657809', '18:43:00', 5, 22900.00, 114500.00, NULL, 'confirmed', '2025-08-19 14:55:41', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(48, 'BK-202508-0009', 6, 'ทัวร์เกาหลี', 20, 24, 'อรทัย จันทราภา', 'user1039@test.com', '0556238692', '09:39:00', 7, 89900.00, 629300.00, NULL, 'confirmed', '2025-08-26 19:55:06', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(49, 'BK-202503-0005', 9, 'ทัวร์เกาหลี', 1, 239, 'ปิยบุตร ศักดา', 'user1040@mail.com', '0175946474', '14:54:00', 8, 15900.00, 127200.00, NULL, 'paid', '2025-03-29 15:40:16', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(50, 'BK-202505-0002', 9, 'ทัวร์ญี่ปุ่น', 1, NULL, 'สุพัตรา รัตนเศรษฐ์', 'user1041@demo.co.th', '0439533942', '18:41:00', 1, 27900.00, 27900.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'pending', '2025-05-16 19:18:01', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(51, 'BK-202508-0010', 24, 'ทั้วจีน', 27, NULL, 'วราภรณ์ ศักดา', 'user1042@test.com', '0884247451', '09:09:00', 4, 45900.00, 183600.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-08-21 09:58:18', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(52, 'BK-202503-0006', 4, 'ทัวร์เวียดนาม', 30, NULL, 'ปวริศา จตุพร', 'user1043@demo.co.th', '0513709859', '09:40:00', 8, 27900.00, 223200.00, NULL, 'pending', '2025-03-08 14:00:16', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(53, 'BK-202509-0008', 8, 'ทัวร์ไทย', 35, NULL, 'ชญานิษฐ์ สุขใจ', 'user1044@demo.co.th', '0926179640', NULL, 4, 55900.00, 223600.00, NULL, 'pending', '2025-09-28 12:31:07', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(54, 'BK-202503-0007', 2, 'ทั้วจีน', 26, NULL, 'ศศิธร สรรพกิจ', 'user1045@example.com', '0390053293', '20:35:00', 4, 89900.00, 359600.00, NULL, 'paid', '2025-03-12 18:23:34', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(55, 'BK-202508-0011', 9, 'ทัวร์จีน', 10, 277, 'ปวริศา โสภณ', 'user1046@example.com', '0020539502', '10:47:00', 7, 69900.00, 489300.00, NULL, 'pending', '2025-08-29 20:57:38', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(56, 'BK-202508-0012', 17, 'ทัวร์เกาหลี', 15, 23, 'อรรคพล อัครเดช', 'user1047@demo.co.th', '0007661771', NULL, 6, 89900.00, 539400.00, NULL, 'pending', '2025-08-29 16:37:06', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(57, 'BK-202508-0013', 20, 'ทัวร์ญี่ปุ่น', 34, NULL, 'อรรคพล รัตนเศรษฐ์', 'user1048@demo.co.th', '0118367365', NULL, 7, 45900.00, 321300.00, NULL, 'confirmed', '2025-08-01 16:45:20', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(58, 'BK-202508-0014', 3, 'ทัวร์เกาหลี', 6, 48, 'ภานุวัฒน์ บุญชู', 'user1049@test.com', '0280988516', '18:48:00', 7, 12900.00, 90300.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-08-08 13:09:43', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(59, 'BK-202503-0008', 8, 'ทัวร์เกาหลี', 7, NULL, 'กชกร สุขใจ', 'user1050@example.com', '0493689980', '18:53:00', 5, 12900.00, 64500.00, NULL, 'paid', '2025-03-14 16:13:09', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(60, 'BK-202509-0009', 19, 'ทัวร์ยุโรป', 26, NULL, 'ประภาภรณ์ พัฒนาวงศ์', 'user1051@mail.com', '0667525459', '08:09:00', 3, 89900.00, 269700.00, NULL, 'pending', '2025-09-24 13:00:11', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(61, 'BK-202508-0015', 9, 'ทัวร์ญี่ปุ่น', 14, NULL, 'ศศิธร สุขใจ', 'user1052@demo.co.th', '0149784036', NULL, 1, 12900.00, 12900.00, NULL, 'cancelled', '2025-08-07 15:38:28', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(62, 'BK-202508-0016', 16, 'ทัวร์จีน', 28, NULL, 'ธีรเดช ศักดา', 'user1053@mail.com', '0885160607', NULL, 6, 89900.00, 539400.00, NULL, 'confirmed', '2025-08-27 12:20:07', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(63, 'BK-202506-0007', 6, 'ทั้วจีน', 40, NULL, 'ชนินทร์ พัฒนาวงศ์', 'user1054@demo.co.th', '0136968164', NULL, 4, 35900.00, 143600.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'pending', '2025-06-15 09:25:01', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(64, 'BK-202503-0009', 12, 'ทั้วจีน', 10, NULL, 'วราภรณ์ วัฒนศิริ', 'user1055@mail.com', '0292127799', '17:41:00', 6, 35900.00, 215400.00, NULL, 'pending', '2025-03-18 16:12:57', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(65, 'BK-202508-0017', 12, 'ทัวร์จีน', 33, NULL, 'ชญานิษฐ์ สรรพกิจ', 'user1056@example.com', '0054119986', '16:50:00', 1, 55900.00, 55900.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-08-01 20:17:37', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(67, 'BK-202508-0018', 22, 'ทัวร์เวียดนาม', 25, NULL, 'ณัฐชา มณีโชติ', 'user1058@test.com', '0192546291', '14:41:00', 6, 17900.00, 107400.00, NULL, 'paid', '2025-08-04 09:21:06', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(69, 'BK-202506-0008', 26, 'ทัวร์สวิตเซอร์แลนด์', 11, NULL, 'สุพัตรา จิตต์ดี', 'user1060@test.com', '0379473834', NULL, 8, 22900.00, 183200.00, NULL, 'cancelled', '2025-06-06 10:11:53', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(70, 'BK-202506-0009', 17, 'ทัวร์เวียดนาม', 27, 419, 'สิทธิชัย รัตนเศรษฐ์', 'user1061@mail.com', '0407581814', '10:17:00', 8, 69900.00, 559200.00, NULL, 'confirmed', '2025-06-26 12:49:24', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(71, 'BK-202509-0010', 2, 'ทัวร์ญี่ปุ่น', 26, NULL, 'ณัฐวุฒิ คงคา', 'user1062@example.com', '0530515220', '15:44:00', 3, 55900.00, 167700.00, NULL, 'pending', '2025-09-05 13:59:01', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(72, 'BK-202501-0002', 24, 'ทัวร์เวียดนาม', 39, NULL, 'ศศิธร อัครเดช', 'user1063@mail.com', '0410369711', NULL, 8, 89900.00, 719200.00, NULL, 'paid', '2025-01-27 11:53:09', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(73, 'BK-202504-0005', 20, 'ทัวร์จีน', 23, NULL, 'ภานุวัฒน์ โสภณ', 'user1064@example.com', '0851888880', '15:02:00', 7, 35900.00, 251300.00, NULL, 'pending', '2025-04-30 10:18:27', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(74, 'BK-202505-0004', 25, 'ทัวร์ไทย', 38, NULL, 'อรทัย มณีโชติ', 'user1065@example.com', '0585277221', NULL, 8, 12900.00, 103200.00, NULL, 'pending', '2025-05-28 19:42:40', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(75, 'BK-202509-0011', 13, 'ทัวร์เวียดนาม', 35, NULL, 'สุพัตรา ศิริวงศ์', 'user1066@test.com', '0505415667', NULL, 7, 35900.00, 251300.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'confirmed', '2025-09-18 13:59:19', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(76, 'BK-202506-0010', 24, 'ทัวร์ไทย', 28, NULL, 'ณัฐชา โสภณ', 'user1067@test.com', '0511544796', '15:22:00', 8, 12900.00, 103200.00, NULL, 'cancelled', '2025-06-03 20:33:17', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(78, 'BK-202506-0011', 17, 'ทัวร์ยุโรป', 24, NULL, 'ปวริศา พิมพ์ทอง', 'user1069@example.com', '0027868144', NULL, 4, 89900.00, 359600.00, NULL, 'cancelled', '2025-06-04 20:04:36', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(79, 'BK-202504-0006', 6, 'ทัวร์เกาหลี', 29, 415, 'อรทัย สุขใจ', 'user1070@example.com', '0884422583', '11:50:00', 3, 22900.00, 68700.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'pending', '2025-04-17 10:54:04', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(80, 'BK-202507-0005', 20, 'ทัวร์ยุโรป', 6, NULL, 'ธีรเดช หาญกล้า', 'user1071@demo.co.th', '0691251778', '20:35:00', 3, 17900.00, 53700.00, NULL, 'cancelled', '2025-07-20 15:51:35', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(81, 'BK-202509-0012', 6, 'ทัวร์ยุโรป', 21, 116, 'ชนินทร์ อิสระ', 'user1072@test.com', '0143842498', '18:10:00', 3, 17900.00, 53700.00, NULL, 'paid', '2025-09-08 16:09:19', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(82, 'BK-202505-0005', 3, 'ทัวร์จีน', 3, 395, 'พิมพ์ชนก วัฒนศิริ', 'user1073@mail.com', '0969078447', '20:43:00', 7, 27900.00, 195300.00, NULL, 'paid', '2025-05-27 17:02:52', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(83, 'BK-202506-0012', 20, 'ทั้วจีน', 10, NULL, 'อรทัย สุขใจ', 'user1074@demo.co.th', '0258815371', '11:09:00', 2, 12900.00, 25800.00, NULL, 'confirmed', '2025-06-21 15:29:13', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(84, 'BK-202506-0013', 30, 'ทั้วจีน', 37, NULL, 'สิทธิชัย โสภณ', 'user1075@demo.co.th', '0877470168', '11:13:00', 6, 12900.00, 77400.00, NULL, 'confirmed', '2025-06-16 21:57:10', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(86, 'BK-202506-0014', 25, 'ทั้วจีน', 10, NULL, 'อรรคพล คงคา', 'user1077@example.com', '0002578729', NULL, 3, 35900.00, 107700.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'confirmed', '2025-06-10 20:21:35', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(87, 'BK-202510-0006', 11, 'ทัวร์สวิตเซอร์แลนด์', 33, 273, 'พรทิพย์ จตุพร', 'user1078@test.com', '0705516940', '12:41:00', 4, 12900.00, 51600.00, NULL, 'pending', '2025-10-06 19:53:19', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(88, 'BK-202508-0020', 27, 'ทัวร์ยุโรป', 16, NULL, 'ศศิธร ศิริวงศ์', 'user1079@example.com', '0756263689', NULL, 1, 17900.00, 17900.00, NULL, 'paid', '2025-08-19 20:53:24', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(89, 'BK-202506-0015', 30, 'ทัวร์เกาหลี', 35, NULL, 'ชนินทร์ วัฒนศิริ', 'user1080@demo.co.th', '0334843443', NULL, 8, 35900.00, 287200.00, NULL, 'paid', '2025-06-29 14:20:11', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(90, 'BK-202505-0006', 13, 'ทัวร์เวียดนาม', 26, 489, 'วราภรณ์ อัครเดช', 'user1081@example.com', '0415747338', '16:44:00', 5, 17900.00, 89500.00, NULL, 'paid', '2025-05-09 09:36:43', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(91, 'BK-202504-0007', 21, 'ทัวร์อินเดีย', 15, NULL, 'ภานุวัฒน์ พงศ์ศิริ', 'user1082@demo.co.th', '0120826773', NULL, 5, 35900.00, 179500.00, NULL, 'pending', '2025-04-30 18:57:33', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(92, 'BK-202508-0021', 30, 'ทัวร์จีน', 12, NULL, 'ธนกฤต จันทราภา', 'user1083@demo.co.th', '0724004991', NULL, 6, 27900.00, 167400.00, NULL, 'paid', '2025-08-05 11:34:47', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(93, 'BK-202507-0006', 9, 'ทัวร์เกาหลี', 13, 170, 'ธนกฤต สรรพกิจ', 'user1084@test.com', '0205493296', NULL, 6, 15900.00, 95400.00, NULL, 'pending', '2025-07-03 10:54:32', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(94, 'BK-202506-0016', 8, 'ทัวร์ญี่ปุ่น', 29, NULL, 'อรทัย อัครเดช', 'user1085@example.com', '0453017426', NULL, 5, 15900.00, 79500.00, NULL, 'cancelled', '2025-06-29 11:00:16', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(95, 'BK-202507-0007', 15, 'ทัวร์ยุโรป', 39, NULL, 'กชกร หาญกล้า', 'user1086@mail.com', '0391878519', '16:57:00', 4, 89900.00, 359600.00, NULL, 'pending', '2025-07-29 11:08:30', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(96, 'BK-202506-0017', 16, 'ทัวร์สวิตเซอร์แลนด์', 6, 229, 'สุพัตรา สรรพกิจ', 'user1087@mail.com', '0867908740', '08:47:00', 4, 89900.00, 359600.00, NULL, 'confirmed', '2025-06-21 18:13:45', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(97, 'BK-202502-0002', 30, 'ทัวร์ไทย', 31, NULL, 'ภานุวัฒน์ โสภณ', 'user1088@mail.com', '0656766182', NULL, 6, 15900.00, 95400.00, NULL, 'paid', '2025-02-23 08:59:57', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(98, 'BK-202503-0010', 10, 'ทัวร์จีน', 30, NULL, 'กชกร จิตต์ดี', 'user1089@demo.co.th', '0337524310', NULL, 7, 89900.00, 629300.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'paid', '2025-03-23 11:53:00', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(99, 'BK-202504-0008', 15, 'ทัวร์จีน', 39, NULL, 'สุพัตรา พิมพ์ทอง', 'user1090@example.com', '0673830284', '19:36:00', 6, 89900.00, 539400.00, NULL, 'paid', '2025-04-30 09:06:38', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(100, 'BK-202504-0009', 14, 'ทัวร์อินเดีย', 20, NULL, 'กชกร จตุพร', 'user1091@mail.com', '0687056856', '12:24:00', 3, 69900.00, 209700.00, NULL, 'paid', '2025-04-29 10:42:33', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(101, 'BK-202509-0013', 18, 'ทัวร์จีน', 27, 286, 'อรรคพล มณีโชติ', 'user1092@demo.co.th', '0343517518', NULL, 4, 12900.00, 51600.00, NULL, 'paid', '2025-09-13 10:51:29', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(102, 'BK-202502-0003', 24, 'ทัวร์สวิตเซอร์แลนด์', 36, NULL, 'สิทธิชัย พงศ์ศิริ', 'user1093@test.com', '0033173936', NULL, 6, 27900.00, 167400.00, NULL, 'paid', '2025-02-10 09:12:51', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(103, 'BK-202505-0007', 15, 'ทัวร์เกาหลี', 7, 370, 'พรทิพย์ พงศ์ศิริ', 'user1094@mail.com', '0556093204', '17:37:00', 7, 27900.00, 195300.00, NULL, 'confirmed', '2025-05-19 13:32:56', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(104, 'BK-202507-0008', 9, 'ทั้วจีน', 32, 329, 'พรทิพย์ สรรพกิจ', 'user1095@mail.com', '0522872808', '09:52:00', 1, 12900.00, 12900.00, NULL, 'paid', '2025-07-19 11:31:35', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(105, 'BK-202502-0004', 15, 'ทัวร์เวียดนาม', 24, NULL, 'ณัฐชา หาญกล้า', 'user1096@demo.co.th', '0008860084', '12:01:00', 7, 17900.00, 125300.00, NULL, 'pending', '2025-02-26 10:00:13', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(106, 'BK-202504-0010', 9, 'ทั้วจีน', 26, NULL, 'ธีรเดช สรรพกิจ', 'user1097@mail.com', '0341016668', '18:00:00', 3, 15900.00, 47700.00, NULL, 'confirmed', '2025-04-30 17:33:16', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(107, 'BK-202507-0009', 30, 'ทัวร์เวียดนาม', 3, NULL, 'พิมพ์ชนก หาญกล้า', 'user1098@test.com', '0014890250', NULL, 3, 45900.00, 137700.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'confirmed', '2025-07-19 21:06:57', '192.168.1.10', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(108, 'BK-202504-0011', 11, 'ทัวร์เวียดนาม', 25, NULL, 'วราภรณ์ พัฒนาวงศ์', 'user1099@test.com', '0016315999', '20:20:00', 1, 27900.00, 27900.00, 'ลูกค้าติดต่อผ่านเว็บไซต์', 'confirmed', '2025-04-30 21:54:24', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(109, 'BK-202510-9460', 10, 'TVZ3241', 1, NULL, 'asdfa', 'test@test.com', 'sadfasdf', NULL, 4, 124999.98, 499999.92, 'aSDfasd', 'paid', '2025-10-13 21:41:24', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `booking_status_history`
--

CREATE TABLE `booking_status_history` (
  `id` bigint UNSIGNED NOT NULL,
  `booking_id` bigint UNSIGNED NOT NULL,
  `actor_id` bigint UNSIGNED DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_general_ci NOT NULL,
  `new_status` enum('pending','confirmed','paid','cancelled') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_status_history`
--

INSERT INTO `booking_status_history` (`id`, `booking_id`, `actor_id`, `actor_name`, `note`, `new_status`, `created_at`) VALUES
(1, 4, 4, 'Pokin Wong', 'คุยกับลูกค้าแล้ว วันที่ 17 ตุลาคม จะนัดจ่ายเงิน', NULL, '2025-10-05 20:12:40'),
(2, 4, 4, 'Pokin Wong', 'วันนี้ลูกค้าขอดูข้อมูลอีกรอบ', NULL, '2025-10-05 20:13:12'),
(3, 4, 4, 'Pokin Wong', 'ชำระเงินเรียบร้อยแล้ว', 'paid', '2025-10-05 20:15:28'),
(4, 5, 4, 'Pokin Wong', 'วันนี้คุรลูกค้า เตรียมเงินอยู่', NULL, '2025-10-05 20:31:04'),
(5, 5, 4, 'Pokin Wong', 'ชำระเงินเรียบร้อยแล้ว', 'paid', '2025-10-05 20:31:23'),
(10, 109, 4, 'Pokin Wong', 'ชำระเงินเรียบร้อยแล้ว', 'paid', '2025-12-06 12:52:26');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int NOT NULL,
  `country_id` int NOT NULL,
  `name_en` varchar(190) NOT NULL,
  `name_th` varchar(190) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `country_id`, `name_en`, `name_th`) VALUES
(1, 1, 'Bangkok', 'กรุงเทพฯ'),
(2, 1, 'Chiang Mai', 'เชียงใหม่'),
(3, 1, 'Phuket', 'ภูเก็ต'),
(4, 2, 'Tokyo', 'โตเกียว'),
(5, 2, 'Osaka', 'โอซาก้า'),
(6, 3, 'Paris', 'ปารีส'),
(7, 3, 'Nice', 'นีซ'),
(8, 4, 'New York', 'นิวยอร์ก'),
(9, 4, 'Los Angeles', 'ลอสแอนเจลิส'),
(10, 5, 'London', 'ลอนดอน'),
(11, 5, 'Manchester', 'แมนเชสเตอร์'),
(12, 6, 'Beijing', 'ปักกิ่ง'),
(13, 6, 'Shanghai', 'เซี่ยงไฮ้'),
(14, 7, 'Seoul', 'โซล'),
(15, 7, 'Busan', 'ปูซาน'),
(16, 8, 'Singapore', 'สิงคโปร์'),
(17, 9, 'Kuala Lumpur', 'กัวลาลัมเปอร์'),
(18, 10, 'Hanoi', 'ฮานอย'),
(19, 10, 'Ho Chi Minh City', 'โฮจิมินห์ซิตี'),
(20, 11, 'Vientiane', 'เวียงจันทน์'),
(21, 12, 'Phnom Penh', 'พนมเปญ'),
(22, 13, 'Jakarta', 'จาการ์ตา'),
(23, 13, 'Bali', 'บาหลี'),
(24, 14, 'Sydney', 'ซิดนีย์'),
(25, 14, 'Melbourne', 'เมลเบิร์น'),
(26, 15, 'Frankfurt', 'แฟรงก์เฟิร์ต'),
(27, 15, 'Munich', 'มิวนิก'),
(28, 16, 'Rome', 'โรม'),
(29, 16, 'Milan', 'มิลาน'),
(30, 17, 'Barcelona', 'บาร์เซโลนา'),
(31, 17, 'Madrid', 'มาดริด'),
(32, 18, 'Zurich', 'ซูริก'),
(33, 19, 'Dubai', 'ดูไบ'),
(34, 19, 'Abu Dhabi', 'อาบูดาบี'),
(35, 20, 'Delhi', 'เดลี'),
(36, 20, 'Mumbai', 'มุมไบ');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int NOT NULL,
  `iso2` char(2) NOT NULL,
  `name_en` varchar(190) NOT NULL,
  `name_th` varchar(190) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `iso2`, `name_en`, `name_th`) VALUES
(1, 'TH', 'Thailand', 'ประเทศไทย'),
(2, 'JP', 'Japan', 'ญี่ปุ่น'),
(3, 'FR', 'France', 'ฝรั่งเศส'),
(4, 'US', 'United States', 'สหรัฐอเมริกา'),
(5, 'GB', 'United Kingdom', 'สหราชอาณาจักร'),
(6, 'CN', 'China', 'จีน'),
(7, 'KR', 'South Korea', 'เกาหลีใต้'),
(8, 'SG', 'Singapore', 'สิงคโปร์'),
(9, 'MY', 'Malaysia', 'มาเลเซีย'),
(10, 'VN', 'Vietnam', 'เวียดนาม'),
(11, 'LA', 'Laos', 'ลาว'),
(12, 'KH', 'Cambodia', 'กัมพูชา'),
(13, 'ID', 'Indonesia', 'อินโดนีเซีย'),
(14, 'AU', 'Australia', 'ออสเตรเลีย'),
(15, 'DE', 'Germany', 'เยอรมนี'),
(16, 'IT', 'Italy', 'อิตาลี'),
(17, 'ES', 'Spain', 'สเปน'),
(18, 'CH', 'Switzerland', 'สวิตเซอร์แลนด์'),
(19, 'AE', 'United Arab Emirates', 'สหรัฐอาหรับเอมิเรตส์'),
(20, 'IN', 'India', 'อินเดีย');

-- --------------------------------------------------------

--
-- Table structure for table `guides`
--

CREATE TABLE `guides` (
  `id` int NOT NULL,
  `first_name` varchar(190) NOT NULL,
  `last_name` varchar(190) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(190) NOT NULL,
  `alt_phone` varchar(50) DEFAULT NULL,
  `address` text,
  `line_id` varchar(190) DEFAULT NULL,
  `idcard_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved') NOT NULL DEFAULT 'pending',
  `approval_token` varchar(64) DEFAULT NULL,
  `approval_token_expires` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `guides`
--

INSERT INTO `guides` (`id`, `first_name`, `last_name`, `phone`, `email`, `alt_phone`, `address`, `line_id`, `idcard_path`, `status`, `approval_token`, `approval_token_expires`, `approved_at`, `approved_by`, `created_at`) VALUES
(1, 'test', 'test', '0999999999', 'test33@test.com', '0999999999', 'ฟหกเดฟหกดเ', 'Hennn', 'uploads/ids/id_447e53fb6827f881.png', 'pending', '6233ab2c14926f7370eb99db7fec1ddda549d4c5a68b12c5', '2025-10-07 11:38:03', NULL, NULL, '2025-10-04 11:38:03'),
(2, 'test', 'test', '0999999999', 'test334@test.com', '0999999999', 'กำะ้ั่ืกำะั่', 'Hennn', 'uploads/ids/id_57f9b1a11b13f29d.png', 'pending', '45f349e8b66c9fc9367ff3fb73bdb533ab0b1d363396e11d', '2025-10-07 11:39:14', NULL, NULL, '2025-10-04 11:39:14'),
(3, 'พัี่าทดกเ้่', 'ทด้เ่ทดเ้่', 'ทดเ้่ท', 'test335@test.com', '0999999999', 'กดเหดเ', 'Hennn', 'uploads/ids/id_f2b7083037c028f4.png', 'pending', 'fba27a89f19ec5f02a92e6511a53e01bdc01ae0e3a0d68e7', '2025-10-07 11:39:39', NULL, NULL, '2025-10-04 11:39:39'),
(4, 'สามารถ', 'บังคันทาร', '09999955555', 'pawat.womg30@gmail.com', '', 'ทดสอบระบบ', 'samart223', 'uploads/ids/id_3c9131edc0097be2.png', 'approved', NULL, NULL, '2026-04-06 14:42:09', 4, '2025-10-04 11:43:59'),
(5, 'ทิวสน', 'องค์อาจ', '05555555555', 'pawat.womg30@gmail.com', '', 'Test', 'Hennn', 'uploads/ids/id_b3210f17659453dd.png', 'approved', NULL, NULL, '2025-10-07 00:09:26', 4, '2025-10-04 11:50:11'),
(6, 'สามารถ', 'องค์อาจ', '0999999999', 'pawat.womg30@gmail.com', '0999999999', 'sdsdfg', 'Hennn', 'uploads/ids/id_814ff6d9d1e93ca9.png', 'approved', NULL, NULL, '2025-10-04 19:06:21', 4, '2025-10-04 11:53:52'),
(7, 'สามารถ', 'องค์อาจ', '0999999999', 'pawat.womg30@gmail.com', '0999999999', 'sdsdfg', 'Hennn', 'uploads/ids/id_73fa3669bc190f73.png', 'approved', NULL, NULL, '2025-10-04 19:06:19', 4, '2025-10-04 11:55:53'),
(8, 'วสันต์', 'ฤดู', '0999999999', 'pokin.wong30@gmail.com', '0999999999', 'ฟดเฟกดเ', 'samart223', 'uploads/ids/id_e0e738b37617b24b.png', 'approved', NULL, NULL, '2025-10-04 19:06:16', 4, '2025-10-04 11:56:18'),
(9, 'ทิวสน', 'ฤดู', '0999999999', 'pokin.wong30@gmail.com', '0999999999', 'ฟหกดเฟกหเ', 'samart223', 'uploads/ids/id_d55392ea915f1998.png', 'approved', NULL, NULL, '2025-10-04 19:06:13', 4, '2025-10-04 12:05:23'),
(10, 'ทิวสน', 'ฤดู', '0999999999', 'pokin.wong30@gmail.com', '0999999999', 'ฟหกดเฟกหเ', 'samart223', 'uploads/ids/id_59e4867b42c3fbab.png', 'approved', NULL, NULL, '2025-10-05 20:23:56', 4, '2025-10-04 12:21:14'),
(11, 'Ohio', 'Rate', '0999999999', 'pokin.wong30@gmail.com', '0999999999', 'asdgadfg', 'samart223', 'uploads/ids/id_453b2002665c3636.png', 'approved', NULL, NULL, '2025-10-05 10:16:13', 4, '2025-10-04 12:21:59'),
(12, 'Fesco', 'Art', '0999999999', 'pawat.womg30@gmail.com', '0999999999', 'sdfgszfd', 'Hennn', 'uploads/ids/id_fa02e1867013b2d5.png', 'approved', NULL, NULL, '2025-10-05 01:02:00', 4, '2025-10-04 12:34:32'),
(13, 'Satin', 'Sheet', '0999999999', 'pokin.wong30@gmail.com', '0999999999', 'asdrhg', 'Hennn', 'uploads/ids/id_8faf635c18e024a1.png', 'approved', NULL, NULL, '2025-10-04 20:35:45', 4, '2025-10-04 12:36:49'),
(14, 'Henna', 'Hand', '0999999999', 'pawat.womg30@gmail.com', '0999999999', 'azsdfg', 'Hennn', 'uploads/ids/id_c4fa0272c3246a70.png', 'approved', NULL, NULL, '2025-10-04 20:24:13', 4, '2025-10-04 12:43:17'),
(15, 'Lenon', 'John', '0999999999', 'pawat.womg30@gmail.com', '0999999999', 'adfsgadfg', 'Hennn', 'uploads/ids/id_8a53d7accd3cdd45.png', 'approved', NULL, NULL, '2025-10-04 20:23:43', 4, '2025-10-04 13:12:29'),
(16, 'Benny', 'Ledder', '09999955555', 'pawat.womg30@gmail.com', '0999999999', 'asdgadfg', 'samart223', 'uploads/ids/id_ebdc580c99c1c3a6.png', 'approved', NULL, NULL, '2025-10-04 20:23:41', 4, '2025-10-04 13:18:29');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` bigint UNSIGNED NOT NULL,
  `name_th` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `contact_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stars` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `name_th`, `name_en`, `address`, `contact_name`, `phone`, `stars`, `created_at`, `updated_at`) VALUES
(2, 'sfdghn', 'hsdxfghnsd', 'fghsdfg', 'sdfgjhdsfg', '09999955555', 4, '2025-10-13 13:29:39', '2025-10-13 13:29:39');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','disabled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `body`, `created_by`, `created_at`) VALUES
(8, 'agva', 'ergbvaerdadfbaszdfbadfbaedfrb', 3, '2025-10-04 10:50:57'),
(9, 'ดอิปดเิื', 'หปดเิปดเืกปเ้ื', 1, '2025-10-04 10:58:27'),
(10, 'หผกด้เหผ', 'กดเ้ิหผดเ', 1, '2025-10-04 10:58:33');

-- --------------------------------------------------------

--
-- Table structure for table `tours`
--

CREATE TABLE `tours` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `long_description` mediumtext,
  `country_id` int NOT NULL,
  `city_id` int DEFAULT NULL,
  `airline_id` int DEFAULT NULL,
  `airline_logo_path` varchar(255) DEFAULT NULL,
  `depart_date` date DEFAULT NULL,
  `travel_window` varchar(255) NOT NULL,
  `duration_days` int NOT NULL,
  `origin_airport_id` int NOT NULL,
  `dest_airport_id` int NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `tour_type` enum('group','private') NOT NULL DEFAULT 'group',
  `market_type` enum('domestic','inbound','outbound') NOT NULL DEFAULT 'outbound',
  `pdf_path` varchar(255) DEFAULT NULL,
  `cover_image_path` varchar(255) DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_on_sale` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=เปิดขาย,0=ปิดขาย'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tours`
--

INSERT INTO `tours` (`id`, `code`, `name`, `long_description`, `country_id`, `city_id`, `airline_id`, `airline_logo_path`, `depart_date`, `travel_window`, `duration_days`, `origin_airport_id`, `dest_airport_id`, `price`, `tour_type`, `market_type`, `pdf_path`, `cover_image_path`, `created_by`, `created_at`, `is_on_sale`) VALUES
(4, 'CH-12225555555', NULL, NULL, 20, 35, 14, 'uploads/airlines/al_2f2d317f73cf8caf.jpg', NULL, 'เดินทางไกลมากๆเลย22', 25000, 2, 9, 2500000000.00, 'private', 'outbound', 'uploads/tours/tour_fa04e8569b968d6c.pdf', NULL, 4, '2025-10-04 16:13:37', 1),
(5, 'IT5555', 'ไปจีนกันดีกว่าาา', NULL, 16, 29, 12, 'uploads/airlines/al_dc9907504edeaff3.jpg', NULL, 'เดินทางไกล', 3500, 27, 8, 150000.00, 'group', 'outbound', 'uploads/tours/tour_1e7f9634fc68ac72.pdf', 'uploads/tours/covers/cover_ad77c77002c79fb4.jpg', 4, '2025-10-04 16:56:15', 1),
(6, 'CH-12225555555', 'เที่ยวเวียดนาม22', 'bsfgnbsfgn sfgb\r\nszsdf\r\nb\r\nazdsf\r\nb\r\nszdf\r\nb\r\nsazdfb', 20, 36, 19, 'uploads/airlines/al_2ba10668c5db0735.jpg', NULL, 'เดินทางไกลมากๆเลย22', 250, 4, 30, 1200.00, 'group', 'outbound', 'uploads/tours/tour_751fa92b45450126.pdf', 'uploads/tours/covers/cover_ab1508d9e866e0d6.jpg', 4, '2025-10-04 17:30:32', 1),
(10, 'TVZ3241', 'ทัวร์ยุโรป อิตาลี สวิตเซอร์แลนด์ ฝรั่งเศส', 'xfgbnsdrrtgvsdxrvgเที่ยว : สนามบินดูไบ (สหรัฐอาหรับเอมิเรตส์) – สนามบินฟูมิชิโน - โรม (อิตาลี )– วาติกัน – มหาวิหารเซนต์ปีเตอร์ – ถ่ายรูปโคลอสเซี่ยม – น้ำพุเทรวี่ - โรม - ปิซ่า -จัตุรัสกัมโป เดย์ มีราโกลี - ชมหอเอนปิซ่า - ฟลอเรนซ์ - ถ่ายรูปวิหารของเมืองฟลอเรนซ์ - ฟลอเรนซ์ – เวนิสเมสเทร่ - เกาะเวนิส -ชมสะพานถอนหายใจ - โบสถ์ซานมาร์โค - เวนิสเมสเทร่ (อิตาลี) - เวนิสเมสเทร่ – มิลาน (อิตาลี) –มมหาวิหารแห่งเมืองมิลาน - ชอปปิงแกลเลอรี วิคเตอร์ เอ็มมานูเอล - อินเทอร์ลาเก้น (สวิตเซอร์แลนด์) - อินเทอร์ลาเก้น –เมืองกรินเดอวาลด์ - กระเช้า Eiger Express ยอดเขาจุงเฟรา (สวิตเซอร์แลนด์) – ดิจอง (ฝรั่งเศส) - ดิจอง - ปารีส - ล่องเรือแม่น้ำแซน - ชอปปิงแกลเลอรี่ ลาฟาแยตต์ (ฝรั่งเศส) - ปารีส – ถ่ายรูปประตูชัยนโปเลียน - ทานอาหารกลางวัน ณ ภัตตาคาร บนหอไอเฟล พระราชวังแวร์ซายส์ (ฝรั่งเศส) - มงมาร์ต -ขึ้นรถรางชมวิวปารีส - ถ่ายรูปวิหารสเกรเกอร์ - ถ่ายรูปพิพิธภัณฑ์ลูฟท์ - ชอปปิงห้างลา ซามาริแตง – ชอปปิงOUTLET – สนามบินชาร์ลเดอโกลล์ (ฝรั่งเศส) - สนามบินดูไบ (สหรัฐอาหรับเอมิเรตส์) – สนามบินสุวรรณภูมิ\r\nไฮไลท์ : ยอดเขาจุงเฟรา ดิจอง ปารีส หอเอนปีซ่า ล่องเรือแม่น้ำแซน แกลเลอรี่ ลาฟาแยตต์ประตูชัยนโปเลียน พระราชวังแวร์ซายน์ มงมาร์ต พีพิธภัณฑ์ลูฟร์ ห้างลา ซามาริแตง OUTLET', 7, 14, 2, 'uploads/airlines/al_e5fb576b099e98d8.jpg', NULL, '11ต.ค. 68 - มี.ค.699', 300, 28, 10, 1099000000.00, 'group', 'outbound', 'uploads/tours/tour_060cf946d2b67755.pdf', 'uploads/tours/covers/cover_7d2094797a684b86.jpg', 4, '2025-10-04 17:52:32', 1),
(11, 'xfgn', 'dsfgnd', 'ndfgn', 9, 17, 1, 'uploads/airlines/al_3719226ad23c4e8d.jpg', NULL, 'sfgndfg', 12, 2, 12, 4558566.00, 'group', 'outbound', 'uploads/tours/tour_0cff47d9cc1e395d.pdf', 'uploads/tours/covers/cover_7b36e356ef54afee.jpg', 4, '2025-10-05 03:15:20', 1),
(12, 'ดกหะิ้หพะ5555', 'ไทย', '55555หกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิ', 1, 1, 13, 'uploads/airlines/al_840ab157f5a998de.jpg', NULL, 'เดินทางไกลมากๆเลย22', 20, 2, 2, 1253666000.00, 'private', 'outbound', 'uploads/tours/tour_1a39b90c35ffb361.pdf', 'uploads/tours/covers/cover_6f09926621def24f.jpg', 4, '2025-10-05 06:56:31', 0),
(13, 'TVZ324556', 'ไทยเที่ยวไทย', 'ฟหกดเฟำพเ', 1, 2, 17, 'uploads/airlines/al_a97acc7af0c7ca43.jpg', NULL, '11ต.ค. 68 - มี.ค.699', 2, 28, 4, 1500.00, 'group', 'outbound', 'uploads/tours/tour_79b8a61d78b678e7.pdf', 'uploads/tours/covers/cover_89a6c4a882fb74a6.jpg', 4, '2025-12-06 10:46:06', 1),
(14, 'th-2522', 'ไทยเที่ยวไทย2', 'ฟหกพเๆฟำพเ', 1, 3, 19, 'uploads/airlines/al_1af8a16194def100.jpg', NULL, '11ต.ค. 68 - มี.ค.69', 1, 8, 2, 200.00, 'group', 'outbound', 'uploads/tours/tour_da7f82d4286fe0c3.pdf', 'uploads/tours/covers/cover_5b24dad3284a2492.jpg', 4, '2025-12-06 10:52:22', 1),
(15, 'TVZ3241666', 'ไทยเที่ยวไทย3', 'ฟกดเฟหกดเ', 1, 2, 11, 'uploads/airlines/al_a872300c942510c0.jpg', NULL, '11ต.ค. 68 - มี.ค.699', 2, 29, 29, 1200.00, 'group', 'outbound', 'uploads/tours/tour_d74e1ce6dcae4105.pdf', 'uploads/tours/covers/cover_0b115686eedcf412.jpg', 4, '2025-12-06 11:03:39', 1),
(16, 'TVZ3241-8899', 'ไทยเที่ยวไทย4', 'กแเ้่ทืกเ้่', 1, 3, 8, 'uploads/airlines/al_15fe0ab20fea8e23.jpg', NULL, '11ต.ค. 68 - มี.ค.69', 2, 4, 12, 6900.00, 'group', 'outbound', 'uploads/tours/tour_ab1e93b850b99920.pdf', 'uploads/tours/covers/cover_34cdac3da69ac084.jpg', 4, '2025-12-06 11:22:35', 1),
(17, 'TVZ3241-8899', 'ไทยเที่ยวไทย4', 'กแเ้่ทืกเ้่', 1, 3, 8, 'uploads/airlines/al_7dc63c641b816fff.jpg', NULL, '11ต.ค. 68 - มี.ค.69', 2, 4, 12, 6900.00, 'group', 'outbound', 'uploads/tours/tour_00969eb1da0f849b.pdf', 'uploads/tours/covers/cover_fc1275c2328beaf3.jpg', 4, '2025-12-06 11:23:53', 1),
(18, 'TVZ3241-9988', 'ไทยเที่ยวไทย5', 'กฟดเฟกพดเ', 1, 3, 12, 'uploads/airlines/al_a9d623c9b3d9f165.jpg', NULL, '11ต.ค. 68 - มี.ค.699', 2, 2, 2, 9000.00, 'group', 'outbound', 'uploads/tours/tour_0bcc54307ce2dca9.pdf', 'uploads/tours/covers/cover_81572e049204f06d.jpg', 4, '2025-12-06 11:26:09', 1),
(19, 'rrtt22', 'japan1', 'aerfghsath', 2, 5, 15, 'uploads/airlines/al_1ff859ed0f36a1f9.jpg', NULL, '11ต.ค. 68 - มี.ค.69', 2, 30, 24, 1000.00, 'group', 'outbound', 'uploads/tours/tour_b6f63129103e956c.pdf', 'uploads/tours/covers/cover_d03d40f856629cb0.jpg', 4, '2025-12-07 05:08:17', 1),
(20, 'gadfga', 'ergarga', 'argaerg', 2, 4, 9, 'uploads/airlines/al_8626c560436d56b5.jpg', NULL, '11ต.ค. 68 - มี.ค.699', 2, 25, 2, 1500.00, 'group', 'domestic', 'uploads/tours/tour_18f9a9f025ad60f9.pdf', 'uploads/tours/covers/cover_bcc8d5a153ffe843.jpg', 4, '2025-12-07 05:37:49', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tour_costs`
--

CREATE TABLE `tour_costs` (
  `id` bigint UNSIGNED NOT NULL,
  `tour_id` bigint UNSIGNED NOT NULL,
  `pax` int NOT NULL DEFAULT '0',
  `profit_percent` decimal(10,2) NOT NULL DEFAULT '0.00',
  `day_count` int NOT NULL DEFAULT '1',
  `base_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `manual_profit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `percent_profit_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `selling_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `per_pax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `per_day` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payload` longtext,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tour_costs`
--

INSERT INTO `tour_costs` (`id`, `tour_id`, `pax`, `profit_percent`, `day_count`, `base_cost`, `manual_profit`, `percent_profit_amount`, `selling_total`, `per_pax`, `per_day`, `payload`, `created_at`, `updated_at`) VALUES
(1, 19, 20, 15.00, 2, 6000.00, 500.00, 900.00, 7400.00, 370.00, 3700.00, '{\"pax\":20,\"profitPercent\":15,\"dayCount\":2,\"totalsPerCategory\":{\"airfare\":500,\"hotel\":500,\"transport\":500,\"guide\":500,\"meals\":500,\"entrance\":500,\"insurance\":500,\"documents\":500,\"operating\":500,\"marketing\":500,\"fx\":500,\"contingency\":500,\"profit\":500},\"totalsPerDay\":{\"1\":3250,\"2\":3250},\"baseCost\":6000,\"manualProfit\":500,\"profitByPercent\":900,\"sellingTotal\":7400,\"perPax\":370,\"perDay\":3700}', '2025-12-07 05:08:17', '2025-12-07 05:08:17'),
(2, 20, 20, 15.00, 2, 6000.00, 500.00, 900.00, 7400.00, 370.00, 3700.00, '{\"pax\":20,\"profitPercent\":15,\"dayCount\":2,\"totalsPerCategory\":{\"airfare\":500,\"hotel\":500,\"transport\":500,\"guide\":500,\"meals\":500,\"entrance\":500,\"insurance\":500,\"documents\":500,\"operating\":500,\"marketing\":500,\"fx\":500,\"contingency\":500,\"profit\":500},\"totalsPerDay\":{\"1\":3250,\"2\":3250},\"baseCost\":6000,\"manualProfit\":500,\"profitByPercent\":900,\"sellingTotal\":7400,\"perPax\":370,\"perDay\":3700}', '2025-12-07 05:37:49', '2025-12-07 05:37:49');

-- --------------------------------------------------------

--
-- Table structure for table `tour_days`
--

CREATE TABLE `tour_days` (
  `id` int NOT NULL,
  `tour_id` int NOT NULL,
  `day_no` int NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tour_days`
--

INSERT INTO `tour_days` (`id`, `tour_id`, `day_no`, `description`) VALUES
(13, 4, 1, 'adfbnsrfgnsrfgnกดเ้หพะเ้ำพะ้หกดเิปดเิ'),
(14, 4, 2, 'sdfnbgkjholjhio;.uกดเ้ืกืัำผปกดิผกดเิ'),
(16, 4, 3, 'กพเ้กืิกดเิ'),
(17, 4, 4, 'ผิหผกดเิผกดิิ'),
(18, 5, 1, 'fbdghmfhjm'),
(19, 5, 2, 'dghmfdujdxfdbdfgn'),
(21, 6, 1, 'ัีสาัสรนีใ้รนใ้รน'),
(22, 6, 2, 'ส้รี่กัืกดะั้่กะั่ดกะัีา่'),
(33, 10, 1, 'สนามบินสุวรรณภูมิ'),
(34, 10, 2, 'สนามบินดูไบ (สหรัฐอาหรับเอมิเรตส์) – สนามบินฟูมิชิโน - โรม (อิตาลี )– วาติกัน – มหาวิหารเซนต์ปีเตอร์ – ถ่ายรูปโคลอสเซี่ยม – น้ำพุเทรวี่'),
(35, 10, 3, 'โรม - ปิซ่า -จัตุรัสกัมโป เดย์ มีราโกลี - ชมหอเอนปิซ่า - ฟลอเรนซ์ - ถ่ายรูปวิหารของเมืองฟลอเรนซ์'),
(36, 10, 4, 'ฟลอเรนซ์ – เวนิสเมสเทร่ - เกาะเวนิส -ชมสะพานถอนหายใจ - โบสถ์ซานมาร์โค - เวนิสเมสเทร่ (อิตาลี)'),
(39, 10, 5, 'ฟลอเรนซ์ – เวนิสเมสเทร่ - เกาะเวนิส -ชมสะพานถอนหายใจ - โบสถ์ซานมาร์โค - เวนิสเมสเทร่ (อิตาลี)'),
(41, 11, 1, 'dxfgndfg'),
(43, 12, 1, 'กดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิห'),
(44, 12, 2, 'กดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิห'),
(45, 12, 3, 'กดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิหกดิหกดิหกดเิหกดิหฟกดเิห'),
(47, 13, 1, 'ฟกหดเฟำพเ้ฟไำพะเ'),
(48, 13, 2, 'ฟำพเฟำพเ้ิฟำพ'),
(49, 14, 1, 'หก้เหพะ้'),
(50, 15, 1, 'ฟกพดเิฟ'),
(51, 15, 2, 'กดิฟกำพิ'),
(52, 16, 1, '่ดเีัะดาีัเอส่า'),
(53, 16, 2, 'กหะพำกป้เดอิสา'),
(54, 17, 1, '่ดเีัะดาีัเอส่า'),
(55, 17, 2, 'กหะพำกป้เดอิสา'),
(56, 18, 1, 'ดเ้า'),
(57, 18, 2, 'ดกเา่ีา'),
(58, 19, 1, 'hjmdfghjm'),
(59, 19, 2, 'jdgyjdyj'),
(60, 20, 1, 'adfgadreg'),
(61, 20, 2, 'arhsrthytj');

-- --------------------------------------------------------

--
-- Table structure for table `tour_departures`
--

CREATE TABLE `tour_departures` (
  `id` int NOT NULL,
  `tour_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `capacity` int NOT NULL DEFAULT '0',
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tour_departures`
--

INSERT INTO `tour_departures` (`id`, `tour_id`, `start_date`, `end_date`, `capacity`, `price`, `created_at`, `updated_at`) VALUES
(1, 10, '2025-10-14', '2025-10-25', 5, 124999.98, '2025-10-04 18:15:21', '2025-10-13 15:02:36'),
(2, 10, '2025-10-29', '2025-11-01', 5, 125000.00, '2025-10-04 18:15:21', '2025-10-13 15:02:38'),
(4, 10, '2025-10-28', '2025-10-29', 5, 12355500.00, '2025-10-04 18:15:51', '2025-10-13 15:02:39'),
(5, 6, '2025-10-22', '2025-10-30', 5, 1250000.00, '2025-10-04 18:20:44', '2025-10-13 15:02:40'),
(7, 5, '2025-10-29', '2025-11-08', 5, 250000.00, '2025-10-04 18:59:14', '2025-10-13 15:02:41'),
(8, 5, '2025-10-22', '2025-11-06', 5, 12000.00, '2025-10-05 05:24:23', '2025-10-13 15:02:44'),
(9, 12, '2025-10-05', '2025-11-08', 5, 1250.00, '2025-10-05 11:33:20', '2025-10-13 15:02:46'),
(10, 12, '2025-10-30', '2025-11-05', 5, 2500.00, '2025-10-05 13:28:43', '2025-10-13 15:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `transportations`
--

CREATE TABLE `transportations` (
  `id` bigint UNSIGNED NOT NULL,
  `license_plate` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_fullname` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reg_book_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transportations`
--

INSERT INTO `transportations` (`id`, `license_plate`, `vehicle_type`, `brand`, `owner_fullname`, `reg_book_file`, `created_at`, `updated_at`) VALUES
(5, '7กฌ5520', 'รถตู้ 15 ที่นั่ง', 'MG ZS', 'Henry Wong', 'uploads/transportations/regbook_7-5520_20251013_072800.png', '2025-10-13 14:28:00', '2025-10-14 20:36:27');

-- --------------------------------------------------------

--
-- Table structure for table `transport_bookings`
--

CREATE TABLE `transport_bookings` (
  `id` bigint UNSIGNED NOT NULL,
  `transportation_id` bigint UNSIGNED NOT NULL,
  `departure_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(190) NOT NULL,
  `role` enum('director','manager','officer','non-admin') NOT NULL DEFAULT 'non-admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'test@test.com', '$2y$10$lAY2OfllfgiifUABEEsj5.9woJkkZD128mW8V8Fq8PShfyKwPEA0y', 'test', 'officer', '2025-10-04 08:49:48'),
(3, 'test2@test.com', '$2y$10$kDlag3VPthVPZh2mIEHOFurXbBntVaHBnXJecHt4aoR.fKe2KT4MW', 'ประภาภรณ์ อมรสิงห์', 'manager', '2025-10-04 09:35:01'),
(4, 'pokin.wong30@gmail.com', '$2y$10$0Qb.uFS1SHcGDZ7Gf1OfRu6g2hZLoO41JyUvSCzS0e2GJRyKUZdeK', 'Pokin Wong', 'director', '2025-10-04 11:41:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `airlines`
--
ALTER TABLE `airlines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_air_iata` (`iata`),
  ADD KEY `idx_air_name` (`name`),
  ADD KEY `fk_air_country` (`country_id`);

--
-- Indexes for table `airports`
--
ALTER TABLE `airports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ap_iata` (`iata`),
  ADD KEY `idx_ap_country` (`country_id`),
  ADD KEY `idx_ap_city` (`city_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_created_at` (`created_at`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_actor` (`actor_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_bookings_ref` (`ref_code`),
  ADD KEY `idx_bookings_member` (`member_id`),
  ADD KEY `idx_bookings_tour_code` (`tour_code`),
  ADD KEY `idx_bookings_departure` (`departure_id`),
  ADD KEY `idx_bookings_status` (`status`),
  ADD KEY `idx_bookings_created` (`created_at`);

--
-- Indexes for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_booking` (`booking_id`),
  ADD KEY `idx_hist_created` (`created_at`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_city_country` (`country_id`),
  ADD KEY `idx_city_name_en` (`name_en`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_c_iso2` (`iso2`),
  ADD KEY `idx_c_name_en` (`name_en`);

--
-- Indexes for table `guides`
--
ALTER TABLE `guides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `status` (`status`),
  ADD KEY `approval_token` (`approval_token`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hotels_name_th` (`name_th`),
  ADD KEY `idx_hotels_name_en` (`name_en`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_members_email` (`email`),
  ADD KEY `idx_members_status` (`status`),
  ADD KEY `idx_members_created` (`created_at`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `tours`
--
ALTER TABLE `tours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tour_code` (`code`),
  ADD KEY `idx_tour_depart` (`depart_date`),
  ADD KEY `fk_tour_country` (`country_id`),
  ADD KEY `fk_tour_city` (`city_id`),
  ADD KEY `fk_tour_air` (`airline_id`),
  ADD KEY `fk_tour_ap1` (`origin_airport_id`),
  ADD KEY `fk_tour_ap2` (`dest_airport_id`),
  ADD KEY `fk_tour_user` (`created_by`);

--
-- Indexes for table `tour_costs`
--
ALTER TABLE `tour_costs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tour_costs_tour_id` (`tour_id`);

--
-- Indexes for table `tour_days`
--
ALTER TABLE `tour_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_td_tour` (`tour_id`);

--
-- Indexes for table `tour_departures`
--
ALTER TABLE `tour_departures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_depart_tour` (`tour_id`);

--
-- Indexes for table `transportations`
--
ALTER TABLE `transportations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transport_license` (`license_plate`),
  ADD KEY `idx_transport_type` (`vehicle_type`);

--
-- Indexes for table `transport_bookings`
--
ALTER TABLE `transport_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tb_transport` (`transportation_id`),
  ADD KEY `idx_tb_departure` (`departure_id`),
  ADD KEY `idx_tb_range` (`transportation_id`,`start_date`,`end_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `airlines`
--
ALTER TABLE `airlines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `airports`
--
ALTER TABLE `airports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=253;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `booking_status_history`
--
ALTER TABLE `booking_status_history`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `guides`
--
ALTER TABLE `guides`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tours`
--
ALTER TABLE `tours`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tour_costs`
--
ALTER TABLE `tour_costs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tour_days`
--
ALTER TABLE `tour_days`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `tour_departures`
--
ALTER TABLE `tour_departures`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transportations`
--
ALTER TABLE `transportations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transport_bookings`
--
ALTER TABLE `transport_bookings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `airlines`
--
ALTER TABLE `airlines`
  ADD CONSTRAINT `fk_air_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `airports`
--
ALTER TABLE `airports`
  ADD CONSTRAINT `fk_ap_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ap_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `fk_city_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tours`
--
ALTER TABLE `tours`
  ADD CONSTRAINT `fk_tour_air` FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`id`),
  ADD CONSTRAINT `fk_tour_ap1` FOREIGN KEY (`origin_airport_id`) REFERENCES `airports` (`id`),
  ADD CONSTRAINT `fk_tour_ap2` FOREIGN KEY (`dest_airport_id`) REFERENCES `airports` (`id`),
  ADD CONSTRAINT `fk_tour_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  ADD CONSTRAINT `fk_tour_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  ADD CONSTRAINT `fk_tour_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `tour_days`
--
ALTER TABLE `tour_days`
  ADD CONSTRAINT `fk_td_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tour_departures`
--
ALTER TABLE `tour_departures`
  ADD CONSTRAINT `fk_depart_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transport_bookings`
--
ALTER TABLE `transport_bookings`
  ADD CONSTRAINT `fk_tb_departure` FOREIGN KEY (`departure_id`) REFERENCES `tour_departures` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_tb_transportation` FOREIGN KEY (`transportation_id`) REFERENCES `transportations` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
