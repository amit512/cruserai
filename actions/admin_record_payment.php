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
$amount = (float)($_POST['amount'] ?? 0);
$note = trim($_POST['note'] ?? '');

if ($sellerId <= 0 || $amount <= 0) { http_response_code(422); die('Invalid input'); }

$pdo = db();

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS seller_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        note VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_seller_id (seller_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $stmt = $pdo->prepare("INSERT INTO seller_payments (seller_id, amount, note) VALUES (?, ?, ?)");
    $stmt->execute([$sellerId, $amount, $note]);

    // After payment, optionally unfreeze if due falls below threshold
    try {
        // Load threshold and compute due quickly
        $rate = 5.0;
        $stmt = $pdo->prepare("SELECT commission_rate FROM commission_structure WHERE seller_id = ? ORDER BY effective_from DESC LIMIT 1");
        $stmt->execute([$sellerId]);
        $row = $stmt->fetch(); if ($row) $rate = (float)$row['commission_rate'];

        $delivered = 0.0;
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(total),0) FROM orders WHERE seller_id = ? AND status='Delivered'");
        $stmt->execute([$sellerId]);
        $delivered = (float)$stmt->fetchColumn();
        $accrued = round($delivered * ($rate/100.0), 2);

        $paid = 0.0;
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(amount),0) FROM seller_payments WHERE seller_id = ?");
        $stmt->execute([$sellerId]);
        $paid = (float)$stmt->fetchColumn();
        $due = max(0.0, $accrued - $paid);

        $threshold = 1000.00; $acc = null;
        $stmt = $pdo->prepare("SELECT freeze_threshold FROM seller_accounts WHERE seller_id = ?");
        $stmt->execute([$sellerId]);
        $acc = $stmt->fetch(); if ($acc) $threshold = (float)$acc['freeze_threshold'];

        if ($due <= $threshold) {
            $pdo->prepare("UPDATE seller_accounts SET is_frozen = 0 WHERE seller_id = ?")->execute([$sellerId]);
        }
    } catch (Exception $e) {}

    header('Location: ../admin/billing.php');
    exit;
} catch (Exception $e) {
    error_log('admin_record_payment failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Failed to record payment.';
}

