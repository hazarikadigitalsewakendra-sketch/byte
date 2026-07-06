<?php
/**
 * Add Student - Coordinator Panel
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = escape_input($conn, $_POST['name'] ?? '');
    $dob = escape_input($conn, $_POST['dob'] ?? '');
    $mobile = escape_input($conn, $_POST['mobile'] ?? '');
    $class = escape_input($conn, $_POST['class'] ?? '');
    $school = escape_input($conn, $_POST['school'] ?? '');
    
    if ($name && $dob && $mobile && $class && $school) {
        // Generate registration number
        $count = $conn->query('SELECT COUNT(*) as c FROM students')->fetch_assoc()['c'];
        $reg_no = 'BDO26' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        
        $stmt = prepare_query($conn, 'INSERT INTO students (registration_no, name, dob, mobile, class, school_name) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssss', $reg_no, $name, $dob, $mobile, $class, $school);
        
        if ($stmt->execute()) {
            $message = "Student registered successfully! Registration No: $reg_no";
        } else {
            $error = 'Error registering student!';
        }
        $stmt->close();
    } else {
        $error = 'All fields are required!';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .sidebar h2 { margin-bottom: 30px; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 10px 15px; border-radius: 5px; margin-bottom: 10px; transition: background 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.2); }
        .main-content { flex: 1; padding: 30px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; width: 100%; }
        button:hover { transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>🧠 ByteLab</h2>
            <a href="add_student.php">➕ Add Student</a>
            <a href="student_list.php">📋 My Students</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
        
        <div class="main-content">
            <h1>Register New Student</h1>
            <p style="color: #666; margin-bottom: 20px;">Enter student details below</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth *</label>
                            <input type="date" name="dob" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mobile Number *</label>
                            <input type="tel" name="mobile" required>
                        </div>
                        <div class="form-group">
                            <label>Class *</label>
                            <select name="class" required>
                                <option value="">Select Class</option>
                                <option value="5">Class 5</option>
                                <option value="6">Class 6</option>
                                <option value="7">Class 7</option>
                                <option value="8">Class 8</option>
                                <option value="9">Class 9</option>
                                <option value="10">Class 10</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>School Name *</label>
                        <input type="text" name="school" required>
                    </div>
                    
                    <button type="submit">Register Student</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
