<?php
// Start session
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit;
}

// Database connection parameters for XAMPP
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (blank)

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Get student ID for the logged-in user
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if (!$student) {
        throw new Exception("Student record not found");
    }
    
    $student_id = $student['student_id'];
    
    // Process enrollment if form submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['enroll'])) {
            $offering_id = $_POST['offering_id'];
            
            // Check if already enrolled
            $stmt = $conn->prepare("SELECT enrollment_id FROM enrollments 
                                  WHERE student_id = ? AND offering_id = ?");
            $stmt->bind_param("ii", $student_id, $offering_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            
            if ($result->num_rows > 0) {
                $message = "You are already enrolled in this course.";
                $message_type = "error";
            } else {
                // Enroll the student
                $status = "Enrolled";
                $stmt = $conn->prepare("INSERT INTO enrollments (student_id, offering_id, status) 
                                      VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $student_id, $offering_id, $status);
                $stmt->execute();
                $stmt->close();
                
                $message = "Successfully enrolled in the course!";
                $message_type = "success";
            }
        } else if (isset($_POST['withdraw'])) {
            $enrollment_id = $_POST['enrollment_id'];
            
            // Check if enrollment exists and belongs to the student
            $stmt = $conn->prepare("SELECT e.enrollment_id, c.course_name 
                                  FROM enrollments e
                                  JOIN course_offerings co ON e.offering_id = co.offering_id
                                  JOIN courses c ON co.course_id = c.course_id
                                  WHERE e.enrollment_id = ? AND e.student_id = ?");
            $stmt->bind_param("ii", $enrollment_id, $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $enrollment = $result->fetch_assoc();
            $stmt->close();
            
            if (!$enrollment) {
                $message = "Invalid enrollment selection.";
                $message_type = "error";
            } else {
                // Update enrollment status to Withdrawn
                $status = "Withdrawn";
                $stmt = $conn->prepare("UPDATE enrollments SET status = ? WHERE enrollment_id = ?");
                $stmt->bind_param("si", $status, $enrollment_id);
                $stmt->execute();
                $stmt->close();
                
                $message = "Successfully withdrawn from " . $enrollment['course_name'] . ".";
                $message_type = "success";
            }
        }
    }
    
    // Get enrolled courses
    $enrolled_courses = [];
    $sql = "SELECT e.enrollment_id, c.course_code, c.course_name, c.credit_hours, 
                  co.semester, co.academic_year, e.status
           FROM enrollments e
           JOIN course_offerings co ON e.offering_id = co.offering_id
           JOIN courses c ON co.course_id = c.course_id
           WHERE e.student_id = ? AND e.status != 'Withdrawn'
           ORDER BY co.academic_year DESC, co.semester, c.course_code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $enrolled_courses[] = $row;
    }
    $stmt->close();
    
    // Get available courses
    $available_courses = [];
    $sql = "SELECT co.offering_id, c.course_code, c.course_name, c.credit_hours, 
                  co.semester, co.academic_year, co.max_students,
                  (SELECT COUNT(*) FROM enrollments e WHERE e.offering_id = co.offering_id AND e.status != 'Withdrawn') AS current_enrollment
           FROM course_offerings co
           JOIN courses c ON co.course_id = c.course_id
           WHERE co.status = 'Active'
           AND co.offering_id NOT IN (
               SELECT offering_id FROM enrollments 
               WHERE student_id = ? AND status != 'Withdrawn'
           )
           ORDER BY co.academic_year, co.semester, c.course_code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $available_courses[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    $message = $e->getMessage();
    $message_type = "error";
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Course Enrollment</title>
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
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
        }
        
        /* Card and section styles */
        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .section-title {
            color: var(--text-color);
            border-bottom: 2px solid var(--gray-light);
            padding-bottom: 12px;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 600;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
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
        
        /* Table styles */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }
        
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }
        
        th {
            font-weight: 600;
            color: var(--gray-color);
            background-color: var(--card-bg);
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background-color: rgba(93, 92, 222, 0.05);
        }
        
        /* Badge styles */
        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .badge-enrolled {
            background-color: var(--success-light);
            color: var(--success-color);
        }
        
        .badge-withdrawn {
            background-color: var(--danger-light);
            color: var(--danger-color);
        }
        
        .badge-completed {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }
        
        /* Button styles */
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #bd2130;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        /* Helper classes */
        .text-center {
            text-align: center;
        }
        
        .empty-state {
            padding: 30px;
            text-align: center;
            color: var(--gray-color);
            font-style: italic;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
            }
            
            .user-info {
                margin-top: 15px;
                flex-wrap: wrap;
            }
            
            .container {
                padding: 0 15px;
            }
            
            .card {
                padding: 15px;
            }
            
            th, td {
                padding: 10px 12px;
            }
            
            .table-responsive {
                margin-left: -15px;
                margin-right: -15px;
                width: calc(100% + 30px);
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
            <span><?php echo $_SESSION['name']; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="nav-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">Course Enrollment</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="section-title">Current Enrollments</h2>
            
            <?php if (count($enrolled_courses  ) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrolled_courses as $course): ?>
                                <tr>
                                    <td><?php echo $course['course_code']; ?></td>
                                    <td><?php echo $course['course_name']; ?></td>
                                    <td><?php echo $course['credit_hours']; ?></td>
                                    <td><?php echo $course['semester']; ?></td>
                                    <td><?php echo $course['academic_year']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($course['status']); ?>">
                                            <?php echo $course['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($course['status'] === 'Enrolled'): ?>
                                            <form method="post" onsubmit="return confirm('Are you sure you want to withdraw from this course?');">
                                                <input type="hidden" name="enrollment_id" value="<?php echo $course['enrollment_id']; ?>">
                                                <button type="submit" name="withdraw" class="btn btn-danger btn-sm">Withdraw</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>You are not currently enrolled in any courses.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2 class="section-title">Available Courses</h2>
            
            <?php if (count($available_courses) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Enrollment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_courses as $course): ?>
                                <tr>
                                    <td><?php echo $course['course_code']; ?></td>
                                    <td><?php echo $course['course_name']; ?></td>
                                    <td><?php echo $course['credit_hours']; ?></td>
                                    <td><?php echo $course['semester']; ?></td>
                                    <td><?php echo $course['academic_year']; ?></td>
                                    <td><?php echo $course['current_enrollment'] . '/' . $course['max_students']; ?></td>
                                    <td>
                                        <?php if ($course['current_enrollment'] < $course['max_students']): ?>
                                            <form method="post">
                                                <input type="hidden" name="offering_id" value="<?php echo $course['offering_id']; ?>">
                                                <button type="submit" name="enroll" class="btn btn-primary btn-sm">Enroll</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Course Full</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No courses available for enrollment at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Keep notification on screen for 5 seconds before fading out
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 1s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 1000);
                }, 5000);
            }
        });
    </script>
</body>
</html>