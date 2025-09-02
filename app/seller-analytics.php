<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: ../public/login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// Get date range for analytics
$date_range = $_GET['date_range'] ?? '30';
$start_date = date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = date('Y-m-d');

// Fetch analytics data
try {
    // Total revenue for the period
    $stmt = $pdo->prepare("
        SELECT SUM(total) as total_revenue, COUNT(*) as total_orders
        FROM orders 
        WHERE seller_id = ? AND status = 'Delivered' 
        AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$user['id'], $start_date, $end_date]);
    $revenue_data = $stmt->fetch();

    // Daily sales data for charts
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, 
               COUNT(*) as orders, 
               SUM(total) as revenue
        FROM orders 
        WHERE seller_id = ? AND status = 'Delivered'
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$user['id'], $start_date, $end_date]);
    $daily_sales = $stmt->fetchAll();

    // Top performing products
    $stmt = $pdo->prepare("
        SELECT p.name, p.image, COUNT(o.id) as order_count, SUM(o.total) as total_revenue
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.seller_id = ? AND o.status = 'Delivered'
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY total_revenue DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id'], $start_date, $end_date]);
    $top_products = $stmt->fetchAll();

    // Customer analytics
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT buyer_id) as unique_customers,
               COUNT(*) as total_orders
        FROM orders 
        WHERE seller_id = ? AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$user['id'], $start_date, $end_date]);
    $customer_data = $stmt->fetch();

    // Category performance
    $stmt = $pdo->prepare("
        SELECT p.category, COUNT(o.id) as orders, SUM(o.total) as revenue
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.seller_id = ? AND o.status = 'Delivered'
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.category
        ORDER BY revenue DESC
    ");
    $stmt->execute([$user['id'], $start_date, $end_date]);
    $category_performance = $stmt->fetchAll();

    // Recent activity
    $stmt = $pdo->prepare("
        SELECT o.id, o.total, o.status, o.created_at, u.name as customer_name, p.name as product_name
        FROM orders o
        JOIN users u ON o.buyer_id = u.id
        JOIN products p ON o.product_id = p.id
        WHERE o.seller_id = ?
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user['id']]);
    $recent_activity = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    $revenue_data = ['total_revenue' => 0, 'total_orders' => 0];
    $daily_sales = [];
    $top_products = [];
    $customer_data = ['unique_customers' => 0, 'total_orders' => 0];
    $category_performance = [];
    $recent_activity = [];
}

// Prepare chart data
$chart_labels = [];
$chart_revenue = [];
$chart_orders = [];

foreach ($daily_sales as $day) {
    $chart_labels[] = date('M d', strtotime($day['date']));
    $chart_revenue[] = $day['revenue'];
    $chart_orders[] = $day['orders'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics Dashboard - <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../public/handcraf.css"/>
<link rel="stylesheet" href="../public/startstyle.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  /* Analytics Dashboard Styles - Matching Buyer Dashboard Theme */
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
  
  /* Date Range Selector */
  .date-selector {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border: 1px solid #f0f0f0;
  }
  
  .date-selector h3 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    font-weight: bold;
  }
  
  .date-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
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
  
  /* Stats Grid */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
  
  /* Charts Section */
  .charts-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
  }
  
  .chart-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 1px solid #f0f0f0;
  }
  
  .chart-card h3 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    font-weight: bold;
  }
  
  .chart-container {
    position: relative;
    height: 300px;
  }
  
  /* Top Products */
  .top-products {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 1px solid #f0f0f0;
  }
  
  .top-products h3 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    font-weight: bold;
  }
  
  .product-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
  }
  
  .product-item:last-child {
    border-bottom: none;
  }
  
  .product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
  }
  
  .product-info {
    flex: 1;
  }
  
  .product-name {
    font-weight: bold;
    color: #333;
    margin-bottom: 0.25rem;
  }
  
  .product-stats {
    font-size: 0.9rem;
    color: #666;
  }
  
  .product-revenue {
    font-weight: bold;
    color: #4CAF50;
  }
  
  /* Category Performance */
  .category-performance {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border: 1px solid #f0f0f0;
  }
  
  .category-performance h3 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    font-weight: bold;
  }
  
  .category-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
  }
  
  .category-item:last-child {
    border-bottom: none;
  }
  
  .category-name {
    font-weight: 500;
    color: #333;
  }
  
  .category-stats {
    text-align: right;
  }
  
  .category-orders {
    font-size: 0.9rem;
    color: #666;
  }
  
  .category-revenue {
    font-weight: bold;
    color: #4CAF50;
  }
  
  /* Recent Activity */
  .recent-activity {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 1px solid #f0f0f0;
  }
  
  .recent-activity h3 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    font-weight: bold;
  }
  
  .activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
  }
  
  .activity-item:last-child {
    border-bottom: none;
  }
  
  .activity-info {
    flex: 1;
  }
  
  .activity-customer {
    font-weight: 500;
    color: #333;
    margin-bottom: 0.25rem;
  }
  
  .activity-product {
    font-size: 0.9rem;
    color: #666;
  }
  
  .activity-amount {
    font-weight: bold;
    color: #4CAF50;
    text-align: right;
  }
  
  .activity-date {
    font-size: 0.8rem;
    color: #999;
    text-align: right;
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
    .charts-section {
      grid-template-columns: 1fr;
    }
    
    .date-form {
      flex-direction: column;
      align-items: stretch;
    }
    
    .stats-grid {
      grid-template-columns: 1fr;
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
      <li><a href="seller-analytics.php" class="active">Analytics</a></li>
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
    <h1>Analytics Dashboard</h1>
    <p>Track your business performance and make data-driven decisions.</p>
  </div>
</section>

<div class="container">
  <!-- Date Range Selector -->
  <section class="date-selector">
    <h3>Select Date Range</h3>
    <form class="date-form" method="GET">
      <div class="form-group">
        <label for="date_range">Period</label>
        <select id="date_range" name="date_range" class="form-control">
          <option value="7" <?= $date_range === '7' ? 'selected' : '' ?>>Last 7 Days</option>
          <option value="30" <?= $date_range === '30' ? 'selected' : '' ?>>Last 30 Days</option>
          <option value="90" <?= $date_range === '90' ? 'selected' : '' ?>>Last 90 Days</option>
          <option value="365" <?= $date_range === '365' ? 'selected' : '' ?>>Last Year</option>
        </select>
      </div>
      <div class="form-group">
        <label>&nbsp;</label>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-chart-line"></i> Update Analytics
        </button>
      </div>
    </form>
  </section>

  <!-- Stats Grid -->
  <section class="stats-grid">
    <div class="stat-card revenue">
      <i class="fas fa-rupee-sign"></i>
      <h3>Rs <?= number_format($revenue_data['total_revenue'] ?? 0, 2) ?></h3>
      <p>Total Revenue (<?= $date_range ?> days)</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-shopping-bag"></i>
      <h3><?= $revenue_data['total_orders'] ?? 0 ?></h3>
      <p>Total Orders</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-users"></i>
      <h3><?= $customer_data['unique_customers'] ?? 0 ?></h3>
      <p>Unique Customers</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-chart-line"></i>
      <h3>Rs <?= number_format(($revenue_data['total_revenue'] ?? 0) / max(1, $date_range), 2) ?></h3>
      <p>Daily Average Revenue</p>
    </div>
  </section>

  <!-- Charts Section -->
  <section class="charts-section">
    <div class="chart-card">
      <h3>Sales Trend</h3>
      <div class="chart-container">
        <canvas id="salesChart"></canvas>
      </div>
    </div>
    
    <div class="top-products">
      <h3>Top Performing Products</h3>
      <?php if ($top_products): ?>
        <?php foreach ($top_products as $product): ?>
        <div class="product-item">
          <img src="image.php?file=<?php echo urlencode($product['image']); ?>" 
               alt="<?= htmlspecialchars($product['name']) ?>" 
               class="product-image">
          <div class="product-info">
            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
            <div class="product-stats">
              <?= $product['order_count'] ?> orders
            </div>
          </div>
          <div class="product-revenue">
            Rs <?= number_format($product['total_revenue'], 2) ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align: center; color: #666; font-style: italic;">
          No products sold in this period.
        </p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Category Performance -->
  <section class="category-performance">
    <h3>Category Performance</h3>
    <?php if ($category_performance): ?>
      <?php foreach ($category_performance as $category): ?>
      <div class="category-item">
        <div class="category-name">
          <?= htmlspecialchars(ucfirst($category['category'] ?? 'General')) ?>
        </div>
        <div class="category-stats">
          <div class="category-orders"><?= $category['orders'] ?> orders</div>
          <div class="category-revenue">Rs <?= number_format($category['revenue'], 2) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align: center; color: #666; font-style: italic;">
        No category data available for this period.
      </p>
    <?php endif; ?>
  </section>

  <!-- Recent Activity -->
  <section class="recent-activity">
    <h3>Recent Activity</h3>
    <?php if ($recent_activity): ?>
      <?php foreach ($recent_activity as $activity): ?>
      <div class="activity-item">
        <div class="activity-info">
          <div class="activity-customer"><?= htmlspecialchars($activity['customer_name']) ?></div>
          <div class="activity-product"><?= htmlspecialchars($activity['product_name']) ?></div>
        </div>
        <div class="activity-amount">
          Rs <?= number_format($activity['total'], 2) ?>
          <div class="activity-date"><?= date("M d, Y", strtotime($activity['created_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align: center; color: #666; font-style: italic;">
        No recent activity.
      </p>
    <?php endif; ?>
  </section>
</div>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-section">
      <h4>HandCraft</h4>
      <p>Track your business performance with detailed analytics.</p>
    </div>
    <div class="footer-section">
      <h4>Quick Links</h4>
      <a href="../public/seller-dashboard.php">Dashboard</a>
      <a href="manage-products.php">Products</a>
      <a href="manage-order.php">Orders</a>
      <a href="seller-analytics.php">Analytics</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date("Y") ?> HandCraft. All rights reserved.</p>
  </div>
</footer>

<script>
// Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'Revenue (Rs)',
      data: <?= json_encode($chart_revenue) ?>,
      borderColor: '#4CAF50',
      backgroundColor: 'rgba(76, 175, 80, 0.1)',
      tension: 0.4,
      fill: true
    }, {
      label: 'Orders',
      data: <?= json_encode($chart_orders) ?>,
      borderColor: '#2196F3',
      backgroundColor: 'rgba(33, 150, 243, 0.1)',
      tension: 0.4,
      fill: false,
      yAxisID: 'y1'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    scales: {
      x: {
        display: true,
        title: {
          display: true,
          text: 'Date'
        }
      },
      y: {
        type: 'linear',
        display: true,
        position: 'left',
        title: {
          display: true,
          text: 'Revenue (Rs)'
        }
      },
      y1: {
        type: 'linear',
        display: true,
        position: 'right',
        title: {
          display: true,
          text: 'Orders'
        },
        grid: {
          drawOnChartArea: false,
        },
      }
    },
    plugins: {
      legend: {
        position: 'top',
      },
      title: {
        display: false
      }
    }
  }
});
</script>

</body>
</html>
