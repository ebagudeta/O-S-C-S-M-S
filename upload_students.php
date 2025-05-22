<?php
// Start session
session_start();

// Check if user is logged in and is a registrar
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
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
$processed_records = 0;
$failed_records = 0;
$log = [];

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    try {
        // Check if file was uploaded without errors
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            $file_name = $_FILES['csv_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate file extension
            if ($file_ext != 'csv') {
                throw new Exception("Only CSV files are allowed");
            }
            
            // Open uploaded file
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if (!$file) {
                throw new Exception("Failed to open file");
            }
            
            // Get column headers
            $headers = fgetcsv($file);
            if (!$headers) {
                throw new Exception("CSV file is empty or invalid");
            }
            
            // Check required columns
            $required_columns = ['first_name', 'last_name', 'email', 'student_number', 'program_code', 'enrollment_date'];
            $header_map = [];
            
            foreach ($required_columns as $col) {
                $index = array_search($col, array_map('strtolower', $headers));
                if ($index === false) {
                    throw new Exception("Missing required column: $col");
                }
                $header_map[$col] = $index;
            }
            
            // Get all programs for mapping
            $programs = [];
            $stmt = $conn->prepare("SELECT program_id, program_code FROM programs");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $programs[$row['program_code']] = $row['program_id'];
            }
            $stmt->close();
            
            // Process each row in the CSV
            $line = 1; // Start from line 2 (after headers)
            $conn->begin_transaction();
            
            while (($data = fgetcsv($file)) !== FALSE) {
                $line++;
                try {
                    // Extract data
                    $first_name = trim($data[$header_map['first_name']]);
                    $last_name = trim($data[$header_map['last_name']]);
                    $email = trim($data[$header_map['email']]);
                    $student_number = trim($data[$header_map['student_number']]);
                    $program_code = trim($data[$header_map['program_code']]);
                    $enrollment_date = trim($data[$header_map['enrollment_date']]);
                    
                    // Validate data
                    if (empty($first_name) || empty($last_name) || empty($email) || 
                        empty($student_number) || empty($program_code) || empty($enrollment_date)) {
                        throw new Exception("Missing required fields");
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Invalid email format");
                    }
                    
                    // Get program_id from program_code
                    if (!isset($programs[$program_code])) {
                        throw new Exception("Invalid program code: $program_code");
                    }
                    $program_id = $programs[$program_code];
                    
                    // Validate date
                    $date_obj = DateTime::createFromFormat('Y-m-d', $enrollment_date);
                    if (!$date_obj || $date_obj->format('Y-m-d') !== $enrollment_date) {
                        throw new Exception("Invalid date format (should be YYYY-MM-DD)");
                    }
                    
                    // Check if student number already exists
                    $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_number = ?");
                    $stmt->bind_param("s", $student_number);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        throw new Exception("Student number already exists");
                    }
                    $stmt->close();
                    
                    // Check if email already exists
                    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        throw new Exception("Email already exists");
                    }
                    $stmt->close();
                    
                    // Generate username and default password
                    $username = strtolower(substr($first_name, 0, 1) . $last_name) . rand(100, 999);
                    $default_password = 'Student@' . rand(1000, 9999);
                    $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
                    
                    // Insert user record
                    $role_id = 2; // Student role ID
                    $is_active = 1;
                    
                    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, first_name, last_name, role_id, is_active) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssii", $username, $password_hash, $email, $first_name, $last_name, $role_id, $is_active);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error creating user account: " . $stmt->error);
                    }
                    
                    $user_id = $conn->insert_id;
                    $stmt->close();
                    
                    // Insert student record
                    $status = 'Active';
                    $stmt = $conn->prepare("INSERT INTO students (user_id, student_number, program_id, enrollment_date, academic_status) 
                                         VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("isiss", $user_id, $student_number, $program_id, $enrollment_date, $status);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error creating student record: " . $stmt->error);
                    }
                    
                    $stmt->close();
                    
                    // Log success record with credentials for the registrar
                    $log[] = [
                        'line' => $line,
                        'status' => 'success',
                        'student' => "$first_name $last_name",
                        'username' => $username,
                        'password' => $default_password,
                        'message' => "Student added successfully"
                    ];
                    
                    $processed_records++;
                    
                } catch (Exception $e) {
                    // Log failed record
                    $log[] = [
                        'line' => $line,
                        'status' => 'error',
                        'student' => isset($first_name) && isset($last_name) ? "$first_name $last_name" : "Unknown",
                        'message' => $e->getMessage()
                    ];
                    
                    $failed_records++;
                }
            }
            
            fclose($file);
            
            // Commit transaction if we have successful records
            if ($processed_records > 0) {
                $conn->commit();
                $message = "Import completed. $processed_records student(s) added successfully. $failed_records failed.";
                $message_type = "success";
            } else {
                $conn->rollback();
                $message = "Import failed. No valid records found.";
                $message_type = "error";
            }
            
        } else {
            throw new Exception("Please select a CSV file to upload");
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno == 0) {
            $conn->rollback();
        }
        
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// Prepare sample CSV
$sample_csv = "first_name,last_name,email,student_number,program_code,enrollment_date\n";
$sample_csv .= "John,Doe,john.doe@example.com,S12350,CS-BSC,2023-09-01\n";
$sample_csv .= "Jane,Smith,jane.smith@example.com,S12351,MATH-BSC,2023-09-01";

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCSMS - Bulk Upload Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .header {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
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
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .back-link {
            display: flex;
            align-items: center;
            color: #5D5CDE;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .back-link i {
            margin-right: 5px;
        }
        .page-title {
            color: #5D5CDE;
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        input[type="file"] {
            padding: 10px;
            border: 1px dashed #ddd;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        button {
            background-color: #5D5CDE;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
        }
        button:hover {
            background-color: #4A49B0;
        }
        .sample-csv {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .code-block {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre;
            overflow-x: auto;
            font-size: 14px;
        }
        .download-link {
            display: inline-block;
            margin-top: 10px;
            color: #5D5CDE;
            text-decoration: none;
        }
        .import-log {
            margin-top: 30px;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
        }
        .log-table th, .log-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .log-table th {
            background-color: #f9f9f9;
            font-weight: 600;
        }
        .log-success {
            color: #2e7d32;
        }
        .log-error {
            color: #c62828;
        }
        .instructions {
            margin-bottom: 25px;
        }
        .instructions ul {
            padding-left: 20px;
        }
        .instructions li {
            margin-bottom: 8px;
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
        <a href="student_list.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Student List
        </a>
        
        <h1 class="page-title">Bulk Upload Students</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="instructions">
                <h2>Instructions</h2>
                <ul>
                    <li>Prepare a CSV file with the required columns: first_name, last_name, email, student_number, program_code, enrollment_date</li>
                    <li>Make sure program_code matches one of the existing program codes in the system</li>
                    <li>Date format should be YYYY-MM-DD</li>
                    <li>All fields are required</li>
                    <li>The system will generate usernames and passwords for the students</li>
                </ul>
            </div>
            
            <form method="post" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">Upload CSV File</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                </div>
                
                <button type="submit" name="upload">Upload and Process</button>
            </form>
            
            <div class="sample-csv">
                <h3>Sample CSV Format</h3>
                <div class="code-block"><?php echo htmlspecialchars($sample_csv); ?></div>
                <a href="data:text/csv;charset=utf-8,<?php echo urlencode($sample_csv); ?>" download="sample_students.csv" class="download-link">
                    <i class="fas fa-download"></i> Download Sample CSV
                </a>
            </div>
            
            <?php if (!empty($log)): ?>
                <div class="import-log">
                    <h3>Import Log</h3>
                    <p>Total processed: <?php echo $processed_records + $failed_records; ?>, Success: <?php echo $processed_records; ?>, Failed: <?php echo $failed_records; ?></p>
                    
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>Line</th>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Message</th>
                                <?php if ($processed_records > 0): ?>
                                    <th>Username</th>
                                    <th>Password</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($log as $entry): ?>
                                <tr>
                                    <td><?php echo $entry['line']; ?></td>
                                    <td><?php echo $entry['student']; ?></td>
                                    <td class="log-<?php echo $entry['status']; ?>">
                                        <?php echo ucfirst($entry['status']); ?>
                                    </td>
                                    <td><?php echo $entry['message']; ?></td>
                                    <?php if ($processed_records > 0 && $entry['status'] === 'success'): ?>
                                        <td><?php echo $entry['username']; ?></td>
                                        <td><?php echo $entry['password']; ?></td>
                                    <?php elseif ($processed_records > 0): ?>
                                        <td colspan="2">-</td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($processed_records > 0): ?>
                        <p><strong>Note:</strong> Please save or print this page to keep a record of the generated usernames and passwords.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>