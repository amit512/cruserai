-- Seller Account Freeze System (Fixed Version)
-- This file adds account status management and payment verification for sellers
-- It checks for existing columns before adding them to avoid errors

-- Check and add account status fields to users table (only if they don't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'account_status') = 0,
    'ALTER TABLE `users` ADD COLUMN `account_status` ENUM("active", "frozen", "suspended") DEFAULT "active" AFTER `role`',
    'SELECT "Column account_status already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'frozen_reason') = 0,
    'ALTER TABLE `users` ADD COLUMN `frozen_reason` TEXT NULL AFTER `account_status`',
    'SELECT "Column frozen_reason already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'frozen_at') = 0,
    'ALTER TABLE `users` ADD COLUMN `frozen_at` TIMESTAMP NULL AFTER `frozen_reason`',
    'SELECT "Column frozen_at already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'subscription_expires') = 0,
    'ALTER TABLE `users` ADD COLUMN `subscription_expires` DATE NULL AFTER `frozen_at`',
    'SELECT "Column subscription_expires already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create seller_payments table (only if it doesn't exist)
CREATE TABLE IF NOT EXISTS `seller_payments` (
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
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraints (only if they don't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'seller_payments' 
     AND COLUMN_NAME = 'seller_id' 
     AND REFERENCED_TABLE_NAME = 'users') = 0,
    'ALTER TABLE `seller_payments` ADD CONSTRAINT `fk_seller_payments_seller_id` FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE',
    'SELECT "Foreign key constraint already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'seller_payments' 
     AND COLUMN_NAME = 'verified_by' 
     AND REFERENCED_TABLE_NAME = 'users') = 0,
    'ALTER TABLE `seller_payments` ADD CONSTRAINT `fk_seller_payments_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL',
    'SELECT "Foreign key constraint already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create seller_subscriptions table (only if it doesn't exist)
CREATE TABLE IF NOT EXISTS `seller_subscriptions` (
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
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraint for seller_subscriptions (only if it doesn't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'seller_subscriptions' 
     AND COLUMN_NAME = 'seller_id' 
     AND REFERENCED_TABLE_NAME = 'users') = 0,
    'ALTER TABLE `seller_subscriptions` ADD CONSTRAINT `fk_seller_subscriptions_seller_id` FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE',
    'SELECT "Foreign key constraint already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert default subscription plans (only if table is empty)
INSERT IGNORE INTO `seller_subscriptions` (`plan_type`, `monthly_fee`, `features`, `start_date`, `end_date`) VALUES
('basic', 299.00, '["basic_listing", "order_management", "basic_analytics"]', '2025-01-01', '2025-12-31'),
('premium', 599.00, '["basic_listing", "order_management", "advanced_analytics", "priority_support", "featured_products"]', '2025-01-01', '2025-12-31'),
('enterprise', 999.00, '["basic_listing", "order_management", "advanced_analytics", "priority_support", "featured_products", "bulk_operations", "api_access"]', '2025-01-01', '2025-12-31');

-- Add indexes for better performance (only if they don't exist)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND INDEX_NAME = 'idx_users_account_status') = 0,
    'CREATE INDEX `idx_users_account_status` ON `users`(`account_status`)',
    'SELECT "Index idx_users_account_status already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'users' 
     AND INDEX_NAME = 'idx_users_role_status') = 0,
    'CREATE INDEX `idx_users_role_status` ON `users`(`role`, `account_status`)',
    'SELECT "Index idx_users_role_status already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing users to have 'active' account status if not set
UPDATE `users` SET `account_status` = 'active' WHERE `account_status` IS NULL;

-- Display completion message
SELECT 'Seller Account Freeze System setup completed successfully!' as status;