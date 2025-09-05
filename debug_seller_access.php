<?php
/**
 * Debug Script: Test Seller Access and Freeze Check
 * Run this to see what's happening with seller access
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/AccountManager.php';

echo "<h1>Debug Seller Access and Freeze Check</h1>\n";

try {
    $pdo = db();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Test 1: Check all sellers and their freeze status
    echo "<h2>1. All Sellers and Freeze Status</h2>\n";
    
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.role,
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
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Sub Expires</th><th>Created</th><th>Legacy Frozen</th><th>Should Freeze?</th></tr>\n";
        
        foreach ($sellers as $seller) {
            $subExpires = $seller['subscription_expires'] ?: 'NULL';
            $status = $seller['account_status'] ?: 'NULL';
            $created = $seller['created_at'] ?: 'NULL';
            $legacyFrozen = $seller['legacy_frozen'] ?: 'NULL';
            
            // Calculate if trial should be expired
            $shouldFreeze = 'NO';
            if ($seller['created_at'] && $seller['subscription_expires']) {
                try {
                    $createdDate = new DateTime($seller['created_at']);
                    $trialEnd = (clone $createdDate)->modify('+3 days');
                    $today = new DateTime('today');
                    if ($today > $trialEnd) {
                        $shouldFreeze = 'YES - Trial Expired';
                    }
                } catch (Exception $e) {
                    $shouldFreeze = 'ERROR: ' . $e->getMessage();
                }
            }
            
            echo "<tr>";
            echo "<td>{$seller['id']}</td>";
            echo "<td>{$seller['name']}</td>";
            echo "<td>{$seller['email']}</td>";
            echo "<td>$status</td>";
            echo "<td>$subExpires</td>";
            echo "<td>$created</td>";
            echo "<td>$legacyFrozen</td>";
            echo "<td>$shouldFreeze</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Test 2: Test AccountManager freeze check for each seller
    echo "<h2>2. AccountManager Freeze Check Test</h2>\n";
    
    if (!empty($sellers)) {
        foreach ($sellers as $seller) {
            $sellerId = $seller['id'];
            
            try {
                $isFrozen = AccountManager::isAccountFrozen($sellerId);
                $status = AccountManager::getAccountStatus($sellerId);
                
                echo "<p><strong>{$seller['name']}</strong> (ID: $sellerId):</p>\n";
                echo "<ul>\n";
                echo "<li>Database account_status: {$seller['account_status']}</li>\n";
                echo "<li>AccountManager isAccountFrozen(): " . ($isFrozen ? 'TRUE' : 'FALSE') . "</li>\n";
                echo "<li>AccountManager getAccountStatus(): " . ($status ? 'Working' : 'NULL') . "</li>\n";
                
                if ($status) {
                    echo "<li>AccountManager account_status: {$status['account_status']}</li>\n";
                    echo "<li>AccountManager frozen_reason: {$status['frozen_reason']}</li>\n";
                }
                
                echo "</ul>\n";
                
            } catch (Exception $e) {
                echo "<p>❌ Error testing seller $sellerId: " . $e->getMessage() . "</p>\n";
            }
        }
    }
    
    // Test 3: Check if there are any direct access points
    echo "<h2>3. Checking for Direct Access Points</h2>\n";
    
    $sellerFiles = [
        'seller/dashboard.php',
        'seller/products.php',
        'seller/orders.php',
        'seller/add-product.php',
        'seller/analytics.php'
    ];
    
    foreach ($sellerFiles as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, 'AccountManager::isAccountFrozen') !== false) {
                echo "<p>✅ $file - Has freeze check</p>\n";
            } else {
                echo "<p>❌ $file - Missing freeze check</p>\n";
            }
        } else {
            echo "<p>⚠️ $file - File not found</p>\n";
        }
    }
    
    // Test 4: Check session handling
    echo "<h2>4. Session and Access Test</h2>\n";
    
    echo "<p>Current session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "</p>\n";
    
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "<p>Session data:</p>\n";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>\n";
    }
    
    // Test 5: Simulate what happens when a frozen seller tries to access dashboard
    echo "<h2>5. Simulating Frozen Seller Access</h2>\n";
    
    if (!empty($sellers)) {
        $frozenSeller = null;
        foreach ($sellers as $seller) {
            if ($seller['account_status'] === 'frozen') {
                $frozenSeller = $seller;
                break;
            }
        }
        
        if ($frozenSeller) {
            echo "<p>Testing with frozen seller: {$frozenSeller['name']} (ID: {$frozenSeller['id']})</p>\n";
            
            $isFrozen = AccountManager::isAccountFrozen($frozenSeller['id']);
            echo "<p>isAccountFrozen() result: " . ($isFrozen ? 'TRUE' : 'FALSE') . "</p>\n";
            
            if ($isFrozen) {
                echo "<p>✅ This seller should be redirected to payment-upload.php</p>\n";
                echo "<p>Redirect URL: seller/payment-upload.php</p>\n";
            } else {
                echo "<p>❌ This seller should NOT be redirected (but they are frozen in database)</p>\n";
            }
        } else {
            echo "<p>⚠️ No frozen sellers found to test with</p>\n";
        }
    }
    
    echo "<h2>✅ Debug Test Completed!</h2>\n";
    echo "<p>If the freeze check is working but sellers can still access pages, check:</p>\n";
    echo "<ol>\n";
    echo "<li>Are they accessing through the correct URL path?</li>\n";
    echo "<li>Is there a session issue?</li>\n";
    echo "<li>Are they bypassing the PHP files somehow?</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Fatal error: " . $e->getMessage() . "</p>\n";
}
?>
