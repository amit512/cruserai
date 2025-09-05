<?php
/**
 * Simple Redirect Test
 * Test if the redirect is working properly
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/AccountManager.php';

echo "<h1>Simple Redirect Test</h1>\n";

// Test 1: Check if we can simulate a frozen seller
echo "<h2>1. Testing with Frozen Seller</h2>\n";

// Simulate om prakash (frozen account)
$sellerId = 4;
$sellerName = 'om prakash';

echo "<p>Testing with seller: $sellerName (ID: $sellerId)</p>\n";

try {
    $isFrozen = AccountManager::isAccountFrozen($sellerId);
    echo "<p>isAccountFrozen() result: " . ($isFrozen ? 'TRUE' : 'FALSE') . "</p>\n";
    
    if ($isFrozen) {
        echo "<p>✅ This account should be redirected to payment-upload.php</p>\n";
        echo "<p>Redirect URL: /homecraft-php/seller/payment-upload.php</p>\n";
        
        // Test the actual redirect (comment out to avoid redirect during testing)
        // header('Location: /homecraft-php/seller/payment-upload.php');
        // exit;
        
        echo "<p>🔍 Redirect would happen here in real scenario</p>\n";
    } else {
        echo "<p>❌ This account should NOT be redirected (but it's frozen in database)</p>\n>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test 2: Check if we can simulate an active seller
echo "<h2>2. Testing with Active Seller</h2>\n";

// Simulate amit raut (active account)
$sellerId = 10;
$sellerName = 'amit raut';

echo "<p>Testing with seller: $sellerName (ID: $sellerId)</p>\n";

try {
    $isFrozen = AccountManager::isAccountFrozen($sellerId);
    echo "<p>isAccountFrozen() result: " . ($isFrozen ? 'TRUE' : 'FALSE') . "</p>\n";
    
    if ($isFrozen) {
        echo "<p>❌ This account should NOT be redirected (but it's active in database)</p>\n>";
    } else {
        echo "<p>✅ This account should NOT be redirected - can access seller pages</p>\n>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test 3: Check the actual redirect mechanism
echo "<h2>3. Testing Redirect Mechanism</h2>\n";

echo "<p>Testing redirect to: /homecraft-php/seller/payment-upload.php</p>\n";

// Create a test redirect (comment out to avoid actual redirect)
// echo "<p>🔍 About to redirect...</p>\n";
// header('Location: /homecraft-php/seller/payment-upload.php');
// exit;

echo "<p>🔍 Redirect test completed (redirect was commented out)</p>\n";

echo "<h2>✅ Redirect Test Completed!</h2>\n";
echo "<p>Now test the real system:</p>\n";
echo "<ol>\n";
echo "<li>Run: <code>php force_freeze_expired.php</code> to freeze expired accounts</li>\n";
echo "<li>Login as om prakash (frozen account)</li>\n";
echo "<li>Try to access: /homecraft-php/seller/dashboard.php</li>\n";
echo "<li>Should be redirected to: /homecraft-php/seller/payment-upload.php</li>\n";
echo "</ol>\n";
?>
