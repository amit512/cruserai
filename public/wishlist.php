<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $itemId = (int) $_POST['item_id'];
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->execute([$itemId, $user['id']]);
    header('Location: wishlist.php');
    exit;
}

// Handle move to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_to_cart'])) {
    $itemId = (int) $_POST['item_id'];
    
    // Get wishlist item details
    $stmt = $pdo->prepare("
        SELECT w.*, p.name, p.price, p.stock 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.id = ? AND w.user_id = ?
    ");
    $stmt->execute([$itemId, $user['id']]);
    $wishlistItem = $stmt->fetch();
    
    if ($wishlistItem) {
        // Check if item already exists in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user['id'], $wishlistItem['product_id']]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            // Update existing cart item
            $newQuantity = $existingItem['quantity'] + 1;
            if ($newQuantity <= $wishlistItem['stock']) {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQuantity, $existingItem['id']]);
            }
        } else {
            // Add new item to cart
            $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$user['id'], $wishlistItem['product_id']]);
        }
        
        // Remove from wishlist
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
        $stmt->execute([$itemId, $user['id']]);
    }
    
    header('Location: wishlist.php');
    exit;
}

// Fetch wishlist items
try {
    $stmt = $pdo->prepare("
        SELECT w.*, p.name, p.price, p.image, p.stock, u.name as seller_name 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        JOIN users u ON p.seller_id = u.id 
        WHERE w.user_id = ? AND p.is_active = 1
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $wishlistItems = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching wishlist items: " . $e->getMessage());
    $wishlistItems = [];
}

$totalValue = 0;
foreach ($wishlistItems as $item) {
    $totalValue += $item['price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - <?= SITE_NAME ?></title>
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
        
        .wishlist-container {
            min-height: 100vh;
        }
        
        .wishlist-header {
            background: linear-gradient(135deg, #E91E63, #C2185B);
            color: white;
            text-align: center;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .wishlist-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .wishlist-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .wishlist-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .wishlist-items {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .wishlist-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .wishlist-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .wishlist-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .wishlist-details {
            flex: 1;
        }
        
        .wishlist-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }
        
        .wishlist-seller {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .wishlist-price {
            color: #E91E63;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .wishlist-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
            padding: 0.75rem 1rem;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            transition: background 0.3s;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background: #45a049;
        }
        
        .btn-secondary {
            background: #2196F3;
            color: white;
            padding: 0.75rem 1rem;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            transition: background 0.3s;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        
        .btn-secondary:hover {
            background: #1976D2;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            border: none;
            transition: background 0.3s;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .wishlist-summary {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .summary-title {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: bold;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: bold;
            color: #E91E63;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #E91E63;
        }
        
        .empty-wishlist {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-wishlist i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-wishlist h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .continue-shopping {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .continue-shopping:hover {
            background: #45a049;
        }
        
        .wishlist-stats {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #E91E63;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .wishlist-layout {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .wishlist-item {
                flex-direction: column;
                text-align: center;
            }
            
            .wishlist-actions {
                flex-direction: row;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="wishlist-container">
        <!-- Header -->
        <section class="wishlist-header">
            <div class="container">
                <h1>My Wishlist</h1>
                <p>Save your favorite handmade treasures for later</p>
            </div>
        </section>

        <div class="container">
            <!-- Wishlist Stats -->
            <div class="wishlist-stats">
                <h2 style="margin-bottom: 1rem; color: #333;">Wishlist Overview</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= count($wishlistItems) ?></div>
                        <div class="stat-label">Items Saved</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= format_price($totalValue) ?></div>
                        <div class="stat-label">Total Value</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= count(array_unique(array_column($wishlistItems, 'seller_name'))) ?></div>
                        <div class="stat-label">Unique Sellers</div>
                    </div>
                </div>
            </div>

            <?php if (empty($wishlistItems)): ?>
                <div class="empty-wishlist">
                    <i class="fas fa-heart"></i>
                    <h3>Your wishlist is empty</h3>
                    <p>Start adding products you love to your wishlist!</p>
                    <a href="catalog.php" class="continue-shopping">Browse Products</a>
                </div>
            <?php else: ?>
                <div class="wishlist-layout">
                    <!-- Wishlist Items -->
                    <div class="wishlist-items">
                        <h2 style="margin-bottom: 1.5rem; color: #333;">Saved Items (<?= count($wishlistItems) ?>)</h2>
                        
                        <?php foreach ($wishlistItems as $item): ?>
                            <div class="wishlist-item">
                                <img src="image.php?file=<?= urlencode($item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="wishlist-image">
                                
                                <div class="wishlist-details">
                                    <div class="wishlist-title"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="wishlist-seller">by <?= htmlspecialchars($item['seller_name']) ?></div>
                                    <div class="wishlist-price"><?= format_price($item['price']) ?></div>
                                </div>
                                
                                <div class="wishlist-actions">
                                    <a href="product.php?id=<?= $item['product_id'] ?>" class="btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="move_to_cart" class="btn-secondary">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="remove_item" class="btn-danger" 
                                                onclick="return confirm('Remove this item from wishlist?')">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Wishlist Summary -->
                    <div class="wishlist-summary">
                        <h3 class="summary-title">Wishlist Summary</h3>
                        
                        <div class="summary-item">
                            <span>Items Saved</span>
                            <span><?= count($wishlistItems) ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Total Value</span>
                            <span><?= format_price($totalValue) ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Unique Sellers</span>
                            <span><?= count(array_unique(array_column($wishlistItems, 'seller_name'))) ?></span>
                        </div>
                        
                        <div style="text-align: center; margin-top: 2rem;">
                            <a href="catalog.php" class="continue-shopping">
                                <i class="fas fa-search"></i> Continue Shopping
                            </a>
                        </div>
                        
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="buyer-dashboard.php" class="btn-secondary">
                                <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
