-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 20, 2026 at 09:59 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crm_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','inprogress','completed','onhold') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `assigned_to` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `name`, `description`, `status`, `file_path`, `created_by`, `assigned_to`, `created_at`, `updated_at`, `completed_at`, `deleted_at`, `is_deleted`) VALUES
(8, 'speakers not working ', 'Asdadsadczxvcbdfgwefdfjfdgsdfiubjxkfggjioweg[p]sfgikowehioprtgjlkedfuigopry', 'pending', NULL, 2, NULL, '2026-02-19 10:26:33', '2026-02-19 12:46:49', NULL, NULL, 1),
(9, 'speakers working ', 'asdasdas', 'completed', NULL, 3, 4, '2026-02-20 09:28:51', '2026-02-20 09:39:15', NULL, NULL, 0),
(10, 'sdsad', 'sdasdas', 'completed', NULL, 5, 5, '2026-02-20 09:52:19', '2026-02-20 09:53:01', NULL, NULL, 0),
(11, 'resume update', 'i want to update this resume ', 'completed', '1771581219_Resume Aman.pdf', 3, 4, '2026-02-20 09:53:39', '2026-02-20 09:54:03', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(2, 'AMAN', '123@gmail.com', '$2y$10$lkzsSMzkSY5ZqK8oYTDZPOGmVQtNPaBmELnPbtG1uyxz0MS6qheVy', 'admin', '2026-02-19 10:01:44'),
(3, 'user', 'user123@gmail.com', '$2y$10$TAuXJi21znnrJKLoqKiUXOzbUL2xzAAy47us4co4AHvLx9M1Yn1ha', 'user', '2026-02-20 09:09:13'),
(4, 'Staff', 'staff123@gmail.com', '$2y$10$ri2TGoxnD1kFwj9CtejyVefoutpiTgIAmrZAi6XGnEoUju4JhxZXK', 'staff', '2026-02-20 09:09:55'),
(5, 'staff2', '12@gmail.com', '$2y$10$iC0xREeSl91z4PdoRnddEenrCkD5hvyh8WmDp7Jj1HpiqG.tcIKJu', 'staff', '2026-02-20 09:51:40');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
