<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../public/login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $shippingCity = trim($_POST['shipping_city'] ?? '');
    $shippingState = trim($_POST['shipping_state'] ?? '');
    $shippingZip = trim($_POST['shipping_zip'] ?? '');
    $shippingPhone = trim($_POST['shipping_phone'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($shippingAddress)) $errors[] = "Shipping address is required";
    if (empty($shippingCity)) $errors[] = "City is required";
    if (empty($shippingState)) $errors[] = "State is required";
    if (empty($shippingZip)) $errors[] = "ZIP code is required";
    if (empty($shippingPhone)) $errors[] = "Phone number is required";
    if (empty($paymentMethod)) $errors[] = "Payment method is required";
    
    if (empty($errors)) {
        try {
            // Fetch cart items for checkout
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
            
            if (empty($cartItems)) {
                header('Location: ../public/cart.php?error=empty_cart');
                exit;
            }
            
            $pdo->beginTransaction();
            
            // Create orders for each cart item
            foreach ($cartItems as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO orders (buyer_id, seller_id, product_id, quantity, total, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
                ");
                $stmt->execute([
                    $user['id'],
                    $item['seller_id'],
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'] * $item['quantity']
                ]);
                
                $orderId = $pdo->lastInsertId();
                
                // Save shipping details
                $stmt = $pdo->prepare("
                    INSERT INTO order_details (order_id, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_phone, payment_method)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderId,
                    $shippingAddress,
                    $shippingCity,
                    $shippingState,
                    $shippingZip,
                    $shippingPhone,
                    $paymentMethod
                ]);
                
                // Update product stock
                $stmt = $pdo->prepare("
                    UPDATE products SET stock = stock - ? WHERE id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            $pdo->commit();
            
            // Redirect to success page with the first order ID
            header('Location: ../public/order-success.php?order_id=' . $orderId);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Checkout error: " . $e->getMessage());
            header('Location: ../public/checkout.php?error=checkout_failed');
            exit;
        }
    } else {
        // Redirect back with errors
        $errorString = urlencode(implode('|', $errors));
        header('Location: ../public/checkout.php?errors=' . $errorString);
        exit;
    }
} else {
    // If not POST request, redirect to cart
    header('Location: ../public/cart.php');
    exit;
}
?>
