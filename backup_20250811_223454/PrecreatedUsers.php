<?php
$title = "Manage Pre-created Users | SLGTI";
include_once("config.php");
include_once("head.php");
include_once("menu.php");

// Function to parse CSV file
function parseImportFile($file_path, $file_extension) {
    $data = [];
    $handle = null;
    
    // Check if it's an Excel file
    $isExcel = in_array(strtolower($file_extension), ['xls', 'xlsx']);
    if ($isExcel) {
        throw new Exception("Excel files (.xls, .xlsx) are not directly supported. Please save your file as CSV (Comma Delimited) before uploading.");
    }
    
    // Only process CSV files
    if (strtolower($file_extension) !== 'csv') {
        throw new Exception("Only CSV files are supported. Please save your file as CSV (Comma Delimited) before uploading.");
    }
    
    try {
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            throw new Exception("Failed to open file. Please make sure the file is not open in another program.");
        }
        
        // Skip BOM if present
        $bom = "\xef\xbb\xbf";
        if (fgets($handle, 4) !== $bom) {
            rewind($handle);
        }
        
        $row_number = 0;
        $header = [];
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($row, function($value) { 
                return $value !== null && $value !== ''; 
            }))) {
                continue;
            }
            
            // Validate username (first column)
            if (empty($row[0]) || !is_string($row[0])) {
                throw new Exception("Row $row_number: Username is required and must be text");
            }
            
            // Clean and validate data
            $username = trim($row[0]);
            
            // Skip if username is invalid
            if (empty($username)) {
                continue;
            }
            
            // Add to data array
            $data[] = [
                'username' => $username,
                'email' => isset($row[1]) ? trim($row[1]) : '',
                'full_name' => isset($row[2]) ? trim($row[2]) : '',
                'department' => isset($row[3]) ? trim($row[3]) : '',
                'position' => isset($row[4]) ? trim($row[4]) : '',
                'phone_no' => isset($row[5]) ? trim($row[5]) : ''
            ];
        }
        
        if (empty($data)) {
            throw new Exception("No valid data found in the file. Please check that the file is not empty and contains at least a username column.");
        }
        
        return $data;
        
    } catch (Exception $e) {
        throw $e;
    } finally {
        if ($handle !== null && is_resource($handle)) {
            fclose($handle);
        }
    }
}

// Check admin permissions - Add your own permission check here
// if (!isAdmin()) { header('Location: index.php'); exit; }

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new pre-created user
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $department = trim($_POST['department']);
        $position = trim($_POST['position']);
        
        // Validate input
        if (empty($username)) {
            $message = '<div class="alert alert-danger">Username is required.</div>';
        } else {
            // Check if username already exists
            $check_sql = "SELECT id FROM precreated_users WHERE username = ?";
            $stmt = $con->prepare($check_sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $message = '<div class="alert alert-warning">Username already exists in pre-created users.</div>';
            } else {
                // Insert new pre-created user
                $phone_no = trim($_POST['phone_no']);
                $sql = "INSERT INTO precreated_users (username, email, full_name, department, position, phone_no) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("ssssss", $username, $email, $full_name, $department, $position, $phone_no);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">User added to pre-created list successfully.</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error adding user: ' . $con->error . '</div>';
                }
            }
        }
    } elseif (isset($_POST['import_users'])) {
        // Handle Excel/CSV import
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $error = $_FILES['import_file']['error'] ?? 'Unknown error';
            $message = '<div class="alert alert-danger">Error uploading file. Please try again. (Error: ' . $error . ')</div>';
        } else {
            $file = $_FILES['import_file']['tmp_name'];
            $original_name = $_FILES['import_file']['name'];
            $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            
            $allowed_extensions = ['xls', 'xlsx', 'csv'];
            if (!in_array($file_extension, $allowed_extensions)) {
                $message = '<div class="alert alert-danger">Invalid file format. Please upload Excel (.xls, .xlsx) or CSV files only.</div>';
            } else {
                try {
                    // Validate file size (max 5MB)
                    if ($_FILES['import_file']['size'] > 5 * 1024 * 1024) {
                        throw new Exception("File size is too large. Maximum allowed size is 5MB.");
                    }
                    
                    // Parse the file
                    $users = parseImportFile($file, $file_extension);
                    
                    if (empty($users)) {
                        throw new Exception("No valid data found in the file.");
                    }
                    
                    $imported = 0;
                    $skipped = [];
                    $row_number = 1; // Start counting after header
                    
                    // Start transaction
                    $con->begin_transaction();
                    
                    foreach ($users as $user_data) {
                        $row_number++;
                        $username = $user_data['username'];
                        $email = $user_data['email'];
                        $full_name = $user_data['full_name'];
                        $department = $user_data['department'];
                        $position = $user_data['position'];
                        
                        // Validate username
                        if (empty($username)) {
                            $skipped[] = "Row " . ($imported + count($skipped) + 1) . ": Username is required";
                            continue;
                        }
                        
                        // Check if username already exists
                        $check_sql = "SELECT id FROM precreated_users WHERE username = ?";
                        $stmt = $con->prepare($check_sql);
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        
                        if ($stmt->get_result()->num_rows > 0) {
                            $skipped[] = "Username '$username' already exists";
                            continue;
                        }
                        
                        // Get phone number from imported data
                        $phone_no = $user_data['phone_no'] ?? '';
                        
                        // Insert new pre-created user with phone number
                        $sql = "INSERT INTO precreated_users (username, email, full_name, department, position, phone_no) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param("ssssss", $username, $email, $full_name, $department, $position, $phone_no);
                        
                        if ($stmt->execute()) {
                            $imported++;
                        } else {
                            $skipped[] = "Error importing '$username': " . $con->error;
                        }
                    }
                    
                    // Commit transaction
                    $con->commit();
                    
                    // Prepare success message
                    $message = '<div class="alert alert-success">';
                    $message .= "Successfully imported $imported users.<br>";
                    
                    if (!empty($skipped)) {
                        $message .= count($skipped) . ' entries were skipped:<br>';
                        $message .= '<ul><li>' . implode('</li><li>', array_slice($skipped, 0, 10)) . '</li>';
                        if (count($skipped) > 10) {
                            $message .= '<li>... and ' . (count($skipped) - 10) . ' more</li>';
                        }
                        $message .= '</ul>';
                    }
                    $message .= '</div>';
                    
                } catch (Exception $e) {
                    if (isset($con) && $con) {
                        $con->rollback();
                    }
                    $message = '<div class="alert alert-danger">Error importing file: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete pre-created user
        $user_id = (int)$_POST['user_id'];
        $sql = "DELETE FROM precreated_users WHERE id = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">User removed from pre-created list.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error removing user: ' . $con->error . '</div>';
        }
    }
}

// Get all pre-created users
$users = [];
$sql = "SELECT * FROM precreated_users ORDER BY is_used, created_at DESC";
if ($result = $con->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2><i class="fas fa-users-cog"></i> Manage Pre-created Users</h2>
            <hr>
            
            <?php echo $message; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add New Pre-created User</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single" type="button" role="tab" aria-controls="single" aria-selected="true">Single Entry</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk" type="button" role="tab" aria-controls="bulk" aria-selected="false">Bulk Import</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="userTabsContent">
                        <!-- Single Entry Form -->
                        <div class="tab-pane fade show active" id="single" role="tabpanel" aria-labelledby="single-tab">
                            <form method="post" action="" class="row g-3">
                                <div class="col-md-3">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                                <div class="col-md-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name">
                                </div>
                                <div class="col-md-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="department" name="department">
                                </div>
                                <div class="col-md-2">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="position" name="position">
                                </div>
                                <div class="col-md-2">
                                    <label for="phone_no" class="form-label">Phone No</label>
                                    <input type="tel" class="form-control" id="phone_no" name="phone_no" pattern="[0-9+\-() ]{10,20}" title="Enter a valid phone number">
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="add_user" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add User
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Bulk Import Form -->
                        <div class="tab-pane fade" id="bulk" role="tabpanel" aria-labelledby="bulk-tab">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Import Instructions</h5>
                                <p>Upload an Excel (.xls, .xlsx) or CSV file with the following columns in order:</p>
                                <ol>
                                    <li><strong>Username</strong> (required)</li>
                                    <li>Email</li>
                                    <li>Full Name</li>
                                    <li>Department</li>
                                    <li>Position</li>
                                    <li>Phone No (optional)</li>
                                </ol>
                                <p class="mb-0">The first row will be skipped as it's assumed to be a header row.</p>
                            </div>
                            
                            <form method="post" action="" enctype="multipart/form-data" class="row g-3">
                                <div class="col-md-8">
                                    <label for="import_file" class="form-label">Select File</label>
                                    <input type="file" class="form-control" id="import_file" name="import_file" accept=".xls,.xlsx,.csv" required>
                                    <div class="form-text">Supported formats: .xls, .xlsx, .csv</div>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" name="import_users" class="btn btn-success">
                                        <i class="fas fa-file-import"></i> Import Users
                                    </button>
                                    <a href="sample_users_import.csv" class="btn btn-outline-secondary ms-2" download>
                                        <i class="fas fa-download"></i> Download Sample CSV File
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Pre-created Users List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Phone No</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">No pre-created users found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="<?php echo $user['is_used'] ? 'table-secondary' : ''; ?>">
                                            <td><?php echo $user['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($user['username']); ?>
                                                <?php if (!empty($user['email'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($user['position'] ?? '-'); ?></td>
                                            <td><?php echo !empty($user['phone_no']) ? htmlspecialchars($user['phone_no']) : '-'; ?></td>
                                            <td>
                                                <?php if ($user['is_used']): ?>
                                                    <span class="badge badge-danger">Used</span>
                                                    <?php if ($user['used_at']): ?>
                                                        <br><small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($user['used_at'])); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Available</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <?php if (!$user['is_used']): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this pre-created user?');">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled title="Cannot delete used account">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once("footer.php"); ?>
