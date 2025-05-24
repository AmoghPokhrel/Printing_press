<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$success = '';

// Check if email is set in session
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['otp'])) {
        $otp = filter_var($_POST['otp'], FILTER_SANITIZE_STRING);
        $email = $_SESSION['reset_email'];

        // Debug information
        error_log("Attempting OTP verification for email: " . $email);
        error_log("Entered OTP: " . $otp);

        // Verify OTP
        $stmt = $conn->prepare("SELECT id, otp, otp_expiry FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Debug information
            error_log("Stored OTP: " . $user['otp']);
            error_log("OTP Expiry: " . $user['otp_expiry']);

            // Check if OTP matches and is not expired
            if ($user['otp'] === $otp && strtotime($user['otp_expiry']) > time()) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $reset_token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Update user with reset token
                $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                $updateStmt->bind_param("ssi", $reset_token, $reset_token_expiry, $user['id']);

                if ($updateStmt->execute()) {
                    $_SESSION['reset_token'] = $reset_token;
                    header("Location: reset_password.php");
                    exit();
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            } else {
                if ($user['otp'] !== $otp) {
                    $error = "Invalid OTP. Please check and try again.";
                } else {
                    $error = "OTP has expired. Please request a new one.";
                }
            }
        } else {
            $error = "User not found. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Printing Press</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .verify-otp-container {
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
            text-align: center;
            letter-spacing: 8px;
            font-size: 20px;
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
        <div class="verify-otp-container">
            <!-- <div class="logo">
                <img src="assets/images/logo.png" alt="Printing Press Logo">
            </div> -->
            <h2 class="text-center mb-4">Verify OTP</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="otp" class="form-label">Enter OTP</label>
                    <input type="text" class="form-control" id="otp" name="otp" maxlength="6" required>
                    <small class="text-muted">Enter the 6-digit OTP sent to your email</small>
                </div>
                <button type="submit" class="btn btn-primary">Verify OTP</button>
            </form>

            <div class="back-to-login">
                <a href="pages/login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-format OTP input
        document.getElementById('otp').addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>

</html>