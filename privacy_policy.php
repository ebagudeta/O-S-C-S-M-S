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
    <title>Privacy Policy</title>
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
        <h1>Privacy Policy</h1>
    </header>

    <div class="content-container">
        <h2>Introduction</h2>
        <p>Your privacy is important to us. This policy outlines how we collect, use, and protect your information.</p>

        <h2>Information Collection</h2>
        <p>We collect personal information when you register on our site, fill out forms, or interact with our services.</p>

        <h2>Use of Information</h2>
        <p>Your information helps us to improve our services, communicate with you, and provide a better user experience.</p>

        <h2>Data Protection</h2>
        <p>We implement various security measures to maintain the safety of your personal information.</p>

        <h2>Changes to This Policy</h2>
        <p>We may update this policy periodically. Any changes will be posted on this page.</p>

        <a href="dashboard.php" class="back-button">Back to Dashboard</a>
    </div>

    <div id="footer">
        <footer>
            <p>&copy; <?php echo date('Y'); ?> MaU Online Student Cost Sharing Management System</p>
        </footer>
    </div>
</body>
</html>