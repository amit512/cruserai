<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

$pdo = db();

// Ensure tables exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS seller_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        note VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_seller_id (seller_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS seller_accounts (
        seller_id INT PRIMARY KEY,
        is_frozen TINYINT(1) DEFAULT 0,
        freeze_threshold DECIMAL(10,2) DEFAULT 1000.00,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Exception $e) {}

// Fetch sellers
$sellers = $pdo->query("SELECT id, name, email FROM users WHERE role='seller' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Helper to get commission rate
function get_commission_rate(PDO $pdo, int $sellerId): float {
    try {
        $stmt = $pdo->prepare("SELECT commission_rate FROM commission_structure WHERE seller_id = ? ORDER BY effective_from DESC LIMIT 1");
        $stmt->execute([$sellerId]);
        $row = $stmt->fetch();
        if ($row && isset($row['commission_rate'])) return (float)$row['commission_rate'];
    } catch (Exception $e) {}
    return 5.0; // default
}

// Build rows with financials
$rows = [];
foreach ($sellers as $s) {
    $sellerId = (int)$s['id'];
    $rate = get_commission_rate($pdo, $sellerId);
    $delivered = 0.0;
    try {
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(total),0) FROM orders WHERE seller_id = ? AND status='Delivered'");
        $stmt->execute([$sellerId]);
        $delivered = (float)$stmt->fetchColumn();
    } catch (Exception $e) {}
    $accrued = round($delivered * ($rate/100.0), 2);
    $paid = 0.0;
    try {
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(amount),0) FROM seller_payments WHERE seller_id = ?");
        $stmt->execute([$sellerId]);
        $paid = (float)$stmt->fetchColumn();
    } catch (Exception $e) {}
    $due = max(0.0, $accrued - $paid);
    $acc = ['is_frozen' => 0, 'freeze_threshold' => 1000.00];
    try {
        $stmt = $pdo->prepare("SELECT is_frozen, freeze_threshold FROM seller_accounts WHERE seller_id = ?");
        $stmt->execute([$sellerId]);
        $accRow = $stmt->fetch();
        if ($accRow) $acc = $accRow;
    } catch (Exception $e) {}

    $rows[] = [
        'seller' => $s,
        'rate' => $rate,
        'delivered' => $delivered,
        'accrued' => $accrued,
        'paid' => $paid,
        'due' => $due,
        'is_frozen' => (int)$acc['is_frozen'],
        'threshold' => (float)$acc['freeze_threshold']
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Billing - HomeCraft</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .badge { padding: 2px 8px; border-radius: 12px; font-size: 12px; }
  </style>
  </head>
<body class="bg-gray-100 flex">
  <!-- Sidebar -->
  <aside class="w-64 bg-white shadow-lg h-screen p-6">
    <h1 class="text-2xl font-bold text-indigo-600 mb-6">HomeCraft Admin</h1>
    <nav class="space-y-4">
      <a href="admin-dashboard.php" class="block text-gray-700 font-medium hover:text-indigo-600">📊 Dashboard</a>
      <a href="manage-orders.php" class="block text-gray-700 font-medium hover:text-indigo-600">📦 Orders</a>
      <a href="manage-products.php" class="block text-gray-700 font-medium hover:text-indigo-600">🛒 Products</a>
      <a href="manage-users.php" class="block text-gray-700 font-medium hover:text-indigo-600">👤 Customers</a>
      <a href="billing.php" class="block text-indigo-600 font-semibold">💳 Billing</a>
      <a href="../public/logout.php" class="block text-red-600 font-medium hover:text-red-800 mt-6">🚪 Logout</a>
    </nav>
    <div class="mt-10 flex items-center space-x-3">
      <img src="assets/img/admin-avatar.png" class="w-10 h-10 rounded-full border" alt="Admin">
      <span class="font-semibold"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin', ENT_QUOTES) ?></span>
    </div>
  </aside>

  <!-- Main -->
  <main class="flex-1 p-8 overflow-y-auto">
    <h2 class="text-3xl font-bold mb-6">Billing</h2>
    <div class="bg-white rounded-xl shadow p-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 border">Seller</th>
            <th class="p-2 border">Rate %</th>
            <th class="p-2 border">Delivered (Rs)</th>
            <th class="p-2 border">Accrued (Rs)</th>
            <th class="p-2 border">Paid (Rs)</th>
            <th class="p-2 border">Due (Rs)</th>
            <th class="p-2 border">Threshold</th>
            <th class="p-2 border">Status</th>
            <th class="p-2 border">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): $sid = (int)$r['seller']['id']; ?>
          <tr class="hover:bg-gray-50">
            <td class="p-2 border">
              <div class="font-semibold"><?php echo htmlspecialchars($r['seller']['name']); ?></div>
              <div class="text-gray-500"><?php echo htmlspecialchars($r['seller']['email']); ?></div>
            </td>
            <td class="p-2 border text-center"><?php echo number_format($r['rate'], 2); ?></td>
            <td class="p-2 border text-right"><?php echo number_format($r['delivered'], 2); ?></td>
            <td class="p-2 border text-right"><?php echo number_format($r['accrued'], 2); ?></td>
            <td class="p-2 border text-right"><?php echo number_format($r['paid'], 2); ?></td>
            <td class="p-2 border text-right font-semibold"><?php echo number_format($r['due'], 2); ?></td>
            <td class="p-2 border text-right">
              <form action="../actions/admin_update_seller_account.php" method="post" class="flex items-center gap-2 justify-end">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="seller_id" value="<?php echo $sid; ?>">
                <input type="number" name="freeze_threshold" step="0.01" min="0" value="<?php echo number_format($r['threshold'],2,'.',''); ?>" class="border rounded px-2 py-1 w-28">
                <button class="bg-indigo-600 text-white px-3 py-1 rounded">Save</button>
              </form>
            </td>
            <td class="p-2 border text-center">
              <?php if ($r['is_frozen']): ?>
                <span class="badge bg-red-100 text-red-700">Frozen</span>
              <?php else: ?>
                <span class="badge bg-green-100 text-green-700">Active</span>
              <?php endif; ?>
            </td>
            <td class="p-2 border">
              <form action="../actions/admin_record_payment.php" method="post" class="flex items-center gap-2">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="seller_id" value="<?php echo $sid; ?>">
                <input type="number" name="amount" step="0.01" min="0" placeholder="Amount" class="border rounded px-2 py-1 w-28" required>
                <input type="text" name="note" placeholder="Note" class="border rounded px-2 py-1">
                <button class="bg-green-600 text-white px-3 py-1 rounded">Record</button>
              </form>
              <?php if ($r['is_frozen']): ?>
                <form action="../actions/admin_update_seller_account.php" method="post" class="mt-2">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="seller_id" value="<?php echo $sid; ?>">
                  <input type="hidden" name="action" value="unfreeze">
                  <button class="bg-yellow-600 text-white px-3 py-1 rounded">Unfreeze</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>

