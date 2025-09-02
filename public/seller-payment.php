<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    header('Location: login.php');
    exit;
}

$pdo = db();
$user = $_SESSION['user'];

// Ensure table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS seller_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        note VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_seller_id (seller_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Exception $e) {}

// Fetch recent payments
$payments = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM seller_payments WHERE seller_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$user['id']]);
    $payments = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Seller Payments - HomeCraft</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="handcraf.css"/>
  <link rel="stylesheet" href="startstyle.css"/>
</head>
<body style="max-width:900px;margin:2rem auto;padding:1rem;">
  <h1>Submit Payment</h1>
  <form action="../actions/seller_record_payment.php" method="post" style="margin:1rem 0;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <div style="margin-bottom:.5rem;">
      <label>Amount (Rs)</label><br>
      <input type="number" name="amount" step="0.01" min="0" required>
    </div>
    <div style="margin-bottom:.5rem;">
      <label>Note</label><br>
      <input type="text" name="note" placeholder="Reference / remarks">
    </div>
    <button class="btn btn-primary" type="submit">Submit</button>
  </form>

  <h2>Recent Payments</h2>
  <table>
    <thead><tr><th>ID</th><th>Amount</th><th>Note</th><th>Date</th></tr></thead>
    <tbody>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td><?php echo (int)$p['id']; ?></td>
        <td>Rs <?php echo number_format($p['amount'],2); ?></td>
        <td><?php echo htmlspecialchars($p['note'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($p['created_at']); ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($payments)): ?>
      <tr><td colspan="4">No payments yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>

