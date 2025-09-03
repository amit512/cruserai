<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';

$user = $_SESSION['user'] ?? null;
if (!$user || !is_buyer()) {
    http_response_code(403);
    die('Please log in as a buyer to view tracking.');
}

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($orderId <= 0) {
    http_response_code(400);
    die('Order ID is required.');
}

// Basic order lookup to show summary if possible
$order = null;
try {
    $stmt = Database::pdo()->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
    $stmt->execute([$orderId, (int)$user['id']]);
    $order = $stmt->fetch();
} catch (Exception $e) {
    // orders table may vary
}

$events = [];
try {
    $stmt = Database::pdo()->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY tracked_at ASC, id ASC");
    $stmt->execute([$orderId]);
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to fetch tracking: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking #<?= (int)$orderId ?> | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="handcraf.css">
    <link rel="stylesheet" href="startstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 1000px; margin: 0 auto; padding: 2rem 1rem; }
        .page-title { font-size: 2rem; margin-bottom: .5rem; }
        .muted { color: #666; }
        .timeline { position: relative; padding-left: 2rem; }
        .timeline:before { content: ''; position: absolute; left: 12px; top: 0; bottom: 0; width: 2px; background: #e5e7eb; }
        .event { position: relative; margin: 1rem 0 1rem 0; padding-left: 1rem; }
        .event:before { content: ''; position: absolute; left: -6px; top: 6px; width: 14px; height: 14px; border-radius: 50%; background: #4CAF50; box-shadow: 0 0 0 4px #e8f5e9; }
        .event .title { font-weight: 700; margin-bottom: .25rem; }
        .event .meta { font-size: .9rem; color: #666; margin-bottom: .25rem; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); padding: 1.25rem; }
        .header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1rem; }
        .ext-link { color: #2196F3; text-decoration: none; }
        .ext-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container">
        <div class="header">
            <div>
                <div class="page-title">Tracking Order #<?= (int)$orderId ?></div>
                <?php if ($order): ?>
                    <div class="muted">Placed on <?= htmlspecialchars(date('F j, Y', strtotime($order['created_at'] ?? 'now'))) ?> • Status: <?= htmlspecialchars($order['status'] ?? '—') ?></div>
                <?php endif; ?>
            </div>
            <div>
                <a class="ext-link" href="my-orders.php">Back to My Orders</a>
            </div>
        </div>

        <div class="card">
            <?php if (empty($events)): ?>
                <p class="muted">No tracking updates yet. Please check back later.</p>
            <?php else: ?>
                <?php
                    $finalTrackingUrl = null;
                    foreach ($events as $ev) { if (!empty($ev['tracking_url'])) { $finalTrackingUrl = $ev['tracking_url']; }}
                ?>
                <?php if ($finalTrackingUrl): ?>
                    <p>Carrier Tracking: <a class="ext-link" href="<?= htmlspecialchars($finalTrackingUrl) ?>" target="_blank" rel="noopener">Open carrier site</a></p>
                <?php endif; ?>
                <div class="timeline">
                    <?php foreach ($events as $ev): ?>
                        <div class="event">
                            <div class="title"><?= htmlspecialchars($ev['status']) ?></div>
                            <div class="meta">
                                <?= htmlspecialchars(date('M j, Y g:i A', strtotime($ev['tracked_at']))) ?>
                                <?php if (!empty($ev['location'])): ?> • <?= htmlspecialchars($ev['location']) ?><?php endif; ?>
                            </div>
                            <?php if (!empty($ev['description'])): ?>
                                <div><?= nl2br(htmlspecialchars($ev['description'])) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($ev['tracking_number']) && !empty($ev['carrier'])): ?>
                                <div class="muted">Tracking #: <?= htmlspecialchars($ev['tracking_number']) ?> • Carrier: <?= htmlspecialchars($ev['carrier']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

