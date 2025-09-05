<?php
/**
 * Simple Test Script for Subscription System
 * Run this to check if everything is working
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';

echo "<h1>HomeCraft Subscription System Test</h1>\n";

try {
    $pdo = db();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Test 1: Check seller status
    echo "<h2>1. Current Seller Status</h2>\n";
    
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
    } else {
        echo "<p>⚠️ No sellers found</p>\n";
    }
    
    // Test 2: Check trial calculation
    echo "<h2>2. Trial Period Calculation</h2>\n";
    
    if (!empty($sellers)) {
        foreach ($sellers as $seller) {
            if ($seller['created_at'] && $seller['subscription_expires']) {
                try {
                    $created = new DateTime($seller['created_at']);
                    $trialEnd = (clone $created)->modify('+3 days');
                    $today = new DateTime('today');
                    $trialDaysLeft = max(0, $today->diff($trialEnd)->days);
                    $isTrialExpired = $today > $trialEnd;
                    
                    echo "<p><strong>{$seller['name']}</strong>:</p>\n";
                    echo "<ul>\n";
                    echo "<li>Created: " . $created->format('Y-m-d H:i:s') . "</li>\n";
                    echo "<li>Trial ends: " . $trialEnd->format('Y-m-d H:i:s') . "</li>\n";
                    echo "<li>Today: " . $today->format('Y-m-d H:i:s') . "</li>\n";
                    echo "<li>Trial days left: $trialDaysLeft</li>\n";
                    echo "<li>Trial expired: " . ($isTrialExpired ? 'YES' : 'NO') . "</li>\n";
                    echo "<li>Current subscription expires: {$seller['subscription_expires']}</li>\n";
                    echo "</ul>\n";
                    
                } catch (Exception $e) {
                    echo "<p>❌ Date calculation error for {$seller['name']}: " . $e->getMessage() . "</p>\n";
                }
            }
        }
    }
    
    // Test 3: Check if AccountManager can be loaded
    echo "<h2>3. AccountManager Test</h2>\n";
    
    try {
        require_once __DIR__ . '/app/AccountManager.php';
        echo "<p>✅ AccountManager loaded successfully</p>\n";
        
        if (!empty($sellers)) {
            $testSeller = $sellers[0];
            $sellerId = $testSeller['id'];
            
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
            
        }
        
    } catch (Exception $e) {
        echo "<p>❌ AccountManager error: " . $e->getMessage() . "</p>\n";
    }
    
    // Test 4: Check payment system
    echo "<h2>4. Payment System Check</h2>\n";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM seller_payments");
        $result = $stmt->fetch();
        echo "<p>Total payments: " . $result['count'] . "</p>\n";
        
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT * FROM seller_payments ORDER BY created_at DESC LIMIT 3");
            $payments = $stmt->fetchAll();
            
            echo "<p>Recent payments:</p>\n";
            foreach ($payments as $payment) {
                echo "<p>- ID: {$payment['id']}, Seller: {$payment['seller_id']}, Type: {$payment['payment_type']}, Status: {$payment['status']}</p>\n";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Payment system error: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<h2>✅ Test Completed!</h2>\n";
    echo "<p>If everything looks good, try:</p>\n";
    echo "<ol>\n";
    echo "<li>Login as a seller to see trial status</li>\n";
    echo "<li>Run the cron job: <code>php cron_auto_freeze.php</code></li>\n";
    echo "<li>Check admin billing page</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Fatal error: " . $e->getMessage() . "</p>\n";
}
?>
