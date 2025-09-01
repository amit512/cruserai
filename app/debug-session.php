<?php
session_start();
require_once __DIR__ . '/../config/config.php';

echo "<h1>Session Debug</h1>";

// Check if user is logged in
if (isset($_SESSION['user'])) {
    echo "<h2>✅ User is logged in</h2>";
    echo "<p><strong>User ID:</strong> {$_SESSION['user']['id']}</p>";
    echo "<p><strong>Name:</strong> {$_SESSION['user']['name']}</p>";
    echo "<p><strong>Email:</strong> {$_SESSION['user']['email']}</p>";
    echo "<p><strong>Role:</strong> {$_SESSION['user']['role']}</p>";
    
    // Check if user is a seller
    if ($_SESSION['user']['role'] === 'seller') {
        echo "<p>✅ User is a seller</p>";
        
        // Check if user has products
        try {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ?");
            $stmt->execute([$_SESSION['user']['id']]);
            $result = $stmt->fetch();
            
            echo "<p><strong>Products owned:</strong> {$result['count']}</p>";
            
            if ($result['count'] > 0) {
                // Show first product for testing
                $stmt = $pdo->prepare("SELECT id, name, is_active FROM products WHERE seller_id = ? LIMIT 1");
                $stmt->execute([$_SESSION['user']['id']]);
                $product = $stmt->fetch();
                
                echo "<h3>Test Product:</h3>";
                echo "<p><strong>ID:</strong> {$product['id']}</p>";
                echo "<p><strong>Name:</strong> {$product['name']}</p>";
                echo "<p><strong>Current Status:</strong> " . ($product['is_active'] ? 'Active' : 'Inactive') . "</p>";
                
                // Test toggle links
                $action = $product['is_active'] ? 'deactivate' : 'activate';
                $toggleUrl = "toggle-status.php?id={$product['id']}&action={$action}";
                
                echo "<h3>Test Toggle:</h3>";
                echo "<p><a href='{$toggleUrl}' target='_blank'>Test {$action} for product {$product['id']}</a></p>";
                echo "<p><a href='test-toggle.php?id={$product['id']}&action={$action}' target='_blank'>Debug toggle for product {$product['id']}</a></p>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>❌ User is not a seller (role: {$_SESSION['user']['role']})</p>";
    }
    
} else {
    echo "<h2>❌ User is NOT logged in</h2>";
    echo "<p>No session found. You need to log in first.</p>";
    echo "<p><a href='../public/login.php'>Go to Login</a></p>";
}

// Show all session data
echo "<h2>All Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Show server info
echo "<h2>Server Information:</h2>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";

echo "<hr>";
echo "<p><a href='manage-products.php'>Go to Manage Products</a></p>";
echo "<p><a href='../public/seller-dashboard.php'>Go to Seller Dashboard</a></p>";
echo "<p><a href='../public/logout.php'>Logout</a></p>";
?>