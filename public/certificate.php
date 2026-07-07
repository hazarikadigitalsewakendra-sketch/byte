<?php
/**
 * Certificate Download/View - Public Portal
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

$student = null;
$error = '';
$certificate = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_no = escape_input($conn, $_POST['registration_no'] ?? '');
    $mobile = escape_input($conn, $_POST['mobile'] ?? '');
    
    if ($reg_no && $mobile) {
        $stmt = prepare_query($conn, '
            SELECT 
                s.id,
                s.registration_no,
                s.name,
                s.class,
                s.school_name,
                s.roll_no,
                r.marks,
                r.rank,
                r.status as result_status,
                c.id as cert_id,
                c.certificate_no,
                c.issued_date,
                c.file_path
            FROM students s
            LEFT JOIN results r ON s.id = r.student_id
            LEFT JOIN certificates c ON s.id = c.student_id
            WHERE s.registration_no = ? AND s.mobile = ?
        ');
        $stmt->bind_param('ss', $reg_no, $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $student = $result->fetch_assoc();
            
            // Check if results are published
            if ($student['result_status'] != 'published') {
                $error = 'Results not yet published. Certificate will be available once results are published.';
                $student = null;
            } elseif (!$student['marks']) {
                $error = 'No marks found. Please contact admin.';
                $student = null;
            } elseif (!$student['cert_id']) {
                // Generate certificate if doesn't exist
                $certificate_no = 'CERT-' . date('Ymd') . '-' . str_pad($student['id'], 5, '0', STR_PAD_LEFT);
                $issued_date = date('Y-m-d H:i:s');
                
                $cert_stmt = prepare_query($conn, 'INSERT INTO certificates (student_id, certificate_no, issued_date) VALUES (?, ?, ?)');
                $cert_stmt->bind_param('iss', $student['id'], $certificate_no, $issued_date);
                
                if ($cert_stmt->execute()) {
                    $student['cert_id'] = $conn->insert_id;
                    $student['certificate_no'] = $certificate_no;
                    $student['issued_date'] = $issued_date;
                } else {
                    $error = 'Error generating certificate. Please try again.';
                    $student = null;
                }
                $cert_stmt->close();
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
    <title>Certificate - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 30px auto; padding: 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: white; text-decoration: none; font-weight: 600; }
        .back-link:hover { opacity: 0.8; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card h1 { color: #667eea; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        button { width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 14px; transition: transform 0.2s; }
        button:hover { transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        
        /* Certificate Styles */
        .certificate-container { background: white; border: 3px solid #c41e3a; padding: 40px; border-radius: 10px; text-align: center; margin-top: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); page-break-inside: avoid; }
        .cert-header { margin-bottom: 30px; }
        .cert-logo { font-size: 48px; margin-bottom: 10px; }
        .cert-title { font-size: 32px; font-weight: bold; color: #c41e3a; margin: 15px 0; text-transform: uppercase; }
        .cert-subtitle { font-size: 18px; color: #666; margin-bottom: 30px; }
        .cert-border { width: 80%; height: 2px; background: linear-gradient(90deg, transparent, #c41e3a, transparent); margin: 20px auto; }
        .cert-content { margin: 40px 0; }
        .cert-content p { margin: 15px 0; font-size: 16px; color: #333; }
        .presented-to { font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .student-name { font-size: 36px; font-weight: bold; color: #c41e3a; margin: 20px 0; }
        .cert-detail { font-size: 14px; color: #666; margin: 15px 0; }
        .cert-achievements { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .achievement-row { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 15px 0; }
        .achievement-item { text-align: center; }
        .achievement-label { font-size: 12px; color: #999; text-transform: uppercase; }
        .achievement-value { font-size: 24px; font-weight: bold; color: #667eea; margin: 5px 0; }
        .cert-footer { margin-top: 50px; font-size: 12px; color: #999; }
        .signature-line { display: inline-block; margin: 0 40px; border-top: 2px solid #333; width: 150px; padding-top: 5px; }
        .signature-label { font-size: 12px; color: #333; margin-top: 5px; }
        .cert-number { font-size: 11px; color: #999; margin-top: 20px; }
        .print-btn { background: #27ae60; margin-top: 20px; }
        .print-btn:hover { background: #229954; }
        .download-btn { background: #3498db; }
        .download-btn:hover { background: #2980b9; }
        .button-group { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
        
        @media print {
            body { background: white; }
            .container { margin: 0; padding: 0; }
            .back-link { display: none; }
            .card { box-shadow: none; border: none; }
            form { display: none; }
            .button-group { display: none; }
        }
        
        .no-cert-message { text-align: center; padding: 30px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Home</a>
        
        <div class="card">
            <h1>🎖️ Certificate Download</h1>
            
            <?php if (!$student): ?>
                <?php if ($error): ?>
                    <div class="alert alert-<?php echo (strpos($error, 'published') !== false || strpos($error, 'marks') !== false) ? 'warning' : 'error'; ?>">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
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
                    <button type="submit">Get Certificate</button>
                </form>
            <?php else: ?>
                <!-- Certificate Display -->
                <div class="certificate-container" id="certificateContent">
                    <div class="cert-header">
                        <div class="cert-logo">🎖️</div>
                        <div class="cert-title">Certificate of Achievement</div>
                        <div class="cert-subtitle">ByteLab Olympiad</div>
                        <div class="cert-border"></div>
                    </div>
                    
                    <div class="cert-content">
                        <p class="presented-to">This Certificate is proudly presented to</p>
                        <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
                        
                        <p style="font-style: italic; color: #999;">
                            For exceptional performance and outstanding participation in
                        </p>
                        <p style="font-weight: 600; font-size: 18px;">
                            ByteLab District Olympiad Examination
                        </p>
                        
                        <div class="cert-achievements">
                            <div class="achievement-row">
                                <div class="achievement-item">
                                    <div class="achievement-label">Marks Obtained</div>
                                    <div class="achievement-value"><?php echo $student['marks']; ?>/100</div>
                                </div>
                                <div class="achievement-item">
                                    <div class="achievement-label">Rank</div>
                                    <div class="achievement-value">#<?php echo $student['rank'] ? $student['rank'] : '-'; ?></div>
                                </div>
                            </div>
                            <div class="achievement-row">
                                <div class="achievement-item">
                                    <div class="achievement-label">Class</div>
                                    <div class="achievement-value"><?php echo htmlspecialchars($student['class']); ?></div>
                                </div>
                                <div class="achievement-item">
                                    <div class="achievement-label">School</div>
                                    <div class="achievement-value" style="font-size: 14px;"><?php echo htmlspecialchars($student['school_name']); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <p style="margin-top: 40px; font-size: 14px;">
                            Issued on: <strong><?php echo date('d F Y', strtotime($student['issued_date'])); ?></strong>
                        </p>
                    </div>
                    
                    <div class="cert-footer">
                        <div style="margin-bottom: 40px;">
                            <div class="signature-line"></div>
                            <div class="signature-label">Admin Signature</div>
                        </div>
                        
                        <div class="cert-number">
                            Certificate No: <?php echo htmlspecialchars($student['certificate_no']); ?><br>
                            Registration: <?php echo htmlspecialchars($student['registration_no']); ?>
                        </div>
                        
                        <p style="margin-top: 20px; font-size: 11px; color: #ccc;">
                            ByteLab Olympiad Management System | © 2026 All Rights Reserved
                        </p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="button-group">
                    <button class="print-btn" onclick="window.print()">🖨️ Print Certificate</button>
                    <button class="download-btn" onclick="downloadCertificate()">⬇️ Download as PDF</button>
                </div>
                
                <!-- Success Message -->
                <div class="alert alert-success" style="margin-top: 20px;">
                    ✅ <strong>Certificate Generated Successfully!</strong> Your certificate is ready for download and printing.
                </div>
                
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function downloadCertificate() {
            // For PDF download, we would typically use a library like html2pdf
            // For now, we'll use the browser's print-to-PDF feature
            alert('To save as PDF:\n1. Click "Print" button\n2. Change printer to "Save as PDF"\n3. Click "Save"\n\nOr use Print function with PDF printer.');
            window.print();
        }
        
        // Alternative: You can use html2pdf library
        // Add this to enable PDF download:
        // <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        /*
        function downloadCertificate() {
            const element = document.getElementById('certificateContent');
            const opt = {
                margin: 10,
                filename: 'certificate.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
            };
            html2pdf().set(opt).from(element).save();
        }
        */
    </script>
</body>
</html>
