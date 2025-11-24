-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 04:17 AM
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
-- Database: `admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_text` varchar(255) NOT NULL,
  `qr_monitoring_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`qr_monitoring_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `office_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `activity_type`, `activity_text`, `qr_monitoring_data`, `created_at`, `office_id`, `admin_id`) VALUES
(1, 'user', 'Profile information updated', NULL, '2025-05-26 17:50:00', NULL, NULL),
(2, 'file', 'Floor plan modified', NULL, '2025-05-26 17:50:00', NULL, NULL),
(3, 'office', 'New office added', NULL, '2025-05-26 17:50:00', NULL, NULL),
(4, 'feedback', 'New feedback received', NULL, '2025-05-26 17:50:00', NULL, NULL),
(5, 'test', 'Test activity from diagnostic script', NULL, '2025-05-26 17:50:00', NULL, NULL),
(6, 'office', 'Office \'PAO\' was deleted', NULL, '2025-05-26 17:54:30', NULL, NULL),
(7, 'office', 'New office \'azriel\' added', NULL, '2025-05-26 17:57:53', NULL, NULL),
(15, 'office', 'New office \'Accounting\' added', NULL, '2025-05-26 18:09:59', NULL, NULL),
(16, 'office', 'New office \'STI\' added', NULL, '2025-05-26 18:10:36', NULL, NULL),
(26, 'file', 'Floor plan location updated for STI', NULL, '2025-05-26 18:14:36', NULL, NULL),
(27, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-26 18:14:36', NULL, NULL),
(30, 'office', 'New office \'azriel\' added', NULL, '2025-05-26 18:31:00', NULL, NULL),
(31, 'office', 'New office \'Accounting\' added', NULL, '2025-05-26 18:31:21', NULL, NULL),
(34, 'office', 'New office \'umayyy\' added', NULL, '2025-05-26 18:37:31', NULL, NULL),
(35, 'office', 'New office \'Accounting\' added', NULL, '2025-05-26 18:37:51', NULL, NULL),
(39, 'office', 'New office \'umayyy\' added', NULL, '2025-05-26 18:41:37', NULL, NULL),
(42, 'office', 'New office \'azriel\' added', NULL, '2025-05-26 18:47:30', NULL, NULL),
(43, 'office', 'Office \'azriel\' was deleted', NULL, '2025-05-26 18:47:39', NULL, NULL),
(44, 'office', 'New office \'azriel\' added', NULL, '2025-05-26 18:47:53', NULL, NULL),
(45, 'office', 'New office \'Accounting\' added', NULL, '2025-05-26 18:55:52', NULL, NULL),
(46, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-26 19:00:53', NULL, NULL),
(47, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-26 19:00:53', NULL, NULL),
(48, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-26 19:01:03', NULL, NULL),
(49, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-26 19:01:03', NULL, NULL),
(50, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-26 19:01:36', NULL, NULL),
(51, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-26 19:01:36', NULL, NULL),
(52, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-26 19:03:44', NULL, NULL),
(53, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-26 19:03:44', NULL, NULL),
(54, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-26 19:03:51', NULL, NULL),
(55, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-26 19:03:51', NULL, NULL),
(56, 'file', 'Floor plan location swapped with Accounting', NULL, '2025-05-26 19:10:36', NULL, NULL),
(57, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-26 19:10:36', NULL, NULL),
(58, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-26 19:10:36', NULL, NULL),
(59, 'file', 'Floor plan location swapped with azriel', NULL, '2025-05-26 19:10:41', NULL, NULL),
(60, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-26 19:10:41', NULL, NULL),
(61, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-26 19:10:41', NULL, NULL),
(62, 'file', 'Floor plan location swapped with Accounting', NULL, '2025-05-26 19:10:54', NULL, NULL),
(63, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-26 19:10:54', NULL, NULL),
(64, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-26 19:10:54', NULL, NULL),
(65, 'file', 'Floor plan location swapped with azriel', NULL, '2025-05-27 01:45:43', NULL, NULL),
(66, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 01:45:43', NULL, NULL),
(67, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 01:45:43', NULL, NULL),
(68, 'file', 'Floor plan location swapped with Accounting', NULL, '2025-05-27 01:45:50', NULL, NULL),
(69, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 01:45:50', NULL, NULL),
(70, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 01:45:50', NULL, NULL),
(71, 'file', 'Floor plan location swapped with azriel', NULL, '2025-05-27 03:37:25', NULL, NULL),
(72, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 03:37:25', NULL, NULL),
(73, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 03:37:25', NULL, NULL),
(74, 'feedback', 'New feedback received', NULL, '2025-05-27 04:39:49', NULL, NULL),
(75, 'file', 'Floor plan location swapped with Accounting', NULL, '2025-05-27 05:36:02', NULL, NULL),
(76, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 05:36:02', NULL, NULL),
(77, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 05:36:02', NULL, NULL),
(78, 'office', 'New office \'umayyy\' added', NULL, '2025-05-27 07:19:07', NULL, NULL),
(79, 'file', 'Floor plan location swapped with umayyy', NULL, '2025-05-27 07:24:37', NULL, NULL),
(80, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 07:24:37', NULL, NULL),
(81, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 07:24:37', NULL, NULL),
(82, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 07:24:37', NULL, NULL),
(83, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 07:31:50', NULL, NULL),
(84, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 07:31:50', NULL, NULL),
(85, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 07:31:50', NULL, NULL),
(86, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 07:56:45', NULL, NULL),
(87, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 07:56:45', NULL, NULL),
(88, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 07:56:45', NULL, NULL),
(89, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:07:33', NULL, NULL),
(90, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:07:33', NULL, NULL),
(91, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:07:33', NULL, NULL),
(92, 'file', 'Floor plan location swapped with azriel', NULL, '2025-05-27 08:07:38', NULL, NULL),
(93, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:07:38', NULL, NULL),
(94, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:07:38', NULL, NULL),
(95, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:07:38', NULL, NULL),
(96, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:10:59', NULL, NULL),
(97, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:10:59', NULL, NULL),
(98, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:10:59', NULL, NULL),
(99, 'file', 'Floor plan location swapped with umayyy', NULL, '2025-05-27 08:14:41', NULL, NULL),
(100, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:14:41', NULL, NULL),
(101, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:14:41', NULL, NULL),
(102, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:14:41', NULL, NULL),
(103, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:19:21', NULL, NULL),
(104, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:19:21', NULL, NULL),
(105, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:19:21', NULL, NULL),
(106, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:19:32', NULL, NULL),
(107, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:19:32', NULL, NULL),
(108, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:19:32', NULL, NULL),
(109, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:20:15', NULL, NULL),
(110, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:20:15', NULL, NULL),
(111, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:20:15', NULL, NULL),
(112, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:23:09', NULL, NULL),
(113, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:23:09', NULL, NULL),
(114, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:23:09', NULL, NULL),
(115, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:28:11', NULL, NULL),
(116, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:28:11', NULL, NULL),
(117, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:28:11', NULL, NULL),
(118, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:31:00', NULL, NULL),
(119, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:31:00', NULL, NULL),
(120, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:31:00', NULL, NULL),
(121, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:34:36', NULL, NULL),
(122, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:34:36', NULL, NULL),
(123, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:34:36', NULL, NULL),
(124, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:35:49', NULL, NULL),
(125, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:35:49', NULL, NULL),
(126, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:35:49', NULL, NULL),
(127, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:35:58', NULL, NULL),
(128, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:35:58', NULL, NULL),
(129, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:35:58', NULL, NULL),
(130, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:53:51', NULL, NULL),
(131, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:53:51', NULL, NULL),
(132, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:53:51', NULL, NULL),
(133, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 08:58:54', NULL, NULL),
(134, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 08:58:54', NULL, NULL),
(135, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 08:58:54', NULL, NULL),
(136, 'file', 'Floor plan location swapped with umayyy', NULL, '2025-05-27 09:16:58', NULL, NULL),
(137, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 09:16:58', NULL, NULL),
(138, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 09:16:58', NULL, NULL),
(139, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 09:16:58', NULL, NULL),
(140, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 09:20:04', NULL, NULL),
(141, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 09:20:04', NULL, NULL),
(142, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 09:20:04', NULL, NULL),
(143, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 09:29:08', NULL, NULL),
(144, 'file', 'Floor plan location swapped with Accounting', NULL, '2025-05-27 09:29:08', NULL, NULL),
(145, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 09:29:08', NULL, NULL),
(146, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 09:29:08', NULL, NULL),
(147, 'file', 'Floor plan location swapped with umayyy', NULL, '2025-05-27 09:34:02', NULL, NULL),
(148, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 09:34:02', NULL, NULL),
(149, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 09:34:02', NULL, NULL),
(150, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 09:34:02', NULL, NULL),
(151, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:00:15', NULL, NULL),
(152, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:00:15', NULL, NULL),
(153, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:00:15', NULL, NULL),
(154, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:18:12', NULL, NULL),
(155, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:18:12', NULL, NULL),
(156, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:18:12', NULL, NULL),
(157, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:18:23', NULL, NULL),
(158, 'file', 'Floor plan location swapped with Accounting', NULL, '2025-05-27 10:18:23', NULL, NULL),
(159, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:18:23', NULL, NULL),
(160, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:18:23', NULL, NULL),
(161, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:27:53', NULL, NULL),
(162, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:27:53', NULL, NULL),
(163, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:27:53', NULL, NULL),
(164, 'file', 'Floor plan location swapped with Accounting', NULL, '2025-05-27 10:42:10', NULL, NULL),
(165, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:42:10', NULL, NULL),
(166, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:42:10', NULL, NULL),
(167, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:42:10', NULL, NULL),
(168, 'file', 'Floor plan location swapped with umayyy', NULL, '2025-05-27 10:42:17', NULL, NULL),
(169, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:42:17', NULL, NULL),
(170, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:42:17', NULL, NULL),
(171, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:42:17', NULL, NULL),
(172, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:42:31', NULL, NULL),
(173, 'file', 'Floor plan location swapped with azriel', NULL, '2025-05-27 10:42:31', NULL, NULL),
(174, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:42:31', NULL, NULL),
(175, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:42:31', NULL, NULL),
(176, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:42:47', NULL, NULL),
(177, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:42:47', NULL, NULL),
(178, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:42:47', NULL, NULL),
(179, 'file', 'Floor plan location swapped with azriel', NULL, '2025-05-27 10:44:41', NULL, NULL),
(180, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:44:41', NULL, NULL),
(181, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:44:41', NULL, NULL),
(182, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:44:41', NULL, NULL),
(183, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:46:53', NULL, NULL),
(184, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:46:53', NULL, NULL),
(185, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:46:53', NULL, NULL),
(186, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 10:46:59', NULL, NULL),
(187, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 10:46:59', NULL, NULL),
(188, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 10:46:59', NULL, NULL),
(189, 'office', 'New office \'azriel\' added', NULL, '2025-05-27 11:17:37', NULL, NULL),
(190, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 12:51:41', NULL, NULL),
(191, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 12:51:41', NULL, NULL),
(192, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 12:51:41', NULL, NULL),
(193, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 12:51:41', NULL, NULL),
(194, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 12:55:47', NULL, NULL),
(195, 'file', 'Floor plan location swapped with umayyy', NULL, '2025-05-27 12:55:47', NULL, NULL),
(196, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 12:55:47', NULL, NULL),
(197, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 12:55:47', NULL, NULL),
(198, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 12:55:47', NULL, NULL),
(199, 'file', 'Floor plan location swapped with azriel', NULL, '2025-05-27 12:55:57', NULL, NULL),
(200, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 12:55:57', NULL, NULL),
(201, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 12:55:57', NULL, NULL),
(202, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 12:55:57', NULL, NULL),
(203, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 12:55:57', NULL, NULL),
(204, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:18:45', NULL, NULL),
(205, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 13:18:45', NULL, NULL),
(206, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 13:18:45', NULL, NULL),
(207, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:18:45', NULL, NULL),
(208, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:18:50', NULL, NULL),
(209, 'file', 'Floor plan location swapped with Accounting', NULL, '2025-05-27 13:18:50', NULL, NULL),
(210, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 13:18:50', NULL, NULL),
(211, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 13:18:50', NULL, NULL),
(212, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:18:50', NULL, NULL),
(213, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:27:28', NULL, NULL),
(214, 'file', 'Floor plan location swapped with umayyy', NULL, '2025-05-27 13:27:28', NULL, NULL),
(215, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 13:27:28', NULL, NULL),
(216, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 13:27:28', NULL, NULL),
(217, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:27:28', NULL, NULL),
(218, 'file', 'Floor plan location swapped with umayyy', NULL, '2025-05-27 13:36:42', NULL, NULL),
(219, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 13:36:42', NULL, NULL),
(220, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:36:42', NULL, NULL),
(221, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 13:36:42', NULL, NULL),
(222, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:36:42', NULL, NULL),
(223, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 13:39:24', NULL, NULL),
(224, 'file', 'Floor plan location swapped with Accounting', NULL, '2025-05-27 13:39:24', NULL, NULL),
(225, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 13:39:24', NULL, NULL),
(226, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:39:24', NULL, NULL),
(227, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 13:39:24', NULL, NULL),
(228, 'office', 'New office \'hhhhh\' added', NULL, '2025-05-27 13:54:36', NULL, NULL),
(229, 'office', 'New office \'STI\' added', NULL, '2025-05-27 22:17:31', NULL, NULL),
(230, 'file', 'Floor plan location updated for hhhhh', NULL, '2025-05-27 22:17:57', NULL, NULL),
(231, 'file', 'Floor plan location updated for umayyy', NULL, '2025-05-27 22:17:57', NULL, NULL),
(232, 'file', 'Floor plan location updated for Accounting', NULL, '2025-05-27 22:17:57', NULL, NULL),
(233, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 22:17:57', NULL, NULL),
(234, 'file', 'Floor plan location swapped with STI', NULL, '2025-05-27 22:17:57', NULL, NULL),
(235, 'file', 'Floor plan location updated for STI', NULL, '2025-05-27 22:17:57', NULL, NULL),
(236, 'file', 'Floor plan location updated for azriel', NULL, '2025-05-27 22:17:57', NULL, NULL),
(237, 'office', 'New office \'PAO\' added', NULL, '2025-05-27 22:20:27', NULL, NULL),
(238, 'office', 'Office \'PAO\' was updated', NULL, '2025-05-27 22:20:40', NULL, NULL),
(239, 'office', 'Office \'Accounting\' was deleted', NULL, '2025-05-27 22:36:56', NULL, NULL),
(240, 'office', 'Office \'azriel\' was deleted', NULL, '2025-05-27 22:36:59', NULL, NULL),
(241, 'office', 'Office \'azriel\' was deleted', NULL, '2025-05-27 22:37:02', NULL, NULL),
(242, 'office', 'Office \'hhhhh\' was deleted', NULL, '2025-05-27 22:37:07', NULL, NULL),
(243, 'office', 'Office \'PAO\' was deleted', NULL, '2025-05-27 22:37:15', NULL, NULL),
(244, 'office', 'Office \'STI\' was deleted', NULL, '2025-05-27 22:37:21', NULL, NULL),
(245, 'office', 'New office \'Human Resources\' added', NULL, '2025-05-27 22:42:16', NULL, NULL),
(246, 'office', 'Office \'umayyy\' was deleted', NULL, '2025-05-27 22:42:22', NULL, NULL),
(247, 'office', 'New office \'Information Technology\' added', NULL, '2025-05-27 22:43:30', NULL, NULL),
(248, 'office', 'New office \'Public Relations\' added', NULL, '2025-05-27 22:44:27', NULL, NULL),
(249, 'office', 'New office \'Legal Affairs\' added', NULL, '2025-05-27 22:45:18', NULL, NULL),
(250, 'office', 'New office \'Procurement\' added', NULL, '2025-05-27 22:46:06', NULL, NULL),
(251, 'office', 'New office \'Records Management\' added', NULL, '2025-05-27 22:47:10', NULL, NULL),
(252, 'office', 'Office \'Procurement\' was updated', NULL, '2025-05-27 22:47:22', NULL, NULL),
(253, 'office', 'New office \'Customer Service\' added', NULL, '2025-05-27 22:48:40', NULL, NULL),
(254, 'office', 'New office \'Maintenance and Facilities\' added', NULL, '2025-05-27 22:52:12', NULL, NULL),
(255, 'office', 'New office \'Planning and Development\' added', NULL, '2025-05-27 22:56:49', NULL, NULL),
(256, 'office', 'New office \'Internal Audit\' added', NULL, '2025-05-27 22:57:48', NULL, NULL),
(257, 'office', 'Office \'Customer Service\' was updated', NULL, '2025-05-27 23:07:58', NULL, NULL),
(258, 'feedback', 'New feedback received', NULL, '2025-09-08 15:50:57', NULL, NULL),
(259, 'office', 'New office \'Room 1\' added', NULL, '2025-09-11 18:03:29', NULL, NULL),
(260, 'file', 'Floor plan location updated for Room 1', NULL, '2025-09-14 08:38:57', NULL, NULL),
(261, 'file', 'Floor plan location updated for Procurement', NULL, '2025-09-14 08:38:57', NULL, NULL),
(262, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-09-14 08:38:57', NULL, NULL),
(263, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-09-14 08:38:57', NULL, NULL),
(264, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-09-14 08:38:57', NULL, NULL),
(265, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-09-14 08:38:57', NULL, NULL),
(266, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-09-14 08:38:57', NULL, NULL),
(267, 'file', 'Floor plan location updated for Records Management', NULL, '2025-09-14 08:38:57', NULL, NULL),
(268, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-09-14 08:38:57', NULL, NULL),
(269, 'file', 'Floor plan location updated for Customer Service', NULL, '2025-09-14 08:38:57', NULL, NULL),
(270, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-09-14 08:38:57', NULL, NULL),
(271, 'file', 'Floor plan location updated for Room 1', NULL, '2025-09-14 08:39:04', NULL, NULL),
(272, 'file', 'Floor plan location updated for Procurement', NULL, '2025-09-14 08:39:04', NULL, NULL),
(273, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-09-14 08:39:04', NULL, NULL),
(274, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-09-14 08:39:04', NULL, NULL),
(275, 'file', 'Floor plan location swapped with Information Technology', NULL, '2025-09-14 08:39:04', NULL, NULL),
(276, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-09-14 08:39:04', NULL, NULL),
(277, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-09-14 08:39:04', NULL, NULL),
(278, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-09-14 08:39:04', NULL, NULL),
(279, 'file', 'Floor plan location updated for Records Management', NULL, '2025-09-14 08:39:04', NULL, NULL),
(280, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-09-14 08:39:04', NULL, NULL),
(281, 'file', 'Floor plan location updated for Customer Service', NULL, '2025-09-14 08:39:04', NULL, NULL),
(282, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-09-14 08:39:04', NULL, NULL),
(283, 'office', 'New office \'Room 11\' added', NULL, '2025-09-20 13:52:04', NULL, NULL),
(284, 'office', 'Office \'Room 1\' was deleted', NULL, '2025-09-23 10:01:18', NULL, NULL),
(285, 'office', 'Office \'Room 11\' was deleted', NULL, '2025-09-23 10:01:20', NULL, NULL),
(286, 'office', 'New office \'AHaya\' added', NULL, '2025-10-02 13:32:52', NULL, NULL),
(287, 'office', 'Office \'Customer Service\' was updated', NULL, '2025-10-03 02:09:52', NULL, NULL),
(288, 'office', 'Office \'AHaya\' was updated', NULL, '2025-10-03 02:10:18', NULL, NULL),
(289, 'office', 'Office \'Information Technology\' was updated', NULL, '2025-10-03 03:40:00', NULL, NULL),
(290, 'office', 'Office \'AHaya\' was deleted', NULL, '2025-10-10 05:13:59', NULL, NULL),
(291, 'office', 'New office \'cvfdddffddfdf\' added', NULL, '2025-10-10 05:14:32', NULL, NULL),
(292, 'office', 'New office \'gwapo\' added', NULL, '2025-10-10 10:25:28', NULL, NULL),
(293, 'qr_code', 'QR code for office \'Customer Service\' was deactivated', NULL, '2025-10-11 02:04:18', NULL, NULL),
(294, 'qr_code', 'QR code for office \'Legal Affairs\' was deactivated', NULL, '2025-10-11 02:06:21', NULL, NULL),
(295, 'office', 'New office \'waaay kaagih scanned\' added', NULL, '2025-10-11 02:27:38', NULL, NULL),
(296, 'qr_code', 'QR code for office \'Customer Service\' was activated', NULL, '2025-10-11 02:30:40', NULL, NULL),
(297, 'office', 'New office \'kakapoy\' added', NULL, '2025-10-11 11:26:35', NULL, NULL),
(298, 'office', 'New office \'Third 4\' added', NULL, '2025-10-15 17:35:36', NULL, NULL),
(299, 'office', 'New office \'Third 1\' added', NULL, '2025-10-15 17:36:00', NULL, NULL),
(300, 'office', 'New office \'2nd 12\' added', NULL, '2025-10-15 17:36:51', NULL, NULL),
(301, 'office', 'New office \'This is room 17 \' added', NULL, '2025-10-16 05:55:55', NULL, NULL),
(302, 'office', 'Office \'This is room 17 \' was deleted', NULL, '2025-10-16 06:02:27', NULL, NULL),
(303, 'office', 'New office \'Room-1-2\' added', NULL, '2025-10-16 06:03:05', NULL, NULL),
(304, 'office', 'New office \'Room-17-2\' added', NULL, '2025-10-16 06:03:53', NULL, NULL),
(305, 'office', 'New office \'Room-18-2\' added', NULL, '2025-10-16 06:05:04', NULL, NULL),
(306, 'office', 'Office \'Room-17-2\' was deleted', NULL, '2025-10-16 06:16:59', NULL, NULL),
(307, 'office', 'Office \'Room-1-2\' was deleted', NULL, '2025-10-16 06:17:03', NULL, NULL),
(308, 'office', 'Office \'Room-18-2\' was deleted', NULL, '2025-10-16 06:17:06', NULL, NULL),
(309, 'office', 'Office \'Customer Service\' was deleted', NULL, '2025-10-16 06:19:25', NULL, NULL),
(310, 'office', 'New office \'This is room 17 \' added', NULL, '2025-10-16 06:29:34', NULL, NULL),
(311, 'office', 'Office \'This is room 17 \' was deleted', NULL, '2025-10-16 06:34:35', NULL, NULL),
(312, 'office', 'New office \'This is room 18\' added', NULL, '2025-10-16 11:26:50', NULL, NULL),
(313, 'office', 'New office \'Hello\' added', NULL, '2025-10-19 03:22:36', NULL, NULL),
(314, 'file', 'Floor plan location updated for gwapo', NULL, '2025-10-19 11:15:39', NULL, NULL),
(315, 'file', 'Floor plan location swapped with Legal Affairs', NULL, '2025-10-19 11:15:39', NULL, NULL),
(316, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-10-19 11:15:39', NULL, NULL),
(317, 'file', 'Floor plan location updated for Procurement', NULL, '2025-10-19 11:15:39', NULL, NULL),
(318, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-10-19 11:15:39', NULL, NULL),
(319, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-10-19 11:15:39', NULL, NULL),
(320, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-10-19 11:15:39', NULL, NULL),
(321, 'file', 'Floor plan location updated for kakapoy', NULL, '2025-10-19 11:15:39', NULL, NULL),
(322, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-19 11:15:39', NULL, NULL),
(323, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-19 11:15:39', NULL, NULL),
(324, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-19 11:15:39', NULL, NULL),
(325, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-19 11:15:39', NULL, NULL),
(326, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-19 11:15:39', NULL, NULL),
(327, 'file', 'Floor plan location updated for cvfdddffddfdf', NULL, '2025-10-19 11:15:39', NULL, NULL),
(328, 'file', 'Floor plan location updated for gwapo', NULL, '2025-10-19 11:17:52', NULL, NULL),
(329, 'file', 'Floor plan location swapped with Procurement', NULL, '2025-10-19 11:17:52', NULL, NULL),
(330, 'file', 'Floor plan location updated for Procurement', NULL, '2025-10-19 11:17:52', NULL, NULL),
(331, 'file', 'Floor plan location swapped with Internal Audit', NULL, '2025-10-19 11:17:52', NULL, NULL),
(332, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-10-19 11:17:52', NULL, NULL),
(333, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-10-19 11:17:52', NULL, NULL),
(334, 'file', 'Floor plan location swapped with Human Resources', NULL, '2025-10-19 11:17:52', NULL, NULL),
(335, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-10-19 11:17:52', NULL, NULL),
(336, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-10-19 11:17:52', NULL, NULL),
(337, 'file', 'Floor plan location updated for kakapoy', NULL, '2025-10-19 11:17:52', NULL, NULL),
(338, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-19 11:17:52', NULL, NULL),
(339, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-19 11:17:52', NULL, NULL),
(340, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-19 11:17:52', NULL, NULL),
(341, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-19 11:17:52', NULL, NULL),
(342, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-19 11:17:52', NULL, NULL),
(343, 'file', 'Floor plan location updated for cvfdddffddfdf', NULL, '2025-10-19 11:17:52', NULL, NULL),
(344, 'file', 'Floor plan location updated for gwapo', NULL, '2025-10-19 11:18:07', NULL, NULL),
(345, 'file', 'Floor plan location swapped with Internal Audit', NULL, '2025-10-19 11:18:07', NULL, NULL),
(346, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-10-19 11:18:07', NULL, NULL),
(347, 'file', 'Floor plan location updated for Procurement', NULL, '2025-10-19 11:18:07', NULL, NULL),
(348, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-10-19 11:18:07', NULL, NULL),
(349, 'file', 'Floor plan location swapped with Information Technology', NULL, '2025-10-19 11:18:07', NULL, NULL),
(350, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-10-19 11:18:07', NULL, NULL),
(351, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-10-19 11:18:07', NULL, NULL),
(352, 'file', 'Floor plan location updated for kakapoy', NULL, '2025-10-19 11:18:07', NULL, NULL),
(353, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-19 11:18:07', NULL, NULL),
(354, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-19 11:18:07', NULL, NULL),
(355, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-19 11:18:07', NULL, NULL),
(356, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-19 11:18:07', NULL, NULL),
(357, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-19 11:18:07', NULL, NULL),
(358, 'file', 'Floor plan location updated for cvfdddffddfdf', NULL, '2025-10-19 11:18:07', NULL, NULL),
(359, 'file', 'Floor plan location updated for gwapo', NULL, '2025-10-19 11:19:06', NULL, NULL),
(360, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-10-19 11:19:06', NULL, NULL),
(361, 'file', 'Floor plan location updated for Procurement', NULL, '2025-10-19 11:19:06', NULL, NULL),
(362, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-10-19 11:19:06', NULL, NULL),
(363, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-10-19 11:19:06', NULL, NULL),
(364, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-10-19 11:19:06', NULL, NULL),
(365, 'file', 'Floor plan location updated for kakapoy', NULL, '2025-10-19 11:19:06', NULL, NULL),
(366, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-19 11:19:06', NULL, NULL),
(367, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-19 11:19:06', NULL, NULL),
(368, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-19 11:19:06', NULL, NULL),
(369, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-19 11:19:06', NULL, NULL),
(370, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-19 11:19:06', NULL, NULL),
(371, 'file', 'Floor plan location updated for cvfdddffddfdf', NULL, '2025-10-19 11:19:06', NULL, NULL),
(372, 'file', 'Floor plan location updated for gwapo', NULL, '2025-10-19 11:20:27', NULL, NULL),
(373, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-10-19 11:20:27', NULL, NULL),
(374, 'file', 'Floor plan location updated for Procurement', NULL, '2025-10-19 11:20:27', NULL, NULL),
(375, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-10-19 11:20:27', NULL, NULL),
(376, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-10-19 11:20:27', NULL, NULL),
(377, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-10-19 11:20:27', NULL, NULL),
(378, 'file', 'Floor plan location updated for kakapoy', NULL, '2025-10-19 11:20:27', NULL, NULL),
(379, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-19 11:20:27', NULL, NULL),
(380, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-19 11:20:27', NULL, NULL),
(381, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-19 11:20:27', NULL, NULL),
(382, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-19 11:20:27', NULL, NULL),
(383, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-19 11:20:27', NULL, NULL),
(384, 'file', 'Floor plan location updated for cvfdddffddfdf', NULL, '2025-10-19 11:20:27', NULL, NULL),
(385, 'file', 'Floor plan location swapped with Internal Audit', NULL, '2025-10-19 12:02:55', NULL, NULL),
(386, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-10-19 12:02:55', NULL, NULL),
(387, 'file', 'Floor plan location updated for gwapo', NULL, '2025-10-19 12:02:55', NULL, NULL),
(388, 'file', 'Floor plan location updated for Procurement', NULL, '2025-10-19 12:02:55', NULL, NULL),
(389, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-10-19 12:02:55', NULL, NULL),
(390, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-10-19 12:02:55', NULL, NULL),
(391, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-10-19 12:02:55', NULL, NULL),
(392, 'file', 'Floor plan location updated for kakapoy', NULL, '2025-10-19 12:02:55', NULL, NULL),
(393, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-19 12:02:55', NULL, NULL),
(394, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-19 12:02:55', NULL, NULL),
(395, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-19 12:02:55', NULL, NULL),
(396, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-19 12:02:55', NULL, NULL),
(397, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-19 12:02:55', NULL, NULL),
(398, 'file', 'Floor plan location updated for cvfdddffddfdf', NULL, '2025-10-19 12:02:55', NULL, NULL),
(399, 'file', 'Floor plan location swapped with gwapo', NULL, '2025-10-19 12:03:37', NULL, NULL),
(400, 'file', 'Floor plan location updated for gwapo', NULL, '2025-10-19 12:03:37', NULL, NULL),
(401, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-10-19 12:03:37', NULL, NULL),
(402, 'file', 'Floor plan location updated for Procurement', NULL, '2025-10-19 12:03:37', NULL, NULL),
(403, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-10-19 12:03:37', NULL, NULL),
(404, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-10-19 12:03:37', NULL, NULL),
(405, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-10-19 12:03:37', NULL, NULL),
(406, 'file', 'Floor plan location updated for kakapoy', NULL, '2025-10-19 12:03:37', NULL, NULL),
(407, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-19 12:03:37', NULL, NULL),
(408, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-19 12:03:37', NULL, NULL),
(409, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-19 12:03:37', NULL, NULL),
(410, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-19 12:03:37', NULL, NULL),
(411, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-19 12:03:37', NULL, NULL),
(412, 'file', 'Floor plan location updated for cvfdddffddfdf', NULL, '2025-10-19 12:03:37', NULL, NULL),
(413, 'file', 'Floor plan location updated for gwapo', NULL, '2025-10-19 12:03:44', NULL, NULL),
(414, 'file', 'Floor plan location swapped with Procurement', NULL, '2025-10-19 12:03:44', NULL, NULL),
(415, 'file', 'Floor plan location updated for Procurement', NULL, '2025-10-19 12:03:44', NULL, NULL),
(416, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-10-19 12:03:44', NULL, NULL),
(417, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-10-19 12:03:44', NULL, NULL),
(418, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-10-19 12:03:44', NULL, NULL),
(419, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-10-19 12:03:44', NULL, NULL),
(420, 'file', 'Floor plan location updated for kakapoy', NULL, '2025-10-19 12:03:44', NULL, NULL),
(421, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-19 12:03:44', NULL, NULL),
(422, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-19 12:03:44', NULL, NULL),
(423, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-19 12:03:44', NULL, NULL),
(424, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-19 12:03:44', NULL, NULL),
(425, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-19 12:03:44', NULL, NULL),
(426, 'file', 'Floor plan location updated for cvfdddffddfdf', NULL, '2025-10-19 12:03:44', NULL, NULL),
(427, 'file', 'Floor plan location swapped with Information Technology', NULL, '2025-10-19 12:03:53', NULL, NULL),
(428, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-10-19 12:03:53', NULL, NULL),
(429, 'file', 'Floor plan location updated for Procurement', NULL, '2025-10-19 12:03:53', NULL, NULL),
(430, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-10-19 12:03:53', NULL, NULL),
(431, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-10-19 12:03:53', NULL, NULL),
(432, 'file', 'Floor plan location updated for gwapo', NULL, '2025-10-19 12:03:53', NULL, NULL),
(433, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-10-19 12:03:53', NULL, NULL),
(434, 'file', 'Floor plan location updated for kakapoy', NULL, '2025-10-19 12:03:53', NULL, NULL),
(435, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-19 12:03:53', NULL, NULL),
(436, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-19 12:03:53', NULL, NULL),
(437, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-19 12:03:53', NULL, NULL),
(438, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-19 12:03:53', NULL, NULL),
(439, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-19 12:03:53', NULL, NULL),
(440, 'file', 'Floor plan location updated for cvfdddffddfdf', NULL, '2025-10-19 12:03:53', NULL, NULL),
(441, 'file', 'Floor plan location updated for 2nd 12', NULL, '2025-10-19 12:09:17', NULL, NULL),
(442, 'file', 'Floor plan location updated for Hello', NULL, '2025-10-19 12:09:17', NULL, NULL),
(443, 'file', 'Floor plan location updated for This is room 18', NULL, '2025-10-19 12:09:17', NULL, NULL),
(444, 'file', 'Floor plan location updated for 2nd 12', NULL, '2025-10-19 12:09:33', NULL, NULL),
(445, 'file', 'Floor plan location updated for Hello', NULL, '2025-10-19 12:09:33', NULL, NULL),
(446, 'file', 'Floor plan location updated for This is room 18', NULL, '2025-10-19 12:09:33', NULL, NULL),
(447, 'file', 'Floor plan location updated for 2nd 12', NULL, '2025-10-19 12:09:44', NULL, NULL),
(448, 'file', 'Floor plan location updated for Hello', NULL, '2025-10-19 12:09:44', NULL, NULL),
(449, 'file', 'Floor plan location updated for This is room 18', NULL, '2025-10-19 12:09:44', NULL, NULL),
(450, 'file', 'Floor plan location updated for 2nd 12', NULL, '2025-10-19 12:09:51', NULL, NULL),
(451, 'file', 'Floor plan location updated for This is room 18', NULL, '2025-10-19 12:09:51', NULL, NULL),
(452, 'file', 'Floor plan location updated for Hello', NULL, '2025-10-19 12:09:51', NULL, NULL),
(453, 'file', 'Floor plan location updated for 2nd 12', NULL, '2025-10-19 12:10:05', NULL, NULL),
(454, 'file', 'Floor plan location swapped with Hello', NULL, '2025-10-19 12:10:05', NULL, NULL),
(455, 'file', 'Floor plan location updated for Hello', NULL, '2025-10-19 12:10:05', NULL, NULL),
(456, 'file', 'Floor plan location updated for This is room 18', NULL, '2025-10-19 12:10:05', NULL, NULL),
(457, 'file', 'Floor plan location updated for Third 1', NULL, '2025-10-19 12:10:17', NULL, NULL),
(458, 'file', 'Floor plan location updated for Third 4', NULL, '2025-10-19 12:10:17', NULL, NULL),
(459, 'office', 'Office \'Pubblic Toilet\' was updated', NULL, '2025-10-19 19:03:13', NULL, NULL),
(460, 'office', 'Office \'S.P Staff\' was updated', NULL, '2025-10-19 19:04:36', NULL, NULL),
(461, 'office', 'Office \'Prov Library\' was updated', NULL, '2025-10-19 19:05:18', NULL, NULL),
(462, 'office', 'Office \'Historical Comm\' was updated', NULL, '2025-10-19 19:05:47', NULL, NULL),
(463, 'office', 'Office \'S.P Staff and Records\' was updated', NULL, '2025-10-19 19:06:37', NULL, NULL),
(464, 'office', 'Office \'Budget\' was updated', NULL, '2025-10-19 19:07:11', NULL, NULL),
(465, 'office', 'Office \'Public Toilet\' was updated', NULL, '2025-10-19 19:08:32', NULL, NULL),
(466, 'office', 'Office \'public toilet\' was updated', NULL, '2025-10-19 19:08:59', NULL, NULL),
(467, 'office', 'New office \'IT Department\' added', NULL, '2025-10-20 07:00:56', NULL, NULL),
(468, 'office', 'New office \'ICTD Office\' added', NULL, '2025-10-20 07:50:42', NULL, NULL),
(469, 'office', 'Office \'2nd 12\' was deleted', NULL, '2025-10-20 07:50:56', NULL, NULL),
(470, 'qr_code', 'QR code for office \'Budget\' was deactivated', NULL, '2025-10-20 07:51:06', NULL, NULL),
(471, 'qr_code', 'QR code for office \'Budget\' was activated', NULL, '2025-10-20 07:51:07', NULL, NULL),
(472, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-10-20 07:51:36', NULL, NULL),
(473, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-10-20 07:51:36', NULL, NULL),
(474, 'file', 'Floor plan location updated for Prov Library', NULL, '2025-10-20 07:51:36', NULL, NULL),
(475, 'file', 'Floor plan location updated for Historical Comm', NULL, '2025-10-20 07:51:36', NULL, NULL),
(476, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-10-20 07:51:36', NULL, NULL),
(477, 'file', 'Floor plan location swapped with Public Relations', NULL, '2025-10-20 07:51:36', NULL, NULL),
(478, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-20 07:51:36', NULL, NULL),
(479, 'file', 'Floor plan location updated for Public Toilet', NULL, '2025-10-20 07:51:36', NULL, NULL),
(480, 'file', 'Floor plan location updated for Budget', NULL, '2025-10-20 07:51:36', NULL, NULL),
(481, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-20 07:51:36', NULL, NULL),
(482, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-20 07:51:36', NULL, NULL),
(483, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-20 07:51:36', NULL, NULL),
(484, 'file', 'Floor plan location updated for IT Department', NULL, '2025-10-20 07:51:36', NULL, NULL),
(485, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-20 07:51:36', NULL, NULL),
(486, 'file', 'Floor plan location updated for public toilet', NULL, '2025-10-20 07:51:36', NULL, NULL),
(487, 'office', 'Office \'Prov Library and ICTD\' was updated', NULL, '2025-10-20 07:52:35', NULL, NULL),
(488, 'feedback', 'New feedback received', NULL, '2025-10-20 08:00:14', NULL, NULL),
(489, 'feedback', 'Feedback #77 was archived', NULL, '2025-10-20 13:39:50', NULL, NULL),
(490, 'feedback', 'Feedback #77 was deleted', NULL, '2025-10-20 13:40:36', NULL, NULL),
(491, 'feedback', 'Feedback #77 was deleted', NULL, '2025-10-20 13:40:59', NULL, NULL),
(492, 'feedback', 'Feedback #76 was archived', NULL, '2025-10-20 13:41:25', NULL, NULL),
(493, 'feedback', 'Feedback #69 was archived', NULL, '2025-10-20 13:41:35', NULL, NULL),
(494, 'feedback', 'Feedback #74 was archived', NULL, '2025-10-20 13:41:35', NULL, NULL),
(495, 'feedback', 'Feedback #73 was archived', NULL, '2025-10-20 13:41:35', NULL, NULL),
(496, 'feedback', 'Feedback #72 was archived', NULL, '2025-10-20 13:41:35', NULL, NULL),
(497, 'feedback', 'New feedback received', NULL, '2025-10-20 13:41:55', NULL, NULL),
(498, 'feedback', 'Feedback #78 was archived', NULL, '2025-10-20 13:42:08', NULL, NULL),
(499, 'feedback', 'Feedback #78 was unarchived', NULL, '2025-10-20 13:55:47', NULL, NULL),
(500, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-10-21 06:12:59', NULL, NULL),
(501, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-10-21 06:12:59', NULL, NULL),
(502, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-10-21 06:12:59', NULL, NULL),
(503, 'file', 'Floor plan location updated for Historical Comm', NULL, '2025-10-21 06:12:59', NULL, NULL),
(504, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-10-21 06:12:59', NULL, NULL),
(505, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-21 06:12:59', NULL, NULL),
(506, 'file', 'Floor plan location updated for Public Toilet', NULL, '2025-10-21 06:12:59', NULL, NULL),
(507, 'file', 'Floor plan location swapped with waaay kaagih scanned', NULL, '2025-10-21 06:12:59', NULL, NULL),
(508, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-21 06:12:59', NULL, NULL),
(509, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-21 06:12:59', NULL, NULL),
(510, 'file', 'Floor plan location updated for Budget', NULL, '2025-10-21 06:12:59', NULL, NULL),
(511, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-21 06:12:59', NULL, NULL),
(512, 'file', 'Floor plan location updated for IT Department', NULL, '2025-10-21 06:12:59', NULL, NULL),
(513, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-21 06:12:59', NULL, NULL),
(514, 'file', 'Floor plan location updated for public toilet', NULL, '2025-10-21 06:12:59', NULL, NULL),
(515, 'office', 'Office \'Budget\' was updated', NULL, '2025-10-21 06:30:53', NULL, NULL),
(516, 'feedback', 'Feedback #76 was deleted', NULL, '2025-10-21 08:46:06', NULL, NULL),
(517, 'feedback', 'Feedback #76 was deleted', NULL, '2025-10-21 08:46:26', NULL, NULL),
(518, 'office', 'New office \'Hello World\' added', NULL, '2025-10-21 18:44:47', NULL, NULL),
(519, 'office', 'New office \'ADd office\' added', NULL, '2025-10-21 18:51:35', NULL, NULL),
(520, 'office', 'New office \'Keee\' added', NULL, '2025-10-21 19:43:28', NULL, NULL),
(521, 'office', 'New office \'kamusta tiad\' added', NULL, '2025-10-21 20:03:53', NULL, NULL),
(522, 'office', 'New office \'Office Hours\' added', NULL, '2025-10-21 21:56:21', NULL, NULL),
(523, 'office', 'Office \'ADd office\' was deleted', NULL, '2025-10-22 01:25:22', NULL, NULL),
(524, 'office', 'Office \'Budget\' was deleted', NULL, '2025-10-22 01:25:28', NULL, NULL),
(525, 'office', 'Office \'Public Toilet\' was deleted', NULL, '2025-10-22 01:25:44', NULL, NULL),
(526, 'office', 'Office \'ICTD Office\' was deleted', NULL, '2025-10-22 01:26:03', NULL, NULL),
(527, 'office', 'Office \'Historical Comm\' was deleted', NULL, '2025-10-22 01:26:28', NULL, NULL),
(528, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-10-23 03:40:03', NULL, NULL);
INSERT INTO `activities` (`id`, `activity_type`, `activity_text`, `qr_monitoring_data`, `created_at`, `office_id`, `admin_id`) VALUES
(529, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-10-23 03:40:03', NULL, NULL),
(530, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-10-23 03:40:03', NULL, NULL),
(531, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-10-23 03:40:03', NULL, NULL),
(532, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-23 03:40:03', NULL, NULL),
(533, 'file', 'Floor plan location swapped with IT Department', NULL, '2025-10-23 03:40:03', NULL, NULL),
(534, 'file', 'Floor plan location updated for IT Department', NULL, '2025-10-23 03:40:03', NULL, NULL),
(535, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-23 03:40:03', NULL, NULL),
(536, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-23 03:40:03', NULL, NULL),
(537, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-23 03:40:03', NULL, NULL),
(538, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-23 03:40:03', NULL, NULL),
(539, 'file', 'Floor plan location updated for public toilet', NULL, '2025-10-23 03:40:03', NULL, NULL),
(540, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-10-23 03:41:26', NULL, NULL),
(541, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-10-23 03:41:26', NULL, NULL),
(542, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-10-23 03:41:26', NULL, NULL),
(543, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-10-23 03:41:26', NULL, NULL),
(544, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-23 03:41:26', NULL, NULL),
(545, 'file', 'Floor plan location swapped with waaay kaagih scanned', NULL, '2025-10-23 03:41:26', NULL, NULL),
(546, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-23 03:41:26', NULL, NULL),
(547, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-23 03:41:26', NULL, NULL),
(548, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-23 03:41:26', NULL, NULL),
(549, 'file', 'Floor plan location updated for IT Department', NULL, '2025-10-23 03:41:26', NULL, NULL),
(550, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-23 03:41:26', NULL, NULL),
(551, 'file', 'Floor plan location updated for public toilet', NULL, '2025-10-23 03:41:26', NULL, NULL),
(552, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-10-23 03:41:43', NULL, NULL),
(553, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-10-23 03:41:43', NULL, NULL),
(554, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-10-23 03:41:43', NULL, NULL),
(555, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-10-23 03:41:43', NULL, NULL),
(556, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-23 03:41:43', NULL, NULL),
(557, 'file', 'Floor plan location swapped with IT Department', NULL, '2025-10-23 03:41:43', NULL, NULL),
(558, 'file', 'Floor plan location updated for IT Department', NULL, '2025-10-23 03:41:43', NULL, NULL),
(559, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-23 03:41:43', NULL, NULL),
(560, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-23 03:41:43', NULL, NULL),
(561, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-23 03:41:43', NULL, NULL),
(562, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-23 03:41:43', NULL, NULL),
(563, 'file', 'Floor plan location updated for public toilet', NULL, '2025-10-23 03:41:43', NULL, NULL),
(564, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-10-23 03:41:52', NULL, NULL),
(565, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-10-23 03:41:52', NULL, NULL),
(566, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-10-23 03:41:52', NULL, NULL),
(567, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-10-23 03:41:52', NULL, NULL),
(568, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-23 03:41:52', NULL, NULL),
(569, 'file', 'Floor plan location swapped with waaay kaagih scanned', NULL, '2025-10-23 03:41:52', NULL, NULL),
(570, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-23 03:41:52', NULL, NULL),
(571, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-23 03:41:52', NULL, NULL),
(572, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-23 03:41:52', NULL, NULL),
(573, 'file', 'Floor plan location updated for IT Department', NULL, '2025-10-23 03:41:52', NULL, NULL),
(574, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-23 03:41:52', NULL, NULL),
(575, 'file', 'Floor plan location updated for public toilet', NULL, '2025-10-23 03:41:52', NULL, NULL),
(576, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-10-23 03:42:03', NULL, NULL),
(577, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-10-23 03:42:03', NULL, NULL),
(578, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-10-23 03:42:03', NULL, NULL),
(579, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-10-23 03:42:03', NULL, NULL),
(580, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-10-23 03:42:03', NULL, NULL),
(581, 'file', 'Floor plan location swapped with IT Department', NULL, '2025-10-23 03:42:03', NULL, NULL),
(582, 'file', 'Floor plan location updated for IT Department', NULL, '2025-10-23 03:42:03', NULL, NULL),
(583, 'file', 'Floor plan location updated for Records Management', NULL, '2025-10-23 03:42:03', NULL, NULL),
(584, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-10-23 03:42:03', NULL, NULL),
(585, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-10-23 03:42:03', NULL, NULL),
(586, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-10-23 03:42:03', NULL, NULL),
(587, 'file', 'Floor plan location updated for public toilet', NULL, '2025-10-23 03:42:03', NULL, NULL),
(588, 'office', 'New office \'Hello haha\' added', NULL, '2025-10-23 11:59:53', NULL, NULL),
(589, 'office', 'New office \'Hello from room 11\' added', NULL, '2025-10-26 03:36:55', NULL, NULL),
(590, 'office', 'New office \'HEllo World from 2nd\' added', NULL, '2025-10-26 03:46:01', NULL, NULL),
(591, 'office', 'New office \'SA third ya??\' added', NULL, '2025-10-26 03:46:53', NULL, NULL),
(592, 'office', 'New office \'HEllo room 5\' added', NULL, '2025-10-26 09:44:41', NULL, NULL),
(593, 'office', 'New office \'Army nu flor\' added', NULL, '2025-10-26 09:46:46', NULL, NULL),
(594, 'office', 'New office \'JAvier wifi\' added', NULL, '2025-10-26 10:08:44', NULL, NULL),
(595, 'office', 'New office \'Javier Wifi2\' added', NULL, '2025-10-26 10:17:15', NULL, NULL),
(596, 'office', 'New office \'MnM\'s\' added', NULL, '2025-10-27 00:27:38', NULL, NULL),
(597, 'office', 'New office \'KAY MAMA\' added', NULL, '2025-11-08 08:36:35', NULL, NULL),
(598, 'office', 'New office \'Door Implementation\' added', NULL, '2025-11-08 10:50:35', NULL, NULL),
(599, 'office', 'New office \'Door Implementation\' added', NULL, '2025-11-08 10:51:53', NULL, NULL),
(600, 'office', 'Office \'Door Implementation\' was deleted', NULL, '2025-11-08 10:53:03', NULL, NULL),
(601, 'office', 'Office \'Door Implementation\' was deleted', NULL, '2025-11-08 10:53:05', NULL, NULL),
(602, 'office', 'New office \'Door Implementation\' added', NULL, '2025-11-08 10:53:28', NULL, NULL),
(603, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-11-08 13:34:19', NULL, NULL),
(604, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-11-08 13:34:19', NULL, NULL),
(605, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-11-08 13:34:19', NULL, NULL),
(606, 'file', 'Floor plan location updated for Door Implementation', NULL, '2025-11-08 13:34:19', NULL, NULL),
(607, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-11-08 13:34:19', NULL, NULL),
(608, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-11-08 13:34:19', NULL, NULL),
(609, 'file', 'Floor plan location updated for MnM\'s', NULL, '2025-11-08 13:34:19', NULL, NULL),
(610, 'file', 'Floor plan location updated for IT Department', NULL, '2025-11-08 13:34:19', NULL, NULL),
(611, 'file', 'Floor plan location updated for Records Management', NULL, '2025-11-08 13:34:19', NULL, NULL),
(612, 'file', 'Floor plan location updated for Hello from room 11', NULL, '2025-11-08 13:34:19', NULL, NULL),
(613, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-11-08 13:34:19', NULL, NULL),
(614, 'file', 'Floor plan location swapped with Maintenance and Facilities', NULL, '2025-11-08 13:34:19', NULL, NULL),
(615, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-11-08 13:34:19', NULL, NULL),
(616, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-11-08 13:34:19', NULL, NULL),
(617, 'file', 'Floor plan location updated for public toilet', NULL, '2025-11-08 13:34:19', NULL, NULL),
(618, 'office', 'New office \'asd\' added', NULL, '2025-11-12 11:49:27', NULL, NULL),
(619, 'office', 'Office \'Army nu flor\' was updated', NULL, '2025-11-12 12:06:32', NULL, NULL),
(620, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-11-12 13:25:05', NULL, NULL),
(621, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-11-12 13:25:05', NULL, NULL),
(622, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-11-12 13:25:05', NULL, NULL),
(623, 'file', 'Floor plan location updated for Door Implementation', NULL, '2025-11-12 13:25:06', NULL, NULL),
(624, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-11-12 13:25:06', NULL, NULL),
(625, 'file', 'Floor plan location swapped with IT Department', NULL, '2025-11-12 13:25:06', NULL, NULL),
(626, 'file', 'Floor plan location updated for IT Department', NULL, '2025-11-12 13:25:06', NULL, NULL),
(627, 'file', 'Floor plan location updated for Army nu flor', NULL, '2025-11-12 13:25:06', NULL, NULL),
(628, 'file', 'Floor plan location updated for MnM\'s', NULL, '2025-11-12 13:25:06', NULL, NULL),
(629, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-11-12 13:25:06', NULL, NULL),
(630, 'file', 'Floor plan location updated for Records Management', NULL, '2025-11-12 13:25:06', NULL, NULL),
(631, 'file', 'Floor plan location updated for Hello from room 11', NULL, '2025-11-12 13:25:06', NULL, NULL),
(632, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-11-12 13:25:06', NULL, NULL),
(633, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-11-12 13:25:06', NULL, NULL),
(634, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-11-12 13:25:06', NULL, NULL),
(635, 'file', 'Floor plan location updated for public toilet', NULL, '2025-11-12 13:25:06', NULL, NULL),
(636, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-11-12 13:26:43', NULL, NULL),
(637, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-11-12 13:26:43', NULL, NULL),
(638, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-11-12 13:26:43', NULL, NULL),
(639, 'file', 'Floor plan location updated for Door Implementation', NULL, '2025-11-12 13:26:43', NULL, NULL),
(640, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-11-12 13:26:43', NULL, NULL),
(641, 'file', 'Floor plan location updated for IT Department', NULL, '2025-11-12 13:26:43', NULL, NULL),
(642, 'file', 'Floor plan location updated for Army nu flor', NULL, '2025-11-12 13:26:43', NULL, NULL),
(643, 'file', 'Floor plan location updated for MnM\'s', NULL, '2025-11-12 13:26:43', NULL, NULL),
(644, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-11-12 13:26:43', NULL, NULL),
(645, 'file', 'Floor plan location updated for Records Management', NULL, '2025-11-12 13:26:43', NULL, NULL),
(646, 'file', 'Floor plan location updated for Hello from room 11', NULL, '2025-11-12 13:26:43', NULL, NULL),
(647, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-11-12 13:26:43', NULL, NULL),
(648, 'file', 'Floor plan location swapped with waaay kaagih scanned', NULL, '2025-11-12 13:26:43', NULL, NULL),
(649, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-11-12 13:26:43', NULL, NULL),
(650, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-11-12 13:26:43', NULL, NULL),
(651, 'file', 'Floor plan location updated for public toilet', NULL, '2025-11-12 13:26:43', NULL, NULL),
(652, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-11-12 13:42:48', NULL, NULL),
(653, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-11-12 13:42:48', NULL, NULL),
(654, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-11-12 13:42:48', NULL, NULL),
(655, 'file', 'Floor plan location updated for Door Implementation', NULL, '2025-11-12 13:42:48', NULL, NULL),
(656, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-11-12 13:42:48', NULL, NULL),
(657, 'file', 'Floor plan location updated for IT Department', NULL, '2025-11-12 13:42:48', NULL, NULL),
(658, 'file', 'Floor plan location updated for Army nu flor', NULL, '2025-11-12 13:42:48', NULL, NULL),
(659, 'file', 'Floor plan location updated for MnM\'s', NULL, '2025-11-12 13:42:48', NULL, NULL),
(660, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-11-12 13:42:48', NULL, NULL),
(661, 'file', 'Floor plan location updated for Records Management', NULL, '2025-11-12 13:42:48', NULL, NULL),
(662, 'file', 'Floor plan location updated for Hello from room 11', NULL, '2025-11-12 13:42:48', NULL, NULL),
(663, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-11-12 13:42:48', NULL, NULL),
(664, 'file', 'Floor plan location swapped with Maintenance and Facilities', NULL, '2025-11-12 13:42:48', NULL, NULL),
(665, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-11-12 13:42:48', NULL, NULL),
(666, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-11-12 13:42:48', NULL, NULL),
(667, 'file', 'Floor plan location updated for public toilet', NULL, '2025-11-12 13:42:48', NULL, NULL),
(668, 'file', 'Floor plan location updated for JAvier wifi', NULL, '2025-11-12 13:43:01', NULL, NULL),
(669, 'file', 'Floor plan location updated for Javier Wifi2', NULL, '2025-11-12 13:43:01', NULL, NULL),
(670, 'file', 'Floor plan location updated for HEllo room 5', NULL, '2025-11-12 13:43:01', NULL, NULL),
(671, 'file', 'Floor plan location updated for HEllo World from 2nd', NULL, '2025-11-12 13:43:01', NULL, NULL),
(672, 'file', 'Floor plan location updated for KAY MAMA', NULL, '2025-11-12 13:43:01', NULL, NULL),
(673, 'file', 'Floor plan location updated for Hello World', NULL, '2025-11-12 13:43:01', NULL, NULL),
(674, 'file', 'Floor plan location updated for Office Hours', NULL, '2025-11-12 13:43:01', NULL, NULL),
(675, 'file', 'Floor plan location updated for Hello', NULL, '2025-11-12 13:43:01', NULL, NULL),
(676, 'file', 'Floor plan location updated for Keee', NULL, '2025-11-12 13:43:01', NULL, NULL),
(677, 'file', 'Floor plan location updated for Hello haha', NULL, '2025-11-12 13:43:01', NULL, NULL),
(678, 'file', 'Floor plan location updated for This is room 18', NULL, '2025-11-12 13:43:01', NULL, NULL),
(679, 'file', 'Floor plan location updated for Javier Wifi2', NULL, '2025-11-12 13:43:15', NULL, NULL),
(680, 'file', 'Floor plan location updated for JAvier wifi', NULL, '2025-11-12 13:43:15', NULL, NULL),
(681, 'file', 'Floor plan location updated for HEllo room 5', NULL, '2025-11-12 13:43:15', NULL, NULL),
(682, 'file', 'Floor plan location updated for HEllo World from 2nd', NULL, '2025-11-12 13:43:15', NULL, NULL),
(683, 'file', 'Floor plan location updated for KAY MAMA', NULL, '2025-11-12 13:43:15', NULL, NULL),
(684, 'file', 'Floor plan location updated for Hello World', NULL, '2025-11-12 13:43:15', NULL, NULL),
(685, 'file', 'Floor plan location updated for Office Hours', NULL, '2025-11-12 13:43:15', NULL, NULL),
(686, 'file', 'Floor plan location updated for Hello', NULL, '2025-11-12 13:43:15', NULL, NULL),
(687, 'file', 'Floor plan location updated for Keee', NULL, '2025-11-12 13:43:15', NULL, NULL),
(688, 'file', 'Floor plan location updated for Hello haha', NULL, '2025-11-12 13:43:15', NULL, NULL),
(689, 'file', 'Floor plan location updated for This is room 18', NULL, '2025-11-12 13:43:15', NULL, NULL),
(690, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-11-12 13:44:12', NULL, NULL),
(691, 'file', 'Floor plan location swapped with Prov Library and ICTD', NULL, '2025-11-12 13:44:12', NULL, NULL),
(692, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-11-12 13:44:12', NULL, NULL),
(693, 'file', 'Floor plan location swapped with S.P Staff and Records', NULL, '2025-11-12 13:44:12', NULL, NULL),
(694, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-11-12 13:44:12', NULL, NULL),
(695, 'file', 'Floor plan location updated for Door Implementation', NULL, '2025-11-12 13:44:12', NULL, NULL),
(696, 'file', 'Floor plan location swapped with IT Department', NULL, '2025-11-12 13:44:12', NULL, NULL),
(697, 'file', 'Floor plan location updated for IT Department', NULL, '2025-11-12 13:44:12', NULL, NULL),
(698, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-11-12 13:44:12', NULL, NULL),
(699, 'file', 'Floor plan location updated for Army nu flor', NULL, '2025-11-12 13:44:12', NULL, NULL),
(700, 'file', 'Floor plan location updated for MnM\'s', NULL, '2025-11-12 13:44:12', NULL, NULL),
(701, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-11-12 13:44:12', NULL, NULL),
(702, 'file', 'Floor plan location updated for Records Management', NULL, '2025-11-12 13:44:12', NULL, NULL),
(703, 'file', 'Floor plan location updated for Hello from room 11', NULL, '2025-11-12 13:44:12', NULL, NULL),
(704, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-11-12 13:44:12', NULL, NULL),
(705, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-11-12 13:44:12', NULL, NULL),
(706, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-11-12 13:44:12', NULL, NULL),
(707, 'file', 'Floor plan location updated for public toilet', NULL, '2025-11-12 13:44:12', NULL, NULL),
(708, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-11-12 13:45:12', NULL, NULL),
(709, 'file', 'Floor plan location swapped with S.P Staff and Records', NULL, '2025-11-12 13:45:12', NULL, NULL),
(710, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-11-12 13:45:12', NULL, NULL),
(711, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-11-12 13:45:12', NULL, NULL),
(712, 'file', 'Floor plan location updated for Door Implementation', NULL, '2025-11-12 13:45:12', NULL, NULL),
(713, 'file', 'Floor plan location updated for IT Department', NULL, '2025-11-12 13:45:12', NULL, NULL),
(714, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-11-12 13:45:12', NULL, NULL),
(715, 'file', 'Floor plan location updated for Army nu flor', NULL, '2025-11-12 13:45:12', NULL, NULL),
(716, 'file', 'Floor plan location updated for MnM\'s', NULL, '2025-11-12 13:45:12', NULL, NULL),
(717, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-11-12 13:45:12', NULL, NULL),
(718, 'file', 'Floor plan location updated for Records Management', NULL, '2025-11-12 13:45:12', NULL, NULL),
(719, 'file', 'Floor plan location updated for Hello from room 11', NULL, '2025-11-12 13:45:12', NULL, NULL),
(720, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-11-12 13:45:12', NULL, NULL),
(721, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-11-12 13:45:12', NULL, NULL),
(722, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-11-12 13:45:12', NULL, NULL),
(723, 'file', 'Floor plan location updated for public toilet', NULL, '2025-11-12 13:45:12', NULL, NULL),
(724, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-11-12 13:50:50', NULL, NULL),
(725, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-11-12 13:50:50', NULL, NULL),
(726, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-11-12 13:50:50', NULL, NULL),
(727, 'file', 'Floor plan location updated for Door Implementation', NULL, '2025-11-12 13:50:50', NULL, NULL),
(728, 'file', 'Floor plan location updated for IT Department', NULL, '2025-11-12 13:50:50', NULL, NULL),
(729, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-11-12 13:50:50', NULL, NULL),
(730, 'file', 'Floor plan location updated for Army nu flor', NULL, '2025-11-12 13:50:50', NULL, NULL),
(731, 'file', 'Floor plan location updated for MnM\'s', NULL, '2025-11-12 13:50:50', NULL, NULL),
(732, 'file', 'Floor plan location swapped with Maintenance and Facilities', NULL, '2025-11-12 13:50:50', NULL, NULL),
(733, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-11-12 13:50:50', NULL, NULL),
(734, 'file', 'Floor plan location updated for Records Management', NULL, '2025-11-12 13:50:50', NULL, NULL),
(735, 'file', 'Floor plan location updated for Hello from room 11', NULL, '2025-11-12 13:50:50', NULL, NULL),
(736, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-11-12 13:50:50', NULL, NULL),
(737, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-11-12 13:50:50', NULL, NULL),
(738, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-11-12 13:50:50', NULL, NULL),
(739, 'file', 'Floor plan location updated for public toilet', NULL, '2025-11-12 13:50:50', NULL, NULL),
(740, 'file', 'Floor plan location updated for Pubblic Toilet', NULL, '2025-11-12 13:51:39', NULL, NULL),
(741, 'file', 'Floor plan location updated for S.P Staff and Records', NULL, '2025-11-12 13:51:39', NULL, NULL),
(742, 'file', 'Floor plan location updated for Prov Library and ICTD', NULL, '2025-11-12 13:51:39', NULL, NULL),
(743, 'file', 'Floor plan location updated for Door Implementation', NULL, '2025-11-12 13:51:39', NULL, NULL),
(744, 'file', 'Floor plan location swapped with S.P Staff', NULL, '2025-11-12 13:51:39', NULL, NULL),
(745, 'file', 'Floor plan location updated for S.P Staff', NULL, '2025-11-12 13:51:39', NULL, NULL),
(746, 'file', 'Floor plan location updated for IT Department', NULL, '2025-11-12 13:51:39', NULL, NULL),
(747, 'file', 'Floor plan location updated for Army nu flor', NULL, '2025-11-12 13:51:39', NULL, NULL),
(748, 'file', 'Floor plan location updated for MnM\'s', NULL, '2025-11-12 13:51:39', NULL, NULL),
(749, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-11-12 13:51:39', NULL, NULL),
(750, 'file', 'Floor plan location updated for Records Management', NULL, '2025-11-12 13:51:39', NULL, NULL),
(751, 'file', 'Floor plan location updated for Hello from room 11', NULL, '2025-11-12 13:51:39', NULL, NULL),
(752, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-11-12 13:51:39', NULL, NULL),
(753, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-11-12 13:51:39', NULL, NULL),
(754, 'file', 'Floor plan location updated for waaay kaagih scanned', NULL, '2025-11-12 13:51:39', NULL, NULL),
(755, 'file', 'Floor plan location updated for public toilet', NULL, '2025-11-12 13:51:39', NULL, NULL),
(756, 'office', 'Office \'asd\' was deleted', NULL, '2025-11-12 13:53:47', NULL, NULL),
(757, 'office', 'Office \'Army nu flor\' was deleted', NULL, '2025-11-12 13:57:29', NULL, NULL),
(758, 'office', 'New office \'THis is 6\' added', NULL, '2025-11-12 16:01:16', NULL, NULL),
(759, 'office', 'Office \'This is room 181\' was updated', NULL, '2025-11-12 17:09:54', NULL, NULL),
(760, 'office', 'Office \'Door Implementation\' was deleted', NULL, '2025-11-12 18:55:30', NULL, NULL),
(761, 'office', 'Office \'Hello\' was deleted', NULL, '2025-11-12 18:55:31', NULL, NULL),
(762, 'office', 'Office \'Hello from room 11\' was deleted', NULL, '2025-11-12 18:55:33', NULL, NULL),
(763, 'office', 'Office \'Hello haha\' was deleted', NULL, '2025-11-12 18:55:34', NULL, NULL),
(764, 'office', 'Office \'HEllo room 5\' was deleted', NULL, '2025-11-12 18:55:35', NULL, NULL),
(765, 'office', 'Office \'Hello World\' was deleted', NULL, '2025-11-12 18:55:37', NULL, NULL),
(766, 'office', 'Office \'HEllo World from 2nd\' was deleted', NULL, '2025-11-12 18:55:38', NULL, NULL),
(767, 'office', 'Office \'IT Department\' was deleted', NULL, '2025-11-12 18:55:40', NULL, NULL),
(768, 'office', 'Office \'JAvier wifi\' was deleted', NULL, '2025-11-12 18:55:41', NULL, NULL),
(769, 'office', 'Office \'Javier Wifi2\' was deleted', NULL, '2025-11-12 18:55:43', NULL, NULL),
(770, 'office', 'Office \'kamusta tiad\' was deleted', NULL, '2025-11-12 18:55:45', NULL, NULL),
(771, 'office', 'Office \'KAY MAMA\' was deleted', NULL, '2025-11-12 18:55:47', NULL, NULL),
(772, 'office', 'Office \'Keee\' was deleted', NULL, '2025-11-12 18:55:49', NULL, NULL),
(773, 'office', 'Office \'Maintenance and Facilities\' was deleted', NULL, '2025-11-12 18:55:51', NULL, NULL),
(774, 'office', 'Office \'MnM\'s\' was deleted', NULL, '2025-11-12 18:55:52', NULL, NULL),
(775, 'office', 'Office \'Office Hours\' was deleted', NULL, '2025-11-12 18:55:54', NULL, NULL),
(776, 'office', 'Office \'Planning and Development\' was deleted', NULL, '2025-11-12 18:55:55', NULL, NULL),
(777, 'office', 'Office \'Prov Library and ICTD\' was deleted', NULL, '2025-11-12 18:55:57', NULL, NULL),
(778, 'office', 'Office \'Pubblic Toilet\' was deleted', NULL, '2025-11-12 18:55:58', NULL, NULL),
(779, 'office', 'Office \'Records Management\' was deleted', NULL, '2025-11-12 18:56:01', NULL, NULL),
(780, 'office', 'Office \'Public Relations\' was deleted', NULL, '2025-11-12 18:56:03', NULL, NULL),
(781, 'office', 'Office \'public toilet\' was deleted', NULL, '2025-11-12 18:56:04', NULL, NULL),
(782, 'office', 'Office \'S.P Staff\' was deleted', NULL, '2025-11-12 18:56:06', NULL, NULL),
(783, 'office', 'Office \'S.P Staff and Records\' was deleted', NULL, '2025-11-12 18:56:07', NULL, NULL),
(784, 'office', 'Office \'SA third ya??\' was deleted', NULL, '2025-11-12 18:56:08', NULL, NULL),
(785, 'office', 'Office \'Third 1\' was deleted', NULL, '2025-11-12 18:56:10', NULL, NULL),
(786, 'office', 'Office \'Third 4\' was deleted', NULL, '2025-11-12 18:56:11', NULL, NULL),
(787, 'office', 'Office \'THis is 6\' was deleted', NULL, '2025-11-12 18:56:13', NULL, NULL),
(788, 'office', 'Office \'This is room 181\' was deleted', NULL, '2025-11-12 18:56:15', NULL, NULL),
(789, 'office', 'Office \'waaay kaagih scanned\' was deleted', NULL, '2025-11-12 18:56:16', NULL, NULL),
(790, 'office', 'New office \'Kinder Joy\' added', NULL, '2025-11-12 18:56:31', 145, NULL),
(791, 'office', 'Office \'Stale Test Office\' was deleted', NULL, '2025-11-12 19:19:05', NULL, NULL),
(792, 'office', 'Office \'Room-12-1\' was updated', NULL, '2025-11-16 14:00:18', 145, NULL),
(793, 'office', 'New office \'Room-2-1\' added', NULL, '2025-11-16 14:02:31', 147, NULL),
(794, 'office', 'Office \'Room-2-1\' was updated', NULL, '2025-11-16 14:03:24', 147, NULL),
(795, 'file', 'Floor plan location updated for Room-2-1', NULL, '2025-11-20 11:19:40', 147, NULL),
(796, 'file', 'Floor plan location updated for Room-12-1', NULL, '2025-11-20 11:19:40', 145, NULL),
(797, 'office', 'New office \'ICTD\' added', NULL, '2025-11-22 15:15:48', 148, NULL),
(798, 'office', 'New office \'Room-12-2\' added', NULL, '2025-11-23 02:48:30', 149, NULL),
(799, 'office', 'New office \'Third\' added', NULL, '2025-11-23 13:25:33', 150, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `email`, `password`, `created_at`, `updated_at`, `status`, `last_login`) VALUES
(1, 'localhost', 'redrexjavier@gmail.com', '$2y$10$1QirVwssmeYoTv51.X0McOXrXyQQB0A3csYDaXpayyKkHzU.irpcC', '2025-04-18 15:27:30', '2025-11-20 14:31:46', 'active', '2025-05-26 17:11:48');

-- --------------------------------------------------------

--
-- Table structure for table `animated_hotspot_icons`
--

CREATE TABLE `animated_hotspot_icons` (
  `id` int(11) NOT NULL,
  `icon_name` varchar(100) NOT NULL,
  `icon_description` text DEFAULT NULL,
  `icon_category` varchar(50) DEFAULT 'general',
  `icon_file_path` varchar(255) NOT NULL,
  `icon_file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` varchar(50) DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `animated_hotspot_icons`
--

INSERT INTO `animated_hotspot_icons` (`id`, `icon_name`, `icon_description`, `icon_category`, `icon_file_path`, `icon_file_name`, `file_size`, `upload_date`, `is_active`, `created_by`) VALUES
(29, 'gif_20230208080425', 'gif_20230208080425', 'general', 'animated_hotspot_icons/gif_20230208080425_68ebd6161f4c8.gif', 'gif_20230208080425_68ebd6161f4c8.gif', 10114, '2025-10-12 16:23:50', 1, 'admin'),
(30, 'Icon', 'Icon', 'general', 'animated_hotspot_icons/icon_68f591c966b6c.gif', 'icon_68f591c966b6c.gif', 1342728, '2025-10-20 01:35:05', 1, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `animated_hotspot_videos`
--

CREATE TABLE `animated_hotspot_videos` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `duration` decimal(5,2) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `animated_hotspot_videos`
--

INSERT INTO `animated_hotspot_videos` (`id`, `name`, `filename`, `original_filename`, `file_size`, `duration`, `width`, `height`, `created_at`, `updated_at`) VALUES
(3, 'dadad', 'dadad_68e332a76da5d.mp4', 'arrow.mp4', 29099, NULL, NULL, NULL, '2025-10-06 03:08:23', '2025-10-06 03:08:23'),
(4, 'arrow.mp4', 'arrow_mp4_68e33e305f005.mp4', 'arrow.mp4', 29099, NULL, NULL, NULL, '2025-10-06 03:57:36', '2025-10-06 03:57:36');

-- --------------------------------------------------------

--
-- Table structure for table `door_qrcodes`
--

CREATE TABLE `door_qrcodes` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `door_index` int(11) NOT NULL,
  `room_id` varchar(50) NOT NULL COMMENT 'SVG room ID like room-101-1',
  `qr_code_data` text NOT NULL COMMENT 'URL encoded in QR code',
  `qr_code_image` varchar(255) NOT NULL COMMENT 'Filename of QR code image',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1 = active, 0 = inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `door_qrcodes`
--

INSERT INTO `door_qrcodes` (`id`, `office_id`, `door_index`, `room_id`, `qr_code_data`, `qr_code_image`, `is_active`, `created_at`, `updated_at`) VALUES
(50, 145, 1, 'room-12-1', 'http://localhost/gabay/mobileScreen/explore.php?door_qr=1&office_id=145&door_index=1&from_qr=1', 'Room-12-1_door_1_office_145.png', 1, '2025-11-12 18:56:52', '2025-11-23 09:49:29'),
(51, 145, 2, 'room-12-1', 'http://localhost/gabay/mobileScreen/explore.php?door_qr=1&office_id=145&door_index=2&from_qr=1', 'Room-12-1_door_2_office_145.png', 0, '2025-11-12 18:56:52', '2025-11-23 09:49:30'),
(52, 145, 3, 'room-12-1', 'http://localhost/gabay/mobileScreen/explore.php?door_qr=1&office_id=145&door_index=3&from_qr=1', 'Room-12-1_door_3_office_145.png', 0, '2025-11-12 18:56:52', '2025-11-23 09:49:30'),
(55, 148, 0, 'room-3-1', 'http://localhost/gabay/mobileScreen/explore.php?door_qr=1&office_id=148&door_index=0&from_qr=1', 'ICTD_door_0_office_148.png', 1, '2025-11-23 03:22:22', '2025-11-23 03:22:22'),
(56, 145, 0, 'room-12-1', 'http://localhost/gabay/mobileScreen/explore.php?door_qr=1&office_id=145&door_index=0&from_qr=1', 'Room-12-1_door_0_office_145.png', 1, '2025-11-23 09:49:29', '2025-11-23 09:49:29');

-- --------------------------------------------------------

--
-- Table structure for table `door_status`
--

CREATE TABLE `door_status` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `door_id` varchar(100) NOT NULL COMMENT 'Format: roomId-door-index (e.g., room-101-1-door-0)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active/open, 0 = inactive/closed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks active/inactive status of entry points (doors) for each office';

--
-- Dumping data for table `door_status`
--

INSERT INTO `door_status` (`id`, `office_id`, `door_id`, `is_active`, `created_at`, `updated_at`) VALUES
(6, 145, 'room-12-1-door-3', 0, '2025-11-23 10:53:10', '2025-11-23 10:53:10');

-- --------------------------------------------------------

--
-- Table structure for table `entrance_qrcodes`
--

CREATE TABLE `entrance_qrcodes` (
  `id` int(11) NOT NULL,
  `entrance_id` varchar(50) NOT NULL COMMENT 'Unique entrance identifier (e.g., entrance_main_1)',
  `floor` int(11) NOT NULL COMMENT 'Floor number where entrance is located',
  `label` varchar(255) NOT NULL COMMENT 'Human-readable entrance name (e.g., Main Entrance)',
  `x` decimal(10,2) NOT NULL COMMENT 'X coordinate on floor SVG',
  `y` decimal(10,2) NOT NULL COMMENT 'Y coordinate on floor SVG',
  `nearest_path_id` varchar(100) DEFAULT NULL COMMENT 'Closest walkable path ID from floor graph',
  `qr_code_data` text NOT NULL COMMENT 'QR code URL: explore.php?entrance_qr=1&entrance_id=X&floor=Y',
  `qr_code_image` varchar(255) NOT NULL COMMENT 'Filename: entrance_main_1_floor_1.png',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Toggle to activate/deactivate entrance QR',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='QR codes for building entrances - independent of offices';

--
-- Dumping data for table `entrance_qrcodes`
--

INSERT INTO `entrance_qrcodes` (`id`, `entrance_id`, `floor`, `label`, `x`, `y`, `nearest_path_id`, `qr_code_data`, `qr_code_image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'entrance_main_1', 1, 'Main Entrance', 920.00, 100.00, 'path2', 'http://192.168.254.164/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_main_1&floor=1', 'entrance_main_1_floor_1.png', 1, '2025-11-22 13:25:41', '2025-11-22 13:51:16'),
(2, 'entrance_west_1', 1, 'West Entrance', 70.00, 340.00, 'path1', 'http://192.168.254.164/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_west_1&floor=1', 'entrance_west_1_floor_1.png', 1, '2025-11-22 13:25:41', '2025-11-23 14:16:51'),
(3, 'entrance_east_1', 1, 'East Entrance', 1750.00, 215.00, 'path2', 'http://192.168.254.164/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_east_1&floor=1', 'entrance_east_1_floor_1.png', 1, '2025-11-22 13:25:41', '2025-11-23 09:29:19'),
(4, 'entrance_main_2', 2, 'Main Entrance (Floor 2)', 970.00, 300.00, 'lobby_vertical_2', 'http://192.168.254.164/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_main_2&floor=2', 'entrance_main_2_floor_2.png', 1, '2025-11-22 13:25:41', '2025-11-23 10:08:47'),
(5, 'entrance_west_2', 2, 'West Entrance (Floor 2)', 205.00, 210.00, 'path1_floor2', 'http://192.168.254.164/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_west_2&floor=2', 'entrance_west_2_floor_2.png', 1, '2025-11-22 13:25:41', '2025-11-22 13:51:16'),
(6, 'entrance_main_3', 3, 'Main Entrance (Floor 3)', 975.00, 140.00, 'lobby_vertical_2_floor3', 'http://192.168.254.164/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_main_3&floor=3', 'entrance_main_3_floor_3.png', 1, '2025-11-22 13:25:41', '2025-11-22 13:51:16'),
(7, 'entrance_west_3', 3, 'West Entrance (Floor 3)', 845.00, 140.00, 'path3_floor3', 'http://192.168.254.164/gabay/mobileScreen/explore.php?entrance_qr=1&entrance_id=entrance_west_3&floor=3', 'entrance_west_3_floor_3.png', 1, '2025-11-22 13:25:41', '2025-11-22 13:51:16');

-- --------------------------------------------------------

--
-- Table structure for table `entrance_scan_logs`
--

CREATE TABLE `entrance_scan_logs` (
  `id` int(11) NOT NULL,
  `entrance_id` varchar(50) NOT NULL COMMENT 'FK to entrance_qrcodes.entrance_id',
  `entrance_qr_id` int(11) NOT NULL COMMENT 'FK to entrance_qrcodes.id',
  `check_in_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL COMMENT 'PHP session ID for deduplication',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'Browser user agent',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPv4 or IPv6 address'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Scan logs for entrance QR codes - isolated from office statistics';

--
-- Dumping data for table `entrance_scan_logs`
--

INSERT INTO `entrance_scan_logs` (`id`, `entrance_id`, `entrance_qr_id`, `check_in_time`, `session_id`, `user_agent`, `ip_address`) VALUES
(1, 'entrance_west_1', 2, '2025-11-22 13:52:14', '6kqnuma3m7j2h66rap0arpailu', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(2, 'entrance_west_1', 2, '2025-11-22 14:49:06', 'dq2pflbht5786ad8vlfspeop15', 'Mozilla/5.0 (Linux; Android 12; CPH2113 Build/SKQ1.210216.001) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.7444.171 Mobile Safari/537.36 OPX/3.0', '192.168.254.158'),
(3, 'entrance_west_1', 2, '2025-11-22 14:50:06', '8nivbvhk28234jsr3t2mk96s2d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '192.168.254.164'),
(4, 'entrance_west_1', 2, '2025-11-22 15:21:26', 'po625f4v2tmk4vrsig4o1douqi', 'Mozilla/5.0 (Linux; Android 12; CPH2113 Build/SKQ1.210216.001) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.7444.171 Mobile Safari/537.36 OPX/3.0', '192.168.254.158'),
(5, 'entrance_west_1', 2, '2025-11-23 02:32:02', 'q971e74hf09c114fph92u94e6r', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(6, 'entrance_west_1', 2, '2025-11-23 02:33:00', 'gghv7lpp19aiupb79q9mq1f7in', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(7, 'entrance_west_1', 2, '2025-11-23 04:13:42', 'suhp7vrf1e0sapc6667nuclinv', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '::1'),
(8, 'entrance_west_1', 2, '2025-11-23 08:24:39', 'im0b8aag3uthv6427t5mksqqq5', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '::1'),
(9, 'entrance_west_1', 2, '2025-11-23 08:33:45', '29u3ta2jeb5bdneco858bfvut6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '::1'),
(10, 'entrance_west_1', 2, '2025-11-23 08:54:35', 'aujg8sgj5o0npjmtcorqhpe4mr', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '::1'),
(11, 'entrance_east_1', 3, '2025-11-23 08:55:11', '936cfckm0h4c2m0c0brqelgtth', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(12, 'entrance_east_1', 3, '2025-11-23 09:05:29', '8nivbvhk28234jsr3t2mk96s2d', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '192.168.254.164'),
(13, 'entrance_east_1', 3, '2025-11-23 09:12:11', 'dao0mi70mhghfim6qn7hq9pnm2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '192.168.254.164'),
(14, 'entrance_east_1', 3, '2025-11-23 09:16:03', '8jv40um7shltlcdsdto6co8k2c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '192.168.254.164'),
(15, 'entrance_west_1', 2, '2025-11-23 09:29:08', '936cfckm0h4c2m0c0brqelgtth', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(16, 'entrance_main_2', 4, '2025-11-23 10:08:57', '53b8q0ccco9pg6h3aahj3itbgs', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(17, 'entrance_main_1', 1, '2025-11-23 10:09:19', '53b8q0ccco9pg6h3aahj3itbgs', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(18, 'entrance_main_1', 1, '2025-11-23 10:38:38', '1db47tna5fnt9v7kps2vmrutna', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(19, 'entrance_east_1', 3, '2025-11-23 10:42:30', 'sup6opg61tinq0alikjjep248n', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '::1'),
(20, 'entrance_east_1', 3, '2025-11-23 13:04:12', 'adgbe54pmqtvspjvkatvvmhsmp', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(21, 'entrance_west_1', 2, '2025-11-23 14:05:18', 'adgbe54pmqtvspjvkatvvmhsmp', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(22, 'entrance_west_1', 2, '2025-11-23 14:17:09', 'c11vbrt56i82o76ct5lmcu4tvt', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(23, 'entrance_west_1', 2, '2025-11-23 14:17:52', '413huquo4h7cj14uip2b1hnemr', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158'),
(24, 'entrance_west_1', 2, '2025-11-23 14:20:01', 'fdilfc2qigj4rq4gviaoc4736b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '192.168.254.164'),
(25, 'entrance_west_1', 2, '2025-11-23 14:23:42', 'pkgsdj6a5ispe0gufn5c0s3ug6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '::1'),
(26, 'entrance_west_1', 2, '2025-11-23 14:43:08', 'ctdvc0et3m6sfrh6hbqj8rhvmq', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feed_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visitor_name` varchar(50) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0 COMMENT 'Archive status: 0=active, 1=archived',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete timestamp',
  `archived_at` timestamp NULL DEFAULT NULL COMMENT 'Archive timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feed_id`, `rating`, `comments`, `submitted_at`, `visitor_name`, `office_id`, `is_archived`, `deleted_at`, `archived_at`) VALUES
(4, 5, 'Excellent service and very accommodating staff.', '2025-04-19 10:53:35', 'Juan Dela Cruz', NULL, 0, NULL, NULL),
(5, 4, 'Good experience overall. Slight delay but manageable.', '2025-04-19 10:53:35', 'Maria Santos', NULL, 0, NULL, NULL),
(6, 3, 'Average service. Needs improvement in coordination.', '2025-04-19 10:53:35', 'Pedro Ramirez', NULL, 0, NULL, NULL),
(7, 5, 'Very helpful and friendly staff. Keep it up!', '2025-04-19 10:53:35', 'Ana Lopez', NULL, 0, NULL, NULL),
(8, 2, 'Had to wait too long, and there was some confusion.', '2025-04-19 10:53:35', 'Carlos Reyes', NULL, 0, NULL, NULL),
(9, 4, 'Quick response time. Appreciated the support!', '2025-04-19 11:10:21', 'Elena Garcia', NULL, 0, NULL, NULL),
(10, 3, 'Average. The staff were okay but could be more attentive.', '2025-04-19 11:12:08', 'Marco Cruz', NULL, 0, NULL, NULL),
(11, 5, 'Fantastic experience! Everyone was very professional.', '2025-04-19 11:14:55', 'Liza Aquino', NULL, 0, NULL, NULL),
(12, 2, 'Unorganized and had to wait too long.', '2025-04-19 11:17:30', 'Andres Torres', NULL, 0, NULL, NULL),
(13, 1, 'Very poor service. No one was available to assist.', '2025-04-19 11:20:00', 'Regina Flores', NULL, 0, NULL, NULL),
(14, 4, 'They addressed my concern swiftly. Satisfied.', '2025-04-19 11:22:15', 'Miguel Navarro', NULL, 0, NULL, NULL),
(15, 5, 'Excellent! Friendly staff and efficient service.', '2025-04-19 11:24:39', 'Bianca Reyes', NULL, 0, NULL, NULL),
(16, 3, 'It was okay. Not bad, not great.', '2025-04-19 11:26:11', 'Joey Mendoza', NULL, 0, NULL, NULL),
(24, 5, 'Excellent service and very helpful staff!', '2025-04-14 02:23:45', 'Juan Dela Cruz', NULL, 0, NULL, NULL),
(25, 4, 'Quick response to my concern.', '2025-04-15 01:10:30', 'Maria Santos', NULL, 0, NULL, NULL),
(26, 3, 'Average experience, nothing special.', '2025-04-16 05:45:00', 'Pedro Reyes', NULL, 0, NULL, NULL),
(27, 2, 'Long waiting time.', '2025-04-17 07:05:21', 'Anna Lopez', NULL, 0, NULL, NULL),
(28, 5, 'Very organized and clean office.', '2025-04-18 03:12:55', 'Carlos Garcia', NULL, 0, NULL, NULL),
(29, 1, 'Staff were not accommodating.', '2025-04-19 00:50:10', 'Luisa Mendoza', NULL, 0, NULL, NULL),
(30, 4, 'Great improvement from last time.', '2025-04-19 08:22:37', 'Mark Rivera', NULL, 0, NULL, NULL),
(31, 1, 'kalaw ay', '2025-05-05 06:10:57', 'azriel jed tiad', NULL, 0, NULL, NULL),
(32, 5, 'hhhhhhhhhhhhhhhhhhhhh', '2025-05-05 06:14:26', 'lalalalal', NULL, 0, NULL, NULL),
(33, 2, 'lala ang nag program', '2025-05-06 04:53:42', 'javier', NULL, 0, NULL, NULL),
(34, 3, 'gagamit kosa selpon', '2025-05-06 15:18:02', 'akoni', NULL, 0, NULL, NULL),
(35, 4, 'kakapoy pulaw HAHHA', '2025-05-06 17:30:02', 'Azriel Jed Tiad', NULL, 0, NULL, NULL),
(36, 4, 'Solid simo cous! neeed more refinement\r\n-ps', '2025-05-07 07:19:30', 'Pao', NULL, 0, NULL, NULL),
(37, 3, 'BDO We find way\r\nsalamat sa inyo', '2025-05-07 07:20:09', 'Earl', NULL, 0, NULL, NULL),
(38, 5, 'wow!', '2025-05-07 07:27:52', 'Rusty', NULL, 0, NULL, NULL),
(39, 5, 'Niceee', '2025-05-07 08:32:26', 'Jamjam', NULL, 0, NULL, NULL),
(40, 5, 'Bshshshzb', '2025-05-07 09:24:43', 'Paul', NULL, 0, NULL, NULL),
(41, 5, 'Hshs', '2025-05-08 08:03:14', 'Pao', NULL, 0, NULL, NULL),
(42, 5, 'Great service!', '2020-01-15 02:00:00', 'Visitor 1', NULL, 0, NULL, NULL),
(43, 4, 'Friendly staff.', '2020-03-20 03:30:00', 'Visitor 2', NULL, 0, NULL, NULL),
(44, 3, 'Okay experience.', '2020-06-10 06:45:00', 'Visitor 3', NULL, 0, NULL, NULL),
(45, 2, 'Needs improvement.', '2020-09-05 08:20:00', 'Visitor 4', NULL, 0, NULL, NULL),
(46, 1, 'Very slow service.', '2020-11-23 01:10:00', 'Visitor 5', NULL, 0, NULL, NULL),
(47, 4, 'Quite helpful.', '2021-01-08 00:50:00', 'Visitor 6', NULL, 0, NULL, NULL),
(48, 5, 'Fast and efficient.', '2021-04-15 05:40:00', 'Visitor 7', NULL, 0, NULL, NULL),
(49, 3, 'Average assistance.', '2021-07-02 07:25:00', 'Visitor 8', NULL, 0, NULL, NULL),
(50, 2, 'Disorganized.', '2021-10-10 04:30:00', 'Visitor 9', NULL, 0, NULL, NULL),
(51, 1, 'Unclear directions.', '2021-12-20 03:15:00', 'Visitor 10', NULL, 0, NULL, NULL),
(52, 5, 'Excellent support!', '2022-02-17 02:05:00', 'Visitor 11', NULL, 0, NULL, NULL),
(53, 4, 'Pretty smooth process.', '2022-05-04 06:00:00', 'Visitor 12', NULL, 0, NULL, NULL),
(54, 3, 'Neutral feedback.', '2022-07-21 01:35:00', 'Visitor 13', NULL, 0, NULL, NULL),
(55, 2, 'Could be better.', '2022-09-29 05:10:00', 'Visitor 14', NULL, 0, NULL, NULL),
(56, 1, 'Unpleasant experience.', '2022-12-11 08:50:00', 'Visitor 15', NULL, 0, NULL, NULL),
(57, 5, 'Very organized!', '2023-01-19 00:15:00', 'Visitor 16', NULL, 0, NULL, NULL),
(58, 4, 'Helped me a lot.', '2023-03-27 02:40:00', 'Visitor 17', NULL, 0, NULL, NULL),
(59, 3, 'Moderate experience.', '2023-06-08 04:05:00', 'Visitor 18', NULL, 0, NULL, NULL),
(60, 2, 'Staff not attentive.', '2023-08-14 06:25:00', 'Visitor 19', NULL, 0, NULL, NULL),
(61, 1, 'Had to wait long.', '2023-11-03 08:10:00', 'Visitor 20', NULL, 0, NULL, NULL),
(62, 4, 'Staff was courteous.', '2024-02-05 01:45:00', 'Visitor 21', NULL, 0, NULL, NULL),
(63, 5, 'Clean and fast.', '2024-04-10 03:55:00', 'Visitor 22', NULL, 0, NULL, NULL),
(64, 3, 'Satisfactory.', '2024-06-15 05:30:00', 'Visitor 23', NULL, 0, NULL, NULL),
(65, 2, 'Limited information.', '2024-08-20 07:00:00', 'Visitor 24', NULL, 0, NULL, NULL),
(66, 1, 'Didn’t meet expectations.', '2024-10-25 02:25:00', 'Visitor 25', NULL, 0, NULL, NULL),
(67, 5, 'Truly helpful.', '2025-01-12 00:35:00', 'Visitor 26', NULL, 0, NULL, NULL),
(68, 4, 'Polite and quick.', '2025-03-18 02:00:00', 'Visitor 27', NULL, 0, NULL, NULL),
(69, 3, 'It was fine.', '2025-05-27 04:15:00', 'Visitor 28', NULL, 1, NULL, '2025-10-20 13:41:35'),
(72, 4, 'Great experience', '2025-05-23 09:46:19', 'earl', NULL, 1, NULL, '2025-10-20 13:41:35'),
(73, 4, 'Good service', '2025-05-26 10:55:45', 'Jepoy', NULL, 1, NULL, '2025-10-20 13:41:35'),
(74, 4, 'Good service', '2025-05-26 11:19:32', 'Jopays', NULL, 1, NULL, '2025-10-20 13:41:35'),
(78, 5, 'WGHasdad', '2025-10-20 13:41:55', 'KOKAK', NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedback_archive_log`
--

CREATE TABLE `feedback_archive_log` (
  `id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `action` enum('archived','unarchived','deleted','restored') NOT NULL,
  `action_by` varchar(100) DEFAULT 'Admin User',
  `action_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Audit log for feedback archive and delete operations';

--
-- Dumping data for table `feedback_archive_log`
--

INSERT INTO `feedback_archive_log` (`id`, `feedback_id`, `action`, `action_by`, `action_at`, `notes`) VALUES
(1, 77, 'archived', 'Admin User', '2025-10-20 13:39:50', 'Archived by admin'),
(2, 77, 'deleted', 'Admin User', '2025-10-20 13:40:36', 'Moved to trash (soft delete)'),
(3, 77, 'deleted', 'Admin User', '2025-10-20 13:40:59', 'Permanently deleted'),
(4, 76, 'archived', 'Admin User', '2025-10-20 13:41:25', 'Archived by admin'),
(5, 69, 'archived', 'Admin User', '2025-10-20 13:41:35', 'Archived by admin'),
(6, 74, 'archived', 'Admin User', '2025-10-20 13:41:35', 'Archived by admin'),
(7, 73, 'archived', 'Admin User', '2025-10-20 13:41:35', 'Archived by admin'),
(8, 72, 'archived', 'Admin User', '2025-10-20 13:41:35', 'Archived by admin'),
(9, 78, 'archived', 'Admin User', '2025-10-20 13:42:08', 'Archived by admin'),
(10, 78, 'unarchived', 'Admin User', '2025-10-20 13:55:47', 'Restored from archive'),
(11, 76, 'deleted', 'Admin User', '2025-10-21 08:46:06', 'Moved to trash (soft delete)'),
(12, 76, 'deleted', 'Admin User', '2025-10-21 08:46:26', 'Permanently deleted');

-- --------------------------------------------------------

--
-- Table structure for table `floor_plan`
--

CREATE TABLE `floor_plan` (
  `id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `floor_number` int(11) DEFAULT NULL,
  `layout_image` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geofences`
--

CREATE TABLE `geofences` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether geofencing is enabled (1) or disabled (0)',
  `center_lat` decimal(10,7) NOT NULL COMMENT 'Center latitude coordinate',
  `center_lng` decimal(10,7) NOT NULL COMMENT 'Center longitude coordinate',
  `radius1` int(11) NOT NULL DEFAULT 50 COMMENT 'Zone 1 radius in meters (Main Building)',
  `radius2` int(11) NOT NULL DEFAULT 100 COMMENT 'Zone 2 radius in meters (Complex)',
  `radius3` int(11) NOT NULL DEFAULT 150 COMMENT 'Zone 3 radius in meters (Grounds)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Geofencing configuration and control';

--
-- Dumping data for table `geofences`
--

INSERT INTO `geofences` (`id`, `name`, `enabled`, `center_lat`, `center_lng`, `radius1`, `radius2`, `radius3`, `created_at`, `updated_at`) VALUES
(1, 'default', 0, 10.6703700, 122.9587200, 150, 150, 150, '2025-10-21 22:45:29', '2025-11-17 07:32:08');

-- --------------------------------------------------------

--
-- Table structure for table `geofence_access_logs`
--

CREATE TABLE `geofence_access_logs` (
  `id` int(11) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `zone_name` varchar(100) DEFAULT NULL,
  `access_granted` tinyint(1) NOT NULL,
  `distance_meters` decimal(10,2) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `geofence_access_logs`
--

INSERT INTO `geofence_access_logs` (`id`, `latitude`, `longitude`, `zone_name`, `access_granted`, `distance_meters`, `ip_address`, `user_agent`, `timestamp`) VALUES
(1, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:09:39'),
(2, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:09:41'),
(3, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:09:43'),
(4, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:09:44'),
(5, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:09:45'),
(6, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:16:42'),
(7, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:16:48'),
(8, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:16:50'),
(9, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:16:51'),
(10, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:16:52'),
(11, 10.6377700, 122.9506700, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 17:22:10'),
(12, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:24:36'),
(13, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.10, NULL, NULL, '2025-10-11 17:24:43'),
(14, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.10, NULL, NULL, '2025-10-11 17:25:40'),
(15, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.10, NULL, NULL, '2025-10-11 17:25:40'),
(16, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.10, NULL, NULL, '2025-10-11 17:31:36'),
(17, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:34:53'),
(18, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:34:55'),
(19, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:35:11'),
(20, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:35:11'),
(21, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:35:11'),
(22, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:35:18'),
(23, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:37:13'),
(24, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:37:20'),
(25, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:37:22'),
(26, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:37:23'),
(27, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:37:24'),
(28, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:37:30'),
(29, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.77, NULL, NULL, '2025-10-11 17:41:11'),
(30, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.77, NULL, NULL, '2025-10-11 17:42:45'),
(31, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:45:41'),
(32, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:45:48'),
(33, 10.3100000, 123.8900000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:47:49'),
(34, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:47:51'),
(35, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:50:11'),
(36, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:50:12'),
(37, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:50:13'),
(38, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:50:15'),
(39, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:50:15'),
(40, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:50:16'),
(41, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:55:09'),
(42, 10.6765400, 122.9506400, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:56:19'),
(43, 10.6765400, 122.9506400, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:56:23'),
(44, 10.6765000, 122.9506400, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 17:59:01'),
(45, 10.6378100, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 17:59:25'),
(46, 10.6378100, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 17:59:33'),
(47, 10.6378100, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 17:59:33'),
(48, 10.6378100, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 17:59:51'),
(49, 10.6378100, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:03:38'),
(50, 10.6378100, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:05:34'),
(51, 10.6378100, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:05:39'),
(52, 10.6378100, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:07:34'),
(53, 10.6378100, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:07:36'),
(54, 10.6764900, 122.9506400, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:08:03'),
(55, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.20, NULL, NULL, '2025-10-11 18:09:17'),
(56, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.20, NULL, NULL, '2025-10-11 18:09:22'),
(57, 10.6764900, 122.9506400, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:09:36'),
(58, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:09:51'),
(59, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:09:55'),
(60, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:11:05'),
(61, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:11:06'),
(62, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:11:08'),
(63, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:11:12'),
(64, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:11:17'),
(65, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:11:18'),
(66, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:11:18'),
(67, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:11:18'),
(68, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:11:18'),
(69, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:13:36'),
(70, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:13:37'),
(71, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.20, NULL, NULL, '2025-10-11 18:13:40'),
(72, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.20, NULL, NULL, '2025-10-11 18:13:54'),
(73, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.20, NULL, NULL, '2025-10-11 18:14:45'),
(74, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.20, NULL, NULL, '2025-10-11 18:14:47'),
(75, 10.6379515, 122.9508865, 'Main Palace Building', 1, 31.20, NULL, NULL, '2025-10-11 18:16:19'),
(76, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:16:36'),
(77, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:21:05'),
(78, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:22:23'),
(79, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:24:12'),
(80, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:24:14'),
(81, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:24:17'),
(82, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:24:35'),
(83, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:30:53'),
(84, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:31:23'),
(85, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:31:34'),
(86, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:31:39'),
(87, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:31:49'),
(88, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:32:02'),
(89, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:32:06'),
(90, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:33:08'),
(91, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:41:53'),
(92, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:42:05'),
(93, 10.6379515, 122.9508865, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:46:22'),
(94, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:46:23'),
(95, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:47:52'),
(96, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:47:52'),
(97, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:48:40'),
(98, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:48:43'),
(99, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:48:44'),
(100, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:48:45'),
(101, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:48:49'),
(102, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:48:54'),
(103, 10.6613000, 122.9506400, 'Main Palace Building', 1, 0.00, NULL, NULL, '2025-10-11 18:48:55'),
(104, 14.4309000, 120.9025000, 'Outside all zones', 0, NULL, NULL, NULL, '2025-10-11 18:49:16');

-- --------------------------------------------------------

--
-- Table structure for table `nav_path`
--

CREATE TABLE `nav_path` (
  `id` int(11) NOT NULL,
  `from_office_id` int(11) DEFAULT NULL,
  `to_office_id` int(11) DEFAULT NULL,
  `estimated_time` varchar(50) DEFAULT NULL,
  `path_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `services` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`, `details`, `contact`, `location`, `services`, `created_at`, `status`, `created_by`) VALUES
(145, 'Room-12-1', 'asdasd', 'asdad', 'room-12-1', 'asdasda', '2025-11-12 18:56:31', 'active', NULL),
(147, 'Room-2-1', 'Room Details', 'Room Contact Information', 'room-2-1', 'Room Services', '2025-11-16 14:02:31', 'active', NULL),
(148, 'ICTD', 'Details', 'Contact', 'room-3-1', 'Service', '2025-11-22 15:15:48', 'active', NULL),
(149, 'Room-12-2', 'details', '', 'room-12-2', 'service', '2025-11-23 02:48:30', 'active', NULL),
(150, 'Third', 'das', 'asdasd', 'room-3-3', 'asdasdasd', '2025-11-23 13:25:33', 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `office_hours`
--

CREATE TABLE `office_hours` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `office_image`
--

CREATE TABLE `office_image` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panorama_hotspots`
--

CREATE TABLE `panorama_hotspots` (
  `id` int(11) NOT NULL,
  `path_id` varchar(50) NOT NULL COMMENT 'Path ID from floor graph',
  `point_index` int(11) NOT NULL COMMENT 'Point index within the path',
  `floor_number` int(11) NOT NULL COMMENT 'Floor number (1, 2, 3, etc.)',
  `hotspot_id` varchar(100) NOT NULL COMMENT 'Unique identifier for the hotspot',
  `position_x` decimal(10,6) NOT NULL COMMENT '3D X position in panorama space',
  `position_y` decimal(10,6) NOT NULL COMMENT '3D Y position in panorama space',
  `position_z` decimal(10,6) NOT NULL COMMENT '3D Z position in panorama space',
  `title` varchar(200) DEFAULT NULL COMMENT 'Hotspot title/label',
  `description` text DEFAULT NULL COMMENT 'Hotspot description',
  `hotspot_type` enum('info','navigation','office','external') NOT NULL DEFAULT 'info' COMMENT 'Type of hotspot interaction',
  `target_url` varchar(500) DEFAULT NULL COMMENT 'Target URL for navigation or external links',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this hotspot is active/visible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `icon_class` varchar(100) DEFAULT 'fas fa-info-circle' COMMENT 'Font Awesome icon class for the hotspot',
  `animated_icon_id` int(11) DEFAULT NULL,
  `link_type` varchar(50) DEFAULT 'none',
  `link_path_id` varchar(100) DEFAULT NULL,
  `link_point_index` int(11) DEFAULT NULL,
  `link_floor_number` int(11) DEFAULT NULL,
  `navigation_angle` decimal(5,2) DEFAULT 0.00,
  `is_navigation` tinyint(1) DEFAULT 0,
  `rotation_x` decimal(10,6) DEFAULT 0.000000,
  `rotation_y` decimal(10,6) DEFAULT 0.000000,
  `rotation_z` decimal(10,6) DEFAULT 0.000000,
  `scale_x` decimal(10,6) DEFAULT 1.000000,
  `scale_y` decimal(10,6) DEFAULT 1.000000,
  `scale_z` decimal(10,6) DEFAULT 1.000000,
  `animation_type` varchar(50) DEFAULT 'none',
  `animation_speed` decimal(3,2) DEFAULT 1.00,
  `animation_scale` decimal(3,2) DEFAULT 1.20,
  `animation_enabled` tinyint(1) DEFAULT 0,
  `animation_delay` decimal(3,2) DEFAULT 0.00,
  `hotspot_file` varchar(255) DEFAULT NULL COMMENT 'Filename of uploaded hotspot image/GIF',
  `original_filename` varchar(255) DEFAULT NULL COMMENT 'Original filename when uploaded',
  `file_size` int(11) DEFAULT NULL COMMENT 'File size in bytes',
  `mime_type` varchar(50) DEFAULT NULL COMMENT 'MIME type (image/gif, image/png, etc.)',
  `file_uploaded_at` timestamp NULL DEFAULT NULL COMMENT 'File upload timestamp',
  `video_hotspot_id` int(11) DEFAULT NULL,
  `video_hotspot_path` varchar(500) DEFAULT NULL,
  `video_hotspot_name` varchar(255) DEFAULT NULL,
  `video_duration` decimal(5,2) DEFAULT NULL,
  `video_dimensions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`video_dimensions`)),
  `animated_icon_path` varchar(500) DEFAULT NULL,
  `animated_icon_name` varchar(255) DEFAULT NULL,
  `linked_panorama_id` int(11) DEFAULT NULL,
  `target_pitch` decimal(10,6) DEFAULT 0.000000,
  `asset_type` varchar(50) DEFAULT NULL,
  `asset_path` varchar(500) DEFAULT NULL,
  `icon_path` varchar(500) DEFAULT NULL,
  `always_visible` tinyint(1) DEFAULT 0,
  `target_path_id` varchar(50) DEFAULT NULL,
  `target_point_index` int(11) DEFAULT NULL,
  `target_floor` int(11) DEFAULT NULL,
  `target_office_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores interactive hotspots for panorama images';

--
-- Dumping data for table `panorama_hotspots`
--

INSERT INTO `panorama_hotspots` (`id`, `path_id`, `point_index`, `floor_number`, `hotspot_id`, `position_x`, `position_y`, `position_z`, `title`, `description`, `hotspot_type`, `target_url`, `is_active`, `created_at`, `updated_at`, `icon_class`, `animated_icon_id`, `link_type`, `link_path_id`, `link_point_index`, `link_floor_number`, `navigation_angle`, `is_navigation`, `rotation_x`, `rotation_y`, `rotation_z`, `scale_x`, `scale_y`, `scale_z`, `animation_type`, `animation_speed`, `animation_scale`, `animation_enabled`, `animation_delay`, `hotspot_file`, `original_filename`, `file_size`, `mime_type`, `file_uploaded_at`, `video_hotspot_id`, `video_hotspot_path`, `video_hotspot_name`, `video_duration`, `video_dimensions`, `animated_icon_path`, `animated_icon_name`, `linked_panorama_id`, `target_pitch`, `asset_type`, `asset_path`, `icon_path`, `always_visible`, `target_path_id`, `target_point_index`, `target_floor`, `target_office_id`) VALUES
(528, 'path10_floor2', 1, 2, 'marker_1_1761039566814', 0.045679, -0.008164, -9.999892, 'Go to A', 'Navigate to A on Floor 2 (path10_floor2)', 'navigation', '', 1, '2025-10-21 09:39:28', '2025-10-21 09:39:28', 'fas fa-info-circle', 30, 'panorama', 'path10_floor2', 0, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'animated_hotspot_icons/icon_68f591c966b6c.gif', 'Icon', NULL, 0.000000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(530, 'path10_floor2', 5, 2, 'marker_1_1761039857141', 1.091975, 0.030889, -9.940153, 'Go to B', 'Navigate to B on Floor 2 (path10_floor2)', 'navigation', '', 1, '2025-10-21 09:44:20', '2025-10-21 09:44:20', 'fas fa-info-circle', 30, 'panorama', 'path10_floor2', 1, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'animated_hotspot_icons/icon_68f591c966b6c.gif', 'Icon', NULL, 0.000000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(532, 'path2', 0, 1, 'marker_1_1761074309790', 0.069971, -0.006410, -9.999753, 'Go to BBB', 'Navigate to BBB on Floor 1 (path1)', 'navigation', '', 1, '2025-10-21 19:18:32', '2025-10-21 19:18:32', 'fas fa-info-circle', 30, 'panorama', 'path1', 5, 1, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'animated_hotspot_icons/icon_68f591c966b6c.gif', 'Icon', NULL, 0.000000, NULL, NULL, NULL, 0, 'path1', 5, 1, NULL),
(536, 'path1', 5, 1, 'marker_1_1761074335987', 0.026494, -0.003674, -9.999964, 'Go to AAAA', 'Navigate to AAAA on Floor 1 (path2)', 'navigation', '', 1, '2025-10-21 23:40:53', '2025-10-21 23:40:53', 'fas fa-info-circle', 30, 'panorama', 'path2', 0, 1, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'animated_hotspot_icons/icon_68f591c966b6c.gif', 'Icon', NULL, 0.000000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(537, 'path1', 5, 1, 'marker_1_1761090012355', 0.079029, 0.015214, -9.999676, 'Go to A', 'Navigate to A on Floor 2 (path10_floor2)', 'navigation', '', 1, '2025-10-21 23:40:53', '2025-10-21 23:40:53', 'fas fa-info-circle', 30, 'panorama', 'path10_floor2', 0, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'animated_hotspot_icons/icon_68f591c966b6c.gif', 'Icon', NULL, 0.000000, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(541, 'path17_floor2', 0, 2, 'marker_1_1763802210955', 0.085244, 0.054212, -9.999490, 'Go to 3rd jam', 'Navigate to 3rd jam on Floor 3 (path_central_exclusive_floor3)', 'navigation', '', 1, '2025-11-22 09:03:34', '2025-11-22 09:03:34', 'fas fa-info-circle', 30, 'panorama', 'path_central_exclusive_floor3', 1, 3, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'animated_hotspot_icons/icon_68f591c966b6c.gif', 'Icon', NULL, 0.000000, NULL, NULL, NULL, 0, 'path_central_exclusive_floor3', 1, 3, NULL),
(542, 'path_central_exclusive_floor3', 1, 3, 'marker_1_1763802263771', 1.013066, 0.019632, -9.948533, 'Go to 2nd floor pao', 'Navigate to 2nd floor pao on Floor 2 (path17_floor2)', 'navigation', '', 1, '2025-11-22 09:04:28', '2025-11-22 09:04:28', 'fas fa-info-circle', 30, 'panorama', 'path17_floor2', 0, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'animated_hotspot_icons/icon_68f591c966b6c.gif', 'Icon', NULL, 0.000000, NULL, NULL, NULL, 0, 'path17_floor2', 0, 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `panorama_image`
--

CREATE TABLE `panorama_image` (
  `id` int(11) NOT NULL,
  `path_id` varchar(50) NOT NULL COMMENT 'References the path ID from floor_graph.json (e.g., path1, path2)',
  `point_index` int(11) NOT NULL COMMENT 'Index of the point within the path (0-based)',
  `point_x` decimal(10,2) NOT NULL COMMENT 'X coordinate of the panorama point',
  `point_y` decimal(10,2) NOT NULL COMMENT 'Y coordinate of the panorama point',
  `floor_number` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Floor number (1, 2, 3, etc.)',
  `image_filename` varchar(255) NOT NULL COMMENT 'Filename of the panorama image (stored in Pano/ directory)',
  `original_filename` varchar(255) DEFAULT NULL COMMENT 'Original filename when uploaded',
  `title` varchar(100) DEFAULT NULL COMMENT 'Optional title/description for the panorama',
  `description` text DEFAULT NULL COMMENT 'Optional detailed description',
  `file_size` int(11) DEFAULT NULL COMMENT 'File size in bytes',
  `mime_type` varchar(50) DEFAULT NULL COMMENT 'MIME type of the image (image/jpeg, image/png, etc.)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this panorama is active/visible',
  `uploaded_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who uploaded (future use)',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Upload timestamp',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update timestamp',
  `current_path_id` int(11) DEFAULT NULL,
  `is_path_start` tinyint(1) DEFAULT 0,
  `is_path_end` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores panorama images for point markers with isPano attribute';

--
-- Dumping data for table `panorama_image`
--

INSERT INTO `panorama_image` (`id`, `path_id`, `point_index`, `point_x`, `point_y`, `floor_number`, `image_filename`, `original_filename`, `title`, `description`, `file_size`, `mime_type`, `is_active`, `uploaded_by`, `uploaded_at`, `updated_at`, `current_path_id`, `is_path_start`, `is_path_end`, `status`) VALUES
(122, 'path10_floor2', 0, 1036.00, 255.00, 2, 'pano_path10_floor2_0_1761039414_68f75436b0553.jpg', '788a962f-4385-428a-a379-fb0efe4e8a35.jpg', 'A', 'A', 380276, 'image/jpeg', 1, NULL, '2025-10-21 09:36:54', '2025-10-21 09:36:54', NULL, 0, 0, 'active'),
(123, 'path10_floor2', 1, 1078.00, 255.00, 2, 'pano_path10_floor2_1_1761039431_68f75447ca521.png', 'path5_floor2_point1_floor2.png', 'B', 'B', 536, 'image/png', 1, NULL, '2025-10-21 09:37:11', '2025-10-21 09:37:11', NULL, 0, 0, 'active'),
(124, 'path10_floor2', 5, 1397.00, 305.00, 2, 'pano_path10_floor2_5_1761039462_68f75466b1bca.jpg', 'pano_path1_8_1760923990_68f59156e6d9b.jpg', 'C', 'C', 379578, 'image/jpeg', 1, NULL, '2025-10-21 09:37:42', '2025-10-21 09:37:42', NULL, 0, 0, 'active'),
(129, 'path1', 5, 165.00, 340.00, 1, 'pano_path1_5_1761076529_68f7e531820d1.png', 'Keee_127.png', 'Keeee', NULL, 407, 'image/png', 1, NULL, '2025-10-21 19:55:29', '2025-10-21 19:55:29', NULL, 0, 0, 'active'),
(133, 'path1', 8, 100.00, 340.00, 1, 'pano_path1_8_1761227981_68fa34cd5de52.jpg', '84a15d0e-5a22-4a45-b9be-6f33b7114185.jpg', 'HHAA', 'asasd', 361505, 'image/jpeg', 1, NULL, '2025-10-23 13:59:41', '2025-10-23 13:59:41', NULL, 0, 0, 'active'),
(136, 'path2', 11, 1084.00, 215.00, 1, 'pano_path2_11_1762616197_690f6385b6646.png', 'path2_point11_floor1.png', 'asdsad', 'dasdasdasd', 547, 'image/png', 1, NULL, '2025-11-08 15:36:37', '2025-11-08 15:36:37', NULL, 0, 0, 'active'),
(137, 'path17_floor2', 0, 1660.00, 180.00, 2, 'pano_path17_floor2_0_1763802154_69217c2a63720.jpg', '582079393_1172755324232939_6120783085968096778_n.jpg', '2nd floor pao', NULL, 296287, 'image/jpeg', 1, NULL, '2025-11-22 09:02:34', '2025-11-22 09:02:34', NULL, 0, 0, 'active'),
(138, 'path_central_exclusive_floor3', 1, 1770.00, 174.00, 3, 'pano_path_central_exclusive_floor3_1_1763802177_69217c413d37d.jpg', '581808854_1160794548970689_2318475193435279600_n.jpg', '3rd jam', NULL, 335782, 'image/jpeg', 1, NULL, '2025-11-22 09:02:57', '2025-11-22 09:02:57', NULL, 0, 0, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `panorama_images`
--

CREATE TABLE `panorama_images` (
  `id` int(11) NOT NULL,
  `path_id` varchar(50) NOT NULL,
  `point_index` int(11) NOT NULL,
  `floor_number` int(11) NOT NULL,
  `image_filename` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panorama_interaction_logs`
--

CREATE TABLE `panorama_interaction_logs` (
  `id` int(11) NOT NULL,
  `hotspot_id` int(11) DEFAULT NULL,
  `panorama_id` int(11) DEFAULT NULL,
  `interaction_type` enum('click','navigate','view','office_view') NOT NULL DEFAULT 'click',
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panorama_navigation_paths`
--

CREATE TABLE `panorama_navigation_paths` (
  `id` int(11) NOT NULL,
  `path_name` varchar(100) NOT NULL,
  `path_description` text DEFAULT NULL,
  `floor` int(11) NOT NULL,
  `start_panorama_id` int(11) DEFAULT NULL,
  `end_panorama_id` int(11) DEFAULT NULL,
  `total_steps` int(11) DEFAULT 0,
  `estimated_minutes` int(11) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panorama_path_steps`
--

CREATE TABLE `panorama_path_steps` (
  `id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL,
  `panorama_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL,
  `next_panorama_id` int(11) DEFAULT NULL,
  `hotspot_yaw` decimal(6,2) NOT NULL,
  `hotspot_pitch` decimal(6,2) DEFAULT 0.00,
  `step_description` varchar(255) DEFAULT NULL,
  `auto_advance_seconds` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panorama_qrcodes`
--

CREATE TABLE `panorama_qrcodes` (
  `id` int(11) NOT NULL,
  `path_id` varchar(50) NOT NULL,
  `point_index` int(11) NOT NULL,
  `floor_number` int(11) NOT NULL DEFAULT 1,
  `qr_filename` varchar(255) NOT NULL,
  `mobile_url` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `panorama_qrcodes`
--

INSERT INTO `panorama_qrcodes` (`id`, `path_id`, `point_index`, `floor_number`, `qr_filename`, `mobile_url`, `created_at`, `updated_at`) VALUES
(67, 'path3_floor3', 0, 3, 'path3_floor3_point0_floor3.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path3_floor3_point:0_floor:3&from_qr=1', '2025-10-11 06:09:01', '2025-10-11 06:09:02'),
(76, 'path10_floor2', 1, 2, 'path10_floor2_point1_floor2.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path10_floor2_point:1_floor:2&from_qr=1', '2025-10-21 09:02:34', '2025-10-21 09:37:13'),
(77, 'lobby_vertical_2', 1, 2, 'lobby_vertical_2_point1_floor2.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:lobby_vertical_2_point:1_floor:2&from_qr=1', '2025-10-21 09:06:22', '2025-10-21 09:06:23'),
(79, 'path2', 2, 1, 'path2_point2_floor1.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path2_point:2_floor:1&from_qr=1', '2025-10-21 09:16:30', '2025-10-21 09:16:31'),
(80, 'path2', 1, 1, 'path2_point1_floor1.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path2_point:1_floor:1&from_qr=1', '2025-10-21 09:17:53', '2025-10-21 09:17:54'),
(82, 'path10_floor2', 0, 2, 'path10_floor2_point0_floor2.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path10_floor2_point:0_floor:2&from_qr=1', '2025-10-21 09:36:54', '2025-10-21 09:36:55'),
(83, 'path10_floor2', 5, 2, 'path10_floor2_point5_floor2.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path10_floor2_point:5_floor:2&from_qr=1', '2025-10-21 09:37:42', '2025-10-21 09:37:43'),
(86, 'path1', 5, 1, 'panorama_floor1_path1_point5.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path1_point:5_floor:1&from_qr=1', '2025-10-21 19:55:29', '2025-10-21 19:55:29'),
(90, 'path1', 8, 1, 'path1_point8_floor1.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path1_point:8_floor:1&from_qr=1', '2025-10-23 13:59:41', '2025-10-23 13:59:41'),
(93, 'path2', 11, 1, 'path2_point11_floor1.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path2_point:11_floor:1&from_qr=1', '2025-11-08 15:36:37', '2025-11-08 15:36:38'),
(94, 'path17_floor2', 0, 2, 'path17_floor2_point0_floor2.png', 'http://localhost/gabay/mobileScreen/explore.php?scanned_panorama=path_id:path17_floor2_point:0_floor:2&from_qr=1', '2025-11-22 09:02:34', '2025-11-22 09:02:34'),
(95, 'path_central_exclusive_floor3', 1, 3, 'path_central_exclusive_floor3_point1_floor3.png', 'http://localhost/gabay/mobileScreen/explore.php?scanned_panorama=path_id:path_central_exclusive_floor3_point:1_floor:3&from_qr=1', '2025-11-22 09:02:57', '2025-11-22 09:02:57');

-- --------------------------------------------------------

--
-- Table structure for table `panorama_qr_scans`
--

CREATE TABLE `panorama_qr_scans` (
  `id` int(11) NOT NULL,
  `qr_id` int(11) NOT NULL,
  `scan_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `panorama_qr_scans`
--

INSERT INTO `panorama_qr_scans` (`id`, `qr_id`, `scan_timestamp`, `user_agent`, `ip_address`) VALUES
(165, 67, '2025-10-11 11:24:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '::1'),
(225, 90, '2025-11-08 15:38:01', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '192.168.254.158');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `admin_id`, `token`, `expiry`, `used`, `created_at`) VALUES
(1, 1, 'ee73cbbd73784494543a2df3fdf89c3432210264ec7a9d704b1fb4f3b0e3469f', '2025-11-17 16:18:55', 1, '2025-11-17 07:18:55');

-- --------------------------------------------------------

--
-- Table structure for table `qrcode_info`
--

CREATE TABLE `qrcode_info` (
  `id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `qr_code_data` text DEFAULT NULL,
  `qr_code_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `qrcode_info`
--

INSERT INTO `qrcode_info` (`id`, `office_id`, `qr_code_data`, `qr_code_image`, `is_active`, `created_at`) VALUES
(288, 145, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=145', 'Kinder_Joy_145.png', 1, '2025-11-12 18:56:31'),
(290, 147, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=147', 'Room-2-1_147.png', 1, '2025-11-16 14:02:32'),
(291, 148, 'https://192.168.254.164/gabay/mobileScreen/explore.php?office_id=148', 'ICTD_148.png', 1, '2025-11-22 15:15:48'),
(292, 149, 'https://192.168.254.164/gabay/mobileScreen/explore.php?office_id=149', 'Room-12-2_149.png', 1, '2025-11-23 02:48:30'),
(293, 150, 'https://192.168.254.164/gabay/mobileScreen/explore.php?office_id=150', 'Third_150.png', 1, '2025-11-23 13:25:33');

-- --------------------------------------------------------

--
-- Table structure for table `qr_scan_logs`
--

CREATE TABLE `qr_scan_logs` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `door_index` int(11) DEFAULT NULL COMMENT 'Index of the door scanned (NULL for legacy office-level scans)',
  `qr_type` enum('office','panorama') DEFAULT 'office',
  `panorama_id` varchar(255) DEFAULT NULL,
  `location_context` varchar(255) DEFAULT NULL,
  `qr_code_id` int(11) NOT NULL,
  `check_in_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `qr_scan_logs`
--

INSERT INTO `qr_scan_logs` (`id`, `office_id`, `door_index`, `qr_type`, `panorama_id`, `location_context`, `qr_code_id`, `check_in_time`) VALUES
(1982, 145, NULL, 'office', NULL, NULL, 288, '2025-11-12 18:57:11'),
(1983, 145, 0, 'office', NULL, NULL, 288, '2025-11-12 19:13:05'),
(1991, 145, 0, 'office', NULL, NULL, 288, '2025-11-16 14:02:00'),
(1992, 148, 0, 'office', NULL, NULL, 291, '2025-11-23 03:22:52'),
(1993, 148, 0, 'office', NULL, NULL, 291, '2025-11-23 09:29:09'),
(1994, 145, 0, 'office', NULL, NULL, 288, '2025-11-23 09:49:57'),
(1995, 145, 0, 'office', NULL, NULL, 288, '2025-11-23 10:55:40');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_activities`
--

CREATE TABLE `visitor_activities` (
  `id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `qr_code_id` int(11) DEFAULT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activities_ibfk_2` (`admin_id`),
  ADD KEY `activities_ibfk_1` (`office_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `animated_hotspot_icons`
--
ALTER TABLE `animated_hotspot_icons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `icon_name` (`icon_name`),
  ADD KEY `category_idx` (`icon_category`),
  ADD KEY `active_idx` (`is_active`);

--
-- Indexes for table `animated_hotspot_videos`
--
ALTER TABLE `animated_hotspot_videos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `door_qrcodes`
--
ALTER TABLE `door_qrcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_door` (`office_id`,`door_index`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `door_status`
--
ALTER TABLE `door_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_office_door` (`office_id`,`door_id`),
  ADD KEY `idx_office_id` (`office_id`),
  ADD KEY `idx_door_id` (`door_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `entrance_qrcodes`
--
ALTER TABLE `entrance_qrcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_entrance_id` (`entrance_id`),
  ADD KEY `idx_floor` (`floor`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `entrance_scan_logs`
--
ALTER TABLE `entrance_scan_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entrance_id` (`entrance_id`),
  ADD KEY `idx_entrance_qr_id` (`entrance_qr_id`),
  ADD KEY `idx_check_in_time` (`check_in_time`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feed_id`),
  ADD KEY `fk_feedback_office` (`office_id`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `feedback_archive_log`
--
ALTER TABLE `feedback_archive_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedback_id` (`feedback_id`),
  ADD KEY `idx_action_at` (`action_at`);

--
-- Indexes for table `floor_plan`
--
ALTER TABLE `floor_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_floorplan_office` (`office_id`);

--
-- Indexes for table `geofences`
--
ALTER TABLE `geofences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `geofence_access_logs`
--
ALTER TABLE `geofence_access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_access` (`access_granted`);

--
-- Indexes for table `nav_path`
--
ALTER TABLE `nav_path`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_navpath_from_office` (`from_office_id`),
  ADD KEY `fk_navpath_to_office` (`to_office_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_offices_created_by` (`created_by`);

--
-- Indexes for table `office_hours`
--
ALTER TABLE `office_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `office_hours_ibfk_1` (`office_id`);

--
-- Indexes for table `office_image`
--
ALTER TABLE `office_image`
  ADD PRIMARY KEY (`id`),
  ADD KEY `office_id` (`office_id`);

--
-- Indexes for table `panorama_hotspots`
--
ALTER TABLE `panorama_hotspots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_hotspot_per_point` (`path_id`,`point_index`,`floor_number`,`hotspot_id`),
  ADD KEY `idx_panorama_point` (`path_id`,`point_index`,`floor_number`),
  ADD KEY `idx_floor_number` (`floor_number`),
  ADD KEY `idx_hotspot_type` (`hotspot_type`),
  ADD KEY `animated_icon_id` (`animated_icon_id`),
  ADD KEY `idx_video_hotspot_id` (`video_hotspot_id`);

--
-- Indexes for table `panorama_image`
--
ALTER TABLE `panorama_image`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_path_point` (`path_id`,`point_index`,`floor_number`),
  ADD KEY `idx_floor_number` (`floor_number`),
  ADD KEY `idx_coordinates` (`point_x`,`point_y`),
  ADD KEY `idx_path_id` (`path_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_current_path_id` (`current_path_id`);

--
-- Indexes for table `panorama_images`
--
ALTER TABLE `panorama_images`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_panorama` (`path_id`,`point_index`,`floor_number`);

--
-- Indexes for table `panorama_interaction_logs`
--
ALTER TABLE `panorama_interaction_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hotspot_id` (`hotspot_id`),
  ADD KEY `idx_panorama_id` (`panorama_id`),
  ADD KEY `idx_interaction_type` (`interaction_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `panorama_navigation_paths`
--
ALTER TABLE `panorama_navigation_paths`
  ADD PRIMARY KEY (`id`),
  ADD KEY `start_panorama_id` (`start_panorama_id`),
  ADD KEY `end_panorama_id` (`end_panorama_id`);

--
-- Indexes for table `panorama_path_steps`
--
ALTER TABLE `panorama_path_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_path_step` (`path_id`,`step_order`),
  ADD KEY `next_panorama_id` (`next_panorama_id`),
  ADD KEY `idx_panorama_path` (`panorama_id`,`path_id`);

--
-- Indexes for table `panorama_qrcodes`
--
ALTER TABLE `panorama_qrcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_panorama` (`path_id`,`point_index`,`floor_number`);

--
-- Indexes for table `panorama_qr_scans`
--
ALTER TABLE `panorama_qr_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qr_id` (`qr_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `admin_id` (`admin_id`),
  ADD KEY `expiry` (`expiry`),
  ADD KEY `idx_token_expiry` (`token`,`expiry`,`used`);

--
-- Indexes for table `qrcode_info`
--
ALTER TABLE `qrcode_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qrcode_info_ibfk_1` (`office_id`);

--
-- Indexes for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qr_scan_logs_ibfk_1` (`office_id`),
  ADD KEY `qr_scan_logs_ibfk_2` (`qr_code_id`),
  ADD KEY `idx_office_door` (`office_id`,`door_index`);

--
-- Indexes for table `visitor_activities`
--
ALTER TABLE `visitor_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_visitoractivity_office` (`office_id`),
  ADD KEY `fk_qrcode` (`qr_code_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=800;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `animated_hotspot_icons`
--
ALTER TABLE `animated_hotspot_icons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `animated_hotspot_videos`
--
ALTER TABLE `animated_hotspot_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `door_qrcodes`
--
ALTER TABLE `door_qrcodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `door_status`
--
ALTER TABLE `door_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `entrance_qrcodes`
--
ALTER TABLE `entrance_qrcodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `entrance_scan_logs`
--
ALTER TABLE `entrance_scan_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `feedback_archive_log`
--
ALTER TABLE `feedback_archive_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `floor_plan`
--
ALTER TABLE `floor_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `geofences`
--
ALTER TABLE `geofences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `geofence_access_logs`
--
ALTER TABLE `geofence_access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `nav_path`
--
ALTER TABLE `nav_path`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `office_hours`
--
ALTER TABLE `office_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `office_image`
--
ALTER TABLE `office_image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `panorama_hotspots`
--
ALTER TABLE `panorama_hotspots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=543;

--
-- AUTO_INCREMENT for table `panorama_image`
--
ALTER TABLE `panorama_image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `panorama_images`
--
ALTER TABLE `panorama_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `panorama_interaction_logs`
--
ALTER TABLE `panorama_interaction_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `panorama_navigation_paths`
--
ALTER TABLE `panorama_navigation_paths`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `panorama_path_steps`
--
ALTER TABLE `panorama_path_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `panorama_qrcodes`
--
ALTER TABLE `panorama_qrcodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `panorama_qr_scans`
--
ALTER TABLE `panorama_qr_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `qrcode_info`
--
ALTER TABLE `qrcode_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=294;

--
-- AUTO_INCREMENT for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1996;

--
-- AUTO_INCREMENT for table `visitor_activities`
--
ALTER TABLE `visitor_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `door_qrcodes`
--
ALTER TABLE `door_qrcodes`
  ADD CONSTRAINT `door_qrcodes_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `door_status`
--
ALTER TABLE `door_status`
  ADD CONSTRAINT `fk_door_status_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `entrance_scan_logs`
--
ALTER TABLE `entrance_scan_logs`
  ADD CONSTRAINT `entrance_scan_logs_ibfk_1` FOREIGN KEY (`entrance_qr_id`) REFERENCES `entrance_qrcodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `floor_plan`
--
ALTER TABLE `floor_plan`
  ADD CONSTRAINT `fk_floorplan_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `floor_plan_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nav_path`
--
ALTER TABLE `nav_path`
  ADD CONSTRAINT `fk_navpath_from_office` FOREIGN KEY (`from_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_navpath_to_office` FOREIGN KEY (`to_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `nav_path_ibfk_1` FOREIGN KEY (`from_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `nav_path_ibfk_2` FOREIGN KEY (`to_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `fk_offices_created_by` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`);

--
-- Constraints for table `office_hours`
--
ALTER TABLE `office_hours`
  ADD CONSTRAINT `office_hours_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `office_image`
--
ALTER TABLE `office_image`
  ADD CONSTRAINT `office_image_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `panorama_hotspots`
--
ALTER TABLE `panorama_hotspots`
  ADD CONSTRAINT `panorama_hotspots_ibfk_1` FOREIGN KEY (`animated_icon_id`) REFERENCES `animated_hotspot_icons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `panorama_hotspots_ibfk_2` FOREIGN KEY (`animated_icon_id`) REFERENCES `animated_hotspot_icons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `panorama_hotspots_ibfk_3` FOREIGN KEY (`animated_icon_id`) REFERENCES `animated_hotspot_icons` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `panorama_navigation_paths`
--
ALTER TABLE `panorama_navigation_paths`
  ADD CONSTRAINT `panorama_navigation_paths_ibfk_1` FOREIGN KEY (`start_panorama_id`) REFERENCES `panorama_image` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `panorama_navigation_paths_ibfk_2` FOREIGN KEY (`end_panorama_id`) REFERENCES `panorama_image` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `panorama_path_steps`
--
ALTER TABLE `panorama_path_steps`
  ADD CONSTRAINT `panorama_path_steps_ibfk_1` FOREIGN KEY (`path_id`) REFERENCES `panorama_navigation_paths` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panorama_path_steps_ibfk_2` FOREIGN KEY (`panorama_id`) REFERENCES `panorama_image` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panorama_path_steps_ibfk_3` FOREIGN KEY (`next_panorama_id`) REFERENCES `panorama_image` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `panorama_qr_scans`
--
ALTER TABLE `panorama_qr_scans`
  ADD CONSTRAINT `panorama_qr_scans_ibfk_1` FOREIGN KEY (`qr_id`) REFERENCES `panorama_qrcodes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qrcode_info`
--
ALTER TABLE `qrcode_info`
  ADD CONSTRAINT `fk_qrcode_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `qrcode_info_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  ADD CONSTRAINT `qr_scan_logs_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `qr_scan_logs_ibfk_2` FOREIGN KEY (`qr_code_id`) REFERENCES `qrcode_info` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `visitor_activities`
--
ALTER TABLE `visitor_activities`
  ADD CONSTRAINT `fk_qrcode` FOREIGN KEY (`qr_code_id`) REFERENCES `qrcode_info` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_visitoractivity_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `visitor_activities_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
