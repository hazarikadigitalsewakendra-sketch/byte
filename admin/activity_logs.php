<?php
/**
 * Activity Logs - Admin Panel
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

// Clear logs
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'clear_logs') {
    $days = (int)$_POST['days'] ?? 0;
    
    if ($days > 0) {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $stmt = prepare_query($conn, 'DELETE FROM activity_logs WHERE created_at < ?');
        $stmt->bind_param('s', $date);
        
        if ($stmt->execute()) {
            $message = "Activity logs older than $days days deleted!";
        } else {
            $error = 'Error clearing logs!';
        }
        $stmt->close();
    }
}

// Get statistics
$total_logs = $conn->query('SELECT COUNT(*) as count FROM activity_logs')->fetch_assoc()['count'];
$today_logs = $conn->query('SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()')->fetch_assoc()['count'];

// Get activity logs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

$logs_result = $conn->query("
    SELECT 
        l.id,
        l.user_id,
        l.action,
        l.entity_type,
        l.entity_id,
        l.description,
        l.created_at,
        u.username
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT $offset, $per_page
");

$logs = [];
while ($row = $logs_result->fetch_assoc()) {
    $logs[] = $row;
}

// Get total pages
$total_result = $conn->query('SELECT COUNT(*) as count FROM activity_logs');
$total = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total / $per_page);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - <?php echo SYSTEM_NAME; ?></title>
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
        .controls { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { display: flex; gap: 10px; align-items: flex-end; }
        .form-group input, .form-group select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group button { background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; }
        .form-group button:hover { background: #c0392b; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .table-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f0f0; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #ddd; font-size: 13px; }
        tr:hover { background: #f9f9f9; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
        .badge-create { background: #d4edda; color: #155724; }
        .badge-update { background: #d1ecf1; color: #0c5460; }
        .badge-delete { background: #f8d7da; color: #721c24; }
        .badge-login { background: #cfe2ff; color: #084298; }
        .pagination { display: flex; gap: 5px; justify-content: center; margin-top: 20px; }
        .pagination a, .pagination span { padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; color: #667eea; }
        .pagination a:hover { background: #667eea; color: white; }
        .pagination .active { background: #667eea; color: white; border-color: #667eea; }
        .empty-message { text-align: center; color: #999; padding: 40px; }
        .timestamp { color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1>📋 Activity Logs</h1>
            <p>Track all administrative actions and system changes</p>
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
                <div class="stat-number"><?php echo $total_logs; ?></div>
                <div class="stat-label">Total Logs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $today_logs; ?></div>
                <div class="stat-label">Today's Activities</div>
            </div>
        </div>
        
        <!-- Clear Logs -->
        <div class="controls">
            <form method="POST" style="display: flex; gap: 10px; align-items: flex-end;">
                <input type="hidden" name="action" value="clear_logs">
                <div class="form-group">
                    <label style="margin-bottom: 0; margin-right: 5px;">Clear logs older than:</label>
                    <select name="days" required>
                        <option value="">-- Select Period --</option>
                        <option value="7">7 days</option>
                        <option value="30">30 days</option>
                        <option value="90">90 days</option>
                        <option value="180">180 days</option>
                    </select>
                    <button type="submit" onclick="return confirm('Are you sure? This cannot be undone.');">🗑 Clear Logs</button>
                </div>
            </form>
        </div>
        
        <!-- Logs Table -->
        <div class="table-section">
            <h2 style="margin-bottom: 15px; color: #333;">Activity Log (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)</h2>
            
            <?php if (count($logs) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="timestamp"><?php echo date('d-M-Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($log['action']); ?>">
                                        <?php echo strtoupper($log['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['entity_type'] ?? '-'); ?> (#<?php echo $log['entity_id'] ?? '-'; ?>)</td>
                                <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-message">
                    <p>No activity logs found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
