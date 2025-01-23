<?php
session_start();
require '../includes/db.php'; 
require '../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['send_otp'])) {
            // Generate a random OTP
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiration'] = time() + 300; 

            $stmt = $conn->prepare("SELECT email FROM user WHERE name = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($email);
            $stmt->fetch();
            $stmt->close();

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = "smtp.gmail.com"; 
                $mail->SMTPAuth = true;
                $mail->Username = 'amogh0012@gmail.com'; 
                $mail->Password = 'ntyr alnh sono dhin'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@example.com', 'Booth System');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password change OTP';
                $mail->Body    = "Your OTP code to change your password is: <strong>{$otp}</strong>";

                $mail->send();
                echo '<script>alert("OTP sent to your registered email."); window.location.href = "../pages/change_password.php";</script>';
            } catch (Exception $e) {
                echo '<script>alert("OTP could not be sent. Mailer Error: ' . $mail->ErrorInfo . '"); window.location.href = "../pages/change_password.php";</script>';
            }
        } elseif (isset($_POST['verify_otp'])) {
            $user_otp = $_POST['otp'];
            if ($user_otp == $_SESSION['otp'] && time() < $_SESSION['otp_expiration']) {
                $_SESSION['otp_verified'] = true;
                echo '<script>alert("OTP verified. You can now change your password."); window.location.href = "../pages/change_password.php";</script>';
            } else {
                echo '<script>alert("Invalid or expired OTP."); window.location.href = "../pages/change_password.php";</script>';
            }
        } elseif (isset($_POST['change_password']) && isset($_SESSION['otp_verified']) && $_SESSION['otp_verified']) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (strlen($new_password) < 8 || !preg_match('/\d/', $new_password)) {
                echo '<script>alert("New password must be at least 8 characters long and contain at least one number."); window.location.href = "../pages/change_password.php";</script>';
                exit();
            }

            if ($new_password !== $confirm_password) {
                echo '<script>alert("New passwords do not match."); window.location.href = "../pages/change_password.php";</script>';
                exit();
            }

            $stmt = $conn->prepare("SELECT password FROM user WHERE name = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($hashed_password);
            $stmt->fetch();
            $stmt->close();

            if (!password_verify($current_password, $hashed_password)) {
                echo '<script>alert("Current password is incorrect."); window.location.href = "../pages/change_password.php";</script>';
                exit();
            }

            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE user SET password = ? WHERE name = ?");
            $stmt->bind_param("ss", $new_hashed_password, $username);

            if ($stmt->execute()) {
                unset($_SESSION['otp_verified']); 
                echo '<script>alert("Password changed successfully."); window.location.href = "../pages/change_password.php";</script>';
            } else {
                echo '<script>alert("Error updating password."); window.location.href = "../pages/change_password.php";</script>';
            }
            $stmt->close();
            $conn->close();
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <title>Change Password</title>
        <link rel="stylesheet" type="text/css" href="../assets/css/main.css">
        <style>
            .content {
                display: flex;
                justify-content: center;
                align-items: center;
                height: calc(100vh - 100px);
            }

            .form-container {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                width: 300px;
            }

            .form-container h2 {
                margin-bottom: 20px;
            }

            .form-container label {
                display: block;
                margin-bottom: 5px;
            }

            .form-container input {
                width: 100%;
                padding: 8px;
                margin-bottom: 10px;
                border: 1px solid #ccc;
                border-radius: 4px;
            }

            .form-container input[type="submit"] {
                background-color: #4CAF50;
                color: white;
                border: none;
                cursor: pointer;
            }

            .form-container input[type="submit"]:hover {
                background-color: #45a049;
            }
        </style>
    </head>
    <body>
    <div class="sidebar">
        <?php include('../includes/header.php'); ?>
    </div>

    <div class="content">
        <div class="form-container">
            <h2>Change Password</h2>
            <?php if (!isset($_SESSION['otp_verified'])): ?>
                <form action="../pages/change_password.php" method="post">
                    <input type="hidden" name="send_otp" value="1">
                    <input type="submit" value="Send OTP">
                </form>

                <?php if (isset($_SESSION['otp'])): ?>
                    <form action="../pages/change_password.php" method="post">
                        <label for="otp">Enter OTP:</label>
                        <input type="text" id="otp" name="otp" required>
                        <input type="hidden" name="verify_otp" value="1">
                        <input type="submit" value="Verify OTP">
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <form action="../pages/change_password.php" method="post">
                    <label for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" required><br>

                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required><br>

                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required><br>

                    <input type="hidden" name="change_password" value="1">
                    <input type="submit" value="Change Password">
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php include('../includes/footer.php'); ?>
    </body>
    </html>
    <?php
} else {
    echo '<script>alert("You need to log in"); window.location.href = "..//index.php";</script>';
}
?>
