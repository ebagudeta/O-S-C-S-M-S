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
    <title>Terms of Service</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        header {
            background: #f4f4f4;
            color: black;
            padding: 10px 0;
            text-align: center;
        }
        .content-container {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
            margin: 20px auto;
            text-align: left;
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
        #footer {
            margin-top: 100px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Terms of Service</h1>
    </header>

    <div class="content-container">
        <h2>Acceptance of Terms</h2>
        <p>By using our services, you agree to these terms. If you do not agree, please do not use our services.</p>

        <h2>Service Description</h2>
        <p>We provide an online cost sharing management system for students at Mattu University.</p>

        <h2>User Responsibilities</h2>
        <p>Users are responsible for maintaining the confidentiality of their account information and for all activities under their account.</p>

        <h2>Limitation of Liability</h2>
        <p>We are not liable for any damages resulting from the use of our services.</p>

        <h2>Modifications</h2>
        <p>We reserve the right to modify these terms at any time. Continued use of our services indicates acceptance of the revised terms.</p>

        <a href="dashboard.php" class="back-button">Back to Dashboard</a>
    </div>

    <div id="footer">
        <footer>
            <p>&copy; <?php echo date('Y'); ?> MaU Online Student Cost Sharing Management System</p>
        </footer>
    </div>
</body>
</html>