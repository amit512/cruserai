<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Admin check (optional)
// ...

$pdo = db();

// Delete user
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    header("Location: manage-users.php");
    exit;
}

// Get all users
$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Helper
function e($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Customers - HomeCraft</title>
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
    <h1 class="text-3xl font-bold mb-6">Manage Customers</h1>

    <div class="bg-white shadow rounded-lg p-6">
      <table class="min-w-full text-left text-sm border border-gray-200 rounded-lg">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-3 border-b">ID</th>
            <th class="p-3 border-b">Name</th>
            <th class="p-3 border-b">Email</th>
            <th class="p-3 border-b">Role</th>
            <th class="p-3 border-b">Joined</th>
            <th class="p-3 border-b">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
          <tr class="hover:bg-gray-50">
            <td class="p-3 border-b"><?= e($user['id']) ?></td>
            <td class="p-3 border-b"><?= e($user['name']) ?></td>
            <td class="p-3 border-b"><?= e($user['email']) ?></td>
            <td class="p-3 border-b"><?= ucfirst(e($user['role'])) ?></td>
            <td class="p-3 border-b"><?= e($user['created_at']) ?></td>
            <td class="p-3 border-b">
              <a href="manage-users.php?delete=<?= $user['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Delete this user?')">🗑 Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

</body>
</html>
