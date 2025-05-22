<?php
// Start session
session_start();

// Database connection parameters for XAMPP
$host = 'localhost';
$dbname = 'ocsms';
$db_username = 'root'; // Default XAMPP username
$db_password = '';     // Default XAMPP password (blank)

// Get user input
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

// Validate input
if (empty($username) || empty($password) || empty($role)) {
    $_SESSION['login_error'] = 'Please fill in all fields';
    header("Location: index.php");
    exit;
}

// Create MySQLi connection
$conn = new mysqli($host, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    $_SESSION['login_error'] = "Connection failed: " . $conn->connect_error;
    header("Location: index.php");
    exit;
}

try {
    // First, verify the user exists and get their role
    $stmt = $conn->prepare("SELECT u.user_id, u.username, u.password_hash, u.first_name, u.last_name, 
                           r.role_id, r.role_name 
                           FROM users u
                           JOIN roles r ON u.role_id = r.role_id
                           WHERE u.username = ? AND u.is_active = TRUE");
    
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if user exists
    if (!$user) {
        $_SESSION['login_error'] = 'Invalid username or password';
        header("Location: index.php");
        exit;
    }
    
    // Verify password
    // For proper security, you should use password_verify() with properly hashed passwords
    // If your passwords are stored with MD5 or another method, adjust accordingly
    if (password_verify($password, $user['password_hash'])) {
        // Password is correct, now check if the role matches
        
        // Get the role ID for the selected role
        $stmt = $conn->prepare("SELECT role_id FROM roles WHERE role_name = ?");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $role_data = $result->fetch_assoc();
        $stmt->close();
        
        if (!$role_data) {
            $_SESSION['login_error'] = 'Invalid role selected';
            header("Location: index.php");
            exit;
        }
        
        // Check if user's role matches the selected role
        if ($user['role_id'] != $role_data['role_id']) {
            $_SESSION['login_error'] = 'You do not have permission to access the system as ' . ucfirst($role);
            header("Location: index.php");
            exit;
        }
        
      // Authentication successful - store user data in session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['role'] = $user['role_name'];

// Role-based redirects
switch ($_SESSION['role']) {
    case 'finance_officer':
        header("Location: finance_dashboard.php");
        break;
    case 'registrar':
        header("Location: dashboard.php"); // Or registrar_dashboard.php if you have one
        break;
    case 'cost_sharing_officer':
        header("Location: dashboard.php"); // Or cost_sharing_dashboard.php if you have one
        break;
    case 'student':
        header("Location: dashboard.php"); // Or student_dashboard.php if you have one
        break;
    default:
        header("Location: dashboard.php");
}
exit;
    } else {
        // Password is incorrect
        $_SESSION['login_error'] = 'Invalid username or password';
        header("Location: index.php");
        exit;
    }
    
} catch (Exception $e) {
    $_SESSION['login_error'] = 'Error: ' . $e->getMessage();
    header("Location: index.php");
    exit;
}

// Close the connection
$conn->close();
?>
      