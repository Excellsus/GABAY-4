-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2025 at 05:32 PM
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `office_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `activity_type`, `activity_text`, `created_at`, `office_id`, `admin_id`) VALUES
(1, 'user', 'Profile information updated', '2025-05-26 17:50:00', NULL, NULL),
(2, 'file', 'Floor plan modified', '2025-05-26 17:50:00', NULL, NULL),
(3, 'office', 'New office added', '2025-05-26 17:50:00', NULL, NULL),
(4, 'feedback', 'New feedback received', '2025-05-26 17:50:00', NULL, NULL),
(5, 'test', 'Test activity from diagnostic script', '2025-05-26 17:50:00', NULL, NULL),
(6, 'office', 'Office \'PAO\' was deleted', '2025-05-26 17:54:30', NULL, NULL),
(7, 'office', 'New office \'azriel\' added', '2025-05-26 17:57:53', NULL, NULL),
(15, 'office', 'New office \'Accounting\' added', '2025-05-26 18:09:59', NULL, NULL),
(16, 'office', 'New office \'STI\' added', '2025-05-26 18:10:36', NULL, NULL),
(26, 'file', 'Floor plan location updated for STI', '2025-05-26 18:14:36', NULL, NULL),
(27, 'file', 'Floor plan location updated for Accounting', '2025-05-26 18:14:36', NULL, NULL),
(30, 'office', 'New office \'azriel\' added', '2025-05-26 18:31:00', NULL, NULL),
(31, 'office', 'New office \'Accounting\' added', '2025-05-26 18:31:21', NULL, NULL),
(34, 'office', 'New office \'umayyy\' added', '2025-05-26 18:37:31', NULL, NULL),
(35, 'office', 'New office \'Accounting\' added', '2025-05-26 18:37:51', NULL, NULL),
(39, 'office', 'New office \'umayyy\' added', '2025-05-26 18:41:37', NULL, NULL),
(42, 'office', 'New office \'azriel\' added', '2025-05-26 18:47:30', NULL, NULL),
(43, 'office', 'Office \'azriel\' was deleted', '2025-05-26 18:47:39', NULL, NULL),
(44, 'office', 'New office \'azriel\' added', '2025-05-26 18:47:53', NULL, NULL),
(45, 'office', 'New office \'Accounting\' added', '2025-05-26 18:55:52', NULL, NULL),
(46, 'file', 'Floor plan location updated for azriel', '2025-05-26 19:00:53', NULL, NULL),
(47, 'file', 'Floor plan location updated for Accounting', '2025-05-26 19:00:53', NULL, NULL),
(48, 'file', 'Floor plan location updated for azriel', '2025-05-26 19:01:03', NULL, NULL),
(49, 'file', 'Floor plan location updated for Accounting', '2025-05-26 19:01:03', NULL, NULL),
(50, 'file', 'Floor plan location updated for azriel', '2025-05-26 19:01:36', NULL, NULL),
(51, 'file', 'Floor plan location updated for Accounting', '2025-05-26 19:01:36', NULL, NULL),
(52, 'file', 'Floor plan location updated for azriel', '2025-05-26 19:03:44', NULL, NULL),
(53, 'file', 'Floor plan location updated for Accounting', '2025-05-26 19:03:44', NULL, NULL),
(54, 'file', 'Floor plan location updated for azriel', '2025-05-26 19:03:51', NULL, NULL),
(55, 'file', 'Floor plan location updated for Accounting', '2025-05-26 19:03:51', NULL, NULL),
(56, 'file', 'Floor plan location swapped with Accounting', '2025-05-26 19:10:36', NULL, NULL),
(57, 'file', 'Floor plan location updated for Accounting', '2025-05-26 19:10:36', NULL, NULL),
(58, 'file', 'Floor plan location updated for azriel', '2025-05-26 19:10:36', NULL, NULL),
(59, 'file', 'Floor plan location swapped with azriel', '2025-05-26 19:10:41', NULL, NULL),
(60, 'file', 'Floor plan location updated for azriel', '2025-05-26 19:10:41', NULL, NULL),
(61, 'file', 'Floor plan location updated for Accounting', '2025-05-26 19:10:41', NULL, NULL),
(62, 'file', 'Floor plan location swapped with Accounting', '2025-05-26 19:10:54', NULL, NULL),
(63, 'file', 'Floor plan location updated for Accounting', '2025-05-26 19:10:54', NULL, NULL),
(64, 'file', 'Floor plan location updated for azriel', '2025-05-26 19:10:54', NULL, NULL),
(65, 'file', 'Floor plan location swapped with azriel', '2025-05-27 01:45:43', NULL, NULL),
(66, 'file', 'Floor plan location updated for azriel', '2025-05-27 01:45:43', NULL, NULL),
(67, 'file', 'Floor plan location updated for Accounting', '2025-05-27 01:45:43', NULL, NULL),
(68, 'file', 'Floor plan location swapped with Accounting', '2025-05-27 01:45:50', NULL, NULL),
(69, 'file', 'Floor plan location updated for Accounting', '2025-05-27 01:45:50', NULL, NULL),
(70, 'file', 'Floor plan location updated for azriel', '2025-05-27 01:45:50', NULL, NULL),
(71, 'file', 'Floor plan location swapped with azriel', '2025-05-27 03:37:25', NULL, NULL),
(72, 'file', 'Floor plan location updated for azriel', '2025-05-27 03:37:25', NULL, NULL),
(73, 'file', 'Floor plan location updated for Accounting', '2025-05-27 03:37:25', NULL, NULL),
(74, 'feedback', 'New feedback received', '2025-05-27 04:39:49', NULL, NULL),
(75, 'file', 'Floor plan location swapped with Accounting', '2025-05-27 05:36:02', NULL, NULL),
(76, 'file', 'Floor plan location updated for Accounting', '2025-05-27 05:36:02', NULL, NULL),
(77, 'file', 'Floor plan location updated for azriel', '2025-05-27 05:36:02', NULL, NULL),
(78, 'office', 'New office \'umayyy\' added', '2025-05-27 07:19:07', NULL, NULL),
(79, 'file', 'Floor plan location swapped with umayyy', '2025-05-27 07:24:37', NULL, NULL),
(80, 'file', 'Floor plan location updated for umayyy', '2025-05-27 07:24:37', NULL, NULL),
(81, 'file', 'Floor plan location updated for azriel', '2025-05-27 07:24:37', NULL, NULL),
(82, 'file', 'Floor plan location updated for Accounting', '2025-05-27 07:24:37', NULL, NULL),
(83, 'file', 'Floor plan location updated for umayyy', '2025-05-27 07:31:50', NULL, NULL),
(84, 'file', 'Floor plan location updated for azriel', '2025-05-27 07:31:50', NULL, NULL),
(85, 'file', 'Floor plan location updated for Accounting', '2025-05-27 07:31:50', NULL, NULL),
(86, 'file', 'Floor plan location updated for umayyy', '2025-05-27 07:56:45', NULL, NULL),
(87, 'file', 'Floor plan location updated for azriel', '2025-05-27 07:56:45', NULL, NULL),
(88, 'file', 'Floor plan location updated for Accounting', '2025-05-27 07:56:45', NULL, NULL),
(89, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:07:33', NULL, NULL),
(90, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:07:33', NULL, NULL),
(91, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:07:33', NULL, NULL),
(92, 'file', 'Floor plan location swapped with azriel', '2025-05-27 08:07:38', NULL, NULL),
(93, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:07:38', NULL, NULL),
(94, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:07:38', NULL, NULL),
(95, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:07:38', NULL, NULL),
(96, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:10:59', NULL, NULL),
(97, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:10:59', NULL, NULL),
(98, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:10:59', NULL, NULL),
(99, 'file', 'Floor plan location swapped with umayyy', '2025-05-27 08:14:41', NULL, NULL),
(100, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:14:41', NULL, NULL),
(101, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:14:41', NULL, NULL),
(102, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:14:41', NULL, NULL),
(103, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:19:21', NULL, NULL),
(104, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:19:21', NULL, NULL),
(105, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:19:21', NULL, NULL),
(106, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:19:32', NULL, NULL),
(107, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:19:32', NULL, NULL),
(108, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:19:32', NULL, NULL),
(109, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:20:15', NULL, NULL),
(110, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:20:15', NULL, NULL),
(111, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:20:15', NULL, NULL),
(112, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:23:09', NULL, NULL),
(113, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:23:09', NULL, NULL),
(114, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:23:09', NULL, NULL),
(115, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:28:11', NULL, NULL),
(116, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:28:11', NULL, NULL),
(117, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:28:11', NULL, NULL),
(118, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:31:00', NULL, NULL),
(119, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:31:00', NULL, NULL),
(120, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:31:00', NULL, NULL),
(121, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:34:36', NULL, NULL),
(122, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:34:36', NULL, NULL),
(123, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:34:36', NULL, NULL),
(124, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:35:49', NULL, NULL),
(125, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:35:49', NULL, NULL),
(126, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:35:49', NULL, NULL),
(127, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:35:58', NULL, NULL),
(128, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:35:58', NULL, NULL),
(129, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:35:58', NULL, NULL),
(130, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:53:51', NULL, NULL),
(131, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:53:51', NULL, NULL),
(132, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:53:51', NULL, NULL),
(133, 'file', 'Floor plan location updated for azriel', '2025-05-27 08:58:54', NULL, NULL),
(134, 'file', 'Floor plan location updated for umayyy', '2025-05-27 08:58:54', NULL, NULL),
(135, 'file', 'Floor plan location updated for Accounting', '2025-05-27 08:58:54', NULL, NULL),
(136, 'file', 'Floor plan location swapped with umayyy', '2025-05-27 09:16:58', NULL, NULL),
(137, 'file', 'Floor plan location updated for umayyy', '2025-05-27 09:16:58', NULL, NULL),
(138, 'file', 'Floor plan location updated for azriel', '2025-05-27 09:16:58', NULL, NULL),
(139, 'file', 'Floor plan location updated for Accounting', '2025-05-27 09:16:58', NULL, NULL),
(140, 'file', 'Floor plan location updated for umayyy', '2025-05-27 09:20:04', NULL, NULL),
(141, 'file', 'Floor plan location updated for azriel', '2025-05-27 09:20:04', NULL, NULL),
(142, 'file', 'Floor plan location updated for Accounting', '2025-05-27 09:20:04', NULL, NULL),
(143, 'file', 'Floor plan location updated for umayyy', '2025-05-27 09:29:08', NULL, NULL),
(144, 'file', 'Floor plan location swapped with Accounting', '2025-05-27 09:29:08', NULL, NULL),
(145, 'file', 'Floor plan location updated for Accounting', '2025-05-27 09:29:08', NULL, NULL),
(146, 'file', 'Floor plan location updated for azriel', '2025-05-27 09:29:08', NULL, NULL),
(147, 'file', 'Floor plan location swapped with umayyy', '2025-05-27 09:34:02', NULL, NULL),
(148, 'file', 'Floor plan location updated for umayyy', '2025-05-27 09:34:02', NULL, NULL),
(149, 'file', 'Floor plan location updated for azriel', '2025-05-27 09:34:02', NULL, NULL),
(150, 'file', 'Floor plan location updated for Accounting', '2025-05-27 09:34:02', NULL, NULL),
(151, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:00:15', NULL, NULL),
(152, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:00:15', NULL, NULL),
(153, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:00:15', NULL, NULL),
(154, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:18:12', NULL, NULL),
(155, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:18:12', NULL, NULL),
(156, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:18:12', NULL, NULL),
(157, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:18:23', NULL, NULL),
(158, 'file', 'Floor plan location swapped with Accounting', '2025-05-27 10:18:23', NULL, NULL),
(159, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:18:23', NULL, NULL),
(160, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:18:23', NULL, NULL),
(161, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:27:53', NULL, NULL),
(162, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:27:53', NULL, NULL),
(163, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:27:53', NULL, NULL),
(164, 'file', 'Floor plan location swapped with Accounting', '2025-05-27 10:42:10', NULL, NULL),
(165, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:42:10', NULL, NULL),
(166, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:42:10', NULL, NULL),
(167, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:42:10', NULL, NULL),
(168, 'file', 'Floor plan location swapped with umayyy', '2025-05-27 10:42:17', NULL, NULL),
(169, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:42:17', NULL, NULL),
(170, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:42:17', NULL, NULL),
(171, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:42:17', NULL, NULL),
(172, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:42:31', NULL, NULL),
(173, 'file', 'Floor plan location swapped with azriel', '2025-05-27 10:42:31', NULL, NULL),
(174, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:42:31', NULL, NULL),
(175, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:42:31', NULL, NULL),
(176, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:42:47', NULL, NULL),
(177, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:42:47', NULL, NULL),
(178, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:42:47', NULL, NULL),
(179, 'file', 'Floor plan location swapped with azriel', '2025-05-27 10:44:41', NULL, NULL),
(180, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:44:41', NULL, NULL),
(181, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:44:41', NULL, NULL),
(182, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:44:41', NULL, NULL),
(183, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:46:53', NULL, NULL),
(184, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:46:53', NULL, NULL),
(185, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:46:53', NULL, NULL),
(186, 'file', 'Floor plan location updated for Accounting', '2025-05-27 10:46:59', NULL, NULL),
(187, 'file', 'Floor plan location updated for umayyy', '2025-05-27 10:46:59', NULL, NULL),
(188, 'file', 'Floor plan location updated for azriel', '2025-05-27 10:46:59', NULL, NULL),
(189, 'office', 'New office \'azriel\' added', '2025-05-27 11:17:37', NULL, NULL),
(190, 'file', 'Floor plan location updated for Accounting', '2025-05-27 12:51:41', NULL, NULL),
(191, 'file', 'Floor plan location updated for azriel', '2025-05-27 12:51:41', NULL, NULL),
(192, 'file', 'Floor plan location updated for umayyy', '2025-05-27 12:51:41', NULL, NULL),
(193, 'file', 'Floor plan location updated for azriel', '2025-05-27 12:51:41', NULL, NULL),
(194, 'file', 'Floor plan location updated for Accounting', '2025-05-27 12:55:47', NULL, NULL),
(195, 'file', 'Floor plan location swapped with umayyy', '2025-05-27 12:55:47', NULL, NULL),
(196, 'file', 'Floor plan location updated for umayyy', '2025-05-27 12:55:47', NULL, NULL),
(197, 'file', 'Floor plan location updated for azriel', '2025-05-27 12:55:47', NULL, NULL),
(198, 'file', 'Floor plan location updated for azriel', '2025-05-27 12:55:47', NULL, NULL),
(199, 'file', 'Floor plan location swapped with azriel', '2025-05-27 12:55:57', NULL, NULL),
(200, 'file', 'Floor plan location updated for azriel', '2025-05-27 12:55:57', NULL, NULL),
(201, 'file', 'Floor plan location updated for umayyy', '2025-05-27 12:55:57', NULL, NULL),
(202, 'file', 'Floor plan location updated for Accounting', '2025-05-27 12:55:57', NULL, NULL),
(203, 'file', 'Floor plan location updated for azriel', '2025-05-27 12:55:57', NULL, NULL),
(204, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:18:45', NULL, NULL),
(205, 'file', 'Floor plan location updated for umayyy', '2025-05-27 13:18:45', NULL, NULL),
(206, 'file', 'Floor plan location updated for Accounting', '2025-05-27 13:18:45', NULL, NULL),
(207, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:18:45', NULL, NULL),
(208, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:18:50', NULL, NULL),
(209, 'file', 'Floor plan location swapped with Accounting', '2025-05-27 13:18:50', NULL, NULL),
(210, 'file', 'Floor plan location updated for Accounting', '2025-05-27 13:18:50', NULL, NULL),
(211, 'file', 'Floor plan location updated for umayyy', '2025-05-27 13:18:50', NULL, NULL),
(212, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:18:50', NULL, NULL),
(213, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:27:28', NULL, NULL),
(214, 'file', 'Floor plan location swapped with umayyy', '2025-05-27 13:27:28', NULL, NULL),
(215, 'file', 'Floor plan location updated for umayyy', '2025-05-27 13:27:28', NULL, NULL),
(216, 'file', 'Floor plan location updated for Accounting', '2025-05-27 13:27:28', NULL, NULL),
(217, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:27:28', NULL, NULL),
(218, 'file', 'Floor plan location swapped with umayyy', '2025-05-27 13:36:42', NULL, NULL),
(219, 'file', 'Floor plan location updated for umayyy', '2025-05-27 13:36:42', NULL, NULL),
(220, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:36:42', NULL, NULL),
(221, 'file', 'Floor plan location updated for Accounting', '2025-05-27 13:36:42', NULL, NULL),
(222, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:36:42', NULL, NULL),
(223, 'file', 'Floor plan location updated for umayyy', '2025-05-27 13:39:24', NULL, NULL),
(224, 'file', 'Floor plan location swapped with Accounting', '2025-05-27 13:39:24', NULL, NULL),
(225, 'file', 'Floor plan location updated for Accounting', '2025-05-27 13:39:24', NULL, NULL),
(226, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:39:24', NULL, NULL),
(227, 'file', 'Floor plan location updated for azriel', '2025-05-27 13:39:24', NULL, NULL),
(228, 'office', 'New office \'hhhhh\' added', '2025-05-27 13:54:36', NULL, NULL),
(229, 'office', 'New office \'STI\' added', '2025-05-27 22:17:31', NULL, NULL),
(230, 'file', 'Floor plan location updated for hhhhh', '2025-05-27 22:17:57', NULL, NULL),
(231, 'file', 'Floor plan location updated for umayyy', '2025-05-27 22:17:57', NULL, NULL),
(232, 'file', 'Floor plan location updated for Accounting', '2025-05-27 22:17:57', NULL, NULL),
(233, 'file', 'Floor plan location updated for azriel', '2025-05-27 22:17:57', NULL, NULL),
(234, 'file', 'Floor plan location swapped with STI', '2025-05-27 22:17:57', NULL, NULL),
(235, 'file', 'Floor plan location updated for STI', '2025-05-27 22:17:57', NULL, NULL),
(236, 'file', 'Floor plan location updated for azriel', '2025-05-27 22:17:57', NULL, NULL),
(237, 'office', 'New office \'PAO\' added', '2025-05-27 22:20:27', NULL, NULL),
(238, 'office', 'Office \'PAO\' was updated', '2025-05-27 22:20:40', NULL, NULL),
(239, 'office', 'Office \'Accounting\' was deleted', '2025-05-27 22:36:56', NULL, NULL),
(240, 'office', 'Office \'azriel\' was deleted', '2025-05-27 22:36:59', NULL, NULL),
(241, 'office', 'Office \'azriel\' was deleted', '2025-05-27 22:37:02', NULL, NULL),
(242, 'office', 'Office \'hhhhh\' was deleted', '2025-05-27 22:37:07', NULL, NULL),
(243, 'office', 'Office \'PAO\' was deleted', '2025-05-27 22:37:15', NULL, NULL),
(244, 'office', 'Office \'STI\' was deleted', '2025-05-27 22:37:21', NULL, NULL),
(245, 'office', 'New office \'Human Resources\' added', '2025-05-27 22:42:16', 96, NULL),
(246, 'office', 'Office \'umayyy\' was deleted', '2025-05-27 22:42:22', NULL, NULL),
(247, 'office', 'New office \'Information Technology\' added', '2025-05-27 22:43:30', 97, NULL),
(248, 'office', 'New office \'Public Relations\' added', '2025-05-27 22:44:27', 98, NULL),
(249, 'office', 'New office \'Legal Affairs\' added', '2025-05-27 22:45:18', 99, NULL),
(250, 'office', 'New office \'Procurement\' added', '2025-05-27 22:46:06', 100, NULL),
(251, 'office', 'New office \'Records Management\' added', '2025-05-27 22:47:10', 101, NULL),
(252, 'office', 'Office \'Procurement\' was updated', '2025-05-27 22:47:22', 100, NULL),
(253, 'office', 'New office \'Customer Service\' added', '2025-05-27 22:48:40', 102, NULL),
(254, 'office', 'New office \'Maintenance and Facilities\' added', '2025-05-27 22:52:12', 103, NULL),
(255, 'office', 'New office \'Planning and Development\' added', '2025-05-27 22:56:49', 104, NULL),
(256, 'office', 'New office \'Internal Audit\' added', '2025-05-27 22:57:48', 105, NULL),
(257, 'office', 'Office \'Customer Service\' was updated', '2025-05-27 23:07:58', 102, NULL),
(258, 'feedback', 'New feedback received', '2025-09-08 15:50:57', NULL, NULL),
(259, 'office', 'New office \'Room 1\' added', '2025-09-11 18:03:29', 106, NULL),
(260, 'file', 'Floor plan location updated for Room 1', '2025-09-14 08:38:57', 106, NULL),
(261, 'file', 'Floor plan location updated for Procurement', '2025-09-14 08:38:57', 100, NULL),
(262, 'file', 'Floor plan location updated for Legal Affairs', '2025-09-14 08:38:57', 99, NULL),
(263, 'file', 'Floor plan location updated for Internal Audit', '2025-09-14 08:38:57', 105, NULL),
(264, 'file', 'Floor plan location updated for Human Resources', '2025-09-14 08:38:57', 96, NULL),
(265, 'file', 'Floor plan location updated for Information Technology', '2025-09-14 08:38:57', 97, NULL),
(266, 'file', 'Floor plan location updated for Public Relations', '2025-09-14 08:38:57', 98, NULL),
(267, 'file', 'Floor plan location updated for Records Management', '2025-09-14 08:38:57', 101, NULL),
(268, 'file', 'Floor plan location updated for Planning and Development', '2025-09-14 08:38:57', 104, NULL),
(269, 'file', 'Floor plan location updated for Customer Service', '2025-09-14 08:38:57', 102, NULL),
(270, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-09-14 08:38:57', 103, NULL),
(271, 'file', 'Floor plan location updated for Room 1', '2025-09-14 08:39:04', 106, NULL),
(272, 'file', 'Floor plan location updated for Procurement', '2025-09-14 08:39:04', 100, NULL),
(273, 'file', 'Floor plan location updated for Legal Affairs', '2025-09-14 08:39:04', 99, NULL),
(274, 'file', 'Floor plan location updated for Internal Audit', '2025-09-14 08:39:04', 105, NULL),
(275, 'file', 'Floor plan location swapped with Information Technology', '2025-09-14 08:39:04', 96, NULL),
(276, 'file', 'Floor plan location updated for Information Technology', '2025-09-14 08:39:04', 97, NULL),
(277, 'file', 'Floor plan location updated for Human Resources', '2025-09-14 08:39:04', 96, NULL),
(278, 'file', 'Floor plan location updated for Public Relations', '2025-09-14 08:39:04', 98, NULL),
(279, 'file', 'Floor plan location updated for Records Management', '2025-09-14 08:39:04', 101, NULL),
(280, 'file', 'Floor plan location updated for Planning and Development', '2025-09-14 08:39:04', 104, NULL),
(281, 'file', 'Floor plan location updated for Customer Service', '2025-09-14 08:39:04', 102, NULL),
(282, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-09-14 08:39:04', 103, NULL);

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
(97, 'Information Technology', 'Manages the organization’s computer systems, networks, and software infrastructure.', 'it@organization.gov', 'room-6-1', 'Technical support and troubleshooting\r\n\r\nNetwork and data security\r\n\r\nHardware and software maintenance\r\n\r\nSystem upgrades and installations\r\n\r\nUser account management', '2025-05-27 22:43:30', 'active', NULL),
(98, 'Public Relations', 'Handles media relations, public announcements, and community engagement.', 'pr@organization.gov', 'room-12-1', 'Press releases and media coordination\r\n\r\nEvent planning and coverage\r\n\r\nSocial media management\r\n\r\nPublic feedback handling\r\n\r\nCommunity outreach programs', '2025-05-27 22:44:27', 'active', NULL),
(99, 'Legal Affairs', 'Provides legal support, drafts contracts, and ensures compliance with laws and regulations.', ' legal@organization.gov', 'room-3-1', 'Legal consultations and advice\r\n\r\nContract drafting and review\r\n\r\nCompliance monitoring\r\n\r\nDispute resolution\r\n\r\nLiaison with external legal counsel', '2025-05-27 22:45:18', 'active', NULL),
(100, 'Procurement', 'Responsible for acquiring goods and services required by the organization.', 'procurement@organization.gov', 'room-2-1', 'Supplier evaluation and management\r\n\r\nPurchase order processing\r\n\r\nInventory control\r\n\r\nBidding and quotation evaluation\r\n\r\nContract negotiations', '2025-05-27 22:46:06', 'active', NULL),
(101, 'Records Management', 'Maintains and secures organizational records and archives.', 'records@organization.gov', 'room-10-1', 'Document filing and retrieval\r\n\r\nRecords retention and disposal\r\n\r\nDigital archiving\r\n\r\nRecords classification and indexing\r\n\r\nCompliance with data privacy regulations', '2025-05-27 22:47:10', 'active', NULL),
(102, 'Customer Service', 'Assists visitors and stakeholders with inquiries, requests, and general support.', '09668903760', 'room-14-1', 'Information desk services\r\n\r\nComplaint handling\r\n\r\nApplication assistance\r\n\r\nGeneral inquiries\r\n\r\nFeedback collection', '2025-05-27 22:48:40', 'active', NULL),
(103, 'Maintenance and Facilities', 'Ensures the cleanliness, safety, and functionality of the physical work environment.', 'maintenance@organization.gov', 'room-15-1', 'Building and equipment maintenance\r\n\r\nJanitorial services\r\n\r\nUtilities management\r\n\r\nFacility repairs\r\n\r\nSafety inspections', '2025-05-27 22:52:12', 'active', NULL),
(104, 'Planning and Development', 'Focuses on strategic initiatives, project development, and infrastructure planning.', 'planning@organization.gov', 'room-13-1', 'Project planning and implementation\r\n\r\nData analysis and research\r\n\r\nDevelopment proposals\r\n\r\nUrban and regional planning\r\n\r\nPolicy formulation', '2025-05-27 22:56:49', 'active', NULL),
(105, 'Internal Audit', 'Conducts independent assessments of processes to ensure accountability and transparency.', 'audit@organization.gov', 'room-4-1', 'Internal control evaluations\r\n\r\nRisk assessment\r\n\r\nOperational audits\r\n\r\nFinancial audits\r\n\r\nCompliance reviews', '2025-05-27 22:57:48', 'active', NULL),
(106, 'Room 1', 'just room', 'ads', 'room-1-1', 'asdadsa', '2025-09-11 18:03:29', 'active', NULL);

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
(34, 102, 'Wednesday', '07:00:00', '17:00:00'),
(35, 96, 'Wednesday', '07:00:00', '17:00:00'),
(36, 97, 'Wednesday', '07:00:00', '19:00:00'),
(37, 105, 'Wednesday', '07:00:00', '20:00:00'),
(38, 99, 'Wednesday', '07:00:00', '09:00:00'),
(39, 103, 'Wednesday', '07:56:00', '22:00:00'),
(40, 104, 'Wednesday', '07:00:00', '19:00:00'),
(41, 100, 'Wednesday', '08:00:00', '19:11:00'),
(42, 98, 'Wednesday', '10:00:00', '17:00:00'),
(43, 101, 'Wednesday', '06:00:00', '14:00:00');

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
(33, 105, 'office_6836436cef3305.06982716.webp', '2025-05-28 06:57:48');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores panorama images for point markers with isPano attribute';

--
-- Dumping data for table `panorama_image`
--

INSERT INTO `panorama_image` (`id`, `path_id`, `point_index`, `point_x`, `point_y`, `floor_number`, `image_filename`, `original_filename`, `title`, `description`, `file_size`, `mime_type`, `is_active`, `uploaded_by`, `uploaded_at`, `updated_at`) VALUES
(12, 'path1_floor2', 0, 170.00, 185.00, 2, 'pano_path1_floor2_0_1757954203_68c8409bb4002.jpg', 'f7602a28-730b-4f04-bb2e-e2b76b329a17.jpg', NULL, NULL, 361505, 'image/jpeg', 1, NULL, '2025-09-15 16:36:43', '2025-09-15 16:36:43'),
(13, 'lobby_vertical_1_floor3', 1, 845.00, 233.00, 3, 'pano_lobby_vertical_1_floor3_1_1757954235_68c840bbdbdd1.jpg', '62b5213f-4e9b-4c20-938d-cd121e77fa68.jpg', NULL, NULL, 897235, 'image/jpeg', 1, NULL, '2025-09-15 16:37:15', '2025-09-15 16:37:15'),
(15, 'path1', 5, 165.00, 340.00, 1, 'pano_path1_5_1758031418_68c96e3a1d147.jpg', 'd4963c2e-827e-4474-8cae-1cae1732e237.jpg', 'Exit', NULL, 379578, 'image/jpeg', 1, NULL, '2025-09-16 14:03:38', '2025-09-16 14:03:38'),
(17, 'path1', 7, 220.00, 340.00, 1, 'pano_path1_7_1758031559_68c96ec7edf30.jpg', 'f7602a28-730b-4f04-bb2e-e2b76b329a17.jpg', 'Security', NULL, 361505, 'image/jpeg', 1, NULL, '2025-09-16 14:05:59', '2025-09-16 14:05:59'),
(19, 'path1', 4, 165.00, 255.00, 1, 'pano_path1_4_1758031979_68c9706b4f9ff.jpg', '3c4c94e6-ec09-4ede-8078-1af34e00975d.jpg', 'Inner', NULL, 342147, 'image/jpeg', 1, NULL, '2025-09-16 14:12:59', '2025-09-16 14:12:59'),
(20, 'path1', 2, 165.00, 175.00, 1, 'pano_path1_2_1758097274_68ca6f7a1ee01.jpg', '86ed8c30-b083-4981-b9a1-159f09d0bebc.jpg', 'Procurement', NULL, 335786, 'image/jpeg', 1, NULL, '2025-09-17 08:21:14', '2025-09-17 08:21:14'),
(21, 'path1', 1, 130.00, 175.00, 1, 'pano_path1_1_1758098248_68ca73482277c.jpg', 'f9bf2021-a541-444f-b272-af81929147b1.jpg', '1st Floor CR to Stairs', NULL, 304593, 'image/jpeg', 1, NULL, '2025-09-17 08:37:28', '2025-09-17 08:37:28'),
(24, 'path2', 0, 467.00, 220.00, 1, 'pano_path2_0_1758099304_68ca7768c0d44.jpg', 'cdf2ffa4-4532-4903-948c-9dc82b483efd.jpg', 'Me and the bois', NULL, 359876, 'image/jpeg', 1, NULL, '2025-09-17 08:55:04', '2025-09-17 08:55:04'),
(25, 'path2', 7, 950.00, 130.00, 1, 'pano_path2_7_1758099716_68ca7904c9c49.jpg', '077b71e3-79c7-42ba-99bf-9c0dafb9d405.jpg', NULL, NULL, 317898, 'image/jpeg', 1, NULL, '2025-09-17 09:01:56', '2025-09-17 09:01:56'),
(26, 'path2', 6, 880.00, 130.00, 1, 'pano_path2_6_1758100210_68ca7af233fb9.jpg', '70364a1c-f6d5-4c5f-8f8b-7c846190f3db.jpg', NULL, NULL, 258188, 'image/jpeg', 1, NULL, '2025-09-17 09:10:10', '2025-09-17 09:10:10'),
(27, 'path2', 5, 851.00, 130.00, 1, 'pano_path2_5_1758100261_68ca7b25392f7.jpg', 'd3c163d5-4fce-4a1c-bd99-fe8cba293789.jpg', NULL, NULL, 337021, 'image/jpeg', 1, NULL, '2025-09-17 09:11:01', '2025-09-17 09:11:01'),
(28, 'path2', 4, 850.00, 220.00, 1, 'pano_path2_4_1758100366_68ca7b8eb4e92.jpg', '9163cc42-9f68-4122-9b6f-2fd01a330797.jpg', NULL, NULL, 392133, 'image/jpeg', 1, NULL, '2025-09-17 09:12:46', '2025-09-17 09:12:46'),
(29, 'path2', 2, 770.00, 220.00, 1, 'pano_path2_2_1758100404_68ca7bb431344.jpg', '6c3da21e-cd73-4b29-b05c-8481c1520c8c.jpg', NULL, NULL, 354986, 'image/jpeg', 1, NULL, '2025-09-17 09:13:24', '2025-09-17 09:13:24'),
(30, 'path2', 8, 1020.00, 130.00, 1, 'pano_path2_8_1758100471_68ca7bf7c3f32.jpg', '7c7c9b80-3cc9-47ac-813d-fb9ec8d60902.jpg', NULL, NULL, 264176, 'image/jpeg', 1, NULL, '2025-09-17 09:14:31', '2025-09-17 09:14:31'),
(31, 'path2', 9, 1045.00, 130.00, 1, 'pano_path2_9_1758100513_68ca7c21b7356.jpg', '8c161297-ffa7-4293-91bf-18d9ab188112.jpg', NULL, NULL, 268616, 'image/jpeg', 1, NULL, '2025-09-17 09:15:13', '2025-09-17 09:15:13'),
(32, 'path2', 10, 1045.00, 215.00, 1, 'pano_path2_10_1758100552_68ca7c48f0090.jpg', 'df890e54-913b-4549-a6b9-d6be9a2c79e2.jpg', NULL, NULL, 327894, 'image/jpeg', 1, NULL, '2025-09-17 09:15:52', '2025-09-17 09:15:52'),
(34, 'path2', 12, 1184.00, 215.00, 1, 'pano_path2_12_1758100718_68ca7cee279fa.jpg', 'fbda1e1e-03c7-4e36-826b-8eaaf9507571 (1).jpg', NULL, NULL, 380276, 'image/jpeg', 1, NULL, '2025-09-17 09:18:38', '2025-09-17 09:18:38'),
(35, 'path2', 13, 1313.00, 215.00, 1, 'pano_path2_13_1758100802_68ca7d421801d.jpg', 'f804d8d7-9523-4c2c-87ff-752ebd2278a7.jpg', NULL, NULL, 314432, 'image/jpeg', 1, NULL, '2025-09-17 09:20:02', '2025-09-17 09:20:02'),
(36, 'path2', 14, 1419.00, 215.00, 1, 'pano_path2_14_1758100835_68ca7d63de0a0.jpg', 'fa882327-d331-444b-aec5-a29070fa7057.jpg', NULL, NULL, 293210, 'image/jpeg', 1, NULL, '2025-09-17 09:20:35', '2025-09-17 09:20:35'),
(37, 'path2', 15, 1449.00, 215.00, 1, 'pano_path2_15_1758100884_68ca7d94d802f.jpg', 'dde9f348-482a-4320-b93b-cc18460db76f.jpg', NULL, NULL, 323785, 'image/jpeg', 1, NULL, '2025-09-17 09:21:24', '2025-09-17 09:21:24'),
(38, 'path2', 16, 1557.00, 215.00, 1, 'pano_path2_16_1758100944_68ca7dd0130bb.jpg', '4c5facfe-f7fa-47d9-89d5-082fb715e1c3.jpg', NULL, NULL, 372607, 'image/jpeg', 1, NULL, '2025-09-17 09:22:24', '2025-09-17 09:22:24'),
(39, 'path2', 17, 1685.00, 215.00, 1, 'pano_path2_17_1758100999_68ca7e07e34dd.jpg', '11bcd465-2e75-4ca7-9e74-4a963502e329.jpg', NULL, NULL, 364000, 'image/jpeg', 1, NULL, '2025-09-17 09:23:19', '2025-09-17 09:23:19'),
(40, 'path2', 18, 1778.00, 215.00, 1, 'pano_path2_18_1758101042_68ca7e32c761b.jpg', '8788b7ad-110a-4ba4-8c58-6c5e8125c737.jpg', NULL, NULL, 337180, 'image/jpeg', 1, NULL, '2025-09-17 09:24:02', '2025-09-17 09:24:02'),
(41, 'path2', 19, 1778.00, 170.00, 1, 'pano_path2_19_1758101166_68ca7eae74cf7.jpg', '39951a4d-65c9-4d33-9157-dcdf9b998501.jpg', NULL, NULL, 351486, 'image/jpeg', 1, NULL, '2025-09-17 09:26:06', '2025-09-17 09:26:06'),
(42, 'path2', 20, 1778.00, 110.00, 1, 'pano_path2_20_1758101227_68ca7eeb063b4.jpg', '5e4539a1-eb64-4fc5-91da-0ea07fb944a8.jpg', NULL, NULL, 408836, 'image/jpeg', 1, NULL, '2025-09-17 09:27:07', '2025-09-17 09:27:07'),
(45, 'path5_floor2', 1, 235.00, 255.00, 2, 'pano_path5_floor2_1_1758206405_68cc19c5e754f.jpg', '4eb298d5-a1d4-4642-a1b4-4bd9757e1ab4.jpg', 'Gwa Capitol', 'Kami sa gwa', 502632, 'image/jpeg', 1, NULL, '2025-09-18 14:40:05', '2025-09-18 14:40:05');

-- --------------------------------------------------------

--
-- Table structure for table `qrcode_info`
--

CREATE TABLE `qrcode_info` (
  `id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `qr_code_data` text DEFAULT NULL,
  `qr_code_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `qrcode_info`
--

INSERT INTO `qrcode_info` (`id`, `office_id`, `qr_code_data`, `qr_code_image`, `created_at`) VALUES
(239, 96, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=96', 'Human_Resources_96.png', '2025-05-27 22:42:16'),
(240, 97, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=97', 'Information_Technology_97.png', '2025-05-27 22:43:30'),
(241, 98, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=98', 'Public_Relations_98.png', '2025-05-27 22:44:27'),
(242, 99, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=99', 'Legal_Affairs_99.png', '2025-05-27 22:45:18'),
(243, 100, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=100', 'Procurement_100.png', '2025-05-27 22:46:06'),
(244, 101, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=101', 'Records_Management_101.png', '2025-05-27 22:47:10'),
(245, 102, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=102', 'Customer_Service_102.png', '2025-05-27 22:48:40'),
(246, 103, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=103', 'Maintenance_and_Facilities_103.png', '2025-05-27 22:52:12'),
(247, 104, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=104', 'Planning_and_Development_104.png', '2025-05-27 22:56:49'),
(248, 105, 'http://192.168.200.40/FinalDev/mobileScreen/explore.php?office_id=105', 'Internal_Audit_105.png', '2025-05-27 22:57:48'),
(249, 106, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=106', 'Room_1_106.png', '2025-09-11 18:03:29');

-- --------------------------------------------------------

--
-- Table structure for table `qr_scan_logs`
--

CREATE TABLE `qr_scan_logs` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `qr_code_id` int(11) NOT NULL,
  `check_in_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `qr_scan_logs`
--

INSERT INTO `qr_scan_logs` (`id`, `office_id`, `qr_code_id`, `check_in_time`) VALUES
(747, 99, 242, '2025-05-27 23:12:54'),
(748, 103, 246, '2024-06-28 10:11:34'),
(749, 103, 246, '2025-01-03 08:19:53'),
(750, 98, 241, '2023-08-04 04:01:23'),
(751, 96, 239, '2022-05-20 05:59:41'),
(752, 99, 242, '2021-02-25 01:55:16'),
(753, 101, 244, '2020-03-10 22:13:34'),
(754, 102, 245, '2024-05-17 00:16:21'),
(755, 96, 239, '2020-08-16 07:09:10'),
(756, 105, 248, '2021-10-04 21:25:08'),
(757, 101, 244, '2021-06-18 05:56:13'),
(758, 98, 241, '2022-09-02 04:17:40'),
(759, 101, 244, '2021-06-27 01:39:03'),
(760, 104, 247, '2024-05-30 01:37:28'),
(761, 103, 246, '2024-07-03 03:46:26'),
(762, 104, 247, '2020-06-04 21:00:49'),
(763, 101, 244, '2022-12-25 04:34:05'),
(764, 105, 248, '2021-02-14 23:08:53'),
(765, 96, 239, '2020-04-24 02:30:13'),
(766, 100, 243, '2020-03-12 01:02:33'),
(767, 105, 248, '2022-09-26 11:03:55'),
(768, 96, 239, '2022-04-07 04:30:00'),
(769, 104, 247, '2020-02-19 02:39:56'),
(770, 97, 240, '2023-05-21 11:28:47'),
(771, 104, 247, '2020-06-15 01:23:10'),
(772, 96, 239, '2021-05-25 22:59:00'),
(773, 104, 247, '2020-01-15 15:17:13'),
(774, 104, 247, '2020-05-16 00:03:29'),
(775, 105, 248, '2021-07-18 21:12:10'),
(776, 105, 248, '2021-11-09 11:31:08'),
(777, 99, 242, '2020-08-06 12:22:22'),
(778, 97, 240, '2021-05-10 05:14:03'),
(779, 97, 240, '2025-04-13 23:18:00'),
(780, 98, 241, '2023-05-28 09:27:50'),
(781, 105, 248, '2020-06-24 23:18:56'),
(782, 97, 240, '2022-12-28 05:13:41'),
(783, 97, 240, '2021-08-29 10:41:38'),
(784, 98, 241, '2022-11-18 22:03:43'),
(785, 101, 244, '2022-07-16 18:22:23'),
(786, 104, 247, '2022-08-23 05:51:56'),
(787, 98, 241, '2021-09-26 18:38:16'),
(788, 98, 241, '2024-07-31 12:01:41'),
(789, 101, 244, '2021-02-19 02:36:26'),
(790, 99, 242, '2021-08-17 18:16:21'),
(791, 100, 243, '2024-01-01 16:51:02'),
(792, 105, 248, '2022-09-09 13:06:14'),
(793, 98, 241, '2022-11-24 00:28:47'),
(794, 99, 242, '2024-04-28 06:25:07'),
(795, 101, 244, '2020-07-04 10:44:14'),
(796, 100, 243, '2021-07-11 05:30:56'),
(797, 103, 246, '2020-10-20 17:28:00'),
(798, 99, 242, '2022-01-02 05:22:24'),
(799, 97, 240, '2022-03-28 07:37:57'),
(800, 102, 245, '2022-02-07 04:03:41'),
(801, 101, 244, '2022-06-24 07:08:57'),
(802, 101, 244, '2025-04-15 18:10:44'),
(803, 104, 247, '2022-09-13 05:07:14'),
(804, 100, 243, '2020-04-10 10:20:36'),
(805, 99, 242, '2024-06-18 08:27:49'),
(806, 103, 246, '2025-01-16 16:50:03'),
(807, 98, 241, '2021-08-19 20:09:27'),
(808, 102, 245, '2020-11-09 08:36:35'),
(809, 98, 241, '2023-03-21 15:42:00'),
(810, 105, 248, '2022-05-18 22:04:51'),
(811, 97, 240, '2020-12-21 11:21:25'),
(812, 105, 248, '2025-03-04 06:59:32'),
(813, 101, 244, '2020-07-04 12:15:12'),
(814, 101, 244, '2021-09-21 17:01:06'),
(815, 99, 242, '2024-10-18 22:33:59'),
(816, 102, 245, '2025-03-10 08:55:39'),
(817, 98, 241, '2020-08-27 08:46:45'),
(818, 103, 246, '2021-07-29 17:05:04'),
(819, 100, 243, '2024-01-17 00:40:01'),
(820, 100, 243, '2021-09-10 10:13:49'),
(821, 103, 246, '2024-02-24 03:11:23'),
(822, 105, 248, '2024-11-29 04:46:05'),
(823, 97, 240, '2022-06-01 10:14:35'),
(824, 96, 239, '2022-08-25 03:48:39'),
(825, 103, 246, '2024-08-15 05:56:45'),
(826, 97, 240, '2020-11-13 14:40:09'),
(827, 99, 242, '2023-09-30 07:57:42'),
(828, 104, 247, '2021-06-21 13:52:43'),
(829, 99, 242, '2020-07-19 14:27:24'),
(830, 98, 241, '2023-02-14 01:56:01'),
(831, 102, 245, '2021-08-12 18:52:27'),
(832, 104, 247, '2023-10-26 02:35:56'),
(833, 99, 242, '2022-03-19 12:56:42'),
(834, 101, 244, '2022-02-09 07:02:40'),
(835, 104, 247, '2020-09-02 23:27:56'),
(836, 105, 248, '2023-05-27 18:32:56'),
(837, 101, 244, '2020-01-28 18:19:08'),
(838, 105, 248, '2021-08-31 13:57:59'),
(839, 105, 248, '2024-07-24 04:10:46'),
(840, 100, 243, '2024-01-14 02:33:43'),
(841, 102, 245, '2023-12-17 17:41:36'),
(842, 101, 244, '2022-11-18 23:12:02'),
(843, 104, 247, '2024-11-28 13:40:22'),
(844, 101, 244, '2021-09-10 23:08:37'),
(845, 96, 239, '2022-02-11 01:34:16'),
(846, 97, 240, '2025-04-11 18:00:11'),
(847, 102, 245, '2020-08-20 20:18:23'),
(848, 96, 239, '2020-05-12 00:32:45'),
(849, 97, 240, '2020-07-22 01:15:22'),
(850, 98, 241, '2020-09-10 02:05:33'),
(851, 99, 242, '2020-11-18 03:22:11'),
(852, 100, 243, '2021-01-05 04:45:09'),
(853, 101, 244, '2021-03-30 05:12:28'),
(854, 102, 245, '2021-05-14 06:33:47'),
(855, 103, 246, '2021-07-21 07:20:15'),
(856, 104, 247, '2021-09-11 08:45:32'),
(857, 105, 248, '2021-12-03 09:18:59'),
(858, 96, 239, '2022-02-12 00:05:21'),
(859, 97, 240, '2022-04-15 01:12:33'),
(860, 98, 241, '2022-06-03 02:22:44'),
(861, 99, 242, '2022-08-18 03:35:15'),
(862, 100, 243, '2022-10-10 04:42:26'),
(863, 101, 244, '2022-12-22 05:15:37'),
(864, 102, 245, '2023-02-05 06:25:48'),
(865, 103, 246, '2023-04-19 07:35:59'),
(866, 104, 247, '2023-06-11 08:45:10'),
(867, 105, 248, '2023-08-25 09:55:21'),
(868, 96, 239, '2023-10-07 00:15:32'),
(869, 97, 240, '2023-12-14 01:25:43'),
(870, 98, 241, '2024-01-03 02:35:54'),
(871, 99, 242, '2024-02-18 03:45:05'),
(872, 100, 243, '2024-03-10 04:55:16'),
(873, 101, 244, '2024-04-22 05:05:27'),
(874, 102, 245, '2024-05-05 06:15:38'),
(875, 103, 246, '2024-06-19 07:25:49'),
(876, 104, 247, '2024-07-11 08:35:50'),
(877, 105, 248, '2024-08-25 09:45:51'),
(878, 96, 239, '2020-06-14 23:32:11'),
(879, 97, 240, '2020-08-24 00:45:22'),
(880, 98, 241, '2020-10-12 01:55:33'),
(881, 99, 242, '2021-01-28 02:12:44'),
(882, 100, 243, '2021-03-15 03:25:55'),
(883, 101, 244, '2021-05-30 04:32:16'),
(884, 102, 245, '2021-07-14 05:43:27'),
(885, 103, 246, '2021-09-01 06:52:38'),
(886, 104, 247, '2021-11-21 07:15:49'),
(887, 105, 248, '2022-01-13 08:22:50'),
(888, 96, 239, '2022-03-21 23:35:01'),
(889, 97, 240, '2022-05-25 00:42:12'),
(890, 98, 241, '2022-07-13 01:52:23'),
(891, 99, 242, '2022-09-28 02:05:34'),
(892, 100, 243, '2022-11-20 03:15:45'),
(893, 101, 244, '2023-01-02 04:25:56'),
(894, 102, 245, '2023-03-15 05:35:07'),
(895, 103, 246, '2023-05-29 06:45:18'),
(896, 104, 247, '2023-07-01 07:55:29'),
(897, 105, 248, '2023-09-15 08:05:30'),
(898, 96, 239, '2023-11-16 23:15:41'),
(899, 97, 240, '2024-01-24 00:25:52'),
(900, 98, 241, '2024-03-03 01:35:03'),
(901, 99, 242, '2024-04-18 02:45:14'),
(902, 100, 243, '2024-05-10 03:55:25'),
(903, 101, 244, '2024-06-22 04:05:36'),
(904, 102, 245, '2024-07-05 05:15:47'),
(905, 103, 246, '2024-08-19 06:25:58'),
(906, 104, 247, '2024-09-11 07:35:09'),
(907, 105, 248, '2024-10-25 08:45:10'),
(908, 96, 239, '2020-07-15 23:55:21'),
(909, 97, 240, '2020-09-26 00:05:32'),
(910, 98, 241, '2020-11-20 01:15:43'),
(911, 99, 242, '2021-02-08 02:25:54'),
(912, 100, 243, '2021-04-25 03:35:05'),
(913, 101, 244, '2021-06-10 04:45:16'),
(914, 102, 245, '2021-08-24 05:55:27'),
(915, 103, 246, '2021-10-11 06:05:38'),
(916, 104, 247, '2021-12-31 07:15:49'),
(917, 105, 248, '2022-02-23 08:25:50'),
(918, 96, 239, '2022-04-01 23:35:01'),
(919, 97, 240, '2022-06-05 00:45:12'),
(920, 98, 241, '2022-08-23 01:55:23'),
(921, 99, 242, '2022-10-18 02:05:34'),
(922, 100, 243, '2022-12-10 03:15:45'),
(923, 101, 244, '2023-02-12 04:25:56'),
(924, 102, 245, '2023-04-25 05:35:07'),
(925, 103, 246, '2023-06-09 06:45:18'),
(926, 104, 247, '2023-08-11 07:55:29'),
(927, 105, 248, '2023-10-25 08:05:30'),
(928, 96, 239, '2023-12-06 23:15:41'),
(929, 97, 240, '2024-02-14 00:25:52'),
(930, 98, 241, '2024-04-13 01:35:03'),
(931, 99, 242, '2024-05-28 02:45:14'),
(932, 100, 243, '2024-06-10 03:55:25'),
(933, 101, 244, '2024-07-22 04:05:36'),
(934, 102, 245, '2024-08-15 05:15:47'),
(935, 103, 246, '2024-09-29 06:25:58'),
(936, 104, 247, '2024-10-21 07:35:09'),
(937, 105, 248, '2024-11-25 08:45:10'),
(938, 96, 239, '2020-08-15 23:05:11'),
(939, 97, 240, '2020-10-26 00:15:22'),
(940, 98, 241, '2020-12-10 01:25:33'),
(941, 99, 242, '2021-03-08 02:35:44'),
(942, 100, 243, '2021-05-15 03:45:55'),
(943, 101, 244, '2021-07-20 04:55:06'),
(944, 102, 245, '2021-09-14 05:05:17'),
(945, 103, 246, '2021-11-01 06:15:28'),
(946, 104, 247, '2022-01-21 07:25:39'),
(947, 105, 248, '2022-03-13 08:35:40'),
(948, 96, 239, '2022-05-11 23:45:51'),
(949, 97, 240, '2022-07-15 00:55:02'),
(950, 98, 241, '2022-09-03 01:05:13'),
(951, 99, 242, '2022-11-28 02:15:24'),
(952, 100, 243, '2023-01-10 03:25:35'),
(953, 101, 244, '2023-03-22 04:35:46'),
(954, 102, 245, '2023-05-05 05:45:57'),
(955, 103, 246, '2023-07-19 06:55:08'),
(956, 104, 247, '2023-09-01 07:05:19'),
(957, 105, 248, '2023-11-15 08:15:20'),
(958, 97, 240, '2020-01-02 21:22:18'),
(959, 103, 246, '2020-02-14 15:15:42'),
(960, 98, 241, '2020-03-08 06:03:57'),
(961, 96, 239, '2020-04-18 19:45:11'),
(962, 105, 248, '2020-05-21 10:32:04'),
(963, 101, 244, '2020-06-10 23:58:29'),
(964, 102, 245, '2020-07-30 04:14:36'),
(965, 99, 242, '2020-08-09 12:47:53'),
(966, 104, 247, '2020-09-16 17:25:40'),
(967, 100, 243, '2020-10-22 01:11:15'),
(968, 97, 240, '2020-11-05 08:39:28'),
(969, 103, 246, '2020-12-31 14:08:17'),
(970, 96, 239, '2021-01-11 20:52:43'),
(971, 98, 241, '2021-02-28 05:27:59'),
(972, 101, 244, '2021-03-15 11:45:31'),
(973, 105, 248, '2021-04-03 00:14:22'),
(974, 102, 245, '2021-05-19 07:33:47'),
(975, 99, 242, '2021-06-23 22:21:38'),
(976, 104, 247, '2021-07-07 03:49:05'),
(977, 100, 243, '2021-08-13 15:57:16'),
(978, 97, 240, '2021-09-28 18:35:24'),
(979, 103, 246, '2021-10-11 09:08:42'),
(980, 96, 239, '2021-11-22 02:44:53'),
(981, 98, 241, '2021-12-05 06:26:37'),
(982, 101, 244, '2022-01-17 21:39:18'),
(983, 105, 248, '2022-02-27 13:15:29'),
(984, 102, 245, '2022-03-09 04:48:51'),
(985, 99, 242, '2022-04-13 19:27:44'),
(986, 104, 247, '2022-05-23 08:39:12'),
(987, 100, 243, '2022-06-30 01:52:37'),
(988, 97, 240, '2022-07-12 14:14:58'),
(989, 103, 246, '2022-08-18 23:33:21'),
(990, 96, 239, '2022-09-01 10:45:39'),
(991, 98, 241, '2022-10-10 17:27:45'),
(992, 101, 244, '2022-11-24 05:59:16'),
(993, 105, 248, '2022-12-06 20:38:27'),
(994, 102, 245, '2023-01-13 07:22:48'),
(995, 99, 242, '2023-02-28 12:45:53'),
(996, 104, 247, '2023-03-10 00:17:29'),
(997, 100, 243, '2023-04-19 03:39:44'),
(998, 97, 240, '2023-05-25 15:58:15'),
(999, 103, 246, '2023-06-06 06:27:36'),
(1000, 96, 239, '2023-07-17 21:49:52'),
(1001, 98, 241, '2023-08-22 11:33:47'),
(1002, 101, 244, '2023-09-05 02:44:58'),
(1003, 105, 248, '2023-10-16 18:15:29'),
(1004, 102, 245, '2023-11-29 08:38:41'),
(1005, 99, 242, '2023-12-11 23:52:53'),
(1006, 104, 247, '2024-01-24 13:15:34'),
(1007, 100, 243, '2024-02-07 04:47:59'),
(1008, 97, 240, '2024-03-18 19:29:16'),
(1009, 103, 246, '2024-04-22 09:48:25'),
(1010, 96, 239, '2024-05-05 01:15:37'),
(1011, 98, 241, '2024-06-18 14:39:48'),
(1012, 101, 244, '2024-07-23 06:52:19'),
(1013, 105, 248, '2024-08-08 21:27:31'),
(1014, 102, 245, '2024-09-15 10:45:42'),
(1015, 99, 242, '2024-10-28 02:33:54'),
(1016, 104, 247, '2024-11-10 17:59:15'),
(1017, 100, 243, '2024-12-24 05:27:36'),
(1018, 97, 240, '2020-01-06 22:33:47'),
(1019, 103, 246, '2020-02-18 13:45:58'),
(1020, 98, 241, '2020-03-22 04:59:19'),
(1021, 96, 239, '2020-04-04 20:15:21'),
(1022, 105, 248, '2020-05-17 09:38:32'),
(1023, 101, 244, '2020-06-29 01:52:43'),
(1024, 102, 245, '2020-07-12 14:14:54'),
(1025, 99, 242, '2020-08-25 06:27:15'),
(1026, 104, 247, '2020-09-07 21:39:26'),
(1027, 100, 243, '2020-10-19 10:52:37'),
(1028, 97, 240, '2020-11-01 02:15:48'),
(1029, 103, 246, '2020-12-13 17:29:59'),
(1030, 96, 239, '2021-01-26 05:42:11'),
(1031, 98, 241, '2021-02-08 20:55:22'),
(1032, 101, 244, '2021-03-22 12:08:33'),
(1033, 105, 248, '2021-04-05 03:21:44'),
(1034, 102, 245, '2021-05-17 19:34:55'),
(1035, 99, 242, '2021-06-30 07:48:06'),
(1036, 104, 247, '2021-07-12 23:01:17'),
(1037, 100, 243, '2021-08-26 14:14:28'),
(1038, 97, 240, '2021-09-09 05:27:39'),
(1039, 103, 246, '2021-10-20 20:40:41'),
(1040, 96, 239, '2021-11-03 11:53:52'),
(1041, 98, 241, '2021-12-16 03:07:03'),
(1042, 101, 244, '2022-01-28 18:20:14'),
(1043, 105, 248, '2022-02-11 09:33:25'),
(1044, 102, 245, '2022-03-26 00:46:36'),
(1045, 99, 242, '2022-04-08 15:59:47'),
(1046, 104, 247, '2022-05-21 07:12:58'),
(1047, 100, 243, '2022-06-03 22:26:09'),
(1048, 97, 240, '2022-07-17 13:39:11'),
(1049, 103, 246, '2022-08-30 04:52:22'),
(1050, 96, 239, '2022-09-12 20:05:33'),
(1051, 98, 241, '2022-10-26 11:18:44'),
(1052, 101, 244, '2022-11-09 02:31:55'),
(1053, 105, 248, '2022-12-21 17:45:06'),
(1054, 102, 245, '2023-01-04 08:58:17'),
(1055, 99, 242, '2023-02-17 00:11:28'),
(1056, 104, 247, '2023-03-02 15:24:39'),
(1057, 100, 243, '2023-04-15 06:37:41'),
(1058, 97, 240, '2023-05-27 21:50:52'),
(1059, 103, 246, '2023-06-10 12:04:03'),
(1060, 96, 239, '2023-07-23 03:17:14'),
(1061, 98, 241, '2023-08-05 18:30:25'),
(1062, 101, 244, '2023-09-18 09:43:36'),
(1063, 105, 248, '2023-10-01 00:56:47'),
(1064, 102, 245, '2023-11-13 16:09:58'),
(1065, 99, 242, '2023-12-27 07:23:09'),
(1066, 104, 247, '2024-01-09 22:36:11'),
(1067, 100, 243, '2024-02-22 13:49:22'),
(1068, 97, 240, '2024-03-07 05:02:33'),
(1069, 103, 246, '2024-04-19 20:15:44'),
(1070, 96, 239, '2024-05-03 11:28:55'),
(1071, 98, 241, '2024-06-16 02:42:06'),
(1072, 101, 244, '2024-07-28 17:55:17'),
(1073, 105, 248, '2024-08-11 09:08:28'),
(1074, 102, 245, '2024-09-24 00:21:39'),
(1075, 99, 242, '2024-10-07 15:34:41'),
(1076, 104, 247, '2024-11-20 06:47:52'),
(1077, 100, 243, '2024-12-03 22:01:03'),
(1078, 97, 240, '2020-01-17 11:14:14'),
(1079, 103, 246, '2020-02-20 02:27:25'),
(1080, 98, 241, '2020-03-04 17:40:36'),
(1081, 96, 239, '2020-04-18 08:53:47'),
(1082, 105, 248, '2020-05-01 00:06:58'),
(1083, 101, 244, '2020-06-14 15:20:09'),
(1084, 102, 245, '2020-07-27 06:33:11'),
(1085, 99, 242, '2020-08-09 21:46:22'),
(1086, 104, 247, '2020-09-22 12:59:33'),
(1087, 100, 243, '2020-10-06 04:12:44'),
(1088, 97, 240, '2020-11-18 19:25:55'),
(1089, 103, 246, '2020-12-02 10:39:06'),
(1090, 96, 239, '2021-01-15 01:52:17'),
(1091, 98, 241, '2021-02-27 17:05:28'),
(1092, 101, 244, '2021-03-12 08:18:39'),
(1093, 105, 248, '2021-04-24 23:31:41'),
(1094, 102, 245, '2021-05-08 14:44:52'),
(1095, 99, 242, '2021-06-21 05:58:03'),
(1096, 104, 247, '2021-07-03 21:11:14'),
(1097, 100, 243, '2021-08-17 12:24:25'),
(1098, 97, 240, '2021-09-30 03:37:36'),
(1099, 103, 246, '2021-10-12 18:50:47'),
(1100, 96, 239, '2021-11-26 10:03:58'),
(1101, 98, 241, '2021-12-09 01:17:09'),
(1102, 101, 244, '2022-01-21 16:30:11'),
(1103, 105, 248, '2022-02-04 07:43:22'),
(1104, 102, 245, '2022-03-18 22:56:33'),
(1105, 99, 242, '2022-04-01 14:09:44'),
(1106, 104, 247, '2022-05-15 05:22:55'),
(1107, 100, 243, '2022-06-27 20:36:06'),
(1108, 97, 240, '2022-07-11 11:49:17'),
(1109, 103, 246, '2022-08-24 03:02:28'),
(1110, 96, 239, '2022-09-06 18:15:39'),
(1111, 98, 241, '2022-10-20 09:28:41'),
(1112, 101, 244, '2022-11-03 00:41:52'),
(1113, 105, 248, '2022-12-16 15:55:03'),
(1114, 102, 245, '2023-01-29 07:08:14'),
(1115, 99, 242, '2023-02-10 22:21:25'),
(1116, 104, 247, '2023-03-26 13:34:36'),
(1117, 100, 243, '2023-04-09 04:47:47'),
(1118, 97, 240, '2023-05-21 20:00:58'),
(1119, 103, 246, '2023-06-04 11:14:09'),
(1120, 96, 239, '2023-07-17 02:27:11'),
(1121, 98, 241, '2023-08-29 17:40:22'),
(1122, 101, 244, '2023-09-12 08:53:33'),
(1123, 105, 248, '2023-10-26 00:06:44'),
(1124, 102, 245, '2023-11-08 15:19:55'),
(1125, 99, 242, '2023-12-22 06:33:06'),
(1126, 104, 247, '2024-01-04 21:46:17'),
(1127, 100, 243, '2024-02-18 12:59:28'),
(1128, 97, 240, '2024-03-02 04:12:39'),
(1129, 103, 246, '2024-04-14 19:25:41'),
(1130, 96, 239, '2024-05-28 10:38:52'),
(1131, 98, 241, '2024-06-10 01:52:03'),
(1132, 101, 244, '2024-07-23 17:05:14'),
(1133, 105, 248, '2024-08-06 08:18:25'),
(1134, 102, 245, '2024-09-18 23:31:36'),
(1135, 99, 242, '2024-10-02 14:44:47'),
(1136, 104, 247, '2024-11-15 05:57:58'),
(1137, 100, 243, '2024-12-28 21:11:09'),
(1138, 96, 239, '2024-06-01 00:15:32'),
(1139, 97, 240, '2024-06-01 03:23:45'),
(1140, 98, 241, '2024-06-01 06:37:18'),
(1141, 99, 242, '2024-06-01 08:52:09'),
(1142, 100, 243, '2024-06-01 01:41:27'),
(1143, 101, 244, '2024-06-01 05:08:54'),
(1144, 102, 245, '2024-06-01 09:25:36'),
(1145, 103, 246, '2024-06-01 02:19:43'),
(1146, 104, 247, '2024-06-01 07:47:21'),
(1147, 105, 248, '2024-06-01 04:33:58'),
(1148, 96, 239, '2024-06-01 23:58:12'),
(1149, 97, 240, '2024-06-02 02:42:35'),
(1150, 98, 241, '2024-06-02 05:15:49'),
(1151, 99, 242, '2024-06-02 08:28:07'),
(1152, 100, 243, '2024-06-02 00:53:24'),
(1153, 101, 244, '2024-06-02 03:37:56'),
(1154, 102, 245, '2024-06-02 06:22:18'),
(1155, 103, 246, '2024-06-02 09:45:39'),
(1156, 104, 247, '2024-06-02 01:31:42'),
(1157, 105, 248, '2024-06-02 04:19:05'),
(1158, 96, 239, '2024-06-03 00:24:37'),
(1159, 97, 240, '2024-06-03 03:52:14'),
(1160, 98, 241, '2024-06-03 06:38:26'),
(1161, 99, 242, '2024-06-03 08:17:49'),
(1162, 100, 243, '2024-06-03 01:45:32'),
(1163, 101, 244, '2024-06-03 05:28:57'),
(1164, 102, 245, '2024-06-03 09:39:04'),
(1165, 103, 246, '2024-06-03 02:22:18'),
(1166, 104, 247, '2024-06-03 07:14:36'),
(1167, 105, 248, '2024-06-03 04:47:59'),
(1168, 96, 239, '2024-06-03 23:39:21'),
(1169, 97, 240, '2024-06-04 02:28:43'),
(1170, 98, 241, '2024-06-04 05:45:17'),
(1171, 99, 242, '2024-06-04 08:52:38'),
(1172, 100, 243, '2024-06-04 00:19:54'),
(1173, 101, 244, '2024-06-04 03:37:26'),
(1174, 102, 245, '2024-06-04 06:23:49'),
(1175, 103, 246, '2024-06-04 09:18:32'),
(1176, 104, 247, '2024-06-04 01:42:15'),
(1177, 105, 248, '2024-06-04 04:29:47'),
(1178, 96, 239, '2024-06-05 00:17:39'),
(1179, 97, 240, '2024-06-05 03:24:52'),
(1180, 98, 241, '2024-06-05 06:38:14'),
(1181, 99, 242, '2024-06-05 08:45:27'),
(1182, 100, 243, '2024-06-05 01:32:48'),
(1183, 101, 244, '2024-06-05 05:19:05'),
(1184, 102, 245, '2024-06-05 09:28:36'),
(1185, 103, 246, '2024-06-05 02:14:29'),
(1186, 104, 247, '2024-06-05 07:37:42'),
(1187, 105, 248, '2024-06-05 04:22:54'),
(1188, 96, 239, '2024-05-01 00:12:34'),
(1189, 97, 240, '2024-05-01 03:23:45'),
(1190, 103, 246, '2024-05-01 06:37:18'),
(1191, 99, 242, '2024-05-01 08:45:09'),
(1192, 101, 244, '2024-05-01 01:31:27'),
(1193, 105, 248, '2024-05-01 05:05:54'),
(1194, 102, 245, '2024-05-01 09:22:36'),
(1195, 98, 241, '2024-05-01 02:15:43'),
(1196, 104, 247, '2024-05-01 07:42:21'),
(1197, 100, 243, '2024-05-01 04:33:58'),
(1198, 97, 240, '2024-05-01 23:58:12'),
(1199, 103, 246, '2024-05-02 02:42:35'),
(1200, 96, 239, '2024-05-02 05:19:49'),
(1201, 102, 245, '2024-05-02 08:28:07'),
(1202, 104, 247, '2024-05-02 00:53:24'),
(1203, 101, 244, '2024-05-02 03:37:56'),
(1204, 105, 248, '2024-05-02 06:25:18'),
(1205, 99, 242, '2024-05-02 09:41:39'),
(1206, 100, 243, '2024-05-02 01:31:42'),
(1207, 98, 241, '2024-05-02 04:19:05'),
(1208, 96, 239, '2024-05-03 00:24:37'),
(1209, 103, 246, '2024-05-03 03:52:14'),
(1210, 97, 240, '2024-05-03 06:38:26'),
(1211, 101, 244, '2024-05-03 08:17:49'),
(1212, 105, 248, '2024-05-03 01:45:32'),
(1213, 99, 242, '2024-05-03 05:28:57'),
(1214, 104, 247, '2024-05-03 09:39:04'),
(1215, 102, 245, '2024-05-03 02:22:18'),
(1216, 100, 243, '2024-05-03 07:14:36'),
(1217, 98, 241, '2024-05-03 04:47:59'),
(1218, 103, 246, '2024-05-14 23:39:21'),
(1219, 96, 239, '2024-05-15 02:28:43'),
(1220, 97, 240, '2024-05-15 05:45:17'),
(1221, 105, 248, '2024-05-15 08:52:38'),
(1222, 101, 244, '2024-05-15 00:19:54'),
(1223, 99, 242, '2024-05-15 03:37:26'),
(1224, 104, 247, '2024-05-15 06:23:49'),
(1225, 102, 245, '2024-05-15 09:18:32'),
(1226, 100, 243, '2024-05-15 01:42:15'),
(1227, 98, 241, '2024-05-15 04:29:47'),
(1228, 96, 239, '2024-05-28 00:17:39'),
(1229, 97, 240, '2024-05-28 03:24:52'),
(1230, 98, 241, '2024-05-28 06:38:14'),
(1231, 99, 242, '2024-05-28 08:45:27'),
(1232, 100, 243, '2024-05-28 01:32:48'),
(1233, 101, 244, '2024-05-28 05:19:05'),
(1234, 102, 245, '2024-05-28 09:28:36'),
(1235, 103, 246, '2024-05-28 02:14:29'),
(1236, 104, 247, '2024-05-28 07:37:42'),
(1237, 105, 248, '2024-05-28 04:22:54'),
(1238, 96, 239, '2025-01-15 00:32:45'),
(1239, 97, 240, '2025-02-03 01:15:22'),
(1240, 98, 241, '2025-03-12 02:05:33'),
(1241, 99, 242, '2025-04-05 03:22:11'),
(1242, 100, 243, '2025-05-20 04:45:09'),
(1243, 101, 244, '2025-06-08 05:12:28'),
(1244, 102, 245, '2025-07-17 06:33:47'),
(1245, 103, 246, '2025-08-09 07:20:15'),
(1246, 104, 247, '2025-09-14 08:45:32'),
(1247, 105, 248, '2025-10-25 09:18:59'),
(1248, 96, 239, '2025-01-22 00:05:21'),
(1249, 97, 240, '2025-02-11 01:12:33'),
(1250, 98, 241, '2025-03-19 02:22:44'),
(1251, 99, 242, '2025-04-16 03:35:15'),
(1252, 100, 243, '2025-05-28 04:42:26'),
(1253, 101, 244, '2025-06-14 05:15:37'),
(1254, 102, 245, '2025-07-23 06:25:48'),
(1255, 103, 246, '2025-08-15 07:35:59'),
(1256, 104, 247, '2025-09-21 08:45:10'),
(1257, 105, 248, '2025-10-30 09:55:21'),
(1258, 96, 239, '2025-01-07 00:15:32'),
(1259, 97, 240, '2025-02-18 01:25:43'),
(1260, 98, 241, '2025-03-25 02:35:54'),
(1261, 99, 242, '2025-04-09 03:45:05'),
(1262, 100, 243, '2025-05-15 04:55:16'),
(1263, 101, 244, '2025-06-22 05:05:27'),
(1264, 102, 245, '2025-07-05 06:15:38'),
(1265, 103, 246, '2025-08-19 07:25:49'),
(1266, 104, 247, '2025-09-11 08:35:50'),
(1267, 105, 248, '2025-10-25 09:45:51'),
(1268, 96, 239, '2025-11-12 00:32:11'),
(1269, 97, 240, '2025-11-24 01:45:22'),
(1270, 98, 241, '2025-12-05 02:55:33'),
(1271, 99, 242, '2025-01-28 03:12:44'),
(1272, 100, 243, '2025-02-15 04:25:55'),
(1273, 101, 244, '2025-03-30 05:32:16'),
(1274, 102, 245, '2025-04-14 06:43:27'),
(1275, 103, 246, '2025-05-01 07:52:38'),
(1276, 104, 247, '2025-06-21 08:15:49'),
(1277, 105, 248, '2025-07-13 09:22:50'),
(1278, 96, 239, '2025-08-22 00:35:01'),
(1279, 97, 240, '2025-09-05 01:42:12'),
(1280, 98, 241, '2025-10-13 02:52:23'),
(1281, 99, 242, '2025-11-28 03:05:34'),
(1282, 100, 243, '2025-12-10 04:15:45'),
(1283, 101, 244, '2025-01-02 05:25:56'),
(1284, 102, 245, '2025-02-15 06:35:07'),
(1285, 103, 246, '2025-03-29 07:45:18'),
(1286, 104, 247, '2025-04-01 08:55:29'),
(1287, 105, 248, '2025-05-15 09:05:30'),
(1288, 96, 239, '2025-05-01 00:15:22'),
(1289, 97, 240, '2025-05-01 03:23:45'),
(1290, 98, 241, '2025-05-01 06:37:18'),
(1291, 99, 242, '2025-05-01 08:52:09'),
(1292, 100, 243, '2025-05-01 01:41:27'),
(1293, 101, 244, '2025-05-01 05:08:54'),
(1294, 102, 245, '2025-05-01 09:25:36'),
(1295, 103, 246, '2025-05-01 02:19:43'),
(1296, 104, 247, '2025-05-01 07:47:21'),
(1297, 105, 248, '2025-05-01 04:33:58'),
(1298, 96, 239, '2025-05-04 23:58:12'),
(1299, 97, 240, '2025-05-05 02:42:35'),
(1300, 98, 241, '2025-05-05 05:15:49'),
(1301, 99, 242, '2025-05-05 08:28:07'),
(1302, 100, 243, '2025-05-05 00:53:24'),
(1303, 101, 244, '2025-05-05 03:37:56'),
(1304, 102, 245, '2025-05-05 06:22:18'),
(1305, 103, 246, '2025-05-05 09:45:39'),
(1306, 104, 247, '2025-05-05 01:31:42'),
(1307, 105, 248, '2025-05-05 04:19:05'),
(1308, 96, 239, '2025-05-10 00:24:37'),
(1309, 97, 240, '2025-05-10 03:52:14'),
(1310, 98, 241, '2025-05-10 06:38:26'),
(1311, 99, 242, '2025-05-10 08:17:49'),
(1312, 100, 243, '2025-05-10 01:45:32'),
(1313, 101, 244, '2025-05-10 05:28:57'),
(1314, 102, 245, '2025-05-10 09:39:04'),
(1315, 103, 246, '2025-05-10 02:22:18'),
(1316, 104, 247, '2025-05-10 07:14:36'),
(1317, 105, 248, '2025-05-10 04:47:59'),
(1318, 96, 239, '2025-05-14 23:39:21'),
(1319, 97, 240, '2025-05-15 02:28:43'),
(1320, 98, 241, '2025-05-15 05:45:17'),
(1321, 99, 242, '2025-05-15 08:52:38'),
(1322, 100, 243, '2025-05-15 00:19:54'),
(1323, 101, 244, '2025-05-15 03:37:26'),
(1324, 102, 245, '2025-05-15 06:23:49'),
(1325, 103, 246, '2025-05-15 09:18:32'),
(1326, 104, 247, '2025-05-15 01:42:15'),
(1327, 105, 248, '2025-05-15 04:29:47'),
(1328, 96, 239, '2025-05-28 00:17:39'),
(1329, 97, 240, '2025-05-28 03:24:52'),
(1330, 98, 241, '2025-05-28 06:38:14'),
(1331, 99, 242, '2025-05-28 08:45:27'),
(1332, 100, 243, '2025-05-28 01:32:48'),
(1333, 101, 244, '2025-05-28 05:19:05'),
(1334, 102, 245, '2025-05-28 09:28:36'),
(1335, 103, 246, '2025-05-28 02:14:29'),
(1336, 104, 247, '2025-05-28 07:37:42'),
(1337, 105, 248, '2025-05-28 04:22:54'),
(1338, 106, 249, '2025-09-11 18:03:53'),
(1339, 106, 249, '2025-09-11 18:13:29'),
(1340, 106, 249, '2025-09-11 18:22:25'),
(1341, 106, 249, '2025-09-11 18:22:46'),
(1342, 106, 249, '2025-09-11 18:23:06'),
(1343, 106, 249, '2025-09-11 18:23:39'),
(1344, 106, 249, '2025-09-11 18:23:48'),
(1345, 106, 249, '2025-09-11 18:24:04'),
(1346, 106, 249, '2025-09-11 18:24:11'),
(1347, 106, 249, '2025-09-11 18:24:41'),
(1348, 106, 249, '2025-09-11 18:24:59'),
(1349, 106, 249, '2025-09-11 18:25:09'),
(1350, 106, 249, '2025-09-11 18:49:02'),
(1351, 106, 249, '2025-09-11 18:49:19'),
(1352, 106, 249, '2025-09-11 18:49:23'),
(1353, 106, 249, '2025-09-11 18:49:27'),
(1354, 106, 249, '2025-09-11 18:49:31'),
(1355, 106, 249, '2025-09-11 18:49:33'),
(1356, 106, 249, '2025-09-11 18:56:41'),
(1357, 106, 249, '2025-09-11 18:56:44'),
(1358, 106, 249, '2025-09-11 18:56:49'),
(1359, 106, 249, '2025-09-11 18:56:51');

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
-- Indexes for table `panorama_image`
--
ALTER TABLE `panorama_image`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_path_point` (`path_id`,`point_index`,`floor_number`),
  ADD KEY `idx_floor_number` (`floor_number`),
  ADD KEY `idx_coordinates` (`point_x`,`point_y`),
  ADD KEY `idx_path_id` (`path_id`),
  ADD KEY `idx_active` (`is_active`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=283;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `nav_path`
--
ALTER TABLE `nav_path`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `office_hours`
--
ALTER TABLE `office_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `office_image`
--
ALTER TABLE `office_image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `panorama_image`
--
ALTER TABLE `panorama_image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `qrcode_info`
--
ALTER TABLE `qrcode_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

--
-- AUTO_INCREMENT for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1360;

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
