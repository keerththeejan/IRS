<!------START DON'T CHANGE ORDER HEAD,MANU,FOOTER----->
<!---BLOCK 01--->
<?php 
   
include_once("config.php");

$title = "STUDENT MANAGEMENT | SLGTI"; //YOUR HEAD TITLE CREATE VARIABLE BEFORE FILE NAME
include_once("head.php");
include_once("menu.php");

// Initialize variables
$stid = $title = $fname = $ininame = $gender = $civil = $email = $nic = $dob = $phone = $address = $zip = $district = $division = $province = $blood = $mode =
$ename = $eaddress = $ephone = $erelation = $enstatus = $coid = $year = $enroll = $exit = $qutype = $index = $yoe = $subject = $results = $status = $id = null;
?>

<!-- Custom CSS -->
<style>
    .student-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
        border-radius: 10px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    .student-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .card-header {
        background: linear-gradient(135deg, #4e54c8, #8f94fb);
        color: white;
        font-weight: 600;
        border-bottom: none;
    }
    .search-box {
        position: relative;
    }
    .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }
    .search-box .form-control {
        padding-left: 40px;
        border-radius: 20px;
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 10px;
        font-weight: 500;
    }
    .action-buttons .btn {
        margin: 0 2px;
        border-radius: 20px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    .student-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 10px;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?php echo (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'STU') ? 'My Profile' : 'Student Management'; ?></h1>
            <?php if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'STU'): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Students</li>
                </ol>
            </nav>
            <?php endif; ?>
        </div>
        <?php if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'STU'): ?>
        <a href="AddStudent.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>Add New Student
        </a>
        <?php endif; ?>
    </div>

    <?php if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'STU'): ?>
    <!-- Search and Filter Card - Only for Staff/Admin -->
    <div class="card student-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-search me-2"></i>Search & Filter
            </div>
            <button class="btn btn-sm btn-link text-white" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="card-body collapse show" id="filterCollapse">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Student</label>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="studentSearch" name="search_term" placeholder="Search by ID or name..." value="<?php echo isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Student ID</label>
                    <select name="student_id" id="student_id" class="form-select">
                        <option value="">All Students</option>
                        <?php
                        $sql = "SELECT * FROM `student` ORDER BY `student_id` DESC";
                        $result = mysqli_query($con, $sql);
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $selected = (isset($_GET['student_id']) && $_GET['student_id'] == $row["student_id"]) ? 'selected' : '';
                                echo '<option value="'.$row["student_id"].'" '.$selected.'>'.$row["student_id"].' - '.$row["student_fullname"].'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Status</option>
                        <?php
                        $sql = "SELECT DISTINCT `student_enroll_status` FROM `student_enroll`";
                        $result = mysqli_query($con, $sql);
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $selected = (isset($_GET['status']) && $_GET['status'] == $row["student_enroll_status"]) ? 'selected' : '';
                                echo '<option value="'.$row["student_enroll_status"].'" '.$selected.'>'.ucfirst($row["student_enroll_status"]).'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2" name="search">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                    <a href="Student.php" class="btn btn-outline-secondary">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Students Table -->
    <div class="card student-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-users me-2"></i>Students List
            </div>
            <div class="d-flex">
                <button class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <button class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-download me-1"></i> Export
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="studentsTable" class="table table-hover" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Course</th>
                            <th>Status</th>
                            <?php if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'STU'): ?>
                            <th class="text-end">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // For specific student view (ID: 80 - keerththeejan)
                        $specificStudentId = '80'; // Hardcoded student ID for keerththeejan
                        
                        // Build the query to get specific student's status
                        $sql = "SELECT s.*, 
                               (SELECT student_enroll_status FROM student_enroll WHERE student_id = s.student_id ORDER BY student_enroll_date DESC LIMIT 1) as status
                               FROM student s 
                               WHERE s.student_id = '$specificStudentId'";
                        
                        // Apply search filters
                        $search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';
                        $student_id = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';
                        $status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
                        
                        if (!empty($search_term)) {
                            $search_term = mysqli_real_escape_string($con, $search_term);
                            $sql .= " AND (s.student_fullname LIKE '%$search_term%' OR s.student_id LIKE '%$search_term%' OR s.student_nic LIKE '%$search_term%')";
                        }
                        
                        if (!empty($student_id)) {
                            $student_id = mysqli_real_escape_string($con, $student_id);
                            $sql .= " AND s.student_id = '$student_id'";
                        }
                        
                        if (!empty($status_filter)) {
                            $status_filter = mysqli_real_escape_string($con, $status_filter);
                            $sql .= " AND s.student_id IN (SELECT student_id FROM student_enroll WHERE student_enroll_status = '$status_filter')";
                        }
                        
                        // Default sorting
                        $sql .= " ORDER BY s.student_id DESC";
                        
                        $result = mysqli_query($con, $sql);
                        $count = 1;
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $status = !empty($row['status']) ? $row['status'] : 'Inactive';
                                $statusClass = ($status == 'Active' || $status == 'Following') ? 'bg-success' : 'bg-warning';
                                
                                echo '<tr>';
                                echo '<td>'.$count++.'</td>';
                                
                                // Student name with avatar
                                echo '<td class="d-flex align-items-center">';
                                echo '<img src="'.(!empty($row['student_profile_img']) ? $row['student_profile_img'] : 'assets/img/default-avatar.png').'" class="student-avatar" alt="Student Avatar">';
                                echo '<div>';
                                echo '<div class="fw-semibold">'.$row["student_fullname"].'</div>';
                                echo '<small class="text-muted">'.$row["student_id"].'</small>';
                                echo '</div>';
                                echo '</td>';
                                
                                echo '<td>'.$row["student_id"].'</td>';
                                echo '<td><a href="mailto:'.$row["student_email"].'" class="text-primary">'.$row["student_email"].'</a></td>';
                                echo '<td>'.$row["student_nic"].'</td>';
                                echo '<td><a href="tel:'.$row["student_phone"].'" class="text-primary">'.$row["student_phone"].'</a></td>';
                                echo '<td><span class="status-badge '.$statusClass.'">'.$status.'</span></td>';
                                
                                // Action buttons - Only show for non-student users
                                if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'STU') {
                                    echo '<td class="text-end">';
                                    echo '<div class="action-buttons">';
                                    echo '<a href="Student_profile.php?Sid='.$row["student_id"].'" class="btn btn-sm btn-outline-primary" title="View">';
                                    echo '<i class="fas fa-eye"></i>';
                                    echo '</a>';
                                    
                                    echo '<a href="AddStudent.php?edit='.$row["student_id"].'" class="btn btn-sm btn-outline-success" title="Edit">';
                                    echo '<i class="fas fa-edit"></i>';
                                    echo '</a>';
                                    
                                    echo '<div class="dropdown d-inline-block">';
                                    echo '<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">';
                                    echo '<i class="fas fa-ellipsis-v"></i>';
                                    echo '</button>';
                                    echo '<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">';
                                    echo '<li><a class="dropdown-item" href="#"><i class="fas fa-id-card me-2"></i>View ID Card</a></li>';
                                    echo '<li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2"></i>Generate Report</a></li>';
                                    echo '<li><hr class="dropdown-divider"></li>';
                                    echo '<li><a class="dropdown-item text-danger" href="StudentDelete.php?student_id='.$row["student_id"].'" onclick="return confirm(\'Are you sure you want to delete this student?\')">';
                                    echo '<i class="fas fa-trash me-2"></i>Delete';
                                    echo '</a></li>';
                                    
                                    // Close all the HTML tags
                                    echo '</ul>';  // Close dropdown-menu
                                    echo '</div>'; // Close dropdown
                                    echo '</div>'; // Close action-buttons
                                    echo '</td>';  // Close td
                                }
                                            </ul>
                                        </div> 
                                    </div> 
                                </td>
                                <?php endif; ?>
                                echo '<li><hr class="dropdown-divider"></li>';
                                echo '<li><a class="dropdown-item text-danger" href="StudentDelete.php?student_id='.$row["student_id"].'" onclick="return confirm(\'Are you sure you want to delete this student?\')">';
                                echo '<i class="fas fa-trash me-2"></i>Delete';
                                echo '</a></li>';
                                echo '</ul>';
                                echo '</div>'; // dropdown
                                
                                echo '</div>'; // action-buttons
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="8" class="text-center py-4">No students found. Try adjusting your search criteria.</td></tr>';
                        }
                        ?>
                    </tbody>
    </table>
        </div>
    </div>
    
    <!-- Required JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#studentsTable').DataTable({
            responsive: true,
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search students...",
                lengthMenu: "Show _MENU_ students per page",
                zeroRecords: "No matching students found",
                info: "Showing _START_ to _END_ of _TOTAL_ students",
                infoEmpty: "No students available",
                infoFiltered: "(filtered from _MAX_ total students)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            columnDefs: [
                { orderable: false, targets: [0, 7] } // Disable sorting on # and Actions columns
            ],
            order: [[2, 'asc']] // Default sort by Student ID
        });

        // Add search functionality to the custom search box
        $('#studentSearch').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Handle print button
        $('.btn-print').on('click', function() {
            window.print();
        });

        // Handle export to Excel
        $('.btn-export').on('click', function() {
            // Create a temporary table for export
            var data = [];
            var headers = [];
            
            // Get headers
            $('#studentsTable thead th').each(function() {
                headers.push($(this).text().trim());
            });
            data.push(headers);
            
            // Get data rows
            $('#studentsTable tbody tr').each(function() {
                var row = [];
                $(this).find('td').each(function() {
                    row.push($(this).text().trim());
                });
                data.push(row);
            });
            
            // Convert to CSV
            var csvContent = "data:text/csv;charset=utf-8,";
            data.forEach(function(rowArray) {
                var row = rowArray.join(",");
                csvContent += row + "\r\n";
            });
            
            // Trigger download
            var encodedUri = encodeURI(csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "students_export_" + new Date().toISOString().slice(0, 10) + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</div>

<!---BLOCK 03--->
<!----DON'T CHANGE THE ORDER--->
<?php 
include_once("footer.php"); 
?>