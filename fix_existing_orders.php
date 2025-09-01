<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['user'])) {
    echo "<p style='color: red;'>Please login first</p>";
    echo "<p><a href='public/login.php'>Go to Login</a></p>";
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

echo "<h1>Fix Existing Orders</h1>";
echo "<p>This script will add order details to existing orders that don't have them.</p>";

try {
    // Check how many orders don't have details
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM orders o 
        LEFT JOIN order_details od ON o.id = od.order_id 
        WHERE od.id IS NULL
    ");
    $stmt->execute();
    $ordersWithoutDetails = $stmt->fetch()['count'];
    
    echo "<p><strong>Orders without details:</strong> $ordersWithoutDetails</p>";
    
    if ($ordersWithoutDetails == 0) {
        echo "<p style='color: green;'>✓ All orders already have details!</p>";
        echo "<p><a href='public/my-orders.php'>View My Orders</a></p>";
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo->beginTransaction();
        
        // Get orders without details
        $stmt = $pdo->prepare("
            SELECT o.id 
            FROM orders o 
            LEFT JOIN order_details od ON o.id = od.order_id 
            WHERE od.id IS NULL
        ");
        $stmt->execute();
        $ordersToFix = $stmt->fetchAll();
        
        $fixedCount = 0;
        foreach ($ordersToFix as $order) {
            // Add default order details
            $stmt = $pdo->prepare("
                INSERT INTO order_details (order_id, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_phone, payment_method)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order['id'],
                'Address not specified',
                'City not specified',
                'State not specified',
                'ZIP not specified',
                'Phone not specified',
                'Not specified'
            ]);
            $fixedCount++;
        }
        
        $pdo->commit();
        
        echo "<p style='color: green;'>✓ Successfully added details to $fixedCount orders!</p>";
        echo "<p><a href='public/my-orders.php'>View My Orders</a></p>";
        echo "<p><a href='public/my-orders.php?debug=1'>View My Orders with Debug</a></p>";
        
    } else {
        echo "<form method='POST'>";
        echo "<p>Click the button below to add order details to existing orders:</p>";
        echo "<button type='submit' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Fix Existing Orders</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Quick Links</h2>";
echo "<p><a href='test_database.php'>Database Check</a></p>";
echo "<p><a href='public/my-orders.php'>My Orders Page</a></p>";
echo "<p><a href='public/checkout.php'>Checkout Page</a></p>";
echo "<p><a href='public/cart.php'>Cart Page</a></p>";
?>
