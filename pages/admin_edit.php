<?php
require_once '../includes/db.php';
session_start();
$pageTitle = 'Edit Admin';

// Redirect if not logged in or not a Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin') {
    echo '<script>alert("You need Super Admin privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

// Check if 'id' parameter is passed for editing a specific user
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid request.'); window.location.href='admin_setup.php';</script>";
    exit();
}

$id = $_GET['id'];

// Fetch existing data for the user from the database
$query = "SELECT * FROM users WHERE id = '$id' AND role = 'Admin'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Admin not found.'); window.location.href='admin_setup.php';</script>";
    exit();
}

$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin</title>
    <link rel="stylesheet" href="../assets/css/a_register.css">
</head>

<body>

    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="back-button">
            <a href="admin_setup.php" class="btn-primary">Back</a>
        </div>

        <div class="container">
            <h2>Edit Admin</h2>

            <form method="POST" action="../modules/admin_edit_backend.php">

                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <label for="name">Full Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

                <label for="email">Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly
                    required>

                <label for="phone">Phone:</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>

                <label for="address">Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>

                <label for="dob">Date of Birth:</label>
                <input type="date" name="DOB" value="<?php echo !empty($user['DOB']) ? $user['DOB'] : ''; ?>" required>

                <label for="gender">Gender:</label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="male" <?php echo ($user['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo ($user['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo ($user['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                </select>

                <button type="submit" class="btn-primary">Update Admin</button>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

</body>

</html>