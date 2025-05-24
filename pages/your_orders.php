<?php
session_start();
$pageTitle = 'Your Orders';

include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all orders for the user
$query = "SELECT * FROM `order` WHERE uid = ? ORDER BY order_date DESC, id DESC";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all items for these orders
$order_items = [];
if (!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $in = str_repeat('?,', count($order_ids) - 1) . '?';
    $types = str_repeat('i', count($order_ids));
    $item_query = "SELECT * FROM order_item_line WHERE oid IN ($in) ORDER BY oid DESC, id ASC";
    $item_stmt = $conn->prepare($item_query);
    $item_stmt->bind_param($types, ...$order_ids);
    $item_stmt->execute();
    $items = $item_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($items as $item) {
        $order_items[$item['oid']][] = $item;
    }
}

// Pagination logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 3;
$total_orders = count($orders);
$total_pages = ceil($total_orders / $per_page);
$start = ($page - 1) * $per_page;
$paginated_orders = array_slice($orders, $start, $per_page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/registers.css">
    <style>
        .orders-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 18px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.10);
        }

        .order-header {
            padding: 10px 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-id {
            font-weight: 600;
            color: #2c3e50;
        }

        .order-date {
            color: #7f8c8d;
            font-size: 0.95em;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .order-table th,
        .order-table td {
            padding: 5px 6px;
            text-align: left;
        }

        .order-table th {
            background: #f1f3f6;
            color: #333;
            font-size: 0.98em;
        }

        .order-table tr:nth-child(even) {
            background: #fafbfc;
        }

        .order-table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .order-status {
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 0.95em;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 1.2em;
        }

        .btn-info {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-info:hover {
            background-color: #217dbb;
        }
    </style>
</head>

<body>
    <?php include('../includes/header.php'); ?>
    <div class="main-content">
        <?php include('../includes/inner_header.php'); ?>
        <div class="container">
            <h2><?php echo $pageTitle; ?></h2>
            <div class="orders-container">
                <?php if (empty($paginated_orders)): ?>
                    <div class="no-orders">
                        <p>You haven't placed any orders yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($paginated_orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <span class="order-id">Order #<?= $order['id'] ?></span>
                                </div>
                                <span class="order-date">Order Date:
                                    <?= htmlspecialchars(date('Y-m-d', strtotime($order['order_date']))) ?></span>
                            </div>
                            <div style="padding: 18px;">
                                <table class="order-table">
                                    <thead>
                                        <tr>
                                            <th>Template</th>
                                            <th>Image</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items[$order['id']] ?? [] as $item): ?>
                                            <?php
                                            // Fetch req_type and final_design from cart_item_line for this item
                                            $req_type = '';
                                            $final_design = '';
                                            if (!empty($item['ca_it_id'])) {
                                                $cart_item_stmt = $conn->prepare("SELECT req_type, final_design FROM cart_item_line WHERE id = ? LIMIT 1");
                                                $cart_item_stmt->bind_param("i", $item['ca_it_id']);
                                                $cart_item_stmt->execute();
                                                $cart_item_result = $cart_item_stmt->get_result();
                                                if ($cart_item = $cart_item_result->fetch_assoc()) {
                                                    $req_type = $cart_item['req_type'];
                                                    $final_design = $cart_item['final_design'];
                                                }
                                                $cart_item_stmt->close();
                                            }
                                            $image = '';
                                            if (!empty($req_type)) {
                                                if ($req_type === 'modify') {
                                                    $image = '/printing_press/uploads/template_designs/' . $final_design;
                                                } elseif ($req_type === 'custom') {
                                                    $image = '/printing_press/uploads/custom_templates/' . $final_design;
                                                }
                                            } else {
                                                if (!empty($item['template_image'])) {
                                                    $image = '/printing_press/uploads/template_images/' . $item['template_image'];
                                                }
                                            }
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['template_name']) ?></td>
                                                <td>
                                                    <?php if (!empty($image)): ?>
                                                        <img src="<?= htmlspecialchars($image) ?>" alt="Template Image"
                                                            style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #eee;cursor:pointer;"
                                                            onclick="showOrderImageModal(this.src, this.alt)">
                                                    <?php else: ?>
                                                        <div class="no-image-placeholder"><i class="fas fa-image"></i></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td>Rs <?= number_format($item['unit_price'], 2) ?></td>
                                                <td>Rs <?= number_format($item['total_price'], 2) ?></td>
                                                <td>
                                                    <span class="order-status status-<?= htmlspecialchars($item['status']) ?>">
                                                        <?= htmlspecialchars(ucfirst($item['status'])) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div
                        style="text-align:center; margin: 20px 0; display: flex; justify-content: center; align-items: center; gap: 10px;">
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
    </div>
    <?php include('../includes/footer.php'); ?>
    <!-- Add this before </body> -->
    <div id="orderImageModal"
        style="display:none;position:fixed;z-index:1001;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);backdrop-filter:blur(3px);align-items:center;justify-content:center;">
        <span id="orderImageModalClose"
            style="position:absolute;top:20px;right:40px;font-size:40px;color:#fff;cursor:pointer;font-weight:bold;">&times;</span>
        <img id="orderImageModalImg" src="" alt=""
            style="max-width:90vw;max-height:80vh;display:block;margin:auto;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.4);">
        <script>
            function showOrderImageModal(src, alt) {
                var modal = document.getElementById('orderImageModal');
                var modalImg = document.getElementById('orderImageModalImg');
                var caption = document.getElementById('orderImageModalCaption');
                modal.style.display = 'flex';
                modalImg.src = src;
                caption.textContent = alt;
            }
            document.getElementById('orderImageModalClose').onclick = function () {
                document.getElementById('orderImageModal').style.display = 'none';
            };
            document.getElementById('orderImageModal').onclick = function (e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            };
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    document.getElementById('orderImageModal').style.display = 'none';
                }
            });
        </script>
</body>

</html>