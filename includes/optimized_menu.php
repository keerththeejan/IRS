<?php
/**
 * Optimized Menu System
 * 
 * This file provides a more efficient menu system with:
 * - Prepared statements for security
 * - Caching of user data
 * - Optimized database queries
 * - Better error handling
 */

// Start timing for performance measurement
$start_time = microtime(true);

// Initialize session variables with default values
$u_n = $_SESSION['user_name'] ?? 'Guest';
$u_ta = $_SESSION['user_table'] ?? '';
$u_t = $_SESSION['user_type'] ?? '';
$d_c = $_SESSION['department_code'] ?? 'N/A';
$username = $u_n; // Default to user ID if name not found

// Only query database if we have a valid user table and name
if (in_array($u_ta, ['staff', 'student'], true)) {
    $cache_key = "user_{$u_ta}_{$u_n}";
    
    // Try to get from cache first
    if (function_exists('apcu_fetch')) {
        $user_data = apcu_fetch($cache_key);
        if ($user_data !== false) {
            $username = $user_data['name'];
        }
    }
    
    // If not in cache, query the database
    if (!isset($user_data)) {
        $field = $u_ta === 'staff' ? 'staff_name' : 'student_fullname';
        $sql = "SELECT `$field` as name FROM `$u_ta` WHERE `" . ($u_ta === 'staff' ? 'staff_id' : 'student_id') . "` = ? LIMIT 1";
        
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param('s', $u_n);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $username = $row['name'];
                // Cache the result for 1 hour
                if (function_exists('apcu_store')) {
                    apcu_store($cache_key, ['name' => $username], 3600);
                }
            }
            $stmt->close();
        }
    }
}

// Log menu generation time if debugging is enabled
if (defined('DEBUG') && DEBUG === true) {
    error_log(sprintf(
        'Menu generated in %.4f seconds for user: %s',
        microtime(true) - $start_time,
        $u_n
    ));
}
?>

<nav id="sidebar" class="sidebar-wrapper">
    <div class="sidebar-content">
        <div class="sidebar-brand">
            <a href="<?php echo SITE_URL; ?>">MIS@SLGTI</a>
            <div id="close-sidebar">
                <i class="fas fa-times"></i>
            </div>
        </div>
        <div class="sidebar-header">
            <div class="user-pic">
                <img class="img-responsive img-rounded" 
                     src="<?php echo ASSETS_URL; ?>/img/user.jpg" 
                     alt="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                     loading="lazy"
                     width="40" height="40">
            </div>
            <div class="user-info">
                <span class="user-name">
                    <strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong>
                </span>
                <span class="user-role">
                    <?php echo htmlspecialchars($u_t, ENT_QUOTES, 'UTF-8'); ?> | 
                    <?php echo htmlspecialchars($d_c, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <span class="user-status">
                    <i class="fa fa-user"></i>
                    <span>
                        <a href="<?php echo $u_t === 'STU' ? 'Student_profile' : 'Profile'; ?>">
                            Profile
                        </a>
                    </span>
                </span>
            </div>
        </div>
        <!-- Rest of your menu items would go here -->
    </div>
</nav>
