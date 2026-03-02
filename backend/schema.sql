-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 16, 2026 at 11:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `my_little_thingz`
--

-- --------------------------------------------------------

--
-- Table structure for table `artworks`
--

CREATE TABLE `artworks` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `pricing_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pricing_schema`)),
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `weight` decimal(10,2) NOT NULL DEFAULT 0.50 COMMENT 'Product weight in kg',
  `offer_price` decimal(10,2) DEFAULT NULL,
  `offer_percent` decimal(5,2) DEFAULT NULL,
  `offer_starts_at` datetime DEFAULT NULL,
  `offer_ends_at` datetime DEFAULT NULL,
  `force_offer_badge` tinyint(1) NOT NULL DEFAULT 0,
  `image_url` varchar(255) NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `artist_id` int(10) UNSIGNED DEFAULT NULL,
  `is_combo` tinyint(1) NOT NULL DEFAULT 0,
  `availability` enum('in_stock','out_of_stock','made_to_order') NOT NULL DEFAULT 'in_stock',
  `requires_customization` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `average_rating` decimal(3,2) DEFAULT NULL COMMENT 'Average rating (1.00-5.00)',
  `total_ratings` int(11) DEFAULT 0 COMMENT 'Total number of ratings',
  `rating_updated_at` timestamp NULL DEFAULT NULL COMMENT 'Last time ratings were calculated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `artworks`
--

INSERT INTO `artworks` (`id`, `title`, `description`, `pricing_schema`, `price`, `weight`, `offer_price`, `offer_percent`, `offer_starts_at`, `offer_ends_at`, `force_offer_badge`, `image_url`, `category_id`, `artist_id`, `is_combo`, `availability`, `requires_customization`, `status`, `created_at`, `updated_at`, `average_rating`, `total_ratings`, `rating_updated_at`) VALUES
(2, 'wedding card', 'per card 50 rupees', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 50.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/wedding_card_20250909_162709_3882f9.png', 6, 5, 0, 'in_stock', 1, 'active', '2025-09-09 08:57:09', '2025-10-16 03:49:28', NULL, 0, NULL),
(3, 'poloroids', '20 photos 150 ruppes', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 150.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/poloroid_20250909_162827_bad52f.png', 4, 5, 0, 'in_stock', 1, 'active', '2025-09-09 08:58:27', '2025-10-16 03:49:28', NULL, 0, NULL),
(4, '4 * 4 frame', 'mini frame', '{\"options\":{\"size\":{\"type\":\"select\",\"values\":[{\"value\":\"A5\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"A4\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"A3\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"frame\":{\"type\":\"select\",\"values\":[{\"value\":\"none\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"basic\",\"delta\":{\"type\":\"flat\",\"value\":199}},{\"value\":\"premium\",\"delta\":{\"type\":\"flat\",\"value\":399}}]},\"material\":{\"type\":\"select\",\"values\":[{\"value\":\"paper\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"canvas\",\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 120.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/4_4_frame_20250909_163041_e6da75.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:00:41', '2025-10-16 03:49:28', NULL, 0, NULL),
(5, '6 * 4', 'best friends frames', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 250.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/6_4_frame_20250909_163511_f22d0e.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:05:11', '2025-10-16 03:49:28', NULL, 0, NULL),
(6, 'A3 frame', 'cartoon frame', '{\"options\":{\"size\":{\"type\":\"select\",\"values\":[{\"value\":\"A5\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"A4\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"A3\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"frame\":{\"type\":\"select\",\"values\":[{\"value\":\"none\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"basic\",\"delta\":{\"type\":\"flat\",\"value\":199}},{\"value\":\"premium\",\"delta\":{\"type\":\"flat\",\"value\":399}}]},\"material\":{\"type\":\"select\",\"values\":[{\"value\":\"paper\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"canvas\",\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 749.99, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/A3_frame_20250909_163546_05748c.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:05:46', '2025-10-16 03:49:28', NULL, 0, NULL),
(7, 'album', 'carboard sheet album', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 200.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/album_20250909_163744_d02862.png', 8, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:07:44', '2025-10-16 03:49:28', NULL, 0, NULL),
(8, 'boaqutes', 'red rose boaqutes', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 400.00, 0.50, 300.00, 99.25, '2025-09-25 12:07:00', '2025-09-26 12:07:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/boaqutes_20250909_163941_3994ac.png', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:09:41', '2025-10-16 03:49:28', NULL, 0, NULL),
(10, 'Custom Drawing', 'Sketches', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 1000.00, 0.50, 700.00, 99.30, '2025-10-22 22:51:00', '2025-10-29 22:52:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/drawings_20250909_164237_1da7b5.png', 7, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:12:37', '2025-10-22 11:52:10', NULL, 0, NULL),
(11, 'gift set', 'it consist of gift box boaqutes frames', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 3000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/gift_box_set_20250909_164422_32ae0c.png', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:14:22', '2025-10-16 03:49:28', NULL, 0, NULL),
(12, 'gift box', 'giftbox consist of chocolates and watch', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 2000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/gift_box_20250909_164839_021d32.png', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:18:39', '2025-10-16 03:49:28', NULL, 0, NULL),
(13, 'Micro frame', 'micro frame', '{\"options\":{\"size\":{\"type\":\"select\",\"values\":[{\"value\":\"A5\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"A4\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"A3\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"frame\":{\"type\":\"select\",\"values\":[{\"value\":\"none\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"basic\",\"delta\":{\"type\":\"flat\",\"value\":199}},{\"value\":\"premium\",\"delta\":{\"type\":\"flat\",\"value\":399}}]},\"material\":{\"type\":\"select\",\"values\":[{\"value\":\"paper\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"canvas\",\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 90.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/micro_frame_20250909_164926_56f796.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:19:26', '2025-10-16 03:49:28', NULL, 0, NULL),
(14, 'mini frame', 'mini frame', '{\"options\":{\"size\":{\"type\":\"select\",\"values\":[{\"value\":\"A5\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"A4\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"A3\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"frame\":{\"type\":\"select\",\"values\":[{\"value\":\"none\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"basic\",\"delta\":{\"type\":\"flat\",\"value\":199}},{\"value\":\"premium\",\"delta\":{\"type\":\"flat\",\"value\":399}}]},\"material\":{\"type\":\"select\",\"values\":[{\"value\":\"paper\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"canvas\",\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 149.99, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/mini_frame_20250909_165028_7eaa02.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:20:28', '2025-10-16 03:49:28', NULL, 0, NULL),
(15, 'album', '20  photos 150', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 150.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/poloroid__2__20250909_165107_4ac16f.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:21:07', '2025-10-16 03:49:28', NULL, 0, NULL),
(16, 'Hamper', 'wedding hamper', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 2000.00, 0.50, 1500.00, 25.00, '2025-10-28 14:36:00', '2025-10-25 14:36:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/Wedding_hamper_20250909_165223_b117bb.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-09 09:22:23', '2025-10-25 03:36:25', NULL, 0, NULL),
(18, 'wedding set', 'set in wedding', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 6000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/Celebrate_life_s_special_moments_with____20250921_153611_602073.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-21 08:06:11', '2025-10-16 03:49:28', NULL, 0, NULL),
(19, 'boquetes', 'pink flowers', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 500.00, 0.50, 320.00, 99.40, '2025-10-08 19:09:00', '2025-10-09 19:09:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/_artsybaken_20250921_153825_9108f6.jpg', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-21 08:08:25', '2025-10-16 03:49:28', NULL, 0, NULL),
(20, 'trolly hamper', 'birthday hamper', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 1000.00, 0.50, 599.99, 99.40, '2025-10-01 14:28:00', '2025-10-09 14:28:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/Birthday_hamper_20250921_153957_58a57d.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-21 08:09:57', '2025-10-16 03:49:28', NULL, 0, NULL),
(21, 'shirt box', 'shirt hamper', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 500.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/_trousseau__weddingpackaging__giftboxforhim__hamperbox__sliderbox__birthdayhamper__hamperforher__hamperforhim__nammasalem_hamper__hampers__giftbox__instagramreels_r_20250921_154121_26dfde.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-21 08:11:21', '2025-10-16 03:49:28', NULL, 0, NULL),
(22, 'nutt box', 'nuts box', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 1000.00, 0.50, 910.00, 99.10, '2025-09-25 19:07:00', '2025-09-30 19:08:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__3__20250923_162849_9b1084.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-23 08:58:49', '2025-10-16 03:49:28', NULL, 0, NULL),
(23, 'perfume box', 'perfume+watch', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 1000.00, 0.50, 800.00, 20.00, '2025-10-01 14:05:00', '2025-10-10 14:05:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/Perfume_Gift_ideas_watch_gift_ideas_20250923_163345_8fe78d.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-23 09:03:45', '2025-10-16 03:49:28', NULL, 0, NULL),
(24, 'poloroid boquetes', 'boquestes with poloroid', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 500.04, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/beautiful_photos_bouquet______20250923_163516_fd2fb9.jpg', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-23 09:05:16', '2025-10-16 03:49:28', NULL, 0, NULL),
(25, 'kinderjoy boquetes', 'kinderjoy boquetes', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 600.00, 0.50, 400.00, 99.33, '2025-10-01 14:02:00', '2025-10-08 14:02:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__6__20250923_163633_52f745.jpg', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-23 09:06:33', '2025-10-16 03:49:28', NULL, 0, NULL),
(27, 'custom chocoltes', 'choocoo', '{\"options\":{\"flavor\":{\"type\":\"select\",\"values\":[{\"value\":\"milk\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"dark\",\"delta\":{\"type\":\"flat\",\"value\":20}},{\"value\":\"white\",\"delta\":{\"type\":\"flat\",\"value\":10}}]},\"boxSize\":{\"type\":\"select\",\"values\":[{\"value\":\"6pc\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"12pc\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"24pc\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"messageLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 25.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/mschocoworld_-_9952979286_20250923_164331_87efb6.jpg', 5, 5, 0, 'in_stock', 0, 'active', '2025-09-23 09:13:31', '2025-10-16 03:49:28', NULL, 0, NULL),
(29, 'birthday gift', 'gift box', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 2000.00, 0.50, NULL, 20.00, '2025-09-24 21:09:00', '2025-09-26 21:10:00', 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__8__20250924_174025_9f9ae8.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-24 10:10:25', '2025-10-16 03:49:28', NULL, 0, NULL),
(32, 'heart boquetes', 'valentin\'s day gift', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 600.00, 0.50, 399.96, NULL, '2025-09-25 11:04:00', '2025-09-27 11:04:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__9__20250925_073531_8b855b.jpg', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-25 00:05:31', '2025-10-16 03:49:28', NULL, 0, NULL),
(33, 'wedding card', 'blue theme', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 54.98, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__11__20250925_154744_c5c9fc.jpg', 6, 5, 0, 'in_stock', 0, 'active', '2025-09-25 08:17:44', '2025-10-16 03:49:28', NULL, 0, NULL),
(34, 'wedding card', 'red theme', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 50.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/31_Wedding_Card_Ideas_20250925_154819_53c009.jpg', 6, 5, 0, 'in_stock', 0, 'active', '2025-09-25 08:18:19', '2025-10-16 03:49:28', NULL, 0, NULL),
(36, 'birthday card', 'consist of chocolate and pen', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 19.99, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__10__20250925_155002_5379f7.jpg', NULL, 5, 0, 'in_stock', 0, 'active', '2025-09-25 08:20:02', '2025-10-16 03:49:28', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `auth_providers`
--

CREATE TABLE `auth_providers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `provider` enum('google') NOT NULL,
  `provider_user_id` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auth_providers`
--

INSERT INTO `auth_providers` (`id`, `user_id`, `provider`, `provider_user_id`, `created_at`) VALUES
(2, 1, 'google', '102325132871747177182', '2025-08-15 23:09:32'),
(3, 2, 'google', '104067605047026890772', '2025-08-15 23:10:07'),
(4, 5, 'google', '107043815178028470916', '2025-09-09 05:09:25'),
(7, 8, 'google', '106031145699305807929', '2025-09-13 00:38:52'),
(8, 9, 'google', '115496851249528699969', '2025-09-15 02:52:24'),
(9, 11, 'google', '110350776552417646009', '2025-09-17 00:52:37'),
(10, 12, 'google', '114542462885540925445', '2025-09-22 03:20:48'),
(11, 13, 'google', '116968811920977241300', '2025-09-22 08:48:56'),
(12, 14, 'google', '104464372294846036228', '2025-09-22 08:54:05'),
(13, 17, 'google', '100677287622416457024', '2025-10-21 05:15:37'),
(14, 18, 'google', '112078509104401590064', '2025-10-24 00:49:25'),
(15, 19, 'google', '108768002618889328675', '2026-01-04 02:56:57');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `artwork_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `artwork_id`, `quantity`, `added_at`) VALUES
(52, 1, 27, 1, '2025-10-20 12:11:53'),
(53, 1, 22, 1, '2025-10-20 12:26:59'),
(75, 11, 10, 1, '2025-10-31 04:21:48'),
(77, 13, 27, 1, '2025-12-26 10:40:40');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`) VALUES
(1, 'Gift box', 'Gift box', 'active', '2025-09-09 05:06:50'),
(2, 'boquetes', 'boquetes', 'active', '2025-09-09 05:06:50'),
(3, 'frames', 'frames', 'active', '2025-09-09 05:06:50'),
(4, 'poloroid', 'poloroid', 'active', '2025-09-09 05:06:50'),
(5, 'custom chocolate', 'custom chocolate', 'active', '2025-09-09 05:06:50'),
(6, 'Wedding card', 'Wedding card', 'active', '2025-09-09 05:06:50'),
(7, 'drawings', 'drawings', 'active', '2025-09-09 05:06:50'),
(8, 'album', 'album', 'active', '2025-09-09 05:06:50');

-- --------------------------------------------------------

--
-- Table structure for table `courier_serviceability_cache`
--

CREATE TABLE `courier_serviceability_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `pickup_pincode` varchar(10) NOT NULL,
  `delivery_pincode` varchar(10) NOT NULL,
  `weight` decimal(10,2) NOT NULL,
  `cod` tinyint(1) NOT NULL DEFAULT 0,
  `courier_data` text NOT NULL COMMENT 'JSON data of available couriers',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_requests`
--

CREATE TABLE `custom_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` varchar(100) NOT NULL DEFAULT '',
  `customer_id` int(10) UNSIGNED DEFAULT 0,
  `customer_name` varchar(255) NOT NULL DEFAULT '',
  `customer_email` varchar(255) NOT NULL DEFAULT '',
  `customer_phone` varchar(50) DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `occasion` varchar(100) DEFAULT '',
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `budget_min` decimal(10,2) DEFAULT 500.00,
  `budget_max` decimal(10,2) DEFAULT 1000.00,
  `deadline` date DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('submitted','pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `design_url` varchar(500) DEFAULT '',
  `source` enum('form','cart','admin') DEFAULT 'form',
  `artwork_id` int(11) DEFAULT NULL,
  `requested_quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_requests`
--

INSERT INTO `custom_requests` (`id`, `user_id`, `order_id`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `title`, `occasion`, `description`, `requirements`, `budget_min`, `budget_max`, `deadline`, `priority`, `status`, `admin_notes`, `design_url`, `source`, `artwork_id`, `requested_quantity`, `created_at`, `updated_at`) VALUES
(8, NULL, 'CR-20260108-029', 13, 'Customer #13', '', '', 'bdygift', 'Birthday', 'vhjhnjdfmae', '', 2000.00, 1000.00, '2026-01-09', 'medium', 'cancelled', '', '', 'form', NULL, 1, '2026-01-06 07:32:15', '2026-01-08 04:26:08'),
(9, NULL, 'CR-20260108-028', 13, 'Customer #13', '', '', 'bestfriendframe', 'Birthday', 'ineedaframe', '', 2000.00, 1000.00, '2026-01-08', 'medium', 'cancelled', '', '', 'form', NULL, 1, '2026-01-04 10:36:38', '2026-01-08 06:01:12'),
(10, NULL, 'CR-20260108-027', 11, 'Customer #11', '', '', 'Cart customization - anniversary - 2025-10-29', 'anniversary', 'klkmk', '', 500.00, 1000.00, '2025-10-29', 'medium', 'cancelled', '', '', 'cart', NULL, 1, '2025-10-28 17:43:01', '2026-01-08 06:01:13'),
(11, NULL, 'CR-20260108-026', 18, 'Customer #18', '', '', 'Cart customization - birthday - 2025-10-26', 'birthday', 'vfbggnny', '', 500.00, 1000.00, '2025-10-26', 'medium', 'cancelled', '', '', 'cart', NULL, 1, '2025-10-25 03:31:30', '2026-01-08 06:01:26'),
(12, NULL, 'CR-20260108-025', 11, 'Customer #11', '', '', 'birthday gift', 'Birthday', 'blue theme', '', 1999.99, 1000.00, '2025-10-22', 'medium', 'cancelled', '', '', 'form', NULL, 1, '2025-10-21 02:53:59', '2026-01-08 06:01:28'),
(13, NULL, 'CR-20260108-024', 11, 'Customer #11', '', '', 'Cart customization - baby_shower - 2025-10-02', 'baby_shower', 'fhghhj', '', 500.00, 1000.00, '2025-10-02', 'medium', 'cancelled', '', '', 'cart', NULL, 1, '2025-09-23 09:15:45', '2026-01-08 06:01:30'),
(14, NULL, 'CR-20260108-023', 11, 'Customer #11', '', '', 'Cart customization - birthday - 2025-10-02', 'birthday', 'need a micro frame', '', 500.00, 1000.00, '2025-10-02', 'medium', 'cancelled', '', '', 'cart', NULL, 1, '2025-09-23 08:59:50', '2026-01-08 06:01:31'),
(15, NULL, 'CR-20260108-022', 11, 'Customer #11', '', '', 'bd', 'Birthday', 'yghjjkh', '', 200.00, 1000.00, '2025-09-25', 'medium', 'cancelled', '', '', 'form', NULL, 1, '2025-09-23 00:35:35', '2026-01-08 06:01:15'),
(16, NULL, 'CR-20260108-021', 1, 'Customer #1', '', '', 'Cart customization - birthday - 2025-09-24', 'birthday', 'blue theme', '', 500.00, 1000.00, '2025-09-24', 'medium', 'cancelled', '', '', 'cart', NULL, 1, '2025-09-20 09:04:03', '2026-01-08 06:01:17'),
(17, NULL, 'CR-20260108-020', 11, 'Customer #11', '', '', 'Cart customization - birthday - 2025-09-23', 'birthday', 'i need 10 photos', '', 500.00, 1000.00, '2025-09-23', 'medium', 'cancelled', '', '', 'cart', NULL, 1, '2025-09-20 08:13:12', '2026-01-08 03:49:27'),
(18, NULL, 'CR-20260108-019', 11, 'Customer #11', '', '', 'Cart customization - wedding - 2025-09-19', 'wedding', '                                         ttttttttttttttttt            \r\n                                     \r\n                         ', '', 500.00, 1000.00, '2025-09-19', 'medium', 'cancelled', '', '', 'cart', NULL, 1, '2025-09-18 02:44:00', '2026-01-08 06:01:33'),
(19, NULL, 'CR-20260108-018', 11, 'Customer #11', '', '', 'Cart customization - other - 2025-09-18', 'other', 'rfrrf4434443', '', 500.00, 1000.00, '2025-09-18', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-18 02:42:04', '2026-01-08 03:49:27'),
(20, NULL, 'CR-20260108-017', 10, 'Customer #10', '', '', 'wedding', 'Wedding', 'yff', '', 2000.00, 1000.00, '2025-09-30', 'medium', 'completed', '', '', 'form', NULL, 1, '2025-09-16 23:15:11', '2026-01-08 03:49:27'),
(21, NULL, 'CR-20260108-016', 9, 'Customer #9', '', '', 'wedding card', 'Wedding', 'photo card', '', 200.00, 1000.00, '2025-09-17', 'medium', 'cancelled', '', '', 'form', NULL, 1, '2025-09-15 05:27:00', '2026-01-08 03:49:27'),
(22, NULL, 'CR-20260108-015', 9, 'Customer #9', '', '', 'birthday  gift', 'Birthday', 'golden theme gift box', '', 2000.00, 1000.00, '2025-09-18', 'medium', 'completed', '', '', 'form', NULL, 1, '2025-09-15 04:50:45', '2026-01-08 03:49:27'),
(23, NULL, 'CR-20260108-014', 1, 'Customer #1', '', '', 'Cart customization - birthday - 2025-09-18', 'birthday', 'red rosses', '', 500.00, 1000.00, '2025-09-18', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-13 12:21:41', '2026-01-08 03:49:27'),
(24, NULL, 'CR-20260108-013', 1, 'Customer #1', '', '', 'Cart customization - birthday - 2025-09-20', 'birthday', 'gift set', '', 500.00, 1000.00, '2025-09-20', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-13 00:20:19', '2026-01-08 03:49:27'),
(25, NULL, 'CR-20260108-012', 1, 'Customer #1', '', '', 'Cart customization - wedding - 2025-09-17', 'wedding', 'wedding card', '', 500.00, 1000.00, '2025-09-17', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-12 22:59:21', '2026-01-08 03:49:27'),
(26, NULL, 'CR-20260108-011', 1, 'Customer #1', '', '', 'anniversary gift', 'Anniversary', 'gift box consist of chocolates', '', 2000.00, 1000.00, '2025-09-15', 'medium', 'completed', '', '', 'form', NULL, 1, '2025-09-12 05:05:32', '2026-01-08 03:49:27'),
(27, NULL, 'CR-20260108-010', 1, 'Customer #1', '', '', 'Cart customization - birthday - 2025-09-15', 'birthday', 'frame', '', 500.00, 1000.00, '2025-09-15', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-12 04:46:53', '2026-01-08 03:49:27'),
(28, NULL, 'CR-20260108-009', 1, 'Customer #1', '', '', 'Cart customization - birthday - 2025-09-14', 'birthday', 'poloroids with 10 photos', '', 500.00, 1000.00, '2025-09-14', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-12 04:42:12', '2026-01-08 03:49:27'),
(29, NULL, 'CR-20260108-008', 1, 'Customer #1', '', '', 'Cart customization - valentine - 2025-09-30', 'valentine', 'kbhxbh', '', 500.00, 1000.00, '2025-09-30', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-12 03:03:21', '2026-01-08 03:49:27'),
(30, NULL, 'CR-20260108-007', 1, 'Customer #1', '', '', 'Cart customization - baby_shower - 2025-09-30', 'baby_shower', 'jhhh', '', 500.00, 1000.00, '2025-09-30', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-12 03:00:34', '2026-01-08 03:49:27'),
(31, NULL, 'CR-20260108-006', 1, 'Customer #1', '', '', 'Cart customization - baby_shower - 2025-09-30', 'baby_shower', 'bh', '', 500.00, 1000.00, '2025-09-30', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-12 02:57:06', '2026-01-08 03:49:27'),
(32, NULL, 'CR-20260108-005', 1, 'Customer #1', '', '', 'Cart customization - wedding - 2025-09-18', 'wedding', 'purple theme', '', 500.00, 1000.00, '2025-09-18', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-12 02:53:37', '2026-01-08 03:49:27'),
(33, NULL, 'CR-20260108-004', 1, 'Customer #1', '', '', 'Cart customization - christmas - 2025-09-30', 'christmas', 'vft', '', 500.00, 1000.00, '2025-09-30', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-12 02:47:26', '2026-01-08 03:49:27'),
(34, NULL, 'CR-20260108-003', 1, 'Customer #1', '', '', 'Cart customization - wedding - 2025-09-19', 'wedding', 'red flower ', '', 500.00, 1000.00, '2025-09-19', 'medium', 'completed', '', '', 'cart', NULL, 1, '2025-09-12 02:45:05', '2026-01-08 03:49:27'),
(35, NULL, 'CR-20260108-002', 1, 'Customer #1', '', '', 'bjksjkds', 'wedding', 'kd ndjk', '', 39.98, 1000.00, '2025-09-26', 'medium', 'completed', '', '', 'form', NULL, 1, '2025-09-12 00:39:13', '2026-01-08 03:49:27'),
(36, NULL, 'CR-20260108-001', 1, 'Customer #1', '', '', 'gift box for birthday', 'Birthday', 'gift box consist of chocolates', '', 500.00, 1000.00, '2025-09-15', 'medium', 'completed', '', '', 'form', NULL, 1, '2025-09-11 02:46:30', '2026-01-08 03:49:27'),
(37, NULL, '', 13, '', '', '', 'Cart customization - birthday - 2026-01-16', 'birthday', 'nxhxhhcgjdtdtr', NULL, NULL, NULL, '2026-01-16', 'medium', 'in_progress', NULL, '', 'cart', 4, 1, '2026-01-08 05:51:45', '2026-01-08 07:38:20'),
(38, NULL, '', 13, '', '', '', 'Cart customization - birthday - 2026-01-10', 'birthday', 'gvjhknjo;kplklkljkjhghfgfgf', NULL, NULL, NULL, '2026-01-10', 'medium', 'completed', NULL, '', 'cart', 27, 1, '2026-01-08 06:05:23', '2026-01-08 10:18:25');

-- --------------------------------------------------------

--
-- Table structure for table `custom_requests_backup`
--

CREATE TABLE `custom_requests_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `occasion` varchar(100) DEFAULT NULL,
  `budget_min` decimal(10,2) DEFAULT NULL,
  `budget_max` decimal(10,2) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `gift_tier` enum('budget','premium') DEFAULT 'budget',
  `source` enum('form','cart') NOT NULL DEFAULT 'form',
  `artwork_id` int(11) DEFAULT NULL,
  `requested_quantity` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_requests_backup`
--

INSERT INTO `custom_requests_backup` (`id`, `user_id`, `title`, `description`, `category_id`, `occasion`, `budget_min`, `budget_max`, `deadline`, `special_instructions`, `gift_tier`, `source`, `artwork_id`, `requested_quantity`, `status`, `created_at`) VALUES
(1, 1, 'gift box for birthday', 'gift box consist of chocolates', NULL, 'Birthday', 500.00, NULL, '2025-09-15', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-11 02:46:30'),
(2, 1, 'bjksjkds', 'kd ndjk', NULL, 'wedding', 39.98, NULL, '2025-09-26', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-12 00:39:13'),
(3, 1, 'Cart customization - wedding - 2025-09-19', 'red flower ', NULL, 'wedding', NULL, NULL, '2025-09-19', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 02:45:05'),
(4, 1, 'Cart customization - christmas - 2025-09-30', 'vft', NULL, 'christmas', NULL, NULL, '2025-09-30', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 02:47:26'),
(5, 1, 'Cart customization - wedding - 2025-09-18', 'purple theme', NULL, 'wedding', NULL, NULL, '2025-09-18', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 02:53:37'),
(6, 1, 'Cart customization - baby_shower - 2025-09-30', 'bh', NULL, 'baby_shower', NULL, NULL, '2025-09-30', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 02:57:06'),
(7, 1, 'Cart customization - baby_shower - 2025-09-30', 'jhhh', NULL, 'baby_shower', NULL, NULL, '2025-09-30', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 03:00:34'),
(8, 1, 'Cart customization - valentine - 2025-09-30', 'kbhxbh', NULL, 'valentine', NULL, NULL, '2025-09-30', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 03:03:21'),
(9, 1, 'Cart customization - birthday - 2025-09-14', 'poloroids with 10 photos', NULL, 'birthday', NULL, NULL, '2025-09-14', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 04:42:12'),
(10, 1, 'Cart customization - birthday - 2025-09-15', 'frame', NULL, 'birthday', NULL, NULL, '2025-09-15', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 04:46:53'),
(11, 1, 'anniversary gift', 'gift box consist of chocolates', NULL, 'Anniversary', 2000.00, NULL, '2025-09-15', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-12 05:05:32'),
(12, 1, 'Cart customization - wedding - 2025-09-17', 'wedding card', NULL, 'wedding', NULL, NULL, '2025-09-17', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 22:59:21'),
(13, 1, 'Cart customization - birthday - 2025-09-20', 'gift set', NULL, 'birthday', NULL, NULL, '2025-09-20', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-13 00:20:19'),
(14, 1, 'Cart customization - birthday - 2025-09-18', 'red rosses', NULL, 'birthday', NULL, NULL, '2025-09-18', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-13 12:21:41'),
(15, 9, 'birthday  gift', 'golden theme gift box', NULL, 'Birthday', 2000.00, NULL, '2025-09-18', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-15 04:50:45'),
(16, 9, 'wedding card', 'photo card', NULL, 'Wedding', 200.00, NULL, '2025-09-17', '', 'budget', 'form', NULL, 1, 'cancelled', '2025-09-15 05:27:00'),
(17, 10, 'wedding', 'yff', NULL, 'Wedding', 2000.00, NULL, '2025-09-30', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-16 23:15:11'),
(18, 11, 'Cart customization - other - 2025-09-18', 'rfrrf4434443', NULL, 'other', NULL, NULL, '2025-09-18', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-18 02:42:04'),
(19, 11, 'Cart customization - wedding - 2025-09-19', '                                         ttttttttttttttttt            \r\n                                     \r\n                         ', NULL, 'wedding', NULL, NULL, '2025-09-19', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-18 02:44:00'),
(20, 11, 'Cart customization - birthday - 2025-09-23', 'i need 10 photos', NULL, 'birthday', NULL, NULL, '2025-09-23', '', 'budget', 'cart', NULL, 1, 'cancelled', '2025-09-20 08:13:12'),
(21, 1, 'Cart customization - birthday - 2025-09-24', 'blue theme', NULL, 'birthday', NULL, NULL, '2025-09-24', '', 'budget', 'cart', NULL, 1, 'pending', '2025-09-20 09:04:03'),
(22, 11, 'bd', 'yghjjkh', NULL, 'Birthday', 200.00, NULL, '2025-09-25', '', 'budget', 'form', NULL, 1, 'pending', '2025-09-23 00:35:35'),
(23, 11, 'Cart customization - birthday - 2025-10-02', 'need a micro frame', NULL, 'birthday', NULL, NULL, '2025-10-02', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-23 08:59:50'),
(24, 11, 'Cart customization - baby_shower - 2025-10-02', 'fhghhj', NULL, 'baby_shower', NULL, NULL, '2025-10-02', '', 'budget', 'cart', 27, 1, 'completed', '2025-09-23 09:15:45'),
(25, 11, 'birthday gift', 'blue theme', NULL, 'Birthday', 1999.99, NULL, '2025-10-22', '', 'premium', 'form', NULL, 1, 'completed', '2025-10-21 02:53:59'),
(26, 18, 'Cart customization - birthday - 2025-10-26', 'vfbggnny', NULL, 'birthday', NULL, NULL, '2025-10-26', '', 'budget', 'cart', 27, 1, 'completed', '2025-10-25 03:31:30'),
(27, 11, 'Cart customization - anniversary - 2025-10-29', 'klkmk', NULL, 'anniversary', NULL, NULL, '2025-10-29', '', 'budget', 'cart', 4, 1, 'pending', '2025-10-28 17:43:01'),
(28, 13, 'bestfriendframe', 'ineedaframe', NULL, 'Birthday', 2000.00, NULL, '2026-01-08', '', 'premium', 'form', NULL, 1, 'pending', '2026-01-04 10:36:38'),
(29, 13, 'bdygift', 'vhjhnjdfmae', NULL, 'Birthday', 2000.00, NULL, '2026-01-09', '', 'budget', 'form', NULL, 1, 'pending', '2026-01-06 07:32:15');

-- --------------------------------------------------------

--
-- Table structure for table `custom_request_designs`
--

CREATE TABLE `custom_request_designs` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED DEFAULT NULL,
  `canvas_width` int(11) NOT NULL,
  `canvas_height` int(11) NOT NULL,
  `canvas_data` longtext DEFAULT NULL,
  `design_image_url` varchar(500) DEFAULT NULL,
  `design_pdf_url` varchar(500) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `status` enum('draft','designing','design_completed','approved','rejected') DEFAULT 'draft',
  `admin_notes` text DEFAULT NULL,
  `customer_feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `template_version` varchar(50) DEFAULT NULL,
  `export_format` enum('png','pdf','jpg') DEFAULT 'png',
  `export_quality` enum('draft','standard','high','print') DEFAULT 'standard',
  `is_template_locked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_request_designs`
--

INSERT INTO `custom_request_designs` (`id`, `request_id`, `template_id`, `canvas_width`, `canvas_height`, `canvas_data`, `design_image_url`, `design_pdf_url`, `version`, `status`, `admin_notes`, `customer_feedback`, `created_at`, `updated_at`, `template_version`, `export_format`, `export_quality`, `is_template_locked`) VALUES
(1, 38, 10, 1200, 1800, NULL, NULL, NULL, 1, 'draft', NULL, NULL, '2026-01-08 08:01:21', '2026-01-08 08:05:43', NULL, 'png', 'standard', 0),
(2, 38, 10, 1200, 1800, NULL, NULL, NULL, 2, 'draft', NULL, NULL, '2026-01-08 08:05:43', '2026-01-08 08:23:43', NULL, 'png', 'standard', 0),
(3, 38, 10, 1200, 1800, NULL, NULL, NULL, 3, 'draft', NULL, NULL, '2026-01-08 08:23:43', '2026-01-08 08:31:49', NULL, 'png', 'standard', 0),
(4, 38, 10, 1200, 1800, NULL, NULL, NULL, 4, 'draft', NULL, NULL, '2026-01-08 08:31:49', '2026-01-08 08:37:23', NULL, 'png', 'standard', 0),
(5, 38, 10, 1200, 1800, NULL, NULL, NULL, 5, 'draft', NULL, NULL, '2026-01-08 08:37:23', '2026-01-08 08:43:40', NULL, 'png', 'standard', 0),
(6, 38, 257, 1200, 1800, NULL, NULL, NULL, 6, 'draft', NULL, NULL, '2026-01-08 08:43:40', '2026-01-08 08:58:45', NULL, 'png', 'standard', 0),
(7, 38, 395, 2400, 2400, NULL, NULL, NULL, 7, 'draft', NULL, NULL, '2026-01-08 08:58:45', '2026-01-08 08:59:18', NULL, 'png', 'standard', 0),
(8, 38, 371, 3300, 4200, NULL, NULL, NULL, 8, 'draft', NULL, NULL, '2026-01-08 08:59:18', '2026-01-08 09:20:22', NULL, 'png', 'standard', 0),
(9, 38, 371, 3300, 4200, NULL, NULL, NULL, 9, 'draft', NULL, NULL, '2026-01-08 09:20:22', '2026-01-08 10:13:39', NULL, 'png', 'standard', 0),
(10, 38, 384, 1500, 2100, NULL, NULL, NULL, 10, 'designing', NULL, NULL, '2026-01-08 10:13:39', '2026-01-08 10:13:39', NULL, 'png', 'standard', 0);

-- --------------------------------------------------------

--
-- Table structure for table `custom_request_images`
--

CREATE TABLE `custom_request_images` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_request_images`
--

INSERT INTO `custom_request_images` (`id`, `request_id`, `image_path`, `uploaded_at`) VALUES
(1, 36, 'uploads/custom-requests/68c2855e19ea14.31233524_giftbox.png', '2025-09-11 02:46:30'),
(2, 35, 'uploads/custom-requests/admin_68c3b95b0cf748.50349646_Screenshot_2025-08-19_110222.png', '2025-09-12 00:40:35'),
(3, 35, 'uploads/custom-requests/admin_68c3b96ac09dc7.23792864_Screenshot_2025-08-19_110222.png', '2025-09-12 00:40:50'),
(4, 34, 'uploads/custom-requests/cart_68c3d6898ac679.34186019_Screenshot_2025-08-13_200022.png', '2025-09-12 02:45:05'),
(5, 33, 'uploads/custom-requests/cart_68c3d716e4db56.32790334_giftbox.png', '2025-09-12 02:47:26'),
(6, 34, 'uploads/custom-requests/admin_68c3d75443ba16.17243950_giftbox.png', '2025-09-12 02:48:28'),
(7, 32, 'uploads/custom-requests/cart_68c3d889996fa4.88738438_Screenshot_2025-08-13_194304.png', '2025-09-12 02:53:37'),
(8, 36, 'uploads/custom-requests/admin_68c3d8bf0454d1.40723189_giftbox.png', '2025-09-12 02:54:31'),
(9, 31, 'uploads/custom-requests/cart_68c3d95a7217c7.86892870_giftbox.png', '2025-09-12 02:57:06'),
(10, 30, 'uploads/custom-requests/cart_68c3da2a964ba6.21480005_giftbox.png', '2025-09-12 03:00:34'),
(11, 29, 'uploads/custom-requests/cart_68c3dad16ba016.50878251_giftbox.png', '2025-09-12 03:03:21'),
(12, 28, 'uploads/custom-requests/cart_68c3f1fcee73d5.43732643_Screenshot_2024-07-26_192946.png', '2025-09-12 04:42:12'),
(13, 27, 'uploads/custom-requests/cart_68c3f315b41b92.89872427_Screenshot_2025-08-06_215747.png', '2025-09-12 04:46:53'),
(14, 26, 'uploads/custom-requests/68c3f774d06da0.88489536_giftbox.png', '2025-09-12 05:05:32'),
(15, 25, 'uploads/custom-requests/cart_68c4f321f10f91.48701177_Screenshot_2025-08-13_194304.png', '2025-09-12 22:59:21'),
(16, 24, 'uploads/custom-requests/cart_68c5061b7f7669.76286331_Screenshot_2025-08-13_195837.png', '2025-09-13 00:20:19'),
(17, 23, 'uploads/custom-requests/cart_68c5af2dc7ebb9.90802846_Screenshot_2025-08-13_200022.png', '2025-09-13 12:21:41'),
(18, 23, 'uploads/custom-requests/admin_68c5af609b2450.52810488_Screenshot_2025-08-13_200022.png', '2025-09-13 12:22:32'),
(19, 22, 'uploads/custom-requests/68c7e87d755500.73900552_giftbox.png', '2025-09-15 04:50:45'),
(20, 21, 'uploads/custom-requests/68c7f0fc6e88c9.85452840_Screenshot_2025-08-13_194304.png', '2025-09-15 05:27:00'),
(21, 20, 'uploads/custom-requests/68ca3cd7935c03.43426036_Screenshot_2024-07-26_193102.png', '2025-09-16 23:15:11'),
(22, 19, 'uploads/custom-requests/cart_68cbbed4b19772.52544926_adb.jpg', '2025-09-18 02:42:04'),
(23, 19, 'uploads/custom-requests/cart_68cbbf488ca693.53268668_adb.jpg', '2025-09-18 02:44:00'),
(24, 20, 'uploads/custom-requests/cart_68ceaf701e2720.84443167_adb.jpg', '2025-09-20 08:13:12'),
(25, 21, 'uploads/custom-requests/cart_68cebb5b62a1e1.92658194_adb.jpg', '2025-09-20 09:04:03'),
(26, 22, 'uploads/custom-requests/68d238af659946.33631684_admin_dashboard.png', '2025-09-23 00:35:35'),
(27, 23, 'uploads/custom-requests/cart_68d2aede511337.17953082_Butterfly_candle_holder.jpg', '2025-09-23 08:59:50'),
(28, 24, 'uploads/custom-requests/cart_68d2b2990846c8.97585563_download__6_.jpg', '2025-09-23 09:15:45'),
(29, 25, 'uploads/custom-requests/68f7431f9b9569.58251936_Screenshot_2025-08-13_195837.png', '2025-10-21 02:53:59'),
(30, 26, 'uploads/custom-requests/cart_68fc91eae5c126.67475275_a_chocolate_tower_______.jpeg', '2025-10-25 03:31:30'),
(31, 27, 'uploads/custom-requests/cart_690100a5b87301.96266802_giftbox.png', '2025-10-28 17:43:01'),
(32, 28, 'uploads/custom-requests/695a42b6548a43.63509223_Bows.jpg', '2026-01-04 10:36:38'),
(33, 29, 'uploads/custom-requests/695cba7f5cb200.45131359_Bows.jpg', '2026-01-06 07:32:15'),
(34, 37, 'uploads/custom-requests/cart_695f45f1c3cbf3.28056093_pathu.jpg', '2026-01-08 05:51:45'),
(35, 38, 'uploads/custom-requests/cart_695f4923ca1002.98989118_Screenshot_2024-07-26_192906.png', '2026-01-08 06:05:23');

-- --------------------------------------------------------

--
-- Table structure for table `design_templates`
--

CREATE TABLE `design_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `orientation` enum('portrait','landscape','square') NOT NULL,
  `unit` enum('px','mm','inch') DEFAULT 'px',
  `dpi` int(11) DEFAULT 300,
  `background_color` varchar(7) DEFAULT '#FFFFFF',
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `design_templates`
--

INSERT INTO `design_templates` (`id`, `name`, `width`, `height`, `orientation`, `unit`, `dpi`, `background_color`, `description`, `category`, `is_active`, `created_at`) VALUES
(1, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:01:19'),
(2, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:01:19'),
(3, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:01:19'),
(4, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:01:19'),
(5, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:01:19'),
(6, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:01:19'),
(7, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:01:19'),
(8, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:01:19'),
(9, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:01:19'),
(10, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:01:19'),
(11, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:01:19'),
(12, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:01:19'),
(13, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:01:19'),
(14, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:01:19'),
(15, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:01:19'),
(16, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:01:19'),
(17, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:01:19'),
(18, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:01:19'),
(19, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:01:19'),
(20, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:01:19'),
(21, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:01:19'),
(22, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:01:19'),
(23, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:01:19'),
(24, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:01:19'),
(25, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:01:19'),
(26, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:01:19'),
(27, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:01:21'),
(28, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:01:21'),
(29, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:01:21'),
(30, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:01:21'),
(31, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:01:21'),
(32, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:01:21'),
(33, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:01:21'),
(34, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:01:21'),
(35, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:01:21'),
(36, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:01:21'),
(37, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:01:21'),
(38, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:01:21'),
(39, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:01:21'),
(40, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:05:41'),
(41, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:05:41'),
(42, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:05:41'),
(43, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:05:41'),
(44, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:05:41'),
(45, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:05:41'),
(46, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:05:41'),
(47, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:05:41'),
(48, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:05:41'),
(49, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:05:41'),
(50, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:05:41'),
(51, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:05:41'),
(52, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:05:41'),
(53, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:05:41'),
(54, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:05:41'),
(55, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:05:41'),
(56, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:05:41'),
(57, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:05:41'),
(58, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:05:41'),
(59, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:05:41'),
(60, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:05:41'),
(61, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:05:41'),
(62, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:05:41'),
(63, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:05:41'),
(64, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:05:41'),
(65, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:05:41'),
(66, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:05:43'),
(67, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:05:43'),
(68, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:05:43'),
(69, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:05:43'),
(70, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:05:43'),
(71, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:05:43'),
(72, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:05:43'),
(73, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:05:43'),
(74, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:05:43'),
(75, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:05:43'),
(76, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:05:43'),
(77, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:05:43'),
(78, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:05:43'),
(79, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:18:01'),
(80, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:18:01'),
(81, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:18:01'),
(82, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:18:01'),
(83, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:18:01'),
(84, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:18:01'),
(85, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:18:01'),
(86, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:18:01'),
(87, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:18:01'),
(88, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:18:01'),
(89, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:18:01'),
(90, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:18:01'),
(91, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:18:01'),
(92, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:18:01'),
(93, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:18:01'),
(94, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:18:01'),
(95, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:18:01'),
(96, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:18:01'),
(97, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:18:01'),
(98, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:18:01'),
(99, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:18:01'),
(100, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:18:01'),
(101, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:18:01'),
(102, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:18:01'),
(103, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:18:01'),
(104, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:18:01'),
(105, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:23:41'),
(106, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:23:41'),
(107, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:23:41'),
(108, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:23:41'),
(109, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:23:41'),
(110, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:23:41'),
(111, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:23:41'),
(112, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:23:41'),
(113, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:23:41'),
(114, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:23:41'),
(115, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:23:41'),
(116, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:23:41'),
(117, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:23:41'),
(118, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:23:41'),
(119, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:23:41'),
(120, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:23:41'),
(121, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:23:41'),
(122, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:23:41'),
(123, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:23:41'),
(124, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:23:41'),
(125, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:23:41'),
(126, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:23:41'),
(127, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:23:41'),
(128, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:23:41'),
(129, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:23:41'),
(130, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:23:41'),
(131, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:23:43'),
(132, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:23:43'),
(133, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:23:43'),
(134, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:23:43'),
(135, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:23:43'),
(136, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:23:43'),
(137, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:23:43'),
(138, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:23:43'),
(139, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:23:43'),
(140, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:23:43'),
(141, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:23:43'),
(142, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:23:43'),
(143, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:23:43'),
(144, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:23:53'),
(145, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:23:53'),
(146, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:23:53'),
(147, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:23:53'),
(148, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:23:53'),
(149, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:23:53'),
(150, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:23:53'),
(151, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:23:53'),
(152, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:23:53'),
(153, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:23:53'),
(154, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:23:53'),
(155, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:23:53'),
(156, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:23:53'),
(157, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:23:53'),
(158, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:23:53'),
(159, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:23:53'),
(160, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:23:53'),
(161, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:23:53'),
(162, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:23:53'),
(163, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:23:53'),
(164, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:23:53'),
(165, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:23:53'),
(166, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:23:53'),
(167, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:23:53'),
(168, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:23:53'),
(169, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:23:53'),
(170, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:31:46'),
(171, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:31:46'),
(172, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:31:46'),
(173, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:31:46'),
(174, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:31:46'),
(175, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:31:46'),
(176, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:31:46'),
(177, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:31:46'),
(178, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:31:46'),
(179, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:31:46'),
(180, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:31:46'),
(181, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:31:46'),
(182, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:31:46'),
(183, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:31:46'),
(184, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:31:46'),
(185, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:31:46'),
(186, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:31:46'),
(187, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:31:46'),
(188, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:31:46'),
(189, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:31:46'),
(190, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:31:46'),
(191, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:31:46'),
(192, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:31:46'),
(193, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:31:46'),
(194, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:31:46'),
(195, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:31:46'),
(196, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:31:49'),
(197, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:31:49'),
(198, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:31:49'),
(199, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:31:49'),
(200, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:31:49'),
(201, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:31:49'),
(202, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:31:49'),
(203, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:31:49'),
(204, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:31:49'),
(205, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:31:49'),
(206, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:31:49'),
(207, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:31:49'),
(208, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:31:49'),
(209, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:37:18'),
(210, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:37:18'),
(211, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:37:18'),
(212, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:37:18'),
(213, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:37:18'),
(214, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:37:18'),
(215, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:37:18'),
(216, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:37:18'),
(217, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:37:18'),
(218, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:37:18'),
(219, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:37:18'),
(220, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:37:18'),
(221, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:37:18'),
(222, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:37:18'),
(223, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:37:18'),
(224, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:37:18'),
(225, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:37:18'),
(226, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:37:18'),
(227, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:37:18'),
(228, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:37:18'),
(229, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:37:18'),
(230, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:37:18'),
(231, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:37:18'),
(232, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:37:18'),
(233, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:37:18'),
(234, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:37:18'),
(235, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:37:23'),
(236, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:37:23'),
(237, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:37:23'),
(238, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:37:23'),
(239, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:37:23'),
(240, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:37:23'),
(241, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:37:23'),
(242, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:37:23'),
(243, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:37:23'),
(244, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:37:23'),
(245, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:37:23'),
(246, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:37:23'),
(247, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:37:23'),
(248, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:38:16'),
(249, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:38:16'),
(250, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:38:16'),
(251, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:38:16'),
(252, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:38:16'),
(253, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:38:16'),
(254, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:38:16'),
(255, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:38:16'),
(256, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:38:16'),
(257, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:38:16'),
(258, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:38:16'),
(259, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:38:16'),
(260, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:38:16'),
(261, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:38:16'),
(262, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:38:16'),
(263, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:38:16'),
(264, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:38:16'),
(265, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:38:16'),
(266, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:38:16'),
(267, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:38:16'),
(268, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:38:16'),
(269, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:38:16'),
(270, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:38:16'),
(271, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:38:16'),
(272, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:38:16'),
(273, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:38:16'),
(274, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:38:37'),
(275, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:38:37'),
(276, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:38:37'),
(277, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:38:37'),
(278, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:38:37'),
(279, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:38:37'),
(280, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:38:37'),
(281, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:38:37'),
(282, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:38:37'),
(283, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:38:37'),
(284, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:38:37'),
(285, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:38:37'),
(286, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:38:37'),
(287, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:38:48'),
(288, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:38:48'),
(289, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:38:48'),
(290, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:38:48'),
(291, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:38:48'),
(292, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:38:48'),
(293, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:38:48'),
(294, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:38:48'),
(295, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:38:48'),
(296, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:38:48'),
(297, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:38:48'),
(298, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:38:48'),
(299, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:38:48'),
(300, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:39:21'),
(301, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:39:21'),
(302, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:39:21'),
(303, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:39:21'),
(304, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:39:21'),
(305, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:39:21'),
(306, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:39:21'),
(307, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:39:21'),
(308, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:39:21'),
(309, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:39:21'),
(310, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:39:21'),
(311, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:39:21'),
(312, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:39:21'),
(313, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:39:33'),
(314, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:39:33'),
(315, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:39:33'),
(316, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:39:33'),
(317, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:39:33'),
(318, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:39:33'),
(319, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:39:33'),
(320, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:39:33'),
(321, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:39:33'),
(322, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:39:33'),
(323, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:39:33'),
(324, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:39:33'),
(325, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:39:33'),
(326, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:39:55'),
(327, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:39:55'),
(328, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:39:55'),
(329, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:39:55'),
(330, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:39:55'),
(331, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:39:55'),
(332, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:39:55'),
(333, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:39:55'),
(334, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:39:55'),
(335, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:39:55'),
(336, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:39:55'),
(337, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:39:55'),
(338, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:39:55'),
(339, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:40:26'),
(340, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:40:26'),
(341, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:40:26'),
(342, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:40:26'),
(343, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:40:26'),
(344, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:40:26'),
(345, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:40:26'),
(346, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:40:26'),
(347, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:40:26'),
(348, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:40:26'),
(349, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:40:26'),
(350, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:40:26'),
(351, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:40:26'),
(352, '4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo', 1, '2026-01-08 08:40:39'),
(353, '4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo', 1, '2026-01-08 08:40:39'),
(354, 'A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document', 1, '2026-01-08 08:40:39'),
(355, 'A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document', 1, '2026-01-08 08:40:39'),
(356, 'Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social', 1, '2026-01-08 08:40:39'),
(357, 'Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social', 1, '2026-01-08 08:40:39'),
(358, 'Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster', 1, '2026-01-08 08:40:39'),
(359, 'Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster', 1, '2026-01-08 08:40:39');
INSERT INTO `design_templates` (`id`, `name`, `width`, `height`, `orientation`, `unit`, `dpi`, `background_color`, `description`, `category`, `is_active`, `created_at`) VALUES
(360, 'Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card', 1, '2026-01-08 08:40:39'),
(361, 'Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card', 1, '2026-01-08 08:40:39'),
(362, 'Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Photo', 1, '2026-01-08 08:40:39'),
(363, 'Photo Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 5×7 inch', 'Frame', 1, '2026-01-08 08:40:39'),
(364, 'Photo Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Photo frame size 8×10 inch', 'Frame', 1, '2026-01-08 08:40:39'),
(365, '5×7 Photo', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard 5×7 inch photo print', 'Photo', 1, '2026-01-08 08:55:26'),
(366, '8×10 Photo', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Standard 8×10 inch photo print', 'Photo', 1, '2026-01-08 08:55:26'),
(367, '11×14 Photo', 3300, 4200, 'portrait', 'px', 300, '#FFFFFF', 'Large 11×14 inch photo print', 'Photo', 1, '2026-01-08 08:55:26'),
(368, 'Classic Frame 4×6', 1200, 1800, 'portrait', 'px', 300, '#F5F5DC', 'Classic beige frame - 4×6 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(369, 'Classic Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#F5F5DC', 'Classic beige frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(370, 'Classic Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#F5F5DC', 'Classic beige frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(371, 'Classic Frame 11×14', 3300, 4200, 'portrait', 'px', 300, '#F5F5DC', 'Classic beige frame - 11×14 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(372, 'Modern Black Frame 4×6', 1200, 1800, 'portrait', 'px', 300, '#1A1A1A', 'Modern black frame - 4×6 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(373, 'Modern Black Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#1A1A1A', 'Modern black frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(374, 'Modern Black Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#1A1A1A', 'Modern black frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(375, 'Modern White Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Modern white frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(376, 'Modern White Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Modern white frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(377, 'Elegant Gold Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFD700', 'Elegant gold frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(378, 'Elegant Gold Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFD700', 'Elegant gold frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(379, 'Elegant Silver Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#C0C0C0', 'Elegant silver frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(380, 'Elegant Silver Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#C0C0C0', 'Elegant silver frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(381, 'Wooden Frame 4×6', 1200, 1800, 'portrait', 'px', 300, '#8B4513', 'Natural wood frame - 4×6 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(382, 'Wooden Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#8B4513', 'Natural wood frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(383, 'Wooden Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#8B4513', 'Natural wood frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(384, 'Dark Wood Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#654321', 'Dark wood frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(385, 'Dark Wood Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#654321', 'Dark wood frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(386, 'Vintage Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#D2B48C', 'Vintage distressed frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(387, 'Vintage Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#D2B48C', 'Vintage distressed frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(388, 'Antique Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#CD853F', 'Antique style frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(389, 'Antique Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#CD853F', 'Antique style frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(390, 'Minimalist Frame 4×6', 1200, 1800, 'portrait', 'px', 300, '#E8E8E8', 'Minimalist thin frame - 4×6 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(391, 'Minimalist Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#E8E8E8', 'Minimalist thin frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(392, 'Floating Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Floating frame style - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(393, 'Gallery Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#F0F0F0', 'Gallery style frame - 5×7 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(394, 'Gallery Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#F0F0F0', 'Gallery style frame - 8×10 inch', 'Frame', 1, '2026-01-08 08:55:26'),
(395, 'Collage Frame 2×2', 2400, 2400, 'square', 'px', 300, '#FFFFFF', '2×2 photo collage frame', 'Frame', 1, '2026-01-08 08:55:26'),
(396, 'Collage Frame 3×3', 3600, 3600, 'square', 'px', 300, '#FFFFFF', '3×3 photo collage frame', 'Frame', 1, '2026-01-08 08:55:26'),
(397, 'Collage Frame 4×4', 4800, 4800, 'square', 'px', 300, '#FFFFFF', '4×4 photo collage frame', 'Frame', 1, '2026-01-08 08:55:26'),
(398, 'Family Frame Horizontal', 4800, 3600, 'landscape', 'px', 300, '#FFFFFF', 'Horizontal family photo frame', 'Frame', 1, '2026-01-08 08:55:26'),
(399, 'Family Frame Vertical', 3600, 4800, 'portrait', 'px', 300, '#FFFFFF', 'Vertical family photo frame', 'Frame', 1, '2026-01-08 08:55:26'),
(400, 'Polaroid Landscape', 3600, 3000, 'landscape', 'px', 300, '#FFFFFF', 'Polaroid landscape style', 'Polaroid', 1, '2026-01-08 08:55:26'),
(401, 'Letter Portrait', 2550, 3300, 'portrait', 'px', 300, '#FFFFFF', 'US Letter size (8.5×11 inch) portrait', 'Document', 1, '2026-01-08 08:55:26'),
(402, 'Invitation Card', 1050, 1485, 'portrait', 'px', 300, '#FFFFFF', 'Standard invitation card (A5)', 'Card', 1, '2026-01-08 08:55:26'),
(403, 'Instagram Post', 1080, 1080, 'square', 'px', 300, '#FFFFFF', 'Instagram square post', 'Social', 1, '2026-01-08 08:55:26'),
(404, 'Instagram Story', 1080, 1920, 'portrait', 'px', 300, '#FFFFFF', 'Instagram story format', 'Social', 1, '2026-01-08 08:55:26'),
(405, 'Poster 24×36', 7200, 10800, 'portrait', 'px', 300, '#FFFFFF', 'Extra large poster 24×36 inch', 'Poster', 1, '2026-01-08 08:55:26');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `invoice_date` datetime NOT NULL,
  `billing_name` varchar(191) DEFAULT NULL,
  `billing_email` varchar(191) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `addon_total` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `items_json` longtext DEFAULT NULL,
  `addons_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `order_id`, `invoice_number`, `invoice_date`, `billing_name`, `billing_email`, `billing_address`, `subtotal`, `tax_amount`, `shipping_cost`, `addon_total`, `total_amount`, `items_json`, `addons_json`, `created_at`, `updated_at`) VALUES
(1, 43, 'INV-20251022-060300-b48862', '2025-10-22 06:03:44', 'Fathima', 'fathimashibu15@gmail.com', 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 8545746954', 329.98, 0.00, 120.00, 0.00, 449.98, '[{\"name\":\"wedding card\",\"quantity\":1,\"price\":54.98,\"line_total\":54.98},{\"name\":\"custom chocoltes\",\"quantity\":1,\"price\":25,\"line_total\":25},{\"name\":\"6 * 4\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '[]', '2025-10-21 22:33:44', '2025-10-21 22:33:44'),
(2, 44, 'INV-20251022-063957-de46ba', '2025-10-22 06:40:21', 'Fathima', 'fathimashibu15@gmail.com', 'appz sandhosh\nMannarakkayam - Koovappally Road\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 8564789456', 1000.00, 0.00, 60.00, 0.00, 1060.00, '[{\"name\":\"nutt box\",\"quantity\":1,\"price\":1000,\"line_total\":1000}]', '[]', '2025-10-21 23:10:21', '2025-10-21 23:10:21'),
(3, 45, 'INV-20251022-070022-172ba1', '2025-10-22 07:00:45', 'Fathima', 'fathimashibu15@gmail.com', 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9587456217', 500.00, 0.00, 60.00, 0.00, 560.00, '[{\"name\":\"boquetes\",\"quantity\":1,\"price\":500,\"line_total\":500}]', '[]', '2025-10-21 23:30:45', '2025-10-21 23:30:45'),
(4, 46, 'INV-20251024-082131-498482', '2025-10-24 08:22:03', 'Shifa Fathima', 'shifafathima0815@gmail.com', 'shifa fathima\nPattimattam\nKottayam\nanakkal, Kerala, 686518\nIndia\nPhone: 8954746512', 25.00, 0.00, 60.00, 0.00, 85.00, '[{\"name\":\"custom chocoltes\",\"quantity\":1,\"price\":25,\"line_total\":25}]', '[]', '2025-10-24 00:52:03', '2025-10-24 00:52:03'),
(5, 47, 'INV-20251024-105749-7f90c0', '2025-10-24 10:58:19', 'Shifa Fathima', 'shifafathima0815@gmail.com', 'shifa fathima\nAmal Jyothi College of Engineering Skywalk\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 9874561412', 400.00, 0.00, 60.00, 0.00, 460.00, '[{\"name\":\"boaqutes\",\"quantity\":1,\"price\":400,\"line_total\":400}]', '[]', '2025-10-24 03:28:19', '2025-10-24 03:28:19'),
(6, 48, 'INV-20251024-111704-c7059a', '2025-10-24 11:18:47', 'Shifa Fathima', 'shifafathima0815@gmail.com', 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495450077', 1000.00, 0.00, 60.00, 0.00, 1060.00, '[{\"name\":\"nutt box\",\"quantity\":1,\"price\":1000,\"line_total\":1000}]', '[]', '2025-10-24 03:48:47', '2025-10-24 03:48:47');

-- --------------------------------------------------------

--
-- Table structure for table `learning_progress`
--

CREATE TABLE `learning_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) NOT NULL,
  `watch_time_seconds` int(11) DEFAULT 0,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `completed_at` timestamp NULL DEFAULT NULL,
  `practice_uploaded` tinyint(1) DEFAULT 0,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learning_progress`
--

INSERT INTO `learning_progress` (`id`, `user_id`, `tutorial_id`, `watch_time_seconds`, `completion_percentage`, `completed_at`, `practice_uploaded`, `last_accessed`, `created_at`) VALUES
(1, 19, 7, 0, 75.00, NULL, 1, '2026-01-07 10:55:51', '2026-01-07 10:55:51'),
(2, 19, 5, 0, 75.00, NULL, 1, '2026-01-07 14:11:43', '2026-01-07 14:11:05'),
(4, 19, 6, 0, 75.00, NULL, 1, '2026-01-07 16:12:20', '2026-01-07 16:12:20'),
(5, 19, 2, 0, 75.00, NULL, 1, '2026-01-14 10:41:50', '2026-01-14 08:42:25');

-- --------------------------------------------------------

--
-- Table structure for table `live_sessions`
--

CREATE TABLE `live_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `google_meet_link` varchar(500) NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `status` enum('scheduled','live','completed','cancelled') DEFAULT 'scheduled',
  `max_participants` int(11) DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `live_sessions`
--

INSERT INTO `live_sessions` (`id`, `subject_id`, `teacher_id`, `title`, `description`, `google_meet_link`, `scheduled_date`, `scheduled_time`, `duration_minutes`, `status`, `max_participants`, `created_at`, `updated_at`) VALUES
(1, 4, 5, 'jvvjj', 'hggyj', 'https://meet.google.com/hqa-oyqx-xqv', '2025-12-26', '16:47:00', 60, 'scheduled', 50, '2025-12-26 11:15:22', '2025-12-26 11:15:22'),
(2, 7, 5, 'simple clock making', 'clock making using resin', 'https://meet.google.com/aft-mehz-kir', '2025-12-27', '11:00:00', 60, 'scheduled', 50, '2025-12-27 05:26:54', '2025-12-27 05:26:54'),
(3, 6, 5, 'Bridal mehandi tutorial', 'for brides', 'https://meet.google.com/bco-bvmf-vdj', '2026-01-04', '08:22:00', 30, 'scheduled', 10, '2026-01-04 02:52:49', '2026-01-04 02:52:49'),
(4, 3, 5, 'hamper making', 'tower chocolate', 'https://meet.google.com/byp-cyfj-btp', '2026-01-04', '08:29:00', 60, 'scheduled', 8, '2026-01-04 02:56:44', '2026-01-04 02:56:44'),
(5, 2, 5, 'fdf', '', 'https://meet.google.com/rja-dtex-dbf', '2026-01-07', '01:18:00', 60, 'scheduled', 50, '2026-01-06 05:49:12', '2026-01-06 05:49:12');

-- --------------------------------------------------------

--
-- Table structure for table `live_session_registrations`
--

CREATE TABLE `live_session_registrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attended` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_subjects`
--

CREATE TABLE `live_subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_url` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#667eea',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `live_subjects`
--

INSERT INTO `live_subjects` (`id`, `name`, `description`, `icon_url`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:12:03', '2025-12-26 11:12:03'),
(2, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:12:03', '2025-12-26 11:12:03'),
(3, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:12:03', '2025-12-26 11:12:03'),
(4, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:12:03', '2025-12-26 11:12:03'),
(5, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:12:03', '2025-12-26 11:12:03'),
(6, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:12:03', '2025-12-26 11:12:03'),
(7, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:12:03', '2025-12-26 11:12:03'),
(8, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(9, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(10, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(11, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(12, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(13, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(14, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(15, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(16, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(17, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(18, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(19, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(20, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(21, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:12:05', '2025-12-26 11:12:05'),
(22, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(23, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(24, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(25, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(26, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(27, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(28, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(29, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(30, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(31, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(32, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(33, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(34, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(35, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:14:18', '2025-12-26 11:14:18'),
(36, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(37, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(38, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(39, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(40, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(41, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(42, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(43, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(44, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(45, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(46, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(47, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(48, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(49, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:15:49', '2025-12-26 11:15:49'),
(50, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:32:48', '2025-12-26 11:32:48'),
(51, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:32:48', '2025-12-26 11:32:48'),
(52, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:32:48', '2025-12-26 11:32:48'),
(53, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:32:48', '2025-12-26 11:32:48'),
(54, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:32:48', '2025-12-26 11:32:48'),
(55, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:32:49', '2025-12-26 11:32:49'),
(56, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:32:49', '2025-12-26 11:32:49'),
(57, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:32:49', '2025-12-26 11:32:49'),
(58, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:32:49', '2025-12-26 11:32:49'),
(59, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:32:49', '2025-12-26 11:32:49'),
(60, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:32:49', '2025-12-26 11:32:49'),
(61, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:32:49', '2025-12-26 11:32:49'),
(62, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:32:49', '2025-12-26 11:32:49'),
(63, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:32:49', '2025-12-26 11:32:49'),
(64, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:33:17', '2025-12-26 11:33:17'),
(65, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:33:17', '2025-12-26 11:33:17'),
(66, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:33:17', '2025-12-26 11:33:17'),
(67, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:33:17', '2025-12-26 11:33:17'),
(68, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:33:17', '2025-12-26 11:33:17'),
(69, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:33:17', '2025-12-26 11:33:17'),
(70, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:33:17', '2025-12-26 11:33:17'),
(71, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-26 11:33:18', '2025-12-26 11:33:18'),
(72, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-26 11:33:18', '2025-12-26 11:33:18'),
(73, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-26 11:33:18', '2025-12-26 11:33:18'),
(74, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-26 11:33:18', '2025-12-26 11:33:18'),
(75, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-26 11:33:18', '2025-12-26 11:33:18'),
(76, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-26 11:33:18', '2025-12-26 11:33:18'),
(77, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-26 11:33:18', '2025-12-26 11:33:18'),
(78, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(79, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(80, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(81, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(82, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(83, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(84, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(85, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(86, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(87, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(88, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(89, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(90, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(91, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:20:30', '2025-12-27 05:20:30'),
(92, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:24:08', '2025-12-27 05:24:08'),
(93, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:24:08', '2025-12-27 05:24:08'),
(94, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:24:08', '2025-12-27 05:24:08'),
(95, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:24:08', '2025-12-27 05:24:08'),
(96, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:24:08', '2025-12-27 05:24:08'),
(97, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:24:08', '2025-12-27 05:24:08'),
(98, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:24:08', '2025-12-27 05:24:08'),
(99, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(100, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(101, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(102, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(103, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(104, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(105, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(106, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(107, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(108, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(109, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(110, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(111, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(112, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:24:16', '2025-12-27 05:24:16'),
(113, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:24:48', '2025-12-27 05:24:48'),
(114, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:24:48', '2025-12-27 05:24:48'),
(115, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(116, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(117, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(118, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(119, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(120, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(121, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(122, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(123, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(124, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(125, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(126, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:24:49', '2025-12-27 05:24:49'),
(127, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(128, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(129, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(130, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(131, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(132, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(133, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(134, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(135, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(136, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(137, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(138, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(139, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(140, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:27:37', '2025-12-27 05:27:37'),
(141, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-27 05:29:14', '2025-12-27 05:29:14'),
(142, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-27 05:29:14', '2025-12-27 05:29:14'),
(143, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-27 05:29:14', '2025-12-27 05:29:14'),
(144, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-27 05:29:14', '2025-12-27 05:29:14'),
(145, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-27 05:29:14', '2025-12-27 05:29:14'),
(146, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-27 05:29:14', '2025-12-27 05:29:14'),
(147, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-27 05:29:14', '2025-12-27 05:29:14'),
(148, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(149, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(150, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(151, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(152, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(153, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(154, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(155, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(156, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(157, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(158, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(159, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(160, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(161, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-28 03:42:01', '2025-12-28 03:42:01'),
(162, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-29 16:40:04', '2025-12-29 16:40:04'),
(163, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-29 16:40:04', '2025-12-29 16:40:04'),
(164, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-29 16:40:04', '2025-12-29 16:40:04'),
(165, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-29 16:40:04', '2025-12-29 16:40:04'),
(166, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-29 16:40:04', '2025-12-29 16:40:04'),
(167, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-29 16:40:04', '2025-12-29 16:40:04'),
(168, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-29 16:40:04', '2025-12-29 16:40:04'),
(169, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2025-12-29 16:40:05', '2025-12-29 16:40:05'),
(170, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2025-12-29 16:40:05', '2025-12-29 16:40:05'),
(171, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2025-12-29 16:40:05', '2025-12-29 16:40:05'),
(172, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2025-12-29 16:40:05', '2025-12-29 16:40:05'),
(173, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2025-12-29 16:40:05', '2025-12-29 16:40:05'),
(174, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2025-12-29 16:40:05', '2025-12-29 16:40:05'),
(175, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2025-12-29 16:40:05', '2025-12-29 16:40:05'),
(176, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:43:57', '2026-01-04 02:43:57'),
(177, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:43:57', '2026-01-04 02:43:57'),
(178, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:43:57', '2026-01-04 02:43:57'),
(179, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:43:57', '2026-01-04 02:43:57'),
(180, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:43:57', '2026-01-04 02:43:57'),
(181, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:43:57', '2026-01-04 02:43:57'),
(182, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:43:57', '2026-01-04 02:43:57'),
(183, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:43:58', '2026-01-04 02:43:58'),
(184, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:43:58', '2026-01-04 02:43:58'),
(185, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:43:58', '2026-01-04 02:43:58'),
(186, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:43:58', '2026-01-04 02:43:58'),
(187, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:43:58', '2026-01-04 02:43:58'),
(188, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:43:58', '2026-01-04 02:43:58'),
(189, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:43:58', '2026-01-04 02:43:58'),
(190, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:49:42', '2026-01-04 02:49:42'),
(191, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:49:42', '2026-01-04 02:49:42'),
(192, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:49:42', '2026-01-04 02:49:42'),
(193, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:49:42', '2026-01-04 02:49:42'),
(194, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:49:42', '2026-01-04 02:49:42'),
(195, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:49:42', '2026-01-04 02:49:42'),
(196, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:49:42', '2026-01-04 02:49:42'),
(197, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(198, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(199, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(200, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(201, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(202, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(203, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(204, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(205, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(206, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(207, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(208, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(209, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(210, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:49:45', '2026-01-04 02:49:45'),
(211, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(212, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(213, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(214, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(215, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(216, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(217, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(218, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(219, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(220, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(221, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(222, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(223, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(224, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:53:26', '2026-01-04 02:53:26'),
(225, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:54:04', '2026-01-04 02:54:04'),
(226, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:54:04', '2026-01-04 02:54:04'),
(227, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:54:04', '2026-01-04 02:54:04'),
(228, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:54:04', '2026-01-04 02:54:04'),
(229, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:54:04', '2026-01-04 02:54:04'),
(230, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:54:04', '2026-01-04 02:54:04'),
(231, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:54:04', '2026-01-04 02:54:04'),
(232, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(233, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(234, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(235, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(236, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(237, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(238, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(239, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(240, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(241, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(242, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(243, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(244, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(245, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:54:07', '2026-01-04 02:54:07'),
(246, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(247, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(248, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(249, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(250, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(251, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(252, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(253, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(254, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(255, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(256, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(257, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(258, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(259, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:54:58', '2026-01-04 02:54:58'),
(260, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(261, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(262, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(263, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(264, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(265, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(266, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(267, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(268, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(269, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(270, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(271, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(272, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(273, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:55:21', '2026-01-04 02:55:21'),
(274, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(275, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(276, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(277, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(278, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(279, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(280, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(281, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(282, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(283, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(284, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(285, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(286, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(287, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:57:28', '2026-01-04 02:57:28'),
(288, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:59:23', '2026-01-04 02:59:23'),
(289, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:59:23', '2026-01-04 02:59:23'),
(290, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:59:23', '2026-01-04 02:59:23'),
(291, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:59:23', '2026-01-04 02:59:23'),
(292, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:59:23', '2026-01-04 02:59:23'),
(293, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:59:23', '2026-01-04 02:59:23'),
(294, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:59:23', '2026-01-04 02:59:23'),
(295, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 02:59:52', '2026-01-04 02:59:52'),
(296, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 02:59:52', '2026-01-04 02:59:52'),
(297, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 02:59:52', '2026-01-04 02:59:52'),
(298, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 02:59:52', '2026-01-04 02:59:52'),
(299, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 02:59:52', '2026-01-04 02:59:52'),
(300, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 02:59:52', '2026-01-04 02:59:52'),
(301, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 02:59:52', '2026-01-04 02:59:52'),
(302, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(303, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(304, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(305, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(306, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(307, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(308, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(309, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(310, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(311, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(312, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(313, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(314, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(315, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 03:28:31', '2026-01-04 03:28:31'),
(316, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(317, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(318, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(319, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(320, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(321, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(322, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(323, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(324, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(325, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(326, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(327, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(328, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(329, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 06:42:10', '2026-01-04 06:42:10'),
(330, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(331, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(332, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(333, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(334, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(335, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(336, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(337, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(338, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(339, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(340, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(341, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(342, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(343, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 07:40:41', '2026-01-04 07:40:41'),
(344, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(345, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(346, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(347, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(348, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(349, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(350, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(351, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(352, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(353, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(354, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(355, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(356, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(357, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-04 07:45:22', '2026-01-04 07:45:22'),
(358, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(359, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(360, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(361, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(362, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(363, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(364, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(365, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(366, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(367, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(368, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(369, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(370, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(371, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:38:19', '2026-01-06 05:38:19'),
(372, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:38:54', '2026-01-06 05:38:54'),
(373, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:38:54', '2026-01-06 05:38:54'),
(374, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:38:54', '2026-01-06 05:38:54'),
(375, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:38:54', '2026-01-06 05:38:54'),
(376, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:38:54', '2026-01-06 05:38:54'),
(377, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:38:54', '2026-01-06 05:38:54'),
(378, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:38:54', '2026-01-06 05:38:54'),
(379, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:39:51', '2026-01-06 05:39:51'),
(380, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:39:51', '2026-01-06 05:39:51'),
(381, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:39:51', '2026-01-06 05:39:51'),
(382, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:39:51', '2026-01-06 05:39:51'),
(383, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:39:51', '2026-01-06 05:39:51'),
(384, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:39:51', '2026-01-06 05:39:51'),
(385, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:39:51', '2026-01-06 05:39:51'),
(386, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(387, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(388, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(389, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(390, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(391, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(392, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(393, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(394, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(395, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(396, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(397, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(398, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(399, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:39:53', '2026-01-06 05:39:53'),
(400, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:44:21', '2026-01-06 05:44:21'),
(401, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:44:21', '2026-01-06 05:44:21'),
(402, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:44:21', '2026-01-06 05:44:21'),
(403, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:44:21', '2026-01-06 05:44:21'),
(404, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:44:21', '2026-01-06 05:44:21');
INSERT INTO `live_subjects` (`id`, `name`, `description`, `icon_url`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(405, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:44:21', '2026-01-06 05:44:21'),
(406, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:44:21', '2026-01-06 05:44:21'),
(407, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(408, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(409, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(410, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(411, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(412, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(413, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(414, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(415, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(416, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(417, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(418, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(419, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(420, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:44:23', '2026-01-06 05:44:23'),
(421, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(422, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(423, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(424, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(425, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(426, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(427, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(428, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(429, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(430, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(431, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(432, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(433, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(434, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:49:31', '2026-01-06 05:49:31'),
(435, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(436, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(437, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(438, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(439, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(440, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(441, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(442, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(443, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(444, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(445, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(446, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(447, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(448, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 05:51:27', '2026-01-06 05:51:27'),
(449, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(450, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(451, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(452, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(453, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(454, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(455, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(456, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(457, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(458, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(459, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(460, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(461, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(462, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 15:34:32', '2026-01-06 15:34:32'),
(463, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(464, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(465, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(466, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(467, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(468, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(469, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(470, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(471, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(472, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(473, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(474, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(475, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(476, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 15:34:38', '2026-01-06 15:34:38'),
(477, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(478, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(479, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(480, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(481, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(482, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(483, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(484, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(485, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(486, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(487, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(488, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(489, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(490, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-06 16:11:32', '2026-01-06 16:11:32'),
(491, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(492, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(493, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(494, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(495, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(496, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(497, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(498, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(499, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(500, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(501, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(502, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(503, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(504, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-07 10:07:53', '2026-01-07 10:07:53'),
(505, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(506, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(507, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(508, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(509, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(510, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(511, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(512, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(513, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(514, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(515, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(516, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(517, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(518, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-07 10:26:03', '2026-01-07 10:26:03'),
(519, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(520, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(521, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(522, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(523, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(524, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(525, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(526, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(527, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(528, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(529, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(530, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(531, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(532, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-14 06:32:26', '2026-01-14 06:32:26'),
(533, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(534, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(535, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(536, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(537, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(538, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(539, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(540, 'Candle Making', 'Live classes for Candle Making', NULL, '#F0E68C', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(541, 'Clay Modeling', 'Live classes for Clay Modeling', NULL, '#DDA0DD', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(542, 'Gift Making', 'Live classes for Gift Making', NULL, '#FFDAB9', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(543, 'Hand Embroidery', 'Live classes for Hand Embroidery', NULL, '#FFB6C1', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(544, 'Jewelry Making', 'Live classes for Jewelry Making', NULL, '#FFC0CB', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(545, 'Mylanchi / Mehandi Art', 'Live classes for Mylanchi / Mehandi Art', NULL, '#E6E6FA', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38'),
(546, 'Resin Art', 'Live classes for Resin Art', NULL, '#B0E0E6', 1, '2026-01-16 06:24:38', '2026-01-16 06:24:38');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT '',
  `type` varchar(60) NOT NULL DEFAULT '',
  `size` varchar(60) NOT NULL DEFAULT '',
  `color` varchar(60) DEFAULT NULL,
  `grade` varchar(60) DEFAULT NULL,
  `brand` varchar(60) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `availability` enum('available','out_of_stock') NOT NULL DEFAULT 'available',
  `image_url` varchar(500) DEFAULT NULL,
  `attributes_json` text DEFAULT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(32) NOT NULL DEFAULT 'pcs',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `supplier_id`, `name`, `category`, `type`, `size`, `color`, `grade`, `brand`, `tags`, `location`, `availability`, `image_url`, `attributes_json`, `sku`, `quantity`, `unit`, `updated_at`, `created_at`, `price`) VALUES
(1, 8, 'flower', 'Flowers', '', '', '', '', '', '', '', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250914_113430_9c357c15a96b.jpg', NULL, 'yellow flowers for boqutes', 50, 'pcs', '2025-09-14 04:04:32', '2025-09-14 04:04:32', 0.00),
(2, 8, 'frame', 'frames', '', '', '', '', '', '', '', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_152436_8e491e286334.jpg', NULL, '6*4 frame', 30, 'pcs', '2025-09-21 07:54:38', '2025-09-21 07:54:38', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `action_url`, `created_at`, `read_at`) VALUES
(1, 5, 'Welcome to My Little Thingz!', 'Start your craft learning journey with our premium tutorials.', 'success', 0, '/tutorials', '2026-01-04 04:41:43', NULL),
(2, 5, 'Practice Work Approved', 'Your practice submission for \"Resin Art Basics\" has been approved! Great work!', 'success', 0, '/pro-dashboard', '2026-01-04 04:41:43', NULL),
(3, 5, 'New Tutorial Available', 'Check out our latest tutorial: \"Advanced Embroidery Techniques\"', 'info', 0, '/tutorials', '2026-01-04 04:41:43', NULL),
(4, 5, 'Certificate Ready!', 'Congratulations! You\'ve reached 80% completion and can now download your certificate.', 'success', 0, '/pro-dashboard', '2026-01-04 04:41:43', NULL),
(5, 5, 'Live Workshop Tomorrow', 'Don\'t forget about the live jewelry making workshop tomorrow at 3 PM.', 'warning', 0, '/tutorials', '2026-01-04 04:41:43', NULL),
(6, 5, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL),
(7, 14, 'Welcome to My Little Thingz!', 'Thank you for joining our craft learning platform. Start exploring our tutorials!', 'success', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(8, 14, 'New Tutorial Available', 'Check out our latest Hand Embroidery tutorial - perfect for beginners!', 'info', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(9, 14, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL),
(10, 8, 'Welcome to My Little Thingz!', 'Thank you for joining our craft learning platform. Start exploring our tutorials!', 'success', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(11, 8, 'New Tutorial Available', 'Check out our latest Hand Embroidery tutorial - perfect for beginners!', 'info', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(12, 8, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL),
(13, 11, 'Welcome to My Little Thingz!', 'Thank you for joining our craft learning platform. Start exploring our tutorials!', 'success', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(14, 11, 'New Tutorial Available', 'Check out our latest Hand Embroidery tutorial - perfect for beginners!', 'info', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(15, 11, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL),
(16, 12, 'Welcome to My Little Thingz!', 'Thank you for joining our craft learning platform. Start exploring our tutorials!', 'success', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(17, 12, 'New Tutorial Available', 'Check out our latest Hand Embroidery tutorial - perfect for beginners!', 'info', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(18, 12, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL),
(19, 17, 'Welcome to My Little Thingz!', 'Thank you for joining our craft learning platform. Start exploring our tutorials!', 'success', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(20, 17, 'New Tutorial Available', 'Check out our latest Hand Embroidery tutorial - perfect for beginners!', 'info', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(21, 17, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL),
(22, 10, 'Welcome to My Little Thingz!', 'Thank you for joining our craft learning platform. Start exploring our tutorials!', 'success', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(23, 10, 'New Tutorial Available', 'Check out our latest Hand Embroidery tutorial - perfect for beginners!', 'info', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(24, 10, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL),
(25, 15, 'Welcome to My Little Thingz!', 'Thank you for joining our craft learning platform. Start exploring our tutorials!', 'success', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(26, 15, 'New Tutorial Available', 'Check out our latest Hand Embroidery tutorial - perfect for beginners!', 'info', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(27, 15, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL),
(28, 13, 'Welcome to My Little Thingz!', 'Thank you for joining our craft learning platform. Start exploring our tutorials!', 'success', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(29, 13, 'New Tutorial Available', 'Check out our latest Hand Embroidery tutorial - perfect for beginners!', 'info', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(30, 13, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL),
(31, 18, 'Welcome to My Little Thingz!', 'Thank you for joining our craft learning platform. Start exploring our tutorials!', 'success', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(32, 18, 'New Tutorial Available', 'Check out our latest Hand Embroidery tutorial - perfect for beginners!', 'info', 0, '/tutorials', '2026-01-04 05:03:52', NULL),
(33, 18, 'Subscription Reminder', 'Upgrade to Premium to unlock unlimited access to all tutorials.', 'warning', 0, '/tutorials#subscription', '2026-01-04 05:03:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `offers_promos`
--

CREATE TABLE `offers_promos` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `razorpay_signature` varchar(191) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `shiprocket_order_id` int(11) DEFAULT NULL COMMENT 'Shiprocket order ID',
  `shiprocket_shipment_id` int(11) DEFAULT NULL COMMENT 'Shiprocket shipment ID',
  `courier_id` int(11) DEFAULT NULL COMMENT 'Courier company ID',
  `courier_name` varchar(100) DEFAULT NULL COMMENT 'Courier company name',
  `awb_code` varchar(100) DEFAULT NULL COMMENT 'AWB tracking code',
  `pickup_scheduled_date` datetime DEFAULT NULL COMMENT 'Scheduled pickup date',
  `pickup_token_number` varchar(100) DEFAULT NULL COMMENT 'Pickup token number',
  `shipment_status` varchar(100) DEFAULT NULL,
  `current_status` varchar(100) DEFAULT NULL,
  `tracking_updated_at` timestamp NULL DEFAULT NULL,
  `label_url` varchar(500) DEFAULT NULL COMMENT 'Shipping label URL',
  `manifest_url` varchar(500) DEFAULT NULL COMMENT 'Manifest URL',
  `shipping_charges` decimal(10,2) DEFAULT 0.00 COMMENT 'Actual shipping charges',
  `weight` decimal(10,2) DEFAULT 0.50 COMMENT 'Package weight in kg',
  `length` decimal(10,2) DEFAULT 10.00 COMMENT 'Package length in cm',
  `breadth` decimal(10,2) DEFAULT 10.00 COMMENT 'Package breadth in cm',
  `height` decimal(10,2) DEFAULT 10.00 COMMENT 'Package height in cm',
  `estimated_delivery` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Customer orders with Shiprocket integration';

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `status`, `payment_method`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `total_amount`, `subtotal`, `tax_amount`, `shipping_cost`, `shipping_address`, `tracking_number`, `shiprocket_order_id`, `shiprocket_shipment_id`, `courier_id`, `courier_name`, `awb_code`, `pickup_scheduled_date`, `pickup_token_number`, `shipment_status`, `current_status`, `tracking_updated_at`, `label_url`, `manifest_url`, `shipping_charges`, `weight`, `length`, `breadth`, `height`, `estimated_delivery`, `created_at`, `shipped_at`, `delivered_at`) VALUES
(2, 1, 'ORD-20250912-064702-f270cb', 'delivered', 'razorpay', 'paid', 'order_RGYzo1NeNPAuPc', 'pay_RGZ4lD3DDSOyGz', 'ea2a3513a18b17f2c8a1fa49876ba53062ff73ff0a70c64c231acf292c98284d', 2250.00, 2250.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-11 23:17:02', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(3, 1, 'ORD-20250912-065609-408699', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-11 23:26:09', NULL, NULL),
(4, 1, 'ORD-20250912-070324-5de70f', 'pending', NULL, 'pending', NULL, NULL, NULL, 120.00, 120.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-11 23:33:24', NULL, NULL),
(5, 1, 'ORD-20250912-070525-6af48e', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-11 23:35:25', NULL, NULL),
(6, 1, 'ORD-20250912-070628-400786', 'pending', NULL, 'pending', NULL, NULL, NULL, 120.00, 120.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-11 23:36:28', NULL, NULL),
(7, 1, 'ORD-20250912-072223-f6aba9', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-11 23:52:23', NULL, NULL),
(8, 1, 'ORD-20250912-081443-57dddd', 'delivered', 'razorpay', 'paid', 'order_RGaUSGjDC0uHXD', 'pay_RGaUcMT48bnDTA', '40d3afc1d58693301eff69caa62c0f0fb2c187527d53bc2af48804dc1b0544b8', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 00:44:43', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(9, 1, 'ORD-20250912-081739-c13563', 'delivered', 'razorpay', 'paid', 'order_RGaXXYEki73TL2', 'pay_RGaXbwfGwu6ehQ', '112f30fd1accc6144acd19b8e29025d0e119ab56027352c0fbc93502120ea062', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 00:47:39', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(10, 1, 'ORD-20250912-103402-777b98', 'delivered', 'razorpay', 'paid', 'order_RGcrctuI0BK3hh', 'pay_RGcrtSGu7IqiYy', 'f23104b8da2aa1cef77e7a1e2c809a0925223c37d00f8e66214a875fa34a7b1c', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 03:04:02', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(11, 1, 'ORD-20250912-121347-6f44d8', 'delivered', 'razorpay', 'paid', 'order_RGeYzyLPujcMWV', 'pay_RGeZBvdQrdCUcg', 'f99f22a27c0e97e43c59882968cb4eb73c6689bc592b4d0c0d3789d636705056', 150.00, 150.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 04:43:47', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(12, 1, 'ORD-20250912-121736-dc99c9', 'delivered', 'razorpay', 'paid', 'order_RGed1GBd4rgAu6', 'pay_RGedqgO02jPPMx', 'f50daa592861af7d2c6cbbfd562c98a78e647c76889978e80d95e76eb6aa90f5', 250.00, 250.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 04:47:36', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(13, 1, 'ORD-20250912-122449-a40eff', 'pending', 'razorpay', 'pending', 'order_RGekdJLtU2RAX0', NULL, NULL, 2000.00, 2000.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-12 04:54:49', NULL, NULL),
(14, 1, 'ORD-20250913-061526-12456c', 'delivered', 'razorpay', 'paid', 'order_RGwzav8Gn2aqaZ', 'pay_RGwznsPSJHnM9y', '2d16e571c7d5fceb0b5ccfa9dcdae196d3423f6bbd42468b92a189b16c0909c9', 2000.00, 2000.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 22:45:26', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(15, 1, 'ORD-20250913-071058-60cbd7', 'delivered', 'razorpay', 'paid', 'order_RGxwG25tCUcwDh', 'pay_RGxwwO79h0QoTa', 'ba00aeaeb86242faa5940bc46e77cb8aa4c91efa6f97a79fb5a3fac56178ebbd', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 23:40:58', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(16, 1, 'ORD-20250913-075311-390498', 'delivered', 'razorpay', 'paid', 'order_RGyeqcfJ05WOD2', 'pay_RGyf5n0arwrKc0', '85af2064c2880c081dc4fa93fb9a99ff888a1603dba24a7cb0625daf219514a8', 3000.00, 3000.00, 0.00, 0.00, 'purathel,anakkal kanjirapally 686598,8765457889', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-13 00:23:11', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(17, 9, 'ORD-20250915-102334-53c51f', 'delivered', 'razorpay', 'paid', 'order_RHoHtfe5EAfVBz', 'pay_RHoI4lQmpiAz1w', 'c106ce2b5c40cd9e41b647029c0943f934175264f06099419a966344c1b4ac88', 2000.00, 2000.00, 0.00, 0.00, 'elemashery,kottyam', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-15 02:53:34', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(18, 9, 'ORD-20250915-120710-dbe9c0', 'delivered', 'razorpay', 'paid', 'order_RHq3JxbBKgi47N', 'pay_RHq3fwRdV36i5l', 'f5c7811a4a59f13e5bec075714af01f5e7fb2a7fd46b119d7041f8afac617f37', 5000.00, 5000.00, 0.00, 0.00, 'vijetha jinu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nVizhikkathodu, Kottayam\nAnakkal ,Kanjirapally, kerala, 686508\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-15 04:37:10', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(19, 11, 'ORD-20250917-082534-7c7848', 'delivered', 'razorpay', 'paid', 'order_RIZLX71USIKr5P', 'pay_RIZLnX25ZDWYbV', 'd13d6f9b2e0764fe13c40a6cdf6eb18485722af378d748e1a88c0b0c54dff216', 2000.00, 2000.00, 0.00, 0.00, 'fathima shibu\nMDR\nThoppumpady, Ernakulam\nKochi, Kerala, 682005\nPhone: 9495470077', NULL, 991031262, 987435320, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-17 00:55:34', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(20, 11, 'ORD-20250918-101700-a9109b', 'delivered', 'razorpay', 'paid', 'order_RIzmIerATMwLuP', 'pay_RIzmx2C2bOXE1H', 'b4c50d2acc2e21660f1717aefb0b9e25583a020fb5c370f399e7c87d51feaac6', 3400.00, 3400.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nPhone: 9495470077', NULL, 991031240, 987435298, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 1.00, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-18 02:47:00', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(21, 1, 'ORD-20250921-151803-e85fbc', 'delivered', 'razorpay', 'paid', 'order_RKGVjxclYpiV2l', 'pay_RKGVucYc1R1yVc', '1142036924fd5675d1b433a1806b7d27a69c80ec2f6350277ce736f4a26ef939', 2400.00, 2400.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nPhone: 9495470077', NULL, 991031223, 987435281, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 1.00, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-21 07:48:03', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(22, 1, 'ORD-20250922-172224-a16dcb', 'pending', 'razorpay', 'pending', 'order_RKhAFZtDXBSYu1', NULL, NULL, 500.00, 500.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nNorth Paravur, Ernakulam\nAnakkal ,Kanjirapally, kerala, 686508\nPhone: 9495402077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-22 09:52:24', NULL, NULL),
(23, 11, 'ORD-20250923-163030-6d671b', 'pending', 'razorpay', 'pending', 'order_RL4oTN9rVwMl9t', NULL, NULL, 90.00, 90.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nThalassery, Kannur\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495430077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-23 09:00:30', NULL, NULL),
(24, 11, 'ORD-20250923-165018-0265ec', 'delivered', 'razorpay', 'paid', 'order_RL59MuPwACZ92h', 'pay_RL59fZQUI2o08Y', '375535169f7d71b728e042aead556c67700f896995e4c83aa8f1f375466fbb0f', 25.00, 25.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495400773', NULL, 991031188, 987435246, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-23 09:20:18', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(25, 11, 'ORD-20250924-051144-b530cc', 'pending', 'razorpay', 'pending', 'order_RLHmadGMPG9CKn', NULL, NULL, 500.00, 500.00, 0.00, 0.00, 'fathima shibu\nMannarakkayam - Koovappally Road\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 9475486254', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-23 21:41:44', NULL, NULL),
(26, 11, 'ORD-20250925-060512-02775c', 'pending', 'razorpay', 'pending', 'order_RLhEAs5STcXVHz', NULL, NULL, 1600.00, 1600.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nKoovapally, Kottayam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-24 22:35:12', NULL, NULL),
(27, 11, 'ORD-20250925-081812-1cac7a', 'pending', NULL, 'pending', NULL, NULL, NULL, 1999.99, 1999.99, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-25 00:48:12', NULL, NULL),
(28, 11, 'ORD-20250925-104439-c14ecc', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-25 03:14:39', NULL, NULL),
(29, 11, 'ORD-20251006-130915-760c8b', 'delivered', 'razorpay', 'paid', 'order_RQAKRa4eO4puQS', 'pay_RQAKmkiRdRNV73', 'eaadb8f1570a17c30148758d919b1e00fe2eba0e62281599e8a585e68c61e8e5', 625.00, 625.00, 0.00, 0.00, 'shijin thomas\n42/3154A Prathibha Road\nPadivattom, Ernakulam\nErnakulam, Kerala, 682025\nIndia\nPhone: 9495470077', NULL, 991031157, 987435211, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 1.00, 10.00, 10.00, 10.00, '2025-10-12', '2025-10-06 05:39:15', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(30, 11, 'ORD-20251006-132257-145f7d', 'delivered', 'razorpay', 'paid', 'order_RQAYuFp8lVqAGN', 'pay_RQAZ5mrLeR2mVy', 'a711ddae23919099cced893e0624d715c6b709be8f67190cd9d1d359897aaced', 50.00, 50.00, 0.00, 0.00, 'binil  jacob\n42/3154A Prathibha Road\nPadivattom, Ernakulam\nErnakulam, Kerala, 682025\nIndia\nPhone: 9495470077', NULL, 991030333, 987434391, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-10-06 05:52:57', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(31, 11, 'ORD-20251006-161503-57a92f', 'pending', 'razorpay', 'pending', 'order_RQDUi4Szzuc3an', NULL, NULL, 400.00, 400.00, 0.00, 0.00, 'vijetha  jinu\nDD Golden Gate\nKakkanad West, Ernakulam\nErnakulam, Kerala, 682037\nIndia\nPhone: 8864947452', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-06 08:45:03', NULL, NULL),
(32, 11, 'ORD-20251006-163704-0a0322', 'delivered', 'razorpay', 'paid', 'order_RQDrxNZ9MDI765', 'pay_RQDsNer3E0XVww', 'e125a822b944c5a42956ac0c022e23725ef72d5ac6b7784ad9a0662388aaa017', 460.00, 400.00, 0.00, 60.00, 'vij jinu\nDD Golden Gate\nKakkanad West, Ernakulam\nErnakulam, Kerala, 682037\nIndia\nPhone: 7895641589', NULL, 991095374, 987499414, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-10-06 09:07:04', '2025-10-07 01:09:12', '2025-10-07 01:25:04'),
(33, 11, 'ORD-20251016-062323-5af814', 'processing', 'razorpay', 'paid', 'order_RU0kwrGJY53una', 'pay_RU0lAUjEZC8Cj8', '0707bbffde06f121b1351c0d0f4d067a684bdfe9e5a72b113f4b501ffb7714ce', 1660.00, 1600.00, 0.00, 60.00, 'appz sandhosh\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9754684123', NULL, 1003407456, 999805596, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 1.00, 10.00, 10.00, 10.00, NULL, '2025-10-15 22:53:23', NULL, NULL),
(34, 11, 'ORD-20251016-155129-d4139b', 'pending', 'razorpay', 'pending', 'order_RUAR4DtVnee2P3', NULL, NULL, 85.00, 25.00, 0.00, 60.00, 'fathima shibu\nPetta\nFeroke, Kozhikode\nFeroke, Kerala, 673631\nIndia\nPhone: 9188436587', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-16 08:21:29', NULL, NULL),
(35, 11, 'ORD-20251016-160348-245fdc', 'processing', 'razorpay', 'paid', 'order_RUAe3ECQHiDdA8', 'pay_RUAeGuHnOsZwWw', '98dc892b9c0d7fdaee384317fdb0ad7d1b06486e32d552671363533bb2775b8d', 85.00, 25.00, 0.00, 60.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nFeroke, Kozhikode\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495400477', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-16 08:33:48', NULL, NULL),
(36, 1, 'ORD-20251020-184803-fc908b', 'pending', 'razorpay', 'pending', 'order_RVna2AxG1AG8j0', NULL, NULL, 1560.00, 1500.00, 0.00, 60.00, 'Fathima Shibu\npurathel house,anakkal p o,kanjirappally\nlp school\nanakkal, kerala, 686508\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-20 11:18:03', NULL, NULL),
(37, 1, 'ORD-20251020-190124-17ed87', 'pending', 'razorpay', 'pending', 'order_RVno5tfQ8XjSb3', NULL, NULL, 1710.00, 1500.00, 0.00, 60.00, 'Fathima Shibu\npurathel house,anakkal p o,kanjirappally\nlp school\nanakkal, kerala, 686508\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-20 11:31:24', NULL, NULL),
(38, 1, 'ORD-20251020-193022-5034ee', 'pending', NULL, 'pending', NULL, NULL, NULL, 25.00, 25.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-20 12:00:22', NULL, NULL),
(39, 1, 'ORD-20251020-194216-dc9989', 'pending', 'razorpay', 'pending', 'order_RVoVGdrXE67SSB', NULL, NULL, 85.00, 25.00, 0.00, 60.00, 'Fathima Shibu\npurathel house,anakkal p o,kanjirappally\nlp school\nanakkal, kerala, 686508\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-20 12:12:16', NULL, NULL),
(40, 11, 'ORD-20251021-100312-178d4d', 'pending', 'razorpay', 'pending', 'order_RW3AiTmXAdiQER', NULL, NULL, 2059.99, 1999.99, 0.00, 60.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-21 02:33:12', NULL, NULL),
(41, 11, 'ORD-20251021-100335-bcd4b1', 'processing', 'razorpay', 'paid', 'order_RW3B74xn7H4L8W', 'pay_RW3BVK8m50YPvl', 'fab8ab82ec3ad4de3788f56c92d854b32f1609a158f60f66cc471838fa3b3d70', 2059.99, 1999.99, 0.00, 60.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-21 02:33:35', NULL, NULL),
(42, 11, 'ORD-20251022-060210-5b7de4', 'pending', 'razorpay', 'pending', 'order_RWNbENQu19Mm2W', NULL, NULL, 449.98, 329.98, 0.00, 120.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 8545746954', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 120.00, 1.50, 10.00, 10.00, 10.00, NULL, '2025-10-21 22:32:10', NULL, NULL),
(43, 11, 'ORD-20251022-060300-b48862', 'processing', 'razorpay', 'paid', 'order_RWNc7ITRd8VmO7', 'pay_RWNcbkpNcSQOhM', '178d7d004669224d136041225ab66dbbf8fc6f02adb3b401de0934cc7577a3bf', 449.98, 329.98, 0.00, 120.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 8545746954', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 120.00, 1.50, 10.00, 10.00, 10.00, NULL, '2025-10-21 22:33:00', NULL, NULL),
(44, 11, 'ORD-20251022-063957-de46ba', 'processing', 'razorpay', 'paid', 'order_RWOF8qoHp7RFBQ', 'pay_RWOFJx97op8xmq', '026de0fa09fe25889d749b5b38fb82e19ad573ef4215b57fa4fe6bea02b1d081', 1060.00, 1000.00, 0.00, 60.00, 'appz sandhosh\nMannarakkayam - Koovappally Road\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 8564789456', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-21 23:09:57', NULL, NULL),
(45, 11, 'ORD-20251022-070022-172ba1', 'processing', 'razorpay', 'paid', 'order_RWOaj8Sg6bfUfz', 'pay_RWOasjIuGqnoKG', 'e5c6e7924415dd571e89b50f9c6eeefc5471fb40503343a60a29964b731ea49a', 560.00, 500.00, 0.00, 60.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9587456217', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-21 23:30:22', NULL, NULL),
(46, 18, 'ORD-20251024-082131-498482', 'processing', 'razorpay', 'paid', 'order_RXD2eAAqn5tADf', 'pay_RXD2xGUv8otZxQ', 'efdcd8745cad88ef504df2d3d67a306150a5c30ced2050e4b569c0fb1469fd5d', 85.00, 25.00, 0.00, 60.00, 'shifa fathima\nPattimattam\nKottayam\nanakkal, Kerala, 686518\nIndia\nPhone: 8954746512', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-24 00:51:31', NULL, NULL),
(47, 18, 'ORD-20251024-105749-7f90c0', 'processing', 'razorpay', 'paid', 'order_RXFhlGmT8Z0aKp', 'pay_RXFi1xUlHlmmqI', 'a45ed4c3a91ae2da9c106e0bc69ab35c9423e812d464733fb442aa399801b8e2', 460.00, 400.00, 0.00, 60.00, 'shifa fathima\nAmal Jyothi College of Engineering Skywalk\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 9874561412', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-24 03:27:49', NULL, NULL),
(48, 18, 'ORD-20251024-111704-c7059a', 'processing', 'razorpay', 'paid', 'order_RXG24VKeVTjN5K', 'pay_RXG3eG4cF2OIhO', '1a1a07f61b6f45040d4def0f035aa37df194bb671c87c7d96b2305c8c7166a9b', 1060.00, 1000.00, 0.00, 60.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495450077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-24 03:47:04', NULL, NULL),
(49, 18, 'ORD-20251025-110315-5a6afc', 'pending', 'razorpay', 'pending', 'order_RXeKcpb847Dvbk', NULL, NULL, 160.00, 25.00, 0.00, 60.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nKanjirappalli, Kottayam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495400775', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-25 03:33:15', NULL, NULL),
(50, 11, 'ORD-20251028-184354-3d4943', 'pending', 'razorpay', 'pending', 'order_RYynyf5Atqn8zF', NULL, NULL, 579.00, 519.00, 0.00, 60.00, 'fathima shibu\nkadapra\nകടപ്ര, പത്തനംതിട്ട ജില്ല\nകടപ്ര, Kerala, 686621\nIndia\nPhone: 8654745695', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-28 17:43:54', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_addons`
--

CREATE TABLE `order_addons` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `addon_id` varchar(50) NOT NULL,
  `addon_name` varchar(255) NOT NULL,
  `addon_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_addons`
--

INSERT INTO `order_addons` (`id`, `order_id`, `addon_id`, `addon_name`, `addon_price`, `created_at`) VALUES
(1, 37, 'greeting_card', 'Greeting Card', 150.00, '2025-10-20 11:31:24'),
(2, 49, 'ribbon', 'Decorative Ribbon', 75.00, '2025-10-25 03:33:15');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `artwork_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `artwork_id`, `quantity`, `price`) VALUES
(5, 2, 9, 2, 30.00),
(6, 2, 2, 2, 50.00),
(7, 2, 13, 1, 90.00),
(8, 2, 12, 1, 2000.00),
(9, 3, 2, 1, 50.00),
(10, 4, 4, 1, 120.00),
(11, 5, 2, 1, 50.00),
(12, 6, 4, 1, 120.00),
(13, 7, 2, 1, 50.00),
(14, 8, 2, 1, 50.00),
(15, 9, 2, 1, 50.00),
(16, 10, 2, 1, 50.00),
(17, 11, 3, 1, 150.00),
(18, 12, 5, 1, 250.00),
(19, 13, 12, 1, 2000.00),
(20, 14, 12, 1, 2000.00),
(21, 15, 2, 1, 50.00),
(22, 16, 11, 1, 3000.00),
(23, 17, 12, 1, 2000.00),
(24, 18, 11, 1, 3000.00),
(25, 18, 16, 1, 2000.00),
(26, 19, 16, 1, 2000.00),
(27, 20, 8, 1, 400.00),
(28, 20, 11, 1, 3000.00),
(29, 21, 8, 1, 400.00),
(30, 21, 12, 1, 2000.00),
(31, 22, 21, 1, 500.00),
(32, 23, 13, 1, 90.00),
(33, 24, 27, 1, 25.00),
(34, 25, 22, 1, 500.00),
(35, 26, 29, 1, 1600.00),
(36, 27, 26, 1, 1999.99),
(37, 28, 2, 1, 50.00),
(38, 29, 27, 1, 25.00),
(39, 29, 32, 1, 600.00),
(40, 30, 34, 1, 50.00),
(41, 31, 25, 1, 400.00),
(42, 32, 25, 1, 400.00),
(43, 33, 23, 1, 1000.00),
(44, 33, 25, 1, 600.00),
(45, 34, 27, 1, 25.00),
(46, 35, 27, 1, 25.00),
(47, 36, 37, 1, 1500.00),
(48, 37, 37, 1, 1500.00),
(49, 38, 27, 1, 25.00),
(50, 39, 27, 1, 25.00),
(51, 40, 26, 1, 1999.99),
(52, 41, 26, 1, 1999.99),
(53, 42, 33, 1, 54.98),
(54, 42, 27, 1, 25.00),
(55, 42, 5, 1, 250.00),
(56, 43, 33, 1, 54.98),
(57, 43, 27, 1, 25.00),
(58, 43, 5, 1, 250.00),
(59, 44, 22, 1, 1000.00),
(60, 45, 19, 1, 500.00),
(61, 46, 27, 1, 25.00),
(62, 47, 8, 1, 400.00),
(63, 48, 22, 1, 1000.00),
(64, 49, 27, 1, 25.00),
(65, 50, 4, 1, 519.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_payments`
--

CREATE TABLE `order_payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `provider` varchar(50) NOT NULL DEFAULT 'razorpay',
  `provider_order_id` varchar(100) DEFAULT NULL,
  `provider_payment_id` varchar(100) DEFAULT NULL,
  `provider_signature` varchar(191) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `status` varchar(40) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_requirements`
--

CREATE TABLE `order_requirements` (
  `id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `order_ref` varchar(100) NOT NULL,
  `material_name` varchar(120) NOT NULL,
  `required_qty` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(32) NOT NULL DEFAULT 'pcs',
  `due_date` date DEFAULT NULL,
  `status` enum('pending','packed','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_requirements`
--

INSERT INTO `order_requirements` (`id`, `supplier_id`, `order_ref`, `material_name`, `required_qty`, `unit`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 8, 'clip', 'poloroid clip', 21, 'pcs', '2025-09-23', 'packed', '2025-09-21 09:18:00', '2025-09-21 09:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_requirement_messages`
--

CREATE TABLE `order_requirement_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `requirement_id` int(10) UNSIGNED NOT NULL,
  `sender` enum('admin','supplier') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_requirement_messages`
--

INSERT INTO `order_requirement_messages` (`id`, `requirement_id`, `sender`, `message`, `created_at`) VALUES
(1, 1, 'admin', 'it is very very arjent', '2025-09-21 09:18:34'),
(2, 1, 'admin', 'is it available', '2025-09-21 09:29:55'),
(3, 1, 'supplier', 'yes available', '2025-09-21 09:37:27'),
(4, 1, 'supplier', 'do you needed', '2025-09-21 10:00:01'),
(5, 1, 'supplier', 'do you needed', '2025-09-21 10:00:19'),
(6, 1, 'supplier', 'hloo', '2025-09-21 10:04:33'),
(7, 1, 'admin', 'okk', '2025-09-21 10:05:10');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(7, 'kiranshibuthomas2026@mca.ajce.in', '148587', '2025-09-17 07:07:53', '2025-09-16 23:07:53'),
(10, 'fathimashibu15@gmail.com', '679562', '2025-10-15 18:34:14', '2025-10-15 10:34:14');

-- --------------------------------------------------------

--
-- Table structure for table `practice_uploads`
--

CREATE TABLE `practice_uploads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_feedback` text DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `practice_uploads`
--

INSERT INTO `practice_uploads` (`id`, `user_id`, `tutorial_id`, `description`, `images`, `status`, `admin_feedback`, `upload_date`, `reviewed_date`) VALUES
(1, 19, 10, 'Practice work for tutorial: Earing', '[{\"original_name\":\"\\ud83c\\udfa7.jpg\",\"stored_name\":\"direct_1767783095_1_\\ud83c\\udfa7.jpg\",\"file_size\":60676}]', 'pending', NULL, '2026-01-07 10:51:35', NULL),
(2, 19, 7, 'Practice work for tutorial: Clock resin art', '[{\"original_name\":\"\\ud83c\\udfa7.jpg\",\"stored_name\":\"direct_1767783351_1_\\ud83c\\udfa7.jpg\",\"file_size\":60676}]', 'approved', 'Auto-approved for demo - Excellent work! Great attention to detail and creativity.', '2026-01-07 10:55:51', '2026-01-07 10:55:51'),
(3, 19, 5, 'Practice work for tutorial: Pearl Jewelery', '[{\"original_name\":\"SmartfonAz\\u00ae Telefon Mag\\u0306azas\\u0131 on Instagram_ _\\u018fn sevdiyiniz _Samsung_ modeli hans\\u0131d\\u0131r_\\ud83d\\udcf1 ________________________ Samsung _Galaxy S22_ seriyas\\u0131_ Galaxy S22 5G (8_256) \\u2022 1499\\u20bc Galaxy S22+ 5G (8_256) \\u2022 1799\\u20bc Galaxy S.jpg\",\"stored_name\":\"direct_1767795065_1_SmartfonAz\\u00ae Telefon Mag\\u0306azas\\u0131 on Instagram_ _\\u018fn sevdiyiniz _Samsung_ modeli hans\\u0131d\\u0131r_\\ud83d\\udcf1 ________________________ Samsung _Galaxy S22_ seriyas\\u0131_ Galaxy S22 5G (8_256) \\u2022 1499\\u20bc Galaxy S22+ 5G (8_256) \\u2022 1799\\u20bc Galaxy S.jpg\",\"file_size\":48791}]', 'approved', 'Auto-approved for demo - Excellent work! Great attention to detail and creativity.', '2026-01-07 14:11:05', '2026-01-07 14:11:05'),
(4, 19, 5, 'Practice work for tutorial: Pearl Jewelery', '[{\"original_name\":\"Brand New IPhone 17 pro Orange color.jpg\",\"stored_name\":\"direct_1767795103_1_Brand New IPhone 17 pro Orange color.jpg\",\"file_size\":61613}]', 'approved', 'Auto-approved for demo - Excellent work! Great attention to detail and creativity.', '2026-01-07 14:11:43', '2026-01-07 14:11:43'),
(5, 19, 6, 'Practice work for tutorial: Mirror clay', '[{\"original_name\":\"giftbox.png\",\"stored_name\":\"direct_1767802340_1_giftbox.png\",\"file_size\":1134165}]', 'approved', 'Auto-approved for demo - Excellent work! Great attention to detail and creativity.', '2026-01-07 16:12:20', '2026-01-07 16:12:20'),
(6, 19, 2, 'Practice work for tutorial: cap embroidery', '[{\"original_name\":\"Borduuridee\\u00ebn.jpg\",\"stored_name\":\"direct_1768380145_1_Borduuridee\\u00ebn.jpg\",\"file_size\":78076}]', 'approved', 'Auto-approved for demo - Excellent work! Great attention to detail and creativity.', '2026-01-14 08:42:25', '2026-01-14 08:42:25'),
(7, 19, 2, 'Practice work for tutorial: cap embroidery', '[{\"original_name\":\"Borduuridee\\u00ebn.jpg\",\"stored_name\":\"direct_1768380255_1_Borduuridee\\u00ebn.jpg\",\"file_size\":78076}]', 'approved', 'Auto-approved for demo - Excellent work! Great attention to detail and creativity.', '2026-01-14 08:44:15', '2026-01-14 08:44:15'),
(8, 19, 2, 'Practice work for tutorial: cap embroidery', '[{\"original_name\":\"Borduuridee\\u00ebn.jpg\",\"stored_name\":\"direct_1768380306_1_Borduuridee\\u00ebn.jpg\",\"file_size\":78076}]', 'approved', 'Auto-approved for demo - Excellent work! Great attention to detail and creativity.', '2026-01-14 08:45:06', '2026-01-14 08:45:06'),
(9, 19, 2, 'Practice work for tutorial: cap embroidery', '[{\"original_name\":\"Borduuridee\\u00ebn.jpg\",\"stored_name\":\"direct_1768387269_1_Borduuridee\\u00ebn.jpg\",\"file_size\":78076}]', 'approved', 'Auto-approved for demo - Excellent work! Great attention to detail and creativity.', '2026-01-14 10:41:09', '2026-01-14 10:41:09'),
(10, 19, 2, 'Practice work for tutorial: cap embroidery', '[{\"original_name\":\"pathu.jpg\",\"stored_name\":\"direct_1768387310_1_pathu.jpg\",\"file_size\":19529}]', 'approved', 'Auto-approved for demo - Excellent work! Great attention to detail and creativity.', '2026-01-14 10:41:50', '2026-01-14 10:41:50');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('design-based','handmade','mixed') NOT NULL,
  `description` text DEFAULT NULL,
  `requires_editor` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `type`, `description`, `requires_editor`, `created_at`) VALUES
(1, 'Photo Frames', 'design-based', 'Custom photo frames with personalized designs', 1, '2026-01-08 07:38:20'),
(2, 'Polaroids', 'design-based', 'Custom polaroid prints with editing', 1, '2026-01-08 07:38:20'),
(3, 'Wedding Cards', 'design-based', 'Wedding invitation cards with custom designs', 1, '2026-01-08 07:38:20'),
(4, 'Posters', 'design-based', 'Custom posters and prints', 1, '2026-01-08 07:38:20'),
(5, 'Name Boards', 'design-based', 'Personalized name boards and signs', 1, '2026-01-08 07:38:20'),
(6, 'Bouquets', 'handmade', 'Handcrafted flower bouquets', 0, '2026-01-08 07:38:20'),
(7, 'Handcrafted Gifts', 'handmade', 'Custom handmade gift items', 0, '2026-01-08 07:38:20'),
(8, 'Jewelry', 'handmade', 'Custom jewelry pieces', 0, '2026-01-08 07:38:20'),
(9, 'Cakes', 'handmade', 'Custom decorated cakes', 0, '2026-01-08 07:38:20');

-- --------------------------------------------------------

--
-- Table structure for table `product_chat_messages`
--

CREATE TABLE `product_chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `cart_item_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `sender_type` enum('admin','user') NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `message_content` text NOT NULL,
  `message_type` enum('text','image','customization_request') DEFAULT 'text',
  `customization_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customization_details`)),
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_chat_messages`
--

INSERT INTO `product_chat_messages` (`id`, `product_id`, `cart_item_id`, `user_id`, `sender_type`, `sender_id`, `message_content`, `message_type`, `customization_details`, `is_read`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, 'user', 1, 'Welcome! Chat system is working perfectly! 🎉', 'text', NULL, 0, '2025-12-23 06:53:53', '2025-12-23 06:53:53'),
(2, 8, NULL, 13, 'user', 13, 'Hello! This is a test message from the setup system. Chat is working perfectly! 🎉', 'text', NULL, 0, '2025-12-23 06:53:58', '2025-12-23 06:53:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_ratings`
--

CREATE TABLE `product_ratings` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `artwork_id` int(11) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `feedback` text DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(64) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'INR',
  `payment_method` varchar(32) DEFAULT 'razorpay',
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `razorpay_order_id` varchar(64) DEFAULT NULL,
  `razorpay_payment_id` varchar(64) DEFAULT NULL,
  `razorpay_signature` varchar(128) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `admin_id`, `order_number`, `total_amount`, `currency`, `payment_method`, `payment_status`, `status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `shipping_address`, `created_at`, `updated_at`) VALUES
(1, 5, 'PO-20250921-121203-0a8b9f', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKDLGe1WmPiJbq', NULL, NULL, 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 04:42:03', '2025-09-21 04:42:04'),
(2, 5, 'PO-20250921-121203-f32b3a', 9000.00, 'INR', 'razorpay', 'paid', 'processing', 'order_RKDLH0kzQl1oa6', 'pay_RKDML96tI9uhLN', '0d761420f5ff9466460812e7e7ab468a4a4aa09460fb8975a028e1c5055a39bb', 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 04:42:03', '2025-09-21 04:43:17'),
(3, 5, 'PO-20250921-121853-69c3c5', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKDSTSzGtqr0zW', NULL, NULL, 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 04:48:53', '2025-09-21 04:48:53'),
(4, 5, 'PO-20250921-122722-b4e491', 918000.00, 'INR', 'razorpay', 'pending', 'pending', NULL, NULL, NULL, 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 04:57:22', '2025-09-21 04:57:22'),
(5, 5, 'PO-20250921-123722-52d34e', 36000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKDlzfW251YpPE', NULL, NULL, 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 05:07:22', '2025-09-21 05:07:22'),
(6, 5, 'PO-20250921-135515-765beb', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKF6IDs3n0apm0', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 06:25:15', '2025-09-21 06:25:16'),
(7, 5, 'PO-20250921-140106-06d6a4', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKFCSYhociG9TM', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 06:31:06', '2025-09-21 06:31:07'),
(8, 5, 'PO-20250921-140137-dd87dd', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKFCzZjSFwh3Mr', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 06:31:37', '2025-09-21 06:31:37'),
(9, 5, 'PO-20250921-140951-9f5fc7', 0.00, 'INR', 'razorpay', 'pending', 'pending', NULL, NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 06:39:51', '2025-09-21 06:39:51'),
(10, 5, 'PO-20250921-141020-8a34dc', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKFMCkNfvT195s', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 06:40:20', '2025-09-21 06:40:20'),
(11, 5, 'PO-20250921-141045-68613f', 9000.00, 'INR', 'razorpay', 'paid', 'processing', 'order_RKFMennITtuAMq', 'pay_RKFMlqIOqCwrOq', '54040c0b6500e5650ddbdd592ef9c12b6c80d6090b853b9172d2f5dfc6c9f080', 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 06:40:45', '2025-09-21 06:41:05'),
(12, 5, 'PO-20250921-145848-888f99', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKGBPrdIgQ06DH', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 07:28:48', '2025-09-21 07:28:49'),
(13, 5, 'PO-20250921-150129-99449a', 9000.00, 'INR', 'razorpay', 'paid', 'processing', 'order_RKGEEcXnzRpJVm', 'pay_RKGERtnyLKaBrM', '769a597d0e97cfd5b4662d8513a22b2913ff793742f83679f43defc9802f0ad1', 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 07:31:29', '2025-09-21 07:31:55'),
(14, 5, 'PO-20250923-170930-3ea50c', 600.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RL5Tdpv5FMekIP', NULL, NULL, 'Purathel\nAnakkal PO\nKanjirapally, Kerala, 686508\nIndia\nPhone: 9495470077', '2025-09-23 09:39:30', '2025-09-23 09:39:30'),
(15, 5, 'PO-20251006-164057-5eb1ea', 1000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RQDw376Z48bOJn', NULL, NULL, 'Purathel\nAnakkal PO\nKanjirapally, Kerala, 686508\nIndia\nPhone: 9495470077', '2025-10-06 09:10:57', '2025-10-06 09:10:57');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `purchase_order_id` int(10) UNSIGNED NOT NULL,
  `supplier_product_id` int(10) UNSIGNED DEFAULT NULL,
  `materials_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `colors_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `supplier_product_id`, `materials_id`, `name`, `price`, `quantity`, `supplier_id`, `created_at`, `colors_json`) VALUES
(1, 1, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 04:42:03', NULL),
(2, 2, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 04:42:03', NULL),
(3, 3, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 04:48:53', NULL),
(4, 4, 3, NULL, 'gift box', 9000.00, 102, 8, '2025-09-21 04:57:22', '[{\"color\":\"blue\",\"qty\":12},{\"color\":\"green\",\"qty\":90}]'),
(5, 5, 3, NULL, 'gift box', 9000.00, 4, 8, '2025-09-21 05:07:22', '[{\"color\":\"green\",\"qty\":4}]'),
(6, 6, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 06:25:15', '[{\"color\":\"red\",\"qty\":1}]'),
(7, 7, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 06:31:06', '[{\"color\":\"red\",\"qty\":1}]'),
(8, 8, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 06:31:37', '[{\"color\":\"red\",\"qty\":1}]'),
(9, 8, NULL, 1, 'flower', 0.00, 1, 8, '2025-09-21 06:31:37', '[{\"color\":\"red\",\"qty\":1}]'),
(10, 9, NULL, 1, 'flower', 0.00, 1, 8, '2025-09-21 06:39:51', '[{\"color\":\"red\",\"qty\":1}]'),
(11, 10, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 06:40:20', '[{\"color\":\"red\",\"qty\":1}]'),
(12, 11, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 06:40:45', '[{\"color\":\"red\",\"qty\":1}]'),
(13, 11, NULL, 1, 'flower', 0.00, 1, 8, '2025-09-21 06:40:45', '[{\"color\":\"red\",\"qty\":1}]'),
(14, 12, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 07:28:48', '[{\"color\":\"grren\",\"qty\":1}]'),
(15, 13, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 07:31:29', '[{\"color\":\"grren\",\"qty\":1}]'),
(16, 14, 7, NULL, 'nuts box', 600.00, 1, 8, '2025-09-23 09:39:30', '[{\"color\":\"red\",\"qty\":1}]'),
(17, 15, 6, NULL, 'wedding hamper', 1000.00, 1, 8, '2025-10-06 09:10:57', '[{\"color\":\"pink\",\"qty\":1}]');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `artwork_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `sentiment` enum('Positive','Neutral','Negative') DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `artwork_id`, `rating`, `comment`, `sentiment`, `status`, `admin_reply`, `created_at`, `updated_at`) VALUES
(1, 11, 25, 5, 'excellent product', 'Positive', 'pending', 'thankyou', '2025-10-22 11:39:19', '2025-10-31 04:12:43'),
(2, 11, 27, 4, 'nice', 'Positive', 'approved', 'thankyou', '2025-10-22 11:45:50', '2025-10-27 09:00:22'),
(4, 11, 16, 4, 'delay of order  lag in date', 'Neutral', 'pending', NULL, '2025-10-24 08:31:06', '2025-10-27 09:02:32'),
(5, 11, 8, 5, 'super boqutes i like very much', 'Positive', 'pending', NULL, '2025-10-27 08:33:48', '2025-10-27 09:00:22'),
(6, 11, 34, 5, 'good product', 'Positive', 'pending', NULL, '2025-10-27 08:39:11', '2025-10-27 09:00:22'),
(8, 11, 32, 5, 'nice product', NULL, 'pending', NULL, '2025-10-28 16:17:12', '2025-10-28 16:17:12');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'admin'),
(2, 'customer'),
(3, 'supplier');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_tracking_history`
--

CREATE TABLE `shipment_tracking_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `awb_code` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL,
  `status_code` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `tracking_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `razorpay_subscription_id` varchar(100) DEFAULT NULL,
  `razorpay_plan_id` varchar(100) DEFAULT NULL,
  `status` enum('created','authenticated','active','pending','halted','cancelled','completed','expired') DEFAULT 'created',
  `current_start` timestamp NULL DEFAULT NULL,
  `current_end` timestamp NULL DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `total_count` int(11) DEFAULT NULL,
  `paid_count` int(11) DEFAULT 0,
  `remaining_count` int(11) DEFAULT NULL,
  `notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notes`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `plan_id`, `razorpay_subscription_id`, `razorpay_plan_id`, `status`, `current_start`, `current_end`, `quantity`, `total_count`, `paid_count`, `remaining_count`, `notes`, `created_at`, `updated_at`) VALUES
(1, 19, 2, 'pl_plink_RrlqUe3DtgkL2w', NULL, 'cancelled', NULL, NULL, 1, 12, 0, NULL, '{\"payment_method\":\"payment_link\"}', '2025-12-15 05:24:20', '2026-01-06 05:04:58'),
(2, 19, 2, NULL, NULL, 'cancelled', '2025-12-16 05:54:05', '2026-01-15 05:54:05', 1, 12, 1, 11, NULL, '2025-12-16 10:24:05', '2026-01-06 05:04:58'),
(3, 19, 3, 'order_Rtl2xX5A32fuqi', NULL, 'cancelled', '2025-12-20 05:55:56', '2026-01-20 05:55:56', 1, 1, 1, 0, '{\"razorpay_order_id\": \"order_Rtl2xX5A32fuqi\", \"billing_period\": \"monthly\", \"amount\": 99900, \"payment_type\": \"order\", \"razorpay_payment_id\": \"pay_Rtl3G777oDCUDl\", \"payment_verified_at\": \"2025-12-20 11:25:56\"}', '2025-12-20 05:55:22', '2026-01-06 05:04:58'),
(4, 19, 2, 'order_Rtl8OUOAJHaoQj', NULL, 'cancelled', NULL, NULL, 1, 1, 0, 1, '{\"razorpay_order_id\":\"order_Rtl8OUOAJHaoQj\",\"billing_period\":\"monthly\",\"amount\":49900,\"payment_type\":\"order\"}', '2025-12-20 06:00:31', '2026-01-06 05:04:58'),
(5, 19, 3, 'order_Rze5D2FjuJ2RPb', NULL, 'cancelled', '2026-01-04 03:01:14', '2026-02-04 03:01:14', 1, 1, 1, 0, '{\"razorpay_order_id\": \"order_Rze5D2FjuJ2RPb\", \"billing_period\": \"monthly\", \"amount\": 99900, \"payment_type\": \"order\", \"razorpay_payment_id\": \"pay_Rze5T0oQPlNFQv\", \"payment_verified_at\": \"2026-01-04 08:31:14\"}', '2026-01-04 03:00:43', '2026-01-06 05:04:58'),
(6, 19, 2, 'order_RzeZ1pQVe8q9S2', NULL, 'cancelled', '2026-01-04 03:29:20', '2026-02-04 03:29:20', 1, 1, 1, 0, '{\"razorpay_order_id\": \"order_RzeZ1pQVe8q9S2\", \"billing_period\": \"monthly\", \"amount\": 49900, \"payment_type\": \"order\", \"razorpay_payment_id\": \"pay_RzeZDIZ40cuXY2\", \"payment_verified_at\": \"2026-01-04 08:59:20\"}', '2026-01-04 03:28:57', '2026-01-06 05:04:58'),
(7, 19, 3, 'order_RzhqM0hdy05pyf', NULL, 'cancelled', '2026-01-04 06:42:01', '2026-02-04 06:42:01', 1, 1, 1, 0, '{\"razorpay_order_id\": \"order_RzhqM0hdy05pyf\", \"billing_period\": \"monthly\", \"amount\": 99900, \"payment_type\": \"order\", \"razorpay_payment_id\": \"pay_RzhqmCrBeYpnnq\", \"payment_verified_at\": \"2026-01-04 12:12:01\"}', '2026-01-04 06:41:25', '2026-01-06 05:04:58'),
(8, 19, 2, 'order_S0SjXFJUnmH0A7', NULL, 'cancelled', '2026-01-06 04:34:04', '2026-02-06 04:34:04', 1, 1, 1, 0, '{\"razorpay_order_id\": \"order_S0SjXFJUnmH0A7\", \"billing_period\": \"monthly\", \"amount\": 49900, \"payment_type\": \"order\", \"razorpay_payment_id\": \"pay_S0SjkiaGJaGxPC\", \"payment_verified_at\": \"2026-01-06 10:04:04\"}', '2026-01-06 04:33:34', '2026-01-06 05:04:58'),
(9, 19, 3, 'order_S0SkIvwNB37Zjm', NULL, 'cancelled', '2026-01-06 04:34:41', '2026-02-06 04:34:41', 1, 1, 1, 0, '{\"razorpay_order_id\": \"order_S0SkIvwNB37Zjm\", \"billing_period\": \"monthly\", \"amount\": 99900, \"payment_type\": \"order\", \"razorpay_payment_id\": \"pay_S0SkQL0C4hDb4V\", \"payment_verified_at\": \"2026-01-06 10:04:41\"}', '2026-01-06 04:34:18', '2026-01-06 05:04:58'),
(10, 19, 1, NULL, NULL, 'cancelled', NULL, NULL, 1, NULL, 0, NULL, NULL, '2026-01-06 05:04:55', '2026-01-06 05:04:58'),
(11, 19, 2, NULL, NULL, 'cancelled', NULL, NULL, 1, NULL, 0, NULL, NULL, '2026-01-06 05:04:57', '2026-01-06 05:04:58'),
(12, 19, 3, NULL, NULL, 'cancelled', NULL, NULL, 1, NULL, 0, NULL, NULL, '2026-01-06 05:04:58', '2026-01-06 05:28:52'),
(13, 19, 2, 'order_S0Tg1MGZOYdAvN', NULL, 'cancelled', '2026-01-06 05:29:18', '2026-02-06 05:29:18', 1, 1, 1, 0, '{\"razorpay_order_id\": \"order_S0Tg1MGZOYdAvN\", \"billing_period\": \"monthly\", \"amount\": 49900, \"payment_type\": \"order\", \"razorpay_payment_id\": \"pay_S0Tg9Gwo6xIdyF\", \"payment_verified_at\": \"2026-01-06 10:59:18\"}', '2026-01-06 05:28:56', '2026-01-06 05:29:29'),
(14, 19, 3, 'order_S0TgbcGWG2betK', NULL, 'cancelled', '2026-01-06 05:29:51', '2026-02-06 05:29:51', 1, 1, 1, 0, '{\"razorpay_order_id\": \"order_S0TgbcGWG2betK\", \"billing_period\": \"monthly\", \"amount\": 99900, \"payment_type\": \"order\", \"razorpay_payment_id\": \"pay_S0Tgj3OONKcO7d\", \"payment_verified_at\": \"2026-01-06 10:59:51\"}', '2026-01-06 05:29:29', '2026-01-06 05:53:05'),
(15, 19, 2, 'order_S0U5Z3yHebdd5D', NULL, 'created', NULL, NULL, 1, 1, 0, 1, '{\"razorpay_order_id\":\"order_S0U5Z3yHebdd5D\",\"billing_period\":\"monthly\",\"amount\":49900,\"payment_type\":\"order\"}', '2026-01-06 05:53:07', '2026-01-06 05:53:07');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_invoices`
--

CREATE TABLE `subscription_invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `razorpay_invoice_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `status` enum('issued','paid','partially_paid','cancelled','expired') DEFAULT 'issued',
  `invoice_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `billing_period` enum('monthly','yearly') DEFAULT 'monthly',
  `razorpay_plan_id` varchar(100) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `plan_code`, `name`, `description`, `price`, `currency`, `billing_period`, `razorpay_plan_id`, `features`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'free', 'Free', 'Limited access to free tutorials', 0.00, 'INR', 'monthly', NULL, '[\"Limited free tutorials\",\"Basic video quality\",\"Community support\"]', 1, '2025-12-15 04:52:04', '2025-12-15 04:52:04'),
(2, 'premium', 'Premium', 'Unlimited access to all tutorials', 499.00, 'INR', 'monthly', NULL, '[\"Unlimited tutorial access\",\"HD video quality\",\"New content weekly\",\"Priority support\",\"Download videos\"]', 1, '2025-12-15 04:52:04', '2025-12-15 04:52:04'),
(3, 'pro', 'Pro', 'Everything in Premium plus mentorship', 999.00, 'INR', 'monthly', NULL, '[\"Everything in Premium\",\"1-on-1 mentorship\",\"Live workshops\",\"Certificate of completion\",\"Early access to new content\"]', 1, '2025-12-15 04:52:04', '2025-12-15 04:52:04');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_products`
--

CREATE TABLE `supplier_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(32) NOT NULL DEFAULT 'pcs',
  `availability` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `image_url` varchar(500) DEFAULT NULL,
  `is_trending` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_products`
--

INSERT INTO `supplier_products` (`id`, `supplier_id`, `name`, `description`, `category`, `sku`, `price`, `quantity`, `unit`, `availability`, `image_url`, `is_trending`, `status`, `created_at`, `updated_at`) VALUES
(2, 7, 'gift box', 'trending one', 'Gift box', NULL, 1000.00, 0, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/7/sp_20250910_124402_b8014cb9564e.jpg', 1, 'pending', '2025-09-10 05:14:02', '2025-09-10 05:14:02'),
(3, 8, 'gift box', 'trending gift box', 'Gift box', NULL, 9000.00, 20, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250914_123124_547bee2dfc4b.jpg', 1, 'pending', '2025-09-14 05:01:24', '2025-09-14 05:01:24'),
(4, 8, 'gift box', 'glass typ material', 'Gift box', NULL, 1000.00, 10, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_150821_861843afed99.jpg', 1, 'pending', '2025-09-21 07:38:21', '2025-09-21 07:38:21'),
(5, 8, 'round box', 'red theme', 'Gift box', NULL, 700.00, 10, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_151034_6c8cc5f7a2f9.jpg', 1, 'pending', '2025-09-21 07:40:34', '2025-09-21 07:40:34'),
(6, 8, 'wedding hamper', 'weeding hamper', 'Gift box', NULL, 1000.00, 5, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_151306_072915aa4609.jpg', 1, 'pending', '2025-09-21 07:43:06', '2025-09-21 07:43:06'),
(7, 8, 'nuts box', 'nuts box', 'Gift box', NULL, 600.00, 5, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_151454_b3f32bf0d7d0.jpg', 1, 'pending', '2025-09-21 07:44:54', '2025-09-21 07:44:54'),
(8, 8, 'ring holder', 'ring', 'Gift box', NULL, 600.00, 10, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_160839_13a80d5e0b13.jpg', 1, 'pending', '2025-09-21 08:38:39', '2025-09-21 08:38:39');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_profiles`
--

CREATE TABLE `supplier_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `shop_name` varchar(120) NOT NULL DEFAULT '',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_profiles`
--

INSERT INTO `supplier_profiles` (`user_id`, `shop_name`, `status`, `created_at`, `updated_at`) VALUES
(7, '', 'approved', '2025-09-10 03:18:27', '2025-09-10 03:19:18'),
(8, '', 'approved', '2025-09-13 00:38:52', '2025-09-13 00:39:33'),
(16, 'jbjjbbjbb', 'rejected', '2025-10-15 03:46:25', '2026-01-06 08:59:39'),
(17, 'kripa shop', 'pending', '2025-10-21 05:15:37', '2025-10-21 05:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `template_categories`
--

CREATE TABLE `template_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `template_categories`
--

INSERT INTO `template_categories` (`id`, `name`, `display_name`, `description`, `icon`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Birthday', 'Birthday', 'Birthday celebration templates', 'gift', 1, 1, '2026-01-16 07:57:50'),
(2, 'Wedding', 'Wedding', 'Wedding and anniversary templates', 'heart', 2, 1, '2026-01-16 07:57:50'),
(3, 'Invitation', 'Invitation', 'Event invitation templates', 'mail', 3, 1, '2026-01-16 07:57:50'),
(4, 'Posters', 'Posters', 'Poster and banner templates', 'image', 4, 1, '2026-01-16 07:57:50'),
(5, 'Photo Frames', 'Photo Frames', 'Photo frame templates', 'frame', 5, 1, '2026-01-16 07:57:50');

-- --------------------------------------------------------

--
-- Table structure for table `template_usage`
--

CREATE TABLE `template_usage` (
  `id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `user_type` enum('customer','admin') DEFAULT 'customer',
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutorials`
--

CREATE TABLE `tutorials` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `price` decimal(10,2) DEFAULT 0.00,
  `is_free` tinyint(1) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutorials`
--

INSERT INTO `tutorials` (`id`, `title`, `description`, `thumbnail_url`, `video_url`, `duration`, `difficulty_level`, `price`, `is_free`, `category`, `created_by`, `created_at`, `updated_at`, `is_active`) VALUES
(2, 'cap embroidery', 'cap embroidery ', NULL, 'uploads/tutorials/videos/video_1765203120_6936dcb08a17e.mp4', NULL, 'beginner', 39.97, 0, 'Hand Embroidery', 5, '2025-12-08 14:12:00', '2025-12-23 04:43:09', 1),
(3, 'Mehandi Tutorial', ' simple Mehandi Tutorial', 'uploads/tutorials/thumb_1765858714_6940dd9a043c3.jpg', 'uploads/tutorials/videos/video_1765858714_6940dd9a03b50.mp4', NULL, 'beginner', 40.00, 0, 'Mylanchi / Mehandi Art', 5, '2025-12-16 04:18:34', '2025-12-23 04:43:09', 1),
(4, 'Watermelon Candle Making', 'Fruits candle  making', 'uploads/tutorials/thumb_1765859773_6940e1bd040c8.jpg', 'uploads/tutorials/videos/video_1765859773_6940e1bd03a51.mp4', NULL, 'beginner', 30.00, 0, 'Candle Making', 5, '2025-12-16 04:36:13', '2025-12-23 04:43:09', 1),
(5, 'Pearl Jewelery', 'Perl jewelary', 'uploads/tutorials/thumb_1765861176_6940e73803c8c.jpg', 'uploads/tutorials/videos/video_1765861176_6940e738031ea.mp4', NULL, 'intermediate', 50.00, 0, 'Jewelry Making', 5, '2025-12-16 04:59:36', '2025-12-23 04:43:09', 1),
(6, 'Mirror clay', 'Mirror clay', 'uploads/tutorials/thumb_1765862869_6940edd5a2af8.jpg', 'uploads/tutorials/videos/video_1765862869_6940edd5a24f5.mp4', NULL, 'intermediate', 40.00, 0, 'Clay Modeling', 5, '2025-12-16 05:27:49', '2025-12-23 04:43:09', 1),
(7, 'Clock resin art', 'Clock resin art', 'uploads/tutorials/thumb_1765864371_6940f3b31d6a9.jpg', 'uploads/tutorials/videos/video_1765864371_6940f3b31cbfe.mp4', NULL, 'advanced', 59.99, 0, 'Resin Art', 5, '2025-12-16 05:52:51', '2025-12-23 04:43:09', 1),
(9, 'Kitkat Chocolate boquetes', '', 'uploads/tutorials/thumb_1766134132_694511743655e.jpg', 'uploads/tutorials/videos/video_1766134132_69451174358f4.mp4', NULL, 'intermediate', 44.93, 0, 'Gift Making', 5, '2025-12-19 08:48:52', '2025-12-23 04:43:09', 1),
(10, 'Earing', 'Sugar beed Earing Tutorial', 'uploads/tutorials/thumb_1766463692_694a18cc36ff2.png', 'uploads/tutorials/videos/video_1766463692_694a18cc3677f.mp4', NULL, 'intermediate', 29.98, 1, 'Jewelry Making', 5, '2025-12-23 04:21:32', '2025-12-23 04:43:09', 1),
(11, 'Ring', 'Sugar bead earing', 'uploads/tutorials/thumb_1766464044_694a1a2c455ea.jpg', 'uploads/tutorials/videos/video_1766464044_694a1a2c450d1.mp4', NULL, 'intermediate', 20.00, 0, 'Jewelry Making', 5, '2025-12-23 04:27:24', '2025-12-23 04:43:09', 1),
(12, 'Ring', 'Ring', 'uploads/tutorials/thumb_1766464088_694a1a589cb49.jpg', 'uploads/tutorials/videos/video_1766464088_694a1a589c683.mp4', NULL, 'intermediate', 29.97, 0, 'Jewelry Making', 5, '2025-12-23 04:28:08', '2025-12-23 04:43:09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tutorial_purchases`
--

CREATE TABLE `tutorial_purchases` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `tutorial_id` int(10) UNSIGNED NOT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'completed',
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutorial_purchases`
--

INSERT INTO `tutorial_purchases` (`id`, `user_id`, `tutorial_id`, `purchase_date`, `expiry_date`, `payment_method`, `payment_status`, `amount_paid`, `razorpay_order_id`, `razorpay_payment_id`) VALUES
(1, 19, 2, '2025-12-08 15:55:23', NULL, 'razorpay', 'completed', 39.97, 'order_RpnkxG2VO2dFW9', 'pay_RpnlJu64xVhamn'),
(12, 19, 4, '2025-12-16 05:29:52', NULL, 'razorpay', 'completed', 30.00, 'order_RsATSHHR2fcIN5', 'pay_RsATlisw1BToYz');

-- --------------------------------------------------------

--
-- Table structure for table `tutorial_user_profiles`
--

CREATE TABLE `tutorial_user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `razorpay_customer_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `created_at`, `updated_at`, `razorpay_customer_id`) VALUES
(1, 'shijin', 'thomas', 'shijinthomas369@gmail.com', '$2y$10$i7sKI1aenMGqrzx7SWQU0eSx1XScN2YAX22TQsJFdjImNt8yIqT5e', '2025-08-15 10:20:09', '2025-08-15 10:20:09', NULL),
(2, 'shijin', 'thomas', 'shijinthomas248@gmail.com', '$2y$10$86CWTt86VyN9kgHzHK9JaujQhfFCdE40uykQylM4UDWw2vlH2LvCq', '2025-08-15 10:29:21', '2025-08-15 10:29:21', NULL),
(5, 'Admin', 'User', 'fathima470077@gmail.com', '$2y$10$9RO.o7wA3NcbRmB74Nt9qeH6h84xT3c05pbXqjtIPfLD383m4aGfi', '2025-08-15 22:58:13', '2025-08-15 22:58:13', NULL),
(8, 'fathima', 'shibu', 'fathimashibu0805@gmail.com', NULL, '2025-09-13 00:38:52', '2025-09-13 00:38:52', NULL),
(9, 'VIJETHA', 'JINU', 'vijethajinu@gmail.com', NULL, '2025-09-15 02:52:24', '2025-09-15 02:52:24', NULL),
(10, 'kiran', 'shibu', 'kiranshibuthomas2026@mca.ajce.in', '$2y$10$WBFKmNJe0lnpMiKcrBmm3Oe4YJPNhlJKXoqpzMVYqIF9fLXJV2rpS', '2025-09-16 22:39:49', '2025-09-16 22:55:37', NULL),
(11, 'Fathima', '', 'fathimashibu15@gmail.com', NULL, '2025-09-17 00:52:37', '2025-09-17 00:52:37', NULL),
(12, 'FATHIMA SHIBU', 'MCA2024-2026', 'fathimashibu2026@mca.ajce.in', NULL, '2025-09-22 03:20:48', '2025-09-22 03:20:48', NULL),
(13, 'Sera', 'Mol', 'seramol1508@gmail.com', '$2y$10$mxHGIaajQzST9Q6GwaODAemGW.ddnJWtPR7qiWGwVpm.UR0jgFUY2', '2025-09-22 08:48:56', '2025-10-15 10:30:51', NULL),
(14, 'Fathima', 'Shibu', 'fathima686231@gmail.com', NULL, '2025-09-22 08:54:05', '2025-09-22 08:54:05', NULL),
(15, 'Fathima', 'Shibu', 'nobinrajeev2026@mca.ajce.in', '$2y$10$f5vO0p2gJXAz7VVp7BV7xOH84hw2vubqc3Gv87nm9CvHTnsyUSxeO', '2025-10-15 03:43:51', '2025-10-15 03:43:51', NULL),
(16, 'Fathima', 'Shibu', 'thomasshijin@gmail.com', '$2y$10$9Y3L8Doxc8LZO5S0ZeyBTuCikojug08jM7kOYPxf9MiK8ggygqI6y', '2025-10-15 03:46:25', '2025-10-15 03:46:25', NULL),
(17, 'Fiya', 'Fathim', 'fiyafathim19@gmail.com', NULL, '2025-10-21 05:15:37', '2025-10-21 05:15:37', NULL),
(18, 'Shifa', 'Fathima', 'shifafathima0815@gmail.com', NULL, '2025-10-24 00:49:25', '2025-10-24 00:49:25', NULL),
(19, '', '', 'soudhame52@gmail.com', '', '2025-12-08 15:54:38', '2025-12-15 04:59:05', 'cust_RrlPpiqWhd5FBn');

-- --------------------------------------------------------

--
-- Table structure for table `user_behavior_log`
--

CREATE TABLE `user_behavior_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `behavior_type` varchar(50) NOT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 2),
(2, 2),
(2, 3),
(5, 1),
(5, 2),
(8, 3),
(9, 2),
(10, 2),
(11, 2),
(12, 2),
(13, 2),
(14, 2),
(15, 2),
(16, 3),
(17, 3),
(18, 2),
(19, 2);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `artwork_id` int(10) UNSIGNED NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `artwork_id`, `added_at`) VALUES
(15, 1, 2, '2025-09-21 07:47:35'),
(20, 1, 12, '2025-09-29 04:22:28'),
(26, 1, 23, '2025-10-20 11:52:47'),
(27, 1, 22, '2025-10-20 12:26:57'),
(28, 11, 33, '2025-10-21 00:01:59'),
(29, 18, 34, '2025-10-24 03:49:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `artworks`
--
ALTER TABLE `artworks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_artworks_category` (`category_id`),
  ADD KEY `idx_artworks_status` (`status`),
  ADD KEY `idx_artworks_rating` (`average_rating`,`total_ratings`);

--
-- Indexes for table `auth_providers`
--
ALTER TABLE `auth_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_provider_user` (`provider`,`provider_user_id`),
  ADD KEY `fk_auth_providers_user` (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart` (`user_id`,`artwork_id`),
  ADD KEY `idx_cart_user` (`user_id`),
  ADD KEY `idx_cart_artwork` (`artwork_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_category_name` (`name`);

--
-- Indexes for table `courier_serviceability_cache`
--
ALTER TABLE `courier_serviceability_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pincodes` (`pickup_pincode`,`delivery_pincode`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `custom_requests`
--
ALTER TABLE `custom_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_customer_email` (`customer_email`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `custom_request_designs`
--
ALTER TABLE `custom_request_designs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_id` (`request_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_template_id` (`template_id`);

--
-- Indexes for table `custom_request_images`
--
ALTER TABLE `custom_request_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `design_templates`
--
ALTER TABLE `design_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_invoice_number` (`invoice_number`),
  ADD UNIQUE KEY `uniq_invoice_order` (`order_id`);

--
-- Indexes for table `learning_progress`
--
ALTER TABLE `learning_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_tutorial` (`user_id`,`tutorial_id`);

--
-- Indexes for table `live_sessions`
--
ALTER TABLE `live_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subject` (`subject_id`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled` (`scheduled_date`,`scheduled_time`);

--
-- Indexes for table `live_session_registrations`
--
ALTER TABLE `live_session_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`session_id`,`user_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `live_subjects`
--
ALTER TABLE `live_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `offers_promos`
--
ALTER TABLE `offers_promos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_shiprocket_order_id` (`shiprocket_order_id`),
  ADD KEY `idx_shiprocket_shipment_id` (`shiprocket_shipment_id`),
  ADD KEY `idx_awb_code` (`awb_code`),
  ADD KEY `idx_courier_id` (`courier_id`);

--
-- Indexes for table `order_addons`
--
ALTER TABLE `order_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_payments`
--
ALTER TABLE `order_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_requirements`
--
ALTER TABLE `order_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `order_ref` (`order_ref`);

--
-- Indexes for table `order_requirement_messages`
--
ALTER TABLE `order_requirement_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requirement_id` (`requirement_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD KEY `email` (`email`),
  ADD KEY `idx_email_token` (`email`,`token`);

--
-- Indexes for table `practice_uploads`
--
ALTER TABLE `practice_uploads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `product_chat_messages`
--
ALTER TABLE `product_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_cart_item_id` (`cart_item_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_sender` (`sender_type`,`sender_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_read_status` (`is_read`),
  ADD KEY `idx_product_messages` (`product_id`,`user_id`,`created_at`),
  ADD KEY `idx_cart_messages` (`cart_item_id`,`created_at`);

--
-- Indexes for table `product_ratings`
--
ALTER TABLE `product_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_artwork_rating` (`order_id`,`artwork_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_artwork_id` (`artwork_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_artwork` (`user_id`,`artwork_id`),
  ADD KEY `idx_artwork_status` (`artwork_id`,`status`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `shipment_tracking_history`
--
ALTER TABLE `shipment_tracking_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_awb_code` (`awb_code`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_razorpay_subscription_id` (`razorpay_subscription_id`);

--
-- Indexes for table `subscription_invoices`
--
ALTER TABLE `subscription_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subscription` (`subscription_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_razorpay_invoice_id` (`razorpay_invoice_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_code` (`plan_code`),
  ADD KEY `idx_plan_code` (`plan_code`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `supplier_products`
--
ALTER TABLE `supplier_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `status` (`status`),
  ADD KEY `category` (`category`),
  ADD KEY `is_trending` (`is_trending`);

--
-- Indexes for table `supplier_profiles`
--
ALTER TABLE `supplier_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `template_categories`
--
ALTER TABLE `template_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `template_usage`
--
ALTER TABLE `template_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_template_id` (`template_id`),
  ADD KEY `idx_request_id` (`request_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `tutorials`
--
ALTER TABLE `tutorials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tutorial_purchases`
--
ALTER TABLE `tutorial_purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_purchase` (`user_id`,`tutorial_id`),
  ADD KEY `tutorial_id` (`tutorial_id`);

--
-- Indexes for table `tutorial_user_profiles`
--
ALTER TABLE `tutorial_user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_behavior_log`
--
ALTER TABLE `user_behavior_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_behavior` (`user_id`,`behavior_type`,`created_at`),
  ADD KEY `idx_product_behavior` (`product_id`,`behavior_type`,`created_at`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `fk_user_roles_role` (`role_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_artwork` (`user_id`,`artwork_id`),
  ADD KEY `idx_wishlist_user` (`user_id`),
  ADD KEY `idx_wishlist_artwork` (`artwork_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `artworks`
--
ALTER TABLE `artworks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `auth_providers`
--
ALTER TABLE `auth_providers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7313;

--
-- AUTO_INCREMENT for table `courier_serviceability_cache`
--
ALTER TABLE `courier_serviceability_cache`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_requests`
--
ALTER TABLE `custom_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `custom_request_designs`
--
ALTER TABLE `custom_request_designs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `custom_request_images`
--
ALTER TABLE `custom_request_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `design_templates`
--
ALTER TABLE `design_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=406;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `learning_progress`
--
ALTER TABLE `learning_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `live_sessions`
--
ALTER TABLE `live_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `live_session_registrations`
--
ALTER TABLE `live_session_registrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `live_subjects`
--
ALTER TABLE `live_subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=547;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `offers_promos`
--
ALTER TABLE `offers_promos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `order_addons`
--
ALTER TABLE `order_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `order_payments`
--
ALTER TABLE `order_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_requirements`
--
ALTER TABLE `order_requirements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_requirement_messages`
--
ALTER TABLE `order_requirement_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `practice_uploads`
--
ALTER TABLE `practice_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `product_chat_messages`
--
ALTER TABLE `product_chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_ratings`
--
ALTER TABLE `product_ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shipment_tracking_history`
--
ALTER TABLE `shipment_tracking_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `subscription_invoices`
--
ALTER TABLE `subscription_invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `supplier_products`
--
ALTER TABLE `supplier_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `template_categories`
--
ALTER TABLE `template_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `template_usage`
--
ALTER TABLE `template_usage`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tutorials`
--
ALTER TABLE `tutorials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tutorial_purchases`
--
ALTER TABLE `tutorial_purchases`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tutorial_user_profiles`
--
ALTER TABLE `tutorial_user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `user_behavior_log`
--
ALTER TABLE `user_behavior_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auth_providers`
--
ALTER TABLE `auth_providers`
  ADD CONSTRAINT `fk_auth_providers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_request_designs`
--
ALTER TABLE `custom_request_designs`
  ADD CONSTRAINT `fk_template_id` FOREIGN KEY (`template_id`) REFERENCES `design_templates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `fk_materials_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_addons`
--
ALTER TABLE `order_addons`
  ADD CONSTRAINT `order_addons_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_requirements`
--
ALTER TABLE `order_requirements`
  ADD CONSTRAINT `fk_req_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_requirement_messages`
--
ALTER TABLE `order_requirement_messages`
  ADD CONSTRAINT `fk_req_msg` FOREIGN KEY (`requirement_id`) REFERENCES `order_requirements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_tracking_history`
--
ALTER TABLE `shipment_tracking_history`
  ADD CONSTRAINT `shipment_tracking_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_usage`
--
ALTER TABLE `template_usage`
  ADD CONSTRAINT `template_usage_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `design_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `template_usage_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `custom_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tutorial_purchases`
--
ALTER TABLE `tutorial_purchases`
  ADD CONSTRAINT `tutorial_purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tutorial_purchases_ibfk_2` FOREIGN KEY (`tutorial_id`) REFERENCES `tutorials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
