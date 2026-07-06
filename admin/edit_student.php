<?php
/**
 * Edit Student Details - Admin Panel
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';
$student = null;

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    header('Location: students.php');
    exit();
}

// Fetch student details
$stmt = prepare_query($conn, 'SELECT * FROM students WHERE id = ?');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: students.php');
    exit();
}

$student = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = escape_input($conn, $_POST['name'] ?? '');
    $dob = escape_input($conn, $_POST['dob'] ?? '');
    $mobile = escape_input($conn, $_POST['mobile'] ?? '');
    $class = escape_input($conn, $_POST['class'] ?? '');
    $school = escape_input($conn, $_POST['school'] ?? '');
    $payment_status = escape_input($conn, $_POST['payment_status'] ?? 'pending');
    $centre_id = !empty($_POST['centre_id']) ? (int)$_POST['centre_id'] : NULL;

    if ($name && $dob && $mobile && $class && $school) {
        $stmt = prepare_query($conn, 'UPDATE students SET name = ?, dob = ?, mobile = ?, class = ?, school_name = ?, payment_status = ?, centre_id = ? WHERE id = ?');
        $stmt->bind_param('sssssisi', $name, $dob, $mobile, $class, $school, $payment_status, $centre_id, $student_id);

        if ($stmt->execute()) {
            $message = 'Student details updated successfully!';
            
            // Refresh student data
            $stmt_refresh = prepare_query($conn, 'SELECT * FROM students WHERE id = ?');
            $stmt_refresh->bind_param('i', $student_id);
            $stmt_refresh->execute();
            $student = $stmt_refresh->get_result()->fetch_assoc();
            $stmt_refresh->close();
        } else {
            $error = 'Error updating student: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = 'All fields are required!';
    }
}

// Get all exam centres
$centres = [];
$centres_result = $conn->query('SELECT id, centre_name FROM exam_centres ORDER BY centre_name');
while ($row = $centres_result->fetch_assoc()) {
    $centres[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header h1 { color: #333; margin-bottom: 5px; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link:hover { color: #764ba2; }
        .form-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-section h2 { color: #667eea; margin-bottom: 20px; font-size: 20px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
        input, select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus { 
            outline: none; 
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 30px; }
        button { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            padding: 12px 20px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: 600;
            transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); }
        .btn-cancel { 
            background: #e74c3c; 
        }
        .btn-cancel:hover { 
            background: #c0392b;
        }
        .alert { 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            border-left: 4px solid;
        }
        .alert-error { 
            background: #fee; 
            color: #c33; 
            border-color: #c33;
        }
        .alert-success { 
            background: #efe; 
            color: #3c3; 
            border-color: #3c3;
        }
        .info-box {
            background: #f0f7ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-box strong { color: #667eea; }
        .section-title {
            background: #f9f9f9;
            padding: 15px;
            margin: 20px -30px 15px -30px;
            border-left: 4px solid #667eea;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .read-only {
            background-color: #f5f5f5;
            color: #666;
        }
        .read-only:focus {
            background-color: #f5f5f5;
            border-color: #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="students.php" class="back-link">← Back to Students</a>
        
        <div class="header">
            <h1>✏️ Edit Student Details</h1>
            <p>Update student information and allocation</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="form-section">
            <div class="info-box">
                <strong>📋 Registration Number:</strong> <?php echo htmlspecialchars($student['registration_no']); ?>
                <?php if ($student['roll_no']): ?>
                    | <strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_no']); ?>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="">
                <!-- Personal Information Section -->
                <div class="section-title">👤 Personal Information</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth *</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($student['dob']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="mobile">Mobile Number *</label>
                        <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($student['mobile']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="class">Class *</label>
                        <select id="class" name="class" required>
                            <option value="">Select Class</option>
                            <option value="5" <?php echo $student['class'] == '5' ? 'selected' : ''; ?>>Class 5</option>
                            <option value="6" <?php echo $student['class'] == '6' ? 'selected' : ''; ?>>Class 6</option>
                            <option value="7" <?php echo $student['class'] == '7' ? 'selected' : ''; ?>>Class 7</option>
                            <option value="8" <?php echo $student['class'] == '8' ? 'selected' : ''; ?>>Class 8</option>
                            <option value="9" <?php echo $student['class'] == '9' ? 'selected' : ''; ?>>Class 9</option>
                            <option value="10" <?php echo $student['class'] == '10' ? 'selected' : ''; ?>>Class 10</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="school">School Name *</label>
                    <input type="text" id="school" name="school" value="<?php echo htmlspecialchars($student['school_name']); ?>" required>
                </div>
                
                <!-- Payment & Allocation Section -->
                <div class="section-title">💳 Payment & Allocation</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_status">Payment Status *</label>
                        <select id="payment_status" name="payment_status" required>
                            <option value="pending" <?php echo $student['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $student['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="centre_id">Exam Centre</label>
                        <select id="centre_id" name="centre_id">
                            <option value="">-- Not Allocated --</option>
                            <?php foreach ($centres as $centre): ?>
                                <option value="<?php echo $centre['id']; ?>" <?php echo $student['centre_id'] == $centre['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($centre['centre_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Timestamps Section -->
                <div class="section-title">📅 Metadata</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Registered On</label>
                        <input type="text" value="<?php echo date('d M Y, h:i A', strtotime($student['created_at'])); ?>" readonly class="read-only">
                    </div>
                    <div class="form-group">
                        <label>Last Updated</label>
                        <input type="text" value="<?php echo date('d M Y, h:i A', strtotime($student['updated_at'])); ?>" readonly class="read-only">
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit">💾 Save Changes</button>
                    <a href="students.php" class="btn btn-cancel" style="text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">❌ Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
