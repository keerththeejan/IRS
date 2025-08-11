<?php
/**
 * Performance Test Script
 * 
 * This script tests the performance improvements made to the IRS system.
 * It measures execution time and memory usage before and after optimizations.
 */

// Start timing
echo "<pre>\n=== IRS Performance Test ===\n\n";
$start_time = microtime(true);
$start_memory = memory_get_usage();

// Include the optimized configuration
require_once __DIR__ . '/config/config.optimized.php';

// Test 1: Database Connection
$db_test_start = microtime(true);
$result = $con->query("SELECT 'Database connection test' AS test");
$db_test_time = (microtime(true) - $db_test_start) * 1000; // in ms
$row = $result->fetch_assoc();

echo "[✓] Database Connection: " . number_format($db_test_time, 2) . " ms\n";

// Test 2: Query Performance
$query_test_start = microtime(true);
$query = $con->prepare("SELECT COUNT(*) as count FROM student_enroll");
$query->execute();
$result = $query->get_result();
$row = $result->fetch_assoc();
$query_test_time = (microtime(true) - $query_test_start) * 1000; // in ms

echo "[✓] Simple Query: " . number_format($query_test_time, 2) . " ms\n";

// Test 3: Complex Query (using our optimized view)
$complex_test_start = microtime(true);
$query = $con->prepare("SELECT COUNT(*) as count FROM student_surveys");
$query->execute();
$result = $query->get_result();
$row = $result->fetch_assoc();
$complex_test_time = (microtime(true) - $complex_test_start) * 1000; // in ms

echo "[✓] Complex Query (View): " . number_format($complex_test_time, 2) . " ms\n";

// Test 4: Session Handling
$session_test_start = microtime(true);
session_start();
$_SESSION['test'] = 'performance_test';
session_write_close();
$session_test_time = (microtime(true) - $session_test_start) * 1000; // in ms

echo "[✓] Session Handling: " . number_format($session_test_time, 2) . " ms\n";

// Memory usage
$end_memory = memory_get_usage();
$memory_used = ($end_memory - $start_memory) / 1024; // in KB

// Total execution time
$total_time = (microtime(true) - $start_time) * 1000; // in ms

// Output results
echo "\n=== Test Results ===\n";
echo "Total Execution Time: " . number_format($total_time, 2) . " ms\n";
echo "Memory Used: " . number_format($memory_used, 2) . " KB\n";

// Database version and stats
$result = $con->query("SHOW VARIABLES LIKE 'version%'");
echo "\n=== Database Info ===\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Variable_name'] . ": " . $row['Value'] . "\n";
}

// PHP Info
echo "\n=== PHP Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";

// Check if GZIP is working
echo "\n=== GZIP Compression ===\n";
if (function_exists('ob_gzhandler')) {
    echo "[✓] GZIP Compression: Enabled\n";
} else {
    echo "[!] GZIP Compression: Not enabled\n";
}

// Check if mod_deflate is loaded
echo "\n=== Server Modules ===\n";
$modules = [
    'mod_deflate',
    'mod_expires',
    'mod_headers',
    'mod_rewrite'
];

foreach ($modules as $module) {
    if (in_array($module, apache_get_modules())) {
        echo "[✓] $module: Loaded\n";
    } else {
        echo "[!] $module: Not loaded\n";
    }
}

// Recommendations
echo "\n=== Recommendations ===\n";

// Check for OPcache
if (function_exists('opcache_get_status') && opcache_get_status()) {
    echo "[✓] OPcache is enabled\n";
} else {
    echo "[!] Consider enabling OPcache for better PHP performance\n";
    echo "    Add to php.ini: opcache.enable=1\n";
}

// Check for HTTP/2
if (isset($_SERVER['SERVER_PROTOCOL']) && strpos($_SERVER['SERVER_PROTOCOL'], 'HTTP/2') !== false) {
    echo "[✓] HTTP/2 is enabled\n";
} else {
    echo "[!] Consider enabling HTTP/2 for better performance\n";
}

// Check for HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    echo "[✓] HTTPS is enabled\n";
} else {
    echo "[!] Consider enabling HTTPS for security and performance (HTTP/2 requires HTTPS)\n";
}

echo "\n=== Test Complete ===\n";
echo "Run this test after making changes to measure performance improvements.\n";
echo "</pre>";

// Close database connection
$con->close();
