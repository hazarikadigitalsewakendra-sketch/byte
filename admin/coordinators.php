<?php
/**
 * Manage Coordinators - Admin Panel
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

// Add new coordinator
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $username = escape_input($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $email = escape_input($conn, $_POST['email'] ?? '');
    
    if (!$username || !$password || !$email) {
        $error = 'All fields are required!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match!';
    } else {
        // Check if username already exists
        $check_stmt = prepare_query($conn, 'SELECT id FROM users WHERE username = ?');
        $check_stmt->bind_param('s', $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Username already exists! Please choose a different username.';
        } else {
            // Hash password and insert coordinator
            $hashed_password = hash_password($password);
            $role = 'coordinator';
            $status = 'active';
            
            $stmt = prepare_query($conn, 'INSERT INTO users (username, password, role, email, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $username, $hashed_password, $role, $email, $status);
            
            if ($stmt->execute()) {
                $message = 'Coordinator added successfully! Username: ' . htmlspecialchars($username);
                // Clear form
                $_POST = [];
            } else {
                $error = 'Error adding coordinator: ' . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Update coordinator status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
    $coordinator_id = (int)$_POST['coordinator_id'];
    $new_status = escape_input($conn, $_POST['status'] ?? '');
    
    if ($coordinator_id > 0 && in_array($new_status, ['active', 'inactive'])) {
        $stmt = prepare_query($conn, 'UPDATE users SET status = ? WHERE id = ? AND role = ?');
        $role = 'coordinator';
        $stmt->bind_param('sis', $new_status, $coordinator_id, $role);
        
        if ($stmt->execute()) {
            $message = 'Coordinator status updated successfully!';
        } else {
            $error = 'Error updating coordinator status!';
        }
        $stmt->close();
    }
}

// Delete coordinator
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $coordinator_id = (int)$_POST['coordinator_id'];
    
    if ($coordinator_id > 0) {
        $stmt = prepare_query($conn, 'DELETE FROM users WHERE id = ? AND role = ?');
        $role = 'coordinator';
        $stmt->bind_param('is', $coordinator_id, $role);
        
        if ($stmt->execute()) {
            $message = 'Coordinator deleted successfully!';
        } else {
            $error = 'Error deleting coordinator!';
        }
        $stmt->close();
    }
}

// Get all coordinators
$coordinators_result = $conn->query('
    SELECT 
        u.id,
        u.username,
        u.email,
        u.status,
        u.created_at,
        u.updated_at,
        COUNT(s.id) as students_registered
    FROM users u
    LEFT JOIN students s ON u.id = s.coordinator_id OR s.created_by = u.id
    WHERE u.role = "coordinator"
    GROUP BY u.id
    ORDER BY u.created_at DESC
');

$coordinators = [];
while ($row = $coordinators_result->fetch_assoc()) {
    $coordinators[] = $row;
}

// Get statistics
$total_coordinators = count($coordinators);
$active_coordinators = 0;
$inactive_coordinators = 0;
$total_students_by_coordinators = 0;

foreach ($coordinators as $coord) {
    if ($coord['status'] == 'active') {
        $active_coordinators++;
    } else {
        $inactive_coordinators++;
    }
    $total_students_by_coordinators += $coord['students_registered'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coordinators - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header h1 { color: #333; margin-bottom: 10px; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link:hover { color: #764ba2; }
        .form-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; transition: transform 0.2s; width: 100%; }
        button:hover { transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #667eea; text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 12px; color: #999; margin-top: 5px; }
        .table-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f0f0; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f9f9f9; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .action-buttons { display: flex; gap: 10px; }
        .action-btn { padding: 6px 12px; border-radius: 3px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-block; border: none; cursor: pointer; transition: all 0.2s; }
        .edit-btn { background: #3498db; color: white; }
        .edit-btn:hover { background: #2980b9; }
        .toggle-btn { background: #f39c12; color: white; }
        .toggle-btn:hover { background: #e67e22; }
        .delete-btn { background: #e74c3c; color: white; }
        .delete-btn:hover { background: #c0392b; }
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background-color: white; padding: 30px; border-radius: 10px; max-width: 400px; width: 90%; }
        .modal-content h2 { color: #e74c3c; margin-bottom: 15px; }
        .modal-content p { margin-bottom: 20px; color: #666; }
        .modal-buttons { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .modal-btn { padding: 10px 15px; border-radius: 5px; border: none; cursor: pointer; font-weight: 600; }
        .modal-btn-cancel { background: #95a5a6; color: white; }
        .modal-btn-confirm { background: #e74c3c; color: white; }
        .empty-message { text-align: center; color: #999; padding: 40px; }
        .form-section h2 { margin-bottom: 15px; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1>👥 Manage Coordinators</h1>
            <p>Add and manage data entry coordinators</p>
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
                <div class="stat-number"><?php echo $total_coordinators; ?></div>
                <div class="stat-label">Total Coordinators</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_coordinators; ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $inactive_coordinators; ?></div>
                <div class="stat-label">Inactive</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_students_by_coordinators; ?></div>
                <div class="stat-label">Students Registered</div>
            </div>
        </div>
        
        <!-- Add Coordinator Form -->
        <div class="form-section">
            <h2>➕ Add New Coordinator</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" placeholder="Enter unique username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" placeholder="Enter email address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" placeholder="Minimum 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" name="password_confirm" placeholder="Re-enter password" required>
                    </div>
                </div>
                <button type="submit">➕ Add Coordinator</button>
            </form>
        </div>
        
        <!-- Coordinators Table -->
        <div class="table-section">
            <h2 style="margin-bottom: 15px; color: #333;">Coordinators List (<?php echo $total_coordinators; ?>)</h2>
            <?php if ($total_coordinators > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Students Registered</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coordinators as $coordinator): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($coordinator['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($coordinator['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $coordinator['status']; ?>">
                                        <?php echo strtoupper($coordinator['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $coordinator['students_registered']; ?></strong> students
                                </td>
                                <td><?php echo date('d-M-Y', strtotime($coordinator['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="coordinator_id" value="<?php echo $coordinator['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $coordinator['status'] == 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="action-btn toggle-btn">
                                                <?php echo $coordinator['status'] == 'active' ? '⏸ Deactivate' : '▶ Activate'; ?>
                                            </button>
                                        </form>
                                        
                                        <button class="action-btn delete-btn" onclick="showDeleteModal(<?php echo $coordinator['id']; ?>, '<?php echo htmlspecialchars($coordinator['username']); ?>')">
                                            🗑 Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-message">
                    <p>No coordinators added yet.</p>
                    <p>Add your first coordinator using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h2>⚠️ Confirm Delete</h2>
            <p>Are you sure you want to delete coordinator: <strong id="coordinatorName"></strong>?</p>
            <p style="font-size: 12px; color: #999;">This action cannot be undone.</p>
            
            <form method="POST" style="display: inline-block; width: 100%;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="coordinator_id" id="coordinatorId" value="">
                
                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="modal-btn modal-btn-confirm">Delete</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showDeleteModal(coordinatorId, coordinatorName) {
            document.getElementById('coordinatorId').value = coordinatorId;
            document.getElementById('coordinatorName').textContent = coordinatorName;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>
