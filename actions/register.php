<?php
require_once __DIR__ . '/../config/config.php';
session_start();

// Verify CSRF token
verify_csrf();

$name  = trim($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$pass  = $_POST['password'] ?? '';
$role  = in_array($_POST['role'] ?? 'buyer', ['buyer','seller'], true) ? $_POST['role'] : 'buyer';

// Basic validation
if (!$name || !$email || strlen($pass) < 6) {
    $_SESSION['flash'] = 'Please fill all fields correctly.';
    header('Location: ../public/register.php'); exit;
}

try {
    $pdo = db();

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['flash'] = 'Email already registered.';
        header('Location: ../public/register.php'); exit;
    }

    // Hash password and insert user
    $hashed = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,subscription_expires) VALUES (?,?,?,?,?)");
    
    // Set 3-day trial for sellers, no expiry for buyers
    $subscriptionExpires = null;
    if ($role === 'seller') {
        $trialEnd = (new DateTime('today'))->modify('+3 days')->format('Y-m-d');
        $subscriptionExpires = $trialEnd;
    }
    
    $stmt->execute([$name,$email,$hashed,$role,$subscriptionExpires]);

    // Save user info in session
    $_SESSION['user'] = [
        'id' => $pdo->lastInsertId(),
        'name' => $name,
        'email' => $email,
        'role' => $role
    ];

    header('Location: ../public/login.php'); exit;

} catch (Exception $e) {
    $_SESSION['flash'] = 'Registration failed: ' . $e->getMessage();
    header('Location: ../public/register.php'); exit;
}
