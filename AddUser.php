<?php
// Include config first (session is already started there)
include_once("config.php");

// Check if user is admin
if ($_SESSION['user_type'] != 'ADM') {
    header("Location: index.php");
    exit();
}

// Handle CSV template download first, before any output
if (isset($_GET['download_template'])) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="user_import_template.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['username', 'email', 'user_type', 'position_type']);
    
    // Add sample data rows
    fputcsv($output, ['johndoe', 'john@example.com', 'staff', 'ADM']);
    fputcsv($output, ['janedoe', 'jane@example.com', 'student', '']);
    
    // Add helpful comments
    fputcsv($output, ['', '', '', '']); // Empty line
    fputcsv($output, ['# INSTRUCTIONS:']);
    fputcsv($output, ['# 1. Fill in user details in the rows above']);
    fputcsv($output, ['# 2. For user_type, use exactly "staff" or "student" (without quotes)']);
    fputcsv($output, ['# 3. For staff, use position codes from the list below:']);
    fputcsv($output, ['#    (Leave position_type empty for students)']);
    
    // Add position types reference
    $position_sql = "SELECT * FROM staff_position_type ORDER BY staff_position_type_id";
    $position_result = $con->query($position_sql);
    if ($position_result) {
        fputcsv($output, ['#', 'Position Code', 'Position Name']);
        while ($pos = $position_result->fetch_assoc()) {
            fputcsv($output, ['#', $pos['staff_position_type_id'], $pos['staff_position_type_name']]);
        }
    }
    
    fclose($output);
    exit();
}

// Fetch position types for the dropdown
$position_types = [];
$position_sql = "SELECT staff_position_type_id, staff_position_type_name 
                FROM staff_position_type 
                ORDER BY staff_position_type_name";
$position_result = $con->query($position_sql);
if ($position_result) {
    while ($row = $position_result->fetch_assoc()) {
        $position_types[] = $row;
    }
}

// Now include other files and continue with normal page load
$title = "Add User | SLGTI";
include_once("head.php");
include_once("menu.php");

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

// Handle form submission for adding a single user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    // Get position type if user type is staff and position type is set, otherwise set to NULL
    $position_type = NULL;
    if ($user_type === 'staff') {
        if (isset($_POST['position_type']) && !empty($_POST['position_type'])) {
            $position_type = $_POST['position_type'];
        } else {
            $errors[] = "Position type is required for staff users";
        }
    }
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (!isValidEmail($email)) {
        $errors[] = "Valid email is required";
    }
    
    // Password validation
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if ($user_type === 'staff' && empty($position_type)) {
        $errors[] = "Position type is required for staff";
    }
    
    // Check if username already exists
    $check_sql = "SELECT user_id FROM user WHERE user_name = ?";
    if ($stmt = $con->prepare($check_sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username already exists";
        }
        $stmt->close();
    }
    
    // Check if email already exists
    $check_sql = "SELECT user_id FROM user WHERE user_email = ?";
    if ($stmt = $con->prepare($check_sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered";
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Debug: Log the values being inserted
        error_log("Inserting user - Username: $username, Email: $email, Type: $user_type, Position Type: " . ($position_type ?? 'NULL'));
        
        // Insert the new user
        $stmt = null;
        $success = false;
        
        if ($user_type === 'staff') {
            $sql = "INSERT INTO user (user_name, user_email, user_password_hash, user_table, staff_position_type_id, 
                    user_active, user_creation_timestamp) 
                    VALUES (?, ?, ?, ?, ?, 1, ?)";
                    
            if ($stmt = $con->prepare($sql)) {
                $timestamp = time();
                $stmt->bind_param("sssssi", $username, $email, $password_hash, $user_type, $position_type, $timestamp);
                $success = $stmt->execute();
            }
        } else {
            $sql = "INSERT INTO user (user_name, user_email, user_password_hash, user_table, 
                    user_active, user_creation_timestamp) 
                    VALUES (?, ?, ?, ?, 1, ?)";
                    
            if ($stmt = $con->prepare($sql)) {
                $timestamp = time();
                $stmt->bind_param("ssssi", $username, $email, $password_hash, $user_type, $timestamp);
                $success = $stmt->execute();
            }
        }
        
        if ($success) {
            $message = '<div class="alert alert-success">';
            $message .= "User '$username' has been created successfully with the provided password.";
            if ($user_type === 'staff') {
                $message .= "<br>Position Type ID: " . htmlspecialchars($position_type);
            }
            $message .= '</div>';
        } else {
            $message = '<div class="alert alert-danger">Error creating user: ' . ($stmt->error ?? 'Unknown error') . '</div>';
        }
        
        if ($stmt) {
            $stmt->close();
        }
    } else {
        $message = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    }
}

// Handle CSV import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle); // Skip header row
        
        $imported = 0;
        $errors = [];
        $row_num = 1; // Start from 1 to account for header
        
        while (($data = fgetcsv($handle)) !== false) {
            $row_num++;
            if (count($data) < 3) {
                $errors[] = "Row $row_num: Invalid number of columns";
                continue;
            }
            
            $username = trim($data[0]);
            $email = trim($data[1]);
            $user_type = strtolower(trim($data[2]));
            $position_type = ($user_type === 'staff' && isset($data[3])) ? trim($data[3]) : null;
            
            // Validate data
            if (empty($username) || empty($email) || empty($user_type)) {
                $errors[] = "Row $row_num: Missing required fields";
                continue;
            }
            
            if (!in_array($user_type, ['staff', 'student'])) {
                $errors[] = "Row $row_num: Invalid user type (must be 'staff' or 'student')";
                continue;
            }
            
            if (!isValidEmail($email)) {
                $errors[] = "Row $row_num: Invalid email format";
                continue;
            }
            
            // Check for existing username/email
            $check_sql = "SELECT user_id FROM user WHERE user_name = ? OR user_email = ?";
            if ($stmt = $con->prepare($check_sql)) {
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $errors[] = "Row $row_num: Username or email already exists";
                    $stmt->close();
                    continue;
                }
                $stmt->close();
            }
            
            // Insert user
            $password = generateRandomPassword(12);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO user (user_name, user_email, user_password_hash, user_table, staff_position_type_id, 
                    user_active, user_creation_timestamp) 
                    VALUES (?, ?, ?, ?, ?, 1, ?)";
            
            if ($stmt = $con->prepare($sql)) {
                $timestamp = time();
                $stmt->bind_param("sssssi", $username, $email, $password_hash, $user_type, $position_type, $timestamp);
                
                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors[] = "Row $row_num: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        fclose($handle);
        
        // Prepare result message
        if ($imported > 0) {
            $message = '<div class="alert alert-success">';
            $message .= "Successfully imported $imported users.<br>";
            $message .= 'Temporary passwords have been generated for all users.<br>';
            $message .= 'Please inform users to change their passwords after first login.';
            $message .= '</div>';
        }
        
        if (!empty($errors)) {
            $message .= '<div class="alert alert-warning">';
            $message .= '<strong>Some errors occurred during import:</strong><br>';
            $message .= implode('<br>', array_slice($errors, 0, 10)); // Show first 10 errors
            if (count($errors) > 10) {
                $message .= '<br>... and ' . (count($errors) - 10) . ' more errors';
            }
            $message .= '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Error uploading file. Please try again.</div>';
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
    <h1>Add New User</h1>
    
    <?php if ($message): ?>
        <?= $message ?>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Add Single User</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" 
                                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                                   title="Must contain at least one number, one uppercase and lowercase letter, and at least 8 or more characters"
                                   onkeyup='checkPasswordStrength();' required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Password must be at least 8 characters long and include uppercase, lowercase, and numbers.
                        </small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div id="password-strength" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small id="password-strength-text" class="form-text"></small>
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   onkeyup='checkPasswordMatch();' required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div id="password-match" class="mt-1"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="user_type">User Type</label>
                        <select class="form-control" id="user_type" name="user_type" required>
                            <option value="">-- Select User Type --</option>
                            <option value="staff">Staff</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-6" id="position_type_container" style="display: none;">
                        <label for="position_type">Position Type (for staff only) <span class="text-danger">*</span></label>
                        <select class="form-control" id="position_type" name="position_type" required>
                            <option value="">-- Select Position --</option>
                            <?php if (!empty($position_types)): ?>
                                <?php foreach ($position_types as $position): ?>
                                    <option value="<?= htmlspecialchars($position['staff_position_type_id']) ?>">
                                        <?= htmlspecialchars($position['staff_position_type_name']) ?> (<?= htmlspecialchars($position['staff_position_type_id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No position types found in database</option>
                            <?php endif; ?>
                        </select>
                        <small class="form-text text-muted">Required when user type is Staff</small>
                    </div>
                    
                    <div class="form-group col-md-6" id="position_code_container" style="display: none;">
                        <label for="position_code">Position Code (for staff only) <span class="text-danger">*</span></label>
                        <select class="form-control" id="position_code" name="position_code" required>
                            <option value="">-- Select Position Code --</option>
                            <?php if (!empty($position_types)): ?>
                                <?php foreach ($position_types as $position): ?>
                                    <option value="<?= htmlspecialchars($position['staff_position_type_id']) ?>">
                                        <?= htmlspecialchars($position['staff_position_type_id']) ?> - <?= htmlspecialchars($position['staff_position_type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No position codes found in database</option>
                            <?php endif; ?>
                        </select>
                        <small class="form-text text-muted">Select the position code for this staff member</small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Import Users from CSV</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>CSV Format:</strong>
                <ul class="mb-0">
                    <li>File must be in CSV format (comma-separated values)</li>
                    <li>First row should be header (it will be skipped)</li>
                    <li>Required columns in order: username, email, user_type (staff/student), position_type (for staff only)</li>
                    <li>Example:<br>
                        <code>username,email,user_type,position_type<br>
                        johndoe,john@example.com,staff,ADM<br>
                        janedoe,jane@example.com,student,</code>
                    </li>
                    <li class="mt-2">
                        <a href="?download_template=1" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-file-download"></i> Download CSV Template
                        </a>
                    </li>
                </ul>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="csv_file" name="csv_file" accept=".csv" required>
                        <label class="custom-file-label" for="csv_file">Choose CSV file</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-file-import"></i> Import Users
                </button>
                <a href="User" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Password strength checker
function checkPasswordStrength() {
    var password = document.getElementById('password').value;
    var strength = 0;
    var text = '';
    var progress = document.getElementById('password-strength');
    var textElement = document.getElementById('password-strength-text');
    
    // Check password length
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 15;
    
    // Check for numbers
    if (password.match(/([0-9])/)) strength += 20;
    
    // Check for special chars
    if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 20;
    
    // Check for uppercase and lowercase
    if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 20;
    
    // Update progress bar
    progress.style.width = strength + '%';
    
    // Update text and color
    if (strength < 40) {
        progress.className = 'progress-bar bg-danger';
        text = 'Weak';
    } else if (strength < 70) {
        progress.className = 'progress-bar bg-warning';
        text = 'Moderate';
    } else {
        progress.className = 'progress-bar bg-success';
        text = 'Strong';
    }
    
    textElement.textContent = text;
}

// Check if passwords match
function checkPasswordMatch() {
    var password = document.getElementById('password').value;
    var confirmPassword = document.getElementById('confirm_password').value;
    var message = document.getElementById('password-match');
    
    if (password === '' || confirmPassword === '') {
        message.innerHTML = '';
        return;
    }
    
    if (password === confirmPassword) {
        message.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Passwords match</span>';
    } else {
        message.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Passwords do not match</span>';
    }
}

// Toggle password visibility
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle buttons
    document.getElementById('togglePassword').addEventListener('click', function() {
        togglePasswordVisibility('password', 'togglePassword');
    });
    
    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
        togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

function togglePasswordVisibility(fieldId, buttonId) {
    var field = document.getElementById(fieldId);
    var button = document.getElementById(buttonId);
    var icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Show/hide position type based on user type
$(document).ready(function() {
    // Toggle position type field based on user type
    function togglePositionType(userType) {
        if (userType === 'staff') {
            $('#position_type_container').show();
            $('#position_type').prop('required', true);
            $('#position_code_container').show();
            $('#position_code').prop('required', true);
        } else {
            $('#position_type_container').hide();
            $('#position_type').prop('required', false);
            $('#position_code_container').hide();
            $('#position_code').prop('required', false);
        }
    }
    
    $('#user_type').change(function() {
        togglePositionType($(this).val());
    }).trigger('change'); // Trigger on page load to set initial state
    
    // Handle change event
    $('#user_type').on('change', function() {
        togglePositionType($(this).val());
    });
    
    // Show filename in custom file input
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass('selected').html(fileName);
    });
    
    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        togglePasswordVisibility('password', 'togglePassword');
    });
    
    $('#toggleConfirmPassword').on('click', function() {
        togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
    });
});
</script>

<?php include_once("footer.php"); ?>
