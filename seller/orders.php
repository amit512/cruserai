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

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query based on filters
$whereConditions = ['o.seller_id = ?'];
$params = [$user['id']];

if ($status && $status !== 'all') {
    $whereConditions[] = 'o.status = ?';
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = '(p.name LIKE ? OR u.name LIKE ? OR o.id LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_from) {
    $whereConditions[] = 'DATE(o.created_at) >= ?';
    $params[] = $date_from;
}

if ($date_to) {
    $whereConditions[] = 'DATE(o.created_at) <= ?';
    $params[] = $date_to;
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM orders o 
             JOIN products p ON o.product_id = p.id 
             JOIN users u ON o.buyer_id = u.id 
             WHERE $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalOrders = $stmt->fetchColumn();
$totalPages = ceil($totalOrders / $perPage);

// Get orders with details
$sql = "SELECT o.*, p.name AS product_name, p.image, u.name AS buyer_name, u.email AS buyer_email
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN users u ON o.buyer_id = u.id 
        WHERE $whereClause 
        ORDER BY o.created_at DESC 
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'Shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    SUM(total) as total_revenue
    FROM orders WHERE seller_id = ?");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-card { transition: transform 0.2s, box-shadow 0.2s; }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
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
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="products.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
                    <i class="fas fa-box mr-2"></i>Products
                </a>
                <a href="orders.php" class="border-b-2 border-orange-500 text-orange-600 py-4 px-1 font-medium">
                    <i class="fas fa-shopping-cart mr-2"></i>Orders
                </a>
                <a href="analytics.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
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
            <h1 class="text-3xl font-bold text-gray-900">Manage Orders</h1>
            <p class="text-gray-600 mt-2">Track and manage customer orders efficiently</p>
        </div>

        <!-- Order Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-600">Total Orders</p>
                        <p class="text-lg font-semibold text-gray-900"><?= $stats['total_orders'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-lg font-semibold text-gray-900"><?= $stats['pending_orders'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-600">Shipped</p>
                        <p class="text-lg font-semibold text-gray-900"><?= $stats['shipped_orders'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-600">Delivered</p>
                        <p class="text-lg font-semibold text-gray-900"><?= $stats['delivered_orders'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-600">Cancelled</p>
                        <p class="text-lg font-semibold text-gray-900"><?= $stats['cancelled_orders'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-600">Revenue</p>
                        <p class="text-lg font-semibold text-gray-900">Rs. <?= number_format($stats['total_revenue'] ?? 0, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="all">All Status</option>
                        <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Shipped" <?= $status === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="Delivered" <?= $status === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Order ID, product, or buyer" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" name="date_from" value="<?= $date_from ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" name="date_to" value="<?= $date_to ?>" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>
            </form>
            
            <!-- Quick Filters -->
            <div class="flex flex-wrap gap-2 mt-4">
                <a href="?status=Pending" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm hover:bg-yellow-200 transition">
                    <i class="fas fa-hourglass-half mr-1"></i>Pending
                </a>
                <a href="?status=Shipped" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm hover:bg-blue-200 transition">
                    <i class="fas fa-shipping-fast mr-1"></i>Shipped
                </a>
                <a href="orders.php" class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-times mr-1"></i>Clear Filters
                </a>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="flex justify-between items-center mb-6">
            <p class="text-gray-600">
                Showing <?= $totalOrders ?> order<?= $totalOrders !== 1 ? 's' : '' ?>
                <?php if ($search || $status !== '' || $date_from || $date_to): ?>
                    (filtered)
                <?php endif; ?>
            </p>
        </div>

        <!-- Orders List -->
        <?php if ($orders): ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-4">
                                    <img src="../public/uploads/<?= htmlspecialchars($order['image'] ?? 'default-product.jpg') ?>" 
                                         alt="<?= htmlspecialchars($order['product_name']) ?>" 
                                         class="w-16 h-16 object-cover rounded-lg">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 text-lg"><?= htmlspecialchars($order['product_name']) ?></h3>
                                        <p class="text-gray-600">Order #<?= $order['id'] ?></p>
                                        <p class="text-sm text-gray-500">Buyer: <?= htmlspecialchars($order['buyer_name']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($order['buyer_email']) ?></p>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-orange-600">Rs. <?= number_format($order['total'], 2) ?></p>
                                    <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                        <?= $order['status'] ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Quantity:</span>
                                    <span class="font-medium"><?= $order['quantity'] ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Order Date:</span>
                                    <span class="font-medium"><?= date('M j, Y', strtotime($order['created_at'])) ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Unit Price:</span>
                                    <span class="font-medium">Rs. <?= number_format($order['total'] / $order['quantity'], 2) ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Order ID:</span>
                                    <span class="font-medium">#<?= $order['id'] ?></span>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-2">
                                <?php if ($order['status'] === 'Pending'): ?>
                                    <button onclick="updateOrderStatus(<?= $order['id'] ?>, 'Shipped')" 
                                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                        <i class="fas fa-shipping-fast mr-1"></i>Mark as Shipped
                                    </button>
                                    <button onclick="updateOrderStatus(<?= $order['id'] ?>, 'Cancelled')" 
                                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">
                                        <i class="fas fa-times mr-1"></i>Cancel Order
                                    </button>
                                <?php elseif ($order['status'] === 'Shipped'): ?>
                                    <button onclick="updateOrderStatus(<?= $order['id'] ?>, 'Delivered')" 
                                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                                        <i class="fas fa-check mr-1"></i>Mark as Delivered
                                    </button>
                                <?php endif; ?>
                                
                                <button onclick="viewOrderDetails(<?= $order['id'] ?>)" 
                                        class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm">
                                    <i class="fas fa-eye mr-1"></i>View Details
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-8">
                    <nav class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg <?= $i === $page ? 'bg-orange-600 text-white border-orange-600' : 'text-gray-700 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                <p class="text-gray-600 mb-6">
                    <?php if ($search || $status !== '' || $date_from || $date_to): ?>
                        Try adjusting your filters or search terms.
                    <?php else: ?>
                        Start selling to see orders here!
                    <?php endif; ?>
                </p>
                <a href="dashboard.php" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateOrderStatus(orderId, newStatus) {
            const statusText = newStatus.toLowerCase();
            if (confirm(`Are you sure you want to mark this order as ${statusText}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../actions/order_update_status.php';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= csrf_token() ?>';
                
                const orderInput = document.createElement('input');
                orderInput.type = 'hidden';
                orderInput.name = 'order_id';
                orderInput.value = orderId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = newStatus;
                
                form.appendChild(csrfInput);
                form.appendChild(orderInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewOrderDetails(orderId) {
            // Implement order details modal or redirect to details page
            alert('Order details functionality will be implemented here. Order ID: ' + orderId);
        }
    </script>
</body>
</html>
