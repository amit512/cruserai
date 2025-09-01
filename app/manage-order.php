<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// --- Handle status update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=? AND seller_id=?");
    $stmt->execute([$status, $orderId, $user['id']]);
    header("Location: manage-orders.php?updated=1");
    exit;
}

// --- Fetch all orders for seller ---
$stmt = $pdo->prepare("SELECT o.id, o.quantity, o.total, o.status, o.created_at, 
                              u.name AS buyer_name, p.name AS product_name
                       FROM orders o
                       JOIN users u ON o.buyer_id = u.id
                       JOIN products p ON o.product_id = p.id
                       WHERE o.seller_id = ?
                       ORDER BY o.created_at DESC");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Orders - HandCraft</title>

<link rel="stylesheet" href="../public/seller-dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="page-wrapper">
<header class="main-header">
  <div class="logo"><span>Hand</span>Craft</div>
  <nav class="nav-links">
    <ul>
      <li><a href="../public/seller-dashboard.php">Dashboard</a></li>
      <li><a href="manage-products.php">Products</a></li>
      <li><a href="manage-order.php" class="active">Orders</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($user['name']) ?></span>
    <a href="../public/logout.php" class="btn login">Logout</a>
  </div>
</header>

<section class="orders-section">
  <h1>Manage Orders</h1>

  <?php if ($orders): ?>
    <table>
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Buyer</th>
          <th>Product</th>
          <th>Quantity</th>
          <th>Total</th>
          <th>Status</th>
          <th>Update</th>
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
          <td>Rs<?= number_format($order['total'], 2) ?></td>
          <td><?= htmlspecialchars($order['status']) ?></td>
          <td>
            <form method="post" class="status-form">
              <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
              <select name="status">
                <option value="Pending" <?= $order['status']=="Pending"?"selected":"" ?>>Pending</option>
                <option value="Processing" <?= $order['status']=="Processing"?"selected":"" ?>>Processing</option>
                <option value="Shipped" <?= $order['status']=="Shipped"?"selected":"" ?>>Shipped</option>
                <option value="Completed" <?= $order['status']=="Completed"?"selected":"" ?>>Completed</option>
                <option value="Cancelled" <?= $order['status']=="Cancelled"?"selected":"" ?>>Cancelled</option>
              </select>
              <button type="submit" class="btn">Update</button>
            </form>
          </td>
          <td><?= date("d M Y", strtotime($order['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="empty-message">No orders yet.</p>
  <?php endif; ?>
</section>
  </div>
<section>
<footer class="footer">
  <div class="footer-container">
    <div class="footer-section">
      <h4>HandCraft</h4>
      <p>Sell your handmade products easily and efficiently.</p>
    </div>
    <div class="footer-section">
      <h4>Quick Links</h4>
      <a href="seller-dashboard.php">Dashboard</a>
      <a href="../app/manage-products.php">Products</a>
      <a href="manage-orders.php">Orders</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date("Y") ?> HandCraft. All rights reserved.</p>
  </div>
</footer>
</section>
</body>
</html>
