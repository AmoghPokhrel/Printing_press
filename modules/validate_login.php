<?php
session_start();

include('../includes/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if the database is empty
    $checkEmptyQuery = "SELECT COUNT(*) as count FROM user";
    $emptyResult = $conn->query($checkEmptyQuery);
    $emptyRow = $emptyResult->fetch_assoc();

    if ($emptyRow['count'] == 0) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $name;
        $_SESSION['role'] = $role;
        $_SESSION['database_empty'] = true;

        if ($role == "Student") {
            $_SESSION['year'] = 'N/A';
            $_SESSION['faculty'] = 'N/A'; 
        }

        $_SESSION['crn'] = 'N/A'; 

        if ($role == "Admin") {
            header('Location: ../pages/dashboard.php');
        } else {
            header('Location: ../pages/popup.php');
        }
        exit;
    } else {
        $sql = "SELECT password, year, faculty, crn FROM user WHERE name = ? AND role = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("ss", $name, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $hashed_password = $row['password'];

            if (password_verify($password, $hashed_password)) {
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['crn'] = $row['crn'];  

                if ($role == "Student") {
                    $_SESSION['year'] = $row['year'];
                    $_SESSION['faculty'] = $row['faculty'];
                }

                if ($role == "Admin") {
                    header('Location: ../pages/dashboard.php');
                } else {
                    header('Location: ../pages/popup.php');
                }
                exit;
            } else {
                echo '<script>alert("Invalid password."); window.location.href = "../index.php";</script>';
            }
        } else {
            echo '<script>alert("Invalid username or role."); window.location.href = "../index.php";</script>';
        }

        $stmt->close();
    }
}

$conn->close();
?>
