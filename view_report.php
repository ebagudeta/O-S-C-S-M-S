






<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header("Location: index.php");
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];

// Sample data for demonstration - in a real application, this would come from a database
function getReportData($user_id, $role) {
    if ($role == 'student') {
        return [
            'academic' => [
                ['course_code' => 'CS101', 'course_name' => 'Introduction to Programming', 'grade' => 'A', 'status' => 'Completed'],
                ['course_code' => 'MATH201', 'course_name' => 'Calculus II', 'grade' => 'B+', 'status' => 'Completed'],
                ['course_code' => 'ENG105', 'course_name' => 'Technical Writing', 'grade' => '-', 'status' => 'In Progress'],
                ['course_code' => 'PHYS101', 'course_name' => 'Physics I', 'grade' => 'C', 'status' => 'Completed'],
            ],
            'payment' => [
                ['semester' => 'Fall 2023', 'amount' => '$2,500', 'payment_date' => '2023-08-15', 'status' => 'Paid'],
                ['semester' => 'Spring 2024', 'amount' => '$2,500', 'payment_date' => '2024-01-10', 'status' => 'Paid'],
                ['semester' => 'Summer 2024', 'amount' => '$1,200', 'payment_date' => '-', 'status' => 'Pending'],
            ],
            'attendance' => [
                ['course_code' => 'CS101', 'present' => 42, 'absent' => 3, 'percentage' => '93.3%'],
                ['course_code' => 'MATH201', 'present' => 38, 'absent' => 7, 'percentage' => '84.4%'],
                ['course_code' => 'ENG105', 'present' => 20, 'absent' => 0, 'percentage' => '100%'],
                ['course_code' => 'PHYS101', 'present' => 40, 'absent' => 5, 'percentage' => '88.9%'],
            ],
        ];
    } else if ($role == 'registrar') {
        return [
            'enrollment' => [
                ['course_code' => 'CS101', 'course_name' => 'Introduction to Programming', 'enrolled' => 120, 'capacity' => 150, 'percentage' => '80%'],
                ['course_code' => 'MATH201', 'course_name' => 'Calculus II', 'enrolled' => 85, 'capacity' => 100, 'percentage' => '85%'],
                ['course_code' => 'ENG105', 'course_name' => 'Technical Writing', 'enrolled' => 65, 'capacity' => 80, 'percentage' => '81.3%'],
                ['course_code' => 'PHYS101', 'course_name' => 'Physics I', 'enrolled' => 110, 'capacity' => 120, 'percentage' => '91.7%'],
            ],
            'performance' => [
                ['course_code' => 'CS101', 'avg_grade' => 3.4, 'pass_rate' => '92%', 'highest_grade' => 'A+', 'lowest_grade' => 'D'],
                ['course_code' => 'MATH201', 'avg_grade' => 3.1, 'pass_rate' => '88%', 'highest_grade' => 'A', 'lowest_grade' => 'F'],
                ['course_code' => 'ENG105', 'avg_grade' => 3.6, 'pass_rate' => '95%', 'highest_grade' => 'A+', 'lowest_grade' => 'C'],
                ['course_code' => 'PHYS101', 'avg_grade' => 2.9, 'pass_rate' => '85%', 'highest_grade' => 'A', 'lowest_grade' => 'F'],
            ],
            'payment_status' => [
                ['semester' => 'Fall 2023', 'total_students' => 850, 'paid' => 820, 'pending' => 30, 'percentage' => '96.5%'],
                ['semester' => 'Spring 2024', 'total_students' => 830, 'paid' => 790, 'pending' => 40, 'percentage' => '95.2%'],
                ['semester' => 'Summer 2024', 'total_students' => 450, 'paid' => 380, 'pending' => 70, 'percentage' => '84.4%'],
            ],
        ];
    }
    
    return [];
}

$reports = getReportData($user_id, $role);
?>
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';
$role = $_SESSION['role'];

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password (blank)

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get student ID
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student_basic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student_basic) {
        throw new Exception("Student record not found");
    }
    
    $student_id = $student_basic['student_id'];

    // Initialize report variables
    $academic_performance = [];
    $payment_history = [];
    $attendance_record = [];
    $enrollment_statistics = [];
    $cost_share_reports = [];
    $student_feedback = [];

    // Fetch academic performance
    $stmt = $pdo->prepare("
        SELECT c.course_code, c.course_name, g.grade, g.status 
        FROM courses c 
        JOIN grades g ON c.course_id = g.course_id 
        WHERE g.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $academic_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch payment history
    $stmt = $pdo->prepare("
        SELECT p.semester, p.amount, p.payment_date, p.status 
        FROM payments p 
        JOIN invoices i ON p.invoice_id = i.invoice_id 
        WHERE i.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch attendance record
    $stmt = $pdo->prepare("
        SELECT c.course_code, a.present, a.absent, 
               (a.present / (a.present + a.absent) * 100) as percentage 
        FROM attendance a 
        JOIN courses c ON a.course_id = c.course_id 
        WHERE a.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $attendance_record = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch enrollment statistics for registrar
    if ($role == 'registrar') {
        $stmt = $pdo->prepare("
            SELECT c.course_code, c.course_name, 
                   COUNT(e.student_id) as enrolled, c.capacity, 
                   (COUNT(e.student_id) / c.capacity * 100) as percentage 
            FROM courses c 
            LEFT JOIN enrollments e ON c.course_id = e.course_id 
            GROUP BY c.course_id
        ");
        $stmt->execute();
        $enrollment_statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch cost share reports
        $stmt = $pdo->prepare("
            SELECT * FROM cost_share WHERE department_id IN (SELECT department_id FROM departments WHERE college_id IN (SELECT college_id FROM colleges WHERE college_id = ?))
        ");
        $stmt->execute([$student_id]);
        $cost_share_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch student feedback
        $stmt = $pdo->prepare("
            SELECT f.*, s.name AS student_name 
            FROM feedback f 
            JOIN students s ON f.student_id = s.student_id 
            WHERE f.is_anonymous = 0
        ");
        $stmt->execute();
        $student_feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}

// HTML Output
?>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - View Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        :root {
            --primary-color: #5D5CDE;
            --primary-dark: #4A49B0;
            --bg-light: #FFFFFF;
            --bg-dark: #181818;
            --text-light: #333333;
            --text-dark: #F5F5F5;
            --border-light: #e0e0e0;
            --border-dark: #444444;
            --chart-1: #4CAF50;
            --chart-2: #2196F3;
            --chart-3: #FFC107;
            --chart-4: #F44336;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: var(--text-light);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo i {
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-role {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .nav-links a i {
            margin-right: 5px;
        }
        
        .nav-links a:hover {
            color: var(--primary-dark);
        }
        
        .page-title {
            margin: 20px 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 10px;
            font-size: 24px;
        }
        
        .report-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .report-title {
            font-size: 20px;
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .report-title i {
            margin-right: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }
        
        th {
            background-color: #f9f9f9;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-completed, .status-paid {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-in-progress, .status-pending {
            background-color: #fff8e1;
            color: #f57f17;
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .chart-box {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            text-align: center;
            margin-bottom: 15px;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .bar-chart {
            height: 200px;
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            padding-top: 20px;
        }
        
        .bar {
            width: 40px;
            background-color: var(--primary-color);
            border-radius: 5px 5px 0 0;
            position: relative;
            transition: height 0.5s ease;
        }
        
        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .bar-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary-dark);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .print-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            float: right;
            display: flex;
            align-items: center;
        }
        
        .print-btn i {
            margin-right: 5px;
        }
        
        .print-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .summary-box {
            background-color: #f9f9f9;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        
        .summary-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-weight: bold;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 14px;
            margin-top: 40px;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 14px;
            }
            
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
        
        /* Dark mode styles */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: var(--bg-dark);
                color: var(--text-dark);
            }
            
            header, .report-section, .chart-box {
                background-color: #222;
                color: var(--text-dark);
            }
            
            th {
                background-color: #333;
                color: #ddd;
            }
            
            th, td {
                border-bottom: 1px solid var(--border-dark);
            }
            
            .summary-box {
                background-color: #2a2a2a;
            }
            
            .status-completed, .status-paid {
                background-color: rgba(46, 125, 50, 0.2);
            }
            
            .status-in-progress, .status-pending {
                background-color: rgba(245, 127, 23, 0.2);
            }
        }
    </style>
    <style>
        /* Add your styles here */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo i {
            color: #5D5CDE;
            margin-right: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-role {
            background-color: #5D5CDE;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 10px;
        }
        
        .nav-links a {
            margin-left: 15px;
            color: #5D5CDE;
            text-decoration: none;
        }
        
        .page-title {
            margin: 20px 0;
            color: #5D5CDE;
            font-size: 28px;
        }
        
        .report-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .report-title {
            font-size: 20px;
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            color: #5D5CDE;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        
        .no-data {
            text-align: center;
            color: #777;
            padding: 20px;
        }
        
    </style>
</head>
<body>
  <header>
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <h1>OCSMS</h1>
        </div>
        <div class="user-info">
            <span class="user-role"><?php echo ucfirst($role); ?></span>
            <span><?php echo $name; ?></span>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-title">
            <i class="fas fa-file-alt"></i>
            <h1>Reports</h1>
            <button class="print-btn" onclick="window.print();">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <?php if ($role == 'student'): ?>
            <!-- Student Reports -->
            <div class="report-section">
                <h2 class="report-title">
                    <i class="fas fa-graduation-cap"></i>
                    Academic Performance
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports['academic'] as $course): ?>
                            <tr>
                                <td><?php echo $course['course_code']; ?></td>
                                <td><?php echo $course['course_name']; ?></td>
                                <td><?php echo $course['grade']; ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower(str_replace(' ', '-', $course['status'])); ?>">
                                        <?php echo $course['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="summary-box">
                    <div class="summary-title">Academic Summary</div>
                    <div class="summary-item">
                        <span class="summary-label">GPA:</span>
                        <span>3.4/4.0</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Credits Completed:</span>
                        <span>45/120</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Academic Standing:</span>
                        <span>Good Standing</span>
                    </div>
                </div>
            </div>

            <div class="report-section">
                <h2 class="report-title">
                    <i class="fas fa-clock"></i>
                    Attendance Record
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports['attendance'] as $record): ?>
                            <tr>
                                <td><?php echo $record['course_code']; ?></td>
                                <td><?php echo $record['present']; ?></td>
                                <td><?php echo $record['absent']; ?></td>
                                <td><?php echo $record['percentage']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <h2 class="report-title">
                    <i class="fas fa-credit-card"></i>
                    Payment History
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports['payment'] as $payment): ?>
                            <tr>
                                <td><?php echo $payment['semester']; ?></td>
                                <td><?php echo $payment['amount']; ?></td>
                                <td><?php echo $payment['payment_date']; ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($payment['status']); ?>">
                                        <?php echo $payment['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="summary-box">
                    <div class="summary-title">Payment Summary</div>
                    <div class="summary-item">
                        <span class="summary-label">Total Paid:</span>
                        <span>$5,000</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Pending Payment:</span>
                        <span>$1,200</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Payment Status:</span>
                        <span>Up to date</span>
                    </div>
                </div>
            </div>

        <?php elseif ($role == 'registrar'): ?>
            <!-- Registrar Reports -->
            <div class="report-section">
                <h2 class="report-title">
                    <i class="fas fa-users"></i>
                    Enrollment Statistics
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Enrolled</th>
                            <th>Capacity</th>
                            <th>Enrollment %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports['enrollment'] as $course): ?>
                            <tr>
                                <td><?php echo $course['course_code']; ?></td>
                                <td><?php echo $course['course_name']; ?></td>
                                <td><?php echo $course['enrolled']; ?></td>
                                <td><?php echo $course['capacity']; ?></td>
                                <td><?php echo $course['percentage']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="charts-container">
                    <div class="chart-box">
                        <div class="chart-title">Enrollment by Course</div>
                        <div class="bar-chart">
                            <?php 
                            $colors = ['#4CAF50', '#2196F3', '#FFC107', '#F44336'];
                            $i = 0;
                            foreach ($reports['enrollment'] as $course): 
                                $percentage = intval($course['percentage']);
                                $height = $percentage * 1.8; // max height is 180px for 100%
                            ?>
                                <div class="bar" style="height: <?php echo $height; ?>px; background-color: <?php echo $colors[$i % count($colors)]; ?>">
                                    <div class="bar-value"><?php echo $course['percentage']; ?></div>
                                    <div class="bar-label"><?php echo $course['course_code']; ?></div>
                                </div>
                            <?php 
                                $i++;
                                endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-section">
                <h2 class="report-title">
                    <i class="fas fa-chart-line"></i>
                    Student Performance
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Average Grade</th>
                            <th>Pass Rate</th>
                            <th>Highest Grade</th>
                            <th>Lowest Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports['performance'] as $course): ?>
                            <tr>
                                <td><?php echo $course['course_code']; ?></td>
                                <td><?php echo $course['avg_grade']; ?></td>
                                <td><?php echo $course['pass_rate']; ?></td>
                                <td><?php echo $course['highest_grade']; ?></td>
                                <td><?php echo $course['lowest_grade']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <h2 class="report-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Payment Status
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Total Students</th>
                            <th>Paid</th>
                            <th>Pending</th>
                            <th>Payment %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports['payment_status'] as $semester): ?>
                            <tr>
                                <td><?php echo $semester['semester']; ?></td>
                                <td><?php echo $semester['total_students']; ?></td>
                                <td><?php echo $semester['paid']; ?></td>
                                <td><?php echo $semester['pending']; ?></td>
                                <td><?php echo $semester['percentage']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="charts-container">
                    <div class="chart-box">
                        <div class="chart-title">Payment Completion Rate</div>
                        <div class="bar-chart">
                            <?php 
                            $colors = ['#4CAF50', '#2196F3', '#F44336'];
                            $i = 0;
                            foreach ($reports['payment_status'] as $semester): 
                                $percentage = intval($semester['percentage']);
                                $height = $percentage * 1.8; // max height is 180px for 100%
                            ?>
                                <div class="bar" style="height: <?php echo $height; ?>px; background-color: <?php echo $colors[$i % count($colors)]; ?>">
                                    <div class="bar-value"><?php echo $semester['percentage']; ?></div>
                                    <div class="bar-label"><?php echo $semester['semester']; ?></div>
                                </div>
                            <?php 
                                $i++;
                                endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

<script>
        // Simple print functionality
        document.querySelector('.print-btn').addEventListener('click', function() {
            window.print();
        });
    </script>
</body>
<body>

   

    <div class="container">
<!-- Student Reports -->
        <?php if ($role == 'student'): ?>
            <div class="report-section">
                <h2 class="report-title">Academic Performance</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($academic_performance as $course): ?>
                            <tr>
                                <td><?php echo $course['course_code']; ?></td>
                                <td><?php echo $course['course_name']; ?></td>
                                <td><?php echo $course['grade']; ?></td>
                                <td><?php echo $course['status']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <h2 class="report-title">Payment History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment_history as $payment): ?>
                            <tr>
                                <td><?php echo $payment['semester']; ?></td>
                                <td><?php echo $payment['amount']; ?></td>
                                <td><?php echo $payment['payment_date']; ?></td>
                                <td><?php echo $payment['status']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <h2 class="report-title">Attendance Record</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_record as $record): ?>
                            <tr>
                                <td><?php echo $record['course_code']; ?></td>
                                <td><?php echo $record['present']; ?></td>
                                <td><?php echo $record['absent']; ?></td>
                                <td><?php echo number_format($record['percentage'], 2) . '%'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($role == 'registrar'): ?>
            <!-- Registrar Reports -->
            <div class="report-section">
                <h2 class="report-title">Enrollment Statistics</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Enrolled</th>
                            <th>Capacity</th>
                            <th>Enrollment %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($enrollment_statistics)): ?>
                            <?php foreach ($enrollment_statistics as $course): ?>
                                <tr>
                                    <td><?php echo $course['course_code']; ?></td>
                                    <td><?php echo $course['course_name']; ?></td>
                                    <td><?php echo $course['enrolled']; ?></td>
                                    <td><?php echo $course['capacity']; ?></td>
                                    <td><?php echo number_format($course['percentage'], 2) . '%'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data">No enrollment statistics available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <h2 class="report-title">Cost Share Reports</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Academic Year</th>
                            <th>Tuition Fee</th>
                            <th>Food Expense</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cost_share_reports)): ?>
                            <?php foreach ($cost_share_reports as $report): ?>
                                <tr>
                                    <td><?php echo $report['department_id']; // Adjust based on your actual data ?></td>
                                    <td><?php echo $report['academic_year']; ?></td>
                                    <td><?php echo number_format($report['tuition_fee'], 2); ?></td>
                                    <td><?php echo number_format($report['food_expense'], 2); ?></td>
                                    <td><?php echo number_format($report['total_cost'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data">No cost share reports available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-section">
                <h2 class="report-title">Student Feedback</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Feedback Type</th>
                            <th>Student Name</th>
                            <th>Comments</th>
                            <th>Submission Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($student_feedback)): ?>
                            <?php foreach ($student_feedback as $feedback): ?>
                                <tr>
                                    <td><?php echo $feedback['feedback_type']; ?></td>
                                    <td><?php echo $feedback['student_name']; ?></td>
                                    <td><?php echo $feedback['comments']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($feedback['submission_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="no-data">No feedback available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

     <footer class="site-footer">
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
</body>
</html>



