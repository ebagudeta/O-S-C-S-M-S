<!-- header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'OCSMS'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your common CSS styling */
        :root {
            --primary-color: #5D5CDE;
            --primary-dark: #4A49B0;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .navbar {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .navbar-brand i {
            margin-right: 10px;
        }
        
        .navbar-links {
            display: flex;
        }
        
        .navbar-links a {
            color: white;
            margin-left: 15px;
            text-decoration: none;
        }
        
        .content {
            padding: 20px;
        }
        
        /* Add your additional CSS here */
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="navbar">
        <a href="dashboard.php" class="navbar-brand">
            <i class="fas fa-graduation-cap"></i>
            OCSMS
        </a>
        <div class="navbar-links">
            <span><?php echo $_SESSION['name']; ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
            <a href="dashboard.php">Dashboard</a>
            <?php if ($_SESSION['role'] == 'student'): ?>
                <a href="enroll_courses.php">Courses</a>
            <?php elseif ($_SESSION['role'] == 'registrar'): ?>
                <a href="student_list.php">Students</a>
                <a href="course_list.php">Courses</a>
            <?php elseif ($_SESSION['role'] == 'cost_sharing_officer'): ?>
                <a href="cost_sharing.php">Applications</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="content">