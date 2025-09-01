<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user'])) {
    echo "<p style='color: red;'>No user logged in</p>";
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

echo "<h1>Test Fixed Query</h1>";
echo "<p><strong>Current User:</strong> {$user['name']} (ID: {$user['id']})</p>";

try {
    // Test the FIXED query (without LIMIT/OFFSET parameters)
    echo "<h2>Testing the FIXED query</h2>";
    
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
        LIMIT $perPage OFFSET $offset
    ";
    
    echo "<p><strong>Fixed Query:</strong></p>";
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
    echo "<p><strong>Parameters:</strong> buyer_id = {$user['id']}</p>";
    echo "<p><strong>Limit:</strong> $perPage, <strong>Offset:</strong> $offset</p>";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
    
    echo "<p style='color: green;'><strong>✓ SUCCESS!</strong> Query executed without errors.</p>";
    echo "<p><strong>Results:</strong> " . count($orders) . " orders found</p>";
    
    if (count($orders) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Order ID</th><th>Product</th><th>Seller</th><th>Status</th><th>Has Details</th><th>Shipping City</th>";
        echo "</tr>";
        foreach ($orders as $order) {
            $hasDetails = !empty($order['shipping_address']) ? "✓ Yes" : "✗ No";
            $shippingCity = $order['shipping_city'] ?? "N/A";
            echo "<tr>";
            echo "<td><strong>#{$order['id']}</strong></td>";
            echo "<td>" . htmlspecialchars($order['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($order['seller_name']) . "</td>";
            echo "<td>{$order['status']}</td>";
            echo "<td>$hasDetails</td>";
            echo "<td>" . htmlspecialchars($shippingCity) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<hr>";
        echo "<h3>Order Details Sample</h3>";
        $sampleOrder = $orders[0];
        echo "<p><strong>Sample Order #{$sampleOrder['id']}:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Product:</strong> " . htmlspecialchars($sampleOrder['product_name']) . "</li>";
        echo "<li><strong>Seller:</strong> " . htmlspecialchars($sampleOrder['seller_name']) . "</li>";
        echo "<li><strong>Status:</strong> {$sampleOrder['status']}</li>";
        echo "<li><strong>Quantity:</strong> {$sampleOrder['quantity']}</li>";
        echo "<li><strong>Total:</strong> \${$sampleOrder['total']}</li>";
        if (!empty($sampleOrder['shipping_address'])) {
            echo "<li><strong>Shipping:</strong> " . htmlspecialchars($sampleOrder['shipping_city']) . ", " . htmlspecialchars($sampleOrder['shipping_state']) . "</li>";
            echo "<li><strong>Payment:</strong> " . ucfirst(htmlspecialchars($sampleOrder['payment_method'])) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>No orders returned, but query executed successfully.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Test Results</h2>";
echo "<p style='color: green; font-weight: bold;'>If you see the orders table above, the fix worked!</p>";
echo "<p>Now you can visit your My Orders page and it should work correctly.</p>";

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<p><a href='public/my-orders.php'>Go to My Orders Page</a></p>";
echo "<p><a href='debug_orders.php'>Debug Orders</a></p>";
echo "<p><a href='show_all_orders.php'>Show All Orders</a></p>";
?>
