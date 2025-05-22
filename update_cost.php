<?php
// Start session
session_start();

// Check if user is logged in and is a cost sharing officer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cost_sharing_officer') {
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
$selected_student = null;
$cost_share_details = null;

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle student selection
        if (isset($_POST['select_student'])) {
            $student_id = $_POST['student_id'];
            
            // Get student details
            $stmt = $conn->prepare("SELECT s.student_id, s.student_number, u.first_name, u.last_name, 
                                  p.program_id, p.program_name
                                  FROM students s
                                  JOIN users u ON s.user_id = u.user_id
                                  JOIN programs p ON s.program_id = p.program_id
                                  WHERE s.student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $selected_student = $result->fetch_assoc();
            $stmt->close();
            
            if (!$selected_student) {
                throw new Exception("Student not found");
            }
            
            // Get current cost share details
            $stmt = $conn->prepare("SELECT scs.cost_share_id, scs.program_id, csp.program_name, 
                                  scs.coverage_percent, scs.academic_year, scs.status,
                                  scs.additional_info
                                  FROM student_cost_share scs
                                  JOIN cost_share_programs csp ON scs.program_id = csp.program_id
                                  WHERE scs.student_id = ?
                                  ORDER BY scs.academic_year DESC, scs.cost_share_id DESC
                                  LIMIT 1");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cost_share_details = $result->fetch_assoc();
            $stmt->close();
        }
        
        // Handle cost share update
        if (isset($_POST['update_cost_share'])) {
            $student_id = $_POST['student_id'];
            $program_id = $_POST['program_id'];
            $coverage_percent = $_POST['coverage_percent'];
            $academic_year = $_POST['academic_year'];
            $additional_info = $_POST['additional_info'];
            
            // Validate inputs
            if (empty($student_id) || empty($program_id) || empty($coverage_percent) || empty($academic_year)) {
                throw new Exception("All required fields must be filled");
            }
            
            if (!is_numeric($coverage_percent) || $coverage_percent < 0 || $coverage_percent > 100) {
                throw new Exception("Coverage percentage must be between 0 and 100");
            }
            
            // Check if there's an existing cost share record to update
            if (isset($_POST['cost_share_id']) && !empty($_POST['cost_share_id'])) {
                $cost_share_id = $_POST['cost_share_id'];
                
                // Update existing record
                $stmt = $conn->prepare("UPDATE student_cost_share 
                                      SET program_id = ?, 
                                          coverage_percent = ?, 
                                          academic_year = ?,
                                          additional_info = ?,
                                          last_updated_by = ?,
                                          last_updated_at = CURRENT_TIMESTAMP
                                      WHERE cost_share_id = ? AND student_id = ?");
                $stmt->bind_param("idssiii", $program_id, $coverage_percent, $academic_year, $additional_info, $_SESSION['user_id'], $cost_share_id, $student_id);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $message = "Cost share record updated successfully";
                } else {
                    $message = "No changes were made to the cost share record";
                }
                $message_type = "success";
                $stmt->close();
            } else {
                // Create new record
                $status = "Pending";
                $stmt = $conn->prepare("INSERT INTO student_cost_share 
                                      (student_id, program_id, coverage_percent, academic_year, status, additional_info, created_by)
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidssis", $student_id, $program_id, $coverage_percent, $academic_year, $status, $additional_info, $_SESSION['user_id']);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $message = "New cost share record created successfully";
                } else {
                    throw new Exception("Failed to create cost share record");
                }
                $message_type = "success";
                $stmt->close();
            }
            
            // Refresh cost share details
            $stmt = $conn->prepare("SELECT scs.cost_share_id, scs.program_id, csp.program_name, 
                                  scs.coverage_percent, scs.academic_year, scs.status,
                                  scs.additional_info
                                  FROM student_cost_share scs
                                  JOIN cost_share_programs csp ON scs.program_id = csp.program_id
                                  WHERE scs.student_id = ?
                                  ORDER BY scs.academic_year DESC, scs.cost_share_id DESC
                                  LIMIT 1");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cost_share_details = $result->fetch_assoc();
            $stmt->close();
            
            // Get student details
            $stmt = $conn->prepare("SELECT s.student_id, s.student_number, u.first_name, u.last_name, 
                                  p.program_id, p.program_name
                                  FROM students s
                                  JOIN users u ON s.user_id = u.user_id
                                  JOIN programs p ON s.program_id = p.program_id
                                  WHERE s.student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $selected_student = $result->fetch_assoc();
            $stmt->close();
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// Get all students for selection
$students = [];
$sql = "SELECT s.student_id, s.student_number, u.first_name, u.last_name, p.program_name
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        JOIN programs p ON s.program_id = p.program_id
        ORDER BY u.last_name, u.first_name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Get all cost share programs
$programs = [];
$sql = "SELECT program_id, program_name, description FROM cost_share_programs ORDER BY program_name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
}

// Get current and upcoming academic years
$current_year = date('Y');
$academic_years = [];
for ($i = -1; $i <= 3; $i++) {
    $year = $current_year + $i;
    $academic_years[] = $year . '-' . ($year + 1);
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Update Cost Share</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            position: sticky;
            top: 0;
            z-index: 1000;
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
            transition: background-color 0.2s;
        }
        
        .logout-btn:hover {
            background-color: #bd2130;
        }
        
        /* Main container */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        /* Navigation and page header */
        .nav-link {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .nav-link i {
            margin-right: 8px;
        }
        
        .page-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
        }
        
        /* Card and section styles */
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
        }
        
        .section-title {
            color: var(--primary-color);
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .alert.success {
            background-color: var(--success-light);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert.error {
            background-color: var(--danger-light);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        select,
        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--gray-light);
            border-radius: 4px;
            background-color: var(--card-bg);
            color: var(--text-color);
            font-size: 16px;
        }
        
        textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-col {
            flex: 1;
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
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        /* Student info styles */
        .student-info {
            background-color: var(--primary-light);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-group {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: 600;
            width: 120px;
            flex-shrink: 0;
        }
        
        .info-value {
            flex-grow: 1;
        }
        
        /* Status badge */
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pending {
            background-color: var(--warning-light);
            color: #856404;
        }
        
        .badge-approved {
            background-color: var(--success-light);
            color: var(--success-color);
        }
        
        .badge-rejected {
            background-color: var(--danger-light);
            color: var(--danger-color);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .header {
                flex-direction: column;
                padding: 10px;
            }
            
            .user-info {
                margin-top: 10px;
                justify-content: center;
                width: 100%;
            }
            
            .container {
                padding: 0 15px;
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
            <span class="role-badge">Cost Sharing Officer</span>
            <span><?php echo $_SESSION['name'] ?? 'User'; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="nav-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">Update Cost Share</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="section-title">Select Student</h2>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="student_id">Student</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>" <?php echo (isset($selected_student) && $selected_student['student_id'] == $student['student_id']) ? 'selected' : ''; ?>>
                                    <?php echo $student['student_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['program_name'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="select_student" class="btn btn-primary">Select</button>
                </form>
            </div>
        </div>
        
        <?php if ($selected_student): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="section-title">Update Cost Share for Student</h2>
                </div>
                <div class="card-body">
                    <div class="student-info">
                        <div class="info-group">
                            <div class="info-label">Student ID:</div>
                            <div class="info-value"><?php echo $selected_student['student_number']; ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Name:</div>
                            <div class="info-value"><?php echo $selected_student['first_name'] . ' ' . $selected_student['last_name']; ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Program:</div>
                            <div class="info-value"><?php echo $selected_student['program_name']; ?></div>
                        </div>
                        
                        <?php if ($cost_share_details): ?>
                            <div class="info-group">
                                <div class="info-label">Current Status:</div>
                                <div class="info-value">
                                    <span class="badge badge-<?php echo strtolower($cost_share_details['status']); ?>">
                                        <?php echo $cost_share_details['status']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" action="">
                        <input type="hidden" name="student_id" value="<?php echo $selected_student['student_id']; ?>">
                        <?php if ($cost_share_details): ?>
                            <input type="hidden" name="cost_share_id" value="<?php echo $cost_share_details['cost_share_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="program_id">Cost Share Program</label>
                                    <select id="program_id" name="program_id" required>
                                        <option value="">-- Select Program --</option>
                                        <?php foreach ($programs as $program): ?>
                                            <option value="<?php echo $program['program_id']; ?>" <?php echo (isset($cost_share_details) && $cost_share_details['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                                <?php echo $program['program_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="coverage_percent">Coverage Percentage (%)</label>
                                    <input type="number" id="coverage_percent" name="coverage_percent" min="0" max="100" step="0.01" required value="<?php echo isset($cost_share_details) ? $cost_share_details['coverage_percent'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="academic_year">Academic Year</label>
                            <select id="academic_year" name="academic_year" required>
                                <?php foreach ($academic_years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo (isset($cost_share_details) && $cost_share_details['academic_year'] == $year) ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="additional_info">Additional Information</label>
                            <textarea id="additional_info" name="additional_info"><?php echo isset($cost_share_details) ? $cost_share_details['additional_info'] : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_cost_share" class="btn btn-success">
                            <?php echo isset($cost_share_details) ? 'Update Cost Share' : 'Create Cost Share'; ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'opacity 0.5s, transform 0.5s';
                    
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>