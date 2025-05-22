<?php
// Start session
session_start();

// Check if user is logged in and is a registrar
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
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
$edit_user = null;

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add password strength validation function here
function validatePasswordStrength($password) {
    $errors = [];
    
    // Check length
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    // Check for lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    // Check for number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    // Check for special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

// Safely execute a query, handling errors without stopping script
function safeQuery($conn, $query, $params = [], $types = '') {
    try {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (Exception $e) {
        // Just return false on failure, don't throw
        return false;
    }
}

try {
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create new user
        if (isset($_POST['create_user'])) {
            $username = trim($_POST['username']);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $role_id = $_POST['role_id'];
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate inputs
            if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
                throw new Exception("All fields are required");
            }
            
            if ($password !== $confirm_password) {
                throw new Exception("Passwords do not match");
            }
            
            // Validate password strength
            $password_errors = validatePasswordStrength($password);
            if (!empty($password_errors)) {
                throw new Exception("Password is not strong enough: " . implode(", ", $password_errors));
            }
            
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception("Username or email already exists");
            }
            $stmt->close();
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, first_name, last_name, role_id) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $username, $password_hash, $email, $first_name, $last_name, $role_id);
            $stmt->execute();
            $stmt->close();
            
            $message = "User created successfully";
            $message_type = "success";
        }
        
        // Update existing user
        if (isset($_POST['update_user'])) {
            $user_id = $_POST['user_id'];
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $role_id = $_POST['role_id'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Validate inputs
            if (empty($first_name) || empty($last_name) || empty($email)) {
                throw new Exception("Name and email are required");
            }
            
            // Update user info
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role_id = ?, is_active = ? WHERE user_id = ?");
            $stmt->bind_param("sssiii", $first_name, $last_name, $email, $role_id, $is_active, $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Update password if provided
            if (!empty($_POST['password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    throw new Exception("Passwords do not match");
                }
                
                // Validate password strength
                $password_errors = validatePasswordStrength($_POST['password']);
                if (!empty($password_errors)) {
                    throw new Exception("Password is not strong enough: " . implode(", ", $password_errors));
                }
                
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("si", $password_hash, $user_id);
                $stmt->execute();
                $stmt->close();
            }
            
            $message = "User updated successfully";
            $message_type = "success";
        }
        
        // Delete user
        if (isset($_POST['delete_user'])) {
            $user_id = $_POST['user_id'];
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("User not found");
            }
            $stmt->close();
            
            // Check if user is not the current user
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account");
            }
            
            // Start a transaction to ensure data integrity
            $conn->begin_transaction();
            
            try {
                // Get student IDs associated with this user
                $student_ids = [];
                $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $student_ids[] = $row['student_id'];
                }
                $stmt->close();
                
                // Delete enrollments first for all these students
                if (!empty($student_ids)) {
                    foreach ($student_ids as $student_id) {
                        safeQuery($conn, "DELETE FROM enrollments WHERE student_id = ?", [$student_id], "i");
                    }
                }
                
                // Now, systematically delete records from all related tables in the proper order
                
                // 1. Student-related tables: Must be deleted before students
                foreach ($student_ids as $student_id) {
                    // Student payments
                    safeQuery($conn, "DELETE FROM payments WHERE student_id = ?", [$student_id], "i");
                    
                    // Student cost sharing
                    safeQuery($conn, "DELETE FROM student_cost_share WHERE student_id = ?", [$student_id], "i");
                    
                    // Any other student-related tables
                    safeQuery($conn, "DELETE FROM student_grades WHERE student_id = ?", [$student_id], "i");
                    safeQuery($conn, "DELETE FROM attendance WHERE student_id = ?", [$student_id], "i");
                }
                
                // 2. Now delete student records
                safeQuery($conn, "DELETE FROM students WHERE user_id = ?", [$user_id], "i");
                
                // 3. User-related tables
                // Student cost share approvals
                safeQuery($conn, "DELETE FROM student_cost_share WHERE approved_by = ?", [$user_id], "i");
                
                // Generated reports
                safeQuery($conn, "DELETE FROM generated_reports WHERE generated_by = ?", [$user_id], "i");
                
                // Feedback
                safeQuery($conn, "DELETE FROM feedback WHERE user_id = ?", [$user_id], "i");
                
                // System logs
                safeQuery($conn, "DELETE FROM system_logs WHERE user_id = ?", [$user_id], "i");
                
                // 4. Check for any other tables with potential foreign keys to user_id
                $tables_result = $conn->query("SHOW TABLES");
                if ($tables_result) {
                    while ($table_row = $tables_result->fetch_row()) {
                        $table_name = $table_row[0];
                        
                        // Skip the users table itself
                        if ($table_name === 'users') continue;
                        
                        // Check if this table has columns that might reference user_id
                        $columns_result = $conn->query("DESCRIBE `$table_name`");
                        if ($columns_result) {
                            while ($column = $columns_result->fetch_assoc()) {
                                $column_name = $column['Field'];
                                
                                // Look for columns that might be references to users table
                                if (in_array($column_name, ['user_id', 'created_by', 'updated_by', 'approved_by', 'owner_id', 'generated_by'])) {
                                    safeQuery($conn, "DELETE FROM `$table_name` WHERE `$column_name` = ?", [$user_id], "i");
                                }
                            }
                        }
                    }
                }
                
                // 5. Finally delete the user
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
                
                // Commit the transaction
                $conn->commit();
                
                $message = "User deleted successfully";
                $message_type = "success";
            } catch (Exception $e) {
                // Rollback the transaction if any error occurs
                $conn->rollback();
                throw new Exception("Error deleting user: " . $e->getMessage());
            }
        }
    }
    
    // Load user for editing if requested
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $edit_id = $_GET['edit'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_user = $result->fetch_assoc();
        $stmt->close();
    }
    
    // Get all roles
    $roles = [
    ['role_id' => 2, 'role_name' => 'student'],
    ['role_id' => 3, 'role_name' => 'finance_officer'],
    ['role_id' => 4, 'role_name' => 'cost_sharing_officer'],
];
  
    
    // Get all users with role information
    $users = [];
    $result = $conn->query("SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, 
                        r.role_name, u.is_active, u.created_at
                        FROM users u
                        JOIN roles r ON u.role_id = r.role_id
                        ORDER BY u.last_name, u.first_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
} catch (Exception $e) {
    $message = $e->getMessage();
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Manage Accounts</title>
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
        .message {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        .message.error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
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
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #5D5CDE;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background-color: #4A49B0;
        }
        .btn-edit {
            background-color: #2196f3;
            color: white;
            border: none;
        }
        .btn-edit:hover {
            background-color: #0b7dda;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
            border: none;
        }
        .btn-delete:hover {
            background-color: #d32f2f;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-col {
            flex: 1;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .checkbox-group label {
            margin: 0 0 0 8px;
            font-weight: normal;
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
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .inactive {
            background-color: #ffebee;
            color: #c62828;
        }
        .actions {
            white-space: nowrap;
        }
        .btn-icon {
            padding: 6px 10px;
            margin-right: 5px;
        }
        .search-bar {
            display: flex;
            margin-bottom: 20px;
        }
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        .search-btn {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 0 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        .filter-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .filter-options select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .modal-title {
            font-size: 18px;
            font-weight: bold;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #888;
        }
        .tab-container {
            margin-bottom: 20px;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #eee;
        }
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .tab.active {
            border-bottom-color: #5D5CDE;
            color: #5D5CDE;
            font-weight: bold;
        }
        .tab-content {
            padding-top: 15px;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
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
            <span class="role-badge">Registrar</span>
            <span><?php echo $_SESSION['name']; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">Manage Accounts</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" data-tab="users-list">User List</div>
                    <div class="tab" data-tab="create-user">Create New User</div>
                    <?php if ($edit_user): ?>
                        <div class="tab" data-tab="edit-user">Edit User</div>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content">
                    <!-- User List Tab -->
                    <div class="tab-pane active" id="users-list">
                        <div class="search-bar">
                            <input type="text" id="searchInput" class="search-input" placeholder="Search by name, username, or email...">
                            <button class="search-btn"><i class="fas fa-search"></i></button>
                        </div>
                        
                        <div class="filter-options">
                            <select id="roleFilter">
                                <option value="">All Roles</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_name']; ?>"><?php echo ucfirst($role['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select id="statusFilter">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <table id="usersTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($users) && is_array($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                                            <td><?php echo $user['username']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td><?php echo ucfirst($user['role_name']); ?></td>
                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="status-badge active">Active</span>
                                                <?php else: ?>
                                                    <span class="status-badge inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td class="actions">
                                                <a href="?edit=<?php echo $user['user_id']; ?>" class="btn btn-edit btn-icon">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn btn-delete btn-icon" onclick="confirmDelete(<?php echo $user['user_id']; ?>, '<?php echo $user['username']; ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Create User Tab -->
                    <div class="tab-pane" id="create-user">
                        <h3>Create New User</h3>
                        <form method="post" id="createUserForm" onsubmit="return validateCreateForm()">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" id="username" name="username" required>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="role_id">Role</label>
                                          <select name="role_id" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['role_id']; ?>"><?php echo ucfirst($role['role_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input type="text" id="first_name" name="first_name" required>
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group required-field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <span id="email-error" class="error-text"></span>
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
                            
                            <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                        </form>
                    </div>
                    
                    <!-- Edit User Tab -->
                    <?php if ($edit_user): ?>
                        <div class="tab-pane" id="edit-user">
                            <h3>Edit User: <?php echo $edit_user['username']; ?></h3>
                            <form method="post" id="editUserForm" onsubmit="return validateEditForm()">
                                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="edit_username">Username</label>
                                            <input type="text" id="edit_username" value="<?php echo $edit_user['username']; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="edit_role_id">Role</label>
                                            <select id="edit_role_id" name="role_id" required>
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?php echo $role['role_id']; ?>"
                                                     <?php echo ($role['role_id'] == $edit_user['role_id']) ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($role['role_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="edit_first_name">First Name</label>
                                            <input type="text" id="edit_first_name" name="first_name" value="<?php echo $edit_user['first_name']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="edit_last_name">Last Name</label>
                                            <input type="text" id="edit_last_name" name="last_name" value="<?php echo $edit_user['last_name']; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="edit_email">Email</label>
                                    <input type="email" id="edit_email" name="email" value="<?php echo $edit_user['email']; ?>" required>
                                </div>
                                
                             <div class="form-row">
    <div class="form-col">
        <div class="form-group">
            <label for="edit_password">Password (leave blank to keep current)</label>
            <input type="password" id="edit_password" name="password">
            <div class="password-strength-meter">
                <div class="password-strength-meter-fill" id="editPasswordStrength"></div>
            </div>
            <div class="password-requirements">
                <div class="requirement" id="edit-req-length"><i class="fas fa-times-circle"></i> At least 8 characters long</div>
                <div class="requirement" id="edit-req-uppercase"><i class="fas fa-times-circle"></i> Contains uppercase letter</div>
                <div class="requirement" id="edit-req-lowercase"><i class="fas fa-times-circle"></i> Contains lowercase letter</div>
                <div class="requirement" id="edit-req-number"><i class="fas fa-times-circle"></i> Contains number</div>
                <div class="requirement" id="edit-req-special"><i class="fas fa-times-circle"></i> Contains special character</div>
            </div>
        </div>
    </div>


    <div class="form-col">
        <div class="form-group">
            <label for="edit_confirm_password">Confirm Password</label>
            <input type="password" id="edit_confirm_password" name="confirm_password">
            <div id="edit-password-match" class="password-requirements"></div>
        </div>
    </div>
</div>
                                
                                <div class="checkbox-group">
                                    <input type="checkbox" id="is_active" name="is_active" <?php echo $edit_user['is_active'] ? 'checked' : ''; ?>>
                                    <label for="is_active">Active Account</label>
                                </div>
                                
                                <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Confirm Delete</div>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <p>Are you sure you want to delete the user <span id="deleteUsername"></span>?</p>
            <p style="color: #c62828;">This action cannot be undone.</p>
            <p style="color: #c62828;"><strong>Warning:</strong> This will also delete all associated data including:</p>
            <ul style="color: #c62828;">
                <li>Student enrollments</li>
                <li>Student records</li>
                <li>Cost sharing records</li>
                <li>Generated reports</li>
                <li>Feedback and other records</li>
            </ul>
            <form method="post" id="deleteForm">
                <input type="hidden" id="deleteUserId" name="user_id">
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" style="background-color: #e0e0e0;" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-delete">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and panes
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding pane
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        // Make edit tab active if editing
        <?php if ($edit_user): ?>
            document.querySelector('.tab[data-tab="edit-user"]').click();
        <?php endif; ?>
        
        // Form validation
        function validateCreateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
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
            
            return true;
        }
        
        // Delete modal functionality
        function confirmDelete(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');
        const table = document.getElementById('usersTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const roleValue = roleFilter.value.toLowerCase();
            const statusValue = statusFilter.value.toLowerCase();
            
            for (let i = 0; i < rows.length; i++) {
                const name = rows[i].cells[0].textContent.toLowerCase();
                const username = rows[i].cells[1].textContent.toLowerCase();
                const email = rows[i].cells[2].textContent.toLowerCase();
                const role = rows[i].cells[3].textContent.toLowerCase();
                const status = rows[i].cells[4].textContent.toLowerCase();
                
                const matchesSearch = name.includes(searchTerm) || 
                                     username.includes(searchTerm) || 
                                     email.includes(searchTerm);
                                     
                const matchesRole = roleValue === '' || role.includes(roleValue);
                const matchesStatus = statusValue === '' || status.includes(statusValue);
                
                if (matchesSearch && matchesRole && matchesStatus) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
        
        searchInput.addEventListener('keyup', filterTable);
        roleFilter.addEventListener('change', filterTable);
        statusFilter.addEventListener('change', filterTable);
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