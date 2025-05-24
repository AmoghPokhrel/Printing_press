<?php
session_start();
$pageTitle = 'Admin Setup';
include '../includes/db.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
    echo '<script>alert("You need administrator privileges to access this page"); window.location.href = "login.php";</script>';
    exit();
}

// Fetch admins from the users table, including DOB and availability
$query = "SELECT u.*, a.availability FROM users u 
          INNER JOIN admin a ON u.id = a.user_id";
$result = mysqli_query($conn, $query);

// Fetch all rows into an array for pagination
$admins = [];
while ($row = mysqli_fetch_assoc($result)) {
    $admins[] = $row;
}

// Pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 8;
$total_admins = count($admins);
$total_pages = ceil($total_admins / $per_page);
$start = ($page - 1) * $per_page;
$paginated_admins = array_slice($admins, $start, $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <style>
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-active {
            background-color: #2ecc71;
        }

        .status-inactive {
            background-color: #e74c3c;
        }

        .toggle-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .toggle-btn.active {
            background-color: #e74c3c;
            color: white;
        }

        .toggle-btn.inactive {
            background-color: #2ecc71;
            color: white;
        }
    </style>
</head>

<body>

    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="container">
            <h2>Admin Setup</h2>

            <!-- Add Admin Button - Only for Super Admin -->
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                <a href="a_register.php" class="btn-primary">+ Add Admin</a>
            <?php endif; ?>

            <!-- Admin Table -->
            <div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Date of Birth</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginated_admins as $row): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($row['name']) ?>
                                    <span
                                        class="status-indicator <?= $row['availability'] === 'active' ? 'status-active' : 'status-inactive' ?>"></span>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><?= htmlspecialchars($row['DOB']) ?></td>
                                <td class="action-buttons">
                                    <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                                        <button
                                            class="toggle-btn <?= $row['availability'] === 'active' ? 'active' : 'inactive' ?>"
                                            onclick="toggleStatus(<?= $row['id'] ?>, '<?= $row['availability'] ?>')">
                                            <?= $row['availability'] === 'active' ? 'Inactive' : 'Active' ?>
                                        </button>
                                    <?php endif; ?>
                                    <button class="view-btn"
                                        onclick="viewAdmin('<?= $row['name'] ?>', '<?= $row['email'] ?>', '<?= $row['phone'] ?>', '<?= $row['address'] ?>', '<?= $row['DOB'] ?>')">View</button>
                                    <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                                        <a href="admin_edit.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                                        <a href="../modules/admin_delete.php?id=<?= $row['id'] ?>" class="delete-btn"
                                            onclick="return confirm('Are you sure you want to delete this admin?');">Delete</a>
                                    <?php endif; ?>
                                </td>
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

    <!-- Admin View Modal -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Admin Details</h3>
            <p><strong>Name:</strong> <span id="adminName"></span></p>
            <p><strong>Email:</strong> <span id="adminEmail"></span></p>
            <p><strong>Phone:</strong> <span id="adminPhone"></span></p>
            <p><strong>Address:</strong> <span id="adminAddress"></span></p>
            <p><strong>Date of Birth:</strong> <span id="adminDOB"></span></p>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

    <script>
        function viewAdmin(name, email, phone, address, dob) {
            document.getElementById('adminName').innerText = name;
            document.getElementById('adminEmail').innerText = email;
            document.getElementById('adminPhone').innerText = phone;
            document.getElementById('adminAddress').innerText = address;
            document.getElementById('adminDOB').innerText = dob;
            document.getElementById('adminModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('adminModal').style.display = 'none';
        }

        function toggleStatus(adminId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

            fetch('../modules/toggle_admin_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `admin_id=${adminId}&status=${newStatus}`
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

        window.onclick = function (event) {
            let modal = document.getElementById('adminModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>

</body>

</html>