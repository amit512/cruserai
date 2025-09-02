<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: ../public/login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['action'])) {
    $productId = (int)$_GET['id'];
    $action = $_GET['action'];
    
    // Verify the product belongs to this seller
    $stmt = $pdo->prepare("SELECT id, name, is_active FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $user['id']]);
    $product = $stmt->fetch();
    
    if ($product) {
        $newStatus = ($action === 'activate') ? 1 : 0;
        $statusText = ($action === 'activate') ? 'activated' : 'deactivated';
        
        // Update the product status
        $stmt = $pdo->prepare("UPDATE products SET is_active = ? WHERE id = ? AND seller_id = ?");
        $stmt->execute([$newStatus, $productId, $user['id']]);
        
        if ($stmt->rowCount() > 0) {
            // Success
            $_SESSION['success_message'] = "Product '{$product['name']}' has been {$statusText} successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update product status.";
        }
    } else {
        $_SESSION['error_message'] = "Product not found or you don't have permission to modify it.";
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

// Redirect back to manage products page
header('Location: manage-products.php');
exit;
?>