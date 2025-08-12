<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration and session
include_once("../config.php");
session_start();

// Log the start of the script
error_log("Starting update_user_permissions.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_name']) || $_SESSION['user_type'] != 'ADM') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Get user ID from POST data
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

// Log the POST data
error_log("POST data: " . print_r($_POST, true));

// Get user details from database
$userId = isset($_POST['user_id']) ? $con->real_escape_string($_POST['user_id']) : '';
$userQuery = $con->query("SELECT user_name FROM `user` WHERE user_id = '$userId'");

// Log any SQL errors
if (!$userQuery) {
    error_log("SQL Error: " . $con->error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $con->error]);
    exit;
}

if ($userQuery->num_rows === 0) {
    error_log("User not found with ID: " . $userId);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$userData = $userQuery->fetch_assoc();
$username = $userData['user_name'];

// Get permissions from POST data
$permissions = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['permissions'])) {
    error_log("Raw permissions data: " . $_POST['permissions']);
    $permissions = json_decode($_POST['permissions'], true);
    
    // Log JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid permissions data: ' . json_last_error_msg(),
            'raw_data' => $_POST['permissions']
        ]);
        exit;
    }
}

// Log the decoded permissions
error_log("Decoded permissions: " . print_r($permissions, true));

// Validate input
if (empty($permissions) || !is_array($permissions)) {
    $errorMsg = 'Invalid permissions data. ';
    $errorMsg .= 'Type: ' . gettype($permissions) . '. ';
    $errorMsg .= 'Value: ' . print_r($permissions, true);
    error_log($errorMsg);
    
    echo json_encode([
        'success' => false, 
        'message' => $errorMsg
    ]);
    exit;
}

// Define all possible permissions with their default values
$allPermissions = [
    'viewProfile' => true,  // Required permission, can't be changed
    'editProfile' => false,
    'emailNotifications' => false,
    'enrollModules' => false,
    'viewGrades' => false,
    'manageUsers' => false,
    'manageContent' => false,
    'viewReports' => false
];

// Merge with default permissions to ensure all permissions are included
$permissions = array_merge($allPermissions, $permissions);

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
    if (!$con->begin_transaction()) {
        throw new Exception('Failed to start transaction: ' . $con->error);
    }
    
    error_log("Transaction started successfully");
    
    // First, check if the table exists
    $tableCheck = $con->query("SHOW TABLES LIKE 'user_permissions'");
    if (!$tableCheck) {
        throw new Exception('Failed to check for user_permissions table: ' . $con->error);
    }
    
    if ($tableCheck->num_rows === 0) {
        error_log("user_permissions table does not exist, creating it...");
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
            throw new Exception('Failed to create permissions table: ' . $con->error);
        }
        error_log("Created user_permissions table");
    }
    
    // Prepare the update statement
    $sql = "INSERT INTO user_permissions (username, permission_name, is_allowed) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE is_allowed = VALUES(is_allowed)";
            
    error_log("Preparing SQL: $sql");
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
        error_log("Updating permission - User: $username, Permission: $permission, Allowed: $isAllowed");
        
        try {
            $stmt->bind_param("ssi", $username, $permission, $isAllowed);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute permission update: ' . $stmt->error);
            }
            error_log("Successfully updated permission: $permission");
        } catch (Exception $e) {
            error_log("Error updating permission $permission: " . $e->getMessage());
            throw $e;
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
