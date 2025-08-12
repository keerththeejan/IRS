<?php
// Include configuration and session
include_once("../config.php");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_name']) || $_SESSION['user_type'] != 'ADM') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Get user ID from request
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$userId = $con->real_escape_string($_POST['user_id']);

// Get username from database
$userQuery = $con->query("SELECT user_name, user_table FROM `user` WHERE user_id = '$userId'");
if (!$userQuery || $userQuery->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$userData = $userQuery->fetch_assoc();
$username = $userData['user_name'];
$userType = $userData['user_table'];

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
        } else {
            // If no permissions found, set default permissions based on user type
            if ($userType === 'staff') {
                $response['permissions'] = [
                    'viewProfile' => true,
                    'editProfile' => true,
                    'emailNotifications' => true,
                    'enrollModules' => false,
                    'viewGrades' => true,
                    'manageUsers' => false,
                    'manageContent' => false,
                    'viewReports' => true
                ];
            } else if ($userType === 'student') {
                $response['permissions'] = [
                    'viewProfile' => true,
                    'editProfile' => true,
                    'emailNotifications' => true,
                    'enrollModules' => true,
                    'viewGrades' => true,
                    'manageUsers' => false,
                    'manageContent' => false,
                    'viewReports' => false
                ];
            }
        }
    }
    
} catch (Exception $e) {
    // Log error but still return default permissions
    error_log("Error getting user permissions: " . $e->getMessage());
}

// Set JSON header
header('Content-Type: application/json');
echo json_encode($response);
