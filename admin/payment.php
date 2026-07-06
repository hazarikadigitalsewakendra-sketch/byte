<?php
/**
 * Payment Verification - Admin Panel
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get pending payments
$payments = $conn->query('SELECT s.*, p.id as payment_id, p.amount, p.status FROM students s LEFT JOIN payments p ON s.id = p.student_id WHERE s.payment_status = "pending" ORDER BY s.created_at DESC LIMIT 50')->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Verification - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI'; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        th { background: #f0f0f0; padding: 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
        td { padding: 15px; border-bottom: 1px solid #ddd; }
        .btn { padding: 8px 15px; border-radius: 5px; cursor: pointer; border: none; color: white; font-weight: 600; }
        .btn-verify { background: #27ae60; }
        .btn-verify:hover { background: #229954; }
        .badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-paid { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💳 Payment Verification</h1>
            <p>Verify pending student payments</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Registration No</th>
                    <th>Student Name</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><strong><?php echo $payment['registration_no']; ?></strong></td>
                        <td><?php echo $payment['name']; ?></td>
                        <td><?php echo $payment['mobile']; ?></td>
                        <td><span class="badge badge-pending">PENDING</span></td>
                        <td>
                            <button class="btn btn-verify" onclick="verifyPayment(<?php echo $payment['id']; ?>)">✓ Verify</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script>
        function verifyPayment(studentId) {
            if (confirm('Verify payment for this student?')) {
                // Send verification request
                fetch('verify_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'student_id=' + studentId
                }).then(r => r.text()).then(d => {
                    if (d === 'success') {
                        alert('Payment verified!');
                        location.reload();
                    }
                });
            }
        }
    </script>
</body>
</html>
