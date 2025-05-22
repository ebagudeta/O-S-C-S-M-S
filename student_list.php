<?php
// Start session and verify user is logged in with registrar role
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
    header("Location: index.php");
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'ocsms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all students with their information from system_users and students tables
    $sql = "SELECT s.student_id, s.student_number, su.name, 
                   su.email, s.department, s.college, s.enrollment_date, s.academic_level as academic_status
            FROM students s
            JOIN system_users su ON s.user_id = su.user_id
            WHERE su.role = 'student' AND su.status = 'active'
            ORDER BY su.name";
    
    $stmt = $pdo->query($sql);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Student Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .header {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 55px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            font-size: 24px;
        }
        .logo i {
            color: #5D5CDE;
            margin-right: 10px;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .role-badge {
            background-color: #5D5CDE;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 10px;
            font-size: 14px;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .page-title {
            color: #5D5CDE;
            margin-bottom: 20px;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        .btn:hover {
            background-color: #4A49B0;
        }
        .error {
            color: #f44336;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .suspended {
            background-color: #ffebee;
            color: #c62828;
        }
        .probation {
            background-color: #fff3e0;
            color: #e65100;
        }
        .graduated {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        .undergraduate, .undergraduate {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .graduate {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        .postgraduate {
            background-color: #d1c4e9;
            color: #4527a0;
        }
        .action-links a {
            text-decoration: none;
            color: #5D5CDE;
            margin-right: 20px;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
        .search-box {
            margin-bottom: 20px;
            display: flex;
        }
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        .search-box button {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #5D5CDE;
        }
        .back-link i {
            margin-right: 5px;
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
            <span class="role-badge">Registrar</span>
            <span><?php echo $_SESSION['name']; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1 class="page-title">Student Management</h1>
        
        <?php if (isset($error)): ?>
            <div class="card">
                <p class="error"><?php echo $error; ?></p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="actions">
                    <div>
                        <a href="add_student.php" class="btn">
                            <i class="fas fa-user-plus"></i> Add New Student
                        </a>
                        <a href="upload_students.php" class="btn">
                            <i class="fas fa-file-upload"></i> Bulk Upload
                        </a>
                    </div>
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search by name, ID, or email...">
                        <button type="button" id="searchButton">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>College</th>
                                <th>Enrollment Date</th>
                                <th>Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['student_number']; ?></td>
                                        <td><?php echo $student['name']; ?></td>
                                        <td><?php echo $student['email']; ?></td>
                                        <td><?php echo $student['department']; ?></td>
                                        <td><?php echo $student['college']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = strtolower($student['academic_status']);
                                                echo '<span class="status-badge ' . $statusClass . '">' . $student['academic_status'] . '</span>';
                                            ?>
                                        </td>
                                        <td class="action-links">
                                            <a href="edit_student.php?id=<?php echo $student['student_id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="view_student.php?id=<?php echo $student['student_id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchButton').addEventListener('click', function() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const studentId = rows[i].cells[0].textContent.toLowerCase();
                const name = rows[i].cells[1].textContent.toLowerCase();
                const email = rows[i].cells[2].textContent.toLowerCase();
                const department = rows[i].cells[3].textContent.toLowerCase();
                const college = rows[i].cells[4].textContent.toLowerCase();
                
                if (studentId.includes(searchTerm) || 
                    name.includes(searchTerm) || 
                    email.includes(searchTerm) || 
                    department.includes(searchTerm) || 
                    college.includes(searchTerm)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
        
        // Also trigger search when Enter key is pressed
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                document.getElementById('searchButton').click();
            }
        });
    </script>
</body>
</html>