<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';

$user = $_SESSION['user'] ?? null;
$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;

try {
    $product = $productId ? Product::find($productId) : null;
} catch (Exception $e) {
    error_log('Error fetching product for reviews: ' . $e->getMessage());
    $product = null;
}

if (!$product) {
    http_response_code(404);
    echo '<h1>Product Not Found</h1>';
    echo "<a href='catalog.php'>Back to Catalog</a>";
    exit;
}

// Fetch existing reviews (customer_feedback joined by order to buyer if needed)
$reviews = [];
try {
    $stmt = Database::pdo()->prepare("SELECT cf.*, u.name AS customer_name
        FROM customer_feedback cf
        JOIN users u ON u.id = cf.customer_id
        WHERE cf.seller_id = ? AND cf.is_public = 1
        ORDER BY cf.created_at DESC");
    $stmt->execute([$product['seller_id']]);
    $reviews = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to fetch reviews: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - <?= htmlspecialchars($product['name']) ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="handcraf.css">
    <link rel="stylesheet" href="startstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem; }
        .page-title { font-size: 2rem; margin-bottom: 1rem; }
        .product-summary { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .product-summary img { width: 72px; height: 72px; object-fit: cover; border-radius: 8px; }
        .reviews-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); padding: 1.25rem; }
        .review { border-bottom: 1px solid #eee; padding: 1rem 0; }
        .review:last-child { border-bottom: none; }
        .stars { color: #f5a623; }
        .media-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; margin-top: .5rem; }
        .media-grid img { width: 100%; height: 90px; object-fit: cover; border-radius: 6px; }
        .muted { color: #666; font-size: .9rem; }
        .btn { display: inline-block; background: #4CAF50; color: #fff; padding: .6rem 1rem; border-radius: 8px; text-decoration: none; border: none; cursor: pointer; }
        .btn.secondary { background: #2196F3; }
        .form-row { margin-bottom: .75rem; }
        .form-row label { display: block; font-weight: 600; margin-bottom: .25rem; }
        .form-row input[type="file"], .form-row textarea, .form-row select { width: 100%; padding: .6rem; border: 1px solid #ddd; border-radius: 8px; }
        .empty { text-align: center; padding: 2rem; color: #666; }
        @media (max-width: 900px) { .reviews-grid { grid-template-columns: 1fr; } }
    </style>
    </head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container">
        <div class="page-title">Reviews for <?= htmlspecialchars($product['name']) ?></div>
        <div class="product-summary">
            <?php if (!empty($product['image'])): ?>
                <img src="image.php?file=<?= urlencode($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <div style="width:72px;height:72px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#999;">
                    <i class="fas fa-image"></i>
                </div>
            <?php endif; ?>
            <div>
                <div><a href="product.php?id=<?= (int)$product['id'] ?>" class="muted">Back to product</a></div>
                <div class="muted">Category: <?= htmlspecialchars(ucfirst($product['category'])) ?> • Seller: <?= htmlspecialchars($product['seller_name']) ?></div>
            </div>
        </div>

        <div class="reviews-grid">
            <div class="card">
                <?php if (empty($reviews)): ?>
                    <div class="empty">No reviews yet. Be the first to review this product!</div>
                <?php else: ?>
                    <?php foreach ($reviews as $r): ?>
                        <div class="review">
                            <div class="stars">
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <?php if ($i <= (int)$r['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div style="margin:.25rem 0 .5rem 0; font-weight:600;"><?= htmlspecialchars($r['customer_name'] ?? 'Buyer') ?></div>
                            <div class="muted" style="margin-bottom:.5rem;"><?= date('F j, Y', strtotime($r['created_at'])) ?></div>
                            <?php if (!empty($r['review'])): ?>
                                <div><?= nl2br(htmlspecialchars($r['review'])) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($r['images'])): ?>
                                <?php $imgs = @json_decode($r['images'], true) ?: []; ?>
                                <?php if ($imgs): ?>
                                    <div class="media-grid">
                                        <?php foreach ($imgs as $img): ?>
                                            <img src="uploads/<?= htmlspecialchars(basename($img)) ?>" alt="review media">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3 style="margin-top:0;">Write a review</h3>
                <?php if (!$user || !is_buyer()): ?>
                    <p class="muted">Please <a href="login.php">log in</a> as a buyer to write a review.</p>
                <?php else: ?>
                    <form action="../actions/review_create.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <div class="form-row">
                            <label for="rating">Rating</label>
                            <select id="rating" name="rating" required>
                                <option value="">Select</option>
                                <?php for ($i=5; $i>=1; $i--): ?>
                                    <option value="<?= $i ?>"><?= $i ?> star<?= $i>1?'s':'' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="comment">Comment</label>
                            <textarea id="comment" name="comment" rows="5" placeholder="Share details about quality, fit, shipping, etc."></textarea>
                        </div>
                        <div class="form-row">
                            <label for="images">Add photos (optional)</label>
                            <input type="file" id="images" name="images[]" accept="image/*" multiple>
                            <div class="muted">Up to 5 images, max 2MB each.</div>
                        </div>
                        <button class="btn" type="submit">Submit review</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

