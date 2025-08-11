<?php
// Enable error reporting for development
// In production, set display_errors to 0 and log_errors to 1
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.gc_maxlifetime', 14400); // 4 hours
ini_set('session.cookie_lifetime', 0); // Until browser closes

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '1234');
define('DB_NAME', 'mis');

// Connection with error handling
try {
    $con = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($con->connect_error) {
        throw new Exception("Connection failed: " . $con->connect_error);
    }
    $con->set_charset("utf8mb4");
    
    // Set timezone
    $con->query("SET time_zone = '+05:30'");
    
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Connection error. Please try again later.");
}

// Application configuration
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('ASSETS_URL', SITE_URL . '/assets');

// Enable output buffering with GZIP compression
if (!ob_start("ob_gzhandler")) {
    ob_start();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('Asia/Colombo');

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com; style-src \'self\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;');

// Performance headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('X-Content-Type-Options: nosniff');

// Disable PHP version disclosure
header_remove('X-Powered-By');
