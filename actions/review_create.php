<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';

verify_csrf();

if (!isset($_SESSION['user']) || !is_buyer()) {
    http_response_code(403);
    die('Unauthorized');
}

$userId = (int) $_SESSION['user']['id'];
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$comment = trim($_POST['comment'] ?? '');

if ($productId <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(422);
    die('Invalid input');
}

try {
    $product = Product::find($productId);
    if (!$product) {
        throw new Exception('Product not found');
    }

    // Optional: ensure buyer purchased the product. Skipping due to unknown schema.

    // Handle image uploads (optional)
    $uploaded = [];
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $count = min(count($_FILES['images']['name']), 5);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['images']['size'][$i] > 2 * 1024 * 1024) continue; // 2MB limit
            $tmp = $_FILES['images']['tmp_name'][$i];
            $name = basename($_FILES['images']['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
            $safe = sha1_file($tmp) . '.' . $ext;
            $destDir = __DIR__ . '/../public/uploads/reviews';
            if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
            $dest = $destDir . '/' . $safe;
            if (move_uploaded_file($tmp, $dest)) {
                $uploaded[] = 'reviews/' . $safe;
            }
        }
    }

    $imagesJson = $uploaded ? json_encode($uploaded) : null;

    // Insert into customer_feedback (seller-level). If you prefer per-product, create product_reviews.
    $stmt = Database::pdo()->prepare("INSERT INTO customer_feedback (customer_id, seller_id, order_id, rating, review, is_public) VALUES (?, ?, ?, ?, ?, 1)");
    $orderId = null; // unknown without schema linkage
    $stmt->execute([$userId, (int)$product['seller_id'], $orderId, $rating, $comment]);

    // If images were uploaded, attempt to store in an images column if present
    try {
        if ($imagesJson) {
            Database::pdo()->prepare("ALTER TABLE customer_feedback ADD COLUMN images JSON NULL")->execute();
        }
    } catch (Exception $e) {
        // Column may already exist
    }
    if ($imagesJson) {
        $feedbackId = (int) Database::pdo()->lastInsertId();
        $upd = Database::pdo()->prepare("UPDATE customer_feedback SET images = ? WHERE id = ?");
        $upd->execute([$imagesJson, $feedbackId]);
    }

    header('Location: /public/product-reviews.php?product_id=' . $productId);
    exit;
} catch (Exception $e) {
    error_log('Review create failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Failed to submit review.';
}

