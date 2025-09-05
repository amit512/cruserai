<?php
session_start();
require_once __DIR__ . '/../config/config.php';

$pdo = db();

// Update order status
if (isset($_POST['update_status'])) {
    $id = (int) $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->execute([$status, $id]);
    header("Location: manage-orders.php");
    exit;
}

// Get all orders
$orders = $pdo->query("SELECT o.id, u.name AS buyer, p.name AS product, o.quantity, o.total, o.status, o.created_at
                       FROM orders o
                       JOIN users u ON o.buyer_id = u.id
                       JOIN products p ON o.product_id = p.id
                       ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Helper
function e($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Orders - HomeCraft</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex bg-gray-100 min-h-screen">

  <!-- Sidebar -->
  <aside class="w-64 bg-white shadow-lg h-screen p-6">
    <h1 class="text-2xl font-bold text-indigo-600 mb-6">HomeCraft Admin</h1>
    <nav class="space-y-4">
      <a href="admin-dashboard.php" class="block text-gray-700 font-medium hover:text-indigo-600">📊 Dashboard</a>
      <a href="manage-orders.php" class="block text-gray-700 font-medium hover:text-indigo-600">📦 Orders</a>
      <a href="manage-products.php" class="block text-gray-700 font-medium hover:text-indigo-600">🛒 Products</a>
      <a href="manage-users.php" class="block text-gray-700 font-medium hover:text-indigo-600">👤 Customers</a>
      <a href="manage-seller-payments.php" class="block text-gray-700 font-medium hover:text-indigo-600">💳 Seller Payments</a>
      <a href="../public/logout.php" class="block text-red-600 font-medium hover:text-red-800 mt-6">🚪 Logout</a>

    </nav>
    <div class="mt-10 flex items-center space-x-3">
      <img src="assets/img/admin-avatar.png" class="w-10 h-10 rounded-full border" alt="Admin">
      <span class="font-semibold"><?= e($_SESSION['user']['name'] ?? 'Admin') ?></span>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-8 overflow-y-auto">
    <h1 class="text-3xl font-bold mb-6">Manage Orders</h1>

    <div class="bg-white shadow rounded-lg p-6">
      <table class="min-w-full border border-gray-200 text-left text-sm rounded-lg">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-3 border-b">ID</th>
            <th class="p-3 border-b">Buyer</th>
            <th class="p-3 border-b">Product</th>
            <th class="p-3 border-b">Qty</th>
            <th class="p-3 border-b">Total</th>
            <th class="p-3 border-b">Status</th>
            <th class="p-3 border-b">Date</th>
            <th class="p-3 border-b">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr class="hover:bg-gray-50">
            <td class="p-3 border-b"><?= e($o['id']) ?></td>
            <td class="p-3 border-b"><?= e($o['buyer']) ?></td>
            <td class="p-3 border-b"><?= e($o['product']) ?></td>
            <td class="p-3 border-b"><?= e($o['quantity']) ?></td>
            <td class="p-3 border-b">Rs <?= number_format($o['total'],2) ?></td>
            <td class="p-3 border-b"><?= ucfirst(e($o['status'])) ?></td>
            <td class="p-3 border-b"><?= e($o['created_at']) ?></td>
            <td class="p-3 border-b">
              <form method="post" class="flex space-x-2">
                <input type="hidden" name="order_id" value="<?= e($o['id']) ?>">
                <select name="status" class="border rounded px-2 py-1 text-sm">
                  <option value="pending" <?= $o['status']=='pending'?'selected':'' ?>>Pending</option>
                  <option value="shipped" <?= $o['status']=='shipped'?'selected':'' ?>>Shipped</option>
                  <option value="delivered" <?= $o['status']=='delivered'?'selected':'' ?>>Delivered</option>
                  <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                </select>
                <button type="submit" name="update_status" class="bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700 text-sm">Update</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

</body>
</html>
