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
    
    // Handle add to wishlist
    if (isset($_POST['add_to_wishlist'])) {
        $productId = (int) $_POST['product_id'];
        
        // Check if product exists and is active
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        
        if (!$stmt->fetch()) {
            header('Location: ../public/catalog.php?error=product_not_found');
            exit;
        }
        
        // Check if already in wishlist
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user['id'], $productId]);
        
        if ($stmt->fetch()) {
            header('Location: ../public/catalog.php?error=already_in_wishlist');
            exit;
        }
        
        // Add to wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user['id'], $productId]);
        
        header('Location: ../public/catalog.php?message=added_to_wishlist');
        exit;
    }
    
    // Handle remove from wishlist
    if (isset($_POST['remove_from_wishlist'])) {
        $productId = (int) $_POST['product_id'];
        
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user['id'], $productId]);
        
        header('Location: ../public/wishlist.php?message=removed_from_wishlist');
        exit;
    }
    
    // Handle remove from wishlist (from dashboard)
    if (isset($_POST['remove_wishlist_item'])) {
        $wishlistId = (int) $_POST['wishlist_id'];
        
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
        $stmt->execute([$wishlistId, $user['id']]);
        
        header('Location: ../public/wishlist.php?message=removed_from_wishlist');
        exit;
    }
    
} else {
    // If not POST request, redirect to catalog
    header('Location: ../public/catalog.php');
    exit;
}
?>
