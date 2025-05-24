<?php
session_start();

// Default page title
$pageTitle = 'Designer';

include '../includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    echo '<script>alert("You need administrator privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

// Get user role and ID
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Set page title based on role
if ($user_role === 'Admin') {
    $pageTitle = 'Staff Setup';
} elseif ($user_role === 'Staff') {
    $pageTitle = 'Staff Details';
} elseif ($user_role === 'Customer') {
    $pageTitle = 'Select Designer';
}

// Handle designer selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_id'])) {
    $staff_id = intval($_POST['staff_id']);

    // If there's no request_id, just redirect to custom_template.php with the staff_id
    if (!isset($_POST['request_id'])) {
        header("Location: custom_template.php?staff_id=" . $staff_id);
        exit();
    }

    $request_id = intval($_POST['request_id']);

    // Update the custom_template_requests table
    $update_query = "UPDATE custom_template_requests 
                    SET preferred_staff_id = ? 
                    WHERE id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "iii", $staff_id, $request_id, $_SESSION['user_id']);

    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Preferred staff member has been updated successfully!";
        // Redirect to custom_template.php with the staff ID
        header("Location: custom_template.php?staff_id=" . $staff_id);
        exit();
    } else {
        $error_message = "Error updating preferred staff: " . mysqli_error($conn);
    }
}

// Update the query to fetch request details
if (isset($_GET['request_id'])) {
    $request_id = intval($_GET['request_id']);
    $request_query = "SELECT ctr.*, c.c_Name as category_name, mt.name as media_type_name 
                     FROM custom_template_requests ctr
                     JOIN category c ON ctr.category_id = c.c_id
                     JOIN media_type mt ON ctr.media_type_id = mt.id
                     WHERE ctr.id = ? AND ctr.user_id = ?";

    $stmt = mysqli_prepare($conn, $request_query);
    mysqli_stmt_bind_param($stmt, "ii", $request_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $request_result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($request_result);
}

// Fetch available staff members based on their current workload
if ($user_role === 'Customer') {
    // Get staff with less than 2 pending/in-progress requests and only active staff
    $query = "SELECT u.*, s.id as staff_id, s.availability 
              FROM users u
              JOIN staff s ON u.id = s.user_id
              WHERE s.availability = 'active'
              AND (
                  SELECT COUNT(*) 
                  FROM custom_template_requests ctr 
                  WHERE ctr.assigned_staff_id = s.id 
                  AND ctr.status IN ('Pending', 'In Progress')
              ) < 2
              ORDER BY u.name ASC";
} else {
    // Admin/Staff see all staff members (regardless of current role)
    $query = "SELECT u.*, s.id as staff_id, s.availability, s.admin_id, a.user_id as admin_user_id, au.name as admin_name
              FROM users u
              INNER JOIN staff s ON u.id = s.user_id
              LEFT JOIN admin a ON s.admin_id = a.id
              LEFT JOIN users au ON a.user_id = au.id
              ORDER BY u.name ASC";
}

$result = mysqli_query($conn, $query);

// Fetch all staff into an array for pagination
$staff_list = [];
while ($row = mysqli_fetch_assoc($result)) {
    $staff_list[] = $row;
}

// Custom pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = ($page == 1) ? 5 : 6;
$total_staff = count($staff_list);
// Calculate total pages: 1st page has 5, rest have 6 each
if ($total_staff <= 5) {
    $total_pages = 1;
} else {
    $total_pages = 1 + ceil(($total_staff - 5) / 6);
}
if ($page == 1) {
    $start = 0;
    $length = 5;
} else {
    $start = 5 + ($page - 2) * 6;
    $length = 6;
}
$paginated_staff = array_slice($staff_list, $start, $length);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 50%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        /* Form styles */
        .confirmation-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-actions {
            margin-top: 20px;
            text-align: right;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-confirm {
            background-color: #2ecc71;
            color: white;
        }

        .btn-cancel {
            background-color: #e74c3c;
            color: white;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Add these styles to your existing CSS */
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
        }

        .status-active {
            background-color: #4CAF50;
        }

        .status-inactive {
            background-color: #f44336;
        }

        .toggle-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .toggle-btn.active {
            background-color: #f44336;
            color: white;
        }

        .toggle-btn.inactive {
            background-color: #4CAF50;
            color: white;
        }

        .name-content {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        td,
        th {
            vertical-align: middle;
        }

        .action-buttons>* {
            margin-right: 5px;
            margin-bottom: 3px;
        }

        /* Arrange Professional Details buttons */
        .prof-details-btns {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Enhance Action column width */
        table th:last-child,
        table td:last-child {
            width: 220px;
            min-width: 180px;
            max-width: 300px;
        }
    </style>
</head>

<body>

    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="container">
            <h2><?php echo $pageTitle; ?></h2>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error_message']; ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Add Staff Button (Only visible to Admin) -->
            <?php if ($user_role === 'Admin' && $page == 1): ?>
                <a href="s_personal_register.php" class="btn-primary">+ Add Staff</a>
            <?php endif; ?>

            <!-- Staff Table -->
            <div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Date of Birth</th>
                            <th>Staff Role</th>
                            <th>Professional Details</th>
                            <?php if ($user_role === 'Admin' || $user_role === 'Customer'): ?>
                                <th>Action</th>
                            <?php endif; ?>
                            <?php if ($user_role === 'Super Admin'): ?>
                                <th>Registered By</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginated_staff as $row): ?>
                            <tr>
                                <td>
                                    <span class="name-content">
                                        <?= htmlspecialchars($row['name']) ?>
                                        <span
                                            class="status-indicator <?= $row['availability'] === 'active' ? 'status-active' : 'status-inactive' ?>"></span>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><?= htmlspecialchars($row['DOB']) ?></td>
                                <td><?= htmlspecialchars($row['staff_role']) ?></td>
                                <td class="prof-details-btns">
                                    <a href="../modules/view_professional_details.php?id=<?= $row['id']; ?>"
                                        class="view-details-btn">View</a>
                                    <?php if ($user_role !== 'Super Admin'): ?>
                                        <a href="s_professional_register.php?id=<?= $row['id']; ?>"
                                            class="add-details-btn">Add</a>
                                        <a href="../modules/edit_professional_details.php?id=<?= $row['id']; ?>"
                                            class="edit-details-btn">Edit</a>
                                    <?php endif; ?>
                                </td>
                                <?php if ($user_role === 'Admin' || $user_role === 'Customer'): ?>
                                    <td class="action-buttons">
                                        <?php if ($user_role === 'Admin'): ?>
                                            <button
                                                class="toggle-btn <?= $row['availability'] === 'active' ? 'active' : 'inactive' ?>"
                                                onclick="toggleStatus(<?= $row['staff_id'] ?>, '<?= $row['availability'] ?>')">
                                                <?= $row['availability'] === 'active' ? 'Inactive' : 'Active' ?>
                                            </button>
                                            <button class="view-btn"
                                                onclick="viewStaff('<?= $row['name'] ?>', '<?= $row['email'] ?>', '<?= $row['phone'] ?>', '<?= $row['address'] ?>', '<?= $row['DOB'] ?>', '<?= $row['staff_role'] ?>')">View</button>
                                            <a href="staff_edit.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                                            <a href="../modules/staff_delete.php?id=<?= $row['id'] ?>" class="delete-btn"
                                                onclick="return confirm('Are you sure you want to delete this Staff?');">Delete</a>
                                        <?php elseif ($user_role === 'Customer'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="staff_id" value="<?= $row['staff_id'] ?>">
                                                <button type="submit" class="select-btn">Select</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <?php if ($user_role === 'Super Admin'): ?>
                                    <td><?= htmlspecialchars($row['admin_name'] ?? 'Unknown') ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-controls"
                    style="display: flex; justify-content: center; align-items: center; gap: 10px; margin: 20px 0;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="btn btn-info">Previous</a>
                    <?php endif; ?>
                    <span style="font-weight: 500;">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="btn btn-info">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Staff View Modal -->
    <div id="staffModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('staffModal')">&times;</span>
            <h3>Staff Details</h3>
            <p><strong>Name:</strong> <span id="staffName"></span></p>
            <p><strong>Email:</strong> <span id="staffEmail"></span></p>
            <p><strong>Phone:</strong> <span id="staffPhone"></span></p>
            <p><strong>Address:</strong> <span id="staffAddress"></span></p>
            <p><strong>Date of Birth:</strong> <span id="staffDOB"></span></p>
            <p><strong>Staff Role:</strong> <span id="staffRole"></span></p>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('confirmationModal')">&times;</span>
            <h3>Confirm Designer Selection</h3>
            <p>Are you sure you want to select this designer?</p>
            <form class="confirmation-form" action="" method="POST">
                <input type="hidden" name="staff_id" id="staff_id" value="">
                <div class="form-actions">
                    <button type="submit" class="btn btn-confirm">Confirm</button>
                    <button type="button" class="btn btn-cancel"
                        onclick="closeModal('confirmationModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to display confirmation modal
        function showConfirmationModal(staffId, staffName) {
            // Create a form element
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            // Add staff_id input
            const staffInput = document.createElement('input');
            staffInput.type = 'hidden';
            staffInput.name = 'staff_id';
            staffInput.value = staffId;
            form.appendChild(staffInput);

            // Add the form to the document and submit it
            document.body.appendChild(form);
            form.submit();
        }

        // Function to close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Function to view staff details in modal
        function viewStaff(name, email, phone, address, dob, staffRole) {
            document.getElementById('staffName').innerText = name;
            document.getElementById('staffEmail').innerText = email;
            document.getElementById('staffPhone').innerText = phone;
            document.getElementById('staffAddress').innerText = address;
            document.getElementById('staffDOB').innerText = dob;
            document.getElementById('staffRole').innerText = staffRole;
            document.getElementById('staffModal').style.display = 'block';
        }

        // Add this function to your existing JavaScript
        function toggleStatus(staffId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            fetch('../modules/toggle_staff_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `staff_id=${staffId}&status=${newStatus}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status. Please try again.');
                });
        }
    </script>

    <?php include('../includes/footer.php'); ?>
</body>

</html>