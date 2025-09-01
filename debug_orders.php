<?php
session_start();
require_once __DIR__ . '/config/config.php';

echo "<h1>Debug Orders Issue</h1>";

if (!isset($_SESSION['user'])) {
    echo "<p style='color: red;'>No user logged in</p>";
    echo "<p><a href='public/login.php'>Go to Login</a></p>";
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

echo "<h2>Current User Info</h2>";
echo "<p><strong>User ID:</strong> " . $user['id'] . "</p>";
echo "<p><strong>User Name:</strong> " . htmlspecialchars($user['name']) . "</p>";
echo "<p><strong>User Role:</strong> " . htmlspecialchars($user['role']) . "</p>";

echo "<hr>";

try {
    // Check all orders in database
    echo "<h2>All Orders in Database</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $totalOrders = $stmt->fetch()['count'];
    echo "<p><strong>Total orders in database:</strong> $totalOrders</p>";
    
    if ($totalOrders > 0) {
        $stmt = $pdo->query("
            SELECT o.*, p.name as product_name, u.name as buyer_name, u2.name as seller_name
            FROM orders o 
            JOIN products p ON o.product_id = p.id 
            JOIN users u ON o.buyer_id = u.id 
            JOIN users u2 ON o.seller_id = u2.id 
            ORDER BY o.id DESC 
            LIMIT 10
        ");
        $allOrders = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Order ID</th><th>Buyer ID</th><th>Buyer Name</th><th>Product</th><th>Status</th><th>Created</th></tr>";
        foreach ($allOrders as $order) {
            $highlight = ($order['buyer_id'] == $user['id']) ? "style='background: #d4edda;'" : "";
            echo "<tr $highlight>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['buyer_id']}</td>";
            echo "<td>" . htmlspecialchars($order['buyer_name']) . "</td>";
            echo "<td>" . htmlspecialchars($order['product_name']) . "</td>";
            echo "<td>{$order['status']}</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    
    // Check orders for current user
    echo "<h2>Orders for Current User (ID: {$user['id']})</h2>";
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE buyer_id = ?
    ");
    $stmt->execute([$user['id']]);
    $userOrders = $stmt->fetch()['count'];
    echo "<p><strong>Orders for current user:</strong> $userOrders</p>";
    
    if ($userOrders > 0) {
        $stmt = $pdo->prepare("
            SELECT o.*, p.name as product_name, u.name as seller_name,
                   od.shipping_address, od.shipping_city, od.shipping_state
            FROM orders o 
            JOIN products p ON o.product_id = p.id 
            JOIN users u ON o.seller_id = u.id 
            LEFT JOIN order_details od ON o.id = od.order_id
            WHERE o.buyer_id = ? 
            ORDER BY o.id DESC
        ");
        $stmt->execute([$user['id']]);
        $userOrderList = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Order ID</th><th>Product</th><th>Seller</th><th>Status</th><th>Has Details</th><th>Shipping City</th></tr>";
        foreach ($userOrderList as $order) {
            $hasDetails = !empty($order['shipping_address']) ? "Yes" : "No";
            $shippingCity = $order['shipping_city'] ?? "N/A";
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>" . htmlspecialchars($order['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($order['seller_name']) . "</td>";
            echo "<td>{$order['status']}</td>";
            echo "<td>$hasDetails</td>";
            echo "<td>" . htmlspecialchars($shippingCity) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    
    // Check if there are any users with different IDs
    echo "<h2>All Users in Database</h2>";
    $stmt = $pdo->query("SELECT id, name, role FROM users ORDER BY id");
    $allUsers = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Role</th><th>Is Current User</th></tr>";
    foreach ($allUsers as $dbUser) {
        $isCurrent = ($dbUser['id'] == $user['id']) ? "✓ YES" : "No";
        $highlight = ($dbUser['id'] == $user['id']) ? "style='background: #d4edda;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$dbUser['id']}</td>";
        echo "<td>" . htmlspecialchars($dbUser['name']) . "</td>";
        echo "<td>" . htmlspecialchars($dbUser['role']) . "</td>";
        echo "<td>$isCurrent</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<p><a href='fix_existing_orders.php'>Fix Existing Orders</a></p>";
echo "<p><a href='public/my-orders.php'>Go to My Orders Page</a></p>";
echo "<p><a href='public/my-orders.php?debug=1'>My Orders with Debug</a></p>";
echo "<p><a href='test_database.php'>Database Check</a></p>";
?>
