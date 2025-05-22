<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$name = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Sent</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">

    <style>
        #foter{

            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 14px;
            margin-top: 100px;
            margin-left: -40px;
            margin-right: -40px;

        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        header {
            background:  #f4f4f4;
            color: black;
            padding: 10px 0;
            text-align: center;
        }
        .confirmation-container {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
            margin: 20px auto;
            text-align: center;
        }
        .back-button {
            background: #28a745;
            color: #fff;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            display: inline-block;
        }
        .back-button:hover {
            background: #218838;
        }
        footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>
<body>
    <header>
        <h1>Feedback Sent</h1>
    </header>

    <div class="confirmation-container">
        <h2>Thank You, <?php echo htmlspecialchars($name); ?>!</h2>
        <p>Your feedback has been successfully sent.</p>
        <a href="dashboard.php" class="back-button">Go Back to Dashboard</a>
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