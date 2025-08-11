<?php
// Include configuration and session
include_once("../config.php");
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get username from session
$username = $_SESSION['user_name'];

// Get permissions from POST data
$permissions = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['permissions'])) {
    $permissions = json_decode($_POST['permissions'], true);
}

// Validate input
if (empty($permissions) || !is_array($permissions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid permissions data']);
    exit;
}

// Initialize response
$response = ['success' => true, 'message' => 'Permissions updated successfully'];

try {
    // Check if the permissions table exists
    $tableCheck = $con->query("SHOW TABLES LIKE 'user_permissions'");
    
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        // Create the table if it doesn't exist
        $createTableSql = "
            CREATE TABLE IF NOT EXISTS user_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                permission_name VARCHAR(50) NOT NULL,
                is_allowed BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_permission (username, permission_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        if (!$con->query($createTableSql)) {
            throw new Exception('Failed to create permissions table');
        }
    }
    
    // Start transaction
    $con->begin_transaction();
    
    // Prepare the update statement
    $sql = "INSERT INTO user_permissions (username, permission_name, is_allowed) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE is_allowed = VALUES(is_allowed)";
    $stmt = $con->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $con->error);
    }
    
    // Update each permission
    foreach ($permissions as $permission => $isAllowed) {
        // Skip viewProfile as it's required and can't be changed
        if ($permission === 'viewProfile') {
            continue;
        }
        
        $isAllowed = (bool)$isAllowed ? 1 : 0;
        $stmt->bind_param("ssi", $username, $permission, $isAllowed);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update permission: ' . $stmt->error);
        }
    }
    
    // Commit the transaction
    $con->commit();
    
} catch (Exception $e) {
    // Rollback the transaction on error
    if (isset($con) && $con) {
        $con->rollback();
    }
    $response = [
        'success' => false, 
        'message' => 'Error updating permissions: ' . $e->getMessage()
    ];
    
    // Log the error
    error_log("Error updating user permissions: " . $e->getMessage());
}

// Set JSON header
header('Content-Type: application/json');
echo json_encode($response);
