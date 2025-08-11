<?php
// Include config first (session is already started there)
include_once("config.php");

// Check if user is admin
if ($_SESSION['user_type'] != 'ADM') {
    header("Location: index.php");
    exit();
}

$title = "Pending User Registrations | SLGTI";
include_once("head.php");
include_once("menu.php");

$message = '';

// Handle user activation
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'activate') {
        $sql = "UPDATE user SET user_active = 1 WHERE user_id = ?";
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">User activated successfully.</div>';
            } else {
                $message = '<div class="alert alert-danger">Error activating user: ' . $con->error . '</div>';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM user WHERE user_id = ?";
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">User deleted successfully.</div>';
            } else {
                $message = '<div class="alert alert-danger">Error deleting user: ' . $con->error . '</div>';
            }
            $stmt->close();
        }
    }
}

// Fetch users who haven't completed signup (no last login and account not active)
$sql = "SELECT u.*, spt.staff_position_type_name as position_name 
        FROM user u 
        LEFT JOIN staff_position_type spt ON u.staff_position_type_id = spt.staff_position_type_id 
        WHERE (u.user_last_login_timestamp IS NULL OR u.user_last_login_timestamp = 0) 
        AND u.user_active = 0
        ORDER BY u.user_creation_timestamp DESC";
$result = $con->query($sql);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Pending User Registrations</h1>
        <a href="User" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to User Management
        </a>
    </div>
    
    <?php if ($message): ?>
        <?= $message ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>User Type</th>
                                <th>Position</th>
                                <th>Created On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['user_id']) ?></td>
                                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                                    <td><?= htmlspecialchars($row['user_email']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($row['user_table'])) ?></td>
                                    <td><?= !empty($row['position_name']) ? htmlspecialchars($row['position_name']) : 'N/A' ?></td>
                                    <td><?= date('Y-m-d H:i', $row['user_creation_timestamp']) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=activate&id=<?= $row['user_id'] ?>" 
                                               class="btn btn-sm btn-success"
                                               onclick="return confirm('Are you sure you want to activate this user?')">
                                                <i class="fas fa-check"></i> Activate
                                            </a>
                                            <a href="?action=delete&id=<?= $row['user_id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No pending user registrations found.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Help</h5>
        </div>
        <div class="card-body">
            <p>This page shows users who have been added to the system but haven't completed their registration process.</p>
            <ul>
                <li><strong>Activate</strong>: Activate the user account. The user will be able to log in with their credentials.</li>
                <li><strong>Delete</strong>: Permanently remove the user account from the system.</li>
            </ul>
        </div>
    </div>
</div>

<?php include_once("footer.php"); ?>

<script>
// Confirm before taking action
$(document).ready(function() {
    $('.btn-danger').click(function() {
        return confirm('Are you sure you want to delete this user? This action cannot be undone.');
    });
    
    $('.btn-success').click(function() {
        return confirm('Are you sure you want to activate this user?');
    });
});
</script>
