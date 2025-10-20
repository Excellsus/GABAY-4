-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2025 at 03:12 PM
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
(245, 'office', 'New office \'Human Resources\' added', NULL, '2025-05-27 22:42:16', 96, NULL),
(246, 'office', 'Office \'umayyy\' was deleted', NULL, '2025-05-27 22:42:22', NULL, NULL),
(247, 'office', 'New office \'Information Technology\' added', NULL, '2025-05-27 22:43:30', 97, NULL),
(248, 'office', 'New office \'Public Relations\' added', NULL, '2025-05-27 22:44:27', 98, NULL),
(249, 'office', 'New office \'Legal Affairs\' added', NULL, '2025-05-27 22:45:18', 99, NULL),
(250, 'office', 'New office \'Procurement\' added', NULL, '2025-05-27 22:46:06', 100, NULL),
(251, 'office', 'New office \'Records Management\' added', NULL, '2025-05-27 22:47:10', 101, NULL),
(252, 'office', 'Office \'Procurement\' was updated', NULL, '2025-05-27 22:47:22', 100, NULL),
(253, 'office', 'New office \'Customer Service\' added', NULL, '2025-05-27 22:48:40', 102, NULL),
(254, 'office', 'New office \'Maintenance and Facilities\' added', NULL, '2025-05-27 22:52:12', 103, NULL),
(255, 'office', 'New office \'Planning and Development\' added', NULL, '2025-05-27 22:56:49', 104, NULL),
(256, 'office', 'New office \'Internal Audit\' added', NULL, '2025-05-27 22:57:48', 105, NULL),
(257, 'office', 'Office \'Customer Service\' was updated', NULL, '2025-05-27 23:07:58', 102, NULL),
(258, 'feedback', 'New feedback received', NULL, '2025-09-08 15:50:57', NULL, NULL),
(259, 'office', 'New office \'Room 1\' added', NULL, '2025-09-11 18:03:29', NULL, NULL),
(260, 'file', 'Floor plan location updated for Room 1', NULL, '2025-09-14 08:38:57', NULL, NULL),
(261, 'file', 'Floor plan location updated for Procurement', NULL, '2025-09-14 08:38:57', 100, NULL),
(262, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-09-14 08:38:57', 99, NULL),
(263, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-09-14 08:38:57', 105, NULL),
(264, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-09-14 08:38:57', 96, NULL),
(265, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-09-14 08:38:57', 97, NULL),
(266, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-09-14 08:38:57', 98, NULL),
(267, 'file', 'Floor plan location updated for Records Management', NULL, '2025-09-14 08:38:57', 101, NULL),
(268, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-09-14 08:38:57', 104, NULL),
(269, 'file', 'Floor plan location updated for Customer Service', NULL, '2025-09-14 08:38:57', 102, NULL),
(270, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-09-14 08:38:57', 103, NULL),
(271, 'file', 'Floor plan location updated for Room 1', NULL, '2025-09-14 08:39:04', NULL, NULL),
(272, 'file', 'Floor plan location updated for Procurement', NULL, '2025-09-14 08:39:04', 100, NULL),
(273, 'file', 'Floor plan location updated for Legal Affairs', NULL, '2025-09-14 08:39:04', 99, NULL),
(274, 'file', 'Floor plan location updated for Internal Audit', NULL, '2025-09-14 08:39:04', 105, NULL),
(275, 'file', 'Floor plan location swapped with Information Technology', NULL, '2025-09-14 08:39:04', 96, NULL),
(276, 'file', 'Floor plan location updated for Information Technology', NULL, '2025-09-14 08:39:04', 97, NULL),
(277, 'file', 'Floor plan location updated for Human Resources', NULL, '2025-09-14 08:39:04', 96, NULL),
(278, 'file', 'Floor plan location updated for Public Relations', NULL, '2025-09-14 08:39:04', 98, NULL),
(279, 'file', 'Floor plan location updated for Records Management', NULL, '2025-09-14 08:39:04', 101, NULL),
(280, 'file', 'Floor plan location updated for Planning and Development', NULL, '2025-09-14 08:39:04', 104, NULL),
(281, 'file', 'Floor plan location updated for Customer Service', NULL, '2025-09-14 08:39:04', 102, NULL),
(282, 'file', 'Floor plan location updated for Maintenance and Facilities', NULL, '2025-09-14 08:39:04', 103, NULL),
(283, 'office', 'New office \'Room 11\' added', NULL, '2025-09-20 13:52:04', NULL, NULL),
(284, 'office', 'Office \'Room 1\' was deleted', NULL, '2025-09-23 10:01:18', NULL, NULL),
(285, 'office', 'Office \'Room 11\' was deleted', NULL, '2025-09-23 10:01:20', NULL, NULL),
(286, 'office', 'New office \'AHaya\' added', NULL, '2025-10-02 13:32:52', NULL, NULL),
(287, 'office', 'Office \'Customer Service\' was updated', NULL, '2025-10-03 02:09:52', 102, NULL),
(288, 'office', 'Office \'AHaya\' was updated', NULL, '2025-10-03 02:10:18', NULL, NULL),
(289, 'office', 'Office \'Information Technology\' was updated', NULL, '2025-10-03 03:40:00', 97, NULL),
(290, 'office', 'Office \'AHaya\' was deleted', NULL, '2025-10-10 05:13:59', NULL, NULL),
(291, 'office', 'New office \'cvfdddffddfdf\' added', NULL, '2025-10-10 05:14:32', 109, NULL),
(292, 'office', 'New office \'gwapo\' added', NULL, '2025-10-10 10:25:28', 110, NULL),
(293, 'qr_code', 'QR code for office \'Customer Service\' was deactivated', NULL, '2025-10-11 02:04:18', 102, NULL),
(294, 'qr_code', 'QR code for office \'Legal Affairs\' was deactivated', NULL, '2025-10-11 02:06:21', 99, NULL),
(295, 'office', 'New office \'waaay kaagih scanned\' added', NULL, '2025-10-11 02:27:38', 111, NULL),
(296, 'qr_code', 'QR code for office \'Customer Service\' was activated', NULL, '2025-10-11 02:30:40', 102, NULL),
(297, 'office', 'New office \'kakapoy\' added', NULL, '2025-10-11 11:26:35', 112, NULL);

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
(1, 'admin_user', 'admin@negros-occ.gov.ph', '$2y$10$rKyVt62WWQQ/499PFx4kG.lkoFsXwM.gb0lrAlm1oV8rnQI6MU1N2', '2025-04-18 15:27:30', '2025-05-26 17:11:48', 'active', '2025-05-26 17:11:48');

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
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feed_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visitor_name` varchar(50) NOT NULL,
  `office_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feed_id`, `rating`, `comments`, `submitted_at`, `visitor_name`, `office_id`) VALUES
(4, 5, 'Excellent service and very accommodating staff.', '2025-04-19 10:53:35', 'Juan Dela Cruz', NULL),
(5, 4, 'Good experience overall. Slight delay but manageable.', '2025-04-19 10:53:35', 'Maria Santos', NULL),
(6, 3, 'Average service. Needs improvement in coordination.', '2025-04-19 10:53:35', 'Pedro Ramirez', NULL),
(7, 5, 'Very helpful and friendly staff. Keep it up!', '2025-04-19 10:53:35', 'Ana Lopez', NULL),
(8, 2, 'Had to wait too long, and there was some confusion.', '2025-04-19 10:53:35', 'Carlos Reyes', NULL),
(9, 4, 'Quick response time. Appreciated the support!', '2025-04-19 11:10:21', 'Elena Garcia', NULL),
(10, 3, 'Average. The staff were okay but could be more attentive.', '2025-04-19 11:12:08', 'Marco Cruz', NULL),
(11, 5, 'Fantastic experience! Everyone was very professional.', '2025-04-19 11:14:55', 'Liza Aquino', NULL),
(12, 2, 'Unorganized and had to wait too long.', '2025-04-19 11:17:30', 'Andres Torres', NULL),
(13, 1, 'Very poor service. No one was available to assist.', '2025-04-19 11:20:00', 'Regina Flores', NULL),
(14, 4, 'They addressed my concern swiftly. Satisfied.', '2025-04-19 11:22:15', 'Miguel Navarro', NULL),
(15, 5, 'Excellent! Friendly staff and efficient service.', '2025-04-19 11:24:39', 'Bianca Reyes', NULL),
(16, 3, 'It was okay. Not bad, not great.', '2025-04-19 11:26:11', 'Joey Mendoza', NULL),
(24, 5, 'Excellent service and very helpful staff!', '2025-04-14 02:23:45', 'Juan Dela Cruz', NULL),
(25, 4, 'Quick response to my concern.', '2025-04-15 01:10:30', 'Maria Santos', NULL),
(26, 3, 'Average experience, nothing special.', '2025-04-16 05:45:00', 'Pedro Reyes', NULL),
(27, 2, 'Long waiting time.', '2025-04-17 07:05:21', 'Anna Lopez', NULL),
(28, 5, 'Very organized and clean office.', '2025-04-18 03:12:55', 'Carlos Garcia', NULL),
(29, 1, 'Staff were not accommodating.', '2025-04-19 00:50:10', 'Luisa Mendoza', NULL),
(30, 4, 'Great improvement from last time.', '2025-04-19 08:22:37', 'Mark Rivera', NULL),
(31, 1, 'kalaw ay', '2025-05-05 06:10:57', 'azriel jed tiad', NULL),
(32, 5, 'hhhhhhhhhhhhhhhhhhhhh', '2025-05-05 06:14:26', 'lalalalal', NULL),
(33, 2, 'lala ang nag program', '2025-05-06 04:53:42', 'javier', NULL),
(34, 3, 'gagamit kosa selpon', '2025-05-06 15:18:02', 'akoni', NULL),
(35, 4, 'kakapoy pulaw HAHHA', '2025-05-06 17:30:02', 'Azriel Jed Tiad', NULL),
(36, 4, 'Solid simo cous! neeed more refinement\r\n-ps', '2025-05-07 07:19:30', 'Pao', NULL),
(37, 3, 'BDO We find way\r\nsalamat sa inyo', '2025-05-07 07:20:09', 'Earl', NULL),
(38, 5, 'wow!', '2025-05-07 07:27:52', 'Rusty', NULL),
(39, 5, 'Niceee', '2025-05-07 08:32:26', 'Jamjam', NULL),
(40, 5, 'Bshshshzb', '2025-05-07 09:24:43', 'Paul', NULL),
(41, 5, 'Hshs', '2025-05-08 08:03:14', 'Pao', NULL),
(42, 5, 'Great service!', '2020-01-15 02:00:00', 'Visitor 1', NULL),
(43, 4, 'Friendly staff.', '2020-03-20 03:30:00', 'Visitor 2', NULL),
(44, 3, 'Okay experience.', '2020-06-10 06:45:00', 'Visitor 3', NULL),
(45, 2, 'Needs improvement.', '2020-09-05 08:20:00', 'Visitor 4', NULL),
(46, 1, 'Very slow service.', '2020-11-23 01:10:00', 'Visitor 5', NULL),
(47, 4, 'Quite helpful.', '2021-01-08 00:50:00', 'Visitor 6', NULL),
(48, 5, 'Fast and efficient.', '2021-04-15 05:40:00', 'Visitor 7', NULL),
(49, 3, 'Average assistance.', '2021-07-02 07:25:00', 'Visitor 8', NULL),
(50, 2, 'Disorganized.', '2021-10-10 04:30:00', 'Visitor 9', NULL),
(51, 1, 'Unclear directions.', '2021-12-20 03:15:00', 'Visitor 10', NULL),
(52, 5, 'Excellent support!', '2022-02-17 02:05:00', 'Visitor 11', NULL),
(53, 4, 'Pretty smooth process.', '2022-05-04 06:00:00', 'Visitor 12', NULL),
(54, 3, 'Neutral feedback.', '2022-07-21 01:35:00', 'Visitor 13', NULL),
(55, 2, 'Could be better.', '2022-09-29 05:10:00', 'Visitor 14', NULL),
(56, 1, 'Unpleasant experience.', '2022-12-11 08:50:00', 'Visitor 15', NULL),
(57, 5, 'Very organized!', '2023-01-19 00:15:00', 'Visitor 16', NULL),
(58, 4, 'Helped me a lot.', '2023-03-27 02:40:00', 'Visitor 17', NULL),
(59, 3, 'Moderate experience.', '2023-06-08 04:05:00', 'Visitor 18', NULL),
(60, 2, 'Staff not attentive.', '2023-08-14 06:25:00', 'Visitor 19', NULL),
(61, 1, 'Had to wait long.', '2023-11-03 08:10:00', 'Visitor 20', NULL),
(62, 4, 'Staff was courteous.', '2024-02-05 01:45:00', 'Visitor 21', NULL),
(63, 5, 'Clean and fast.', '2024-04-10 03:55:00', 'Visitor 22', NULL),
(64, 3, 'Satisfactory.', '2024-06-15 05:30:00', 'Visitor 23', NULL),
(65, 2, 'Limited information.', '2024-08-20 07:00:00', 'Visitor 24', NULL),
(66, 1, 'Didn’t meet expectations.', '2024-10-25 02:25:00', 'Visitor 25', NULL),
(67, 5, 'Truly helpful.', '2025-01-12 00:35:00', 'Visitor 26', NULL),
(68, 4, 'Polite and quick.', '2025-03-18 02:00:00', 'Visitor 27', NULL),
(69, 3, 'It was fine.', '2025-05-27 04:15:00', 'Visitor 28', NULL),
(72, 4, 'Great experience', '2025-05-23 09:46:19', 'earl', NULL),
(73, 4, 'Good service', '2025-05-26 10:55:45', 'Jepoy', NULL),
(74, 4, 'Good service', '2025-05-26 11:19:32', 'Jopays', NULL),
(76, 4, 'Namit', '2025-09-08 15:50:57', 'Hello', NULL);

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
(96, 'Human Resources', 'Handles recruitment, employee relations, benefits administration, and training programs for staff.', 'hr@organization.gov', 'room-7-1', 'Job postings and recruitment\r\n\r\nEmployee onboarding and orientation\r\n\r\nPayroll and benefits management\r\n\r\nTraining and development programs\r\n\r\nEmployee records and compliance', '2025-05-27 22:42:16', 'active', NULL),
(97, 'Information Technology', 'Manages the organization’s computer systems, networks, and software infrastructure.', 'it@organization.gov', 'room-6-1', 'Technical support and troubleshooting\r\n\r\nNetwork and data security\r\n\r\nHardware and software maintenance\r\n\r\nSystem upgrades and installations\r\n\r\nUser account management\r\n\r\nahay', '2025-05-27 22:43:30', 'active', NULL),
(98, 'Public Relations', 'Handles media relations, public announcements, and community engagement.', 'pr@organization.gov', 'room-12-1', 'Press releases and media coordination\r\n\r\nEvent planning and coverage\r\n\r\nSocial media management\r\n\r\nPublic feedback handling\r\n\r\nCommunity outreach programs', '2025-05-27 22:44:27', 'active', NULL),
(99, 'Legal Affairs', 'Provides legal support, drafts contracts, and ensures compliance with laws and regulations.', ' legal@organization.gov', 'room-3-1', 'Legal consultations and advice\r\n\r\nContract drafting and review\r\n\r\nCompliance monitoring\r\n\r\nDispute resolution\r\n\r\nLiaison with external legal counsel', '2025-05-27 22:45:18', 'active', NULL),
(100, 'Procurement', 'Responsible for acquiring goods and services required by the organization.', 'procurement@organization.gov', 'room-2-1', 'Supplier evaluation and management\r\n\r\nPurchase order processing\r\n\r\nInventory control\r\n\r\nBidding and quotation evaluation\r\n\r\nContract negotiations', '2025-05-27 22:46:06', 'active', NULL),
(101, 'Records Management', 'Maintains and secures organizational records and archives.', 'records@organization.gov', 'room-10-1', 'Document filing and retrieval\r\n\r\nRecords retention and disposal\r\n\r\nDigital archiving\r\n\r\nRecords classification and indexing\r\n\r\nCompliance with data privacy regulations', '2025-05-27 22:47:10', 'active', NULL),
(102, 'Customer Service', 'Assists visitors and stakeholders with inquiries, requests, and general support.', '09668903760', 'room-14-1', 'Information desk services\r\n\r\nComplaint handling\r\n\r\nApplication assistance\r\n\r\nGeneral inquiries\r\n\r\nFeedback collection', '2025-05-27 22:48:40', 'active', NULL),
(103, 'Maintenance and Facilities', 'Ensures the cleanliness, safety, and functionality of the physical work environment.', 'maintenance@organization.gov', 'room-15-1', 'Building and equipment maintenance\r\n\r\nJanitorial services\r\n\r\nUtilities management\r\n\r\nFacility repairs\r\n\r\nSafety inspections', '2025-05-27 22:52:12', 'active', NULL),
(104, 'Planning and Development', 'Focuses on strategic initiatives, project development, and infrastructure planning.', 'planning@organization.gov', 'room-13-1', 'Project planning and implementation\r\n\r\nData analysis and research\r\n\r\nDevelopment proposals\r\n\r\nUrban and regional planning\r\n\r\nPolicy formulation', '2025-05-27 22:56:49', 'active', NULL),
(105, 'Internal Audit', 'Conducts independent assessments of processes to ensure accountability and transparency.', 'audit@organization.gov', 'room-4-1', 'Internal control evaluations\r\n\r\nRisk assessment\r\n\r\nOperational audits\r\n\r\nFinancial audits\r\n\r\nCompliance reviews', '2025-05-27 22:57:48', 'active', NULL),
(109, 'cvfdddffddfdf', 'dadada', '', 'room-16-1', '', '2025-10-10 05:14:32', 'active', NULL),
(110, 'gwapo', 'dadadada', '', 'room-1-1', 'adhada\r\ndadadad', '2025-10-10 10:25:28', 'active', NULL),
(111, 'waaay kaagih scanned', 'adadad', '', 'room-11-1', 'dadadadadad', '2025-10-11 02:27:38', 'active', NULL),
(112, 'kakapoy', 'dada', 'adad', 'room-9-1', 'adad', '2025-10-11 11:26:35', 'active', NULL);

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

--
-- Dumping data for table `office_hours`
--

INSERT INTO `office_hours` (`id`, `office_id`, `day_of_week`, `open_time`, `close_time`) VALUES
(35, 96, 'Wednesday', '07:00:00', '17:00:00'),
(36, 97, 'Wednesday', '07:00:00', '19:00:00'),
(37, 105, 'Wednesday', '07:00:00', '20:00:00'),
(38, 99, 'Wednesday', '07:00:00', '09:00:00'),
(39, 103, 'Wednesday', '07:56:00', '22:00:00'),
(40, 104, 'Wednesday', '07:00:00', '19:00:00'),
(41, 100, 'Wednesday', '08:00:00', '19:11:00'),
(42, 98, 'Wednesday', '10:00:00', '17:00:00'),
(43, 101, 'Wednesday', '06:00:00', '14:00:00'),
(44, 102, 'Wednesday', '07:00:00', '17:00:00'),
(46, 109, 'Friday', '14:16:00', '17:20:00');

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

--
-- Dumping data for table `office_image`
--

INSERT INTO `office_image` (`id`, `office_id`, `image_path`, `uploaded_at`) VALUES
(24, 96, 'office_68363fc8b154b6.26054442.jpeg', '2025-05-28 06:42:16'),
(25, 97, 'office_68364012711943.16973858.jpeg', '2025-05-28 06:43:30'),
(26, 98, 'office_6836404bf12721.49346085.webp', '2025-05-28 06:44:27'),
(27, 99, 'office_6836407eb6e470.30083009.jpeg', '2025-05-28 06:45:18'),
(28, 101, 'office_683640ee4332d1.34464981.jpeg', '2025-05-28 06:47:10'),
(29, 100, 'office_683640fb01d307.11312821.jpeg', '2025-05-28 06:47:23'),
(30, 102, 'office_68364148713b25.84557867.jpeg', '2025-05-28 06:48:40'),
(31, 103, 'office_6836421c3e78a2.36671589.jpeg', '2025-05-28 06:52:12'),
(32, 104, 'office_68364331b7e7e9.81121001.jpeg', '2025-05-28 06:56:49'),
(33, 105, 'office_6836436cef3305.06982716.webp', '2025-05-28 06:57:48'),
(34, 109, 'office_68e89638d30256.97429930.jpeg', '2025-10-10 13:14:32'),
(35, 110, 'office_68e8df18661174.68695652.jpeg', '2025-10-10 18:25:28'),
(36, 111, 'office_68e9c09a932419.53848712.webp', '2025-10-11 10:27:38'),
(37, 112, 'office_68ea3eeb236477.27913193.jpeg', '2025-10-11 19:26:35');

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
  `animated_icon_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores interactive hotspots for panorama images';

--
-- Dumping data for table `panorama_hotspots`
--

INSERT INTO `panorama_hotspots` (`id`, `path_id`, `point_index`, `floor_number`, `hotspot_id`, `position_x`, `position_y`, `position_z`, `title`, `description`, `hotspot_type`, `target_url`, `is_active`, `created_at`, `updated_at`, `icon_class`, `animated_icon_id`, `link_type`, `link_path_id`, `link_point_index`, `link_floor_number`, `navigation_angle`, `is_navigation`, `rotation_x`, `rotation_y`, `rotation_z`, `scale_x`, `scale_y`, `scale_z`, `animation_type`, `animation_speed`, `animation_scale`, `animation_enabled`, `animation_delay`, `hotspot_file`, `original_filename`, `file_size`, `mime_type`, `file_uploaded_at`, `video_hotspot_id`, `video_hotspot_path`, `video_hotspot_name`, `video_duration`, `video_dimensions`, `animated_icon_path`, `animated_icon_name`) VALUES
(412, 'path3_floor2', 3, 2, 'marker_1_1759829118219', 0.005662, 0.003575, -9.999998, 'Go to 222222222222222222', 'Navigate to 222222222222222222 on Floor 1 (path1)', 'navigation', '', 1, '2025-10-07 09:25:39', '2025-10-07 09:25:39', 'fas fa-info-circle', NULL, 'panorama', 'path3_floor2', 5, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(413, 'path3_floor2', 3, 2, 'marker_1_1759829137724', 0.159315, -0.058018, -9.998563, 'Go to 6666666666666666', 'Navigate to 6666666666666666 on Floor 1 (path2)', 'navigation', '', 1, '2025-10-07 09:25:39', '2025-10-07 09:25:39', 'fas fa-info-circle', NULL, 'panorama', 'path2', 17, 1, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(416, 'path1', 5, 1, 'marker_1_1759828551921', 1.087283, -0.011008, -9.940709, 'video_1759761556_68e3d49443f20', 'Same Floor Navigation Hotspot', 'navigation', '', 1, '2025-10-07 09:35:33', '2025-10-07 09:35:33', 'fas fa-info-circle', NULL, 'panorama', 'path1', 8, 1, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(417, 'path1', 5, 1, 'marker_1_1759829668629', 1.067094, -0.005886, -9.942901, 'video_1759761556_68e3d49443f20', 'Same Floor Navigation Hotspot', 'navigation', '', 1, '2025-10-07 09:35:33', '2025-10-07 09:35:33', 'fas fa-info-circle', NULL, 'panorama', 'path1', 8, 1, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(422, 'path2', 17, 1, 'marker_1_1759830024354', 0.026620, 0.003535, -9.999964, 'Go to 7655757', 'Navigate to 7655757 on Floor 2 (path3_floor2)', 'navigation', '', 1, '2025-10-07 09:40:30', '2025-10-07 09:40:30', 'fas fa-info-circle', NULL, 'panorama', 'path3_floor2', 2, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(428, 'path1', 4, 1, 'marker_1_1759832464654', 0.007448, -0.019280, -9.999979, 'Go to 21212121212121212', 'Navigate to 21212121212121212 on Floor 2 (path3_floor2)', 'navigation', '', 1, '2025-10-07 10:21:06', '2025-10-07 10:21:06', 'fas fa-info-circle', NULL, 'panorama', 'path3_floor2', 3, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(429, 'path1', 8, 1, 'marker_2_1760073106768', 0.546109, -0.042670, -9.984986, 'Go to 7655757', 'Navigate to 7655757 on Floor 2 (path3_floor2)', 'navigation', '', 1, '2025-10-10 05:11:48', '2025-10-10 05:11:48', 'fas fa-info-circle', NULL, 'panorama', 'path3_floor2', 2, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(438, 'path10_floor2', 5, 2, 'marker_1_1760079935745', 0.035948, 0.004388, -9.999934, 'Go to kokodakdoadoaodoad', 'Navigate to kokodakdoadoaodoad on Floor 2 (path17_floor2)', 'navigation', '', 1, '2025-10-10 07:07:37', '2025-10-10 07:07:37', 'fas fa-info-circle', NULL, 'panorama', 'path10_floor2', 8, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(439, 'path10_floor2', 5, 2, 'marker_1_1760080056135', 1.062954, -0.186841, -9.941590, 'Go to kokodakdoadoaodoad', 'Navigate to kokodakdoadoaodoad on Floor 2 (path17_floor2)', 'navigation', '', 1, '2025-10-10 07:07:37', '2025-10-10 07:07:37', 'fas fa-info-circle', NULL, 'panorama', 'path17_floor2', 0, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(444, 'path17_floor2', 0, 2, 'marker_1_1760079952445', 0.167448, 0.012745, -9.998590, 'Go to kakkakakakkakakaka', 'Navigate to kakkakakakkakakaka on Floor 2 (path10_floor2)', 'navigation', '', 1, '2025-10-10 07:09:21', '2025-10-10 07:09:21', 'fas fa-info-circle', NULL, 'panorama', 'path17_floor2', 5, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(445, 'path17_floor2', 0, 2, 'marker_1_1760080159578', 0.047511, -0.010352, -9.999882, 'Go to 7655757', 'Navigate to 7655757 on Floor 2 (path3_floor2)', 'navigation', '', 1, '2025-10-10 07:09:21', '2025-10-10 07:09:21', 'fas fa-info-circle', NULL, 'panorama', 'path3_floor2', 2, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(446, 'path3_floor2', 2, 2, 'marker_1_1760078131201', 0.062100, 0.017036, -9.999793, 'Go to 222222222222222222', 'Navigate to 222222222222222222 on Floor 1 (path1)', 'navigation', '', 1, '2025-10-10 07:10:11', '2025-10-10 07:10:11', 'fas fa-info-circle', NULL, 'panorama', 'path3_floor2', 5, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(447, 'path3_floor2', 2, 2, 'marker_1_1760078206357', 0.871916, -0.027253, -9.961878, 'Go to 21212121212121212', 'Navigate to 21212121212121212 on Floor 2 (path3_floor2)', 'navigation', '', 1, '2025-10-10 07:10:11', '2025-10-10 07:10:11', 'fas fa-info-circle', NULL, 'panorama', 'path3_floor2', 5, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(448, 'path3_floor2', 2, 2, 'marker_1_1760080006707', 1.075894, 0.153299, -9.940772, 'Go to kakkakakakkakakaka', 'Navigate to kakkakakakkakakaka on Floor 2 (path10_floor2)', 'navigation', '', 1, '2025-10-10 07:10:11', '2025-10-10 07:10:11', 'fas fa-info-circle', NULL, 'panorama', 'path3_floor2', 5, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(449, 'path3_floor2', 2, 2, 'marker_1_1760080209156', 0.674233, -0.099330, -9.976750, 'Go to kooooooooooooooooooooooooooooooooooooooo', 'Navigate to kooooooooooooooooooooooooooooooooooooooo on Floor 2 (path17_floor2)', 'navigation', '', 1, '2025-10-10 07:10:11', '2025-10-10 07:10:11', 'fas fa-info-circle', NULL, 'panorama', 'path17_floor2', 0, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(467, 'connector_path3_to_lobby', 1, 2, 'marker_1_1760085857663', 0.016983, -0.002082, -9.999985, 'Go to basta2', 'Navigate to basta2 on Floor 2 (path10_floor2)', 'navigation', '', 1, '2025-10-10 08:44:19', '2025-10-10 08:44:19', 'fas fa-info-circle', NULL, 'panorama', 'path10_floor2', 0, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(474, 'path10_floor2', 0, 2, 'marker_1_1760086790235', 0.012532, 0.017530, -9.999977, 'Go to basta3', 'Navigate to basta3 on Floor 2 (path10_floor2)', 'navigation', '', 1, '2025-10-10 09:11:05', '2025-10-10 09:11:05', 'fas fa-info-circle', NULL, 'panorama', 'path10_floor2', 5, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(475, 'path10_floor2', 0, 2, 'marker_1_1760087462982', 0.012532, -0.003875, -9.999991, 'Go to basta3', 'Navigate to basta3 on Floor 2 (path10_floor2)', 'navigation', '', 1, '2025-10-10 09:11:05', '2025-10-10 09:11:05', 'fas fa-info-circle', NULL, 'panorama', 'path10_floor2', 1, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(486, 'path10_floor2', 1, 2, 'marker_1_1760087569180', 0.016983, 0.006839, -9.999983, 'Go to basta2', 'Navigate to basta2 on Floor 2 (path10_floor2)', 'navigation', '', 1, '2025-10-10 09:13:42', '2025-10-10 09:13:42', 'fas fa-info-circle', NULL, 'panorama', 'path10_floor2', 5, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(487, 'path10_floor2', 1, 2, 'marker_1_1760087597776', 0.014315, -0.067344, -9.999763, 'Go to 7655757', 'Navigate to 7655757 on Floor 2 (path3_floor2)', 'navigation', '', 1, '2025-10-10 09:13:42', '2025-10-10 09:13:42', 'fas fa-info-circle', NULL, 'panorama', 'path3_floor2', 2, 2, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL),
(488, 'path10_floor2', 1, 2, 'marker_2_1760087621217', 0.014017, 0.081419, -9.999659, 'Go to 222222222222222222', 'Navigate to 222222222222222222 on Floor 1 (path1)', 'navigation', '', 1, '2025-10-10 09:13:42', '2025-10-10 09:13:42', 'fas fa-info-circle', NULL, 'panorama', 'path1', 5, 1, 0.00, 1, 0.000000, 0.000000, 0.000000, 1.000000, 1.000000, 1.000000, 'none', 1.00, 1.20, 0, 0.00, NULL, NULL, NULL, NULL, NULL, 0, 'uploads/videos/video_1759761556_68e3d49443f20.mp4', 'video_1759761556_68e3d49443f20', NULL, NULL, NULL, NULL);

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
(79, 'path3_floor2', 2, 314.00, 295.00, 2, 'pano_path3_floor2_2_1759830006_68e4dff641a7d.jpg', 'd556273e-a9db-4acc-be89-4a41077e9de1.jpg', '7655757', '75757575', 77949, 'image/jpeg', 1, NULL, '2025-10-07 09:40:06', '2025-10-07 09:40:06', NULL, 0, 0, 'active'),
(80, 'path1', 4, 165.00, 255.00, 1, 'pano_path1_4_1759832445_68e4e97d21f3e.jpg', '552757791_4278580192427466_532254028001704487_n.jpg', 'gwapo', 'hahah', 179130, 'image/jpeg', 1, NULL, '2025-10-07 10:20:45', '2025-10-07 10:20:45', NULL, 0, 0, 'active'),
(81, 'path10_floor2', 5, 1397.00, 310.00, 2, 'pano_path10_floor2_5_1760079887_68e8b00f77d97.jpg', '508153222_2173372119772057_7944795502159278386_n.jpg', 'kakkakakakkakakaka', 'dadadadadadadad', 104248, 'image/jpeg', 1, NULL, '2025-10-10 07:04:47', '2025-10-10 07:04:47', NULL, 0, 0, 'active'),
(83, 'path17_floor2', 0, 1660.00, 180.00, 2, 'pano_path17_floor2_0_1760080148_68e8b114d8183.jpg', 'e114a30b8d4474592b6f75c631244446.jpg', 'kooooooooooooooooooooooooooooooooooooooo', 'kooooooooooooooooooooooooooooooooo', 320235, 'image/jpeg', 1, NULL, '2025-10-10 07:09:08', '2025-10-10 07:09:08', NULL, 0, 0, 'active'),
(92, 'connector_path3_to_lobby', 1, 906.00, 255.00, 2, 'pano_connector_path3_to_lobby_1_1760085559_68e8c637e5450.jpg', 'e114a30b8d4474592b6f75c631244446.jpg', 'basta1', 'dadadad', 320235, 'image/jpeg', 1, NULL, '2025-10-10 08:39:19', '2025-10-10 08:39:19', NULL, 0, 0, 'active'),
(94, 'path10_floor2', 0, 1036.00, 255.00, 2, 'pano_path10_floor2_0_1760087434_68e8cd8a89f09.jpg', 'CollierReview_hero_image_01312022.jpg', 'basta2', NULL, 105589, 'image/jpeg', 1, NULL, '2025-10-10 09:10:34', '2025-10-10 09:10:34', NULL, 0, 0, 'active'),
(95, 'path10_floor2', 1, 1078.00, 255.00, 2, 'pano_path10_floor2_1_1760087446_68e8cd968eca8.webp', 'TEC-PO-TW-NanShanPlaza-OfficeSuiteWithCityView.webp', 'basta3', NULL, 77254, 'image/webp', 1, NULL, '2025-10-10 09:10:46', '2025-10-10 09:10:46', NULL, 0, 0, 'active'),
(97, 'path1', 1, 130.00, 175.00, 1, 'pano_path1_1_1760155263_68e9d67fb62c6.jpg', '552395513_640587865577290_716814951309371099_n.jpg', 'lallalal', 'dadadad', 181466, 'image/jpeg', 1, NULL, '2025-10-11 04:01:03', '2025-10-11 04:01:03', NULL, 0, 0, 'active'),
(98, 'path2', 12, 1184.00, 215.00, 1, 'pano_path2_12_1760155355_68e9d6dba9836.jpg', '552689658_801399072483066_4688523880866883308_n.jpg', 'pppppp', 'pppp', 176480, 'image/jpeg', 1, NULL, '2025-10-11 04:02:35', '2025-10-11 04:02:35', NULL, 0, 0, 'active'),
(99, 'path2', 20, 1778.00, 110.00, 1, 'pano_path2_20_1760155404_68e9d70cd60b8.jpg', '552689658_801399072483066_4688523880866883308_n.jpg', 'mmmmmmmmmmmmmmmmm', 'nnnnnnnnnnnnnnn', 176480, 'image/jpeg', 1, NULL, '2025-10-11 04:03:24', '2025-10-11 04:03:24', NULL, 0, 0, 'active'),
(100, 'path2', 10, 1045.00, 215.00, 1, 'pano_path2_10_1760155917_68e9d90d8f5ac.jpg', '552616637_4295499990732470_945254161669472552_n.jpg', 'gggggggggggggggggggggg', 'ggggggggggggggggg', 172829, 'image/jpeg', 1, NULL, '2025-10-11 04:11:57', '2025-10-11 04:11:57', NULL, 0, 0, 'active'),
(101, 'path2', 0, 467.00, 220.00, 1, 'pano_path2_0_1760156550_68e9db86eb60a.jpg', '554733176_1301726238108377_2119382253373556624_n.jpg', 'dadadadad', 'dadadada', 169778, 'image/jpeg', 1, NULL, '2025-10-11 04:22:30', '2025-10-11 04:22:30', NULL, 0, 0, 'active'),
(102, 'path2', 18, 1778.00, 215.00, 1, 'pano_path2_18_1760156588_68e9dbac80bf2.png', 'panorama_qr_floor2_pathpath3_floor2_point2.png', 'daaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaa', 474, 'image/png', 1, NULL, '2025-10-11 04:23:08', '2025-10-11 04:23:08', NULL, 0, 0, 'active'),
(103, 'path1_floor2', 0, 170.00, 185.00, 2, 'pano_path1_floor2_0_1760157369_68e9deb95c0f9.jpg', '552395513_640587865577290_716814951309371099_n.jpg', 'tttttttttttttttttttttttttttttt', 'tttttttttttttttttttttttttttttttttt', 181466, 'image/jpeg', 1, NULL, '2025-10-11 04:36:09', '2025-10-11 04:36:09', NULL, 0, 0, 'active'),
(104, 'path1', 8, 100.00, 340.00, 1, 'pano_path1_8_1760157431_68e9def79ee55.jpg', '552757791_4278580192427466_532254028001704487_n.jpg', 'aaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaa', 179130, 'image/jpeg', 1, NULL, '2025-10-11 04:37:11', '2025-10-11 04:37:11', NULL, 0, 0, 'active'),
(105, 'path1', 5, 165.00, 340.00, 1, 'pano_path1_5_1760157958_68e9e106293d7.jpg', '552616637_4295499990732470_945254161669472552_n.jpg', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaaa', 172829, 'image/jpeg', 1, NULL, '2025-10-11 04:45:58', '2025-10-11 04:45:58', NULL, 0, 0, 'active'),
(106, 'path3_floor3', 0, 845.00, 140.00, 3, 'pano_path3_floor3_0_1760162941_68e9f47d51627.jpg', '554733176_1301726238108377_2119382253373556624_n.jpg', 'hdhdhdhdh', 'hdhdhdhd', 169778, 'image/jpeg', 1, NULL, '2025-10-11 06:09:01', '2025-10-11 06:09:01', NULL, 0, 0, 'active');

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

--
-- Dumping data for table `panorama_images`
--

INSERT INTO `panorama_images` (`id`, `path_id`, `point_index`, `floor_number`, `image_filename`, `title`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'path1', 0, 1, 'pano_path1_5_1757952260_68c83904dc6da.jpg', 'Main Entrance', 'Panoramic view of the main entrance area', 1, '2025-10-03 08:05:30', '2025-10-03 10:02:21'),
(2, 'corridor2', 3, 2, 'sample_panorama_2.jpg', 'Second Floor Hallway', 'View of the second floor corridor', 1, '2025-10-03 08:05:30', '2025-10-03 08:05:30');

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
(65, 'path1', 8, 1, 'path1_point8_floor1.png', 'http://192.168.1.33/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path1_point:8_floor:1&from_qr=1', '2025-10-11 04:37:11', '2025-10-11 04:39:45'),
(66, 'path1', 5, 1, 'path1_point5_floor1.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path1_point:5_floor:1&from_qr=1', '2025-10-11 04:45:58', '2025-10-11 04:45:59'),
(67, 'path3_floor3', 0, 3, 'path3_floor3_point0_floor3.png', 'http://localhost/FinalDev/mobileScreen/explore.php?scanned_panorama=path_id:path3_floor3_point:0_floor:3&from_qr=1', '2025-10-11 06:09:01', '2025-10-11 06:09:02');

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
(162, 65, '2025-10-11 04:46:24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '::1'),
(163, 66, '2025-10-11 04:47:01', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '::1'),
(164, 66, '2025-10-11 05:05:21', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '192.168.1.12'),
(165, 67, '2025-10-11 11:24:05', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '::1'),
(166, 65, '2025-10-11 11:24:40', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '192.168.1.12'),
(167, 65, '2025-10-11 11:32:19', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '192.168.1.33');

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
(239, 96, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=96', 'Human_Resources_96.png', 1, '2025-05-27 22:42:16'),
(240, 97, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=97', 'Information_Technology_97.png', 1, '2025-05-27 22:43:30'),
(241, 98, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=98', 'Public_Relations_98.png', 1, '2025-05-27 22:44:27'),
(242, 99, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=99', 'Legal_Affairs_99.png', 0, '2025-05-27 22:45:18'),
(243, 100, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=100', 'Procurement_100.png', 1, '2025-05-27 22:46:06'),
(244, 101, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=101', 'Records_Management_101.png', 1, '2025-05-27 22:47:10'),
(245, 102, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=102', 'Customer_Service_102.png', 1, '2025-05-27 22:48:40'),
(246, 103, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=103', 'Maintenance_and_Facilities_103.png', 1, '2025-05-27 22:52:12'),
(247, 104, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=104', 'Planning_and_Development_104.png', 1, '2025-05-27 22:56:49'),
(248, 105, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=105', 'Internal_Audit_105.png', 1, '2025-05-27 22:57:48'),
(252, 109, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=109', 'cvfdddffddfdf_109.png', 1, '2025-10-10 05:14:32'),
(253, 110, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=110', 'gwapo_110.png', 1, '2025-10-10 10:25:28'),
(254, 111, 'http://localhost/FinalDev/mobileScreen/qr_location_check.php?office_id=111', 'waaay_kaagih_scanned_111.png', 1, '2025-10-11 02:27:38'),
(255, 112, 'http://10.79.189.249/FinalDev/mobileScreen/explore.php?office_id=112', 'kakapoy_112.png', 1, '2025-10-11 11:26:35');

-- --------------------------------------------------------

--
-- Table structure for table `qr_scan_logs`
--

CREATE TABLE `qr_scan_logs` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `qr_type` enum('office','panorama') DEFAULT 'office',
  `panorama_id` varchar(255) DEFAULT NULL,
  `location_context` varchar(255) DEFAULT NULL,
  `qr_code_id` int(11) NOT NULL,
  `check_in_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `qr_scan_logs`
--

INSERT INTO `qr_scan_logs` (`id`, `office_id`, `qr_type`, `panorama_id`, `location_context`, `qr_code_id`, `check_in_time`) VALUES
(747, 99, 'office', NULL, NULL, 242, '2025-05-27 23:12:54'),
(748, 103, 'office', NULL, NULL, 246, '2024-06-28 10:11:34'),
(749, 103, 'office', NULL, NULL, 246, '2025-01-03 08:19:53'),
(750, 98, 'office', NULL, NULL, 241, '2023-08-04 04:01:23'),
(751, 96, 'office', NULL, NULL, 239, '2022-05-20 05:59:41'),
(752, 99, 'office', NULL, NULL, 242, '2021-02-25 01:55:16'),
(753, 101, 'office', NULL, NULL, 244, '2020-03-10 22:13:34'),
(754, 102, 'office', NULL, NULL, 245, '2024-05-17 00:16:21'),
(755, 96, 'office', NULL, NULL, 239, '2020-08-16 07:09:10'),
(756, 105, 'office', NULL, NULL, 248, '2021-10-04 21:25:08'),
(757, 101, 'office', NULL, NULL, 244, '2021-06-18 05:56:13'),
(758, 98, 'office', NULL, NULL, 241, '2022-09-02 04:17:40'),
(759, 101, 'office', NULL, NULL, 244, '2021-06-27 01:39:03'),
(760, 104, 'office', NULL, NULL, 247, '2024-05-30 01:37:28'),
(761, 103, 'office', NULL, NULL, 246, '2024-07-03 03:46:26'),
(762, 104, 'office', NULL, NULL, 247, '2020-06-04 21:00:49'),
(763, 101, 'office', NULL, NULL, 244, '2022-12-25 04:34:05'),
(764, 105, 'office', NULL, NULL, 248, '2021-02-14 23:08:53'),
(765, 96, 'office', NULL, NULL, 239, '2020-04-24 02:30:13'),
(766, 100, 'office', NULL, NULL, 243, '2020-03-12 01:02:33'),
(767, 105, 'office', NULL, NULL, 248, '2022-09-26 11:03:55'),
(768, 96, 'office', NULL, NULL, 239, '2022-04-07 04:30:00'),
(769, 104, 'office', NULL, NULL, 247, '2020-02-19 02:39:56'),
(770, 97, 'office', NULL, NULL, 240, '2023-05-21 11:28:47'),
(771, 104, 'office', NULL, NULL, 247, '2020-06-15 01:23:10'),
(772, 96, 'office', NULL, NULL, 239, '2021-05-25 22:59:00'),
(773, 104, 'office', NULL, NULL, 247, '2020-01-15 15:17:13'),
(774, 104, 'office', NULL, NULL, 247, '2020-05-16 00:03:29'),
(775, 105, 'office', NULL, NULL, 248, '2021-07-18 21:12:10'),
(776, 105, 'office', NULL, NULL, 248, '2021-11-09 11:31:08'),
(777, 99, 'office', NULL, NULL, 242, '2020-08-06 12:22:22'),
(778, 97, 'office', NULL, NULL, 240, '2021-05-10 05:14:03'),
(779, 97, 'office', NULL, NULL, 240, '2025-04-13 23:18:00'),
(780, 98, 'office', NULL, NULL, 241, '2023-05-28 09:27:50'),
(781, 105, 'office', NULL, NULL, 248, '2020-06-24 23:18:56'),
(782, 97, 'office', NULL, NULL, 240, '2022-12-28 05:13:41'),
(783, 97, 'office', NULL, NULL, 240, '2021-08-29 10:41:38'),
(784, 98, 'office', NULL, NULL, 241, '2022-11-18 22:03:43'),
(785, 101, 'office', NULL, NULL, 244, '2022-07-16 18:22:23'),
(786, 104, 'office', NULL, NULL, 247, '2022-08-23 05:51:56'),
(787, 98, 'office', NULL, NULL, 241, '2021-09-26 18:38:16'),
(788, 98, 'office', NULL, NULL, 241, '2024-07-31 12:01:41'),
(789, 101, 'office', NULL, NULL, 244, '2021-02-19 02:36:26'),
(790, 99, 'office', NULL, NULL, 242, '2021-08-17 18:16:21'),
(791, 100, 'office', NULL, NULL, 243, '2024-01-01 16:51:02'),
(792, 105, 'office', NULL, NULL, 248, '2022-09-09 13:06:14'),
(793, 98, 'office', NULL, NULL, 241, '2022-11-24 00:28:47'),
(794, 99, 'office', NULL, NULL, 242, '2024-04-28 06:25:07'),
(795, 101, 'office', NULL, NULL, 244, '2020-07-04 10:44:14'),
(796, 100, 'office', NULL, NULL, 243, '2021-07-11 05:30:56'),
(797, 103, 'office', NULL, NULL, 246, '2020-10-20 17:28:00'),
(798, 99, 'office', NULL, NULL, 242, '2022-01-02 05:22:24'),
(799, 97, 'office', NULL, NULL, 240, '2022-03-28 07:37:57'),
(800, 102, 'office', NULL, NULL, 245, '2022-02-07 04:03:41'),
(801, 101, 'office', NULL, NULL, 244, '2022-06-24 07:08:57'),
(802, 101, 'office', NULL, NULL, 244, '2025-04-15 18:10:44'),
(803, 104, 'office', NULL, NULL, 247, '2022-09-13 05:07:14'),
(804, 100, 'office', NULL, NULL, 243, '2020-04-10 10:20:36'),
(805, 99, 'office', NULL, NULL, 242, '2024-06-18 08:27:49'),
(806, 103, 'office', NULL, NULL, 246, '2025-01-16 16:50:03'),
(807, 98, 'office', NULL, NULL, 241, '2021-08-19 20:09:27'),
(808, 102, 'office', NULL, NULL, 245, '2020-11-09 08:36:35'),
(809, 98, 'office', NULL, NULL, 241, '2023-03-21 15:42:00'),
(810, 105, 'office', NULL, NULL, 248, '2022-05-18 22:04:51'),
(811, 97, 'office', NULL, NULL, 240, '2020-12-21 11:21:25'),
(812, 105, 'office', NULL, NULL, 248, '2025-03-04 06:59:32'),
(813, 101, 'office', NULL, NULL, 244, '2020-07-04 12:15:12'),
(814, 101, 'office', NULL, NULL, 244, '2021-09-21 17:01:06'),
(815, 99, 'office', NULL, NULL, 242, '2024-10-18 22:33:59'),
(816, 102, 'office', NULL, NULL, 245, '2025-03-10 08:55:39'),
(817, 98, 'office', NULL, NULL, 241, '2020-08-27 08:46:45'),
(818, 103, 'office', NULL, NULL, 246, '2021-07-29 17:05:04'),
(819, 100, 'office', NULL, NULL, 243, '2024-01-17 00:40:01'),
(820, 100, 'office', NULL, NULL, 243, '2021-09-10 10:13:49'),
(821, 103, 'office', NULL, NULL, 246, '2024-02-24 03:11:23'),
(822, 105, 'office', NULL, NULL, 248, '2024-11-29 04:46:05'),
(823, 97, 'office', NULL, NULL, 240, '2022-06-01 10:14:35'),
(824, 96, 'office', NULL, NULL, 239, '2022-08-25 03:48:39'),
(825, 103, 'office', NULL, NULL, 246, '2024-08-15 05:56:45'),
(826, 97, 'office', NULL, NULL, 240, '2020-11-13 14:40:09'),
(827, 99, 'office', NULL, NULL, 242, '2023-09-30 07:57:42'),
(828, 104, 'office', NULL, NULL, 247, '2021-06-21 13:52:43'),
(829, 99, 'office', NULL, NULL, 242, '2020-07-19 14:27:24'),
(830, 98, 'office', NULL, NULL, 241, '2023-02-14 01:56:01'),
(831, 102, 'office', NULL, NULL, 245, '2021-08-12 18:52:27'),
(832, 104, 'office', NULL, NULL, 247, '2023-10-26 02:35:56'),
(833, 99, 'office', NULL, NULL, 242, '2022-03-19 12:56:42'),
(834, 101, 'office', NULL, NULL, 244, '2022-02-09 07:02:40'),
(835, 104, 'office', NULL, NULL, 247, '2020-09-02 23:27:56'),
(836, 105, 'office', NULL, NULL, 248, '2023-05-27 18:32:56'),
(837, 101, 'office', NULL, NULL, 244, '2020-01-28 18:19:08'),
(838, 105, 'office', NULL, NULL, 248, '2021-08-31 13:57:59'),
(839, 105, 'office', NULL, NULL, 248, '2024-07-24 04:10:46'),
(840, 100, 'office', NULL, NULL, 243, '2024-01-14 02:33:43'),
(841, 102, 'office', NULL, NULL, 245, '2023-12-17 17:41:36'),
(842, 101, 'office', NULL, NULL, 244, '2022-11-18 23:12:02'),
(843, 104, 'office', NULL, NULL, 247, '2024-11-28 13:40:22'),
(844, 101, 'office', NULL, NULL, 244, '2021-09-10 23:08:37'),
(845, 96, 'office', NULL, NULL, 239, '2022-02-11 01:34:16'),
(846, 97, 'office', NULL, NULL, 240, '2025-04-11 18:00:11'),
(847, 102, 'office', NULL, NULL, 245, '2020-08-20 20:18:23'),
(848, 96, 'office', NULL, NULL, 239, '2020-05-12 00:32:45'),
(849, 97, 'office', NULL, NULL, 240, '2020-07-22 01:15:22'),
(850, 98, 'office', NULL, NULL, 241, '2020-09-10 02:05:33'),
(851, 99, 'office', NULL, NULL, 242, '2020-11-18 03:22:11'),
(852, 100, 'office', NULL, NULL, 243, '2021-01-05 04:45:09'),
(853, 101, 'office', NULL, NULL, 244, '2021-03-30 05:12:28'),
(854, 102, 'office', NULL, NULL, 245, '2021-05-14 06:33:47'),
(855, 103, 'office', NULL, NULL, 246, '2021-07-21 07:20:15'),
(856, 104, 'office', NULL, NULL, 247, '2021-09-11 08:45:32'),
(857, 105, 'office', NULL, NULL, 248, '2021-12-03 09:18:59'),
(858, 96, 'office', NULL, NULL, 239, '2022-02-12 00:05:21'),
(859, 97, 'office', NULL, NULL, 240, '2022-04-15 01:12:33'),
(860, 98, 'office', NULL, NULL, 241, '2022-06-03 02:22:44'),
(861, 99, 'office', NULL, NULL, 242, '2022-08-18 03:35:15'),
(862, 100, 'office', NULL, NULL, 243, '2022-10-10 04:42:26'),
(863, 101, 'office', NULL, NULL, 244, '2022-12-22 05:15:37'),
(864, 102, 'office', NULL, NULL, 245, '2023-02-05 06:25:48'),
(865, 103, 'office', NULL, NULL, 246, '2023-04-19 07:35:59'),
(866, 104, 'office', NULL, NULL, 247, '2023-06-11 08:45:10'),
(867, 105, 'office', NULL, NULL, 248, '2023-08-25 09:55:21'),
(868, 96, 'office', NULL, NULL, 239, '2023-10-07 00:15:32'),
(869, 97, 'office', NULL, NULL, 240, '2023-12-14 01:25:43'),
(870, 98, 'office', NULL, NULL, 241, '2024-01-03 02:35:54'),
(871, 99, 'office', NULL, NULL, 242, '2024-02-18 03:45:05'),
(872, 100, 'office', NULL, NULL, 243, '2024-03-10 04:55:16'),
(873, 101, 'office', NULL, NULL, 244, '2024-04-22 05:05:27'),
(874, 102, 'office', NULL, NULL, 245, '2024-05-05 06:15:38'),
(875, 103, 'office', NULL, NULL, 246, '2024-06-19 07:25:49'),
(876, 104, 'office', NULL, NULL, 247, '2024-07-11 08:35:50'),
(877, 105, 'office', NULL, NULL, 248, '2024-08-25 09:45:51'),
(878, 96, 'office', NULL, NULL, 239, '2020-06-14 23:32:11'),
(879, 97, 'office', NULL, NULL, 240, '2020-08-24 00:45:22'),
(880, 98, 'office', NULL, NULL, 241, '2020-10-12 01:55:33'),
(881, 99, 'office', NULL, NULL, 242, '2021-01-28 02:12:44'),
(882, 100, 'office', NULL, NULL, 243, '2021-03-15 03:25:55'),
(883, 101, 'office', NULL, NULL, 244, '2021-05-30 04:32:16'),
(884, 102, 'office', NULL, NULL, 245, '2021-07-14 05:43:27'),
(885, 103, 'office', NULL, NULL, 246, '2021-09-01 06:52:38'),
(886, 104, 'office', NULL, NULL, 247, '2021-11-21 07:15:49'),
(887, 105, 'office', NULL, NULL, 248, '2022-01-13 08:22:50'),
(888, 96, 'office', NULL, NULL, 239, '2022-03-21 23:35:01'),
(889, 97, 'office', NULL, NULL, 240, '2022-05-25 00:42:12'),
(890, 98, 'office', NULL, NULL, 241, '2022-07-13 01:52:23'),
(891, 99, 'office', NULL, NULL, 242, '2022-09-28 02:05:34'),
(892, 100, 'office', NULL, NULL, 243, '2022-11-20 03:15:45'),
(893, 101, 'office', NULL, NULL, 244, '2023-01-02 04:25:56'),
(894, 102, 'office', NULL, NULL, 245, '2023-03-15 05:35:07'),
(895, 103, 'office', NULL, NULL, 246, '2023-05-29 06:45:18'),
(896, 104, 'office', NULL, NULL, 247, '2023-07-01 07:55:29'),
(897, 105, 'office', NULL, NULL, 248, '2023-09-15 08:05:30'),
(898, 96, 'office', NULL, NULL, 239, '2023-11-16 23:15:41'),
(899, 97, 'office', NULL, NULL, 240, '2024-01-24 00:25:52'),
(900, 98, 'office', NULL, NULL, 241, '2024-03-03 01:35:03'),
(901, 99, 'office', NULL, NULL, 242, '2024-04-18 02:45:14'),
(902, 100, 'office', NULL, NULL, 243, '2024-05-10 03:55:25'),
(903, 101, 'office', NULL, NULL, 244, '2024-06-22 04:05:36'),
(904, 102, 'office', NULL, NULL, 245, '2024-07-05 05:15:47'),
(905, 103, 'office', NULL, NULL, 246, '2024-08-19 06:25:58'),
(906, 104, 'office', NULL, NULL, 247, '2024-09-11 07:35:09'),
(907, 105, 'office', NULL, NULL, 248, '2024-10-25 08:45:10'),
(908, 96, 'office', NULL, NULL, 239, '2020-07-15 23:55:21'),
(909, 97, 'office', NULL, NULL, 240, '2020-09-26 00:05:32'),
(910, 98, 'office', NULL, NULL, 241, '2020-11-20 01:15:43'),
(911, 99, 'office', NULL, NULL, 242, '2021-02-08 02:25:54'),
(912, 100, 'office', NULL, NULL, 243, '2021-04-25 03:35:05'),
(913, 101, 'office', NULL, NULL, 244, '2021-06-10 04:45:16'),
(914, 102, 'office', NULL, NULL, 245, '2021-08-24 05:55:27'),
(915, 103, 'office', NULL, NULL, 246, '2021-10-11 06:05:38'),
(916, 104, 'office', NULL, NULL, 247, '2021-12-31 07:15:49'),
(917, 105, 'office', NULL, NULL, 248, '2022-02-23 08:25:50'),
(918, 96, 'office', NULL, NULL, 239, '2022-04-01 23:35:01'),
(919, 97, 'office', NULL, NULL, 240, '2022-06-05 00:45:12'),
(920, 98, 'office', NULL, NULL, 241, '2022-08-23 01:55:23'),
(921, 99, 'office', NULL, NULL, 242, '2022-10-18 02:05:34'),
(922, 100, 'office', NULL, NULL, 243, '2022-12-10 03:15:45'),
(923, 101, 'office', NULL, NULL, 244, '2023-02-12 04:25:56'),
(924, 102, 'office', NULL, NULL, 245, '2023-04-25 05:35:07'),
(925, 103, 'office', NULL, NULL, 246, '2023-06-09 06:45:18'),
(926, 104, 'office', NULL, NULL, 247, '2023-08-11 07:55:29'),
(927, 105, 'office', NULL, NULL, 248, '2023-10-25 08:05:30'),
(928, 96, 'office', NULL, NULL, 239, '2023-12-06 23:15:41'),
(929, 97, 'office', NULL, NULL, 240, '2024-02-14 00:25:52'),
(930, 98, 'office', NULL, NULL, 241, '2024-04-13 01:35:03'),
(931, 99, 'office', NULL, NULL, 242, '2024-05-28 02:45:14'),
(932, 100, 'office', NULL, NULL, 243, '2024-06-10 03:55:25'),
(933, 101, 'office', NULL, NULL, 244, '2024-07-22 04:05:36'),
(934, 102, 'office', NULL, NULL, 245, '2024-08-15 05:15:47'),
(935, 103, 'office', NULL, NULL, 246, '2024-09-29 06:25:58'),
(936, 104, 'office', NULL, NULL, 247, '2024-10-21 07:35:09'),
(937, 105, 'office', NULL, NULL, 248, '2024-11-25 08:45:10'),
(938, 96, 'office', NULL, NULL, 239, '2020-08-15 23:05:11'),
(939, 97, 'office', NULL, NULL, 240, '2020-10-26 00:15:22'),
(940, 98, 'office', NULL, NULL, 241, '2020-12-10 01:25:33'),
(941, 99, 'office', NULL, NULL, 242, '2021-03-08 02:35:44'),
(942, 100, 'office', NULL, NULL, 243, '2021-05-15 03:45:55'),
(943, 101, 'office', NULL, NULL, 244, '2021-07-20 04:55:06'),
(944, 102, 'office', NULL, NULL, 245, '2021-09-14 05:05:17'),
(945, 103, 'office', NULL, NULL, 246, '2021-11-01 06:15:28'),
(946, 104, 'office', NULL, NULL, 247, '2022-01-21 07:25:39'),
(947, 105, 'office', NULL, NULL, 248, '2022-03-13 08:35:40'),
(948, 96, 'office', NULL, NULL, 239, '2022-05-11 23:45:51'),
(949, 97, 'office', NULL, NULL, 240, '2022-07-15 00:55:02'),
(950, 98, 'office', NULL, NULL, 241, '2022-09-03 01:05:13'),
(951, 99, 'office', NULL, NULL, 242, '2022-11-28 02:15:24'),
(952, 100, 'office', NULL, NULL, 243, '2023-01-10 03:25:35'),
(953, 101, 'office', NULL, NULL, 244, '2023-03-22 04:35:46'),
(954, 102, 'office', NULL, NULL, 245, '2023-05-05 05:45:57'),
(955, 103, 'office', NULL, NULL, 246, '2023-07-19 06:55:08'),
(956, 104, 'office', NULL, NULL, 247, '2023-09-01 07:05:19'),
(957, 105, 'office', NULL, NULL, 248, '2023-11-15 08:15:20'),
(958, 97, 'office', NULL, NULL, 240, '2020-01-02 21:22:18'),
(959, 103, 'office', NULL, NULL, 246, '2020-02-14 15:15:42'),
(960, 98, 'office', NULL, NULL, 241, '2020-03-08 06:03:57'),
(961, 96, 'office', NULL, NULL, 239, '2020-04-18 19:45:11'),
(962, 105, 'office', NULL, NULL, 248, '2020-05-21 10:32:04'),
(963, 101, 'office', NULL, NULL, 244, '2020-06-10 23:58:29'),
(964, 102, 'office', NULL, NULL, 245, '2020-07-30 04:14:36'),
(965, 99, 'office', NULL, NULL, 242, '2020-08-09 12:47:53'),
(966, 104, 'office', NULL, NULL, 247, '2020-09-16 17:25:40'),
(967, 100, 'office', NULL, NULL, 243, '2020-10-22 01:11:15'),
(968, 97, 'office', NULL, NULL, 240, '2020-11-05 08:39:28'),
(969, 103, 'office', NULL, NULL, 246, '2020-12-31 14:08:17'),
(970, 96, 'office', NULL, NULL, 239, '2021-01-11 20:52:43'),
(971, 98, 'office', NULL, NULL, 241, '2021-02-28 05:27:59'),
(972, 101, 'office', NULL, NULL, 244, '2021-03-15 11:45:31'),
(973, 105, 'office', NULL, NULL, 248, '2021-04-03 00:14:22'),
(974, 102, 'office', NULL, NULL, 245, '2021-05-19 07:33:47'),
(975, 99, 'office', NULL, NULL, 242, '2021-06-23 22:21:38'),
(976, 104, 'office', NULL, NULL, 247, '2021-07-07 03:49:05'),
(977, 100, 'office', NULL, NULL, 243, '2021-08-13 15:57:16'),
(978, 97, 'office', NULL, NULL, 240, '2021-09-28 18:35:24'),
(979, 103, 'office', NULL, NULL, 246, '2021-10-11 09:08:42'),
(980, 96, 'office', NULL, NULL, 239, '2021-11-22 02:44:53'),
(981, 98, 'office', NULL, NULL, 241, '2021-12-05 06:26:37'),
(982, 101, 'office', NULL, NULL, 244, '2022-01-17 21:39:18'),
(983, 105, 'office', NULL, NULL, 248, '2022-02-27 13:15:29'),
(984, 102, 'office', NULL, NULL, 245, '2022-03-09 04:48:51'),
(985, 99, 'office', NULL, NULL, 242, '2022-04-13 19:27:44'),
(986, 104, 'office', NULL, NULL, 247, '2022-05-23 08:39:12'),
(987, 100, 'office', NULL, NULL, 243, '2022-06-30 01:52:37'),
(988, 97, 'office', NULL, NULL, 240, '2022-07-12 14:14:58'),
(989, 103, 'office', NULL, NULL, 246, '2022-08-18 23:33:21'),
(990, 96, 'office', NULL, NULL, 239, '2022-09-01 10:45:39'),
(991, 98, 'office', NULL, NULL, 241, '2022-10-10 17:27:45'),
(992, 101, 'office', NULL, NULL, 244, '2022-11-24 05:59:16'),
(993, 105, 'office', NULL, NULL, 248, '2022-12-06 20:38:27'),
(994, 102, 'office', NULL, NULL, 245, '2023-01-13 07:22:48'),
(995, 99, 'office', NULL, NULL, 242, '2023-02-28 12:45:53'),
(996, 104, 'office', NULL, NULL, 247, '2023-03-10 00:17:29'),
(997, 100, 'office', NULL, NULL, 243, '2023-04-19 03:39:44'),
(998, 97, 'office', NULL, NULL, 240, '2023-05-25 15:58:15'),
(999, 103, 'office', NULL, NULL, 246, '2023-06-06 06:27:36'),
(1000, 96, 'office', NULL, NULL, 239, '2023-07-17 21:49:52'),
(1001, 98, 'office', NULL, NULL, 241, '2023-08-22 11:33:47'),
(1002, 101, 'office', NULL, NULL, 244, '2023-09-05 02:44:58'),
(1003, 105, 'office', NULL, NULL, 248, '2023-10-16 18:15:29'),
(1004, 102, 'office', NULL, NULL, 245, '2023-11-29 08:38:41'),
(1005, 99, 'office', NULL, NULL, 242, '2023-12-11 23:52:53'),
(1006, 104, 'office', NULL, NULL, 247, '2024-01-24 13:15:34'),
(1007, 100, 'office', NULL, NULL, 243, '2024-02-07 04:47:59'),
(1008, 97, 'office', NULL, NULL, 240, '2024-03-18 19:29:16'),
(1009, 103, 'office', NULL, NULL, 246, '2024-04-22 09:48:25'),
(1010, 96, 'office', NULL, NULL, 239, '2024-05-05 01:15:37'),
(1011, 98, 'office', NULL, NULL, 241, '2024-06-18 14:39:48'),
(1012, 101, 'office', NULL, NULL, 244, '2024-07-23 06:52:19'),
(1013, 105, 'office', NULL, NULL, 248, '2024-08-08 21:27:31'),
(1014, 102, 'office', NULL, NULL, 245, '2024-09-15 10:45:42'),
(1015, 99, 'office', NULL, NULL, 242, '2024-10-28 02:33:54'),
(1016, 104, 'office', NULL, NULL, 247, '2024-11-10 17:59:15'),
(1017, 100, 'office', NULL, NULL, 243, '2024-12-24 05:27:36'),
(1018, 97, 'office', NULL, NULL, 240, '2020-01-06 22:33:47'),
(1019, 103, 'office', NULL, NULL, 246, '2020-02-18 13:45:58'),
(1020, 98, 'office', NULL, NULL, 241, '2020-03-22 04:59:19'),
(1021, 96, 'office', NULL, NULL, 239, '2020-04-04 20:15:21'),
(1022, 105, 'office', NULL, NULL, 248, '2020-05-17 09:38:32'),
(1023, 101, 'office', NULL, NULL, 244, '2020-06-29 01:52:43'),
(1024, 102, 'office', NULL, NULL, 245, '2020-07-12 14:14:54'),
(1025, 99, 'office', NULL, NULL, 242, '2020-08-25 06:27:15'),
(1026, 104, 'office', NULL, NULL, 247, '2020-09-07 21:39:26'),
(1027, 100, 'office', NULL, NULL, 243, '2020-10-19 10:52:37'),
(1028, 97, 'office', NULL, NULL, 240, '2020-11-01 02:15:48'),
(1029, 103, 'office', NULL, NULL, 246, '2020-12-13 17:29:59'),
(1030, 96, 'office', NULL, NULL, 239, '2021-01-26 05:42:11'),
(1031, 98, 'office', NULL, NULL, 241, '2021-02-08 20:55:22'),
(1032, 101, 'office', NULL, NULL, 244, '2021-03-22 12:08:33'),
(1033, 105, 'office', NULL, NULL, 248, '2021-04-05 03:21:44'),
(1034, 102, 'office', NULL, NULL, 245, '2021-05-17 19:34:55'),
(1035, 99, 'office', NULL, NULL, 242, '2021-06-30 07:48:06'),
(1036, 104, 'office', NULL, NULL, 247, '2021-07-12 23:01:17'),
(1037, 100, 'office', NULL, NULL, 243, '2021-08-26 14:14:28'),
(1038, 97, 'office', NULL, NULL, 240, '2021-09-09 05:27:39'),
(1039, 103, 'office', NULL, NULL, 246, '2021-10-20 20:40:41'),
(1040, 96, 'office', NULL, NULL, 239, '2021-11-03 11:53:52'),
(1041, 98, 'office', NULL, NULL, 241, '2021-12-16 03:07:03'),
(1042, 101, 'office', NULL, NULL, 244, '2022-01-28 18:20:14'),
(1043, 105, 'office', NULL, NULL, 248, '2022-02-11 09:33:25'),
(1044, 102, 'office', NULL, NULL, 245, '2022-03-26 00:46:36'),
(1045, 99, 'office', NULL, NULL, 242, '2022-04-08 15:59:47'),
(1046, 104, 'office', NULL, NULL, 247, '2022-05-21 07:12:58'),
(1047, 100, 'office', NULL, NULL, 243, '2022-06-03 22:26:09'),
(1048, 97, 'office', NULL, NULL, 240, '2022-07-17 13:39:11'),
(1049, 103, 'office', NULL, NULL, 246, '2022-08-30 04:52:22'),
(1050, 96, 'office', NULL, NULL, 239, '2022-09-12 20:05:33'),
(1051, 98, 'office', NULL, NULL, 241, '2022-10-26 11:18:44'),
(1052, 101, 'office', NULL, NULL, 244, '2022-11-09 02:31:55'),
(1053, 105, 'office', NULL, NULL, 248, '2022-12-21 17:45:06'),
(1054, 102, 'office', NULL, NULL, 245, '2023-01-04 08:58:17'),
(1055, 99, 'office', NULL, NULL, 242, '2023-02-17 00:11:28'),
(1056, 104, 'office', NULL, NULL, 247, '2023-03-02 15:24:39'),
(1057, 100, 'office', NULL, NULL, 243, '2023-04-15 06:37:41'),
(1058, 97, 'office', NULL, NULL, 240, '2023-05-27 21:50:52'),
(1059, 103, 'office', NULL, NULL, 246, '2023-06-10 12:04:03'),
(1060, 96, 'office', NULL, NULL, 239, '2023-07-23 03:17:14'),
(1061, 98, 'office', NULL, NULL, 241, '2023-08-05 18:30:25'),
(1062, 101, 'office', NULL, NULL, 244, '2023-09-18 09:43:36'),
(1063, 105, 'office', NULL, NULL, 248, '2023-10-01 00:56:47'),
(1064, 102, 'office', NULL, NULL, 245, '2023-11-13 16:09:58'),
(1065, 99, 'office', NULL, NULL, 242, '2023-12-27 07:23:09'),
(1066, 104, 'office', NULL, NULL, 247, '2024-01-09 22:36:11'),
(1067, 100, 'office', NULL, NULL, 243, '2024-02-22 13:49:22'),
(1068, 97, 'office', NULL, NULL, 240, '2024-03-07 05:02:33'),
(1069, 103, 'office', NULL, NULL, 246, '2024-04-19 20:15:44'),
(1070, 96, 'office', NULL, NULL, 239, '2024-05-03 11:28:55'),
(1071, 98, 'office', NULL, NULL, 241, '2024-06-16 02:42:06'),
(1072, 101, 'office', NULL, NULL, 244, '2024-07-28 17:55:17'),
(1073, 105, 'office', NULL, NULL, 248, '2024-08-11 09:08:28'),
(1074, 102, 'office', NULL, NULL, 245, '2024-09-24 00:21:39'),
(1075, 99, 'office', NULL, NULL, 242, '2024-10-07 15:34:41'),
(1076, 104, 'office', NULL, NULL, 247, '2024-11-20 06:47:52'),
(1077, 100, 'office', NULL, NULL, 243, '2024-12-03 22:01:03'),
(1078, 97, 'office', NULL, NULL, 240, '2020-01-17 11:14:14'),
(1079, 103, 'office', NULL, NULL, 246, '2020-02-20 02:27:25'),
(1080, 98, 'office', NULL, NULL, 241, '2020-03-04 17:40:36'),
(1081, 96, 'office', NULL, NULL, 239, '2020-04-18 08:53:47'),
(1082, 105, 'office', NULL, NULL, 248, '2020-05-01 00:06:58'),
(1083, 101, 'office', NULL, NULL, 244, '2020-06-14 15:20:09'),
(1084, 102, 'office', NULL, NULL, 245, '2020-07-27 06:33:11'),
(1085, 99, 'office', NULL, NULL, 242, '2020-08-09 21:46:22'),
(1086, 104, 'office', NULL, NULL, 247, '2020-09-22 12:59:33'),
(1087, 100, 'office', NULL, NULL, 243, '2020-10-06 04:12:44'),
(1088, 97, 'office', NULL, NULL, 240, '2020-11-18 19:25:55'),
(1089, 103, 'office', NULL, NULL, 246, '2020-12-02 10:39:06'),
(1090, 96, 'office', NULL, NULL, 239, '2021-01-15 01:52:17'),
(1091, 98, 'office', NULL, NULL, 241, '2021-02-27 17:05:28'),
(1092, 101, 'office', NULL, NULL, 244, '2021-03-12 08:18:39'),
(1093, 105, 'office', NULL, NULL, 248, '2021-04-24 23:31:41'),
(1094, 102, 'office', NULL, NULL, 245, '2021-05-08 14:44:52'),
(1095, 99, 'office', NULL, NULL, 242, '2021-06-21 05:58:03'),
(1096, 104, 'office', NULL, NULL, 247, '2021-07-03 21:11:14'),
(1097, 100, 'office', NULL, NULL, 243, '2021-08-17 12:24:25'),
(1098, 97, 'office', NULL, NULL, 240, '2021-09-30 03:37:36'),
(1099, 103, 'office', NULL, NULL, 246, '2021-10-12 18:50:47'),
(1100, 96, 'office', NULL, NULL, 239, '2021-11-26 10:03:58'),
(1101, 98, 'office', NULL, NULL, 241, '2021-12-09 01:17:09'),
(1102, 101, 'office', NULL, NULL, 244, '2022-01-21 16:30:11'),
(1103, 105, 'office', NULL, NULL, 248, '2022-02-04 07:43:22'),
(1104, 102, 'office', NULL, NULL, 245, '2022-03-18 22:56:33'),
(1105, 99, 'office', NULL, NULL, 242, '2022-04-01 14:09:44'),
(1106, 104, 'office', NULL, NULL, 247, '2022-05-15 05:22:55'),
(1107, 100, 'office', NULL, NULL, 243, '2022-06-27 20:36:06'),
(1108, 97, 'office', NULL, NULL, 240, '2022-07-11 11:49:17'),
(1109, 103, 'office', NULL, NULL, 246, '2022-08-24 03:02:28'),
(1110, 96, 'office', NULL, NULL, 239, '2022-09-06 18:15:39'),
(1111, 98, 'office', NULL, NULL, 241, '2022-10-20 09:28:41'),
(1112, 101, 'office', NULL, NULL, 244, '2022-11-03 00:41:52'),
(1113, 105, 'office', NULL, NULL, 248, '2022-12-16 15:55:03'),
(1114, 102, 'office', NULL, NULL, 245, '2023-01-29 07:08:14'),
(1115, 99, 'office', NULL, NULL, 242, '2023-02-10 22:21:25'),
(1116, 104, 'office', NULL, NULL, 247, '2023-03-26 13:34:36'),
(1117, 100, 'office', NULL, NULL, 243, '2023-04-09 04:47:47'),
(1118, 97, 'office', NULL, NULL, 240, '2023-05-21 20:00:58'),
(1119, 103, 'office', NULL, NULL, 246, '2023-06-04 11:14:09'),
(1120, 96, 'office', NULL, NULL, 239, '2023-07-17 02:27:11'),
(1121, 98, 'office', NULL, NULL, 241, '2023-08-29 17:40:22'),
(1122, 101, 'office', NULL, NULL, 244, '2023-09-12 08:53:33'),
(1123, 105, 'office', NULL, NULL, 248, '2023-10-26 00:06:44'),
(1124, 102, 'office', NULL, NULL, 245, '2023-11-08 15:19:55'),
(1125, 99, 'office', NULL, NULL, 242, '2023-12-22 06:33:06'),
(1126, 104, 'office', NULL, NULL, 247, '2024-01-04 21:46:17'),
(1127, 100, 'office', NULL, NULL, 243, '2024-02-18 12:59:28'),
(1128, 97, 'office', NULL, NULL, 240, '2024-03-02 04:12:39'),
(1129, 103, 'office', NULL, NULL, 246, '2024-04-14 19:25:41'),
(1130, 96, 'office', NULL, NULL, 239, '2024-05-28 10:38:52'),
(1131, 98, 'office', NULL, NULL, 241, '2024-06-10 01:52:03'),
(1132, 101, 'office', NULL, NULL, 244, '2024-07-23 17:05:14'),
(1133, 105, 'office', NULL, NULL, 248, '2024-08-06 08:18:25'),
(1134, 102, 'office', NULL, NULL, 245, '2024-09-18 23:31:36'),
(1135, 99, 'office', NULL, NULL, 242, '2024-10-02 14:44:47'),
(1136, 104, 'office', NULL, NULL, 247, '2024-11-15 05:57:58'),
(1137, 100, 'office', NULL, NULL, 243, '2024-12-28 21:11:09'),
(1138, 96, 'office', NULL, NULL, 239, '2024-06-01 00:15:32'),
(1139, 97, 'office', NULL, NULL, 240, '2024-06-01 03:23:45'),
(1140, 98, 'office', NULL, NULL, 241, '2024-06-01 06:37:18'),
(1141, 99, 'office', NULL, NULL, 242, '2024-06-01 08:52:09'),
(1142, 100, 'office', NULL, NULL, 243, '2024-06-01 01:41:27'),
(1143, 101, 'office', NULL, NULL, 244, '2024-06-01 05:08:54'),
(1144, 102, 'office', NULL, NULL, 245, '2024-06-01 09:25:36'),
(1145, 103, 'office', NULL, NULL, 246, '2024-06-01 02:19:43'),
(1146, 104, 'office', NULL, NULL, 247, '2024-06-01 07:47:21'),
(1147, 105, 'office', NULL, NULL, 248, '2024-06-01 04:33:58'),
(1148, 96, 'office', NULL, NULL, 239, '2024-06-01 23:58:12'),
(1149, 97, 'office', NULL, NULL, 240, '2024-06-02 02:42:35'),
(1150, 98, 'office', NULL, NULL, 241, '2024-06-02 05:15:49'),
(1151, 99, 'office', NULL, NULL, 242, '2024-06-02 08:28:07'),
(1152, 100, 'office', NULL, NULL, 243, '2024-06-02 00:53:24'),
(1153, 101, 'office', NULL, NULL, 244, '2024-06-02 03:37:56'),
(1154, 102, 'office', NULL, NULL, 245, '2024-06-02 06:22:18'),
(1155, 103, 'office', NULL, NULL, 246, '2024-06-02 09:45:39'),
(1156, 104, 'office', NULL, NULL, 247, '2024-06-02 01:31:42'),
(1157, 105, 'office', NULL, NULL, 248, '2024-06-02 04:19:05'),
(1158, 96, 'office', NULL, NULL, 239, '2024-06-03 00:24:37'),
(1159, 97, 'office', NULL, NULL, 240, '2024-06-03 03:52:14'),
(1160, 98, 'office', NULL, NULL, 241, '2024-06-03 06:38:26'),
(1161, 99, 'office', NULL, NULL, 242, '2024-06-03 08:17:49'),
(1162, 100, 'office', NULL, NULL, 243, '2024-06-03 01:45:32'),
(1163, 101, 'office', NULL, NULL, 244, '2024-06-03 05:28:57'),
(1164, 102, 'office', NULL, NULL, 245, '2024-06-03 09:39:04'),
(1165, 103, 'office', NULL, NULL, 246, '2024-06-03 02:22:18'),
(1166, 104, 'office', NULL, NULL, 247, '2024-06-03 07:14:36'),
(1167, 105, 'office', NULL, NULL, 248, '2024-06-03 04:47:59'),
(1168, 96, 'office', NULL, NULL, 239, '2024-06-03 23:39:21'),
(1169, 97, 'office', NULL, NULL, 240, '2024-06-04 02:28:43'),
(1170, 98, 'office', NULL, NULL, 241, '2024-06-04 05:45:17'),
(1171, 99, 'office', NULL, NULL, 242, '2024-06-04 08:52:38'),
(1172, 100, 'office', NULL, NULL, 243, '2024-06-04 00:19:54'),
(1173, 101, 'office', NULL, NULL, 244, '2024-06-04 03:37:26'),
(1174, 102, 'office', NULL, NULL, 245, '2024-06-04 06:23:49'),
(1175, 103, 'office', NULL, NULL, 246, '2024-06-04 09:18:32'),
(1176, 104, 'office', NULL, NULL, 247, '2024-06-04 01:42:15'),
(1177, 105, 'office', NULL, NULL, 248, '2024-06-04 04:29:47'),
(1178, 96, 'office', NULL, NULL, 239, '2024-06-05 00:17:39'),
(1179, 97, 'office', NULL, NULL, 240, '2024-06-05 03:24:52'),
(1180, 98, 'office', NULL, NULL, 241, '2024-06-05 06:38:14'),
(1181, 99, 'office', NULL, NULL, 242, '2024-06-05 08:45:27'),
(1182, 100, 'office', NULL, NULL, 243, '2024-06-05 01:32:48'),
(1183, 101, 'office', NULL, NULL, 244, '2024-06-05 05:19:05'),
(1184, 102, 'office', NULL, NULL, 245, '2024-06-05 09:28:36'),
(1185, 103, 'office', NULL, NULL, 246, '2024-06-05 02:14:29'),
(1186, 104, 'office', NULL, NULL, 247, '2024-06-05 07:37:42'),
(1187, 105, 'office', NULL, NULL, 248, '2024-06-05 04:22:54'),
(1188, 96, 'office', NULL, NULL, 239, '2024-05-01 00:12:34'),
(1189, 97, 'office', NULL, NULL, 240, '2024-05-01 03:23:45'),
(1190, 103, 'office', NULL, NULL, 246, '2024-05-01 06:37:18'),
(1191, 99, 'office', NULL, NULL, 242, '2024-05-01 08:45:09'),
(1192, 101, 'office', NULL, NULL, 244, '2024-05-01 01:31:27'),
(1193, 105, 'office', NULL, NULL, 248, '2024-05-01 05:05:54'),
(1194, 102, 'office', NULL, NULL, 245, '2024-05-01 09:22:36'),
(1195, 98, 'office', NULL, NULL, 241, '2024-05-01 02:15:43'),
(1196, 104, 'office', NULL, NULL, 247, '2024-05-01 07:42:21'),
(1197, 100, 'office', NULL, NULL, 243, '2024-05-01 04:33:58'),
(1198, 97, 'office', NULL, NULL, 240, '2024-05-01 23:58:12'),
(1199, 103, 'office', NULL, NULL, 246, '2024-05-02 02:42:35'),
(1200, 96, 'office', NULL, NULL, 239, '2024-05-02 05:19:49'),
(1201, 102, 'office', NULL, NULL, 245, '2024-05-02 08:28:07'),
(1202, 104, 'office', NULL, NULL, 247, '2024-05-02 00:53:24'),
(1203, 101, 'office', NULL, NULL, 244, '2024-05-02 03:37:56'),
(1204, 105, 'office', NULL, NULL, 248, '2024-05-02 06:25:18'),
(1205, 99, 'office', NULL, NULL, 242, '2024-05-02 09:41:39'),
(1206, 100, 'office', NULL, NULL, 243, '2024-05-02 01:31:42'),
(1207, 98, 'office', NULL, NULL, 241, '2024-05-02 04:19:05'),
(1208, 96, 'office', NULL, NULL, 239, '2024-05-03 00:24:37'),
(1209, 103, 'office', NULL, NULL, 246, '2024-05-03 03:52:14'),
(1210, 97, 'office', NULL, NULL, 240, '2024-05-03 06:38:26'),
(1211, 101, 'office', NULL, NULL, 244, '2024-05-03 08:17:49'),
(1212, 105, 'office', NULL, NULL, 248, '2024-05-03 01:45:32'),
(1213, 99, 'office', NULL, NULL, 242, '2024-05-03 05:28:57'),
(1214, 104, 'office', NULL, NULL, 247, '2024-05-03 09:39:04'),
(1215, 102, 'office', NULL, NULL, 245, '2024-05-03 02:22:18'),
(1216, 100, 'office', NULL, NULL, 243, '2024-05-03 07:14:36'),
(1217, 98, 'office', NULL, NULL, 241, '2024-05-03 04:47:59'),
(1218, 103, 'office', NULL, NULL, 246, '2024-05-14 23:39:21'),
(1219, 96, 'office', NULL, NULL, 239, '2024-05-15 02:28:43'),
(1220, 97, 'office', NULL, NULL, 240, '2024-05-15 05:45:17'),
(1221, 105, 'office', NULL, NULL, 248, '2024-05-15 08:52:38'),
(1222, 101, 'office', NULL, NULL, 244, '2024-05-15 00:19:54'),
(1223, 99, 'office', NULL, NULL, 242, '2024-05-15 03:37:26'),
(1224, 104, 'office', NULL, NULL, 247, '2024-05-15 06:23:49'),
(1225, 102, 'office', NULL, NULL, 245, '2024-05-15 09:18:32'),
(1226, 100, 'office', NULL, NULL, 243, '2024-05-15 01:42:15'),
(1227, 98, 'office', NULL, NULL, 241, '2024-05-15 04:29:47'),
(1228, 96, 'office', NULL, NULL, 239, '2024-05-28 00:17:39'),
(1229, 97, 'office', NULL, NULL, 240, '2024-05-28 03:24:52'),
(1230, 98, 'office', NULL, NULL, 241, '2024-05-28 06:38:14'),
(1231, 99, 'office', NULL, NULL, 242, '2024-05-28 08:45:27'),
(1232, 100, 'office', NULL, NULL, 243, '2024-05-28 01:32:48'),
(1233, 101, 'office', NULL, NULL, 244, '2024-05-28 05:19:05'),
(1234, 102, 'office', NULL, NULL, 245, '2024-05-28 09:28:36'),
(1235, 103, 'office', NULL, NULL, 246, '2024-05-28 02:14:29'),
(1236, 104, 'office', NULL, NULL, 247, '2024-05-28 07:37:42'),
(1237, 105, 'office', NULL, NULL, 248, '2024-05-28 04:22:54'),
(1238, 96, 'office', NULL, NULL, 239, '2025-01-15 00:32:45'),
(1239, 97, 'office', NULL, NULL, 240, '2025-02-03 01:15:22'),
(1240, 98, 'office', NULL, NULL, 241, '2025-03-12 02:05:33'),
(1241, 99, 'office', NULL, NULL, 242, '2025-04-05 03:22:11'),
(1242, 100, 'office', NULL, NULL, 243, '2025-05-20 04:45:09'),
(1243, 101, 'office', NULL, NULL, 244, '2025-06-08 05:12:28'),
(1244, 102, 'office', NULL, NULL, 245, '2025-07-17 06:33:47'),
(1245, 103, 'office', NULL, NULL, 246, '2025-08-09 07:20:15'),
(1246, 104, 'office', NULL, NULL, 247, '2025-09-14 08:45:32'),
(1247, 105, 'office', NULL, NULL, 248, '2025-10-25 09:18:59'),
(1248, 96, 'office', NULL, NULL, 239, '2025-01-22 00:05:21'),
(1249, 97, 'office', NULL, NULL, 240, '2025-02-11 01:12:33'),
(1250, 98, 'office', NULL, NULL, 241, '2025-03-19 02:22:44'),
(1251, 99, 'office', NULL, NULL, 242, '2025-04-16 03:35:15'),
(1252, 100, 'office', NULL, NULL, 243, '2025-05-28 04:42:26'),
(1253, 101, 'office', NULL, NULL, 244, '2025-06-14 05:15:37'),
(1254, 102, 'office', NULL, NULL, 245, '2025-07-23 06:25:48'),
(1255, 103, 'office', NULL, NULL, 246, '2025-08-15 07:35:59'),
(1256, 104, 'office', NULL, NULL, 247, '2025-09-21 08:45:10'),
(1257, 105, 'office', NULL, NULL, 248, '2025-10-30 09:55:21'),
(1258, 96, 'office', NULL, NULL, 239, '2025-01-07 00:15:32'),
(1259, 97, 'office', NULL, NULL, 240, '2025-02-18 01:25:43'),
(1260, 98, 'office', NULL, NULL, 241, '2025-03-25 02:35:54'),
(1261, 99, 'office', NULL, NULL, 242, '2025-04-09 03:45:05'),
(1262, 100, 'office', NULL, NULL, 243, '2025-05-15 04:55:16'),
(1263, 101, 'office', NULL, NULL, 244, '2025-06-22 05:05:27'),
(1264, 102, 'office', NULL, NULL, 245, '2025-07-05 06:15:38'),
(1265, 103, 'office', NULL, NULL, 246, '2025-08-19 07:25:49'),
(1266, 104, 'office', NULL, NULL, 247, '2025-09-11 08:35:50'),
(1267, 105, 'office', NULL, NULL, 248, '2025-10-25 09:45:51'),
(1268, 96, 'office', NULL, NULL, 239, '2025-11-12 00:32:11'),
(1269, 97, 'office', NULL, NULL, 240, '2025-11-24 01:45:22'),
(1270, 98, 'office', NULL, NULL, 241, '2025-12-05 02:55:33'),
(1271, 99, 'office', NULL, NULL, 242, '2025-01-28 03:12:44'),
(1272, 100, 'office', NULL, NULL, 243, '2025-02-15 04:25:55'),
(1273, 101, 'office', NULL, NULL, 244, '2025-03-30 05:32:16'),
(1274, 102, 'office', NULL, NULL, 245, '2025-04-14 06:43:27'),
(1275, 103, 'office', NULL, NULL, 246, '2025-05-01 07:52:38'),
(1276, 104, 'office', NULL, NULL, 247, '2025-06-21 08:15:49'),
(1277, 105, 'office', NULL, NULL, 248, '2025-07-13 09:22:50'),
(1278, 96, 'office', NULL, NULL, 239, '2025-08-22 00:35:01'),
(1279, 97, 'office', NULL, NULL, 240, '2025-09-05 01:42:12'),
(1280, 98, 'office', NULL, NULL, 241, '2025-10-13 02:52:23'),
(1281, 99, 'office', NULL, NULL, 242, '2025-11-28 03:05:34'),
(1282, 100, 'office', NULL, NULL, 243, '2025-12-10 04:15:45'),
(1283, 101, 'office', NULL, NULL, 244, '2025-01-02 05:25:56'),
(1284, 102, 'office', NULL, NULL, 245, '2025-02-15 06:35:07'),
(1285, 103, 'office', NULL, NULL, 246, '2025-03-29 07:45:18'),
(1286, 104, 'office', NULL, NULL, 247, '2025-04-01 08:55:29'),
(1287, 105, 'office', NULL, NULL, 248, '2025-05-15 09:05:30'),
(1288, 96, 'office', NULL, NULL, 239, '2025-05-01 00:15:22'),
(1289, 97, 'office', NULL, NULL, 240, '2025-05-01 03:23:45'),
(1290, 98, 'office', NULL, NULL, 241, '2025-05-01 06:37:18'),
(1291, 99, 'office', NULL, NULL, 242, '2025-05-01 08:52:09'),
(1292, 100, 'office', NULL, NULL, 243, '2025-05-01 01:41:27'),
(1293, 101, 'office', NULL, NULL, 244, '2025-05-01 05:08:54'),
(1294, 102, 'office', NULL, NULL, 245, '2025-05-01 09:25:36'),
(1295, 103, 'office', NULL, NULL, 246, '2025-05-01 02:19:43'),
(1296, 104, 'office', NULL, NULL, 247, '2025-05-01 07:47:21'),
(1297, 105, 'office', NULL, NULL, 248, '2025-05-01 04:33:58'),
(1298, 96, 'office', NULL, NULL, 239, '2025-05-04 23:58:12'),
(1299, 97, 'office', NULL, NULL, 240, '2025-05-05 02:42:35'),
(1300, 98, 'office', NULL, NULL, 241, '2025-05-05 05:15:49'),
(1301, 99, 'office', NULL, NULL, 242, '2025-05-05 08:28:07'),
(1302, 100, 'office', NULL, NULL, 243, '2025-05-05 00:53:24'),
(1303, 101, 'office', NULL, NULL, 244, '2025-05-05 03:37:56'),
(1304, 102, 'office', NULL, NULL, 245, '2025-05-05 06:22:18'),
(1305, 103, 'office', NULL, NULL, 246, '2025-05-05 09:45:39'),
(1306, 104, 'office', NULL, NULL, 247, '2025-05-05 01:31:42'),
(1307, 105, 'office', NULL, NULL, 248, '2025-05-05 04:19:05'),
(1308, 96, 'office', NULL, NULL, 239, '2025-05-10 00:24:37'),
(1309, 97, 'office', NULL, NULL, 240, '2025-05-10 03:52:14'),
(1310, 98, 'office', NULL, NULL, 241, '2025-05-10 06:38:26'),
(1311, 99, 'office', NULL, NULL, 242, '2025-05-10 08:17:49'),
(1312, 100, 'office', NULL, NULL, 243, '2025-05-10 01:45:32'),
(1313, 101, 'office', NULL, NULL, 244, '2025-05-10 05:28:57'),
(1314, 102, 'office', NULL, NULL, 245, '2025-05-10 09:39:04'),
(1315, 103, 'office', NULL, NULL, 246, '2025-05-10 02:22:18'),
(1316, 104, 'office', NULL, NULL, 247, '2025-05-10 07:14:36'),
(1317, 105, 'office', NULL, NULL, 248, '2025-05-10 04:47:59'),
(1318, 96, 'office', NULL, NULL, 239, '2025-05-14 23:39:21'),
(1319, 97, 'office', NULL, NULL, 240, '2025-05-15 02:28:43'),
(1320, 98, 'office', NULL, NULL, 241, '2025-05-15 05:45:17'),
(1321, 99, 'office', NULL, NULL, 242, '2025-05-15 08:52:38'),
(1322, 100, 'office', NULL, NULL, 243, '2025-05-15 00:19:54'),
(1323, 101, 'office', NULL, NULL, 244, '2025-05-15 03:37:26'),
(1324, 102, 'office', NULL, NULL, 245, '2025-05-15 06:23:49'),
(1325, 103, 'office', NULL, NULL, 246, '2025-05-15 09:18:32'),
(1326, 104, 'office', NULL, NULL, 247, '2025-05-15 01:42:15'),
(1327, 105, 'office', NULL, NULL, 248, '2025-05-15 04:29:47'),
(1328, 96, 'office', NULL, NULL, 239, '2025-05-28 00:17:39'),
(1329, 97, 'office', NULL, NULL, 240, '2025-05-28 03:24:52'),
(1330, 98, 'office', NULL, NULL, 241, '2025-05-28 06:38:14'),
(1331, 99, 'office', NULL, NULL, 242, '2025-05-28 08:45:27'),
(1332, 100, 'office', NULL, NULL, 243, '2025-05-28 01:32:48'),
(1333, 101, 'office', NULL, NULL, 244, '2025-05-28 05:19:05'),
(1334, 102, 'office', NULL, NULL, 245, '2025-05-28 09:28:36'),
(1335, 103, 'office', NULL, NULL, 246, '2025-05-28 02:14:29'),
(1336, 104, 'office', NULL, NULL, 247, '2025-05-28 07:37:42'),
(1337, 105, 'office', NULL, NULL, 248, '2025-05-28 04:22:54'),
(1360, 98, 'office', NULL, NULL, 241, '2025-09-23 10:06:57'),
(1361, 98, 'office', NULL, NULL, 241, '2025-09-23 10:07:54'),
(1362, 98, 'office', NULL, NULL, 241, '2025-09-23 10:08:02'),
(1363, 98, 'office', NULL, NULL, 241, '2025-09-23 10:08:39'),
(1364, 97, 'office', NULL, NULL, 240, '2025-09-23 10:20:39'),
(1365, 96, 'office', NULL, NULL, 239, '2025-09-23 10:20:51'),
(1366, 99, 'office', NULL, NULL, 242, '2025-09-23 10:21:15'),
(1367, 105, 'office', NULL, NULL, 248, '2025-09-23 10:21:26'),
(1368, 105, 'office', NULL, NULL, 248, '2025-09-23 10:21:48'),
(1369, 105, 'office', NULL, NULL, 248, '2025-09-23 10:22:13'),
(1370, 105, 'office', NULL, NULL, 248, '2025-09-23 10:22:13'),
(1371, 97, 'office', NULL, NULL, 240, '2025-09-23 10:27:55'),
(1372, 105, 'office', NULL, NULL, 248, '2025-09-23 10:28:04'),
(1373, 99, 'office', NULL, NULL, 242, '2025-09-23 10:28:54'),
(1374, 100, 'office', NULL, NULL, 243, '2025-09-23 10:29:01'),
(1375, 100, 'office', NULL, NULL, 243, '2025-09-23 10:29:11'),
(1376, 97, 'office', NULL, NULL, 240, '2025-09-23 10:29:42'),
(1377, 99, 'office', NULL, NULL, 242, '2025-09-23 10:29:49'),
(1378, 99, 'office', NULL, NULL, 242, '2025-09-23 10:31:09'),
(1379, 97, 'office', NULL, NULL, 240, '2025-09-23 10:50:27'),
(1380, 99, 'office', NULL, NULL, 242, '2025-09-23 10:50:38'),
(1381, 99, 'office', NULL, NULL, 242, '2025-09-23 10:50:52'),
(1382, 97, 'office', NULL, NULL, 240, '2025-09-23 11:04:09'),
(1383, 96, 'office', NULL, NULL, 239, '2025-09-23 11:04:16'),
(1384, 99, 'office', NULL, NULL, 242, '2025-09-23 11:04:33'),
(1385, 99, 'office', NULL, NULL, 242, '2025-09-23 11:10:21'),
(1386, 99, 'office', NULL, NULL, 242, '2025-09-23 11:10:23'),
(1387, 97, 'office', NULL, NULL, 240, '2025-09-23 11:13:44'),
(1388, 97, 'office', NULL, NULL, 240, '2025-09-23 11:16:51'),
(1389, 96, 'office', NULL, NULL, 239, '2025-09-23 11:17:11'),
(1390, 96, 'office', NULL, NULL, 239, '2025-09-23 11:17:17'),
(1391, 96, 'office', NULL, NULL, 239, '2025-09-23 11:27:41'),
(1392, 96, 'office', NULL, NULL, 239, '2025-09-23 11:27:50'),
(1393, 96, 'office', NULL, NULL, 239, '2025-09-23 11:28:06'),
(1394, 96, 'office', NULL, NULL, 239, '2025-09-23 11:28:14'),
(1395, 96, 'office', NULL, NULL, 239, '2025-09-23 11:30:22'),
(1396, 99, 'office', NULL, NULL, 242, '2025-09-23 11:31:11'),
(1397, 98, 'office', NULL, NULL, 241, '2025-09-23 11:31:24'),
(1398, 97, 'office', NULL, NULL, 240, '2025-09-23 11:32:15'),
(1399, 99, 'office', NULL, NULL, 242, '2025-09-23 11:32:22'),
(1400, 98, 'office', NULL, NULL, 241, '2025-09-23 11:32:30'),
(1401, 97, 'office', NULL, NULL, 240, '2025-09-23 11:32:44'),
(1402, 99, 'office', NULL, NULL, 242, '2025-09-23 11:32:55'),
(1403, 97, 'office', NULL, NULL, 240, '2025-09-23 11:33:01'),
(1404, 99, 'office', NULL, NULL, 242, '2025-09-23 11:37:21'),
(1405, 98, 'office', NULL, NULL, 241, '2025-09-23 11:37:29'),
(1406, 98, 'office', NULL, NULL, 241, '2025-09-23 11:50:56'),
(1407, 97, 'office', NULL, NULL, 240, '2025-09-23 11:51:11'),
(1408, 96, 'office', NULL, NULL, 239, '2025-09-23 11:51:18'),
(1409, 101, 'office', NULL, NULL, 244, '2025-09-23 11:53:32'),
(1410, 101, 'office', NULL, NULL, 244, '2025-09-23 11:53:44'),
(1411, 101, 'office', NULL, NULL, 244, '2025-09-23 11:57:32'),
(1412, 99, 'office', NULL, NULL, 242, '2025-09-23 11:58:03'),
(1413, 99, 'office', NULL, NULL, 242, '2025-09-23 11:58:26'),
(1414, 99, 'office', NULL, NULL, 242, '2025-09-23 12:00:03'),
(1415, 99, 'office', NULL, NULL, 242, '2025-09-23 12:00:07'),
(1416, 100, 'office', NULL, NULL, 243, '2025-09-23 12:00:25'),
(1417, 97, 'office', NULL, NULL, 240, '2025-09-23 12:00:31'),
(1418, 97, 'office', NULL, NULL, 240, '2025-09-23 12:00:37'),
(1419, 96, 'office', NULL, NULL, 239, '2025-09-23 12:00:52'),
(1420, 96, 'office', NULL, NULL, 239, '2025-09-23 12:01:09'),
(1421, 96, 'office', NULL, NULL, 239, '2025-09-23 12:02:20'),
(1422, 96, 'office', NULL, NULL, 239, '2025-09-23 12:02:27'),
(1423, 98, 'office', NULL, NULL, 241, '2025-09-23 12:11:09'),
(1424, 96, 'office', NULL, NULL, 239, '2025-09-23 12:11:19'),
(1425, 98, 'office', NULL, NULL, 241, '2025-09-23 12:12:03'),
(1426, 98, 'office', NULL, NULL, 241, '2025-09-23 12:40:01'),
(1427, 96, 'office', NULL, NULL, 239, '2025-09-23 12:40:19'),
(1428, 96, 'office', NULL, NULL, 239, '2025-09-23 12:43:01'),
(1429, 98, 'office', NULL, NULL, 241, '2025-09-23 12:43:12'),
(1430, 98, 'office', NULL, NULL, 241, '2025-09-23 12:47:09'),
(1431, 97, 'office', NULL, NULL, 240, '2025-09-23 12:47:30'),
(1432, 97, 'office', NULL, NULL, 240, '2025-09-23 12:47:43'),
(1433, 97, 'office', NULL, NULL, 240, '2025-09-23 12:47:48'),
(1434, 98, 'office', NULL, NULL, 241, '2025-09-23 12:47:57'),
(1435, 98, 'office', NULL, NULL, 241, '2025-09-23 12:52:40'),
(1436, 97, 'office', NULL, NULL, 240, '2025-09-23 12:52:57'),
(1437, 97, 'office', NULL, NULL, 240, '2025-09-23 12:55:17'),
(1438, 96, 'office', NULL, NULL, 239, '2025-09-23 12:55:40'),
(1439, 96, 'office', NULL, NULL, 239, '2025-09-23 12:58:53'),
(1440, 96, 'office', NULL, NULL, 239, '2025-09-23 12:58:59'),
(1441, 96, 'office', NULL, NULL, 239, '2025-09-23 12:59:05'),
(1442, 96, 'office', NULL, NULL, 239, '2025-09-23 13:01:10'),
(1443, 96, 'office', NULL, NULL, 239, '2025-09-23 13:01:34'),
(1444, 96, 'office', NULL, NULL, 239, '2025-09-23 13:04:03'),
(1445, 96, 'office', NULL, NULL, 239, '2025-09-23 13:04:08'),
(1446, 98, 'office', NULL, NULL, 241, '2025-09-23 13:04:15'),
(1447, 97, 'office', NULL, NULL, 240, '2025-09-23 13:04:31'),
(1448, 97, 'office', NULL, NULL, 240, '2025-09-23 13:04:50'),
(1449, 97, 'office', NULL, NULL, 240, '2025-09-23 13:16:25'),
(1450, 97, 'office', NULL, NULL, 240, '2025-09-23 13:16:37'),
(1451, 97, 'office', NULL, NULL, 240, '2025-09-23 13:16:39'),
(1452, 99, 'office', NULL, NULL, 242, '2025-09-23 13:16:47'),
(1453, 99, 'office', NULL, NULL, 242, '2025-09-23 13:16:58'),
(1454, 98, 'office', NULL, NULL, 241, '2025-09-23 13:17:31'),
(1455, 97, 'office', NULL, NULL, 240, '2025-09-23 13:18:10'),
(1456, 96, 'office', NULL, NULL, 239, '2025-09-23 13:18:16'),
(1457, 96, 'office', NULL, NULL, 239, '2025-09-23 13:21:33'),
(1458, 97, 'office', NULL, NULL, 240, '2025-09-23 13:21:39'),
(1459, 98, 'office', NULL, NULL, 241, '2025-09-23 13:21:45'),
(1460, 102, 'office', NULL, NULL, 245, '2025-09-23 13:21:53'),
(1461, 103, 'office', NULL, NULL, 246, '2025-09-23 13:22:00'),
(1462, 96, 'office', NULL, NULL, 239, '2025-09-23 13:22:09'),
(1463, 96, 'office', NULL, NULL, 239, '2025-09-23 13:23:44'),
(1464, 96, 'office', NULL, NULL, 239, '2025-09-23 13:23:51'),
(1465, 97, 'office', NULL, NULL, 240, '2025-09-23 13:24:18'),
(1466, 97, 'office', NULL, NULL, 240, '2025-09-23 13:24:23'),
(1467, 97, 'office', NULL, NULL, 240, '2025-09-23 13:24:45'),
(1468, 99, 'office', NULL, NULL, 242, '2025-09-23 13:24:51'),
(1469, 98, 'office', NULL, NULL, 241, '2025-09-23 13:24:57'),
(1470, 102, 'office', NULL, NULL, 245, '2025-09-23 13:25:04'),
(1471, 102, 'office', NULL, NULL, 245, '2025-09-23 13:26:34'),
(1472, 102, 'office', NULL, NULL, 245, '2025-09-23 13:26:45'),
(1473, 102, 'office', NULL, NULL, 245, '2025-09-23 13:26:50'),
(1474, 96, 'office', NULL, NULL, 239, '2025-09-23 13:27:04'),
(1475, 98, 'office', NULL, NULL, 241, '2025-09-23 13:27:11'),
(1476, 98, 'office', NULL, NULL, 241, '2025-09-23 13:27:22'),
(1477, 98, 'office', NULL, NULL, 241, '2025-09-23 13:27:26'),
(1478, 96, 'office', NULL, NULL, 239, '2025-09-23 13:27:38'),
(1479, 96, 'office', NULL, NULL, 239, '2025-09-23 13:27:45'),
(1480, 98, 'office', NULL, NULL, 241, '2025-09-23 13:27:51'),
(1481, 98, 'office', NULL, NULL, 241, '2025-09-23 13:28:01'),
(1482, 96, 'office', NULL, NULL, 239, '2025-09-23 13:28:12'),
(1483, 96, 'office', NULL, NULL, 239, '2025-09-23 13:28:24'),
(1484, 96, 'office', NULL, NULL, 239, '2025-09-23 13:28:28'),
(1485, 103, 'office', NULL, NULL, 246, '2025-09-23 13:28:36'),
(1486, 103, 'office', NULL, NULL, 246, '2025-09-23 13:28:45'),
(1487, 97, 'office', NULL, NULL, 240, '2025-09-23 13:29:03'),
(1488, 98, 'office', NULL, NULL, 241, '2025-09-23 13:29:12'),
(1489, 99, 'office', NULL, NULL, 242, '2025-09-23 13:29:21'),
(1490, 103, 'office', NULL, NULL, 246, '2025-09-23 13:29:29'),
(1491, 103, 'office', NULL, NULL, 246, '2025-09-23 13:34:44'),
(1492, 96, 'office', NULL, NULL, 239, '2025-09-23 13:35:00'),
(1493, 98, 'office', NULL, NULL, 241, '2025-09-23 13:35:12'),
(1494, 103, 'office', NULL, NULL, 246, '2025-09-23 13:35:26'),
(1495, 103, 'office', NULL, NULL, 246, '2025-09-23 13:35:31'),
(1496, 103, 'office', NULL, NULL, 246, '2025-09-23 13:35:36'),
(1497, 99, 'office', NULL, NULL, 242, '2025-09-23 13:36:01'),
(1498, 105, 'office', NULL, NULL, 248, '2025-09-23 13:36:15'),
(1499, 96, 'office', NULL, NULL, 239, '2025-09-23 13:36:21'),
(1500, 96, 'office', NULL, NULL, 239, '2025-09-23 13:37:31'),
(1501, 98, 'office', NULL, NULL, 241, '2025-09-23 13:37:39'),
(1502, 97, 'office', NULL, NULL, 240, '2025-09-23 13:37:45'),
(1503, 99, 'office', NULL, NULL, 242, '2025-09-23 13:37:52'),
(1504, 99, 'office', NULL, NULL, 242, '2025-09-23 13:38:03'),
(1505, 99, 'office', NULL, NULL, 242, '2025-09-23 13:38:13'),
(1506, 97, 'office', NULL, NULL, 240, '2025-09-23 13:38:20'),
(1507, 96, 'office', NULL, NULL, 239, '2025-09-23 13:38:26'),
(1508, 98, 'office', NULL, NULL, 241, '2025-09-23 13:38:32'),
(1509, 96, 'office', NULL, NULL, 239, '2025-09-23 13:38:40'),
(1510, 102, 'office', NULL, NULL, 245, '2025-09-23 13:38:54'),
(1511, 102, 'office', NULL, NULL, 245, '2025-09-23 13:39:53'),
(1512, 98, 'office', NULL, NULL, 241, '2025-09-23 13:40:11'),
(1513, 98, 'office', NULL, NULL, 241, '2025-09-23 13:40:24'),
(1514, 96, 'office', NULL, NULL, 239, '2025-09-23 13:41:06'),
(1515, 99, 'office', NULL, NULL, 242, '2025-09-23 13:41:13'),
(1516, 99, 'office', NULL, NULL, 242, '2025-09-23 13:43:23'),
(1517, 98, 'office', NULL, NULL, 241, '2025-09-23 13:43:37'),
(1518, 96, 'office', NULL, NULL, 239, '2025-09-23 13:43:43'),
(1519, 97, 'office', NULL, NULL, 240, '2025-09-23 13:43:48'),
(1520, 102, 'office', NULL, NULL, 245, '2025-09-23 13:43:56'),
(1521, 98, 'office', NULL, NULL, 241, '2025-09-23 13:44:07'),
(1522, 96, 'office', NULL, NULL, 239, '2025-09-23 13:45:59'),
(1523, 99, 'office', NULL, NULL, 242, '2025-09-23 13:46:05'),
(1524, 100, 'office', NULL, NULL, 243, '2025-09-23 13:46:18'),
(1525, 97, 'office', NULL, NULL, 240, '2025-09-23 13:46:28'),
(1526, 100, 'office', NULL, NULL, 243, '2025-09-23 13:46:33'),
(1527, 99, 'office', NULL, NULL, 242, '2025-09-23 13:46:38'),
(1528, 103, 'office', NULL, NULL, 246, '2025-09-23 13:46:44'),
(1529, 98, 'office', NULL, NULL, 241, '2025-09-23 13:46:50'),
(1530, 96, 'office', NULL, NULL, 239, '2025-09-23 13:46:57'),
(1531, 96, 'office', NULL, NULL, 239, '2025-09-23 13:47:27'),
(1532, 98, 'office', NULL, NULL, 241, '2025-09-23 13:47:32'),
(1533, 102, 'office', NULL, NULL, 245, '2025-09-23 13:47:43'),
(1534, 103, 'office', NULL, NULL, 246, '2025-09-23 13:47:48'),
(1535, 103, 'office', NULL, NULL, 246, '2025-09-23 13:49:05'),
(1536, 97, 'office', NULL, NULL, 240, '2025-09-23 13:49:19'),
(1537, 96, 'office', NULL, NULL, 239, '2025-09-23 13:49:25'),
(1538, 98, 'office', NULL, NULL, 241, '2025-09-23 13:49:30'),
(1539, 102, 'office', NULL, NULL, 245, '2025-09-23 13:49:35'),
(1540, 102, 'office', NULL, NULL, 245, '2025-09-23 13:49:39'),
(1541, 102, 'office', NULL, NULL, 245, '2025-09-23 13:49:42'),
(1542, 102, 'office', NULL, NULL, 245, '2025-09-23 13:49:45'),
(1543, 97, 'office', NULL, NULL, 240, '2025-09-23 13:49:50'),
(1544, 99, 'office', NULL, NULL, 242, '2025-09-23 13:49:55'),
(1545, 98, 'office', NULL, NULL, 241, '2025-09-23 13:50:00'),
(1546, 96, 'office', NULL, NULL, 239, '2025-09-23 13:50:13'),
(1547, 96, 'office', NULL, NULL, 239, '2025-09-23 13:50:17'),
(1548, 97, 'office', NULL, NULL, 240, '2025-09-23 13:50:29'),
(1549, 96, 'office', NULL, NULL, 239, '2025-09-23 13:50:38'),
(1550, 96, 'office', NULL, NULL, 239, '2025-09-23 13:50:44'),
(1551, 96, 'office', NULL, NULL, 239, '2025-09-23 13:50:44'),
(1552, 96, 'office', NULL, NULL, 239, '2025-09-23 13:52:00'),
(1553, 98, 'office', NULL, NULL, 241, '2025-09-23 13:52:29'),
(1554, 96, 'office', NULL, NULL, 239, '2025-09-23 13:52:36'),
(1555, 96, 'office', NULL, NULL, 239, '2025-09-23 13:52:39'),
(1556, 96, 'office', NULL, NULL, 239, '2025-09-23 13:52:42'),
(1557, 97, 'office', NULL, NULL, 240, '2025-09-23 13:52:49'),
(1558, 99, 'office', NULL, NULL, 242, '2025-09-23 13:52:56'),
(1559, 103, 'office', NULL, NULL, 246, '2025-09-23 13:53:06'),
(1560, 102, 'office', NULL, NULL, 245, '2025-09-23 13:53:16'),
(1561, 98, 'office', NULL, NULL, 241, '2025-09-23 13:53:29'),
(1562, 98, 'office', NULL, NULL, 241, '2025-09-23 13:53:32'),
(1563, 96, 'office', NULL, NULL, 239, '2025-09-23 13:53:38'),
(1564, 96, 'office', NULL, NULL, 239, '2025-09-23 13:53:46'),
(1565, 96, 'office', NULL, NULL, 239, '2025-09-23 13:53:51'),
(1566, 97, 'office', NULL, NULL, 240, '2025-09-23 13:53:56'),
(1567, 100, 'office', NULL, NULL, 243, '2025-09-23 13:54:11'),
(1568, 100, 'office', NULL, NULL, 243, '2025-09-23 13:54:21'),
(1569, 99, 'office', NULL, NULL, 242, '2025-09-23 13:54:29'),
(1570, 101, 'office', NULL, NULL, 244, '2025-09-23 13:54:39'),
(1571, 101, 'office', NULL, NULL, 244, '2025-09-23 13:54:43'),
(1572, 98, 'office', NULL, NULL, 241, '2025-09-23 13:54:52'),
(1573, 98, 'office', NULL, NULL, 241, '2025-09-23 13:56:17'),
(1574, 98, 'office', NULL, NULL, 241, '2025-09-23 13:56:21'),
(1575, 97, 'office', NULL, NULL, 240, '2025-09-23 13:56:51'),
(1576, 99, 'office', NULL, NULL, 242, '2025-09-23 13:57:06'),
(1577, 96, 'office', NULL, NULL, 239, '2025-09-23 13:57:12'),
(1578, 98, 'office', NULL, NULL, 241, '2025-09-23 13:57:21'),
(1579, 102, 'office', NULL, NULL, 245, '2025-09-23 13:57:28'),
(1580, 103, 'office', NULL, NULL, 246, '2025-09-23 13:57:35'),
(1581, 103, 'office', NULL, NULL, 246, '2025-09-23 13:57:44'),
(1582, 98, 'office', NULL, NULL, 241, '2025-09-23 13:57:49'),
(1583, 96, 'office', NULL, NULL, 239, '2025-09-23 13:57:55'),
(1584, 98, 'office', NULL, NULL, 241, '2025-09-23 13:58:01'),
(1585, 97, 'office', NULL, NULL, 240, '2025-09-23 13:58:07'),
(1586, 105, 'office', NULL, NULL, 248, '2025-09-23 13:58:12'),
(1587, 103, 'office', NULL, NULL, 246, '2025-09-23 13:58:18'),
(1588, 98, 'office', NULL, NULL, 241, '2025-09-23 13:58:25'),
(1589, 98, 'office', NULL, NULL, 241, '2025-09-23 14:05:02'),
(1590, 96, 'office', NULL, NULL, 239, '2025-09-23 14:05:07'),
(1591, 96, 'office', NULL, NULL, 239, '2025-09-23 14:13:39'),
(1592, 96, 'office', NULL, NULL, 239, '2025-09-23 14:13:59'),
(1593, 96, 'office', NULL, NULL, 239, '2025-09-23 14:15:45'),
(1594, 96, 'office', NULL, NULL, 239, '2025-09-23 14:15:57'),
(1595, 96, 'office', NULL, NULL, 239, '2025-09-23 14:18:50'),
(1596, 96, 'office', NULL, NULL, 239, '2025-09-23 14:18:55');
INSERT INTO `qr_scan_logs` (`id`, `office_id`, `qr_type`, `panorama_id`, `location_context`, `qr_code_id`, `check_in_time`) VALUES
(1597, 97, 'office', NULL, NULL, 240, '2025-09-23 14:19:04'),
(1598, 97, 'office', NULL, NULL, 240, '2025-09-23 14:19:18'),
(1599, 98, 'office', NULL, NULL, 241, '2025-09-23 14:19:42'),
(1600, 96, 'office', NULL, NULL, 239, '2025-09-23 14:19:59'),
(1601, 97, 'office', NULL, NULL, 240, '2025-09-23 14:20:08'),
(1602, 99, 'office', NULL, NULL, 242, '2025-09-23 14:20:13'),
(1603, 100, 'office', NULL, NULL, 243, '2025-09-23 14:20:21'),
(1604, 102, 'office', NULL, NULL, 245, '2025-09-23 14:20:38'),
(1605, 103, 'office', NULL, NULL, 246, '2025-09-23 14:20:45'),
(1606, 98, 'office', NULL, NULL, 241, '2025-09-23 14:23:29'),
(1607, 96, 'office', NULL, NULL, 239, '2025-09-23 14:23:36'),
(1608, 98, 'office', NULL, NULL, 241, '2025-09-23 14:24:53'),
(1609, 96, 'office', NULL, NULL, 239, '2025-09-23 14:25:00'),
(1610, 99, 'office', NULL, NULL, 242, '2025-09-23 14:25:07'),
(1611, 100, 'office', NULL, NULL, 243, '2025-09-23 14:25:15'),
(1612, 97, 'office', NULL, NULL, 240, '2025-09-23 14:25:22'),
(1613, 97, 'office', NULL, NULL, 240, '2025-09-23 14:28:01'),
(1614, 99, 'office', NULL, NULL, 242, '2025-09-23 14:28:06'),
(1615, 96, 'office', NULL, NULL, 239, '2025-09-23 14:28:16'),
(1616, 97, 'office', NULL, NULL, 240, '2025-09-23 14:30:15'),
(1617, 96, 'office', NULL, NULL, 239, '2025-09-23 14:30:28'),
(1618, 98, 'office', NULL, NULL, 241, '2025-09-23 14:30:33'),
(1619, 99, 'office', NULL, NULL, 242, '2025-09-23 14:33:21'),
(1620, 97, 'office', NULL, NULL, 240, '2025-09-23 14:33:32'),
(1621, 98, 'office', NULL, NULL, 241, '2025-09-23 14:33:37'),
(1622, 102, 'office', NULL, NULL, 245, '2025-09-23 14:33:45'),
(1623, 102, 'office', NULL, NULL, 245, '2025-09-23 14:33:49'),
(1624, 103, 'office', NULL, NULL, 246, '2025-09-23 14:33:54'),
(1625, 103, 'office', NULL, NULL, 246, '2025-09-23 14:34:07'),
(1626, 103, 'office', NULL, NULL, 246, '2025-09-23 14:34:20'),
(1627, 99, 'office', NULL, NULL, 242, '2025-09-23 14:34:32'),
(1628, 97, 'office', NULL, NULL, 240, '2025-09-23 14:34:37'),
(1629, 97, 'office', NULL, NULL, 240, '2025-09-23 14:34:47'),
(1630, 96, 'office', NULL, NULL, 239, '2025-09-23 14:34:58'),
(1631, 97, 'office', NULL, NULL, 240, '2025-09-23 14:35:04'),
(1632, 99, 'office', NULL, NULL, 242, '2025-09-23 14:35:11'),
(1633, 96, 'office', NULL, NULL, 239, '2025-09-23 14:36:45'),
(1634, 98, 'office', NULL, NULL, 241, '2025-09-23 14:37:26'),
(1635, 98, 'office', NULL, NULL, 241, '2025-09-23 14:38:35'),
(1636, 96, 'office', NULL, NULL, 239, '2025-09-23 14:39:04'),
(1637, 104, 'office', NULL, NULL, 247, '2025-09-23 14:39:17'),
(1638, 98, 'office', NULL, NULL, 241, '2025-09-23 14:39:43'),
(1639, 98, 'office', NULL, NULL, 241, '2025-09-23 14:39:50'),
(1640, 102, 'office', NULL, NULL, 245, '2025-09-23 14:39:54'),
(1641, 102, 'office', NULL, NULL, 245, '2025-09-23 14:40:00'),
(1642, 98, 'office', NULL, NULL, 241, '2025-09-23 14:42:43'),
(1643, 99, 'office', NULL, NULL, 242, '2025-09-23 14:44:11'),
(1644, 97, 'office', NULL, NULL, 240, '2025-09-23 14:49:11'),
(1645, 99, 'office', NULL, NULL, 242, '2025-09-23 14:49:19'),
(1646, 99, 'office', NULL, NULL, 242, '2025-09-23 14:52:42'),
(1647, 99, 'office', NULL, NULL, 242, '2025-09-23 14:52:44'),
(1648, 97, 'office', NULL, NULL, 240, '2025-09-23 14:52:58'),
(1649, 99, 'office', NULL, NULL, 242, '2025-09-23 14:53:04'),
(1650, 98, 'office', NULL, NULL, 241, '2025-09-23 15:05:28'),
(1651, 98, 'office', NULL, NULL, 241, '2025-09-23 15:05:37'),
(1652, 98, 'office', NULL, NULL, 241, '2025-09-23 15:05:40'),
(1653, 97, 'office', NULL, NULL, 240, '2025-09-23 15:05:53'),
(1654, 99, 'office', NULL, NULL, 242, '2025-09-23 15:05:59'),
(1655, 105, 'office', NULL, NULL, 248, '2025-09-23 15:06:10'),
(1656, 98, 'office', NULL, NULL, 241, '2025-09-23 15:06:46'),
(1657, 98, 'office', NULL, NULL, 241, '2025-09-23 15:09:38'),
(1658, 96, 'office', NULL, NULL, 239, '2025-09-23 15:09:53'),
(1659, 96, 'office', NULL, NULL, 239, '2025-09-23 15:13:20'),
(1660, 98, 'office', NULL, NULL, 241, '2025-09-23 15:13:38'),
(1661, 98, 'office', NULL, NULL, 241, '2025-09-23 15:14:04'),
(1662, 98, 'office', NULL, NULL, 241, '2025-09-23 15:16:21'),
(1663, 98, 'office', NULL, NULL, 241, '2025-09-23 15:16:22'),
(1664, 98, 'office', NULL, NULL, 241, '2025-09-23 15:16:35'),
(1665, 98, 'office', NULL, NULL, 241, '2025-09-23 15:16:43'),
(1666, 98, 'office', NULL, NULL, 241, '2025-09-23 15:16:46'),
(1667, 98, 'office', NULL, NULL, 241, '2025-09-23 15:17:00'),
(1668, 98, 'office', NULL, NULL, 241, '2025-09-23 15:17:08'),
(1669, 98, 'office', NULL, NULL, 241, '2025-09-23 15:17:22'),
(1670, 98, 'office', NULL, NULL, 241, '2025-09-23 15:17:34'),
(1671, 98, 'office', NULL, NULL, 241, '2025-09-23 15:20:06'),
(1672, 98, 'office', NULL, NULL, 241, '2025-09-23 15:20:33'),
(1673, 96, 'office', NULL, NULL, 239, '2025-09-23 15:20:49'),
(1674, 100, 'office', NULL, NULL, 243, '2025-09-23 15:21:01'),
(1675, 97, 'office', NULL, NULL, 240, '2025-09-23 15:21:06'),
(1676, 99, 'office', NULL, NULL, 242, '2025-09-23 15:21:12'),
(1677, 97, 'office', NULL, NULL, 240, '2025-09-23 15:22:02'),
(1678, 105, 'office', NULL, NULL, 248, '2025-09-23 15:22:21'),
(1679, 105, 'office', NULL, NULL, 248, '2025-09-23 15:23:07'),
(1680, 98, 'office', NULL, NULL, 241, '2025-09-23 15:30:08'),
(1681, 96, 'office', NULL, NULL, 239, '2025-09-23 15:30:20'),
(1682, 96, 'office', NULL, NULL, 239, '2025-09-23 15:30:22'),
(1683, 98, 'office', NULL, NULL, 241, '2025-09-23 15:30:42'),
(1684, 99, 'office', NULL, NULL, 242, '2025-09-23 15:56:55'),
(1685, 99, 'office', NULL, NULL, 242, '2025-09-23 17:08:40'),
(1686, 109, 'office', NULL, NULL, 252, '2025-10-10 05:15:13'),
(1687, 109, 'office', NULL, NULL, 252, '2025-10-10 05:15:39'),
(1688, 109, 'office', NULL, NULL, 252, '2025-10-10 05:16:18'),
(1689, 109, 'office', NULL, NULL, 252, '2025-10-10 09:15:41'),
(1690, 109, 'office', NULL, NULL, 252, '2025-10-10 09:15:52'),
(1691, 109, 'office', NULL, NULL, 252, '2025-10-10 09:26:58'),
(1692, 109, 'office', NULL, NULL, 252, '2025-10-10 09:27:32'),
(1693, 109, 'office', NULL, NULL, 252, '2025-10-10 09:29:44'),
(1694, 100, 'office', NULL, NULL, 243, '2025-10-10 10:06:57'),
(1695, 99, 'office', NULL, NULL, 242, '2025-10-10 10:07:05'),
(1696, 102, 'office', NULL, NULL, 245, '2025-10-10 10:20:30'),
(1697, 110, 'office', NULL, NULL, 253, '2025-10-10 10:26:19'),
(1698, 110, 'office', NULL, NULL, 253, '2025-10-10 10:27:17'),
(1699, 98, 'office', NULL, NULL, 241, '2025-10-10 10:27:50'),
(1700, 103, 'office', NULL, NULL, 246, '2025-10-11 02:20:06'),
(1701, 111, 'office', NULL, NULL, 254, '2025-10-11 02:29:52'),
(1702, 109, 'office', NULL, NULL, 252, '2025-10-11 09:22:13'),
(1703, 111, 'office', NULL, NULL, 254, '2025-10-11 11:30:59');

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
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feed_id`),
  ADD KEY `fk_feedback_office` (`office_id`);

--
-- Indexes for table `floor_plan`
--
ALTER TABLE `floor_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_floorplan_office` (`office_id`);

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
  ADD KEY `qr_scan_logs_ibfk_2` (`qr_code_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=298;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `animated_hotspot_icons`
--
ALTER TABLE `animated_hotspot_icons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `animated_hotspot_videos`
--
ALTER TABLE `animated_hotspot_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `floor_plan`
--
ALTER TABLE `floor_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `office_hours`
--
ALTER TABLE `office_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `office_image`
--
ALTER TABLE `office_image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `panorama_hotspots`
--
ALTER TABLE `panorama_hotspots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=489;

--
-- AUTO_INCREMENT for table `panorama_image`
--
ALTER TABLE `panorama_image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `panorama_qr_scans`
--
ALTER TABLE `panorama_qr_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `qrcode_info`
--
ALTER TABLE `qrcode_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- AUTO_INCREMENT for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1704;

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
