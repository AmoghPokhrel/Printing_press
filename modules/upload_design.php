<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['design_image']) && isset($_POST['staff_id'])) {
    $staff_id = intval($_POST['staff_id']);
    $upload_dir = '../uploads/design_catalog/'; // Make sure this directory exists!
    $filename = basename($_FILES['design_image']['name']);
    $target_file = $upload_dir . time() . '_' . $filename;

    // Move uploaded file
    if (move_uploaded_file($_FILES['design_image']['tmp_name'], $target_file)) {
        $image_path = str_replace('../uploads/', '', $target_file); // Save relative path

        $insert = "INSERT INTO design_catalog (staff_id, image_path) VALUES (?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("is", $staff_id, $image_path);

        if ($stmt->execute()) {
            echo "Design uploaded and saved!";
        } else {
            echo "Database error: " . $conn->error;
        }
    } else {
        echo "File upload failed.";
    }
} else {
    echo "Invalid request.";
}
?>