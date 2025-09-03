<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/AccountManager.php';
verify_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); die('Forbidden');
}



// Server-side freeze enforcement
try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT is_frozen FROM seller_accounts WHERE seller_id = ?");
    $stmt->execute([ (int)$_SESSION['user']['id'] ]);
    $acc = $stmt->fetch();
    if ($acc && (int)$acc['is_frozen'] === 1) {
        http_response_code(403);
        die('Account frozen. Please clear dues.');
    }
} catch (Exception $e) {}


// Check if account is frozen
if (AccountManager::isAccountFrozen($_SESSION['user']['id'])) {
    http_response_code(403);
    die('Account frozen. Please submit payment proof to continue.');
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = (float) ($_POST['price'] ?? 0);
$stock = (int) ($_POST['stock'] ?? 0);
$is_active = !empty($_POST['is_active']) ? 1 : 0;

$image_path = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (in_array($ext, $allowed, true)) {
        $safe = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = __DIR__ . '/../public/uploads/' . $safe;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $image_path = $safe;
        }
    }
}

if (!$title || !$description || $price <= 0 || $stock < 0) {
    $_SESSION['flash'] = 'Please fill all fields correctly.';
    header('Location: ../seller/product-new.php'); exit;
}

$id = Product::create([
    'seller_id' => (int) $_SESSION['user']['id'],
    'title' => $title,
    'description' => $description,
    'price' => $price,
    'stock' => $stock,
    'image_path' => $image_path,
    'is_active' => $is_active,
]);

header('Location: ../seller/dashboard.php');
