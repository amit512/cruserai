<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';

$user = $_SESSION['user'] ?? null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $product = $id ? Product::find($id) : null;
} catch (Exception $e) {
    error_log("Error fetching product: " . $e->getMessage());
    $product = null;
}

if (!$product) {
    http_response_code(404);
    echo "<h1>Product Not Found</h1>";
    echo "<p>The product you're looking for doesn't exist.</p>";
    echo "<a href='catalog.php'>Back to Catalog</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="handcraf.css">
    <link rel="stylesheet" href="startstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        
        .product-image-section {
            position: relative;
        }
        
        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .product-info-section {
            padding: 1rem;
        }
        
        .product-title {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .product-price {
            font-size: 2rem;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 1rem;
        }
        
        .product-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .product-meta {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .meta-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .meta-label {
            font-weight: bold;
            color: #333;
        }
        
        .meta-value {
            color: #666;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
        }
        
        .btn-primary:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #2196F3;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
        }
        
        .btn-secondary:hover {
            background: #1976D2;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: #4CAF50;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s;
            border: 2px solid #4CAF50;
            cursor: pointer;
            font-size: 1.1rem;
        }
        
        .btn-outline:hover {
            background: #4CAF50;
            color: white;
        }
        
        .breadcrumb {
            margin-bottom: 2rem;
            color: #666;
        }
        
        .breadcrumb a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .product-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="product-detail-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a> &gt; 
            <a href="catalog.php">Catalog</a> &gt; 
            <a href="catalog.php?category=<?= urlencode($product['category']) ?>"><?= htmlspecialchars(ucfirst($product['category'])) ?></a> &gt; 
            <span><?= htmlspecialchars($product['name']) ?></span>
        </div>

        <div class="product-grid">
            <!-- Product Image -->
            <div class="product-image-section">
                <?php if (!empty($product['image'])): ?>
                    <img class="product-image" 
                         src="image.php?file=<?= urlencode($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                <?php else: ?>
                    <div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-image" style="font-size: 4rem; color: #ccc;"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Information -->
            <div class="product-info-section">
                <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                <div class="product-price"><?= format_price($product['price']) ?></div>
                <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <span class="meta-label">Seller:</span>
                        <span class="meta-value"><?= htmlspecialchars($product['seller_name']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Category:</span>
                        <span class="meta-value"><?= htmlspecialchars(ucfirst($product['category'])) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Stock Available:</span>
                        <span class="meta-value"><?= (int) $product['stock'] ?> units</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Added:</span>
                        <span class="meta-value"><?= date('F j, Y', strtotime($product['created_at'])) ?></span>
                    </div>
                </div>

                <div class="action-buttons">
                    <?php if ($product['stock'] > 0): ?>
                        <button class="btn-primary">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    <?php else: ?>
                        <button class="btn-outline" disabled>
                            <i class="fas fa-times"></i> Out of Stock
                        </button>
                    <?php endif; ?>
                    
                    <a href="catalog.php?category=<?= urlencode($product['category']) ?>" class="btn-secondary">
                        <i class="fas fa-th-large"></i> View Similar
                    </a>
                    
                    <a href="product-reviews.php?product_id=<?= (int)$product['id'] ?>" class="btn-outline">
                        <i class="fas fa-star"></i> See Reviews
                    </a>
                    
                    <a href="catalog.php" class="btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Catalog
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add to cart functionality (placeholder)
        document.querySelector('.btn-primary')?.addEventListener('click', function() {
            if (this.textContent.includes('Add to Cart')) {
                alert('Add to cart functionality will be implemented here!');
            }
        });
    </script>
</body>
</html>
