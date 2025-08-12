<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection first
include_once("config.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_name']) || $_SESSION['user_type'] != 'ADM') {
    header("Location: index.php");
    exit();
}

// Include header and sidebar if they exist
$header = file_exists('includes/header.php') ? 'includes/header.php' : '';
$sidebar = file_exists('includes/sidebar.php') ? 'includes/sidebar.php' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Permissions Management - IRS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .permission-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .permission-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .permission-item:last-child {
            border-bottom: none;
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <?php if (!empty($header)) include($header); ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php if (!empty($sidebar)) include($sidebar); ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm me-2" title="Go Back">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="h2 mb-0">User Permissions Management</h1>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">User List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="usersTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>User Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Query to get all users with their details from staff and student tables
                                    $query = "SELECT 
                                                u.user_id, 
                                                u.user_name, 
                                                u.user_email,
                                                u.user_active,
                                                u.user_deleted,
                                                u.user_table,
                                                IFNULL(s.staff_name, st.student_fullname) as full_name,
                                                IFNULL(s.staff_status, st.student_status) as status,
                                                u.user_table as user_type
                                              FROM `user` u
                                              LEFT JOIN staff s ON u.user_name = s.staff_id AND u.user_table = 'staff'
                                              LEFT JOIN student st ON u.user_name = st.student_id AND u.user_table = 'student'
                                              ORDER BY u.user_table, u.user_name";
                                    
                                    $result = $con->query($query);
                                    $count = 1;
                                    
                                    if ($result && $result->num_rows > 0) {
                                        while ($user = $result->fetch_assoc()) {
                                            $statusClass = '';
                                            $statusText = 'Inactive';
                                            
                                            if ($user['user_active'] && !$user['user_deleted']) {
                                                $statusClass = 'bg-success';
                                                $statusText = 'Active';
                                            } elseif ($user['user_deleted']) {
                                                $statusClass = 'bg-danger';
                                                $statusText = 'Deleted';
                                            } elseif (isset($user['status']) && !empty($user['status'])) {
                                                $statusClass = strtolower($user['status']) === 'working' || strtolower($user['status']) === 'active' ? 'bg-success' : 'bg-warning';
                                                $statusText = $user['status'];
                                            }
                                            
                                            echo "<tr>";
                                            echo "<td>" . $count++ . "</td>";
                                            echo "<td>" . htmlspecialchars($user['user_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($user['full_name'] ?? 'N/A') . "</td>";
                                            echo "<td>" . htmlspecialchars(ucfirst($user['user_type'] ?? 'N/A')) . "</td>";
                                            echo "<td><span class='badge {$statusClass}'>" . htmlspecialchars($statusText) . "</span></td>";
                                            echo "<td>";
                                            echo "<button class='btn btn-sm btn-primary edit-permissions' data-userid='" . $user['user_id'] . "' data-username='" . htmlspecialchars($user['user_name']) . "' data-bs-toggle='modal' data-bs-target='#permissionsModal'><i class='fas fa-edit'></i> Edit Permissions</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No users found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Permissions Modal -->
    <div class="modal fade" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionsModalLabel">Edit Permissions for <span id="modalUsername"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="permissionsContainer">
                        <!-- Permissions will be loaded here via AJAX -->
                        <div class="text-center my-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading permissions...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="savePermissions">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Required Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#usersTable').DataTable({
                "pageLength": 10,
                "order": [[3, 'asc'], [1, 'asc']], // Sort by user type then username
                "columnDefs": [
                    { "orderable": false, "targets": [0, 5] } // Disable sorting on # and Actions columns
                ]
            });

            // When edit button is clicked
            $(document).on('click', '.edit-permissions', function() {
                const userId = $(this).data('userid');
                const username = $(this).data('username');
                
                // Set the username in the modal title
                $('#modalUsername').text(username);
                
                // Store the current user ID in a data attribute
                $('#permissionsModal').data('userId', userId);
                
                // Show loading state
                $('#permissionsContainer').html(`
                    <div class="text-center my-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading permissions...</p>
                    </div>
                `);
                
                // Load permissions via AJAX
                $.ajax({
                    url: 'api/get_user_permissions.php',
                    type: 'POST',
                    data: { user_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            renderPermissionsForm(response.permissions);
                        } else {
                            $('#permissionsContainer').html(`
                                <div class="alert alert-danger">
                                    Error loading permissions: ${response.message || 'Unknown error'}
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-3" onclick="$('#permissionsModal').modal('hide')">Close</button>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        $('#permissionsContainer').html(`
                            <div class="alert alert-danger">
                                Error loading permissions. Please try again later.
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-3" onclick="$('#permissionsModal').modal('hide')">Close</button>
                            </div>
                        `);
                    }
                });
            });

            // Save permissions
            $('#savePermissions').on('click', function() {
                const userId = $('#permissionsModal').data('userId');
                const permissions = {};
                
                // Collect all permission checkboxes
                $('.permission-checkbox').each(function() {
                    const permissionName = $(this).data('permission');
                    permissions[permissionName] = $(this).is(':checked') ? 1 : 0;
                });
                
                // Send data to server
                $.ajax({
                    url: 'api/update_user_permissions.php',
                    type: 'POST',
                    data: {
                        user_id: userId,
                        permissions: JSON.stringify(permissions)
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Permissions updated successfully!');
                            $('#permissionsModal').modal('hide');
                        } else {
                            alert('Error updating permissions: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('Error updating permissions. Please try again.');
                    }
                });
            });

            // Function to render permissions form
            function renderPermissionsForm(permissions) {
                let html = `
                    <div class="permission-group mb-4">
                        <h6>User Profile</h6>
                        <div class="permission-item">
                            <span>View Profile</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input permission-checkbox" type="checkbox" data-permission="viewProfile" ${permissions.viewProfile ? 'checked' : ''}>
                            </div>
                        </div>
                        <div class="permission-item">
                            <span>Edit Profile</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input permission-checkbox" type="checkbox" data-permission="editProfile" ${permissions.editProfile ? 'checked' : ''}>
                            </div>
                        </div>
                    </div>
                    
                    <div class="permission-group mb-4">
                        <h6>Notifications</h6>
                        <div class="permission-item">
                            <span>Email Notifications</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input permission-checkbox" type="checkbox" data-permission="emailNotifications" ${permissions.emailNotifications ? 'checked' : ''}>
                            </div>
                        </div>
                    </div>
                    
                    <div class="permission-group">
                        <h6>Academic</h6>
                        <div class="permission-item">
                            <span>Enroll in Modules</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input permission-checkbox" type="checkbox" data-permission="enrollModules" ${permissions.enrollModules ? 'checked' : ''}>
                            </div>
                        </div>
                        <div class="permission-item">
                            <span>View Grades</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input permission-checkbox" type="checkbox" data-permission="viewGrades" ${permissions.viewGrades ? 'checked' : ''}>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#permissionsContainer').html(html);
            }
        });
    </script>
</body>
</html>
