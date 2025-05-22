<?php
// Start session
session_start();

// Database connection parameters for XAMPP
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (blank)

$message = '';
$message_type = '';

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to validate password strength
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset_password'])) {
        $username = trim($_POST['username']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        try {
            // Validate inputs
            if (empty($username) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("All fields are required");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("Passwords do not match");
            }
            
            // Validate password strength
            $password_errors = validatePasswordStrength($new_password);
            if (!empty($password_errors)) {
                throw new Exception("Password is not strong enough: " . implode(", ", $password_errors));
            }
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Update password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->bind_param("si", $password_hash, $user['user_id']);
            $stmt->execute();
            $stmt->close();
            
            $message = "Password has been reset successfully. You can now log in with your new password.";
            $message_type = "success";
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message_type = "error";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .reset-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 400px;
        }
        h1 {
            text-align: center;
            color: #5D5CDE;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #5D5CDE;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        input[type="submit"]:hover {
            background-color: #4A49B0;
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
        .system-name {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
        }
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
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        .login-link a {
            color: #5D5CDE;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h1>Reset Password</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" id="resetForm" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
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
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <div id="password-match" class="password-requirements"></div>
            </div>
            
            <input type="submit" name="reset_password" value="Reset Password">
        </form>
        
        <div class="login-link">
            <a href="index.php">Back to Login</a>
        </div>
        
        <div class="system-name">
            Online Course/Student Management System
        </div>
    </div>
    
    <script>
        // Password strength validation
        function checkPasswordStrength(password) {
            // Get elements
            const strengthMeter = document.getElementById('passwordStrength');
            const reqLength = document.getElementById('req-length');
            const reqUppercase = document.getElementById('req-uppercase');
            const reqLowercase = document.getElementById('req-lowercase');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');
            
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
        
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const resultElement = document.getElementById('password-match');
            
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
        
        // Setup event listeners
        const passwordField = document.getElementById('new_password');
        const confirmPasswordField = document.getElementById('confirm_password');
        
        passwordField.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            if (confirmPasswordField.value) {
                checkPasswordMatch();
            }
        });
        
        confirmPasswordField.addEventListener('input', checkPasswordMatch);
        
        // Form validation
        function validateForm() {
            const password = document.getElementById('new_password').value;
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
    </script>
</body>
</html>