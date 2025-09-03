<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';
require_once __DIR__ . '/../app/AccountManager.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); 
    echo "<p>Forbidden.</p>"; 
    exit;
}

$user = $_SESSION['user'];

// Check if account is frozen
if (AccountManager::isAccountFrozen($user['id'])) {
    header('Location: payment-upload.php');
    exit;
}

$pdo = db();

// Fetch comprehensive stats
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_products FROM products WHERE seller_id=?");
$stmt->execute([$user['id']]);
$totalProducts = $stmt->fetch()['total_products'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_orders FROM orders WHERE seller_id=?");
$stmt->execute([$user['id']]);
$totalOrders = $stmt->fetch()['total_orders'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) AS pending_orders FROM orders WHERE seller_id=? AND status='Pending'");
$stmt->execute([$user['id']]);
$pendingOrders = $stmt->fetch()['pending_orders'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) AS active_products FROM products WHERE seller_id=? AND is_active=1");
$stmt->execute([$user['id']]);
$activeProducts = $stmt->fetch()['active_products'] ?? 0;

// Fetch recent orders with buyer details
$stmt = $pdo->prepare("SELECT o.*, u.name AS buyer_name, p.name AS product_name, p.image 
                       FROM orders o
                       JOIN users u ON o.buyer_id = u.id
                       JOIN products p ON o.product_id = p.id
                       WHERE o.seller_id = ?
                       ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$recentOrders = $stmt->fetchAll();

// Fetch low stock products
$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id=? AND stock <= 5 AND is_active=1 ORDER BY stock ASC LIMIT 5");
$stmt->execute([$user['id']]);
$lowStockProducts = $stmt->fetchAll();

// Fetch products by category for quick overview
$stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM products WHERE seller_id=? GROUP BY category ORDER BY count DESC");
$stmt->execute([$user['id']]);
$categoryStats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .order-status { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-shipped { background: #dbeafe; color: #1e40af; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <span class="text-orange-600">Hand</span>Craft
                    </h1>
                    <span class="ml-4 px-3 py-1 bg-orange-100 text-orange-800 text-sm font-medium rounded-full">
                        Seller Dashboard
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?= htmlspecialchars($user['name']) ?></span>
                    <a href="../public/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="border-b-2 border-orange-500 text-orange-600 py-4 px-1 font-medium">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="products.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-box mr-2"></i>Products
                </a>
                <a href="orders.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-shopping-cart mr-2"></i>Orders
                </a>
                <a href="analytics.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-chart-bar mr-2"></i>Analytics
                </a>
                <a href="payment-upload.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-credit-card mr-2"></i>Payment
                </a>
                <a href="../public/index.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-home mr-2"></i>View Store
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-box text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Products</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $totalProducts ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Products</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $activeProducts ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-shopping-cart text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Orders</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $totalOrders ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-hourglass-half text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending Orders</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $pendingOrders ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="flex flex-wrap gap-4">
                <a href="add-product.php" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition flex items-center">
                    <i class="fas fa-plus mr-2"></i>Add New Product
                </a>
                <a href="orders.php?status=pending" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition flex items-center">
                    <i class="fas fa-eye mr-2"></i>View Pending Orders
                </a>
                <a href="products.php?filter=low-stock" class="bg-yellow-600 text-white px-6 py-3 rounded-lg hover:bg-yellow-700 transition flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Low Stock Alert
                </a>
                <a href="analytics.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition flex items-center">
                    <i class="fas fa-chart-line mr-2"></i>View Analytics
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Orders -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Orders</h3>
                </div>
                <div class="p-6">
                    <?php if ($recentOrders): ?>
                        <div class="space-y-4">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <img src="../public/uploads/<?= htmlspecialchars($order['image']) ?>" 
                                             alt="<?= htmlspecialchars($order['product_name']) ?>" 
                                             class="w-12 h-12 object-cover rounded-lg">
                                        <div>
                                            <p class="font-medium text-gray-900"><?= htmlspecialchars($order['product_name']) ?></p>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($order['buyer_name']) ?></p>
                                            <p class="text-sm text-gray-500">Qty: <?= $order['quantity'] ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900">Rs. <?= number_format($order['total'], 2) ?></p>
                                        <span class="order-status status-<?= strtolower($order['status']) ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="orders.php" class="text-orange-600 hover:text-orange-700 font-medium">
                                View All Orders <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No orders yet. Start selling to see orders here!</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Low Stock Alert</h3>
                </div>
                <div class="p-6">
                    <?php if ($lowStockProducts): ?>
                        <div class="space-y-3">
                            <?php foreach ($lowStockProducts as $product): ?>
                                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                                    <div class="flex items-center space-x-3">
                                        <img src="../public/uploads/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="w-10 h-10 object-cover rounded-lg">
                                        <div>
                                            <p class="font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></p>
                                            <p class="text-sm text-red-600">Stock: <?= $product['stock'] ?></p>
                                        </div>
                                    </div>
                                    <a href="edit-product.php?id=<?= $product['id'] ?>" 
                                       class="text-orange-600 hover:text-orange-700 text-sm font-medium">
                                        Update Stock
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">All products have sufficient stock! 🎉</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Category Overview -->
        <?php if ($categoryStats): ?>
        <div class="bg-white rounded-lg shadow mt-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Products by Category</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <?php foreach ($categoryStats as $cat): ?>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <p class="text-2xl font-bold text-orange-600"><?= $cat['count'] ?></p>
                            <p class="text-sm text-gray-600 capitalize"><?= str_replace('-', ' ', $cat['category']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on page load
            const stats = document.querySelectorAll('.stat-card');
            stats.forEach((stat, index) => {
                setTimeout(() => {
                    stat.style.opacity = '1';
                    stat.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
