<?php
/**
 * Delete Exam Centre - Admin Panel
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

// Get student count for this centre
$student_count_result = $conn->query("SELECT COUNT(*) as count FROM students WHERE centre_id = $centre_id");
$student_count = $student_count_result->fetch_assoc()['count'];

$message = '';
$error = '';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $confirm_text = escape_input($conn, $_POST['confirm_text'] ?? '');
    
    if ($confirm_text === 'DELETE') {
        // Check if centre has students
        if ($student_count > 0) {
            $error = 'Cannot delete centre! ' . $student_count . ' students are allocated to this centre. Please deallocate them first.';
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Delete centre
                $stmt = prepare_query($conn, 'DELETE FROM exam_centres WHERE id = ?');
                $stmt->bind_param('i', $centre_id);
                
                if ($stmt->execute()) {
                    // Commit transaction
                    $conn->commit();
                    
                    // Redirect to centres list with success message
                    $_SESSION['delete_success'] = 'Exam centre deleted successfully!';
                    header('Location: centres.php');
                    exit();
                } else {
                    throw new Exception('Error deleting centre');
                }
                $stmt->close();
                
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = 'Error deleting centre: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Confirmation text must be exactly "DELETE"';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Centre - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .back-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link:hover { color: #764ba2; }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .warning-box h2 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .warning-box p {
            color: #856404;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .centre-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .centre-info strong {
            color: #667eea;
            display: block;
            margin-bottom: 5px;
        }
        .centre-info p {
            margin-bottom: 8px;
            font-size: 13px;
        }
        .danger-info {
            background: #fee;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #c33;
        }
        .danger-info h3 {
            color: #c33;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .danger-info ul {
            margin-left: 20px;
            font-size: 14px;
        }
        .danger-info li {
            margin-bottom: 5px;
        }
        .warning-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #856404;
        }
        .warning-info h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .warning-info p {
            margin: 0;
            font-size: 13px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        .input-hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .form-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 30px;
        }
        button {
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .btn-delete:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }
        .btn-cancel {
            background: #95a5a6;
            color: white;
        }
        .btn-cancel:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
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
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }
        .student-allocation {
            background: #f0f7ff;
            border-left: 4px solid #3498db;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #2c5aa0;
        }
        .student-allocation strong {
            color: #2c5aa0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="centres.php" class="back-link">← Back to Centres</a>
        
        <div class="card">
            <div class="warning-box">
                <h2>⚠️ Delete Exam Centre - Are You Sure?</h2>
                <p>This action <strong>CANNOT BE UNDONE</strong>. Deleting an exam centre will permanently remove it from the system.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="centre-info">
                <strong>Exam Centre Being Deleted:</strong>
                <p>
                    <strong>Name:</strong> <?php echo htmlspecialchars($centre['centre_name']); ?><br>
                    <strong>Address:</strong> <?php echo htmlspecialchars($centre['address']); ?><br>
                    <strong>Capacity:</strong> <?php echo $centre['capacity']; ?><br>
                    <strong>Invigilator:</strong> <?php echo htmlspecialchars($centre['invigilator_name']); ?> (<?php echo $centre['invigilator_mobile']; ?>)
                </p>
            </div>

            <?php if ($student_count > 0): ?>
                <div class="alert alert-error">
                    <strong>❌ ERROR: Cannot delete this centre!</strong><br>
                    This centre has <strong><?php echo $student_count; ?> students</strong> allocated to it. 
                    You must deallocate all students from this centre before deletion.
                </div>
                
                <div class="form-actions" style="margin-top: 20px;">
                    <a href="centres.php" class="btn-cancel" style="text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        ← Go Back
                    </a>
                    <a href="allocate.php" class="btn-delete" style="text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; background: #667eea;">
                        📋 Manage Allocation
                    </a>
                </div>
            <?php else: ?>
                <div class="student-allocation">
                    <strong>✅ No students allocated</strong><br>
                    This centre is currently empty and can be safely deleted.
                </div>

                <div class="danger-info">
                    <h3>⚠️ This will delete:</h3>
                    <ul>
                        <li>✗ Centre information</li>
                        <li>✗ Invigilator assignment</li>
                        <li>✗ All centre records</li>
                    </ul>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="confirm_text">Type "DELETE" to confirm:</label>
                        <input 
                            type="text" 
                            id="confirm_text" 
                            name="confirm_text" 
                            placeholder="Type DELETE exactly as shown"
                            autocomplete="off"
                        >
                        <div class="input-hint">Enter the text exactly to confirm deletion</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="confirm_delete" value="1" class="btn-delete" id="deleteBtn" disabled>
                            🗑️ Permanently Delete
                        </button>
                        <a href="centres.php" class="btn-cancel" style="text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                            ❌ Cancel
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($student_count == 0): ?>
    <script>
        const confirmInput = document.getElementById('confirm_text');
        const deleteBtn = document.getElementById('deleteBtn');
        
        confirmInput.addEventListener('input', function() {
            if (this.value === 'DELETE') {
                deleteBtn.disabled = false;
            } else {
                deleteBtn.disabled = true;
            }
        });
        
        // Prevent accidental clicks
        deleteBtn.addEventListener('click', function(e) {
            if (!confirm('Are you absolutely sure? This cannot be undone!')) {
                e.preventDefault();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
