<?php
session_start();
date_default_timezone_set('Asia/Colombo');
//database connection
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','1234');
define('DB_NAME','mis');

//cookie
define('COOKIE_RUNTIME', 1209600); // 1209600 seconds = 2 weeks
define('COOKIE_DOMAIN','localhost'); // the domain where the cookie is valid for, like '.mydomain.com'
define('COOKIE_SECRET_KEY', '1Wp@TMPS{+$78sppMJFe-92s'); // use to salt cookie content and when changed, can invalidate all databases users cookies
  


$con=mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
if (mysqli_connect_errno()){
      //echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }else{
    //echo "Connected successfully";
  }



// Email Configuration
define("EMAIL_USE_SMTP", true);
// Using Gmail SMTP server
define("EMAIL_SMTP_HOST", 'smtp.gmail.com');
define("EMAIL_SMTP_AUTH", true);
// IMPORTANT: Use a Gmail account with 2-step verification and App Password
// 1. Go to: https://myaccount.google.com/security
// 2. Enable 2-Step Verification if not already enabled
// 3. Go to: https://myaccount.google.com/apppasswords
// Email credentials should be stored securely in environment variables or a secrets manager
define("EMAIL_SMTP_USERNAME", getenv('ict@slgti.com')); 
define("EMAIL_SMTP_PASSWORD", getenv('TCI@itgls2025')); 
define("EMAIL_SMTP_PORT", 587);
define("EMAIL_SMTP_ENCRYPTION", 'tls');
// Enable verbose debug output for email
define("EMAIL_DEBUG_MODE", 2); // 0 = off, 1 = client messages, 2 = client and server messages
/**
 * Configuration file for: password reset email data
 * This is the place where your constants are saved
 * absolute URL to register.php, necessary for email password reset links 
* */

define("EMAIL_PASSWORDRESET_URL", "https://".COOKIE_DOMAIN."/passwordrecovery");
define("EMAIL_PASSWORDRESET_FROM", "ict@slgti.com"); // Must match SMTP username
define("EMAIL_PASSWORDRESET_FROM_NAME", "TCI@itgls2025");
define("EMAIL_PASSWORDRESET_SUBJECT", "[MIS@SLGT] Password Reset");
define("EMAIL_PASSWORDRESET_CONTENT", "Please click on this link to reset your password: ");
/**
 * Configuration file for: verification email data
 * This is the place where your constants are saved
 * absolute URL to register.php, necessary for email verification links 
 * */
define("EMAIL_VERIFICATION_URL", "https://".COOKIE_DOMAIN."/signup");
define("EMAIL_VERIFICATION_FROM", "ict@slgti.com"); // Must match SMTP username
define("EMAIL_VERIFICATION_FROM_NAME", "TCI@itgls2025");
define("EMAIL_VERIFICATION_SUBJECT", "[MIS@SLGT] Account Activation I");
define("EMAIL_VERIFICATION_CONTENT", "Please click on this link to activate your account:");



?>




