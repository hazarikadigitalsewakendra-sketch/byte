<?php
/**
 * Student List - Coordinator Panel
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

// Get all students registered by all coordinators
$students_result = $conn->query('
    SELECT 
        s.id,
        s.registration_no,
        s.name,
        s.dob,
        s.mobile,
        s.class,
        s.school_name,
        s.payment_status,
        s.centre_id,
        ec.centre_name,
        s.created_at
    FROM students s
    LEFT JOIN exam_centres ec ON s.centre_id = ec.id
    ORDER BY s.created_at DESC
');

$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}

// Get statistics
$total_students = count($students);
$pending_payment = 0;
$paid_payment = 0;
$allocated_centre = 0;

foreach ($students as $student) {
    if ($student['payment_status'] == 'pending') {
        $pending_payment++;
    } else {
        $paid_payment++;
    }
    if ($student['centre_id']) {
        $allocated_centre++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List - <?php echo SYSTEM_NAME; ?></title>
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
        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header h1 { color: #333; margin-bottom: 5px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #667eea; text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 12px; color: #999; margin-top: 5px; }
        .table-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f0f0; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; font-size: 13px; }
        td { padding: 12px; border-bottom: 1px solid #ddd; font-size: 13px; }
        tr:hover { background: #f9f9f9; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
        .badge-pending { background: #ffeaa7; color: #d63031; }
        .badge-paid { background: #a9e4d4; color: #00b894; }
        .badge-allocated { background: #d5f4e6; color: #27ae60; }
        .badge-unallocated { background: #fadbd8; color: #c0392b; }
        .search-box { margin-bottom: 20px; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .empty-message { text-align: center; color: #999; padding: 40px; }
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
            <div class="header">
                <h1>📋 Student List</h1>
                <p>View and manage all registered students</p>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_payment; ?></div>
                    <div class="stat-label">Pending Payment</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $paid_payment; ?></div>
                    <div class="stat-label">Payment Verified</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $allocated_centre; ?></div>
                    <div class="stat-label">Centre Allocated</div>
                </div>
            </div>
            
            <!-- Search Box -->
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search by name, registration no, or mobile..." onkeyup="filterTable()">
            </div>
            
            <!-- Students Table -->
            <div class="table-section">
                <h2 style="margin-bottom: 15px; color: #333;">All Students (<?php echo $total_students; ?>)</h2>
                <?php if ($total_students > 0): ?>
                    <table id="studentTable">
                        <thead>
                            <tr>
                                <th>Registration No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Mobile</th>
                                <th>School</th>
                                <th>Payment Status</th>
                                <th>Centre Allocation</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['registration_no']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                                    <td><?php echo htmlspecialchars($student['mobile']); ?></td>
                                    <td><?php echo htmlspecialchars($student['school_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $student['payment_status']; ?>">
                                            <?php echo strtoupper($student['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($student['centre_id']): ?>
                                            <span class="badge badge-allocated">✓ <?php echo htmlspecialchars($student['centre_name']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-unallocated">✗ Not Allocated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d-M-Y', strtotime($student['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-message">
                        <p>No students registered yet.</p>
                        <p><a href="add_student.php" style="color: #667eea; text-decoration: none; font-weight: 600;">➕ Add the first student</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('studentTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }
    </script>
</body>
</html>
