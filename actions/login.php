<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Verify CSRF token
verify_csrf();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    $_SESSION['flash'] = 'Please fill all fields.';
    header('Location: ../public/login.php');
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        // Redirect based on role
        if ($user['role'] === 'buyer') {
            header('Location: ../public/buyer-dashboard.php');
        } elseif ($user['role'] === 'seller') {
            header('Location: ../public/seller-dashboard.php');
        }
        elseif ($user['role'] === 'admin') {
            header('Location: ../admin/admin-dashboard.php');
        }
        else {
            // default fallback
            header('Location: ../public/login.php');
        }
        exit;
    } else {
        $_SESSION['flash'] = 'Invalid email or password.';
        header('Location: ../public/login.php');
        exit;
    }

} catch (Exception $e) {
    $_SESSION['flash'] = 'Login failed: ' . $e->getMessage();
    header('Location: ../public/login.php');
    exit;
}
