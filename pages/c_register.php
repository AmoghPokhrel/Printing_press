<?php
require_once '../includes/init.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Registration</title>
  <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
  <nav>
    <ul>
      <li><a href="../index.php">Home</a></li>
      <li><a href="templates.php">Templates</a></li>
      <li><a href="contact.php">Contact</a></li>
      <li class="login"><a href="login.php">Login</a></li>
    </ul>
  </nav>

  <div class="content">
    <div class="card">
      <h1>Customer Registration</h1>
      <form method="POST" action="../modules/process_c_registration.php">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required placeholder="Enter your full name"
          value="<?php echo isset($_SESSION['form_data']['name']) ? htmlspecialchars($_SESSION['form_data']['name']) : ''; ?>"
          autocomplete="off">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required placeholder="Enter your email"
          value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>"
          autocomplete="off">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="Enter your password"
          autocomplete="off">

        <label for="phone">Phone</label>
        <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number"
          value="<?php echo isset($_SESSION['form_data']['phone']) ? htmlspecialchars($_SESSION['form_data']['phone']) : ''; ?>"
          autocomplete="off">

        <label for="address">Address</label>
        <input type="text" id="address" name="address" required placeholder="Enter your address"
          value="<?php echo isset($_SESSION['form_data']['address']) ? htmlspecialchars($_SESSION['form_data']['address']) : ''; ?>"
          autocomplete="off">

        <label for="dob">Date of Birth</label>
        <input type="date" id="dob" name="dob" required
          value="<?php echo isset($_SESSION['form_data']['dob']) ? htmlspecialchars($_SESSION['form_data']['dob']) : ''; ?>">

        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
          <option value="">Select Gender</option>
          <option value="male" <?php echo (isset($_SESSION['form_data']['gender']) && $_SESSION['form_data']['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
          <option value="female" <?php echo (isset($_SESSION['form_data']['gender']) && $_SESSION['form_data']['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
          <option value="other" <?php echo (isset($_SESSION['form_data']['gender']) && $_SESSION['form_data']['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
        </select>

        <button type="submit">Register</button>
      </form>
    </div>
  </div>

</body>

</html>