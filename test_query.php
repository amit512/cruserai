<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user'])) {
    echo "<p style='color: red;'>No user logged in</p>";
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

echo "<h1>Test My Orders Query</h1>";
echo "<p><strong>Current User:</strong> {$user['name']} (ID: {$user['id']})</p>";

try {
    // Test the EXACT query from my-orders.php
    echo "<h2>Testing the exact query from my-orders.php</h2>";
    
    $page = 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    $query = "
        SELECT o.*, p.name as product_name, p.image, u.name as seller_name,
               od.shipping_address, od.shipping_city, od.shipping_state, 
               od.shipping_zip, od.shipping_phone, od.payment_method
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN users u ON o.seller_id = u.id 
        LEFT JOIN order_details od ON o.id = od.order_id
        WHERE o.buyer_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    echo "<p><strong>Query:</strong></p>";
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
    echo "<p><strong>Parameters:</strong> buyer_id = {$user['id']}, limit = {$perPage}, offset = {$offset}</p>";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user['id'], $perPage, $offset]);
    $orders = $stmt->fetchAll();
    
    echo "<p><strong>Results:</strong> " . count($orders) . " orders found</p>";
    
    if (count($orders) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Order ID</th><th>Product</th><th>Seller</th><th>Status</th><th>Has Details</th></tr>";
        foreach ($orders as $order) {
            $hasDetails = !empty($order['shipping_address']) ? "Yes" : "No";
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>" . htmlspecialchars($order['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($order['seller_name']) . "</td>";
            echo "<td>{$order['status']}</td>";
            echo "<td>$hasDetails</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No orders returned by the query!</p>";
        
        // Let's debug step by step
        echo "<h3>Step-by-step debugging:</h3>";
        
        // Step 1: Check if orders exist for this user
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE buyer_id = ?");
        $stmt->execute([$user['id']]);
        $orderCount = $stmt->fetch()['count'];
        echo "<p>1. Orders for user {$user['id']}: $orderCount</p>";
        
        // Step 2: Check if products exist
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $productCount = $stmt->fetch()['count'];
        echo "<p>2. Total products: $productCount</p>";
        
        // Step 3: Check if users table has the right structure
        $stmt = $pdo->query("DESCRIBE users");
        $userColumns = $stmt->fetchAll();
        echo "<p>3. Users table columns:</p><ul>";
        foreach ($userColumns as $col) {
            echo "<li>{$col['Field']} - {$col['Type']}</li>";
        }
        echo "</ul>";
        
        // Step 4: Check if orders table has the right structure
        $stmt = $pdo->query("DESCRIBE orders");
        $orderColumns = $stmt->fetchAll();
        echo "<p>4. Orders table columns:</p><ul>";
        foreach ($orderColumns as $col) {
            echo "<li>{$col['Field']} - {$col['Type']}</li>";
        }
        echo "</ul>";
        
        // Step 5: Check if products table has the right structure
        $stmt = $pdo->query("DESCRIBE products");
        $productColumns = $stmt->fetchAll();
        echo "<p>5. Products table columns:</p><ul>";
        foreach ($productColumns as $col) {
            echo "<li>{$col['Field']} - {$col['Type']}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Error details:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<p><a href='public/my-orders.php'>Go to My Orders Page</a></p>";
echo "<p><a href='debug_orders.php'>Debug Orders</a></p>";
echo "<p><a href='show_all_orders.php'>Show All Orders</a></p>";
?>
