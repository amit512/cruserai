<?php
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to wishlist']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['product_id'])) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }
    
    $productId = (int) $input['product_id'];
    $userId = $_SESSION['user']['id'];
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    $pdo = db();
    
    // Check if product exists and is active
    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or not available']);
        exit;
    }
    
    // Check if item already exists in wishlist
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $existingItem = $stmt->fetch();
    
    if ($existingItem) {
        echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
        exit;
    }
    
    // Add to wishlist
    $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$userId, $productId]);
    
    // Get updated wishlist count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->execute([$userId]);
    $wishlistCount = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Product added to wishlist successfully',
        'wishlist_count' => $wishlistCount
    ]);
    
} catch (Exception $e) {
    error_log("Add to wishlist error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while adding to wishlist']);
}
?>
