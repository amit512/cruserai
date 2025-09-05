<?php
/**
 * Force Freeze Expired Accounts
 * Run this to freeze all sellers with expired trials
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/AccountManager.php';

echo "<h1>Force Freeze Expired Accounts</h1>\n";

try {
    $pdo = db();
    echo "<p>✅ Database connection successful</p>\n";
    
    // First, let's see what accounts should be frozen
    echo "<h2>1. Checking Accounts That Should Be Frozen</h2>\n";
    
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.account_status,
            u.subscription_expires,
            u.created_at,
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
    
    // Now force freeze expired accounts
    echo "<h2>2. Force Freezing Expired Accounts</h2>\n";
    
    $frozenCount = AccountManager::forceFreezeExpiredAccounts();
    echo "<p>✅ Successfully frozen $frozenCount accounts</p>\n";
    
    // Show updated status
    echo "<h2>3. Updated Account Status</h2>\n";
    
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.account_status,
            u.subscription_expires,
            u.created_at,
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
    
    // Test the freeze check
    echo "<h2>4. Testing Freeze Check</h2>\n";
    
    if (!empty($sellers)) {
        foreach ($sellers as $seller) {
            $sellerId = $seller['id'];
            $isFrozen = AccountManager::isAccountFrozen($sellerId);
            
            echo "<p><strong>{$seller['name']}</strong> (ID: $sellerId): isAccountFrozen() = " . ($isFrozen ? 'TRUE' : 'FALSE') . "</p>\n";
        }
    }
    
    echo "<h2>✅ Force Freeze Completed!</h2>\n";
    echo "<p>Now test the system:</p>\n";
    echo "<ol>\n";
    echo "<li>Try to access seller pages as a frozen seller</li>\n";
    echo "<li>Should be redirected to payment-upload.php</li>\n";
    echo "<li>Run: <code>php debug_seller_access.php</code> to verify</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Fatal error: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
