<?php
/**
 * Optimized Index Page for SLGTI MIS
 * 
 * This version includes:
 * - Output buffering with GZIP compression
 * - Optimized database queries
 * - Prepared statements for security
 * - Better error handling
 * - Caching of survey data
 */

// Start output buffering with GZIP compression
if (!ob_start("ob_gzhandler")) {
    ob_start();
}

// Include optimized configuration
require_once __DIR__ . '/config/config.optimized.php';

// Set page title
$title = "Home | SLGTI";

// Include optimized header
include_once("includes/optimized_header.php");

// Process student notifications
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'STU') {
    $student_id = $_SESSION['user_name'] ?? '';
    
    // Use the optimized view we created
    $sql = "SELECT * FROM student_surveys WHERE student_id = ?";
    
    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($survey = $result->fetch_assoc()) {
            echo sprintf('
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>New Notification! <span style="font-size:20px;">&#129335;</span></strong> 
                New Survey Added For <strong>%s</strong>&nbsp;Please Give Your Feedback&nbsp;
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <a href="Addfbdetail.php?id=%s" class="btn btn-sm btn-warning float-right mr-5">
                    <i class="fas fa-eye"></i>
                </a> 
            </div>',
                htmlspecialchars($survey['module_name'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($survey['survey_id'], ENT_QUOTES, 'UTF-8')
            );
        }
        $stmt->close();
    }
}
?>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Welcome to SLGTI MIS</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Quick Stats -->
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h6 class="card-title">Enrolled Modules</h6>
                                    <h2 class="mb-0">
                                        <?php
                                        if (isset($_SESSION['user_name'])) {
                                            $user_id = $_SESSION['user_name'];
                                            $sql = "SELECT COUNT(*) as count FROM student_enroll WHERE student_id = ? AND student_enroll_status = 'Following'";
                                            if ($stmt = $con->prepare($sql)) {
                                                $stmt->bind_param("s", $user_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $row = $result->fetch_assoc();
                                                echo (int)$row['count'];
                                                $stmt->close();
                                            }
                                        } else {
                                            echo '0';
                                        }
                                        ?>
                                    </h2>
                                </div>
                            </div>
                        </div>

                        <!-- Add more quick stats here -->
                        
                        <!-- Recent Activity -->
                        <div class="col-12 mt-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Activity</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Example of a recent activity feed
                                    if (isset($_SESSION['user_name'])) {
                                        $user_id = $_SESSION['user_name'];
                                        $sql = "SELECT * FROM activity_log WHERE user_id = ? ORDER BY activity_date DESC LIMIT 5";
                                        if ($stmt = $con->prepare($sql)) {
                                            $stmt->bind_param("s", $user_id);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            if ($result->num_rows > 0) {
                                                echo '<ul class="list-group">';
                                                while ($row = $result->fetch_assoc()) {
                                                    echo sprintf(
                                                        '<li class="list-group-item">%s <small class="text-muted">%s</small></li>',
                                                        htmlspecialchars($row['activity_description'], ENT_QUOTES, 'UTF-8'),
                                                        date('M j, Y g:i A', strtotime($row['activity_date']))
                                                    );
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo '<p class="text-muted">No recent activity to display.</p>';
                                            }
                                            $stmt->close();
                                        }
                                    } else {
                                        echo '<p class="text-muted">Please log in to view your activity.</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include optimized footer
include_once("includes/optimized_footer.php");

// Flush output buffer
ob_end_flush();
?>
