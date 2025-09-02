<?php
require_once __DIR__ . '/../config/config.php';
verify_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); 
    die('Forbidden');
}

// Server-side freeze enforcement
try {
    $stmt = db()->prepare("SELECT is_frozen FROM seller_accounts WHERE seller_id = ?");
    $stmt->execute([ (int)$_SESSION['user']['id'] ]);
    $acc = $stmt->fetch();
    if ($acc && (int)$acc['is_frozen'] === 1) {
        http_response_code(403);
        die('Account frozen. Please clear dues.');
    }
} catch (Exception $e) {}

$product_id = (int)($_POST['product_id'] ?? 0);
$is_active = (int)($_POST['is_active'] ?? 0);

if ($product_id <= 0) {
    $_SESSION['flash'] = 'Invalid product ID.';
    header('Location: ../seller/products.php'); 
    exit;
}

try {
    $pdo = db();
    
    // Verify the product belongs to this seller
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$product_id, $_SESSION['user']['id']]);
    
    if (!$stmt->fetch()) {
        $_SESSION['flash'] = 'Product not found or not owned by you.';
        header('Location: ../seller/products.php'); 
        exit;
    }
    
    // Update the product status
    $stmt = $pdo->prepare("UPDATE products SET is_active = ? WHERE id = ? AND seller_id = ?");
    $stmt->execute([$is_active, $product_id, $_SESSION['user']['id']]);
    
    $status_text = $is_active ? 'activated' : 'deactivated';
    $_SESSION['flash'] = "Product successfully $status_text.";
    
} catch (Exception $e) {
    error_log("Product status toggle error: " . $e->getMessage());
    $_SESSION['flash'] = 'Failed to update product status. Please try again.';
}

header('Location: ../seller/products.php');
exit;
