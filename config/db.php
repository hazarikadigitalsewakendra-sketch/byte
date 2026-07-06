<?php
/**
 * Database Configuration
 * ByteLab Olympiad Management System
 */

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bytelab_olympiad');
define('DB_PORT', 3306);

// System Settings
define('SYSTEM_NAME', 'ByteLab Olympiad Management System');
define('SYSTEM_VERSION', '1.0.0');
define('TIMEZONE', 'Asia/Kolkata');

// Set Timezone
date_default_timezone_set(TIMEZONE);

// Create Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    
    // Check Connection
    if ($conn->connect_error) {
        die('Database Connection Error: ' . $conn->connect_error);
    }
    
    // Set Charset
    $conn->set_charset('utf8mb4');
    
} catch (Exception $e) {
    die('Connection Failed: ' . $e->getMessage());
}

// Helper function for prepared statements
function prepare_query($conn, $sql) {
    return $conn->prepare($sql);
}

// Helper function for escaping input
function escape_input($conn, $data) {
    return $conn->real_escape_string(trim($data));
}

// Helper function for hashing passwords
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Helper function for verifying passwords
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Session Configuration
ini_set('session.gc_maxlifetime', 3600);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
