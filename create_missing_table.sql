-- Create missing seller_subscriptions table only
-- This script only creates the table that's missing from your database

-- Create seller_subscriptions table
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

-- Add foreign key constraint
ALTER TABLE `seller_subscriptions` 
ADD CONSTRAINT `fk_seller_subscriptions_seller_id` 
FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- Insert default subscription plans
INSERT INTO `seller_subscriptions` (`plan_type`, `monthly_fee`, `features`, `start_date`, `end_date`) VALUES
('basic', 299.00, '["basic_listing", "order_management", "basic_analytics"]', '2025-01-01', '2025-12-31'),
('premium', 599.00, '["basic_listing", "order_management", "advanced_analytics", "priority_support", "featured_products"]', '2025-01-01', '2025-12-31'),
('enterprise', 999.00, '["basic_listing", "order_management", "advanced_analytics", "priority_support", "featured_products", "bulk_operations", "api_access"]', '2025-01-01', '2025-12-31');

-- Add performance indexes (only if they don't exist)
CREATE INDEX IF NOT EXISTS `idx_users_account_status` ON `users`(`account_status`);
CREATE INDEX IF NOT EXISTS `idx_users_role_status` ON `users`(`role`, `account_status`);

-- Display completion message
SELECT 'Missing table created successfully! Account Freeze System is now complete.' as status;
