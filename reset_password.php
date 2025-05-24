<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$success = '';

// Check if reset token is set in session
if (!isset($_SESSION['reset_token'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && isset($_POST['confirm_password'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $reset_token = $_SESSION['reset_token'];

        // Validate password
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Verify reset token
            $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
            if ($stmt === false) {
                $error = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param("s", $reset_token);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();

                    // Hash new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Update password and clear reset token
                    $updateStmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL, otp = NULL, otp_expiry = NULL WHERE id = ?");
                    if ($updateStmt === false) {
                        $error = "Database error: " . $conn->error;
                    } else {
                        $updateStmt->bind_param("si", $hashed_password, $user['id']);

                        if ($updateStmt->execute()) {
                            // Clear session
                            unset($_SESSION['reset_token']);
                            unset($_SESSION['reset_email']);

                            $success = "Password has been reset successfully. You can now login with your new password.";
                        } else {
                            $error = "Failed to update password. Please try again.";
                        }
                    }
                } else {
                    $error = "Invalid or expired reset token. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Printing Press</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .reset-password-container {
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
        <div class="reset-password-container">
            <!-- <div class="logo">
                <img src="assets/images/logo.png" alt="Printing Press Logo">
            </div> -->
            <h2 class="text-center mb-4">Reset Password</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <div class="mt-3">
                        <a href="pages/login.php" class="btn btn-primary">Go to Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="text-muted">Password must be at least 8 characters long</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            <?php endif; ?>

            <div class="back-to-login">
                <a href="pages/login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>