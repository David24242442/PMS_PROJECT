-- PMS Database Schema
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS pms_database;
USE pms_database;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    job_title VARCHAR(100),
    role ENUM('employee', 'line_manager', 'hr', 'admin') DEFAULT 'employee',
    manager_id INT NULL,
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    head_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Goals table
CREATE TABLE IF NOT EXISTS goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    weight DECIMAL(5,2) DEFAULT 0,
    target_value DECIMAL(10,2),
    current_value DECIMAL(10,2) DEFAULT 0,
    status ENUM('not_started', 'in_progress', 'completed', 'cancelled') DEFAULT 'not_started',
    start_date DATE,
    due_date DATE,
    year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Appraisals table
CREATE TABLE IF NOT EXISTS appraisals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    appraisal_year INT NOT NULL,
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected') DEFAULT 'draft',
    
    -- Performance Ratings (1-5 scale)
    quality_rating INT CHECK (quality_rating BETWEEN 1 AND 5),
    productivity_rating INT CHECK (productivity_rating BETWEEN 1 AND 5),
    teamwork_rating INT CHECK (teamwork_rating BETWEEN 1 AND 5),
    communication_rating INT CHECK (communication_rating BETWEEN 1 AND 5),
    problem_solving_rating INT CHECK (problem_solving_rating BETWEEN 1 AND 5),
    initiative_rating INT CHECK (initiative_rating BETWEEN 1 AND 5),
    reliability_rating INT CHECK (reliability_rating BETWEEN 1 AND 5),
    
    -- Text sections
    self_assessment TEXT,
    achievements TEXT,
    areas_for_improvement TEXT,
    development_plan TEXT,
    goals_next_year TEXT,
    
    -- Manager review
    manager_comments TEXT,
    overall_rating DECIMAL(3,2),
    
    -- HR review
    hr_comments TEXT,
    hr_action_by INT NULL,
    hr_action_date TIMESTAMP NULL,
    
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hr_action_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_year (user_id, appraisal_year)
);

-- Goal ratings for appraisals
CREATE TABLE IF NOT EXISTS goal_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appraisal_id INT NOT NULL,
    goal_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    review_comments TEXT,
    FOREIGN KEY (appraisal_id) REFERENCES appraisals(id) ON DELETE CASCADE,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
);

-- CSV uploads table (for admin)
CREATE TABLE IF NOT EXISTS csv_uploads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    row_count INT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- CSV data table (dynamic storage)
CREATE TABLE IF NOT EXISTS csv_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    upload_id INT NOT NULL,
    row_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (upload_id) REFERENCES csv_uploads(id) ON DELETE CASCADE
);

-- HR Reports table
CREATE TABLE IF NOT EXISTS hr_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_type ENUM('performance_summary', 'department_overview', 'training_needs') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date_from DATE,
    date_to DATE,
    department_id INT NULL,
    report_data JSON,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Feedback/Check-ins table (for line managers)
CREATE TABLE IF NOT EXISTS feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    feedback_type ENUM('praise', 'constructive', 'check_in', 'one_on_one') NOT NULL,
    content TEXT NOT NULL,
    is_private BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (employee_id, first_name, last_name, email, password, department, job_title, role) 
VALUES ('EMP001', 'Admin', 'User', 'admin@pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'IT', 'System Administrator', 'admin')
ON DUPLICATE KEY UPDATE first_name = 'Admin';

-- Insert sample employees
INSERT INTO users (employee_id, first_name, last_name, email, password, department, job_title, role) VALUES
('EMP002', 'John', 'Doe', 'john.doe@pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Engineering', 'Software Developer', 'employee'),
('EMP003', 'Jane', 'Smith', 'jane.smith@pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Engineering', 'Team Lead', 'line_manager'),
('EMP004', 'Sarah', 'Johnson', 'sarah.johnson@pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR', 'HR Manager', 'hr'),
('EMP005', 'Michael', 'Brown', 'michael.brown@pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marketing', 'Marketing Specialist', 'employee')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- Set manager relationships
UPDATE users SET manager_id = (SELECT id FROM (SELECT id FROM users WHERE employee_id = 'EMP003') AS temp) WHERE employee_id = 'EMP002';
UPDATE users SET manager_id = (SELECT id FROM (SELECT id FROM users WHERE employee_id = 'EMP003') AS temp) WHERE employee_id = 'EMP005';

-- Insert sample departments
INSERT INTO departments (name, description) VALUES
('Engineering', 'Software development and technical operations'),
('HR', 'Human Resources and employee management'),
('Marketing', 'Marketing and brand management'),
('Finance', 'Financial operations and accounting')
ON DUPLICATE KEY UPDATE name = VALUES(name);
