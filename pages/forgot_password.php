<?php
session_start();
require '../includes/dbcon.php'; 
require '../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['request_otp'])) {
    $email = $_POST['email'];

    $query = $pdo->prepare("SELECT * FROM user WHERE email = :email");
    $query->bindParam(':email', $email);
    $query->execute();
    $user = $query->fetch();

    if ($user) {
        $otp = rand(100000, 999999);

        $updateQuery = $pdo->prepare("UPDATE user SET otp = :otp WHERE email = :email");
        $updateQuery->bindParam(':otp', $otp);
        $updateQuery->bindParam(':email', $email);
        $updateQuery->execute();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com"; 
            $mail->SMTPAuth = true;
            $mail->Username = 'Your Email'; 
            $mail->Password = 'Your SMTP password'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            //Recipients
            $mail->setFrom('no-reply@example.com', 'Booth System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body    = "Your OTP code to reset your password is <b>$otp</b>";

            $mail->send();
            $_SESSION['email'] = $email;
            echo '<script>alert("OTP has been sent to your email.")</script>';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo '<script>alert("Email not found.")</script>';
    }
} elseif (isset($_POST['verify_otp'])) {
    $otp = $_POST['otp'];
    $email = $_SESSION['email'];
    $new_password = $_POST['new_password'];

    if (strlen($new_password) < 8 || !preg_match('/\d/', $new_password)) {
        echo '<script>alert("Password must be at least 8 characters long and contain at least one number.")</script>';
    } else {
        $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $query = $pdo->prepare("SELECT * FROM user WHERE email = :email AND otp = :otp");
        $query->bindParam(':email', $email);
        $query->bindParam(':otp', $otp);
        $query->execute();
        $user = $query->fetch();

        if ($user) {
            $updateQuery = $pdo->prepare("UPDATE user SET password = :password, otp = NULL WHERE email = :email");
            $updateQuery->bindParam(':password', $new_password_hashed);
            $updateQuery->bindParam(':email', $email);
            $updateQuery->execute();

            echo '<script>alert("Password has been reset successfully.")</script>';
            echo '<script>window.location.href = "../index.php";</script>';
        } else {
            echo '<script>alert("Invalid OTP.")</script>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        input[type="email"],
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button[type="submit"] {
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .alert {
            padding: 15px;
            background-color: #f44336;
            color: white;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        @media (max-width: 500px) {
            .container {
                padding: 20px;
            }

            button[type="submit"] {
                font-size: 14px;
            }
        }
    </style>
    <script>
        function validatePassword() {
            var password = document.getElementById("new_password").value;
            var error = "";

            if (password.length < 8) {
                error += "Password must be at least 8 characters long.\n";
            }

            if (!/\d/.test(password)) {
                error += "Password must contain at least one number.\n";
            }

            if (error !== "") {
                alert(error);
                return false;
            }

            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <form method="post">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit" name="request_otp">Request OTP</button>
        </form>

        <form method="post" onsubmit="return validatePassword()">
            <input type="text" name="otp" placeholder="Enter OTP" required>
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
            <button type="submit" name="verify_otp">Verify OTP and Reset Password</button>
        </form>
    </div>
</body>
</html>
