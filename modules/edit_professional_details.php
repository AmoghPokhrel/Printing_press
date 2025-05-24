<?php
session_start();
$pageTitle = 'Edit Staff Professional Details';
include '../includes/db.php';

// Check if 'id' (user_id) is passed in the URL
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Query to get staff.id using user_id from the staff table
    $query = "SELECT id FROM staff WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);  // Bind the user_id passed in the URL
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $staff = mysqli_fetch_assoc($result);

    // If staff found, use the staff_id to fetch designer details
    if ($staff) {
        $staff_id = $staff['id'];  // Get the staff_id

        // Query the designer table using the staff_id
        $designer_query = "SELECT * FROM designer WHERE staff_id = ?";
        $designer_stmt = mysqli_prepare($conn, $designer_query);
        mysqli_stmt_bind_param($designer_stmt, "i", $staff_id);  // Use staff_id to fetch designer details
        mysqli_stmt_execute($designer_stmt);
        $designer_result = mysqli_stmt_get_result($designer_stmt);

        // Fetch the designer data if available
        if ($designer_data = mysqli_fetch_assoc($designer_result)) {
            // If data is found, populate the form with it
        } else {
            echo "No designer details found for this staff member.";
            exit();
        }
    } else {
        echo "Staff not found!";
        exit();
    }
} else {
    echo "User ID is missing!";
    exit();
}

// Handling form submission for updating designer details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expertise = mysqli_real_escape_string($conn, $_POST['expertise']);
    $portfolio_link = mysqli_real_escape_string($conn, $_POST['portfolio_link']);
    $years_experience = mysqli_real_escape_string($conn, $_POST['years_experience']);
    $software_skills = mysqli_real_escape_string($conn, $_POST['software_skills']);
    $certifications = mysqli_real_escape_string($conn, $_POST['certifications']);
    $availability = mysqli_real_escape_string($conn, $_POST['availability']);

    // Update query
    $update_query = "UPDATE designer SET expertise = ?, portfolio_link = ?, years_experience = ?, software_skills = ?, certifications = ?, availability = ? WHERE staff_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ssisssi", $expertise, $portfolio_link, $years_experience, $software_skills, $certifications, $availability, $staff_id);

    if (mysqli_stmt_execute($update_stmt)) {
        echo "<script>alert('Professional details updated successfully.'); window.location.href = 'view_professional_details.php?id=" . $user_id . "';</script>";
        exit();
    } else {
        echo "Error updating details. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Professional Details</title>
    <link rel="stylesheet" href="../assets/css/professional_details.css">
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="back-button">
            <a href="../pages/staff_setup.php" class="btn-primary">Back</a>
        </div>

        <div class="container">
            <h2>Edit Designer Details</h2>

            <?php if (isset($designer_data)): ?>
                <form method="POST" action="edit_professional_details.php?id=<?php echo $user_id; ?>">
                    <label for="expertise">Expertise:</label>
                    <input type="text" name="expertise" value="<?= htmlspecialchars($designer_data['expertise']) ?>"
                        required>

                    <label for="portfolio_link">Portfolio Link:</label>
                    <input type="url" name="portfolio_link"
                        value="<?= htmlspecialchars($designer_data['portfolio_link']) ?>" required>

                    <label for="years_experience">Years of Experience:</label>
                    <input type="number" name="years_experience"
                        value="<?= htmlspecialchars($designer_data['years_experience']) ?>" required>

                    <label for="software_skills">Software Skills:</label>
                    <input type="text" name="software_skills"
                        value="<?= htmlspecialchars($designer_data['software_skills']) ?>" required>

                    <label for="certifications">Certifications:</label>
                    <input type="text" name="certifications"
                        value="<?= htmlspecialchars($designer_data['certifications']) ?>" required>

                    <label for="availability">Availability:</label>
                    <input type="text" name="availability" value="<?= htmlspecialchars($designer_data['availability']) ?>"
                        required>

                    <button type="submit" class="btn-primary">Update Details</button>
                </form>
            <?php else: ?>
                <p>No designer details found for this staff.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>

</html>