<?php
require_once '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']); // Get DOB from form
    $gender = mysqli_real_escape_string($conn, $_POST['gender']); // Get gender from form
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Secure password hashing

    // Check if email already exists
    $check_query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) > 0) {
        $error = "Email already exists!";
    } else {
        // Insert new admin into the users table, including DOB
        $insert_query = "INSERT INTO users (name, email, phone, address, dob, gender, password_hash, role, created_at, updated_at) 
                         VALUES ('$name', '$email', '$phone', '$address', '$dob', '$gender', '$hashed_password', 'Admin', NOW(), NOW())";

        if (mysqli_query($conn, $insert_query)) {
            // Get the last inserted user_id
            $user_id = mysqli_insert_id($conn);

            // Now insert the user_id into the Admin table
            $admin_insert_query = "INSERT INTO Admin (user_id) VALUES ('$user_id')";

            if (mysqli_query($conn, $admin_insert_query)) {
                echo '<script>
                        alert("Admin registered successfully!");
                        window.location.href = "../pages/admin_setup.php";
                      </script>';
                exit(); // Ensure script stops execution after redirection
            } else {
                $error = "Error adding user_id to Admin table: " . mysqli_error($conn);
            }
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>