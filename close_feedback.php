<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header("Location: index.php");
    exit;
}

// Check if user has the correct role
if ($_SESSION['role'] !== 'Cost_sharing_officer') {
    // Not authorized, redirect to dashboard
    header("Location: dashboard.php");
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'];

// Check if feedback ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect back to feedback list
    header("Location: view_feedback.php");
    exit;
}

$feedback_id = $_GET['id'];

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$db_username = 'root';
$db_password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update feedback status to closed
    $stmt = $pdo->prepare("
        UPDATE feedback
        SET status = 'closed', updated_at = NOW()
        WHERE feedback_id = ?
    ");
    $stmt->execute([$feedback_id]);
    
    // Get student user ID for notification
    $stmt = $pdo->prepare("
        SELECT user_id FROM feedback WHERE feedback_id = ?
    ");
    $stmt->execute([$feedback_id]);
    $student_user_id = $stmt->fetchColumn();
    
    if ($student_user_id) {
        // Create notification for the student
        $notification_message = "Your feedback has been marked as closed by the cost sharing officer.";
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, type, feedback_id, is_read, created_at)
            VALUES (?, ?, 'feedback_closed', ?, 0, NOW())
        ");
        $stmt->execute([$student_user_id, $notification_message, $feedback_id]);
    }
    
    // Set success message in session
    $_SESSION['success_message'] = "Feedback has been marked as closed";
    
    // Redirect back to feedback detail
    header("Location: view_feedback.php?id=$feedback_id");
    exit;
    
} catch (PDOException $e) {
    // Set error message in session
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    
    // Redirect back to feedback detail
    header("Location: view_feedback.php?id=$feedback_id");
    exit;
}