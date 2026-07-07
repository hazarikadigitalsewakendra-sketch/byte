<?php
/**
 * Error Handler
 * ByteLab Olympiad Management System
 */

class ErrorHandler {
    private static $conn;
    
    public static function init($conn) {
        self::$conn = $conn;
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $error_msg = "Error [$errno]: $errstr in $errfile on line $errline";
        self::logError($error_msg, 'PHP_ERROR');
        
        // Show user-friendly message
        if (ini_get('display_errors')) {
            echo "<div style='background: #fee; color: #c33; padding: 15px; border: 1px solid #fcc; border-radius: 5px; margin: 20px;'>";
            echo "<strong>An error occurred:</strong> " . htmlspecialchars($errstr);
            echo "</div>";
        }
        
        return true;
    }
    
    public static function handleException($exception) {
        $error_msg = "Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
        self::logError($error_msg, 'EXCEPTION');
        
        http_response_code(500);
        echo "<div style='background: #fee; color: #c33; padding: 15px; border: 1px solid #fcc; border-radius: 5px; margin: 20px;'>";
        echo "<strong>An error occurred:</strong> Please contact the administrator.";
        echo "</div>";
    }
    
    public static function logError($message, $type = 'ERROR') {
        global $conn;
        
        if (!isset(self::$conn)) {
            return;
        }
        
        try {
            // Create error_logs table if it doesn't exist
            self::$conn->query("
                CREATE TABLE IF NOT EXISTS error_logs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    error_type VARCHAR(50),
                    error_message LONGTEXT,
                    error_file VARCHAR(255),
                    user_id INT,
                    ip_address VARCHAR(45),
                    user_agent VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt = self::$conn->prepare("
                INSERT INTO error_logs (error_type, error_message, user_id, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('ssiss', $type, $message, $user_id, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Silently fail - don't throw another error
        }
    }
    
    public static function logActivity($action, $entity_type, $entity_id, $description = '') {
        global $conn;
        
        if (!isset(self::$conn)) {
            return;
        }
        
        try {
            // Create activity_logs table if it doesn't exist
            self::$conn->query("
                CREATE TABLE IF NOT EXISTS activity_logs (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    action VARCHAR(50),
                    entity_type VARCHAR(50),
                    entity_id INT,
                    description TEXT,
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )
            ");
            
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            
            $stmt = self::$conn->prepare("
                INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('ississ', $user_id, $action, $entity_type, $entity_id, $description, $ip_address);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Silently fail
        }
    }
}
?>
