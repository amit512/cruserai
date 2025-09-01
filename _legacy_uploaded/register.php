<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - HandCraft</title>
  <link rel="stylesheet" href="auth-style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>
  <div class="auth-container">
    <div class="auth-box">
      <h2>Create Account </h2>
      <p>Join the HandCraft community</p>
      <form>
        <div class="input-group">
          <i class="fas fa-user"></i>
          <input type="text" placeholder="Full Name" required />
        </div>
        <div class="input-group">
          <i class="fas fa-envelope"></i>
          <input type="email" placeholder="Email" required />
        </div>
        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" placeholder="Password" required />
        </div>
        <button type="submit" class="btn register-btn">Register</button>
        <p class="switch-text">Already have an account? <a href="login.html">Login</a></p>
      </form>
    </div>
  </div>
</body>
</html>
