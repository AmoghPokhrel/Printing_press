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
    <style>
        .main-content {
            min-height: calc(100vh - 60px);
            padding-bottom: 60px;
            position: relative;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto 60px;
            padding: 20px;
        }

        .back-button {
            width: 100%;
            max-width: 1000px;
            margin: 20px auto;
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
            color: #2d3748;
            font-size: 1.8rem;
            margin-bottom: 25px;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
        }

        form {
            background: #fff;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #4a5568;
            font-size: 0.95rem;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
        }

        input[type="text"],
        input[type="url"],
        input[type="number"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #2d3748;
            transition: all 0.2s ease;
            margin-bottom: 0;
            font-family: 'Inter', sans-serif;
        }

        input[type="text"]:focus,
        input[type="url"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }

        button[type="submit"] {
            background-color: #2ecc71;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: block;
            width: 100%;
            margin-top: 16px;
            font-family: 'Inter', sans-serif;
        }

        button[type="submit"]:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        /* Error message styling */
        .error-message {
            color: #e53e3e;
            font-size: 0.9rem;
            margin-top: 4px;
            font-family: 'Inter', sans-serif;
        }

        /* Success message styling */
        .success-message {
            background-color: #c6f6d5;
            color: #2f855a;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-family: 'Inter', sans-serif;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            form {
                padding: 20px;
            }

            h2 {
                font-size: 1.5rem;
            }

            input[type="text"],
            input[type="url"],
            input[type="number"] {
                padding: 10px 14px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="back-button">
            <a href="../pages/staff_setup.php" class="btn-primary">← Back</a>
        </div>

        <div class="container">
            <h2>Edit Designer Details</h2>

            <?php if (isset($designer_data)): ?>
                <form method="POST" action="edit_professional_details.php?id=<?php echo $user_id; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="expertise">Expertise:</label>
                            <input type="text" id="expertise" name="expertise"
                                value="<?= htmlspecialchars($designer_data['expertise']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="portfolio_link">Portfolio Link:</label>
                            <input type="url" id="portfolio_link" name="portfolio_link"
                                value="<?= htmlspecialchars($designer_data['portfolio_link']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="years_experience">Years of Experience:</label>
                            <input type="number" id="years_experience" name="years_experience"
                                value="<?= htmlspecialchars($designer_data['years_experience']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="software_skills">Software Skills:</label>
                            <input type="text" id="software_skills" name="software_skills"
                                value="<?= htmlspecialchars($designer_data['software_skills']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="certifications">Certifications:</label>
                            <input type="text" id="certifications" name="certifications"
                                value="<?= htmlspecialchars($designer_data['certifications']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="availability">Availability:</label>
                            <input type="text" id="availability" name="availability"
                                value="<?= htmlspecialchars($designer_data['availability']) ?>" required>
                        </div>
                    </div>

                    <button type="submit">Update Details</button>
                </form>
            <?php else: ?>
                <p class="error-message">No designer details found for this staff.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>

</html>