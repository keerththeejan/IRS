-- Create user_permissions table if not exists
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_name VARCHAR(50) NOT NULL,
    is_allowed BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_permission (user_id, permission_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default permissions for students
INSERT INTO user_permissions (user_id, permission_name, is_allowed) 
SELECT id, 'view_profile', 1 FROM users WHERE role = 'student'
ON DUPLICATE KEY UPDATE is_allowed = 1;

INSERT INTO user_permissions (user_id, permission_name, is_allowed) 
SELECT id, 'edit_profile', 1 FROM users WHERE role = 'student'
ON DUPLICATE KEY UPDATE is_allowed = 1;

-- Add more default permissions as needed
