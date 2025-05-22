<?php
// Start session
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit;
}



// Get user information
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';

// Check if we have cost share data in session
$cost_share_total = isset($_SESSION['cost_share_total']) ? $_SESSION['cost_share_total'] : 0;
$invoice_id = isset($_SESSION['invoice_id']) ? $_SESSION['invoice_id'] : null;

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password (blank)

$error_message = '';
$success_message = '';
$info_message = '';
try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First check if the student_bank_accounts table exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as table_exists 
        FROM information_schema.tables 
        WHERE table_schema = ? AND table_name = 'student_bank_accounts'
    ");
    $stmt->execute([$dbname]);
    $table_check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create student_bank_accounts table if it doesn't exist
    if ($table_check['table_exists'] == 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS student_bank_accounts (
              account_id INT(11) NOT NULL AUTO_INCREMENT,
              student_id INT(11) NOT NULL,
              account_number VARCHAR(50) NOT NULL,
              balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
              currency VARCHAR(3) NOT NULL DEFAULT 'ETB',
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (account_id),
              UNIQUE KEY (student_id),
              UNIQUE KEY (account_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        
        // Create university_bank_account table if needed
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS university_bank_account (
              account_id INT(11) NOT NULL AUTO_INCREMENT,
              account_number VARCHAR(50) NOT NULL,
              balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
              currency VARCHAR(3) NOT NULL DEFAULT 'ETB',
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (account_id),
              UNIQUE KEY (account_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        
        // Insert university account
        $pdo->exec("
            INSERT INTO university_bank_account (account_number, balance)
            VALUES ('UNIV123456789', 0.00)
        ");
    }
    
    // Get student ID first without joining bank accounts
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student_basic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_basic) {
        throw new Exception("Student record not found");
    }
    
    $student_id = $student_basic['student_id'];
    
    // Check if student has a bank account, create one if not
    $stmt = $pdo->prepare("SELECT COUNT(*) as has_account FROM student_bank_accounts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $account_check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($account_check['has_account'] == 0) {
        // Create a new account for the student with 100,000 ETB
        $account_number = 'STU' . str_pad($student_id, 8, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("
            INSERT INTO student_bank_accounts (student_id, account_number, balance)
            VALUES (?, ?, 100000.00)
        ");
        $stmt->execute([$student_id, $account_number]);
    }
    
    // Now get the student's bank account information
    $stmt = $pdo->prepare("
        SELECT a.account_id, a.account_number, a.balance 
        FROM student_bank_accounts a
        WHERE a.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $bank_account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Set default values if account not found
    $student_account_id = $bank_account['account_id'] ?? null;
    $student_account_number = $bank_account['account_number'] ?? 'Not Available';
    $student_balance = $bank_account['balance'] ?? 0.00;

    // Get university bank account
    $stmt = $pdo->prepare("SELECT account_id, account_number, balance FROM university_bank_account LIMIT 1");
    $stmt->execute();
    $university_account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$university_account) {
        // Handle case where university account doesn't exist
        $university_account = [
            'account_id' => null,
            'account_number' => 'Not Available',
            'balance' => 0.00
        ];
    }
    
    // Rest of your code for getting outstanding invoices, etc.
    
    // Get outstanding invoices
    $stmt = $pdo->prepare("
        SELECT i.invoice_id, i.academic_year, i.semester, i.total_amount, 
               i.cost_share_amount, i.student_responsibility, i.issue_date, i.due_date, i.status,
               EXISTS(
                 SELECT 1 FROM payments p WHERE p.invoice_id = i.invoice_id
               ) as has_payment
        FROM invoices i
        WHERE i.student_id = ? AND i.status IN ('Issued', 'Partially Paid', 'Overdue')
        ORDER BY i.due_date ASC
    ");
    $stmt->execute([$student_id]);
    $outstanding_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history
    $stmt = $pdo->prepare("
        SELECT p.payment_id, p.invoice_id, p.amount, p.payment_date, p.payment_method, p.reference_number
        FROM payments p
        JOIN invoices i ON p.invoice_id = i.invoice_id
        WHERE i.student_id = ?
        ORDER BY p.payment_date DESC
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

      // Process payment form

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    // Get form data
    $payment_invoice_id = $_POST['invoice_id'];
    $payment_amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $reference_number = $_POST['reference_number'];
    
    // Validate input
    if (empty($payment_invoice_id) || empty($payment_amount) || empty($payment_method)) {
        throw new Exception("All fields are required");
    }
    
    if (!is_numeric($payment_amount) || $payment_amount <= 0) {
        throw new Exception("Payment amount must be a positive number");
    }
    
    // Get invoice
    $stmt = $pdo->prepare("
        SELECT invoice_id, total_amount, student_responsibility, status 
        FROM invoices 
        WHERE invoice_id = ? AND student_id = ?
    ");
    $stmt->execute([$payment_invoice_id, $student_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        throw new Exception("Invoice not found");
    }
    
    // Check if payment amount exceeds remaining balance
    if ($payment_amount > $invoice['student_responsibility']) {
        throw new Exception("Payment amount exceeds remaining invoice balance");
    }
    
    // Check if student has enough funds
    if ($payment_amount > $student_balance) {
        throw new Exception("Insufficient funds in your account. Your current balance is " . number_format($student_balance, 2) . " ETB");
    }
    
    // Start transaction - THIS IS CRITICAL!
    $pdo->beginTransaction();
    
    try {
        // 1. Deduct from student account
        $stmt = $pdo->prepare("
            UPDATE student_bank_accounts 
            SET balance = balance - ?, 
                updated_at = CURRENT_TIMESTAMP
            WHERE student_id = ?
        ");
        $stmt->execute([$payment_amount, $student_id]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("Failed to update student account balance");
        }
        
        // 2. Add to university account
        $stmt = $pdo->prepare("
            UPDATE university_bank_account 
            SET balance = balance + ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE account_id = ?
        ");
        $stmt->execute([$payment_amount, $university_account['account_id']]);
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("Failed to update university account balance");
        }
        
        // 3. Create bank transaction record
      $stmt = $pdo->prepare("
    INSERT INTO bank_transactions (
        student_id, transaction_type, amount, reference_number, payment_method, status, transaction_date
    ) VALUES (?, 'payment', ?, ?, ?, 'completed', NOW())
");
$stmt->execute([$student_id, $payment_amount, $reference_number, $payment_method]);
        
        // 4. Add payment record
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                invoice_id, amount, payment_method, reference_number, received_by
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $payment_invoice_id, 
            $payment_amount, 
            $payment_method, 
            $reference_number, 
            null  // received_by is null since this is an online payment
        ]);
        
        // 5. Update invoice status
        $new_status = 'Partially Paid';
        $new_responsibility = $invoice['student_responsibility'] - $payment_amount;
        
        if ($new_responsibility <= 0) {
            $new_status = 'Paid';
            $new_responsibility = 0;
        }
        
        $stmt = $pdo->prepare("
            UPDATE invoices
            SET student_responsibility = ?, 
                status = ?
            WHERE invoice_id = ?
        ");
        $stmt->execute([$new_responsibility, $new_status, $payment_invoice_id]);
        
        // 6. SAFELY attempt to create notification - This fixes the foreign key constraint error
        try {
            // Check if user exists in system_users before attempting to create notification
            $user_check_stmt = $pdo->prepare("
                SELECT COUNT(*) as user_exists 
                FROM system_users 
                WHERE user_id = ?
            ");
            $user_check_stmt->execute([$user_id]);
            $user_check = $user_check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_check['user_exists'] > 0) {
                // User exists, so it's safe to create a notification
                $notification_message = "Your payment of " . number_format($payment_amount, 2) . " ETB has been processed successfully.";
                
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (
                        user_id, type, message, is_read, created_at
                    ) VALUES (?, 'payment', ?, 0, NOW())
                ");
                $stmt->execute([$user_id, $notification_message]);
            } else {
                // User doesn't exist in system_users table, log the issue but don't fail the transaction
                error_log("Cannot create payment notification: User ID {$user_id} not found in system_users table");
            }
        } catch (Exception $notification_error) {
            // Log notification error but DO NOT fail the transaction
            error_log("Failed to create payment notification: " . $notification_error->getMessage());
            // We intentionally don't rethrow this exception - payment should succeed even if notification fails
        }
        
        // 7. Add system log entry
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $details = "Payment of " . number_format($payment_amount, 2) . " ETB made for invoice #" . $payment_invoice_id;
            
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (
                    user_id, action_type, entity_type, entity_id, ip_address, details
                ) VALUES (?, 'payment', 'invoice', ?, ?, ?)
            ");
            $stmt->execute([$user_id, $payment_invoice_id, $ip_address, $details]);
        } catch (Exception $log_error) {
            // Just log this error but don't stop the process
            error_log("Error adding system log: " . $log_error->getMessage());
        }
        
        // 8. Commit transaction
        $pdo->commit();
        
        // 9. Update the success message
        $success_message = "Payment of " . number_format($payment_amount, 2) . " ETB processed successfully. Your new balance is " . 
                           number_format($student_balance - $payment_amount, 2) . " ETB";
        
        // 10. Redirect to avoid form resubmission
        header("Location: make_Payment.php?success=1&amount=" . $payment_amount);
        exit;
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $pdo->rollBack();
        
        // This is important - capture and show the specific error
        $error_message = "Payment Error: " . $e->getMessage();
        
        // Log the error for debugging
        error_log("Payment processing error: " . $e->getMessage());
    }
}

// At the top of your script, add this function to ensure system_logs table exists
function ensure_system_logs_table_exists($pdo, $dbname) {
    // Check if system_logs table exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as table_exists 
        FROM information_schema.tables 
        WHERE table_schema = ? AND table_name = 'system_logs'
    ");
    $stmt->execute([$dbname]);
    $table_check = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($table_check['table_exists'] == 0) {
        // Create system_logs table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_logs (
                log_id INT(11) NOT NULL AUTO_INCREMENT,
                user_id INT(11) NULL DEFAULT NULL,
                action_type VARCHAR(50) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id VARCHAR(50) NULL DEFAULT NULL,
                ip_address VARCHAR(50) NULL DEFAULT NULL,
                details TEXT NULL,
                severity VARCHAR(20) DEFAULT 'info',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (log_id),
                KEY (user_id),
                KEY (action_type),
                KEY (entity_type),
                KEY (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
}

// Call this function during your database setup
// ensure_system_logs_table_exists($pdo, $dbname);
}catch (Exception $e) {
    // If we're in a transaction, roll it back
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $error_message = $e->getMessage();

}
// Debug information
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Payment form submitted with data: " . print_r($_POST, true));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Make Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
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
            max-width: 900px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #5D5CDE;
            text-decoration: none;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .page-title {
            color: #5D5CDE;
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        
        .error-message {
            color: #f44336;
            background-color: #ffebee;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
        }
        
        .success-message {
            color: #4caf50;
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
        }
        
        .info-message {
            color: #2196F3;
            background-color: #E3F2FD;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f9f9f9;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .no-data {
            text-align: center;
            color: #777;
            padding: 20px;
        }
        
        .btn {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn:hover {
            background-color: #4A49B0;
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
        }
        
        .btn-disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
        }
        
        .btn-disabled:hover {
            background-color: #cccccc;
        }
        
        form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .recent-payment {
            background-color: #f9f9f9;
            border-left: 3px solid #5D5CDE;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .recent-payment p {
            margin: 5px 0;
        }
        
        .recent-payment-date {
            color: #777;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .cost-display {
            background-color: #f0f0ff;
            border: 1px solid #d0d0ff;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .cost-display p {
            margin: 5px 0;
            font-size: 16px;
        }
        
        .cost-total {
            font-size: 20px;
            font-weight: bold;
            color: #5D5CDE;
        }
        
        .payment-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .payment-badge-paid {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .hidden {
            display: none;
        }

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        input[type=number] {
            -moz-appearance: textfield;
        }
        
        .account-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .account-info-title {
            font-weight: 600;
            font-size: 16px;
            color: #1e40af;
            margin-bottom: 12px;
        }
        
        .account-detail {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        
        .account-label {
            font-weight: 500;
            color: #4b5563;
        }
        
        .account-value {
            font-weight: 600;
        }
        
        .account-balance {
            font-size: 18px;
            font-weight: 700;
            color: #047857;
        }
        
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #181818;
                color: #f5f5f5;
            }
            
            .header, .card {
                background-color: #222;
                color: #f5f5f5;
            }
            
            .logo {
                color: #f5f5f5;
            }
            
            th {
                background-color: #2a2a2a;
            }
            
            th, td {
                border-bottom: 1px solid #333;
            }
            
            tr:hover {
                background-color: #2a2a2a;
            }
            
            .recent-payment {
                background-color: #2a2a2a;
            }
            
            .cost-display {
                background-color: #2a2a3c;
                border-color: #3a3a4c;
            }
            
            input[type="text"],
            input[type="number"],
            select {
                background-color: #333;
                border-color: #444;
                color: #f5f5f5;
            }

            .error-message {
                background-color: rgba(244, 67, 54, 0.1);
            }
            
            .success-message {
                background-color: rgba(76, 175, 80, 0.1);
            }
            
            .info-message {
                background-color: rgba(33, 150, 243, 0.1);
            }
            
            .payment-badge-paid {
                background-color: rgba(76, 175, 80, 0.2);
            }
            
            .btn-disabled {
                background-color: #444444;
                color: #999999;
            }
            
            .account-info {
                background-color: #1e293b;
                border-color: #334155;
            }
            
            .account-info-title {
                color: #93c5fd;
            }
            
            .account-detail {
                border-bottom-color: #374151;
            }
            
            .account-label {
                color: #9ca3af;
            }
            
            .account-balance {
                color: #10b981;
            }
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
            <span><?php echo $name; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">Make Payment</h1>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($info_message): ?>
            <div class="info-message">
                <i class="fas fa-info-circle"></i> <?php echo $info_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Account Information Section -->
        <div class="card">
            <h2 class="card-title">Your Account Information</h2>
            
            <div class="account-info">
                <div class="account-info-title">Bank Account Details</div>
                <div class="account-detail">
                    <span class="account-label">Account Number:</span>
                    <span class="account-value"><?php echo $student_account_number ?? 'Not Available'; ?></span>
                </div>
                <div class="account-detail">
                    <span class="account-label">Available Balance:</span>
                    <span class="account-balance"><?php echo number_format($student_balance ?? 0, 2); ?> ETB</span>
                </div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['cost_share_total']) && $_SESSION['cost_share_total'] > 0): ?>
        <div class="card">
            <h2 class="card-title">Cost Share Agreement Summary</h2>
            <div class="cost-display">
                <p>You have recently submitted a cost share agreement with the following details:</p>
                <p class="cost-total">Total Cost: <?php echo number_format($_SESSION['cost_share_total'], 2); ?> ETB</p>
                <?php if (isset($_SESSION['invoice_id'])): ?>
                <p>An invoice has been generated for this agreement.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title">Outstanding Invoices</h2>
            
            <?php if (!empty($outstanding_invoices)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Total Amount</th>
                            <th>Remaining</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outstanding_invoices as $invoice): ?>
                            <tr data-invoice-id="<?php echo $invoice['invoice_id']; ?>" data-has-payment="<?php echo $invoice['has_payment'] ? 'true' : 'false'; ?>">
                                <td><?php echo $invoice['invoice_id']; ?></td>
                                <td><?php echo $invoice['academic_year']; ?></td>
                                <td><?php echo $invoice['semester']; ?></td>
                                <td><?php echo number_format($invoice['total_amount'], 2); ?> ETB</td>
                                <td><?php echo number_format($invoice['student_responsibility'], 2); ?> ETB</td>
                                <td><?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></td>
                                <td>
                                    <?php if ($invoice['status'] == 'Overdue'): ?>
                                        <span style="color: #f44336;"><?php echo $invoice['status']; ?></span>
                                    <?php else: ?>
                                        <?php echo $invoice['status']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($invoice['has_payment']): ?>
                                        <span class="payment-badge payment-badge-paid">Payment Made</span>
                                    <?php else: ?>
                                        <button class="btn btn-sm" onclick="selectInvoice(<?php echo $invoice['invoice_id']; ?>, <?php echo $invoice['student_responsibility']; ?>)">Pay</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">You have no outstanding invoices at this time.</p>
            <?php endif; ?>
            
            <form id="paymentForm" class="hidden" method="post" action="">
                <h3>Make Payment</h3>
                
                <div class="form-group">
                    <label for="invoice_id">Invoice #</label>
                    <input type="text" id="invoice_id" name="invoice_id" readonly>
                </div>
                
                <div class="form-group">
                    <label for="amount">Payment Amount (ETB)</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
                    <small id="amountHelp" style="display: block; margin-top: 5px; color: #777;">
                        Remaining invoice balance: <span id="remainingAmount">0.00</span> ETB<br>
                        Your account balance: <?php echo number_format($student_balance, 2); ?> ETB
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Mobile Money">Mobile Money</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="reference_number">Reference Number</label>
                    <input type="text" id="reference_number" name="reference_number" placeholder="Optional for Cash payments">
                </div>
                
                <button type="submit" id="submitPayment" name="make_payment" class="btn">Submit Payment</button>
            </form>
        </div>
        
        <div class="card">
            <h2 class="card-title">Recent Payment History</h2>
            
            <?php if (!empty($recent_payments)): ?>
                <?php foreach ($recent_payments as $payment): ?>
                    <div class="recent-payment">
                        <p><strong>Amount:</strong> <?php echo number_format($payment['amount'], 2); ?> ETB</p>
                        <p><strong>Method:</strong> <?php echo $payment['payment_method']; ?></p>
                        <p><strong>Reference:</strong> <?php echo $payment['reference_number'] ?: 'N/A'; ?></p>
                        <p class="recent-payment-date">Payment made on <?php echo date('F d, Y', strtotime($payment['payment_date'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data">No payment history found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Email Confirmation Modal -->
        <div id="emailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
                <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Confirmation Email</h3>
                    <button id="closeEmailModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div id="emailPreview" class="border rounded p-4 mb-4 text-sm">
                        <!-- Email content will be inserted here -->
                    </div>
                    <div class="flex justify-between items-center">
                        <span id="emailStatus" class="text-sm text-green-600 dark:text-green-400">
                            <i class="fas fa-check-circle mr-1"></i> Email sent successfully
                        </span>
                        <button id="closeEmailBtn" class="btn">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize payment status tracking
        const paymentStatus = {
            // Store which invoices have been paid by this student
            paidInvoices: new Set(
                <?php 
                    // Convert PHP array of paid invoices to JavaScript
                    $paid_invoices = array_filter($outstanding_invoices, function($inv) {
                        return $inv['has_payment'] == true;
                    });
                    
                    $paid_invoice_ids = array_map(function($inv) {
                        return $inv['invoice_id'];
                    }, $paid_invoices);
                    
                    echo json_encode($paid_invoice_ids);
                ?>
            )
        };
        
        // Show the payment form when an invoice is selected
        function selectInvoice(invoiceId, remainingAmount) {
            // Check if this student has already paid for this invoice
            if (paymentStatus.paidInvoices.has(invoiceId)) {
                showInfoMessage("You have already made a payment for this invoice. Each student is allowed only one payment per invoice.");
                return;
            }
            
            // Show payment form
            const paymentForm = document.getElementById('paymentForm');
            paymentForm.classList.remove('hidden');
            
            // Scroll to the form
            paymentForm.scrollIntoView({ behavior: 'smooth' });
            
            // Set invoice details
            document.getElementById('invoice_id').value = invoiceId;
            document.getElementById('remainingAmount').textContent = remainingAmount.toFixed(2);
            
            // Set default amount to remaining balance or student balance, whichever is lower
            const studentBalance = <?php echo $student_balance ?? 0; ?>;
            const defaultAmount = Math.min(remainingAmount, studentBalance);
            
            document.getElementById('amount').value = defaultAmount.toFixed(2);
            document.getElementById('amount').max = remainingAmount;
            
            // Clear any previous messages
            hideErrorMessage();
            hideSuccessMessage();
            hideInfoMessage();
        }
        
        // Helper functions for UI feedback
        function showErrorMessage(message) {
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            
            if (!errorMessage || !errorText) return;
            
            // Hide other messages
            hideSuccessMessage();
            hideInfoMessage();
            
            errorText.textContent = message;
            errorMessage.classList.remove('hidden');
            
            // Scroll to error message
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function hideErrorMessage() {
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) errorMessage.classList.add('hidden');
        }
        
        function showSuccessMessage(message) {
            const successMessage = document.getElementById('successMessage');
            const successText = document.getElementById('successText');
            
            if (!successMessage || !successText) return;
            
            // Hide other messages
            hideErrorMessage();
            hideInfoMessage();
            
            successText.textContent = message;
            successMessage.classList.remove('hidden');
            
            // Scroll to success message
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function hideSuccessMessage() {
            const successMessage = document.getElementById('successMessage');
            if (successMessage) successMessage.classList.add('hidden');
        }
        
        function showInfoMessage(message) {
            const infoMessage = document.getElementById('infoMessage');
            const infoText = document.getElementById('infoText');
            
            if (!infoMessage || !infoText) return;
            
            // Hide other messages
            hideErrorMessage();
            hideSuccessMessage();
            
            infoText.textContent = message;
            infoMessage.classList.remove('hidden');
            
            // Scroll to info message
            infoMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function hideInfoMessage() {
            const infoMessage = document.getElementById('infoMessage');
            if (infoMessage) infoMessage.classList.add('hidden');
        }
        
        // Email confirmation functions
        function closeEmailModal() {
            const emailModal = document.getElementById('emailModal');
            if (emailModal) emailModal.classList.add('hidden');
        }
        
        // Set up event listeners when document is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Check for dark mode
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
            
            // Listen for dark mode changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
                if (event.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            });
            
            // Setup email modal close buttons
            const closeEmailModalBtn = document.getElementById('closeEmailModal');
            const closeEmailBtn = document.getElementById('closeEmailBtn');
            
            if (closeEmailModalBtn) {
                closeEmailModalBtn.addEventListener('click', closeEmailModal);
            }
            
            if (closeEmailBtn) {
                closeEmailBtn.addEventListener('click', closeEmailModal);
            }
            
            // Set up amount input validation
            const amountInput = document.getElementById('amount');
            if (amountInput) {
                amountInput.addEventListener('input', function() {
                    const studentBalance = <?php echo $student_balance ?? 0; ?>;
                    const remainingAmount = parseFloat(document.getElementById('remainingAmount').textContent);
                    const amount = parseFloat(this.value);
                    
                    if (amount > studentBalance) {
                        showErrorMessage("Amount exceeds your available balance of " + studentBalance.toFixed(2) + " ETB");
                        this.setCustomValidity("Amount exceeds your available balance");
                    } else if (amount > remainingAmount) {
                        showErrorMessage("Amount exceeds remaining invoice balance of " + remainingAmount.toFixed(2) + " ETB");
                        this.setCustomValidity("Amount exceeds remaining invoice balance");
                    } else {
                        hideErrorMessage();
                        this.setCustomValidity("");
                    }
                });
            }
        });
    </script>
</body>
</html> 