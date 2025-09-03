<?php
// seller/edit-product.php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/config.php'; // <-- uses your db() from config.php
require_once __DIR__ . '/../app/AccountManager.php';

// only sellers
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    header('Location: ../public/login.php');
    exit;
}

// Check if account is frozen
if (AccountManager::isAccountFrozen($_SESSION['user']['id'])) {
    header('Location: payment-upload.php');
    exit;
}

$pdo = db();                       // <-- CREATE $pdo
$sellerId = (int)$_SESSION['user']['id'];
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) { die('Invalid product id'); }

// fetch product owned by this seller
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

    // keep old image unless new uploaded
    $image = $product['image']; // store just the filename in DB

    // image upload (optional)
    if (!empty($_FILES['image']['name'])) {
        // ensure uploads dir at project root: /homecraft-php/uploads
        $uploadDir = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        if ($uploadDir === false) {
            // create if not exists
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed, true)) {
            $err = 'Only JPG, PNG, WEBP or GIF images are allowed.';
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $err = 'Image upload failed.';
        } else {
            $newName = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['image']['name']));
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
                $image = $newName; // save filename only
            } else {
                $err = 'Could not move uploaded file.';
            }
        }
    }

    // basic validation
    if (!$err) {
        if ($name === '' || $price < 0 || $stock < 0) {
            $err = 'Please fill all fields correctly.';
        } else {
            $stmt = $pdo->prepare('UPDATE products 
                                   SET name = ?, description = ?, price = ?, stock = ?, image = ?
                                   WHERE id = ? AND seller_id = ?');
            $stmt->execute([$name, $desc, $price, $stock, $image, $productId, $sellerId]);
            $ok = 'Product updated successfully.';
            // refresh product data
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
<link rel="stylesheet" href="../public/seller-dashboard.css">
<link rel="stylesheet" href="edit.css">
</head>
<body>

<header class="main-header">
  <div class="logo"><span>Hand</span>Craft</div>
  <nav class="nav-links">
    <ul>
      <li><a href="../public/seller-dashboard.php">Dashboard</a></li>
      <li><a href="../public/manage-products.php" class="active">Products</a></li>
      <li><a href="../public/manage-orders.php">Orders</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>
    <a href="../public/logout.php" class="btn login">Logout</a>
  </div>
</header>

<section class="dashboard-hero">
  <h1>Edit Product</h1>
  <p>Update the details of your item.</p>
</section>

<section class="dashboard-products">
  <?php if ($ok): ?>
    <p class="empty-message" style="color:#0a7a0a;"><?= htmlspecialchars($ok) ?></p>
  <?php endif; ?>
  <?php if ($err): ?>
    <p class="empty-message" style="color:#b00020;"><?= htmlspecialchars($err) ?></p>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="product-form">
    <label>Product Name
      <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
    </label>

    <label>Description
      <textarea name="description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
    </label>

    <label>Price (Rs)
      <input type="number" name="price" step="1" min="0" value="<?= htmlspecialchars((string)(float)$product['price']) ?>" required>
    </label>

    <label>Stock
      <input type="number" name="stock" min="0" value="<?= (int)$product['stock'] ?>" required>
    </label>

    <label>Product Image (optional)
      <input type="file" name="image" accept="image/*">
    </label>

    <?php if (!empty($product['image'])): ?>
      <p style="margin-top:8px">
        <img src="image.php?file=<?php echo urlencode($product['image']); ?>" width="600" style="border-radius:8px;border:1px solid #ddd;">
      </p>
    <?php endif; ?>

    <button type="submit" class="btn">Update Product</button>
    <a class="btn btn-danger" href="../public/manage-products.php">Cancel</a>
  </form>
</section>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-section">
      <h4>HandCraft</h4>
      <p>Sell your handmade products easily and efficiently.</p>
    </div>
    <div class="footer-section">
      <h4>Quick Links</h4>
      <a href="../public/seller-dashboard.php">Dashboard</a>
      <a href="../public/manage-products.php">Products</a>
      <a href="../public/logout.php">Logout</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> HandCraft. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
