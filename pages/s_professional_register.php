<?php
session_start();
$pageTitle = 'Staff Professional Details';
include '../includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff', 'Super Admin'])) {
    echo '<script>alert("You need administrator privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

// Store staff ID in session if provided (Admins and Super Admin)
if (($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Super Admin') && isset($_GET['id'])) {
    $_SESSION['selected_id'] = $_GET['id'];
}

// If Staff is logged in, ensure they can only edit their own details
if ($_SESSION['role'] === 'Staff') {
    $_SESSION['selected_id'] = $_SESSION['user_id']; // Staff can only edit their own details
}

// Check if selected_id is set
if (!isset($_SESSION['selected_id'])) {
    echo '<script>alert("Staff selection is missing!"); window.location.href = "staff_setup.php";</script>';
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
            <h2>Staff Professional Details</h2>

            <form method="POST" action="../modules/staff_professional_register.php">
                <!-- Hidden input to pass the staff ID -->
                <input type="hidden" name="staff_id"
                    value="<?php echo isset($_SESSION['selected_id']) ? $_SESSION['selected_id'] : ''; ?>">
                <label for="expertise"> Expertise: </label>
                <input type="text" id="expertise" name="expertise" required>

                <label for="portfolio_link">Portfolio Link:</label>
                <input type="text" id="portfolio_link" name="portfolio_link" required>

                <label for="years_experience">Years of Experience:</label>
                <input type="number" id="years_experience" name="years_experience" required>

                <label for="software_skills">Software Skills:</label>
                <input type="text" id="software_skills" name="software_skills" required>

                <label for="certifications">Certifications:</label>
                <input type="text" id="certifications" name="certifications" required>

                <label for="availability">Availability:</label>
                <select name="availability" required>
                    <option value="full-time">Full-time</option>
                    <option value="part-time">Part-time</option>
                    <option value="freelance">Freelance</option>
                </select>

                <button type="submit">Save Details</button>
            </form>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

</body>

</html>