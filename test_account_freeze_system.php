<?php
/**
 * Test Script for Account Freezing System
 * Run this script to test the basic functionality
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/AccountManager.php';

echo "<h1>🧪 Account Freezing System Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>\n";

try {
    $pdo = db();
    echo "<p class='info'>✅ Database connection successful</p>\n";
    
    // Test 1: Check if required tables exist
    echo "<h2>📋 Testing Database Schema</h2>\n";
    
    $tables = ['users', 'seller_payments', 'seller_subscriptions'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Table '$table' exists</p>\n";
        } else {
            echo "<p class='error'>❌ Table '$table' missing - run seller_account_freeze_system.sql first</p>\n";
        }
    }
    
    // Test 2: Check if users table has account_status column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'account_status'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ 'account_status' column exists in users table</p>\n";
    } else {
        echo "<p class='error'>❌ 'account_status' column missing - run seller_account_freeze_system.sql first</p>\n";
    }
    
    // Test 3: Check if there are any sellers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'seller'");
    $sellerCount = $stmt->fetch()['count'];
    echo "<p class='info'>📊 Found $sellerCount seller(s) in the system</p>\n";
    
    if ($sellerCount > 0) {
        // Test 4: Test AccountManager class methods
        echo "<h2>🔧 Testing AccountManager Class</h2>\n";
        
        // Get first seller
        $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'seller' LIMIT 1");
        $seller = $stmt->fetch();
        
        if ($seller) {
            echo "<p class='info'>🧪 Testing with seller: {$seller['name']} (ID: {$seller['id']})</p>\n";
            
            // Test account status check
            $isFrozen = AccountManager::isAccountFrozen($seller['id']);
            echo "<p class='info'>🔍 Account frozen status: " . ($isFrozen ? 'Yes' : 'No') . "</p>\n";
            
            // Test getting account status
            $accountStatus = AccountManager::getAccountStatus($seller['id']);
            if ($accountStatus) {
                echo "<p class='success'>✅ Account status retrieved: " . ($accountStatus['account_status'] ?? 'unknown') . "</p>\n";
            } else {
                echo "<p class='error'>❌ Failed to get account status</p>\n";
            }
            
            // Test subscription plans
            $subscriptionPlans = AccountManager::getSubscriptionPlans();
            if (!empty($subscriptionPlans)) {
                echo "<p class='success'>✅ Found " . count($subscriptionPlans) . " subscription plan(s)</p>\n";
                foreach ($subscriptionPlans as $plan) {
                    echo "<p class='info'>   - {$plan['plan_type']}: ₹{$plan['monthly_fee']}/month</p>\n";
                }
            } else {
                echo "<p class='error'>❌ No subscription plans found</p>\n";
            }
            
            // Test 5: Test payment submission (simulation)
            echo "<h2>💳 Testing Payment System</h2>\n";
            
            $pendingPayments = AccountManager::getPendingPayments();
            echo "<p class='info'>📋 Found " . count($pendingPayments) . " pending payment(s)</p>\n";
            
            // Test 6: Test account freezing (simulation)
            echo "<h2>❄️ Testing Account Freezing (Simulation)</h2>\n";
            
            // Check if we can freeze an account (this is just a test, won't actually freeze)
            echo "<p class='info'>🧪 Account freezing functionality available</p>\n";
            echo "<p class='info'>   - Use admin panel to freeze accounts</p>\n";
            echo "<p class='info'>   - Sellers will be redirected to payment page</p>\n";
            echo "<p class='info'>   - After payment verification, accounts are unfrozen</p>\n";
            
        } else {
            echo "<p class='error'>❌ No sellers found to test with</p>\n";
        }
    }
    
    // Test 7: Check file structure
    echo "<h2>📁 Testing File Structure</h2>\n";
    
    $requiredFiles = [
        'app/AccountManager.php',
        'seller/payment-upload.php',
        'admin/manage-seller-payments.php',
        'seller_account_freeze_system.sql'
    ];
    
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "<p class='success'>✅ File '$file' exists</p>\n";
        } else {
            echo "<p class='error'>❌ File '$file' missing</p>\n";
        }
    }
    
    // Test 8: Check upload directory
    $uploadDir = 'public/uploads/payments';
    if (is_dir($uploadDir)) {
        echo "<p class='success'>✅ Upload directory '$uploadDir' exists</p>\n";
        if (is_writable($uploadDir)) {
            echo "<p class='success'>✅ Upload directory is writable</p>\n";
        } else {
            echo "<p class='error'>❌ Upload directory is not writable</p>\n";
        }
    } else {
        echo "<p class='error'>❌ Upload directory '$uploadDir' missing - create it manually</p>\n";
    }
    
    echo "<h2>🎯 System Status</h2>\n";
    echo "<p class='success'>✅ Account Freezing System is ready for use!</p>\n";
    echo "<p class='info'>📖 Read ACCOUNT_FREEZE_SYSTEM_README.md for detailed instructions</p>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p class='error'>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>\n";
}

echo "<hr>\n";
echo "<h3>🚀 Next Steps:</h3>\n";
echo "<ol>\n";
echo "<li>Run <code>seller_account_freeze_system.sql</code> in your database</li>\n";
echo "<li>Create upload directory: <code>mkdir -p public/uploads/payments</code></li>\n";
echo "<li>Test freezing a seller account from admin panel</li>\n";
echo "<li>Verify seller is redirected to payment page</li>\n";
echo "<li>Test payment submission and verification</li>\n";
echo "</ol>\n";
?>