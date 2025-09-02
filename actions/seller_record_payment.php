<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';

verify_csrf();

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403);
    die('Forbidden');
}

$sellerId = (int)$_SESSION['user']['id'];
$amount = (float)($_POST['amount'] ?? 0);
$note = trim($_POST['note'] ?? '');

if ($amount <= 0) { http_response_code(422); die('Invalid amount'); }

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

    // Unfreeze if due <= threshold
    try {
        $rate = 5.0;
        $stmt = $pdo->prepare("SELECT commission_rate FROM commission_structure WHERE seller_id = ? ORDER BY effective_from DESC LIMIT 1");
        $stmt->execute([$sellerId]); $row = $stmt->fetch(); if ($row) $rate = (float)$row['commission_rate'];

        $stmt = $pdo->prepare("SELECT IFNULL(SUM(total),0) FROM orders WHERE seller_id = ? AND status='Delivered'");
        $stmt->execute([$sellerId]); $delivered = (float)$stmt->fetchColumn();
        $accrued = round($delivered * ($rate/100.0), 2);

        $stmt = $pdo->prepare("SELECT IFNULL(SUM(amount),0) FROM seller_payments WHERE seller_id = ?");
        $stmt->execute([$sellerId]); $paid = (float)$stmt->fetchColumn();
        $due = max(0.0, $accrued - $paid);

        $threshold = 1000.00; $stmt = $pdo->prepare("SELECT freeze_threshold FROM seller_accounts WHERE seller_id = ?");
        $stmt->execute([$sellerId]); $r = $stmt->fetch(); if ($r) $threshold = (float)$r['freeze_threshold'];

        if ($due <= $threshold) {
            $pdo->prepare("UPDATE seller_accounts SET is_frozen = 0 WHERE seller_id = ?")->execute([$sellerId]);
        }
    } catch (Exception $e) {}

    header('Location: ../public/seller-payment.php');
    exit;
} catch (Exception $e) {
    error_log('seller_record_payment failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Failed to submit payment.';
}

