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
<title>Seller Dashboard - <?= SITE_NAME ?></title>
<link rel="stylesheet" href="handcraf.css"/>
<link rel="stylesheet" href="startstyle.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
  /* Seller Dashboard Specific Styles */
  body {
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    padding: 0;
    background: #f5f5f5;
  }
  
  .dashboard-container {
    min-height: 100vh;
  }
  
  .dashboard-hero {
    background: linear-gradient(135deg, #ff6b6b, #f0c987);
    color: white;
    text-align: center;
    padding: 3rem 0;
    margin-bottom: 2rem;
  }
  
  .dashboard-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    font-weight: bold;
  }
  
  .dashboard-hero p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin: 0;
  }
  
  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
  }
  
  .dashboard-layout {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    margin-bottom: 2rem;
  }
  
  /* Sidebar */
  .dashboard-sidebar {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    height: fit-content;
  }
  
  .sidebar-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
  }
  
  .sidebar-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
  }
  
  .sidebar-section h3 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    font-weight: bold;
  }
  
  .quick-stats {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f9f9f9;
    border-radius: 8px;
  }
  
  .stat-label {
    color: #666;
    font-size: 0.9rem;
  }
  
  .stat-value {
    color: #ff6b6b;
    font-weight: bold;
    font-size: 1.1rem;
  }
  
  .quick-links {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .quick-link {
    display: block;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: #666;
    border-radius: 8px;
    transition: all 0.3s;
    border: 1px solid transparent;
  }
  
  .quick-link:hover {
    background: #f0f0f0;
    color: #333;
  }
  
  .quick-link.active {
    background: #ff6b6b;
    color: white;
    border-color: #ff6b6b;
  }
  
  /* Main Content */
  .dashboard-main {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }
  
  .section-title {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    font-weight: bold;
  }
  
  /* Stats Grid */
  .dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .stat {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
  }
  
  .stat:hover {
    transform: translateY(-5px);
  }
  
  .stat i {
    font-size: 2.5rem;
    color: #ff6b6b;
    margin-bottom: 1rem;
  }
  
  .stat h3 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 0.5rem;
    font-weight: bold;
  }
  
  .stat p {
    color: #666;
    font-size: 1rem;
    margin: 0;
  }
  
  /* Content Sections */
  .dashboard-products,
  .dashboard-orders {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
  }
  
  .dashboard-products h2,
  .dashboard-orders h2 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 20px;
    border: 1px solid transparent;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
  }
  
  .btn-primary {
    background: #ff6b6b;
    color: white;
    border-color: #ff6b6b;
  }
  
  .btn-primary:hover {
    background: #e55a5a;
    transform: translateY(-2px);
  }
  
  .btn-danger {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
  }
  
  .btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
  }
  
  .btn-edit {
    background: #28a745;
    color: white;
    border-color: #28a745;
  }
  
  .btn-edit:hover {
    background: #218838;
    transform: translateY(-2px);
  }
  
  /* Tables */
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  
  th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
  }
  
  th {
    background: #f8f9fa;
    color: #333;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  td {
    color: #666;
    font-size: 0.9rem;
  }
  
  tr:hover {
    background: #f8f9fa;
  }
  
  .product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
  }
  
  .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
  }
  
  .status-pending {
    background: #fff3cd;
    color: #856404;
  }
  
  .status-shipped {
    background: #d1ecf1;
    color: #0c5460;
  }
  
  .status-delivered {
    background: #d4edda;
    color: #155724;
  }
  
  .status-cancelled {
    background: #f8d7da;
    color: #721c24;
  }
  
  .empty-message {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px dashed #dee2e6;
  }
  
  .text-muted {
    color: #6c757d !important;
    font-size: 0.85rem;
  }
  
  .stock-amount {
    font-weight: bold;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.9rem;
  }
  
  .stock-amount.in-stock {
    background: #d4edda;
    color: #155724;
  }
  
  .stock-amount.out-of-stock {
    background: #f8d7da;
    color: #721c24;
  }
  
  /* Responsive Design */
  @media (max-width: 768px) {
    .dashboard-layout {
      grid-template-columns: 1fr;
    }
    
    .dashboard-stats {
      grid-template-columns: 1fr;
    }
    
    .dashboard-products h2,
    .dashboard-orders h2 {
      flex-direction: column;
      gap: 1rem;
      align-items: flex-start;
    }
    
    table {
      font-size: 0.8rem;
    }
    
    th, td {
      padding: 0.5rem;
    }
    
    .product-image {
      width: 60px;
      height: 60px;
    }
  }
</style>
</head>
<body>
<div class="dashboard-container">
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
  <div class="container">
    <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
    <p>Manage your products and orders efficiently.</p>
  </div>
</section>

<div class="container">
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

  <div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
      <div class="sidebar-section">
        <h3>Quick Stats</h3>
        <div class="quick-stats">
          <div class="stat-item">
            <span class="stat-label">Products</span>
            <span class="stat-value"><?= $totalProducts ?></span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Orders</span>
            <span class="stat-value"><?= $totalOrders ?></span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Pending</span>
            <span class="stat-value"><?= $pendingOrders ?></span>
          </div>
        </div>
      </div>
      
      <div class="sidebar-section">
        <h3>Quick Actions</h3>
        <div class="quick-links">
          <a href="../app/add-product.php" class="quick-link">
            <i class="fas fa-plus"></i> Add Product
          </a>
          <a href="../app/manage-products.php" class="quick-link">
            <i class="fas fa-edit"></i> Manage Products
          </a>
          <a href="../app/manage-order.php" class="quick-link">
            <i class="fas fa-shopping-bag"></i> View Orders
          </a>
          <a href="index.php" class="quick-link">
            <i class="fas fa-home"></i> Go Home
          </a>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">
      <section class="dashboard-products">
        <h2>
          Your Products
          <a href="../app/add-product.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Product
          </a>
        </h2>

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
                <td>
                  <img src="image.php?file=<?php echo urlencode($product['image']); ?>" 
                       alt="<?= htmlspecialchars($product['name']) ?>" 
                       class="product-image">
                </td>
                <td>
                  <strong><?= htmlspecialchars($product['name']) ?></strong>
                  <br><small class="text-muted"><?= ucfirst($product['category'] ?? 'general') ?></small>
                </td>
                <td><strong>Rs <?= number_format($product['price'], 2) ?></strong></td>
                <td>
                  <span class="stock-amount <?= ($product['stock'] ?? 0) > 0 ? 'in-stock' : 'out-of-stock' ?>">
                    <?= $product['stock'] ?? 0 ?>
                  </span>
                </td>
                <td>
                  <a href="../seller/edit-product.php?id=<?= $product['id'] ?>" 
                     class="btn btn-edit">
                     <i class="fas fa-edit"></i> Edit
                   </a>
                  <a href="../seller/delete-product.php?id=<?= $product['id'] ?>" 
                     class="btn btn-danger" 
                     onclick="return confirm('Are you sure you want to delete this product?')">
                     <i class="fas fa-trash"></i> Delete
                   </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-message">
            <i class="fas fa-box-open" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
            <p>You haven't added any products yet.</p>
            <p>Start by clicking "Add New Product" to showcase your handmade creations!</p>
          </div>
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
                <td><strong>#<?= $order['id'] ?></strong></td>
                <td><?= htmlspecialchars($order['buyer_name']) ?></td>
                <td><?= htmlspecialchars($order['product_name']) ?></td>
                <td><?= $order['quantity'] ?></td>
                <td><strong>Rs <?= number_format($order['total'], 2) ?></strong></td>
                <td>
                  <span class="status-badge status-<?= strtolower($order['status']) ?>">
                    <?= $order['status'] ?>
                  </span>
                </td>
                <td><?= date("d M Y", strtotime($order['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-message">
            <i class="fas fa-shopping-bag" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
            <p>No orders yet.</p>
            <p>Once buyers purchase your products, they will appear here.</p>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
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
      <a href="../app/manage-products.php">Products</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date("Y") ?> HandCraft. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
