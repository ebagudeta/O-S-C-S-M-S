<?php
// Start session
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit;
}

// Database connection parameters for XAMPP
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (blank)

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get student ID
    $stmt = $pdo->prepare("SELECT s.student_id, s.student_number 
                          FROM students s 
                          WHERE s.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception("Student record not found");
    }
    
    $student_id = $student['student_id'];
    
    // Get all invoices
    $sql = "SELECT i.invoice_id, i.academic_year, i.semester, i.total_amount, 
           i.cost_share_amount, i.student_responsibility, i.issue_date, i.due_date, i.status,
           (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.invoice_id = i.invoice_id) AS paid_amount
           FROM invoices i
           WHERE i.student_id = ?
           ORDER BY i.issue_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get detailed invoice if requested
    $selected_invoice = null;
    $invoice_items = [];
    $invoice_payments = [];
    
    if (isset($_GET['invoice_id'])) {
        $invoice_id = $_GET['invoice_id'];
        
        // Get invoice details
        $sql = "SELECT i.*, 
               (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.invoice_id = i.invoice_id) AS paid_amount
               FROM invoices i
               WHERE i.invoice_id = ? AND i.student_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$invoice_id, $student_id]);
        $selected_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selected_invoice) {
            // Get invoice items
            $sql = "SELECT ii.*, c.course_code, c.course_name, cc.category_name
                   FROM invoice_items ii
                   LEFT JOIN courses c ON ii.course_id = c.course_id
                   LEFT JOIN cost_categories cc ON ii.cost_category_id = cc.category_id
                   WHERE ii.invoice_id = ?
                   ORDER BY ii.item_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$invoice_id]);
            $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get payments for this invoice
            $sql = "SELECT p.*
                   FROM payments p
                   WHERE p.invoice_id = ?
                   ORDER BY p.payment_date";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$invoice_id]);
            $invoice_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Calculate financial summary
    $total_billed = 0;
    $total_cost_share = 0;
    $total_responsibility = 0;
    $total_paid = 0;
    $total_balance = 0;
    
    foreach ($invoices as $invoice) {
        $total_billed += $invoice['total_amount'];
        $total_cost_share += $invoice['cost_share_amount'];
        $total_responsibility += $invoice['student_responsibility'];
        $total_paid += $invoice['paid_amount'];
    }
    
    $total_balance = $total_responsibility - $total_paid;
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - View Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .header {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            font-size: 24px;
        }
        .logo i {
            color: #5D5CDE;
            margin-right: 10px;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .role-badge {
            background-color: #5D5CDE;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 10px;
            font-size: 14px;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .page-title {
            color: #5D5CDE;
            margin-bottom: 20px;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .section-title {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .summary-item {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .summary-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .balance-due {
            color: #c62828;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .view-btn {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        .view-btn:hover {
            background-color: #4A49B0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .issued {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        .partially-paid {
            background-color: #fff3e0;
            color: #e65100;
        }
        .overdue {
            background-color: #ffebee;
            color: #c62828;
        }
        .paid {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #5D5CDE;
        }
        .back-link i {
            margin-right: 5px;
        }
        .no-data {
            color: #757575;
            font-style: italic;
            padding: 15px 0;
        }
        .invoice-detail {
            margin-top: 20px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
        }
        .pay-now-btn {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
        }
        .pay-now-btn i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            OCSMS
        </a>
        <div class="user-info">
            <span class="role-badge">Student</span>
            <span><?php echo $_SESSION['name']; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">View Payment</h1>
        
        <?php if (isset($error)): ?>
            <div class="card">
                <p style="color: #f44336;"><?php echo $error; ?></p>
            </div>
        <?php else: ?>
            <div class="card">
                <h2 class="section-title">Financial Summary</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Total Billed</div>
                        <div class="summary-value">$<?php echo number_format($total_billed, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Cost Share Amount</div>
                        <div class="summary-value">$<?php echo number_format($total_cost_share, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Your Responsibility</div>
                        <div class="summary-value">$<?php echo number_format($total_responsibility, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Amount Paid</div>
                        <div class="summary-value">$<?php echo number_format($total_paid, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Balance Due</div>
                        <div class="summary-value balance-due">$<?php echo number_format($total_balance, 2); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2 class="section-title">Invoice History</h2>
                <?php if (count($invoices) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Term</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Total Amount</th>
                                <th>Your Responsibility</th>
                                <th>Amount Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <?php 
                                    $balance = $invoice['student_responsibility'] - $invoice['paid_amount'];
                                    $status_class = strtolower(str_replace(' ', '-', $invoice['status']));
                                ?>
                                <tr>
                                    <td><?php echo $invoice['semester'] . ' ' . $invoice['academic_year']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                    <td>$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                    <td>$<?php echo number_format($invoice['student_responsibility'], 2); ?></td>
                                    <td>$<?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                    <td>$<?php echo number_format($balance, 2); ?></td>
                                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $invoice['status']; ?></span></td>
                                    <td>
                                        <a href="?invoice_id=<?php echo $invoice['invoice_id']; ?>" class="view-btn">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No invoice history found.</p>
                <?php endif; ?>
            </div>
            
            <?php if ($selected_invoice): ?>
                <div class="card invoice-detail">
                    <div class="invoice-header">
                        <div class="invoice-title">
                            Invoice Detail: <?php echo $selected_invoice['semester'] . ' ' . $selected_invoice['academic_year']; ?>
                        </div>
                        <?php if ($selected_invoice['status'] != 'Paid' && ($selected_invoice['student_responsibility'] - $selected_invoice['paid_amount']) > 0): ?>
                            <a href="make_payment.php" class="pay-now-btn">
                                <i class="fas fa-credit-card"></i> Pay Now
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <h3>Invoice Items</h3>
                    <?php if (count($invoice_items) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Course</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoice_items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['description']; ?></td>
                                        <td>
                                            <?php 
                                                if ($item['course_id']) {
                                                    echo $item['course_code'] . ': ' . $item['course_name'];
                                                } else {
                                                    echo 'N/A';
                                                }
                                            ?>
                                        </td>
                                        <td><?php echo $item['category_name'] ?? 'N/A'; ?></td>
                                        <td>$<?php echo number_format($item['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background-color: #f5f5f5; font-weight: bold;">
                                    <td colspan="3" style="text-align: right;">Total Amount:</td>
                                    <td>$<?php echo number_format($selected_invoice['total_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="text-align: right;">Cost Share Amount:</td>
                                    <td>$<?php echo number_format($selected_invoice['cost_share_amount'], 2); ?></td>
                                </tr>
                                <tr style="font-weight: bold;">
                                    <td colspan="3" style="text-align: right;">Your Responsibility:</td>
                                    <td>$<?php echo number_format($selected_invoice['student_responsibility'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No invoice items found.</p>
                    <?php endif; ?>
                    
                    <h3>Payments</h3>
                    <?php if (count($invoice_payments) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoice_payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo $payment['payment_method']; ?></td>
                                        <td><?php echo $payment['reference_number']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background-color: #f5f5f5; font-weight: bold;">
                                    <td colspan="3" style="text-align: right;">Total Paid:</td>
                                    <td>$<?php echo number_format($selected_invoice['paid_amount'], 2); ?></td>
                                </tr>
                                <tr style="font-weight: bold;">
                                    <td colspan="3" style="text-align: right;">Balance Due:</td>
                                    <td>$<?php echo number_format($selected_invoice['student_responsibility'] - $selected_invoice['paid_amount'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No payments have been made for this invoice.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>