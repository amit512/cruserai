<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';

verify_csrf();

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    die('Unauthorized');
}

$userId = (int) $_SESSION['user']['id'];
$reviewId = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$comment = trim($_POST['comment'] ?? '');

if ($reviewId <= 0 || $productId <= 0 || $comment === '') {
    http_response_code(422);
    die('Invalid input');
}

try {
    // Ensure table exists
    Database::pdo()->exec("CREATE TABLE IF NOT EXISTS review_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_review_id (review_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Basic validation: review exists and belongs to product
    $stmt = Database::pdo()->prepare("SELECT id FROM product_reviews WHERE id = ? AND product_id = ?");
    $stmt->execute([$reviewId, $productId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        die('Review not found');
    }

    $insert = Database::pdo()->prepare("INSERT INTO review_comments (review_id, user_id, comment) VALUES (?, ?, ?)");
    $insert->execute([$reviewId, $userId, $comment]);

    // Redirect back
    $inPublicRoot = isset($_SERVER['SCRIPT_NAME']) && (strpos($_SERVER['SCRIPT_NAME'], '/public/') !== false || basename(dirname($_SERVER['SCRIPT_NAME'])) === 'public');
    $redirectUrl = $inPublicRoot ? ('product-reviews.php?product_id=' . $productId) : ('/public/product-reviews.php?product_id=' . $productId);
    header('Location: ' . $redirectUrl);
    exit;
} catch (Exception $e) {
    error_log('Review comment create failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Failed to add comment.';
}

