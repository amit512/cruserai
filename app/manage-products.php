<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// Fetch all products of this seller
$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Products - HandCraft</title>
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
      <li><a href="manage-products.php" class="active">Products</a></li>
      <li><a href="manage-order.php">Orders</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($user['name']) ?></span>
    <a href="logout.php" class="btn login">Logout</a>
  </div>
</header>

<section class="dashboard-hero">
  <h1>Manage Your Products</h1>
  <p>Add, edit, or remove your handcrafted items.</p>
</section>

<section class="dashboard-products">
  <a href="../app/add-product.php" class="btn">+ Add New Product</a>

  <?php if ($products): ?>
    <table>
      <thead>
        <tr>
          <th>Image</th>
          <th>Name</th>
          <th>Description</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Created At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $product): ?>
        <tr>
          <td>
            <img src="image.php?file=<?php echo urlencode($product['image']); ?>" 
                 alt="<?= htmlspecialchars($product['name']) ?>" width="120"> 
          </td>
          <td><?= htmlspecialchars($product['name']) ?></td>
          <td><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</td>
          <td>Rs<?= number_format($product['price'], 2) ?></td>
          <td><?= $product['stock'] ?></td>
          <td><?= date("d M Y", strtotime($product['created_at'])) ?></td>
          <td>
            <a href="../seller/edit-product.php?id=<?= $product['id'] ?>" class="btn">Edit</a>
            <a href="delete-product.php?id=<?= $product['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="empty-message">You haven’t added any products yet. Start by clicking "Add New Product".</p>
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
