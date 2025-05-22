<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cost_sharing_officer') {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = htmlspecialchars(trim($_POST['feedback']));
    
    // Validate feedback
    if (!empty($feedback)) {
        // Send feedback to registrar and finance officer (email simulation)
        $to = 'ebasmart8@gmail.com, zakariyas@gmail.com';
        $subject = 'Feedback from Cost Sharing Officer';
        $message = "Feedback from: $name\n\n$feedback";
        $headers = 'From: noreply@example.com' . "\r\n" .
                   'Reply-To: noreply@example.com';

        // Mail function (uncomment in production)
        // mail($to, $subject, $message, $headers);

        // Redirect after sending
        header("Location: feedback_sent.php");
        exit;
    } else {
        $error = "Please enter your feedback.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
         #foter{

            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 14px;
            margin-top: 100px;
            margin-left: -40px;
            margin-right: -40px;

        }
        header {
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
            background:  #f4f4f4;
            color: black;
            padding: 10px 0;
    

        }
        .feedback-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            margin-bottom: 15px;
            resize: vertical;
        }
        .submit-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .submit-btn:hover {
            background-color: #218838;
        }
        .error-message {
            color: #b91c1c;
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <header>
        <h1>Send Feedback</h1>
    </header>

    <div class="feedback-container">
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <textarea name="feedback" placeholder="Enter your feedback here..." required></textarea>
            <button type="submit" class="submit-btn">Send Feedback</button>
        </form>
    </div>

   <div id="foter">
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
    </div>
</footer>
</body>
</html>