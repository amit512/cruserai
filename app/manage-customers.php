<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: ../public/login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// Get filter parameters
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'total_spent';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Build the query with filters
$whereConditions = ['o.seller_id = ?'];
$params = [$user['id']];

if ($search) {
    $whereConditions[] = '(u.name LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $whereConditions);

// Fetch customers with their purchase data
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.created_at as customer_since,
           COUNT(DISTINCT o.id) as total_orders,
           SUM(o.total) as total_spent,
           MAX(o.created_at) as last_order_date,
           AVG(o.total) as avg_order_value
    FROM users u
    JOIN orders o ON u.id = o.buyer_id
    WHERE $whereClause
    GROUP BY u.id
    ORDER BY $sort_by $sort_order
");
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get customer statistics
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT buyer_id) as total_customers,
           COUNT(*) as total_orders,
           SUM(total) as total_revenue,
           AVG(total) as avg_order_value
    FROM orders 
    WHERE seller_id = ?
");
$stmt->execute([$user['id']]);
$customer_stats = $stmt->fetch();

// Get top customers
$stmt = $pdo->prepare("
    SELECT u.name, SUM(o.total) as total_spent, COUNT(o.id) as orders
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    WHERE o.seller_id = ?
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$top_customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Management - <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../public/handcraf.css"/>
<link rel="stylesheet" href="../public/startstyle.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
  /* Customer Management Styles - Matching Buyer Dashboard Theme */
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
    max-width: 1400px;
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
  
  /* Search and Filters */
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
  
  .btn-info {
    background: #17a2b8;
    color: white;
    border-color: #17a2b8;
  }
  
  .btn-info:hover {
    background: #138496;
    transform: translateY(-2px);
  }
  
  /* Top Customers */
  .top-customers {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border: 1px solid #f0f0f0;
  }
  
  .top-customers h3 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    font-weight: bold;
  }
  
  .customer-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
  }
  
  .customer-item:last-child {
    border-bottom: none;
  }
  
  .customer-name {
    font-weight: 500;
    color: #333;
  }
  
  .customer-stats {
    text-align: right;
  }
  
  .customer-orders {
    font-size: 0.9rem;
    color: #666;
  }
  
  .customer-spent {
    font-weight: bold;
    color: #4CAF50;
  }
  
  /* Customers Table */
  .customers-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border: 1px solid #f0f0f0;
  }
  
  .customers-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }
  
  .customers-header h2 {
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
    cursor: pointer;
    transition: background-color 0.3s;
  }
  
  th:hover {
    background: #e9ecef;
  }
  
  td {
    color: #666;
    font-size: 0.9rem;
  }
  
  tr:hover {
    background: #f8f9fa;
  }
  
  .customer-avatar {
    width: 40px;
    height: 40px;
    background: #4CAF50;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.1rem;
  }
  
  .customer-info {
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  
  .customer-details h4 {
    margin: 0;
    color: #333;
    font-size: 1rem;
  }
  
  .customer-details small {
    color: #666;
    font-size: 0.85rem;
  }
  
  .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
  }
  
  .status-active {
    background: #d4edda;
    color: #155724;
  }
  
  .status-inactive {
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
    
    .customers-header {
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
    
    .customer-info {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
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
      <li><a href="manage-order.php">Orders</a></li>
      <li><a href="seller-analytics.php">Analytics</a></li>
      <li><a href="manage-customers.php" class="active">Customers</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($user['name']) ?></span>
    <a href="../public/logout.php" class="btn logout">Logout</a>
  </div>
</header>

<section class="dashboard-hero">
  <div class="container">
    <h1>Customer Management</h1>
    <p>Manage your customer relationships and track customer behavior.</p>
  </div>
</section>

<div class="container">
  <!-- Stats Section -->
  <section class="stats-section">
    <div class="stat-card">
      <i class="fas fa-users"></i>
      <h3><?= $customer_stats['total_customers'] ?? 0 ?></h3>
      <p>Total Customers</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-shopping-bag"></i>
      <h3><?= $customer_stats['total_orders'] ?? 0 ?></h3>
      <p>Total Orders</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-rupee-sign"></i>
      <h3>Rs <?= number_format($customer_stats['total_revenue'] ?? 0, 2) ?></h3>
      <p>Total Revenue</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-chart-line"></i>
      <h3>Rs <?= number_format($customer_stats['avg_order_value'] ?? 0, 2) ?></h3>
      <p>Average Order Value</p>
    </div>
  </section>

  <!-- Top Customers -->
  <section class="top-customers">
    <h3>Top Customers by Revenue</h3>
    <?php if ($top_customers): ?>
      <?php foreach ($top_customers as $customer): ?>
      <div class="customer-item">
        <div class="customer-name"><?= htmlspecialchars($customer['name']) ?></div>
        <div class="customer-stats">
          <div class="customer-orders"><?= $customer['orders'] ?> orders</div>
          <div class="customer-spent">Rs <?= number_format($customer['total_spent'], 2) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align: center; color: #666; font-style: italic;">
        No customer data available.
      </p>
    <?php endif; ?>
  </section>

  <!-- Search and Filters -->
  <section class="filters-section">
    <h3>Search & Filter Customers</h3>
    <form class="filters-form" method="GET">
      <div class="form-group">
        <label for="search">Search Customers</label>
        <input type="text" id="search" name="search" class="form-control" 
               placeholder="Search by name or email..." 
               value="<?= htmlspecialchars($search) ?>">
      </div>
      
      <div class="form-group">
        <label for="sort_by">Sort By</label>
        <select id="sort_by" name="sort_by" class="form-control">
          <option value="total_spent" <?= $sort_by === 'total_spent' ? 'selected' : '' ?>>Total Spent</option>
          <option value="total_orders" <?= $sort_by === 'total_orders' ? 'selected' : '' ?>>Total Orders</option>
          <option value="last_order_date" <?= $sort_by === 'last_order_date' ? 'selected' : '' ?>>Last Order Date</option>
          <option value="avg_order_value" <?= $sort_by === 'avg_order_value' ? 'selected' : '' ?>>Average Order Value</option>
        </select>
      </div>
      
      <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <select id="sort_order" name="sort_order" class="form-control">
          <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>High to Low</option>
          <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Low to High</option>
        </select>
      </div>
      
      <div class="form-group">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-search"></i> Search
        </button>
        <a href="manage-customers.php" class="btn btn-secondary">
          <i class="fas fa-times"></i> Clear
        </a>
      </div>
    </form>
  </section>

  <!-- Customers Section -->
  <section class="customers-section">
    <div class="customers-header">
      <h2>Customer List (<?= count($customers) ?> found)</h2>
    </div>

    <?php if ($customers): ?>
      <table>
        <thead>
          <tr>
            <th>Customer</th>
            <th>Contact Info</th>
            <th>Orders</th>
            <th>Total Spent</th>
            <th>Avg Order</th>
            <th>Last Order</th>
            <th>Customer Since</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $customer): ?>
          <tr>
            <td>
              <div class="customer-info">
                <div class="customer-avatar">
                  <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                </div>
                <div class="customer-details">
                  <h4><?= htmlspecialchars($customer['name']) ?></h4>
                  <small>ID: <?= $customer['id'] ?></small>
                </div>
              </div>
            </td>
            <td>
              <strong><?= htmlspecialchars($customer['email']) ?></strong>
            </td>
            <td>
              <span class="status-badge status-active">
                <?= $customer['total_orders'] ?> orders
              </span>
            </td>
            <td>
              <strong>Rs <?= number_format($customer['total_spent'], 2) ?></strong>
            </td>
            <td>
              Rs <?= number_format($customer['avg_order_value'], 2) ?>
            </td>
            <td>
              <?= $customer['last_order_date'] ? date("M d, Y", strtotime($customer['last_order_date'])) : 'Never' ?>
            </td>
            <td>
              <?= date("M Y", strtotime($customer['customer_since'])) ?>
            </td>
            <td>
              <a href="customer-details.php?id=<?= $customer['id'] ?>" 
                 class="btn btn-info btn-sm">
                 <i class="fas fa-eye"></i> View Details
               </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty-message">
        <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
        <p><?= $search ? 'No customers found matching your criteria.' : 'No customers yet.' ?></p>
        <p>Once customers purchase your products, they will appear here.</p>
      </div>
    <?php endif; ?>
  </section>
</div>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-section">
      <h4>HandCraft</h4>
      <p>Build strong customer relationships for your business.</p>
    </div>
    <div class="footer-section">
      <h4>Quick Links</h4>
      <a href="../public/seller-dashboard.php">Dashboard</a>
      <a href="manage-products.php">Products</a>
      <a href="manage-order.php">Orders</a>
      <a href="manage-customers.php">Customers</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date("Y") ?> HandCraft. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
