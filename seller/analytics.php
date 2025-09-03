<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
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

// Get date range for analytics
$period = $_GET['period'] ?? 'month';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Set default date range based on period
if (!$date_from || !$date_to) {
    switch ($period) {
        case 'week':
            $date_from = date('Y-m-d', strtotime('-7 days'));
            $date_to = date('Y-m-d');
            break;
        case 'month':
            $date_from = date('Y-m-d', strtotime('-30 days'));
            $date_to = date('Y-m-d');
            break;
        case 'quarter':
            $date_from = date('Y-m-d', strtotime('-90 days'));
            $date_to = date('Y-m-d');
            break;
        case 'year':
            $date_from = date('Y-m-d', strtotime('-365 days'));
            $date_to = date('Y-m-d');
            break;
        default:
            $date_from = date('Y-m-d', strtotime('-30 days'));
            $date_to = date('Y-m-d');
    }
}

// Get sales analytics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_orders,
    SUM(total) as total_revenue,
    AVG(total) as avg_order_value,
    COUNT(DISTINCT buyer_id) as unique_customers
    FROM orders 
    WHERE seller_id = ? AND created_at BETWEEN ? AND ?");
$stmt->execute([$user['id'], $date_from . ' 00:00:00', $date_to . ' 23:59:59']);
$sales_stats = $stmt->fetch();

// Get daily sales data for chart
$stmt = $pdo->prepare("SELECT 
    DATE(created_at) as date,
    COUNT(*) as orders,
    SUM(total) as revenue
    FROM orders 
    WHERE seller_id = ? AND created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date");
$stmt->execute([$user['id'], $date_from . ' 00:00:00', $date_to . ' 23:59:59']);
$daily_sales = $stmt->fetchAll();

// Get top selling products
$stmt = $pdo->prepare("SELECT 
    p.name, p.image, p.category,
    COUNT(o.id) as order_count,
    SUM(o.total) as total_revenue,
    SUM(o.quantity) as total_quantity
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.seller_id = ? AND o.created_at BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY order_count DESC
    LIMIT 5");
$stmt->execute([$user['id'], $date_from . ' 00:00:00', $date_to . ' 23:59:59']);
$top_products = $stmt->fetchAll();

// Get category performance
$stmt = $pdo->prepare("SELECT 
    p.category,
    COUNT(o.id) as order_count,
    SUM(o.total) as total_revenue
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.seller_id = ? AND o.created_at BETWEEN ? AND ?
    GROUP BY p.category
    ORDER BY total_revenue DESC");
$stmt->execute([$user['id'], $date_from . ' 00:00:00', $date_to . ' 23:59:59']);
$category_performance = $stmt->fetchAll();

// Get recent activity
$stmt = $pdo->prepare("SELECT 
    o.*, p.name as product_name, p.image, u.name as buyer_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.buyer_id = u.id
    WHERE o.seller_id = ?
    ORDER BY o.created_at DESC
    LIMIT 10");
$stmt->execute([$user['id']]);
$recent_activity = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
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
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="products.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-box mr-2"></i>Products
                </a>
                <a href="orders.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-shopping-cart mr-2"></i>Orders
                </a>
                <a href="analytics.php" class="border-b-2 border-orange-500 text-orange-600 py-4 px-1 font-medium">
                    <i class="fas fa-chart-bar mr-2"></i>Analytics
                </a>
                <a href="../public/index.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-home mr-2"></i>View Store
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Analytics & Insights</h1>
            <p class="text-gray-600 mt-2">Track your sales performance and business insights</p>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quick Period</label>
                    <select name="period" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="quarter" <?= $period === 'quarter' ? 'selected' : '' ?>>Last 90 Days</option>
                        <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Last Year</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" name="date_from" value="<?= $date_from ?>" 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" name="date_to" value="<?= $date_to ?>" 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-search mr-2"></i>Update
                </button>
            </form>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-shopping-cart text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Orders</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $sales_stats['total_orders'] ?? 0 ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-rupee-sign text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-semibold text-gray-900">Rs. <?= number_format($sales_stats['total_revenue'] ?? 0, 2) ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Avg Order Value</p>
                        <p class="text-2xl font-semibold text-gray-900">Rs. <?= number_format($sales_stats['avg_order_value'] ?? 0, 2) ?></p>
                    </div>
                </div>
            </div>

            <div class="stat-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Unique Customers</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $sales_stats['unique_customers'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Sales Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Daily Sales Trend</h3>
                <canvas id="salesChart" width="400" height="200"></canvas>
            </div>

            <!-- Category Performance -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Category Performance</h3>
                <?php if ($category_performance): ?>
                    <div class="space-y-3">
                        <?php foreach ($category_performance as $cat): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 capitalize"><?= str_replace('-', ' ', $cat['category']) ?></p>
                                    <p class="text-sm text-gray-600"><?= $cat['order_count'] ?> orders</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-orange-600">Rs. <?= number_format($cat['total_revenue'], 2) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No category data available for this period.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Selling Products</h3>
            <?php if ($top_products): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($top_products as $product): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center space-x-3 mb-3">
                                <img src="../public/uploads/<?= htmlspecialchars($product['image'] ?? 'default-product.jpg') ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     class="w-12 h-12 object-cover rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></h4>
                                    <p class="text-sm text-gray-500 capitalize"><?= str_replace('-', ' ', $product['category']) ?></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-sm">
                                <div class="text-center">
                                    <p class="text-gray-500">Orders</p>
                                    <p class="font-semibold"><?= $product['order_count'] ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-gray-500">Quantity</p>
                                    <p class="font-semibold"><?= $product['total_quantity'] ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-gray-500">Revenue</p>
                                    <p class="font-semibold text-orange-600">Rs. <?= number_format($product['total_revenue'], 2) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No product data available for this period.</p>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
            <?php if ($recent_activity): ?>
                <div class="space-y-3">
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <img src="../public/uploads/<?= htmlspecialchars($activity['image'] ?? 'default-product.jpg') ?>" 
                                     alt="<?= htmlspecialchars($activity['product_name']) ?>" 
                                     class="w-10 h-10 object-cover rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($activity['product_name']) ?></p>
                                    <p class="text-sm text-gray-600">Order #<?= $activity['id'] ?> - <?= htmlspecialchars($activity['buyer_name']) ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-orange-600">Rs. <?= number_format($activity['total'], 2) ?></p>
                                <p class="text-sm text-gray-500"><?= date('M j, Y', strtotime($activity['created_at'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No recent activity available.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesData = <?= json_encode($daily_sales) ?>;
        
        const labels = salesData.map(item => item.date);
        const revenueData = salesData.map(item => parseFloat(item.revenue));
        const orderData = salesData.map(item => parseInt(item.orders));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (Rs.)',
                    data: revenueData,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Orders',
                    data: orderData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (Rs.)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    </script>
</body>
</html>
