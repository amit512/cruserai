<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// --- Fetch stats ---
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_products FROM products WHERE seller_id=?");
$stmt->execute([$user['id']]);
$totalProducts = $stmt->fetch()['total_products'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_orders FROM orders WHERE seller_id=?");
$stmt->execute([$user['id']]);
$totalOrders = $stmt->fetch()['total_orders'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) AS pending_orders FROM orders WHERE seller_id=? AND status='Pending'");
$stmt->execute([$user['id']]);
$pendingOrders = $stmt->fetch()['pending_orders'] ?? 0;

// --- Fetch products ---
$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id=? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$products = $stmt->fetchAll();

// --- Fetch recent orders ---
$stmt = $pdo->prepare("SELECT o.id, o.quantity, o.total, o.status, o.created_at, u.name AS buyer_name, p.name AS product_name 
                       FROM orders o
                       JOIN users u ON o.buyer_id = u.id
                       JOIN products p ON o.product_id = p.id
                       WHERE o.seller_id = ?
                       ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seller Dashboard - HandCraft</title>
<link rel="stylesheet" href="seller-dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="page-wrapper">
<header class="main-header">
  <div class="logo"><span>Hand</span>Craft</div>
  <nav class="nav-links">
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="seller-dashboard.php" class="active">Dashboard</a></li>
      <li><a href="../app/manage-products.php">Products</a></li>
      <li><a href="../app/manage-order.php">Orders</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($user['name']) ?></span>
    <a href="logout.php" class="btn login">Logout</a>
  </div>
</header>

<section class="dashboard-hero">
  <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
  <p>Manage your products and orders efficiently.</p>
</section>

<section class="dashboard-stats">
  <div class="stat">
    <i class="fas fa-box"></i>
    <h3><?= $totalProducts ?></h3>
    <p>Total Products</p>
  </div>
  <div class="stat">
    <i class="fas fa-shopping-cart"></i>
    <h3><?= $totalOrders ?></h3>
    <p>Total Orders</p>
  </div>
  <div class="stat">
    <i class="fas fa-hourglass-half"></i>
    <h3><?= $pendingOrders ?></h3>
    <p>Pending Orders</p>
  </div>
</section>

<section class="dashboard-products">
  <h2>Your Products</h2>
  <a href="../app/add-product.php" class="btn">Add New Product</a>

  <?php if ($products): ?>
    <table>
      <thead>
        <tr>
          <th>Image</th>
          <th>Name</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $product): ?>
        <tr>
          <td><img src="image.php?file=<?php echo urlencode($product['image']); ?>" width="120">
</td>
          <td><?= htmlspecialchars($product['name']) ?></td>
          <td>Rs<?= number_format($product['price'],2) ?></td>
          <td><?= $product['stock'] ?? 0 ?></td>
          <td>
            <a href="../seller/edit-product.php?id=<?= $product['id'] ?>" class="btn">Edit</a>
            <a href="../seller/delete-product.php?id=<?= $product['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="empty-message">You haven't added any products yet. Start by clicking "Add New Product".</p>
  <?php endif; ?>
</section>

<section class="dashboard-orders">
  <h2>Recent Orders</h2>
  <?php if ($orders): ?>
    <table>
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Buyer</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Total</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
          <td><?= $order['id'] ?></td>
          <td><?= htmlspecialchars($order['buyer_name']) ?></td>
          <td><?= htmlspecialchars($order['product_name']) ?></td>
          <td><?= $order['quantity'] ?></td>
          <td>Rs<?= number_format($order['total'],2) ?></td>
          <td><?= $order['status'] ?></td>
          <td><?= date("d M Y", strtotime($order['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="empty-message">No orders yet. Once buyers purchase your products, they will appear here.</p>
  <?php endif; ?>
</section>
  </div>
<footer class="footer">
  <div class="footer-container">
    <div class="footer-section">
      <h4>HandCraft</h4>
      <p>Sell your handmade products easily and efficiently.</p>
    </div>
    <div class="footer-section">
      <h4>Quick Links</h4>
      <a href="seller-dashboard.php">Dashboard</a>
      <a href="manage-products.php">Products</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date("Y") ?> HandCraft. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
