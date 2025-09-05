-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 06:45 AM
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
-- Database: `homecraft`
--

-- --------------------------------------------------------

--
-- Table structure for table `seller_subscriptions`
--

CREATE TABLE `seller_subscriptions` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `plan_type` enum('basic','premium','enterprise') DEFAULT 'basic',
  `monthly_fee` decimal(10,2) NOT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `auto_renew` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_subscriptions`
--

INSERT INTO `seller_subscriptions` (`id`, `seller_id`, `plan_type`, `monthly_fee`, `features`, `start_date`, `end_date`, `is_active`, `auto_renew`, `created_at`) VALUES
(4, 4, 'basic', 500.00, '[\"basic_listing\",\"order_management\",\"basic_analytics\"]', '2025-09-03', '2026-09-03', 1, 1, '2025-09-03 06:47:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `seller_subscriptions`
--
ALTER TABLE `seller_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `seller_subscriptions`
--
ALTER TABLE `seller_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `seller_subscriptions`
--
ALTER TABLE `seller_subscriptions`
  ADD CONSTRAINT `fk_seller_subscriptions_seller_id` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
