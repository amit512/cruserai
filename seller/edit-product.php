<?php
// seller/edit-product.php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    header('Location: ../public/login.php');
    exit;
}

$pdo = db();
$user = $_SESSION['user'];
$sellerId = (int)$user['id'];
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) { die('Invalid product id'); }

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND seller_id = ?');
$stmt->execute([$productId, $sellerId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) { die('Product not found or not owned by you.'); }

$err = '';
$ok  = '';

// handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    $image = $product['image']; // default

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];

        if (!in_array($ext, $allowed, true)) {
            $err = 'Only JPG, PNG, WEBP or GIF images are allowed.';
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $err = 'Image upload failed.';
        } else {
            $newName = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['image']['name']));
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
                $image = $newName;
            } else {
                $err = 'Could not move uploaded file.';
            }
        }
    }

    if (!$err) {
        if ($name === '' || $price < 0 || $stock < 0) {
            $err = 'Please fill all fields correctly.';
        } else {
            $stmt = $pdo->prepare('UPDATE products 
                                   SET name = ?, description = ?, price = ?, stock = ?, image = ?
                                   WHERE id = ? AND seller_id = ?');
            $stmt->execute([$name, $desc, $price, $stock, $image, $productId, $sellerId]);

            $ok = 'Product updated successfully.';

            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND seller_id = ?');
            $stmt->execute([$productId, $sellerId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Product - HandCraft</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../public/handcraf.css"/>
<link rel="stylesheet" href="../public/startstyle.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
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
/* Reuse the same dashboard theme */
  .dashboard-hero {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    text-align: center;
    padding: 3rem 0;
    margin-bottom: 2rem;
  }
  .dashboard-hero h1 { font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem; }
  .dashboard-hero p { font-size: 1.2rem; opacity: 0.9; }

  .form-container {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    max-width: 800px;
    margin: 0 auto 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  .product-form label {
    display: block;
    font-weight: 500;
    color: #333;
    margin-bottom: 0.5rem;
  }
  .product-form input,
  .product-form textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-size: 1rem;
  }
  .product-form input:focus,
  .product-form textarea:focus {
    border-color: #2196F3;
  }

  .product-form .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    border: none;
    font-weight: 500;
    cursor: pointer;
  }
  .btn-primary {
    background: #2196F3;
    color: white;
  }
  .btn-primary:hover { background: #1976D2; }
  .btn-danger {
    background: #dc3545;
    color: white;
  }
  .btn-danger:hover { background: #c82333; }

/* Alerts */
.alert {
  padding: 1rem 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
}
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.alert i { font-size: 1.1rem; }

</style>
</head>
<body>
<header class="main-header">
  <div class="logo"><span>Hand</span>Craft</div>
  <nav class="nav-links">
    <ul>
      <li><a href="../public/seller-dashboard.php">Dashboard</a></li>
      <li><a href="../app/manage-products.php" class="active">Products</a></li>
      <li><a href="../app/manage-order.php">Orders</a></li>
      <li><a href="../app/seller-analytics.php">Analytics</a></li>
      <li><a href="../app/manage-customers.php">Customers</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($user['name']) ?></span>
    <a href="../public/logout.php" class="btn logout">Logout</a>
  </div>
</header>

<section class="dashboard-hero">
  <div class="container">
    <h1>Edit Product</h1>
    <p>Update the details of your handcrafted item.</p>
  </div>
</section>

<div class="form-container">
  <?php if ($ok): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($ok) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="product-form">
    <label>Product Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>

    <label>Description</label>
    <textarea name="description" rows="5"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

    <label>Price (Rs)</label>
    <input type="number" name="price" step="1" min="0" value="<?= htmlspecialchars((string)(float)$product['price']) ?>" required>

    <label>Stock</label>
    <input type="number" name="stock" min="0" value="<?= (int)$product['stock'] ?>" required>

    <label>Product Image (optional)</label>
    <input type="file" name="image" accept="image/*">

    <?php if (!empty($product['image'])): ?>
      <p><img src="image.php?file=<?= urlencode($product['image']); ?>" width="300" style="border-radius:8px;border:1px solid #ddd;"></p>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
    <a class="btn btn-danger" href="manage-products.php"><i class="fas fa-times"></i> Cancel</a>
  </form>
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
    <p>&copy; <?= date('Y') ?> HandCraft. All rights reserved.</p>
  </div>
</footer>
</body>
</html>
