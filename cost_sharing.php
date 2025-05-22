<?php
// Start session
session_start();

// Check if user is logged in and is a cost sharing officer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cost_sharing_officer') {
    header("Location: index.php");
    exit;
}

// Database connection parameters for XAMPP
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (blank)

// Initialize variables
$message = '';
$message_type = '';

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['approve'])) {
            $cost_share_id = $_POST['cost_share_id'];
            
            // Update status to Approved
            $stmt = $conn->prepare("UPDATE student_cost_share 
                                  SET status = 'Approved', 
                                      approved_by = ?, 
                                      approval_date = CURRENT_TIMESTAMP 
                                  WHERE cost_share_id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $cost_share_id);
            $stmt->execute();
            $stmt->close();
            
            $message = "Application approved successfully.";
            $message_type = "success";
        } else if (isset($_POST['reject'])) {
            $cost_share_id = $_POST['cost_share_id'];
            
            // Update status to Rejected
            $stmt = $conn->prepare("UPDATE student_cost_share 
                                  SET status = 'Rejected', 
                                      approved_by = ?, 
                                      approval_date = CURRENT_TIMESTAMP 
                                  WHERE cost_share_id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $cost_share_id);
            $stmt->execute();
            $stmt->close();
            
            $message = "Application rejected successfully.";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// Get pending applications
$pending_apps = [];
$sql = "SELECT scs.cost_share_id, s.student_id, s.student_number, 
              u.first_name, u.last_name, 
              p.program_name, csp.program_name AS cost_share_program,
              scs.coverage_percent, scs.academic_year
       FROM student_cost_share scs
       JOIN students s ON scs.student_id = s.student_id
       JOIN users u ON s.user_id = u.user_id
       JOIN programs p ON s.program_id = p.program_id
       JOIN cost_share_programs csp ON scs.program_id = csp.program_id
       WHERE scs.status = 'Pending'
       ORDER BY scs.academic_year, u.last_name, u.first_name";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_apps[] = $row;
    }
}

// Get recently processed applications
$processed_apps = [];
$sql = "SELECT scs.cost_share_id, s.student_id, s.student_number, 
              u.first_name, u.last_name, 
              p.program_name, scs.coverage_percent, scs.academic_year,
              scs.status, scs.approval_date,
              approver.first_name AS approver_first_name, 
              approver.last_name AS approver_last_name
       FROM student_cost_share scs
       JOIN students s ON scs.student_id = s.student_id
       JOIN users u ON s.user_id = u.user_id
       JOIN programs p ON s.program_id = p.program_id
       LEFT JOIN users approver ON scs.approved_by = approver.user_id
       WHERE scs.status IN ('Approved', 'Rejected')
       ORDER BY scs.approval_date DESC
       LIMIT 10";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $processed_apps[] = $row;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Cost Sharing Management</title>
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
            position: sticky;
            top: 0;
            z-index: 1000;
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
            transition: background-color 0.2s;
        }
        
        .logout-btn:hover {
            background-color: #bd2130;
        }
        
        /* Main container */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        /* Navigation and page header */
        .nav-link {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .nav-link i {
            margin-right: 8px;
        }
        
        .page-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
        }
        
        /* Card and section styles */
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
        
        .section-title {
            color: var(--primary-color);
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .alert.success {
            background-color: var(--success-light);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert.error {
            background-color: var(--danger-light);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        /* Table styles */
        .table-responsive {
            overflow-x: auto;
            border-radius: 4px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            font-size: 15px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
        }
        
        thead {
            background-color: var(--light-color);
            border-bottom: 2px solid var(--gray-light);
        }
        
        th {
            font-weight: 600;
            color: var(--gray-color);
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        tr:not(:last-child) {
            border-bottom: 1px solid var(--gray-light);
        }
        
        tbody tr:hover {
            background-color: rgba(93, 92, 222, 0.05);
        }
        
        /* Status badges */
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }
        
        .badge-approved {
            background-color: var(--success-light);
            color: var(--success-color);
        }
        
        .badge-rejected {
            background-color: var(--danger-light);
            color: var(--danger-color);
        }
        
        .badge-pending {
            background-color: var(--warning-light);
            color: #856404;
        }
        
        /* Button styles */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            margin-right: 5px;
        }
        
        .btn:last-child {
            margin-right: 0;
        }
        
        .btn-approve {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-approve:hover {
            background-color: #218838;
        }
        
        .btn-reject {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-reject:hover {
            background-color: #c82333;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--gray-color);
            font-style: italic;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-info {
                margin-top: 10px;
            }
            
            .table-responsive {
                margin: 0 -20px;
                width: calc(100% + 40px);
            }
            
            th, td {
                padding: 10px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 13px;
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
            <span class="role-badge">Cost Sharing Officer</span>
            <span><?php echo $_SESSION['name'] ?? 'User'; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="nav-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">Cost Sharing Management</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="section-title">Pending Applications</h2>
            </div>
            <div class="card-body">
                <?php if (count($pending_apps) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Program</th>
                                    <th>Cost Share Program</th>
                                    <th>Coverage %</th>
                                    <th>Academic Year</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_apps as $app): ?>
                                    <tr>
                                        <td><?php echo $app['student_number']; ?></td>
                                        <td><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></td>
                                        <td><?php echo $app['program_name']; ?></td>
                                        <td><?php echo $app['cost_share_program']; ?></td>
                                        <td><?php echo $app['coverage_percent'] . '%'; ?></td>
                                        <td><?php echo $app['academic_year']; ?></td>
                                        <td>
                                            <form method="post" style="display: inline-block;">
                                                <input type="hidden" name="cost_share_id" value="<?php echo $app['cost_share_id']; ?>">
                                                <button type="submit" name="approve" class="btn btn-approve">Approve</button>
                                            </form>
                                            <form method="post" style="display: inline-block;">
                                                <input type="hidden" name="cost_share_id" value="<?php echo $app['cost_share_id']; ?>">
                                                <button type="submit" name="reject" class="btn btn-reject">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        No pending applications found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="section-title">Recently Processed Applications</h2>
            </div>
            <div class="card-body">
                <?php if (count($processed_apps) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Program</th>
                                    <th>Coverage %</th>
                                    <th>Academic Year</th>
                                    <th>Status</th>
                                    <th>Processed By</th>
                                    <th>Processed Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processed_apps as $app): ?>
                                    <tr>
                                        <td><?php echo $app['student_number']; ?></td>
                                        <td><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></td>
                                        <td><?php echo $app['program_name']; ?></td>
                                        <td><?php echo $app['coverage_percent'] . '%'; ?></td>
                                        <td><?php echo $app['academic_year']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($app['status']); ?>">
                                                <?php echo $app['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $app['approver_first_name'] . ' ' . $app['approver_last_name']; ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($app['approval_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        No processed applications found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'opacity 0.5s, transform 0.5s';
                    
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>