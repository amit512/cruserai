<?php
require_once __DIR__ . '/../config/config.php';
verify_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); die('Forbidden');
}
$id = (int) ($_POST['id'] ?? 0);
$product = Product::find($id);
if (!$product || $product['seller_id'] != $_SESSION['user']['id']) {
    http_response_code(404); die('Not found');
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = (float) ($_POST['price'] ?? 0);
$stock = (int) ($_POST['stock'] ?? 0);
$is_active = !empty($_POST['is_active']) ? 1 : 0;

$image_path = $product['image_path'];
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

Product::update($id, [
    'seller_id' => (int) $_SESSION['user']['id'],
    'title' => $title,
    'description' => $description,
    'price' => $price,
    'stock' => $stock,
    'image_path' => $image_path,
    'is_active' => $is_active,
]);

header('Location: ../seller/dashboard.php');
