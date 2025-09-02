<?php
// admin-dashboard.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

$pdo = db();

// helper
function e($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

try {
    // counts
    $totalUsers     = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalSellers   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='seller'")->fetchColumn();
    $totalBuyers    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='buyer'")->fetchColumn();
    $totalProducts  = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalOrders    = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $pendingOrders  = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status='Pending'")->fetchColumn();
    $completedOrders= (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status='Delivered'")->fetchColumn();
    $cancelledOrders= (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status='Cancelled'")->fetchColumn();
    $totalRevenue   = (float) $pdo->query("SELECT IFNULL(SUM(total),0) FROM orders WHERE status='Delivered'")->fetchColumn();

    // recent
    $recentUsers = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $recentProducts = $pdo->query("SELECT p.id, p.name, p.price, u.name AS seller, p.created_at 
                                   FROM products p JOIN users u ON p.seller_id = u.id 
                                   ORDER BY p.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $recentOrders = $pdo->query("SELECT o.id, u.name AS buyer, p.name AS product, o.quantity, o.total, o.status, o.created_at 
                                 FROM orders o JOIN users u ON o.buyer_id = u.id 
                                 JOIN products p ON o.product_id = p.id 
                                 ORDER BY o.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // revenue trend for chart (last 7 days)
    $stmt = $pdo->query("SELECT DATE(created_at) as day, SUM(total) as revenue 
                         FROM orders 
                         WHERE status='Delivered' 
                         GROUP BY DATE(created_at) 
                         ORDER BY day DESC LIMIT 7");
    $chartData = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

    // Recent Reviews and Top Rated panels
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            rating TINYINT NOT NULL,
            comment TEXT NULL,
            images JSON NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    } catch (Exception $e) {}

    $recentReviews = [];
    try {
        $recentReviews = $pdo->query("SELECT pr.id, pr.product_id, p.name AS product_name, pr.rating, pr.comment, pr.created_at, u.name AS user_name
                                      FROM product_reviews pr 
                                      JOIN products p ON pr.product_id = p.id
                                      JOIN users u ON pr.user_id = u.id
                                      ORDER BY pr.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $recentReviews = []; }

    $topRated = [];
    try {
        $topRated = $pdo->query("SELECT p.id, p.name, AVG(pr.rating) as avg_rating, COUNT(pr.id) as reviews
                                  FROM product_reviews pr
                                  JOIN products p ON pr.product_id = p.id
                                  GROUP BY pr.product_id, p.name
                                  HAVING reviews >= 3
                                  ORDER BY avg_rating DESC, reviews DESC
                                  LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $topRated = []; }

} catch (PDOException $ex) {
    die("Database error: " . $ex->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard - HomeCraft</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      <a href="../public/logout.php" class="block text-red-600 font-medium hover:text-red-800 mt-6">🚪 Logout</a>

    </nav>
    <div class="mt-10 flex items-center space-x-3">
      <img src="assets/img/admin-avatar.png" class="w-10 h-10 rounded-full border" alt="Admin">
      <span class="font-semibold"><?= e($_SESSION['user']['name'] ?? 'Admin') ?></span>
    </div>
  </aside>

  <!-- Main -->
  <main class="flex-1 p-8 overflow-y-auto">
    <h2 class="text-3xl font-bold mb-6">Welcome back, <?= e($_SESSION['user']['name'] ?? 'Admin') ?> 👋</h2>

    <!-- Stats Cards -->
    <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-10">
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <p class="text-gray-500">Total Users</p>
        <h3 class="text-2xl font-bold"><?= $totalUsers ?></h3>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <p class="text-gray-500">Sellers</p>
        <h3 class="text-2xl font-bold"><?= $totalSellers ?></h3>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <p class="text-gray-500">Buyers</p>
        <h3 class="text-2xl font-bold"><?= $totalBuyers ?></h3>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <p class="text-gray-500">Products</p>
        <h3 class="text-2xl font-bold"><?= $totalProducts ?></h3>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <p class="text-gray-500">Orders</p>
        <h3 class="text-2xl font-bold"><?= $totalOrders ?></h3>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <p class="text-gray-500">Pending</p>
        <h3 class="text-2xl font-bold text-yellow-600"><?= $pendingOrders ?></h3>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <p class="text-gray-500">Completed</p>
        <h3 class="text-2xl font-bold text-green-600"><?= $completedOrders ?></h3>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <p class="text-gray-500">Cancelled</p>
        <h3 class="text-2xl font-bold text-red-600"><?= $cancelledOrders ?></h3>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center col-span-2">
        <p class="text-gray-500">Revenue</p>
        <h3 class="text-2xl font-bold text-indigo-600">Rs <?= number_format($totalRevenue, 2) ?></h3>
      </div>
    </section>

    <!-- Chart -->
    <section class="bg-white p-6 rounded-xl shadow mb-10">
      <h3 class="text-xl font-semibold mb-4">Revenue (Last 7 Days)</h3>
      <canvas id="revenueChart" height="120"></canvas>
    </section>

    <!-- Recent Users -->
    <section class="bg-white p-6 rounded-xl shadow mb-10">
      <h3 class="text-xl font-semibold mb-4">Recent Users</h3>
      <table class="min-w-full border border-gray-200 rounded-lg text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Name</th>
            <th class="p-2 border">Email</th>
            <th class="p-2 border">Role</th>
            <th class="p-2 border">Joined</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recentUsers as $u): ?>
          <tr class="hover:bg-gray-50">
            <td class="p-2 border"><?= e($u['id']) ?></td>
            <td class="p-2 border"><?= e($u['name']) ?></td>
            <td class="p-2 border"><?= e($u['email']) ?></td>
            <td class="p-2 border"><?= ucfirst(e($u['role'])) ?></td>
            <td class="p-2 border"><?= e($u['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- Top Rated Products -->
    <section class="bg-white p-6 rounded-xl shadow mb-10">
      <h3 class="text-xl font-semibold mb-4">Top Rated Products</h3>
      <table class="min-w-full border border-gray-200 rounded-lg text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 border">Product</th>
            <th class="p-2 border">Avg Rating</th>
            <th class="p-2 border">Reviews</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($topRated as $t): ?>
          <tr class="hover:bg-gray-50">
            <td class="p-2 border"><?= e($t['name'] ?? $t['product_name'] ?? ('Product #' . ($t['id'] ?? ''))) ?></td>
            <td class="p-2 border"><?= number_format((float)$t['avg_rating'], 1) ?> / 5</td>
            <td class="p-2 border"><?= (int)$t['reviews'] ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($topRated)): ?>
          <tr><td colspan="3" class="p-3 text-center text-gray-500">No rating data yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </section>

    <!-- Recent Products -->
    <section class="bg-white p-6 rounded-xl shadow mb-10">
      <h3 class="text-xl font-semibold mb-4">Recent Products</h3>
      <table class="min-w-full border border-gray-200 rounded-lg text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Name</th>
            <th class="p-2 border">Seller</th>
            <th class="p-2 border">Price</th>
            <th class="p-2 border">Added</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recentProducts as $p): ?>
          <tr class="hover:bg-gray-50">
            <td class="p-2 border"><?= e($p['id']) ?></td>
            <td class="p-2 border"><?= e($p['name']) ?></td>
            <td class="p-2 border"><?= e($p['seller']) ?></td>
            <td class="p-2 border">Rs <?= number_format($p['price'],2) ?></td>
            <td class="p-2 border"><?= e($p['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- Recent Orders -->
    <section class="bg-white p-6 rounded-xl shadow">
      <h3 class="text-xl font-semibold mb-4">Recent Orders</h3>
      <table class="min-w-full border border-gray-200 rounded-lg text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Buyer</th>
            <th class="p-2 border">Product</th>
            <th class="p-2 border">Qty</th>
            <th class="p-2 border">Total</th>
            <th class="p-2 border">Status</th>
            <th class="p-2 border">Date</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recentOrders as $o): ?>
          <tr class="hover:bg-gray-50">
            <td class="p-2 border"><?= e($o['id']) ?></td>
            <td class="p-2 border"><?= e($o['buyer']) ?></td>
            <td class="p-2 border"><?= e($o['product']) ?></td>
            <td class="p-2 border"><?= e($o['quantity']) ?></td>
            <td class="p-2 border">Rs <?= number_format($o['total'],2) ?></td>
            <td class="p-2 border"><?= ucfirst(e($o['status'])) ?></td>
            <td class="p-2 border"><?= e($o['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- Recent Reviews -->
    <section class="bg-white p-6 rounded-xl shadow mt-10">
      <h3 class="text-xl font-semibold mb-4">Recent Reviews</h3>
      <table class="min-w-full border border-gray-200 rounded-lg text-sm">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Product</th>
            <th class="p-2 border">User</th>
            <th class="p-2 border">Rating</th>
            <th class="p-2 border">Comment</th>
            <th class="p-2 border">Date</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recentReviews as $r): ?>
          <tr class="hover:bg-gray-50">
            <td class="p-2 border"><?= e($r['id']) ?></td>
            <td class="p-2 border"><?= e($r['product_name']) ?></td>
            <td class="p-2 border"><?= e($r['user_name']) ?></td>
            <td class="p-2 border"><?= e($r['rating']) ?></td>
            <td class="p-2 border" style="max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e($r['comment'] ?? '') ?>"><?= e($r['comment'] ?? '') ?></td>
            <td class="p-2 border"><?= e($r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($recentReviews)): ?>
          <tr><td colspan="6" class="p-3 text-center text-gray-500">No reviews yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>

  <script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode(array_column($chartData, 'day')) ?>,
        datasets: [{
          label: 'Revenue (Rs)',
          data: <?= json_encode(array_column($chartData, 'revenue')) ?>,
          borderColor: 'rgb(79,70,229)',
          backgroundColor: 'rgba(79,70,229,0.1)',
          tension: 0.3,
          fill: true,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
      }
    });
  </script>
</body>
</html>
