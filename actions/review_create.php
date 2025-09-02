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
$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
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

    // Ensure buyer purchased the product
    $purchased = false;
    try {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND product_id = ? AND status IN ('Delivered','Shipped','Pending')");
        $stmt->execute([$userId, $productId]);
        $purchased = ((int)$stmt->fetchColumn()) > 0;
    } catch (Exception $e) {
        $purchased = false;
    }
    if (!$purchased) {
        http_response_code(403);
        die('You can only review products you purchased.');
    }

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

    // Ensure product_reviews exists (no-op if already there)
    Product::ensureProductReviewsSchema();

    // Detect or add images column if missing
    $hasImagesColumn = false;
    try {
        Database::pdo()->query("SELECT images FROM product_reviews LIMIT 1");
        $hasImagesColumn = true;
    } catch (Exception $e) {
        try {
            Database::pdo()->exec("ALTER TABLE product_reviews ADD COLUMN images JSON NULL");
            $hasImagesColumn = true;
        } catch (Exception $e2) {
            $hasImagesColumn = false;
        }
    }

    // Insert into product_reviews with or without images based on schema
    if ($hasImagesColumn) {
        $stmt = Database::pdo()->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment, images) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$productId, $userId, $rating, $comment, $imagesJson]);
    } else {
        $stmt = Database::pdo()->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$productId, $userId, $rating, $comment]);
    }

    // Optional: update product_performance if table exists
    try {
        Database::pdo()->prepare("UPDATE product_performance SET total_reviews = total_reviews + 1, average_rating = (
            SELECT COALESCE(AVG(rating),0) FROM product_reviews WHERE product_id = ?
        ) WHERE product_id = ?")->execute([$productId, $productId]);
    } catch (Exception $e) {
        // ignore
    }

    header('Location: /public/product-reviews.php?product_id=' . $productId);
    exit;
} catch (PDOException $e) {
    // Handle duplicate review gracefully (unique user_id, product_id)
    if ($e->getCode() === '23000') {
        header('Location: /public/product-reviews.php?product_id=' . $productId . '&error=already_reviewed');
        exit;
    }
    error_log('Review create failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Failed to submit review.';
} catch (Exception $e) {
    error_log('Review create failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Failed to submit review.';
}

