<?php
// Start session
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit;
}

// Check if form was submitted
if (!isset($_POST['enroll']) || !isset($_POST['offering_id'])) {
    $_SESSION['message'] = "Invalid enrollment request.";
    $_SESSION['message_type'] = "error";
    header("Location: view_courses.php");
    exit;
}

// Get the offering ID
$offering_id = $_POST['offering_id'];

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
    
    // Check if course offering exists and is active
    $stmt = $pdo->prepare("SELECT co.offering_id, co.max_students, c.course_name,
                          (SELECT COUNT(*) FROM enrollments e WHERE e.offering_id = co.offering_id) AS current_enrollment
                          FROM course_offerings co
                          JOIN courses c ON co.course_id = c.course_id
                          WHERE co.offering_id = ? AND co.status = 'Active'");
    $stmt->execute([$offering_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        throw new Exception("Course not found or not available for enrollment");
    }
    
    // Check if there's space available
    if ($course['current_enrollment'] >= $course['max_students']) {
        throw new Exception("This course is full and no longer accepting enrollments");
    }
    
    // Check if student is already enrolled
    $stmt = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND offering_id = ?");
    $stmt->execute([$student_id, $offering_id]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception("You are already enrolled in this course");
    }
    
    // Enroll the student
    $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, offering_id, status) VALUES (?, ?, 'Enrolled')");
    $stmt->execute([$student_id, $offering_id]);
    
    // Success message
    $_SESSION['message'] = "Successfully enrolled in " . $course['course_name'];
    $_SESSION['message_type'] = "success";
    
} catch (Exception $e) {
    $_SESSION['message'] = $e->getMessage();
    $_SESSION['message_type'] = "error";
}

// Redirect back to courses page
header("Location: view_courses.php");
exit;
?>