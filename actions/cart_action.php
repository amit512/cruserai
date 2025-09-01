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
    
    // Handle remove from cart
    if (isset($_POST['remove_item'])) {
        $itemId = (int) $_POST['item_id'];
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$itemId, $user['id']]);
        header('Location: ../public/cart.php?message=item_removed');
        exit;
    }
    
    // Handle update quantity
    if (isset($_POST['update_quantity'])) {
        $itemId = (int) $_POST['item_id'];
        $quantity = (int) $_POST['quantity'];
        
        if ($quantity <= 0) {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
            $stmt->execute([$itemId, $user['id']]);
            header('Location: ../public/cart.php?message=item_removed');
        } else {
            // Check if quantity exceeds stock
            $stmt = $pdo->prepare("
                SELECT p.stock FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.id = ? AND ci.user_id = ?
            ");
            $stmt->execute([$itemId, $user['id']]);
            $stock = $stmt->fetchColumn();
            
            if ($quantity > $stock) {
                header('Location: ../public/cart.php?error=insufficient_stock&item_id=' . $itemId);
            } else {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $itemId, $user['id']]);
                header('Location: ../public/cart.php?message=quantity_updated');
            }
        }
        exit;
    }
    
    // Handle add to cart
    if (isset($_POST['add_to_cart'])) {
        $productId = (int) $_POST['product_id'];
        $quantity = (int) ($_POST['quantity'] ?? 1);
        
        if ($quantity <= 0) {
            header('Location: ../public/catalog.php?error=invalid_quantity');
            exit;
        }
        
        // Check if product exists and is active
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $stock = $stmt->fetchColumn();
        
        if (!$stock) {
            header('Location: ../public/catalog.php?error=product_not_found');
            exit;
        }
        
        if ($quantity > $stock) {
            header('Location: ../public/catalog.php?error=insufficient_stock');
            exit;
        }
        
        // Check if item already in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user['id'], $productId]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            // Update existing quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            if ($newQuantity > $stock) {
                header('Location: ../public/catalog.php?error=insufficient_stock');
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $existingItem['id']]);
            header('Location: ../public/cart.php?message=quantity_updated');
        } else {
            // Add new item to cart
            $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $productId, $quantity]);
            header('Location: ../public/cart.php?message=item_added');
        }
        exit;
    }
    
    // Handle clear cart
    if (isset($_POST['clear_cart'])) {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        header('Location: ../public/cart.php?message=cart_cleared');
        exit;
    }
    
} else {
    // If not POST request, redirect to cart
    header('Location: ../public/cart.php');
    exit;
}
?>
