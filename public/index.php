<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';

$user = $_SESSION['user'] ?? null;
$pdo = db();

// Initialize variables with defaults
$featuredProducts = [];
$categories = [];
$latestProducts = [];
$searchQuery = $_GET['search'] ?? '';
$searchResults = [];

// Fetch featured products with error handling
try {
    $featuredProducts = Product::featured(6);
} catch (Exception $e) {
    error_log("Error fetching featured products: " . $e->getMessage());
    $featuredProducts = [];
}

// Fetch categories with product counts with error handling
try {
    $categories = Product::getCategories();
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Fetch latest products with error handling
try {
    $latestProducts = Product::allActive(8);
} catch (Exception $e) {
    error_log("Error fetching latest products: " . $e->getMessage());
    $latestProducts = [];
}

// Get search query if any
if ($searchQuery) {
    try {
        $searchResults = Product::search($searchQuery, 20);
    } catch (Exception $e) {
        error_log("Error searching products: " . $e->getMessage());
        $searchResults = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= SITE_NAME ?> - <?= SITE_DESCRIPTION ?></title>
    <link rel="stylesheet" href="handcraf.css"/>
    <link rel="stylesheet" href="startstyle.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        /* Additional CSS for dynamic features */
        .search-container {
            margin: 2rem 0;
            text-align: center;
        }
        
        .search-form {
            display: inline-block;
            max-width: 500px;
            width: 100%;
        }
        
        .search-input-group {
            display: flex;
            background: white;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            outline: none;
            font-size: 16px;
        }
        
        .search-btn {
            padding: 15px 25px;
            background: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .search-btn:hover {
            background: #45a049;
        }
        
        .search-results-section {
            padding: 3rem 0;
            background: #f9f9f9;
        }
        
        .search-results-section h2 {
            text-align: center;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .search-results-section p {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
        }
        
        .categories-section {
            padding: 4rem 0;
            background: white;
        }
        
        .categories-section h2 {
            text-align: center;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .categories-section > p {
            text-align: center;
            color: #666;
            margin-bottom: 3rem;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .category-card {
            text-decoration: none;
            color: inherit;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .category-icon {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 1rem;
        }
        
        .category-card h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .category-count {
            color: #666;
            font-size: 0.9rem;
        }
        
        .featured-section,
        .latest-section {
            padding: 4rem 0;
        }
        
        .featured-section {
            background: #f9f9f9;
        }
        
        .featured-section h2,
        .latest-section h2 {
            text-align: center;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .featured-section > p,
        .latest-section > p {
            text-align: center;
            color: #666;
            margin-bottom: 3rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
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
        
        .featured-badge,
        .new-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .new-badge {
            background: #2196F3;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-info h3 {
            margin-bottom: 0.5rem;
            color: #333;
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
        }
        
        .view-product-btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            transition: background 0.3s;
        }
        
        .view-product-btn:hover {
            background: #45a049;
        }
        
        .empty-message {
            text-align: center;
            color: #666;
            font-style: italic;
            grid-column: 1 / -1;
            padding: 2rem;
        }
        
        .view-all-container {
            text-align: center;
            margin-top: 3rem;
        }
        
        .view-all-btn {
            display: inline-block;
            background: #2196F3;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .view-all-btn:hover {
            background: #1976D2;
        }
        
        .cta-section {
            padding: 4rem 0;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            text-align: center;
        }
        
        .cta-content h2 {
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .cta-content p {
            margin-bottom: 2rem;
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .cta-primary,
        .cta-secondary {
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .cta-primary {
            background: white;
            color: #4CAF50;
        }
        
        .cta-primary:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }
        
        .cta-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .cta-secondary:hover {
            background: white;
            color: #4CAF50;
        }
        
        .footer {
            background: #333;
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-section h4 {
            margin-bottom: 1rem;
            color: #4CAF50;
        }
        
        .footer-section a {
            display: block;
            color: #ccc;
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: #4CAF50;
        }
        
        .footer-bottom {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #555;
            color: #ccc;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .categories-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    
    <?php include __DIR__ . '/../includes/headerb.php'; ?>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Created with Love,<br>Shared with Care</h1>
            <p>Discover unique handmade treasures from talented artisans around the world.</p>
            
            <!-- Search Bar -->
            <div class="search-container">
                <form method="GET" action="" class="search-form">
                    <div class="search-input-group">
                        <input type="text" name="search" placeholder="Search for handmade treasures..." 
                               value="<?= htmlspecialchars($searchQuery) ?>" class="search-input">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="hero-buttons">
                <a href="catalog.php" class="btn shop">Start Shopping</a>
                <?php if (!$user || $user['role'] !== 'seller'): ?>
                    <a href="seller-dashboard.php" class="btn seller" style="background: f0c987;
  color: white;
      padding: 15px 30px;
    font-size: 1.1rem;
  border-radius: 30px;
  font-weight: bold;
  text-decoration: none;
  
  display: inline-block;
  transition: background 0.3s ease;">Become a Seller</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-image">
            <img src="./uploads/hi-removebg-preview.png" alt="Handcrafting Illustration">
        </div>
    </section>

    <!-- Search Results Section -->
    <?php if ($searchQuery && !empty($searchResults)): ?>
    <section class="search-results-section">
        <div class="container">
            <h2>Search Results for "<?= htmlspecialchars($searchQuery) ?>"</h2>
            <p><?= count($searchResults) ?> products found</p>
            
            <div class="products-grid">
                <?php foreach ($searchResults as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="image.php?file=<?= urlencode($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="product-info">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="seller-name">by <?= htmlspecialchars($product['seller_name']) ?></p>
                            <div class="product-price"><?= format_price($product['price']) ?></div>
                            <p class="product-description"><?= truncate_text($product['description']) ?></p>
                            <a href="product.php?id=<?= $product['id'] ?>" class="view-product-btn">View Product</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Categories Section -->
    <section class="categories-section">
        <div class="container">
            <h2>Browse by Category</h2>
            <p>Find exactly what you're looking for</p>
            
            <div class="categories-grid">
                <?php foreach (get_categories() as $key => $name): ?>
                    <a href="catalog.php?category=<?= $key ?>" class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-<?= get_category_icon($key) ?>"></i>
                        </div>
                        <h3><?= $name ?></h3>
                        <?php 
                        $categoryCount = array_filter($categories, fn($cat) => $cat['category'] === $key);
                        $count = $categoryCount ? reset($categoryCount)['count'] : 0;
                        ?>
                        <span class="category-count"><?= $count ?> items</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-section">
        <div class="container">
            <h2>Featured Handcrafted Products</h2>
            <p>Handpicked treasures from our talented artisans</p>
            
            <div class="products-grid">
                <?php if (!empty($featuredProducts)): ?>
                    <?php foreach ($featuredProducts as $product): ?>
                        <div class="product-card featured">
                            <div class="product-image">
                                <img src="image.php?file=<?= urlencode($product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>">
                                <div class="featured-badge">Featured</div>
                            </div>
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="seller-name">by <?= htmlspecialchars($product['seller_name']) ?></p>
                                <?php $sum = Product::getRatingSummary((int)$product['id']); $filled = (int)round($sum['avg']); ?>
                                <div class="seller-name" style="color:#f5a623;">
                                    <?php for ($i=1; $i<=5; $i++): ?>
                                        <?php if ($i <= $filled): ?><i class="fas fa-star"></i><?php else: ?><i class="far fa-star"></i><?php endif; ?>
                                    <?php endfor; ?>
                                    <span style="color:#666; margin-left:.25rem;">(<?= (int)$sum['count'] ?>)</span>
                                </div>
                                <div class="product-price"><?= format_price($product['price']) ?></div>
                                <p class="product-description"><?= truncate_text($product['description']) ?></p>
                                <a href="product.php?id=<?= $product['id'] ?>" class="view-product-btn">View Product</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">No featured products available at the moment.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Latest Products Section -->
    <section class="latest-section">
        <div class="container">
            <h2>Latest Additions</h2>
            <p>Fresh handmade creations just added</p>
            
            <div class="products-grid">
                <?php if (!empty($latestProducts)): ?>
                    <?php foreach ($latestProducts as $product): ?>
                        <div class="product-card latest">
                            <div class="product-image">
                                <img src="image.php?file=<?= urlencode($product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>">
                                <div class="new-badge">New</div>
                            </div>
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="seller-name">by <?= htmlspecialchars($product['seller_name']) ?></p>
                                <?php $sum = Product::getRatingSummary((int)$product['id']); $filled = (int)round($sum['avg']); ?>
                                <div class="seller-name" style="color:#f5a623;">
                                    <?php for ($i=1; $i<=5; $i++): ?>
                                        <?php if ($i <= $filled): ?><i class="fas fa-star"></i><?php else: ?><i class="far fa-star"></i><?php endif; ?>
                                    <?php endfor; ?>
                                    <span style="color:#666; margin-left:.25rem;">(<?= (int)$sum['count'] ?>)</span>
                                </div>
                                <div class="product-price"><?= format_price($product['price']) ?></div>
                                <p class="product-description"><?= truncate_text($product['description']) ?></p>
                                <a href="product.php?id=<?= $product['id'] ?>" class="view-product-btn">View Product</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">No products available at the moment.</div>
                <?php endif; ?>
            </div>
            
            <div class="view-all-container">
                <a href="catalog.php" class="view-all-btn">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Handcrafted Journey?</h2>
                <p>Join our community of artisans and craft enthusiasts</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn cta-primary">Join Now</a>
                    <a href="about.php" class="btn cta-secondary">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4><?= SITE_NAME ?></h4>
                <p>Connecting artisans with craft enthusiasts worldwide. Every piece tells a story.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="#home">Home</a>
                <a href="catalog.php">Catalog</a>
                <a href="about.php">About Us</a>
                <a href="contact.php">Contact</a>
            </div>
            <div class="footer-section">
                <h4>Categories</h4>
                <?php foreach (array_slice(get_categories(), 0, 5) as $key => $name): ?>
                    <a href="catalog.php?category=<?= $key ?>"><?= $name ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date("Y") ?> <?= SITE_NAME ?>. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Add smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add search functionality
        const searchForm = document.querySelector('.search-form');
        const searchInput = document.querySelector('.search-input');
        
        searchForm.addEventListener('submit', function(e) {
            if (!searchInput.value.trim()) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    </script>
</body>
</html>

<?php
// Helper function for category icons
function get_category_icon(string $category): string {
    $icons = [
        'jewelry' => 'gem',
        'home-decor' => 'home',
        'clothing' => 'tshirt',
        'art' => 'palette',
        'pottery' => 'circle',
        'textiles' => 'cut',
        'woodwork' => 'tree',
        'metalwork' => 'hammer',
        'leather' => 'briefcase',
        'candles' => 'fire'
    ];
    
    return $icons[$category] ?? 'star';
}
?>
