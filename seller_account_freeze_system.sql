-- Seller Account Freeze System
-- This file adds account status management and payment verification for sellers

-- Add account status fields to users table
ALTER TABLE `users` 
ADD COLUMN `account_status` ENUM('active', 'frozen', 'suspended') DEFAULT 'active' AFTER `role`,
ADD COLUMN `frozen_reason` TEXT NULL AFTER `account_status`,
ADD COLUMN `frozen_at` TIMESTAMP NULL AFTER `frozen_reason`,
ADD COLUMN `subscription_expires` DATE NULL AFTER `frozen_at`;

-- Create seller_payments table for payment verification
CREATE TABLE `seller_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `payment_type` ENUM('subscription', 'renewal', 'penalty') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'INR',
  `payment_method` VARCHAR(50) NOT NULL,
  `transaction_id` VARCHAR(100) NULL,
  `payment_proof` VARCHAR(255) NOT NULL COMMENT 'File path to uploaded proof',
  `status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
  `admin_notes` TEXT NULL,
  `verified_by` int(11) NULL,
  `verified_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create seller_subscriptions table for subscription management
CREATE TABLE `seller_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `plan_type` ENUM('basic', 'premium', 'enterprise') DEFAULT 'basic',
  `monthly_fee` DECIMAL(10,2) NOT NULL,
  `features` JSON NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `auto_renew` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_is_active` (`is_active`),
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default subscription plans
INSERT INTO `seller_subscriptions` (`plan_type`, `monthly_fee`, `features`, `start_date`, `end_date`) VALUES
('basic', 299.00, '["basic_listing", "order_management", "basic_analytics"]', '2025-01-01', '2025-12-31'),
('premium', 599.00, '["basic_listing", "order_management", "advanced_analytics", "priority_support", "featured_products"]', '2025-01-01', '2025-12-31'),
('enterprise', 999.00, '["basic_listing", "order_management", "advanced_analytics", "priority_support", "featured_products", "bulk_operations", "api_access"]', '2025-01-01', '2025-12-31');

-- Add indexes for better performance
CREATE INDEX `idx_users_account_status` ON `users`(`account_status`);
CREATE INDEX `idx_users_role_status` ON `users`(`role`, `account_status`);