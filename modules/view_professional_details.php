<?php
session_start();
$pageTitle = 'Staff Professional Details';
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
            // Proceed to display designer details
        } else {
            echo "<script>alert('No designer details found for this staff member.'); window.location.href='../pages/staff_setup.php';</script>";
        }

        // Fetch templates created by this staff member (with category name)
        $templates_query = "SELECT t.*, c.c_Name as category_name FROM templates t LEFT JOIN category c ON t.c_id = c.c_id WHERE t.staff_id = ? ORDER BY t.created_at DESC";
        $templates_stmt = mysqli_prepare($conn, $templates_query);
        mysqli_stmt_bind_param($templates_stmt, "i", $staff_id);
        mysqli_stmt_execute($templates_stmt);
        $templates_result = mysqli_stmt_get_result($templates_stmt);
        $staff_templates = [];
        while ($template = mysqli_fetch_assoc($templates_result)) {
            $staff_templates[] = $template;
        }
    } else {
        echo "<script>alert('Staff not found!'); window.location.href='../pages/staff_setup.php';</script>";
    }
} else {
    echo "User ID is missing!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Details</title>
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
            <h2>Designer Details</h2>

            <?php if (isset($designer_data)): ?>
                <table class="designer-table">
                    <tr>
                        <th>Expertise</th>
                        <td><?= htmlspecialchars($designer_data['expertise']) ?></td>
                    </tr>
                    <tr>
                        <th>Portfolio Link</th>
                        <td><a href="<?= htmlspecialchars($designer_data['portfolio_link']) ?>"
                                target="_blank"><?= htmlspecialchars($designer_data['portfolio_link']) ?></a></td>
                    </tr>
                    <tr>
                        <th>Years of Experience</th>
                        <td><?= htmlspecialchars($designer_data['years_experience']) ?></td>
                    </tr>
                    <tr>
                        <th>Software Skills</th>
                        <td><?= htmlspecialchars($designer_data['software_skills']) ?></td>
                    </tr>
                    <tr>
                        <th>Certifications</th>
                        <td><?= htmlspecialchars($designer_data['certifications']) ?></td>
                    </tr>
                    <tr>
                        <th>Availability</th>
                        <td><?= htmlspecialchars($designer_data['availability']) ?></td>
                    </tr>
                </table>
                <h3 style="margin-top:40px;">Templates Created by This Staff</h3>
                <?php if (!empty($staff_templates)): ?>
                    <table class="designer-table">
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Created At</th>
                            <th>Preview</th>
                        </tr>
                        <?php foreach ($staff_templates as $template): ?>
                            <tr>
                                <td><?= htmlspecialchars($template['title'] ?? $template['name'] ?? 'Untitled') ?></td>
                                <td><?= htmlspecialchars($template['category_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($template['created_at'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($template['image_path'])): ?>
                                        <a href="#"
                                            onclick="showImageModal('../uploads/templates/<?= htmlspecialchars($template['image_path']) ?>'); return false;">
                                            <img src="../uploads/templates/<?= htmlspecialchars($template['image_path']) ?>"
                                                alt="Preview" style="max-width:80px;max-height:80px;">
                                        </a>
                                    <?php else: ?>
                                        No Preview
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>No templates found for this staff member.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>No designer details found for this staff.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <!-- Add this just before </body> -->
    <div id="imageModal"
        style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); align-items:center; justify-content:center;">
        <span onclick="closeImageModal()"
            style="position:absolute; top:30px; right:50px; color:#fff; font-size:40px; cursor:pointer;">&times;</span>
        <img id="modalImg" src=""
            style="max-width:90vw; max-height:90vh; display:block; margin:auto; box-shadow:0 0 20px #000; border-radius:8px;">
    </div>
    <script>
        function showImageModal(src) {
            document.getElementById('modalImg').src = src;
            document.getElementById('imageModal').style.display = 'flex';
        }
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
            document.getElementById('modalImg').src = '';
        }
        // Optional: close modal when clicking outside the image
        window.onclick = function (event) {
            var modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeImageModal();
            }
        }
    </script>
</body>

</html>