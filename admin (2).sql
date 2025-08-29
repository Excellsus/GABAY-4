-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 24, 2025 at 04:51 AM
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
(245, 'office', 'New office \'Human Resources\' added', '2025-05-27 22:42:16', NULL, NULL),
(246, 'office', 'Office \'umayyy\' was deleted', '2025-05-27 22:42:22', NULL, NULL),
(247, 'office', 'New office \'Information Technology\' added', '2025-05-27 22:43:30', NULL, NULL),
(248, 'office', 'New office \'Public Relations\' added', '2025-05-27 22:44:27', NULL, NULL),
(249, 'office', 'New office \'Legal Affairs\' added', '2025-05-27 22:45:18', NULL, NULL),
(250, 'office', 'New office \'Procurement\' added', '2025-05-27 22:46:06', NULL, NULL),
(251, 'office', 'New office \'Records Management\' added', '2025-05-27 22:47:10', NULL, NULL),
(252, 'office', 'Office \'Procurement\' was updated', '2025-05-27 22:47:22', NULL, NULL),
(253, 'office', 'New office \'Customer Service\' added', '2025-05-27 22:48:40', NULL, NULL),
(254, 'office', 'New office \'Maintenance and Facilities\' added', '2025-05-27 22:52:12', NULL, NULL),
(255, 'office', 'New office \'Planning and Development\' added', '2025-05-27 22:56:49', NULL, NULL),
(256, 'office', 'New office \'Internal Audit\' added', '2025-05-27 22:57:48', NULL, NULL),
(257, 'office', 'Office \'Customer Service\' was updated', '2025-05-27 23:07:58', NULL, NULL),
(258, 'file', 'Floor plan location updated for Procurement', '2025-05-28 01:55:44', NULL, NULL),
(259, 'file', 'Floor plan location updated for Legal Affairs', '2025-05-28 01:55:44', NULL, NULL),
(260, 'file', 'Floor plan location updated for Internal Audit', '2025-05-28 01:55:44', NULL, NULL),
(261, 'file', 'Floor plan location updated for Human Resources', '2025-05-28 01:55:44', NULL, NULL),
(262, 'file', 'Floor plan location updated for Information Technology', '2025-05-28 01:55:44', NULL, NULL),
(263, 'file', 'Floor plan location swapped with Records Management', '2025-05-28 01:55:44', NULL, NULL),
(264, 'file', 'Floor plan location updated for Records Management', '2025-05-28 01:55:44', NULL, NULL),
(265, 'file', 'Floor plan location swapped with Customer Service', '2025-05-28 01:55:44', NULL, NULL),
(266, 'file', 'Floor plan location updated for Customer Service', '2025-05-28 01:55:44', NULL, NULL),
(267, 'file', 'Floor plan location updated for Planning and Development', '2025-05-28 01:55:44', NULL, NULL),
(268, 'file', 'Floor plan location updated for Public Relations', '2025-05-28 01:55:44', NULL, NULL),
(269, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-05-28 01:55:44', NULL, NULL),
(270, 'file', 'Floor plan location updated for Procurement', '2025-07-22 06:15:53', NULL, NULL),
(271, 'file', 'Floor plan location updated for Legal Affairs', '2025-07-22 06:15:53', NULL, NULL),
(272, 'file', 'Floor plan location updated for Internal Audit', '2025-07-22 06:15:53', NULL, NULL),
(273, 'file', 'Floor plan location updated for Human Resources', '2025-07-22 06:15:53', NULL, NULL),
(274, 'file', 'Floor plan location updated for Information Technology', '2025-07-22 06:15:53', NULL, NULL),
(275, 'file', 'Floor plan location swapped with Public Relations', '2025-07-22 06:15:53', NULL, NULL),
(276, 'file', 'Floor plan location updated for Public Relations', '2025-07-22 06:15:53', NULL, NULL),
(277, 'file', 'Floor plan location updated for Customer Service', '2025-07-22 06:15:53', NULL, NULL),
(278, 'file', 'Floor plan location updated for Planning and Development', '2025-07-22 06:15:53', NULL, NULL),
(279, 'file', 'Floor plan location updated for Records Management', '2025-07-22 06:15:53', NULL, NULL),
(280, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-07-22 06:15:53', NULL, NULL),
(281, 'file', 'Floor plan location updated for Procurement', '2025-07-22 06:16:03', NULL, NULL),
(282, 'file', 'Floor plan location updated for Legal Affairs', '2025-07-22 06:16:03', NULL, NULL),
(283, 'file', 'Floor plan location updated for Internal Audit', '2025-07-22 06:16:03', NULL, NULL),
(284, 'file', 'Floor plan location updated for Human Resources', '2025-07-22 06:16:03', NULL, NULL),
(285, 'file', 'Floor plan location updated for Information Technology', '2025-07-22 06:16:03', NULL, NULL),
(286, 'file', 'Floor plan location swapped with Records Management', '2025-07-22 06:16:03', NULL, NULL),
(287, 'file', 'Floor plan location updated for Records Management', '2025-07-22 06:16:03', NULL, NULL),
(288, 'file', 'Floor plan location updated for Customer Service', '2025-07-22 06:16:03', NULL, NULL),
(289, 'file', 'Floor plan location updated for Planning and Development', '2025-07-22 06:16:03', NULL, NULL),
(290, 'file', 'Floor plan location updated for Public Relations', '2025-07-22 06:16:03', NULL, NULL),
(291, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-07-22 06:16:03', NULL, NULL),
(292, 'file', 'Floor plan location updated for Procurement', '2025-07-22 06:16:13', NULL, NULL),
(293, 'file', 'Floor plan location updated for Legal Affairs', '2025-07-22 06:16:13', NULL, NULL),
(294, 'file', 'Floor plan location updated for Internal Audit', '2025-07-22 06:16:13', NULL, NULL),
(295, 'file', 'Floor plan location updated for Human Resources', '2025-07-22 06:16:13', NULL, NULL),
(296, 'file', 'Floor plan location updated for Information Technology', '2025-07-22 06:16:13', NULL, NULL),
(297, 'file', 'Floor plan location swapped with Customer Service', '2025-07-22 06:16:13', NULL, NULL),
(298, 'file', 'Floor plan location updated for Customer Service', '2025-07-22 06:16:13', NULL, NULL),
(299, 'file', 'Floor plan location updated for Records Management', '2025-07-22 06:16:13', NULL, NULL),
(300, 'file', 'Floor plan location updated for Planning and Development', '2025-07-22 06:16:13', NULL, NULL),
(301, 'file', 'Floor plan location updated for Public Relations', '2025-07-22 06:16:13', NULL, NULL),
(302, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-07-22 06:16:13', NULL, NULL),
(303, 'file', 'Floor plan location updated for Procurement', '2025-07-31 10:35:24', NULL, NULL),
(304, 'file', 'Floor plan location updated for Legal Affairs', '2025-07-31 10:35:24', NULL, NULL),
(305, 'file', 'Floor plan location updated for Internal Audit', '2025-07-31 10:35:24', NULL, NULL),
(306, 'file', 'Floor plan location updated for Human Resources', '2025-07-31 10:35:24', NULL, NULL),
(307, 'file', 'Floor plan location swapped with Records Management', '2025-07-31 10:35:24', NULL, NULL),
(308, 'file', 'Floor plan location updated for Records Management', '2025-07-31 10:35:24', NULL, NULL),
(309, 'file', 'Floor plan location updated for Customer Service', '2025-07-31 10:35:24', NULL, NULL),
(310, 'file', 'Floor plan location updated for Information Technology', '2025-07-31 10:35:24', NULL, NULL),
(311, 'file', 'Floor plan location updated for Planning and Development', '2025-07-31 10:35:24', NULL, NULL),
(312, 'file', 'Floor plan location updated for Public Relations', '2025-07-31 10:35:24', NULL, NULL),
(313, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-07-31 10:35:24', NULL, NULL),
(314, 'file', 'Floor plan location updated for Procurement', '2025-07-31 10:43:42', NULL, NULL),
(315, 'file', 'Floor plan location swapped with Internal Audit', '2025-07-31 10:43:42', NULL, NULL),
(316, 'file', 'Floor plan location updated for Internal Audit', '2025-07-31 10:43:42', NULL, NULL),
(317, 'file', 'Floor plan location updated for Legal Affairs', '2025-07-31 10:43:42', NULL, NULL),
(318, 'file', 'Floor plan location updated for Information Technology', '2025-07-31 10:43:42', NULL, NULL),
(319, 'file', 'Floor plan location swapped with Customer Service', '2025-07-31 10:43:42', NULL, NULL),
(320, 'file', 'Floor plan location updated for Customer Service', '2025-07-31 10:43:42', NULL, NULL),
(321, 'file', 'Floor plan location swapped with Records Management', '2025-07-31 10:43:42', NULL, NULL),
(322, 'file', 'Floor plan location updated for Records Management', '2025-07-31 10:43:42', NULL, NULL),
(323, 'file', 'Floor plan location updated for Human Resources', '2025-07-31 10:43:42', NULL, NULL),
(324, 'file', 'Floor plan location updated for Planning and Development', '2025-07-31 10:43:42', NULL, NULL),
(325, 'file', 'Floor plan location updated for Public Relations', '2025-07-31 10:43:42', NULL, NULL),
(326, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-07-31 10:43:42', NULL, NULL),
(327, 'file', 'Floor plan location updated for Procurement', '2025-08-01 08:25:07', NULL, NULL),
(328, 'file', 'Floor plan location updated for Internal Audit', '2025-08-01 08:25:07', NULL, NULL),
(329, 'file', 'Floor plan location updated for Legal Affairs', '2025-08-01 08:25:07', NULL, NULL),
(330, 'file', 'Floor plan location updated for Information Technology', '2025-08-01 08:25:07', NULL, NULL),
(331, 'file', 'Floor plan location updated for Customer Service', '2025-08-01 08:25:07', NULL, NULL),
(332, 'file', 'Floor plan location updated for Records Management', '2025-08-01 08:25:07', NULL, NULL),
(333, 'file', 'Floor plan location updated for Human Resources', '2025-08-01 08:25:07', NULL, NULL),
(334, 'file', 'Floor plan location updated for Planning and Development', '2025-08-01 08:25:07', NULL, NULL),
(335, 'file', 'Floor plan location updated for Public Relations', '2025-08-01 08:25:07', NULL, NULL),
(336, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-08-01 08:25:07', NULL, NULL),
(337, 'file', 'Floor plan location updated for Procurement', '2025-08-01 08:26:38', NULL, NULL),
(338, 'file', 'Floor plan location updated for Internal Audit', '2025-08-01 08:26:38', NULL, NULL),
(339, 'file', 'Floor plan location updated for Legal Affairs', '2025-08-01 08:26:38', NULL, NULL),
(340, 'file', 'Floor plan location updated for Information Technology', '2025-08-01 08:26:38', NULL, NULL),
(341, 'file', 'Floor plan location updated for Customer Service', '2025-08-01 08:26:38', NULL, NULL),
(342, 'file', 'Floor plan location updated for Records Management', '2025-08-01 08:26:38', NULL, NULL),
(343, 'file', 'Floor plan location updated for Human Resources', '2025-08-01 08:26:38', NULL, NULL),
(344, 'file', 'Floor plan location updated for Planning and Development', '2025-08-01 08:26:38', NULL, NULL),
(345, 'file', 'Floor plan location updated for Public Relations', '2025-08-01 08:26:38', NULL, NULL),
(346, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-08-01 08:26:38', NULL, NULL),
(347, 'file', 'Floor plan location updated for Procurement', '2025-08-01 08:27:21', NULL, NULL),
(348, 'file', 'Floor plan location updated for Internal Audit', '2025-08-01 08:27:21', NULL, NULL),
(349, 'file', 'Floor plan location updated for Legal Affairs', '2025-08-01 08:27:21', NULL, NULL),
(350, 'file', 'Floor plan location updated for Information Technology', '2025-08-01 08:27:21', NULL, NULL),
(351, 'file', 'Floor plan location updated for Customer Service', '2025-08-01 08:27:21', NULL, NULL),
(352, 'file', 'Floor plan location updated for Records Management', '2025-08-01 08:27:21', NULL, NULL),
(353, 'file', 'Floor plan location updated for Human Resources', '2025-08-01 08:27:21', NULL, NULL),
(354, 'file', 'Floor plan location updated for Planning and Development', '2025-08-01 08:27:21', NULL, NULL),
(355, 'file', 'Floor plan location updated for Public Relations', '2025-08-01 08:27:21', NULL, NULL),
(356, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-08-01 08:27:21', NULL, NULL),
(357, 'file', 'Floor plan location updated for Internal Audit', '2025-08-01 08:27:31', NULL, NULL),
(358, 'file', 'Floor plan location updated for Legal Affairs', '2025-08-01 08:27:31', NULL, NULL),
(359, 'file', 'Floor plan location updated for Information Technology', '2025-08-01 08:27:31', NULL, NULL),
(360, 'file', 'Floor plan location updated for Procurement', '2025-08-01 08:27:31', NULL, NULL),
(361, 'file', 'Floor plan location updated for Customer Service', '2025-08-01 08:27:31', NULL, NULL),
(362, 'file', 'Floor plan location updated for Records Management', '2025-08-01 08:27:31', NULL, NULL),
(363, 'file', 'Floor plan location updated for Human Resources', '2025-08-01 08:27:31', NULL, NULL),
(364, 'file', 'Floor plan location updated for Planning and Development', '2025-08-01 08:27:31', NULL, NULL),
(365, 'file', 'Floor plan location updated for Public Relations', '2025-08-01 08:27:31', NULL, NULL),
(366, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-08-01 08:27:31', NULL, NULL),
(367, 'file', 'Floor plan location updated for Legal Affairs', '2025-08-01 08:27:47', NULL, NULL),
(368, 'file', 'Floor plan location updated for Information Technology', '2025-08-01 08:27:47', NULL, NULL),
(369, 'file', 'Floor plan location updated for Internal Audit', '2025-08-01 08:27:47', NULL, NULL),
(370, 'file', 'Floor plan location updated for Procurement', '2025-08-01 08:27:47', NULL, NULL),
(371, 'file', 'Floor plan location updated for Customer Service', '2025-08-01 08:27:47', NULL, NULL),
(372, 'file', 'Floor plan location updated for Records Management', '2025-08-01 08:27:47', NULL, NULL),
(373, 'file', 'Floor plan location updated for Human Resources', '2025-08-01 08:27:47', NULL, NULL),
(374, 'file', 'Floor plan location updated for Planning and Development', '2025-08-01 08:27:47', NULL, NULL),
(375, 'file', 'Floor plan location updated for Public Relations', '2025-08-01 08:27:47', NULL, NULL),
(376, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-08-01 08:27:47', NULL, NULL),
(377, 'file', 'Floor plan location updated for Legal Affairs', '2025-08-01 08:29:06', NULL, NULL),
(378, 'file', 'Floor plan location updated for Information Technology', '2025-08-01 08:29:06', NULL, NULL),
(379, 'file', 'Floor plan location updated for Internal Audit', '2025-08-01 08:29:06', NULL, NULL),
(380, 'file', 'Floor plan location updated for Procurement', '2025-08-01 08:29:06', NULL, NULL),
(381, 'file', 'Floor plan location updated for Customer Service', '2025-08-01 08:29:06', NULL, NULL),
(382, 'file', 'Floor plan location updated for Records Management', '2025-08-01 08:29:06', NULL, NULL),
(383, 'file', 'Floor plan location updated for Human Resources', '2025-08-01 08:29:06', NULL, NULL),
(384, 'file', 'Floor plan location updated for Planning and Development', '2025-08-01 08:29:06', NULL, NULL),
(385, 'file', 'Floor plan location updated for Public Relations', '2025-08-01 08:29:06', NULL, NULL),
(386, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-08-01 08:29:06', NULL, NULL),
(387, 'file', 'Floor plan location updated for Information Technology', '2025-08-01 08:34:39', NULL, NULL),
(388, 'file', 'Floor plan location updated for Internal Audit', '2025-08-01 08:34:39', NULL, NULL),
(389, 'file', 'Floor plan location updated for Procurement', '2025-08-01 08:34:39', NULL, NULL),
(390, 'file', 'Floor plan location updated for Customer Service', '2025-08-01 08:34:39', NULL, NULL),
(391, 'file', 'Floor plan location updated for Records Management', '2025-08-01 08:34:39', NULL, NULL),
(392, 'file', 'Floor plan location updated for Human Resources', '2025-08-01 08:34:39', NULL, NULL),
(393, 'file', 'Floor plan location updated for Legal Affairs', '2025-08-01 08:34:39', NULL, NULL),
(394, 'file', 'Floor plan location updated for Planning and Development', '2025-08-01 08:34:39', NULL, NULL),
(395, 'file', 'Floor plan location updated for Public Relations', '2025-08-01 08:34:39', NULL, NULL),
(396, 'file', 'Floor plan location updated for Maintenance and Facilities', '2025-08-01 08:34:39', NULL, NULL),
(397, 'office', 'Office \'Customer Service\' was deleted', '2025-08-01 09:09:14', NULL, NULL),
(398, 'office', 'Office \'Human Resources\' was deleted', '2025-08-01 09:09:16', NULL, NULL),
(399, 'office', 'Office \'Information Technology\' was deleted', '2025-08-01 09:09:17', NULL, NULL),
(400, 'office', 'Office \'Internal Audit\' was deleted', '2025-08-01 09:09:19', NULL, NULL),
(401, 'office', 'Office \'Legal Affairs\' was deleted', '2025-08-01 09:09:20', NULL, NULL),
(402, 'office', 'Office \'Maintenance and Facilities\' was deleted', '2025-08-01 09:09:21', NULL, NULL),
(403, 'office', 'Office \'Planning and Development\' was deleted', '2025-08-01 09:09:22', NULL, NULL),
(404, 'office', 'Office \'Procurement\' was deleted', '2025-08-01 09:09:23', NULL, NULL),
(405, 'office', 'Office \'Public Relations\' was deleted', '2025-08-01 09:09:25', NULL, NULL),
(406, 'office', 'Office \'Records Management\' was deleted', '2025-08-01 09:09:26', NULL, NULL),
(407, 'office', 'New office \'JJ\'s Workspace\' added', '2025-08-09 13:30:43', NULL, NULL),
(408, 'file', 'Floor plan location updated for JJ\'s Workspace', '2025-08-09 14:31:57', NULL, NULL),
(409, 'file', 'Floor plan location updated for JJ\'s Workspace', '2025-08-09 14:32:07', NULL, NULL),
(410, 'office', 'New office \'Human resource\' added', '2025-08-09 14:46:59', NULL, NULL),
(411, 'file', 'Floor plan location updated for JJ\'s Workspace', '2025-08-12 04:02:04', NULL, NULL),
(412, 'file', 'Floor plan location updated for Human resource', '2025-08-12 04:02:04', NULL, NULL),
(413, 'office', 'Office \'Human resource\' was deleted', '2025-08-17 06:37:30', NULL, NULL),
(414, 'office', 'Office \'JJ\'s Workspace\' was deleted', '2025-08-17 06:37:31', NULL, NULL),
(415, 'office', 'New office \'Human RE\' added', '2025-08-19 10:18:04', NULL, NULL),
(416, 'office', 'New office \'THE WHOOO\' added', '2025-08-19 10:19:09', NULL, NULL),
(417, 'office', 'Office \'Human RE\' was deleted', '2025-08-19 10:29:11', NULL, NULL),
(418, 'office', 'Office \'THE WHOOO\' was deleted', '2025-08-19 10:29:13', NULL, NULL),
(419, 'office', 'New office \'Resources oasdasdasdsadasdasd\' added', '2025-08-19 10:29:51', NULL, NULL),
(420, 'office', 'Office \'Resources oasdasdasdsadasdasd\' was deleted', '2025-08-19 10:50:03', NULL, NULL),
(421, 'office', 'New office \'Human Resources\' added', '2025-08-19 10:50:27', NULL, NULL),
(422, 'office', 'New office \'The best part is the middle of me\' added', '2025-08-19 11:11:49', NULL, NULL),
(423, 'office', 'Office \'The best part is the middle of me\' was deleted', '2025-08-19 11:47:05', NULL, NULL),
(424, 'office', 'New office \'Governor\'s office\' added', '2025-08-19 11:47:28', 113, NULL),
(425, 'office', 'Office \'Human Resources\' was deleted', '2025-08-19 11:50:08', NULL, NULL),
(426, 'office', 'New office \'Comfort Room 1\' added', '2025-08-19 11:56:35', 114, NULL),
(427, 'office', 'New office \'ICTD Office\' added', '2025-08-19 11:57:20', 115, NULL),
(428, 'office', 'New office \'Comfort Room\' added', '2025-08-19 11:57:56', 116, NULL),
(429, 'office', 'New office \'ROoM4 Ni Lods\' added', '2025-08-19 12:05:58', 117, NULL),
(430, 'office', 'New office \'Room 6 Organization\' added', '2025-08-19 12:12:31', NULL, NULL),
(431, 'office', 'Office \'Room 6 Organization\' was deleted', '2025-08-19 12:12:48', NULL, NULL),
(432, 'office', 'New office \'JJ\'s Workspace\' added', '2025-08-19 12:13:00', 119, NULL),
(433, 'office', 'New office \'ROOm & Organize\' added', '2025-08-19 12:13:31', 120, NULL),
(434, 'office', 'New office \'ROOM8 Dummy\' added', '2025-08-19 12:14:17', 121, NULL),
(435, 'office', 'New office \'ROOM 9 Deposit\' added', '2025-08-19 12:15:22', NULL, NULL),
(436, 'office', 'Office \'ROOM 9 Deposit\' was deleted', '2025-08-19 12:15:44', NULL, NULL),
(437, 'office', 'New office \'Room9\' added', '2025-08-19 12:15:54', NULL, NULL),
(438, 'office', 'Office \'Room9 Dummy\' was updated', '2025-08-19 12:43:45', NULL, NULL),
(439, 'office', 'Office \'Room9 Dummy\' was deleted', '2025-08-19 12:48:10', NULL, NULL),
(440, 'office', 'New office \'Room9 yow\' added', '2025-08-19 13:02:25', 124, NULL),
(441, 'office', 'New office \'2nd floor WHOOOOO\' added', '2025-08-19 14:45:53', NULL, NULL),
(442, 'file', 'Floor plan location swapped with JJ\'s Workspace', '2025-08-19 15:51:17', 114, NULL),
(443, 'file', 'Floor plan location updated for JJ\'s Workspace', '2025-08-19 15:51:17', 119, NULL),
(444, 'file', 'Floor plan location updated for Governor\'s office', '2025-08-19 15:51:17', 113, NULL),
(445, 'file', 'Floor plan location updated for 2nd floor WHOOOOO', '2025-08-19 15:51:17', NULL, NULL),
(446, 'file', 'Floor plan location updated for ROoM4 Ni Lods', '2025-08-19 15:51:17', 117, NULL),
(447, 'file', 'Floor plan location updated for Comfort Room', '2025-08-19 15:51:17', 116, NULL),
(448, 'file', 'Floor plan location updated for Comfort Room 1', '2025-08-19 15:51:17', 114, NULL),
(449, 'file', 'Floor plan location updated for ROOm & Organize', '2025-08-19 15:51:17', 120, NULL),
(450, 'file', 'Floor plan location updated for ROOM8 Dummy', '2025-08-19 15:51:17', 121, NULL),
(451, 'file', 'Floor plan location updated for Room9 yow', '2025-08-19 15:51:17', 124, NULL),
(452, 'office', 'New office \'akon room\' added', '2025-08-22 06:58:07', 126, NULL),
(453, 'office', 'Office \'2nd floor WHOOOOO\' was deleted', '2025-08-23 15:14:49', NULL, NULL),
(454, 'office', 'New office \'Room ni pao pao\' added', '2025-08-23 15:16:01', 127, NULL),
(455, 'office', 'Office \'Jepoy\'s workspace\' was updated', '2025-08-23 16:50:31', 119, NULL);

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
(74, 4, 'Good service', '2025-05-26 11:19:32', 'Jopays', NULL);

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
(113, 'Governor\'s office', 'the governors office', '123123', 'room-2-1', 'The best', '2025-08-19 11:47:28', 'active', NULL),
(114, 'Comfort Room 1', 'asdasd', 'dasdasd', 'room-6-1', 'adadsadas', '2025-08-19 11:56:35', 'active', NULL),
(115, 'ICTD Office', 'asdasd', 'asdasdas', 'room-3-1', 'asdasdas', '2025-08-19 11:57:20', 'active', NULL),
(116, 'Comfort Room', 'adasdasd', 'asdasdasd', 'room-5-1', 'asdasdasd', '2025-08-19 11:57:56', 'active', NULL),
(117, 'ROoM4 Ni Lods', 'adas', 'asdasd', 'room-4-1', 'asdasd', '2025-08-19 12:05:58', 'active', NULL),
(119, 'Jepoy\'s workspace', 'adasd', 'asdasd', 'room-1-1', 'asdasdasd', '2025-08-19 12:13:00', 'active', NULL),
(120, 'ROOm & Organize', 'sadasda', 'asdasd', 'room-7-1', 'asdasdas', '2025-08-19 12:13:31', 'active', NULL),
(121, 'ROOM8 Dummy', 'qewqe', 'adad', 'room-8-1', 'zxczxczx', '2025-08-19 12:14:17', 'active', NULL),
(124, 'Room9 yow', 'asdasd', 'asdasdasd', 'room-9-1', 'asdasdasd', '2025-08-19 13:02:25', 'active', NULL),
(126, 'akon room', 'hjd798', '987897', 'room-12-2', 'bjbhh', '2025-08-22 06:58:07', 'active', NULL),
(127, 'Room ni pao pao', '@nd floor', 'asdasd', 'room-13-2', 'asdasd', '2025-08-23 15:16:01', 'active', NULL);

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
(256, 113, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=113', 'Governors_office_113.png', '2025-08-19 11:47:28'),
(257, 114, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=114', 'Comfort_Room_1_114.png', '2025-08-19 11:56:35'),
(258, 115, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=115', 'ICTD_Office_115.png', '2025-08-19 11:57:20'),
(259, 116, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=116', 'Comfort_Room_116.png', '2025-08-19 11:57:56'),
(260, 117, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=117', 'ROoM4_Ni_Lods_117.png', '2025-08-19 12:05:58'),
(262, 119, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=119', 'JJs_Workspace_119.png', '2025-08-19 12:13:00'),
(263, 120, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=120', 'ROOm_Organize_120.png', '2025-08-19 12:13:31'),
(264, 121, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=121', 'ROOM8_Dummy_121.png', '2025-08-19 12:14:17'),
(267, 124, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=124', 'Room9_yow_124.png', '2025-08-19 13:02:25'),
(269, 126, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=126', 'akon_room_126.png', '2025-08-22 06:58:08'),
(270, 127, 'http://192.168.254.164/FinalDev/mobileScreen/explore.php?office_id=127', 'Room_ni_pao_pao_127.png', '2025-08-23 15:16:01');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=456;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

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
-- AUTO_INCREMENT for table `qrcode_info`
--
ALTER TABLE `qrcode_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=271;

--
-- AUTO_INCREMENT for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1338;

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
