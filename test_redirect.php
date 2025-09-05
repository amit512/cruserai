<?php
/**
 * Test Redirect Functionality
 * This script simulates what happens when a frozen seller tries to access a page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/AccountManager.php';

echo "<h1>Testing Redirect Functionality</h1>\n";

// Simulate a frozen seller session
$_SESSION['user'] = [
    'id' => 4, // om prakash - frozen account
    'name' => 'om prakash',
    'email' => 'om12@gmail.com',
    'role' => 'seller'
];

echo "<p>Simulating session for user: {$_SESSION['user']['name']} (ID: {$_SESSION['user']['id']})</p>\n";

// Test the freeze check
try {
    $isFrozen = AccountManager::isAccountFrozen($_SESSION['user']['id']);
    echo "<p>AccountManager::isAccountFrozen() result: " . ($isFrozen ? 'TRUE' : 'FALSE') . "</p>\n";
    
    if ($isFrozen) {
        echo "<p>✅ This account should be redirected to payment-upload.php</p>\n";
        echo "<p>Redirect URL: /homecraft-php/seller/payment-upload.php</p>\n";
        
        // Test the redirect (comment out to avoid actual redirect during testing)
        // header('Location: /homecraft-php/seller/payment-upload.php');
        // exit;
        
        echo "<p>🔍 Redirect would happen here in real scenario</p>\n";
    } else {
        echo "<p>❌ This account should NOT be redirected (but it's frozen in database)</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test with a different seller
echo "<h2>Testing with Different Seller</h2>\n";

$_SESSION['user']['id'] = 10; // amit raut - active account
echo "<p>Now testing with user: {$_SESSION['user']['name']} (ID: {$_SESSION['user']['id']})</p>\n";

try {
    $isFrozen = AccountManager::isAccountFrozen($_SESSION['user']['id']);
    echo "<p>AccountManager::isAccountFrozen() result: " . ($isFrozen ? 'TRUE' : 'FALSE') . "</p>\n";
    
    if ($isFrozen) {
        echo "<p>❌ This account should NOT be redirected (but it's active in database)</p>\n>";
    } else {
        echo "<p>✅ This account should NOT be redirected - can access seller pages</p>\n>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

echo "<h2>✅ Test Completed!</h2>\n";
echo "<p>Now try accessing seller pages as a frozen seller to see if redirect works:</p>\n";
echo "<ol>\n";
echo "<li>Login as om prakash (frozen account)</li>\n";
echo "<li>Try to access: /homecraft-php/seller/dashboard.php</li>\n";
echo "<li>Should be redirected to: /homecraft-php/seller/payment-upload.php</li>\n";
echo "</ol>\n";
?>
