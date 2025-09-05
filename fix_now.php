<?php
/**
 * Quick Fix for Subscription System Issues
 * Run this to fix the current problems
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';

echo "<h1>Quick Fix for Subscription System</h1>\n";

try {
    $pdo = db();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Fix 1: Update expired subscriptions to proper trial periods
    echo "<h2>1. Fixing Expired Subscriptions</h2>\n";
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET subscription_expires = DATE_ADD(created_at, INTERVAL 3 DAY)
        WHERE role = 'seller' 
        AND subscription_expires < CURDATE()
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "<p>✅ Updated $updated sellers with proper trial periods</p>\n";
    
    // Fix 2: Set account status to 'frozen' for expired trials
    echo "<h2>2. Setting Account Status for Expired Trials</h2>\n";
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET account_status = 'frozen',
            frozen_reason = 'Trial expired. Monthly subscription required.',
            frozen_at = CURRENT_TIMESTAMP
        WHERE role = 'seller' 
        AND subscription_expires < CURDATE()
        AND account_status = 'active'
    ");
    $stmt->execute();
    $frozen = $stmt->rowCount();
    echo "<p>✅ Frozen $frozen accounts with expired trials</p>\n";
    
    // Fix 3: Create missing seller_accounts records
    echo "<h2>3. Creating Missing Seller Accounts</h2>\n";
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO seller_accounts (seller_id, is_frozen, freeze_threshold)
        SELECT id, 
               CASE WHEN account_status = 'frozen' THEN 1 ELSE 0 END,
               1000.00
        FROM users 
        WHERE role = 'seller' 
        AND id NOT IN (SELECT seller_id FROM seller_accounts)
    ");
    $stmt->execute();
    $inserted = $stmt->rowCount();
    echo "<p>✅ Created $inserted missing seller account records</p>\n";
    
    // Fix 4: Sync seller_accounts with users table
    echo "<h2>4. Syncing Seller Accounts Status</h2>\n";
    
    $stmt = $pdo->prepare("
        UPDATE seller_accounts sa
        JOIN users u ON sa.seller_id = u.id
        SET sa.is_frozen = CASE 
            WHEN u.account_status = 'frozen' THEN 1
            ELSE 0
        END
        WHERE u.role = 'seller'
    ");
    $stmt->execute();
    $synced = $stmt->rowCount();
    echo "<p>✅ Synced $synced seller account records</p>\n";
    
    // Show current status after fixes
    echo "<h2>5. Current Status After Fixes</h2>\n";
    
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.account_status,
            u.subscription_expires,
            u.created_at,
            u.frozen_reason,
            sa.is_frozen as legacy_frozen
        FROM users u
        LEFT JOIN seller_accounts sa ON u.id = sa.seller_id
        WHERE u.role = 'seller'
        ORDER BY u.id
    ");
    $sellers = $stmt->fetchAll();
    
    if (!empty($sellers)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Sub Expires</th><th>Created</th><th>Legacy Frozen</th></tr>\n";
        
        foreach ($sellers as $seller) {
            $subExpires = $seller['subscription_expires'] ?: 'NULL';
            $status = $seller['account_status'] ?: 'NULL';
            $created = $seller['created_at'] ?: 'NULL';
            $legacyFrozen = $seller['legacy_frozen'] ?: 'NULL';
            
            echo "<tr>";
            echo "<td>{$seller['id']}</td>";
            echo "<td>{$seller['name']}</td>";
            echo "<td>{$seller['email']}</td>";
            echo "<td>$status</td>";
            echo "<td>$subExpires</td>";
            echo "<td>$created</td>";
            echo "<td>$legacyFrozen</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Test AccountManager after fixes
    echo "<h2>6. Testing AccountManager After Fixes</h2>\n";
    
    try {
        require_once __DIR__ . '/app/AccountManager.php';
        
        if (!empty($sellers)) {
            foreach ($sellers as $seller) {
                $sellerId = $seller['id'];
                $isFrozen = AccountManager::isAccountFrozen($sellerId);
                
                echo "<p><strong>{$seller['name']}</strong> (ID: $sellerId): isAccountFrozen() = " . ($isFrozen ? 'TRUE' : 'FALSE') . "</p>\n";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ AccountManager error: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<h2>✅ Quick Fix Completed!</h2>\n";
    echo "<p>Now test the system:</p>\n";
    echo "<ol>\n";
    echo "<li>Run: <code>php test_system.php</code></li>\n";
    echo "<li>Run: <code>php cron_auto_freeze.php</code></li>\n";
    echo "<li>Login as a seller to see the trial status</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Fatal error: " . $e->getMessage() . "</p>\n";
}
?>
