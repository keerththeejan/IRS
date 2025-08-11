<?php
$title = "User Sign Up | SLGTI";
include_once("config.php");
include_once("head.php");
include_once("menu.php");

$message = '';

// Function to check if username exists in pre-created users
function isPrecreatedUser($username, $con) {
    $sql = "SELECT * FROM precreated_users WHERE username = ? AND is_used = 0 LIMIT 1";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username'])) {
    $username = trim($_POST['username']);
    $email = !empty(trim($_POST['email'])) ? trim($_POST['email']) : null;
    
    // Check if username is pre-created and not used
    $precreatedUser = isPrecreatedUser($username, $con);
    
    if (!$precreatedUser) {
        $message = '<div class="alert alert-warning">Invalid username or this username is not eligible for registration. Please contact the administrator.</div>';
    } else {
        // Use pre-created user's email if available and no email was provided
        if (empty($email) && !empty($precreatedUser['email'])) {
            $email = $precreatedUser['email'];
            $isDefaultEmail = false;
        } elseif (empty($email)) {
            $email = $username . "@slgti.lk";
            $isDefaultEmail = true;
        }
        
        // Generate a random password
        $password = generateRandomPassword(10);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if username already exists in main user table
        $check_sql = "SELECT user_id FROM user WHERE user_name = ?";
        $stmt = $con->prepare($check_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = '<div class="alert alert-warning">This username has already been registered. Please sign in instead.</div>';
        } else {
            // Start transaction
            $con->begin_transaction();
            
            try {
                // Insert into main user table
                $sql = "INSERT INTO user (user_name, user_email, user_password_hash, user_active, user_creation_timestamp) 
                        VALUES (?, ?, ?, 1, UNIX_TIMESTAMP())";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("sss", $username, $email, $password_hash);
                
                if ($stmt->execute()) {
                    // Mark pre-created user as used
                    $update_sql = "UPDATE precreated_users SET is_used = 1, used_at = NOW() WHERE username = ?";
                    $update_stmt = $con->prepare($update_sql);
                    $update_stmt->bind_param("s", $username);
                    
                    if ($update_stmt->execute()) {
                        $con->commit();
                        
                        // Prepare success message
                        $message = '<div class="alert alert-success">
                            <h5>Account Created Successfully!</h5>
                            <div class="alert alert-info">
                                <p class="mb-1"><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
                                <p class="mb-1"><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>
                                <p class="mb-0"><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
                            </div>
                            <div class="alert alert-warning">
                                <p class="mb-0">Please save this information. The password cannot be recovered.</p>
                            </div>
                        </div>';
                    } else {
                        throw new Exception("Error updating pre-created user status");
                    }
                } else {
                    throw new Exception("Error creating user account");
                }
            } catch (Exception $e) {
                $con->rollback();
                $message = '<div class="alert alert-danger">Error creating account: ' . $e->getMessage() . '</div>';
                error_log("User registration error: " . $e->getMessage());
            }
        }
    }
}

// Function to generate random password
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    
    return $password;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Quick User Sign Up</h4>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group row">
                            <label for="username" class="col-md-3 col-form-label">Username</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Enter username" required>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="email" class="col-md-3 col-form-label">Email</label>
                            <div class="col-md-9">
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter email address (optional)">
                            </div>
                        </div>
                        
                        <div class="form-group row mb-0">
                            <div class="col-md-9 offset-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                                <a href="User" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Users
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer bg-light">
                    <div class="alert alert-info mb-0">
                        <h6><i class="fas fa-info-circle"></i> Note:</h6>
                        <ul class="mb-0 pl-3">
                            <li>A random password will be generated automatically.</li>
                            <li>Users should change their password after first login.</li>
                            <li>Email is optional but must be unique if provided.</li>
                            <li>If provided, please enter a valid email address.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once("footer.php"); ?>
