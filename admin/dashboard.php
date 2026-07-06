<?php
/**
 * Admin Dashboard
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get Statistics
$total_students = $conn->query('SELECT COUNT(*) as count FROM students')->fetch_assoc()['count'];
$paid_students = $conn->query('SELECT COUNT(*) as count FROM students WHERE payment_status = "paid"')->fetch_assoc()['count'];
$total_centres = $conn->query('SELECT COUNT(*) as count FROM exam_centres')->fetch_assoc()['count'];
$published_results = $conn->query('SELECT COUNT(*) as count FROM results WHERE status = "published"')->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .sidebar h2 {
            margin-bottom: 30px;
            font-size: 20px;
        }
        .sidebar ul {
            list-style: none;
        }
        .sidebar li {
            margin-bottom: 15px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .main-content {
            flex: 1;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            color: #333;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #667eea;
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .quick-actions {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .quick-actions h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: transform 0.2s;
        }
        .action-btn:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2>🧠 ByteLab</h2>
            <ul>
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="students.php">👥 Manage Students</a></li>
                <li><a href="payment.php">💳 Payment Verification</a></li>
                <li><a href="centres.php">🏢 Exam Centres</a></li>
                <li><a href="allocate.php">📍 Allocate Students</a></li>
                <li><a href="results.php">📈 Results</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="number"><?php echo $total_students; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Payment Verified</h3>
                    <div class="number"><?php echo $paid_students; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Exam Centres</h3>
                    <div class="number"><?php echo $total_centres; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Published Results</h3>
                    <div class="number"><?php echo $published_results; ?></div>
                </div>
            </div>
            
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="students.php" class="action-btn">➕ Add Student</a>
                    <a href="payment.php" class="action-btn">✅ Verify Payment</a>
                    <a href="allocate.php" class="action-btn">📍 Allocate Centre</a>
                    <a href="results.php" class="action-btn">📊 Enter Results</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
