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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 99999;
        }

        .modal.show {
            opacity: 1;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            z-index: 100000;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-content h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #2d3748;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 1.5rem;
        }

        .modal-content p {
            margin: 12px 0;
            color: #4a5568;
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
        }

        .modal-content strong {
            color: #2d3748;
            font-weight: 500;
            margin-right: 8px;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            color: #a0aec0;
            cursor: pointer;
            transition: color 0.2s ease, transform 0.2s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover {
            color: #e53e3e;
            transform: rotate(90deg);
        }

        /* Prevent scrolling when modal is open */
        body.modal-open {
            overflow: hidden;
        }
    </style>
</head>

<body>

    <?php include('../includes/header.php'); ?>

    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>

        <div class="container">
            <!-- <h2>Admin Setup</h2> -->

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
            const modal = document.getElementById('adminModal');
            document.body.classList.add('modal-open');
            modal.style.display = 'flex';
            // Trigger reflow to ensure transition works
            modal.offsetHeight;
            modal.classList.add('show');
        }

        function closeModal() {
            const modal = document.getElementById('adminModal');
            document.body.classList.remove('modal-open');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300); // Match the transition duration
        }

        // Close modal when clicking outside
        document.getElementById('adminModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });

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
                });
        }
    </script>

</body>

</html>