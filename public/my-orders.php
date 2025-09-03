<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    verify_csrf();
    
    $orderId = (int) $_POST['order_id'];
    
    // Check if order belongs to user and can be cancelled
    $stmt = $pdo->prepare("
        SELECT o.*, p.name as product_name 
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        WHERE o.id = ? AND o.buyer_id = ? AND o.status = 'Pending'
    ");
    $stmt->execute([$orderId, $user['id']]);
    $order = $stmt->fetch();
    
    if ($order) {
        try {
            $pdo->beginTransaction();
            
            // Update order status to cancelled
            $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?");
            $stmt->execute([$orderId]);
            
            // Restore product stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$order['quantity'], $order['product_id']]);
            
            $pdo->commit();
            header('Location: my-orders.php?message=order_cancelled');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error cancelling order: " . $e->getMessage());
            header('Location: my-orders.php?error=cancel_failed');
            exit;
        }
    } else {
        header('Location: my-orders.php?error=invalid_order');
        exit;
    }
}

// Handle messages and errors from URL parameters
$message = '';
$error = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'order_cancelled':
            $message = "Order cancelled successfully. Stock has been restored.";
            break;
    }
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'cancel_failed':
            $error = "Failed to cancel order. Please try again.";
            break;
        case 'invalid_order':
            $error = "Invalid order or order cannot be cancelled.";
            break;
    }
}

// Fetch user's orders with pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    // Get total count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM orders 
        WHERE buyer_id = ?
    ");
    $stmt->execute([$user['id']]);
    $totalOrders = $stmt->fetch()['total'];
    $totalPages = ceil($totalOrders / $perPage);
    
    // Fetch orders with details - including order_details
    $stmt = $pdo->prepare("
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
    ");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
    $totalPages = 0;
}

// Group orders by date for better organization
$groupedOrders = [];
foreach ($orders as $order) {
    $date = date('Y-m-d', strtotime($order['created_at']));
    if (!isset($groupedOrders[$date])) {
        $groupedOrders[$date] = [];
    }
    $groupedOrders[$date][] = $order;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="handcraf.css">
    <link rel="stylesheet" href="startstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        
        .orders-container {
            min-height: 100vh;
        }
        
        .orders-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            text-align: center;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .orders-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .back-link {
            display: inline-block;
            color: #4CAF50;
            text-decoration: none;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        
        .orders-summary {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }
        
        .stat-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-group {
            margin-bottom: 2rem;
        }
        
        .order-date {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .order-item {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .order-id {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
        
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-content {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 2rem;
            align-items: start;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .product-details h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        
        .seller-name {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
            padding-top: 0.5rem;
            border-top: 1px solid #e0e0e0;
            font-weight: bold;
        }
        
        .order-actions {
            text-align: center;
        }
        
        .action-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            margin: 0.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .btn-secondary {
            background: #2196F3;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #1976D2;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .page-link:hover,
        .page-link.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        .empty-orders {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-orders i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-orders h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .start-shopping {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .start-shopping:hover {
            background: #45a049;
        }
        
        @media (max-width: 768px) {
            .order-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .order-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .summary-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="orders-container">
        <!-- Header -->
        <section class="orders-header">
            <div class="container">
                <h1>My Orders</h1>
                <p>Track your purchases and order history</p>
            </div>
        </section>

        <div class="container">
            <a href="buyer-dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            
            <?php if ($message): ?>
                <div class="message">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>No orders yet</h3>
                    <p>Start shopping to see your orders here!</p>
                    <a href="catalog.php" class="start-shopping">Start Shopping</a>
                </div>
            <?php else: ?>
                <!-- Debug Info (remove this after testing) -->
                <?php if (isset($_GET['debug'])): ?>
                    <div style="background: #f0f0f0; padding: 1rem; margin: 1rem 0; border-radius: 8px; font-family: monospace; font-size: 12px;">
                        <strong>Debug Info:</strong><br>
                        Total Orders: <?= $totalOrders ?><br>
                        Orders Fetched: <?= count($orders) ?><br>
                        Page: <?= $page ?><br>
                        Per Page: <?= $perPage ?><br>
                        <br>
                        <strong>Raw Order Data:</strong><br>
                        <pre><?= print_r($orders, true) ?></pre>
                    </div>
                <?php endif; ?>
                <!-- Orders Summary -->
                <div class="orders-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?= $totalOrders ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= count(array_filter($orders, fn($o) => $o['status'] === 'Pending')) ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= count(array_filter($orders, fn($o) => $o['status'] === 'Delivered')) ?></div>
                            <div class="stat-label">Delivered</div>
                        </div>
                    </div>
                </div>
                
                <!-- Orders List -->
                <?php foreach ($groupedOrders as $date => $dateOrders): ?>
                    <div class="order-group">
                        <div class="order-date">
                            <i class="fas fa-calendar"></i> <?= date('F j, Y', strtotime($date)) ?>
                        </div>
                        
                        <?php foreach ($dateOrders as $order): ?>
                            <div class="order-item">
                                <div class="order-header">
                                    <div class="order-id">Order #<?= $order['id'] ?></div>
                                    <div class="order-status status-<?= strtolower($order['status']) ?>">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </div>
                                </div>
                                
                                <div class="order-content">
                                    <!-- Product Information -->
                                    <div class="product-info">
                                        <img src="image.php?file=<?= urlencode($order['image']) ?>" 
                                             alt="<?= htmlspecialchars($order['product_name']) ?>" 
                                             class="product-image">
                                        <div class="product-details">
                                            <h4><?= htmlspecialchars($order['product_name']) ?></h4>
                                            <div class="seller-name">by <?= htmlspecialchars($order['seller_name']) ?></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Details -->
                                    <div class="order-details">
                                        <div class="detail-row">
                                            <span>Quantity:</span>
                                            <span><?= $order['quantity'] ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span>Total:</span>
                                            <span><?= format_price($order['total']) ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span>Order Date:</span>
                                            <span><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></span>
                                        </div>
                                        <?php if (!empty($order['shipping_address']) && $order['shipping_address'] !== 'Address not specified'): ?>
                                            <div class="detail-row">
                                                <span>Shipping:</span>
                                                <span><?= htmlspecialchars($order['shipping_city'] . ', ' . $order['shipping_state']) ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span>Payment:</span>
                                                <span><?= ucfirst(htmlspecialchars($order['payment_method'])) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="detail-row">
                                            <span>Order Status:</span>
                                            <span><?= htmlspecialchars($order['status']) ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Actions -->
                                    <div class="order-actions">
                                        <?php if ($order['status'] === 'Pending'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" name="cancel_order" class="action-btn btn-danger">
                                                    <i class="fas fa-times"></i> Cancel Order
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] === 'Shipped' || $order['status'] === 'Delivered'): ?>
                                            <a href="order-tracking.php?order_id=<?= $order['id'] ?>" class="action-btn btn-secondary">
                                                <i class="fas fa-truck"></i> Track Order
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] === 'Delivered'): ?>
                                            <a href="product-reviews.php?product_id=<?= (int)$order['product_id'] ?>" class="action-btn btn-primary">
                                                <i class="fas fa-star"></i> Review Product
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="#" class="action-btn btn-secondary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
