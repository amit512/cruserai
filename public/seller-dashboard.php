<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// --- Commission & Freeze check ---
// Ensure payments and account tables exist
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
} catch (Exception $e) { /* ignore */ }

// Get commission rate (default 5%)
$commissionRate = 5.0;
try {
    $stmt = $pdo->prepare("SELECT commission_rate FROM commission_structure WHERE seller_id = ? ORDER BY effective_from DESC LIMIT 1");
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if ($row && isset($row['commission_rate'])) {
        $commissionRate = (float)$row['commission_rate'];
    }
} catch (Exception $e) {}

// Compute due commission from Delivered orders
$deliveredTotal = 0.0;
try {
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(total),0) FROM orders WHERE seller_id = ? AND status = 'Delivered'");
    $stmt->execute([$user['id']]);
    $deliveredTotal = (float)$stmt->fetchColumn();
} catch (Exception $e) {}

$commissionAccrued = round($deliveredTotal * ($commissionRate / 100.0), 2);

// Sum of payments made by seller
$paymentsTotal = 0.0;
try {
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(amount),0) FROM seller_payments WHERE seller_id = ?");
    $stmt->execute([$user['id']]);
    $paymentsTotal = (float)$stmt->fetchColumn();
} catch (Exception $e) {}

$commissionDue = max(0.0, $commissionAccrued - $paymentsTotal);

// Load/create seller account row
$isFrozen = false;
$freezeThreshold = 1000.00;
try {
    $stmt = $pdo->prepare("SELECT is_frozen, freeze_threshold FROM seller_accounts WHERE seller_id = ?");
    $stmt->execute([$user['id']]);
    $acc = $stmt->fetch();
    if (!$acc) {
        $pdo->prepare("INSERT INTO seller_accounts (seller_id, is_frozen, freeze_threshold) VALUES (?, 0, ?)")
            ->execute([$user['id'], $freezeThreshold]);
    } else {
        $isFrozen = (bool)$acc['is_frozen'];
        $freezeThreshold = (float)$acc['freeze_threshold'];
    }
} catch (Exception $e) {}

// Auto-freeze if due exceeds threshold
try {
    if ($commissionDue > $freezeThreshold) {
        $isFrozen = true;
        $pdo->prepare("UPDATE seller_accounts SET is_frozen = 1 WHERE seller_id = ?")
            ->execute([$user['id']]);
    }
} catch (Exception $e) {}

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
  /* Seller Dashboard Specific Styles - Matching Buyer Dashboard Theme */
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background: #f5f5f5;
  }
  
  .dashboard-container {
    min-height: 100vh;
  }
  
  .dashboard-hero {
    background: linear-gradient(135deg, #4CAF50, #45a049);
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
    color: #4CAF50;
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
    background: #4CAF50;
    color: white;
    border-color: #4CAF50;
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
    border: 1px solid #f0f0f0;
  }
  
  .stat:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  }
  
  .stat i {
    font-size: 2.5rem;
    color: #4CAF50;
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
    border: 1px solid #f0f0f0;
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
    border-radius: 8px;
    border: 1px solid transparent;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
  }
  
  .btn-primary {
    background: #4CAF50;
    color: white;
    border-color: #4CAF50;
  }
  
  .btn-primary:hover {
    background: #45a049;
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
    background: #2196F3;
    color: white;
    border-color: #2196F3;
  }
  
  .btn-edit:hover {
    background: #1976D2;
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
    border: 1px solid #f0f0f0;
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
<<<<<<< Current (Your changes)
  .main-header {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 1rem 2rem;
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.logo {
    font-size: 1.8rem;
    font-weight: bold;
    color: #333;
}

.logo span {
    color: #4CAF50;
}

.nav-links ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 2rem;
}

.nav-links a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: color 0.3s;
    padding: 0.5rem 1rem;
    border-radius: 5px;
}

.nav-links a:hover,
.nav-links a.active {
    color: #4CAF50;
    background: rgba(76, 175, 80, 0.1);
}

.header-icons {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.welcome {
    color: #666;
    font-size: 0.9rem;
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 500;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn.login {
    background: #2196F3;
    color: white;
}

.btn.login:hover {
    background: #1976D2;
}

.btn.orders {
    background: #4CAF50;
    color: white;
}

.btn.orders:hover {
    background: #45a049;
}

.btn.register {
    background: #4CAF50;
    color: white;
}

.btn.register:hover {
    background: #45a049;
}

.btn.logout {
    background: #f44336;
    color: white;
}

.btn.logout:hover {
    background: #d32f2f;
}

.btn.wishlist {
    background: #E91E63;
    color: white;
}

.btn.wishlist:hover {
    background: #C2185B;
}

.btn.cart {
    background: #FF9800;
    color: white;
}

.btn.cart:hover {
    background: #F57C00;
}

/* Hero Section Styles */
.hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
    padding: 4rem 2rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 80vh;
}

.hero-content h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #333;
    line-height: 1.2;
}

.hero-content p {
    font-size: 1.2rem;
    color: #666;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn.shop {
    background: #4CAF50;
    color: white;
    padding: 15px 30px;
    font-size: 1.1rem;
}

.btn.shop:hover {
    background: #45a049;
    transform: translateY(-2px);
}

.btn.seller {
    background: #2196F3;
    color: white;
    padding: 15px 30px;
    font-size: 1.1rem;
}

.btn.seller:hover {
    background: #1976D2;
    transform: translateY(-2px);
}

.hero-image {
    text-align: center;
}

.hero-image img {
    max-width: 100%;
    height: auto;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-header {
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
    }
    
    .nav-links ul {
        gap: 1rem;
    }
    
    .hero {
        grid-template-columns: 1fr;
        text-align: center;
        padding: 2rem 1rem;
    }
    
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-buttons {
        justify-content: center;
    }
    
    .header-icons {
        flex-direction: column;
        gap: 0.5rem;
    }
}
=======
  
>>>>>>> Incoming (Background Agent changes)
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
      
      <li><a href="seller-dashboard.php" class="active">Dashboard</a></li>
      <li><a href="../app/manage-products.php">Products</a></li>
      <li><a href="../app/manage-order.php">Orders</a></li>
      <li><a href="../app/seller-analytics.php">Analytics</a></li>
      <li><a href="../app/manage-customers.php">Customers</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($user['name']) ?></span>
    <a href="logout.php" class="btn logout">Logout</a>
  </div>
</header>

<section class="dashboard-hero">
  <div class="container">
    <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
    <p>Manage your products and orders efficiently.</p>
    <?php if ($isFrozen): ?>
      <div style="margin-top:1rem; padding:0.75rem 1rem; border-radius:8px; background:#fff3cd; color:#856404;">
        <strong>Account Frozen:</strong> Outstanding commission of Rs <?= number_format($commissionDue, 2) ?> exceeds your limit (Rs <?= number_format($freezeThreshold, 2) ?>).
        Please make a payment to restore full access.
      </div>
    <?php elseif ($commissionDue > 0): ?>
      <div style="margin-top:1rem; padding:0.75rem 1rem; border-radius:8px; background:#e8f5e9; color:#2e7d32;">
        <strong>Commission Due:</strong> Rs <?= number_format($commissionDue, 2) ?> at <?= number_format($commissionRate, 2) ?>%.
      </div>
    <?php endif; ?>
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
          <a href="<?= $isFrozen ? '#' : '../app/add-product.php' ?>" class="quick-link" <?= $isFrozen ? 'onclick="return false;" style="opacity:.6; cursor:not-allowed;"' : '' ?>>
            <i class="fas fa-plus"></i> Add Product
          </a>
          <a href="<?= $isFrozen ? '#' : '../app/manage-products.php' ?>" class="quick-link" <?= $isFrozen ? 'onclick="return false;" style="opacity:.6; cursor:not-allowed;"' : '' ?>>
            <i class="fas fa-edit"></i> Manage Products
          </a>
          <a href="<?= $isFrozen ? '#' : '../app/manage-order.php' ?>" class="quick-link" <?= $isFrozen ? 'onclick="return false;" style="opacity:.6; cursor:not-allowed;"' : '' ?>>
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
          <a href="<?= $isFrozen ? '#' : '../app/add-product.php' ?>" class="btn btn-primary" <?= $isFrozen ? 'onclick="return false;" style="opacity:.6; cursor:not-allowed;"' : '' ?>>
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
