<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/AccountManager.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

// Check if account is frozen
if (AccountManager::isAccountFrozen($_SESSION['user']['id'])) {
    header('Location: /homecraft-php/seller/payment-upload.php');
    exit;
}

$pdo = db();  // ✅ Initialize database connection
$user = $_SESSION['user'];

// --- Validate product id ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../public/seller-dashboard.php');
    exit;
}

$productId = (int) $_GET['id'];

// --- Make sure product belongs to this seller ---
$stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND seller_id = ?");
$stmt->execute([$productId, $user['id']]);
$product = $stmt->fetch();

if (!$product) {
    // Product not found or not owned by seller
    header('Location: ../public/seller-dashboard.php?error=notfound');
    exit;
}

// --- Delete product ---
$stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
$stmt->execute([$productId, $user['id']]);

// --- Delete image file (optional) ---
if ($product['image'] && file_exists(__DIR__ . '/../uploads/' . $product['image'])) {
    unlink(__DIR__ . '/../uploads/' . $product['image']);
}

header('Location: ../public/seller-dashboard.php?success=deleted');
exit;
?>
