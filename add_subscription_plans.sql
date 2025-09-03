-- Add subscription plans for existing sellers
-- This script should be run AFTER creating the seller_subscriptions table

-- First, let's see what sellers exist
SELECT 'Existing sellers:' as info;
SELECT id, name, email, role FROM users WHERE role = 'seller';

-- Add subscription plans for existing sellers (only if they don't already have one)
INSERT IGNORE INTO `seller_subscriptions` (`seller_id`, `plan_type`, `monthly_fee`, `features`, `start_date`, `end_date`) 
SELECT 
    u.id as seller_id,
    'basic' as plan_type,
    299.00 as monthly_fee,
    '["basic_listing", "order_management", "basic_analytics"]' as features,
    CURDATE() as start_date,
    DATE_ADD(CURDATE(), INTERVAL 1 YEAR) as end_date
FROM users u 
WHERE u.role = 'seller' 
AND NOT EXISTS (
    SELECT 1 FROM seller_subscriptions ss WHERE ss.seller_id = u.id
);

-- Show what was added
SELECT 'Subscription plans added:' as info;
SELECT 
    ss.id,
    u.name as seller_name,
    ss.plan_type,
    ss.monthly_fee,
    ss.start_date,
    ss.end_date
FROM seller_subscriptions ss
JOIN users u ON ss.seller_id = u.id
ORDER BY ss.id;

-- Display completion message
SELECT 'Subscription plans added successfully!' as status;