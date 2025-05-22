<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password (blank)

$submission_success = false;
$error_message = '';
$success_message = '';

// Function to create banking tables with correct structure
function createBankingTablesIfNeeded($pdo) {
    // Check if we need to drop and recreate the problematic bank_transactions table
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE bank_transactions");
        if ($stmt->rowCount() > 0) {
            $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            // If there's a foreign key constraint on invoice_id causing problems, drop the table
            if (isset($tableInfo['Create Table']) && 
                (strpos($tableInfo['Create Table'], 'FOREIGN KEY (`invoice_id`)') !== false ||
                 strpos($tableInfo['Create Table'], 'CONSTRAINT `bank_transactions_ibfk_1`') !== false)) {
                // Drop the table so we can recreate it with the correct structure
                $pdo->exec("DROP TABLE IF EXISTS bank_transactions");
            }
        }
    } catch (Exception $e) {
        // Table might not exist yet, that's fine
    }
    
    // Check if student_bank_accounts exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'student_bank_accounts'");
    if ($stmt->rowCount() == 0) {
        // Create table
        $pdo->exec("
            CREATE TABLE student_bank_accounts (
                account_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                account_number VARCHAR(50) NOT NULL,
                balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                currency VARCHAR(3) NOT NULL DEFAULT 'ETB',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                UNIQUE (student_id),
                UNIQUE (account_number)
            )
        ");
    }
    
    // Check if bank_transactions exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'bank_transactions'");
    if ($stmt->rowCount() == 0) {
        // Create table WITHOUT the problematic invoice_id foreign key
        $pdo->exec("
            CREATE TABLE bank_transactions (
                transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                transaction_type ENUM('deposit', 'withdrawal', 'payment', 'refund') NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                description VARCHAR(255) NOT NULL,
                reference_number VARCHAR(50),
                transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'completed',
                notes TEXT,
                FOREIGN KEY (student_id) REFERENCES students(student_id)
            )
        ");
    }
    
    // Check if cost_increase_rates exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'cost_increase_rates'");
    if ($stmt->rowCount() == 0) {
        // Create table
        $pdo->exec("
            CREATE TABLE cost_increase_rates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                year INT NOT NULL,
                increase_percentage DECIMAL(5,2) NOT NULL DEFAULT 5.00,
                notes VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE (year)
            )
        ");
        
        // Insert data for current and next year
        $currentYear = date('Y');
        $pdo->exec("
            INSERT INTO cost_increase_rates (year, increase_percentage)
            VALUES 
                ({$currentYear}, 5.00),
                ({$currentYear} + 1, 6.50)
        ");
    }
    
    // Check if finance_notifications exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'finance_notifications'");
    if ($stmt->rowCount() == 0) {
        // Create table
        $pdo->exec("
            CREATE TABLE finance_notifications (
                notification_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
            )
        ");
    }
}

// Get student ID and information from database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if banking tables exist and create them if they don't
    createBankingTablesIfNeeded($pdo);

    // Fetch student ID, academic level, college, and department
    $stmt = $pdo->prepare("SELECT student_id, academic_level, college, department FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student record not found");
    }

    $student_id = $student['student_id'];
    $is_graduate = ($student['academic_level'] === 'Graduate' || $student['academic_level'] === 'Postgraduate');
    $student_college = $student['college'];
    $student_department = $student['department'];

    // Clean up placeholder/incomplete cost share agreements
    try {
        $stmt = $pdo->prepare("
            DELETE FROM cost_share_agreements 
            WHERE student_id = ? 
            AND (total_cost = 0 OR total_cost IS NULL) 
            AND status = 'pending'
        ");
        $stmt->execute([$student_id]);
    } catch (Exception $e) {
        // Table might not exist yet or field 'status' might not exist, ignore this error
    }

    // Check if student has already filled out cost share agreement
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM cost_share_agreements 
            WHERE student_id = ? 
            AND (status = 'approved' OR status = 'pending')
        ");
        $stmt->execute([$student_id]);
        $existing_agreement = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Table might not exist yet, set existing_agreement to null
        $existing_agreement = null;
    }

    // Get student account balance
    $stmt = $pdo->prepare("
        SELECT sa.account_id, sa.account_number, sa.balance 
        FROM student_bank_accounts sa 
        WHERE sa.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $bank_account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If student doesn't have a bank account, create one with 0 balance
    if (!$bank_account) {
        $account_number = 'STU-' . str_pad($student_id, 6, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("
            INSERT INTO student_bank_accounts (student_id, account_number, balance)
            VALUES (?, ?, 0)
        ");
        $stmt->execute([$student_id, $account_number]);
        
        $account_id = $pdo->lastInsertId();
        
        $bank_account = [
            'account_id' => $account_id,
            'account_number' => $account_number,
            'balance' => 0
        ];
        
        $success_message = "A new bank account has been created for you. Please deposit funds to use for payments.";
    }
    
    $student_balance = $bank_account['balance'];
    $account_number = $bank_account['account_number'];

    // Get cost increase factor for the current year
    try {
        $current_year = date('Y');
        $stmt = $pdo->prepare("
            SELECT increase_percentage 
            FROM cost_increase_rates 
            WHERE year = ? 
            LIMIT 1
        ");
        $stmt->execute([$current_year]);
        $rate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Default to 5% if no rate is defined for current year
        $increase_percentage = $rate ? $rate['increase_percentage'] : 5;
    } catch (Exception $e) {
        // Default value if table doesn't exist
        $increase_percentage = 5;
    }

    // Define base department costs
    $base_department_costs = [
        'College of Engineering and Technology' => [
            'Information Technology' => 60000,
            'Computer Science' => 58000,
            'Cotom' => 55000,
            'Civil Engineering' => 62000
        ],
        'College of Health Science' => [
            'Pharmacy' => 65000,
            'Nurse' => 52000,
            'Health Informatics' => 54000,
            'Mid Wifery' => 53000
        ],
        'College of Social Science' => [
            'Economics' => 48000,
            'Accounting' => 50000,
            'Geography' => 45000,
            'Afan Oromo' => 43000
        ],
        'College of Natural Science' => [
            'Sport Science' => 47000,
            'Biology' => 51000,
            'Physics' => 52000,
            'Chemistry' => 53000
        ]
    ];
    
    // Apply this year's increase to base costs
    $department_costs = [];
    foreach ($base_department_costs as $college => $departments) {
        $department_costs[$college] = [];
        foreach ($departments as $dept => $cost) {
            // Calculate current year cost with increase
            $years_since_base = $current_year - 2023; // Assuming 2023 is the base year
            $compounded_increase = pow(1 + ($increase_percentage / 100), $years_since_base);
            $department_costs[$college][$dept] = round($cost * $compounded_increase);
        }
    }

    // Process banking transactions if submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit_amount'])) {
        $deposit_amount = floatval($_POST['deposit_amount']);
        
        if ($deposit_amount <= 0) {
            $error_message = "Deposit amount must be positive";
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Update student balance
                $stmt = $pdo->prepare("
                    UPDATE student_bank_accounts 
                    SET balance = balance + ?, 
                        updated_at = NOW() 
                    WHERE student_id = ?
                ");
                
                $stmt->execute([$deposit_amount, $student_id]);
                
                // Record the deposit transaction
                $stmt = $pdo->prepare("
                    INSERT INTO bank_transactions (
                        student_id, transaction_type, amount, description, reference_number
                    ) VALUES (
                        ?, 'deposit', ?, 'Deposit to student account', ?
                    )
                ");
                
                $reference = 'DEP-' . date('YmdHis');
                $stmt->execute([$student_id, $deposit_amount, $reference]);
                
                // Commit the transaction
                $pdo->commit();
                
                $success_message = "Successfully deposited " . number_format($deposit_amount, 2) . " ETB to your account.";
                
                // Refresh account data
                $stmt = $pdo->prepare("SELECT balance FROM student_bank_accounts WHERE student_id = ?");
                $stmt->execute([$student_id]);
                $refreshed = $stmt->fetch(PDO::FETCH_ASSOC);
                $student_balance = $refreshed['balance'];
                
                // Prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF'] . "?deposit_success=1");
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction
                $pdo->rollBack();
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_amount'])) {
        $withdraw_amount = floatval($_POST['withdraw_amount']);
        
        if ($withdraw_amount <= 0) {
            $error_message = "Withdrawal amount must be positive";
        } else if ($withdraw_amount > $student_balance) {
            $error_message = "Insufficient funds. Your balance is " . number_format($student_balance, 2) . " ETB";
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Update student balance
                $stmt = $pdo->prepare("
                    UPDATE student_bank_accounts 
                    SET balance = balance - ?, 
                        updated_at = NOW() 
                    WHERE student_id = ?
                ");
                
                $stmt->execute([$withdraw_amount, $student_id]);
                
                // Record the withdrawal transaction
                $stmt = $pdo->prepare("
                    INSERT INTO bank_transactions (
                        student_id, transaction_type, amount, description, reference_number
                    ) VALUES (
                        ?, 'withdrawal', ?, 'Withdrawal from student account', ?
                    )
                ");
                
                $reference = 'WIT-' . date('YmdHis');
                $stmt->execute([$student_id, $withdraw_amount, $reference]);
                
                // Commit the transaction
                $pdo->commit();
                
                $success_message = "Successfully withdrew " . number_format($withdraw_amount, 2) . " ETB from your account.";
                
                // Refresh account data
                $stmt = $pdo->prepare("SELECT balance FROM student_bank_accounts WHERE student_id = ?");
                $stmt->execute([$student_id]);
                $refreshed = $stmt->fetch(PDO::FETCH_ASSOC);
                $student_balance = $refreshed['balance'];
                
                // Prevent form resubmission
                header("Location: " . $_SERVER['PHP_SELF'] . "?withdraw_success=1");
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction
                $pdo->rollBack();
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // Get recent transactions for display
    $stmt = $pdo->prepare("
        SELECT 
            transaction_type, 
            amount, 
            description, 
            transaction_date,
            reference_number
        FROM 
            bank_transactions 
        WHERE 
            student_id = ? 
        ORDER BY 
            transaction_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process form submission for cost share agreement
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_agreement'])) {
        // Get form data
        $withdrawal_date = $_POST['withdrawal_date'];
        $withdrawal_month = $_POST['withdrawal_month'];
        $withdrawal_year = $_POST['withdrawal_year'];
        $withdrawal_semester = $_POST['withdrawal_semester'];
        
        $is_transferred = isset($_POST['is_transferred']) ? $_POST['is_transferred'] : 'no';
        $university_name = $is_transferred === 'yes' ? $_POST['university_name'] : null;
        $university_college = $is_transferred === 'yes' ? $_POST['university_college'] : null;
        $university_department = $is_transferred === 'yes' ? $_POST['university_department'] : null;
        $transfer_date = $is_transferred === 'yes' ? $_POST['transfer_date'] : null;
        $transfer_semester = $is_transferred === 'yes' ? $_POST['transfer_semester'] : null;
        $previous_cost = $is_transferred === 'yes' ? $_POST['previous_cost'] : null;
        
        $service_type = $_POST['service_type'];
        $service_option = $_POST['service_option'];
        $college = $_POST['college'];
        $department = $_POST['department'];
        $total_cost = $_POST['total_cost'];
        
        // Graduate student specific fields
        $graduate_payment_type = $is_graduate ? $_POST['graduate_payment_type'] : null;
        $service_duration = $is_graduate ? $_POST['service_duration'] : null;
        
        // Validation
        $is_valid = true;
        
        // Basic validation
        if (empty($withdrawal_date) || empty($withdrawal_month) || empty($withdrawal_year) || empty($withdrawal_semester)) {
            $is_valid = false;
            $error_message = "Please fill all withdrawal date fields";
        }
        
        if ($is_transferred === 'yes' && (empty($university_name) || empty($transfer_date) || empty($transfer_semester))) {
            $is_valid = false;
            $error_message = "Please fill all transfer information fields";
        }
        
        if (empty($service_type) || empty($service_option)) {
            $is_valid = false;
            $error_message = "Please select service type and option";
        }
        
        if (empty($college) || empty($department)) {
            $is_valid = false;
            $error_message = "Please select your college and department";
        }
        
        if ($is_graduate && (empty($graduate_payment_type) || empty($service_duration))) {
            $is_valid = false;
            $error_message = "Please fill all graduate student specific fields";
        }
        
        // Verify student's college and department
        if ($college != $student_college || $department != $student_department) {
            $is_valid = false;
            $error_message = "The selected college and department don't match your student record";
        }
        //hhhhhhhhhhh



        // If all validations pass, save to database
          if ($is_valid) {
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Current academic year and semester info
            $current_year = date('Y');
            $current_month = date('n');
            $academic_year = ($current_month >= 9) ? $current_year . '/' . ($current_year + 1) : ($current_year - 1) . '/' . $current_year;
            
            // Calculate costs based on department
            $education_cost = $total_cost;
            $food_cost = 0;
            $dormitory_cost = 0;
            
            if ($service_option === 'food_only') {
                $food_cost = 15000;
            } else if ($service_option === 'boarding_only') {
                $dormitory_cost = 8000;
            } else if ($service_option === 'food_and_boarding') {
                $food_cost = 15000;
                $dormitory_cost = 8000;
            }
            
            $total_cost_with_services = $education_cost + $food_cost + $dormitory_cost;



                
                // Create cost_share_agreements table if it doesn't exist
                $stmt = $pdo->query("SHOW TABLES LIKE 'cost_share_agreements'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("
                        CREATE TABLE cost_share_agreements (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            student_id INT NOT NULL,
                            academic_year VARCHAR(10) NOT NULL,
                            withdrawal_date DATE NOT NULL,
                            withdrawal_semester VARCHAR(20) NOT NULL,
                            is_transferred TINYINT(1) DEFAULT 0,
                            transfer_university VARCHAR(100) NULL,
                            transfer_college VARCHAR(100) NULL,
                            transfer_department VARCHAR(100) NULL,
                            transfer_date DATE NULL,
                            transfer_semester VARCHAR(20) NULL,
                            previous_cost DECIMAL(10,2) NULL,
                            service_type ENUM('in_kind', 'in_cash') NOT NULL,
                            service_option ENUM('food_only', 'boarding_only', 'food_and_boarding') NOT NULL,
                            graduate_payment_type ENUM('provide_service', 'pay_income') NULL,
                            service_duration INT NULL,
                            food_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                            dormitory_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                            education_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                            total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                            status VARCHAR(20) NOT NULL DEFAULT 'pending',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (student_id) REFERENCES students(student_id)
                        )
                    ");
                }
                
                // Insert cost share agreement
                  $stmt = $pdo->prepare("
                INSERT INTO cost_share_agreements (
                    student_id, academic_year, withdrawal_date, withdrawal_semester, 
                    is_transferred, transfer_university, transfer_college, transfer_department, 
                    transfer_date, transfer_semester, previous_cost,
                    service_type, service_option, graduate_payment_type, service_duration,
                    food_cost, dormitory_cost, education_cost, total_cost, 
                    status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW()
                )
            ");
            
            $withdrawal_full_date = $withdrawal_year . '-' . $withdrawal_month . '-' . $withdrawal_date;
            
            $stmt->execute([
                $student_id, $academic_year, $withdrawal_full_date, $withdrawal_semester,
                $is_transferred === 'yes' ? 1 : 0, $university_name, $university_college, $university_department,
                $transfer_date, $transfer_semester, $previous_cost,
                $service_type, $service_option, $graduate_payment_type, $service_duration,
                $food_cost, $dormitory_cost, $education_cost, $total_cost_with_services
            ]);
            
            $agreement_id = $pdo->lastInsertId();








                
                // Create invoices table if it doesn't exist
                $stmt = $pdo->query("SHOW TABLES LIKE 'invoices'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("
                        CREATE TABLE invoices (
                            invoice_id INT AUTO_INCREMENT PRIMARY KEY,
                            student_id INT NOT NULL,
                            academic_year VARCHAR(10) NOT NULL,
                            semester VARCHAR(20) NOT NULL,
                            total_amount DECIMAL(10,2) NOT NULL,
                            cost_share_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                            student_responsibility DECIMAL(10,2) NOT NULL,
                            issue_date DATE NOT NULL,
                            due_date DATE NOT NULL,
                            status VARCHAR(20) NOT NULL DEFAULT 'Issued',
                            payment_date DATE NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (student_id) REFERENCES students(student_id)
                        )
                    ");
                }
                
                // Create invoice items table if it doesn't exist
                $stmt = $pdo->query("SHOW TABLES LIKE 'invoice_items'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("
                        CREATE TABLE invoice_items (
                            item_id INT AUTO_INCREMENT PRIMARY KEY,
                            invoice_id INT NOT NULL,
                            description VARCHAR(100) NOT NULL,
                            amount DECIMAL(10,2) NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ");
                }
                




                // Create an invoice for the student
            $current_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+14 days')); // Due in 14 days
            $current_semester = ($current_month >= 1 && $current_month <= 6) ? 'Second Semester' : 'First Semester';
            
            // Calculate student responsibility (initially set to full amount)
            $student_responsibility = $total_cost_with_services;
            
            // Create invoice record
            $stmt = $pdo->prepare("
                INSERT INTO invoices (
                    student_id, academic_year, semester, total_amount, 
                    cost_share_amount, student_responsibility, issue_date, due_date, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, 'Issued'
                )
            ");
            
            $stmt->execute([
                $student_id, 
                $academic_year, 
                $current_semester, 
                $total_cost_with_services, 
                0, // Cost share amount (initially 0, can be updated later)
                $student_responsibility, 
                $current_date, 
                $due_date
            ]);
            
            $invoice_id = $pdo->lastInsertId();
            
            // Create invoice items
            if ($education_cost > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO invoice_items (
                        invoice_id, description, amount
                    ) VALUES (?, ?, ?)
                ");
                $stmt->execute([$invoice_id, 'Education Cost', $education_cost]);
            }
            
            if ($food_cost > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO invoice_items (
                        invoice_id, description, amount
                    ) VALUES (?, ?, ?)
                ");
                $stmt->execute([$invoice_id, 'Food Cost', $food_cost]);
            }
            
            if ($dormitory_cost > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO invoice_items (
                        invoice_id, description, amount
                    ) VALUES (?, ?, ?)
                ");
                $stmt->execute([$invoice_id, 'Dormitory Cost', $dormitory_cost]);
            }

                // Commit transaction
                        $pdo->commit();
            
            // Set success flag
            $submission_success = true;
            $success_message = "Your cost share agreement has been successfully submitted and an invoice has been created.";
            
            // Store agreement ID and invoice ID in session for the payment page
            $_SESSION['agreement_id'] = $agreement_id;
            $_SESSION['invoice_id'] = $invoice_id;
            $_SESSION['cost_share_total'] = $total_cost_with_services;
            
            // Redirect to make_payment.php instead of dashboard
            header("Location: make_payment.php");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction
            $pdo->rollBack();
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Define colleges and departments
$colleges = [
    'College of Engineering and Technology',
    'College of Health Science',
    'College of Social Science',
    'College of Natural Science'
];

$departments = [
    'College of Engineering and Technology' => ['Information Technology', 'Computer Science', 'Cotom', 'Civil Engineering'],
    'College of Health Science' => ['Pharmacy', 'Nurse', 'Health Informatics', 'Mid Wifery'],
    'College of Social Science' => ['Economics', 'Accounting', 'Geography', 'Afan Oromo'],
    'College of Natural Science' => ['Sport Science', 'Biology', 'Physics', 'Chemistry']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Fill Cost Share Agreement</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5D5CDE;
            --primary-dark: #4A49B0;
            --secondary-color: #4CAF50;
            --secondary-dark: #388E3C;
            --warning-color: #FF9800;
            --danger-color: #f44336;
            --danger-dark: #d32f2f;
            --bg-light: #FFFFFF;
            --bg-dark: #181818;
            --text-light: #333333;
            --text-dark: #F5F5F5;
            --error-color: #f44336;
            --success-color: #4CAF50;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: var(--text-light);
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
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .role-badge {
            background-color: var(--primary-color);
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
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .page-title {
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .form-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px 15px;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
            padding: 0 10px;
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .radio-group {
            margin-bottom: 15px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .radio-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .error-message {
            color: var(--error-color);
            background-color: #FFEBEE;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid var(--error-color);
        }
        
        .success-message {
            color: var(--success-color);
            background-color: #E8F5E9;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid var(--success-color);
        }
        
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .submit-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .hidden {
            display: none;
        }
        
        /* Date fields container */
        .date-fields {
            display: flex;
            gap: 10px;
        }
        
        .date-fields select {
            flex: 1;
        }
        
        .info-text {
            color: #666;
            font-size: 14px;
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
            color: var(--primary-color);
        }
        
        .readonly-field {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        /* Banking styles */
        .account-info {
            background-color: #f0f0ff;
            border: 1px solid #d0d0ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .account-number {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .account-balance {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .balance-insufficient {
            color: var(--danger-color);
        }
        
        .banking-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .banking-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .deposit-btn {
            background-color: var(--secondary-color);
        }
        
        .deposit-btn:hover {
            background-color: var(--secondary-dark);
        }
        
        .withdraw-btn {
            background-color: var(--warning-color);
        }
        
        .withdraw-btn:hover {
            background-color: #F57C00;
        }
        
        .banking-form {
            margin-top: 15px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            display: none;
        }
        
        .banking-form.show {
            display: block;
        }
        
        .banking-form-title {
            margin-top: 0;
            font-size: 18px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .banking-form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .banking-form-row input {
            flex: 1;
        }
        
        .transaction-history {
            margin-top: 20px;
        }
        
        .transaction-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .transaction-type {
            font-weight: bold;
        }
        
        .transaction-amount {
            font-weight: bold;
        }
        
        .transaction-amount.deposit {
            color: var(--success-color);
        }
        
        .transaction-amount.withdrawal {
            color: var(--danger-color);
        }
        
        .transaction-date {
            color: #666;
            font-size: 12px;
        }
        
        .transaction-reference {
            color: #666;
            font-size: 12px;
        }
        
        .no-transactions {
            font-style: italic;
            color: #666;
            text-align: center;
            padding: 15px;
        }
        
        .notification {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            background-color: #fff8e1;
            border-left: 4px solid var(--warning-color);
        }
        
        .yearly-increase-info {
            background-color: #f9f9f9;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #555;
        }
        
        .yearly-increase-info strong {
            color: var(--primary-color);
        }
        
        /* Dark mode styles */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: var(--bg-dark);
                color: var(--text-dark);
            }
            
            .header, .form-card {
                background-color: #222;
                color: var(--text-dark);
            }
            
            .logo {
                color: var(--text-dark);
            }
            
            .form-section-title {
                border-bottom-color: #444;
            }
            
            input[type="text"],
            input[type="number"],
            input[type="date"],
            select {
                background-color: #333;
                border-color: #444;
                color: var(--text-dark);
            }
            
            .readonly-field {
                background-color: #2a2a2a;
            }
            
            .error-message {
                background-color: #4a1b1b;
            }
            
            .success-message {
                background-color: #1b4a1b;
            }
            
            .info-text {
                color: #aaa;
            }
            
            .cost-display, .account-info {
                background-color: #2a2a3c;
                border-color: #3a3a4c;
            }
            
            .banking-form {
                background-color: #2a2a2a;
            }
            
            .transaction-item {
                border-bottom-color: #444;
            }
            
            .transaction-date, .transaction-reference {
                color: #aaa;
            }
            
            .yearly-increase-info {
                background-color: #2a2a2a;
                color: #aaa;
            }
            
            .notification {
                background-color: #332d16;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
            }
            
            .date-fields {
                flex-direction: column;
            }
            
            .banking-actions {
                flex-direction: column;
            }
        }
        
        /* Footer styles */
        .site-footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 25px 0;
            text-align: center;
            margin-top: 40px;
            width: 100%;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-content {
            margin-bottom: 15px;
        }
        
        .footer-copyright {
            font-size: 15px;
            margin-bottom: 5px;
        }
        
        .footer-rights {
            font-size: 13px;
            color: #bdc3c7;
        }
        
        .footer-links {
            margin-top: 15px;
        }
        
        .footer-links a {
            color: #5D5CDE;
            margin: 0 10px;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #a0a0ff;
            text-decoration: underline;
        }
        
        /* Dark mode footer adjustment */
        @media (prefers-color-scheme: dark) {
            .site-footer {
                background-color: #1a1a2e;
                border-top: 1px solid #333;
            }
            
            .footer-links a {
                color: #a0a0ff;
            }
            
            .footer-links a:hover {
                color: #d0d0ff;
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
        
        <h1 class="page-title">Fill Cost Share Agreement</h1>
        
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
        
        <?php if (isset($_GET['deposit_success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Successfully deposited funds to your account.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['withdraw_success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Successfully withdrew funds from your account.
            </div>
        <?php endif; ?>
        
        <!-- Student Banking Section -->
        <div class="form-card">
            <h2 class="form-section-title">Your Banking Information</h2>
            
            <div class="account-info">
                <div class="account-number">
                    <i class="fas fa-university"></i> Account Number: <strong><?php echo $account_number; ?></strong>
                </div>
                <div class="account-balance <?php echo $student_balance < 10000 ? 'balance-insufficient' : ''; ?>">
                    <i class="fas fa-wallet"></i> Current Balance: <?php echo number_format($student_balance, 2); ?> ETB
                </div>
                
                <?php if ($student_balance < 10000): ?>
                    <div class="notification">
                        <i class="fas fa-exclamation-triangle"></i> Your balance is low. We recommend depositing funds to cover upcoming payments.
                    </div>
                <?php endif; ?>
                
                <div class="banking-actions">
                    <button class="banking-btn deposit-btn" id="depositBtn">
                        <i class="fas fa-plus-circle"></i> Deposit Funds
                    </button>
                    <button class="banking-btn withdraw-btn" id="withdrawBtn">
                        <i class="fas fa-minus-circle"></i> Withdraw Funds
                    </button>
                </div>
                
                <!-- Deposit Form -->
                <div class="banking-form" id="depositForm">
                    <h3 class="banking-form-title">Deposit Funds</h3>
                    <form method="post" action="">
                        <div class="banking-form-row">
                            <input type="number" name="deposit_amount" placeholder="Enter amount to deposit" min="1" step="0.01" required>
                        </div>
                        <button type="submit" class="submit-btn">Confirm Deposit</button>
                    </form>
                </div>
                
                <!-- Withdraw Form -->
                <div class="banking-form" id="withdrawForm">
                    <h3 class="banking-form-title">Withdraw Funds</h3>
                    <form method="post" action="">
                        <div class="banking-form-row">
                            <input type="number" name="withdraw_amount" placeholder="Enter amount to withdraw" min="1" max="<?php echo $student_balance; ?>" step="0.01" required>
                        </div>
                        <button type="submit" class="submit-btn">Confirm Withdrawal</button>
                    </form>
                </div>
                
                <!-- Recent Transactions -->
                <div class="transaction-history">
                    <div class="transaction-title">Recent Transactions</div>
                    
                    <?php if (empty($recent_transactions)): ?>
                        <div class="no-transactions">No recent transactions</div>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <div class="transaction-item">
                                <div>
                                    <div class="transaction-type">
                                        <?php if ($transaction['transaction_type'] == 'deposit'): ?>
                                            <i class="fas fa-arrow-down" style="color: var(--success-color);"></i> Deposit
                                        <?php elseif ($transaction['transaction_type'] == 'withdrawal'): ?>
                                            <i class="fas fa-arrow-up" style="color: var(--danger-color);"></i> Withdrawal
                                        <?php elseif ($transaction['transaction_type'] == 'payment'): ?>
                                            <i class="fas fa-credit-card" style="color: var(--primary-color);"></i> Payment
                                        <?php else: ?>
                                            <i class="fas fa-exchange-alt"></i> <?php echo ucfirst($transaction['transaction_type']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="transaction-reference"><?php echo $transaction['reference_number']; ?></div>
                                </div>
                                <div>
                                    <div class="transaction-amount <?php echo $transaction['transaction_type']; ?>">
                                        <?php 
                                        if ($transaction['transaction_type'] == 'deposit') {
                                            echo '+ ' . number_format($transaction['amount'], 2) . ' ETB';
                                        } else if ($transaction['transaction_type'] == 'withdrawal' || $transaction['transaction_type'] == 'payment') {
                                            echo '- ' . number_format($transaction['amount'], 2) . ' ETB';
                                        } else {
                                            echo number_format($transaction['amount'], 2) . ' ETB';
                                        }
                                        ?>
                                    </div>
                                    <div class="transaction-date">
                                        <?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (isset($existing_agreement) && $existing_agreement): ?>
            <div class="success-message">
                <i class="fas fa-info-circle"></i> You have already submitted a cost share agreement.
                <p>You can view your cost share details in the "View Cost Share" section.</p>
            </div>
        <?php else: ?>
            <div class="form-card">
                <div class="yearly-increase-info">
                    <i class="fas fa-info-circle"></i> Please note: Cost share amounts are adjusted annually. The current year's increase rate is <strong><?php echo $increase_percentage; ?>%</strong> over base costs.
                </div>
                
                <form method="post" action="" id="costShareForm">
                    <!-- Withdrawal Date Information -->
                    <div class="form-section">
                        <h2 class="form-section-title">Date of Withdrawal</h2>
                        <div class="form-row">
                            <div class="form-group date-fields">
                                <div>
                                    <label for="withdrawal_date">Day</label>
                                    <select id="withdrawal_date" name="withdrawal_date" required>
                                        <option value="">Select Day</option>
                                        <?php for ($i = 1; $i <= 31; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="withdrawal_month">Month</label>
                                    <select id="withdrawal_month" name="withdrawal_month" required>
                                        <option value="">Select Month</option>
                                        <?php 
                                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                                'July', 'August', 'September', 'October', 'November', 'December'];
                                        for ($i = 0; $i < 12; $i++): ?>
                                            <option value="<?php echo $i + 1; ?>"><?php echo $months[$i]; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="withdrawal_year">Year</label>
                                    <select id="withdrawal_year" name="withdrawal_year" required>
                                        <option value="">Select Year</option>
                                        <?php 
                                        $current_year = date('Y');
                                        for ($i = $current_year - 5; $i <= $current_year; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="withdrawal_semester">Semester</label>
                                <select id="withdrawal_semester" name="withdrawal_semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="First Semester">First Semester</option>
                                    <option value="Second Semester">Second Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- College and Department Information -->
                    <div class="form-section">
                        <h2 class="form-section-title">College and Department Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="college">College</label>
                                <select id="college" name="college" required>
                                    <option value="">Select College</option>
                                    <?php foreach ($colleges as $college): ?>
                                        <option value="<?php echo $college; ?>" <?php echo ($student_college === $college) ? 'selected' : ''; ?>>
                                            <?php echo $college; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <?php if ($student_college && isset($departments[$student_college])): ?>
                                        <?php foreach ($departments[$student_college] as $dept): ?>
                                            <option value="<?php echo $dept; ?>" <?php echo ($student_department === $dept) ? 'selected' : ''; ?>>
                                                <?php echo $dept; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="total_cost">Total Cost (ETB)</label>
                                <input type="number" id="total_cost" name="total_cost" class="readonly-field" readonly>
                                <p class="info-text">This is the base education cost for your department with the annual increase applied</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transfer Information -->
                    <div class="form-section">
                        <h2 class="form-section-title">Transfer Information</h2>
                        
                        <div class="radio-group">
                            <label>Are you transferred from another university?</label>
                            <div class="radio-option">
                                <input type="radio" id="transfer_yes" name="is_transferred" value="yes">
                                <label for="transfer_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="transfer_no" name="is_transferred" value="no" checked>
                                <label for="transfer_no">No</label>
                            </div>
                        </div>
                        
                        <div id="transfer_details" class="hidden">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="university_name">University Name</label>
                                    <input type="text" id="university_name" name="university_name">
                                </div>
                                <div class="form-group">
                                    <label for="university_college">College/Faculty</label>
                                    <input type="text" id="university_college" name="university_college">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="university_department">Department</label>
                                    <input type="text" id="university_department" name="university_department">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="transfer_date">Date of Transfer</label>
                                    <input type="date" id="transfer_date" name="transfer_date">
                                </div>
                                <div class="form-group">
                                    <label for="transfer_semester">Transfer Semester</label>
                                    <select id="transfer_semester" name="transfer_semester">
                                        <option value="">Select Semester</option>
                                        <option value="First Semester">First Semester</option>
                                        <option value="Second Semester">Second Semester</option>
                                        <option value="Summer">Summer</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="previous_cost">Total Cost Before Transfer (ETB)</label>
                                    <input type="number" id="previous_cost" name="previous_cost" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service Type -->
                    <div class="form-section">
                        <h2 class="form-section-title">Type of Service</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Service Type</label>
                                <div class="radio-option">
                                    <input type="radio" id="in_kind" name="service_type" value="in_kind" required>
                                    <label for="in_kind">In Kind</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="in_cash" name="service_type" value="in_cash">
                                    <label for="in_cash">In Cash</label>
                                </div>
                                <p class="info-text">In Kind means receiving services directly. In Cash means paying the equivalent monetary value.</p>
                            </div>
                            
                            <div class="form-group">
                                <label>Service Option</label>
                                <div class="radio-option">
                                    <input type="radio" id="food_only" name="service_option" value="food_only" required>
                                    <label for="food_only">Food Only</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="boarding_only" name="service_option" value="boarding_only">
                                    <label for="boarding_only">Boarding Only</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="food_and_boarding" name="service_option" value="food_and_boarding">
                                    <label for="food_and_boarding">Food and Boarding</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($is_graduate): ?>
                    <!-- Graduate Student Section -->
                    <div class="form-section">
                        <h2 class="form-section-title">Graduate Student Information</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Type of Payment</label>
                                <div class="radio-option">
                                    <input type="radio" id="provide_service" name="graduate_payment_type" value="provide_service" required>
                                    <label for="provide_service">To Provide Service</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="pay_income" name="graduate_payment_type" value="pay_income">
                                    <label for="pay_income">To Be Paid From My Income</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="service_duration">Duration of Service (Years)</label>
                                <select id="service_duration" name="service_duration" required>
                                    <option value="">Select Duration</option>
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> Year<?php echo $i > 1 ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Cost Summary Section -->
                    <div class="form-section">
                        <h2 class="form-section-title">Cost Summary</h2>
                        
                        <div class="cost-display" id="costSummary">
                            <p>Base Education Cost: <span id="baseCost">0</span> ETB</p>
                            <p>Food Cost: <span id="foodCost">0</span> ETB</p>
                            <p>Dormitory Cost: <span id="dormitoryCost">0</span> ETB</p>
                            <p class="cost-total">Total Cost: <span id="totalCostDisplay">0</span> ETB</p>
                        </div>
                        
                        <div id="balanceCheck" class="notification" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Your current balance is insufficient to cover this cost. Please deposit funds before proceeding.
                            <button class="banking-btn deposit-btn" id="quickDepositBtn" style="margin-top: 10px;">
                                <i class="fas fa-plus-circle"></i> Deposit Funds
                            </button>
                        </div>
                        
                        <p>By submitting this form, I acknowledge that I understand the terms and conditions of the cost sharing program and agree to fulfill my obligations as specified.</p>
                        <button type="submit" name="submit_agreement" id="submitBtn" class="submit-btn">Proceed to Payment</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-copyright">
                    &copy; <?php echo date('Y'); ?> MaU Online Student Cost Sharing Management System
                </div>
                <div class="footer-rights">
                    All rights reserved
                </div>
            </div>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Us</a>
            </div>
        </div>
    </footer>

   <script>
    // Department costs data from PHP
    const departmentCosts = <?php echo json_encode($department_costs); ?>;
    
    // College-Department relationship
    const departments = <?php echo json_encode($departments); ?>;
    
    // Current student balance
    let studentBalance = <?php echo $student_balance; ?>;
    
    // Current year for cost calculations
    const currentYear = <?php echo date('Y'); ?>;
    const baseYear = 2023; // The year when base costs were set
    const yearlyIncreaseRate = <?php echo $increase_percentage; ?> / 100;
    
    // Show/hide banking forms
    document.getElementById('depositBtn').addEventListener('click', function() {
        const depositForm = document.getElementById('depositForm');
        const withdrawForm = document.getElementById('withdrawForm');
        
        depositForm.classList.toggle('show');
        withdrawForm.classList.remove('show');
    });
    
    document.getElementById('withdrawBtn').addEventListener('click', function() {
        const depositForm = document.getElementById('depositForm');
        const withdrawForm = document.getElementById('withdrawForm');
        
        withdrawForm.classList.toggle('show');
        depositForm.classList.remove('show');
    });
    
    // Quick deposit button in insufficient balance warning
    if (document.getElementById('quickDepositBtn')) {
        document.getElementById('quickDepositBtn').addEventListener('click', function() {
            const depositForm = document.getElementById('depositForm');
            depositForm.classList.add('show');
            
            // Scroll to deposit form
            depositForm.scrollIntoView({ behavior: 'smooth' });
        });
    }
    
    // Show/hide transfer details based on selection
    document.querySelectorAll('input[name="is_transferred"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const transferDetails = document.getElementById('transfer_details');
            
            if (this.value === 'yes') {
                transferDetails.classList.remove('hidden');
                // Make fields required
                document.getElementById('university_name').required = true;
                document.getElementById('transfer_date').required = true;
                document.getElementById('transfer_semester').required = true;
            } else {
                transferDetails.classList.add('hidden');
                // Remove required attribute
                document.getElementById('university_name').required = false;
                document.getElementById('transfer_date').required = false;
                document.getElementById('transfer_semester').required = false;
            }
        });
    });
    
    // Update department options based on college selection
    const collegeElement = document.getElementById('college');
    if (collegeElement) {
        collegeElement.addEventListener('change', function() {
            const collegeValue = this.value;
            const departmentSelect = document.getElementById('department');
            
            // Clear current options
            departmentSelect.innerHTML = '<option value="">Select Department</option>';
            
            // Add new options based on selected college
            if (collegeValue && departments[collegeValue]) {
                departments[collegeValue].forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept;
                    option.textContent = dept;
                    departmentSelect.appendChild(option);
                });
            }
            
            // Clear the cost since department changed
            document.getElementById('total_cost').value = '';
            updateCostSummary();
        });
    }
    
    // Update cost based on department selection
    const departmentElement = document.getElementById('department');
    if (departmentElement) {
        departmentElement.addEventListener('change', function() {
            const collegeValue = document.getElementById('college').value;
            const departmentValue = this.value;
            
            // Set the cost if department is selected
            if (collegeValue && departmentValue && departmentCosts[collegeValue] && departmentCosts[collegeValue][departmentValue]) {
                const cost = departmentCosts[collegeValue][departmentValue];
                document.getElementById('total_cost').value = cost;
                updateCostSummary();
            } else {
                document.getElementById('total_cost').value = '';
                updateCostSummary();
            }
        });
    }
    
    // Update cost summary when service options change
    const serviceOptions = document.querySelectorAll('input[name="service_option"]');
    if (serviceOptions.length > 0) {
        serviceOptions.forEach(radio => {
            radio.addEventListener('change', updateCostSummary);
        });
    }
    
    // Function to update cost summary
    function updateCostSummary() {
        const totalCostElement = document.getElementById('total_cost');
        if (!totalCostElement) return;
        
        const baseCost = parseFloat(totalCostElement.value) || 0;
        let foodCost = 0;
        let dormitoryCost = 0;
        
        // Get selected service option
        const serviceOption = document.querySelector('input[name="service_option"]:checked')?.value;
        
        if (serviceOption === 'food_only') {
            foodCost = 15000;
        } else if (serviceOption === 'boarding_only') {
            dormitoryCost = 8000;
        } else if (serviceOption === 'food_and_boarding') {
            foodCost = 15000;
            dormitoryCost = 8000;
        }
        
        const totalCost = baseCost + foodCost + dormitoryCost;
        
        // Check if student balance is sufficient
        const balanceCheckElement = document.getElementById('balanceCheck');
        const submitBtnElement = document.getElementById('submitBtn');
        
        if (balanceCheckElement && submitBtnElement) {
            if (totalCost > studentBalance) {
                balanceCheckElement.style.display = 'block';
                // We don't disable the button anymore since payment happens later
                // submitBtnElement.disabled = true;
                // submitBtnElement.classList.add('btn-disabled');
            } else {
                balanceCheckElement.style.display = 'none';
                // Remove disabled state
                submitBtnElement.disabled = false;
                submitBtnElement.classList.remove('btn-disabled');
            }
        }
        
        // Update display
        const baseCostElement = document.getElementById('baseCost');
        if (baseCostElement) {
            baseCostElement.textContent = baseCost.toLocaleString();
            document.getElementById('foodCost').textContent = foodCost.toLocaleString();
            document.getElementById('dormitoryCost').textContent = dormitoryCost.toLocaleString();
            document.getElementById('totalCostDisplay').textContent = totalCost.toLocaleString();
        }
    }
    
    // Initial cost update
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial department value if we have a cost share form
        const costShareForm = document.getElementById('costShareForm');
        if (costShareForm) {
            const collegeSelect = document.getElementById('college');
            const departmentSelect = document.getElementById('department');
            
            if (collegeSelect.value) {
                // Trigger department cost calculation
                if (departmentSelect.value) {
                    const collegeValue = collegeSelect.value;
                    const departmentValue = departmentSelect.value;
                    
                    if (departmentCosts[collegeValue] && departmentCosts[collegeValue][departmentValue]) {
                        document.getElementById('total_cost').value = departmentCosts[collegeValue][departmentValue];
                        updateCostSummary();
                    }
                }
            }
            
            // Set initial service type and option
            document.getElementById('in_kind').checked = true;
            document.getElementById('food_only').checked = true;
            
            // Update cost display
            updateCostSummary();
        }
    });
</script>
</body>
</html>