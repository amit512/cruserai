<?php
// public/partials/navbar.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Safe escape function
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Get logged-in user info
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? null;
$frozen = $user['is_frozen'] ?? null;

// Decide dashboard link if buyer/seller
$dashboardHref = $role === 'buyer' ? 'buyer-dashboard.php'
               : ($role === 'seller' ? 'seller-dashboard.php' : null);

// Helper function to set 'active' class
function isActive($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>
<style>
/* Header and Navigation Styles */
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
</style>

<header class="main-header">
    <div class="logo"><span>Hand</span>Craft</div>

    <nav class="nav-links">
        <ul>
        <?php if ($frozen == 'false'): ?>
            <li><a href="index.php" class="<?= isActive('index.php') ?>">Home</a></li>
            <li><a href="about.php" class="<?= isActive('about.php') ?>">About</a></li>

            <?php if ($dashboardHref): ?>
                <li>
                    <a href="<?= $dashboardHref ?>" class="<?= isActive(basename($dashboardHref)) ?>">
                        Dashboard
                    </a>
                </li>
                
            <?php endif; ?>
            <?php if ($role === 'seller'): ?>
                
            <li><a href="../app/manage-products.php">Products</a></li>

            <li><a href="../app/manage-order.php">Orders</a></li>
            
            <?php endif; ?>
            <?php if ($role === 'buyer'): ?>
            
            <li><a href="../public/my-orders.php">Orders</a></li>
            
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
                <li><a href="../admin/billing.php" class="<?= isActive('billing.php') ?>">Billing</a></li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="header-icons">
        <?php if ($user): ?>
            <span class="welcome">Hello, <?= e($user['name']) ?></span>
            
            <?php if ($role === 'buyer'): ?>
                <a href="wishlist.php" class="btn wishlist" title="My Wishlist">
                    <i class="fas fa-heart"></i> Wishlist
                </a>
                <a href="cart.php" class="btn cart" title="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i> Cart
                </a>
                
            <?php endif; ?>
            <?php if ($frozen == 'false'): ?>
                 <a href="logout.php" class="btn logout">Logout</a>
                 <?php endif; ?>
                 <a href="../public/logout.php" class="btn logout">Logout</a>
        <?php else: ?>
            
            <a href="login.php" class="btn login">Login</a>
            <a href="register.php" class="btn register">Register</a>
        <?php endif; ?>
    </div>
</header>
