<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

        // Check if email exists
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Generate OTP
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Update user with OTP
            $updateStmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $otp, $otp_expiry, $user['id']);

            if ($updateStmt->execute()) {
                // Create a new PHPMailer instance
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'amogh0012@gmail.com'; // Your Gmail address
                    $mail->Password = 'copp fohl woyr ztsv'; // Replace with your 16-digit App Password (remove spaces)
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('your-gmail@gmail.com', 'Printing Press'); // Replace with your Gmail
                    $mail->addAddress($email, $user['name']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset OTP';
                    $mail->Body = "
                        <h2>Password Reset Request</h2>
                        <p>Dear {$user['name']},</p>
                        <p>Your OTP for password reset is: <strong>{$otp}</strong></p>
                        <p>This OTP will expire in 15 minutes.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <br>
                        <p>Best regards,<br>Printing Press Team</p>
                    ";

                    $mail->send();
                    $_SESSION['reset_email'] = $email;
                    header("Location: verify_otp.php");
                    exit();
                } catch (Exception $e) {
                    $error = "Failed to send OTP. Please try again. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $error = "Something went wrong. Please try again.";
            }
        } else {
            $error = "Email not found in our records.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Printing Press</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .forgot-password-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            max-width: 150px;
        }

        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
        }

        .btn-primary {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            background: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="forgot-password-container">
            <!-- <div class="logo">
                <img src="assets/images/logo.png" alt="Printing Press Logo">
            </div> -->
            <h2 class="text-center mb-4">Forgot Password</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary">Send OTP</button>
            </form>

            <div class="back-to-login">
                <a href="pages/login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>