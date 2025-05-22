<?php
// Start session
session_start();

// Check if user is logged in and is a finance officer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'finance_officer') {
    header("Location: index.php");
    exit;
}

// Database connection parameters for XAMPP
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (blank)

// Initialize variables
$message = '';
$message_type = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$active_sub_tab = isset($_GET['sub_tab']) ? $_GET['sub_tab'] : '';

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Process feedback submission
        if (isset($_POST['submit_feedback'])) {
            $feedback_type = $_POST['feedback_type'];
            $rating = $_POST['rating'];
            $comments = $_POST['comments'];
            
            // Validate inputs
            if (empty($comments)) {
                throw new Exception("Comments field is required");
            }
            
            // Get optional IDs
            $course_id = ($feedback_type == 'Course') ? $_POST['course_id'] : null;
            $instructor_id = ($feedback_type == 'Instructor') ? $_POST['instructor_id'] : null;
            
            // Insert feedback
            $stmt = $conn->prepare("INSERT INTO feedback (user_id, feedback_type, course_id, instructor_id, rating, comments) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiiss", $_SESSION['user_id'], $feedback_type, $course_id, $instructor_id, $rating, $comments);
            $stmt->execute();
            $stmt->close();
            
            $message = "Feedback submitted successfully";
            $message_type = "success";
            $active_tab = 'feedback';
        }
        
        // Process payment record
        if (isset($_POST['record_payment'])) {
            $invoice_id = $_POST['invoice_id'];
            $amount = $_POST['amount'];
            $payment_method = $_POST['payment_method'];
            $reference_number = $_POST['reference_number'];
            
            // Validate inputs
            if (empty($invoice_id) || empty($amount) || empty($payment_method)) {
                throw new Exception("All fields are required");
            }
            
            // Get invoice details to check amount
            $stmt = $conn->prepare("SELECT i.total_amount, i.student_responsibility, 
                                  (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.invoice_id = i.invoice_id) AS paid_amount
                                  FROM invoices i WHERE i.invoice_id = ?");
            $stmt->bind_param("i", $invoice_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $invoice = $result->fetch_assoc();
            $stmt->close();
            
            if (!$invoice) {
                throw new Exception("Invoice not found");
            }
            
            $remaining = $invoice['student_responsibility'] - $invoice['paid_amount'];
            
            if ($amount > $remaining) {
                throw new Exception("Payment amount exceeds the remaining balance");
            }
            
            // Record payment
            $stmt = $conn->prepare("INSERT INTO payments (invoice_id, amount, payment_method, reference_number, received_by) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idssi", $invoice_id, $amount, $payment_method, $reference_number, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            
            // Update invoice status
            $new_paid = $invoice['paid_amount'] + $amount;
            $new_status = ($new_paid >= $invoice['student_responsibility']) ? 'Paid' : 'Partially Paid';
            
            $stmt = $conn->prepare("UPDATE invoices SET status = ? WHERE invoice_id = ?");
            $stmt->bind_param("si", $new_status, $invoice_id);
            $stmt->execute();
            $stmt->close();
            
            $message = "Payment recorded successfully";
            $message_type = "success";
            $active_tab = 'payments';
        }
        
        // Process report sending to registrar
        if (isset($_POST['send_report'])) {
            $report_id = $_POST['report_id'];
            $registrar_id = $_POST['registrar_id'];
            $notes = $_POST['notes'];
            
            // Insert into a sent_reports table (you would need to create this table)
            $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action_type, entity_type, entity_id, details) 
                                  VALUES (?, 'Report Sent', 'reports', ?, ?)");
            $stmt->bind_param("iis", $_SESSION['user_id'], $report_id, $notes);
            $stmt->execute();
            $stmt->close();
            
            $message = "Report sent to registrar successfully";
            $message_type = "success";
            $active_tab = 'reports';
            $active_sub_tab = 'send';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// Fetch financial summary data for dashboard
$total_billed = 0;
$total_received = 0;
$total_outstanding = 0;
$result = $conn->query("SELECT 
                        SUM(total_amount) AS total_billed,
                        (SELECT SUM(amount) FROM payments) AS total_received
                        FROM invoices");
$summary = $result->fetch_assoc();
if ($summary) {
    $total_billed = $summary['total_billed'] ?? 0;
    $total_received = $summary['total_received'] ?? 0;
    $total_outstanding = $total_billed - $total_received;
}

// Fetch recent payments
$recent_payments = [];
$result = $conn->query("SELECT p.payment_id, p.invoice_id, p.amount, p.payment_date, p.payment_method, 
                      p.reference_number, i.student_id, s.student_number,
                      u.first_name, u.last_name
                      FROM payments p
                      JOIN invoices i ON p.invoice_id = i.invoice_id
                      JOIN students s ON i.student_id = s.student_id
                      JOIN users u ON s.user_id = u.user_id
                      ORDER BY p.payment_date DESC
                      LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $recent_payments[] = $row;
}

// Fetch invoices with outstanding balances
$outstanding_invoices = [];
$result = $conn->query("SELECT i.invoice_id, i.student_id, i.academic_year, i.semester, 
                      i.total_amount, i.cost_share_amount, i.student_responsibility, 
                      i.issue_date, i.due_date, i.status,
                      s.student_number, u.first_name, u.last_name,
                      (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.invoice_id = i.invoice_id) AS paid_amount
                      FROM invoices i
                      JOIN students s ON i.student_id = s.student_id
                      JOIN users u ON s.user_id = u.user_id
                      WHERE i.status IN ('Issued', 'Partially Paid', 'Overdue')
                      ORDER BY i.due_date ASC");
while ($row = $result->fetch_assoc()) {
    $row['balance'] = $row['student_responsibility'] - $row['paid_amount'];
    $outstanding_invoices[] = $row;
}

// Fetch reports for viewing
$reports = [];
$result = $conn->query("SELECT r.report_id, r.report_type_id, r.generated_by, r.generation_date,
                       r.report_parameters, rt.report_name, u.first_name, u.last_name
                       FROM generated_reports r
                       JOIN report_types rt ON r.report_type_id = rt.report_type_id
                       JOIN users u ON r.generated_by = u.user_id
                       ORDER BY r.generation_date DESC");
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

// Fetch data for report options
// Get courses for feedback
$courses = [];
$result = $conn->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Get instructors for feedback
$instructors = [];
$result = $conn->query("SELECT u.user_id, u.first_name, u.last_name
                      FROM users u
                      JOIN roles r ON u.role_id = r.role_id
                      WHERE r.role_name = 'instructor'
                      ORDER BY u.last_name, u.first_name");
while ($row = $result->fetch_assoc()) {
    $instructors[] = $row;
}

// Get registrars for sending reports
$registrars = [];
$result = $conn->query("SELECT u.user_id, u.first_name, u.last_name
                      FROM users u
                      JOIN roles r ON u.role_id = r.role_id
                      WHERE r.role_name = 'registrar'
                      ORDER BY u.last_name, u.first_name");
while ($row = $result->fetch_assoc()) {
    $registrars[] = $row;
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Finance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Base styles */
        :root {
            --primary-color: #5D5CDE;
            --primary-dark: #4A49B0;
            --primary-light: #F0F0FF;
            --success-color: #28a745;
            --success-light: #e8f5e9;
            --danger-color: #dc3545;
            --danger-light: #ffebee;
            --warning-color: #ffc107;
            --warning-light: #fff3e0;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --gray-color: #6c757d;
            --gray-light: #e9ecef;
            --body-bg: #f5f5f5;
            --card-bg: #ffffff;
            --text-color: #212529;
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --body-bg: #121212;
                --card-bg: #1e1e1e;
                --text-color: #e0e0e0;
                --gray-light: #2d2d2d;
            }
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--body-bg);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        /* Layout */
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--dark-color);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100%;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            background-color: rgba(0,0,0,0.2);
        }
        
        .logo-icon {
            font-size: 24px;
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .user-info {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 15px;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-role {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
        }
        
        .nav-menu {
            padding: 0;
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            border-left: 4px solid white;
        }
        
        .nav-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        .page-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
        }
        
        /* Cards */
        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Stats cards */
        .stats-row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            margin: 10px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }
        
        .stat-title {
            color: var(--gray-color);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stat-icon {
            font-size: 36px;
            margin-bottom: 15px;
        }
        
        .stat-trend {
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-trend i {
            margin-right: 5px;
        }
        
        .trend-up {
            color: var(--success-color);
        }
        
        .trend-down {
            color: var(--danger-color);
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }
        
        th {
            font-weight: 600;
            color: var(--gray-color);
            background-color: var(--light-color);
        }
        
        tbody tr:hover {
            background-color: rgba(93, 92, 222, 0.05);
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input[type="text"], 
        input[type="email"], 
        input[type="password"],
        input[type="number"],
        input[type="date"],
        select, 
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            background-color: var(--card-bg);
            color: var(--text-color);
        }
        
        textarea {
            height: 120px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            margin: 0 -10px;
        }
        
        .form-col {
            flex: 1;
            padding: 0 10px;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 8px 16px;
            font-size: 14px;
            line-height: 1.5;
            border-radius: 4px;
            transition: all 0.15s;
            text-decoration: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: var(--success-light);
            border-color: var(--success-color);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: var(--danger-light);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 5px 8px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
        }
        
        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }
        
        .badge-success {
            background-color: var(--success-light);
            color: var(--success-color);
        }
        
        .badge-warning {
            background-color: var(--warning-light);
            color: #856404;
        }
        
        .badge-danger {
            background-color: var(--danger-light);
            color: var(--danger-color);
        }
        
        /* Tabs */
        .tab-nav {
            display: flex;
            border-bottom: 1px solid var(--gray-light);
            margin-bottom: 20px;
        }
        
        .tab-link {
            padding: 10px 15px;
            margin-right: 5px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-link.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Utilities */
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-250px);
            }
            
            .content {
                margin-left: 0;
            }
            
            .sidebar.active {
                width: 250px;
                transform: translateX(0);
            }
            
            .content.active {
                margin-left: 250px;
            }
            
            .stats-row {
                flex-direction: column;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .form-col {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
                <div class="sidebar-title">OCSMS</div>
            </div>
            
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['name']; ?></div>
                <div class="user-role">Finance Officer</div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="?tab=dashboard" class="nav-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                        <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?tab=payments" class="nav-link <?php echo $active_tab === 'payments' ? 'active' : ''; ?>">
                        <div class="nav-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <span>Manage Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?tab=reports" class="nav-link <?php echo $active_tab === 'reports' ? 'active' : ''; ?>">
                        <div class="nav-icon"><i class="fas fa-chart-bar"></i></div>
                        <span>Financial Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?tab=feedback" class="nav-link <?php echo $active_tab === 'feedback' ? 'active' : ''; ?>">
                        <div class="nav-icon"><i class="fas fa-comment-alt"></i></div>
                        <span>Send Feedback</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-home"></i></div>
                        <span>Main Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <main class="content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($active_tab === 'dashboard'): ?>
                <h1 class="page-title">Finance Dashboard</h1>
                
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-file-invoice-dollar" style="color: #5D5CDE;"></i></div>
                        <div class="stat-title">Total Billed</div>
                        <div class="stat-value"><?php echo formatCurrency($total_billed); ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up trend-up"></i> 12% this month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-hand-holding-usd" style="color: #28a745;"></i></div>
                        <div class="stat-title">Total Received</div>
                        <div class="stat-value"><?php echo formatCurrency($total_received); ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up trend-up"></i> 8% this month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-exclamation-circle" style="color: #dc3545;"></i></div>
                        <div class="stat-title">Outstanding Balance</div>
                        <div class="stat-value"><?php echo formatCurrency($total_outstanding); ?></div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up trend-up"></i> 5% this month
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payment Trends</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="paymentsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Payments</h3>
                        <a href="?tab=payments" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>ID</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_payments) > 0): ?>
                                        <?php foreach ($recent_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                                                <td><?php echo $payment['student_number']; ?></td>
                                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                                <td><?php echo $payment['payment_method']; ?></td>
                                                <td><?php echo $payment['reference_number']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No recent payments found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($active_tab === 'payments'): ?>
                <h1 class="page-title">Manage Payments</h1>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Record New Payment</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="invoice_id">Select Invoice</label>
                                        <select id="invoice_id" name="invoice_id" required>
                                            <option value="">-- Select Invoice --</option>
                                            <?php foreach ($outstanding_invoices as $invoice): ?>
                                                <option value="<?php echo $invoice['invoice_id']; ?>" data-balance="<?php echo $invoice['balance']; ?>">
                                                    <?php echo $invoice['student_number'] . ' - ' . $invoice['first_name'] . ' ' . $invoice['last_name'] . ' (' . $invoice['semester'] . ' ' . $invoice['academic_year'] . ') - Balance: ' . formatCurrency($invoice['balance']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="amount">Payment Amount</label>
                                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="payment_method">Payment Method</label>
                                        <select id="payment_method" name="payment_method" required>
                                            <option value="">-- Select Method --</option>
                                            <option value="Credit Card">Credit Card</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Check">Check</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="reference_number">Reference Number</label>
                                        <input type="text" id="reference_number" name="reference_number">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="record_payment" class="btn btn-primary">Record Payment</button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Outstanding Invoices</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>ID</th>
                                        <th>Term</th>
                                        <th>Total Amount</th>
                                        <th>Paid</th>
                                        <th>Balance</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($outstanding_invoices) > 0): ?>
                                        <?php foreach ($outstanding_invoices as $invoice): ?>
                                            <tr>
                                                <td><?php echo $invoice['first_name'] . ' ' . $invoice['last_name']; ?></td>
                                                <td><?php echo $invoice['student_number']; ?></td>
                                                <td><?php echo $invoice['semester'] . ' ' . $invoice['academic_year']; ?></td>
                                                <td><?php echo formatCurrency($invoice['student_responsibility']); ?></td>
                                                <td><?php echo formatCurrency($invoice['paid_amount']); ?></td>
                                                <td><?php echo formatCurrency($invoice['balance']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                        $status_class = '';
                                                        switch ($invoice['status']) {
                                                            case 'Issued':
                                                                $status_class = 'badge-primary';
                                                                break;
                                                            case 'Partially Paid':
                                                                $status_class = 'badge-warning';
                                                                break;
                                                            case 'Overdue':
                                                                $status_class = 'badge-danger';
                                                                break;
                                                            default:
                                                                $status_class = 'badge-primary';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>"><?php echo $invoice['status']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No outstanding invoices found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($active_tab === 'reports'): ?>
                <h1 class="page-title">Financial Reports</h1>
                
                <div class="tab-nav">
                    <div class="tab-link <?php echo $active_sub_tab === '' || $active_sub_tab === 'view' ? 'active' : ''; ?>" data-tab="view-reports">View Reports</div>
                    <div class="tab-link <?php echo $active_sub_tab === 'send' ? 'active' : ''; ?>" data-tab="send-reports">Send to Registrar</div>
                </div>
                
                <div id="view-reports" class="tab-content <?php echo $active_sub_tab === '' || $active_sub_tab === 'view' ? 'active' : ''; ?>">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Available Reports</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Report Name</th>
                                            <th>Generated By</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($reports) > 0): ?>
                                            <?php foreach ($reports as $report): ?>
                                                <tr>
                                                    <td><?php echo $report['report_name']; ?></td>
                                                    <td><?php echo $report['first_name'] . ' ' . $report['last_name']; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($report['generation_date'])); ?></td>
                                                    <td>
                                                        <a href="#" class="btn btn-primary btn-sm">View</a>
                                                        <a href="?tab=reports&sub_tab=send&report_id=<?php echo $report['report_id']; ?>" class="btn btn-success btn-sm">Send</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No reports available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Generate New Report</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="report_type">Report Type</label>
                                        <select id="report_type">
                                            <option value="">-- Select Report Type --</option>
                                            <option value="1">Financial Summary</option>
                                            <option value="2">Outstanding Balances</option>
                                            <option value="3">Payment History</option>
                                            <option value="4">Cost Sharing Distribution</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="report_period">Time Period</label>
                                        <select id="report_period">
                                            <option value="current">Current Semester</option>
                                            <option value="previous">Previous Semester</option>
                                            <option value="year">Current Academic Year</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="custom-date-range" style="display: none;">
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="start_date">Start Date</label>
                                            <input type="date" id="start_date">
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="end_date">End Date</label>
                                            <input type="date" id="end_date">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button class="btn btn-primary">Generate Report</button>
                        </div>
                    </div>
                </div>
                
                <div id="send-reports" class="tab-content <?php echo $active_sub_tab === 'send' ? 'active' : ''; ?>">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Send Report to Registrar</h3>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="report_id">Select Report</label>
                                    <select id="report_id" name="report_id" required>
                                        <option value="">-- Select Report --</option>
                                        <?php foreach ($reports as $report): ?>
                                            <option value="<?php echo $report['report_id']; ?>" <?php echo (isset($_GET['report_id']) && $_GET['report_id'] == $report['report_id']) ? 'selected' : ''; ?>>
                                                <?php echo $report['report_name'] . ' (' . date('M d, Y', strtotime($report['generation_date'])) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="registrar_id">Select Registrar</label>
                                    <select id="registrar_id" name="registrar_id" required>
                                        <option value="">-- Select Registrar --</option>
                                        <?php foreach ($registrars as $registrar): ?>
                                            <option value="<?php echo $registrar['user_id']; ?>">
                                                <?php echo $registrar['first_name'] . ' ' . $registrar['last_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea id="notes" name="notes" placeholder="Add any additional information or context for the registrar..."></textarea>
                                </div>
                                
                                <button type="submit" name="send_report" class="btn btn-primary">Send Report</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($active_tab === 'feedback'): ?>
                <h1 class="page-title">Send Feedback</h1>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Submit Feedback</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="feedback_type">Feedback Type</label>
                                <select id="feedback_type" name="feedback_type" required onchange="showRelevantFields()">
                                    <option value="">-- Select Type --</option>
                                    <option value="Course">Course</option>
                                    <option value="Instructor">Instructor</option>
                                    <option value="System">System</option>
                                    <option value="Cost Sharing">Cost Sharing</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div id="course_field" class="form-group" style="display: none;">
                                <label for="course_id">Select Course</label>
                                <select id="course_id" name="course_id">
                                    <option value="">-- Select Course --</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>">
                                            <?php echo $course['course_code'] . ': ' . $course['course_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div id="instructor_field" class="form-group" style="display: none;">
                                <label for="instructor_id">Select Instructor</label>
                                <select id="instructor_id" name="instructor_id">
                                    <option value="">-- Select Instructor --</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                        <option value="<?php echo $instructor['user_id']; ?>">
                                            <?php echo $instructor['first_name'] . ' ' . $instructor['last_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="rating">Rating (1-5)</label>
                                <input type="number" id="rating" name="rating" min="1" max="5" value="5" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="comments">Comments</label>
                                <textarea id="comments" name="comments" required placeholder="Please provide detailed feedback..."></textarea>
                            </div>
                            
                            <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        // Tab navigation
        document.querySelectorAll('.tab-link').forEach(tab => {
            tab.addEventListener('click', function() {
                // Get tab id
                const tabId = this.getAttribute('data-tab');
                
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Remove active class from all tabs
                document.querySelectorAll('.tab-link').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Show active tab content
                document.getElementById(tabId).classList.add('active');
                this.classList.add('active');
                
                // Update URL parameter
                const url = new URL(window.location);
                url.searchParams.set('sub_tab', tabId.replace('-reports', ''));
                window.history.pushState({}, '', url);
            });
        });
        
        // Show fields based on feedback type
        function showRelevantFields() {
            const feedbackType = document.getElementById('feedback_type').value;
            
            // Hide all conditional fields
            document.getElementById('course_field').style.display = 'none';
            document.getElementById('instructor_field').style.display = 'none';
            
            // Show relevant fields
            if (feedbackType === 'Course') {
                document.getElementById('course_field').style.display = 'block';
            } else if (feedbackType === 'Instructor') {
                document.getElementById('instructor_field').style.display = 'block';
            }
        }
        
        // Toggle custom date range
        const reportPeriod = document.getElementById('report_period');
        if (reportPeriod) {
            reportPeriod.addEventListener('change', function() {
                const customDateRange = document.getElementById('custom-date-range');
                if (this.value === 'custom') {
                    customDateRange.style.display = 'block';
                } else {
                    customDateRange.style.display = 'none';
                }
            });
        }
        
        // Update max amount based on invoice balance
        const invoiceSelect = document.getElementById('invoice_id');
        if (invoiceSelect) {
            invoiceSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const balance = selectedOption.getAttribute('data-balance');
                
                if (balance) {
                    const amountInput = document.getElementById('amount');
                    amountInput.setAttribute('max', balance);
                    amountInput.value = balance;
                }
            });
        }
        
        // Initialize payment trends chart
        const ctx = document.getElementById('paymentsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Billed Amount',
                            data: [5000, 7500, 12000, 8000, 9500, 11000, 13500, 15000, 12500, 10000, 9000, 11500],
                            borderColor: '#5D5CDE',
                            backgroundColor: 'rgba(93, 92, 222, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Received Amount',
                            data: [4000, 6000, 10000, 7000, 8500, 9500, 12000, 13500, 10500, 9000, 8000, 10000],
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('en-US', {
                                            style: 'currency',
                                            currency: 'USD'
                                        }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Mobile navigation toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.content').classList.toggle('active');
        }
    </script>
</body>
</html>