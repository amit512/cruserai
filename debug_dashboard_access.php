<?php
/**
 * Debug Dashboard Access
 * Test exactly what happens when a frozen seller tries to access dashboard
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/AccountManager.php';

echo "<h1>Debug Dashboard Access</h1>\n";

// Test 1: Check current session
echo "<h2>1. Current Session Status</h2>\n";
echo "<p>Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "</p>\n";
echo "<p>Session data:</p>\n";
echo "<pre>" . print_r($_SESSION, true) . "</pre>\n";

// Test 2: Simulate om prakash session (frozen seller)
echo "<h2>2. Simulating om prakash Session (Frozen Seller)</h2>\n";

// Set up a test session for om prakash
$_SESSION['user'] = [
    'id' => 4,
    'name' => 'om prakash',
    'email' => 'om12@gmail.com',
    'role' => 'seller'
];

echo "<p>✅ Set up test session for om prakash (ID: 4)</p>\n";
echo "<p>Session user ID: " . ($_SESSION['user']['id'] ?? 'NULL') . "</p>\n";
echo "<p>Session user role: " . ($_SESSION['user']['role'] ?? 'NULL') . "</p>\n";

// Test 3: Check if account is frozen
echo "<h2>3. Testing Account Freeze Check</h2>\n";

$userId = $_SESSION['user']['id'] ?? null;
if ($userId) {
    try {
        $isFrozen = AccountManager::isAccountFrozen($userId);
        echo "<p>User ID: $userId</p>\n";
        echo "<p>isAccountFrozen() result: " . ($isFrozen ? 'TRUE' : 'FALSE') . "</p>\n";
        
        if ($isFrozen) {
            echo "<p>✅ This account should be redirected!</p>\n";
            echo "<p>Redirect URL: /homecraft-php/seller/payment-upload.php</p>\n";
            
            // Test the actual redirect (comment out to avoid redirect during testing)
            // echo "<p>🔍 About to redirect...</p>\n";
            // header('Location: /homecraft-php/seller/payment-upload.php');
            // exit;
            
            echo "<p>🔍 Redirect would happen here in real scenario</p>\n";
        } else {
            echo "<p>❌ This account should NOT be redirected (but it's frozen in database)</p>\n>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error checking freeze status: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p>❌ No user ID in session</p>\n";
}

// Test 4: Check database status directly
echo "<h2>4. Database Status Check</h2>\n";

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.account_status, u.subscription_expires, u.created_at, sa.is_frozen as legacy_frozen
        FROM users u
        LEFT JOIN seller_accounts sa ON u.id = sa.seller_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();
    
    if ($userData) {
        echo "<p>Database status for user $userId:</p>\n";
        echo "<ul>\n";
        echo "<li>Name: {$userData['name']}</li>\n";
        echo "<li>Account Status: {$userData['account_status']}</li>\n";
        echo "<li>Subscription Expires: " . ($userData['subscription_expires'] ?: 'NULL') . "</li>\n";
        echo "<li>Created At: {$userData['created_at']}</li>\n";
        echo "<li>Legacy Frozen: " . ($userData['legacy_frozen'] ?: 'NULL') . "</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p>❌ No user found in database for ID: $userId</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>\n";
}

// Test 5: Test the exact logic from dashboard.php
echo "<h2>5. Testing Dashboard Logic</h2>\n";

echo "<p>Testing the exact logic from seller/dashboard.php:</p>\n";

// Check session
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    echo "<p>❌ Session check failed - user not logged in or not seller</p>\n";
} else {
    echo "<p>✅ Session check passed</p>\n";
    
    $user = $_SESSION['user'];
    echo "<p>User data: " . print_r($user, true) . "</p>\n";
    
    // Check if account is frozen
    if (AccountManager::isAccountFrozen($user['id'])) {
        echo "<p>✅ Freeze check passed - should redirect to payment-upload.php</p>\n";
        echo "<p>Redirect URL: /homecraft-php/seller/payment-upload.php</p>\n";
    } else {
        echo "<p>❌ Freeze check failed - account not frozen</p>\n>";
    }
}

echo "<h2>✅ Debug Test Completed!</h2>\n";
echo "<p>Now check:</p>\n";
echo "<ol>\n";
echo "<li>Is the session being set correctly?</li>\n";
echo "<li>Is AccountManager::isAccountFrozen() returning the expected result?</li>\n";
echo "<li>Is the redirect actually happening?</li>\n";
echo "</ol>\n";
?>
