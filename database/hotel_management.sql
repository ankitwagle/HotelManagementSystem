-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 11, 2026 at 04:42 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int NOT NULL DEFAULT '1',
  `special_requests` text COLLATE utf8mb4_unicode_ci,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'Payment Successful', 'Your payment of $750.00 for reservation #6 was completed successfully. Transaction reference: LUXE-20260826154521-78204F.', 1, '2026-08-26 15:45:21'),
(2, 2, 'Reservation Approved', 'Your reservation #7 has been approved successfully. Your stay is confirmed from 2026-08-29 to 2026-09-01.', 1, '2026-08-26 15:52:20'),
(3, 1, 'New Reservation', 'test has submitted a new reservation request. Reservation #9 requires your review.', 1, '2026-08-26 16:07:13'),
(4, 1, 'New Reservation', 'test has submitted a new reservation request. Reservation #10 requires your review.', 1, '2026-09-05 15:58:14'),
(5, 2, 'Payment Successful', 'Your payment of $740.00 for reservation #10 was completed successfully. Transaction reference: LUXE-20260905160023-362D0E.', 1, '2026-09-05 16:00:23'),
(6, 1, 'New Reservation', 'test has submitted a new reservation request. Reservation #11 requires your review.', 1, '2026-09-05 16:01:47'),
(7, 2, 'Payment Successful', 'Your payment of $450.00 for reservation #11 was completed successfully. Transaction reference: LUXE-20260905160323-CD6EA9.', 0, '2026-09-05 16:03:23'),
(8, 1, 'New Reservation', 'test has submitted a new reservation request. Reservation #12 requires your review.', 0, '2026-09-07 06:13:58'),
(9, 1, 'New Reservation', 'test has submitted a new reservation request. Reservation #13 requires your review.', 0, '2026-09-08 13:59:28'),
(10, 1, 'New Reservation', 'ankit wagle has submitted a new reservation request. Reservation #14 requires your review.', 0, '2026-09-08 14:15:45'),
(11, 6, 'Payment Successful', 'Your payment of $300.00 for reservation #14 was completed successfully. Transaction reference: LUXE-20260908141645-B87F17.', 0, '2026-09-08 14:16:45'),
(12, 1, 'New Reservation', 'Abhinav Shrestha has submitted a new reservation request. Reservation #1 requires your review.', 0, '2026-09-09 02:29:43'),
(13, 1, 'New Reservation', 'Abhinav Shrestha has submitted a new reservation request. Reservation #2 requires your review.', 0, '2026-09-09 02:36:51'),
(14, 7, 'Payment Successful', 'Your payment of $150.00 for reservation #2 was completed successfully. Transaction reference: LUXE-20260909023803-7A4B53.', 0, '2026-09-09 02:38:03'),
(15, 1, 'New Reservation', 'Aekai offcial has submitted a new reservation request. Reservation #3 requires your review.', 0, '2026-09-09 03:17:11'),
(16, 1, 'New Reservation', 'Abhinav Shrestha has submitted a new reservation request. Reservation #4 requires your review.', 0, '2026-09-09 03:18:11'),
(17, 1, 'New Reservation', 'sarthak has submitted a new reservation request. Reservation #5 requires your review.', 0, '2026-09-09 03:24:40'),
(18, 1, 'New Reservation', 'shramika has submitted a new reservation request. Reservation #6 requires your review.', 0, '2026-09-09 03:35:41'),
(19, 10, 'Payment Successful', 'Your payment of $450.00 for reservation #6 was completed successfully. Transaction reference: LUXE-20260909033733-46DB33.', 0, '2026-09-09 03:37:33'),
(20, 1, 'New Reservation', 'Abhinav sherstha has submitted a new reservation request. Reservation #7 requires your review.', 0, '2026-09-10 05:48:29'),
(21, 11, 'Payment Successful', 'Your payment of $185.00 for reservation #7 was completed successfully. Transaction reference: LUXE-20260910054941-A80F91.', 0, '2026-09-10 05:49:41'),
(22, 1, 'New Reservation', 'Abhinav sherstha has submitted a new reservation request. Reservation #8 requires your review.', 0, '2026-09-10 05:53:48'),
(23, 11, 'Payment Successful', 'Your payment of $850.00 for reservation #8 was completed successfully. Transaction reference: LUXE-20260910055620-A79079.', 0, '2026-09-10 05:56:20'),
(24, 1, 'New Reservation', 'Abinav shrestgha has submitted a new reservation request. Reservation #9 requires your review.', 0, '2026-09-10 05:59:24'),
(25, 12, 'Payment Successful', 'Your payment of $1,995.00 for reservation #9 was completed successfully. Transaction reference: LUXE-20260910062727-F7585A.', 0, '2026-09-10 06:27:27'),
(26, 1, 'New Reservation', 'ankit has submitted a new reservation request. Reservation #10 requires your review.', 0, '2026-09-10 06:55:37'),
(27, 13, 'Payment Successful', 'Your payment of $150.00 for reservation #10 was completed successfully. Transaction reference: LUXE-20260910065613-3790B0.', 0, '2026-09-10 06:56:13'),
(28, 1, 'New Reservation', 'ankit wagle has submitted a new reservation request. Reservation #11 requires your review.', 0, '2026-09-10 16:11:52'),
(29, 6, 'Payment Successful', 'Your payment of $300.00 for reservation #11 was completed successfully. Transaction reference: LUXE-20260910161244-3A3865.', 0, '2026-09-10 16:12:44'),
(30, 1, 'New Reservation', 'Abhinav Shrestha has submitted a new reservation request. Reservation #12 requires your review.', 0, '2026-09-11 02:42:09'),
(31, 7, 'Payment Successful', 'Your payment of $1,110.00 for reservation #12 was completed successfully. Transaction reference: LUXE-20260911024420-9E67F3.', 0, '2026-09-11 02:44:20'),
(32, 7, 'Payment Successful', 'Your payment of $300.00 for reservation #4 was completed successfully. Transaction reference: LUXE-20260911032204-DD8A59.', 0, '2026-09-11 03:22:04'),
(33, 1, 'New Reservation', 'ankit wagle has submitted a new reservation request. Reservation #13 requires your review.', 0, '2026-09-11 16:00:39');

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

DROP TABLE IF EXISTS `otps`;
CREATE TABLE IF NOT EXISTS `otps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reservation_id` int NOT NULL,
  `otp_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `attempts` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `reservation_id` (`reservation_id`)
) ENGINE=MyISAM AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otps`
--

INSERT INTO `otps` (`id`, `user_id`, `reservation_id`, `otp_code`, `expires_at`, `is_used`, `attempts`, `created_at`) VALUES
(2, 2, 13, '936600', '2026-09-08 19:59:14', 0, 0, '2026-09-08 14:04:14'),
(3, 6, 14, '884716', '2026-09-08 20:11:18', 1, 0, '2026-09-08 14:16:18'),
(4, 7, 2, '337592', '2026-09-09 08:32:23', 1, 0, '2026-09-09 02:37:23'),
(6, 8, 3, '820378', '2026-09-09 09:12:39', 0, 0, '2026-09-09 03:17:39'),
(8, 7, 4, '193368', '2026-09-09 09:15:08', 1, 0, '2026-09-09 03:20:08'),
(9, 9, 5, '747699', '2026-09-09 09:19:51', 1, 0, '2026-09-09 03:24:51'),
(11, 10, 6, '780211', '2026-09-09 09:32:02', 1, 0, '2026-09-09 03:37:02'),
(12, 11, 7, '110482', '2026-09-10 11:44:25', 1, 0, '2026-09-10 05:49:25'),
(13, 11, 7, '730283', '2026-09-10 11:46:24', 1, 0, '2026-09-10 05:51:24'),
(14, 11, 8, '275311', '2026-09-10 11:50:56', 1, 0, '2026-09-10 05:55:56'),
(30, 12, 9, '916812', '2026-09-10 12:12:38', 1, 0, '2026-09-10 06:27:08'),
(31, 13, 10, '283704', '2026-09-10 12:41:26', 1, 0, '2026-09-10 06:55:56'),
(32, 6, 11, '$2y$10$204fgf5noXzUzWyjQql9JuiWrsEFQir1ikEssfFVj.oxocVJXScL6', '2026-09-10 21:57:52', 1, 0, '2026-09-10 16:12:22'),
(35, 7, 12, '$2y$10$1VaTGHsl1QLvS3yD2yUK0e7y4xL5gbS3eoeDcQzbWlkZCDoEMQmBq', '2026-09-11 08:29:29', 1, 0, '2026-09-11 02:43:59'),
(37, 7, 4, '$2y$10$QDYnAoXQkgaH7MFgomICo.kYF4jimdTHgS41cH4WbQh97D1RQduVq', '2026-09-11 09:07:14', 1, 1, '2026-09-11 03:21:44'),
(39, 6, 13, '$2y$10$v.l5AT5CS92MUb69iaeTFuKGeCLJrlC4yjwfE2oVEKJiCbybjmQGe', '2026-09-11 21:47:10', 1, 0, '2026-09-11 16:01:40');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `payment_method`, `transaction_reference`, `status`, `paid_at`, `created_at`) VALUES
(10, 6, 450.00, 'Direct Payment', 'LUXE-20260909033733-46DB33', 'paid', '2026-09-09 03:37:33', '2026-09-09 03:37:33'),
(11, 7, 185.00, 'Direct Payment', 'LUXE-20260910054941-A80F91', 'paid', '2026-09-10 05:49:41', '2026-09-10 05:49:41'),
(12, 8, 850.00, 'Direct Payment', 'LUXE-20260910055620-A79079', 'paid', '2026-09-10 05:56:20', '2026-09-10 05:56:20'),
(13, 9, 1995.00, 'Direct Payment', 'LUXE-20260910062727-F7585A', 'paid', '2026-09-10 06:27:27', '2026-09-10 06:27:27'),
(14, 10, 150.00, 'Direct Payment', 'LUXE-20260910065613-3790B0', 'paid', '2026-09-10 06:56:13', '2026-09-10 06:56:13'),
(15, 11, 300.00, 'Direct Payment', 'LUXE-20260910161244-3A3865', 'paid', '2026-09-10 16:12:44', '2026-09-10 16:12:44'),
(16, 12, 1110.00, 'Direct Payment', 'LUXE-20260911024420-9E67F3', 'paid', '2026-09-11 02:44:20', '2026-09-11 02:44:20'),
(17, 4, 300.00, 'Direct Payment', 'LUXE-20260911032204-DD8A59', 'paid', '2026-09-11 03:22:04', '2026-09-11 03:22:04'),
(18, 13, 150.00, 'Stripe', 'cs_test_a1Q6IDR9CGYPmra1h4P4SHBfVBf0iYjrVcVyUpb0Sw3R6XIXFZ3fWJokTS', 'paid', '2026-09-11 16:04:02', '2026-09-11 16:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int NOT NULL DEFAULT '1',
  `special_requests` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `room_id`, `check_in`, `check_out`, `guests`, `special_requests`, `status`, `created_at`, `deleted_at`) VALUES
(1, 7, 1, '2026-09-10', '2026-09-12', 1, 'hh', 'confirmed', '2026-09-09 02:29:43', NULL),
(2, 7, 2, '2026-09-10', '2026-09-11', 3, 'hii', 'confirmed', '2026-09-09 02:36:51', NULL),
(3, 8, 12, '2026-09-10', '2026-09-11', 9, 'hi', 'confirmed', '2026-09-09 03:17:11', NULL),
(4, 7, 3, '2026-09-09', '2026-09-11', 1, 'hj', 'confirmed', '2026-09-09 03:18:11', NULL),
(5, 9, 6, '2026-09-09', '2026-09-12', 2, 'hii', 'confirmed', '2026-09-09 03:24:40', NULL),
(6, 10, 4, '2026-09-09', '2026-09-12', 2, 'personal', 'confirmed', '2026-09-09 03:35:41', NULL),
(7, 11, 7, '2026-09-11', '2026-09-12', 1, 'hii', 'confirmed', '2026-09-10 05:48:29', NULL),
(8, 11, 13, '2026-09-10', '2026-09-11', 1, 'now', 'confirmed', '2026-09-10 05:53:48', NULL),
(9, 12, 10, '2026-09-12', '2026-09-19', 1, 'hi', 'confirmed', '2026-09-10 05:59:24', NULL),
(10, 13, 2, '2026-09-11', '2026-09-12', 1, 'hi', 'confirmed', '2026-09-10 06:55:37', NULL),
(11, 6, 5, '2026-09-10', '2026-09-12', 1, 'nn', 'confirmed', '2026-09-10 16:11:52', NULL),
(12, 7, 6, '2026-09-12', '2026-09-18', 2, '', 'confirmed', '2026-09-11 02:42:09', NULL),
(13, 6, 3, '2026-09-11', '2026-09-12', 1, 'new', 'confirmed', '2026-09-11 16:00:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `capacity` int NOT NULL DEFAULT '2',
  `floor` int DEFAULT NULL,
  `status` enum('available','maintenance','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `description`, `price`, `capacity`, `floor`, `status`, `image`, `created_at`) VALUES
(1, '101', 'Standard Room', 'Comfortable standard room with modern amenities.', 150.00, 2, 1, 'available', NULL, '2026-09-08 15:02:13'),
(2, '102', 'Standard Room', 'Comfortable standard room with modern amenities.', 150.00, 2, 1, 'available', NULL, '2026-09-08 15:02:13'),
(3, '103', 'Standard Room', 'Comfortable standard room with modern amenities.', 150.00, 2, 1, 'available', NULL, '2026-09-08 15:02:13'),
(4, '104', 'Standard Room', 'Comfortable standard room with modern amenities.', 150.00, 2, 1, 'available', NULL, '2026-09-08 15:02:13'),
(5, '105', 'Standard Room', 'Comfortable standard room with modern amenities.', 150.00, 2, 1, 'available', NULL, '2026-09-08 15:02:13'),
(6, '201', 'Standard Double', 'Spacious double room suitable for two guests.', 185.00, 2, 2, 'available', NULL, '2026-09-08 15:02:13'),
(7, '202', 'Standard Double', 'Spacious double room suitable for two guests.', 185.00, 2, 2, 'available', NULL, '2026-09-08 15:02:13'),
(8, '203', 'Standard Double', 'Spacious double room suitable for two guests.', 185.00, 2, 2, 'available', NULL, '2026-09-08 15:02:13'),
(9, '204', 'Standard Double', 'Spacious double room suitable for two guests.', 185.00, 2, 2, 'available', NULL, '2026-09-08 15:02:13'),
(10, '301', 'Deluxe Ocean View', 'Luxury room with beautiful ocean views.', 285.00, 2, 3, 'available', NULL, '2026-09-08 15:02:13'),
(11, '302', 'Deluxe Ocean View', 'Luxury room with beautiful ocean views.', 285.00, 2, 3, 'available', NULL, '2026-09-08 15:02:13'),
(12, '501', 'Presidential Suite', 'Large premium suite with panoramic views.', 850.00, 4, 5, 'available', NULL, '2026-09-08 15:02:13'),
(13, '502', 'Presidential Suite', 'Large premium suite with panoramic views.', 850.00, 4, 5, 'available', NULL, '2026-09-08 15:02:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','guest') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `role`, `created_at`, `deleted_at`) VALUES
(1, 'Hotel Administrator', 'admin@luxestay.com', NULL, NULL, '$2y$10$uTXgKxCPEBejJuT1e9essu0ZiYLRnGTrCbuB6lw2rHXiuC3rnvg92', 'admin', '2026-08-24 14:33:41', NULL),
(2, 'test', 'test@gmail.com', '123456789', 'ktm', '$2y$10$0C.U29DG0ITQVpJdX0VtHeGJgXQweMzvPH.XPweUmRObSXRAb5Q1y', 'guest', '2026-08-24 14:39:17', NULL),
(4, 'one test', 'onetest@gmail.com', '+977 12345678', 'ktm', '$2y$10$sWRWCQNmWdYvYx0lL4cEIuo.e3h1zxET5tCUDiYEUbLCfErsOoU2G', 'guest', '2026-08-24 15:30:55', NULL),
(5, 'nishan', 'nishan@gmail.com', '+977 151894981', 'orchid', '$2y$10$fEQp9DdXCU8rR8XKRxOj9eEOQmhVKbREjM9eF0PpXNeNxuaSsjK7u', 'guest', '2026-08-25 00:50:46', NULL),
(6, 'ankit wagle', 'ankitwagle5@gmail.com', '+977 1515316531', 'ktmm', '$2y$10$5Q64NcFpcZ2e/imqB2fQh.zb9EryyTf8KenWuXs4DtxH4BldMj4rS', 'guest', '2026-09-08 14:14:57', NULL),
(7, 'Abhinav Shrestha', 'abhinavstha46@gmail.com', '+977 9848395823', 'kathmandu', '$2y$10$pITNe87aOa.P8XPMNGyC9.zP18ix8uj7Vh6G431fLyzNAXIovnO.q', 'guest', '2026-09-09 02:18:37', NULL),
(8, 'Aekai offcial', 'aekaiofficial@gmail.com', '+977 7465734567', 'ktm', '$2y$10$hYu8CArdN/ljgcABc663SuD7l2yQBEpfUQmzETwn98EuPioU5XnIG', 'guest', '2026-09-09 03:16:32', NULL),
(9, 'sarthak', 'sarthak.access@gmail.com', '+977 5184848', 'aaa', '$2y$10$l/5oiNyGsHYoo/4EfihPpeZhLl6eorbIP/WD77zx1tQpMGjSt60fC', 'guest', '2026-09-09 03:24:18', NULL),
(10, 'shramika', 'shramikapaudel215@gmail.com', '+977 9848395824', 'bkt', '$2y$10$lOhAZIhoQM4pe06h3VQoK.NtZoLbMvRRRh.OEN8aN77fsUlZGKQwS', 'guest', '2026-09-09 03:35:18', NULL),
(11, 'Abhinav sherstha', 'abhinavbim23@oic.edu.np', '+977 9848395823', 'hii', '$2y$10$8ijnIqngrRxbqrA2dZd18OB6ViAVUI/OK05X.kEh9ayVuG3eTKYX6', 'guest', '2026-09-10 05:48:05', NULL),
(12, 'Abinav shrestgha', 'abhinavsherestha@gmail.com', '+977 9848395823', 'hello', '$2y$10$lnPEvzYkULVbULEuMnOjEeeBumWW/tNs2nfpy1JYh2QTLqUGC5t0W', 'guest', '2026-09-10 05:58:55', NULL),
(13, 'ankit', 'ankitbim23@oic.edu.np', '+977 12345678', 'new jersy', '$2y$10$46riHzoI30dbAR1JFyMWtO709exxBZEnHh6d9oP.gB1ognHBV4rgu', 'guest', '2026-09-10 06:30:42', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
