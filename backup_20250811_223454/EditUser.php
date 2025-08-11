<?php
$title = "Edit User | SLGTI";
include_once("config.php");
include_once("head.php");
include_once("menu.php");

// Check if user is admin
if ($_SESSION['user_type'] != 'ADM') {
    header("Location: index.php");
    exit();
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

// Function to generate a random password
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Fetch user data
$user = null;
$sql = "SELECT u.*, spt.staff_position_type_name as position_name 
        FROM user u 
        LEFT JOIN staff_position_type spt ON u.staff_position_type_id = spt.staff_position_type_id 
        WHERE u.user_id = ?";

if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

if (!$user) {
    die("User not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $errors = [];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $position_type = $_POST['position_type'] ?? null;
    $reset_password = isset($_POST['reset_password']);
    
    // Validate email format
    if (!isValidEmail($email)) {
        $errors[] = "Invalid email format.";
    }
    
    // Check if email already exists (excluding current user)
    $check_sql = "SELECT user_id FROM user WHERE user_email = ? AND user_id != ?";
    if ($stmt = $con->prepare($check_sql)) {
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered to another user.";
        }
        $stmt->close();
    }
    
    // If there are validation errors, show them
    if (!empty($errors)) {
        $message = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    } else {
        // Proceed with updates if no errors
        
        // Handle password reset if requested
        $password_update = '';
        if ($reset_password) {
            $new_password = generateRandomPassword(12);
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $password_update = ", user_password_hash = ?, user_password_reset_hash = NULL, user_password_reset_timestamp = NULL";
        }
        
        // Update user details
        $update_sql = "UPDATE user SET 
                      user_name = ?, 
                      user_email = ?, 
                      user_active = ?";
        
        // Only update staff_position_type_id if it's a staff user
        if ($user['user_table'] === 'staff') {
            $update_sql .= ", staff_position_type_id = ?";
        } else {
            $update_sql .= ", staff_position_type_id = NULL";
        }
        
        $update_sql .= " $password_update WHERE user_id = ?";
                      
        if ($stmt = $con->prepare($update_sql)) {
            if ($reset_password) {
                if ($user['user_table'] === 'staff') {
                    $stmt->bind_param("sssiss", $username, $email, $is_active, $position_type, $password_hash, $user_id);
                } else {
                    $stmt->bind_param("ssssi", $username, $email, $is_active, $password_hash, $user_id);
                }
            } else {
                if ($user['user_table'] === 'staff') {
                    $stmt->bind_param("ssisi", $username, $email, $is_active, $position_type, $user_id);
                } else {
                    $stmt->bind_param("ssii", $username, $email, $is_active, $user_id);
                }
            }
            
            if ($stmt->execute()) {
                $success_message = 'User updated successfully!';
                
                // If password was reset, show the new password (only once)
                if ($reset_password) {
                    $success_message .= "<br>New temporary password: <strong>$new_password</strong><br>Please inform the user to change it after login.";
                }
                
                $message = '<div class="alert alert-success">' . $success_message . '</div>';
                // Refresh user data using a new statement variable
                $refresh_sql = "SELECT u.*, spt.staff_position_type_name as position_name 
                              FROM user u 
                              LEFT JOIN staff_position_type spt ON u.staff_position_type_id = spt.staff_position_type_id 
                              WHERE u.user_id = ?";
                if ($refresh_stmt = $con->prepare($refresh_sql)) {
                    $refresh_stmt->bind_param("i", $user_id);
                    $refresh_stmt->execute();
                    $result = $refresh_stmt->get_result();
                    $user = $result->fetch_assoc();
                    $refresh_stmt->close();
                }
            } else {
                $message = '<div class="alert alert-danger">Error updating user: ' . $con->error . '</div>';
            }
            $stmt->close();
        }
    }
}

// Fetch all position types for the dropdown
$position_types = [];
$sql = "SELECT * FROM staff_position_type ORDER BY staff_position_type_name";
$result = $con->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $position_types[] = $row;
    }
}
?>

<div class="container mt-4">
    <h1>Edit User</h1>
    
    <?php if ($message): ?>
        <?= $message ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($user['user_name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($user['user_email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                               <?= $user['user_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active Account</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="reset_password" name="reset_password">
                        <label class="form-check-label text-danger" for="reset_password">
                            <i class="fas fa-key"></i> Reset Password (generate new temporary password)
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Account Created</label>
                    <p class="form-control-static">
                        <?= date('F j, Y, g:i a', $user['user_creation_timestamp'] ?? time()) ?>
                    </p>
                </div>
                
                <div class="form-group">
                    <label>Last Login</label>
                    <p class="form-control-static">
                        <?= $user['user_last_login_timestamp'] ? date('F j, Y, g:i a', $user['user_last_login_timestamp']) : 'Never logged in' ?>
                    </p>
                </div>
                
                <?php if ($user['user_table'] === 'staff'): ?>
                <div class="form-group">
                    <label for="position_type">Position Type</label>
                    <select class="form-control" id="position_type" name="position_type">
                        <option value="">-- Select Position --</option>
                        <?php foreach ($position_types as $position): ?>
                            <option value="<?= $position['staff_position_type_id'] ?>"
                                <?= ($user['staff_position_type_id'] === $position['staff_position_type_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($position['staff_position_type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="User" class="btn btn-secondary">Back to Users</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once("footer.php"); ?>
