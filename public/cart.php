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

// Handle messages and errors from URL parameters
$message = '';
$error = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'item_removed':
            $message = "Item removed from cart successfully.";
            break;
        case 'quantity_updated':
            $message = "Cart quantity updated successfully.";
            break;
        case 'cart_cleared':
            $message = "Cart cleared successfully.";
            break;
        case 'item_added':
            $message = "Item added to cart successfully.";
            break;
    }
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'insufficient_stock':
            $error = "Insufficient stock available for this item.";
            break;
        case 'seller_frozen':
            $error = "This seller's account is temporarily unavailable. Please try again later or choose another product.";
            break;
    }
}

// Fetch cart items
try {
    $stmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.image, p.stock, u.name as seller_name 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        JOIN users u ON p.seller_id = u.id 
        WHERE ci.user_id = ? AND p.is_active = 1
        ORDER BY ci.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $cartItems = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching cart items: " . $e->getMessage());
    $cartItems = [];
}

$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?= SITE_NAME ?></title>
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
        
        .cart-container {
            min-height: 100vh;
        }
        
        .cart-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            text-align: center;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .cart-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .cart-items {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }
        
        .cart-item-seller {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .cart-item-price {
            color: #4CAF50;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            background: #f0f0f0;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.25rem;
        }
        
        .remove-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .remove-btn:hover {
            background: #d32f2f;
        }
        
        .cart-summary {
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
            color: #4CAF50;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #4CAF50;
        }
        
        .checkout-btn {
            width: 100%;
            background: #4CAF50;
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: background 0.3s;
        }
        
        .checkout-btn:hover {
            background: #45a049;
        }
        
        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-cart h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .continue-shopping {
            background: #2196F3;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .continue-shopping:hover {
            background: #1976D2;
        }
        
        @media (max-width: 768px) {
            .cart-layout {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .cart-item-controls {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/headerb.php'; ?>

    <div class="cart-container">
        <!-- Header -->
        <section class="cart-header">
            <div class="container">
                <h1>Shopping Cart</h1>
                <p>Review your selected items before checkout</p>
            </div>
        </section>

        <div class="container">
            <?php if ($message): ?>
                <div class="success-message" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #c3e6cb;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #f5c6cb;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Add some beautiful handmade products to get started!</p>
                    <a href="catalog.php" class="continue-shopping">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <!-- Cart Items -->
                    <div class="cart-items">
                        <h2 style="margin-bottom: 1.5rem; color: #333;">Cart Items (<?= count($cartItems) ?>)</h2>
                        
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item">
                                <img src="image.php?file=<?= urlencode($item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="cart-item-image">
                                
                                <div class="cart-item-details">
                                    <div class="cart-item-title"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="cart-item-seller">by <?= htmlspecialchars($item['seller_name']) ?></div>
                                    <div class="cart-item-price"><?= format_price($item['price']) ?></div>
                                </div>
                                
                                <div class="cart-item-controls">
                                    <div class="quantity-controls">
                                        <form method="POST" action="../actions/cart_action.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="quantity" value="<?= max(1, $item['quantity'] - 1) ?>">
                                            <button type="submit" name="update_quantity" class="quantity-btn">-</button>
                                        </form>
                                        
                                        <input type="number" value="<?= $item['quantity'] ?>" 
                                               class="quantity-input" 
                                               onchange="updateQuantity(<?= $item['id'] ?>, this.value)"
                                               min="1" max="<?= $item['stock'] ?>">
                                        
                                        <form method="POST" action="../actions/cart_action.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="quantity" value="<?= min($item['stock'], $item['quantity'] + 1) ?>">
                                            <button type="submit" name="update_quantity" class="quantity-btn">+</button>
                                        </form>
                                    </div>
                                    
                                    <form method="POST" action="../actions/cart_action.php" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="remove_item" class="remove-btn">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <h3 class="summary-title">Order Summary</h3>
                        
                        <div class="summary-item">
                            <span>Subtotal (<?= count($cartItems) ?> items)</span>
                            <span><?= format_price($totalAmount) ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Tax</span>
                            <span>Included</span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Total</span>
                            <span><?= format_price($totalAmount) ?></span>
                        </div>
                        
                        <a href="checkout.php" class="checkout-btn" style="text-decoration: none; display: block; text-align: center;">
                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                        </a>
                        
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="catalog.php" class="continue-shopping">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function updateQuantity(itemId, quantity) {
            if (quantity < 1) {
                if (confirm('Remove this item from cart?')) {
                    document.querySelector(`form input[name="item_id"][value="${itemId}"]`).closest('form').submit();
                }
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../actions/cart_action.php';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="item_id" value="${itemId}">
                <input type="hidden" name="quantity" value="${quantity}">
                <input type="hidden" name="update_quantity" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        

    </script>
</body>
</html>
