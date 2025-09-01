<?php
session_start();
require_once __DIR__ . '/../config/config.php'; // include db and csrf

// Generate CSRF token

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - HandCraft</title>
  <link rel="stylesheet" href="login.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
  <div class="auth-container">
    <div class="auth-box">
      <h2>Welcome Back</h2>
      <p>Login to your HandCraft account</p>

      <?php if (!empty($_SESSION['flash'])): ?>
        <p style="color:red;"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></p>
      <?php endif; ?>

      <form method="POST" action="../actions/login.php">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="input-group">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" placeholder="Email" required />
        </div>
        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" placeholder="Password" required />
        </div>
        <button type="submit" class="btn login-btn">Login</button>
        <p class="switch-text">Don't have an account? <a href="register.php">Register</a></p>
      </form>
    </div>
  </div>
</body>
</html>
