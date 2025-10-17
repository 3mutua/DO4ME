-- DO4ME Platform Database Schema
SET FOREIGN_KEY_CHECKS=0;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('client', 'freelancer', 'admin') DEFAULT 'client',
    phone VARCHAR(20),
    country CHAR(3),
    profile_picture VARCHAR(500),
    bio TEXT,
    skills JSON,
    wallet_balance DECIMAL(12,2) DEFAULT 0.00,
    is_verified BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    completed_tasks_count INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_country (country)
);

-- Tasks table
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    client_id INT NOT NULL,
    freelancer_id INT NULL,
    budget DECIMAL(10,2) NOT NULL CHECK (budget > 0),
    platform_fee DECIMAL(10,2) DEFAULT 0.00,
    transaction_fee DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('draft', 'open', 'assigned', 'in_progress', 'completed', 'cancelled', 'disputed') DEFAULT 'draft',
    category ENUM('writing', 'design', 'programming', 'marketing', 'data_entry', 'customer_service', 'other') DEFAULT 'other',
    urgency ENUM('low', 'medium', 'high') DEFAULT 'medium',
    duration_days INT DEFAULT 7,
    attachments JSON,
    assigned_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_client_id (client_id),
    INDEX idx_freelancer_id (freelancer_id)
);

-- Proposals table
CREATE TABLE proposals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    cover_letter TEXT NOT NULL,
    estimated_days INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_task_freelancer (task_id, freelancer_id),
    INDEX idx_status (status)
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    task_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) DEFAULT 'USD',
    payment_method ENUM('stripe', 'mpesa', 'paypal', 'flutterwave') NOT NULL,
    gateway_reference VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    INDEX idx_gateway_reference (gateway_reference),
    INDEX idx_status (status)
);

-- Wallet transactions table
CREATE TABLE wallet_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('deposit', 'withdrawal', 'task_payment', 'refund', 'commission') NOT NULL,
    description VARCHAR(500),
    reference_id VARCHAR(100),
    balance_after DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type)
);

-- Task milestones table
CREATE TABLE task_milestones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'approved') DEFAULT 'pending',
    due_date DATE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_status (status)
);

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating TINYINT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_task_review (task_id, reviewer_id),
    INDEX idx_reviewee_id (reviewee_id)
);

-- Messages table
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id),
    INDEX idx_sender_receiver (sender_id, receiver_id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    related_entity_type ENUM('task', 'payment', 'proposal', 'message') DEFAULT NULL,
    related_entity_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
);

-- Audit log table
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id)
);

SET FOREIGN_KEY_CHECKS=1;