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

// Fetch cart items for checkout
try {
    $stmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.image, p.stock, p.seller_id, u.name as seller_name 
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

// Redirect if cart is empty
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

// Handle error messages from URL parameters
$errors = [];
if (isset($_GET['errors'])) {
    $errors = explode('|', $_GET['errors']);
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'empty_cart':
            $errors[] = "Your cart is empty. Please add items before checkout.";
            break;
        case 'checkout_failed':
            $errors[] = "An error occurred during checkout. Please try again.";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= SITE_NAME ?></title>
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
        
        .checkout-container {
            min-height: 100vh;
        }
        
        .checkout-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            text-align: center;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .checkout-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .checkout-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .payment-option:hover {
            border-color: #4CAF50;
            background: #f9f9f9;
        }
        
        .payment-option.selected {
            border-color: #4CAF50;
            background: #e8f5e8;
        }
        
        .payment-option input[type="radio"] {
            margin-right: 0.5rem;
        }
        
        .order-summary {
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
        
        .order-items {
            margin-bottom: 1.5rem;
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
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
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
        
        .place-order-btn {
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
        
        .place-order-btn:hover {
            background: #45a049;
        }
        
        .place-order-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #ffcdd2;
        }
        
        .back-to-cart {
            display: inline-block;
            color: #4CAF50;
            text-decoration: none;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .back-to-cart:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .checkout-layout {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="checkout-container">
        <!-- Header -->
        <section class="checkout-header">
            <div class="container">
                <h1>Checkout</h1>
                <p>Complete your purchase with secure checkout</p>
            </div>
        </section>

        <div class="container">
            <a href="cart.php" class="back-to-cart">
                <i class="fas fa-arrow-left"></i> Back to Cart
            </a>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <strong>Please fix the following errors:</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="../actions/checkout_action.php" class="checkout-layout">
                <!-- Checkout Form -->
                <div class="checkout-form">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <!-- Shipping Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-shipping-fast"></i> Shipping Information</h3>
                        
                        <div class="form-group full-width">
                            <label for="shipping_address">Shipping Address *</label>
                            <textarea id="shipping_address" name="shipping_address" required 
                                      placeholder="Enter your complete shipping address"><?= htmlspecialchars($_POST['shipping_address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_city">City *</label>
                                <input type="text" id="shipping_city" name="shipping_city" required 
                                       value="<?= htmlspecialchars($_POST['shipping_city'] ?? '') ?>" 
                                       placeholder="Enter city">
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_state">State *</label>
                                <input type="text" id="shipping_state" name="shipping_state" required 
                                       value="<?= htmlspecialchars($_POST['shipping_state'] ?? '') ?>" 
                                       placeholder="Enter state">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_zip">ZIP Code *</label>
                                <input type="text" id="shipping_zip" name="shipping_zip" required 
                                       value="<?= htmlspecialchars($_POST['shipping_zip'] ?? '') ?>" 
                                       placeholder="Enter ZIP code">
                            </div>
                            
                            <div class="form-group">
                                <label for="shipping_phone">Phone Number *</label>
                                <input type="tel" id="shipping_phone" name="shipping_phone" required 
                                       value="<?= htmlspecialchars($_POST['shipping_phone'] ?? '') ?>" 
                                       placeholder="Enter phone number">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="form-section">
                        <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                        
                        <div class="payment-methods">
                            <div class="payment-option" onclick="selectPayment('cod')">
                                <input type="radio" name="payment_method" value="cod" id="cod" required>
                                <label for="cod">
                                    <i class="fas fa-money-bill-wave"></i><br>
                                    Cash on Delivery
                                </label>
                            </div>
                            
                            <div class="payment-option" onclick="selectPayment('bank')">
                                <input type="radio" name="payment_method" value="bank" id="bank" required>
                                <label for="bank">
                                    <i class="fas fa-university"></i><br>
                                    Bank Transfer
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <div class="order-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="order-item">
                                <img src="image.php?file=<?= urlencode($item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="order-item-image">
                                
                                <div class="order-item-details">
                                    <div class="order-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="order-item-seller">by <?= htmlspecialchars($item['seller_name']) ?></div>
                                </div>
                                
                                <div class="order-item-price">
                                    <?= format_price($item['price'] * $item['quantity']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
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
                    
                    <button type="submit" class="place-order-btn">
                        <i class="fas fa-lock"></i> Place Order Securely
                    </button>
                    
                    <p style="text-align: center; margin-top: 1rem; color: #666; font-size: 0.9rem;">
                        <i class="fas fa-shield-alt"></i> Your payment information is secure
                    </p>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function selectPayment(method) {
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById(method).checked = true;
        }
        
        // Auto-select first payment method
        document.addEventListener('DOMContentLoaded', function() {
            const firstPayment = document.querySelector('.payment-option');
            if (firstPayment) {
                firstPayment.classList.add('selected');
            }
        });
    </script>
</body>
</html>
