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

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get student ID
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_id = $student ? $student['student_id'] : null;
    
    if (!$student_id) {
        throw new Exception("Student record not found");
    }
    
    // Get enrolled courses
    $sql = "SELECT c.course_id, c.course_code, c.course_name, c.credit_hours, 
                  co.semester, co.academic_year, e.status,
                  d.department_name, e.final_grade
           FROM enrollments e
           JOIN course_offerings co ON e.offering_id = co.offering_id
           JOIN courses c ON co.course_id = c.course_id
           JOIN departments d ON c.department_id = d.department_id
           WHERE e.student_id = ?
           ORDER BY co.academic_year DESC, co.semester, c.course_code";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available courses
    $sql = "SELECT c.course_id, c.course_code, c.course_name, c.credit_hours, 
                  d.department_name, co.semester, co.academic_year,
                  co.max_students, co.offering_id,
                  (SELECT COUNT(*) FROM enrollments e WHERE e.offering_id = co.offering_id) AS current_enrollment
           FROM courses c
           JOIN departments d ON c.department_id = d.department_id
           JOIN course_offerings co ON c.course_id = co.course_id
           WHERE co.status = 'Active'
           AND co.offering_id NOT IN (
               SELECT offering_id FROM enrollments WHERE student_id = ?
           )
           ORDER BY co.academic_year, co.semester, c.course_code";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - View Courses</title>
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
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .enrolled {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .completed {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        .withdrawn {
            background-color: #ffebee;
            color: #c62828;
        }
        .enroll-btn {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .enroll-btn:hover {
            background-color: #4A49B0;
        }
        .full {
            background-color: #f5f5f5;
            color: #9e9e9e;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: not-allowed;
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
        .no-data {
            color: #757575;
            font-style: italic;
            padding: 15px 0;
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
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">View Courses</h1>
        
        <?php if (isset($error)): ?>
            <div class="card">
                <p style="color: #f44336;"><?php echo $error; ?></p>
            </div>
        <?php else: ?>
            <div class="card">
                <h2 class="section-title">My Enrolled Courses</h2>
                <?php if (count($enrolled_courses) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Credits</th>
                                <th>Term</th>
                                <th>Status</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrolled_courses as $course): ?>
                                <tr>
                                    <td><?php echo $course['course_code']; ?></td>
                                    <td><?php echo $course['course_name']; ?></td>
                                    <td><?php echo $course['department_name']; ?></td>
                                    <td><?php echo $course['credit_hours']; ?></td>
                                    <td><?php echo $course['semester'] . ' ' . $course['academic_year']; ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($course['status']); ?>">
                                            <?php echo $course['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $course['final_grade'] ? $course['final_grade'] : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">You are not enrolled in any courses.</p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2 class="section-title">Available Courses for Enrollment</h2>
                <?php if (count($available_courses) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Credits</th>
                                <th>Term</th>
                                <th>Enrollment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_courses as $course): ?>
                                <tr>
                                    <td><?php echo $course['course_code']; ?></td>
                                    <td><?php echo $course['course_name']; ?></td>
                                    <td><?php echo $course['department_name']; ?></td>
                                    <td><?php echo $course['credit_hours']; ?></td>
                                    <td><?php echo $course['semester'] . ' ' . $course['academic_year']; ?></td>
                                    <td><?php echo $course['current_enrollment'] . '/' . $course['max_students']; ?></td>
                                    <td>
                                        <?php if ($course['current_enrollment'] < $course['max_students']): ?>
                                            <form action="enroll_course.php" method="post">
                                                <input type="hidden" name="offering_id" value="<?php echo $course['offering_id']; ?>">
                                                <button type="submit" class="enroll-btn" name="enroll">Enroll</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="full">Course Full</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No courses available for enrollment at this time.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>