<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/AccountManager.php';
verify_csrf();

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'seller') {
    http_response_code(403); die('Forbidden');
}

// Check if account is frozen
if (AccountManager::isAccountFrozen($_SESSION['user']['id'])) {
    http_response_code(403);
    die('Account frozen. Please submit payment proof to continue.');
}
$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    Product::delete($id, (int) $_SESSION['user']['id']);
}
header('Location: ../seller/dashboard.php');
