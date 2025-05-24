<?php
session_start();

// Default page title
$pageTitle = 'Profile';

include '../includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Fetch user information
$userQuery = "SELECT name, email, phone, address, role, gender, staff_role, DOB FROM users WHERE id = ?";

try {
    $stmt = $conn->prepare($userQuery);
    if ($stmt === false) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    $userData = $result->fetch_assoc();
    if (!$userData) {
        throw new Exception("User not found");
    }
} catch (Exception $e) {
    error_log("Profile Error: " . $e->getMessage());
    $userData = [
        'name' => 'Error',
        'email' => 'Error',
        'phone' => 'Error',
        'address' => 'Error',
        'role' => 'Error',
        'gender' => 'Error',
        'staff_role' => '',
        'DOB' => 'Error'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            width: 80%;
            max-width: none;
            margin: 2rem auto;
            padding: 2.5rem 2.5rem 2rem 2.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Ensure profile-container takes full width inside .content grid */
        .content .profile-container {
            grid-column: 1 / -1;
            width: 80% !important;
            max-width: none !important;
            margin-left: auto;
            margin-right: auto;
        }

        .profile-header-section {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .profile-avatar-section {
            width: 100px;
            height: 100px;
            background: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
        }

        .profile-title-section {
            flex: 1;
        }

        .profile-title-section h1 {
            margin: 0;
            font-size: 1.8rem;
            color: #1a1a1a;
        }

        .profile-title-section p {
            margin: 0.5rem 0 0;
            color: #666;
            font-size: 1rem;
        }

        .profile-info-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .profile-info-block {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
        }

        .profile-info-block h2 {
            margin: 0 0 1rem;
            font-size: 1.2rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-info-block h2 i {
            color: #3b82f6;
        }

        .profile-info-item {
            margin-bottom: 1rem;
        }

        .profile-info-item:last-child {
            margin-bottom: 0;
        }

        .profile-info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .profile-info-value {
            font-size: 1rem;
            color: #1a1a1a;
            font-weight: 500;
        }

        .edit-profile-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-profile-btn:hover {
            background: #2563eb;
        }

        .profile-error-message {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem;
                padding: 1rem;
            }

            .profile-header-section {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .profile-info-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="container">
            <div class="content">
                <div class="profile-container">
                    <?php if (isset($e)): ?>
                        <div class="profile-error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>There was an error loading your profile. Please try again later.</span>
                        </div>
                    <?php endif; ?>

                    <div class="profile-header-section">
                        <div class="profile-avatar-section">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="profile-title-section">
                            <h1><?php echo htmlspecialchars($userData['name'] ?? 'Not provided'); ?></h1>
                            <p><?php echo ucfirst($userData['role'] ?? ''); ?></p>
                        </div>
                        <button class="edit-profile-btn">
                            <i class="fas fa-edit"></i>
                            Edit Profile
                        </button>
                    </div>

                    <div class="profile-info-section">
                        <div class="profile-info-block">
                            <h2><i class="fas fa-user-circle"></i> Personal Information</h2>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Name</div>
                                <div class="profile-info-value">
                                    <?php echo htmlspecialchars($userData['name'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Email</div>
                                <div class="profile-info-value">
                                    <?php echo htmlspecialchars($userData['email'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Phone</div>
                                <div class="profile-info-value">
                                    <?php echo htmlspecialchars($userData['phone'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Gender</div>
                                <div class="profile-info-value">
                                    <?php echo htmlspecialchars($userData['gender'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Date of Birth</div>
                                <div class="profile-info-value">
                                    <?php echo htmlspecialchars($userData['DOB'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Role</div>
                                <div class="profile-info-value">
                                    <?php echo htmlspecialchars($userData['role'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                            <?php if (!empty($userData['staff_role'])): ?>
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Staff Role</div>
                                    <div class="profile-info-value"><?php echo htmlspecialchars($userData['staff_role']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="profile-info-block">
                            <h2><i class="fas fa-map-marker-alt"></i> Address Information</h2>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Address</div>
                                <div class="profile-info-value">
                                    <?php echo htmlspecialchars($userData['address'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Add click handler for edit profile button
        document.querySelector('.edit-profile-btn').addEventListener('click', function () {
            // TODO: Implement edit profile functionality
            alert('Edit profile functionality coming soon!');
        });
    </script>
</body>

</html>