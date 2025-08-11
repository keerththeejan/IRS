<!-- BLOCK#1 START DON'T CHANGE THE ORDER-->
<?php
$title = "Create your MIS @ SLGTI Account ";
include_once("config.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;         
require './library/PHPMailer/autoload.php';
$msg = $msgs = null;

if (isset($_POST['SignUp']) && !empty($_POST['username'])) {
    $user_name = trim(htmlspecialchars($_POST['username']));
    
    // First check if username exists in pre-created users
    try {
        // Debug: Check database connection
        if (!$con) {
            throw new Exception("Database connection failed: " . mysqli_connect_error());
        }
        
        // Debug: Check if table exists
        $table_check = $con->query("SHOW TABLES LIKE 'precreated_users'");
        if ($table_check->num_rows == 0) {
            throw new Exception("Table 'precreated_users' does not exist in the database.");
        }
        
        $precreated_sql = "SELECT * FROM precreated_users WHERE username = ? LIMIT 1";
        $stmt = $con->prepare($precreated_sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $con->error);
        }
        
        $stmt->bind_param("s", $user_name);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $precreated_user = $result->fetch_assoc();
        
        if (!$precreated_user) {
            throw new Exception('Username not found in the pre-created users list. Please contact the administrator.');
        } elseif ($precreated_user['is_used'] == 1) {
            throw new Exception('This username has already been used. Please contact the administrator if you believe this is an error.');
        }
        
        // If we get here, user exists and is not used - proceed with signup
        $precreated_user_found = true;
        
    } catch (Exception $e) {
        $msg = $e->getMessage();
        $precreated_user_found = false;
    }
    
    if ($precreated_user_found) {
        // Check if user already exists in main user table
        $check_sql = "SELECT user_id, user_email, user_active, user_password_hash, user_table, staff_position_type_id 
                     FROM user 
                     WHERE user_name = ? 
                     LIMIT 1";
        $stmt = $con->prepare($check_sql);
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            // User already exists in main table
            $row = $result->fetch_assoc();
            $user_email = $row['user_email'];
            $user_active = $row['user_active'];
            
            if($user_active == 1) {
                $msg = 'This account is already active. Please <a href="signin.php">sign in</a> instead.';
            } else {
                $msg = 'An error occurred. Please contact the administrator.';
            }
        } else {
            // Generate a strong random password
            $new_password = generateRandomPassword(12);
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $email = !empty($precreated_user['email']) ? $precreated_user['email'] : $user_name . "@slgti.lk";
            $user_type = 'user'; // Default user type
            $is_admin = false;
            
            // Start transaction
            $con->begin_transaction();
            
            try {
                // Insert new user record
                $insert_sql = "INSERT INTO user (user_name, user_email, user_password_hash, user_active, user_creation_timestamp) 
                             VALUES (?, ?, ?, 1, UNIX_TIMESTAMP())";
                $stmt = $con->prepare($insert_sql);
                $stmt->bind_param("sss", $user_name, $email, $password_hash);
                $insert_result = $stmt->execute();
                
                if ($insert_result) {
                    // Mark pre-created user as used
                    $update_sql = "UPDATE precreated_users SET is_used = 1, used_at = NOW() WHERE id = ?";
                    $stmt = $con->prepare($update_sql);
                    $stmt->bind_param("i", $precreated_user['id']);
                    $update_result = $stmt->execute();
                    
                    if ($update_result) {
                        $con->commit();
                        
                        // Prepare success message with credentials
                        $msgs = '<div class="alert alert-success">';
                        $msgs .= '<h4>Account Activated Successfully!</h4>';
                        $msgs .= '<p>Your account has been activated with the following credentials:</p>';
                        $msgs .= '<table class="table table-bordered">';
                        $msgs .= '<tr><th>Username:</th><td>' . htmlspecialchars($user_name) . '</td></tr>';
                        $msgs .= '<tr><th>Password:</th><td><strong>' . htmlspecialchars($new_password) . '</strong></td></tr>';
                        $msgs .= '<tr><th>Account Type:</th><td>' . ($is_admin ? 'Administrator' : ucfirst($user_type)) . '</td></tr>';
                        $msgs .= '</table>';
                        $msgs .= '<div class="alert alert-warning">';
                        $msgs .= '<strong>Important:</strong> Please save this password in a secure location. ';
                        $msgs .= 'For security reasons, this password cannot be recovered if lost.';
                        $msgs .= '</div>';
                        $msgs .= '<a href="signin.php" class="btn btn-primary">Proceed to Sign In</a>';
                        $msgs .= '</div>';

                        // Send welcome email with credentials
                        try {
                            $mail = new PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = EMAIL_SMTP_HOST;
                            $mail->Port = 587;
                            $mail->SMTPAuth = true;
                            $mail->Username = EMAIL_SMTP_USERNAME;
                            $mail->Password = EMAIL_SMTP_PASSWORD;
                            $mail->setFrom(EMAIL_VERIFICATION_FROM, EMAIL_VERIFICATION_FROM_NAME);
                            $mail->addReplyTo(EMAIL_VERIFICATION_FROM, EMAIL_VERIFICATION_FROM_NAME);
                            $mail->addAddress($email);
                            $mail->Subject = 'Your MIS @ SLGTI Account Has Been Activated';
                            
                            $html_message = "
                                <p>Dear " . htmlspecialchars($user_name) . ",</p>
                                <p>Your MIS @ SLGTI account has been successfully activated.</p>
                                <p><strong>Login Details:</strong></p>
                                <p>Username: " . htmlspecialchars($user_name) . "<br>
                                Password: " . htmlspecialchars($new_password) . "</p>
                                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0;'>
                                    <strong>Important:</strong> Please save this password in a secure location. 
                                    For security reasons, this password cannot be recovered if lost.
                                </div>
                                <p>You can now <a href='signin.php'>sign in</a> to your account.</p>
                                <p>Best Regards,<br>
                                The ICT Support Team<br>
                                <small>Technical Support Contact: achchuthan@slgti.com</small></p>
                            ";
                            
                            $mail->msgHTML($html_message, __DIR__);
                            if (!$mail->send()) {
                                // If email fails, add a note to the success message
                                $msgs = str_replace(
                                    '</div>', 
                                    '<div class="alert alert-warning mt-3">' .
                                    '<strong>Note:</strong> We were unable to send the confirmation email, but your account has been activated.' .
                                    '</div></div>', 
                                    $msgs
                                );
                            }
                        } catch (Exception $e) {
                            // Log the error but don't show it to the user
                            error_log("Email sending failed: " . $e->getMessage());
                        }
                    } else {
                        throw new Exception("Failed to update pre-created user status");
                    }
                } else {
                    throw new Exception("Failed to create user account");
                }
            } catch (Exception $e) {
                $con->rollback();
                $msg = 'Error activating your account. Please try again or contact support.';
                error_log("Account activation failed for user: " . $user_name . ". Error: " . $e->getMessage());
            }
        }
    }
}
?>
<?php
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

if (isset($_POST['ChangePassword']) && !empty($_POST['password'])&& !empty($_POST['repeatpassword'])) {
    $password = htmlspecialchars($_POST['password']);
    $repeatpassword = htmlspecialchars($_POST['repeatpassword']);
    $user_name = htmlspecialchars($_GET['un']);
    $user_activation_hash = htmlspecialchars($_GET['vc']);
    if($password==$repeatpassword){
        if(strlen($password)>=8){
            $password_hash = hash('sha256', $password);
            $sql_r = "UPDATE user  SET user_password_hash = '$password_hash', user_active = 1,
            user_activation_hash = NULL, user_password_reset_timestamp = NULL
            WHERE user_name = '$user_name' AND user_activation_hash = '$user_activation_hash'";
             if(mysqli_query($con,$sql_r)){
                $msgs = 'User Activated';
             }else{
                $msg = 'User Activation failed';
             }
             
        }else{
            $msg = 'Password too short';
        }
    }else{
        $msg = 'Bad confirm password';
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/signin.css">
    <link href="css/all.min.css" rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">
    <link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
    <title><?php echo $title; ?></title>
</head>

<body>

    <div class="container-fluid">
        <div class="row no-gutter">
            <!-- The image half -->
            <div class="col-md-6 d-none d-md-flex bg-image"></div>


            <!-- The content half -->
            <div class="col-md-6 bg-light">
                <div class="login d-flex align-items-center py-5">

                    <!-- Demo content-->
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-10 col-xl-7 mx-auto">
                                <h3 class="display-4 text-center">MIS@SLGTI</h3>
                                <p class="text-muted text-center mb-4 blockquote-footer">Management Information System
                                </p>
                                <form method="post" action="">
                                    <?php
                                    if (!empty($msg)) {
                                        echo '<div class="alert alert-danger rounded-pill border-0 shadow-sm px-4">' . $msg . '</div>';
                                        error_log("Signup Error: " . $msg);
                                    }
                                    
                                    if (!empty($msgs)) {
                                        echo '<div class="alert alert-success rounded-pill border-0 shadow-sm px-4">' . $msgs . '</div>';
                                    }
                                    ?>
                                    <?php 
                                    if(!isset($_GET['un']) && !isset($_GET['vc'])){
                                    ?>
                                    <div class="form-group mb-3">
                                        <input id="inputEmail" type="text" name="username" placeholder="Username" required=""
                                            autofocus="" class="form-control rounded-pill border-0 shadow-sm px-4">
                                    </div>
                                    
                                    <button type="submit" name="SignUp"
                                        class="btn btn-primary btn-block text-uppercase mb-2 rounded-pill shadow-sm">Sign
                                        up</button>
                                    <?php
                                    }
                                    ?>
                                    <?php 
                                    if(isset($_GET['un']) && isset($_GET['vc'])){
                                    ?>
                                    <!-- reset password? -->
                                    <div class="form-group mb-3">
                                        <input id="inputpassword" type="password" name="password" placeholder="New password" required=""
                                            autofocus="" class="form-control rounded-pill border-0 shadow-sm px-4" onkeyup="checkPass(); return false;">
                                    </div>

                                    <div class="form-group mb-3">
                                        <input id="inputrepeatpassword" type="password" name="repeatpassword" placeholder="Re-enter new password" required=""
                                            autofocus="" class="form-control rounded-pill border-0 shadow-sm px-4" onkeyup="checkPass(); return false;" >
                                    </div> 

                                    <div class="d-flex mt-4">
                                    <p class="text-center" id="error-nwl"></p>
                                    </div>
                                    <button type="submit" name="ChangePassword" id="ChangePassword"
                                        class="btn btn-primary btn-block text-uppercase mb-2 rounded-pill shadow-sm">Activate  Account</button>
                                    
                                    <!-- reset password? -->

                                    <?php
                                    }
                                    ?>
                                    <div class="form-group mb-3 text-center">
                                    <a href="passwordrecovery" class="font-italic text-muted pr-1">Forgot password?</a>
                                   
                                    <a href="signin"class="font-italic text-muted text-right">Sign in instead</a>
                                    </div>
                                    <div class="text-center d-flex justify-content-between mt-4">
                                        <p>All Rights Reserved. Designed and Developed by Department of Information and
                                            Communication Technology, <a href="http://slgti.com"
                                                class="font-italic text-muted">
                                                Sri Lanka-German Training Institute.</a></p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div><!-- End -->

                </div>
            </div><!-- End -->

        </div>
    </div>
    <script>
    document.getElementById("ChangePassword").disabled = true;
    function checkPass(){
    var pass1 = document.getElementById('inputpassword');
    var pass2 = document.getElementById('inputrepeatpassword');
    var message = document.getElementById('error-nwl');
    var goodColor = "rgb(147, 255, 171)";
    var badColor = "rgb(255, 201, 206)";
 	
    if(pass1.value.length >= 8)
    {
        pass1.style.backgroundColor = goodColor;
        message.style.color = goodColor;
        message.innerHTML ="";
    }
    else
    {
        pass1.style.backgroundColor = badColor;
        message.style.color = badColor;
        message.innerHTML = " You have to enter at least 8 digit!"
        return;
    }
  
    if(pass1.value == pass2.value)
    {
        pass2.style.backgroundColor = goodColor;
        message.style.color = goodColor;
        document.getElementById("ChangePassword").disabled = false;
        message.innerHTML ="";
    }
	else
    {
        pass2.style.backgroundColor = badColor;
        message.style.color = badColor;
        message.innerHTML = " These passwords don't match"
    }
}  
</script>
</body>

</html>