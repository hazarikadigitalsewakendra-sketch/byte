<?php
/**
 * Verify Payment - Admin Payment Verification Handler
 * ByteLab Olympiad Management System
 */

require_once '../config/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo 'error';
    exit();
}

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'])) {
    $student_id = (int)$_POST['student_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get student details
        $stmt = $conn->prepare('SELECT id, payment_status FROM students WHERE id = ?');
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Student not found');
        }
        
        $student = $result->fetch_assoc();
        $stmt->close();
        
        // Update student payment status
        $stmt = $conn->prepare('UPDATE students SET payment_status = ? WHERE id = ?');
        $payment_status = 'paid';
        $stmt->bind_param('si', $payment_status, $student_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update student payment status');
        }
        $stmt->close();
        
        // Update payment record if exists
        $stmt = $conn->prepare('SELECT id FROM payments WHERE student_id = ? AND status = ?');
        $pending_status = 'pending';
        $stmt->bind_param('is', $student_id, $pending_status);
        $stmt->execute();
        $payment_result = $stmt->get_result();
        $stmt->close();
        
        if ($payment_result->num_rows > 0) {
            // Update payment record
            $verified_by = $_SESSION['user_id'];
            $verified_date = date('Y-m-d H:i:s');
            $verified_status = 'verified';
            
            $stmt = $conn->prepare('UPDATE payments SET status = ?, verification_date = ?, verified_by = ? WHERE student_id = ? AND status = ?');
            $stmt->bind_param('sssii', $verified_status, $verified_date, $verified_by, $student_id, $pending_status);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update payment record');
            }
            $stmt->close();
        } else {
            // Create payment record if doesn't exist
            $amount = 0;
            $payment_method = 'cash';
            $verified_by = $_SESSION['user_id'];
            $verified_date = date('Y-m-d H:i:s');
            $verified_status = 'verified';
            
            $stmt = $conn->prepare('INSERT INTO payments (student_id, amount, payment_method, status, verification_date, verified_by) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('idsssi', $student_id, $amount, $payment_method, $verified_status, $verified_date, $verified_by);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create payment record');
            }
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        echo 'success';
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        echo 'error: ' . $e->getMessage();
    }
} else {
    echo 'error: Invalid request';
}
?>
