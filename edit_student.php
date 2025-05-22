<?php
// Start session
session_start();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'registrar')) {
    header("Location: login.php");
    exit;
}

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root';
$password = '';

// Initialize variables
$error_message = '';
$success_message = '';
$student = null;
$colleges = [];
$departments = [];
$programs = [];

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if student ID is provided
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid student ID");
    }
    
    $student_id = $_GET['id'];
    
    // Get all colleges for dropdown
    $stmt = $pdo->query("SELECT college_id, college_name FROM colleges ORDER BY college_name");
    $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all departments for dropdown
    $stmt = $pdo->query("SELECT department_id, department_name, college_id FROM departments ORDER BY department_name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all programs for dropdown
    $stmt = $pdo->query("SELECT program_id, program_name, department_id FROM programs ORDER BY program_name");
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student details
    $stmt = $pdo->prepare("
        SELECT s.*, u.first_name, u.last_name, u.email, u.username
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception("Student not found");
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Update user information
            $stmt = $pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ? 
                WHERE user_id = ?
            ");
            $stmt->execute([
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $student['user_id']
            ]);
            
            // Update student information
            $stmt = $pdo->prepare("
                UPDATE students 
                SET student_number = ?, 
                    academic_level = ?, 
                    program_id = ?, 
                    department = ?, 
                    college = ?,
                    gender = ?,
                    date_of_birth = ?,
                    phone_number = ?,
                    academic_status = ?
                WHERE student_id = ?
            ");
            $stmt->execute([
                $_POST['student_number'],
                $_POST['academic_level'],
                $_POST['program_id'],
                $_POST['department'],
                $_POST['college'],
                $_POST['gender'],
                $_POST['date_of_birth'],
                $_POST['phone_number'],
                $_POST['academic_status'],
                $student_id
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (
                    user_id, action_type, entity_type, entity_id, details, ip_address
                ) VALUES (?, 'update', 'student', ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $student_id,
                "Updated student: " . $_POST['first_name'] . " " . $_POST['last_name'] . " (" . $_POST['student_number'] . ")",
                $_SERVER['REMOTE_ADDR']
            ]);
            
            $success_message = "Student information updated successfully";
            
            // Refresh student data after update
            $stmt = $pdo->prepare("
                SELECT s.*, u.first_name, u.last_name, u.email, u.username
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.student_id = ?
            ");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Roll back transaction on error
            $pdo->rollBack();
            $error_message = "Error updating student: " . $e->getMessage();
        }
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Edit Student</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
                --light-color: #2c2c2c;
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
        
        /* Header styles */
        .header {
            background-color: var(--card-bg);
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
            color: var(--text-color);
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
            gap: 15px;
        }
        
        .role-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        /* Main container */
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        .page-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }
        
        /* Card styles */
        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--light-color);
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            color: var(--primary-color);
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Form styles */
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .form-group {
            margin-bottom: 20px;
            padding: 0 10px;
            flex: 1 0 calc(50% - 20px);
        }
        
        .form-group.full-width {
            flex: 1 0 calc(100% - 20px);
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="date"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--gray-light);
            border-radius: 4px;
            background-color: var(--card-bg);
            color: var(--text-color);
            font-size: 16px;
            box-sizing: border-box;
        }
        
        /* Button styles */
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 10px 16px;
            font-size: 16px;
            line-height: 1.5;
            border-radius: 4px;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--gray-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        /* Alert styles */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: var(--success-light);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-error {
            background-color: var(--danger-light);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .form-group {
                flex: 1 0 calc(100% - 20px);
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
            <span class="role-badge"><?php echo ucfirst($_SESSION['role']); ?></span>
            <span><?php echo $_SESSION['name']; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="student_list.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Student List
        </a>
        
        <h1 class="page-title">Edit Student</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($student): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Student Information</h2>
                    <div>
                        <a href="view_student.php?id=<?php echo $student_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <h3>Personal Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($student['phone_number'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" required>
                                    <option value="Male" <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($student['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <h3>Academic Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="student_number">Student Number</label>
                                <input type="text" id="student_number" name="student_number" value="<?php echo htmlspecialchars($student['student_number']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="academic_level">Academic Level</label>
                                <select id="academic_level" name="academic_level" required>
                                    <option value="Undergraduate" <?php echo ($student['academic_level'] == 'Undergraduate') ? 'selected' : ''; ?>>Undergraduate</option>
                                    <option value="Graduate" <?php echo ($student['academic_level'] == 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
                                    <option value="Postgraduate" <?php echo ($student['academic_level'] == 'Postgraduate') ? 'selected' : ''; ?>>Postgraduate</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="college">College</label>
                                <select id="college" name="college" required onchange="updateDepartments()">
                                    <option value="">-- Select College --</option>
                                    <?php foreach ($colleges as $college): ?>
                                        <option value="<?php echo htmlspecialchars($college['college_name']); ?>" data-id="<?php echo $college['college_id']; ?>" <?php echo ($student['college'] == $college['college_name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($college['college_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select id="department" name="department" required onchange="updatePrograms()">
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo htmlspecialchars($department['department_name']); ?>" data-college="<?php echo $department['college_id']; ?>" data-id="<?php echo $department['department_id']; ?>" <?php echo ($student['department'] == $department['department_name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($department['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="program_id">Program</label>
                                <select id="program_id" name="program_id" required>
                                    <option value="">-- Select Program --</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?php echo $program['program_id']; ?>" data-department="<?php echo $program['department_id']; ?>" <?php echo ($student['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($program['program_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="academic_status">Academic Status</label>
                                <select id="academic_status" name="academic_status" required>
                                    <option value="Active" <?php echo ($student['academic_status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="On Leave" <?php echo ($student['academic_status'] == 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
                                    <option value="Graduated" <?php echo ($student['academic_status'] == 'Graduated') ? 'selected' : ''; ?>>Graduated</option>
                                    <option value="Withdrawn" <?php echo ($student['academic_status'] == 'Withdrawn') ? 'selected' : ''; ?>>Withdrawn</option>
                                    <option value="Suspended" <?php echo ($student['academic_status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <button type="submit" name="update_student" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Student
                                </button>
                                <a href="student_list.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <p>Student not found or you don't have permission to edit this student.</p>
                    <a href="student_list.php" class="btn btn-primary">Return to Student List</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Function to filter departments based on selected college
        function updateDepartments() {
            const collegeSelect = document.getElementById('college');
            const departmentSelect = document.getElementById('department');
            const selectedOption = collegeSelect.options[collegeSelect.selectedIndex];
            const collegeId = selectedOption.getAttribute('data-id');
            
            // Hide all department options first
            for (let i = 0; i < departmentSelect.options.length; i++) {
                const option = departmentSelect.options[i];
                if (i === 0) {
                    // Always show the placeholder option
                    option.style.display = '';
                    continue;
                }
                
                const deptCollegeId = option.getAttribute('data-college');
                if (!collegeId || deptCollegeId === collegeId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
            
            // Reset department selection
            departmentSelect.value = '';
            
            // Update programs based on department
            updatePrograms();
        }
        
        // Function to filter programs based on selected department
        function updatePrograms() {
            const departmentSelect = document.getElementById('department');
            const programSelect = document.getElementById('program_id');
            const selectedOption = departmentSelect.options[departmentSelect.selectedIndex];
            const departmentId = selectedOption.getAttribute('data-id');
            
            // Hide all program options first
            for (let i = 0; i < programSelect.options.length; i++) {
                const option = programSelect.options[i];
                if (i === 0) {
                    // Always show the placeholder option
                    option.style.display = '';
                    continue;
                }
                
                const progDeptId = option.getAttribute('data-department');
                if (!departmentId || progDeptId === departmentId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
            
            // Reset program selection
            programSelect.value = '';
        }
        
        // Initialize the filters on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateDepartments();
        });
    </script>
</body>
</html>