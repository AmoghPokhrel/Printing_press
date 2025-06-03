<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get the order ID from the request
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    die("Invalid order ID");
}

// Fetch order details with all items
$stmt = $conn->prepare("
    SELECT 
        o.*,
        u.name as customer_name,
        u.email as customer_email,
        u.address as customer_address,
        GROUP_CONCAT(DISTINCT p.transaction_id) as transaction_ids,
        MAX(p.payment_date) as payment_date,
        SUM(oil.total_price) as total_amount
    FROM `order` o
    JOIN users u ON o.uid = u.id
    JOIN order_item_line oil ON o.id = oil.oid
    JOIN payments p ON oil.id = p.order_item_id
    WHERE o.id = ? AND p.status = 'completed'
    GROUP BY o.id, o.order_date, o.uid, u.name, u.email, u.address
");

$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found or payment not completed");
}

// Fetch all items in the order
$items_stmt = $conn->prepare("
    SELECT oil.*, p.status as payment_status
    FROM order_item_line oil
    JOIN payments p ON oil.id = p.order_item_id
    WHERE oil.oid = ? AND p.status = 'completed'
");

$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="bill_' . $order_id . '.pdf"');

// Include TCPDF library
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Printing Press');
$pdf->SetAuthor('Your Company Name');
$pdf->SetTitle('Bill #' . $order_id);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Company Logo and Details
$pdf->Image('../assets/images/logo.png', 10, 10, 30); // Adjust path and dimensions
$pdf->SetXY(50, 10);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Printing Press', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(50, 20);
$pdf->Cell(0, 5, 'Your Company Address', 0, 1, 'L');
$pdf->Cell(0, 5, 'Phone: +123 456 7890', 0, 1, 'L');
$pdf->Cell(0, 5, 'Email: info@printingpress.com', 0, 1, 'L');

// Bill Details
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'BILL', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);

// Customer Details
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Bill To:', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, $order['customer_name'], 0, 1);
$pdf->Cell(0, 5, $order['customer_email'], 0, 1);
$pdf->MultiCell(0, 5, $order['customer_address'], 0, 'L');

// Bill Details Table
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(50, 7, 'Bill Number', 1, 0, 'L', true);
$pdf->Cell(0, 7, '#' . $order_id, 1, 1, 'L');
$pdf->Cell(50, 7, 'Date', 1, 0, 'L', true);
$pdf->Cell(0, 7, date('d/m/Y', strtotime($order['payment_date'])), 1, 1, 'L');
$pdf->Cell(50, 7, 'Transaction ID(s)', 1, 0, 'L', true);
$pdf->Cell(0, 7, $order['transaction_ids'], 1, 1, 'L');

// Order Details
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'Order Details', 0, 1);
$pdf->SetFont('helvetica', 'B', 10);

// Table Header
$pdf->Cell(90, 7, 'Description', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Quantity', 1, 0, 'C', true);
$pdf->Cell(35, 7, 'Unit Price', 1, 0, 'C', true);
$pdf->Cell(35, 7, 'Total', 1, 1, 'C', true);

// Table Content
$pdf->SetFont('helvetica', '', 10);
$total = 0;
foreach ($items as $item) {
    $pdf->Cell(90, 7, $item['template_name'], 1, 0, 'L');
    $pdf->Cell(30, 7, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(35, 7, number_format($item['unit_price'], 2), 1, 0, 'R');
    $pdf->Cell(35, 7, number_format($item['total_price'], 2), 1, 1, 'R');
    $total += $item['total_price'];
}

// Total
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(155, 7, 'Total Amount:', 0, 0, 'R');
$pdf->Cell(35, 7, 'Rs. ' . number_format($total, 2), 0, 1, 'R');

// Payment Details
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Payment Information', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(50, 7, 'Payment Method:', 0, 0);
$pdf->Cell(0, 7, 'eSewa', 0, 1);
$pdf->Cell(50, 7, 'Payment Status:', 0, 0);
$pdf->Cell(0, 7, 'Completed', 0, 1);
$pdf->Cell(50, 7, 'Payment Date:', 0, 0);
$pdf->Cell(0, 7, date('d/m/Y H:i', strtotime($order['payment_date'])), 0, 1);

// Footer Note
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'I', 9);
$pdf->MultiCell(0, 5, 'Thank you for your order', 0, 'C');

// Output PDF
$pdf->Output('bill_' . $order_id . '.pdf', 'D');