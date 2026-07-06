<?php
/**
 * Manage Results - Admin Panel
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

// Handle result entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'enter_result') {
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $marks = isset($_POST['marks']) ? (int)$_POST['marks'] : 0;

    if ($student_id > 0 && $marks >= 0 && $marks <= 100) {
        // Check if result already exists
        $check_result = $conn->query("SELECT id FROM results WHERE student_id = $student_id");
        
        if ($check_result->num_rows > 0) {
            // Update existing result
            $stmt = prepare_query($conn, 'UPDATE results SET marks = ? WHERE student_id = ?');
            $stmt->bind_param('ii', $marks, $student_id);
        } else {
            // Insert new result
            $stmt = prepare_query($conn, 'INSERT INTO results (student_id, marks, status) VALUES (?, ?, "pending")');
            $stmt->bind_param('ii', $student_id, $marks);
        }

        if ($stmt->execute()) {
            $message = 'Result saved successfully!';
        } else {
            $error = 'Error saving result: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = 'Invalid marks! Marks must be between 0 and 100.';
    }
}

// Handle rank calculation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'calculate_ranks') {
    $conn->begin_transaction();
    
    try {
        // Reset ranks
        $conn->query("UPDATE results SET rank = NULL");
        
        // Get all results ordered by marks (highest first)
        $results_query = $conn->query("SELECT id, marks FROM results WHERE marks IS NOT NULL ORDER BY marks DESC");
        
        $rank = 1;
        while ($row = $results_query->fetch_assoc()) {
            $conn->query("UPDATE results SET rank = $rank WHERE id = " . $row['id']);
            $rank++;
        }
        
        $conn->commit();
        $message = 'Ranks calculated successfully! ' . ($rank - 1) . ' students ranked.';
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Error calculating ranks: ' . $e->getMessage();
    }
}

// Handle publish results
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'publish_results') {
    $confirm_text = escape_input($conn, $_POST['confirm_text'] ?? '');
    
    if ($confirm_text === 'PUBLISH') {
        // Check if all students have marks
        $no_marks = $conn->query("SELECT COUNT(*) as count FROM students WHERE id NOT IN (SELECT student_id FROM results WHERE marks IS NOT NULL)")
            ->fetch_assoc()['count'];
        
        if ($no_marks > 0) {
            $error = 'Cannot publish results! ' . $no_marks . ' students do not have marks entered yet.';
        } else {
            $conn->begin_transaction();
            
            try {
                // Update all results to published
                $conn->query("UPDATE results SET status = 'published' WHERE status = 'pending'");
                
                $conn->commit();
                $message = 'Results published successfully! Students can now view their results.';
                
                // Reset form
                $_POST = [];
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error publishing results: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Confirmation text must be exactly "PUBLISH"';
    }
}

// Get all students for result entry
$students_result = $conn->query('
    SELECT 
        s.id,
        s.registration_no,
        s.name,
        s.class,
        s.roll_no,
        r.marks,
        r.rank,
        r.status
    FROM students s
    LEFT JOIN results r ON s.id = r.student_id
    ORDER BY s.class DESC, s.name ASC
');

$students_data = [];
while ($row = $students_result->fetch_assoc()) {
    $students_data[] = $row;
}

// Calculate statistics
$total_students = count($students_data);
$with_marks = 0;
$with_ranks = 0;
$published = 0;

foreach ($students_data as $student) {
    if ($student['marks'] !== null) $with_marks++;
    if ($student['rank'] !== null) $with_ranks++;
    if ($student['status'] === 'published') $published++;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Results - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header h1 { color: #333; margin-bottom: 5px; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link:hover { color: #764ba2; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid; }
        .alert-error { background: #fee; color: #c33; border-color: #c33; }
        .alert-success { background: #efe; color: #3c3; border-color: #3c3; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
            text-align: center;
        }
        .stat-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 12px; color: #999; margin-top: 5px; }
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #eee;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .action-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .action-card h2 { color: #667eea; margin-bottom: 15px; font-size: 16px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 14px; }
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        select:focus, input:focus { outline: none; border-color: #667eea; }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); }
        .btn-calculate { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); }
        .btn-publish { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); }
        .table-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .table-section h2 { color: #667eea; margin-bottom: 15px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f0f0; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; font-size: 13px; }
        td { padding: 12px; border-bottom: 1px solid #ddd; font-size: 13px; }
        tr:hover { background: #f9f9f9; }
        .marks-input {
            width: 80px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 13px;
        }
        .save-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .save-btn:hover { background: #2980b9; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-pending { background: #ffeaa7; color: #d63031; }
        .badge-published { background: #a9e4d4; color: #00b894; }
        .modal-backdrop { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
        .modal-backdrop.active { display: flex; align-items: center; justify-content: center; }
        .modal { background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; }
        .modal h2 { color: #e74c3c; margin-bottom: 15px; }
        .modal p { margin-bottom: 15px; line-height: 1.6; }
        .modal-form-group { margin-bottom: 15px; }
        .modal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        .modal-actions button { padding: 10px 15px; }
        .btn-cancel-modal { background: #95a5a6; }
        .btn-cancel-modal:hover { background: #7f8c8d; }
        .info-box {
            background: #f0f7ff;
            border-left: 3px solid #667eea;
            padding: 12px;
            border-radius: 5px;
            font-size: 13px;
            color: #2c5aa0;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1>📊 Manage Results</h1>
            <p>Enter marks, calculate ranks, and publish results</p>
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
                <div class="stat-number"><?php echo $with_marks; ?></div>
                <div class="stat-label">With Marks</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $total_students > 0 ? ($with_marks / $total_students) * 100 : 0; ?>%;"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $with_ranks; ?></div>
                <div class="stat-label">With Ranks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $published; ?></div>
                <div class="stat-label">Published</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions-grid">
            <!-- Calculate Ranks -->
            <div class="action-card">
                <h2>🏆 Calculate Ranks</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="calculate_ranks">
                    <p style="font-size: 13px; margin-bottom: 15px; color: #666;">
                        Calculate rankings based on entered marks. This will assign ranks to all students with marks.
                    </p>
                    <?php if ($with_marks > 0): ?>
                        <button type="submit" class="btn-calculate">🏆 Calculate Ranks (<?php echo $with_marks; ?> students)</button>
                        <div class="info-box">
                            ✅ Ready to calculate rankings
                        </div>
                    <?php else: ?>
                        <button type="button" disabled class="btn-calculate" style="background: #bdc3c7; cursor: not-allowed;">
                            🏆 Calculate Ranks - Enter marks first
                        </button>
                        <div class="info-box" style="background: #fff3cd; color: #856404; border-color: #ffc107;">
                            ⚠️ Enter marks for at least one student first
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Publish Results -->
            <div class="action-card">
                <h2>📢 Publish Results</h2>
                <button type="button" class="btn-publish" onclick="showPublishModal()">📢 Publish Results</button>
                <div class="info-box">
                    <?php if ($published > 0): ?>
                        ✅ Results already published
                    <?php else: ?>
                        ⚠️ Results not yet published
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="table-section">
            <h2>📋 Student Results (<?php echo $total_students; ?>)</h2>
            <?php if ($total_students > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Reg No</th>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Marks</th>
                                <th>Rank</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_data as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['registration_no']); ?></strong></td>
                                    <td><?php echo $student['roll_no'] ? htmlspecialchars($student['roll_no']) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="enter_result">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <input 
                                                type="number" 
                                                name="marks" 
                                                class="marks-input" 
                                                value="<?php echo $student['marks'] !== null ? $student['marks'] : ''; ?>"
                                                min="0"
                                                max="100"
                                                placeholder="0-100"
                                            >
                                            <button type="submit" class="save-btn">Save</button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($student['rank'] !== null): ?>
                                            <strong style="color: #667eea; font-size: 14px;">#<?php echo $student['rank']; ?></strong>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $student['status']; ?>">
                                            <?php echo strtoupper($student['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="#" onclick="editResult(<?php echo htmlspecialchars(json_encode($student)); ?>); return false;" style="color: #667eea; text-decoration: none; font-size: 12px;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 30px;">No students registered yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Publish Modal -->
    <div class="modal-backdrop" id="publishModal">
        <div class="modal">
            <h2>⚠️ Publish Results</h2>
            <p>Publishing results will make them visible to all students. This action cannot be easily undone.</p>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="publish_results">
                
                <div class="modal-form-group">
                    <label>Type "PUBLISH" to confirm:</label>
                    <input 
                        type="text" 
                        name="confirm_text" 
                        id="publishConfirmText"
                        placeholder="Type PUBLISH exactly as shown"
                        autocomplete="off"
                    >
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="hidePublishModal()">Cancel</button>
                    <button type="submit" class="btn-publish" id="publishSubmitBtn" disabled>Publish Results</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showPublishModal() {
            document.getElementById('publishModal').classList.add('active');
        }

        function hidePublishModal() {
            document.getElementById('publishModal').classList.remove('active');
            document.getElementById('publishConfirmText').value = '';
            document.getElementById('publishSubmitBtn').disabled = true;
        }

        document.getElementById('publishConfirmText')?.addEventListener('input', function() {
            document.getElementById('publishSubmitBtn').disabled = this.value !== 'PUBLISH';
        });

        document.getElementById('publishSubmitBtn')?.addEventListener('click', function(e) {
            if (!confirm('Are you absolutely sure? This will publish results to all students!')) {
                e.preventDefault();
            }
        });

        function editResult(student) {
            alert('Student: ' + student.name + '\nMarks: ' + (student.marks || 'Not entered') + '\nRank: ' + (student.rank || 'Not ranked'));
        }
    </script>
</body>
</html>
