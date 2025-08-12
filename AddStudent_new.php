<?php 
include_once("config.php");
$title ="STUDENTS' REGISTRATION FORM | SLGTI";
include_once("head.php");
include_once("menu.php");
?>

<style>
/* Responsive form styles */
.student-form-card {
    border-radius: 10px;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    margin-bottom: 2rem;
    border: 1px solid #e3e6f0;
}

.student-form-card .card-header {
    background: #4e73df;
    color: white;
    font-weight: 600;
    border-radius: 10px 10px 0 0 !important;
    padding: 1rem 1.25rem;
}

.form-section {
    margin-bottom: 1.5rem;
    padding: 1.25rem;
    background: #f8f9fc;
    border-radius: 8px;
    border-left: 4px solid #4e73df;
}

/* Responsive table */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Form control styling */
.form-control, .form-select {
    border-radius: 0.35rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d3e2;
    font-size: 0.9rem;
    width: 100%;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-section {
        padding: 1rem;
    }
    
    .btn-action {
        width: 100%;
        margin-bottom: 10px;
    }
}
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div class="mb-3 mb-md-0">
            <h1 class="h3 mb-0">Student Registration</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="Student.php">Students</a></li>
                    <li class="breadcrumb-item active"><?php echo isset($_GET['edit']) ? 'Edit' : 'Add'; ?> Student</li>
                </ol>
            </nav>
        </div>
        <div class="d-grid gap-2 d-md-block">
            <a href="Student.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>

    <?php
    // Your existing PHP code for form processing and data retrieval
    $stid = $title = $fname = $ininame = $gender = $civil = $email = $nic = $dob = $phone = $address = $zip = $district = $division = $province = $blood = $mode =
    $ename = $eaddress = $ephone = $erelation = $enstatus = $coid = $year = $enroll = $exit = $qutype = $index = $yoe = $subject = $results = null;
    
    if(isset($_GET['edit'])) {
        $stid = $_GET['edit'];
        // Your existing edit query code here
    }
    ?>

    <form method="POST" class="needs-validation" novalidate>
        <!-- Student ID and Course Section -->
        <div class="card student-form-card mb-4">
            <div class="card-header">
                <i class="fas fa-id-card me-2"></i>Student & Course Information
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="sid" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="sid" name="sid" value="<?php echo $stid; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cid" class="form-label">Course</label>
                        <select name="cid" id="cid" class="form-select" required>
                            <option value="" disabled selected>Select Course</option>
                            <?php
                            $course_query = "SELECT * FROM course";
                            $course_result = mysqli_query($con, $course_query);
                            while($course = mysqli_fetch_assoc($course_result)) {
                                $selected = ($coid == $course['course_id']) ? 'selected' : '';
                                echo "<option value='".$course['course_id']."' $selected>".$course['course_name']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <select name="academic_year" id="academic_year" class="form-select" required>
                            <option value="" disabled selected>Select Year</option>
                            <?php
                            $current_year = date('Y');
                            for($i = $current_year; $i <= ($current_year + 5); $i++) {
                                $selected = ($year == $i) ? 'selected' : '';
                                echo "<option value='$i' $selected>$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="enrolldate" class="form-label">Enroll Date</label>
                        <input type="date" class="form-control" id="enrolldate" name="enrolldate" value="<?php echo $enroll; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="exitdate" class="form-label">Expected Exit Date</label>
                        <input type="date" class="form-control" id="exitdate" name="exitdate" value="<?php echo $exit; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="row mb-4">
            <div class="col-12 text-end">
                <div class="d-grid gap-2 d-md-block">
                    <?php if(isset($_GET['edit'])): ?>
                        <button type="submit" name="Edit" class="btn btn-primary btn-action">
                            <i class="fas fa-save me-2"></i>Update
                        </button>
                        <a href="Student.php" class="btn btn-secondary btn-action">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    <?php else: ?>
                        <button type="submit" name="Submit" class="btn btn-primary btn-action">
                            <i class="fas fa-save me-2"></i>Save
                        </button>
                        <button type="reset" class="btn btn-warning btn-action">
                            <i class="fas fa-undo me-2"></i>Reset
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Add your JavaScript for form validation and dynamic behavior here -->

<?php include_once("footer.php"); ?>
