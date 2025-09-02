-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 02, 2025 at 11:10 AM
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
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) DEFAULT NULL,
  `target_table` varchar(64) DEFAULT NULL,
  `target_id` bigint(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(22, 2, 10, 6, '2025-09-02 07:23:14');

-- --------------------------------------------------------

--
-- Table structure for table `commission_structure`
--

CREATE TABLE `commission_structure` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL COMMENT 'Percentage rate',
  `fixed_fee` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `commission_structure`
--

INSERT INTO `commission_structure` (`id`, `seller_id`, `category`, `commission_rate`, `fixed_fee`, `is_active`, `effective_from`, `effective_to`, `created_at`) VALUES
(1, 4, 'general', 10.00, 0.00, 1, '2025-09-02', NULL, '2025-09-01 18:56:35'),
(2, 4, 'general', 10.00, 0.00, 1, '2025-09-02', NULL, '2025-09-01 18:58:08');

-- --------------------------------------------------------

--
-- Table structure for table `customer_communications`
--

CREATE TABLE `customer_communications` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `type` enum('email','sms','message','notification') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('sent','delivered','failed','read') DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_loyalty`
--

CREATE TABLE `customer_loyalty` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `points_earned` int(11) DEFAULT 0,
  `points_redeemed` int(11) DEFAULT 0,
  `current_points` int(11) DEFAULT 0,
  `loyalty_tier` varchar(50) DEFAULT 'Bronze',
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `last_order_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_sales_analytics`
--

CREATE TABLE `daily_sales_analytics` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `total_products_sold` int(11) DEFAULT 0,
  `new_customers` int(11) DEFAULT 0,
  `repeat_customers` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_transactions`
--

CREATE TABLE `financial_transactions` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `transaction_type` enum('order_payment','commission','refund','withdrawal','bonus') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_history`
--

CREATE TABLE `inventory_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `action` enum('stock_added','stock_removed','stock_adjusted','order_placed','order_cancelled') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'Order ID or other reference',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_transactions`
--

CREATE TABLE `loyalty_transactions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `transaction_type` enum('earned','redeemed','expired','bonus') NOT NULL,
  `points` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `email_enabled` tinyint(1) DEFAULT 1,
  `sms_enabled` tinyint(1) DEFAULT 0,
  `push_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_preferences`
--

INSERT INTO `notification_preferences` (`id`, `seller_id`, `notification_type`, `email_enabled`, `sms_enabled`, `push_enabled`, `created_at`) VALUES
(1, 4, 'new_order', 1, 0, 1, '2025-09-01 18:56:35'),
(2, 4, 'low_stock', 1, 0, 1, '2025-09-01 18:56:35'),
(3, 4, 'payment_received', 1, 0, 1, '2025-09-01 18:56:35'),
(4, 4, 'customer_message', 1, 0, 1, '2025-09-01 18:56:35');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `total` decimal(10,2) NOT NULL,
  `status` enum('Pending','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `buyer_id`, `seller_id`, `product_id`, `quantity`, `total`, `status`, `created_at`) VALUES
(1, 2, 4, 9, 1, 3400.00, 'Delivered', '2025-08-29 11:00:00'),
(2, 2, 4, 10, 1, 7000.00, 'Delivered', '2025-08-30 05:35:00'),
(10, 2, 4, 9, 1, 3400.00, 'Delivered', '2025-08-29 11:00:00'),
(11, 2, 4, 10, 1, 7000.00, 'Delivered', '2025-08-30 05:35:00'),
(13, 2, 4, 11, 1, 5000.00, 'Pending', '2025-09-01 08:44:03'),
(14, 2, 4, 12, 1, 1000.00, 'Pending', '2025-09-01 08:44:03'),
(15, 2, 4, 8, 2, 18000.00, 'Pending', '2025-09-01 08:44:03'),
(16, 2, 4, 8, 2, 18000.00, 'Pending', '2025-09-01 08:44:03'),
(17, 2, 4, 12, 1, 1000.00, 'Shipped', '2025-09-01 08:51:54'),
(18, 2, 4, 7, 1, 15000.00, 'Pending', '2025-09-01 08:57:56'),
(19, 2, 4, 6, 1, 20000.00, '', '2025-09-01 08:57:56'),
(20, 2, 4, 6, 1, 20000.00, 'Pending', '2025-09-01 09:02:07'),
(21, 2, 4, 6, 1, 20000.00, 'Delivered', '2025-09-01 09:02:25'),
(22, 2, 4, 6, 1, 20000.00, 'Delivered', '2025-09-01 09:02:31'),
(23, 2, 4, 6, 1, 20000.00, 'Delivered', '2025-09-01 09:04:19');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_state` varchar(100) NOT NULL,
  `shipping_zip` varchar(20) NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_zip`, `shipping_phone`, `payment_method`, `created_at`) VALUES
(1, 13, 'edcede', 'efce', 'wdcefefce', '98128', '9812402806', 'bank', '2025-09-01 08:44:03'),
(2, 14, 'edcede', 'efce', 'wdcefefce', '98128', '9812402806', 'bank', '2025-09-01 08:44:03'),
(3, 15, 'edcede', 'efce', 'wdcefefce', '98128', '9812402806', 'bank', '2025-09-01 08:44:03'),
(4, 16, 'edcede', 'efce', 'wdcefefce', '98128', '9812402806', 'bank', '2025-09-01 08:44:03'),
(5, 17, 'edcede', 'efce', 'wdcefefce', '98128', '9812402806', 'cod', '2025-09-01 08:51:54'),
(6, 18, 'edcede', 'efce', 'wdcefefce', '98128', '9812402806', 'cod', '2025-09-01 08:57:56'),
(7, 19, 'edcede', 'efce', 'wdcefefce', '98128', '9812402806', 'cod', '2025-09-01 08:57:56'),
(8, 20, '123 Test Street', 'Test City', 'Test State', '12345', '555-1234', 'cod', '2025-09-01 09:02:07'),
(9, 21, '123 Test Street', 'Test City', 'Test State', '12345', '555-1234', 'cod', '2025-09-01 09:02:25'),
(10, 22, '123 Test Street', 'Test City', 'Test State', '12345', '555-1234', 'cod', '2025-09-01 09:02:31'),
(11, 23, '123 Test Street', 'Test City', 'Test State', '12345', '555-1234', 'cod', '2025-09-01 09:04:19');

-- --------------------------------------------------------

--
-- Table structure for table `order_notes`
--

CREATE TABLE `order_notes` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `note_type` enum('internal','customer_visible','shipping','payment') NOT NULL,
  `note` text NOT NULL,
  `created_by` int(11) NOT NULL COMMENT 'User ID who created the note',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `tracking_url` varchar(500) DEFAULT NULL,
  `status` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tracked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `price` decimal(10,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `description`, `category`, `price`, `stock`, `is_active`, `image`, `created_at`) VALUES
(6, 4, 'Bookshelf', 'Giraffe shape Bookshelf', 'woodwork', 20000.00, 19, 1, 'uploads/1756488952_55.jpeg', '2025-08-29 17:35:52'),
(7, 4, 'Bamboo Bucket', 'Bamboo Bucket made of Bamboo', 'home-decor', 15000.00, 49, 1, 'uploads/1756489020_98.jpeg', '2025-08-29 17:37:00'),
(8, 4, 'Vintage Painting', 'Handmade Picture', 'art', 9000.00, 30, 1, 'uploads/1756489108_989.jpeg', '2025-08-29 17:38:28'),
(9, 4, 'key Holder', 'Wooden Key holder made of wood', 'woodwork', 3400.00, 23, 1, 'uploads/1756489195_77.jpeg', '2025-08-29 17:39:55'),
(10, 4, 'Bag', 'Handcrafted Bag', 'clothing', 7000.00, 45, 1, 'uploads/1756489950_12.jpg', '2025-08-29 17:52:30'),
(11, 4, 'Necklace', 'necklace made with shells', 'jewelry', 5000.00, 79, 1, 'uploads/1756490059_76.jpeg', '2025-08-29 17:54:19'),
(12, 4, 'Doll', 'Handcrafted Doll', 'home-decor', 1000.00, 3, 1, 'uploads/1756528952_7.jpeg', '2025-08-30 04:42:32');

-- --------------------------------------------------------

--
-- Table structure for table `product_alerts`
--

CREATE TABLE `product_alerts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','out_of_stock','reorder_point','expiry_warning') NOT NULL,
  `threshold_value` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_triggered` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'star',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `slug`, `description`, `icon`, `is_active`, `created_at`) VALUES
(1, 'Handmade Jewelry', 'jewelry', 'Beautiful handcrafted jewelry pieces', 'gem', 1, '2025-09-01 08:00:00'),
(2, 'Home Decor', 'home-decor', 'Unique decorative items for your home', 'home', 1, '2025-09-01 08:00:00'),
(3, 'Clothing & Accessories', 'clothing', 'Handcrafted clothing and fashion items', 'tshirt', 1, '2025-09-01 08:00:00'),
(4, 'Art & Paintings', 'art', 'Original artwork and paintings', 'palette', 1, '2025-09-01 08:00:00'),
(5, 'Pottery & Ceramics', 'pottery', 'Handmade pottery and ceramic items', 'circle', 1, '2025-09-01 08:00:00'),
(6, 'Textiles & Fabrics', 'textiles', 'Handwoven textiles and fabric items', 'cut', 1, '2025-09-01 08:00:00'),
(7, 'Woodwork & Furniture', 'woodwork', 'Handcrafted wooden items and furniture', 'tree', 1, '2025-09-01 08:00:00'),
(8, 'Metalwork & Sculptures', 'metalwork', 'Metal crafts and sculptures', 'hammer', 1, '2025-09-01 08:00:00'),
(9, 'Leather Goods', 'leather', 'Handcrafted leather products', 'briefcase', 1, '2025-09-01 08:00:00'),
(10, 'Candles & Soaps', 'candles', 'Handmade candles and soaps', 'fire', 1, '2025-09-01 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `product_performance`
--

CREATE TABLE `product_performance` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `total_quantity_sold` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_notifications`
--

CREATE TABLE `seller_notifications` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `type` enum('new_order','low_stock','payment_received','customer_message','system_update') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'Order ID, product ID, etc.',
  `is_read` tinyint(1) DEFAULT 0,
  `is_email_sent` tinyint(1) DEFAULT 0,
  `is_sms_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_zones`
--

CREATE TABLE `shipping_zones` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `countries` text DEFAULT NULL COMMENT 'JSON array of country codes',
  `states` text DEFAULT NULL COMMENT 'JSON array of state codes',
  `cities` text DEFAULT NULL COMMENT 'JSON array of city names',
  `shipping_rate` decimal(10,2) NOT NULL,
  `free_shipping_threshold` decimal(10,2) DEFAULT NULL,
  `estimated_delivery_days` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_settings`
--

CREATE TABLE `store_settings` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `store_name` varchar(255) NOT NULL,
  `store_description` text DEFAULT NULL,
  `store_logo` varchar(255) DEFAULT NULL,
  `store_banner` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `business_hours` text DEFAULT NULL,
  `shipping_policy` text DEFAULT NULL,
  `return_policy` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('buyer','seller','admin') DEFAULT 'buyer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(2, 'om prakash', 'om@gmail.com', '$2y$10$N8WWLOGIpqe0VH66alnMCemzPHfQDJs8CHF3.OZl3JVzVkAhfvoc2', 'buyer', '2025-08-28 12:31:17'),
(4, 'om prakash', 'om12@gmail.com', '$2y$10$yVvbLYwsxnNS7gOMvICWAeZgvmjBg5Kze7Z.GY82KM/vpYuq4xQ9q', 'seller', '2025-08-28 16:27:03'),
(7, 'admin', 'admin@gmail.com', '$2y$10$wW2SqJdx8ykUKxA6qx6I5uIH44KuCzrNwsAwhbgIEgq1MtlLr7AN2', 'admin', '2025-08-28 17:34:39');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('shipping','billing') DEFAULT 'shipping',
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(18, 2, 6, '2025-09-01 16:25:51'),
(21, 2, 12, '2025-09-02 06:58:54'),
(22, 2, 10, '2025-09-02 07:25:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `commission_structure`
--
ALTER TABLE `commission_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_effective_dates` (`effective_from`,`effective_to`);

--
-- Indexes for table `customer_communications`
--
ALTER TABLE `customer_communications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_customer` (`seller_id`,`customer_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_customer_communications_seller_customer_date` (`seller_id`,`customer_id`,`created_at`);

--
-- Indexes for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_feedback` (`order_id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_customer_id` (`customer_id`);

--
-- Indexes for table `customer_loyalty`
--
ALTER TABLE `customer_loyalty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_customer_seller` (`customer_id`,`seller_id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_loyalty_tier` (`loyalty_tier`);

--
-- Indexes for table `daily_sales_analytics`
--
ALTER TABLE `daily_sales_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seller_date` (`seller_id`,`date`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_seller` (`customer_id`,`seller_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seller_notification_type` (`seller_id`,`notification_type`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_orders_seller_status_date` (`seller_id`,`status`,`created_at`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_notes`
--
ALTER TABLE `order_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_note_type` (`note_type`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_tracking_number` (`tracking_number`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seller` (`seller_id`),
  ADD KEY `idx_products_category` (`category`),
  ADD KEY `idx_products_is_active` (`is_active`),
  ADD KEY `idx_products_seller_active_category` (`seller_id`,`is_active`,`category`);

--
-- Indexes for table `product_alerts`
--
ALTER TABLE `product_alerts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_alert` (`product_id`,`alert_type`),
  ADD KEY `idx_seller_id` (`seller_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `product_performance`
--
ALTER TABLE `product_performance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_performance` (`product_id`),
  ADD KEY `idx_seller_id` (`seller_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product_review_unique` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller_id` (`seller_id`);

--
-- Indexes for table `store_settings`
--
ALTER TABLE `store_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seller_store` (`seller_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `commission_structure`
--
ALTER TABLE `commission_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_communications`
--
ALTER TABLE `customer_communications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_loyalty`
--
ALTER TABLE `customer_loyalty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_sales_analytics`
--
ALTER TABLE `daily_sales_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_notes`
--
ALTER TABLE `order_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_alerts`
--
ALTER TABLE `product_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_performance`
--
ALTER TABLE `product_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_settings`
--
ALTER TABLE `store_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
