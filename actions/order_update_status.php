<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/AccountManager.php';
verify_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); 
    die('Forbidden');
}

// Check if account is frozen

if (AccountManager::isAccountFrozen($_SESSION['user']['id'])) {
    
    http_response_code(403);
    die('Account frozen. Please submit payment proof to continue.');
}

$order_id = (int)($_POST['order_id'] ?? 0);
$status = $_POST['status'] ?? '';


$allowed_statuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];

if ($order_id <= 0 || !in_array($status, $allowed_statuses)) {
    $_SESSION['flash'] = 'Invalid order ID or status.';
    header('Location: ../seller/orders.php'); 
    exit;
}

try {
    $pdo = db();
    
    // Verify the order belongs to this seller
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND seller_id = ?");
    $stmt->execute([$order_id, $_SESSION['user']['id']]);
    
    if (!$stmt->fetch()) {
        $_SESSION['flash'] = 'Order not found or not owned by you.';
        header('Location: ../seller/orders.php'); 
        exit;
    }
    
    // Update the order status
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?");
    $stmt->execute([$status, $order_id, $_SESSION['user']['id']]);
    
    $_SESSION['flash'] = "Order #$order_id status updated to $status successfully.";
    
} catch (Exception $e) {
    error_log("Order status update error: " . $e->getMessage());
    $_SESSION['flash'] = 'Failed to update order status. Please try again.';
}

header('Location: ../seller/orders.php');
exit;
