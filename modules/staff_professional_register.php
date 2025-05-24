<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['selected_id']) || empty($_SESSION['selected_id'])) {
        echo '<script>alert("Error: Staff selection is missing!"); window.history.back();</script>';
        exit();
    }

    $selected_id = $_SESSION['selected_id'];

    // Fetch the actual staff ID from the staff table
    $query = "SELECT id FROM staff WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $selected_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $staff = mysqli_fetch_assoc($result);

    if (!$staff) {
        echo '<script>alert("Error: Staff not found!"); window.history.back();</script>';
        exit();
    }

    $staff_id = $staff['id'];

    // Check if the staff already has professional details in the designer table
    $checkQuery = "SELECT * FROM designer WHERE staff_id = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "i", $staff_id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    // If a record already exists for this staff_id, prevent insertion
    if (mysqli_num_rows($checkResult) > 0) {
        echo '<script>alert("This staff member already has professional details!"); window.history.back();</script>';
        exit();
    }

    // Collect form data
    $expertise = $_POST['expertise'];
    $portfolio_link = $_POST['portfolio_link'];
    $years_experience = $_POST['years_experience'];
    $software_skills = $_POST['software_skills'];
    $certifications = $_POST['certifications'];
    $availability = $_POST['availability'];

    // Insert data into designer table
    $insertQuery = "INSERT INTO designer (staff_id, expertise, portfolio_link, years_experience, software_skills, certifications, availability)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($stmt, "ississs", $staff_id, $expertise, $portfolio_link, $years_experience, $software_skills, $certifications, $availability);

    if (mysqli_stmt_execute($stmt)) {
        echo '<script>alert("Professional details saved successfully!"); window.location.href = "../pages/staff_setup.php";</script>';
    } else {
        echo '<script>alert("Error saving details: ' . mysqli_error($conn) . '");</script>';
    }
}
?>