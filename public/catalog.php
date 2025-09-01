<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';

$user = $_SESSION['user'] ?? null;

// Get filter parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Fetch products based on filters
try {
    if ($category && $category !== 'all') {
        $products = Product::byCategory($category);
        $totalProducts = count($products);
        $products = array_slice($products, $offset, $perPage);
    } elseif ($search) {
        $products = Product::search($search);
        $totalProducts = count($products);
        $products = array_slice($products, $offset, $perPage);
    } else {
        $products = Product::allActive($perPage, $offset);
        $totalProducts = Product::getTotalCount();
    }
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
    $totalProducts = 0;
}

// Fetch categories for filter
try {
    $categories = Product::getCategories();
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

$totalPages = ceil($totalProducts / $perPage);

// Sort products if needed
if ($sort === 'price-low') {
    usort($products, fn($a, $b) => $a['price'] <=> $b['price']);
} elseif ($sort === 'price-high') {
    usort($products, fn($a, $b) => $b['price'] <=> $a['price']);
} elseif ($sort === 'name') {
    usort($products, fn($a, $b) => strcasecmp($a['name'], $b['name']));
}

$allCategories = get_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="handcraf.css">
    <link rel="stylesheet" href="startstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Catalog Page Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        
        .catalog-container {
            min-height: 100vh;
        }
        
        .catalog-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        .catalog-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .catalog-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .catalog-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            padding: 2rem 0;
        }
        
        /* Sidebar Styles */
        .catalog-sidebar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .filter-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .filter-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .filter-section h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-option {
            display: block;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: #666;
            border-radius: 8px;
            transition: all 0.3s;
            border: 1px solid transparent;
        }
        
        .filter-option:hover {
            background: #f0f0f0;
            color: #333;
        }
        
        .filter-option.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        /* Search Styles */
        .sidebar-search {
            margin-top: 1rem;
        }
        
        .search-input-group {
            display: flex;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem;
            border: none;
            outline: none;
            font-size: 0.9rem;
        }
        
        .search-btn {
            padding: 0.75rem 1rem;
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .search-btn:hover {
            background: #45a049;
        }
        
        /* Active Filters */
        .active-filters {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .active-filter {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .remove-filter {
            color: #1976d2;
            text-decoration: none;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        .remove-filter:hover {
            color: #d32f2f;
        }
        
        /* Main Content Area */
        .catalog-main {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .catalog-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .results-info {
            color: #666;
        }
        
        .view-options {
            display: flex;
            gap: 0.5rem;
        }
        
        .view-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            background: white;
            color: #666;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .view-btn:hover,
        .view-btn.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #f0f0f0;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .product-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: bold;
            line-height: 1.3;
        }
        
        .seller-name {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 1rem;
        }
        
        .product-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-view {
            flex: 1;
            background: #4CAF50;
            color: white;
            padding: 0.75rem;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            transition: background 0.3s;
            font-weight: 500;
        }
        
        .btn-view:hover {
            background: #45a049;
        }
        
        .btn-cart {
            background: #2196F3;
            color: white;
            padding: 0.75rem 1rem;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
            font-weight: 500;
        }
        
        .btn-cart:hover {
            background: #1976D2;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }
        
        .page-link {
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background: #f0f0f0;
            color: #333;
        }
        
        .page-link.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        .page-link.disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .catalog-layout {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .catalog-sidebar {
                position: static;
                order: 2;
                display: none;
            }
            
            .catalog-sidebar.mobile-open {
                display: block;
            }
            
            .catalog-main {
                order: 1;
            }
            
            .catalog-header h1 {
                font-size: 2rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .catalog-toolbar {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .mobile-filter-toggle {
                display: block;
            }
        }
        
        /* Loading State */
        .loading {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .loading i {
            font-size: 2rem;
            color: #4CAF50;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Mobile Filter Toggle */
        .mobile-filter-toggle {
            display: none;
            background: #4CAF50;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .mobile-filter-toggle:hover {
            background: #45a049;
        }
        
        /* Category Badge */
        .category-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(76, 175, 80, 0.9);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* No Products State */
        .no-products {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-products-icon {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .no-products h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .no-products-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn.shop,
        .btn.seller {
            display: inline-block;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn.shop {
            background: #4CAF50;
            color: white;
        }
        
        .btn.shop:hover {
            background: #45a049;
        }
        
        .btn.seller {
            background: #2196F3;
            color: white;
        }
        
        .btn.seller:hover {
            background: #1976D2;
        }
        
        /* Results Count */
        .results-count {
            font-weight: bold;
            color: #333;
        }
        
        .category-label {
            color: #666;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="catalog-container">
        <!-- Page Header -->
        <div class="catalog-header">
            <div class="container">
                <h1>Handcrafted Products</h1>
                <p>Discover unique creations from talented artisans</p>
            </div>
    </div>

        <div class="container">
            <div class="catalog-layout">
                <!-- Sidebar Filters -->
                <aside class="catalog-sidebar">
                    <div class="filter-section">
                        <h3>Categories</h3>
                        <div class="filter-options">
                            <a href="?<?= http_build_query(array_merge($_GET, ['category' => '', 'page' => 1])) ?>" 
                               class="filter-option <?= !$category ? 'active' : '' ?>">
                                All Categories
                            </a>
                            <?php foreach ($allCategories as $key => $name): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['category' => $key, 'page' => 1])) ?>" 
                                   class="filter-option <?= $category === $key ? 'active' : '' ?>">
                                    <?= $name ?>
  </a>
<?php endforeach; ?>
</div>
                    </div>

                    <div class="filter-section">
                        <h3>Sort By</h3>
                        <div class="filter-options">
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'newest', 'page' => 1])) ?>" 
                               class="filter-option <?= $sort === 'newest' ? 'active' : '' ?>">
                                Newest First
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'price-low', 'page' => 1])) ?>" 
                               class="filter-option <?= $sort === 'price-low' ? 'active' : '' ?>">
                                Price: Low to High
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'price-high', 'page' => 1])) ?>" 
                               class="filter-option <?= $sort === 'price-high' ? 'active' : '' ?>">
                                Price: High to Low
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'name', 'page' => 1])) ?>" 
                               class="filter-option <?= $sort === 'name' ? 'active' : '' ?>">
                                Name A-Z
                            </a>
                        </div>
                    </div>

                    <!-- Search Filter -->
                    <div class="filter-section">
                        <h3>Search</h3>
                        <form method="GET" class="sidebar-search">
                            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                            <div class="search-input-group">
                                <input type="text" name="search" placeholder="Search products..." 
                                       value="<?= htmlspecialchars($search) ?>" class="search-input">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Active Filters Display -->
                    <?php if ($category || $search): ?>
                    <div class="filter-section">
                        <h3>Active Filters</h3>
                        <div class="active-filters">
                            <?php if ($category): ?>
                                <span class="active-filter">
                                    Category: <?= $allCategories[$category] ?? $category ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['category' => '', 'page' => 1])) ?>" 
                                       class="remove-filter">×</a>
                                </span>
                            <?php endif; ?>
                            <?php if ($search): ?>
                                <span class="active-filter">
                                    Search: "<?= htmlspecialchars($search) ?>"
                                    <a href="?<?= http_build_query(array_merge($_GET, ['search' => '', 'page' => 1])) ?>" 
                                       class="remove-filter">×</a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </aside>

                <!-- Main Content -->
                <main class="catalog-main">
                    <!-- Results Summary -->
                                            <div class="catalog-toolbar">
                            <div class="results-info">
                                <span class="results-count">
                                    <?= $totalProducts ?> product<?= $totalProducts !== 1 ? 's' : '' ?> found
                                </span>
                                <?php if ($category): ?>
                                    <span class="category-label">in <?= $allCategories[$category] ?? $category ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Mobile Filter Toggle -->
                            <button class="mobile-filter-toggle" onclick="toggleMobileFilters()">
                                <i class="fas fa-filter"></i> Filters
                            </button>
                        </div>

                    <!-- Products Grid -->
                    <?php if (!empty($products)): ?>
                        <div class="products-grid catalog-grid">
                            <?php foreach ($products as $product): ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="image.php?file=<?= urlencode($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                        <?php if (isset($product['category']) && $product['category']): ?>
                                            <div class="category-badge"><?= $allCategories[$product['category']] ?? $product['category'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                                        <p class="seller-name">by <?= htmlspecialchars($product['seller_name']) ?></p>
                                        <div class="product-price"><?= format_price($product['price']) ?></div>
                                        <p class="product-description"><?= truncate_text($product['description']) ?></p>
                                        <div class="product-actions">
                                            <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">View Product</a>
                                            <?php if ($user && $user['role'] === 'buyer'): ?>
                                                <button class="btn-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                       class="page-link prev">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>

                                <div class="page-numbers">
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                           class="page-link <?= $i === $page ? 'active' : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                       class="page-link next">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="no-products">
                            <div class="no-products-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>No products found</h3>
                            <p>
                                <?php if ($search): ?>
                                    No products match your search for "<?= htmlspecialchars($search) ?>".
                                <?php elseif ($category): ?>
                                    No products found in the <?= $allCategories[$category] ?? $category ?> category.
                                <?php else: ?>
                                    No products are currently available.
                                <?php endif; ?>
                            </p>
                            <div class="no-products-actions">
                                <a href="catalog.php" class="btn shop">View All Products</a>
                                <a href="index.php" class="btn seller">Back to Home</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function toggleMobileFilters() {
            const sidebar = document.querySelector('.catalog-sidebar');
            sidebar.classList.toggle('mobile-open');
        }

        function addToCart(productId) {
            // Add to cart functionality
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                } else {
                    alert('Error adding product to cart: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            });
        }

        // Close mobile filters when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.catalog-sidebar');
            const toggleBtn = document.querySelector('.mobile-filter-toggle');
            
            if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                sidebar.classList.remove('mobile-open');
            }
        });
    </script>
</body>
</html>
