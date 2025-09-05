<?php
/**
 * Test Script: Debug Monthly Subscription System
 * Run this to check what's working and what's not
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/AccountManager.php';

echo "<h1>HomeCraft Subscription System Test</h1>\n";

try {
    $pdo = db();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Check if required database fields exist
    echo "<h2>Database Schema Check</h2>\n";
    
    $requiredFields = [
        'users' => ['subscription_expires', 'account_status', 'frozen_reason', 'frozen_at'],
        'seller_payments' => ['payment_type', 'status', 'admin_notes', 'verified_by', 'verified_at']
    ];
    
    foreach ($requiredFields as $table => $fields) {
        echo "<h3>Table: $table</h3>\n";
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            foreach ($fields as $field) {
                if (in_array($field, $columns)) {
                    echo "<p>✅ $field - exists</p>\n";
                } else {
                    echo "<p>❌ $field - MISSING</p>\n";
                }
            }
        } catch (Exception $e) {
            echo "<p>❌ Error checking table $table: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Check existing sellers
    echo "<h2>Existing Sellers Check</h2>\n";
    
    try {
        $stmt = $pdo->query("SELECT id, name, email, role, subscription_expires, account_status, created_at FROM users WHERE role = 'seller' LIMIT 5");
        $sellers = $stmt->fetchAll();
        
        if (empty($sellers)) {
            echo "<p>⚠️ No sellers found in database</p>\n";
        } else {
            echo "<p>Found " . count($sellers) . " sellers:</p>\n";
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
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking sellers: " . $e->getMessage() . "</p>\n";
    }
    
    // Test AccountManager functions
    echo "<h2>AccountManager Test</h2>\n";
    
    if (!empty($sellers)) {
        $testSeller = $sellers[0];
        $sellerId = $testSeller['id'];
        
        echo "<p>Testing with seller ID: $sellerId</p>\n";
        
        try {
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
            
        } catch (Exception $e) {
            echo "<p>❌ AccountManager error: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Test trial calculation
    echo "<h2>Trial Period Test</h2>\n";
    
    if (!empty($sellers)) {
        $testSeller = $sellers[0];
        $createdAt = $testSeller['created_at'];
        
        if ($createdAt) {
            try {
                $created = new DateTime($createdAt);
                $trialEnd = (clone $created)->modify('+3 days');
                $today = new DateTime('today');
                $trialDaysLeft = max(0, $today->diff($trialEnd)->days);
                $isTrialExpired = $today > $trialEnd;
                
                echo "<p>Seller created: " . $created->format('Y-m-d H:i:s') . "</p>\n";
                echo "<p>Trial ends: " . $trialEnd->format('Y-m-d H:i:s') . "</p>\n";
                echo "<p>Today: " . $today->format('Y-m-d H:i:s') . "</p>\n";
                echo "<p>Trial days left: $trialDaysLeft</p>\n";
                echo "<p>Trial expired: " . ($isTrialExpired ? 'YES' : 'NO') . "</p>\n";
                
            } catch (Exception $e) {
                echo "<p>❌ Date calculation error: " . $e->getMessage() . "</p>\n";
            }
        } else {
            echo "<p>⚠️ No created_at date for test seller</p>\n";
        }
    }
    
    // Check seller_payments table
    echo "<h2>Payment System Check</h2>\n";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM seller_payments");
        $result = $stmt->fetch();
        echo "<p>Total payments in system: " . $result['count'] . "</p>\n";
        
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT * FROM seller_payments ORDER BY created_at DESC LIMIT 3");
            $payments = $stmt->fetchAll();
            
            echo "<p>Recent payments:</p>\n";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            echo "<tr><th>ID</th><th>Seller ID</th><th>Type</th><th>Amount</th><th>Status</th><th>Created</th></tr>\n";
            
            foreach ($payments as $payment) {
                echo "<tr>";
                echo "<td>{$payment['id']}</td>";
                echo "<td>{$payment['seller_id']}</td>";
                echo "<td>" . ($payment['payment_type'] ?: 'NULL') . "</td>";
                echo "<td>{$payment['amount']}</td>";
                echo "<td>" . ($payment['status'] ?: 'NULL') . "</td>";
                echo "<td>{$payment['created_at']}</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error checking payments: " . $e->getMessage() . "</p>\n";
    }
    
    // Test cron job logic
    echo "<h2>Cron Job Logic Test</h2>\n";
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.subscription_expires, u.created_at
            FROM users u 
            WHERE u.role = 'seller' 
            AND u.account_status != 'frozen'
            AND u.subscription_expires IS NOT NULL
            AND u.subscription_expires < CURDATE()
        ");
        $stmt->execute();
        $expiredSellers = $stmt->fetchAll();
        
        echo "<p>Found " . count($expiredSellers) . " sellers with expired subscriptions</p>\n";
        
        if (!empty($expiredSellers)) {
            echo "<p>Expired sellers:</p>\n";
            foreach ($expiredSellers as $seller) {
                echo "<p>- {$seller['name']} ({$seller['email']}) - Expired: {$seller['subscription_expires']}</p>\n";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error testing cron logic: " . $e->getMessage() . "</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Fatal error: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Next Steps</h2>\n";
echo "<p>If you see missing database fields, run these SQL commands:</p>\n";
echo "<pre>\n";
echo "-- Add to users table\n";
echo "ALTER TABLE users ADD COLUMN subscription_expires DATE DEFAULT NULL;\n";
echo "ALTER TABLE users ADD COLUMN account_status ENUM('active','frozen','suspended') DEFAULT 'active';\n";
echo "ALTER TABLE users ADD COLUMN frozen_reason TEXT DEFAULT NULL;\n";
echo "ALTER TABLE users ADD COLUMN frozen_at TIMESTAMP NULL DEFAULT NULL;\n\n";
echo "-- Add to seller_payments table\n";
echo "ALTER TABLE seller_payments ADD COLUMN payment_type VARCHAR(50) DEFAULT NULL;\n";
echo "ALTER TABLE seller_payments ADD COLUMN status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending';\n";
echo "ALTER TABLE seller_payments ADD COLUMN admin_notes TEXT DEFAULT NULL;\n";
echo "ALTER TABLE seller_payments ADD COLUMN verified_by INT DEFAULT NULL;\n";
echo "ALTER TABLE seller_payments ADD COLUMN verified_at TIMESTAMP NULL DEFAULT NULL;\n";
echo "</pre>\n";

echo "<p>Then test the system again!</p>\n";
?>
