<?php
// Start session
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: send_feedback.php");
    exit;
}

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAM PP username
$password = '';     // Default XAMPP password (blank)

// Initialize variables
$message = '';
$message_type = '';
$courses = [];
$instructors = [];
$student_id = null;

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student information
$stmt = $conn->prepare("SELECT student_id, program_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $student_id = $row['student_id'];
    $program_id = $row['program_id'];
}
$stmt->close();

if (!$student_id) {
    die("Student record not found");
}

// Get courses for this student
$sql = "SELECT c.course_id, c.course_code, c.course_name 
        FROM courses c
        JOIN course_offerings co ON c.course_id = co.course_id
        JOIN enrollments e ON co.offering_id = e.offering_id
        WHERE e.student_id = ? 
        ORDER BY c.course_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

// Get instructors
$sql = "SELECT u.user_id, u.first_name, u.last_name
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE r.role_name = 'instructor'
        ORDER BY u.last_name, u.first_name";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $instructors[] = $row;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    try {
        // Get form data
        $feedback_type = $_POST['feedback_type'];
        $rating = $_POST['rating'];
        $comments = trim($_POST['comments']);
        $course_id = ($feedback_type == 'Course') ? $_POST['course_id'] : null;
        $instructor_id = ($feedback_type == 'Instructor') ? $_POST['instructor_id'] : null;
        
        // Validate input
        if (empty($feedback_type) || empty($comments)) {
            throw new Exception("Feedback type and comments are required");
        }
        
        if ($feedback_type == 'Course' && empty($course_id)) {
            throw new Exception("Please select a course");
        }
        
        if ($feedback_type == 'Instructor' && empty($instructor_id)) {
            throw new Exception("Please select an instructor");
        }
        
        // Insert feedback
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, feedback_type, course_id, instructor_id, rating, comments, submission_date) 
                               VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isiiss", $_SESSION['user_id'], $feedback_type, $course_id, $instructor_id, $rating, $comments);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $message = "Your feedback has been submitted successfully. Thank you!";
            $message_type = "success";
        } else {
            throw new Exception("Failed to submit feedback. Please try again.");
        }
        $stmt->close();
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Send Feedback</title>
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
            max-width: 800px;
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
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="email"],
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
        
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-text {
            margin-top: 5px;
            font-size: 14px;
            color: var(--gray-color);
        }
        
        /* Rating styles */
        .rating-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .rating-option {
            display: none;
        }
        
        .rating-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            padding: 10px 15px;
            border: 1px solid var(--gray-light);
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .rating-label:hover {
            background-color: var(--primary-light);
        }
        
        .rating-option:checked + .rating-label {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-dark);
        }
        
        .rating-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .rating-text {
            font-size: 12px;
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
        
        /* Conditional fields */
        .conditional-field {
            display: none;
            margin-top: 15px;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .rating-container {
                justify-content: space-between;
            }
            
            .rating-label {
                flex: 1 0 18%;
                min-width: 60px;
            }
        }
        
        @media (max-width: 576px) {
            .rating-label {
                flex: 1 0 40%;
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
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">Send Feedback</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Submit Your Feedback</h2>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="feedback_type">Feedback Type</label>
                        <select id="feedback_type" name="feedback_type" required onchange="toggleConditionalFields()">
                            <option value="">-- Select Feedback Type --</option>
                            <option value="Course">Course Feedback</option>
                            <option value="Instructor">Instructor Feedback</option>
                            <option value="System">System Feedback</option>
                            <option value="General">General Feedback</option>
                        </select>
                    </div>
                    
                    <div id="course_field" class="conditional-field">
                        <div class="form-group">
                            <label for="course_id">Select Course</label>
                            <select id="course_id" name="course_id">
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="instructor_field" class="conditional-field">
                        <div class="form-group">
                            <label for="instructor_id">Select Instructor</label>
                            <select id="instructor_id" name="instructor_id">
                                <option value="">-- Select Instructor --</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo $instructor['user_id']; ?>">
                                        <?php echo $instructor['first_name'] . ' ' . $instructor['last_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Rating</label>
                        <div class="rating-container">
                            <?php 
                            $ratings = [
                                1 => 'Poor',
                                2 => 'Fair',
                                3 => 'Average',
                                4 => 'Good',
                                5 => 'Excellent'
                            ];
                            
                            foreach ($ratings as $value => $text): 
                            ?>
                                <input type="radio" class="rating-option" id="rating-<?php echo $value; ?>" name="rating" value="<?php echo $value; ?>" <?php echo ($value == 5) ? 'checked' : ''; ?>>
                                <label for="rating-<?php echo $value; ?>" class="rating-label">
                                    <span class="rating-value"><?php echo $value; ?></span>
                                    <span class="rating-text"><?php echo $text; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comments">Comments</label>
                        <textarea id="comments" name="comments" required placeholder="Please share your detailed feedback here..."></textarea>
                        <div class="form-text">Your feedback helps us improve. Please be specific and constructive.</div>
                    </div>
                    
                    <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Why Your Feedback Matters</h2>
            </div>
            <div class="card-body">
                <p>Your feedback is invaluable to us as we continuously strive to improve the quality of education and services we provide. Here's how we use your feedback:</p>
                
                <ul>
                    <li><strong>Course Improvement</strong> - Your course feedback helps instructors refine their teaching materials and methods.</li>
                    <li><strong>Instructor Development</strong> - Feedback on instructors helps them grow professionally and enhance their teaching effectiveness.</li>
                    <li><strong>System Enhancement</strong> - Your suggestions about the system help us make it more user-friendly and efficient.</li>
                    <li><strong>Overall Experience</strong> - General feedback helps us understand your holistic experience and address any concerns.</li>
                </ul>
                
                <p>All feedback is kept confidential, and we appreciate your honesty and constructive input.</p>
            </div>
        </div>
    </div>

    <script>
        // Function to toggle conditional fields based on feedback type
        function toggleConditionalFields() {
            const feedbackType = document.getElementById('feedback_type').value;
            const courseField = document.getElementById('course_field');
            const instructorField = document.getElementById('instructor_field');
            
            // Hide all conditional fields first
            courseField.style.display = 'none';
            instructorField.style.display = 'none';
            
            // Show relevant field based on selection
            if (feedbackType === 'Course') {
                courseField.style.display = 'block';
            } else if (feedbackType === 'Instructor') {
                instructorField.style.display = 'block';
            }
        }
        
        // Initialize the form
        document.addEventListener('DOMContentLoaded', function() {
            toggleConditionalFields();
            
            // If there was a form submission error, restore the selected feedback type
            <?php if (!empty($message) && $message_type === 'error'): ?>
                const feedbackType = "<?php echo isset($_POST['feedback_type']) ? $_POST['feedback_type'] : ''; ?>";
                if (feedbackType) {
                    document.getElementById('feedback_type').value = feedbackType;
                    toggleConditionalFields();
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>