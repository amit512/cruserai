-- HomeCraft Subscription System Fix Script
-- Run this to fix existing data and set up proper trial periods

-- 1. Update existing sellers to have proper trial periods (3 days from creation)
UPDATE users 
SET subscription_expires = DATE_ADD(created_at, INTERVAL 3 DAY)
WHERE role = 'seller' 
AND subscription_expires IS NOT NULL 
AND subscription_expires < CURDATE();

-- 2. Set account status to 'active' for sellers who are not manually frozen
UPDATE users 
SET account_status = 'active' 
WHERE role = 'seller' 
AND account_status = 'frozen'
AND frozen_reason NOT LIKE '%Manual%';

-- 3. Clear frozen_reason and frozen_at for sellers who should be active
UPDATE users 
SET frozen_reason = NULL, frozen_at = NULL
WHERE role = 'seller' 
AND account_status = 'active'
AND subscription_expires >= CURDATE();

-- 4. Update seller_accounts table to match users table status
UPDATE seller_accounts sa
JOIN users u ON sa.seller_id = u.id
SET sa.is_frozen = CASE 
    WHEN u.account_status = 'frozen' THEN 1
    ELSE 0
END
WHERE u.role = 'seller';

-- 5. Insert missing seller_accounts records for sellers who don't have them
INSERT IGNORE INTO seller_accounts (seller_id, is_frozen, freeze_threshold)
SELECT id, 
       CASE WHEN account_status = 'frozen' THEN 1 ELSE 0 END,
       1000.00
FROM users 
WHERE role = 'seller' 
AND id NOT IN (SELECT seller_id FROM seller_accounts);

-- 6. Show current status after fixes
SELECT 
    u.id,
    u.name,
    u.email,
    u.role,
    u.account_status,
    u.subscription_expires,
    u.created_at,
    u.frozen_reason,
    sa.is_frozen as legacy_frozen,
    CASE 
        WHEN u.subscription_expires IS NULL THEN 'No subscription data'
        WHEN u.subscription_expires < CURDATE() THEN 'Expired'
        WHEN u.subscription_expires = CURDATE() THEN 'Expires today'
        WHEN u.subscription_expires <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'Expires soon'
        ELSE 'Active'
    END as subscription_status
FROM users u
LEFT JOIN seller_accounts sa ON u.id = sa.seller_id
WHERE u.role = 'seller'
ORDER BY u.id;
