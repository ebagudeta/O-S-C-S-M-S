<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has the correct role
if ($_SESSION['role'] !== 'cost_sharing_officer') {
    header("Location: dashboard.php");
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$db_username = 'root';
$db_password = '';

// Initialize variables
$success_message = '';
$error_message = '';
$departments = [];

// Get all departments from the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM departments ORDER BY department_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get form data
        $department_id = $_POST['department_id'];
        $academic_year = $_POST['academic_year'];
        $semester = $_POST['semester'];
        $tuition_fee = $_POST['tuition_fee'];
        $food_expense = $_POST['food_expense'];
        $dormitory_expense = $_POST['dormitory_expense'];
        $registration_fee = $_POST['registration_fee'];
        $other_expenses = $_POST['other_expenses'];
        
        // Calculate total cost
        $total_cost = $tuition_fee + $food_expense + $dormitory_expense + $registration_fee + $other_expenses;
        
        // Check if a record already exists for this department, academic year and semester
        $stmt = $pdo->prepare("SELECT * FROM cost_share WHERE department_id = ? AND academic_year = ? AND semester = ?");
        $stmt->execute([$department_id, $academic_year, $semester]);
        
        if ($stmt->rowCount() > 0) {
            $error_message = "Cost share information already exists for this department, academic year and semester.";
        } else {
            // Insert new cost share record
            $stmt = $pdo->prepare("
                INSERT INTO cost_share (department_id, academic_year, semester, tuition_fee, food_expense, 
                dormitory_expense, registration_fee, other_expenses, total_cost, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $department_id, $academic_year, $semester, $tuition_fee, $food_expense, 
                $dormitory_expense, $registration_fee, $other_expenses, $total_cost, $user_id
            ]);
            
            $success_message = "Cost share information has been successfully registered.";
        }
        
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Cost Share - MaUOSCSMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .register-cost-container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .form-heading {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .form-col {
            flex: 1;
            padding: 0 10px;
            min-width: 200px;
        }
        
        .btn-primary {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        .btn-primary:hover {
            background-color: #4a49b8;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        
        .total-cost {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            border-left: 4px solid #5D5CDE;
        }
        
        .cost-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .cost-section h3 {
            margin-bottom: 15px;
            color: #444;
        }
        
        @media (max-width: 768px) {
            .form-col {
                flex: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="MaUOSCSMS Logo" width="80" height="70">
            <h1>MaUOSCSMS</h1>
        </div>
        <div class="user-info">
            <span class="user-role"><?php echo ucfirst($role); ?></span>
            <span><?php echo $name; ?></span>
            <a href="dashboard.php" class="dashboard-btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="register-cost-container">
        <div class="form-card">
            <h2 class="form-heading">
                <i class="fas fa-plus-circle"></i> Register New Cost Share
            </h2>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" id="costShareForm">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="department_id">Department</label>
                            <select class="form-control" id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['department_id']; ?>">
                                        <?php echo htmlspecialchars($department['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="academic_year">Academic Year</label>
                            <select class="form-control" id="academic_year" name="academic_year" required>
                                <option value="">Select Academic Year</option>
                                <?php 
                                $currentYear = date('Y');
                                for ($i = 0; $i < 5; $i++) {
                                    $year = $currentYear - $i;
                                    $academicYear = $year . '/' . ($year + 1);
                                    echo "<option value=\"$academicYear\">$academicYear</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="semester">Semester</label>
                            <select class="form-control" id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1">First Semester</option>
                                <option value="2">Second Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="cost-section">
                    <h3><i class="fas fa-money-bill-wave"></i> Cost Details</h3>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="tuition_fee">Tuition Fee (ETB)</label>
                                <input type="number" class="form-control cost-input" id="tuition_fee" name="tuition_fee" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="food_expense">Food Expense (ETB)</label>
                                <input type="number" class="form-control cost-input" id="food_expense" name="food_expense" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="dormitory_expense">Dormitory Expense (ETB)</label>
                                <input type="number" class="form-control cost-input" id="dormitory_expense" name="dormitory_expense" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="registration_fee">Registration Fee (ETB)</label>
                                <input type="number" class="form-control cost-input" id="registration_fee" name="registration_fee" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="other_expenses">Other Expenses (ETB)</label>
                        <input type="number" class="form-control cost-input" id="other_expenses" name="other_expenses" min="0" step="0.01" required>
                    </div>
                    
                    <div class="total-cost" id="totalCostDisplay">
                        <i class="fas fa-calculator"></i> Total Cost: 0.00 ETB
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 25px; text-align: center;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Register Cost Share
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="site-footer" style="margin-top: 60px;">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-copyright">
                    &copy; <?php echo date('Y'); ?> MaU Online Student Cost Sharing Management System by IT GC g5
                </div>
                <div class="footer-rights">
                    All rights reserved
                </div>
            </div>
           <div class="footer-links">
            <a href="privacy_policy.php">Privacy Policy</a>
            <a href="terms_of_service.php">Terms of Service</a>
            <a href="contact_us.php">Contact Us</a>
        </div>
        </div>
    </footer>

    <script>
        // Calculate total cost whenever any cost input changes
        document.querySelectorAll('.cost-input').forEach(input => {
            input.addEventListener('input', calculateTotalCost);
        });
        
        function calculateTotalCost() {
            const tuitionFee = parseFloat(document.getElementById('tuition_fee').value) || 0;
            const foodExpense = parseFloat(document.getElementById('food_expense').value) || 0;
            const dormitoryExpense = parseFloat(document.getElementById('dormitory_expense').value) || 0;
            const registrationFee = parseFloat(document.getElementById('registration_fee').value) || 0;
            const otherExpenses = parseFloat(document.getElementById('other_expenses').value) || 0;
            
            const totalCost = tuitionFee + foodExpense + dormitoryExpense + registrationFee + otherExpenses;
            
            document.getElementById('totalCostDisplay').innerHTML = `<i class="fas fa-calculator"></i> Total Cost: ${totalCost.toFixed(2)} ETB`;
        }
        
        // Initialize with zeros
        calculateTotalCost();
    </script>
</body>
</html>