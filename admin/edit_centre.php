<?php
/**
 * Edit Exam Centre - Admin Panel
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$centre_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($centre_id <= 0) {
    header('Location: centres.php');
    exit();
}

// Fetch centre details
$stmt = prepare_query($conn, 'SELECT * FROM exam_centres WHERE id = ?');
$stmt->bind_param('i', $centre_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: centres.php');
    exit();
}

$centre = $result->fetch_assoc();
$stmt->close();

$message = '';
$error = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $centre_name = escape_input($conn, $_POST['centre_name'] ?? '');
    $address = escape_input($conn, $_POST['address'] ?? '');
    $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
    $invigilator_name = escape_input($conn, $_POST['invigilator_name'] ?? '');
    $invigilator_mobile = escape_input($conn, $_POST['invigilator_mobile'] ?? '');

    if ($centre_name && $address && $capacity > 0 && $invigilator_name && $invigilator_mobile) {
        $stmt = prepare_query($conn, 'UPDATE exam_centres SET centre_name = ?, address = ?, capacity = ?, invigilator_name = ?, invigilator_mobile = ? WHERE id = ?');
        $stmt->bind_param('ssissi', $centre_name, $address, $capacity, $invigilator_name, $invigilator_mobile, $centre_id);

        if ($stmt->execute()) {
            $message = 'Exam centre updated successfully!';
            
            // Refresh centre data
            $stmt_refresh = prepare_query($conn, 'SELECT * FROM exam_centres WHERE id = ?');
            $stmt_refresh->bind_param('i', $centre_id);
            $stmt_refresh->execute();
            $centre = $stmt_refresh->get_result()->fetch_assoc();
            $stmt_refresh->close();
        } else {
            $error = 'Error updating centre: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = 'All fields are required! Capacity must be a number greater than 0.';
    }
}

// Get student count for this centre
$student_count_result = $conn->query("SELECT COUNT(*) as count FROM students WHERE centre_id = $centre_id");
$student_count = $student_count_result->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Centre - <?php echo SYSTEM_NAME; ?></title>
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
        input, textarea { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        textarea { resize: vertical; min-height: 100px; }
        input:focus, textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
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
            background: #95a5a6; 
        }
        .btn-cancel:hover { 
            background: #7f8c8d;
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
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .stat-label {
            font-size: 12px;
            color: #999;
            font-weight: 600;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-top: 5px;
        }
        .capacity-info {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 12px;
            border-radius: 5px;
            font-size: 13px;
            color: #2c5aa0;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="centres.php" class="back-link">← Back to Centres</a>
        
        <div class="header">
            <h1>✏️ Edit Exam Centre</h1>
            <p>Update centre information and details</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="form-section">
            <div class="info-box">
                <strong>🏢 Centre ID:</strong> <?php echo $centre_id; ?> | 
                <strong>Created:</strong> <?php echo date('d M Y', strtotime($centre['created_at'])); ?>
            </div>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-label">Students Allocated</div>
                    <div class="stat-value"><?php echo $student_count; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total Capacity</div>
                    <div class="stat-value"><?php echo $centre['capacity']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Available Seats</div>
                    <div class="stat-value" style="color: <?php echo ($centre['capacity'] - $student_count) <= 0 ? '#e74c3c' : '#27ae60'; ?>;">
                        <?php echo max(0, $centre['capacity'] - $student_count); ?>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="">
                <!-- Centre Information Section -->
                <div class="section-title">📍 Centre Information</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="centre_name">Centre Name *</label>
                        <input type="text" id="centre_name" name="centre_name" value="<?php echo htmlspecialchars($centre['centre_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="capacity">Capacity *</label>
                        <input type="number" id="capacity" name="capacity" value="<?php echo $centre['capacity']; ?>" min="1" required>
                        <div class="capacity-info">
                            ⚠️ Changing capacity will not affect already allocated students (<?php echo $student_count; ?>)
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <textarea id="address" name="address" required><?php echo htmlspecialchars($centre['address']); ?></textarea>
                </div>

                <!-- Invigilator Information Section -->
                <div class="section-title">👤 Invigilator Information</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="invigilator_name">Invigilator Name *</label>
                        <input type="text" id="invigilator_name" name="invigilator_name" value="<?php echo htmlspecialchars($centre['invigilator_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="invigilator_mobile">Invigilator Mobile *</label>
                        <input type="tel" id="invigilator_mobile" name="invigilator_mobile" value="<?php echo htmlspecialchars($centre['invigilator_mobile']); ?>" required>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit">💾 Save Changes</button>
                    <a href="centres.php" class="btn-cancel" style="text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        ❌ Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
