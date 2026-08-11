/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: isp_billing_lite_db
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(191) DEFAULT NULL,
  `user_role` varchar(100) DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_created_at` (`created_at`),
  KEY `idx_activity_logs_user_email` (`user_email`),
  KEY `idx_activity_logs_module` (`module`),
  KEY `idx_activity_logs_action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=346 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES
(1,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully.','127.0.0.1','2026-03-12 08:44:33'),
(2,1,'admin@isp.com','ROLE_ADMIN','Customers','CREATE','Added customer: mama','127.0.0.1','2026-03-12 08:45:23'),
(3,1,'admin@isp.com','ROLE_ADMIN','Customers','DELETE','Deleted customer: mama','127.0.0.1','2026-03-12 08:45:41'),
(4,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully.','127.0.0.1','2026-03-12 09:32:13'),
(5,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 4: jomar@gmail.com','127.0.0.1','2026-03-12 09:32:43'),
(6,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 6: admin@gmail.com','127.0.0.1','2026-03-12 09:32:48'),
(7,1,'admin@isp.com','ROLE_ADMIN','Payments','CREATE','Recorded payment ID 45 for invoice ID 34.','127.0.0.1','2026-03-12 09:33:25'),
(8,1,'admin@isp.com','ROLE_ADMIN','Payments','REJECT','Rejected payment ID 45 for invoice ID 34.','127.0.0.1','2026-03-12 09:33:29'),
(9,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-12 10:01:41'),
(10,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 1: lacierasjomar@gmail.com','127.0.0.1','2026-03-12 10:28:53'),
(11,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 1: admin@isp.com','127.0.0.1','2026-03-12 10:29:24'),
(12,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-12 10:42:27'),
(13,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP exemption.','127.0.0.1','2026-03-12 10:42:32'),
(14,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 13: lacierasjomar17@gmail.com','127.0.0.1','2026-03-12 10:42:54'),
(15,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 12: admin@fusionitsolution.com','127.0.0.1','2026-03-12 10:42:58'),
(16,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-12 10:43:00'),
(17,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP exemption.','127.0.0.1','2026-03-12 10:44:10'),
(18,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-12 10:46:01'),
(19,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-12 10:59:52'),
(20,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-12 11:04:35'),
(21,13,'lacierasjomar17@gmail.com','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','127.0.0.1','2026-03-12 11:05:20'),
(22,13,'lacierasjomar17@gmail.com','ROLE_CUSTOMER','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-12 11:05:28'),
(23,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-12 11:05:59'),
(24,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 11: 22-05583@g.batstate-u.edu.ph','127.0.0.1','2026-03-12 11:06:11'),
(25,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-12 11:06:15'),
(26,11,'22-05583@g.batstate-u.edu.ph','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','127.0.0.1','2026-03-12 11:06:43'),
(27,11,'22-05583@g.batstate-u.edu.ph','ROLE_CUSTOMER','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-12 11:25:32'),
(28,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-12 11:25:39'),
(29,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-12 11:33:15'),
(30,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-12 14:04:57'),
(31,1,'admin@isp.com','ROLE_ADMIN','Inquiries','REGISTER_CUSTOMER','Converted inquiry ID 1 into customer ID 15, subscription, and invoice ID 35.','127.0.0.1','2026-03-12 16:17:52'),
(32,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 2 email(s), failed 0.','127.0.0.1','2026-03-12 16:18:16'),
(33,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-12 16:28:13'),
(34,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-13 00:50:07'),
(35,1,'admin@isp.com','ROLE_ADMIN','Inquiries','REJECT','Rejected inquiry ID 2.','127.0.0.1','2026-03-13 01:04:02'),
(36,13,'lacierasjomar17@gmail.com','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','127.0.0.1','2026-03-16 08:54:16'),
(37,13,'lacierasjomar17@gmail.com','ROLE_CUSTOMER','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-16 08:54:25'),
(38,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-16 08:55:04'),
(39,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-17 01:58:27'),
(40,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-17 04:57:48'),
(41,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-17 05:01:37'),
(42,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-17 12:11:45'),
(43,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-18 03:22:01'),
(44,1,'admin@isp.com','ROLE_ADMIN','Settings','RESTORE','Restored latest backup: isp_billing_backup_2026-03-18_03-29-07.sql','127.0.0.1','2026-03-18 05:30:59'),
(45,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','CREATE','Created subscription for customer ID 9 with plan ID 12','127.0.0.1','2026-03-18 05:34:54'),
(46,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-18 05:35:43'),
(47,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-18 06:10:54'),
(48,1,'admin@isp.com','ROLE_ADMIN','Settings','UPDATE','Updated system settings.','127.0.0.1','2026-03-18 06:21:14'),
(49,1,'admin@isp.com','ROLE_ADMIN','Inquiries','REGISTER_CUSTOMER','Converted inquiry ID 3 into customer ID 16, subscription, and prorated invoice ID 36.','127.0.0.1','2026-03-18 06:56:02'),
(50,1,'admin@isp.com','ROLE_ADMIN','Settings','RESTORE','Restored latest backup: isp_billing_backup_2026-03-18_06-56-13.sql','127.0.0.1','2026-03-18 06:56:45'),
(51,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-18 08:15:54'),
(52,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-18 23:30:09'),
(53,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-19 00:10:46'),
(54,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS content sections.','127.0.0.1','2026-03-19 00:23:12'),
(55,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS content sections.','127.0.0.1','2026-03-19 00:23:24'),
(56,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-21 13:36:44'),
(57,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-23 01:45:50'),
(58,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-24 01:53:28'),
(59,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-24 03:15:33'),
(60,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-24 03:52:05'),
(61,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-24 04:45:13'),
(62,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-24 04:45:27'),
(63,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-24 04:45:32'),
(64,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:02:41'),
(65,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:02:46'),
(66,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:02:50'),
(67,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:02:55'),
(68,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:03:00'),
(69,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:03:05'),
(70,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:04:33'),
(71,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:04:37'),
(72,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:04:41'),
(73,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:04:46'),
(74,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:04:49'),
(75,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:04:53'),
(76,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:05:01'),
(77,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:05:40'),
(78,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','127.0.0.1','2026-03-24 05:05:56'),
(79,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-24 05:06:55'),
(80,13,'lacierasjomar17@gmail.com','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','127.0.0.1','2026-03-24 05:07:44'),
(81,13,'lacierasjomar17@gmail.com','ROLE_CUSTOMER','Payments','CREATE','Recorded payment ID 46 for invoice ID 34.','127.0.0.1','2026-03-24 05:08:20'),
(82,13,'lacierasjomar17@gmail.com','ROLE_CUSTOMER','Auth','LOGOUT','User logged out.','127.0.0.1','2026-03-24 05:08:29'),
(83,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-24 05:08:34'),
(84,1,'admin@isp.com','ROLE_ADMIN','Payments','VERIFY','Verified payment ID 46 for invoice ID 34.','127.0.0.1','2026-03-24 05:08:48'),
(85,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-24 07:59:40'),
(86,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-24 08:00:08'),
(87,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-25 11:02:03'),
(88,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS content sections.','127.0.0.1','2026-03-25 11:03:31'),
(89,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-25 15:59:23'),
(90,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS content sections.','127.0.0.1','2026-03-25 16:01:21'),
(91,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-26 00:37:35'),
(92,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-30 13:20:49'),
(93,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-30 13:22:17'),
(94,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-30 13:22:54'),
(95,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-30 13:23:31'),
(96,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-30 13:25:09'),
(97,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-30 13:27:56'),
(98,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:31:07'),
(99,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:31:39'),
(100,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:32:09'),
(101,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:32:52'),
(102,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:34:32'),
(103,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:34:57'),
(104,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:35:57'),
(105,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:35:58'),
(106,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:37:12'),
(107,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-03-30 13:37:39'),
(108,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS content sections.','127.0.0.1','2026-03-30 13:38:25'),
(109,1,'admin@isp.com','ROLE_ADMIN','Settings','RESTORE','Restored latest backup: isp_billing_backup_2026-03-30_13-40-20.sql','127.0.0.1','2026-03-30 14:02:37'),
(110,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-03-31 00:58:50'),
(111,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-31 01:21:38'),
(112,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-31 01:23:44'),
(113,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-31 01:24:08'),
(114,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-31 01:24:16'),
(115,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-31 01:24:25'),
(116,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-31 01:24:36'),
(117,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-03-31 01:24:44'),
(118,13,'lacierasjomar17@gmail.com','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','127.0.0.1','2026-04-02 01:43:18'),
(119,13,'lacierasjomar17@gmail.com','ROLE_CUSTOMER','Auth','LOGOUT','User logged out.','127.0.0.1','2026-04-02 01:45:45'),
(120,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-04-02 01:46:54'),
(121,1,'admin@isp.com','ROLE_ADMIN','Settings','UPDATE','Updated system settings including SMTP configuration.','127.0.0.1','2026-04-02 01:54:27'),
(122,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-04-03 01:06:34'),
(123,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-04-03 01:07:10'),
(124,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-04-03 01:07:27'),
(125,1,'admin@isp.com','ROLE_ADMIN','Users','CREATE','Created user: danica_mae_canosa@bec.edu.ph (ROLE_CUSTOMER)','127.0.0.1','2026-04-03 01:08:16'),
(126,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-04-03 01:08:19'),
(127,14,'danica_mae_canosa@bec.edu.ph','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','127.0.0.1','2026-04-03 01:08:43'),
(128,14,'danica_mae_canosa@bec.edu.ph','ROLE_CUSTOMER','Payments','CREATE','Recorded payment ID 47 for invoice ID 41.','127.0.0.1','2026-04-03 01:09:10'),
(129,14,'danica_mae_canosa@bec.edu.ph','ROLE_CUSTOMER','Auth','LOGOUT','User logged out.','127.0.0.1','2026-04-03 01:09:23'),
(130,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-04-03 01:09:32'),
(131,1,'admin@isp.com','ROLE_ADMIN','Payments','VERIFY','Verified payment ID 47 for invoice ID 41.','127.0.0.1','2026-04-03 01:09:39'),
(132,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-04-03 01:10:10'),
(133,14,'danica_mae_canosa@bec.edu.ph','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','127.0.0.1','2026-04-03 01:10:33'),
(134,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-04-06 00:55:02'),
(135,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','::1','2026-04-14 02:00:12'),
(136,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','::1','2026-04-14 02:08:40'),
(137,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','::1','2026-04-14 02:08:44'),
(138,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','::1','2026-04-14 02:22:46'),
(139,1,'admin@isp.com','ROLE_ADMIN','Settings','UPDATE','Updated system settings including SMTP configuration.','::1','2026-04-14 02:25:55'),
(140,1,'admin@isp.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','::1','2026-04-14 02:26:10'),
(141,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','::1','2026-04-14 02:36:05'),
(142,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','::1','2026-04-14 02:36:10'),
(143,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS content sections.','::1','2026-04-14 02:47:28'),
(144,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','::1','2026-04-14 02:48:58'),
(145,1,'admin@isp.com','ROLE_ADMIN','Users','CREATE','Created user: fusionlinkcomp.1@gmail.com (ROLE_ADMIN)','::1','2026-04-14 02:49:28'),
(146,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','::1','2026-04-14 02:49:30'),
(147,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','::1','2026-04-14 02:50:00'),
(148,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','::1','2026-04-14 02:50:33'),
(149,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Settings','RESTORE','Restored latest backup: isp_billing_backup_2026-04-14_02-51-15.sql','::1','2026-04-14 02:51:42'),
(150,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','::1','2026-04-14 02:58:25'),
(151,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','::1','2026-04-14 03:00:14'),
(152,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Customers','CREATE','Added customer: fusion','::1','2026-04-14 03:12:08'),
(153,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Subscriptions','CREATE','Created subscription for customer ID 17 with plan ID 11','::1','2026-04-14 03:12:24'),
(154,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Customers','CREATE','Added customer: ronald de torres','::1','2026-04-14 03:14:07'),
(155,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Subscriptions','CREATE','Created subscription for customer ID 18 with plan ID 12','::1','2026-04-14 03:14:18'),
(156,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Invoices','GENERATE_SEND','Generated 0 invoice(s), sent 1 email(s), failed 0.','::1','2026-04-14 03:14:48'),
(157,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Inquiries','REGISTER_CUSTOMER','Converted inquiry ID 5 into customer ID 19, subscription, and prorated invoice ID 45.','::1','2026-04-14 03:17:07'),
(158,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Users','CREATE','Created user: ronaldjdetorres@gmail.com (ROLE_CUSTOMER)','::1','2026-04-14 03:20:04'),
(159,16,'ronaldjdetorres@gmail.com','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','::1','2026-04-14 03:20:31'),
(160,16,'ronaldjdetorres@gmail.com','ROLE_CUSTOMER','Auth','LOGOUT','User logged out.','::1','2026-04-14 03:21:02'),
(161,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','::1','2026-04-14 03:21:13'),
(162,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS content sections.','::1','2026-04-14 03:22:27'),
(163,1,'admin@isp.com','ROLE_ADMIN','Settings','RESTORE','Restored latest backup: isp_billing_backup_2026-04-14_03-22-43.sql','::1','2026-04-14 03:23:06'),
(164,1,'admin@isp.com','ROLE_ADMIN','Plans','CREATE','Added plan: 500MBPS (5000 MBPS)','::1','2026-04-14 03:30:34'),
(165,1,'admin@isp.com','ROLE_ADMIN','Plans','DELETE','Deleted plan: 500MBPS','::1','2026-04-14 03:32:09'),
(166,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 28','::1','2026-04-14 03:32:44'),
(167,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 27','::1','2026-04-14 03:32:46'),
(168,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 26','::1','2026-04-14 03:32:47'),
(169,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 25','::1','2026-04-14 03:32:48'),
(170,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 24','::1','2026-04-14 03:32:49'),
(171,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 23','::1','2026-04-14 03:32:49'),
(172,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 22','::1','2026-04-14 03:32:50'),
(173,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 21','::1','2026-04-14 03:32:50'),
(174,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 16','::1','2026-04-14 03:32:51'),
(175,1,'admin@isp.com','ROLE_ADMIN','Subscriptions','DELETE','Deleted subscription ID 15','::1','2026-04-14 03:32:51'),
(176,1,'admin@isp.com','ROLE_ADMIN','Settings','RESET','Reset business database records.','::1','2026-04-14 03:33:20'),
(177,1,'admin@isp.com','ROLE_ADMIN','Inquiries','REJECT','Rejected inquiry ID 6.','::1','2026-04-14 03:33:30'),
(178,1,'admin@isp.com','ROLE_ADMIN','Inquiries','REJECT','Rejected inquiry ID 4.','::1','2026-04-14 03:33:33'),
(179,1,'admin@isp.com','ROLE_ADMIN','Plans','DELETE','Deleted plan: Fiber 6','::1','2026-04-14 03:33:49'),
(180,1,'admin@isp.com','ROLE_ADMIN','Plans','DELETE','Deleted plan: Fiber 5','::1','2026-04-14 03:33:50'),
(181,1,'admin@isp.com','ROLE_ADMIN','Plans','DELETE','Deleted plan: FIber 3','::1','2026-04-14 03:33:51'),
(182,1,'admin@isp.com','ROLE_ADMIN','Plans','DELETE','Deleted plan: Fiber Ultra','::1','2026-04-14 03:33:51'),
(183,1,'admin@isp.com','ROLE_ADMIN','Plans','DELETE','Deleted plan: Fiber Pro','::1','2026-04-14 03:33:52'),
(184,1,'admin@isp.com','ROLE_ADMIN','Plans','DELETE','Deleted plan: Fiber Basic','::1','2026-04-14 03:33:52'),
(185,1,'admin@isp.com','ROLE_ADMIN','Users','DELETE','Deleted user: ronaldjdetorres@gmail.com','::1','2026-04-14 03:34:08'),
(186,1,'admin@isp.com','ROLE_ADMIN','Users','DELETE','Deleted user: danica_mae_canosa@bec.edu.ph','::1','2026-04-14 03:34:10'),
(187,1,'admin@isp.com','ROLE_ADMIN','Users','DELETE','Deleted user: lacierasjomar17@gmail.com','::1','2026-04-14 03:34:12'),
(188,1,'admin@isp.com','ROLE_ADMIN','Users','DELETE','Deleted user: admin@fusionitsolution.com','::1','2026-04-14 03:34:14'),
(189,1,'admin@isp.com','ROLE_ADMIN','Users','DELETE','Deleted user: 22-05583@g.batstate-u.edu.ph','::1','2026-04-14 03:34:15'),
(190,1,'admin@isp.com','ROLE_ADMIN','Users','DELETE','Deleted user: yonicschill@gmail.com','::1','2026-04-14 03:34:17'),
(191,1,'admin@isp.com','ROLE_ADMIN','Users','DELETE','Deleted user: fusion@gmail.com','::1','2026-04-14 03:34:18'),
(192,1,'admin@isp.com','ROLE_ADMIN','Users','DELETE','Deleted user: admin@gmail.com','::1','2026-04-14 03:34:20'),
(193,1,'admin@isp.com','ROLE_ADMIN','Users','DELETE','Deleted user: jomar@gmail.com','::1','2026-04-14 03:34:21'),
(194,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','::1','2026-04-14 03:34:29'),
(195,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','::1','2026-04-14 03:34:59'),
(196,15,'fusionlinkcomp.1@gmail.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS website settings.','::1','2026-04-14 03:36:15'),
(197,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-06-17 06:46:22'),
(198,1,'admin@isp.com','ROLE_ADMIN','Users','CREATE','Created user: cedrickmacatangay@gmail.com (ROLE_ADMIN)','127.0.0.1','2026-06-17 07:07:30'),
(199,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','::1','2026-06-17 10:50:56'),
(200,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','::1','2026-06-17 10:52:34'),
(201,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','::1','2026-06-17 10:52:50'),
(202,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 17: cedrickmacatangay@gmail.com','::1','2026-06-17 10:53:14'),
(203,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','::1','2026-06-17 10:53:20'),
(204,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','::1','2026-06-17 10:55:21'),
(205,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','::1','2026-06-17 10:55:50'),
(206,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','::1','2026-06-17 10:55:56'),
(207,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','::1','2026-06-17 10:56:05'),
(208,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Settings','UPDATE','Updated system settings including SMTP configuration.','::1','2026-06-17 10:59:24'),
(209,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','::1','2026-06-17 11:08:28'),
(210,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-17 15:43:44'),
(211,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 17: cedrickmacatangay@gmail.com','127.0.0.1','2026-06-17 15:44:01'),
(212,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 15: fusionlinkcomp.1@gmail.com','127.0.0.1','2026-06-17 15:44:14'),
(213,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','::1','2026-06-17 15:51:05'),
(214,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','::1','2026-06-17 15:51:05'),
(215,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','::1','2026-06-17 15:51:27'),
(216,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS content sections.','127.0.0.1','2026-06-17 15:51:42'),
(217,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media settings.','127.0.0.1','2026-06-17 15:52:58'),
(218,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS website settings.','127.0.0.1','2026-06-17 15:54:21'),
(219,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS navigation labels.','127.0.0.1','2026-06-17 15:55:02'),
(220,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','::1','2026-06-17 16:09:58'),
(221,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','::1','2026-06-17 16:13:22'),
(222,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media uploads.','::1','2026-06-17 16:13:22'),
(223,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media uploads.','127.0.0.1','2026-06-17 16:14:37'),
(224,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media uploads.','127.0.0.1','2026-06-17 16:16:05'),
(225,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media uploads.','127.0.0.1','2026-06-17 16:24:23'),
(226,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media uploads.','127.0.0.1','2026-06-17 16:24:41'),
(227,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media uploads.','127.0.0.1','2026-06-17 16:24:54'),
(228,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS media uploads.','127.0.0.1','2026-06-17 16:25:06'),
(229,1,'admin@isp.com','ROLE_ADMIN','Plans','CREATE','Added plan: Residential Connectivity (20MBPS)','127.0.0.1','2026-06-17 16:30:29'),
(230,1,'admin@isp.com','ROLE_ADMIN','Plans','CREATE','Added plan: Remote Work Connectivity (50 MBPS)','127.0.0.1','2026-06-17 16:31:07'),
(231,1,'admin@isp.com','ROLE_ADMIN','Plans','UPDATE','Updated plan ID 14: Residential Connectivity','127.0.0.1','2026-06-17 16:31:20'),
(232,1,'admin@isp.com','ROLE_ADMIN','Plans','CREATE','Added plan: Business Connectivity (60 MBPS)','127.0.0.1','2026-06-17 16:32:01'),
(233,1,'admin@isp.com','ROLE_ADMIN','CMS','UPDATE','Updated CMS design settings.','127.0.0.1','2026-06-17 16:35:56'),
(234,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 17: cedrickmacatangay@gmail.com','127.0.0.1','2026-06-17 16:40:27'),
(235,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-06-17 16:40:32'),
(236,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-17 16:57:49'),
(237,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-06-17 17:18:18'),
(238,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-17 17:39:51'),
(239,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-06-17 17:39:57'),
(240,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-17 17:54:17'),
(241,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','REJECT','Rejected inquiry ID 7.','127.0.0.1','2026-06-17 18:08:04'),
(242,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Settings','RESTORE','Restored latest backup: isp_billing_backup_2026-06-17_18-59-09.sql','127.0.0.1','2026-06-17 19:01:21'),
(243,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Settings','UPDATE','Updated system settings including SMTP configuration.','127.0.0.1','2026-06-17 19:02:29'),
(244,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Settings','TEST_EMAIL','Sent test email to admin@fusionitsolution.com','127.0.0.1','2026-06-17 19:05:21'),
(245,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Settings','RESTORE','Restored backup: isp_billing_uploaded_2026-06-17_19-10-47_isp_billing_backup_2026-06-17_19-06-01.sql','127.0.0.1','2026-06-17 19:10:48'),
(246,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Settings','TEST_EMAIL','Sent test email to admin@fusionitsolution.com','127.0.0.1','2026-06-17 19:11:02'),
(247,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-06-17 19:11:09'),
(248,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-18 09:55:13'),
(249,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-06-18 10:05:39'),
(250,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-18 12:19:23'),
(251,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Users','CREATE','Created user: ronaldjdetorres@gmail.com (ROLE_ADMIN)','127.0.0.1','2026-06-18 12:19:54'),
(252,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Customers','CREATE','Added customer: Marites Enriquez','127.0.0.1','2026-06-18 12:24:44'),
(253,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Subscriptions','CREATE','Created subscription for customer ID 1 with plan ID 14','127.0.0.1','2026-06-18 12:26:13'),
(254,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Users','CREATE','Created user: maritesenriquez5@gmail.com (ROLE_CUSTOMER)','127.0.0.1','2026-06-18 12:26:42'),
(255,19,'maritesenriquez5@gmail.com','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification.','127.0.0.1','2026-06-18 12:29:01'),
(256,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-22 01:50:48'),
(257,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-22 05:12:37'),
(258,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-22 13:20:33'),
(259,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 17: cedrickmacatangay@gmail.com','127.0.0.1','2026-06-22 13:21:07'),
(260,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 18: ronaldjdetorres@gmail.com','127.0.0.1','2026-06-22 13:21:29'),
(261,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-22 13:23:01'),
(262,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-06-22 13:24:01'),
(263,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-22 13:32:00'),
(264,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-22 13:48:42'),
(265,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGOUT','User logged out.','127.0.0.1','2026-06-22 13:49:21'),
(266,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-22 14:59:35'),
(267,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-23 05:47:26'),
(268,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-23 05:50:04'),
(269,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Inquiries','REJECT','Rejected inquiry ID 8.','127.0.0.1','2026-06-23 05:55:08'),
(270,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP.','127.0.0.1','2026-06-23 13:51:00'),
(271,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','REGISTER_CUSTOMER','Converted existing-customer inquiry ID 12 into customer ID 2 with portal access.','127.0.0.1','2026-06-23 14:04:50'),
(272,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','REGISTER_CUSTOMER','Converted existing-customer inquiry ID 11 into customer ID 3 with portal access.','127.0.0.1','2026-06-23 14:05:26'),
(273,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','REGISTER_CUSTOMER','Converted existing-customer inquiry ID 10 into customer ID 4 with portal access.','127.0.0.1','2026-06-23 14:05:37'),
(274,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','REJECT','Rejected inquiry ID 9.','127.0.0.1','2026-06-23 14:05:50'),
(275,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','DELETE','Deleted inquiry ID 9: Lady Lee Canurl','127.0.0.1','2026-06-23 14:06:02'),
(276,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-06-24 04:09:58'),
(277,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-06-25 02:09:53'),
(278,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','REGISTER_CUSTOMER','Converted existing-customer inquiry ID 13 into customer ID 5 with portal access.','127.0.0.1','2026-06-25 02:10:09'),
(279,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Subscriptions','CREATE','Created subscription for customer ID 4 with plan ID 14','127.0.0.1','2026-06-25 02:11:48'),
(280,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Subscriptions','CREATE','Created subscription for customer ID 5 with plan ID 14','127.0.0.1','2026-06-25 02:12:58'),
(281,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Plans','CREATE','Added plan: Legacy(Legacy Customers Only) (15 Mbps)','127.0.0.1','2026-06-25 02:14:31'),
(282,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-01 03:00:32'),
(283,20,'ladyleecanuel759@gmail.com','ROLE_CUSTOMER','Auth','LOGIN_OTP','User logged in successfully via OTP verification. Remember-me enabled for 30 days.','127.0.0.1','2026-07-01 21:51:31'),
(284,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-09 09:58:33'),
(285,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-09 10:10:07'),
(286,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-09 11:01:01'),
(287,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Personnel','CREATE','Added field personnel: clarrencemacatangay07@gmail.com','127.0.0.1','2026-07-09 11:09:17'),
(288,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Bookings','CREATE','Booked Installation on 2026-07-11 at 10:00:00','127.0.0.1','2026-07-09 11:21:16'),
(289,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','REJECT','Rejected inquiry ID 15.','127.0.0.1','2026-07-09 12:16:30'),
(290,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','REJECT','Rejected inquiry ID 14.','127.0.0.1','2026-07-09 12:16:58'),
(291,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-09 14:24:21'),
(292,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','DELETE','Deleted inquiry ID 15: Mary Grace Fernandez-Abrenica','127.0.0.1','2026-07-09 14:25:56'),
(293,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','DELETE','Deleted inquiry ID 14: Mary Grace Fernandez-Abrenica','127.0.0.1','2026-07-09 14:26:11'),
(294,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-09 22:35:17'),
(295,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-10 03:47:04'),
(296,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-10 03:48:56'),
(297,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-11 00:45:08'),
(298,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-11 05:21:13'),
(299,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-11 11:08:59'),
(300,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-11 11:45:01'),
(301,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','AUTO_CONVERT','Auto-converted inquiry ID 16 after installation was marked done.','127.0.0.1','2026-07-11 11:46:04'),
(302,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Bookings','COMPLETE','Completed booking ID 1','127.0.0.1','2026-07-11 11:46:04'),
(303,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Personnel','UPDATE','Updated field personnel ID 1','127.0.0.1','2026-07-11 11:51:12'),
(304,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Personnel','CREATE','Added field personnel: Jerico Lara','127.0.0.1','2026-07-11 11:51:51'),
(305,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Personnel','CREATE','Added field personnel: Carmelito C. Macatangay','127.0.0.1','2026-07-11 11:52:53'),
(306,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-15 06:25:17'),
(307,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-07-17 01:13:41'),
(308,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-08-03 01:45:35'),
(309,18,'ronaldjdetorres@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-08-03 01:47:02'),
(310,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','127.0.0.1','2026-08-10 11:01:59'),
(311,1,'admin@isp.com','ROLE_ADMIN','Users','UPDATE','Updated user ID 17: cedrickmacatangay@gmail.com','127.0.0.1','2026-08-10 11:12:37'),
(312,1,'admin@isp.com','ROLE_ADMIN','Reports','VIEW','Visited Reports / Revenue (/reports/revenue)','115.147.46.187','2026-08-10 11:30:51'),
(313,1,'admin@isp.com','ROLE_ADMIN','Activity Logs','VIEW','Visited Activity Logs (/activity-logs)','115.147.46.187','2026-08-10 11:30:53'),
(314,1,'admin@isp.com','ROLE_ADMIN','Invoices','VIEW','Visited Invoices (/invoices)','115.147.46.187','2026-08-10 11:45:11'),
(315,1,'admin@isp.com','ROLE_ADMIN','Bookings','VIEW','Visited Bookings (/bookings)','115.147.46.187','2026-08-10 11:45:24'),
(316,1,'admin@isp.com','ROLE_ADMIN','Payments','VIEW','Visited Payments / Create (/payments/create) [invoice_id=10]','115.147.46.187','2026-08-10 11:45:32'),
(317,1,'admin@isp.com','ROLE_ADMIN','Invoices','VIEW','Visited Invoices / Pdf (/invoices/pdf) [id=10]','115.147.46.187','2026-08-10 11:45:39'),
(318,1,'admin@isp.com','ROLE_ADMIN','Auth','VIEW','Visited Login (/login)','115.147.46.187','2026-08-10 11:47:51'),
(319,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully without OTP. Remember-me enabled for 30 days.','115.147.46.187','2026-08-10 11:47:59'),
(320,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Home','VIEW','Visited Dashboard (/dashboard)','115.147.46.187','2026-08-10 11:47:59'),
(321,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Subscriptions','VIEW','Visited Subscriptions (/subscriptions)','115.147.46.187','2026-08-10 11:48:04'),
(322,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Payments','VIEW','Visited Payments (/payments)','115.147.46.187','2026-08-10 11:48:08'),
(323,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Invoices','VIEW','Visited Invoices (/invoices)','115.147.46.187','2026-08-10 11:48:11'),
(324,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Home','VIEW','Visited Dashboard (/dashboard)','115.147.46.187','2026-08-10 11:48:44'),
(325,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','VIEW','Visited Inquiries (/inquiries)','115.147.46.187','2026-08-10 11:48:54'),
(326,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Payments','VIEW','Visited Payments (/payments)','115.147.46.187','2026-08-10 11:49:00'),
(327,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Invoices','VIEW','Visited Invoices (/invoices)','115.147.46.187','2026-08-10 11:49:02'),
(328,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Personnel','VIEW','Visited Personnel (/personnel)','115.147.46.187','2026-08-10 11:49:38'),
(329,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Public Site','VIEW','Visited Page / Book (/page/book)','115.147.46.187','2026-08-10 11:50:04'),
(330,NULL,'cron@system','SYSTEM','Billing','CRON','Billing cron task=\"overdue\" result={\"generated\":0,\"overdue\":0,\"due_reminders\":0,\"overdue_notices\":0,\"bills_emailed\":0}',NULL,'2026-08-10 11:51:33'),
(331,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Personnel','VIEW','Visited Personnel (/personnel)','115.147.46.187','2026-08-10 11:52:22'),
(332,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Customers','VIEW','Visited Customers (/customers)','115.147.46.187','2026-08-10 11:53:16'),
(333,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Settings','VIEW','Visited Settings (/settings)','115.147.46.187','2026-08-10 11:53:20'),
(334,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Activity Logs','VIEW','Visited Activity Logs (/activity-logs)','115.147.46.187','2026-08-10 11:53:31'),
(335,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Home','VIEW','Visited Dashboard (/dashboard)','115.147.46.187','2026-08-10 11:53:37'),
(336,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Invoices','VIEW','Visited Invoices (/invoices)','115.147.46.187','2026-08-10 11:53:38'),
(337,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','VIEW','Visited Inquiries (/inquiries)','115.147.46.187','2026-08-10 11:54:17'),
(338,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Inquiries','VIEW','Visited Inquiries (/inquiries)','115.147.46.187','2026-08-10 11:57:58'),
(339,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Invoices','VIEW','Visited Invoices (/invoices)','115.147.46.187','2026-08-10 11:58:09'),
(340,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Invoices','VIEW','Visited Invoices (/invoices)','115.147.46.187','2026-08-10 12:09:22'),
(341,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Payments','VIEW','Visited Payments (/payments)','115.147.46.187','2026-08-10 12:09:36'),
(342,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Customers','VIEW','Visited Customers (/customers)','115.147.46.187','2026-08-10 12:09:43'),
(343,NULL,'cron@system','SYSTEM','Billing','CRON','CLI billing cron task=\"all\" result={\"generated\":0,\"overdue\":0,\"due_reminders\":0,\"overdue_notices\":0,\"bills_emailed\":0}',NULL,'2026-08-10 12:14:56'),
(344,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Home','VIEW','Visited Dashboard (/dashboard)','115.147.46.187','2026-08-10 12:16:37'),
(345,17,'cedrickmacatangay@gmail.com','ROLE_ADMIN','Settings','VIEW','Visited Settings (/settings)','115.147.46.187','2026-08-10 12:16:43');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_plan_content`
--

DROP TABLE IF EXISTS `cms_plan_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_plan_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `feature_1` text DEFAULT NULL,
  `feature_2` text DEFAULT NULL,
  `feature_3` text DEFAULT NULL,
  `badge_text` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_plan_content`
--

LOCK TABLES `cms_plan_content` WRITE;
/*!40000 ALTER TABLE `cms_plan_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_plan_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_settings`
--

DROP TABLE IF EXISTS `cms_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hero_title` text DEFAULT NULL,
  `hero_subtitle` text DEFAULT NULL,
  `hero_image` text DEFAULT NULL,
  `about_title` text DEFAULT NULL,
  `about_text` text DEFAULT NULL,
  `about_image` text DEFAULT NULL,
  `services_title` text DEFAULT NULL,
  `services_text` text DEFAULT NULL,
  `plans_title` text DEFAULT NULL,
  `plans_text` text DEFAULT NULL,
  `cta_title` text DEFAULT NULL,
  `cta_text` text DEFAULT NULL,
  `cta_button_text` varchar(255) DEFAULT NULL,
  `cta_button_link` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `company_email` varchar(255) DEFAULT NULL,
  `company_phone` varchar(50) DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `apply_title` text DEFAULT NULL,
  `apply_subtitle` text DEFAULT NULL,
  `apply_form_title` text DEFAULT NULL,
  `apply_form_text` text DEFAULT NULL,
  `apply_success_message` text DEFAULT NULL,
  `footer_text` text DEFAULT NULL,
  `nav_home_label` varchar(100) DEFAULT NULL,
  `nav_about_label` varchar(100) DEFAULT NULL,
  `nav_plans_label` varchar(100) DEFAULT NULL,
  `nav_contact_label` varchar(100) DEFAULT NULL,
  `nav_apply_label` varchar(100) DEFAULT NULL,
  `primary_color` varchar(20) DEFAULT NULL,
  `secondary_color` varchar(30) DEFAULT NULL,
  `accent_color` varchar(30) DEFAULT NULL,
  `primary_text_color` varchar(20) DEFAULT NULL,
  `background_color` varchar(20) DEFAULT NULL,
  `surface_color` varchar(20) DEFAULT NULL,
  `text_color` varchar(20) DEFAULT NULL,
  `header_background` varchar(30) DEFAULT NULL,
  `section_background` varchar(30) DEFAULT NULL,
  `footer_background` varchar(30) DEFAULT NULL,
  `button_radius` varchar(20) DEFAULT NULL,
  `website_logo` text DEFAULT NULL,
  `website_favicon` text DEFAULT NULL,
  `muted_color` varchar(20) DEFAULT NULL,
  `header_bg` varchar(50) DEFAULT NULL,
  `footer_bg` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_settings`
--

LOCK TABLES `cms_settings` WRITE;
/*!40000 ALTER TABLE `cms_settings` DISABLE KEYS */;
INSERT INTO `cms_settings` VALUES
(1,'Reliable Connectivity Solutions for Residential and Business Users','Delivering Fast, Secure, and Dependable Internet Services.','/uploads/cms/hero_image_20260617_161437_9ebca959.jpg','About FusionLink','FusionLink is a provider of internet connectivity solutions serving residential customers, businesses, and organizations. We are committed to delivering reliable network services, efficient customer support, and seamless account management to ensure a dependable online experience.','/uploads/cms/about_image_20260617_161605_7a24fec6.jpg','Our Services','We deliver internet subscription services, customer account management, billing solutions, payment verification, technical support, and network service operations for residential and business customers.','Featured Plans','Choose from a range of internet packages designed to meet the connectivity requirements of households, professionals, and businesses.','Start Your Connection Today','Submit your application online and our team will assist you throughout the activation process.','Apply for Service','/apply','FusionLink','admin@fusionitsolution.com','09183114656','Calit-Calit, San Juan, Batangas','Apply for FusionLink Internet','Fill out the form below to get connected.','Application Form','Please provide accurate details.','Your application has been submitted successfully!','© FusionLink 2026','Home','About','Plans','Contact','Apply For Service','#111111','#111111','#111111',NULL,NULL,NULL,'#111111','#0f0f10','#111113','#0a0a0a','16','','/uploads/cms/website_favicon_20260617_162506_0f0175ab.png',NULL,NULL,NULL,'2026-03-18 07:42:47','2026-06-17 16:35:56');
/*!40000 ALTER TABLE `cms_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('ACTIVE','DISCONNECTED') DEFAULT 'ACTIVE',
  `referred_by_customer_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES
(1,'Marites Enriquez','maritesenriquez5@gmail.com','09123456789','Camella Calitcalit, San Juan, Batangas','ACTIVE',NULL,'2026-06-18 12:24:44'),
(2,'Lady Lee Canuel','ladyleecanuel759@gmail.com','09385319739',NULL,'ACTIVE',NULL,'2026-06-23 14:04:46'),
(3,'Rechel De Gula Javier','recheljavier30@gmail.com','09150589663',NULL,'ACTIVE',NULL,'2026-06-23 14:05:22'),
(4,'Anavella Rosales','anavellarosales1@gmail.com','09913359929',NULL,'ACTIVE',NULL,'2026-06-23 14:05:33'),
(5,'Loradel L. Lamac','lamacloradel7@gmail.com','09476935701',NULL,'ACTIVE',NULL,'2026-06-25 02:10:05'),
(6,'Mary Grace Fernandez-Abrenica','aakaishi@icloud.com','09271387385','Block 8 Lot 25 Camella Homes Calit-Calit San Juan Batangas','ACTIVE',NULL,'2026-07-11 11:46:00');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `field_personnel`
--

DROP TABLE IF EXISTS `field_personnel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `field_personnel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `work_start_time` time NOT NULL DEFAULT '08:00:00',
  `work_end_time` time NOT NULL DEFAULT '17:00:00',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `field_personnel`
--

LOCK TABLES `field_personnel` WRITE;
/*!40000 ALTER TABLE `field_personnel` DISABLE KEYS */;
INSERT INTO `field_personnel` VALUES
(1,'Clarrence C. Macatangay','6396487998',1,'09:00:00','17:00:00','2026-07-09 11:09:17'),
(2,'Jerico Lara','09515407828',1,'09:00:00','17:00:00','2026-07-11 11:51:51'),
(3,'Carmelito C. Macatangay','09692944551',1,'09:00:00','17:00:00','2026-07-11 11:52:53');
/*!40000 ALTER TABLE `field_personnel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `field_personnel_services`
--

DROP TABLE IF EXISTS `field_personnel_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `field_personnel_services` (
  `personnel_id` int(10) unsigned NOT NULL,
  `service_type_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`personnel_id`,`service_type_id`),
  KEY `idx_service_type` (`service_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `field_personnel_services`
--

LOCK TABLES `field_personnel_services` WRITE;
/*!40000 ALTER TABLE `field_personnel_services` DISABLE KEYS */;
INSERT INTO `field_personnel_services` VALUES
(1,1),
(1,2),
(1,3),
(1,4),
(2,1),
(2,2),
(2,3),
(2,4),
(3,1),
(3,2),
(3,3),
(3,4);
/*!40000 ALTER TABLE `field_personnel_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `field_service_types`
--

DROP TABLE IF EXISTS `field_service_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `field_service_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `duration_minutes` int(10) unsigned NOT NULL DEFAULT 60,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `field_service_types`
--

LOCK TABLES `field_service_types` WRITE;
/*!40000 ALTER TABLE `field_service_types` DISABLE KEYS */;
INSERT INTO `field_service_types` VALUES
(1,'Installation',60,1,'2026-06-23 03:01:29'),
(2,'Repair / Troubleshooting',90,1,'2026-06-23 03:01:29'),
(3,'Site Survey',60,1,'2026-06-23 03:01:29'),
(4,'Ocular',60,1,'2026-07-09 11:01:22');
/*!40000 ALTER TABLE `field_service_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `referral_credit_applied` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `billing_period_start` date DEFAULT NULL,
  `billing_period_end` date DEFAULT NULL,
  `is_prorated` tinyint(1) NOT NULL DEFAULT 0,
  `coverage_days` int(10) unsigned DEFAULT NULL,
  `status` enum('DRAFT','ISSUED','PAID','OVERDUE') NOT NULL DEFAULT 'DRAFT',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES
(1,1,1500.00,0.00,'2026-06-30',NULL,NULL,0,NULL,'OVERDUE','2026-06-22 05:12:57'),
(2,4,1500.00,0.00,'2026-06-30',NULL,NULL,0,NULL,'OVERDUE','2026-06-25 02:12:02'),
(3,1,1500.00,0.00,'2026-07-30',NULL,NULL,0,NULL,'OVERDUE','2026-07-11 05:21:29'),
(4,4,1500.00,0.00,'2026-07-30',NULL,NULL,0,NULL,'OVERDUE','2026-07-11 05:21:29'),
(5,5,1500.00,0.00,'2026-07-30',NULL,NULL,0,NULL,'OVERDUE','2026-07-11 05:21:29'),
(6,6,1016.13,0.00,'2026-07-30',NULL,NULL,0,NULL,'OVERDUE','2026-07-11 11:46:00'),
(7,1,1500.00,0.00,'2026-08-30',NULL,NULL,0,NULL,'ISSUED','2026-08-03 01:46:18'),
(8,4,1500.00,0.00,'2026-08-30',NULL,NULL,0,NULL,'ISSUED','2026-08-03 01:46:18'),
(9,5,1500.00,0.00,'2026-08-30',NULL,NULL,0,NULL,'ISSUED','2026-08-03 01:46:18'),
(10,6,1500.00,0.00,'2026-08-30',NULL,NULL,0,NULL,'ISSUED','2026-08-03 01:46:18');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_otps`
--

DROP TABLE IF EXISTS `login_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_otps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_otps`
--

LOCK TABLES `login_otps` WRITE;
/*!40000 ALTER TABLE `login_otps` DISABLE KEYS */;
INSERT INTO `login_otps` VALUES
(6,11,'22-05583@g.batstate-u.edu.ph','916910','2026-03-12 11:11:18',1,'2026-03-12 11:06:18'),
(12,13,'lacierasjomar17@gmail.com','128760','2026-04-02 01:47:58',1,'2026-04-02 01:42:58'),
(14,14,'danica_mae_canosa@bec.edu.ph','739211','2026-04-03 01:15:14',1,'2026-04-03 01:10:14'),
(18,16,'ronaldjdetorres@gmail.com','557631','2026-04-14 03:25:15',1,'2026-04-14 03:20:15'),
(19,15,'fusionlinkcomp.1@gmail.com','754518','2026-04-14 03:39:41',1,'2026-04-14 03:34:41'),
(20,17,'cedrickmacatangay@gmail.com','549768','2026-06-17 10:58:26',0,'2026-06-17 10:53:26'),
(21,19,'maritesenriquez5@gmail.com','968087','2026-06-18 12:32:10',1,'2026-06-18 12:27:10'),
(22,20,'ladyleecanuel759@gmail.com','749110','2026-07-01 21:55:09',1,'2026-07-01 21:50:09');
/*!40000 ALTER TABLE `login_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `invoice_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
(1,1,NULL,'RENEWAL_REMINDER','maritesenriquez5@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Marites Enriquez, this is a reminder that your internet subscription for plan \"Residential Connectivity\" will continue for the upcoming month. Monthly fee: ₱1,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-06-22 05:12:57'),
(2,1,NULL,'EXISTING_CUSTOMER_PORTAL','maritesenriquez5@gmail.com','Billing Portal Login Reminder - FUSIONLINK','Hello Marites Enriquez, you already have billing portal access. Login: https://allinjuanservices.com/fusionlink/login','SENT','2026-06-22 15:09:53'),
(3,1,NULL,'EXISTING_CUSTOMER_PORTAL','maritesenriquez5@gmail.com','Billing Portal Login Reminder - FUSIONLINK','Hello Marites Enriquez, you already have billing portal access. Login: https://allinjuanservices.com/fusionlink/login','SENT','2026-06-22 15:09:58'),
(4,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@fusionitsolution.com','Existing Customer Needs Review - FUSIONLINK','Florenda Gamo de Torres submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 05:49:06'),
(5,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@isp.com','Existing Customer Needs Review - FUSIONLINK','Florenda Gamo de Torres submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 05:49:10'),
(6,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','fusionlinkcomp.1@gmail.com','Existing Customer Needs Review - FUSIONLINK','Florenda Gamo de Torres submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 05:49:14'),
(7,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','cedrickmacatangay@gmail.com','Existing Customer Needs Review - FUSIONLINK','Florenda Gamo de Torres submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 05:49:18'),
(8,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','ronaldjdetorres@gmail.com','Existing Customer Needs Review - FUSIONLINK','Florenda Gamo de Torres submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 05:49:22'),
(9,0,NULL,'APPLICATION_CONFIRMATION','florendagamo@gmail.com','We Received Your Details - FUSIONLINK','Hello Florenda Gamo de Torres, we received your billing portal setup request and will verify your account soon.','SENT','2026-06-23 05:49:25'),
(10,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@fusionitsolution.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canurl submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:35:15'),
(11,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@isp.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canurl submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:35:19'),
(12,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','fusionlinkcomp.1@gmail.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canurl submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:35:22'),
(13,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','cedrickmacatangay@gmail.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canurl submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:35:26'),
(14,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','ronaldjdetorres@gmail.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canurl submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:35:30'),
(15,0,NULL,'APPLICATION_CONFIRMATION','ladyleecanuel759@gmail.com','We Received Your Details - FUSIONLINK','Hello Lady Lee Canurl, we received your billing portal setup request and will verify your account soon.','SENT','2026-06-23 07:35:34'),
(16,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@fusionitsolution.com','Existing Customer Needs Review - FUSIONLINK','Anavella Rosales submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:56:39'),
(17,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@isp.com','Existing Customer Needs Review - FUSIONLINK','Anavella Rosales submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:56:44'),
(18,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','fusionlinkcomp.1@gmail.com','Existing Customer Needs Review - FUSIONLINK','Anavella Rosales submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:56:48'),
(19,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','cedrickmacatangay@gmail.com','Existing Customer Needs Review - FUSIONLINK','Anavella Rosales submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:56:51'),
(20,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','ronaldjdetorres@gmail.com','Existing Customer Needs Review - FUSIONLINK','Anavella Rosales submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 07:56:55'),
(21,0,NULL,'APPLICATION_CONFIRMATION','anavellarosales1@gmail.com','We Received Your Details - FUSIONLINK','Hello Anavella Rosales, we received your billing portal setup request and will verify your account soon.','SENT','2026-06-23 07:56:58'),
(22,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@fusionitsolution.com','Existing Customer Needs Review - FUSIONLINK','Rechel De Gula Javier submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:25:07'),
(23,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@isp.com','Existing Customer Needs Review - FUSIONLINK','Rechel De Gula Javier submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:25:12'),
(24,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','fusionlinkcomp.1@gmail.com','Existing Customer Needs Review - FUSIONLINK','Rechel De Gula Javier submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:25:16'),
(25,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','cedrickmacatangay@gmail.com','Existing Customer Needs Review - FUSIONLINK','Rechel De Gula Javier submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:25:19'),
(26,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','ronaldjdetorres@gmail.com','Existing Customer Needs Review - FUSIONLINK','Rechel De Gula Javier submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:25:23'),
(27,0,NULL,'APPLICATION_CONFIRMATION','recheljavier30@gmail.com','We Received Your Details - FUSIONLINK','Hello Rechel De Gula Javier, we received your billing portal setup request and will verify your account soon.','SENT','2026-06-23 12:25:26'),
(28,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@fusionitsolution.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canuel submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:33:05'),
(29,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@isp.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canuel submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:33:10'),
(30,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','fusionlinkcomp.1@gmail.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canuel submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:33:14'),
(31,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','cedrickmacatangay@gmail.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canuel submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:33:17'),
(32,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','ronaldjdetorres@gmail.com','Existing Customer Needs Review - FUSIONLINK','Lady Lee Canuel submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-23 12:33:21'),
(33,0,NULL,'APPLICATION_CONFIRMATION','ladyleecanuel759@gmail.com','We Received Your Details - FUSIONLINK','Hello Lady Lee Canuel, we received your billing portal setup request and will verify your account soon.','SENT','2026-06-23 12:33:25'),
(34,2,NULL,'PORTAL_CREDENTIALS','ladyleecanuel759@gmail.com','Your Billing Portal Login - FUSIONLINK','Hello Lady Lee Canuel, your billing portal login is ready. URL: https://allinjuanservices.com/fusionlink/login | Email: ladyleecanuel759@gmail.com | Password: WEAytkD7kb','SENT','2026-06-23 14:04:46'),
(35,3,NULL,'PORTAL_CREDENTIALS','recheljavier30@gmail.com','Your Billing Portal Login - FUSIONLINK','Hello Rechel De Gula Javier, your billing portal login is ready. URL: https://allinjuanservices.com/fusionlink/login | Email: recheljavier30@gmail.com | Password: fW2CMJXFvF','SENT','2026-06-23 14:05:22'),
(36,4,NULL,'PORTAL_CREDENTIALS','anavellarosales1@gmail.com','Your Billing Portal Login - FUSIONLINK','Hello Anavella Rosales, your billing portal login is ready. URL: https://allinjuanservices.com/fusionlink/login | Email: anavellarosales1@gmail.com | Password: ibF8JvkdVD','SENT','2026-06-23 14:05:33'),
(37,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@fusionitsolution.com','Existing Customer Needs Review - FUSIONLINK','Loradel L. Lamac submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-24 13:15:59'),
(38,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','admin@isp.com','Existing Customer Needs Review - FUSIONLINK','Loradel L. Lamac submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-24 13:16:03'),
(39,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','fusionlinkcomp.1@gmail.com','Existing Customer Needs Review - FUSIONLINK','Loradel L. Lamac submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-24 13:16:07'),
(40,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','cedrickmacatangay@gmail.com','Existing Customer Needs Review - FUSIONLINK','Loradel L. Lamac submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-24 13:16:11'),
(41,0,NULL,'EXISTING_CUSTOMER_REGISTRATION','ronaldjdetorres@gmail.com','Existing Customer Needs Review - FUSIONLINK','Loradel L. Lamac submitted the existing customer form but no customer record matched their phone.','SENT','2026-06-24 13:16:15'),
(42,0,NULL,'APPLICATION_CONFIRMATION','lamacloradel7@gmail.com','We Received Your Details - FUSIONLINK','Hello Loradel L. Lamac, we received your billing portal setup request and will verify your account soon.','SENT','2026-06-24 13:16:19'),
(43,5,NULL,'PORTAL_CREDENTIALS','lamacloradel7@gmail.com','Your Billing Portal Login - FUSIONLINK','Hello Loradel L. Lamac, your billing portal login is ready. URL: https://allinjuanservices.com/fusionlink/login | Email: lamacloradel7@gmail.com | Password: WFqH7M3rws','SENT','2026-06-25 02:10:05'),
(44,4,NULL,'RENEWAL_REMINDER','anavellarosales1@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Anavella Rosales, this is a reminder that your internet subscription for plan \"Residential Connectivity\" will continue for the upcoming month. Monthly fee: ₱1,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-06-25 02:12:02'),
(45,0,NULL,'APPLICATION_RECEIVED','admin@fusionitsolution.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:07:41'),
(46,0,NULL,'APPLICATION_RECEIVED','admin@isp.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:07:45'),
(47,0,NULL,'APPLICATION_RECEIVED','fusionlinkcomp.1@gmail.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:07:49'),
(48,0,NULL,'APPLICATION_RECEIVED','cedrickmacatangay@gmail.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:07:52'),
(49,0,NULL,'APPLICATION_RECEIVED','ronaldjdetorres@gmail.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:07:56'),
(50,0,NULL,'APPLICATION_CONFIRMATION','aakaishi@icloud.com','Application Received - FUSIONLINK','Hello Mary Grace Fernandez-Abrenica, we received your service application for Residential Connectivity - 25 MBPS - ₱1,500.00. Our team will review it and contact you soon.','SENT','2026-07-09 10:07:59'),
(51,0,NULL,'APPLICATION_RECEIVED','admin@fusionitsolution.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:08:03'),
(52,0,NULL,'APPLICATION_RECEIVED','admin@isp.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:08:07'),
(53,0,NULL,'APPLICATION_RECEIVED','fusionlinkcomp.1@gmail.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:08:10'),
(54,0,NULL,'APPLICATION_RECEIVED','cedrickmacatangay@gmail.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:08:13'),
(55,0,NULL,'APPLICATION_RECEIVED','ronaldjdetorres@gmail.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:08:18'),
(56,0,NULL,'APPLICATION_CONFIRMATION','aakaishi@icloud.com','Application Received - FUSIONLINK','Hello Mary Grace Fernandez-Abrenica, we received your service application for Residential Connectivity - 25 MBPS - ₱1,500.00. Our team will review it and contact you soon.','SENT','2026-07-09 10:08:21'),
(57,0,NULL,'APPLICATION_RECEIVED','admin@fusionitsolution.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:09:51'),
(58,0,NULL,'APPLICATION_RECEIVED','admin@isp.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:09:55'),
(59,0,NULL,'APPLICATION_RECEIVED','fusionlinkcomp.1@gmail.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:09:58'),
(60,0,NULL,'APPLICATION_RECEIVED','cedrickmacatangay@gmail.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:10:01'),
(61,0,NULL,'APPLICATION_RECEIVED','ronaldjdetorres@gmail.com','New Service Application - FUSIONLINK','Mary Grace Fernandez-Abrenica submitted a new service application for Residential Connectivity - 25 MBPS - ₱1,500.00.','SENT','2026-07-09 10:10:04'),
(62,0,NULL,'APPLICATION_CONFIRMATION','aakaishi@icloud.com','Application Received - FUSIONLINK','Hello Mary Grace Fernandez-Abrenica, we received your service application for Residential Connectivity - 25 MBPS - ₱1,500.00. Our team will review it and contact you soon.','SENT','2026-07-09 10:10:08'),
(63,0,NULL,'SERVICE_BOOKING','admin@fusionitsolution.com','Application Installation visit Scheduled - FUSIONLINK','Mary Grace Fernandez-Abrenica has a scheduled installation visit on July 11, 2026 at 10:00 AM.','SENT','2026-07-09 11:20:45'),
(64,0,NULL,'SERVICE_BOOKING','admin@isp.com','Application Installation visit Scheduled - FUSIONLINK','Mary Grace Fernandez-Abrenica has a scheduled installation visit on July 11, 2026 at 10:00 AM.','SENT','2026-07-09 11:20:58'),
(65,0,NULL,'SERVICE_BOOKING','fusionlinkcomp.1@gmail.com','Application Installation visit Scheduled - FUSIONLINK','Mary Grace Fernandez-Abrenica has a scheduled installation visit on July 11, 2026 at 10:00 AM.','SENT','2026-07-09 11:21:01'),
(66,0,NULL,'SERVICE_BOOKING','cedrickmacatangay@gmail.com','Application Installation visit Scheduled - FUSIONLINK','Mary Grace Fernandez-Abrenica has a scheduled installation visit on July 11, 2026 at 10:00 AM.','SENT','2026-07-09 11:21:05'),
(67,0,NULL,'SERVICE_BOOKING','ronaldjdetorres@gmail.com','Application Installation visit Scheduled - FUSIONLINK','Mary Grace Fernandez-Abrenica has a scheduled installation visit on July 11, 2026 at 10:00 AM.','SENT','2026-07-09 11:21:09'),
(68,0,NULL,'APPLICATION_CONFIRMATION','aakaishi@icloud.com','Installation visit Scheduled - FUSIONLINK','Hello Mary Grace Fernandez-Abrenica, your installation visit is scheduled on July 11, 2026 at 10:00 AM. Assigned technician: clarrencemacatangay07@gmail.com.','SENT','2026-07-09 11:21:12'),
(69,4,NULL,'RENEWAL_REMINDER','anavellarosales1@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Anavella Rosales, this is a reminder that your internet subscription for plan \"Residential Connectivity\" will continue for the upcoming month. Monthly fee: ₱1,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-07-11 05:21:29'),
(70,5,NULL,'RENEWAL_REMINDER','lamacloradel7@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Loradel L. Lamac, this is a reminder that your internet subscription for plan \"Residential Connectivity\" will continue for the upcoming month. Monthly fee: ₱1,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-07-11 05:21:33'),
(71,1,NULL,'RENEWAL_REMINDER','maritesenriquez5@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Marites Enriquez, this is a reminder that your internet subscription for plan \"Residential Connectivity\" will continue for the upcoming month. Monthly fee: ₱1,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-07-11 05:21:36'),
(72,1,1,'OVERDUE_REMINDER','maritesenriquez5@gmail.com','Overdue Invoice Reminder - FUSIONLINK','Hello Marites Enriquez, your invoice #1 is now overdue. Amount due: ₱1,500.00. Please settle your payment as soon as possible.','SENT','2026-07-11 05:21:39'),
(73,4,2,'OVERDUE_REMINDER','anavellarosales1@gmail.com','Overdue Invoice Reminder - FUSIONLINK','Hello Anavella Rosales, your invoice #2 is now overdue. Amount due: ₱1,500.00. Please settle your payment as soon as possible.','SENT','2026-07-11 05:21:43'),
(74,0,NULL,'APPLICATION_CONFIRMATION','aakaishi@icloud.com','Installation Completed - FUSIONLINK','Hello Mary Grace Fernandez-Abrenica, your installation has been completed. Your account setup will follow shortly.','SENT','2026-07-11 11:45:57'),
(75,6,6,'APPLICATION_APPROVED','aakaishi@icloud.com','Application Approved - FUSIONLINK','Hello Mary Grace Fernandez-Abrenica, your internet service application has been approved. Portal login — URL: https://allinjuanservices.com/fusionlink/login | Email: aakaishi@icloud.com | Password: 4qkAAPGe3F','SENT','2026-07-11 11:46:00'),
(76,4,NULL,'RENEWAL_REMINDER','anavellarosales1@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Anavella Rosales, this is a reminder that your internet subscription for plan \"Residential Connectivity\" will continue for the upcoming month. Monthly fee: ₱1,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-08-03 01:46:18'),
(77,5,NULL,'RENEWAL_REMINDER','lamacloradel7@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Loradel L. Lamac, this is a reminder that your internet subscription for plan \"Residential Connectivity\" will continue for the upcoming month. Monthly fee: ₱1,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-08-03 01:46:21'),
(78,1,NULL,'RENEWAL_REMINDER','maritesenriquez5@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Marites Enriquez, this is a reminder that your internet subscription for plan \"Residential Connectivity\" will continue for the upcoming month. Monthly fee: ₱1,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-08-03 01:46:25'),
(79,6,NULL,'RENEWAL_REMINDER','aakaishi@icloud.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Mary Grace Fernandez-Abrenica, this is a reminder that your internet subscription for plan \"Residential Connectivity\" will continue for the upcoming month. Monthly fee: ₱1,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-08-03 01:46:29'),
(80,1,3,'OVERDUE_REMINDER','maritesenriquez5@gmail.com','Overdue Invoice Reminder - FUSIONLINK','Hello Marites Enriquez, your invoice #3 is now overdue. Amount due: ₱1,500.00. Please settle your payment as soon as possible.','SENT','2026-08-03 01:46:33'),
(81,4,4,'OVERDUE_REMINDER','anavellarosales1@gmail.com','Overdue Invoice Reminder - FUSIONLINK','Hello Anavella Rosales, your invoice #4 is now overdue. Amount due: ₱1,500.00. Please settle your payment as soon as possible.','SENT','2026-08-03 01:46:38'),
(82,5,5,'OVERDUE_REMINDER','lamacloradel7@gmail.com','Overdue Invoice Reminder - FUSIONLINK','Hello Loradel L. Lamac, your invoice #5 is now overdue. Amount due: ₱1,500.00. Please settle your payment as soon as possible.','SENT','2026-08-03 01:46:42'),
(83,6,6,'OVERDUE_REMINDER','aakaishi@icloud.com','Overdue Invoice Reminder - FUSIONLINK','Hello Mary Grace Fernandez-Abrenica, your invoice #6 is now overdue. Amount due: ₱1,016.13. Please settle your payment as soon as possible.','SENT','2026-08-03 01:46:45');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_logins`
--

DROP TABLE IF EXISTS `otp_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_logins`
--

LOCK TABLES `otp_logins` WRITE;
/*!40000 ALTER TABLE `otp_logins` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_logins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('bank','gcash') NOT NULL,
  `account_name` varchar(190) NOT NULL DEFAULT '',
  `account_number` varchar(190) NOT NULL DEFAULT '',
  `bank_branch` varchar(190) DEFAULT NULL,
  `qr_path` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payment_methods_sort` (`sort_order`),
  KEY `idx_payment_methods_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES
(2,'gcash','Cedrick C. Macatangay','09451464806',NULL,NULL,0,1,'2026-06-17 19:01:21','2026-06-17 19:02:29');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `method` varchar(50) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') DEFAULT 'PENDING',
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `speed` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_legacy` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES
(14,'Residential Connectivity','25 MBPS',1500.00,0,'2026-06-17 16:30:29'),
(15,'Remote Work Connectivity','50 MBPS',2500.00,0,'2026-06-17 16:31:07'),
(16,'Business Connectivity','60 MBPS',3000.00,0,'2026-06-17 16:32:01'),
(17,'Legacy(Legacy Customers Only)','15 Mbps',1000.00,1,'2026-06-25 02:14:31');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `referral_rewards`
--

DROP TABLE IF EXISTS `referral_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_rewards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `referrer_customer_id` int(10) unsigned NOT NULL,
  `referred_customer_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 500.00,
  `status` enum('PENDING','APPLIED') NOT NULL DEFAULT 'PENDING',
  `applied_invoice_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `applied_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_referred_customer` (`referred_customer_id`),
  KEY `idx_referrer_status` (`referrer_customer_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referral_rewards`
--

LOCK TABLES `referral_rewards` WRITE;
/*!40000 ALTER TABLE `referral_rewards` DISABLE KEYS */;
/*!40000 ALTER TABLE `referral_rewards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `remember_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remember_tokens`
--

LOCK TABLES `remember_tokens` WRITE;
/*!40000 ALTER TABLE `remember_tokens` DISABLE KEYS */;
INSERT INTO `remember_tokens` VALUES
(16,17,'4e01813488e9ec64221efc7cfcb65dde1efb90f9d06054c48e532d33d2f74561','2026-08-14 06:25:17','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-07-15 14:25:17','2026-07-15 06:25:17'),
(17,17,'e54b7d974eb9b357b6eca26dc75179044245be04302206fa1b9fa81cc2a67e39','2026-08-16 01:13:41','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36','2026-07-17 09:13:41','2026-07-17 01:13:41'),
(18,18,'44010e0ee4fe243fb2c064c56cc105b70596f35ce952973af19b0b434d4805f5','2026-09-02 01:45:35','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-03 09:45:35','2026-08-03 01:45:35'),
(19,18,'70811afebd82ed612bff43e91a70293bcebfc91e297efa3b949f86ef7b20e44d','2026-09-02 01:47:02','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-03 09:47:02','2026-08-03 01:47:02'),
(20,1,'457f21113c4c6ee71aaeb9690012ea9d196bed79bf976a63ce0817007f5151fc','2026-09-09 11:01:59','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-10 19:01:59','2026-08-10 11:01:59'),
(21,17,'82d0d281a21c3b602f4084265173a3865578e5d815f96e1ecb186222b44d1215','2026-09-09 11:47:59','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-10 19:47:59','2026-08-10 11:47:59');
/*!40000 ALTER TABLE `remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_bookings`
--

DROP TABLE IF EXISTS `service_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `service_type_id` int(10) unsigned NOT NULL,
  `personnel_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `service_request_id` int(10) unsigned DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('BOOKED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'BOOKED',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_personnel_date` (`personnel_id`,`booking_date`,`status`),
  KEY `idx_booking_date` (`booking_date`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_bookings`
--

LOCK TABLES `service_bookings` WRITE;
/*!40000 ALTER TABLE `service_bookings` DISABLE KEYS */;
INSERT INTO `service_bookings` VALUES
(1,1,1,NULL,16,'Mary Grace Fernandez-Abrenica','09271387385','aakaishi@icloud.com','Block 8 Lot 25 Camella Homes Calit-Calit San Juan Batangas','2026-07-11','10:00:00','11:00:00','COMPLETED',NULL,'2026-07-09 11:20:45');
/*!40000 ALTER TABLE `service_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_requests`
--

DROP TABLE IF EXISTS `service_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `plan` varchar(100) DEFAULT NULL,
  `referred_by_phone` varchar(50) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `converted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_requests`
--

LOCK TABLES `service_requests` WRITE;
/*!40000 ALTER TABLE `service_requests` DISABLE KEYS */;
INSERT INTO `service_requests` VALUES
(1,'danica','danica_mae_canosa@bec.edu.ph','09122222222','mabini st','Fiber 6 - 65 Mbps - ₱8,000.00',NULL,'CONVERTED',1,'2026-03-13 00:17:47','2026-03-12 15:53:12'),
(2,'joshua','joshua@gmail.com','09112321343','mabini st','Fiber Pro - 100 Mbps - ₱2,500.00',NULL,'REJECTED',0,NULL,'2026-03-13 01:03:31'),
(3,'joshua','joshua@gmail.com','09123456789','mabiniq ssad','Fiber Ultra - 300 Mbps - ₱4,500.00',NULL,'CONVERTED',1,'2026-03-18 14:55:57','2026-03-18 06:55:50'),
(4,'joshua','joshua@gmail.com','09123456789','mabiniq ssad','Fiber Ultra - 300 Mbps - ₱4,500.00',NULL,'REJECTED',0,NULL,'2026-03-18 07:11:16'),
(5,'adawdawd','adwawd@gmail.com','09123123123','Adhlawdawd','Fiber 6 - 65 Mbps - ₱8,000.00',NULL,'CONVERTED',1,'2026-04-14 11:17:03','2026-04-14 02:46:04'),
(6,'adawd','adwawd@gmail.com','09512857885','awdaw','Fiber Pro - 100 Mbps - ₱2,500.00',NULL,'REJECTED',0,NULL,'2026-04-14 03:16:35'),
(7,'this is a test application','test@gmail.com','09183114656','this is a test application','Residential Connectivity - 25 MBPS - ₱1,500.00',NULL,'REJECTED',0,NULL,'2026-06-17 17:53:50'),
(8,'Florenda Gamo de Torres','florendagamo@gmail.com','09165427230','Calitcalit','Existing Customer - Portal Setup',NULL,'REJECTED',0,NULL,'2026-06-23 05:49:06'),
(10,'Anavella Rosales','anavellarosales1@gmail.com','09913359929',NULL,'Existing Customer - Portal Setup',NULL,'CONVERTED',1,'2026-06-23 22:05:37','2026-06-23 07:56:39'),
(11,'Rechel De Gula Javier','recheljavier30@gmail.com','09150589663',NULL,'Existing Customer - Portal Setup',NULL,'CONVERTED',1,'2026-06-23 22:05:26','2026-06-23 12:25:07'),
(12,'Lady Lee Canuel','ladyleecanuel759@gmail.com','09385319739',NULL,'Existing Customer - Portal Setup',NULL,'CONVERTED',1,'2026-06-23 22:04:50','2026-06-23 12:33:05'),
(13,'Loradel L. Lamac','lamacloradel7@gmail.com','09476935701',NULL,'Existing Customer - Portal Setup',NULL,'CONVERTED',1,'2026-06-25 10:10:09','2026-06-24 13:15:59'),
(16,'Mary Grace Fernandez-Abrenica','aakaishi@icloud.com','09271387385','Block 8 Lot 25 Camella Homes Calit-Calit San Juan Batangas','Residential Connectivity - 25 MBPS - ₱1,500.00',NULL,'CONVERTED',1,'2026-07-11 19:46:00','2026-07-09 10:09:51');
/*!40000 ALTER TABLE `service_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) NOT NULL DEFAULT 'ISP-BILLING-LITE',
  `business_address` text DEFAULT NULL,
  `bank_account` varchar(255) DEFAULT NULL,
  `gcash_account` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `smtp_host` varchar(190) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `smtp_username` varchar(190) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `smtp_encryption` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `billing_due_day` int(11) NOT NULL DEFAULT 1,
  `referral_reward_amount` decimal(10,2) NOT NULL DEFAULT 500.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'FUSIONLINK','Calit-Calit, San Juan, Batangas','12312312','12412412412','09183114656','admin@fusionitsolution.com','smtp.gmail.com',587,'cedrickmacatangay@gmail.com','ahdf zxyo mmvr ybde','tls','2026-03-11 00:26:40','2026-08-10 11:51:04',8,500.00);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `plan_id` int(10) unsigned NOT NULL,
  `start_date` date DEFAULT NULL,
  `status` enum('ACTIVE','SUSPENDED','CANCELLED') DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES
(1,1,14,'2026-06-01','ACTIVE','2026-06-18 12:26:13'),
(2,4,14,'2026-06-01','ACTIVE','2026-06-25 02:11:48'),
(3,5,14,'2026-06-01','ACTIVE','2026-06-25 02:12:58'),
(4,6,14,'2026-07-11','ACTIVE','2026-07-11 11:46:00');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('ROLE_ADMIN','ROLE_STAFF','ROLE_CUSTOMER') NOT NULL DEFAULT 'ROLE_CUSTOMER',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uniq_users_customer_id` (`customer_id`),
  CONSTRAINT `fk_users_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,NULL,'admin@isp.com','$2y$10$mPz2hgluRL8fVenmwWYPLORszC5GGd.mNnOqxbjApcBl7J2GXpM0.','ROLE_ADMIN','2026-03-05 07:33:13'),
(15,NULL,'fusionlinkcomp.1@gmail.com','$2y$10$yzbMXr3UElBkrgDZrYjRKOXuYmO1EuNLMVYF184rG9WLZx5YbbOwq','ROLE_ADMIN','2026-04-14 02:49:28'),
(17,NULL,'cedrickmacatangay@gmail.com','$2y$10$6D1at25mEGIzUf6TGVPYVuuhzrxd1bZ3OX5K1c1.xwyLgmaz2s8ty','ROLE_ADMIN','2026-06-17 07:07:30'),
(18,NULL,'ronaldjdetorres@gmail.com','$2y$10$oMUna55gKQYv4mOFcWwTfO8wpTuhuI3xN4JLFzuLK/7cpa0nT9YDq','ROLE_ADMIN','2026-06-18 12:19:54'),
(19,1,'maritesenriquez5@gmail.com','$2y$10$dmI5sVkJ68WDP3dwf8/z/.EEB9jeMwukNJn5Af0laKBrgRDRkh1OC','ROLE_CUSTOMER','2026-06-18 12:26:42'),
(20,2,'ladyleecanuel759@gmail.com','$2y$10$uThTNiRTVOPTdBwEQGif5OSOmOA/.ndnjKCh5epF/vpLKT0qbOlTS','ROLE_CUSTOMER','2026-06-23 14:04:46'),
(21,3,'recheljavier30@gmail.com','$2y$10$W3VEHzaOFVU0YQkVrc9Rae8w5hq0e0KXAb9eWBOD4HMwRuHCTr1sK','ROLE_CUSTOMER','2026-06-23 14:05:22'),
(22,4,'anavellarosales1@gmail.com','$2y$10$UP5f8lL.Jrya3CYY1B6mhedrs2vGXQ4ncXM2/MOpuKd.yAsAbkmzO','ROLE_CUSTOMER','2026-06-23 14:05:33'),
(23,5,'lamacloradel7@gmail.com','$2y$10$rXq6kDu4KImamZavhb9oHuqXl/leCFJiVo9CYXVkEF9LW4lg2RPAW','ROLE_CUSTOMER','2026-06-25 02:10:05'),
(24,6,'aakaishi@icloud.com','$2y$10$XrT0yv/4ARGFYW8bQcMXOODaNOZZDUKCUjkTGbBbp1QfCuW5jivo.','ROLE_CUSTOMER','2026-07-11 11:46:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'isp_billing_lite_db'
--

--
-- Dumping routines for database 'isp_billing_lite_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-10 20:22:42
