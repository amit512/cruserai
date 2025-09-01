<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: catalog.php');
    exit;
}

$pdo = db();

// Fetch order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, p.name as product_name, p.image, u.name as seller_name
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN users u ON o.seller_id = u.id
        WHERE o.buyer_id = ? AND o.id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user['id'], $orderId]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    $orders = [];
}

if (empty($orders)) {
    header('Location: catalog.php');
    exit;
}

$totalAmount = 0;
foreach ($orders as $order) {
    $totalAmount += $order['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - <?= SITE_NAME ?></title>
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
        
        .success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
        }
        
        .success-title {
            color: #333;
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .success-message {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .order-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .order-details h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 0.5rem;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .order-item-seller {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-item-price {
            color: #4CAF50;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .order-summary {
            display: flex;
            justify-content: space-between;
            font-size: 1.1rem;
            font-weight: bold;
            color: #4CAF50;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #4CAF50;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #2196F3;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #1976D2;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: #4CAF50;
            border: 2px solid #4CAF50;
        }
        
        .btn-outline:hover {
            background: #4CAF50;
            color: white;
        }
        
        .order-number {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .next-steps {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .next-steps h4 {
            color: #856404;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .next-steps ul {
            margin: 0;
            padding-left: 1.5rem;
            color: #856404;
        }
        
        .next-steps li {
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .success-card {
                padding: 2rem 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 class="success-title">Order Confirmed!</h1>
            <p class="success-message">
                Thank you for your purchase! Your order has been successfully placed and is being processed.
            </p>
            
            <div class="order-number">
                Order #<?= $orderId ?>
            </div>
            
            <div class="order-details">
                <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                
                <?php foreach ($orders as $order): ?>
                    <div class="order-item">
                        <img src="image.php?file=<?= urlencode($order['image']) ?>" 
                             alt="<?= htmlspecialchars($order['product_name']) ?>" 
                             class="order-item-image">
                        
                        <div class="order-item-details">
                            <div class="order-item-name"><?= htmlspecialchars($order['product_name']) ?></div>
                            <div class="order-item-seller">by <?= htmlspecialchars($order['seller_name']) ?></div>
                        </div>
                        
                        <div class="order-item-price">
                            <?= format_price($order['total']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-summary">
                    <span>Total Amount</span>
                    <span><?= format_price($totalAmount) ?></span>
                </div>
            </div>
            
            <div class="next-steps">
                <h4><i class="fas fa-info-circle"></i> What happens next?</h4>
                <ul>
                    <li>You'll receive an email confirmation shortly</li>
                    <li>The seller will process your order within 24-48 hours</li>
                    <li>You'll be notified when your order ships</li>
                    <li>Track your order status in your dashboard</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="my-orders.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> View Orders
                </a>
                
                <a href="catalog.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
                
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Go Home
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
