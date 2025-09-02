<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: ../public/login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// --- Handle status update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Verify the order belongs to this seller
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND seller_id = ?");
    $stmt->execute([$orderId, $user['id']]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?");
        $stmt->execute([$status, $orderId, $user['id']]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Order #$orderId status updated to $status successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update order status.";
        }
    } else {
        $_SESSION['error_message'] = "Order not found or you don't have permission to modify it.";
    }
    
    header("Location: manage-order.php");
    exit;
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';

// Build the query with filters
$whereConditions = ['o.seller_id = ?'];
$params = [$user['id']];

if ($status_filter) {
    $whereConditions[] = 'o.status = ?';
    $params[] = $status_filter;
}

if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $whereConditions[] = 'DATE(o.created_at) = CURDATE()';
            break;
        case 'week':
            $whereConditions[] = 'o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            break;
        case 'month':
            $whereConditions[] = 'o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            break;
    }
}

$whereClause = implode(' AND ', $whereConditions);

// --- Fetch all orders for seller with filters ---
$stmt = $pdo->prepare("
    SELECT o.id, o.quantity, o.total, o.status, o.created_at, 
           u.name AS buyer_name, u.email AS buyer_email,
           p.name AS product_name, p.image AS product_image,
           od.shipping_address, od.shipping_city, od.shipping_state, od.shipping_zip, od.shipping_phone
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    JOIN products p ON o.product_id = p.id
    LEFT JOIN order_details od ON o.id = od.order_id
    WHERE $whereClause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE seller_id = ?");
$stmt->execute([$user['id']]);
$totalOrders = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM orders WHERE seller_id = ? AND status = 'Pending'");
$stmt->execute([$user['id']]);
$pendingOrders = $stmt->fetch()['pending'];

$stmt = $pdo->prepare("SELECT COUNT(*) as shipped FROM orders WHERE seller_id = ? AND status = 'Shipped'");
$stmt->execute([$user['id']]);
$shippedOrders = $stmt->fetch()['shipped'];

$stmt = $pdo->prepare("SELECT COUNT(*) as delivered FROM orders WHERE seller_id = ? AND status = 'Delivered'");
$stmt->execute([$user['id']]);
$deliveredOrders = $stmt->fetch()['delivered'];

$stmt = $pdo->prepare("SELECT SUM(total) as total_revenue FROM orders WHERE seller_id = ? AND status = 'Delivered'");
$stmt->execute([$user['id']]);
$totalRevenue = $stmt->fetch()['total_revenue'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Orders - <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../public/handcraf.css"/>
<link rel="stylesheet" href="../public/startstyle.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
  /* Manage Orders Styles - Matching Buyer Dashboard Theme */
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
  
  /* Stats Section */
  .stats-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .stat-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    border: 1px solid #f0f0f0;
  }
  
  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  }
  
  .stat-card i {
    font-size: 2.5rem;
    color: #4CAF50;
    margin-bottom: 1rem;
  }
  
  .stat-card h3 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 0.5rem;
    font-weight: bold;
  }
  
  .stat-card p {
    color: #666;
    font-size: 1rem;
    margin: 0;
  }
  
  .stat-card.revenue {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
  }
  
  .stat-card.revenue i,
  .stat-card.revenue h3,
  .stat-card.revenue p {
    color: white;
  }
  
  /* Filters Section */
  .filters-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border: 1px solid #f0f0f0;
  }
  
  .filters-section h3 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    font-weight: bold;
  }
  
  .filters-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
  }
  
  .form-group {
    display: flex;
    flex-direction: column;
  }
  
  .form-group label {
    color: #333;
    margin-bottom: 0.5rem;
    font-weight: 500;
    font-size: 0.9rem;
  }
  
  .form-control {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s;
  }
  
  .form-control:focus {
    border-color: #4CAF50;
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
  
  .btn-secondary {
    background: #6c757d;
    color: white;
    border-color: #6c757d;
  }
  
  .btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
  }
  
  .btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
  }
  
  /* Orders Section */
  .orders-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border: 1px solid #f0f0f0;
  }
  
  .orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }
  
  .orders-header h2 {
    color: #333;
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
  }
  
  /* Tables */
  table {
    width: 100%;
    border-collapse: collapse;
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
    width: 60px;
    height: 60px;
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
  
  .status-processing {
    background: #d1ecf1;
    color: #0c5460;
  }
  
  .status-shipped {
    background: #e3f2fd;
    color: #1976d2;
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
  
  .status-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }
  
  .status-form select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.9rem;
    outline: none;
  }
  
  .order-details {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 0.5rem;
    font-size: 0.85rem;
  }
  
  .order-details h4 {
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
  }
  
  .order-details p {
    margin: 0.25rem 0;
    color: #666;
  }
  
  /* Alert Messages */
  .alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
  }
  
  .alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }
  
  .alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }
  
  .alert i {
    font-size: 1.1rem;
  }

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
  /* Responsive Design */
  @media (max-width: 768px) {
    .filters-form {
      grid-template-columns: 1fr;
    }
    
    .orders-header {
      flex-direction: column;
      gap: 1rem;
      align-items: flex-start;
    }
    
    .status-form {
      flex-direction: column;
      align-items: stretch;
    }
    
    table {
      font-size: 0.8rem;
    }
    
    th, td {
      padding: 0.5rem;
    }
    
    .product-image {
      width: 50px;
      height: 50px;
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
      <li><a href="../public/seller-dashboard.php">Dashboard</a></li>
      <li><a href="manage-products.php">Products</a></li>
      <li><a href="manage-order.php" class="active">Orders</a></li>
      <li><a href="seller-analytics.php">Analytics</a></li>
      <li><a href="manage-customers.php">Customers</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($user['name']) ?></span>
    <a href="../public/logout.php" class="btn logout">Logout</a>
  </div>
</header>

<section class="dashboard-hero">
  <div class="container">
    <h1>Manage Orders</h1>
    <p>Track and update the status of your customer orders.</p>
  </div>
</section>

<div class="container">
  <!-- Stats Section -->
  <section class="stats-section">
    <div class="stat-card">
      <i class="fas fa-shopping-bag"></i>
      <h3><?= $totalOrders ?></h3>
      <p>Total Orders</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-hourglass-half"></i>
      <h3><?= $pendingOrders ?></h3>
      <p>Pending Orders</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-shipping-fast"></i>
      <h3><?= $shippedOrders ?></h3>
      <p>Shipped Orders</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-check-circle"></i>
      <h3><?= $deliveredOrders ?></h3>
      <p>Delivered Orders</p>
    </div>
    <div class="stat-card revenue">
      <i class="fas fa-rupee-sign"></i>
      <h3>Rs <?= number_format($totalRevenue, 2) ?></h3>
      <p>Total Revenue</p>
    </div>
  </section>

  <!-- Filters Section -->
  <section class="filters-section">
    <h3>Filter Orders</h3>
    <form class="filters-form" method="GET">
      <div class="form-group">
        <label for="status_filter">Order Status</label>
        <select id="status_filter" name="status" class="form-control">
          <option value="">All Statuses</option>
          <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
          <option value="Processing" <?= $status_filter === 'Processing' ? 'selected' : '' ?>>Processing</option>
          <option value="Shipped" <?= $status_filter === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
          <option value="Delivered" <?= $status_filter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
          <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      
      <div class="form-group">
        <label for="date_filter">Date Range</label>
        <select id="date_filter" name="date_filter" class="form-control">
          <option value="">All Time</option>
          <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
          <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
          <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
        </select>
      </div>
      
      <div class="form-group">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-filter"></i> Apply Filters
        </button>
        <a href="manage-order.php" class="btn btn-secondary">
          <i class="fas fa-times"></i> Clear
        </a>
      </div>
    </form>
  </section>

  <!-- Orders Section -->
  <section class="orders-section">
    <div class="orders-header">
      <h2>Order Details (<?= count($orders) ?> found)</h2>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($_SESSION['success_message']) ?>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($_SESSION['error_message']) ?>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if ($orders): ?>
      <table>
        <thead>
          <tr>
            <th>Order Details</th>
            <th>Customer Info</th>
            <th>Product</th>
            <th>Total</th>
            <th>Status</th>
            <th>Actions</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
          <tr>
            <td>
              <strong>Order #<?= $order['id'] ?></strong>
              <br>
              <small>Qty: <?= $order['quantity'] ?></small>
            </td>
            <td>
              <strong><?= htmlspecialchars($order['buyer_name']) ?></strong>
              <br>
              <small><?= htmlspecialchars($order['buyer_email']) ?></small>
              <?php if ($order['shipping_address']): ?>
                <div class="order-details">
                  <h4>Shipping Address:</h4>
                  <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                  <p><?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> <?= htmlspecialchars($order['shipping_zip']) ?></p>
                  <p>Phone: <?= htmlspecialchars($order['shipping_phone']) ?></p>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <img src="image.php?file=<?php echo urlencode($order['product_image']); ?>" 
                   alt="<?= htmlspecialchars($order['product_name']) ?>" 
                   class="product-image">
              <br>
              <strong><?= htmlspecialchars($order['product_name']) ?></strong>
            </td>
            <td><strong>Rs <?= number_format($order['total'], 2) ?></strong></td>
            <td>
              <span class="status-badge status-<?= strtolower($order['status']) ?>">
                <?= htmlspecialchars($order['status']) ?>
              </span>
            </td>
            <td>
              <form method="post" class="status-form">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <select name="status" class="form-control">
                  <option value="Pending" <?= $order['status']=="Pending"?"selected":"" ?>>Pending</option>
                  <option value="Processing" <?= $order['status']=="Processing"?"selected":"" ?>>Processing</option>
                  <option value="Shipped" <?= $order['status']=="Shipped"?"selected":"" ?>>Shipped</option>
                  <option value="Delivered" <?= $order['status']=="Delivered"?"selected":"" ?>>Delivered</option>
                  <option value="Cancelled" <?= $order['status']=="Cancelled"?"selected":"" ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="fas fa-save"></i> Update
                </button>
              </form>
            </td>
            <td><?= date("d M Y", strtotime($order['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty-message">
        <i class="fas fa-shopping-bag" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
        <p><?= $status_filter || $date_filter ? 'No orders found matching your criteria.' : 'No orders yet.' ?></p>
        <p>Once customers purchase your products, they will appear here.</p>
      </div>
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
      <a href="../public/seller-dashboard.php">Dashboard</a>
      <a href="manage-products.php">Products</a>
      <a href="manage-order.php">Orders</a>
      <a href="../public/logout.php">Logout</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date("Y") ?> HandCraft. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
