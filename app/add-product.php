<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = db();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    // Handle image upload
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = 'uploads/' . $filename;
        } else {
            $error = "Failed to upload image.";
        }
    }

    if (!$error && $name && $price > 0 && $stock >= 0) {
        $stmt = $pdo->prepare("INSERT INTO products (seller_id, name, description, price, stock, image) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$user['id'], $name, $description, $price, $stock, $imagePath]);
        $success = "Product added successfully!";
    } elseif (!$error) {
        $error = "Please fill all required fields correctly.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product - HandCraft</title>
<link rel="stylesheet" href="add-product.css">
</head>
<body>

<header class="main-header">
  <div class="logo"><span>Hand</span>Craft</div>
  <nav class="nav-links">
    <ul>
      <li><a href="../public/seller-dashboard.php">Dashboard</a></li>
      <li><a href="add-product.php" class="active">Add Product</a></li>
      <li><a href="manage-products.php">Manage Products</a></li>
    </ul>
  </nav>
  <div class="header-icons">
    <span class="welcome">Hello, <?= htmlspecialchars($user['name']) ?></span>
    <a href="logout.php" class="btn login">Logout</a>
  </div>
</header>

<section class="dashboard-hero">
  <h1>Add New Product</h1>
  <p>Fill in the details to add a new product to your store.</p>
</section>

<section class="dashboard-products">
  <?php if ($success): ?>
      <p class="empty-message" style="color:green;"><?= htmlspecialchars($success) ?></p>
  <?php elseif ($error): ?>
      <p class="empty-message" style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form action="add-product.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:15px;">
      <label>
          Product Name:
          <input type="text" name="name" placeholder="Enter product name" required>
      </label>

      <label>
          Description:
          <textarea name="description" placeholder="Enter product description" rows="4"></textarea>
      </label>

      <label>
          Price (Rs):
          <input type="number" name="price" step="0.01" min="0" placeholder="Enter price" required>
      </label>

      <label>
          Stock Quantity:
          <input type="number" name="stock" min="0" placeholder="Enter stock quantity" required>
      </label>

      <label>
          Product Image:
          <input type="file" name="image" accept="image/*">
      </label>

      <button type="submit" class="btn">Add Product</button>
  </form>
</section>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-section">
      <h4>HandCraft</h4>
      <p>Sell your handmade products efficiently.</p>
    </div>
    <div class="footer-section">
      <h4>Quick Links</h4>
      <a href="seller-dashboard.php">Dashboard</a>
      <a href="manage-products.php">Manage Products</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date("Y") ?> HandCraft. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
