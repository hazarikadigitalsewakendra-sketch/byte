<?php
/**
 * Public Homepage
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .header { background: rgba(255,255,255,0.1); color: white; padding: 20px; text-align: center; }
        .header h1 { font-size: 36px; margin-bottom: 10px; }
        .container { max-width: 900px; margin: 50px auto; padding: 20px; }
        .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 20px; }
        .card h2 { color: #667eea; margin-bottom: 20px; }
        .card p { color: #666; margin-bottom: 15px; line-height: 1.6; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 15px 30px; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 600; margin-right: 10px; margin-top: 10px; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-3px); }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .feature { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .feature h3 { color: #667eea; margin-bottom: 10px; }
        .feature p { color: #666; font-size: 14px; }
        .footer { background: rgba(255,255,255,0.1); color: white; text-align: center; padding: 20px; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧠 ByteLab Olympiad</h1>
        <p>District Talent Examination Portal</p>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Welcome to ByteLab Olympiad Management System</h2>
            <p>Welcome to the official portal for ByteLab District Olympiad. Here you can:</p>
            <ul style="margin-left: 20px; color: #666;">
                <li>Download your admit card</li>
                <li>Check your exam results</li>
                <li>Download your certificate</li>
            </ul>
        </div>
        
        <div class="features">
            <div class="feature">
                <h3>📋 Admit Card</h3>
                <p>Download your admit card using registration number and mobile</p>
                <a href="admit_card.php" class="btn" style="display: inline-block; margin-top: 15px; padding: 10px 20px;">View</a>
            </div>
            <div class="feature">
                <h3>📊 Results</h3>
                <p>Check your exam results and ranking</p>
                <a href="result.php" class="btn" style="display: inline-block; margin-top: 15px; padding: 10px 20px;">Check</a>
            </div>
            <div class="feature">
                <h3>🎖️ Certificate</h3>
                <p>Download your achievement certificate</p>
                <a href="certificate.php" class="btn" style="display: inline-block; margin-top: 15px; padding: 10px 20px;">Download</a>
            </div>
        </div>
        
        <div class="card">
            <h2>📅 Important Dates</h2>
            <p><strong>Exam Date:</strong> To be announced</p>
            <p><strong>Result Publication:</strong> To be announced</p>
            <p><strong>For more information, contact the admin panel</strong></p>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2026 ByteLab Olympiad Management System | All Rights Reserved</p>
    </div>
</body>
</html>
