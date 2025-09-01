<?php
require_once __DIR__ . '/../config/config.php';


// Display flash messages
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - HandCraft</title>
  <link rel="stylesheet" href="registration.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
  <div class="auth-container">
    <div class="auth-box">
      <h2>Create Account</h2>
      <p>Join HandCraft today</p>

      <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="POST" action="../actions/register.php">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="input-group">
          <i class="fas fa-user"></i>
          <input type="text" name="name" placeholder="Full Name" required />
        </div>
        <div class="input-group">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" placeholder="Email" required />
        </div>
        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" placeholder="Password (min 6 chars)" minlength="6" required />
        </div>
        <div class="input-group">
          <i class="fas fa-user-tag"></i>
          <select name="role" required>
            <option value="buyer">Buyer</option>
            <option value="seller">Seller</option>
          </select>
        </div>
        <button type="submit" class="btn register-btn">Register</button>
        <p class="switch-text">Already have an account? <a href="login.php">Login</a></p>
      </form>
    </div>
  </div>
</body>
</html>
