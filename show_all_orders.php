<?php
require_once __DIR__ . '/config/config.php';

echo "<h1>All Orders in Database</h1>";

try {
    $pdo = db();
    
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $totalOrders = $stmt->fetch()['count'];
    echo "<p><strong>Total orders in database:</strong> $totalOrders</p>";
    
    if ($totalOrders == 0) {
        echo "<p>No orders found in database.</p>";
        exit;
    }
    
    // Get all orders with details
    $stmt = $pdo->query("
        SELECT o.*, p.name as product_name, p.image, 
               u1.name as buyer_name, u1.id as buyer_id,
               u2.name as seller_name, u2.id as seller_id,
               od.shipping_address, od.shipping_city, od.shipping_state, 
               od.shipping_zip, od.shipping_phone, od.payment_method
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN users u1 ON o.buyer_id = u1.id 
        JOIN users u2 ON o.seller_id = u2.id 
        LEFT JOIN order_details od ON o.id = od.order_id
        ORDER BY o.id DESC
    ");
    $orders = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Order ID</th><th>Buyer</th><th>Product</th><th>Quantity</th><th>Total</th><th>Status</th><th>Created</th><th>Has Details</th>";
    echo "</tr>";
    
    foreach ($orders as $order) {
        $hasDetails = !empty($order['shipping_address']) ? "✓ Yes" : "✗ No";
        $rowColor = !empty($order['shipping_address']) ? "#d4edda" : "#f8d7da";
        
        echo "<tr style='background: $rowColor;'>";
        echo "<td><strong>#{$order['id']}</strong></td>";
        echo "<td>{$order['buyer_name']} (ID: {$order['buyer_id']})</td>";
        echo "<td>" . htmlspecialchars($order['product_name']) . "</td>";
        echo "<td>{$order['quantity']}</td>";
        echo "<td>\${$order['total']}</td>";
        echo "<td>{$order['status']}</td>";
        echo "<td>" . date('M j, Y', strtotime($order['created_at'])) . "</td>";
        echo "<td>$hasDetails</td>";
        echo "</tr>";
        
        // Show order details if they exist
        if (!empty($order['shipping_address'])) {
            echo "<tr style='background: #e8f5e8;'>";
            echo "<td colspan='8' style='padding: 10px;'>";
            echo "<strong>Shipping Details:</strong> ";
            echo htmlspecialchars($order['shipping_address']) . ", ";
            echo htmlspecialchars($order['shipping_city']) . ", ";
            echo htmlspecialchars($order['shipping_state']) . " ";
            echo htmlspecialchars($order['shipping_zip']) . " | ";
            echo "Phone: " . htmlspecialchars($order['shipping_phone']) . " | ";
            echo "Payment: " . ucfirst(htmlspecialchars($order['payment_method']));
            echo "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<hr>";
    
    // Summary by user
    echo "<h2>Orders by User</h2>";
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.role, COUNT(o.id) as order_count
        FROM users u
        LEFT JOIN orders o ON u.id = o.buyer_id
        GROUP BY u.id, u.name, u.role
        ORDER BY order_count DESC
    ");
    $userStats = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>User ID</th><th>Name</th><th>Role</th><th>Order Count</th>";
    echo "</tr>";
    
    foreach ($userStats as $user) {
        $orderCount = $user['order_count'] ?? 0;
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>$orderCount</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<p><a href='debug_orders.php'>Debug Orders for Current User</a></p>";
echo "<p><a href='fix_existing_orders.php'>Fix Existing Orders</a></p>";
echo "<p><a href='public/my-orders.php'>Go to My Orders Page</a></p>";
echo "<p><a href='test_database.php'>Database Check</a></p>";
?>
