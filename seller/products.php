<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); 
    echo "<p>Forbidden.</p>"; 
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// Get filter parameters
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query based on filters
$whereConditions = ['seller_id = ?'];
$params = [$user['id']];

if ($category && $category !== 'all') {
    $whereConditions[] = 'category = ?';
    $params[] = $category;
}

if ($status && $status !== 'all') {
    if ($status === 'active') {
        $whereConditions[] = 'is_active = 1';
    } elseif ($status === 'inactive') {
        $whereConditions[] = 'is_active = 0';
    }
}

if ($search) {
    $whereConditions[] = '(name LIKE ? OR description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter === 'low-stock') {
    $whereConditions[] = 'stock <= 5';
}

$whereClause = implode(' AND ', $whereConditions);

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM products WHERE $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products
$sql = "SELECT * FROM products WHERE $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = get_categories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-card { transition: transform 0.2s, box-shadow 0.2s; }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .stock-low { color: #dc2626; font-weight: 600; }
        .stock-ok { color: #059669; }
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
                <a href="products.php" class="border-b-2 border-orange-500 text-orange-600 py-4 px-1 font-medium">
                    <i class="fas fa-box mr-2"></i>Products
                </a>
                <a href="orders.php" class="text-gray-500 hover:text-gray-700 py-4 px-1 font-medium">
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
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Manage Products</h1>
                <p class="text-gray-600 mt-2">Manage your product catalog and inventory</p>
            </div>
            <a href="add-product.php" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition flex items-center">
                <i class="fas fa-plus mr-2"></i>Add New Product
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $category === $key ? 'selected' : '' ?>>
                                <?= $value ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="all">All Status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Product name or description" 
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
                <a href="?filter=low-stock" class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm hover:bg-red-200 transition">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Low Stock
                </a>
                <a href="?status=inactive" class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-eye-slash mr-1"></i>Inactive
                </a>
                <a href="products.php" class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-times mr-1"></i>Clear Filters
                </a>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="flex justify-between items-center mb-6">
            <p class="text-gray-600">
                Showing <?= $totalProducts ?> product<?= $totalProducts !== 1 ? 's' : '' ?>
                <?php if ($search || $category !== '' || $status !== '' || $filter): ?>
                    (filtered)
                <?php endif; ?>
            </p>
            
            <!-- Bulk Actions -->
            <div class="flex items-center space-x-4">
                <select id="bulkAction" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate Selected</option>
                    <option value="deactivate">Deactivate Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button id="applyBulkAction" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition disabled:opacity-50" disabled>
                    Apply
                </button>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if ($products): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($products as $product): ?>
                    <div class="product-card bg-white rounded-lg shadow overflow-hidden">
                        <div class="relative">
                            <img src="../public/uploads/<?= htmlspecialchars($product['image'] ?? 'default-product.jpg') ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="w-full h-48 object-cover">
                            
                            <!-- Status Badge -->
                            <div class="absolute top-2 right-2">
                                <span class="status-badge <?= $product['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                            
                            <!-- Stock Badge -->
                            <div class="absolute top-2 left-2">
                                <span class="px-2 py-1 bg-white bg-opacity-90 rounded-full text-xs font-medium <?= $product['stock'] <= 5 ? 'stock-low' : 'stock-ok' ?>">
                                    Stock: <?= $product['stock'] ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-semibold text-gray-900 text-lg"><?= htmlspecialchars($product['name']) ?></h3>
                                <input type="checkbox" class="product-checkbox" value="<?= $product['id'] ?>" 
                                       onchange="updateBulkActionButton()">
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                <?= htmlspecialchars(substr($product['description'], 0, 100)) ?>
                                <?= strlen($product['description']) > 100 ? '...' : '' ?>
                            </p>
                            
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-2xl font-bold text-orange-600">
                                    Rs. <?= number_format($product['price'], 2) ?>
                                </span>
                                <span class="text-sm text-gray-500 capitalize">
                                    <?= str_replace('-', ' ', $product['category']) ?>
                                </span>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="edit-product.php?id=<?= $product['id'] ?>" 
                                   class="flex-1 bg-blue-600 text-white text-center py-2 rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </a>
                                <button onclick="toggleProductStatus(<?= $product['id'] ?>, <?= $product['is_active'] ? 0 : 1 ?>)"
                                        class="flex-1 <?= $product['is_active'] ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' ?> text-white py-2 rounded-lg transition">
                                    <i class="fas <?= $product['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?> mr-1"></i>
                                    <?= $product['is_active'] ? 'Deactivate' : 'Activate' ?>
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
                <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                <p class="text-gray-600 mb-6">
                    <?php if ($search || $category !== '' || $status !== '' || $filter): ?>
                        Try adjusting your filters or search terms.
                    <?php else: ?>
                        Get started by adding your first product!
                    <?php endif; ?>
                </p>
                <a href="add-product.php" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add Your First Product
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateBulkActionButton() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            const bulkAction = document.getElementById('bulkAction');
            const applyButton = document.getElementById('applyBulkAction');
            
            applyButton.disabled = checkboxes.length === 0 || !bulkAction.value;
        }
        
        function toggleProductStatus(productId, newStatus) {
            if (confirm('Are you sure you want to ' + (newStatus ? 'activate' : 'deactivate') + ' this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../actions/product_toggle_status.php';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= csrf_token() ?>';
                
                const productInput = document.createElement('input');
                productInput.type = 'hidden';
                productInput.name = 'product_id';
                productInput.value = productId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'is_active';
                statusInput.value = newStatus;
                
                form.appendChild(csrfInput);
                form.appendChild(productInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Bulk actions
        document.getElementById('applyBulkAction').addEventListener('click', function() {
            const action = document.getElementById('bulkAction').value;
            const selectedProducts = Array.from(document.querySelectorAll('.product-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedProducts.length === 0) {
                alert('Please select products first.');
                return;
            }
            
            if (action === 'delete' && !confirm('Are you sure you want to delete the selected products? This action cannot be undone.')) {
                return;
            }
            
            // Implement bulk actions here
            console.log('Bulk action:', action, 'Products:', selectedProducts);
            alert('Bulk action functionality will be implemented here.');
        });
        
        // Update bulk action button when selection changes
        document.getElementById('bulkAction').addEventListener('change', updateBulkActionButton);
    </script>
</body>
</html>
