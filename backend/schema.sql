-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 01:37 PM
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
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image_url` varchar(255) NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `artist_id` int(10) UNSIGNED DEFAULT NULL,
  `availability` enum('in_stock','out_of_stock','made_to_order') NOT NULL DEFAULT 'in_stock',
  `requires_customization` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `artworks`
--

INSERT INTO `artworks` (`id`, `title`, `description`, `price`, `image_url`, `category_id`, `artist_id`, `availability`, `requires_customization`, `status`, `created_at`, `updated_at`) VALUES
(2, 'wedding card', 'per card 50 rupees', 50.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/wedding_card_20250909_162709_3882f9.png', 6, 5, 'in_stock', 1, 'active', '2025-09-09 14:27:09', '2025-09-12 05:19:12'),
(3, 'poloroids', '20 photos 150 ruppes', 150.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/poloroid_20250909_162827_bad52f.png', 4, 5, 'in_stock', 1, 'active', '2025-09-09 14:28:27', '2025-09-12 05:19:12'),
(4, '4 * 4 frame', 'mini frame', 120.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/4_4_frame_20250909_163041_e6da75.png', 3, 5, 'in_stock', 0, 'active', '2025-09-09 14:30:41', '2025-09-09 14:30:41'),
(5, '6 * 4', 'best friends frames', 250.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/6_4_frame_20250909_163511_f22d0e.png', 3, 5, 'in_stock', 0, 'active', '2025-09-09 14:35:11', '2025-09-09 14:35:11'),
(6, 'A3 frame', 'cartoon frame', 749.99, 'http://localhost/my_little_thingz/backend/uploads/artworks/A3_frame_20250909_163546_05748c.png', 3, 5, 'in_stock', 0, 'active', '2025-09-09 14:35:46', '2025-09-09 14:35:46'),
(7, 'album', 'carboard sheet album', 200.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/album_20250909_163744_d02862.png', 8, 5, 'in_stock', 0, 'active', '2025-09-09 14:37:44', '2025-09-09 14:37:44'),
(8, 'boaqutes', 'red rose boaqutes', 400.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/boaqutes_20250909_163941_3994ac.png', 2, 5, 'in_stock', 0, 'active', '2025-09-09 14:39:41', '2025-09-09 14:39:41'),
(9, 'chocolates', 'per chocolates 30', 30.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/custom_chocolate_20250909_164039_e9be6c.png', 5, 5, 'in_stock', 0, 'active', '2025-09-09 14:40:39', '2025-09-09 14:40:39'),
(10, 'Custom Drawing', 'Sketches', 1000.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/drawings_20250909_164237_1da7b5.png', 7, 5, 'in_stock', 0, 'active', '2025-09-09 14:42:37', '2025-09-09 14:42:37'),
(11, 'gift set', 'it consist of gift box boaqutes frames', 3000.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/gift_box_set_20250909_164422_32ae0c.png', 1, 5, 'in_stock', 0, 'active', '2025-09-09 14:44:22', '2025-09-09 14:44:22'),
(12, 'gift box', 'giftbox consist of chocolates and watch', 2000.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/gift_box_20250909_164839_021d32.png', 1, 5, 'in_stock', 0, 'active', '2025-09-09 14:48:39', '2025-09-09 14:48:39'),
(13, 'Micro frame', 'micro frame', 90.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/micro_frame_20250909_164926_56f796.png', 3, 5, 'in_stock', 0, 'active', '2025-09-09 14:49:26', '2025-09-09 14:49:26'),
(14, 'mini frame', 'mini frame', 149.99, 'http://localhost/my_little_thingz/backend/uploads/artworks/mini_frame_20250909_165028_7eaa02.png', 3, 5, 'in_stock', 0, 'active', '2025-09-09 14:50:28', '2025-09-09 14:50:28'),
(15, 'album', '20  photos 150', 150.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/poloroid__2__20250909_165107_4ac16f.png', 3, 5, 'in_stock', 0, 'active', '2025-09-09 14:51:07', '2025-09-09 14:51:07'),
(16, 'Hamper', 'wedding hamper', 2000.00, 'http://localhost/my_little_thingz/backend/uploads/artworks/Wedding_hamper_20250909_165223_b117bb.jpg', 1, 5, 'in_stock', 0, 'active', '2025-09-09 14:52:23', '2025-09-09 14:52:23');

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
(7, 8, 'google', '106031145699305807929', '2025-09-13 06:08:52');

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
(17, 1, 8, 1, '2025-09-13 17:51:41'),
(18, 1, 12, 1, '2025-09-14 11:22:19');

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
  `source` enum('form','cart') NOT NULL DEFAULT 'form',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_requests`
--

INSERT INTO `custom_requests` (`id`, `user_id`, `title`, `description`, `category_id`, `occasion`, `budget_min`, `budget_max`, `deadline`, `special_instructions`, `source`, `status`, `created_at`) VALUES
(1, 1, 'gift box for birthday', 'gift box consist of chocolates', NULL, 'Birthday', 500.00, NULL, '2025-09-15', '', 'form', 'completed', '2025-09-11 08:16:30'),
(2, 1, 'bjksjkds', 'kd ndjk', NULL, 'wedding', 39.98, NULL, '2025-09-26', '', 'form', 'completed', '2025-09-12 06:09:13'),
(3, 1, 'Cart customization - wedding - 2025-09-19', 'red flower ', NULL, 'wedding', NULL, NULL, '2025-09-19', '', 'cart', 'completed', '2025-09-12 08:15:05'),
(4, 1, 'Cart customization - christmas - 2025-09-30', 'vft', NULL, 'christmas', NULL, NULL, '2025-09-30', '', 'cart', 'completed', '2025-09-12 08:17:26'),
(5, 1, 'Cart customization - wedding - 2025-09-18', 'purple theme', NULL, 'wedding', NULL, NULL, '2025-09-18', '', 'cart', 'completed', '2025-09-12 08:23:37'),
(6, 1, 'Cart customization - baby_shower - 2025-09-30', 'bh', NULL, 'baby_shower', NULL, NULL, '2025-09-30', '', 'cart', 'completed', '2025-09-12 08:27:06'),
(7, 1, 'Cart customization - baby_shower - 2025-09-30', 'jhhh', NULL, 'baby_shower', NULL, NULL, '2025-09-30', '', 'cart', 'completed', '2025-09-12 08:30:34'),
(8, 1, 'Cart customization - valentine - 2025-09-30', 'kbhxbh', NULL, 'valentine', NULL, NULL, '2025-09-30', '', 'cart', 'completed', '2025-09-12 08:33:21'),
(9, 1, 'Cart customization - birthday - 2025-09-14', 'poloroids with 10 photos', NULL, 'birthday', NULL, NULL, '2025-09-14', '', 'cart', 'completed', '2025-09-12 10:12:12'),
(10, 1, 'Cart customization - birthday - 2025-09-15', 'frame', NULL, 'birthday', NULL, NULL, '2025-09-15', '', 'cart', 'completed', '2025-09-12 10:16:53'),
(11, 1, 'anniversary gift', 'gift box consist of chocolates', NULL, 'Anniversary', 2000.00, NULL, '2025-09-15', '', 'form', 'completed', '2025-09-12 10:35:32'),
(12, 1, 'Cart customization - wedding - 2025-09-17', 'wedding card', NULL, 'wedding', NULL, NULL, '2025-09-17', '', 'cart', 'completed', '2025-09-13 04:29:21'),
(13, 1, 'Cart customization - birthday - 2025-09-20', 'gift set', NULL, 'birthday', NULL, NULL, '2025-09-20', '', 'cart', 'completed', '2025-09-13 05:50:19'),
(14, 1, 'Cart customization - birthday - 2025-09-18', 'red rosses', NULL, 'birthday', NULL, NULL, '2025-09-18', '', 'cart', 'completed', '2025-09-13 17:51:41');

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
(18, 14, 'uploads/custom-requests/admin_68c5af609b2450.52810488_Screenshot_2025-08-13_200022.png', '2025-09-13 17:52:32');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `supplier_id`, `name`, `category`, `type`, `size`, `color`, `grade`, `brand`, `tags`, `location`, `availability`, `image_url`, `attributes_json`, `sku`, `quantity`, `unit`, `updated_at`, `created_at`) VALUES
(1, 8, 'flower', 'Flowers', '', '', '', '', '', '', '', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250914_113430_9c357c15a96b.jpg', NULL, 'yellow flowers for boqutes', 50, 'pcs', '2025-09-14 09:34:32', '2025-09-14 09:34:32');

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
  `estimated_delivery` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `status`, `payment_method`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `total_amount`, `subtotal`, `tax_amount`, `shipping_cost`, `shipping_address`, `tracking_number`, `estimated_delivery`, `created_at`, `shipped_at`, `delivered_at`) VALUES
(2, 1, 'ORD-20250912-064702-f270cb', 'processing', 'razorpay', 'paid', 'order_RGYzo1NeNPAuPc', 'pay_RGZ4lD3DDSOyGz', 'ea2a3513a18b17f2c8a1fa49876ba53062ff73ff0a70c64c231acf292c98284d', 2250.00, 2250.00, 0.00, 0.00, NULL, NULL, NULL, '2025-09-12 04:47:02', NULL, NULL),
(3, 1, 'ORD-20250912-065609-408699', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, '2025-09-12 04:56:09', NULL, NULL),
(4, 1, 'ORD-20250912-070324-5de70f', 'pending', NULL, 'pending', NULL, NULL, NULL, 120.00, 120.00, 0.00, 0.00, 'N/A', NULL, NULL, '2025-09-12 05:03:24', NULL, NULL),
(5, 1, 'ORD-20250912-070525-6af48e', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, '2025-09-12 05:05:25', NULL, NULL),
(6, 1, 'ORD-20250912-070628-400786', 'pending', NULL, 'pending', NULL, NULL, NULL, 120.00, 120.00, 0.00, 0.00, 'N/A', NULL, NULL, '2025-09-12 05:06:28', NULL, NULL),
(7, 1, 'ORD-20250912-072223-f6aba9', 'pending', NULL, 'pending', NULL, NULL, NULL, 50.00, 50.00, 0.00, 0.00, 'N/A', NULL, NULL, '2025-09-12 05:22:23', NULL, NULL),
(8, 1, 'ORD-20250912-081443-57dddd', 'processing', 'razorpay', 'paid', 'order_RGaUSGjDC0uHXD', 'pay_RGaUcMT48bnDTA', '40d3afc1d58693301eff69caa62c0f0fb2c187527d53bc2af48804dc1b0544b8', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, '2025-09-12 06:14:43', NULL, NULL),
(9, 1, 'ORD-20250912-081739-c13563', 'processing', 'razorpay', 'paid', 'order_RGaXXYEki73TL2', 'pay_RGaXbwfGwu6ehQ', '112f30fd1accc6144acd19b8e29025d0e119ab56027352c0fbc93502120ea062', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, '2025-09-12 06:17:39', NULL, NULL),
(10, 1, 'ORD-20250912-103402-777b98', 'processing', 'razorpay', 'paid', 'order_RGcrctuI0BK3hh', 'pay_RGcrtSGu7IqiYy', 'f23104b8da2aa1cef77e7a1e2c809a0925223c37d00f8e66214a875fa34a7b1c', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, '2025-09-12 08:34:02', NULL, NULL),
(11, 1, 'ORD-20250912-121347-6f44d8', 'processing', 'razorpay', 'paid', 'order_RGeYzyLPujcMWV', 'pay_RGeZBvdQrdCUcg', 'f99f22a27c0e97e43c59882968cb4eb73c6689bc592b4d0c0d3789d636705056', 150.00, 150.00, 0.00, 0.00, NULL, NULL, NULL, '2025-09-12 10:13:47', NULL, NULL),
(12, 1, 'ORD-20250912-121736-dc99c9', 'processing', 'razorpay', 'paid', 'order_RGed1GBd4rgAu6', 'pay_RGedqgO02jPPMx', 'f50daa592861af7d2c6cbbfd562c98a78e647c76889978e80d95e76eb6aa90f5', 250.00, 250.00, 0.00, 0.00, NULL, NULL, NULL, '2025-09-12 10:17:36', NULL, NULL),
(13, 1, 'ORD-20250912-122449-a40eff', 'pending', 'razorpay', 'pending', 'order_RGekdJLtU2RAX0', NULL, NULL, 2000.00, 2000.00, 0.00, 0.00, NULL, NULL, NULL, '2025-09-12 10:24:49', NULL, NULL),
(14, 1, 'ORD-20250913-061526-12456c', 'processing', 'razorpay', 'paid', 'order_RGwzav8Gn2aqaZ', 'pay_RGwznsPSJHnM9y', '2d16e571c7d5fceb0b5ccfa9dcdae196d3423f6bbd42468b92a189b16c0909c9', 2000.00, 2000.00, 0.00, 0.00, NULL, NULL, NULL, '2025-09-13 04:15:26', NULL, NULL),
(15, 1, 'ORD-20250913-071058-60cbd7', 'processing', 'razorpay', 'paid', 'order_RGxwG25tCUcwDh', 'pay_RGxwwO79h0QoTa', 'ba00aeaeb86242faa5940bc46e77cb8aa4c91efa6f97a79fb5a3fac56178ebbd', 50.00, 50.00, 0.00, 0.00, NULL, NULL, NULL, '2025-09-13 05:10:58', NULL, NULL),
(16, 1, 'ORD-20250913-075311-390498', 'processing', 'razorpay', 'paid', 'order_RGyeqcfJ05WOD2', 'pay_RGyf5n0arwrKc0', '85af2064c2880c081dc4fa93fb9a99ff888a1603dba24a7cb0625daf219514a8', 3000.00, 3000.00, 0.00, 0.00, 'purathel,anakkal kanjirapally 686598,8765457889', NULL, NULL, '2025-09-13 05:53:11', NULL, NULL);

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
(22, 16, 11, 1, 3000.00);

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
(3, 8, 'gift box', 'trending gift box', 'Gift box', NULL, 9000.00, 20, 'pcs', 'available', 'http://localhost/my_little_thingz/backend/uploads/supplier-products/8/sp_20250914_123124_547bee2dfc4b.jpg', 1, 'pending', '2025-09-14 10:31:24', '2025-09-14 10:31:24');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_profiles`
--

CREATE TABLE `supplier_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_profiles`
--

INSERT INTO `supplier_profiles` (`user_id`, `status`, `created_at`, `updated_at`) VALUES
(7, 'approved', '2025-09-10 08:48:27', '2025-09-10 08:49:18'),
(8, 'approved', '2025-09-13 06:08:52', '2025-09-13 06:09:33');

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
(8, 'fathima', 'shibu', 'fathimashibu0805@gmail.com', NULL, '2025-09-13 06:08:52', '2025-09-13 06:08:52');

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
(8, 3);

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
-- Indexes for dumped tables
--

--
-- Indexes for table `artworks`
--
ALTER TABLE `artworks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_artworks_category` (`category_id`),
  ADD KEY `idx_artworks_status` (`status`);

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
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`);

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
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `auth_providers`
--
ALTER TABLE `auth_providers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1105;

--
-- AUTO_INCREMENT for table `custom_requests`
--
ALTER TABLE `custom_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `custom_request_images`
--
ALTER TABLE `custom_request_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `order_payments`
--
ALTER TABLE `order_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_requirements`
--
ALTER TABLE `order_requirements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_requirement_messages`
--
ALTER TABLE `order_requirement_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `supplier_products`
--
ALTER TABLE `supplier_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `fk_materials_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
