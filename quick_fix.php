<?php
/**
 * Quick Fix Script for Subscription System
 * Run this to fix common issues
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';

echo "<h1>HomeCraft Subscription System Quick Fix</h1>\n";

try {
    $pdo = db();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Fix 1: Add missing database fields
    echo "<h2>1. Adding Missing Database Fields</h2>\n";
    
    $fixes = [
        // Users table fixes
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_expires DATE DEFAULT NULL" => "subscription_expires",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS account_status ENUM('active','frozen','suspended') DEFAULT 'active'" => "account_status",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS frozen_reason TEXT DEFAULT NULL" => "frozen_reason",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS frozen_at TIMESTAMP NULL DEFAULT NULL" => "frozen_at",
        
        // Seller payments table fixes
        "ALTER TABLE seller_payments ADD COLUMN IF NOT EXISTS payment_type VARCHAR(50) DEFAULT NULL" => "payment_type",
        "ALTER TABLE seller_payments ADD COLUMN IF NOT EXISTS status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending'" => "status",
        "ALTER TABLE seller_payments ADD COLUMN IF NOT EXISTS admin_notes TEXT DEFAULT NULL" => "admin_notes",
        "ALTER TABLE seller_payments ADD COLUMN IF NOT EXISTS verified_by INT DEFAULT NULL" => "verified_by",
        "ALTER TABLE seller_payments ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL DEFAULT NULL" => "verified_at"
    ];
    
    foreach ($fixes as $sql => $field) {
        try {
            $pdo->exec($sql);
            echo "<p>✅ Added field: $field</p>\n";
        } catch (Exception $e) {
            echo "<p>⚠️ Field $field already exists or error: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Fix 2: Update existing sellers with trial period
    echo "<h2>2. Setting Trial Periods for Existing Sellers</h2>\n";
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_expires = DATE_ADD(created_at, INTERVAL 3 DAY)
            WHERE role = 'seller' 
            AND subscription_expires IS NULL 
            AND created_at IS NOT NULL
        ");
        $stmt->execute();
        $updated = $stmt->rowCount();
        echo "<p>✅ Updated $updated sellers with trial periods</p>\n";
    } catch (Exception $e) {
        echo "<p>❌ Error updating trial periods: " . $e->getMessage() . "</p>\n";
    }
    
    // Fix 3: Set account status for existing sellers
    echo "<h2>3. Setting Account Status for Existing Sellers</h2>\n";
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET account_status = 'active' 
            WHERE role = 'seller' 
            AND account_status IS NULL
        ");
        $stmt->execute();
        $updated = $stmt->rowCount();
        echo "<p>✅ Updated $updated sellers with account status</p>\n";
    } catch (Exception $e) {
        echo "<p>❌ Error updating account status: " . $e->getMessage() . "</p>\n";
    }
    
    // Fix 4: Create admin_logs table
    echo "<h2>4. Creating Admin Logs Table</h2>\n";
    
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                target_table VARCHAR(50) NOT NULL,
                target_id INT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_id (admin_id),
                INDEX idx_action (action),
                INDEX idx_target (target_table, target_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "<p>✅ Admin logs table created/verified</p>\n";
    } catch (Exception $e) {
        echo "<p>❌ Error with admin logs table: " . $e->getMessage() . "</p>\n";
    }
    
    // Fix 5: Test AccountManager
    echo "<h2>5. Testing AccountManager</h2>\n";
    
    try {
        require_once __DIR__ . '/app/AccountManager.php';
        
        // Test with first seller
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'seller' LIMIT 1");
        $seller = $stmt->fetch();
        
        if ($seller) {
            $sellerId = $seller['id'];
            echo "<p>Testing with seller ID: $sellerId</p>\n";
            
            // Test getAccountStatus
            $status = AccountManager::getAccountStatus($sellerId);
            if ($status) {
                echo "<p>✅ getAccountStatus() working</p>\n";
                echo "<pre>" . print_r($status, true) . "</pre>\n";
            } else {
                echo "<p>⚠️ getAccountStatus() returned null</p>\n";
            }
            
            // Test isAccountFrozen
            $isFrozen = AccountManager::isAccountFrozen($sellerId);
            echo "<p>isAccountFrozen(): " . ($isFrozen ? 'TRUE' : 'FALSE') . "</p>\n";
            
        } else {
            echo "<p>⚠️ No sellers found to test with</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ AccountManager test failed: " . $e->getMessage() . "</p>\n";
    }
    
    // Fix 6: Show current seller status
    echo "<h2>6. Current Seller Status</h2>\n";
    
    try {
        $stmt = $pdo->query("
            SELECT id, name, email, subscription_expires, account_status, created_at 
            FROM users 
            WHERE role = 'seller' 
            LIMIT 5
        ");
        $sellers = $stmt->fetchAll();
        
        if (!empty($sellers)) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Sub Expires</th><th>Status</th><th>Created</th></tr>\n";
            
            foreach ($sellers as $seller) {
                $subExpires = $seller['subscription_expires'] ?: 'NULL';
                $status = $seller['account_status'] ?: 'NULL';
                $created = $seller['created_at'] ?: 'NULL';
                
                echo "<tr>";
                echo "<td>{$seller['id']}</td>";
                echo "<td>{$seller['name']}</td>";
                echo "<td>{$seller['email']}</td>";
                echo "<td>$subExpires</td>";
                echo "<td>$status</td>";
                echo "<td>$created</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<p>⚠️ No sellers found</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error showing seller status: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<h2>✅ Quick Fix Completed!</h2>\n";
    echo "<p>Now try:</p>\n";
    echo "<ol>\n";
    echo "<li>Register a new seller account to test trial period</li>\n";
    echo "<li>Check if existing sellers can access their dashboard</li>\n";
    echo "<li>Run the cron job: <code>php cron_auto_freeze.php</code></li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Fatal error: " . $e->getMessage() . "</p>\n";
}
?>
