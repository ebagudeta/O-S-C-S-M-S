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

// Check for login note
$note = '';
if (isset($_SESSION['login_note'])) {
    $note = $_SESSION['login_note'];
    unset($_SESSION['login_note']);
}

// Function to get unread notifications
function getUnreadNotifications($user_id) {
    // In a real application, you would fetch this from a database
    // For now, we'll simulate with dummy data
    
    // Database connection parameters
    $host = 'localhost';
    $dbname = 'ocsms';
    $db_username = 'root';
    $db_password = '';
    
    try {
        // Create PDO connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Query to get unread notifications for the user
        $stmt = $pdo->prepare("
            SELECT n.*, f.subject 
            FROM notifications n
            LEFT JOIN feedback f ON n.feedback_id = f.feedback_id
            WHERE n.user_id = ? AND n.is_read = 0
            ORDER BY n.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If database error, return empty array
        // In production, you might want to log this error
        return [];
    }
}

// Mark notification as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    
    // Database connection parameters
    $host = 'localhost';
    $dbname = 'ocsms';
    $db_username = 'root';
    $db_password = '';
    
    try {
        // Create PDO connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Update notification to mark as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        
        // Redirect to avoid form resubmission
        header("Location: dashboard.php");
        exit;
    } catch (PDOException $e) {
        // Handle error gracefully
        $error = "Database error: " . $e->getMessage();
    }
}

// Get unread notifications
$notifications = getUnreadNotifications($user_id);
$unread_count = count($notifications);

// Function to get role-specific features
function getRoleFeatures($role) {
    $features = [];
    
    switch ($role) {
        case 'registrar':
            $features = [
                ['id' => 'manageAccount', 'title' => 'Manage Account', 'icon' => 'user-cog', 'link' => 'manage_account.php'],
                ['id' => 'uploadStudentList', 'title' => 'Upload Student List', 'icon' => 'file-upload', 'link' => 'student_list.php'],
                ['id' => 'viewCostShare', 'title' => 'View Cost Share', 'icon' => 'money-bill', 'link' => 'registrar_cost_share.php'],
                ['id' => 'viewReport', 'title' => 'View Report', 'icon' => 'file-alt', 'link' => 'view_report.php'],
                
                // Other features...
            ];
            break;
        case 'student':
            $features = [
                ['id' => 'viewCostShare', 'title' => 'View Cost Share', 'icon' => 'money-bill', 'link' => 'cost_sharing.php'],
                ['id' => 'fillCostShare', 'title' => 'Fill Cost Share', 'icon' => 'pen-to-square', 'link' => 'fill_cost_share.php'],
                ['id' => 'makePayment', 'title' => 'Make Payment', 'icon' => 'credit-card', 'link' => 'make_Payment.php'],
                ['id' => 'sendFeedback', 'title' => 'Send Feedback', 'icon' => 'comments', 'link' => 'send_feedback.php'],
            ];
            break;
        case 'cost_sharing_officer':
            $features = [
               
                ['id' => 'registerCost', 'title' => 'Register Cost', 'icon' => 'plus-circle', 'link' => 'register_cost.php',
                 'description' => 'Register new cost share rates for departments.'],
                 
                ['id' => 'updateCost', 'title' => 'Update Cost', 'icon' => 'edit', 'link' => 'update_cost.php',
                 'description' => 'Update existing cost share rates.'],
                 
                ['id' => 'viewCostShare', 'title' => 'View Cost Share', 'icon' => 'eye', 'link' => 'cost_sharing.php',
                 'description' => 'View and manage cost share agreements.'],
                 
            ['id' => 'viewFeedback', 'title' => 'View Feedback', 'icon' => 'comment-dots', 'link' => 'view_feedbacks.php',
                 'description' => 'Review feedback from students about cost sharing.'],
                 
                ['id' => 'viewStudentList', 'title' => 'View Student List', 'icon' => 'users', 'link' => 'students_list.php',
                 'description' => 'Access and review student information.'],
                 
                ['id' => 'sendReport', 'title' => 'Send Report', 'icon' => 'file-export', 'link' => 'f_send_feedback.php',
                 'description' => 'Generate and send reports to management.'],
            ];
            break;

        // Other roles...
    }
    
    return $features;
}

// Get features for the current role
$features = getRoleFeatures($role);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaUOSCSMS - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
 <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="Description of the image" width="80" height="70">
            <h1>MaUOSCSMS</h1>
        </div>
        <div class="user-info">
            <span class="user-role"><?php echo ucfirst($role); ?></span>
            <span><?php echo $name; ?></span>
            
            <!-- Notification Center -->
            <div class="notification-container">
                <div class="notification-bell" id="notificationBell">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                    <span class="notification-count"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <?php if ($unread_count > 0): ?>
                        <form method="post" action="mark_all_read.php">
                            <button type="submit" class="mark-read-button">Mark all as read</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($notifications)): ?>
                        <div class="no-notifications">
                            No new notifications
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <div class="notification-title">
                                    <i class="fas fa-comment-dots"></i> 
                                    Feedback Response
                                </div>
                                <div class="notification-message">
                                    <?php 
                                    $subject = isset($notification['subject']) ? $notification['subject'] : 'Your feedback';
                                    echo "Response received for: " . htmlspecialchars($subject);
                                    ?>
                                </div>
                                <div class="notification-time">
                                    <?php 
                                    $time = isset($notification['created_at']) ? strtotime($notification['created_at']) : time();
                                    echo date('M d, Y h:i A', $time);
                                    ?>
                                </div>
                                <div class="notification-actions">
                                    <a href="view_feedback.php?id=<?php echo isset($notification['feedback_id']) ? $notification['feedback_id'] : '0'; ?>" class="view-link">
                                        View Response
                                    </a>
                                    <form method="post" action="">
                                        <input type="hidden" name="notification_id" value="<?php echo isset($notification['notification_id']) ? $notification['notification_id'] : '0'; ?>">
                                        <button type="submit" name="mark_read" class="mark-read-button">
                                            Mark as read
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="notification-footer">
                        <a href="all_notifications.php">See all notifications</a>
                    </div>
                </div>
            </div>
            
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h2 class="welcome-heading">Welcome, <?php echo $name; ?>!</h2>
            <p>You are logged in as a <?php echo ucfirst($role); ?>. Below are your available features.</p>
            
            <?php if (!empty($note)): ?>
                <div class="note">
                    <strong>Note:</strong> <?php echo $note; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="features-grid">
            <?php foreach ($features as $feature): ?>
                <a href="<?php echo $feature['link']; ?>" style="text-decoration: none; color: inherit;">
                    <div class="feature-card">
                        <div class="feature-header">
                            <div class="feature-icon">
                                <i class="fas fa-<?php echo $feature['icon']; ?>"></i>
                            </div>
                            <div class="feature-title"><?php echo $feature['title']; ?></div>
                        </div>
                        <p><?php echo isset($feature['description']) ? $feature['description'] : 'Access this feature to manage related operations.'; ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <footer class="site-footer" style=" margin-top: 85px;">
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
    <script>
        // Toggle notification dropdown
        document.getElementById('notificationBell').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notificationDropdown').classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown.classList.contains('show') && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>