-- Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    username VARCHAR(50) NOT NULL,
    department VARCHAR(50) NOT NULL,
    action VARCHAR(20) NOT NULL COMMENT 'LOGIN, LOGOUT, CREATE, UPDATE, DELETE',
    module VARCHAR(50) NOT NULL COMMENT 'auth, admin, warehouse, production, finance',
    target_type VARCHAR(50) NOT NULL COMMENT 'user, customer, item, po, delivery, production, excess, receipt, price_list',
    target_id INT DEFAULT NULL,
    description TEXT NOT NULL,
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_department (department),
    INDEX idx_module (module),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
