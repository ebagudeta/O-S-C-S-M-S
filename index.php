<?php
// Start the session
session_start();

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (blank)

// Initialize error message
$error_message = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $input_username = $_POST['username'] ?? '';
    $input_password = $_POST['password'] ?? '';
    $selected_role = $_POST['role'] ?? '';
    
    // Validate inputs
    if (empty($input_username) || empty($input_password) || empty($selected_role)) {
        $error_message = 'Please fill in all fields';
    } else {
        try {
            // Create database connection
            $conn = new mysqli($host, $username, $password, $dbname);
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            // Query both tables
            $stmt = $conn->prepare("
                SELECT u.user_id, u.username, u.password_hash AS password, u.first_name AS name, 
                       r.role_name AS role, u.email 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.username = ? AND u.is_active = 1
                UNION
                SELECT s.user_id, s.username, s.password, s.name, s.role, s.email 
                FROM system_users s 
                WHERE s.username = ? AND s.status = 'active'
            ");
            
            if (!$stmt) {
                throw new Exception("Query preparation failed: " . $conn->error);
            }
            
            $stmt->bind_param("ss", $input_username, $input_username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Check if user exists 
            if (!$user) {
                $error_message = 'Invalid username or password';
            } 
            // Check password
            else if (!password_verify($input_password, $user['password'])) {
                $error_message = 'Invalid username or password';
            } 
            // Check if the user's role matches the selected role
            else if (strtolower($user['role']) != strtolower($selected_role)) {
                $error_message = 'You do not have permission to access the system as ' . $selected_role;
            } 
            // Authentication successful
            else {
                // Store user data in session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                // Redirect based on role
                switch (strtolower($user['role'])) {
                    case 'student':
                    case 'registrar':
                        header("Location: dashboard.php");
                        break;
                    case 'finance_officer':
                        header("Location: finance_dashboard.php");
                        break;
                    case 'cost_sharing_officer':
                        header("Location: dashboard.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                exit;
            }
            
            // Close connection
            $conn->close();
            
        } catch (Exception $e) {
            $error_message = 'Login error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaUOCSMS - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
            width: 350px;
            text-align: center;
        }
        .login-header {
            margin-bottom: 30px;
        }
        .login-title {
            color: #5D5CDE;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .login-btn {
            background-color: #5D5CDE;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
        }
        .login-btn:hover {
            background-color: #4A49B0;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: left;
            border-left: 4px solid #c62828;
        }
        .system-name {
            color: #666;
            font-size: 14px;
            margin-top: 20px;
        }
        .logo-icon {
            color: #5D5CDE;
            font-size: 36px;
            margin-bottom: 10px;
        }
        .forgot-password {
            margin-top: 15px;
            display: block;
            color: #5D5CDE;
            text-decoration: none;
            font-size: 14px;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-icon">
                            <img src="logo.png" alt="Description of the image" width="180" height="110">

            </div>
            <h1 class="login-title">Login to MaUOCSMS</h1>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Login As</label>
                <select id="role" name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="registrar" <?php echo (isset($_POST['role']) && $_POST['role'] == 'registrar') ? 'selected' : ''; ?>>Registrar</option>
                    <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="finance_officer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'finance_officer') ? 'selected' : ''; ?>>Finance Officer</option>
                    <option value="cost_sharing_officer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'cost_sharing_officer') ? 'selected' : ''; ?>>Cost Sharing Officer</option>
                </select>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
        
        <div class="system-name">
           Mattu University Online Student Cost Sharing Management System
        </div>
    </div>
</body>
</html>