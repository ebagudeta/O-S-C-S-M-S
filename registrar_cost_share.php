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

// Check if the user is a registrar
if ($role !== 'registrar') {
    // Not authorized, redirect to dashboard
    header("Location: dashboard.php");
    exit;
}

// Database connection parameters for XAMPP
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (blank)

// Create database connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get academic year and semester for display
$currentYear = date('Y');
$prevYear = $currentYear - 1;
$nextYear = $currentYear + 1;

$month = date('n');
if ($month >= 9 && $month <= 12) {
    $semester = 'First Semester';
    $academicYear = "$currentYear/$nextYear";
} else if ($month >= 1 && $month <= 5) {
    $semester = 'Second Semester';
    $academicYear = "$prevYear/$currentYear";
} else {
    $semester = 'Summer Term';
    $academicYear = "$prevYear/$currentYear";
}

// Initialize variables to prevent undefined variable errors
$costSharingSummary = [
    'totalStudents' => 0,
    'fullCostSharing' => 0,
    'partialCostSharing' => 0,
    'fullScholarship' => 0,
    'totalAmount' => 0,
    'collectedAmount' => 0,
    'pendingAmount' => 0
];

$departmentData = [];
$categoryData = [];

// Try to get cost sharing data from database
try {
    // Get total count of students
    $query = "SELECT COUNT(*) as total FROM students";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $costSharingSummary['totalStudents'] = $row['total'];
    }
    
    // You would add more queries here to get actual data from your database
    // For now, let's use some sample data
    $costSharingSummary = [
        'totalStudents' => 2345,
        'fullCostSharing' => 1820,
        'partialCostSharing' => 420,
        'fullScholarship' => 105,
        'totalAmount' => 12540000,
        'collectedAmount' => 9876000,
        'pendingAmount' => 2664000
    ];

    $departmentData = [
        ['department' => 'Computer Science', 'students' => 320, 'totalFee' => 1920000, 'collected' => 1740000, 'pending' => 180000, 'percentage' => '90.6%'],
        ['department' => 'Electrical Engineering', 'students' => 290, 'totalFee' => 1740000, 'collected' => 1450000, 'pending' => 290000, 'percentage' => '83.3%'],
        ['department' => 'Mechanical Engineering', 'students' => 310, 'totalFee' => 1860000, 'collected' => 1680000, 'pending' => 180000, 'percentage' => '90.3%'],
        ['department' => 'Civil Engineering', 'students' => 280, 'totalFee' => 1680000, 'collected' => 1520000, 'pending' => 160000, 'percentage' => '90.5%'],
        ['department' => 'Business Administration', 'students' => 350, 'totalFee' => 1750000, 'collected' => 1420000, 'pending' => 330000, 'percentage' => '81.1%']
    ];

    $categoryData = [
        ['category' => 'Regular', 'students' => 1640, 'costShare' => 60, 'totalFee' => 8200000, 'collected' => 7500000, 'percentage' => '91.5%'],
        ['category' => 'Extension', 'students' => 450, 'costShare' => 100, 'totalFee' => 2700000, 'collected' => 1820000, 'percentage' => '67.4%'],
        ['category' => 'Summer', 'students' => 255, 'costShare' => 80, 'totalFee' => 1640000, 'collected' => 1020000, 'percentage' => '62.2%']
    ];
    
} catch (Exception $e) {
    // If there's an error, we'll just use the default empty values
    // In a production environment, you'd want to log this error
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Cost Sharing (Registrar View)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5D5CDE;
            --primary-dark: #4A49B0;
            --bg-light: #FFFFFF;
            --bg-dark: #181818;
            --text-light: #333333;
            --text-dark: #F5F5F5;
            --border-light: #e0e0e0;
            --border-dark: #444444;
            --success-color: #4CAF50;
            --warning-color: #FFC107;
            --danger-color: #F44336;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: var(--text-light);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo i {
            font-size: 24px;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-role {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .nav-links a i {
            margin-right: 5px;
        }
        
        .nav-links a:hover {
            color: var(--primary-dark);
        }
        
        .page-title {
            margin: 20px 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .page-title i {
            margin-right: 10px;
            font-size: 24px;
        }
        
        .button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .button i {
            margin-right: 5px;
        }
        
        .button:hover {
            background-color: var(--primary-dark);
        }
        
        .section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-card i {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-card.warning .stat-value {
            color: var(--warning-color);
        }
        
        .stat-card.success .stat-value {
            color: var(--success-color);
        }
        
        .stat-card.danger .stat-value {
            color: var(--danger-color);
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }
        
        th {
            background-color: #f9f9f9;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .progress-bar {
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: var(--primary-color);
        }
        
        .search-box {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .search-box button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-item {
            display: flex;
            flex-direction: column;
        }
        
        .filter-label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #666;
        }
        
        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-left: auto;
            align-self: flex-end;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-warning {
            background-color: #fff8e1;
            color: #f57f17;
        }
        
        .badge-danger {
            background-color: #ffebee;
            color: #c62828;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 14px;
            margin-top: 40px;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 14px;
            }
            
            .filter-section {
                flex-direction: column;
            }
            
            .filter-buttons {
                margin-left: 0;
                margin-top: 10px;
            }
        }
        
        /* Dark mode styles */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: var(--bg-dark);
                color: var(--text-dark);
            }
            
            header, .section, .stat-card {
                background-color: #222;
                color: var(--text-dark);
            }
            
            th {
                background-color: #333;
                color: #ddd;
            }
            
            th, td {
                border-bottom: 1px solid var(--border-dark);
            }
            
            .filter-section {
                background-color: #2a2a2a;
            }
            
            .filter-label {
                color: #aaa;
            }
            
            .filter-input {
                background-color: #333;
                border-color: #444;
                color: #fff;
            }
            
            .badge-success {
                background-color: rgba(46, 125, 50, 0.2);
            }
            
            .badge-warning {
                background-color: rgba(245, 127, 23, 0.2);
            }
            
            .badge-danger {
                background-color: rgba(198, 40, 40, 0.2);
            }
            
            .progress-bar {
                background-color: #444;
            }
            
            .search-box input {
                background-color: #333;
                border-color: #444;
                color: #fff;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <h1>OCSMS</h1>
        </div>
        <div class="user-info">
            <span class="user-role"><?php echo ucfirst($role); ?></span>
            <span><?php echo $name; ?></span>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-title">
            <div>
                <i class="fas fa-money-bill"></i>
                <h1>Cost Sharing Management</h1>
            </div>
            <a href="#" class="button" onclick="alert('Export functionality would go here')">
                <i class="fas fa-file-export"></i> Export Data
            </a>
        </div>

        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                Current Cost Sharing Summary
            </h2>
            <div class="academic-info">
                <p><strong>Academic Year:</strong> <?php echo $academicYear; ?> | <strong>Semester:</strong> <?php echo $semester; ?></p>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-title">Total Students</div>
                    <div class="stat-value"><?php echo number_format($costSharingSummary['totalStudents']); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-graduate"></i>
                    <div class="stat-title">Full Cost Sharing</div>
                    <div class="stat-value"><?php echo number_format($costSharingSummary['fullCostSharing']); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-tag"></i>
                    <div class="stat-title">Partial Cost Sharing</div>
                    <div class="stat-value"><?php echo number_format($costSharingSummary['partialCostSharing']); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-award"></i>
                    <div class="stat-title">Full Scholarship</div>
                    <div class="stat-value"><?php echo number_format($costSharingSummary['fullScholarship']); ?></div>
                </div>
                <div class="stat-card success">
                    <i class="fas fa-hand-holding-usd"></i>
                    <div class="stat-title">Total Amount (ETB)</div>
                    <div class="stat-value"><?php echo number_format($costSharingSummary['totalAmount']); ?></div>
                </div>
                <div class="stat-card success">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-title">Collected Amount (ETB)</div>
                    <div class="stat-value"><?php echo number_format($costSharingSummary['collectedAmount']); ?></div>
                </div>
                <div class="stat-card warning">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="stat-title">Pending Amount (ETB)</div>
                    <div class="stat-value"><?php echo number_format($costSharingSummary['pendingAmount']); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-pie"></i>
                    <div class="stat-title">Collection Rate</div>
                    <div class="stat-value"><?php echo round(($costSharingSummary['collectedAmount'] / $costSharingSummary['totalAmount']) * 100, 1); ?>%</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-search"></i>
                Find Student Cost Sharing
            </h2>
            <div class="search-box">
                <input type="text" placeholder="Enter student ID, name or department">
                <button type="button"><i class="fas fa-search"></i> Search</button>
            </div>
            <div class="filter-section">
                <div class="filter-item">
                    <label class="filter-label">Department</label>
                    <select class="filter-input">
                        <option value="">All Departments</option>
                        <option value="cs">Computer Science</option>
                        <option value="ee">Electrical Engineering</option>
                        <option value="me">Mechanical Engineering</option>
                        <option value="ce">Civil Engineering</option>
                        <option value="ba">Business Administration</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label class="filter-label">Category</label>
                    <select class="filter-input">
                        <option value="">All Categories</option>
                        <option value="regular">Regular</option>
                        <option value="extension">Extension</option>
                        <option value="summer">Summer</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label class="filter-label">Payment Status</label>
                    <select class="filter-input">
                        <option value="">All Statuses</option>
                        <option value="paid">Paid</option>
                        <option value="partial">Partially Paid</option>
                        <option value="unpaid">Unpaid</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label class="filter-label">Year</label>
                    <select class="filter-input">
                        <option value="">All Years</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="5">5th Year</option>
                    </select>
                </div>
                <div class="filter-buttons">
                    <button class="button" type="button"><i class="fas fa-filter"></i> Apply Filters</button>
                    <button class="button" type="button" style="background-color: #777;"><i class="fas fa-redo"></i> Reset</button>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-building"></i>
                Cost Sharing by Department
            </h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Students</th>
                            <th>Total Fee (ETB)</th>
                            <th>Collected (ETB)</th>
                            <th>Pending (ETB)</th>
                            <th>Collection Rate</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departmentData as $dept): ?>
                            <tr>
                                <td><?php echo $dept['department']; ?></td>
                                <td><?php echo number_format($dept['students']); ?></td>
                                <td><?php echo number_format($dept['totalFee']); ?></td>
                                <td><?php echo number_format($dept['collected']); ?></td>
                                <td><?php echo number_format($dept['pending']); ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $dept['percentage']; ?>"></div>
                                    </div>
                                    <div style="text-align: right; font-size: 12px; margin-top: 5px;">
                                        <?php echo $dept['percentage']; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $percentage = floatval(str_replace('%', '', $dept['percentage']));
                                        if ($percentage >= 90) {
                                            echo '<span class="badge badge-success">Good</span>';
                                        } else if ($percentage >= 70) {
                                            echo '<span class="badge badge-warning">Average</span>';
                                        } else {
                                            echo '<span class="badge badge-danger">Low</span>';
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-layer-group"></i>
                Cost Sharing by Category
            </h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Students</th>
                            <th>Cost Share (%)</th>
                            <th>Total Fee (ETB)</th>
                            <th>Collected (ETB)</th>
                            <th>Collection Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryData as $cat): ?>
                            <tr>
                                <td><?php echo $cat['category']; ?></td>
                                <td><?php echo number_format($cat['students']); ?></td>
                                <td><?php echo $cat['costShare']; ?>%</td>
                                <td><?php echo number_format($cat['totalFee']); ?></td>
                                <td><?php echo number_format($cat['collected']); ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $cat['percentage']; ?>"></div>
                                    </div>
                                    <div style="text-align: right; font-size: 12px; margin-top: 5px;">
                                        <?php echo $cat['percentage']; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> Online Course/Student Management System. All rights reserved.
    </footer>

    <script>
        // Simple interactivity
        document.querySelectorAll('.search-box button').forEach(button => {
            button.addEventListener('click', function() {
                const searchInput = this.previousElementSibling.value;
                if (searchInput.trim()) {
                    alert("Searching for: " + searchInput + "\nThis would perform a database search in a real application.");
                } else {
                    alert("Please enter a search term");
                }
            });
        });
        
        document.querySelectorAll('.filter-buttons .button').forEach(button => {
            if (button.textContent.includes('Apply')) {
                button.addEventListener('click', function() {
                    alert("Filters would be applied in a real application.");
                });
            } else if (button.textContent.includes('Reset')) {
                button.addEventListener('click', function() {
                    document.querySelectorAll('.filter-input').forEach(input => {
                        input.value = '';
                    });
                    alert("Filters have been reset.");
                });
            }
        });
    </script>
</body>
</html>