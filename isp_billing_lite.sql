/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: isp_billing_lite_db
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
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
(134,1,'admin@isp.com','ROLE_ADMIN','Auth','LOGIN','User logged in successfully with OTP exemption.','127.0.0.1','2026-04-06 00:55:02');
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
(1,'Reliable Internet for Your Home','Fast. Stable. Unlimited.','https://i.pinimg.com/1200x/0f/5b/72/0f5b72bb39a5bd2cde75aa786c69946e.jpg','About FusionLink','FusionLink provides dependable internet solutions with a simpler application experience and connected billing management.','https://www.atlanticcouncil.org/wp-content/uploads/2022/09/AdobeStock_230917104-1024x614.jpeg','Our Services','We provide internet subscription plans, online applications, customer billing, payment verification, and support-ready service operations.','Featured Plans','Choose a plan that matches your home or business connectivity needs.','Ready to get connected?','Submit your application online and let the team assist you with plan activation.','Apply Now','/apply','FusionLink','admin@fusionitsolution.com','09123456789','Your Address Here','Apply for FusionLink Internet','Fill out the form below to get connected.','Application Form','Please provide accurate details.','Your application has been submitted successfully!','© FusionLink 2026',NULL,NULL,NULL,NULL,NULL,'#111111','#111111','#111111',NULL,NULL,NULL,'#eae7e6','#0f0f10','#111113','#0a0a0a','16','','',NULL,NULL,NULL,'2026-03-18 07:42:47','2026-03-31 01:24:44');
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES
(8,'Dewmar','22-05583@g.batstate-u.edu.ph','09196405705','mabini st','ACTIVE','2026-03-06 10:44:49'),
(9,'Danica','yonicschill@gmail.com','09196405705','mabini st','ACTIVE','2026-03-07 10:15:52'),
(12,'Walter','admin@fusionitsolution.com','09196405705','mabini st','ACTIVE','2026-03-10 05:53:34'),
(13,'Jomar','lacierasjomar17@gmail.com','09123123932','mabini st','ACTIVE','2026-03-11 07:18:57'),
(15,'danica','danica_mae_canosa@bec.edu.ph','09122222222','mabini st','ACTIVE','2026-03-12 16:17:47'),
(16,'joshua','joshua@gmail.com','09123456789','mabiniq ssad','ACTIVE','2026-03-18 06:55:57');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
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
  `due_date` date DEFAULT NULL,
  `status` enum('DRAFT','ISSUED','PAID','OVERDUE') NOT NULL DEFAULT 'DRAFT',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES
(27,8,6000.00,'2026-03-17','PAID','2026-03-10 10:11:46'),
(28,9,6050.00,'2026-03-17','PAID','2026-03-10 10:11:46'),
(33,12,6050.00,'2026-03-01','PAID','2026-03-11 02:45:45'),
(34,13,3048.39,'2026-03-07','PAID','2026-03-11 07:19:20'),
(35,15,8000.00,'2026-03-12','OVERDUE','2026-03-12 16:17:47'),
(36,16,2032.26,'2026-03-03','OVERDUE','2026-03-18 06:55:57'),
(37,9,6050.00,'2026-04-03','ISSUED','2026-04-02 01:47:05'),
(38,8,6000.00,'2026-04-03','ISSUED','2026-04-02 01:47:05'),
(39,12,6050.00,'2026-04-03','ISSUED','2026-04-02 01:47:05'),
(40,13,4500.00,'2026-04-03','ISSUED','2026-04-02 01:47:05'),
(41,15,8000.00,'2026-04-03','PAID','2026-04-02 01:47:05'),
(42,16,4500.00,'2026-04-03','ISSUED','2026-04-02 01:47:05');
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_otps`
--

LOCK TABLES `login_otps` WRITE;
/*!40000 ALTER TABLE `login_otps` DISABLE KEYS */;
INSERT INTO `login_otps` VALUES
(6,11,'22-05583@g.batstate-u.edu.ph','916910','2026-03-12 11:11:18',1,'2026-03-12 11:06:18'),
(12,13,'lacierasjomar17@gmail.com','128760','2026-04-02 01:47:58',1,'2026-04-02 01:42:58'),
(14,14,'danica_mae_canosa@bec.edu.ph','739211','2026-04-03 01:15:14',1,'2026-04-03 01:10:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
(29,9,NULL,'RENEWAL_REMINDER','yonicschill@gmail.com','Upcoming Subscription Reminder - ISP-BILLING-LITE','Hello Danica, this is a reminder that your internet subscription for plan \"FIber 3\" will continue for the upcoming month. Monthly fee: ₱6,000.00. Please expect your next billing cycle soon. Thank you for choosing ISP-BILLING-LITE.','SENT','2026-03-10 04:11:27'),
(30,8,NULL,'RENEWAL_REMINDER','22-05583@g.batstate-u.edu.ph','Upcoming Subscription Reminder - ISP-BILLING-LITE','Hello Dewmar, this is a reminder that your internet subscription for plan \"FIber 3\" will continue for the upcoming month. Monthly fee: ₱6,000.00. Please expect your next billing cycle soon. Thank you for choosing ISP-BILLING-LITE.','SENT','2026-03-10 04:11:35'),
(34,12,NULL,'RENEWAL_REMINDER','admin@fusionitsolution.com','Upcoming Subscription Reminder - ISP-BILLING-LITE','Hello Walter, this is a reminder that your internet subscription for plan \"Fiber 5\" will continue for the upcoming month. Monthly fee: ₱6,050.00. Please expect your next billing cycle soon. Thank you for choosing ISP-BILLING-LITE.','SENT','2026-03-10 05:54:46'),
(40,9,28,'PAYMENT_CONFIRMED','yonicschill@gmail.com','Payment Confirmation Notice - ISP Billing Lite','Hello Danica, your payment for Invoice #28 has been successfully verified. Your account is now up to date.','SENT','2026-03-10 12:21:51'),
(41,8,27,'PAYMENT_CONFIRMED','22-05583@g.batstate-u.edu.ph','Payment Confirmation Notice - ISP Billing Lite','Hello Dewmar, your payment for Invoice #27 has been successfully verified. Your account is now up to date.','SENT','2026-03-10 13:06:19'),
(42,12,33,'PAYMENT_CONFIRMED','admin@fusionitsolution.com','Payment Confirmation Notice - ISP Billing Lite','Hello Walter, your payment for Invoice #33 has been successfully verified. Your account is now up to date.','SENT','2026-03-11 02:48:03'),
(43,12,33,'OVERDUE_REMINDER','admin@fusionitsolution.com','Overdue Invoice Reminder - ISP-BILLING-LITE','Hello Walter, your invoice #33 is now overdue. Amount due: ₱6,050.00. Please settle your payment as soon as possible.','SENT','2026-03-11 03:44:41'),
(44,12,33,'PAYMENT_CONFIRMED','admin@fusionitsolution.com','Payment Confirmation Notice - ISP Billing Lite','Hello Walter, your payment for Invoice #33 has been successfully verified. Your account is now up to date.','SENT','2026-03-11 03:47:21'),
(45,13,NULL,'RENEWAL_REMINDER','lacierasjomar17@gmail.com','Upcoming Subscription Reminder - ISP-BILLING-LITE','Hello Jomar, this is a reminder that your internet subscription for plan \"Fiber Ultra\" will continue for the upcoming month. Monthly fee: ₱4,500.00. Please expect your next billing cycle soon. Thank you for choosing ISP-BILLING-LITE.','SENT','2026-03-11 07:19:20'),
(46,13,34,'OVERDUE_REMINDER','lacierasjomar17@gmail.com','Overdue Invoice Reminder - ISP-BILLING-LITE','Hello Jomar, your invoice #34 is now overdue. Amount due: ₱3,048.39. Please settle your payment as soon as possible.','SENT','2026-03-11 07:19:25'),
(47,13,34,'PAYMENT_REJECTED','lacierasjomar17@gmail.com','Payment Verification Failed - ISP Billing Lite','Hello Jomar, unfortunately your payment for Invoice #34 could not be verified. Please check your payment receipt and submit again.','SENT','2026-03-11 08:08:39'),
(48,13,34,'PAYMENT_REJECTED','lacierasjomar17@gmail.com','Payment Verification Failed - ISP Billing Lite','Hello Jomar, unfortunately your payment for Invoice #34 could not be verified. Please check your payment receipt and submit again.','SENT','2026-03-11 08:38:19'),
(49,13,34,'PAYMENT_REJECTED','lacierasjomar17@gmail.com','Payment Verification Failed - ISP Billing Lite','Hello Jomar, unfortunately your payment for Invoice #34 could not be verified. Please check your payment receipt and submit again.','SENT','2026-03-11 08:39:13'),
(50,13,34,'PAYMENT_REJECTED','lacierasjomar17@gmail.com','Payment Verification Failed - ISP Billing Lite','Hello Jomar, unfortunately your payment for Invoice #34 could not be verified. Please check your payment receipt and submit again.','SENT','2026-03-11 08:40:10'),
(51,13,34,'PAYMENT_REJECTED','lacierasjomar17@gmail.com','Payment Verification Failed - ISP Billing Lite','Hello Jomar, unfortunately your payment for Invoice #34 could not be verified. Please check your payment receipt and submit again.','SENT','2026-03-12 09:33:29'),
(52,15,NULL,'RENEWAL_REMINDER','danica_mae_canosa@bec.edu.ph','Upcoming Subscription Reminder - ISP-BILLING-LITE','Hello danica, this is a reminder that your internet subscription for plan \"Fiber 6\" will continue for the upcoming month. Monthly fee: ₱8,000.00. Please expect your next billing cycle soon. Thank you for choosing ISP-BILLING-LITE.','SENT','2026-03-12 16:17:57'),
(53,16,NULL,'RENEWAL_REMINDER','joshua@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello joshua, this is a reminder that your internet subscription for plan \"Fiber Ultra\" will continue for the upcoming month. Monthly fee: ₱4,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-03-18 06:56:03'),
(54,13,34,'PAYMENT_CONFIRMED','lacierasjomar17@gmail.com','Payment Processed','Hello Jomar, your payment for Invoice #34 has been successfully verified. Your account is now up to date.','SENT','2026-03-24 05:08:48'),
(55,9,NULL,'RENEWAL_REMINDER','yonicschill@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Danica, this is a reminder that your internet subscription for plan \"Fiber 6\" will continue for the upcoming month. Monthly fee: ₱8,000.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-04-02 01:47:05'),
(56,15,NULL,'RENEWAL_REMINDER','danica_mae_canosa@bec.edu.ph','Upcoming Subscription Reminder - FUSIONLINK','Hello danica, this is a reminder that your internet subscription for plan \"Fiber 6\" will continue for the upcoming month. Monthly fee: ₱8,000.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-04-02 01:47:10'),
(57,8,NULL,'RENEWAL_REMINDER','22-05583@g.batstate-u.edu.ph','Upcoming Subscription Reminder - FUSIONLINK','Hello Dewmar, this is a reminder that your internet subscription for plan \"FIber 3\" will continue for the upcoming month. Monthly fee: ₱6,000.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-04-02 01:47:15'),
(58,13,NULL,'RENEWAL_REMINDER','lacierasjomar17@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Jomar, this is a reminder that your internet subscription for plan \"Fiber Ultra\" will continue for the upcoming month. Monthly fee: ₱4,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-04-02 01:47:20'),
(59,16,NULL,'RENEWAL_REMINDER','joshua@gmail.com','Upcoming Subscription Reminder - FUSIONLINK','Hello joshua, this is a reminder that your internet subscription for plan \"Fiber Ultra\" will continue for the upcoming month. Monthly fee: ₱4,500.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-04-02 01:47:23'),
(60,12,NULL,'RENEWAL_REMINDER','admin@fusionitsolution.com','Upcoming Subscription Reminder - FUSIONLINK','Hello Walter, this is a reminder that your internet subscription for plan \"Fiber 5\" will continue for the upcoming month. Monthly fee: ₱6,050.00. Please expect your next billing cycle soon. Thank you for choosing FUSIONLINK.','SENT','2026-04-02 01:47:27'),
(61,15,35,'OVERDUE_REMINDER','danica_mae_canosa@bec.edu.ph','Overdue Invoice Reminder - FUSIONLINK','Hello danica, your invoice #35 is now overdue. Amount due: ₱8,000.00. Please settle your payment as soon as possible.','SENT','2026-04-02 01:47:32'),
(62,16,36,'OVERDUE_REMINDER','joshua@gmail.com','Overdue Invoice Reminder - FUSIONLINK','Hello joshua, your invoice #36 is now overdue. Amount due: ₱2,032.26. Please settle your payment as soon as possible.','SENT','2026-04-02 01:47:36'),
(63,15,41,'PAYMENT_CONFIRMED','danica_mae_canosa@bec.edu.ph','Payment Processed','Hello danica, your payment for Invoice #41 has been successfully verified. Your account is now up to date.','SENT','2026-04-03 01:09:39');
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
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES
(37,28,6050.00,'2026-03-10','GCASH','/uploads/receipts/1773145268_69b00cb4e9f06_216af6d6-b113-4222-b9f2-3229acacc58b.jpg','VERIFIED'),
(38,27,6000.00,'2026-03-10','GCASH','/uploads/receipts/1773147959_69b01737c411f_216af6d6-b113-4222-b9f2-3229acacc58b.jpg','VERIFIED'),
(39,33,6050.00,'2026-03-11','GCASH','/uploads/receipts/1773197266_69b0d7d24abfa_216af6d6-b113-4222-b9f2-3229acacc58b.jpg','VERIFIED'),
(40,33,6050.00,'2026-03-11','GCASH','/uploads/receipts/1773200836_69b0e5c4c4317_216af6d6-b113-4222-b9f2-3229acacc58b.jpg','VERIFIED'),
(41,34,3048.39,'2026-03-11','CASH',NULL,'REJECTED'),
(42,34,3048.39,'2026-03-11','GCASH',NULL,'REJECTED'),
(43,34,3048.39,'2026-03-11','CASH',NULL,'REJECTED'),
(44,34,3048.39,'2026-03-11','GCASH',NULL,'REJECTED'),
(45,34,3048.39,'2026-03-12','GCASH',NULL,'REJECTED'),
(46,34,3048.39,'2026-03-24','GCASH','/uploads/receipts/1774328900_69c21c44c1259_img_20230501_145945.webp','VERIFIED'),
(47,41,8000.00,'2026-04-03','GCASH','/uploads/receipts/1775178550_69cf1336eeb4f_img_20230501_145945.webp','VERIFIED');
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES
(1,'Fiber Basic','50 Mbps',1500.00,'2026-03-05 08:32:53'),
(2,'Fiber Pro','100 Mbps',2500.00,'2026-03-05 08:32:53'),
(3,'Fiber Ultra','300 Mbps',4500.00,'2026-03-05 08:32:53'),
(9,'FIber 3','40 MBPS',6000.00,'2026-03-06 08:48:02'),
(11,'Fiber 5','50 Mbps',6050.00,'2026-03-10 02:21:55'),
(12,'Fiber 6','65 Mbps',8000.00,'2026-03-12 03:19:52');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
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
  `status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `converted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_requests`
--

LOCK TABLES `service_requests` WRITE;
/*!40000 ALTER TABLE `service_requests` DISABLE KEYS */;
INSERT INTO `service_requests` VALUES
(1,'danica','danica_mae_canosa@bec.edu.ph','09122222222','mabini st','Fiber 6 - 65 Mbps - ₱8,000.00','CONVERTED',1,'2026-03-13 00:17:47','2026-03-12 15:53:12'),
(2,'joshua','joshua@gmail.com','09112321343','mabini st','Fiber Pro - 100 Mbps - ₱2,500.00','REJECTED',0,NULL,'2026-03-13 01:03:31'),
(3,'joshua','joshua@gmail.com','09123456789','mabiniq ssad','Fiber Ultra - 300 Mbps - ₱4,500.00','CONVERTED',1,'2026-03-18 14:55:57','2026-03-18 06:55:50'),
(4,'joshua','joshua@gmail.com','09123456789','mabiniq ssad','Fiber Ultra - 300 Mbps - ₱4,500.00','PENDING',0,NULL,'2026-03-18 07:11:16');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'FUSIONLINK','Calit-Calit, San Juan, Batangas','12312312','12412412412','09196405705','admin@fusionitsolution.com','smtp.gmail.com',587,'','','tls','2026-03-11 00:26:40','2026-04-02 01:54:27',30);
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES
(15,9,11,'2026-03-10','ACTIVE','2026-03-10 10:11:20'),
(16,8,9,'2026-03-10','ACTIVE','2026-03-10 10:11:33'),
(21,12,11,'2026-03-11','ACTIVE','2026-03-11 02:45:42'),
(22,13,3,'2026-03-11','ACTIVE','2026-03-11 07:19:18'),
(23,15,12,'2026-03-12','ACTIVE','2026-03-12 16:17:47'),
(24,9,12,'2026-03-18','ACTIVE','2026-03-18 05:34:54'),
(25,16,3,'2026-03-18','ACTIVE','2026-03-18 06:55:57');
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,NULL,'admin@isp.com','$2y$10$mPz2hgluRL8fVenmwWYPLORszC5GGd.mNnOqxbjApcBl7J2GXpM0.','ROLE_ADMIN','2026-03-05 07:33:13'),
(4,NULL,'jomar@gmail.com','$2y$10$KVvBcbviq4y//.VTMtRBN.pL3ex8yK1NyuGeh.04Sco2VPNWat68i','ROLE_ADMIN','2026-03-07 11:11:06'),
(6,NULL,'admin@gmail.com','$2y$10$m3CNKSWpdA6HCpyxJHr6r.ncqlFtjAoTp5M4E.bk0t6ZLnKRkrW/W','ROLE_ADMIN','2026-03-07 11:16:45'),
(7,NULL,'fusion@gmail.com','$2y$10$SSM5Kd8Ju5jVRs9bb17qjurDS6RNitiVKlMAQHB4kJ7m/Dp8TuE0m','ROLE_STAFF','2026-03-07 11:35:10'),
(10,9,'yonicschill@gmail.com','$2y$10$E60ouQSteKuPzsEUrODOpu1fXErv6ODGF9eiGu.4WiipIEpZ9ycXK','ROLE_CUSTOMER','2026-03-10 11:51:19'),
(11,8,'22-05583@g.batstate-u.edu.ph','$2y$10$oPXCBZhM3iQNQRe0c89NtumVu/QhuxSh7yPuBd8HqEmXP43mA4Jsa','ROLE_CUSTOMER','2026-03-10 11:51:19'),
(12,12,'admin@fusionitsolution.com','$2y$10$/.U4NxA6eJxpsTITRWv2U.jCu3m5pdRleF.XYqNC.qoqG7yfkvUwq','ROLE_CUSTOMER','2026-03-10 11:51:19'),
(13,13,'lacierasjomar17@gmail.com','$2y$10$cmSxjIQXfkedUIVZ3RzPG.s.y6xAeUt8Qvpfir5QzA1qp0TjavFmK','ROLE_CUSTOMER','2026-03-11 07:21:00'),
(14,15,'danica_mae_canosa@bec.edu.ph','$2y$10$2LMOudMq2EVa8fZ5nF5PeOcQpA38RKnjyuSul6kQGbHUbuBBpAz3S','ROLE_CUSTOMER','2026-04-03 01:08:16');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-10  6:43:16
