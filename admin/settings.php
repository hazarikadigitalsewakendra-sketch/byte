<?php
/**
 * System Settings - Admin Panel
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

// Get current settings
$settings = [];
$settings_result = $conn->query('SELECT setting_key, setting_value FROM settings');
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Update settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_settings') {
    $setting_keys = ['exam_date', 'exam_time', 'exam_duration', 'result_publication_date', 'registration_deadline', 'payment_amount'];
    
    $conn->begin_transaction();
    try {
        foreach ($setting_keys as $key) {
            $value = escape_input($conn, $_POST[$key] ?? '');
            
            // Check if setting exists
            $check_stmt = prepare_query($conn, 'SELECT id FROM settings WHERE setting_key = ?');
            $check_stmt->bind_param('s', $key);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing setting
                $stmt = prepare_query($conn, 'UPDATE settings SET setting_value = ? WHERE setting_key = ?');
                $stmt->bind_param('ss', $value, $key);
            } else {
                // Insert new setting
                $stmt = prepare_query($conn, 'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');
                $stmt->bind_param('ss', $key, $value);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Error saving setting: ' . $key);
            }
            $stmt->close();
            $check_stmt->close();
        }
        
        $conn->commit();
        $message = 'Settings saved successfully!';
        
        // Refresh settings
        $settings = [];
        $settings_result = $conn->query('SELECT setting_key, setting_value FROM settings');
        while ($row = $settings_result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header h1 { color: #333; margin-bottom: 10px; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link:hover { color: #764ba2; }
        .settings-section { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .settings-section h2 { color: #667eea; margin-bottom: 20px; font-size: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
        input, select { padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-weight: 600; transition: transform 0.2s; }
        button:hover { transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid; }
        .alert-error { background: #fee; color: #c33; border-color: #c33; }
        .alert-success { background: #efe; color: #3c3; border-color: #3c3; }
        .settings-info { background: #f0f7ff; border-left: 3px solid #667eea; padding: 12px; border-radius: 5px; font-size: 13px; color: #2c5aa0; margin-bottom: 20px; }
        .settings-info strong { color: #1976d2; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1>⚙️ System Settings</h1>
            <p>Configure exam details and system-wide parameters</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="save_settings">
            
            <!-- Exam Schedule Section -->
            <div class="settings-section">
                <h2>📅 Exam Schedule</h2>
                <div class="settings-info">
                    <strong>Note:</strong> Set the exam date, time, and duration details. These will be displayed to students on their admit cards.
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Exam Date *</label>
                        <input type="date" name="exam_date" value="<?php echo $settings['exam_date'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Exam Time *</label>
                        <input type="time" name="exam_time" value="<?php echo $settings['exam_time'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Exam Duration (minutes) *</label>
                        <input type="number" name="exam_duration" value="<?php echo $settings['exam_duration'] ?? '120'; ?>" min="30" max="300" required>
                    </div>
                </div>
            </div>
            
            <!-- Registration & Deadline Section -->
            <div class="settings-section">
                <h2>📝 Registration Settings</h2>
                <div class="settings-info">
                    <strong>Note:</strong> Set the registration deadline and payment amount for student registration.
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Registration Deadline *</label>
                        <input type="date" name="registration_deadline" value="<?php echo $settings['registration_deadline'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Payment Amount (₹) *</label>
                        <input type="number" name="payment_amount" value="<?php echo $settings['payment_amount'] ?? '100'; ?>" min="0" step="10" required>
                    </div>
                </div>
            </div>
            
            <!-- Results Publication Section -->
            <div class="settings-section">
                <h2>📊 Results Publication</h2>
                <div class="settings-info">
                    <strong>Note:</strong> Set the date when results will be published and visible to students.
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Result Publication Date *</label>
                        <input type="date" name="result_publication_date" value="<?php echo $settings['result_publication_date'] ?? ''; ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="settings-section" style="text-align: right;">
                <button type="submit">💾 Save Settings</button>
            </div>
        </form>
    </div>
</body>
</html>
