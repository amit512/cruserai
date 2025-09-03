<?php
session_start();
require_once __DIR__ . '/../config/config.php';

$pdo = db();

// Delete product
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$id]);
    header("Location: manage-products.php");
    exit;
}

// Get all products
$products = $pdo->query("SELECT p.id, p.name, p.price, u.name AS seller, p.created_at 
                         FROM products p 
                         JOIN users u ON p.seller_id = u.id 
                         ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Helper
function e($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Products - HomeCraft</title>
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
      <a href="billing.php" class="block text-indigo-600 font-semibold">💳 Billing</a>
    <a href="../public/logout.php" class="block text-red-600 font-medium hover:text-red-800 mt-6">🚪 Logout</a>
</nav>
    <div class="mt-10 flex items-center space-x-3">
      <img src="assets/img/admin-avatar.png" class="w-10 h-10 rounded-full border" alt="Admin">
      <span class="font-semibold"><?= e($_SESSION['user']['name'] ?? 'Admin') ?></span>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-8 overflow-y-auto">
    <h1 class="text-3xl font-bold mb-6">Manage Products</h1>

    <div class="bg-white shadow rounded-lg p-6">
      <table class="min-w-full text-left text-sm border border-gray-200 rounded-lg">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-3 border-b">ID</th>
            <th class="p-3 border-b">Name</th>
            <th class="p-3 border-b">Seller</th>
            <th class="p-3 border-b">Price</th>
            <th class="p-3 border-b">Created</th>
            <th class="p-3 border-b">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr class="hover:bg-gray-50">
            <td class="p-3 border-b"><?= e($p['id']) ?></td>
            <td class="p-3 border-b"><?= e($p['name']) ?></td>
            <td class="p-3 border-b"><?= e($p['seller']) ?></td>
            <td class="p-3 border-b">Rs <?= number_format($p['price'],2) ?></td>
            <td class="p-3 border-b"><?= e($p['created_at']) ?></td>
            <td class="p-3 border-b">
              <a href="manage-products.php?delete=<?= $p['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Delete this product?')">🗑 Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

</body>
</html>
