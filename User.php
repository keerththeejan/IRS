<?php
$title = "User Management | SLGTI";
include_once("config.php");
include_once("head.php");
include_once("menu.php");

// Check if user is admin
if ($_SESSION['user_type'] != 'ADM') {
    header("Location: index.php");
    exit();
}

// Handle user activation/deactivation
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($con, $_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'activate') {
        $sql = "UPDATE user SET user_active = 1 WHERE user_id = '$id'";
        $message = "User activated successfully";
    } elseif ($action == 'deactivate') {
        $sql = "UPDATE user SET user_active = 0 WHERE user_id = '$id'";
        $message = "User deactivated successfully";
    }
    
    if (isset($sql)) {
        if (mysqli_query($con, $sql)) {
            echo "<div class='alert alert-success'>$message</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . mysqli_error($con) . "</div>";
        }
    }
}

// Fetch all users
echo '<div class="container-fluid">';
echo '<h1 class="mt-4">User Management</h1>';
echo '<div class="card mb-4">';
echo '<div class="card-header"><i class="fas fa-users mr-1"></i>User List</div>';
echo '<div class="card-body">';
echo '<div class="table-responsive">';
echo '<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">';
echo '<thead><tr>';
echo '<th>ID</th>';
echo '<th>Username</th>';
echo '<th>Email</th>';
echo '<th>User Type</th>';
echo '<th>Position</th>';
echo '<th>Status</th>';
echo '<th>Actions</th>';
echo '</tr></thead><tbody>';

$sql = "SELECT u.user_id, u.user_name, u.user_email, u.user_table, u.user_active, 
               spt.staff_position_type_name as position
        FROM user u
        LEFT JOIN staff_position_type spt ON u.staff_position_type_id = spt.staff_position_type_id
        ORDER BY u.user_table, u.user_name";
$result = mysqli_query($con, $sql);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['user_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['user_email']) . '</td>';
        echo '<td>' . htmlspecialchars(ucfirst($row['user_table'])) . '</td>';
        echo '<td>' . ($row['user_table'] === 'staff' ? htmlspecialchars($row['position'] ?? 'N/A') : 'N/A') . '</td>';
        
        // Status column with badge
        $status = $row['user_active'] ? 'Active' : 'Inactive';
        $badgeClass = $row['user_active'] ? 'badge-success' : 'badge-secondary';
        echo '<td><span class="badge ' . $badgeClass . '">' . $status . '</span></td>';
        
        // Actions column
        echo '<td>';
        if ($row['user_active']) {
            echo '<a href="?action=deactivate&id=' . $row['user_id'] . '" class="btn btn-sm btn-warning" onclick="return confirm(\'Are you sure you want to deactivate this user?\')">Deactivate</a> ';
        } else {
            echo '<a href="?action=activate&id=' . $row['user_id'] . '" class="btn btn-sm btn-success" onclick="return confirm(\'Are you sure you want to activate this user?\')">Activate</a> ';
        }
        echo '<a href="EditUser.php?id=' . $row['user_id'] . '" class="btn btn-sm btn-primary">Edit</a>';
        echo '</td>';
        
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="text-center">No users found</td></tr>';
}

echo '</tbody></table>';
echo '</div>'; // table-responsive
echo '</div>'; // card-body
echo '</div>'; // card
echo '</div>'; // container-fluid

include_once("footer.php");
?>
