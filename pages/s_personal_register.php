<?php
session_start();
$pageTitle = 'Staff Registration';
include '../includes/db.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo '<script>alert("You need administrator privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

$error = "";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/a_register.css">
</head>

<body>

    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="back-button">
            <a href="staff_setup.php" class="btn-primary">Back</a>
        </div>

        <div class="container">
            <h2>Staff Registration</h2>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="../modules/staff_personal_register.php">
                <label for="name">Full Name:</label>
                <input type="text" name="name" required>

                <label for="email">Email:</label>
                <input type="email" name="email" required>

                <label for="phone">Phone:</label>
                <input type="text" name="phone" required>

                <label for="address">Address:</label>
                <input type="text" name="address" required>

                <label for="password">Password:</label>
                <input type="password" name="password" required>

                <label for="dob">Date of Birth:</label>
                <input type="date" name="dob" required>

                <label for="gender">Gender:</label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>

                <label for="staff_role">Staff Role:</label>
                <select name="staff_role" required>
                    <option value="" disabled selected>Select Role</option>
                    <option value="Designer">Designer</option>
                </select>

                <button type="submit" class="btn-primary1">Register Staff</button>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

</body>

</html>