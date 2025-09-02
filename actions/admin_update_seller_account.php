<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';

verify_csrf();

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

$sellerId = (int)($_POST['seller_id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$threshold = isset($_POST['freeze_threshold']) ? (float)$_POST['freeze_threshold'] : null;

if ($sellerId <= 0) { http_response_code(422); die('Invalid input'); }

$pdo = db();

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS seller_accounts (
        seller_id INT PRIMARY KEY,
        is_frozen TINYINT(1) DEFAULT 0,
        freeze_threshold DECIMAL(10,2) DEFAULT 1000.00,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Upsert threshold
    if ($threshold !== null) {
        $stmt = $pdo->prepare("INSERT INTO seller_accounts (seller_id, freeze_threshold) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE freeze_threshold = VALUES(freeze_threshold)");
        $stmt->execute([$sellerId, $threshold]);
    }

    // Optional unfreeze
    if ($action === 'unfreeze') {
        $pdo->prepare("UPDATE seller_accounts SET is_frozen = 0 WHERE seller_id = ?")->execute([$sellerId]);
    }

    header('Location: ../admin/billing.php');
    exit;
} catch (Exception $e) {
    error_log('admin_update_seller_account failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Failed to update seller account.';
}

