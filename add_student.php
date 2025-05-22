<?php
session_start();

// Check if user is logged in and is an admin/registrar
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'registrar')) {
    header("Location: index.php");
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root';
$password = '';

// Initialize variables
$error_message = '';
$success_message = '';
$colleges = [];
$departments = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create bank tables if they don't exist
    createBankTablesIfNotExist($pdo);
    
    // Fetch colleges
    $stmt = $pdo->prepare("SELECT * FROM colleges ORDER BY college_name");
    $stmt->execute();
    $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch departments
    if (!empty($colleges)) {
        $stmt = $pdo->prepare("SELECT * FROM departments ORDER BY department_name");
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_student'])) {
        // Get form data
        $username = $_POST['username'];
        $email = $_POST['email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $password = $_POST['password']; // In production, you'd want to validate this
        $student_number = $_POST['student_number'];
        $academic_level = $_POST['academic_level'];
        $college = $_POST['college'];
        $department = $_POST['department'];
        $admission_year = $_POST['admission_year'];
        $gender = $_POST['gender'];
        $phone_number = isset($_POST['phone_number']) ? $_POST['phone_number'] : null;
        $date_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        
        // Validate input
        $is_valid = true;
        
        // Basic validation
        if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || 
            empty($password) || empty($student_number) || empty($academic_level) || 
            empty($college) || empty($department) || empty($admission_year)) {
            $is_valid = false;
            $error_message = "All required fields must be filled out.";
        }
        
        // Check if username or email already exists in system_users table
        if ($is_valid) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $is_valid = false;
                $error_message = "Username or email already exists.";
            }
        }
        
        // Check if student number already exists
        if ($is_valid) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_number = ?");
            $stmt->execute([$student_number]);
            if ($stmt->fetchColumn() > 0) {
                $is_valid = false;
                $error_message = "Student number already exists.";
            }
        }
        
        // If all validations pass, create the student
        if ($is_valid) {
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Combine first and last name for system_users table
                $full_name = $first_name . ' ' . $last_name;
                
                // 1. Create user record in system_users table
                $stmt = $pdo->prepare("
                    INSERT INTO system_users (
                        username, password, name, email, role, status, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, 'student', 'active', NOW(), NOW()
                    )
                ");
                
                $stmt->execute([$username, $password_hash, $full_name, $email]);
                $user_id = $pdo->lastInsertId();
                
                // 2. Create student record
                $enrollment_date = date('Y-m-d'); // Current date
                
                $stmt = $pdo->prepare("
                    INSERT INTO students (
                        user_id, student_number, academic_level, college, department,
                        admission_year, gender, date_of_birth, phone_number, enrollment_date
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                
                $stmt->execute([
                    $user_id, $student_number, $academic_level, $college, $department,
                    $admission_year, $gender, $date_of_birth, $phone_number, $enrollment_date
                ]);
                
                $student_id = $pdo->lastInsertId();
                
                // 3. Create student bank account with initial balance
                $account_number = 'STU-' . str_pad($student_id, 6, '0', STR_PAD_LEFT);
                $initial_balance = rand(1000, 100000); // Random balance between 1,000 and 100,000 ETB
                
                $stmt = $pdo->prepare("
                    INSERT INTO student_bank_accounts (
                        student_id, account_number, balance
                    ) VALUES (
                        ?, ?, ?
                    )
                ");
                
                $stmt->execute([$student_id, $account_number, $initial_balance]);
                
// 4. Create default entry in cost_share_agreements table
$current_year = date('Y');
$current_month = date('n');
$academic_year = ($current_month >= 9) ? $current_year . '/' . ($current_year + 1) : ($current_year - 1) . '/' . $current_year;

// Initialize costs
$education_cost = 0; // Set default value
$food_cost = 0;      // Set default value
$dormitory_cost = 0; // Set default value
$total_cost = $education_cost + $food_cost + $dormitory_cost; // Calculate total cost

// Insert initial cost share agreement
$stmt = $pdo->prepare("
    INSERT INTO cost_share_agreements (
        student_id, academic_year, withdrawal_semester,
        is_transferred, service_type, service_option,
        food_cost, dormitory_cost, education_cost, total_cost,
        created_at, status
    ) VALUES (
        ?, ?, NULL, 0, 'in_kind', 'food_only', ?, ?, ?, ?, NOW(), 'pending'
    )
");

$stmt->execute([
    $student_id, $academic_year, $food_cost, $dormitory_cost, $education_cost, $total_cost
]);        
                // 5. Log the action
                $stmt = $pdo->prepare("
                    INSERT INTO system_logs (
                        user_id, action_type, entity_type, entity_id, details, ip_address
                    ) VALUES (
                        ?, 'create', 'student', ?, ?, ?
                    )
                ");
                
                $log_details = "New student registration: $first_name $last_name ($student_number)";
                $ip_address = $_SERVER['REMOTE_ADDR'];
                
                $stmt->execute([$_SESSION['user_id'], $student_id, $log_details, $ip_address]);
                
                // Commit transaction
                $pdo->commit();
                
                // Success message
                $success_message = "Student successfully added. ";
                
                // Clear form data if successful
                $_POST = [];
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                $error_message = "Error adding student: " . $e->getMessage();
            }
        }
    }
    
} catch (Exception $e) {
    $error_message = "System error: " . $e->getMessage();
}

/**
 * Creates the bank-related tables if they don't exist
 */
function createBankTablesIfNotExist($pdo) {
    // Check if student_bank_accounts exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'student_bank_accounts'");
    if ($stmt->rowCount() == 0) {
        // Create table
        $pdo->exec("
            CREATE TABLE student_bank_accounts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                student_id INT NOT NULL,
                account_number VARCHAR(50) NOT NULL,
                balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id)
            )
        ");
    }
    
    // Check and create cost_share_agreements table if it doesn't exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'cost_share_agreements'");
    if ($stmt->rowCount() == 0) {
        // Create table
        $pdo->exec("
            CREATE TABLE cost_share_agreements (
                agreement_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                academic_year VARCHAR(9) NOT NULL,
                withdrawal_date DATE,
                withdrawal_semester VARCHAR(20),
                is_transferred BOOLEAN NOT NULL DEFAULT 0,
                transfer_university VARCHAR(100),
                transfer_college VARCHAR(100),
                transfer_department VARCHAR(100),
                transfer_date DATE,
                transfer_semester VARCHAR(20),
                previous_cost DECIMAL(10, 2),
                service_type ENUM('in_kind', 'in_cash') NOT NULL,
                service_option ENUM('food_only', 'boarding_only', 'food_and_boarding') NOT NULL,
                graduate_payment_type ENUM('provide_service', 'pay_income'),
                service_duration INT,
                food_cost DECIMAL(10, 2) NOT NULL DEFAULT 0,
                dormitory_cost DECIMAL(10, 2) NOT NULL DEFAULT 0,
                education_cost DECIMAL(10, 2) NOT NULL DEFAULT 0,
                total_cost DECIMAL(10, 2) NOT NULL DEFAULT 0,
                status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                approved_by INT,
                approved_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
            )
        ");
    }
    
    // Check if university_bank_account exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'university_bank_account'");
    if ($stmt->rowCount() == 0) {
        // Create table
        $pdo->exec("
            CREATE TABLE university_bank_account (
                id INT PRIMARY KEY AUTO_INCREMENT,
                account_number VARCHAR(50) NOT NULL,
                balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    }
    
    // Check if bank_transactions exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'bank_transactions'");
    if ($stmt->rowCount() == 0) {
        // Create table
        $pdo->exec("
            CREATE TABLE bank_transactions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                student_id INT NOT NULL,
                invoice_id INT,
                amount DECIMAL(10,2) NOT NULL,
                transaction_type ENUM('deposit', 'withdrawal', 'payment', 'refund') NOT NULL,
                description VARCHAR(255) NOT NULL,
                reference_number VARCHAR(50),
                transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'completed',
                FOREIGN KEY (student_id) REFERENCES students(student_id)
            )
        ");
    }
}
?>

<!DOCTYPE html>
<!-- Rest of the HTML code remains unchanged -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Add Student</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5D5CDE;
            --primary-dark: #4A49B0;
            --bg-light: #FFFFFF;
            --bg-dark: #181818;
            --text-light: #333333;
            --text-dark: #F5F5F5;
            --error-color: #f44336;
            --success-color: #4CAF50;
            --warning-color: #FFC107;
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
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(93, 92, 222, 0.1);
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
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        /* Form validation styles */
        .is-invalid {
            border-color: var(--error-color) !important;
            background-color: rgba(244, 67, 54, 0.05) !important;
        }
        
        .is-valid {
            border-color: var(--success-color) !important;
        }
        
        .error-text {
            color: var(--error-color);
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
       
       
    /* Password strength meter styles */
    .password-strength-meter {
        margin-top: 5px;
        height: 5px;
        width: 100%;
        background-color: #ddd;
        border-radius: 3px;
    }
    
    .password-strength-meter-fill {
        height: 100%;
        width: 0%;
        transition: width 0.3s ease;
        border-radius: 3px;
    }
    
    .strength-weak {
        background-color: #f44336;
        width: 25%;
    }
    
    .strength-fair {
        background-color: #ff9800;
        width: 50%;
    }
    
    .strength-good {
        background-color: #2196f3;
        width: 75%;
    }
    
    .strength-strong {
        background-color: #4caf50;
        width: 100%;
    }
    
    .password-requirements {
        font-size: 12px;
        margin-top: 8px;
        color: #666;
    }
    
    .requirement {
        margin-bottom: 3px;
    }
    
    .requirement i {
        margin-right: 5px;
    }
    
    .requirement.met {
        color: #4caf50;
    }
    
    .requirement.unmet {
        color: #f44336;
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
            <span class="role-badge"><?php echo ucfirst($_SESSION['role']); ?></span>
            <span><?php echo $name; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">Add New Student</h1>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif;?>
        
        <form method="post" action="" class="form-card" id="studentForm" novalidate>


<!-- Personal Information -->
            <div class="form-section">
                <h2 class="form-section-title">Personal Information</h2>
                
                <div class="form-row">
                    <div class="form-group required-field">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                        <span id="first_name-error" class="error-text"></span>
                    </div>
                    
                    <div class="form-group required-field">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                        <span id="last_name-error" class="error-text"></span>
                    </div>
                </div>
            
                <div class="form-row">
                    <div class="form-group required-field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <span id="gender-error" class="error-text"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                        <span id="date_of_birth-error" class="error-text"></span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>" placeholder="e.g. +251912345678">
                        <span id="phone_number-error" class="error-text"></span>
                    </div>
                </div>
            </div>


            <!-- Account Information -->
            <div class="form-section">
                <h2 class="form-section-title">Account Information</h2>
                
                <div class="form-row">
                    <div class="form-group required-field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        <span id="username-error" class="error-text"></span>
                    </div>
                    
                    <div class="form-group required-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <span id="email-error" class="error-text"></span>
                    </div>
                </div>
                
                <div class="form-row">
                   <div class="form-col">
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <div class="password-strength-meter">
                <div class="password-strength-meter-fill" id="passwordStrength"></div>
            </div>
            <div class="password-requirements">
                <div class="requirement unmet" id="req-length"><i class="fas fa-times-circle"></i> At least 8 characters long</div>
                <div class="requirement unmet" id="req-uppercase"><i class="fas fa-times-circle"></i> Contains uppercase letter</div>
                <div class="requirement unmet" id="req-lowercase"><i class="fas fa-times-circle"></i> Contains lowercase letter</div>
                <div class="requirement unmet" id="req-number"><i class="fas fa-times-circle"></i> Contains number</div>
                <div class="requirement unmet" id="req-special"><i class="fas fa-times-circle"></i> Contains special character</div>
            </div>
        </div>
    </div>


  <div class="form-col">
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <div id="password-match" class="password-requirements"></div>
        </div>
    </div>


                </div>
            </div>
            
            
            
            <!-- Academic Information -->
            <div class="form-section">
                <h2 class="form-section-title">Academic Information</h2>
                
                <div class="form-row">
                    <div class="form-group required-field">
                        <label for="student_number">Student Number</label>
                        <input type="text" id="student_number" name="student_number" value="<?php echo isset($_POST['student_number']) ? htmlspecialchars($_POST['student_number']) : ''; ?>" required>
                        <span id="student_number-error" class="error-text"></span>
                    </div>
                    
                    <div class="form-group required-field">
                        <label for="academic_level">Academic Level</label>
                        <select id="academic_level" name="academic_level" required>
                            <option value="">Select Academic Level</option>
                            <option value="Undergraduate" <?php echo (isset($_POST['academic_level']) && $_POST['academic_level'] === 'Undergraduate') ? 'selected' : ''; ?>>Undergraduate</option>
                            <option value="Graduate" <?php echo (isset($_POST['academic_level']) && $_POST['academic_level'] === 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
                            <option value="Postgraduate" <?php echo (isset($_POST['academic_level']) && $_POST['academic_level'] === 'Postgraduate') ? 'selected' : ''; ?>>Postgraduate</option>
                        </select>
                        <span id="academic_level-error" class="error-text"></span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group required-field">
                        <label for="college">College</label>
                        <select id="college" name="college" required>
                            <option value="">Select College</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo htmlspecialchars($college['college_name']); ?>" <?php echo (isset($_POST['college']) && $_POST['college'] === $college['college_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($college['college_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span id="college-error" class="error-text"></span>
                    </div>
                    
                    <div class="form-group required-field">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department['department_name']); ?>" data-college="<?php echo htmlspecialchars($department['college_id']); ?>" <?php echo (isset($_POST['department']) && $_POST['department'] === $department['department_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($department['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span id="department-error" class="error-text"></span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group required-field">
                        <label for="admission_year">Admission Year</label>
                        <select id="admission_year" name="admission_year" required>
                            <option value="">Select Year</option>
                            <?php 
                            $current_year = date('Y');
                            for ($i = $current_year - 5; $i <= $current_year; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($_POST['admission_year']) && $_POST['admission_year'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <span id="admission_year-error" class="error-text"></span>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="submit_student" class="submit-btn">Add Student</button>
        </form>
    </div>

    <script>
        // Store validation rules and error messages
        const validationRules = {
            username: {
                required: true,
                minLength: 4,
                pattern: /^[a-zA-Z0-9._@-]+$/,
                message: "Username must be at least 4 characters and contain only letters, numbers, and ._@-"
            },
            email: {
                required: true,
                pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                message: "Please enter a valid email address"
            },
            password: {
                required: true,
                minLength: 8,
                // Password must contain at least one uppercase letter, one lowercase letter, one number
                pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/,
                message: "Password must be at least 8 characters with at least one uppercase letter, one lowercase letter, and one number"
            },
            confirm_password: {
                required: true,
                match: 'password',
                message: "Passwords don't match"
            },
            first_name: {
                required: true,
                pattern: /^[a-zA-Z\s'-]+$/,
                message: "First name should contain only letters, spaces, hyphens and apostrophes"
            },
            last_name: {
                required: true,
                pattern: /^[a-zA-Z\s'-]+$/,
                message: "Last name should contain only letters, spaces, hyphens and apostrophes"
            },
            phone_number: {
                pattern: /^\+?[0-9]{10,15}$/,
                message: "Phone number should be 10-15 digits, optionally starting with +"
            },
            student_number: {
                required: true,
                pattern: /^[A-Za-z0-9\/-]+$/,
                message: "Student number should contain only letters, numbers, slashes, or hyphens"
            },
            date_of_birth: {
                validateAge: true,
                message: "Student must be between 16 and 60 years old"
            },
            gender: {
                required: true,
                message: "Please select a gender"
            },
            academic_level: {
                required: true,
                message: "Please select an academic level"
            },
            college: {
                required: true,
                message: "Please select a college"
            },
            department: {
                required: true,
                message: "Please select a department"
            },
            admission_year: {
                required: true,
                message: "Please select an admission year"
            }
        };

        // Function to validate a specific field
        function validateField(field) {
            const rules = validationRules[field.id];
            if (!rules) return true; // No rules for this field
            
            let isValid = true;
            const errorSpan = document.getElementById(`${field.id}-error`);
            
            // Clear previous error
            if (errorSpan) {
                errorSpan.textContent = "";
                errorSpan.style.display = "none";
            }
            
            field.classList.remove('is-invalid', 'is-valid');
            
            // Don't validate empty optional fields
            if (!rules.required && field.value.trim() === "") {
                return true;
            }
            
            // Required check
            if (rules.required && field.value.trim() === "") {
                isValid = false;
                showError(field, "This field is required");
                return isValid;
            }
            
            // Minimum length check
            if (rules.minLength && field.value.length < rules.minLength) {
                isValid = false;
                showError(field, `Must be at least ${rules.minLength} characters`);
                return isValid;
            }
            
            // Pattern check
            if (rules.pattern && !rules.pattern.test(field.value)) {
                isValid = false;
                showError(field, rules.message);
                return isValid;
            }
            
            // Date of birth (age) check
            if (rules.validateAge && field.value) {
                const dob = new Date(field.value);
                const today = new Date();
                const age = today.getFullYear() - dob.getFullYear();
                
                // Adjust age if birthday hasn't occurred yet this year
                const monthDiff = today.getMonth() - dob.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                if (age < 16 || age > 60) {
                    isValid = false;
                    showError(field, rules.message);
                    return isValid;
                }
            }
            
            // Match another field (e.g., password confirmation)
            if (rules.match) {
                const matchField = document.getElementById(rules.match);
                if (field.value !== matchField.value) {
                    isValid = false;
                    showError(field, rules.message);
                    return isValid;
                }
            }
            
            // Mark field as valid
            if (isValid) {
                field.classList.add('is-valid');
            }
            
            return isValid;
        }
        
        // Function to display error message
        function showError(field, message) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            
            // Create error element if it doesn't exist
            let errorSpan = document.getElementById(`${field.id}-error`);
            if (!errorSpan) {
                errorSpan = document.createElement('span');
                errorSpan.id = `${field.id}-error`;
                errorSpan.className = 'error-text';
                field.parentNode.appendChild(errorSpan);
            }
            
            errorSpan.textContent = message;
            errorSpan.style.display = "block";
        }
        
        // Function to validate the entire form
        function validateForm() {
            let isValid = true;
            
            // Validate all fields with rules
            Object.keys(validationRules).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    const fieldIsValid = validateField(field);
                    isValid = isValid && fieldIsValid;
                }
            });
            
            return isValid;
        }

        // Password strength indicator
        function updatePasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength');
            
            // Remove previous classes
            strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
            
            if (password.length === 0) {
                strengthBar.style.display = 'none';
                return;
            }
            
            strengthBar.style.display = 'block';
            
            // Calculate strength
            let strength = 0;
            if (password.length >= 8) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Update indicator
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }

        // Function to filter departments based on selected college
        function filterDepartments() {
            const collegeSelect = document.getElementById('college');
            const departmentSelect = document.getElementById('department');
            const departmentOptions = departmentSelect.querySelectorAll('option');
            const selectedCollege = collegeSelect.value;
            
            // Get the college ID for the selected college name
            let selectedCollegeId = null;
            <?php foreach ($colleges as $college): ?>
                if ('<?php echo $college['college_name']; ?>' === selectedCollege) {
                    selectedCollegeId = '<?php echo $college['college_id']; ?>';
                }
            <?php endforeach; ?>
            
            // Show only departments from the selected college
            let hasVisibleOptions = false;
            for (let i = 0; i < departmentOptions.length; i++) {
                const option = departmentOptions[i];
                
                if (option.value === '') {
                    // Always show the default option
                    option.style.display = '';
                } else if (!selectedCollegeId || option.getAttribute('data-college') === selectedCollegeId) {
                    option.style.display = '';
                    hasVisibleOptions = true;
                } else {
                    option.style.display = 'none';
                }
            }
            
            // Reset department selection if the currently selected one is now hidden
            if (departmentSelect.selectedIndex > 0 &&
                departmentSelect.options[departmentSelect.selectedIndex].style.display === 'none') {
                departmentSelect.value = '';
            }
            
            // Validate department selection
            validateField(departmentSelect);
        }
        
        // Initialize event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Form submission
            const form = document.getElementById('studentForm');
            form.addEventListener('submit', function(event) {
                // Prevent form submission and validate
                if (!validateForm()) {
                    event.preventDefault();
                    
                    // Scroll to the first error
                    const firstError = document.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
            
            // Set up field validation on blur
            Object.keys(validationRules).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('blur', function() {
                        validateField(this);
                    });
                }
            });
            
            // Password strength indicator
            const passwordField = document.getElementById('password');
            passwordField.addEventListener('input', function() {
                updatePasswordStrength(this.value);
                // Also validate confirm password if it has a value
                const confirmPasswordField = document.getElementById('confirm_password');
                if (confirmPasswordField.value) {
                    validateField(confirmPasswordField);
                }
            });
            
            // Confirm password validation
            const confirmPasswordField = document.getElementById('confirm_password');
            confirmPasswordField.addEventListener('input', function() {
                validateField(this);
            });
            
            // College-Department filtering
            document.getElementById('college').addEventListener('change', filterDepartments);
            
            // Initial filtering
            filterDepartments();
        });
    </script>

</body>
<script>
    // Password strength validation
    function checkPasswordStrength(password, prefix = '') {
        // Get elements
        const strengthMeter = document.getElementById(prefix ? `${prefix}PasswordStrength` : 'passwordStrength');
        const reqLength = document.getElementById(`${prefix}req-length`);
        const reqUppercase = document.getElementById(`${prefix}req-uppercase`);
        const reqLowercase = document.getElementById(`${prefix}req-lowercase`);
        const reqNumber = document.getElementById(`${prefix}req-number`);
        const reqSpecial = document.getElementById(`${prefix}req-special`);
        
        if (!strengthMeter || !reqLength) return; // Elements not found
        
        // Reset classes
        strengthMeter.className = 'password-strength-meter-fill';
        
        if (!password) {
            strengthMeter.style.width = '0%';
            updateRequirement(reqLength, false);
            updateRequirement(reqUppercase, false);
            updateRequirement(reqLowercase, false);
            updateRequirement(reqNumber, false);
            updateRequirement(reqSpecial, false);
            return 0;
        }
        
        // Check requirements
        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[^A-Za-z0-9]/.test(password);
        
        updateRequirement(reqLength, hasLength);
        updateRequirement(reqUppercase, hasUppercase);
        updateRequirement(reqLowercase, hasLowercase);
        updateRequirement(reqNumber, hasNumber);
        updateRequirement(reqSpecial, hasSpecial);
        
        // Calculate strength
        let strength = 0;
        if (hasLength) strength++;
        if (hasUppercase) strength++;
        if (hasLowercase) strength++;
        if (hasNumber) strength++;
        if (hasSpecial) strength++;
        
        // Update visual indicator
        if (strength < 2) {
            strengthMeter.classList.add('strength-weak');
        } else if (strength < 4) {
            strengthMeter.classList.add('strength-fair');
        } else if (strength < 5) {
            strengthMeter.classList.add('strength-good');
        } else {
            strengthMeter.classList.add('strength-strong');
        }
        
        return strength;
    }
    
    function updateRequirement(element, isMet) {
        if (element) {
            if (isMet) {
                element.classList.remove('unmet');
                element.classList.add('met');
                element.querySelector('i').className = 'fas fa-check-circle';
            } else {
                element.classList.remove('met');
                element.classList.add('unmet');
                element.querySelector('i').className = 'fas fa-times-circle';
            }
        }
    }
    
    function checkPasswordMatch(passwordField, confirmField, resultElement) {
        const password = passwordField.value;
        const confirmPassword = confirmField.value;
        
        if (!confirmPassword) {
            resultElement.innerHTML = '';
            return false;
        }
        
        if (password === confirmPassword) {
            resultElement.innerHTML = '<div class="requirement met"><i class="fas fa-check-circle"></i> Passwords match</div>';
            return true;
        } else {
            resultElement.innerHTML = '<div class="requirement unmet"><i class="fas fa-times-circle"></i> Passwords do not match</div>';
            return false;
        }
    }
    
    // Setup event listeners for create user form
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const passwordMatchResult = document.getElementById('password-match');
    
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            if (confirmPasswordField.value) {
                checkPasswordMatch(passwordField, confirmPasswordField, passwordMatchResult);
            }
        });
    }
    
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', function() {
            checkPasswordMatch(passwordField, confirmPasswordField, passwordMatchResult);
        });
    }
    
    // Setup event listeners for edit user form
    const editPasswordField = document.getElementById('edit_password');
    const editConfirmPasswordField = document.getElementById('edit_confirm_password');
    const editPasswordMatchResult = document.getElementById('edit-password-match');
    
    if (editPasswordField) {
        editPasswordField.addEventListener('input', function() {
            checkPasswordStrength(this.value, 'edit-');
            if (editConfirmPasswordField.value) {
                checkPasswordMatch(editPasswordField, editConfirmPasswordField, editPasswordMatchResult);
            }
        });
    }
    
    if (editConfirmPasswordField) {
        editConfirmPasswordField.addEventListener('input', function() {
            checkPasswordMatch(editPasswordField, editConfirmPasswordField, editPasswordMatchResult);
        });
    }
    
    // Enhance form validation
    function validateCreateForm() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return false;
        }
        
        const strength = checkPasswordStrength(password);
        if (strength < 3) {
            alert('Password does not meet minimum strength requirements.');
            return false;
        }
        
        return true;
    }
    
    function validateEditForm() {
        const password = document.getElementById('edit_password').value;
        const confirmPassword = document.getElementById('edit_confirm_password').value;
        
        if (password !== '' && password !== confirmPassword) {
            alert('Passwords do not match');
            return false;
        }
        
        if (password !== '') {
            const strength = checkPasswordStrength(password, 'edit-');
            if (strength < 3) {
                alert('Password does not meet minimum strength requirements.');
                return false;
            }
        }
        
        return true;
    }
</script>
</html>