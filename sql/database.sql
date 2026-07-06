-- ByteLab Olympiad Management System Database Schema
-- Version: 1.0.0

CREATE DATABASE IF NOT EXISTS bytelab_olympiad;
USE bytelab_olympiad;

-- Admin/Coordinator Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'coordinator') DEFAULT 'coordinator',
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    registration_no VARCHAR(20) UNIQUE NOT NULL,
    roll_no VARCHAR(20),
    name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    class VARCHAR(20) NOT NULL,
    school_name VARCHAR(200) NOT NULL,
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    payment_amount DECIMAL(10, 2),
    payment_date DATETIME,
    centre_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (centre_id) REFERENCES exam_centres(id) ON DELETE SET NULL
);

-- Exam Centres Table
CREATE TABLE IF NOT EXISTS exam_centres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    centre_name VARCHAR(200) NOT NULL,
    address TEXT NOT NULL,
    capacity INT NOT NULL,
    current_count INT DEFAULT 0,
    invigilator_name VARCHAR(100),
    invigilator_mobile VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Results Table
CREATE TABLE IF NOT EXISTS results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    marks INT,
    rank INT,
    status ENUM('pending', 'published') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_result (student_id)
);

-- Certificates Table
CREATE TABLE IF NOT EXISTS certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    certificate_no VARCHAR(50) UNIQUE NOT NULL,
    issued_date DATETIME,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Payment Records Table
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'online', 'cheque') DEFAULT 'cash',
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verification_date DATETIME,
    verified_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- System Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Indexes for Better Performance
CREATE INDEX idx_student_registration ON students(registration_no);
CREATE INDEX idx_student_mobile ON students(mobile);
CREATE INDEX idx_student_centre ON students(centre_id);
CREATE INDEX idx_student_payment ON students(payment_status);
CREATE INDEX idx_centre_capacity ON exam_centres(capacity, current_count);
CREATE INDEX idx_result_status ON results(status);
CREATE INDEX idx_payment_status ON payments(status);

-- Insert Default Admin User (username: admin, password: admin123)
INSERT INTO users (username, password, role, email) VALUES 
('admin', '$2y$10$YkBN8WkYg1C0V.J5jN8l4OV.5V5C0V.J5jN8l4OV', 'admin', 'admin@bytelab.com');

-- Insert Sample Exam Centres
INSERT INTO exam_centres (centre_name, address, capacity, invigilator_name, invigilator_mobile) VALUES 
('North Centre', 'Lakhimpur High School, North Road', 100, 'Dr. Rajesh Kumar', '9876543210'),
('South Centre', 'Lakhimpur Public School, South Road', 80, 'Mrs. Priya Singh', '9876543211'),
('East Centre', 'Lakhimpur Academy, East Road', 120, 'Mr. Amit Sharma', '9876543212'),
('West Centre', 'Lakhimpur International, West Road', 90, 'Ms. Neha Verma', '9876543213');

-- Insert Sample Settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('exam_date', '2026-08-15'),
('exam_time', '10:00 AM'),
('exam_duration', '2 hours'),
('result_publication_date', '2026-09-01'),
('registration_prefix', 'BDO26'),
('roll_number_prefix', 'ROLL26');
