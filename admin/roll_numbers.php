<?php
/**
 * Manage Roll Numbers - Admin Panel
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

// Auto-generate roll numbers for class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'auto_generate') {
    $class = escape_input($conn, $_POST['class'] ?? '');
    
    if (!$class) {
        $error = 'Please select a class!';
    } else {
        $conn->begin_transaction();
        try {
            // Get all students in class without roll number
            $stmt = prepare_query($conn, 'SELECT id FROM students WHERE class = ? AND roll_no IS NULL ORDER BY id ASC');
            $stmt->bind_param('s', $class);
            $stmt->execute();
            $result = $stmt->get_result();
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            $stmt->close();
            
            if (count($students) === 0) {
                throw new Exception('No students without roll numbers in this class!');
            }
            
            // Generate roll numbers
            $roll_counter = 1;
            $updated = 0;
            foreach ($students as $student) {
                $roll_no = 'ROLL' . $class . str_pad($roll_counter, 3, '0', STR_PAD_LEFT);
                $update_stmt = prepare_query($conn, 'UPDATE students SET roll_no = ? WHERE id = ?');
                $update_stmt->bind_param('si', $roll_no, $student['id']);
                if ($update_stmt->execute()) {
                    $updated++;
                }
                $update_stmt->close();
                $roll_counter++;
            }
            
            $conn->commit();
            $message = "Roll numbers generated successfully for $updated students in Class $class!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Manually assign roll number
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'assign_roll') {
    $student_id = (int)$_POST['student_id'];
    $roll_no = escape_input($conn, $_POST['roll_no'] ?? '');
    
    if ($student_id <= 0 || !$roll_no) {
        $error = 'Invalid student or roll number!';
    } else {
        $stmt = prepare_query($conn, 'UPDATE students SET roll_no = ? WHERE id = ?');
        $stmt->bind_param('si', $roll_no, $student_id);
        
        if ($stmt->execute()) {
            $message = 'Roll number assigned successfully!';
        } else {
            $error = 'Error assigning roll number!';
        }
        $stmt->close();
    }
}

// Reset roll numbers for class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reset_class') {
    $class = escape_input($conn, $_POST['class'] ?? '');
    
    if (!$class) {
        $error = 'Please select a class!';
    } else {
        $stmt = prepare_query($conn, 'UPDATE students SET roll_no = NULL WHERE class = ?');
        $stmt->bind_param('s', $class);
        
        if ($stmt->execute()) {
            $message = 'Roll numbers reset for Class ' . htmlspecialchars($class) . '!';
        } else {
            $error = 'Error resetting roll numbers!';
        }
        $stmt->close();
    }
}

// Get statistics
$total_students = $conn->query('SELECT COUNT(*) as count FROM students')->fetch_assoc()['count'];
$with_roll = $conn->query('SELECT COUNT(*) as count FROM students WHERE roll_no IS NOT NULL')->fetch_assoc()['count'];
$without_roll = $total_students - $with_roll;

// Get students without roll numbers
$students_no_roll = [];
$students_result = $conn->query('
    SELECT id, registration_no, name, class, school_name 
    FROM students 
    WHERE roll_no IS NULL 
    ORDER BY class, name
');
while ($row = $students_result->fetch_assoc()) {
    $students_no_roll[] = $row;
}

// Get unique classes
$classes_result = $conn->query('SELECT DISTINCT class FROM students ORDER BY class');
$classes = [];
while ($row = $classes_result->fetch_assoc()) {
    $classes[] = $row['class'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roll Numbers - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header h1 { color: #333; margin-bottom: 10px; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link:hover { color: #764ba2; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #667eea; text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 12px; color: #999; margin-top: 5px; }
        .action-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .action-section h2 { color: #667eea; margin-bottom: 15px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; transition: transform 0.2s; }
        button:hover { transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .table-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f0f0; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f9f9f9; }
        .info-box { background: #f0f7ff; border-left: 3px solid #667eea; padding: 12px; border-radius: 5px; font-size: 13px; color: #2c5aa0; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1>🔢 Manage Roll Numbers</h1>
            <p>Auto-generate or manually assign roll numbers to students</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $with_roll; ?></div>
                <div class="stat-label">With Roll Number</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $without_roll; ?></div>
                <div class="stat-label">Without Roll Number</div>
            </div>
        </div>
        
        <!-- Auto-Generate Roll Numbers -->
        <div class="action-section">
            <h2>🔄 Auto-Generate Roll Numbers</h2>
            <div class="info-box">
                <strong>Info:</strong> Select a class to automatically generate roll numbers for all students in that class who don't have one.
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="auto_generate">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Class *</label>
                        <select name="class" required>
                            <option value="">-- Choose a Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class; ?>">Class <?php echo htmlspecialchars($class); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit">🔄 Generate Roll Numbers</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Reset Roll Numbers -->
        <div class="action-section">
            <h2>↩️ Reset Roll Numbers</h2>
            <div class="info-box">
                <strong>Warning:</strong> This will remove all roll numbers for the selected class.
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="reset_class">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Class *</label>
                        <select name="class" required>
                            <option value="">-- Choose a Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class; ?>">Class <?php echo htmlspecialchars($class); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" style="background: #e74c3c;">↩️ Reset All</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Manual Assignment -->
        <?php if (count($students_no_roll) > 0): ?>
        <div class="table-section">
            <h2 style="margin-bottom: 15px; color: #333;">Manually Assign Roll Numbers (<?php echo count($students_no_roll); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Reg No</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>School</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students_no_roll as $student): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['registration_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['class']); ?></td>
                            <td><?php echo htmlspecialchars($student['school_name']); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="assign_roll">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <input type="text" name="roll_no" placeholder="Enter roll no" required style="width: 120px;">
                                    <button type="submit" style="width: auto; padding: 6px 12px; font-size: 12px;">Assign</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
