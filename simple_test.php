<?php
/**
 * Simple Test Script
 * Basic functionality check
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';

echo "<h1>Simple System Test</h1>\n";

try {
    // Test 1: Database connection
    echo "<h2>1. Database Connection Test</h2>\n";
    $pdo = db();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Test 2: Check sellers table
    echo "<h2>2. Sellers Table Test</h2>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'seller'");
    $result = $stmt->fetch();
    echo "<p>Total sellers: " . $result['count'] . "</p>\n";
    
    // Test 3: Check one seller's data
    echo "<h2>3. Sample Seller Data</h2>\n";
    $stmt = $pdo->query("SELECT id, name, email, account_status, subscription_expires FROM users WHERE role = 'seller' LIMIT 1");
    $seller = $stmt->fetch();
    
    if ($seller) {
        echo "<p>Sample seller:</p>\n";
        echo "<ul>\n";
        echo "<li>ID: {$seller['id']}</li>\n";
        echo "<li>Name: {$seller['name']}</li>\n";
        echo "<li>Email: {$seller['email']}</li>\n";
        echo "<li>Status: {$seller['account_status']}</li>\n";
        echo "<li>Sub Expires: " . ($seller['subscription_expires'] ?: 'NULL') . "</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p>⚠️ No sellers found</p>\n";
    }
    
    // Test 4: Check if AccountManager can be loaded
    echo "<h2>4. AccountManager Test</h2>\n";
    
    try {
        require_once __DIR__ . '/app/AccountManager.php';
        echo "<p>✅ AccountManager loaded successfully</p>\n";
        
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
        }
        
    } catch (Exception $e) {
        echo "<p>❌ AccountManager error: " . $e->getMessage() . "</p>\n";
        echo "<p>Stack trace:</p>\n";
        echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    }
    
    echo "<h2>✅ Simple Test Completed!</h2>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Fatal error: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
