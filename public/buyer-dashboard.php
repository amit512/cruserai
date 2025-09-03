<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Product.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();

// Fetch recommended products
try {
    $products = Product::allActive(12);
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
}

// Fetch cart count
try {
$stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM cart_items WHERE user_id = ?");
$stmt->execute([$user['id']]);
$cartCount = $stmt->fetch()['cart_count'] ?? 0;
} catch (Exception $e) {
    $cartCount = 0;
}

// Fetch user's orders
try {
    $stmt = $pdo->prepare("
        SELECT o.*, p.name as product_name, p.image, u.name as seller_name 
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN users u ON o.seller_id = u.id 
        WHERE o.buyer_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $recentOrders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $recentOrders = [];
}

// Fetch categories for quick access
try {
    $categories = Product::getCategories();
    
    
} catch (Exception $e) {
    $categories = [];
}

// Fetch user's wishlist
try {
    $stmt = $pdo->prepare("
        SELECT w.*, p.name, p.price, p.image, u.name as seller_name 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        JOIN users u ON p.seller_id = u.id 
        WHERE w.user_id = ? AND p.is_active = 1
        ORDER BY w.created_at DESC 
        LIMIT 6
    ");
    $stmt->execute([$user['id']]);
    $wishlistItems = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching wishlist: " . $e->getMessage());
    $wishlistItems = [];
}

// Fetch trending products (most viewed/ordered)
try {
    $stmt = $pdo->query("
        SELECT p.*, u.name as seller_name, 
               (SELECT COUNT(*) FROM orders WHERE product_id = p.id) as order_count
        FROM products p 
        JOIN users u ON p.seller_id = u.id 
        WHERE p.is_active = 1 
        ORDER BY order_count DESC, p.created_at DESC 
        LIMIT 8
    ");
    $trendingProducts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching trending products: " . $e->getMessage());
    $trendingProducts = [];
}

// Fetch featured sellers
try {
    $stmt = $pdo->query("
        SELECT u.*, COUNT(p.id) as product_count,
               AVG(p.price) as avg_price
        FROM users u 
        JOIN products p ON u.id = p.seller_id 
        WHERE u.role = 'seller' AND p.is_active = 1
        GROUP BY u.id 
        HAVING product_count >= 3
        ORDER BY product_count DESC 
        LIMIT 6
    ");
    $featuredSellers = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching featured sellers: " . $e->getMessage());
    $featuredSellers = [];
}

// Fetch user's total spent
try {
    $stmt = $pdo->prepare("SELECT SUM(total) as total_spent FROM orders WHERE buyer_id = ? AND status = 'Delivered'");
    $stmt->execute([$user['id']]);
    $totalSpent = $stmt->fetch()['total_spent'] ?? 0;
} catch (Exception $e) {
    $totalSpent = 0;
}

// Fetch user's favorite categories
try {
    $stmt = $pdo->prepare("
        SELECT p.category, COUNT(*) as purchase_count
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        WHERE o.buyer_id = ? AND o.status = 'Delivered'
        GROUP BY p.category 
        ORDER BY purchase_count DESC 
        LIMIT 3
    ");
    $stmt->execute([$user['id']]);
    $favoriteCategories = $stmt->fetchAll();
} catch (Exception $e) {
    $favoriteCategories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Buyer Dashboard - <?= SITE_NAME ?></title>
  
  <link rel="stylesheet" href="handcraf.css"/>
  <link rel="stylesheet" href="startstyle.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    /* Buyer Dashboard Styles */
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
    
    /* Search Bar */
    .search-section {
      background: white;
      border-radius: 15px;
      padding: 1.5rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .search-form {
      display: flex;
      max-width: 600px;
      margin: 0 auto;
    }
    
    .search-input {
      flex: 1;
      padding: 0.75rem 1rem;
      border: 1px solid #ddd;
      border-radius: 8px 0 0 8px;
      outline: none;
      font-size: 1rem;
    }
    
    .search-btn {
      padding: 0.75rem 1.5rem;
      background: #4CAF50;
      color: white;
      border: none;
      border-radius: 0 8px 8px 0;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .search-btn:hover {
      background: #45a049;
    }
    
    /* Products Grid */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 2rem;
    }
    
    .product-card {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s, box-shadow 0.3s;
      border: 1px solid #f0f0f0;
    }
    
    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .product-image {
      position: relative;
      height: 200px;
      overflow: hidden;
    }
    
    .product-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .product-info {
      padding: 1.5rem;
    }
    
    .product-title {
      font-size: 1.2rem;
      color: #333;
      margin-bottom: 0.5rem;
      font-weight: bold;
      line-height: 1.3;
    }
    
    .seller-name {
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }
    
    .product-price {
      font-size: 1.5rem;
      font-weight: bold;
      color: #4CAF50;
      margin-bottom: 1rem;
    }
    
    .product-actions {
      display: flex;
      gap: 0.5rem;
    }
    
    .btn-primary {
      flex: 1;
      background: #4CAF50;
      color: white;
      padding: 0.75rem;
      text-decoration: none;
      border-radius: 8px;
      text-align: center;
      transition: background 0.3s;
      font-weight: 500;
      border: none;
      cursor: pointer;
    }
    
    .btn-primary:hover {
      background: #45a049;
    }
    
    .btn-secondary {
      background: #2196F3;
      color: white;
      padding: 0.75rem 1rem;
      text-decoration: none;
      border-radius: 8px;
      transition: background 0.3s;
      font-weight: 500;
      border: none;
      cursor: pointer;
    }
    
    .btn-secondary:hover {
      background: #1976D2;
    }
    
    /* Orders Section */
    .orders-section {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .order-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      margin-bottom: 1rem;
    }
    
    .order-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
    }
    
    .order-details {
      flex: 1;
    }
    
    .order-title {
      font-weight: bold;
      color: #333;
      margin-bottom: 0.25rem;
    }
    
    .order-seller {
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 0.25rem;
    }
    
    .order-status {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 15px;
      font-size: 0.8rem;
      font-weight: 500;
    }
    
    .order-status.pending {
      background: #fff3cd;
      color: #856404;
    }
    
    .order-status.shipped {
      background: #d1ecf1;
      color: #0c5460;
    }
    
    .order-status.delivered {
      background: #d4edda;
      color: #155724;
    }
    
    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background: white;
      border-radius: 15px;
      padding: 1.5rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      text-align: center;
      transition: transform 0.3s;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.5rem;
      color: white;
    }
    
    .stat-icon.orders { background: linear-gradient(135deg, #4CAF50, #45a049); }
    .stat-icon.cart { background: linear-gradient(135deg, #2196F3, #1976D2); }
    .stat-icon.spent { background: linear-gradient(135deg, #FF9800, #F57C00); }
    .stat-icon.wishlist { background: linear-gradient(135deg, #E91E63, #C2185B); }
    
    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #333;
      margin-bottom: 0.5rem;
    }
    
    .stat-label {
      color: #666;
      font-size: 0.9rem;
    }
    
    /* Featured Categories */
    .featured-categories {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .categories-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 1rem;
      margin-top: 1.5rem;
    }
    
    .category-card {
      text-align: center;
      padding: 1.5rem 1rem;
      border: 2px solid #f0f0f0;
      border-radius: 12px;
      transition: all 0.3s;
      text-decoration: none;
      color: #333;
    }
    
    .category-card:hover {
      border-color: #4CAF50;
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(76, 175, 80, 0.2);
    }
    
    .category-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4CAF50, #45a049);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      color: white;
      font-size: 1.2rem;
    }
    
    .category-name {
      font-weight: bold;
      margin-bottom: 0.25rem;
    }
    
    .category-count {
      color: #666;
      font-size: 0.8rem;
    }
    
    /* Wishlist Section */
    .wishlist-section {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .wishlist-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    
    .wishlist-item {
      text-align: center;
      border: 1px solid #e0e0e0;
      border-radius: 10px;
      overflow: hidden;
      transition: transform 0.3s;
    }
    
    .wishlist-item:hover {
      transform: translateY(-3px);
    }
    
    .wishlist-image {
      width: 100%;
      height: 120px;
      object-fit: cover;
    }
    
    .wishlist-info {
      padding: 1rem;
    }
    
    .wishlist-title {
      font-weight: bold;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }
    
    .wishlist-price {
      color: #4CAF50;
      font-weight: bold;
    }
    
    /* Trending Products */
    .trending-section {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .trending-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    
    .trending-item {
      border: 1px solid #e0e0e0;
      border-radius: 10px;
      overflow: hidden;
      transition: transform 0.3s;
    }
    
    .trending-item:hover {
      transform: translateY(-3px);
    }
    
    .trending-image {
      width: 100%;
      height: 120px;
      object-fit: cover;
    }
    
    .trending-info {
      padding: 1rem;
    }
    
    .trending-title {
      font-weight: bold;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }
    
    .trending-seller {
      color: #666;
      font-size: 0.8rem;
      margin-bottom: 0.5rem;
    }
    
    .trending-price {
      color: #4CAF50;
      font-weight: bold;
    }
    
    .trending-badge {
      background: #FF5722;
      color: white;
      padding: 0.25rem 0.5rem;
      border-radius: 12px;
      font-size: 0.7rem;
      font-weight: bold;
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
    }
    
    /* Featured Sellers */
    .sellers-section {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .sellers-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    
    .seller-card {
      text-align: center;
      padding: 1.5rem;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      transition: transform 0.3s;
    }
    
    .seller-card:hover {
      transform: translateY(-3px);
    }
    
    .seller-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4CAF50, #45a049);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      color: white;
      font-size: 1.5rem;
      font-weight: bold;
    }
    
    .seller-name {
      font-weight: bold;
      margin-bottom: 0.5rem;
    }
    
    .seller-stats {
      color: #666;
      font-size: 0.8rem;
    }
    
    /* Favorite Categories */
    .favorites-section {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .favorites-list {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-top: 1.5rem;
    }
    
    .favorite-tag {
      background: linear-gradient(135deg, #4CAF50, #45a049);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 500;
    }
    
    /* Quick Actions Section */
    .quick-actions-section {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .quick-actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    
    .quick-action-card {
      text-align: center;
      padding: 2rem 1.5rem;
      border: 2px solid #f0f0f0;
      border-radius: 15px;
      text-decoration: none;
      color: #333;
      transition: all 0.3s;
    }
    
    .quick-action-card:hover {
      border-color: #4CAF50;
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(76, 175, 80, 0.2);
    }
    
    .action-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4CAF50, #45a049);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      color: white;
      font-size: 1.5rem;
    }
    
    .quick-action-card h3 {
      margin-bottom: 0.5rem;
      font-size: 1.1rem;
    }
    
    .quick-action-card p {
      color: #666;
      font-size: 0.9rem;
    }
    
    /* Activity Section */
    .activity-section {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .activity-timeline {
      margin-top: 1.5rem;
    }
    
    .activity-item {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      padding: 1rem 0;
      border-bottom: 1px solid #f0f0f0;
    }
    
    .activity-item:last-child {
      border-bottom: none;
    }
    
    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2196F3, #1976D2);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1rem;
      flex-shrink: 0;
    }
    
    .activity-content {
      flex: 1;
    }
    
    .activity-content h4 {
      margin-bottom: 0.25rem;
      color: #333;
      font-size: 1rem;
    }
    
    .activity-content p {
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 0.25rem;
    }
    
    .activity-time {
      color: #999;
      font-size: 0.8rem;
    }
    
    /* Offers Section */
    .offers-section {
      background: white;
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    
    .offers-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    
    .offer-card {
      position: relative;
      padding: 2rem;
      border: 2px solid #f0f0f0;
      border-radius: 15px;
      text-align: center;
      transition: all 0.3s;
      overflow: hidden;
    }
    
    .offer-card:hover {
      border-color: #4CAF50;
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(76, 175, 80, 0.2);
    }
    
    .offer-badge {
      position: absolute;
      top: -10px;
      right: -10px;
      background: linear-gradient(135deg, #FF5722, #E64A19);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
      transform: rotate(15deg);
    }
    
    .offer-card h3 {
      margin-bottom: 1rem;
      color: #333;
      font-size: 1.2rem;
    }
    
    .offer-card p {
      color: #666;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
    }
    
    .offer-btn {
      background: linear-gradient(135deg, #4CAF50, #45a049);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: background 0.3s;
    }
    
    .offer-btn:hover {
      background: linear-gradient(135deg, #45a049, #3d8b40);
    }
    
    /* Newsletter Section */
    .newsletter-section {
      background: linear-gradient(135deg, #4CAF50, #45a049);
      color: white;
      border-radius: 15px;
      padding: 3rem 2rem;
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .newsletter-content h2 {
      font-size: 2rem;
      margin-bottom: 1rem;
    }
    
    .newsletter-content p {
      font-size: 1.1rem;
      margin-bottom: 2rem;
      opacity: 0.9;
    }
    
    .newsletter-form {
      display: flex;
      max-width: 400px;
      margin: 0 auto;
      gap: 1rem;
    }
    
    .newsletter-form input {
      flex: 1;
      padding: 0.75rem 1rem;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
    }
    
    .newsletter-form button {
      background: white;
      color: #4CAF50;
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .newsletter-form button:hover {
      background: #f0f0f0;
      transform: translateY(-2px);
    }

    /* Activity Section */
    .activity-section {
      margin-bottom: 2rem;
    }

    .activity-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
    }

    .activity-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 1.5rem;
      border-radius: 15px;
      text-align: center;
      transition: transform 0.3s;
    }

    .activity-card:hover {
      transform: translateY(-5px);
    }

    .activity-icon {
      font-size: 2rem;
      margin-bottom: 1rem;
      opacity: 0.9;
    }

    .activity-number {
      font-size: 2rem;
      font-weight: bold;
      margin: 0.5rem 0;
    }

    .activity-label {
      opacity: 0.8;
      font-size: 0.9rem;
      margin: 0;
    }

    /* Trending Section */
    .trending-section {
      margin-bottom: 2rem;
    }

    .trending-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .trending-card {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s;
      border: 2px solid #ff6b6b;
    }

    .trending-card:hover {
      transform: translateY(-5px);
    }

    .trending-image {
      position: relative;
      height: 180px;
    }

    .trending-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .trending-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #ff6b6b;
      color: white;
      padding: 0.5rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
    }

    .trending-info {
      padding: 1.5rem;
    }

    .trending-info h4 {
      margin: 0 0 0.5rem 0;
      color: #333;
      font-size: 1.1rem;
    }

    .trending-seller {
      color: #666;
      font-size: 0.9rem;
      margin: 0 0 0.5rem 0;
    }

    .trending-price {
      font-size: 1.3rem;
      font-weight: bold;
      color: #4CAF50;
      margin: 0 0 1rem 0;
    }

    .trending-actions {
      display: flex;
      gap: 0.5rem;
    }

    .btn-view {
      flex: 1;
      background: #4CAF50;
      color: white;
      padding: 0.75rem;
      text-decoration: none;
      border-radius: 8px;
      text-align: center;
      transition: background 0.3s;
      font-weight: 500;
    }

    .btn-view:hover {
      background: #45a049;
    }

    .btn-wishlist {
      background: #ff6b6b;
      color: white;
      border: none;
      padding: 0.75rem;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .btn-wishlist:hover {
      background: #ff5252;
    }

    /* Sellers Section */
    .sellers-section {
      margin-bottom: 2rem;
    }

    .sellers-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .seller-card {
      background: white;
      border-radius: 15px;
      padding: 1.5rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s;
      border: 1px solid #e0e0e0;
    }

    .seller-card:hover {
      transform: translateY(-3px);
    }

    .seller-avatar {
      text-align: center;
      margin-bottom: 1rem;
    }

    .seller-avatar i {
      font-size: 3rem;
      color: #4CAF50;
    }

    .seller-info h4 {
      text-align: center;
      margin: 0 0 1rem 0;
      color: #333;
      font-size: 1.2rem;
    }

    .seller-stats {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }

    .stat {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #666;
      font-size: 0.9rem;
    }

    .btn-view-seller {
      display: block;
      background: #2196F3;
      color: white;
      text-decoration: none;
      padding: 0.75rem;
      border-radius: 8px;
      text-align: center;
      transition: background 0.3s;
      font-weight: 500;
    }

    .btn-view-seller:hover {
      background: #1976D2;
    }

    /* Quick Actions Section */
    .quick-actions-section {
      margin-bottom: 2rem;
    }

    .actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
    }

    .action-card {
      background: white;
      border-radius: 15px;
      padding: 2rem 1.5rem;
      text-decoration: none;
      color: #333;
      text-align: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: all 0.3s;
      border: 2px solid transparent;
    }

    .action-card:hover {
      transform: translateY(-5px);
      border-color: #4CAF50;
      color: #4CAF50;
    }

    .action-icon {
      font-size: 2.5rem;
      color:rgb(215, 227, 216);
      margin-bottom: 1rem;
    }

    .action-card:hover .action-icon {
      color: #rgb(255, 255, 255);
    }

    .action-card h3 {
      margin: 0 0 0.5rem 0;
      font-size: 1.1rem;
    }

    .action-card p {
      margin: 0;
      color: #666;
      font-size: 0.9rem;
    }

    /* Activity Timeline */
    .activity-timeline {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .activity-item {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      padding: 1rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }

    .activity-item:hover {
      transform: translateX(5px);
    }

    .activity-item .activity-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4CAF50, #45a049);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.2rem;
      flex-shrink: 0;
    }

    .activity-item .activity-content h4 {
      margin: 0 0 0.5rem 0;
      color: #333;
      font-size: 1rem;
    }

    .activity-item .activity-content p {
      margin: 0 0 0.5rem 0;
      color: #666;
      font-size: 0.9rem;
    }

    .activity-time {
      color: #999;
      font-size: 0.8rem;
    }

    /* Favorites Section */
    .favorites-section {
      margin-bottom: 2rem;
    }

    .favorites-list {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .favorite-tag {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
      color: white;
      padding: 0.75rem 1rem;
      border-radius: 25px;
      font-size: 0.9rem;
      font-weight: 500;
    }

    /* Quick Actions Grid */
    .quick-actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
    }

    .quick-action-card {
      background: white;
      border-radius: 15px;
      padding: 2rem 1.5rem;
      text-decoration: none;
      color: #333;
      text-align: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      transition: all 0.3s;
      border: 2px solid transparent;
    }

    .quick-action-card:hover {
      transform: translateY(-5px);
      border-color: #4CAF50;
      color: #4CAF50;
    }

    .quick-action-card .action-icon {
      font-size: 2.5rem;
      color: #4CAF50;
      margin-bottom: 1rem;
    }

    .quick-action-card:hover .action-icon {
      color: #4CAF50;
    }

    .quick-action-card h3 {
      margin: 0 0 0.5rem 0;
      font-size: 1.1rem;
    }

    .quick-action-card p {
      margin: 0;
      color: #666;
      font-size: 0.9rem;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .dashboard-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
      
      .dashboard-hero h1 {
        font-size: 2rem;
      }
      
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
      }
      
      .search-form {
        flex-direction: column;
      }
      
      .search-input {
        border-radius: 8px;
        margin-bottom: 0.5rem;
      }
      
      .search-btn {
        border-radius: 8px;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .categories-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .offers-grid {
        grid-template-columns: 1fr;
      }
      
      .newsletter-form {
        flex-direction: column;
      }
    }
  </style>
</head>
<?php error_reporting(E_ALL); ini_set('display_errors', 1); ?>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <div class="dashboard-container">
    <!-- Hero Section -->
    <section class="dashboard-hero">
      <div class="container">
        <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
        <p>Discover handmade treasures curated just for you ✨</p>
      </div>
    </section>

    <div class="container">
      <!-- Search Section -->
      <div class="search-section">
        <form method="GET" action="catalog.php" class="search-form">
          <input type="text" name="search" placeholder="Search for handmade products..." class="search-input">
          <button type="submit" class="search-btn">
            <i class="fas fa-search"></i> Search
          </button>
        </form>
      </div>

      <!-- Dashboard Layout -->
      <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
          <!-- Quick Stats -->
          <div class="sidebar-section">
            <h3>Quick Stats</h3>
            <div class="quick-stats">
              <div class="stat-item">
                <span class="stat-label">Cart Items</span>
                <span class="stat-value"><?= $cartCount ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Total Orders</span>
                <span class="stat-value"><?= count($recentOrders) ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Active Orders</span>
                <span class="stat-value"><?= count(array_filter($recentOrders, fn($o) => $o['status'] !== 'Delivered')) ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Total Spent</span>
                <span class="stat-value"><?= format_price($totalSpent) ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Wishlist Items</span>
                <span class="stat-value"><?= count($wishlistItems) ?></span>
              </div>
            </div>
          </div>

          <!-- Quick Links -->
          <div class="sidebar-section">
            <h3>Quick Actions</h3>
            <div class="quick-links">
              <a href="catalog.php" class="quick-link">
                <i class="fas fa-th-large"></i> Browse Products
              </a>
              <a href="buyer-dashboard.php" class="quick-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
              </a>
              <a href="cart.php" class="quick-link">
                <i class="fas fa-shopping-cart"></i> My Cart (<?= $cartCount ?>)
              </a>
              <a href="my-orders.php" class="quick-link">
                <i class="fas fa-box"></i> My Orders
              </a>
              <a href="wishlist.php" class="quick-link">
                <i class="fas fa-heart"></i> My Wishlist (<?= count($wishlistItems) ?>)
              </a>
              <a href="profile.php" class="quick-link">
                <i class="fas fa-user"></i> My Profile
              </a>
            </div>
          </div>

          <!-- Categories -->
          <div class="sidebar-section">
            <h3>Shop by Category</h3>
            <div class="quick-links">
            <?php foreach (array_slice($categories, 0, 9) as $cat): ?>
  <a href="catalog.php?category=<?= urlencode($cat['category']) ?>" class="quick-link">
    <i class="fas fa-tag"></i> <?= htmlspecialchars($cat['category']) ?> 
    (<?= $cat['count'] ?>)
  </a>
<?php endforeach; ?>


            </div>
          </div>
        </aside>

        

        
        <!-- Main Content -->
        <main class="dashboard-main">
          <!-- Recent Orders -->
          <?php if (!empty($recentOrders)): ?>
          <div class="orders-section">
            <h2 class="section-title">Recent Orders</h2>
            <?php foreach ($recentOrders as $order): ?>
              <div class="order-item">
                <img src="image.php?file=<?= urlencode($order['image']) ?>" 
                     alt="<?= htmlspecialchars($order['product_name']) ?>" 
                     class="order-image">
                <div class="order-details">
                  <div class="order-title"><?= htmlspecialchars($order['product_name']) ?></div>
                  <div class="order-seller">by <?= htmlspecialchars($order['seller_name']) ?></div>
                  <div class="order-status <?= strtolower($order['status']) ?>">
                    <?= htmlspecialchars($order['status']) ?>
                  </div>
                </div>
                <div class="order-price"><?= format_price($order['total']) ?></div>
              </div>
            <?php endforeach; ?>
            <div style="text-align: center; margin-top: 1rem;">
                                      <a href="my-orders.php" class="btn-primary">View All Orders</a>
            </div>
          </div>
          <?php endif; ?>

          <!-- Activity Summary -->
          <div class="activity-section">
            <h2 class="section-title">Your Shopping Activity</h2>
            <div class="activity-grid">
              <div class="activity-card">
                <div class="activity-icon">
                  <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="activity-content">
                  <h3>Total Purchases</h3>
                  <p class="activity-number"><?= count($recentOrders) ?></p>
                  <p class="activity-label">Products bought</p>
                </div>
              </div>
              
              <div class="activity-card">
                <div class="activity-icon">
                  <i class="fas fa-heart"></i>
                </div>
                <div class="activity-content">
                  <h3>Wishlist Items</h3>
                  <p class="activity-number"><?= count($wishlistItems) ?></p>
                  <p class="activity-label">Saved for later</p>
                </div>
              </div>
              
              <div class="activity-card">
                <div class="activity-icon">
                  <i class="fas fa-star"></i>
                </div>
                <div class="activity-content">
                  <h3>Favorite Categories</h3>
                  <p class="activity-number"><?= count($favoriteCategories) ?></p>
                  <p class="activity-label">Based on purchases</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Trending Products -->
          <?php if (!empty($trendingProducts)): ?>
          <div class="trending-section">
            <h2 class="section-title">🔥 Trending Products</h2>
            <div class="trending-grid">
              <?php foreach(array_slice($trendingProducts, 0, 4) as $product): ?>
                <div class="trending-card">
                  <div class="trending-image">
                    <img src="image.php?file=<?= urlencode($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="trending-badge">
                      <i class="fas fa-fire"></i> Hot
                    </div>
                  </div>
                  <div class="trending-info">
                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                    <p class="trending-seller">by <?= htmlspecialchars($product['seller_name']) ?></p>
                    <p class="trending-price"><?= format_price($product['price']) ?></p>
                    <div class="trending-actions">
                      <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">View</a>
                      <button class="btn-wishlist" 
                              data-product-id="<?= $product['id'] ?>" 
                              title="Add to Wishlist"
                              onclick="addToWishlist(this)"
                              aria-label="Add to wishlist">
                        <i class="fas fa-heart"></i>
                      </button>
                      <script>
                        function addToWishlist(btn) {
                          const productId = btn.getAttribute('data-product-id');
                          btn.disabled = true;
                          fetch('add_to_wishlist.php', {
                            method: 'POST',
                            headers: {
                              'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ product_id: productId })
                          })
                          .then(response => response.json())
                          .then(data => {
                            if (data.success) {
                              btn.classList.add('added');
                              btn.title = "Added to Wishlist";
                              btn.innerHTML = '<i class="fas fa-heart"></i> Added';
                            } else {
                              alert(data.message || "Could not add to wishlist.");
                              btn.disabled = false;
                            }
                          })
                          .catch(() => {
                            alert("An error occurred. Please try again.");
                            btn.disabled = false;
                          });
                        }
                      </script>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Featured Sellers -->
          <?php if (!empty($featuredSellers)): ?>
          <div class="sellers-section">
            <h2 class="section-title">⭐ Featured Sellers</h2>
            <div class="sellers-grid">
              <?php foreach(array_slice($featuredSellers, 0, 3) as $seller): ?>
                <div class="seller-card">
                  <div class="seller-avatar">
                    <i class="fas fa-user-circle"></i>
                  </div>
                  <div class="seller-info">
                    <h4><?= htmlspecialchars($seller['name']) ?></h4>
                    <p class="seller-stats">
                      <span class="stat">
                        <i class="fas fa-box"></i> <?= $seller['product_count'] ?> products
                      </span>
                      <span class="stat">
                        <i class="fas fa-tag"></i> Avg: <?= format_price($seller['avg_price']) ?>
                      </span>
                    </p>
                    <a href="catalog.php?seller=<?= $seller['id'] ?>" class="btn-view-seller">View Shop</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Quick Actions Grid -->
          <div class="quick-actions-section">
            <h2 class="section-title">Quick Actions</h2>
            <div class="actions-grid">
              <a href="catalog.php" class="action-card">
                <div class="action-icon">
                  <i class="fas fa-search"></i>
                </div>
                <h3>Browse Products</h3>
                <p>Discover new handmade items</p>
              </a>
              
              <a href="cart.php" class="action-card">
                <div class="action-icon">
                  <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>View Cart</h3>
                <p><?= $cartCount ?> items waiting</p>
              </a>
              
              <a href="my-orders.php" class="action-card">
                <div class="action-icon">
                  <i class="fas fa-box"></i>
                </div>
                <h3>Track Orders</h3>
                <p>Check delivery status</p>
              </a>
              
              <a href="wishlist.php" class="action-card">
                <div class="action-icon">
                  <i class="fas fa-heart"></i>
                </div>
                <h3>My Wishlist</h3>
                <p><?= count($wishlistItems) ?> saved items</p>
              </a>
            </div>
          </div>

          <!-- Recommended Products -->
          <div class="products-section">
            <h2 class="section-title">Recommended Products</h2>
            <div class="products-grid">
              <?php if (!empty($products)): ?>
    <?php foreach($products as $product): ?>
      <div class="product-card">
                    <div class="product-image">
                      <img src="image.php?file=<?= urlencode($product['image']) ?>" 
                           alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="product-info">
                      <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                      <p class="seller-name">by <?= htmlspecialchars($product['seller_name']) ?></p>
                      <div class="product-price"><?= format_price($product['price']) ?></div>
                      <div class="product-actions">
                        <a href="product.php?id=<?= $product['id'] ?>" class="btn-primary">
                          <i class="fas fa-eye"></i> View
                        </a>
                        <button class="btn-secondary" 
        data-id="<?= $product['id'] ?>" 
        onclick="addToCart(<?= $product['id'] ?>)" 
        title="Add to Cart">
  <i class="fas fa-cart-plus"></i>
                        </button>
                        <style>
                          button.btn-secondary.added {
  background-color: #28a745; /* green */
  color: white;
  border-color: #28a745;
}

button.btn-secondary {
  transition: background-color 0.3s, color 0.3s, border-color 0.3s;
}

                        </style>
                        <script>
function addToCart(productId) {
  console.log("Clicked Add to Cart, productId:", productId);

  // find the clicked button
  let btn = document.querySelector(`button[data-id='${productId}']`);

  fetch('add_to_cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ product_id: productId, quantity: 1 })
  })
  .then(response => response.text())
  .then(data => {
    console.log("Raw response:", data); 
    try {
      let json = JSON.parse(data);
      if (json.success) {
        if (btn) {
          btn.classList.add("added");  // light up
        }
      } else {
        console.error("Add to cart failed:", json.message);
      }
    } catch (e) {
      console.error("Invalid JSON:", e);
    }
  })
  .catch(error => {
    console.error("Fetch error:", error);
  });
}
</script>


                        <button class="btn-secondary"  data-product-id="<?= $product['id'] ?>" onclick="addToWishlist(this)" title="Add to Wishlist" style="background: #E91E63;">
                          <i class="fas fa-heart"></i>
                        </button>
                      </div>
                    </div>
      </div>
    <?php endforeach; ?>
              <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">
                  <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; color: #ccc;"></i>
                  <h3>No products available</h3>
                  <p>Check back later for new handmade treasures!</p>
  </div>
              <?php endif; ?>
            </div>
          </div>
        </main>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon orders">
            <i class="fas fa-shopping-bag"></i>
  </div>
          <div class="stat-number"><?= count($recentOrders) ?></div>
          <div class="stat-label">Total Orders</div>
  </div>
        <div class="stat-card">
          <div class="stat-icon cart">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <div class="stat-number"><?= $cartCount ?></div>
          <div class="stat-label">Cart Items</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon spent">
            <i class="fas fa-dollar-sign"></i>
          </div>
          <div class="stat-number"><?= format_price($totalSpent) ?></div>
          <div class="stat-label">Total Spent</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon wishlist">
            <i class="fas fa-heart"></i>
          </div>
          <div class="stat-number"><?= count($wishlistItems) ?></div>
          <div class="stat-label">Wishlist Items</div>
        </div>
      </div>

      <!-- Featured Categories -->
      <div class="featured-categories">
        <h2 class="section-title">Shop by Category</h2>
        <div class="categories-grid">
          <?php foreach (array_slice($categories, 0, 8) as $key => $name): ?>
            <a href="catalog.php?category=<?= $key ?>" class="category-card">
              <div class="category-icon">
                <i class="fas fa-<?= get_category_icon($key) ?>"></i>
              </div>
              <div class="category-name"><?= $name ?></div>
              <div class="category-count"><?= $key ?> products</div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Wishlist Section -->
      <div class="wishlist-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <h2 class="section-title">❤️ Your Wishlist</h2>
          <a href="wishlist.php" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
            <i class="fas fa-heart"></i> View All (<?= count($wishlistItems) ?>)
          </a>
        </div>
        <?php if (!empty($wishlistItems)): ?>
          <div class="wishlist-grid">
            <?php foreach (array_slice($wishlistItems, 0, 6) as $item): ?>
              <div class="wishlist-item">
                <img src="image.php?file=<?= urlencode($item['image']) ?>" 
                     alt="<?= htmlspecialchars($item['name']) ?>" 
                     class="wishlist-image">
                <div class="wishlist-info">
                  <div class="wishlist-title"><?= htmlspecialchars($item['name']) ?></div>
                  <div class="wishlist-price"><?= format_price($item['price']) ?></div>
                  <a href="product.php?id=<?= $item['product_id'] ?>" class="btn-primary" style="margin-top: 0.5rem; display: block;">
                    <i class="fas fa-eye"></i> View Product
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (count($wishlistItems) > 6): ?>
            <div style="text-align: center; margin-top: 1rem;">
              <a href="wishlist.php" class="btn-secondary">
                <i class="fas fa-heart"></i> View All <?= count($wishlistItems) ?> Items
              </a>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div style="text-align: center; padding: 2rem; color: #666;">
            <i class="fas fa-heart" style="font-size: 3rem; margin-bottom: 1rem; color: #ccc;"></i>
            <h3>Your wishlist is empty</h3>
            <p>Start adding products you love to your wishlist!</p>
            <a href="catalog.php" class="btn-primary" style="margin-top: 1rem; display: inline-block;">
              <i class="fas fa-search"></i> Browse Products
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Trending Products -->
      <div class="trending-section">
        <h2 class="section-title">🔥 Trending Products</h2>
        <?php if (!empty($trendingProducts)): ?>
          <div class="trending-grid">
            <?php foreach ($trendingProducts as $product): ?>
              <div class="trending-item" style="position: relative;">
                <div class="trending-badge">Hot</div>
                <img src="image.php?file=<?= urlencode($product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     class="trending-image">
                <div class="trending-info">
                  <div class="trending-title"><?= htmlspecialchars($product['name']) ?></div>
                  <div class="trending-seller">by <?= htmlspecialchars($product['seller_name']) ?></div>
                  <div class="trending-price"><?= format_price($product['price']) ?></div>
                  <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                    <a href="product.php?id=<?= $product['id'] ?>" class="btn-primary" style="flex: 1; text-align: center;">
                      <i class="fas fa-eye"></i> View
                    </a>
                    <button class="btn-secondary" onclick="addToWishlist(<?= $product['id'] ?>)" title="Add to Wishlist" style="background: #E91E63;">
                      <i class="fas fa-heart"></i>
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="text-align: center; padding: 2rem; color: #666;">
            <i class="fas fa-fire" style="font-size: 3rem; margin-bottom: 1rem; color: #ccc;"></i>
            <h3>No trending products yet</h3>
            <p>Products will appear here as they gain popularity!</p>
            <a href="catalog.php" class="btn-primary" style="margin-top: 1rem; display: inline-block;">
              <i class="fas fa-search"></i> Browse All Products
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Featured Sellers -->
      <div class="sellers-section">
        <h2 class="section-title">⭐ Featured Sellers</h2>
        <?php if (!empty($featuredSellers)): ?>
          <div class="sellers-grid">
            <?php foreach ($featuredSellers as $seller): ?>
              <div class="seller-card">
                <div class="seller-avatar">
                  <?= strtoupper(substr($seller['name'], 0, 1)) ?>
                </div>
                <div class="seller-name"><?= htmlspecialchars($seller['name']) ?></div>
                <div class="seller-stats">
                  <?= $seller['product_count'] ?> products<br>
                  Avg: <?= format_price($seller['avg_price']) ?>
                </div>
                <a href="catalog.php?seller=<?= $seller['id'] ?>" class="btn-primary" style="margin-top: 1rem; display: block;">
                  <i class="fas fa-store"></i> Visit Store
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="text-align: center; padding: 2rem; color: #666;">
            <i class="fas fa-store" style="font-size: 3rem; margin-bottom: 1rem; color: #ccc;"></i>
            <h3>No featured sellers yet</h3>
            <p>Top sellers will appear here as they gain more products!</p>
            <a href="catalog.php" class="btn-primary" style="margin-top: 1rem; display: inline-block;">
              <i class="fas fa-search"></i> Browse All Sellers
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Favorite Categories -->
      <?php if (!empty($favoriteCategories)): ?>
      <div class="favorites-section">
        <h2 class="section-title">❤️ Your Favorite Categories</h2>
        <div class="favorites-list">
          <?php foreach ($favoriteCategories as $category): ?>
            <span class="favorite-tag">
              <i class="fas fa-<?= get_category_icon($category['category']) ?>"></i>
              <?= ucfirst(str_replace('-', ' ', $category['category'])) ?>
              (<?= $category['purchase_count'] ?> purchases)
            </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Quick Actions Section -->
      <div class="quick-actions-section">
        <h2 class="section-title">⚡ Quick Actions</h2>
        <div class="quick-actions-grid">
          <a href="catalog.php" class="quick-action-card">
            <div class="action-icon">
              <i class="fas fa-search"></i>
            </div>
            <h3>Browse Products</h3>
            <p>Discover new handmade treasures</p>
          </a>
          
          <a href="catalog.php?category=jewelry" class="quick-action-card">
            <div class="action-icon">
              <i class="fas fa-gem"></i>
            </div>
            <h3>Jewelry</h3>
            <p>Beautiful handmade jewelry</p>
          </a>
          
          <a href="catalog.php?category=woodwork" class="quick-action-card">
            <div class="action-icon">
              <i class="fas fa-tree"></i>
            </div>
            <h3>Woodwork</h3>
            <p>Handcrafted wooden items</p>
          </a>
          
          <a href="catalog.php?category=art" class="quick-action-card">
            <div class="action-icon">
              <i class="fas fa-palette"></i>
            </div>
            <h3>Art & Crafts</h3>
            <p>Creative handmade art</p>
          </a>
        </div>
      </div>

      <!-- Recent Activity Section -->
      <div class="activity-section">
        <h2 class="section-title">📈 Recent Activity</h2>
        <div class="activity-timeline">
          <div class="activity-item">
            <div class="activity-icon">
              <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="activity-content">
              <h4>Added to Cart</h4>
              <p>You added "Vintage Painting" to your cart</p>
              <span class="activity-time">2 hours ago</span>
            </div>
          </div>
          
          <div class="activity-item">
            <div class="activity-icon">
              <i class="fas fa-heart"></i>
            </div>
            <div class="activity-content">
              <h4>Added to Wishlist</h4>
              <p>You saved "Handcrafted Bag" to your wishlist</p>
              <span class="activity-time">1 day ago</span>
            </div>
          </div>
          
          <div class="activity-item">
            <div class="activity-icon">
              <i class="fas fa-box"></i>
            </div>
            <div class="activity-content">
              <h4>Order Shipped</h4>
              <p>Your order for "Bamboo Bucket" has been shipped</p>
              <span class="activity-time">2 days ago</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Special Offers Section -->
      <div class="offers-section">
        <h2 class="section-title">🎉 Special Offers</h2>
        <div class="offers-grid">
          <div class="offer-card">
            <div class="offer-badge">20% OFF</div>
            <h3>New User Discount</h3>
            <p>Get 20% off your first order with code: WELCOME20</p>
            <a href="catalog.php" class="offer-btn">Shop Now</a>
          </div>
          
          <div class="offer-card">
            <div class="offer-badge">FREE SHIPPING</div>
            <h3>Free Delivery</h3>
            <p>Free shipping on orders over $50</p>
            <a href="catalog.php" class="offer-btn">Learn More</a>
          </div>
          
          <div class="offer-card">
            <div class="offer-badge">LIMITED TIME</div>
            <h3>Flash Sale</h3>
            <p>Up to 50% off selected handmade items</p>
            <a href="catalog.php" class="offer-btn">View Sale</a>
          </div>
        </div>
      </div>

      <!-- Newsletter Signup -->
      <div class="newsletter-section">
        <div class="newsletter-content">
          <h2>📧 Stay Updated</h2>
          <p>Get the latest updates on new products, special offers, and handmade crafts</p>
          <form class="newsletter-form">
            <input type="email" placeholder="Enter your email address" required>
            <button type="submit">Subscribe</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script>
   
    
 



    function showNotification(message, type) {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
      `;
      
      // Add styles
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#4CAF50' : '#f44336'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        animation: slideIn 0.3s ease;
      `;
      
      // Add animation styles
      const style = document.createElement('style');
      style.textContent = `
        @keyframes slideIn {
          from { transform: translateX(100%); opacity: 0; }
          to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
          from { transform: translateX(0); opacity: 1; }
          to { transform: translateX(100%); opacity: 0; }
        }
      `;
      document.head.appendChild(style);
      
      document.body.appendChild(notification);
      
      // Remove notification after 3 seconds
      setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 300);
      }, 3000);
    }
    
    // Add smooth scrolling for better UX
    document.addEventListener('DOMContentLoaded', function() {
      // Add loading animation to buttons
      const buttons = document.querySelectorAll('.btn-primary, .btn-secondary');
      buttons.forEach(button => {
        button.addEventListener('click', function() {
          if (this.onclick) return; // Skip if has onclick handler
          
          const originalText = this.innerHTML;
          this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
          this.disabled = true;
          
          setTimeout(() => {
            this.innerHTML = originalText;
            this.disabled = false;
          }, 1000);
        });
      });
    });
  </script>
</body>
</html>
