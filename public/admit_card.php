<?php
/**
 * Admit Card Download
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

$student = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_no = escape_input($conn, $_POST['registration_no'] ?? '');
    $mobile = escape_input($conn, $_POST['mobile'] ?? '');
    
    if ($reg_no && $mobile) {
        $stmt = prepare_query($conn, 'SELECT s.*, e.centre_name, e.address FROM students s LEFT JOIN exam_centres e ON s.centre_id = e.id WHERE s.registration_no = ? AND s.mobile = ?');
        $stmt->bind_param('ss', $reg_no, $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $student = $result->fetch_assoc();
            
            if ($student['payment_status'] != 'paid') {
                $error = 'Payment not verified. Please contact admin.';
                $student = null;
            } elseif (!$student['centre_id']) {
                $error = 'Exam centre not allocated. Please contact admin.';
                $student = null;
            }
        } else {
            $error = 'Student not found. Please check your registration number and mobile.';
        }
        $stmt->close();
    } else {
        $error = 'Both fields are required!';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Card - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 700px; margin: 30px auto; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card h1 { color: #667eea; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        button { width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 16px; }
        button:hover { transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .admit-card { background: #f9f9f9; border: 3px solid #667eea; padding: 20px; border-radius: 10px; margin-top: 20px; page-break-inside: avoid; }
        .admit-header { text-align: center; border-bottom: 2px solid #667eea; padding-bottom: 15px; margin-bottom: 15px; }
        .admit-header h2 { color: #667eea; margin-bottom: 5px; }
        .admit-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .admit-field { border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .admit-label { font-size: 12px; color: #999; font-weight: 600; }
        .admit-value { font-size: 16px; color: #333; font-weight: 600; }
        .print-btn { background: #27ae60; margin-top: 15px; }
        .back-link { display: inline-block; color: white; text-decoration: none; margin-bottom: 20px; }
        @media print {
            body { background: white; }
            .container { margin: 0; }
            .card { box-shadow: none; border: none; }
            form { display: none; }
            .print-btn { display: none; }
            .back-link { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Home</a>
        
        <div class="card">
            <h1>📋 Admit Card</h1>
            
            <?php if (!$student): ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Registration Number *</label>
                        <input type="text" name="registration_no" placeholder="e.g., BDO260001" required>
                    </div>
                    <div class="form-group">
                        <label>Mobile Number *</label>
                        <input type="tel" name="mobile" placeholder="e.g., 9876543210" required>
                    </div>
                    <button type="submit">Get Admit Card</button>
                </form>
            <?php else: ?>
                <div class="admit-card">
                    <div class="admit-header">
                        <h2>🧠 ByteLab Olympiad</h2>
                        <p>ADMIT CARD</p>
                    </div>
                    
                    <div class="admit-row">
                        <div class="admit-field">
                            <div class="admit-label">Student Name</div>
                            <div class="admit-value"><?php echo htmlspecialchars($student['name']); ?></div>
                        </div>
                        <div class="admit-field">
                            <div class="admit-label">Registration Number</div>
                            <div class="admit-value"><?php echo htmlspecialchars($student['registration_no']); ?></div>
                        </div>
                    </div>
                    
                    <div class="admit-row">
                        <div class="admit-field">
                            <div class="admit-label">Roll Number</div>
                            <div class="admit-value"><?php echo htmlspecialchars($student['roll_no'] ?? 'To be announced'); ?></div>
                        </div>
                        <div class="admit-field">
                            <div class="admit-label">Class</div>
                            <div class="admit-value"><?php echo htmlspecialchars($student['class']); ?></div>
                        </div>
                    </div>
                    
                    <div class="admit-row">
                        <div class="admit-field">
                            <div class="admit-label">Exam Centre</div>
                            <div class="admit-value"><?php echo htmlspecialchars($student['centre_name']); ?></div>
                        </div>
                        <div class="admit-field">
                            <div class="admit-label">School</div>
                            <div class="admit-value"><?php echo htmlspecialchars($student['school_name']); ?></div>
                        </div>
                    </div>
                    
                    <div class="admit-field">
                        <div class="admit-label">Centre Address</div>
                        <div class="admit-value"><?php echo htmlspecialchars($student['address']); ?></div>
                    </div>
                    
                    <div style="background: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 15px; font-size: 12px; color: #666;">
                        <p><strong>Important Instructions:</strong></p>
                        <ul style="margin-left: 20px;">
                            <li>Reach the exam centre 15 minutes before the exam</li>
                            <li>Carry this admit card and a valid ID proof</li>
                            <li>Use only black pen for the exam</li>
                            <li>Any malpractice will lead to disqualification</li>
                        </ul>
                    </div>
                    
                    <button class="print-btn" onclick="window.print()">🖨️ Print Admit Card</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
