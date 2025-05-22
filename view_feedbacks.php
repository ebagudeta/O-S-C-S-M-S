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
$name = $_SESSION['name'] ?? '';

// Database connection parameters
$host = 'localhost';
$dbname = 'ocsms';
$db_username = 'root';
$db_password = '';

// Initialize variables
$feedback_list = [];
$current_feedback = null;
$error_message = '';
$success_message = '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First check and update the feedback table if the status column doesn't exist
    $columns = $pdo->query("SHOW COLUMNS FROM feedback LIKE 'status'")->fetchAll();
    if (empty($columns)) {
        // Add status column to feedback table if it doesn't exist
        $pdo->exec("ALTER TABLE feedback ADD COLUMN status ENUM('pending', 'responded', 'closed') DEFAULT 'pending'");
    }
    
    // View a specific feedback if ID is provided
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $feedback_id = $_GET['id'];
        
        // Get the feedback details (now including status column)
        $stmt = $pdo->prepare("
            SELECT f.*, u.name as student_name, s.student_id
            FROM feedback f
            LEFT JOIN system_users u ON f.user_id = u.user_id
            LEFT JOIN students s ON u.user_id = s.user_id
            WHERE f.feedback_id = ?
        ");
        $stmt->execute([$feedback_id]);
        $current_feedback = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get responses to this feedback
        if ($current_feedback) {
            $stmt = $pdo->prepare("
                SELECT r.*, u.name as responder_name, u.role as responder_role
                FROM feedback_responses r
                LEFT JOIN system_users u ON r.responder_id = u.user_id
                WHERE r.feedback_id = ?
                ORDER BY r.created_at ASC
            ");
            $stmt->execute([$feedback_id]);
            $current_feedback['responses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Submit a response to feedback
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_response'])) {
        $feedback_id = $_POST['feedback_id'];
        $response_text = trim($_POST['response_text']);
        
        if (empty($response_text)) {
            $error_message = "Response text cannot be empty";
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Check if feedback_responses table has the correct structure
                $columns = $pdo->query("SHOW COLUMNS FROM feedback_responses LIKE 'response_text'")->fetchAll();
                if (empty($columns)) {
                    // The field might be called 'response' instead of 'response_text'
                    $response_field = 'response';
                } else {
                    $response_field = 'response_text';
                }
                
                // Insert response - dynamically create the SQL with the correct field name
                $sql = "INSERT INTO feedback_responses (feedback_id, responder_id, $response_field, created_at) 
                        VALUES (?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$feedback_id, $user_id, $response_text]);
                
                // Update feedback status
                $stmt = $pdo->prepare("
                    UPDATE feedback
                    SET status = 'responded'
                    WHERE feedback_id = ?
                ");
                $stmt->execute([$feedback_id]);
                
                // Commit transaction
                $pdo->commit();
                $success_message = "Response submitted successfully";
                
                // Refresh the page to show the new response
                header("Location: view_feedbacks.php?id=" . $feedback_id . "&success=1");
                exit;
                
            } catch (Exception $e) {
                // Rollback transaction
                $pdo->rollBack();
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }

    // Build the SQL query for feedback list - select * to avoid missing column errors
    $sql = "
        SELECT f.*, u.name as student_name
        FROM feedback f
        LEFT JOIN system_users u ON f.user_id = u.user_id
    ";
    
    // Add search filter
    $where_conditions = [];
    $params = [];
    if (!empty($search_term)) {
        // Check if subject and comments columns exist
        $hasSubject = !empty($pdo->query("SHOW COLUMNS FROM feedback LIKE 'subject'")->fetchAll());
        $hasComments = !empty($pdo->query("SHOW COLUMNS FROM feedback LIKE 'comments'")->fetchAll());
        $hasMessage = !empty($pdo->query("SHOW COLUMNS FROM feedback LIKE 'message'")->fetchAll());
        
        $conditions = [];
        if ($hasSubject) {
            $conditions[] = "f.subject LIKE ?";
            $params[] = "%$search_term%";
        }
        if ($hasComments) {
            $conditions[] = "f.comments LIKE ?";
            $params[] = "%$search_term%";
        }
        if ($hasMessage) {
            $conditions[] = "f.message LIKE ?";
            $params[] = "%$search_term%";
        }
        $conditions[] = "u.name LIKE ?";
        $params[] = "%$search_term%";
        
        if (!empty($conditions)) {
            $where_conditions[] = "(" . implode(" OR ", $conditions) . ")";
        }
    }
    
    // Combine WHERE conditions if any
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    // Check if submission_date column exists, otherwise use created_at
    $hasSubmissionDate = !empty($pdo->query("SHOW COLUMNS FROM feedback LIKE 'submission_date'")->fetchAll());
    $dateColumn = $hasSubmissionDate ? 'submission_date' : 'created_at';
    
    // Order by date
    $sql .= " ORDER BY f.$dateColumn DESC";
    
    // Execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $feedback_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get response counts in a separate query to avoid GROUP BY issues
    foreach ($feedback_list as $key => $feedback) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM feedback_responses 
            WHERE feedback_id = ?
        ");
        $stmt->execute([$feedback['feedback_id']]);
        $feedback_list[$key]['response_count'] = $stmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Feedback Management - MaUOSCSMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5D5CDE;
            --primary-dark: #4A49B0;
            --bg-light: #FFFFFF;
            --bg-dark: #181818;
            --text-light: #333333;
            --text-dark: #F5F5F5;
            --error-color: #f44336;
            --success-color: #4CAF50;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #5D5CDE;
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .logo h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            gap: 20px;
            padding: 0 20px;
        }
        
        .feedback-list {
            flex: 1;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .feedback-list h2 {
            padding: 15px 20px;
            margin: 0;
            border-bottom: 1px solid #eee;
            color: #5D5CDE;
        }
        
        .search-form {
            padding: 15px;
            display: flex;
            border-bottom: 1px solid #eee;
        }
        
        .search-form input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        
        .search-form button {
            padding: 10px 15px;
            background: #5D5CDE;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .feedback-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .feedback-item:hover {
            background-color: #f5f5f5;
        }
        
        .feedback-item.active {
            background-color: #e0e0ff;
            border-left: 4px solid #5D5CDE;
        }
        
        .feedback-item a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .feedback-subject {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .feedback-meta {
            font-size: 12px;
            color: #777;
            display: flex;
            justify-content: space-between;
        }
        
        .feedback-detail {
            flex: 2;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .feedback-detail h3 {
            padding: 15px 20px;
            margin: 0;
            background-color: #f5f5f5;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        
        .feedback-message {
            padding: 20px;
            line-height: 1.6;
            border-bottom: 1px solid #eee;
            flex: 1;
        }
        
        .responses-section {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .responses-section h4 {
            margin-top: 0;
            color: #5D5CDE;
        }
        
        .response-item {
            padding: 15px;
            border-left: 3px solid #5D5CDE;
            background-color: #f9f9f9;
            margin-bottom: 15px;
            border-radius: 0 4px 4px 0;
        }
        
        .response-item strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .response-item p {
            margin: 5px 0 10px 0;
        }
        
        .response-item small {
            color: #777;
        }
        
        .response-form {
            padding: 20px;
            border-top: 1px solid #eee;
        }
        
        .response-form h3 {
            margin-top: 0;
            padding: 0;
            background: none;
            border: none;
            color: #5D5CDE;
        }
        
        .response-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            height: 100px;
            margin-bottom: 15px;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .submit-btn:hover {
            background-color: #4A49B0;
        }
        
        .detail-placeholder {
            padding: 30px;
            text-align: center;
            color: #777;
            font-style: italic;
        }
        
        .error-alert {
            background-color: #FFEBEE;
            color: #f44336;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 10px 15px;
            border-left: 4px solid #f44336;
        }
        
        .success-alert {
            background-color: #E8F5E9;
            color: #4CAF50;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 10px 15px;
            border-left: 4px solid #4CAF50;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #777;
            border-top: 1px solid #eee;
            margin-top: 20px;
        }
        
        .back-to-dashboard {
            display: inline-block;
            margin: 20px;
            color: #5D5CDE;
            text-decoration: none;
        }
        
        .back-to-dashboard i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .feedback-list, .feedback-detail {
                flex: none;
                width: 100%;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #181818;
                color: #f5f5f5;
            }
            
            .feedback-list, .feedback-detail {
                background-color: #222;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .feedback-list h2, .responses-section h4, .response-form h3 {
                color: #a0a0ff;
            }
            
            .feedback-item {
                border-bottom-color: #333;
            }
            
            .feedback-item:hover {
                background-color: #2a2a2a;
            }
            
            .feedback-item.active {
                background-color: #2a2a3c;
            }
            
            .feedback-subject {
                color: #e0e0e0;
            }
            
            .feedback-meta, .response-item small {
                color: #aaa;
            }
            
            .feedback-detail h3 {
                background-color: #2a2a2a;
                border-bottom-color: #333;
                color: #e0e0e0;
            }
            
            .response-item {
                background-color: #2a2a2a;
            }
            
            .search-form, .feedback-message, .responses-section, .response-form, .feedback-item {
                border-color: #333;
            }
            
            .search-form input[type="text"], .response-form textarea {
                background-color: #333;
                border-color: #444;
                color: #f5f5f5;
            }
            
            .error-alert {
                background-color: #4a1b1b;
                color: #ffb0b0;
            }
            
            .success-alert {
                background-color: #1b4a1b;
                color: #b0ffb0;
            }
            
            footer {
                border-top-color: #333;
                color: #aaa;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <h1>MaUOSCSMS</h1>
        </div>
        <div class="user-info">
            <span>Cost Sharing Officer: <?php echo htmlspecialchars($name); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>
    
    <a href="dashboard.php" class="back-to-dashboard">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="dashboard-container">
        <div class="feedback-list">
            <h2>Feedback List</h2>
            <form method="get" action="" class="search-form">
                <input type="text" name="search" placeholder="Search feedback..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>

            <?php if (!empty($error_message)): ?>
                <div class="error-alert"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="success-alert">Response submitted successfully!</div>
            <?php endif; ?>

            <?php if (empty($feedback_list)): ?>
                <p style="padding: 15px; text-align: center;">No feedback found</p>
            <?php else: ?>
                <?php foreach ($feedback_list as $feedback): ?>
                    <div class="feedback-item <?php echo (isset($current_feedback) && $current_feedback['feedback_id'] == $feedback['feedback_id']) ? 'active' : ''; ?>">
                        <a href="?id=<?php echo $feedback['feedback_id']; ?>">
                            <div class="feedback-subject">
                                <?php echo htmlspecialchars($feedback['subject'] ?? 'No Subject'); ?>
                            </div>
                            <div class="feedback-meta">
                                <span><?php echo htmlspecialchars($feedback['student_name'] ?? 'Unknown Student'); ?></span>
                                <span>
                                    <?php 
                                    $date_field = isset($feedback['submission_date']) ? 'submission_date' : 'created_at';
                                    echo isset($feedback[$date_field]) ? date('M d, Y', strtotime($feedback[$date_field])) : 'Unknown date'; 
                                    ?>
                                </span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="feedback-detail">
            <?php if (!$current_feedback): ?>
                <div class="detail-placeholder">Select a feedback from the list to view details</div>
            <?php else: ?>
                <h3><?php echo htmlspecialchars($current_feedback['subject'] ?? 'No Subject'); ?></h3>
                <div class="feedback-message">
                    <p><strong>From:</strong> <?php echo htmlspecialchars($current_feedback['student_name'] ?? 'Unknown Student'); ?></p>
                    <p><strong>Date:</strong> 
                        <?php 
                        $date_field = isset($current_feedback['submission_date']) ? 'submission_date' : 'created_at';
                        echo isset($current_feedback[$date_field]) ? date('F d, Y H:i', strtotime($current_feedback[$date_field])) : 'Unknown date'; 
                        ?>
                    </p>
                    <p><strong>Message:</strong></p>
                    <?php 
                    // Check for message content in various fields
                    $message_content = '';
                    if (isset($current_feedback['comments'])) {
                        $message_content = $current_feedback['comments'];
                    } elseif (isset($current_feedback['message'])) {
                        $message_content = $current_feedback['message'];
                    } else {
                        $message_content = 'No message content available.';
                    }
                    echo nl2br(htmlspecialchars($message_content)); 
                    ?>
                </div>

                <div class="responses-section">
                    <h4>Responses</h4>
                    <?php if (empty($current_feedback['responses'])): ?>
                        <p>No responses yet.</p>
                    <?php else: ?>
                        <?php foreach ($current_feedback['responses'] as $response): ?>
                            <div class="response-item">
                                <strong><?php echo htmlspecialchars($response['responder_name'] ?? 'Staff Member'); ?> (<?php echo htmlspecialchars(ucfirst($response['responder_role'] ?? 'Staff')); ?>)</strong>
                                <p>
                                    <?php 
                                    // Check for response content in both possible field names
                                    $response_content = '';
                                    if (isset($response['response_text'])) {
                                        $response_content = $response['response_text'];
                                    } elseif (isset($response['response'])) {
                                        $response_content = $response['response'];
                                    }
                                    echo nl2br(htmlspecialchars($response_content)); 
                                    ?>
                                </p>
                                <small>
                                    <?php 
                                    $date_field = isset($response['created_at']) ? 'created_at' : 'response_date';
                                    echo isset($response[$date_field]) ? date('M d, Y H:i', strtotime($response[$date_field])) : 'Unknown date'; 
                                    ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="response-form">
                    <h3>Add a Response</h3>
                    <form method="post" action="">
                        <input type="hidden" name="feedback_id" value="<?php echo $current_feedback['feedback_id']; ?>">
                        <textarea name="response_text" placeholder="Type your response here..." required></textarea>
                        <button type="submit" name="submit_response" class="submit-btn">Send Response</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> MaU Online Student Cost Sharing Management System</p>
    </footer>
</body>
</html>