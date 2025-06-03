<?php
require_once '../includes/db.php';
session_start();
$pageTitle = 'Edit Staff';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo '<script>alert("You need administrator privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

// Check if 'id' parameter is passed for editing a specific staff member
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid request.'); window.location.href='staff_setup.php';</script>";
    exit();
}

$id = $_GET['id'];

// Fetch existing data for the staff member from the database
$query = "SELECT * FROM users WHERE id = '$id' AND role = 'Staff'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Staff not found.'); window.location.href='staff_setup.php';</script>";
    exit();
}

$staff = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff</title>
    <link rel="stylesheet" href="../assets/css/a_register.css">
    <style>
        .main-content {
            min-height: calc(100vh - 60px);
            padding-bottom: 60px;
            position: relative;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }

        .back-button {
            width: 100%;
            max-width: 1000px;
            margin: 10px auto;
            padding: 0;
            text-align: left;
            padding-left: 5px;
        }

        .back-button .btn-primary {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            background-color: #2ecc71;
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .back-button .btn-primary:hover {
            transform: translateX(-4px);
            background-color: #27ae60;
        }

        h2 {
            margin-top: 10px;
            margin-bottom: 20px;
            color: #2d3748;
            font-size: 1.8rem;
            font-weight: 500;
        }

        form {
            background: #fff;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>

<body>

    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="back-button">
            <a href="staff_setup.php" class="btn-primary">← Back</a>
        </div>

        <div class="container">
            <h2>Edit Staff</h2>

            <form method="POST" action="../modules/staff_edit_backend.php">

                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <label for="name">Full Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($staff['name']); ?>" required>

                <label for="email">Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" readonly
                    required>

                <label for="phone">Phone:</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($staff['phone']); ?>" required>

                <label for="address">Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($staff['address']); ?>" required>

                <label for="dob">Date of Birth:</label>
                <input type="date" name="DOB" value="<?php echo !empty($staff['DOB']) ? $staff['DOB'] : ''; ?>"
                    required>

                <label for="gender">Gender:</label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="male" <?php echo ($staff['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo ($staff['gender'] === 'female') ? 'selected' : ''; ?>>Female
                    </option>
                    <option value="other" <?php echo ($staff['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                </select>

                <!-- Add staff role field -->
                <label for="staff_role">Staff Role:</label>
                <select name="staff_role" required>
                    <option value="Designer" <?php echo $staff['role'] == 'Designer' ? 'selected' : ''; ?>>Designer
                    </option>
                </select>

                <button type="submit" class="btn-primary">Update Staff</button>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

</body>

</html>