<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
  <nav>
    <ul>
      <li class="login"><a href="../index.php">Home</a></li>
      <li class="login"><a href="templets.php">Templates</a></li>
      <li class="login"><a href="contact.php">Contact</a></li>
      <li class="login"><a href="login.php">Login</a></li>
    </ul>
  </nav>

  <div class="container">
    <div class="form-container">
      <h1>LOGIN</h1>
      <form method="POST" action="../modules/process_login.php">
        <!-- Add hidden fields for redirect parameters -->
        <?php if (isset($_GET['redirect'])): ?>
          <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
        <?php endif; ?>
        <?php if (isset($_GET['category_id'])): ?>
          <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($_GET['category_id']); ?>">
        <?php endif; ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Password" required>

        <button type="submit">Login</button>
        <a href="../forgot_password.php" class="forgot-password">Forgotten Password?</a>
      </form>
    </div>
    <a href="c_register.php" class="signup">Sign Up</a>
  </div>
</body>

</html>