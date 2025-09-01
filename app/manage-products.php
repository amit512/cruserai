<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: ../public/login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

// Build the query with filters
$whereConditions = ['seller_id = ?'];
$params = [$user['id']];

if ($search) {
    $whereConditions[] = '(name LIKE ? OR description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $whereConditions[] = 'category = ?';
    $params[] = $category;
}

if ($status !== '') {
    $whereConditions[] = 'is_active = ?';
    $params[] = $status;
}

$whereClause = implode(' AND ', $whereConditions);

// Fetch all products of this seller with filters
$stmt = $pdo->prepare("SELECT * FROM products WHERE $whereClause ORDER BY created_at DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$categories = get_categories();

// Get product counts for stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ?");
$stmt->execute([$user['id']]);
$totalProducts = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as active FROM products WHERE seller_id = ? AND is_active = 1");
$stmt->execute([$user['id']]);
$activeProducts = $stmt->fetch()['active'];

$stmt = $pdo->prepare("SELECT COUNT(*) as inactive FROM products WHERE seller_id = ? AND is_active = 0");
$stmt->execute([$user['id']]);
$inactiveProducts = $stmt->fetch()['inactive'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Products - <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../public/handcraf.css"/>
<link rel="stylesheet" href="../public/startstyle.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
  /* Manage Products Styles - Matching Buyer Dashboard Theme */
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
  
  .btn-success {
    background: #28a745;
    color: white;
    border-color: #28a745;
  }
  
  .btn-success:hover {
    background: #218838;
    transform: translateY(-2px);
  }
  
  /* Products Section */
  .products-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border: 1px solid #f0f0f0;
  }
  
  .products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
  }
  
  .products-header h2 {
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
  
  .status-active {
    background: #d4edda;
    color: #155724;
  }
  
  .status-inactive {
    background: #f8d7da;
    color: #721c24;
  }
  
  .category-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    background: #e3f2fd;
    color: #1976d2;
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
  
  .actions-cell {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
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
  
  /* Responsive Design */
  @media (max-width: 768px) {
    .filters-form {
      grid-template-columns: 1fr;
    }
    
    .products-header {
      flex-direction: column;
      gap: 1rem;
      align-items: flex-start;
    }
    
    .actions-cell {
      flex-direction: column;
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
      <li><a href="../public/seller-dashboard.php">Dashboard</a></li>
      <li><a href="manage-products.php" class="active">Products</a></li>
      <li><a href="manage-order.php">Orders</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($user['name']) ?></span>
    <a href="../public/logout.php" class="btn login">Logout</a>
  </div>
</header>

<section class="dashboard-hero">
  <div class="container">
    <h1>Manage Your Products</h1>
    <p>Add, edit, or remove your handcrafted items.</p>
  </div>
</section>

<div class="container">
  <!-- Stats Section -->
  <section class="stats-section">
    <div class="stat-card">
      <i class="fas fa-box"></i>
      <h3><?= $totalProducts ?></h3>
      <p>Total Products</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-check-circle"></i>
      <h3><?= $activeProducts ?></h3>
      <p>Active Products</p>
    </div>
    <div class="stat-card">
      <i class="fas fa-pause-circle"></i>
      <h3><?= $inactiveProducts ?></h3>
      <p>Inactive Products</p>
    </div>
  </section>

  <!-- Search and Filters -->
  <section class="filters-section">
    <h3>Search & Filter Products</h3>
    <form class="filters-form" method="GET">
      <div class="form-group">
        <label for="search">Search Products</label>
        <input type="text" id="search" name="search" class="form-control" 
               placeholder="Search by name or description..." 
               value="<?= htmlspecialchars($search) ?>">
      </div>
      
      <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category" class="form-control">
          <option value="">All Categories</option>
          <?php foreach ($categories as $catKey => $catName): ?>
            <option value="<?= $catKey ?>" <?= $category === $catKey ? 'selected' : '' ?>>
              <?= $catName ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status" class="form-control">
          <option value="">All Status</option>
          <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      
      <div class="form-group">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-search"></i> Search
        </button>
        <a href="manage-products.php" class="btn btn-secondary">
          <i class="fas fa-times"></i> Clear
        </a>
      </div>
    </form>
  </section>

  <!-- Products Section -->
  <section class="products-section">
    <div class="products-header">
      <h2>Your Products (<?= count($products) ?> found)</h2>
      <a href="add-product.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Product
      </a>
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

    <?php if ($products): ?>
      <table>
        <thead>
          <tr>
            <th>Image</th>
            <th>Name & Category</th>
            <th>Description</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th>Created</th>
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
              <br>
              <span class="category-badge">
                <?= htmlspecialchars($categories[$product['category']] ?? ucfirst($product['category'] ?? 'General')) ?>
              </span>
            </td>
            <td>
              <?= htmlspecialchars(substr($product['description'], 0, 80)) ?>
              <?= strlen($product['description']) > 80 ? '...' : '' ?>
            </td>
            <td><strong>Rs <?= number_format($product['price'], 2) ?></strong></td>
            <td>
              <span class="stock-amount <?= ($product['stock'] ?? 0) > 0 ? 'in-stock' : 'out-of-stock' ?>">
                <?= $product['stock'] ?? 0 ?>
              </span>
            </td>
            <td>
              <span class="status-badge status-<?= $product['is_active'] ? 'active' : 'inactive' ?>">
                <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td><?= date("d M Y", strtotime($product['created_at'])) ?></td>
            <td class="actions-cell">
              <a href="edit-product.php?id=<?= $product['id'] ?>" 
                 class="btn btn-edit">
                 <i class="fas fa-edit"></i> Edit
               </a>
              
              <?php if ($product['is_active']): ?>
                <a href="toggle-status.php?id=<?= $product['id'] ?>&action=deactivate" 
                   class="btn btn-secondary"
                   onclick="return confirm('Deactivate this product?')">
                   <i class="fas fa-pause"></i> Pause
                 </a>
              <?php else: ?>
                <a href="toggle-status.php?id=<?= $product['id'] ?>&action=activate" 
                   class="btn btn-success"
                   onclick="return confirm('Activate this product?')">
                   <i class="fas fa-play"></i> Activate
                 </a>
              <?php endif; ?>
              
              <a href="delete-product.php?id=<?= $product['id'] ?>" 
                 class="btn btn-danger" 
                 onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
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
        <p><?= $search || $category || $status !== '' ? 'No products found matching your criteria.' : 'You haven\'t added any products yet.' ?></p>
        <p>Start by clicking "Add New Product" to showcase your handmade creations!</p>
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
      <a href="../public/logout.php">Logout</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date("Y") ?> HandCraft. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
