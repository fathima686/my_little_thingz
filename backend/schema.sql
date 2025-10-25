-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2025 at 11:20 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(2, 'wedding card', 'per card 50 rupees', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 50.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/wedding_card_20250909_162709_3882f9.png', 6, 5, 0, 'in_stock', 1, 'active', '2025-09-09 14:27:09', '2025-10-16 09:19:28', NULL, 0, NULL),
(3, 'poloroids', '20 photos 150 ruppes', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 150.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/poloroid_20250909_162827_bad52f.png', 4, 5, 0, 'in_stock', 1, 'active', '2025-09-09 14:28:27', '2025-10-16 09:19:28', NULL, 0, NULL),
(4, '4 * 4 frame', 'mini frame', '{\"options\":{\"size\":{\"type\":\"select\",\"values\":[{\"value\":\"A5\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"A4\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"A3\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"frame\":{\"type\":\"select\",\"values\":[{\"value\":\"none\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"basic\",\"delta\":{\"type\":\"flat\",\"value\":199}},{\"value\":\"premium\",\"delta\":{\"type\":\"flat\",\"value\":399}}]},\"material\":{\"type\":\"select\",\"values\":[{\"value\":\"paper\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"canvas\",\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 120.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/4_4_frame_20250909_163041_e6da75.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:30:41', '2025-10-16 09:19:28', NULL, 0, NULL),
(5, '6 * 4', 'best friends frames', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 250.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/6_4_frame_20250909_163511_f22d0e.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:35:11', '2025-10-16 09:19:28', NULL, 0, NULL),
(6, 'A3 frame', 'cartoon frame', '{\"options\":{\"size\":{\"type\":\"select\",\"values\":[{\"value\":\"A5\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"A4\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"A3\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"frame\":{\"type\":\"select\",\"values\":[{\"value\":\"none\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"basic\",\"delta\":{\"type\":\"flat\",\"value\":199}},{\"value\":\"premium\",\"delta\":{\"type\":\"flat\",\"value\":399}}]},\"material\":{\"type\":\"select\",\"values\":[{\"value\":\"paper\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"canvas\",\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 749.99, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/A3_frame_20250909_163546_05748c.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:35:46', '2025-10-16 09:19:28', NULL, 0, NULL),
(7, 'album', 'carboard sheet album', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 200.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/album_20250909_163744_d02862.png', 8, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:37:44', '2025-10-16 09:19:28', NULL, 0, NULL),
(8, 'boaqutes', 'red rose boaqutes', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 400.00, 0.50, 300.00, 99.25, '2025-09-25 12:07:00', '2025-09-26 12:07:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/boaqutes_20250909_163941_3994ac.png', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:39:41', '2025-10-16 09:19:28', NULL, 0, NULL),
(10, 'Custom Drawing', 'Sketches', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 1000.00, 0.50, 700.00, 99.30, '2025-10-22 22:51:00', '2025-10-29 22:52:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/drawings_20250909_164237_1da7b5.png', 7, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:42:37', '2025-10-22 17:22:10', NULL, 0, NULL),
(11, 'gift set', 'it consist of gift box boaqutes frames', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 3000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/gift_box_set_20250909_164422_32ae0c.png', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:44:22', '2025-10-16 09:19:28', NULL, 0, NULL),
(12, 'gift box', 'giftbox consist of chocolates and watch', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 2000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/gift_box_20250909_164839_021d32.png', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:48:39', '2025-10-16 09:19:28', NULL, 0, NULL),
(13, 'Micro frame', 'micro frame', '{\"options\":{\"size\":{\"type\":\"select\",\"values\":[{\"value\":\"A5\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"A4\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"A3\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"frame\":{\"type\":\"select\",\"values\":[{\"value\":\"none\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"basic\",\"delta\":{\"type\":\"flat\",\"value\":199}},{\"value\":\"premium\",\"delta\":{\"type\":\"flat\",\"value\":399}}]},\"material\":{\"type\":\"select\",\"values\":[{\"value\":\"paper\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"canvas\",\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 90.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/micro_frame_20250909_164926_56f796.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:49:26', '2025-10-16 09:19:28', NULL, 0, NULL),
(14, 'mini frame', 'mini frame', '{\"options\":{\"size\":{\"type\":\"select\",\"values\":[{\"value\":\"A5\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"A4\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"A3\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"frame\":{\"type\":\"select\",\"values\":[{\"value\":\"none\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"basic\",\"delta\":{\"type\":\"flat\",\"value\":199}},{\"value\":\"premium\",\"delta\":{\"type\":\"flat\",\"value\":399}}]},\"material\":{\"type\":\"select\",\"values\":[{\"value\":\"paper\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"canvas\",\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 149.99, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/mini_frame_20250909_165028_7eaa02.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:50:28', '2025-10-16 09:19:28', NULL, 0, NULL),
(15, 'album', '20  photos 150', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 150.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/poloroid__2__20250909_165107_4ac16f.png', 3, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:51:07', '2025-10-16 09:19:28', NULL, 0, NULL),
(16, 'Hamper', 'wedding hamper', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 2000.00, 0.50, 1500.00, 25.00, '2025-10-28 14:36:00', '2025-10-25 14:36:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/Wedding_hamper_20250909_165223_b117bb.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-09 14:52:23', '2025-10-25 09:06:25', NULL, 0, NULL),
(18, 'wedding set', 'set in wedding', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 6000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/Celebrate_life_s_special_moments_with____20250921_153611_602073.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-21 13:36:11', '2025-10-16 09:19:28', NULL, 0, NULL),
(19, 'boquetes', 'pink flowers', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 500.00, 0.50, 320.00, 99.40, '2025-10-08 19:09:00', '2025-10-09 19:09:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/_artsybaken_20250921_153825_9108f6.jpg', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-21 13:38:25', '2025-10-16 09:19:28', NULL, 0, NULL),
(20, 'trolly hamper', 'birthday hamper', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 1000.00, 0.50, 599.99, 99.40, '2025-10-01 14:28:00', '2025-10-09 14:28:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/Birthday_hamper_20250921_153957_58a57d.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-21 13:39:57', '2025-10-16 09:19:28', NULL, 0, NULL),
(21, 'shirt box', 'shirt hamper', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 500.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/_trousseau__weddingpackaging__giftboxforhim__hamperbox__sliderbox__birthdayhamper__hamperforher__hamperforhim__nammasalem_hamper__hampers__giftbox__instagramreels_r_20250921_154121_26dfde.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-21 13:41:21', '2025-10-16 09:19:28', NULL, 0, NULL),
(22, 'nutt box', 'nuts box', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 1000.00, 0.50, 910.00, 99.10, '2025-09-25 19:07:00', '2025-09-30 19:08:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__3__20250923_162849_9b1084.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-23 14:28:49', '2025-10-16 09:19:28', NULL, 0, NULL),
(23, 'perfume box', 'perfume+watch', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 1000.00, 0.50, 800.00, 20.00, '2025-10-01 14:05:00', '2025-10-10 14:05:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/Perfume_Gift_ideas_watch_gift_ideas_20250923_163345_8fe78d.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-23 14:33:45', '2025-10-16 09:19:28', NULL, 0, NULL),
(24, 'poloroid boquetes', 'boquestes with poloroid', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 500.04, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/beautiful_photos_bouquet______20250923_163516_fd2fb9.jpg', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-23 14:35:16', '2025-10-16 09:19:28', NULL, 0, NULL),
(25, 'kinderjoy boquetes', 'kinderjoy boquetes', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 600.00, 0.50, 400.00, 99.33, '2025-10-01 14:02:00', '2025-10-08 14:02:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__6__20250923_163633_52f745.jpg', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-23 14:36:33', '2025-10-16 09:19:28', NULL, 0, NULL),
(27, 'custom chocoltes', 'choocoo', '{\"options\":{\"flavor\":{\"type\":\"select\",\"values\":[{\"value\":\"milk\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"dark\",\"delta\":{\"type\":\"flat\",\"value\":20}},{\"value\":\"white\",\"delta\":{\"type\":\"flat\",\"value\":10}}]},\"boxSize\":{\"type\":\"select\",\"values\":[{\"value\":\"6pc\",\"delta\":{\"type\":\"flat\",\"value\":0}},{\"value\":\"12pc\",\"delta\":{\"type\":\"flat\",\"value\":150}},{\"value\":\"24pc\",\"delta\":{\"type\":\"flat\",\"value\":350}}]},\"messageLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 25.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/mschocoworld_-_9952979286_20250923_164331_87efb6.jpg', 5, 5, 0, 'in_stock', 0, 'active', '2025-09-23 14:43:31', '2025-10-16 09:19:28', NULL, 0, NULL),
(29, 'birthday gift', 'gift box', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 2000.00, 0.50, NULL, 20.00, '2025-09-24 21:09:00', '2025-09-26 21:10:00', 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__8__20250924_174025_9f9ae8.jpg', 1, 5, 0, 'in_stock', 0, 'active', '2025-09-24 15:40:25', '2025-10-16 09:19:28', NULL, 0, NULL),
(32, 'heart boquetes', 'valentin\'s day gift', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 600.00, 0.50, 399.96, NULL, '2025-09-25 11:04:00', '2025-09-27 11:04:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__9__20250925_073531_8b855b.jpg', 2, 5, 0, 'in_stock', 0, 'active', '2025-09-25 05:35:31', '2025-10-16 09:19:28', NULL, 0, NULL),
(33, 'wedding card', 'blue theme', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 54.98, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__11__20250925_154744_c5c9fc.jpg', 6, 5, 0, 'in_stock', 0, 'active', '2025-09-25 13:47:44', '2025-10-16 09:19:28', NULL, 0, NULL),
(34, 'wedding card', 'red theme', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 50.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/31_Wedding_Card_Ideas_20250925_154819_53c009.jpg', 6, 5, 0, 'in_stock', 0, 'active', '2025-09-25 13:48:19', '2025-10-16 09:19:28', NULL, 0, NULL),
(36, 'birthday card', 'consist of chocolate and pen', '{\"options\":{\"textLength\":{\"type\":\"range\",\"unit\":\"chars\",\"tiers\":[{\"max\":30,\"delta\":{\"type\":\"flat\",\"value\":0}},{\"max\":80,\"delta\":{\"type\":\"flat\",\"value\":99}},{\"max\":160,\"delta\":{\"type\":\"flat\",\"value\":199}}]}}}', 19.99, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__10__20250925_155002_5379f7.jpg', NULL, 5, 0, 'in_stock', 0, 'active', '2025-09-25 13:50:02', '2025-10-16 09:19:28', NULL, 0, NULL),
(47, 'chocolate tower', 'kitkat tower', NULL, 1000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/a_chocolate_tower________20251024_161507_d0b27f.jpeg', 5, 5, 0, 'in_stock', 0, 'active', '2025-10-24 14:15:07', '2025-10-24 14:15:07', NULL, 0, NULL),
(48, 'Diary milk tower', 'blue theme', NULL, 800.00, 0.50, 500.00, 37.50, '2025-10-25 14:34:00', '2025-10-30 14:34:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/download__2__20251024_161548_feb22e.jpeg', 5, 5, 0, 'in_stock', 0, 'active', '2025-10-24 14:15:48', '2025-10-25 09:05:54', NULL, 0, NULL),
(49, 'wedding hamper', 'mr and mrs hamper', NULL, 1000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/Elegant_Handmade_Gifts_for_Engagement___Wedding_20251024_161654_03d2cb.jpeg', 1, 5, 0, 'in_stock', 0, 'active', '2025-10-24 14:16:54', '2025-10-24 14:16:54', NULL, 0, NULL),
(50, 'anniversary set', 'red theme', NULL, 2500.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/Anniversary_Hamper____20251024_161743_8691be.jpeg', NULL, 5, 0, 'in_stock', 0, 'active', '2025-10-24 14:17:43', '2025-10-24 14:17:43', NULL, 0, NULL),
(51, 'birthday set', 'birthday set', NULL, 5000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/Pink_aesthetic_birthday_surrise_20251024_161827_9d4eca.jpeg', 1, 5, 0, 'in_stock', 0, 'active', '2025-10-24 14:18:27', '2025-10-24 14:18:27', NULL, 0, NULL),
(52, 'rose boqutes', 'red', NULL, 499.99, 0.50, 400.00, 20.00, '2025-10-25 14:35:00', '2025-10-30 14:35:00', 1, 'http://localhost/my_little_thingz/backend/uploads/artworks/buket_bunga_mawar_artificial_20251024_161904_98f6a4.jpeg', 2, 5, 0, 'in_stock', 0, 'active', '2025-10-24 14:19:04', '2025-10-25 09:05:41', NULL, 0, NULL),
(53, 'birthday gift box', 'set', NULL, 1000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/Chocolate_hamper_20251024_161957_4ea001.jpeg', 1, 5, 0, 'in_stock', 0, 'active', '2025-10-24 14:19:57', '2025-10-24 14:19:57', NULL, 0, NULL),
(54, 'baby hamper', 'baby items', NULL, 3000.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/Baby_hamper_20251025_110709_3d9e2c.jpeg', 1, 5, 0, 'in_stock', 0, 'active', '2025-10-25 09:07:09', '2025-10-25 09:07:09', NULL, 0, NULL),
(55, 'baby set', 'blue theme', NULL, 2500.00, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/Unique_handmade_gifts_for_every_heart_at_Craft_and_Card__Enjoy_20__off_your_first_order__20251025_110743_d142f8.jpeg', 1, 5, 0, 'in_stock', 0, 'active', '2025-10-25 09:07:43', '2025-10-25 09:07:43', NULL, 0, NULL),
(56, 'baby frame', 'baby detailing frame', NULL, 499.97, 0.50, NULL, NULL, NULL, NULL, 0, 'http://localhost/my_little_thingz/backend/uploads/artworks/Baby_girl_details_frame_and_hamper____________rumi_rumiaesthetic__shiroor_shiroorcreation__babygirl_reel_viral_fyp_trendingreels_20251025_110824_75eeb3.jpeg', 3, 5, 0, 'in_stock', 0, 'active', '2025-10-25 09:08:24', '2025-10-25 09:08:24', NULL, 0, NULL);

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
(2, 1, 'google', '102325132871747177182', '2025-08-16 04:39:32'),
(3, 2, 'google', '104067605047026890772', '2025-08-16 04:40:07'),
(4, 5, 'google', '107043815178028470916', '2025-09-09 10:39:25'),
(7, 8, 'google', '106031145699305807929', '2025-09-13 06:08:52'),
(8, 9, 'google', '115496851249528699969', '2025-09-15 08:22:24'),
(9, 11, 'google', '110350776552417646009', '2025-09-17 06:22:37'),
(10, 12, 'google', '114542462885540925445', '2025-09-22 08:50:48'),
(11, 13, 'google', '116968811920977241300', '2025-09-22 14:18:56'),
(12, 14, 'google', '104464372294846036228', '2025-09-22 14:24:05'),
(13, 17, 'google', '100677287622416457024', '2025-10-21 10:45:37'),
(14, 18, 'google', '112078509104401590064', '2025-10-24 06:19:25');

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
(52, 1, 27, 1, '2025-10-20 17:41:53'),
(53, 1, 22, 1, '2025-10-20 17:56:59'),
(70, 11, 34, 1, '2025-10-25 03:24:55'),
(71, 18, 27, 1, '2025-10-25 09:02:18');

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
(1, 'Gift box', 'Gift box', 'active', '2025-09-09 10:36:50'),
(2, 'boquetes', 'boquetes', 'active', '2025-09-09 10:36:50'),
(3, 'frames', 'frames', 'active', '2025-09-09 10:36:50'),
(4, 'poloroid', 'poloroid', 'active', '2025-09-09 10:36:50'),
(5, 'custom chocolate', 'custom chocolate', 'active', '2025-09-09 10:36:50'),
(6, 'Wedding card', 'Wedding card', 'active', '2025-09-09 10:36:50'),
(7, 'drawings', 'drawings', 'active', '2025-09-09 10:36:50'),
(8, 'album', 'album', 'active', '2025-09-09 10:36:50');

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
  `id` int(11) NOT NULL,
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
-- Dumping data for table `custom_requests`
--

INSERT INTO `custom_requests` (`id`, `user_id`, `title`, `description`, `category_id`, `occasion`, `budget_min`, `budget_max`, `deadline`, `special_instructions`, `gift_tier`, `source`, `artwork_id`, `requested_quantity`, `status`, `created_at`) VALUES
(1, 1, 'gift box for birthday', 'gift box consist of chocolates', NULL, 'Birthday', 500.00, NULL, '2025-09-15', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-11 08:16:30'),
(2, 1, 'bjksjkds', 'kd ndjk', NULL, 'wedding', 39.98, NULL, '2025-09-26', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-12 06:09:13'),
(3, 1, 'Cart customization - wedding - 2025-09-19', 'red flower ', NULL, 'wedding', NULL, NULL, '2025-09-19', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 08:15:05'),
(4, 1, 'Cart customization - christmas - 2025-09-30', 'vft', NULL, 'christmas', NULL, NULL, '2025-09-30', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 08:17:26'),
(5, 1, 'Cart customization - wedding - 2025-09-18', 'purple theme', NULL, 'wedding', NULL, NULL, '2025-09-18', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 08:23:37'),
(6, 1, 'Cart customization - baby_shower - 2025-09-30', 'bh', NULL, 'baby_shower', NULL, NULL, '2025-09-30', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 08:27:06'),
(7, 1, 'Cart customization - baby_shower - 2025-09-30', 'jhhh', NULL, 'baby_shower', NULL, NULL, '2025-09-30', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 08:30:34'),
(8, 1, 'Cart customization - valentine - 2025-09-30', 'kbhxbh', NULL, 'valentine', NULL, NULL, '2025-09-30', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 08:33:21'),
(9, 1, 'Cart customization - birthday - 2025-09-14', 'poloroids with 10 photos', NULL, 'birthday', NULL, NULL, '2025-09-14', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 10:12:12'),
(10, 1, 'Cart customization - birthday - 2025-09-15', 'frame', NULL, 'birthday', NULL, NULL, '2025-09-15', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-12 10:16:53'),
(11, 1, 'anniversary gift', 'gift box consist of chocolates', NULL, 'Anniversary', 2000.00, NULL, '2025-09-15', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-12 10:35:32'),
(12, 1, 'Cart customization - wedding - 2025-09-17', 'wedding card', NULL, 'wedding', NULL, NULL, '2025-09-17', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-13 04:29:21'),
(13, 1, 'Cart customization - birthday - 2025-09-20', 'gift set', NULL, 'birthday', NULL, NULL, '2025-09-20', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-13 05:50:19'),
(14, 1, 'Cart customization - birthday - 2025-09-18', 'red rosses', NULL, 'birthday', NULL, NULL, '2025-09-18', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-13 17:51:41'),
(15, 9, 'birthday  gift', 'golden theme gift box', NULL, 'Birthday', 2000.00, NULL, '2025-09-18', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-15 10:20:45'),
(16, 9, 'wedding card', 'photo card', NULL, 'Wedding', 200.00, NULL, '2025-09-17', '', 'budget', 'form', NULL, 1, 'cancelled', '2025-09-15 10:57:00'),
(17, 10, 'wedding', 'yff', NULL, 'Wedding', 2000.00, NULL, '2025-09-30', '', 'budget', 'form', NULL, 1, 'completed', '2025-09-17 04:45:11'),
(18, 11, 'Cart customization - other - 2025-09-18', 'rfrrf4434443', NULL, 'other', NULL, NULL, '2025-09-18', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-18 08:12:04'),
(19, 11, 'Cart customization - wedding - 2025-09-19', '                                         ttttttttttttttttt            \r\n                                     \r\n                         ', NULL, 'wedding', NULL, NULL, '2025-09-19', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-18 08:14:00'),
(20, 11, 'Cart customization - birthday - 2025-09-23', 'i need 10 photos', NULL, 'birthday', NULL, NULL, '2025-09-23', '', 'budget', 'cart', NULL, 1, 'cancelled', '2025-09-20 13:43:12'),
(21, 1, 'Cart customization - birthday - 2025-09-24', 'blue theme', NULL, 'birthday', NULL, NULL, '2025-09-24', '', 'budget', 'cart', NULL, 1, 'pending', '2025-09-20 14:34:03'),
(22, 11, 'bd', 'yghjjkh', NULL, 'Birthday', 200.00, NULL, '2025-09-25', '', 'budget', 'form', NULL, 1, 'pending', '2025-09-23 06:05:35'),
(23, 11, 'Cart customization - birthday - 2025-10-02', 'need a micro frame', NULL, 'birthday', NULL, NULL, '2025-10-02', '', 'budget', 'cart', NULL, 1, 'completed', '2025-09-23 14:29:50'),
(24, 11, 'Cart customization - baby_shower - 2025-10-02', 'fhghhj', NULL, 'baby_shower', NULL, NULL, '2025-10-02', '', 'budget', 'cart', 27, 1, 'completed', '2025-09-23 14:45:45'),
(25, 11, 'birthday gift', 'blue theme', NULL, 'Birthday', 1999.99, NULL, '2025-10-22', '', 'premium', 'form', NULL, 1, 'completed', '2025-10-21 08:23:59'),
(26, 18, 'Cart customization - birthday - 2025-10-26', 'vfbggnny', NULL, 'birthday', NULL, NULL, '2025-10-26', '', 'budget', 'cart', 27, 1, 'completed', '2025-10-25 09:01:30');

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
(1, 1, 'uploads/custom-requests/68c2855e19ea14.31233524_giftbox.png', '2025-09-11 08:16:30'),
(2, 2, 'uploads/custom-requests/admin_68c3b95b0cf748.50349646_Screenshot_2025-08-19_110222.png', '2025-09-12 06:10:35'),
(3, 2, 'uploads/custom-requests/admin_68c3b96ac09dc7.23792864_Screenshot_2025-08-19_110222.png', '2025-09-12 06:10:50'),
(4, 3, 'uploads/custom-requests/cart_68c3d6898ac679.34186019_Screenshot_2025-08-13_200022.png', '2025-09-12 08:15:05'),
(5, 4, 'uploads/custom-requests/cart_68c3d716e4db56.32790334_giftbox.png', '2025-09-12 08:17:26'),
(6, 3, 'uploads/custom-requests/admin_68c3d75443ba16.17243950_giftbox.png', '2025-09-12 08:18:28'),
(7, 5, 'uploads/custom-requests/cart_68c3d889996fa4.88738438_Screenshot_2025-08-13_194304.png', '2025-09-12 08:23:37'),
(8, 1, 'uploads/custom-requests/admin_68c3d8bf0454d1.40723189_giftbox.png', '2025-09-12 08:24:31'),
(9, 6, 'uploads/custom-requests/cart_68c3d95a7217c7.86892870_giftbox.png', '2025-09-12 08:27:06'),
(10, 7, 'uploads/custom-requests/cart_68c3da2a964ba6.21480005_giftbox.png', '2025-09-12 08:30:34'),
(11, 8, 'uploads/custom-requests/cart_68c3dad16ba016.50878251_giftbox.png', '2025-09-12 08:33:21'),
(12, 9, 'uploads/custom-requests/cart_68c3f1fcee73d5.43732643_Screenshot_2024-07-26_192946.png', '2025-09-12 10:12:12'),
(13, 10, 'uploads/custom-requests/cart_68c3f315b41b92.89872427_Screenshot_2025-08-06_215747.png', '2025-09-12 10:16:53'),
(14, 11, 'uploads/custom-requests/68c3f774d06da0.88489536_giftbox.png', '2025-09-12 10:35:32'),
(15, 12, 'uploads/custom-requests/cart_68c4f321f10f91.48701177_Screenshot_2025-08-13_194304.png', '2025-09-13 04:29:21'),
(16, 13, 'uploads/custom-requests/cart_68c5061b7f7669.76286331_Screenshot_2025-08-13_195837.png', '2025-09-13 05:50:19'),
(17, 14, 'uploads/custom-requests/cart_68c5af2dc7ebb9.90802846_Screenshot_2025-08-13_200022.png', '2025-09-13 17:51:41'),
(18, 14, 'uploads/custom-requests/admin_68c5af609b2450.52810488_Screenshot_2025-08-13_200022.png', '2025-09-13 17:52:32'),
(19, 15, 'uploads/custom-requests/68c7e87d755500.73900552_giftbox.png', '2025-09-15 10:20:45'),
(20, 16, 'uploads/custom-requests/68c7f0fc6e88c9.85452840_Screenshot_2025-08-13_194304.png', '2025-09-15 10:57:00'),
(21, 17, 'uploads/custom-requests/68ca3cd7935c03.43426036_Screenshot_2024-07-26_193102.png', '2025-09-17 04:45:11'),
(22, 18, 'uploads/custom-requests/cart_68cbbed4b19772.52544926_adb.jpg', '2025-09-18 08:12:04'),
(23, 19, 'uploads/custom-requests/cart_68cbbf488ca693.53268668_adb.jpg', '2025-09-18 08:14:00'),
(24, 20, 'uploads/custom-requests/cart_68ceaf701e2720.84443167_adb.jpg', '2025-09-20 13:43:12'),
(25, 21, 'uploads/custom-requests/cart_68cebb5b62a1e1.92658194_adb.jpg', '2025-09-20 14:34:03'),
(26, 22, 'uploads/custom-requests/68d238af659946.33631684_admin_dashboard.png', '2025-09-23 06:05:35'),
(27, 23, 'uploads/custom-requests/cart_68d2aede511337.17953082_Butterfly_candle_holder.jpg', '2025-09-23 14:29:50'),
(28, 24, 'uploads/custom-requests/cart_68d2b2990846c8.97585563_download__6_.jpg', '2025-09-23 14:45:45'),
(29, 25, 'uploads/custom-requests/68f7431f9b9569.58251936_Screenshot_2025-08-13_195837.png', '2025-10-21 08:23:59'),
(30, 26, 'uploads/custom-requests/cart_68fc91eae5c126.67475275_a_chocolate_tower_______.jpeg', '2025-10-25 09:01:30');

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
(1, 43, 'INV-20251022-060300-b48862', '2025-10-22 06:03:44', 'Fathima', 'fathimashibu15@gmail.com', 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 8545746954', 329.98, 0.00, 120.00, 0.00, 449.98, '[{\"name\":\"wedding card\",\"quantity\":1,\"price\":54.98,\"line_total\":54.98},{\"name\":\"custom chocoltes\",\"quantity\":1,\"price\":25,\"line_total\":25},{\"name\":\"6 * 4\",\"quantity\":1,\"price\":250,\"line_total\":250}]', '[]', '2025-10-22 04:03:44', '2025-10-22 04:03:44'),
(2, 44, 'INV-20251022-063957-de46ba', '2025-10-22 06:40:21', 'Fathima', 'fathimashibu15@gmail.com', 'appz sandhosh\nMannarakkayam - Koovappally Road\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 8564789456', 1000.00, 0.00, 60.00, 0.00, 1060.00, '[{\"name\":\"nutt box\",\"quantity\":1,\"price\":1000,\"line_total\":1000}]', '[]', '2025-10-22 04:40:21', '2025-10-22 04:40:21'),
(3, 45, 'INV-20251022-070022-172ba1', '2025-10-22 07:00:45', 'Fathima', 'fathimashibu15@gmail.com', 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9587456217', 500.00, 0.00, 60.00, 0.00, 560.00, '[{\"name\":\"boquetes\",\"quantity\":1,\"price\":500,\"line_total\":500}]', '[]', '2025-10-22 05:00:45', '2025-10-22 05:00:45'),
(4, 46, 'INV-20251024-082131-498482', '2025-10-24 08:22:03', 'Shifa Fathima', 'shifafathima0815@gmail.com', 'shifa fathima\nPattimattam\nKottayam\nanakkal, Kerala, 686518\nIndia\nPhone: 8954746512', 25.00, 0.00, 60.00, 0.00, 85.00, '[{\"name\":\"custom chocoltes\",\"quantity\":1,\"price\":25,\"line_total\":25}]', '[]', '2025-10-24 06:22:03', '2025-10-24 06:22:03'),
(5, 47, 'INV-20251024-105749-7f90c0', '2025-10-24 10:58:19', 'Shifa Fathima', 'shifafathima0815@gmail.com', 'shifa fathima\nAmal Jyothi College of Engineering Skywalk\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 9874561412', 400.00, 0.00, 60.00, 0.00, 460.00, '[{\"name\":\"boaqutes\",\"quantity\":1,\"price\":400,\"line_total\":400}]', '[]', '2025-10-24 08:58:19', '2025-10-24 08:58:19'),
(6, 48, 'INV-20251024-111704-c7059a', '2025-10-24 11:18:47', 'Shifa Fathima', 'shifafathima0815@gmail.com', 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495450077', 1000.00, 0.00, 60.00, 0.00, 1060.00, '[{\"name\":\"nutt box\",\"quantity\":1,\"price\":1000,\"line_total\":1000}]', '[]', '2025-10-24 09:18:47', '2025-10-24 09:18:47');

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
(1, 8, 'flower', 'Flowers', '', '', '', '', '', '', '', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250914_113430_9c357c15a96b.jpg', NULL, 'yellow flowers for boqutes', 50, 'pcs', '2025-09-14 09:34:32', '2025-09-14 09:34:32', 0.00),
(2, 8, 'frame', 'frames', '', '', '', '', '', '', '', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_152436_8e491e286334.jpg', NULL, '6*4 frame', 30, 'pcs', '2025-09-21 13:24:38', '2025-09-21 13:24:38', 0.00);

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
(2, 1, 'ORD-20250912-064702-f270cb', 'delivered', 'razorpay', 'paid', 'order_RGYzo1NeNPAuPc', 'pay_RGZ4lD3DDSOyGz', 'ea2a3513a18b17f2c8a1fa49876ba53062ff73ff0a70c64c231acf292c98284d', 2250.00, 2250.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 04:47:02', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(3, 1, 'ORD-20250912-065609-408699', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-12 04:56:09', NULL, NULL),
(4, 1, 'ORD-20250912-070324-5de70f', 'pending', NULL, 'pending', NULL, NULL, NULL, 120.00, 120.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-12 05:03:24', NULL, NULL),
(5, 1, 'ORD-20250912-070525-6af48e', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-12 05:05:25', NULL, NULL),
(6, 1, 'ORD-20250912-070628-400786', 'pending', NULL, 'pending', NULL, NULL, NULL, 120.00, 120.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-12 05:06:28', NULL, NULL),
(7, 1, 'ORD-20250912-072223-f6aba9', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-12 05:22:23', NULL, NULL),
(8, 1, 'ORD-20250912-081443-57dddd', 'delivered', 'razorpay', 'paid', 'order_RGaUSGjDC0uHXD', 'pay_RGaUcMT48bnDTA', '40d3afc1d58693301eff69caa62c0f0fb2c187527d53bc2af48804dc1b0544b8', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 06:14:43', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(9, 1, 'ORD-20250912-081739-c13563', 'delivered', 'razorpay', 'paid', 'order_RGaXXYEki73TL2', 'pay_RGaXbwfGwu6ehQ', '112f30fd1accc6144acd19b8e29025d0e119ab56027352c0fbc93502120ea062', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 06:17:39', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(10, 1, 'ORD-20250912-103402-777b98', 'delivered', 'razorpay', 'paid', 'order_RGcrctuI0BK3hh', 'pay_RGcrtSGu7IqiYy', 'f23104b8da2aa1cef77e7a1e2c809a0925223c37d00f8e66214a875fa34a7b1c', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 08:34:02', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(11, 1, 'ORD-20250912-121347-6f44d8', 'delivered', 'razorpay', 'paid', 'order_RGeYzyLPujcMWV', 'pay_RGeZBvdQrdCUcg', 'f99f22a27c0e97e43c59882968cb4eb73c6689bc592b4d0c0d3789d636705056', 150.00, 150.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 10:13:47', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(12, 1, 'ORD-20250912-121736-dc99c9', 'delivered', 'razorpay', 'paid', 'order_RGed1GBd4rgAu6', 'pay_RGedqgO02jPPMx', 'f50daa592861af7d2c6cbbfd562c98a78e647c76889978e80d95e76eb6aa90f5', 250.00, 250.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-12 10:17:36', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(13, 1, 'ORD-20250912-122449-a40eff', 'pending', 'razorpay', 'pending', 'order_RGekdJLtU2RAX0', NULL, NULL, 2000.00, 2000.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-12 10:24:49', NULL, NULL),
(14, 1, 'ORD-20250913-061526-12456c', 'delivered', 'razorpay', 'paid', 'order_RGwzav8Gn2aqaZ', 'pay_RGwznsPSJHnM9y', '2d16e571c7d5fceb0b5ccfa9dcdae196d3423f6bbd42468b92a189b16c0909c9', 2000.00, 2000.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-13 04:15:26', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(15, 1, 'ORD-20250913-071058-60cbd7', 'delivered', 'razorpay', 'paid', 'order_RGxwG25tCUcwDh', 'pay_RGxwwO79h0QoTa', 'ba00aeaeb86242faa5940bc46e77cb8aa4c91efa6f97a79fb5a3fac56178ebbd', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-13 05:10:58', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(16, 1, 'ORD-20250913-075311-390498', 'delivered', 'razorpay', 'paid', 'order_RGyeqcfJ05WOD2', 'pay_RGyf5n0arwrKc0', '85af2064c2880c081dc4fa93fb9a99ff888a1603dba24a7cb0625daf219514a8', 3000.00, 3000.00, 0.00, 0.00, 'purathel,anakkal kanjirapally 686598,8765457889', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-13 05:53:11', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(17, 9, 'ORD-20250915-102334-53c51f', 'delivered', 'razorpay', 'paid', 'order_RHoHtfe5EAfVBz', 'pay_RHoI4lQmpiAz1w', 'c106ce2b5c40cd9e41b647029c0943f934175264f06099419a966344c1b4ac88', 2000.00, 2000.00, 0.00, 0.00, 'elemashery,kottyam', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-15 08:23:34', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(18, 9, 'ORD-20250915-120710-dbe9c0', 'delivered', 'razorpay', 'paid', 'order_RHq3JxbBKgi47N', 'pay_RHq3fwRdV36i5l', 'f5c7811a4a59f13e5bec075714af01f5e7fb2a7fd46b119d7041f8afac617f37', 5000.00, 5000.00, 0.00, 0.00, 'vijetha jinu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nVizhikkathodu, Kottayam\nAnakkal ,Kanjirapally, kerala, 686508\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-15 10:07:10', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(19, 11, 'ORD-20250917-082534-7c7848', 'delivered', 'razorpay', 'paid', 'order_RIZLX71USIKr5P', 'pay_RIZLnX25ZDWYbV', 'd13d6f9b2e0764fe13c40a6cdf6eb18485722af378d748e1a88c0b0c54dff216', 2000.00, 2000.00, 0.00, 0.00, 'fathima shibu\nMDR\nThoppumpady, Ernakulam\nKochi, Kerala, 682005\nPhone: 9495470077', NULL, 991031262, 987435320, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-17 06:25:34', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(20, 11, 'ORD-20250918-101700-a9109b', 'delivered', 'razorpay', 'paid', 'order_RIzmIerATMwLuP', 'pay_RIzmx2C2bOXE1H', 'b4c50d2acc2e21660f1717aefb0b9e25583a020fb5c370f399e7c87d51feaac6', 3400.00, 3400.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nPhone: 9495470077', NULL, 991031240, 987435298, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 1.00, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-18 08:17:00', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(21, 1, 'ORD-20250921-151803-e85fbc', 'delivered', 'razorpay', 'paid', 'order_RKGVjxclYpiV2l', 'pay_RKGVucYc1R1yVc', '1142036924fd5675d1b433a1806b7d27a69c80ec2f6350277ce736f4a26ef939', 2400.00, 2400.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nPhone: 9495470077', NULL, 991031223, 987435281, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 1.00, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-21 13:18:03', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(22, 1, 'ORD-20250922-172224-a16dcb', 'pending', 'razorpay', 'pending', 'order_RKhAFZtDXBSYu1', NULL, NULL, 500.00, 500.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nNorth Paravur, Ernakulam\nAnakkal ,Kanjirapally, kerala, 686508\nPhone: 9495402077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-22 15:22:24', NULL, NULL),
(23, 11, 'ORD-20250923-163030-6d671b', 'pending', 'razorpay', 'pending', 'order_RL4oTN9rVwMl9t', NULL, NULL, 90.00, 90.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nThalassery, Kannur\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495430077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-23 14:30:30', NULL, NULL),
(24, 11, 'ORD-20250923-165018-0265ec', 'delivered', 'razorpay', 'paid', 'order_RL59MuPwACZ92h', 'pay_RL59fZQUI2o08Y', '375535169f7d71b728e042aead556c67700f896995e4c83aa8f1f375466fbb0f', 25.00, 25.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495400773', NULL, 991031188, 987435246, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-09-23 14:50:18', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(25, 11, 'ORD-20250924-051144-b530cc', 'pending', 'razorpay', 'pending', 'order_RLHmadGMPG9CKn', NULL, NULL, 500.00, 500.00, 0.00, 0.00, 'fathima shibu\nMannarakkayam - Koovappally Road\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 9475486254', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-24 03:11:44', NULL, NULL),
(26, 11, 'ORD-20250925-060512-02775c', 'pending', 'razorpay', 'pending', 'order_RLhEAs5STcXVHz', NULL, NULL, 1600.00, 1600.00, 0.00, 0.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nKoovapally, Kottayam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-25 04:05:12', NULL, NULL),
(27, 11, 'ORD-20250925-081812-1cac7a', 'pending', NULL, 'pending', NULL, NULL, NULL, 1999.99, 1999.99, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-25 06:18:12', NULL, NULL),
(28, 11, 'ORD-20250925-104439-c14ecc', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-09-25 08:44:39', NULL, NULL),
(29, 11, 'ORD-20251006-130915-760c8b', 'delivered', 'razorpay', 'paid', 'order_RQAKRa4eO4puQS', 'pay_RQAKmkiRdRNV73', 'eaadb8f1570a17c30148758d919b1e00fe2eba0e62281599e8a585e68c61e8e5', 625.00, 625.00, 0.00, 0.00, 'shijin thomas\n42/3154A Prathibha Road\nPadivattom, Ernakulam\nErnakulam, Kerala, 682025\nIndia\nPhone: 9495470077', NULL, 991031157, 987435211, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 1.00, 10.00, 10.00, 10.00, '2025-10-12', '2025-10-06 11:09:15', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(30, 11, 'ORD-20251006-132257-145f7d', 'delivered', 'razorpay', 'paid', 'order_RQAYuFp8lVqAGN', 'pay_RQAZ5mrLeR2mVy', 'a711ddae23919099cced893e0624d715c6b709be8f67190cd9d1d359897aaced', 50.00, 50.00, 0.00, 0.00, 'binil  jacob\n42/3154A Prathibha Road\nPadivattom, Ernakulam\nErnakulam, Kerala, 682025\nIndia\nPhone: 9495470077', NULL, 991030333, 987434391, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-10-06 11:22:57', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(31, 11, 'ORD-20251006-161503-57a92f', 'pending', 'razorpay', 'pending', 'order_RQDUi4Szzuc3an', NULL, NULL, 400.00, 400.00, 0.00, 0.00, 'vijetha  jinu\nDD Golden Gate\nKakkanad West, Ernakulam\nErnakulam, Kerala, 682037\nIndia\nPhone: 8864947452', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-06 14:15:03', NULL, NULL),
(32, 11, 'ORD-20251006-163704-0a0322', 'delivered', 'razorpay', 'paid', 'order_RQDrxNZ9MDI765', 'pay_RQDsNer3E0XVww', 'e125a822b944c5a42956ac0c022e23725ef72d5ac6b7784ad9a0662388aaa017', 460.00, 400.00, 0.00, 60.00, 'vij jinu\nDD Golden Gate\nKakkanad West, Ernakulam\nErnakulam, Kerala, 682037\nIndia\nPhone: 7895641589', NULL, 991095374, 987499414, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, '2025-10-12', '2025-10-06 14:37:04', '2025-10-07 06:39:12', '2025-10-07 06:55:04'),
(33, 11, 'ORD-20251016-062323-5af814', 'processing', 'razorpay', 'paid', 'order_RU0kwrGJY53una', 'pay_RU0lAUjEZC8Cj8', '0707bbffde06f121b1351c0d0f4d067a684bdfe9e5a72b113f4b501ffb7714ce', 1660.00, 1600.00, 0.00, 60.00, 'appz sandhosh\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9754684123', NULL, 1003407456, 999805596, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 1.00, 10.00, 10.00, 10.00, NULL, '2025-10-16 04:23:23', NULL, NULL),
(34, 11, 'ORD-20251016-155129-d4139b', 'pending', 'razorpay', 'pending', 'order_RUAR4DtVnee2P3', NULL, NULL, 85.00, 25.00, 0.00, 60.00, 'fathima shibu\nPetta\nFeroke, Kozhikode\nFeroke, Kerala, 673631\nIndia\nPhone: 9188436587', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-16 13:51:29', NULL, NULL),
(35, 11, 'ORD-20251016-160348-245fdc', 'processing', 'razorpay', 'paid', 'order_RUAe3ECQHiDdA8', 'pay_RUAeGuHnOsZwWw', '98dc892b9c0d7fdaee384317fdb0ad7d1b06486e32d552671363533bb2775b8d', 85.00, 25.00, 0.00, 60.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nFeroke, Kozhikode\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495400477', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-16 14:03:48', NULL, NULL),
(36, 1, 'ORD-20251020-184803-fc908b', 'pending', 'razorpay', 'pending', 'order_RVna2AxG1AG8j0', NULL, NULL, 1560.00, 1500.00, 0.00, 60.00, 'Fathima Shibu\npurathel house,anakkal p o,kanjirappally\nlp school\nanakkal, kerala, 686508\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-20 16:48:03', NULL, NULL),
(37, 1, 'ORD-20251020-190124-17ed87', 'pending', 'razorpay', 'pending', 'order_RVno5tfQ8XjSb3', NULL, NULL, 1710.00, 1500.00, 0.00, 60.00, 'Fathima Shibu\npurathel house,anakkal p o,kanjirappally\nlp school\nanakkal, kerala, 686508\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-20 17:01:24', NULL, NULL),
(38, 1, 'ORD-20251020-193022-5034ee', 'pending', NULL, 'pending', NULL, NULL, NULL, 25.00, 25.00, 0.00, 0.00, 'N/A', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-20 17:30:22', NULL, NULL),
(39, 1, 'ORD-20251020-194216-dc9989', 'pending', 'razorpay', 'pending', 'order_RVoVGdrXE67SSB', NULL, NULL, 85.00, 25.00, 0.00, 60.00, 'Fathima Shibu\npurathel house,anakkal p o,kanjirappally\nlp school\nanakkal, kerala, 686508\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-20 17:42:16', NULL, NULL),
(40, 11, 'ORD-20251021-100312-178d4d', 'pending', 'razorpay', 'pending', 'order_RW3AiTmXAdiQER', NULL, NULL, 2059.99, 1999.99, 0.00, 60.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-21 08:03:12', NULL, NULL),
(41, 11, 'ORD-20251021-100335-bcd4b1', 'processing', 'razorpay', 'paid', 'order_RW3B74xn7H4L8W', 'pay_RW3BVK8m50YPvl', 'fab8ab82ec3ad4de3788f56c92d854b32f1609a158f60f66cc471838fa3b3d70', 2059.99, 1999.99, 0.00, 60.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9495470077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-21 08:03:35', NULL, NULL),
(42, 11, 'ORD-20251022-060210-5b7de4', 'pending', 'razorpay', 'pending', 'order_RWNbENQu19Mm2W', NULL, NULL, 449.98, 329.98, 0.00, 120.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 8545746954', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 120.00, 1.50, 10.00, 10.00, 10.00, NULL, '2025-10-22 04:02:10', NULL, NULL),
(43, 11, 'ORD-20251022-060300-b48862', 'processing', 'razorpay', 'paid', 'order_RWNc7ITRd8VmO7', 'pay_RWNcbkpNcSQOhM', '178d7d004669224d136041225ab66dbbf8fc6f02adb3b401de0934cc7577a3bf', 449.98, 329.98, 0.00, 120.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 8545746954', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 120.00, 1.50, 10.00, 10.00, 10.00, NULL, '2025-10-22 04:03:00', NULL, NULL),
(44, 11, 'ORD-20251022-063957-de46ba', 'processing', 'razorpay', 'paid', 'order_RWOF8qoHp7RFBQ', 'pay_RWOFJx97op8xmq', '026de0fa09fe25889d749b5b38fb82e19ad573ef4215b57fa4fe6bea02b1d081', 1060.00, 1000.00, 0.00, 60.00, 'appz sandhosh\nMannarakkayam - Koovappally Road\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 8564789456', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-22 04:39:57', NULL, NULL),
(45, 11, 'ORD-20251022-070022-172ba1', 'processing', 'razorpay', 'paid', 'order_RWOaj8Sg6bfUfz', 'pay_RWOasjIuGqnoKG', 'e5c6e7924415dd571e89b50f9c6eeefc5471fb40503343a60a29964b731ea49a', 560.00, 500.00, 0.00, 60.00, 'fathima shibu\nPanicheppalli - Vizhikkathodu Road\nVizhikkathodu, Kottayam\nVizhikkathodu, Kerala, 686518\nIndia\nPhone: 9587456217', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-22 05:00:22', NULL, NULL),
(46, 18, 'ORD-20251024-082131-498482', 'processing', 'razorpay', 'paid', 'order_RXD2eAAqn5tADf', 'pay_RXD2xGUv8otZxQ', 'efdcd8745cad88ef504df2d3d67a306150a5c30ced2050e4b569c0fb1469fd5d', 85.00, 25.00, 0.00, 60.00, 'shifa fathima\nPattimattam\nKottayam\nanakkal, Kerala, 686518\nIndia\nPhone: 8954746512', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-24 06:21:31', NULL, NULL),
(47, 18, 'ORD-20251024-105749-7f90c0', 'processing', 'razorpay', 'paid', 'order_RXFhlGmT8Z0aKp', 'pay_RXFi1xUlHlmmqI', 'a45ed4c3a91ae2da9c106e0bc69ab35c9423e812d464733fb442aa399801b8e2', 460.00, 400.00, 0.00, 60.00, 'shifa fathima\nAmal Jyothi College of Engineering Skywalk\nKoovapally, Kottayam\nKoovapally, Kerala, 686518\nIndia\nPhone: 9874561412', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-24 08:57:49', NULL, NULL),
(48, 18, 'ORD-20251024-111704-c7059a', 'processing', 'razorpay', 'paid', 'order_RXG24VKeVTjN5K', 'pay_RXG3eG4cF2OIhO', '1a1a07f61b6f45040d4def0f035aa37df194bb671c87c7d96b2305c8c7166a9b', 1060.00, 1000.00, 0.00, 60.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495450077', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-24 09:17:04', NULL, NULL),
(49, 18, 'ORD-20251025-110315-5a6afc', 'pending', 'razorpay', 'pending', 'order_RXeKcpb847Dvbk', NULL, NULL, 160.00, 25.00, 0.00, 60.00, 'Fathima Shibu\nPurathel(H) Anakkal PO kanjirapally kottyaam\nKanjirappalli, Kottayam\nAnakkal ,Kanjirapally, kerala, 686508\nIndia\nPhone: 9495400775', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 60.00, 0.50, 10.00, 10.00, 10.00, NULL, '2025-10-25 09:03:15', NULL, NULL);

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
(1, 37, 'greeting_card', 'Greeting Card', 150.00, '2025-10-20 17:01:24'),
(2, 49, 'ribbon', 'Decorative Ribbon', 75.00, '2025-10-25 09:03:15');

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
(64, 49, 27, 1, 25.00);

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
(1, 8, 'clip', 'poloroid clip', 21, 'pcs', '2025-09-23', 'packed', '2025-09-21 14:48:00', '2025-09-21 14:49:00');

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
(1, 1, 'admin', 'it is very very arjent', '2025-09-21 14:48:34'),
(2, 1, 'admin', 'is it available', '2025-09-21 14:59:55'),
(3, 1, 'supplier', 'yes available', '2025-09-21 15:07:27'),
(4, 1, 'supplier', 'do you needed', '2025-09-21 15:30:01'),
(5, 1, 'supplier', 'do you needed', '2025-09-21 15:30:19'),
(6, 1, 'supplier', 'hloo', '2025-09-21 15:34:33'),
(7, 1, 'admin', 'okk', '2025-09-21 15:35:10');

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
(7, 'kiranshibuthomas2026@mca.ajce.in', '148587', '2025-09-17 07:07:53', '2025-09-17 04:37:53'),
(10, 'fathimashibu15@gmail.com', '679562', '2025-10-15 18:34:14', '2025-10-15 16:04:14');

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
(1, 5, 'PO-20250921-121203-0a8b9f', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKDLGe1WmPiJbq', NULL, NULL, 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 10:12:03', '2025-09-21 10:12:04'),
(2, 5, 'PO-20250921-121203-f32b3a', 9000.00, 'INR', 'razorpay', 'paid', 'processing', 'order_RKDLH0kzQl1oa6', 'pay_RKDML96tI9uhLN', '0d761420f5ff9466460812e7e7ab468a4a4aa09460fb8975a028e1c5055a39bb', 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 10:12:03', '2025-09-21 10:13:17'),
(3, 5, 'PO-20250921-121853-69c3c5', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKDSTSzGtqr0zW', NULL, NULL, 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 10:18:53', '2025-09-21 10:18:53'),
(4, 5, 'PO-20250921-122722-b4e491', 918000.00, 'INR', 'razorpay', 'pending', 'pending', NULL, NULL, NULL, 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 10:27:22', '2025-09-21 10:27:22'),
(5, 5, 'PO-20250921-123722-52d34e', 36000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKDlzfW251YpPE', NULL, NULL, 'My Little Thingz Warehouse, 123 Main Street, City, State 000000, India', '2025-09-21 10:37:22', '2025-09-21 10:37:22'),
(6, 5, 'PO-20250921-135515-765beb', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKF6IDs3n0apm0', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 11:55:15', '2025-09-21 11:55:16'),
(7, 5, 'PO-20250921-140106-06d6a4', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKFCSYhociG9TM', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 12:01:06', '2025-09-21 12:01:07'),
(8, 5, 'PO-20250921-140137-dd87dd', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKFCzZjSFwh3Mr', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 12:01:37', '2025-09-21 12:01:37'),
(9, 5, 'PO-20250921-140951-9f5fc7', 0.00, 'INR', 'razorpay', 'pending', 'pending', NULL, NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 12:09:51', '2025-09-21 12:09:51'),
(10, 5, 'PO-20250921-141020-8a34dc', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKFMCkNfvT195s', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 12:10:20', '2025-09-21 12:10:20'),
(11, 5, 'PO-20250921-141045-68613f', 9000.00, 'INR', 'razorpay', 'paid', 'processing', 'order_RKFMennITtuAMq', 'pay_RKFMlqIOqCwrOq', '54040c0b6500e5650ddbdd592ef9c12b6c80d6090b853b9172d2f5dfc6c9f080', 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 12:10:45', '2025-09-21 12:11:05'),
(12, 5, 'PO-20250921-145848-888f99', 9000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RKGBPrdIgQ06DH', NULL, NULL, 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 12:58:48', '2025-09-21 12:58:49'),
(13, 5, 'PO-20250921-150129-99449a', 9000.00, 'INR', 'razorpay', 'paid', 'processing', 'order_RKGEEcXnzRpJVm', 'pay_RKGERtnyLKaBrM', '769a597d0e97cfd5b4662d8513a22b2913ff793742f83679f43defc9802f0ad1', 'Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077', '2025-09-21 13:01:29', '2025-09-21 13:01:55'),
(14, 5, 'PO-20250923-170930-3ea50c', 600.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RL5Tdpv5FMekIP', NULL, NULL, 'Purathel\nAnakkal PO\nKanjirapally, Kerala, 686508\nIndia\nPhone: 9495470077', '2025-09-23 15:09:30', '2025-09-23 15:09:30'),
(15, 5, 'PO-20251006-164057-5eb1ea', 1000.00, 'INR', 'razorpay', 'pending', 'pending', 'order_RQDw376Z48bOJn', NULL, NULL, 'Purathel\nAnakkal PO\nKanjirapally, Kerala, 686508\nIndia\nPhone: 9495470077', '2025-10-06 14:40:57', '2025-10-06 14:40:57');

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
(1, 1, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 10:12:03', NULL),
(2, 2, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 10:12:03', NULL),
(3, 3, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 10:18:53', NULL),
(4, 4, 3, NULL, 'gift box', 9000.00, 102, 8, '2025-09-21 10:27:22', '[{\"color\":\"blue\",\"qty\":12},{\"color\":\"green\",\"qty\":90}]'),
(5, 5, 3, NULL, 'gift box', 9000.00, 4, 8, '2025-09-21 10:37:22', '[{\"color\":\"green\",\"qty\":4}]'),
(6, 6, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 11:55:15', '[{\"color\":\"red\",\"qty\":1}]'),
(7, 7, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 12:01:06', '[{\"color\":\"red\",\"qty\":1}]'),
(8, 8, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 12:01:37', '[{\"color\":\"red\",\"qty\":1}]'),
(9, 8, NULL, 1, 'flower', 0.00, 1, 8, '2025-09-21 12:01:37', '[{\"color\":\"red\",\"qty\":1}]'),
(10, 9, NULL, 1, 'flower', 0.00, 1, 8, '2025-09-21 12:09:51', '[{\"color\":\"red\",\"qty\":1}]'),
(11, 10, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 12:10:20', '[{\"color\":\"red\",\"qty\":1}]'),
(12, 11, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 12:10:45', '[{\"color\":\"red\",\"qty\":1}]'),
(13, 11, NULL, 1, 'flower', 0.00, 1, 8, '2025-09-21 12:10:45', '[{\"color\":\"red\",\"qty\":1}]'),
(14, 12, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 12:58:48', '[{\"color\":\"grren\",\"qty\":1}]'),
(15, 13, 3, NULL, 'gift box', 9000.00, 1, 8, '2025-09-21 13:01:29', '[{\"color\":\"grren\",\"qty\":1}]'),
(16, 14, 7, NULL, 'nuts box', 600.00, 1, 8, '2025-09-23 15:09:30', '[{\"color\":\"red\",\"qty\":1}]'),
(17, 15, 6, NULL, 'wedding hamper', 1000.00, 1, 8, '2025-10-06 14:40:57', '[{\"color\":\"pink\",\"qty\":1}]');

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
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `artwork_id`, `rating`, `comment`, `status`, `admin_reply`, `created_at`, `updated_at`) VALUES
(1, 11, 25, 5, 'good', 'approved', 'thankyou', '2025-10-22 17:09:19', '2025-10-22 17:20:32'),
(2, 11, 27, 4, 'nice', 'approved', 'thankyou', '2025-10-22 17:15:50', '2025-10-22 17:20:53'),
(4, 11, 16, 5, 'ecxcellent work', 'pending', NULL, '2025-10-24 14:01:06', '2025-10-24 14:01:06');

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
(2, 7, 'gift box', 'trending one', 'Gift box', NULL, 1000.00, 0, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/7/sp_20250910_124402_b8014cb9564e.jpg', 1, 'pending', '2025-09-10 10:44:02', '2025-09-10 10:44:02'),
(3, 8, 'gift box', 'trending gift box', 'Gift box', NULL, 9000.00, 20, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250914_123124_547bee2dfc4b.jpg', 1, 'pending', '2025-09-14 10:31:24', '2025-09-14 10:31:24'),
(4, 8, 'gift box', 'glass typ material', 'Gift box', NULL, 1000.00, 10, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_150821_861843afed99.jpg', 1, 'pending', '2025-09-21 13:08:21', '2025-09-21 13:08:21'),
(5, 8, 'round box', 'red theme', 'Gift box', NULL, 700.00, 10, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_151034_6c8cc5f7a2f9.jpg', 1, 'pending', '2025-09-21 13:10:34', '2025-09-21 13:10:34'),
(6, 8, 'wedding hamper', 'weeding hamper', 'Gift box', NULL, 1000.00, 5, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_151306_072915aa4609.jpg', 1, 'pending', '2025-09-21 13:13:06', '2025-09-21 13:13:06'),
(7, 8, 'nuts box', 'nuts box', 'Gift box', NULL, 600.00, 5, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_151454_b3f32bf0d7d0.jpg', 1, 'pending', '2025-09-21 13:14:54', '2025-09-21 13:14:54'),
(8, 8, 'ring holder', 'ring', 'Gift box', NULL, 600.00, 10, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250921_160839_13a80d5e0b13.jpg', 1, 'pending', '2025-09-21 14:08:39', '2025-09-21 14:08:39');

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
(7, '', 'approved', '2025-09-10 08:48:27', '2025-09-10 08:49:18'),
(8, '', 'approved', '2025-09-13 06:08:52', '2025-09-13 06:09:33'),
(16, 'jbjjbbjbb', 'approved', '2025-10-15 09:16:25', '2025-10-15 09:18:34'),
(17, 'kripa shop', 'pending', '2025-10-21 10:45:37', '2025-10-21 10:45:37');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'shijin', 'thomas', 'shijinthomas369@gmail.com', '$2y$10$i7sKI1aenMGqrzx7SWQU0eSx1XScN2YAX22TQsJFdjImNt8yIqT5e', '2025-08-15 15:50:09', '2025-08-15 15:50:09'),
(2, 'shijin', 'thomas', 'shijinthomas248@gmail.com', '$2y$10$86CWTt86VyN9kgHzHK9JaujQhfFCdE40uykQylM4UDWw2vlH2LvCq', '2025-08-15 15:59:21', '2025-08-15 15:59:21'),
(5, 'Admin', 'User', 'fathima470077@gmail.com', '$2y$10$9RO.o7wA3NcbRmB74Nt9qeH6h84xT3c05pbXqjtIPfLD383m4aGfi', '2025-08-16 04:28:13', '2025-08-16 04:28:13'),
(8, 'fathima', 'shibu', 'fathimashibu0805@gmail.com', NULL, '2025-09-13 06:08:52', '2025-09-13 06:08:52'),
(9, 'VIJETHA', 'JINU', 'vijethajinu@gmail.com', NULL, '2025-09-15 08:22:24', '2025-09-15 08:22:24'),
(10, 'kiran', 'shibu', 'kiranshibuthomas2026@mca.ajce.in', '$2y$10$WBFKmNJe0lnpMiKcrBmm3Oe4YJPNhlJKXoqpzMVYqIF9fLXJV2rpS', '2025-09-17 04:09:49', '2025-09-17 04:25:37'),
(11, 'Fathima', '', 'fathimashibu15@gmail.com', NULL, '2025-09-17 06:22:37', '2025-09-17 06:22:37'),
(12, 'FATHIMA SHIBU', 'MCA2024-2026', 'fathimashibu2026@mca.ajce.in', NULL, '2025-09-22 08:50:48', '2025-09-22 08:50:48'),
(13, 'Sera', 'Mol', 'seramol1508@gmail.com', '$2y$10$mxHGIaajQzST9Q6GwaODAemGW.ddnJWtPR7qiWGwVpm.UR0jgFUY2', '2025-09-22 14:18:56', '2025-10-15 16:00:51'),
(14, 'Fathima', 'Shibu', 'fathima686231@gmail.com', NULL, '2025-09-22 14:24:05', '2025-09-22 14:24:05'),
(15, 'Fathima', 'Shibu', 'nobinrajeev2026@mca.ajce.in', '$2y$10$f5vO0p2gJXAz7VVp7BV7xOH84hw2vubqc3Gv87nm9CvHTnsyUSxeO', '2025-10-15 09:13:51', '2025-10-15 09:13:51'),
(16, 'Fathima', 'Shibu', 'thomasshijin@gmail.com', '$2y$10$9Y3L8Doxc8LZO5S0ZeyBTuCikojug08jM7kOYPxf9MiK8ggygqI6y', '2025-10-15 09:16:25', '2025-10-15 09:16:25'),
(17, 'Fiya', 'Fathim', 'fiyafathim19@gmail.com', NULL, '2025-10-21 10:45:37', '2025-10-21 10:45:37'),
(18, 'Shifa', 'Fathima', 'shifafathima0815@gmail.com', NULL, '2025-10-24 06:19:25', '2025-10-24 06:19:25');

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
(18, 2);

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
(15, 1, 2, '2025-09-21 13:17:35'),
(20, 1, 12, '2025-09-29 09:52:28'),
(26, 1, 23, '2025-10-20 17:22:47'),
(27, 1, 22, '2025-10-20 17:56:57'),
(28, 11, 33, '2025-10-21 05:31:59'),
(29, 18, 34, '2025-10-24 09:19:55');

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_request_images`
--
ALTER TABLE `custom_request_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_invoice_number` (`invoice_number`),
  ADD UNIQUE KEY `uniq_invoice_order` (`order_id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4697;

--
-- AUTO_INCREMENT for table `courier_serviceability_cache`
--
ALTER TABLE `courier_serviceability_cache`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_requests`
--
ALTER TABLE `custom_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `custom_request_images`
--
ALTER TABLE `custom_request_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `offers_promos`
--
ALTER TABLE `offers_promos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `order_addons`
--
ALTER TABLE `order_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `supplier_products`
--
ALTER TABLE `supplier_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_behavior_log`
--
ALTER TABLE `user_behavior_log`
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
