<?php
session_start();
require_once __DIR__ . '/../config/config.php';

echo "<h1>Toggle Status Test</h1>";

// Check session
echo "<h2>Session Information:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check user
if (isset($_SESSION['user'])) {
    echo "<h2>User Information:</h2>";
    echo "<pre>";
    print_r($_SESSION['user']);
    echo "</pre>";
} else {
    echo "<p>No user session found!</p>";
}

// Check database connection
try {
    $pdo = db();
    echo "<h2>Database Connection:</h2>";
    echo "<p>✅ Database connected successfully</p>";
    
    // Check products table
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    echo "<h3>Products Table Structure:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Check if user has products
    if (isset($_SESSION['user']['id'])) {
        $stmt = $pdo->prepare("SELECT id, name, is_active FROM products WHERE seller_id = ? LIMIT 5");
        $stmt->execute([$_SESSION['user']['id']]);
        $products = $stmt->fetchAll();
        
        echo "<h3>User Products:</h3>";
        echo "<pre>";
        print_r($products);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h2>Database Error:</h2>";
    echo "<p>❌ " . $e->getMessage() . "</p>";
}

// Check GET parameters
echo "<h2>GET Parameters:</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Test toggle functionality
if (isset($_GET['id']) && isset($_GET['action'])) {
    echo "<h2>Testing Toggle:</h2>";
    
    $productId = (int)$_GET['id'];
    $action = $_GET['action'];
    
    echo "<p>Product ID: $productId</p>";
    echo "<p>Action: $action</p>";
    
    try {
        $pdo = db();
        
        // Check current status
        $stmt = $pdo->prepare("SELECT id, name, is_active FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$productId, $_SESSION['user']['id']]);
        $product = $stmt->fetch();
        
        if ($product) {
            echo "<p>Current Status: " . ($product['is_active'] ? 'Active' : 'Inactive') . "</p>";
            
            $newStatus = ($action === 'activate') ? 1 : 0;
            echo "<p>New Status: " . ($newStatus ? 'Active' : 'Inactive') . "</p>";
            
            // Update status
            $stmt = $pdo->prepare("UPDATE products SET is_active = ? WHERE id = ? AND seller_id = ?");
            $result = $stmt->execute([$newStatus, $productId, $_SESSION['user']['id']]);
            
            echo "<p>Update Result: " . ($result ? 'Success' : 'Failed') . "</p>";
            echo "<p>Rows Affected: " . $stmt->rowCount() . "</p>";
            
            if ($stmt->rowCount() > 0) {
                echo "<p>✅ Status updated successfully!</p>";
                
                // Verify the change
                $stmt = $pdo->prepare("SELECT is_active FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $newProduct = $stmt->fetch();
                echo "<p>New Status in DB: " . ($newProduct['is_active'] ? 'Active' : 'Inactive') . "</p>";
            } else {
                echo "<p>❌ No rows were affected</p>";
            }
        } else {
            echo "<p>❌ Product not found or not owned by user</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h2>Test Links:</h2>";
echo "<p><a href='test-toggle.php?id=1&action=activate'>Test Activate Product ID 1</a></p>";
echo "<p><a href='test-toggle.php?id=1&action=deactivate'>Test Deactivate Product ID 1</a></p>";
echo "<p><a href='test-toggle.php'>Clear Test</a></p>";
echo "<p><a href='manage-products.php'>Back to Manage Products</a></p>";
?>