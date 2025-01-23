<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
        include('../includes/header.php');
    ?>
    <div class="main-content">
        <header>
            <div class="menu-icon">
                <span>&#9776;</span> Dashboard
            </div>
            <div class="profile">
                <img src="https://via.placeholder.com/40" alt="User">
                <div>
                    <strong>Sasikant Karki</strong>
                    <br><small>Manager</small>
                </div>
            </div>
        </header>
        <div class="content">
            <div class="card"></div>
            <div class="card"></div>
            <div class="card"></div>
            <div class="card"></div>
            <div class="card extra large"></div>
            <div class="card large"></div>
        </div>
    </div>
    <?php
        include('../includes/footer.php');
    ?>
</body>
</html>
