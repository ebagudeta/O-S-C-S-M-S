<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header("Location: index.php");
    exit;
}

// Check if user has the correct role
if ($_SESSION['role'] !== 'cost_sharing_officer') {
    // Not authorized, redirect to dashboard
    header("Location: view_feedback.php");
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
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
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // View a specific feedback if ID is provided
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $feedback_id = $_GET['id'];
        
        // Get the feedback details
        $stmt = $pdo->prepare("
            SELECT f.*, u.name as student_name, s.student_id
            FROM feedback f
            JOIN system_users u ON f.user_id = u.user_id
            JOIN students s ON u.user_id = s.user_id
            WHERE f.feedback_id = ?
        ");
        $stmt->execute([$feedback_id]);
        $current_feedback = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get responses to this feedback
        if ($current_feedback) {
            $stmt = $pdo->prepare("
                SELECT r.*, u.name as responder_name, u.role as responder_role
                FROM feedback_responses r
                JOIN system_users u ON r.responder_id = u.user_id
                WHERE r.feedback_id = ?
                ORDER BY r.response_date ASC
            ");
            $stmt->execute([$feedback_id]);
            $current_feedback['responses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Mark associated notification as read if it exists
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE feedback_id = ? AND user_id = ?
            ");
            $stmt->execute([$feedback_id, $user_id]);
        }
    }
    
    // Submit a response to feedback
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_response'])) {
        $feedback_id = $_POST['feedback_id'];
        $response_text = trim($_POST['response_text']);
        
        if (empty($response_text)) {
            $error_message = "Response text cannot be empty";
        } else {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert response
            $stmt = $pdo->prepare("
                INSERT INTO feedback_responses (feedback_id, responder_id, response_text, response_date)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$feedback_id, $user_id, $response_text]);
            
            // Update feedback status
            $stmt = $pdo->prepare("
                UPDATE feedback
                SET status = 'responded', updated_at = NOW()
                WHERE feedback_id = ?
            ");
            $stmt->execute([$feedback_id]);
            
            // Create notification for the student
            $stmt = $pdo->prepare("
                SELECT user_id FROM feedback WHERE feedback_id = ?
            ");
            $stmt->execute([$feedback_id]);
            $student_user_id = $stmt->fetchColumn();
            
            if ($student_user_id) {
                $notification_message = "Your feedback has received a response from the cost sharing officer.";
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, message, type, feedback_id, is_read, created_at)
                    VALUES (?, ?, 'feedback_response', ?, 0, NOW())
                ");
                $stmt->execute([$student_user_id, $notification_message, $feedback_id]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            $success_message = "Response submitted successfully";
            
            // Refresh the current feedback data
            $stmt = $pdo->prepare("
                SELECT f.*, u.name as student_name, s.student_id
                FROM feedback f
                JOIN system_users u ON f.user_id = u.user_id
                JOIN students s ON u.user_id = s.user_id
                WHERE f.feedback_id = ?
            ");
            $stmt->execute([$feedback_id]);
            $current_feedback = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get updated responses
            $stmt = $pdo->prepare("
                SELECT r.*, u.name as responder_name, u.role as responder_role
                FROM feedback_responses r
                JOIN system_users u ON r.responder_id = u.user_id
                WHERE r.feedback_id = ?
                ORDER BY r.response_date ASC
            ");
            $stmt->execute([$feedback_id]);
            $current_feedback['responses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
   // Build the SQL query for feedback list without status filter
$sql = "
    SELECT f.*, u.name as student_name, COUNT(r.response_id) as response_count
    FROM feedback f
    JOIN system_users u ON f.user_id = u.user_id
    LEFT JOIN feedback_responses r ON f.feedback_id = r.feedback_id
";

// Add search filter
$where_conditions = [];
$params = [];
if (!empty($search_term)) {
    $where_conditions[] = "(f.subject LIKE ? OR f.message LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

// Combine WHERE conditions if any
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Group by and order
$sql .= " GROUP BY f.feedback_id ORDER BY f.submission_date DESC"; // Change created_at to submission_date

// Execute the query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedback_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback - MaUOSCSMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Feedback page specific styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            font-size: 24px;
            color: #5D5CDE;
        }
        
        .feedback-container {
            display: flex;
            gap: 20px;
            min-height: 500px;
        }
        
        .feedback-list {
            flex: 1;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 350px;
        }
        
        .list-filters {
            padding: 15px;
            border-bottom: 1px solid #eee;
            background-color: #f9f9f9;
        }
        
        .search-form {
            display: flex;
            margin-bottom: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 14px;
        }
        
        .search-button {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .filter-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
        }
        
        .filter-tab {
            padding: 10px 15px;
            cursor: pointer;
            color: #666;
            font-weight: 500;
            position: relative;
        }
        
        .filter-tab.active {
            color: #5D5CDE;
        }
        
        .filter-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #5D5CDE;
        }
        
        .feedback-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }
        
        .feedback-item:hover {
            background-color: #f5f5f5;
        }
        
        .feedback-item.active {
            background-color: #f0f0ff;
            border-left: 3px solid #5D5CDE;
        }
        
        .feedback-subject {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .feedback-meta {
            font-size: 12px;
            color: #777;
            display: flex;
            justify-content: space-between;
        }
        
        .feedback-date {
            font-size: 12px;
            color: #777;
        }
        
        .feedback-status {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-responded {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-closed {
            background-color: #e0e0e0;
            color: #555;
        }
        
        .feedback-detail {
            flex: 2;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .detail-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #777;
            font-size: 16px;
            text-align: center;
            padding: 20px;
        }
        
        .detail-placeholder i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
        
        .detail-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background-color: #f9f9f9;
        }
        
        .detail-subject {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .detail-meta {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .detail-student {
            display: flex;
            align-items: center;
        }
        
        .detail-student i {
            margin-right: 5px;
            color: #5D5CDE;
        }
        
        .detail-date {
            display: flex;
            align-items: center;
        }
        
        .detail-date i {
            margin-right: 5px;
            color: #5D5CDE;
        }
        
        .detail-content {
            padding: 20px;
            flex-grow: 1;
            overflow-y: auto;
        }
        
        .feedback-message {
            line-height: 1.6;
            margin-bottom: 30px;
            color: #333;
            white-space: pre-line;
        }
        
        .responses-section {
            margin-top: 20px;
        }
        
        .responses-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #5D5CDE;
        }
        
        .response-item {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 3px solid #5D5CDE;
        }
        
        .response-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .response-author {
            font-weight: 600;
            color: #5D5CDE;
        }
        
        .response-role {
            font-size: 12px;
            color: #777;
            margin-left: 5px;
        }
        
        .response-date {
            font-size: 12px;
            color: #777;
        }
        
        .response-text {
            line-height: 1.6;
            color: #333;
            white-space: pre-line;
        }
        
        .response-form {
            margin-top: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
        }
        
        .form-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #5D5CDE;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        textarea.form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            min-height: 120px;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .submit-btn:hover {
            background-color: #4A49B0;
        }
        
        .message-alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .error-alert {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }
        
        .success-alert {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .empty-list {
            padding: 30px 20px;
            text-align: center;
            color: #777;
        }
        
        .empty-list i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
        
        /* Dark mode adjustments */
        @media (prefers-color-scheme: dark) {
            .feedback-list, .feedback-detail {
                background-color: #222;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .list-filters, .detail-header {
                background-color: #2a2a2a;
                border-bottom-color: #333;
            }
            
            .filter-tabs, .feedback-item {
                border-bottom-color: #333;
            }
            
            .filter-tab {
                color: #bbb;
            }
            
            .feedback-item:hover {
                background-color: #2a2a2a;
            }
            
            .feedback-item.active {
                background-color: #2a2a3c;
            }
            
            .feedback-subject, .detail-subject {
                color: #f5f5f5;
            }
            
            .search-input {
                background-color: #333;
                border-color: #444;
                color: #f5f5f5;
            }
            
            .response-item {
                background-color: #2a2a2a;
            }
            
            .feedback-message, .response-text {
                color: #e0e0e0;
            }
            
            .detail-meta {
                color: #bbb;
            }
            
            .response-form {
                background-color: #2a2a2a;
            }
            
            textarea.form-control {
                background-color: #333;
                border-color: #444;
                color: #f5f5f5;
            }
            
            .status-pending {
                background-color: rgba(254, 243, 199, 0.2);
                color: #fbbf24;
            }
            
            .status-responded {
                background-color: rgba(209, 250, 229, 0.2);
                color: #34d399;
            }
            
            .status-closed {
                background-color: rgba(224, 224, 224, 0.2);
                color: #bbb;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="University Logo" width="80" height="70">
            <h1>MaUOSCSMS</h1>
        </div>
        <div class="user-info">
            <span class="user-role">Cost_sharing_officer</span>
            <span><?php echo htmlspecialchars($name); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="page-header">
            <h2 class="page-title"><i class="fas fa-comment-dots"></i> Student Feedback Management</h2>
            <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if (!empty($error_message)): ?>
        <div class="message-alert error-alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
        <div class="message-alert success-alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>
        
        <div class="feedback-container">
            <div class="feedback-list">
                <div class="list-filters">
                    <form method="get" action="" class="search-form">
                        <input type="text" name="search" placeholder="Search feedback..." class="search-input" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
                    </form>
                    
                    <div class="filter-tabs">
                        <a href="?status=all<?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" class="filter-tab <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                            All
                        </a>
                        <a href="?status=pending<?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" class="filter-tab <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
                            Pending
                        </a>
                        <a href="?status=responded<?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" class="filter-tab <?php echo $filter_status === 'responded' ? 'active' : ''; ?>">
                            Responded
                        </a>
                        <a href="?status=closed<?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" class="filter-tab <?php echo $filter_status === 'closed' ? 'active' : ''; ?>">
                            Closed
                        </a>
                    </div>
                </div>
                
                <?php if (empty($feedback_list)): ?>
                <div class="empty-list">
                    <i class="fas fa-inbox"></i>
                    <p>No feedback found</p>
                </div>
                <?php else: ?>
                    <?php foreach ($feedback_list as $feedback): ?>
                    <div class="feedback-item <?php echo (isset($current_feedback) && $current_feedback['feedback_id'] == $feedback['feedback_id']) ? 'active' : ''; ?>">
                        <a href="?id=<?php echo $feedback['feedback_id']; ?><?php echo !empty($filter_status) && $filter_status !== 'all' ? '&status='.$filter_status : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" style="text-decoration: none; color: inherit; display: block;">
                            <div class="feedback-subject"><?php echo htmlspecialchars($feedback['subject']); ?></div>
                            <div class="feedback-meta">
                                <span><?php echo htmlspecialchars($feedback['student_name']); ?></span>
                                <span class="feedback-status status-<?php echo $feedback['status']; ?>">
                                    <?php echo ucfirst($feedback['status']); ?>
                                </span>
                            </div>
                            <div class="feedback-date">
                                <?php echo date('M d, Y', strtotime($feedback['created_at'])); ?>
                                <?php if ($feedback['response_count'] > 0): ?>
                                <span style="margin-left: 10px;"><i class="fas fa-reply"></i> <?php echo $feedback['response_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="feedback-detail">
                <?php if (!$current_feedback): ?>
                <div class="detail-placeholder">
                    <i class="fas fa-comment-dots"></i>
                    <p>Select a feedback from the list to view details</p>
                </div>
                <?php else: ?>
                <div class="detail-header">
                    <div class="detail-subject"><?php echo htmlspecialchars($current_feedback['subject']); ?></div>
                    <div class="detail-meta">
                        <div class="detail-student">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($current_feedback['student_name']); ?> 
                            <span style="margin-left: 5px; color: #777;">(Student ID: <?php echo htmlspecialchars($current_feedback['student_id']); ?>)</span>
                        </div>
                        <div class="detail-date">
                            <i class="fas fa-calendar-alt"></i> 
                            <?php echo date('F d, Y \a\t h:i A', strtotime($current_feedback['created_at'])); ?>
                        </div>
                        <div>
                            <span class="feedback-status status-<?php echo $current_feedback['status']; ?>">
                                <?php echo ucfirst($current_feedback['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-content">
                    <div class="feedback-message">
                        <?php echo nl2br(htmlspecialchars($current_feedback['message'])); ?>
                    </div>
                    
                    <div class="responses-section">
                        <h3 class="responses-title">
                            <i class="fas fa-reply"></i> Responses 
                            (<?php echo count($current_feedback['responses'] ?? []); ?>)
                        </h3>
                        
                        <?php if (empty($current_feedback['responses'])): ?>
                        <p style="color: #777; font-style: italic;">No responses yet</p>
                        <?php else: ?>
                            <?php foreach ($current_feedback['responses'] as $response): ?>
                            <div class="response-item">
                                <div class="response-meta">
                                    <div>
                                        <span class="response-author"><?php echo htmlspecialchars($response['responder_name']); ?></span>
                                        <span class="response-role">(<?php echo ucfirst($response['responder_role']); ?>)</span>
                                    </div>
                                    <span class="response-date"><?php echo date('M d, Y \a\t h:i A', strtotime($response['response_date'])); ?></span>
                                </div>
                                <div class="response-text">
                                    <?php echo nl2br(htmlspecialchars($response['response_text'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if ($current_feedback['status'] !== 'closed'): ?>
                        <div class="response-form">
                            <h3 class="form-title">Add a Response</h3>
                            <form method="post" action="">
                                <input type="hidden" name="feedback_id" value="<?php echo $current_feedback['feedback_id']; ?>">
                                
                                <div class="form-group">
                                    <textarea name="response_text" class="form-control" placeholder="Type your response here..." required></textarea>
                                </div>
                                
                                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                                    <button type="submit" name="submit_response" class="submit-btn">
                                        <i class="fas fa-paper-plane"></i> Send Response
                                    </button>
                                    
                                    <?php if ($current_feedback['status'] === 'responded'): ?>
                                    <a href="close_feedback.php?id=<?php echo $current_feedback['feedback_id']; ?>" class="btn" style="background-color: #64748b;" onclick="return confirm('Are you sure you want to close this feedback?')">
                                        <i class="fas fa-check-circle"></i> Mark as Closed
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
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