<?php
/**
 * Delete Student - Admin Panel
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    header('Location: students.php');
    exit();
}

// Fetch student details
$stmt = prepare_query($conn, 'SELECT id, registration_no, name FROM students WHERE id = ?');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: students.php');
    exit();
}

$student = $result->fetch_assoc();
$stmt->close();

$message = '';
$error = '';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $confirm_text = escape_input($conn, $_POST['confirm_text'] ?? '');
    
    if ($confirm_text === 'DELETE') {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete related records first (cascade)
            $delete_payments = $conn->query("DELETE FROM payments WHERE student_id = $student_id");
            $delete_results = $conn->query("DELETE FROM results WHERE student_id = $student_id");
            $delete_certificates = $conn->query("DELETE FROM certificates WHERE student_id = $student_id");
            
            // Delete student
            $stmt = prepare_query($conn, 'DELETE FROM students WHERE id = ?');
            $stmt->bind_param('i', $student_id);
            
            if ($stmt->execute()) {
                // Commit transaction
                $conn->commit();
                
                // Redirect to students list with success message
                $_SESSION['delete_success'] = 'Student deleted successfully!';
                header('Location: students.php');
                exit();
            } else {
                throw new Exception('Error deleting student');
            }
            $stmt->close();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = 'Error deleting student: ' . $e->getMessage();
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
    <title>Delete Student - <?php echo SYSTEM_NAME; ?></title>
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
        .student-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .student-info strong {
            color: #667eea;
            display: block;
            margin-bottom: 5px;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="students.php" class="back-link">← Back to Students</a>
        
        <div class="card">
            <div class="warning-box">
                <h2>⚠️ Delete Student - Are You Sure?</h2>
                <p>This action <strong>CANNOT BE UNDONE</strong>. Deleting a student will permanently remove all their data from the system.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="student-info">
                <strong>Student Being Deleted:</strong>
                <p>
                    <strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?><br>
                    <strong>Registration No:</strong> <?php echo htmlspecialchars($student['registration_no']); ?><br>
                    <strong>Student ID:</strong> <?php echo $student_id; ?>
                </p>
            </div>
            
            <div class="danger-info">
                <h3>⚠️ This will delete:</h3>
                <ul>
                    <li>✗ Student personal information</li>
                    <li>✗ Payment records</li>
                    <li>✗ Exam results (if any)</li>
                    <li>✗ Certificate records (if any)</li>
                    <li>✗ All related data</li>
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
                    <a href="students.php" class="btn-cancel" style="text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        ❌ Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
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
</body>
</html>
