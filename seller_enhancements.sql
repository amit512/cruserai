-- =====================================================
-- SELLER ENHANCEMENTS DATABASE STRUCTURE
-- =====================================================
-- This file contains all new tables and modifications needed
-- for the enhanced seller features in HomeCraft

-- =====================================================
-- 1. CUSTOMER RELATIONSHIP MANAGEMENT
-- =====================================================

-- Customer communications log
CREATE TABLE IF NOT EXISTS `customer_communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `type` enum('email','sms','message','notification') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('sent','delivered','failed','read') DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_customer` (`seller_id`, `customer_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Customer feedback and reviews
CREATE TABLE IF NOT EXISTS `customer_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `review` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_feedback` (`order_id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 2. INVENTORY MANAGEMENT
-- =====================================================

-- Inventory history tracking
CREATE TABLE IF NOT EXISTS `inventory_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `action` enum('stock_added','stock_removed','stock_adjusted','order_placed','order_cancelled') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'Order ID or other reference',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Product reorder points and alerts
CREATE TABLE IF NOT EXISTS `product_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','out_of_stock','reorder_point','expiry_warning') NOT NULL,
  `threshold_value` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_triggered` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_alert` (`product_id`, `alert_type`),
  KEY `idx_seller_id` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 3. FINANCIAL MANAGEMENT
-- =====================================================

-- Financial transactions
CREATE TABLE IF NOT EXISTS `financial_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Commission and fee structure
CREATE TABLE IF NOT EXISTS `commission_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL COMMENT 'Percentage rate',
  `fixed_fee` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_category` (`category`),
  KEY `idx_effective_dates` (`effective_from`, `effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 4. STORE PROFILE MANAGEMENT
-- =====================================================

-- Store settings and profile
CREATE TABLE IF NOT EXISTS `store_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_seller_store` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Shipping zones and rates
CREATE TABLE IF NOT EXISTS `shipping_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `countries` text DEFAULT NULL COMMENT 'JSON array of country codes',
  `states` text DEFAULT NULL COMMENT 'JSON array of state codes',
  `cities` text DEFAULT NULL COMMENT 'JSON array of city names',
  `shipping_rate` decimal(10,2) NOT NULL,
  `free_shipping_threshold` decimal(10,2) DEFAULT NULL,
  `estimated_delivery_days` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_id` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 5. NOTIFICATION SYSTEM
-- =====================================================

-- Seller notifications
CREATE TABLE IF NOT EXISTS `seller_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `type` enum('new_order','low_stock','payment_received','customer_message','system_update') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'Order ID, product ID, etc.',
  `is_read` tinyint(1) DEFAULT 0,
  `is_email_sent` tinyint(1) DEFAULT 0,
  `is_sms_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notification preferences
CREATE TABLE IF NOT EXISTS `notification_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `email_enabled` tinyint(1) DEFAULT 1,
  `sms_enabled` tinyint(1) DEFAULT 0,
  `push_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_seller_notification_type` (`seller_id`, `notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 6. ANALYTICS AND REPORTING
-- =====================================================

-- Daily sales analytics
CREATE TABLE IF NOT EXISTS `daily_sales_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `total_products_sold` int(11) DEFAULT 0,
  `new_customers` int(11) DEFAULT 0,
  `repeat_customers` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_seller_date` (`seller_id`, `date`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Product performance analytics
CREATE TABLE IF NOT EXISTS `product_performance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `total_quantity_sold` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_performance` (`product_id`),
  KEY `idx_seller_id` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 7. ENHANCED ORDER MANAGEMENT
-- =====================================================

-- Order notes and internal comments
CREATE TABLE IF NOT EXISTS `order_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `note_type` enum('internal','customer_visible','shipping','payment') NOT NULL,
  `note` text NOT NULL,
  `created_by` int(11) NOT NULL COMMENT 'User ID who created the note',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_note_type` (`note_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Order tracking and shipping updates
CREATE TABLE IF NOT EXISTS `order_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `tracking_url` varchar(500) DEFAULT NULL,
  `status` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tracked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_tracking_number` (`tracking_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 8. CUSTOMER LOYALTY AND REWARDS
-- =====================================================

-- Customer loyalty points
CREATE TABLE IF NOT EXISTS `customer_loyalty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `points_earned` int(11) DEFAULT 0,
  `points_redeemed` int(11) DEFAULT 0,
  `current_points` int(11) DEFAULT 0,
  `loyalty_tier` varchar(50) DEFAULT 'Bronze',
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `last_order_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_seller` (`customer_id`, `seller_id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_loyalty_tier` (`loyalty_tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Loyalty transactions
CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `transaction_type` enum('earned','redeemed','expired','bonus') NOT NULL,
  `points` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_seller` (`customer_id`, `seller_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_transaction_type` (`transaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default notification preferences for existing sellers
INSERT IGNORE INTO `notification_preferences` (`seller_id`, `notification_type`, `email_enabled`, `sms_enabled`, `push_enabled`)
SELECT DISTINCT id, 'new_order', 1, 0, 1 FROM users WHERE role = 'seller';

INSERT IGNORE INTO `notification_preferences` (`seller_id`, `notification_type`, `email_enabled`, `sms_enabled`, `push_enabled`)
SELECT DISTINCT id, 'low_stock', 1, 0, 1 FROM users WHERE role = 'seller';

INSERT IGNORE INTO `notification_preferences` (`seller_id`, `notification_type`, `email_enabled`, `sms_enabled`, `push_enabled`)
SELECT DISTINCT id, 'payment_received', 1, 0, 1 FROM users WHERE role = 'seller';

INSERT IGNORE INTO `notification_preferences` (`seller_id`, `notification_type`, `email_enabled`, `sms_enabled`, `push_enabled`)
SELECT DISTINCT id, 'customer_message', 1, 0, 1 FROM users WHERE role = 'seller';

-- Insert default commission structure
INSERT IGNORE INTO `commission_structure` (`seller_id`, `category`, `commission_rate`, `fixed_fee`, `effective_from`)
SELECT DISTINCT id, 'general', 10.00, 0.00, CURDATE() FROM users WHERE role = 'seller';

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================

-- Composite indexes for better query performance
CREATE INDEX IF NOT EXISTS `idx_orders_seller_status_date` ON `orders` (`seller_id`, `status`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_products_seller_active_category` ON `products` (`seller_id`, `is_active`, `category`);
CREATE INDEX IF NOT EXISTS `idx_customer_communications_seller_customer_date` ON `customer_communications` (`seller_id`, `customer_id`, `created_at`);

-- =====================================================
-- END OF DATABASE STRUCTURE
-- =====================================================
