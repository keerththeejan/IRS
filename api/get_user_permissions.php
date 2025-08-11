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

// Default response with default permissions
$response = [
    'success' => true,
    'permissions' => [
        'viewProfile' => true,  // Always true as it's required
        'editProfile' => true,
        'emailNotifications' => true,
        'enrollModules' => true,
        'viewGrades' => true
    ]
];

try {
    // Check if the permissions table exists
    $tableCheck = $con->query("SHOW TABLES LIKE 'user_permissions'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Get permissions from database
        $sql = "SELECT permission_name, is_allowed FROM user_permissions WHERE username = ?";
        $stmt = $con->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                $dbPermissions = [];
                while ($row = $result->fetch_assoc()) {
                    $dbPermissions[$row['permission_name']] = (bool)$row['is_allowed'];
                }
                
                // Merge with defaults (database values take precedence)
                $response['permissions'] = array_merge($response['permissions'], $dbPermissions);
            }
            $stmt->close();
        }
    }
    
} catch (Exception $e) {
    // Log error but still return default permissions
    error_log("Error getting user permissions: " . $e->getMessage());
}

// Set JSON header
header('Content-Type: application/json');
echo json_encode($response);
